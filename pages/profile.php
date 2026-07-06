<?php

declare(strict_types=1);

$profileErrors = [];
$passwordErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'update_profile') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));

        if (mb_strlen($name) < 3 || mb_strlen($name) > 100) {
            $profileErrors[] = 'Nama harus terdiri dari 3–100 karakter.';
        }
        if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $username)) {
            $profileErrors[] = 'Username 3–50 karakter dan hanya boleh berisi huruf, angka, titik, garis bawah, atau strip.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            $profileErrors[] = 'Alamat email tidak valid.';
        }

        if (!$profileErrors) {
            $stmt = db()->prepare(
                'SELECT id FROM users
                 WHERE (username = :username OR email = :email)
                   AND id <> :id AND deleted_at IS NULL
                 LIMIT 1'
            );
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'id' => (int) $user['id'],
            ]);

            if ($stmt->fetch()) {
                $profileErrors[] = 'Username atau email sudah digunakan pengguna lain.';
            }
        }

        if (!$profileErrors) {
            $oldValues = [
                'name' => $user['name'],
                'username' => $user['username'],
                'email' => $user['email'],
            ];
            $newValues = [
                'name' => $name,
                'username' => $username,
                'email' => $email,
            ];

            db()->beginTransaction();
            try {
                $stmt = db()->prepare(
                    'UPDATE users
                     SET name = :name, username = :username, email = :email
                     WHERE id = :id'
                );
                $stmt->execute([
                    'name' => $name,
                    'username' => $username,
                    'email' => $email,
                    'id' => (int) $user['id'],
                ]);
                audit_log(
                    (int) $user['id'],
                    'profile_update',
                    'users',
                    (int) $user['id'],
                    'Pengguna memperbarui profil',
                    $oldValues,
                    $newValues
                );
                db()->commit();
                unset($_SESSION['_current_user']);
                set_flash('success', 'Profil berhasil diperbarui.');
                redirect('index.php?page=profile');
            } catch (Throwable $exception) {
                db()->rollBack();
                error_log('Pembaruan profil gagal: ' . $exception->getMessage());
                $profileErrors[] = 'Profil gagal diperbarui. Silakan coba kembali.';
            }
        }
    } elseif ($action === 'change_password') {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

        $stmt = db()->prepare('SELECT password FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int) $user['id']]);
        $passwordHash = (string) $stmt->fetchColumn();

        if (!password_verify($currentPassword, $passwordHash)) {
            $passwordErrors[] = 'Password saat ini tidak sesuai.';
        }
        if (strlen($newPassword) < 8) {
            $passwordErrors[] = 'Password baru minimal 8 karakter.';
        }
        if ($newPassword !== $passwordConfirmation) {
            $passwordErrors[] = 'Konfirmasi password baru tidak sama.';
        }
        if ($currentPassword !== '' && $currentPassword === $newPassword) {
            $passwordErrors[] = 'Password baru harus berbeda dari password saat ini.';
        }

        if (!$passwordErrors) {
            $stmt = db()->prepare('UPDATE users SET password = :password WHERE id = :id');
            $stmt->execute([
                'password' => password_hash($newPassword, PASSWORD_DEFAULT),
                'id' => (int) $user['id'],
            ]);
            audit_log(
                (int) $user['id'],
                'password_update',
                'users',
                (int) $user['id'],
                'Pengguna mengubah password'
            );
            session_regenerate_id(true);
            set_flash('success', 'Password berhasil diubah.');
            redirect('index.php?page=profile');
        }
    } else {
        set_flash('error', 'Aksi profil tidak valid.');
        redirect('index.php?page=profile');
    }
}

$profileUser = current_user();
$registeredAt = date('d F Y', strtotime($profileUser['created_at']));
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Profil Saya</h1>
    <p class="mt-1 text-sm text-slate-500">Kelola informasi akun dan keamanan password Anda.</p>
</div>

<div class="grid gap-6 xl:grid-cols-[320px_minmax(0,1fr)]">
    <aside class="h-fit rounded-lg border border-slate-200 bg-white p-6 text-center shadow-sm">
        <span class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-slate-900 text-white">
            <i data-lucide="user-round" class="h-8 w-8"></i>
        </span>
        <h2 class="mt-4 text-lg font-bold text-slate-900"><?= e($profileUser['name']) ?></h2>
        <p class="mt-1 text-sm text-slate-500">@<?= e($profileUser['username']) ?></p>
        <span class="mt-4 inline-flex rounded-full bg-red-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-red-700 ring-1 ring-red-100">
            <?= e(role_label($profileUser['role'])) ?>
        </span>
        <dl class="mt-6 space-y-3 border-t border-slate-100 pt-5 text-left text-sm">
            <div class="flex items-center justify-between gap-3">
                <dt class="text-slate-500">Status</dt>
                <dd class="font-medium text-emerald-700">Aktif</dd>
            </div>
            <div class="flex items-center justify-between gap-3">
                <dt class="text-slate-500">Terdaftar</dt>
                <dd class="font-medium text-slate-700"><?= e($registeredAt) ?></dd>
            </div>
        </dl>
    </aside>

    <div class="space-y-6">
        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                <h2 class="font-semibold text-slate-900">Informasi Profil</h2>
                <p class="mt-1 text-sm text-slate-500">Informasi ini digunakan untuk mengenali akun Anda.</p>
            </div>

            <form method="post" class="space-y-5 p-5 sm:p-6" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_profile">

                <?php if ($profileErrors): ?>
                    <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                        <?= e($profileErrors[0]) ?>
                    </div>
                <?php endif; ?>

                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700">Nama lengkap</label>
                    <input id="name" name="name" type="text" maxlength="100" value="<?= e(($_POST['action'] ?? '') === 'update_profile' ? ($_POST['name'] ?? '') : $profileUser['name']) ?>" class="mt-2 w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100" required>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="username" class="block text-sm font-medium text-slate-700">Username</label>
                        <input id="username" name="username" type="text" maxlength="50" value="<?= e(($_POST['action'] ?? '') === 'update_profile' ? ($_POST['username'] ?? '') : $profileUser['username']) ?>" class="mt-2 w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100" required>
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                        <input id="email" name="email" type="email" maxlength="150" value="<?= e(($_POST['action'] ?? '') === 'update_profile' ? ($_POST['email'] ?? '') : $profileUser['email']) ?>" class="mt-2 w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100" required>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-md bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">
                        <i data-lucide="save" class="h-4 w-4"></i>
                        Simpan Profil
                    </button>
                </div>
            </form>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                <h2 class="font-semibold text-slate-900">Ubah Password</h2>
                <p class="mt-1 text-sm text-slate-500">Gunakan minimal 8 karakter dan jangan memakai password lama.</p>
            </div>

            <form method="post" class="space-y-5 p-5 sm:p-6" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="change_password">

                <?php if ($passwordErrors): ?>
                    <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                        <?= e($passwordErrors[0]) ?>
                    </div>
                <?php endif; ?>

                <div>
                    <label for="current_password" class="block text-sm font-medium text-slate-700">Password saat ini</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password" class="mt-2 w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100" required>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="new_password" class="block text-sm font-medium text-slate-700">Password baru</label>
                        <input id="new_password" name="new_password" type="password" autocomplete="new-password" minlength="8" class="mt-2 w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100" required>
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Konfirmasi password baru</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" class="mt-2 w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100" required>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-md bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                        <i data-lucide="key-round" class="h-4 w-4"></i>
                        Ubah Password
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>
