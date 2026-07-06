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
        (SELECT COUNT(*) FROM dab) AS dab,
        (SELECT COUNT(*) FROM dal) AS dal,
        (SELECT COUNT(*) FROM dad) AS dad,
        (SELECT COUNT(*) FROM daa) AS daa,
        (SELECT COUNT(*) FROM dai_fasilitas_komputasi) AS dai_fasilitas_komputasi,
        (SELECT COUNT(*) FROM dai_komputasi_awan) AS dai_komputasi_awan,
        (SELECT COUNT(*) FROM dai_jaringan_intra) AS dai_jaringan_intra,
        (SELECT COUNT(*) FROM dai_software) AS dai_software,
        (SELECT COUNT(*) FROM dai_hardware_server) AS dai_hardware_server,
        (SELECT COUNT(*) FROM dai_hardware_jaringan) AS dai_hardware_jaringan,
        (SELECT COUNT(*) FROM dai_hardware_periferal) AS dai_hardware_periferal,
        (SELECT COUNT(*) FROM dai_hardware_storage) AS dai_hardware_storage,
        (SELECT COUNT(*) FROM dai_hardware_keamanan) AS dai_hardware_keamanan,
        (SELECT COUNT(*) FROM dai_splp) AS dai_splp,
        (SELECT COUNT(*) FROM dak_audit_keamanan) AS dak_audit_keamanan,
        (SELECT COUNT(*) FROM dak_edukasi_kesadaran) AS dak_edukasi_kesadaran,
        (SELECT COUNT(*) FROM dak_identifikasi_kerentanan) AS dak_identifikasi_kerentanan,
        (SELECT COUNT(*) FROM dak_kelaikan_keamanan) AS dak_kelaikan_keamanan,
        (SELECT COUNT(*) FROM dak_penanganan_insiden) AS dak_penanganan_insiden,
        (SELECT COUNT(*) FROM dak_peningkatan_keamanan) AS dak_peningkatan_keamanan,
        (SELECT COUNT(*) FROM dak_standar_keamanan) AS dak_standar_keamanan,
        (SELECT COUNT(*) FROM program) AS program,
        (SELECT COUNT(*) FROM skpd) AS skpd'
)->fetch();

$referenceDomains = [
    ['key' => 'rab', 'code' => 'RAB', 'name' => 'Proses Bisnis', 'icon' => 'workflow', 'color' => 'blue'],
    ['key' => 'ral', 'code' => 'RAL', 'name' => 'Layanan', 'icon' => 'handshake', 'color' => 'emerald'],
    ['key' => 'rad', 'code' => 'RAD', 'name' => 'Data', 'icon' => 'database', 'color' => 'violet'],
    ['key' => 'raa', 'code' => 'RAA', 'name' => 'Aplikasi', 'icon' => 'app-window', 'color' => 'amber'],
    ['key' => 'rai', 'code' => 'RAI', 'name' => 'Infrastruktur', 'icon' => 'server', 'color' => 'cyan'],
    ['key' => 'rak', 'code' => 'RAK', 'name' => 'Keamanan', 'icon' => 'shield-check', 'color' => 'rose'],
];

$architectureDomains = [
    ['key' => 'dab', 'code' => 'DAB', 'name' => 'Bisnis', 'icon' => 'git-branch', 'color' => 'blue'],
    ['key' => 'dal', 'code' => 'DAL', 'name' => 'Layanan', 'icon' => 'handshake', 'color' => 'emerald'],
    ['key' => 'dad', 'code' => 'DAD', 'name' => 'Data', 'icon' => 'database', 'color' => 'violet'],
    ['key' => 'daa', 'code' => 'DAA', 'name' => 'Aplikasi', 'icon' => 'app-window', 'color' => 'amber'],
];

$infrastructureDomains = [
    ['key' => 'dai_fasilitas_komputasi', 'name' => 'Fasilitas Komputasi', 'icon' => 'server-cog'],
    ['key' => 'dai_komputasi_awan', 'name' => 'Komputasi Awan', 'icon' => 'cloud'],
    ['key' => 'dai_jaringan_intra', 'name' => 'Jaringan Intra', 'icon' => 'network'],
    ['key' => 'dai_software', 'name' => 'Software', 'icon' => 'package'],
    ['key' => 'dai_hardware_server', 'name' => 'Hardware Server', 'icon' => 'server'],
    ['key' => 'dai_hardware_jaringan', 'name' => 'Hardware Jaringan', 'icon' => 'router'],
    ['key' => 'dai_hardware_periferal', 'name' => 'Hardware Periferal', 'icon' => 'printer'],
    ['key' => 'dai_hardware_storage', 'name' => 'Hardware Storage', 'icon' => 'hard-drive'],
    ['key' => 'dai_hardware_keamanan', 'name' => 'Hardware Keamanan', 'icon' => 'shield'],
    ['key' => 'dai_splp', 'name' => 'SPLP', 'icon' => 'share-2'],
];

