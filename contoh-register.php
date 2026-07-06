<?php
require_once __DIR__ . '/includes/config.php';

guest_only();

$errors = [];
$name = '';
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim((string) ($_POST['name'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

    if ($name === '' || strlen($name) < 3) {
        $errors[] = 'Nama minimal 3 karakter.';
    }

    if ($username === '' || !preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $username)) {
        $errors[] = 'Username 3-50 karakter dan hanya boleh huruf, angka, titik, garis bawah, atau strip.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email tidak valid.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password minimal 8 karakter.';
    }

    if ($password !== $passwordConfirmation) {
        $errors[] = 'Konfirmasi password tidak sama.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1');
        $stmt->execute([
            'username' => $username,
            'email' => $email,
        ]);

        if ($stmt->fetch()) {
            $errors[] = 'Username atau email sudah terdaftar.';
        }
    }

    if (!$errors) {
        $stmt = db()->prepare(
            'INSERT INTO users (name, username, email, password, role)
             VALUES (:name, :username, :email, :password, :role)'
        );
        $stmt->execute([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'user',
        ]);

        $userId = (int) db()->lastInsertId();
        audit_log($userId, 'register', 'users', $userId, 'Registrasi akun baru');
        set_flash('success', 'Registrasi berhasil. Silakan login.');
        redirect('login.php');
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - <?= e(APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="flex min-h-screen items-center justify-center px-4 py-8">
        <section class="w-full max-w-md rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <div class="flex items-center gap-3">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-md bg-slate-950 text-base font-black text-white">SP</span>
                    <div>
                        <h1 class="text-lg font-bold text-slate-900">Daftar Akun</h1>
                        <p class="mt-1 text-sm text-slate-500"><?= e(APP_NAME) ?></p>
                    </div>
                </div>
            </div>

            <form method="post" class="space-y-5 px-6 py-6" novalidate>
                <?= csrf_field() ?>

                <?php if ($errors): ?>
                    <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <?= e($errors[0]) ?>
                    </div>
                <?php endif; ?>

                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700">Nama</label>
                    <div class="relative mt-2">
                        <i data-lucide="user-round" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input id="name" name="name" type="text" autocomplete="name" value="<?= e($name) ?>" class="w-full rounded-md border border-slate-300 py-2.5 pl-10 pr-3 text-sm outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100" required autofocus>
                    </div>
                </div>

                <div>
                    <label for="username" class="block text-sm font-medium text-slate-700">Username</label>
                    <div class="relative mt-2">
                        <i data-lucide="at-sign" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input id="username" name="username" type="text" autocomplete="username" value="<?= e($username) ?>" class="w-full rounded-md border border-slate-300 py-2.5 pl-10 pr-3 text-sm outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100" required>
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                    <div class="relative mt-2">
                        <i data-lucide="mail" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input id="email" name="email" type="email" autocomplete="email" value="<?= e($email) ?>" class="w-full rounded-md border border-slate-300 py-2.5 pl-10 pr-3 text-sm outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100" required>
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                    <div class="relative mt-2">
                        <i data-lucide="lock-keyhole" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input id="password" name="password" type="password" autocomplete="new-password" class="w-full rounded-md border border-slate-300 py-2.5 pl-10 pr-3 text-sm outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100" required>
                    </div>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Konfirmasi Password</label>
                    <div class="relative mt-2">
                        <i data-lucide="shield-check" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="w-full rounded-md border border-slate-300 py-2.5 pl-10 pr-3 text-sm outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100" required>
                    </div>
                </div>

                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-md bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">
                    <i data-lucide="user-plus" class="h-4 w-4"></i>
                    Register
                </button>

                <p class="text-center text-sm text-slate-500">
                    Sudah punya akun?
                    <a href="login.php" class="font-semibold text-blue-700 hover:text-blue-800">Login</a>
                </p>
            </form>
        </section>
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
