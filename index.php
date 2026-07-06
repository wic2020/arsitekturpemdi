<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';

require_login();

$user = current_user();
$page = (string) ($_GET['page'] ?? 'dashboard');
$routes = [
    'dashboard' => [
        'title' => 'Dashboard',
        'file' => __DIR__ . '/pages/dashboard.php',
    ],
    'rab' => [
        'title' => 'Referensi Arsitektur Bisnis',
        'file' => __DIR__ . '/pages/rab.php',
    ],
    'ral' => [
        'title' => 'Referensi Arsitektur Layanan',
        'file' => __DIR__ . '/pages/ral.php',
    ],
    'rad' => [
        'title' => 'Referensi Arsitektur Data',
        'file' => __DIR__ . '/pages/rad.php',
    ],
    'raa' => [
        'title' => 'Referensi Arsitektur Aplikasi',
        'file' => __DIR__ . '/pages/raa.php',
    ],
    'rai' => [
        'title' => 'Referensi Arsitektur Infrastruktur',
        'file' => __DIR__ . '/pages/rai.php',
    ],
    'rak' => [
        'title' => 'Referensi Arsitektur Keamanan',
        'file' => __DIR__ . '/pages/rak.php',
    ],
    'dab' => [
        'title' => 'Domain Arsitektur Bisnis',
        'file' => __DIR__ . '/pages/dab.php',
    ],
    'dal' => [
        'title' => 'Domain Arsitektur Layanan',
        'file' => __DIR__ . '/pages/dal.php',
    ],
    'dad' => [
        'title' => 'Domain Arsitektur Data',
        'file' => __DIR__ . '/pages/dad.php',
    ],
    'daa' => [
        'title' => 'Domain Arsitektur Aplikasi',
        'file' => __DIR__ . '/pages/daa.php',
    ],
    'aspek' => [
        'title' => 'Aspek Peta Rencana',
        'file' => __DIR__ . '/pages/master_rencana.php',
    ],
    'indikator' => [
        'title' => 'Indikator Peta Rencana',
        'file' => __DIR__ . '/pages/master_rencana.php',
    ],
    'peta-rencana' => [
        'title' => 'Peta Rencana',
        'file' => __DIR__ . '/pages/peta_rencana.php',
    ],
    'pemdi-level' => [
        'title' => 'PEMDI Level',
        'file' => __DIR__ . '/pages/pemdi_level.php',
    ],
    'pemdi-evidence' => [
        'title' => 'PEMDI Evidence',
        'file' => __DIR__ . '/pages/pemdi_evidence.php',
    ],
    'dai-fasilitas-komputasi' => [
        'title' => 'Domain Fasilitas Komputasi',
        'file' => __DIR__ . '/pages/dai.php',
    ],
    'dai-komputasi-awan' => [
        'title' => 'Domain Komputasi Awan',
        'file' => __DIR__ . '/pages/dai.php',
    ],
    'dai-jaringan-intra' => [
        'title' => 'Domain Jaringan Intra',
        'file' => __DIR__ . '/pages/dai.php',
    ],
    'dai-software' => [
        'title' => 'Domain Software',
        'file' => __DIR__ . '/pages/dai.php',
    ],
    'dai-hardware-server' => [
        'title' => 'Domain Hardware Server',
        'file' => __DIR__ . '/pages/dai.php',
    ],
    'dai-hardware-jaringan' => [
        'title' => 'Domain Hardware Jaringan',
        'file' => __DIR__ . '/pages/dai.php',
    ],
    'dai-hardware-periferal' => [
        'title' => 'Domain Hardware Periferal',
        'file' => __DIR__ . '/pages/dai.php',
    ],
    'dai-hardware-storage' => [
        'title' => 'Domain Hardware Storage',
        'file' => __DIR__ . '/pages/dai.php',
    ],
    'dai-hardware-keamanan' => [
        'title' => 'Domain Hardware Keamanan',
        'file' => __DIR__ . '/pages/dai.php',
    ],
    'dai-splp' => [
        'title' => 'Domain SPLP',
        'file' => __DIR__ . '/pages/dai.php',
    ],
    'dak-audit-keamanan' => [
        'title' => 'Audit Keamanan',
        'file' => __DIR__ . '/pages/dak.php',
    ],
    'dak-edukasi-kesadaran' => [
        'title' => 'Edukasi Kesadaran',
        'file' => __DIR__ . '/pages/dak.php',
    ],
    'dak-identifikasi-kerentanan' => [
        'title' => 'Identifikasi Kerentanan',
        'file' => __DIR__ . '/pages/dak.php',
    ],
    'dak-kelaikan-keamanan' => [
        'title' => 'Kelaikan Keamanan',
        'file' => __DIR__ . '/pages/dak.php',
    ],
    'dak-penanganan-insiden' => [
        'title' => 'Penanganan Insiden',
        'file' => __DIR__ . '/pages/dak.php',
    ],
    'dak-peningkatan-keamanan' => [
        'title' => 'Peningkatan Keamanan',
        'file' => __DIR__ . '/pages/dak.php',
    ],
    'dak-standar-keamanan' => [
        'title' => 'Standar Keamanan',
        'file' => __DIR__ . '/pages/dak.php',
    ],
    'profile' => [
        'title' => 'Profil Saya',
        'file' => __DIR__ . '/pages/profile.php',
    ],
];