$securityDomains = [
    ['key' => 'dak_audit_keamanan', 'name' => 'Audit Keamanan', 'icon' => 'scan-search'],
    ['key' => 'dak_edukasi_kesadaran', 'name' => 'Edukasi Kesadaran', 'icon' => 'graduation-cap'],
    ['key' => 'dak_identifikasi_kerentanan', 'name' => 'Identifikasi Kerentanan', 'icon' => 'bug'],
    ['key' => 'dak_kelaikan_keamanan', 'name' => 'Kelaikan Keamanan', 'icon' => 'badge-check'],
    ['key' => 'dak_penanganan_insiden', 'name' => 'Penanganan Insiden', 'icon' => 'siren'],
    ['key' => 'dak_peningkatan_keamanan', 'name' => 'Peningkatan Keamanan', 'icon' => 'shield-plus'],
    ['key' => 'dak_standar_keamanan', 'name' => 'Standar Keamanan', 'icon' => 'book-check'],
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
    $referenceDomains
));
$totalInfrastructure = array_sum(array_map(
    static fn (array $domain): int => (int) $counts[$domain['key']],
    $infrastructureDomains
));
$totalSecurity = array_sum(array_map(
    static fn (array $domain): int => (int) $counts[$domain['key']],
    $securityDomains
));
$totalArchitecture = $totalInfrastructure + $totalSecurity + array_sum(array_map(
    static fn (array $domain): int => (int) $counts[$domain['key']],
    $architectureDomains
));
?>

<div class="mb-6">
    <p class="text-sm font-medium text-red-700">Selamat datang, <?= e($user['name']) ?></p>
    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Dashboard Arsitektur SPBE</h1>
    <p class="mt-1 text-sm text-slate-500">Ringkasan data referensi dan domain arsitektur SPBE.</p>
</div>

<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
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
                <p class="text-sm font-medium text-slate-500">Total Domain</p>
                <p class="mt-2 text-3xl font-bold text-slate-900"><?= number_format($totalArchitecture, 0, ',', '.') ?></p>
            </div>
            <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                <i data-lucide="blocks" class="h-5 w-5"></i>
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
        <?php foreach ($referenceDomains as $domain): ?>
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
        <h2 class="font-semibold text-slate-900">Domain Arsitektur</h2>
        <p class="mt-1 text-sm text-slate-500">Jumlah data domain bisnis, layanan, data, aplikasi, infrastruktur, dan keamanan.</p>
    </div>
    <div class="grid gap-px overflow-hidden bg-slate-200 sm:grid-cols-2 xl:grid-cols-4">
        <?php foreach ($architectureDomains as $domain): ?>
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

    <div class="border-t border-slate-200">
        <div class="flex items-center gap-3 bg-slate-50 px-5 py-4">
            <span class="flex h-9 w-9 items-center justify-center rounded-md bg-cyan-50 text-cyan-700 ring-1 ring-cyan-100">
                <i data-lucide="server" class="h-4 w-4"></i>
            </span>
            <div>
                <h3 class="font-semibold text-slate-900">Infrastruktur</h3>
                <p class="text-xs text-slate-500">Domain Arsitektur Infrastruktur</p>
            </div>
            <strong class="ml-auto text-xl text-slate-900"><?= number_format($totalInfrastructure, 0, ',', '.') ?></strong>
        </div>
        <div class="grid gap-px border-t border-slate-200 bg-slate-200 sm:grid-cols-2 xl:grid-cols-5">
            <?php foreach ($infrastructureDomains as $domain): ?>
                <article class="flex min-w-0 items-center gap-3 bg-white px-4 py-3.5">
                    <i data-lucide="<?= e($domain['icon']) ?>" class="h-4 w-4 shrink-0 text-cyan-700"></i>
                    <p class="min-w-0 flex-1 text-xs font-medium text-slate-700"><?= e($domain['name']) ?></p>
                    <strong class="text-sm text-slate-900"><?= number_format((int) $counts[$domain['key']], 0, ',', '.') ?></strong>
                </article>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="border-t border-slate-200">
        <div class="flex items-center gap-3 bg-slate-50 px-5 py-4">
            <span class="flex h-9 w-9 items-center justify-center rounded-md bg-rose-50 text-rose-700 ring-1 ring-rose-100">
                <i data-lucide="shield-check" class="h-4 w-4"></i>
            </span>
            <div>
                <h3 class="font-semibold text-slate-900">Keamanan</h3>
                <p class="text-xs text-slate-500">Domain Arsitektur Keamanan</p>
            </div>
            <strong class="ml-auto text-xl text-slate-900"><?= number_format($totalSecurity, 0, ',', '.') ?></strong>
        </div>
        <div class="grid gap-px border-t border-slate-200 bg-slate-200 sm:grid-cols-2 xl:grid-cols-4">
            <?php foreach ($securityDomains as $domain): ?>
                <article class="flex min-w-0 items-center gap-3 bg-white px-4 py-3.5">
                    <i data-lucide="<?= e($domain['icon']) ?>" class="h-4 w-4 shrink-0 text-rose-700"></i>
                    <p class="min-w-0 flex-1 text-xs font-medium text-slate-700"><?= e($domain['name']) ?></p>
                    <strong class="text-sm text-slate-900"><?= number_format((int) $counts[$domain['key']], 0, ',', '.') ?></strong>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
