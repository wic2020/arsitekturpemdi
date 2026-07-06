<?php

declare(strict_types=1);

$counts = db()->query(
    'SELECT
        (SELECT COUNT(*) FROM rab) AS rab,
        (SELECT COUNT(*) FROM ral) AS ral,
        (SELECT COUNT(*) FROM rad) AS rad,
        (SELECT COUNT(*) FROM raa) AS raa,
        (SELECT COUNT(*) FROM rai) AS rai,
        (SELECT COUNT(*) FROM rak) AS rak,
        (SELECT COUNT(*) FROM program) AS program,
        (SELECT COUNT(*) FROM skpd) AS skpd'
)->fetch();

$domains = [
    ['key' => 'rab', 'code' => 'RAB', 'name' => 'Proses Bisnis', 'icon' => 'workflow', 'color' => 'blue'],
    ['key' => 'ral', 'code' => 'RAL', 'name' => 'Layanan', 'icon' => 'handshake', 'color' => 'emerald'],
    ['key' => 'rad', 'code' => 'RAD', 'name' => 'Data', 'icon' => 'database', 'color' => 'violet'],
    ['key' => 'raa', 'code' => 'RAA', 'name' => 'Aplikasi', 'icon' => 'app-window', 'color' => 'amber'],
    ['key' => 'rai', 'code' => 'RAI', 'name' => 'Infrastruktur', 'icon' => 'server', 'color' => 'cyan'],
    ['key' => 'rak', 'code' => 'RAK', 'name' => 'Keamanan', 'icon' => 'shield-check', 'color' => 'rose'],
];

$colorClasses = [
    'blue' => 'bg-blue-50 text-blue-700 ring-blue-100',
    'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
    'violet' => 'bg-violet-50 text-violet-700 ring-violet-100',
    'amber' => 'bg-amber-50 text-amber-700 ring-amber-100',
    'cyan' => 'bg-cyan-50 text-cyan-700 ring-cyan-100',
    'rose' => 'bg-rose-50 text-rose-700 ring-rose-100',
];

$totalReferences = array_sum(array_map(
    static fn (array $domain): int => (int) $counts[$domain['key']],
    $domains
));

$recentSql = $user['role'] === 'admin'
    ? 'SELECT a.action, a.description, a.created_at, u.name
       FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
       ORDER BY a.id DESC LIMIT 5'
    : 'SELECT a.action, a.description, a.created_at, u.name
       FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
       WHERE a.user_id = :user_id
       ORDER BY a.id DESC LIMIT 5';
$recentStmt = db()->prepare($recentSql);
$recentStmt->execute($user['role'] === 'admin' ? [] : ['user_id' => (int) $user['id']]);
$recentActivities = $recentStmt->fetchAll();

function dashboard_action_label(string $action): string
{
    return match ($action) {
        'login' => 'Login',
        'logout' => 'Logout',
        'login_failed' => 'Login gagal',
        'password_update' => 'Ubah password',
        'profile_update' => 'Ubah profil',
        'create' => 'Tambah data',
        'update' => 'Ubah data',
        'delete' => 'Hapus data',
        default => ucwords(str_replace('_', ' ', $action)),
    };
}
?>

<div class="mb-6">
    <p class="text-sm font-medium text-red-700">Selamat datang, <?= e($user['name']) ?></p>
    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Dashboard Arsitektur SPBE</h1>
    <p class="mt-1 text-sm text-slate-500">Ringkasan data referensi dan aktivitas sistem.</p>
</div>

<div class="mb-6 grid gap-4 sm:grid-cols-3">
    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500">Total Referensi</p>
                <p class="mt-2 text-3xl font-bold text-slate-900"><?= number_format($totalReferences, 0, ',', '.') ?></p>
            </div>
            <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-slate-900 text-white">
                <i data-lucide="layers-3" class="h-5 w-5"></i>
            </span>
        </div>
    </section>
    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500">Program</p>
                <p class="mt-2 text-3xl font-bold text-slate-900"><?= number_format((int) $counts['program'], 0, ',', '.') ?></p>
            </div>
            <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-red-50 text-red-700 ring-1 ring-red-100">
                <i data-lucide="clipboard-list" class="h-5 w-5"></i>
            </span>
        </div>
    </section>
    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500">Unit Kerja</p>
                <p class="mt-2 text-3xl font-bold text-slate-900"><?= number_format((int) $counts['skpd'], 0, ',', '.') ?></p>
            </div>
            <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-50 text-blue-700 ring-1 ring-blue-100">
                <i data-lucide="building-2" class="h-5 w-5"></i>
            </span>
        </div>
    </section>
</div>

<section class="mb-6 rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 px-5 py-4">
        <h2 class="font-semibold text-slate-900">Referensi Arsitektur</h2>
        <p class="mt-1 text-sm text-slate-500">Jumlah data pada enam domain referensi SPBE.</p>
    </div>
    <div class="grid gap-px overflow-hidden rounded-b-lg bg-slate-200 sm:grid-cols-2 xl:grid-cols-3">
        <?php foreach ($domains as $domain): ?>
            <article class="flex items-center gap-4 bg-white p-5">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg ring-1 <?= e($colorClasses[$domain['color']]) ?>">
                    <i data-lucide="<?= e($domain['icon']) ?>" class="h-5 w-5"></i>
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400"><?= e($domain['code']) ?></p>
                    <p class="truncate font-semibold text-slate-800"><?= e($domain['name']) ?></p>
                </div>
                <p class="ml-auto text-xl font-bold text-slate-900"><?= number_format((int) $counts[$domain['key']], 0, ',', '.') ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 px-5 py-4">
        <h2 class="font-semibold text-slate-900">Aktivitas Terbaru</h2>
        <p class="mt-1 text-sm text-slate-500"><?= $user['role'] === 'admin' ? 'Aktivitas terbaru seluruh pengguna.' : 'Aktivitas terbaru akun Anda.' ?></p>
    </div>
    <?php if (!$recentActivities): ?>
        <div class="px-5 py-10 text-center text-sm text-slate-500">Belum ada aktivitas tercatat.</div>
    <?php else: ?>
        <div class="divide-y divide-slate-100">
            <?php foreach ($recentActivities as $activity): ?>
                <div class="flex items-start gap-3 px-5 py-4">
                    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                        <i data-lucide="history" class="h-4 w-4"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-slate-800">
                            <?= e(dashboard_action_label($activity['action'])) ?>
                            <span class="font-normal text-slate-500">oleh <?= e($activity['name'] ?? 'Sistem') ?></span>
                        </p>
                        <p class="mt-0.5 truncate text-xs text-slate-500"><?= e($activity['description'] ?: 'Aktivitas sistem') ?></p>
                    </div>
                    <time class="shrink-0 text-xs text-slate-400"><?= e(date('d/m/Y H:i', strtotime($activity['created_at']))) ?></time>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