if (!isset($routes[$page])) {
    http_response_code(404);
    set_flash('error', 'Halaman yang Anda minta belum tersedia.');
    redirect('index.php?page=dashboard');
}

$pagePermissions = [
    'create' => user_can($page, 'create', $user),
    'update' => user_can($page, 'update', $user),
    'delete' => user_can($page, 'delete', $user),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestedAction = (string) ($_POST['action'] ?? '');
    if (isset($pagePermissions[$requestedAction]) && !$pagePermissions[$requestedAction]) {
        http_response_code(403);
        set_flash('error', 'Anda tidak memiliki kewenangan untuk melakukan tindakan tersebut.');
        redirect('index.php?page=' . rawurlencode($page));
    }
}

$activeRoute = $routes[$page];
$flash = flash();
$referencePages = ['rab', 'ral', 'rad', 'raa', 'rai', 'rak'];
$domainPages = ['dab', 'dal', 'dad', 'daa'];
$referenceMenuOpen = in_array($page, $referencePages, true);
$domainMenuOpen = in_array($page, $domainPages, true)
    || str_starts_with($page, 'dai-')
    || str_starts_with($page, 'dak-');
$evaluationMenuOpen = in_array($page, ['aspek', 'indikator', 'peta-rencana', 'pemdi-level', 'pemdi-evidence'], true);
$breadcrumbLabels = [
    'rab' => 'Proses Bisnis',
    'ral' => 'Layanan',
    'rad' => 'Data',
    'raa' => 'Aplikasi',
    'rai' => 'Infrastruktur',
    'rak' => 'Keamanan',
    'dab' => 'Bisnis',
    'dal' => 'Layanan',
    'dad' => 'Data',
    'daa' => 'Aplikasi',
    'aspek' => 'Aspek',
    'indikator' => 'Indikator',
    'peta-rencana' => 'Peta Rencana',
    'pemdi-level' => 'Level PEMDI',
    'pemdi-evidence' => 'Evidence',
    'dai-fasilitas-komputasi' => 'Fasilitas Komputasi',
    'dai-komputasi-awan' => 'Komputasi Awan',
    'dai-jaringan-intra' => 'Jaringan Intra',
    'dai-software' => 'Software',
    'dai-hardware-server' => 'Hardware Server',
    'dai-hardware-jaringan' => 'Hardware Jaringan',
    'dai-hardware-periferal' => 'Hardware Periferal',
    'dai-hardware-storage' => 'Hardware Storage',
    'dai-hardware-keamanan' => 'Hardware Keamanan',
    'dai-splp' => 'SPLP',
    'dak-audit-keamanan' => 'Audit Keamanan',
    'dak-edukasi-kesadaran' => 'Edukasi Kesadaran',
    'dak-identifikasi-kerentanan' => 'Identifikasi Kerentanan',
    'dak-kelaikan-keamanan' => 'Kelaikan Keamanan',
    'dak-penanganan-insiden' => 'Penanganan Insiden',
    'dak-peningkatan-keamanan' => 'Peningkatan Keamanan',
    'dak-standar-keamanan' => 'Standar Keamanan',
    'profile' => 'Profil Saya',
];
$breadcrumbItems = [['label' => 'Dashboard', 'url' => 'index.php?page=dashboard']];
if ($page !== 'dashboard') {
    if ($referenceMenuOpen) {
        $breadcrumbItems[] = ['label' => 'Referensi Arsitektur', 'url' => null];
    } elseif ($domainMenuOpen) {
        $breadcrumbItems[] = ['label' => 'Domain Arsitektur', 'url' => null];
        if (str_starts_with($page, 'dai-')) $breadcrumbItems[] = ['label' => 'Infrastruktur', 'url' => null];
        if (str_starts_with($page, 'dak-')) $breadcrumbItems[] = ['label' => 'Keamanan', 'url' => null];
    } elseif ($evaluationMenuOpen) {
        $breadcrumbItems[] = ['label' => 'Evaluasi Pemdi', 'url' => null];
    } elseif ($page === 'profile') {
        $breadcrumbItems[] = ['label' => 'Akun', 'url' => null];
    }
    $breadcrumbItems[] = ['label' => $breadcrumbLabels[$page] ?? $activeRoute['title'], 'url' => null];
}

