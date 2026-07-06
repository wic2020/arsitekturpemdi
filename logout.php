<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';

$user = current_user();

if ($user) {
    audit_log(
        (int) $user['id'],
        'logout',
        'users',
        (int) $user['id'],
        'Pengguna logout'
    );
}

logout_user();
set_flash('success', 'Anda telah berhasil logout.');
redirect('login.php');
