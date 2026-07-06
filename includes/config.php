<?php

declare(strict_types=1);

/*
 * Konfigurasi inti Sistem Informasi Arsitektur SPBE.
 * Nilai database dapat dioverride melalui environment variable DB_*.
 */

const APP_NAME = 'Sistem Informasi Arsitektur SPBE';
const APP_URL = 'https://superapp.test/app_arsitektur_v2';
const APP_TIMEZONE = 'Asia/Jakarta';

const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'dbarsitektur';
const DB_USER = 'root';
const DB_PASS = '';

date_default_timezone_set(APP_TIMEZONE);

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_name('arsitektur_spbe_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
}

function env_value(string $key, string $default): string
{
    $value = getenv($key);

    return $value === false ? $default : $value;
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = env_value('DB_HOST', DB_HOST);
    $port = env_value('DB_PORT', DB_PORT);
    $name = env_value('DB_NAME', DB_NAME);
    $user = env_value('DB_USER', DB_USER);
    $pass = env_value('DB_PASS', DB_PASS);
    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET time_zone = '+07:00'");
    } catch (PDOException $exception) {
        error_log('Koneksi database gagal: ' . $exception->getMessage());
        http_response_code(500);
        exit('Koneksi database tidak tersedia. Silakan hubungi administrator.');
    }

    return $pdo;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_url(string $path = ''): string
{
    return rtrim(APP_URL, '/') . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

function redirect(string $path): never
{
    $location = preg_match('#^https?://#i', $path) ? $path : app_url($path);
    header('Location: ' . $location);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $submittedToken = (string) ($_POST['_token'] ?? '');

    if ($submittedToken === '' || !hash_equals(csrf_token(), $submittedToken)) {
        http_response_code(419);
        exit('Sesi formulir tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.');
    }
}

function set_flash(string $type, string $message): void
{
    $_SESSION['_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function flash(): ?array
{
    $message = $_SESSION['_flash'] ?? null;
    unset($_SESSION['_flash']);

    return is_array($message) ? $message : null;
}

function pagination_items(int $currentPage, int $totalPages, int $radius = 2): array
{
    if ($totalPages <= 7) {
        return range(1, $totalPages);
    }

    $pages = [1, $totalPages];
    for ($page = max(2, $currentPage - $radius); $page <= min($totalPages - 1, $currentPage + $radius); $page++) {
        $pages[] = $page;
    }
    $pages = array_values(array_unique($pages));
    sort($pages);

    $items = [];
    $previous = 0;
    foreach ($pages as $page) {
        if ($previous > 0 && $page - $previous > 1) $items[] = 'ellipsis';
        $items[] = $page;
        $previous = $page;
    }
    return $items;
}

function render_numbered_pagination(int $currentPage, int $totalPages, callable $urlForPage): void
{
    $buttonClass = 'inline-flex h-8 min-w-8 items-center justify-center rounded-md border px-2 text-xs font-semibold transition';
    $normalClass = $buttonClass . ' border-slate-300 bg-white text-slate-700 hover:bg-slate-50';
    $activeClass = $buttonClass . ' border-blue-700 bg-blue-700 text-white';
    $disabledClass = $buttonClass . ' cursor-not-allowed border-slate-200 bg-slate-50 text-slate-300';
    ?>
    <nav class="flex flex-wrap items-center justify-end gap-1" aria-label="Navigasi halaman">
        <?php if ($currentPage > 1): ?>
            <a href="<?= e($urlForPage(1)) ?>" class="<?= $normalClass ?>" title="Halaman pertama" aria-label="Halaman pertama"><i data-lucide="chevrons-left" class="h-4 w-4"></i></a>
        <?php else: ?>
            <span class="<?= $disabledClass ?>" title="Halaman pertama" aria-disabled="true"><i data-lucide="chevrons-left" class="h-4 w-4"></i></span>
        <?php endif; ?>
        <?php foreach (pagination_items($currentPage, $totalPages) as $item): ?>
            <?php if ($item === 'ellipsis'): ?>
                <span class="inline-flex h-8 min-w-6 items-center justify-center text-xs text-slate-400">...</span>
            <?php elseif ($item === $currentPage): ?>
                <span class="<?= $activeClass ?>" aria-current="page"><?= $item ?></span>
            <?php else: ?>
                <a href="<?= e($urlForPage($item)) ?>" class="<?= $normalClass ?>" aria-label="Halaman <?= $item ?>"><?= $item ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if ($currentPage < $totalPages): ?>
            <a href="<?= e($urlForPage($totalPages)) ?>" class="<?= $normalClass ?>" title="Halaman terakhir" aria-label="Halaman terakhir"><i data-lucide="chevrons-right" class="h-4 w-4"></i></a>
        <?php else: ?>
            <span class="<?= $disabledClass ?>" title="Halaman terakhir" aria-disabled="true"><i data-lucide="chevrons-right" class="h-4 w-4"></i></span>
        <?php endif; ?>
    </nav>
    <?php
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['last_activity'] = time();
    unset($_SESSION['_current_user']);
}

function logout_user(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

function current_user(): ?array
{
    $userId = (int) ($_SESSION['user_id'] ?? 0);

    if ($userId < 1) {
        return null;
    }

    if (isset($_SESSION['_current_user']) && is_array($_SESSION['_current_user'])) {
        return $_SESSION['_current_user'];
    }

    $stmt = db()->prepare(
        'SELECT id, name, username, email, role, is_active, last_login_at, created_at
         FROM users
         WHERE id = :id AND deleted_at IS NULL
         LIMIT 1'
    );
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

    if (!$user || !(bool) $user['is_active']) {
        unset($_SESSION['user_id'], $_SESSION['_current_user']);
        return null;
    }

    $_SESSION['_current_user'] = $user;

    return $user;
}

function require_login(): void
{
    if (!current_user()) {
        set_flash('error', 'Silakan login untuk melanjutkan.');
        redirect('login.php');
    }
}

function guest_only(): void
{
    if (current_user()) {
        redirect('index.php');
    }
}

function require_role(string $role): void
{
    require_login();
    $user = current_user();

    if (!$user || $user['role'] !== $role) {
        http_response_code(403);
        exit('Anda tidak memiliki hak akses untuk halaman ini.');
    }
}

function role_label(string $role): string
{
    return $role === 'admin' ? 'Administrator' : 'Pengguna';
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'CLI'), 0, 45);
}

function audit_log(
    ?int $userId,
    string $action,
    ?string $tableName = null,
    ?int $recordId = null,
    ?string $description = null,
    ?array $oldValues = null,
    ?array $newValues = null,
    bool $throwOnFailure = false
): void {
    try {
        $stmt = db()->prepare(
            'INSERT INTO audit_logs
                (user_id, action, table_name, record_id, description, old_values, new_values, ip_address, user_agent)
             VALUES
                (:user_id, :action, :table_name, :record_id, :description, :old_values, :new_values, :ip_address, :user_agent)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'action' => substr($action, 0, 50),
            'table_name' => $tableName !== null ? substr($tableName, 0, 100) : null,
            'record_id' => $recordId,
            'description' => $description,
            'old_values' => $oldValues !== null
                ? json_encode($oldValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : null,
            'new_values' => $newValues !== null
                ? json_encode($newValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : null,
            'ip_address' => client_ip(),
            'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? 'CLI'), 0, 500),
        ]);
    } catch (Throwable $exception) {
        // Audit tidak boleh membocorkan detail database kepada pengguna.
        error_log('Audit log gagal ditulis: ' . $exception->getMessage());
        if ($throwOnFailure) {
            throw $exception;
        }
    }
}
