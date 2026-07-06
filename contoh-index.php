<?php
require_once __DIR__ . '/includes/config.php';

require_login();

ob_start();

$user = current_user();
$page = (string) ($_GET['page'] ?? 'dashboard');
$routes = [
    'dashboard' => [
        'title' => 'Dashboard',
        'file' => __DIR__ . '/pages/dashboard.php',
    ],
    'arsitektur' => [
        'title' => 'Arsitektur',
        'file' => __DIR__ . '/pages/arsitektur.php',
    ],
    'unit-kerja' => [
        'title' => 'Unit Kerja',
        'file' => __DIR__ . '/pages/unit_kerja.php',
    ],
    'program' => [
        'title' => 'Program',
        'file' => __DIR__ . '/pages/program.php',
    ],
    'users' => [
        'title' => 'Pengguna',
        'file' => __DIR__ . '/pages/users.php',
    ],
    'profile' => [
        'title' => 'Profil',
        'file' => __DIR__ . '/pages/profile.php',
    ],
    'audit-trail' => [
        'title' => 'Audit Trail',
        'file' => __DIR__ . '/pages/audit_trail.php',
    ],
    'domain-proses-bisnis' => [
        'title' => 'Domain Proses Bisnis',
        'file' => __DIR__ . '/pages/domain_proses_bisnis.php',
    ],
    'domain-layanan' => [
        'title' => 'Domain Layanan',
        'file' => __DIR__ . '/pages/domain_layanan.php',
    ],
    'domain-data' => [
        'title' => 'Domain Data',
        'file' => __DIR__ . '/pages/domain_data.php',
    ],
    'domain-aplikasi' => [
        'title' => 'Domain Aplikasi',
        'file' => __DIR__ . '/pages/domain_aplikasi.php',
    ],
    'domain-infra-fasilitas-komputasi' => [
        'title' => 'Domain Fasilitas Komputasi',
        'file' => __DIR__ . '/pages/domain_infra_fasilitas_komputasi.php',
    ],
    'domain-infra-komputasi-awan' => [
        'title' => 'Domain Komputasi Awan',
        'file' => __DIR__ . '/pages/domain_infra_komputasi_awan.php',
    ],
];

if (!isset($routes[$page])) {
    http_response_code(404);
    $page = 'dashboard';
    set_flash('error', 'Halaman tidak ditemukan.');
}

$activeRoute = $routes[$page];
$flash = flash();
$activeJenis = strtoupper((string) ($_GET['jenis'] ?? ''));
$navKey = $page === 'arsitektur' && $activeJenis !== '' ? 'arsitektur-' . strtolower($activeJenis) : $page;

