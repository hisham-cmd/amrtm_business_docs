<?php
/**
 * prod-db.php — أداة قراءة قاعدة بيانات الإنتاج.
 *
 * ⚠️ ملف مؤقت للاستخدام الإداري فقط.
 *    - يقرأ بيانات الاتصال من .env على السيرفر (لا يحوي أي سرّ)
 *    - قراءة فقط: لا يقبل أي UPDATE / DELETE / INSERT
 *    - يتطلب رمزاً سرياً في الرابط
 *    - يحذف نفسه تلقائياً بعد usage_limit مرات
 *
 * الاستخدام:  https://<site>/backend/prod-db.php?token=<TOKEN>&do=<command>&limit=20
 */

declare(strict_types=1);

const ACCESS_TOKEN  = 'PUT_YOWN_RANDOM_TOKEN_HERE';
const USAGE_LIMIT   = 20;   // يحذف نفسه بعد هذا العدد من الاستدعاءات
const MAX_LIMIT     = 200;

// ── الحماية: طريقة الطلب ──
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    exit(json_out(['error' => 'GET فقط']));
}

// ── الحماية: الرمز السري ──
$token = $_GET['token'] ?? '';
if (! hash_equals(ACCESS_TOKEN, $token)) {
    http_response_code(403);
    exit(json_out(['error' => 'رمز غير صالح']));
}

// ── حد الاستخدام ──
$counterFile = __DIR__ . '/.prod-db-uses';
$uses = is_file($counterFile) ? (int) @file_get_contents($counterFile) : 0;
if ($uses >= USAGE_LIMIT) {
    @unlink(__FILE__);
    @unlink($counterFile);
    http_response_code(410);
    exit(json_out(['error' => 'انتهى عدد الاستعمالات. حُذف الملف تلقائياً.']));
}
@file_put_contents($counterFile, (string) ($uses + 1));

// ── قراءة الإعدادات من .env على السيرفر ──
$envFile = __DIR__ . '/.env';
if (! is_file($envFile)) {
    http_response_code(500);
    exit(json_out(['error' => '.env غير موجود']));
}

$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || ! str_contains($line, '=')) {
        continue;
    }
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim(trim($v), '"\'');
}

$host = $env['DB_HOST']     ?? '';
$port = $env['DB_PORT']     ?? '3306';
$name = $env['DB_DATABASE'] ?? '';
$user = $env['DB_USERNAME'] ?? '';
$pass = $env['DB_PASSWORD'] ?? '';

if ($host === '' || $name === '' || $user === '') {
    http_response_code(500);
    exit(json_out(['error' => 'إعدادات DB ناقصة في .env']));
}

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 15]
    );
} catch (Throwable $e) {
    http_response_code(500);
    exit(json_out(['error' => 'فشل الاتصال: ' . $e->getMessage()]));
}

$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$limit = min(max(1, (int) ($_GET['limit'] ?? 20)), MAX_LIMIT);
$do    = $_GET['do'] ?? 'counts';

/** أوامر القراءة المسموح بها فقط — لا شيء يكتب. */
$commands = [
    'counts' => static function (PDO $pdo) {
        $out = [];
        foreach ([
            'bs_users', 'bs_entities', 'bs_categories', 'bs_services',
            'bs_entity_services', 'bs_service_requests', 'bs_offices',
            'bs_consultants', 'bs_orders', 'bs_payments',
        ] as $t) {
            try {
                $out[$t] = (int) $pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
            } catch (Throwable $e) {
                $out[$t] = 'غير موجود';
            }
        }
        return $out;
    },

    'users' => static function (PDO $pdo) use ($limit) {
        return $pdo->query(
            "SELECT id, name, email, phone, role, account_type, is_active,
                    email_verified_at, nafath_verified_at, created_at
             FROM bs_users ORDER BY id DESC LIMIT {$limit}"
        )->fetchAll();
    },

    'users_by_role' => static function (PDO $pdo) {
        return $pdo->query(
            'SELECT role, account_type, is_active, COUNT(*) AS c
             FROM bs_users GROUP BY role, account_type, is_active'
        )->fetchAll();
    },

    'entities' => static function (PDO $pdo) use ($limit) {
        return $pdo->query(
            "SELECT e.id, e.name_ar, e.images, e.is_active, c.key AS category
             FROM bs_entities e
             LEFT JOIN bs_categories c ON c.id = e.category_id
             ORDER BY e.id DESC LIMIT {$limit}"
        )->fetchAll();
    },

    'services' => static function (PDO $pdo) use ($limit) {
        return $pdo->query(
            "SELECT s.id, s.name_ar, s.price, s.is_active,
                    CHAR_LENGTH(COALESCE(s.custom_fields,'')) AS cf_len,
                    e.name_ar AS entity
             FROM bs_services s
             LEFT JOIN bs_entities e ON e.id = s.entity_id
             ORDER BY s.id DESC LIMIT {$limit}"
        )->fetchAll();
    },

    'sql' => static function (PDO $pdo) use ($limit) {
        // قراءة فقط: نرفض أي كلمة تعديل
        $q = trim((string) ($_GET['q'] ?? 'SELECT 1'));
        $first = strtolower(substr(ltrim($q), 0, 1));
        if ($first !== 's' && $first !== 'w') {
            throw new RuntimeException('مكتوب: SELECT أو WITH فقط');
        }
        foreach (['update', 'delete', 'insert', 'drop', 'alter', 'truncate',
                  'replace', 'create', 'grant', 'revoke'] as $bad) {
            if (preg_match('/\b' . $bad . '\b/i', $q)) {
                throw new RuntimeException("الكلمة المحظورة: {$bad}");
            }
        }
        if (! preg_match('/\blimit\b/i', $q)) {
            $q .= " LIMIT {$limit}";
        }
        return $pdo->query($q)->fetchAll();
    },
];

if (! isset($commands[$do])) {
    http_response_code(400);
    exit(json_out([
        'error'       => 'أمر غير معروف',
        'available'   => array_keys($commands),
        'usage_count' => $uses + 1,
        'limit_left'  => USAGE_LIMIT - ($uses + 1),
    ]));
}

try {
    $result = $commands[$do]($pdo);
    exit(json_out([
        'ok'          => true,
        'command'     => $do,
        'database'    => $name,
        'row_count'   => is_array($result) ? count($result) : 1,
        'data'        => $result,
        'usage_count' => $uses + 1,
        'limit_left'  => USAGE_LIMIT - ($uses + 1),
    ]));
} catch (Throwable $e) {
    http_response_code(500);
    exit(json_out(['error' => $e->getMessage(), 'usage_count' => $uses + 1]));
}

function json_out(array $data): string
{
    return json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
    ) | '';
}
