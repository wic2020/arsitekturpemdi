<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';

guest_only();

$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $username)) {
        $errors[] = 'Username atau password salah.';
    }

    if ($password === '') {
        $errors[] = 'Username atau password salah.';
    }

    if (!$errors) {
        $stmt = db()->prepare(
            'SELECT id, name, username, email, password, role, is_active
             FROM users
             WHERE username = :username AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && (bool) $user['is_active'] && password_verify($password, (string) $user['password'])) {
            login_user($user);

            db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id')
                ->execute(['id' => (int) $user['id']]);

            audit_log(
                (int) $user['id'],
                'login',
                'users',
                (int) $user['id'],
                'Pengguna berhasil login'
            );
            redirect('index.php');
        }

        audit_log(
            $user ? (int) $user['id'] : null,
            'login_failed',
            'users',
            $user ? (int) $user['id'] : null,
            'Percobaan login gagal untuk username: ' . $username
        );
        $errors[] = 'Username atau password salah.';
    }
}

$flash = flash();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Login - <?= e(APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="flex min-h-screen items-center justify-center px-4 py-8">
        <section class="w-full max-w-md overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <div class="flex items-center gap-3">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-md bg-slate-950 text-base font-black text-white">SP</span>
                    <div>
                        <h1 class="text-lg font-bold text-slate-900">Masuk Aplikasi</h1>
                        <p class="mt-1 text-sm text-slate-500"><?= e(APP_NAME) ?></p>
                    </div>
                </div>
            </div>

            <form method="post" class="space-y-5 px-6 py-6" novalidate>
                <?= csrf_field() ?>

                <?php if ($flash): ?>
                    <div class="rounded-md border px-4 py-3 text-sm <?= $flash['type'] === 'error' ? 'border-red-200 bg-red-50 text-red-700' : 'border-green-200 bg-green-50 text-green-700' ?>" role="alert">
                        <?= e($flash['message']) ?>
                    </div>
                <?php endif; ?>

                <?php if ($errors): ?>
                    <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                        <?= e($errors[0]) ?>
                    </div>
                <?php endif; ?>

                <div>
                    <label for="username" class="block text-sm font-medium text-slate-700">Username</label>
                    <div class="relative mt-2">
                        <i data-lucide="user-round" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input id="username" name="username" type="text" autocomplete="username" value="<?= e($username) ?>" maxlength="50" class="w-full rounded-md border border-slate-300 py-2.5 pl-10 pr-3 text-sm outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100" required autofocus>
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                    <div class="relative mt-2">
                        <i data-lucide="lock-keyhole" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input id="password" name="password" type="password" autocomplete="current-password" class="w-full rounded-md border border-slate-300 py-2.5 pl-10 pr-3 text-sm outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100" required>
                    </div>
                </div>

                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-md bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2">
                    <i data-lucide="log-in" class="h-4 w-4"></i>
                    Login
                </button>

                <p class="text-center text-xs text-slate-500">
                    Akun pengguna dibuat oleh administrator.
                </p>
            </form>
        </section>
    </main>

    <script>
        if (window.lucide) {
            lucide.createIcons();
        }
    </script>
</body>
</html>