function nav_classes(string $target, string $current): string
{
    $base = 'flex items-center gap-3 whitespace-nowrap rounded-md px-3 py-2.5 text-sm font-medium transition';

    if ($target === $current) {
        return $base . ' bg-red-700 text-white shadow-sm';
    }

    return $base . ' text-slate-300 hover:bg-white/10 hover:text-white';
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($activeRoute['title']) ?> - <?= e(APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <div class="min-h-screen md:flex">
        <div class="fixed inset-0 z-30 hidden bg-slate-900/50 md:hidden" data-sidebar-overlay></div>

        <aside class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col bg-slate-950 text-white shadow-xl transition-transform duration-200 md:static md:min-h-screen md:translate-x-0 md:shadow-none" data-sidebar>
            <div class="flex items-center justify-between border-b border-white/10 px-6 py-5">
                <a href="index.php" class="flex items-center gap-3">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-md bg-white text-base font-black text-red-700 ring-1 ring-white/20">SP</span>
                    <span class="block text-lg font-bold leading-6">Arsitektur SPBE</span>
                </a>
                <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-md text-slate-300 hover:bg-white/10 hover:text-white md:hidden" data-sidebar-close aria-label="Tutup menu">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-4">
                <a href="index.php?page=dashboard" class="<?= e(nav_classes('dashboard', $navKey)) ?>">
                    <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                    Dashboard
                </a>

                <div class="pt-4">
                    <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Master</p>
                    <a href="index.php?page=unit-kerja" class="<?= e(nav_classes('unit-kerja', $navKey)) ?>">
                        <i data-lucide="building-2" class="h-4 w-4"></i>
                        Unit Kerja
                    </a>
                    <a href="index.php?page=program" class="<?= e(nav_classes('program', $navKey)) ?>">
                        <i data-lucide="clipboard-list" class="h-4 w-4"></i>
                        Program
                    </a>
                </div>

                <div class="pt-4">
                    <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Arsitektur</p>
                    <a href="index.php?page=arsitektur&jenis=RAB" class="<?= e(nav_classes('arsitektur-rab', $navKey)) ?>">
                        <i data-lucide="workflow" class="h-4 w-4"></i>
                        Proses Bisnis
                    </a>
                    <a href="index.php?page=arsitektur&jenis=RAL" class="<?= e(nav_classes('arsitektur-ral', $navKey)) ?>">
                        <i data-lucide="handshake" class="h-4 w-4"></i>
                        Layanan
                    </a>
                    <a href="index.php?page=arsitektur&jenis=RAD" class="<?= e(nav_classes('arsitektur-rad', $navKey)) ?>">
                        <i data-lucide="database" class="h-4 w-4"></i>
                        Data
                    </a>
                    <a href="index.php?page=arsitektur&jenis=RAA" class="<?= e(nav_classes('arsitektur-raa', $navKey)) ?>">
                        <i data-lucide="app-window" class="h-4 w-4"></i>
                        Aplikasi
                    </a>
                    <a href="index.php?page=arsitektur&jenis=RAI" class="<?= e(nav_classes('arsitektur-rai', $navKey)) ?>">
                        <i data-lucide="server" class="h-4 w-4"></i>
                        Infrastruktur
                    </a>
                    <a href="index.php?page=arsitektur&jenis=RAK" class="<?= e(nav_classes('arsitektur-rak', $navKey)) ?>">
                        <i data-lucide="shield-check" class="h-4 w-4"></i>
                        Keamanan
                    </a>
                </div>

                <div class="pt-4">
                    <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Domain Arsitektur</p>
                    <a href="index.php?page=domain-proses-bisnis" class="<?= e(nav_classes('domain-proses-bisnis', $navKey)) ?>">
                        <i data-lucide="git-branch" class="h-4 w-4"></i>
                        Proses Bisnis
                    </a>
                    <a href="index.php?page=domain-layanan" class="<?= e(nav_classes('domain-layanan', $navKey)) ?>">
                        <i data-lucide="handshake" class="h-4 w-4"></i>
                        Layanan
                    </a>
                    <a href="index.php?page=domain-data" class="<?= e(nav_classes('domain-data', $navKey)) ?>">
                        <i data-lucide="database" class="h-4 w-4"></i>
                        Data
                    </a>
                    <a href="index.php?page=domain-aplikasi" class="<?= e(nav_classes('domain-aplikasi', $navKey)) ?>">
                        <i data-lucide="app-window" class="h-4 w-4"></i>
                        Aplikasi
                    </a>
                    <a href="index.php?page=domain-infra-fasilitas-komputasi" class="<?= e(nav_classes('domain-infra-fasilitas-komputasi', $navKey)) ?>">
                        <i data-lucide="server" class="h-4 w-4"></i>
                        Fasilitas Komputasi
                    </a>
                    <a href="index.php?page=domain-infra-komputasi-awan" class="<?= e(nav_classes('domain-infra-komputasi-awan', $navKey)) ?>">
                        <i data-lucide="cloud" class="h-4 w-4"></i>
                        Komputasi Awan
                    </a>
                </div>

                <div class="pt-4">
                    <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Akun</p>
                    <a href="index.php?page=audit-trail" class="<?= e(nav_classes('audit-trail', $navKey)) ?>">
                        <i data-lucide="file-clock" class="h-4 w-4"></i>
                        Audit Trail
                    </a>
                    <a href="index.php?page=users" class="<?= e(nav_classes('users', $navKey)) ?>">
                        <i data-lucide="users" class="h-4 w-4"></i>
                        Pengguna
                    </a>
                    <a href="index.php?page=profile" class="<?= e(nav_classes('profile', $navKey)) ?>">
                        <i data-lucide="user-round" class="h-4 w-4"></i>
                        Profil
                    </a>
                </div>
            </nav>
        </aside>

        <main class="flex min-w-0 flex-1 flex-col">
            <header class="border-b border-slate-200 bg-white px-4 py-4 shadow-sm sm:px-6 lg:px-8">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-start gap-3">
                        <button type="button" class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-50 md:hidden" data-sidebar-open aria-label="Buka menu">
                            <i data-lucide="menu" class="h-5 w-5"></i>
                        </button>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-500">Sistem Informasi</p>
                            <p class="truncate text-base font-semibold text-slate-900"><?= e(APP_NAME) ?></p>
                        </div>
                    </div>

                    <div class="relative shrink-0" data-user-menu>
                        <button type="button" class="flex items-center gap-3 rounded-md border border-slate-200 bg-white px-3 py-2 text-left text-sm shadow-sm transition hover:bg-slate-50" data-user-menu-button aria-expanded="false">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-900 text-white">
                                <i data-lucide="user-round" class="h-4 w-4"></i>
                            </span>
                            <span class="hidden sm:block">
                                <span class="block max-w-40 truncate font-semibold text-slate-800"><?= e((string) $user['name']) ?></span>
                                <span class="block text-xs uppercase tracking-wide text-slate-500"><?= e(role_label((string) $user['role'])) ?></span>
                            </span>
                            <i data-lucide="chevron-down" class="h-4 w-4 text-slate-500"></i>
                        </button>

                        <div class="absolute right-0 z-30 mt-2 hidden w-56 overflow-hidden rounded-md border border-slate-200 bg-white py-2 shadow-lg" data-user-menu-dropdown>
                            <div class="border-b border-slate-100 px-4 py-3">
                                <p class="truncate text-sm font-semibold text-slate-900"><?= e((string) $user['name']) ?></p>
                                <p class="mt-0.5 truncate text-xs text-slate-500">@<?= e((string) $user['username']) ?> - <?= e(role_label((string) $user['role'])) ?></p>
                            </div>
                            <a href="index.php?page=profile" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50">
                                <i data-lucide="user-round-cog" class="h-4 w-4 text-slate-500"></i>
                                Profil
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
                    <div class="mb-4 rounded-md border px-4 py-3 text-sm <?= $flash['type'] === 'error' ? 'border-red-200 bg-red-50 text-red-700' : 'border-green-200 bg-green-50 text-green-700' ?>">
                        <?= e((string) $flash['message']) ?>
                    </div>
                <?php endif; ?>

                <?php require $activeRoute['file']; ?>
            </section>

            <footer class="border-t border-slate-200 bg-white px-4 py-4 text-sm text-slate-500 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <p>&copy; 2026 <?= e(APP_NAME) ?>.</p>
                    <p class="text-xs">Aplikasi pengelolaan referensi arsitektur SPBE</p>
                </div>
            </footer>
        </main>
    </div>

    <script>
        lucide.createIcons();

        const sidebar = document.querySelector('[data-sidebar]');
        const sidebarOverlay = document.querySelector('[data-sidebar-overlay]');
        const sidebarOpen = document.querySelector('[data-sidebar-open]');
        const sidebarClose = document.querySelector('[data-sidebar-close]');
        const userMenu = document.querySelector('[data-user-menu]');
        const userMenuButton = document.querySelector('[data-user-menu-button]');
        const userMenuDropdown = document.querySelector('[data-user-menu-dropdown]');

        const openSidebar = () => {
            sidebar.classList.remove('-translate-x-full');
            sidebarOverlay.classList.remove('hidden');
        };
        const closeSidebar = () => {
            sidebar.classList.add('-translate-x-full');
            sidebarOverlay.classList.add('hidden');
        };

        sidebarOpen?.addEventListener('click', openSidebar);
        sidebarClose?.addEventListener('click', closeSidebar);
        sidebarOverlay?.addEventListener('click', closeSidebar);

        userMenuButton?.addEventListener('click', () => {
            const isHidden = userMenuDropdown.classList.toggle('hidden');
            userMenuButton.setAttribute('aria-expanded', String(!isHidden));
        });

        document.addEventListener('click', (event) => {
            if (userMenu && !userMenu.contains(event.target)) {
                userMenuDropdown.classList.add('hidden');
                userMenuButton.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeSidebar();
                userMenuDropdown.classList.add('hidden');
                userMenuButton.setAttribute('aria-expanded', 'false');
            }
        });
    </script>
</body>
</html>
