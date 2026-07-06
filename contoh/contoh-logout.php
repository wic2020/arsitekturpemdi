<?php
require_once dirname(__DIR__) . '/includes/config.php';

$user = current_user();

if ($user) {
    audit_log((int) $user['id'], 'logout', 'users', (int) $user['id'], 'User logout');
}

logout_user();
redirect('contoh-login.php');