function nav_classes(string $target, string $current): string
{
    $base = 'flex items-center gap-2.5 whitespace-nowrap rounded-md px-2.5 py-2 text-[13px] font-medium transition';

    return $target === $current
        ? $base . ' bg-red-700 text-white shadow-sm'
        : $base . ' text-slate-300 hover:bg-white/10 hover:text-white';
}

function nav_disabled_classes(): string
{
    return 'flex cursor-not-allowed items-center gap-2.5 whitespace-nowrap rounded-md px-2.5 py-2 text-[13px] font-medium text-slate-600';
}

ob_start();
require $activeRoute['file'];
$pageContent = ob_get_clean();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= e($activeRoute['title']) ?> - <?= e(APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <?php if ($page === 'pemdi-evidence'): ?>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Dropify/0.2.2/css/dropify.min.css">
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Dropify/0.2.2/js/dropify.min.js"></script>
    <?php endif; ?>
    <style>
        .sidebar-scroll {
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, .28) transparent;
            scrollbar-gutter: stable;
        }

        .sidebar-scroll:hover {
            scrollbar-color: rgba(248, 113, 113, .78) transparent;
        }

        .sidebar-scroll::-webkit-scrollbar {
            width: 8px;
        }

        .sidebar-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar-scroll::-webkit-scrollbar-thumb {
            min-height: 40px;
            border: 2px solid rgb(2 6 23);
            border-radius: 999px;
            background: linear-gradient(180deg, rgba(148, 163, 184, .28), rgba(100, 116, 139, .18));
        }

        .sidebar-scroll:hover::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, rgba(248, 113, 113, .9), rgba(185, 28, 28, .78));
        }

        .sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, rgb(252 165 165), rgb(220 38 38));
        }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <div class="min-h-screen md:flex">
        <div class="fixed inset-0 z-30 hidden bg-slate-900/50 md:hidden" data-sidebar-overlay></div>

        <aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-slate-950 text-white shadow-xl transition-transform duration-200 md:sticky md:top-0 md:h-screen md:translate-x-0 md:shadow-none" data-sidebar>
            <div class="flex items-center justify-between border-b border-white/10 px-5 py-4">
                <a href="index.php" class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-white text-sm font-black text-red-700 ring-1 ring-white/20">SP</span>
                    <span class="block text-base font-bold leading-5">Arsitektur SPBE</span>
                </a>
                <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-md text-slate-300 hover:bg-white/10 hover:text-white md:hidden" data-sidebar-close aria-label="Tutup menu">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <nav class="sidebar-scroll flex-1 space-y-0.5 overflow-y-auto px-3 py-3">
                <a href="index.php?page=dashboard" class="<?= e(nav_classes('dashboard', $page)) ?>">
                    <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                    Dashboard
                </a>

                <div class="pt-3">
                    <details class="group/reference" <?= $referenceMenuOpen ? 'open' : '' ?>>
                        <summary class="flex cursor-pointer list-none items-center gap-2.5 rounded-md px-2.5 py-2 text-[13px] font-semibold transition hover:bg-white/10 hover:text-white <?= $referenceMenuOpen ? 'bg-white/5 text-white' : 'text-slate-300' ?>">
                            <i data-lucide="book-open" class="h-4 w-4"></i>
                            <span class="flex-1">Referensi Arsitektur</span>
                            <i data-lucide="chevron-right" class="h-3.5 w-3.5 transition-transform group-open/reference:rotate-90"></i>
                        </summary>
                        <div class="ml-4 space-y-0.5 border-l border-white/10 pl-2">
                            <?php foreach ([
                                'rab' => ['workflow', 'Proses Bisnis'],
                                'ral' => ['handshake', 'Layanan'],
                                'rad' => ['database', 'Data'],
                                'raa' => ['app-window', 'Aplikasi'],
                                'rai' => ['server', 'Infrastruktur'],
                                'rak' => ['shield-check', 'Keamanan'],
                            ] as $referencePage => [$referenceIcon, $referenceLabel]): ?>
                                <a href="index.php?page=<?= e($referencePage) ?>" class="<?= e(nav_classes($referencePage, $page)) ?>">
                                    <i data-lucide="<?= e($referenceIcon) ?>" class="h-3.5 w-3.5"></i>
                                    <?= e($referenceLabel) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </details>
                </div>

                <div class="pt-3">
                    <details class="group/domain" <?= $domainMenuOpen ? 'open' : '' ?>>
                        <summary class="flex cursor-pointer list-none items-center gap-2.5 rounded-md px-2.5 py-2 text-[13px] font-semibold transition hover:bg-white/10 hover:text-white <?= $domainMenuOpen ? 'bg-white/5 text-white' : 'text-slate-300' ?>">
                            <i data-lucide="blocks" class="h-4 w-4"></i>
                            <span class="flex-1">Domain Arsitektur</span>
                            <i data-lucide="chevron-right" class="h-3.5 w-3.5 transition-transform group-open/domain:rotate-90"></i>
                        </summary>
                        <div class="ml-4 space-y-0.5 border-l border-white/10 pl-2">
                            <?php foreach ([
                                'dab' => ['git-branch', 'Bisnis'],
                                'dal' => ['handshake', 'Layanan'],
                                'dad' => ['database', 'Data'],
                                'daa' => ['app-window', 'Aplikasi'],
                            ] as $domainPage => [$domainIcon, $domainLabel]): ?>
                                <a href="index.php?page=<?= e($domainPage) ?>" class="<?= e(nav_classes($domainPage, $page)) ?>">
                                    <i data-lucide="<?= e($domainIcon) ?>" class="h-3.5 w-3.5"></i>
                                    <?= e($domainLabel) ?>
                                </a>
                            <?php endforeach; ?>

                    <details class="group mt-0.5" <?= str_starts_with($page, 'dai-') ? 'open' : '' ?>>
                        <summary class="flex cursor-pointer list-none items-center gap-2.5 rounded-md px-2.5 py-2 text-[13px] font-medium transition hover:bg-white/10 hover:text-white <?= str_starts_with($page, 'dai-') ? 'text-white' : 'text-slate-300' ?>">
                            <i data-lucide="server" class="h-4 w-4"></i>
                            <span class="flex-1">Infrastruktur</span>
                            <i data-lucide="chevron-right" class="h-3.5 w-3.5 transition-transform group-open:rotate-90"></i>
                        </summary>
                        <div class="ml-4 space-y-0.5 border-l border-white/10 pl-2">
                            <?php foreach ([
                                'dai-fasilitas-komputasi' => ['server-cog', 'Fasilitas Komputasi'],
                                'dai-komputasi-awan' => ['cloud', 'Komputasi Awan'],
                                'dai-jaringan-intra' => ['network', 'Jaringan Intra'],
                                'dai-software' => ['package', 'Software'],
                                'dai-hardware-server' => ['server', 'Hardware Server'],
                                'dai-hardware-jaringan' => ['router', 'Hardware Jaringan'],
                                'dai-hardware-periferal' => ['printer', 'Hardware Periferal'],
                                'dai-hardware-storage' => ['hard-drive', 'Hardware Storage'],
                                'dai-hardware-keamanan' => ['shield', 'Hardware Keamanan'],
                                'dai-splp' => ['share-2', 'SPLP'],
                            ] as $daiPage => [$daiIcon, $daiLabel]): ?>
                                <a href="index.php?page=<?= e($daiPage) ?>" class="<?= e(nav_classes($daiPage, $page)) ?>">
                                    <i data-lucide="<?= e($daiIcon) ?>" class="h-3.5 w-3.5"></i>
                                    <?= e($daiLabel) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </details>

                    <details class="group mt-0.5" <?= str_starts_with($page, 'dak-') ? 'open' : '' ?>>
                        <summary class="flex cursor-pointer list-none items-center gap-2.5 rounded-md px-2.5 py-2 text-[13px] font-medium transition hover:bg-white/10 hover:text-white <?= str_starts_with($page, 'dak-') ? 'text-white' : 'text-slate-300' ?>">
                            <i data-lucide="shield-check" class="h-4 w-4"></i>
                            <span class="flex-1">Keamanan</span>
                            <i data-lucide="chevron-right" class="h-3.5 w-3.5 transition-transform group-open:rotate-90"></i>
                        </summary>
                        <div class="ml-4 space-y-0.5 border-l border-white/10 pl-2">
                            <?php foreach ([
                                'dak-audit-keamanan' => ['scan-search', 'Audit Keamanan'],
                                'dak-edukasi-kesadaran' => ['graduation-cap', 'Edukasi Kesadaran'],
                                'dak-identifikasi-kerentanan' => ['bug', 'Identifikasi Kerentanan'],
                                'dak-kelaikan-keamanan' => ['badge-check', 'Kelaikan Keamanan'],
                                'dak-penanganan-insiden' => ['siren', 'Penanganan Insiden'],
                                'dak-peningkatan-keamanan' => ['shield-plus', 'Peningkatan Keamanan'],
                                'dak-standar-keamanan' => ['book-check', 'Standar Keamanan'],
                            ] as $dakPage => [$dakIcon, $dakLabel]): ?>
                                <a href="index.php?page=<?= e($dakPage) ?>" class="<?= e(nav_classes($dakPage, $page)) ?>">
                                    <i data-lucide="<?= e($dakIcon) ?>" class="h-3.5 w-3.5"></i>
                                    <?= e($dakLabel) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </details>
                        </div>
                    </details>
                </div>

                <div class="pt-3">
                    <details class="group/evaluation" <?= $evaluationMenuOpen ? 'open' : '' ?>>
                        <summary class="flex cursor-pointer list-none items-center gap-2.5 rounded-md px-2.5 py-2 text-[13px] font-semibold transition hover:bg-white/10 hover:text-white <?= $evaluationMenuOpen ? 'bg-white/5 text-white' : 'text-slate-300' ?>">
                            <i data-lucide="clipboard-check" class="h-4 w-4"></i>
                            <span class="flex-1">Evaluasi Pemdi</span>
                            <i data-lucide="chevron-right" class="h-3.5 w-3.5 transition-transform group-open/evaluation:rotate-90"></i>
                        </summary>
                        <div class="ml-4 space-y-0.5 border-l border-white/10 pl-2">
                            <a href="index.php?page=aspek" class="<?= e(nav_classes('aspek', $page)) ?>">
                                <i data-lucide="layers-3" class="h-3.5 w-3.5"></i>
                                Aspek
                            </a>
                            <a href="index.php?page=indikator" class="<?= e(nav_classes('indikator', $page)) ?>">
                                <i data-lucide="list-checks" class="h-3.5 w-3.5"></i>
                                Indikator
                            </a>
                        </div>
                    </details>
                </div>

                <div class="pt-3">
                    <p class="px-2.5 pb-1.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Akun</p>
                    <a href="index.php?page=profile" class="<?= e(nav_classes('profile', $page)) ?>">
                        <i data-lucide="user-round" class="h-4 w-4"></i>
                        Profil Saya
                    </a>
                </div>
            </nav>
        </aside>

        <main class="flex min-w-0 flex-1 flex-col">
            <header class="border-b border-slate-200 bg-white px-4 py-4 shadow-sm sm:px-6 lg:px-8">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <button type="button" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-50 md:hidden" data-sidebar-open aria-label="Buka menu">
                            <i data-lucide="menu" class="h-5 w-5"></i>
                        </button>
                        <nav class="min-w-0 overflow-hidden" aria-label="Breadcrumb">
                            <ol class="flex min-w-0 items-center gap-1 text-xs text-slate-500 sm:text-sm">
                                <?php foreach ($breadcrumbItems as $index => $item):
                                    $isLastBreadcrumb = $index === count($breadcrumbItems) - 1;
                                ?>
                                    <li class="flex min-w-0 items-center gap-1">
                                        <?php if ($index > 0): ?><i data-lucide="chevron-right" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i><?php endif; ?>
                                        <?php if ($index === 0 && !$isLastBreadcrumb): ?>
                                            <a href="<?= e($item['url']) ?>" class="inline-flex shrink-0 items-center gap-1.5 rounded px-1.5 py-1 font-medium hover:bg-slate-100 hover:text-slate-800" title="Dashboard">
                                                <i data-lucide="house" class="h-3.5 w-3.5"></i>
                                                <span class="hidden sm:inline"><?= e($item['label']) ?></span>
                                            </a>
                                        <?php elseif ($isLastBreadcrumb): ?>
                                            <span class="truncate px-1 py-1 font-semibold text-slate-900" aria-current="page"><?= e($item['label']) ?></span>
                                        <?php else: ?>
                                            <span class="shrink-0 px-1 py-1 font-medium"><?= e($item['label']) ?></span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        </nav>
                    </div>

                    <div class="relative shrink-0" data-user-menu>
                        <button type="button" class="flex items-center gap-3 rounded-md border border-slate-200 bg-white px-3 py-2 text-left text-sm shadow-sm transition hover:bg-slate-50" data-user-menu-button aria-expanded="false">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-900 text-white">
                                <i data-lucide="user-round" class="h-4 w-4"></i>
                            </span>
                            <span class="hidden sm:block">
                                <span class="block max-w-40 truncate font-semibold text-slate-800"><?= e($user['name']) ?></span>
                                <span class="block text-xs uppercase tracking-wide text-slate-500"><?= e(role_label($user['role'])) ?></span>
                            </span>
                            <i data-lucide="chevron-down" class="h-4 w-4 text-slate-500"></i>
                        </button>

                        <div class="absolute right-0 z-30 mt-2 hidden w-60 overflow-hidden rounded-md border border-slate-200 bg-white py-2 shadow-lg" data-user-menu-dropdown>
                            <div class="border-b border-slate-100 px-4 py-3">
                                <p class="truncate text-sm font-semibold text-slate-900"><?= e($user['name']) ?></p>
                                <p class="mt-0.5 truncate text-xs text-slate-500">@<?= e($user['username']) ?> · <?= e(role_label($user['role'])) ?></p>
                            </div>
                            <a href="index.php?page=profile" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50">
                                <i data-lucide="user-round-cog" class="h-4 w-4 text-slate-500"></i>
                                Profil Saya
                            </a>
                            <a href="logout.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-700 hover:bg-red-50">
                                <i data-lucide="log-out" class="h-4 w-4"></i>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <section class="flex-1 px-3 py-4 sm:px-6 sm:py-6 lg:px-8">
                <?php if ($flash): ?>
                    <?php
                    $flashClasses = match ($flash['type']) {
                        'error' => 'border-red-200 bg-red-50 text-red-700',
                        'warning' => 'border-amber-200 bg-amber-50 text-amber-700',
                        default => 'border-green-200 bg-green-50 text-green-700',
                    };
                    ?>
                    <div class="mb-5 flex items-start gap-3 rounded-md border px-4 py-3 text-sm <?= e($flashClasses) ?>" role="alert">
                        <i data-lucide="<?= $flash['type'] === 'error' ? 'circle-alert' : 'circle-check' ?>" class="mt-0.5 h-4 w-4 shrink-0"></i>
                        <span><?= e($flash['message']) ?></span>
                    </div>
                <?php endif; ?>

                <?= $pageContent ?>
            </section>

            <footer class="border-t border-slate-200 bg-white px-4 py-4 text-sm text-slate-500 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>.</p>
                    <!-- <p class="text-xs">Pengelolaan referensi arsitektur SPBE</p> -->
                </div>
            </footer>
        </main>
    </div>

        <script>
            const pagePermissions = <?= json_encode($pagePermissions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            document.querySelectorAll('button, a').forEach((element) => {
                const attributeNames = element.getAttributeNames();
                const permission = attributeNames.some((name) => /^data-.+-add$/.test(name))
                    ? 'create'
                    : attributeNames.some((name) => /^data-.+-edit$/.test(name))
                        ? 'update'
                        : attributeNames.some((name) => /^data-.+-delete$/.test(name))
                            ? 'delete'
                            : null;

                if (permission && !pagePermissions[permission]) {
                    element.remove();
                }
            });

        if (window.lucide) lucide.createIcons();

        const sidebar = document.querySelector('[data-sidebar]');
        const sidebarOverlay = document.querySelector('[data-sidebar-overlay]');
        const sidebarOpen = document.querySelector('[data-sidebar-open]');
        const sidebarClose = document.querySelector('[data-sidebar-close]');
        const userMenu = document.querySelector('[data-user-menu]');
        const userMenuButton = document.querySelector('[data-user-menu-button]');
        const userMenuDropdown = document.querySelector('[data-user-menu-dropdown]');

        const closeSidebar = () => {
            sidebar?.classList.add('-translate-x-full');
            sidebarOverlay?.classList.add('hidden');
        };

        sidebarOpen?.addEventListener('click', () => {
            sidebar?.classList.remove('-translate-x-full');
            sidebarOverlay?.classList.remove('hidden');
        });
        sidebarClose?.addEventListener('click', closeSidebar);
        sidebarOverlay?.addEventListener('click', closeSidebar);

        userMenuButton?.addEventListener('click', () => {
            const hidden = userMenuDropdown?.classList.toggle('hidden');
            userMenuButton.setAttribute('aria-expanded', String(!hidden));
        });

        document.addEventListener('click', (event) => {
            if (userMenu && !userMenu.contains(event.target)) {
                userMenuDropdown?.classList.add('hidden');
                userMenuButton?.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeSidebar();
                userMenuDropdown?.classList.add('hidden');
                userMenuButton?.setAttribute('aria-expanded', 'false');
            }
        });
    </script>
</body>
</html>
