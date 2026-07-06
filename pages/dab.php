<?php

declare(strict_types=1);

$dabFields = ['id_skpd', 'id_program', 'id_rab', 'nama_bisnis', 'uraian', 'sasaran', 'indikator', 'target', 'realisasi'];
$formErrors = [];
$openFormModal = false;
$formMode = 'create';
$formState = array_fill_keys($dabFields, '');
$formState['id'] = '';

function dab_int_or_null(mixed $value): ?int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id ? (int) $id : null;
}

function dab_option_label(?string $code, ?string $name): string
{
    $code = trim((string) $code);
    $name = trim((string) $name);
    return trim($code . ($code !== '' && $name !== '' ? ' - ' : '') . $name);
}

function dab_page_url(int $page, string $search, int $perPage): string
{
    return 'index.php?' . http_build_query([
        'page' => 'dab',
        'q' => $search,
        'per_page' => $perPage,
        'p' => $page,
    ]);
}

$skpdList = db()->query('SELECT id, kode_skpd, nama_skpd FROM skpd ORDER BY kode_skpd, nama_skpd')->fetchAll();
$programList = db()->query(
    'SELECT id, kode_program, nama_program, id_skpd, id_rab
     FROM program
     ORDER BY kode_program, nama_program'
)->fetchAll();
$rabList = db()->query(
    'SELECT id, kode_rab_4, nama_rab_4
     FROM rab
     ORDER BY kode_rab_4, nama_rab_4'
)->fetchAll();

$skpdIds = array_flip(array_map('intval', array_column($skpdList, 'id')));
$rabIds = array_flip(array_map('intval', array_column($rabList, 'id')));
$programMap = [];
foreach ($programList as $program) {
    $programMap[(int) $program['id']] = $program;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if (in_array($action, ['create', 'update'], true)) {
        $formMode = $action;
        $openFormModal = true;
        $recordId = dab_int_or_null($_POST['id'] ?? null);
        $formState['id'] = $recordId ?: '';

        foreach ($dabFields as $field) {
            $formState[$field] = trim((string) ($_POST[$field] ?? ''));
        }

        $idSkpd = dab_int_or_null($formState['id_skpd']);
        $idProgram = dab_int_or_null($formState['id_program']);
        $idRab = dab_int_or_null($formState['id_rab']);

        if (!$idSkpd || !isset($skpdIds[$idSkpd])) {
            $formErrors[] = 'SKPD wajib dipilih.';
        }
        if (!$idProgram || !isset($programMap[$idProgram])) {
            $formErrors[] = 'Program wajib dipilih.';
        }
        if (!$idRab || !isset($rabIds[$idRab])) {
            $formErrors[] = 'Referensi RAB wajib dipilih.';
        }
        if ($idProgram && $idSkpd && isset($programMap[$idProgram]) && (int) $programMap[$idProgram]['id_skpd'] !== $idSkpd) {
            $formErrors[] = 'Program yang dipilih tidak sesuai dengan SKPD.';
        }
        if ($formState['nama_bisnis'] === '') {
            $formErrors[] = 'Nama bisnis wajib diisi.';
        } elseif (mb_strlen($formState['nama_bisnis']) > 255) {
            $formErrors[] = 'Nama bisnis maksimal 255 karakter.';
        }
        foreach (['target', 'realisasi'] as $field) {
            if (mb_strlen($formState[$field]) > 255) {
                $formErrors[] = ucfirst($field) . ' maksimal 255 karakter.';
            }
        }
        if ($action === 'update' && !$recordId) {
            $formErrors[] = 'ID data DAB tidak valid.';
        }

        $oldValues = null;
        if (!$formErrors && $action === 'update') {
            $oldStmt = db()->prepare(
                'SELECT * FROM dab WHERE id = :id LIMIT 1'
            );
            $oldStmt->execute(['id' => $recordId]);
            $oldValues = $oldStmt->fetch();
            if (!$oldValues) {
                $formErrors[] = 'Data DAB yang akan diubah tidak ditemukan.';
            }
        }

        if (!$formErrors) {
            db()->beginTransaction();
            try {
                $params = [
                    'id_skpd' => $idSkpd,
                    'id_program' => $idProgram,
                    'id_rab' => $idRab,
                    'nama_bisnis' => $formState['nama_bisnis'],
                    'uraian' => $formState['uraian'] !== '' ? $formState['uraian'] : null,
                    'sasaran' => $formState['sasaran'] !== '' ? $formState['sasaran'] : null,
                    'indikator' => $formState['indikator'] !== '' ? $formState['indikator'] : null,
                    'target' => $formState['target'] !== '' ? $formState['target'] : null,
                    'realisasi' => $formState['realisasi'] !== '' ? $formState['realisasi'] : null,
                    'updated_by' => (int) $user['id'],
                ];

                if ($action === 'create') {
                    $params['created_by'] = (int) $user['id'];
                    $stmt = db()->prepare(
                        'INSERT INTO dab
                            (id_skpd, id_program, id_rab, nama_bisnis, uraian, sasaran, indikator, target, realisasi, created_by, updated_by)
                         VALUES
                            (:id_skpd, :id_program, :id_rab, :nama_bisnis, :uraian, :sasaran, :indikator, :target, :realisasi, :created_by, :updated_by)'
                    );
                    $stmt->execute($params);
                    $recordId = (int) db()->lastInsertId();
                    $newStmt = db()->prepare('SELECT * FROM dab WHERE id = :id LIMIT 1');
                    $newStmt->execute(['id' => $recordId]);
                    $newValues = $newStmt->fetch() ?: $params;
                    audit_log((int) $user['id'], 'create', 'dab', $recordId, 'Menambahkan domain bisnis ' . $formState['nama_bisnis'], null, $newValues, true);
                    $successMessage = 'Data DAB berhasil ditambahkan.';
                } else {
                    $params['id'] = $recordId;
                    $stmt = db()->prepare(
                        'UPDATE dab SET
                            id_skpd = :id_skpd, id_program = :id_program, id_rab = :id_rab,
                            nama_bisnis = :nama_bisnis, uraian = :uraian, sasaran = :sasaran,
                            indikator = :indikator, target = :target, realisasi = :realisasi,
                            updated_by = :updated_by
                         WHERE id = :id'
                    );
                    $stmt->execute($params);
                    $newStmt = db()->prepare('SELECT * FROM dab WHERE id = :id LIMIT 1');
                    $newStmt->execute(['id' => $recordId]);
                    $newValues = $newStmt->fetch() ?: $params;
                    audit_log((int) $user['id'], 'update', 'dab', (int) $recordId, 'Mengubah domain bisnis ' . $formState['nama_bisnis'], $oldValues, $newValues, true);
                    $successMessage = 'Data DAB berhasil diperbarui.';
                }

                db()->commit();
                set_flash('success', $successMessage);
                redirect('index.php?page=dab');
            } catch (Throwable $exception) {
                db()->rollBack();
                error_log('Penyimpanan DAB gagal: ' . $exception->getMessage());
                $formErrors[] = 'Data DAB gagal disimpan. Silakan coba kembali.';
            }
        }
    } elseif ($action === 'delete') {
        $recordId = dab_int_or_null($_POST['id'] ?? null);
        if (!$recordId) {
            set_flash('error', 'ID data DAB tidak valid.');
            redirect('index.php?page=dab');
        }

        $stmt = db()->prepare(
            'SELECT * FROM dab WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $recordId]);
        $record = $stmt->fetch();
        if (!$record) {
            set_flash('error', 'Data DAB tidak ditemukan.');
            redirect('index.php?page=dab');
        }

        db()->beginTransaction();
        try {
            db()->prepare('DELETE FROM dab WHERE id = :id')->execute(['id' => $recordId]);
            audit_log((int) $user['id'], 'delete', 'dab', (int) $recordId, 'Menghapus domain bisnis ' . $record['nama_bisnis'], $record, null, true);
            db()->commit();
            set_flash('success', 'Data DAB berhasil dihapus.');
        } catch (Throwable $exception) {
            db()->rollBack();
            error_log('Penghapusan DAB gagal: ' . $exception->getMessage());
            set_flash('error', 'Data DAB gagal dihapus.');
        }
        redirect('index.php?page=dab');
    } else {
        set_flash('error', 'Aksi DAB tidak valid.');
        redirect('index.php?page=dab');
    }
}

$search = trim((string) ($_GET['q'] ?? ''));
$perPageOptions = [10, 25, 50];
$perPage = (int) ($_GET['per_page'] ?? 10);
if (!in_array($perPage, $perPageOptions, true)) {
    $perPage = 10;
}
$currentPage = max(1, (int) ($_GET['p'] ?? 1));
$whereSql = '';
$queryParams = [];
if ($search !== '') {
    $whereSql = " WHERE CONCAT_WS(' ',
        d.nama_bisnis, d.uraian, d.sasaran, d.indikator, d.target, d.realisasi,
        s.kode_skpd, s.nama_skpd, p.kode_program, p.nama_program, r.kode_rab_4, r.nama_rab_4
    ) LIKE :search";
    $queryParams['search'] = '%' . $search . '%';
}

$countStmt = db()->prepare(
    'SELECT COUNT(*)
     FROM dab d
     LEFT JOIN skpd s ON s.id = d.id_skpd
     LEFT JOIN program p ON p.id = d.id_program
     LEFT JOIN rab r ON r.id = d.id_rab' . $whereSql
);
$countStmt->execute($queryParams);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}
$offset = ($currentPage - 1) * $perPage;

$dataSql = 'SELECT
        d.id, d.id_skpd, d.id_program, d.id_rab, d.nama_bisnis, d.uraian, d.sasaran, d.indikator, d.target, d.realisasi,
        d.created_by, d.updated_by, d.created_at, d.updated_at,
        s.kode_skpd, s.nama_skpd,
        p.kode_program, p.nama_program,
        r.kode_rab_4, r.nama_rab_4,
        creator.name AS created_by_name,
        updater.name AS updated_by_name
    FROM dab d
    LEFT JOIN skpd s ON s.id = d.id_skpd
    LEFT JOIN program p ON p.id = d.id_program
    LEFT JOIN rab r ON r.id = d.id_rab
    LEFT JOIN users creator ON creator.id = d.created_by
    LEFT JOIN users updater ON updater.id = d.updated_by' . $whereSql . '
    ORDER BY s.kode_skpd ASC, p.kode_program ASC, d.id ASC
    LIMIT :limit OFFSET :offset';
$dataStmt = db()->prepare($dataSql);
foreach ($queryParams as $key => $value) {
    $dataStmt->bindValue(':' . $key, $value);
}
$dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$dataStmt->execute();
$rows = $dataStmt->fetchAll();

$startRow = $totalRows === 0 ? 0 : $offset + 1;
$endRow = min($offset + $perPage, $totalRows);

$programJson = array_map(static fn (array $program): array => [
    'id' => (int) $program['id'],
    'skpd_id' => (int) $program['id_skpd'],
    'rab_id' => $program['id_rab'] !== null ? (int) $program['id_rab'] : null,
    'label' => dab_option_label($program['kode_program'], $program['nama_program']),
], $programList);
?>

<section class="space-y-5">
    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-red-700">Domain Arsitektur</p>
            <h1 class="mt-1 text-xl font-bold tracking-tight text-slate-900">Domain Arsitektur Bisnis</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola proses bisnis instansi yang terhubung dengan SKPD, program, dan referensi RAB.</p>
        </div>
        <div class="flex gap-2">
            <a href="cetak/cetak_dab.php" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                <i data-lucide="printer" class="h-4 w-4"></i>
                Cetak
            </a>
            <button type="button" class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-100" data-dab-add>
                <i data-lucide="plus" class="h-4 w-4"></i>
                Tambah DAB
            </button>
        </div>
    </div>

    <?php if ($formErrors): ?>
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <div class="flex gap-2">
                <i data-lucide="circle-alert" class="mt-0.5 h-4 w-4 shrink-0"></i>
                <div>
                    <p class="font-semibold">Data belum bisa disimpan.</p>
                    <ul class="mt-1 list-disc pl-5">
                        <?php foreach ($formErrors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-4 py-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="font-semibold text-slate-900">Daftar Domain Bisnis</h2>
                <p class="mt-1 text-sm text-slate-500">Menampilkan <?= number_format($totalRows, 0, ',', '.') ?> data<?= $search !== '' ? ' hasil pencarian' : '' ?>.</p>
            </div>
            <form method="get" class="flex flex-col gap-2 sm:flex-row">
                <input type="hidden" name="page" value="dab">
                <div class="relative">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input type="search" name="q" value="<?= e($search) ?>" placeholder="Cari nama bisnis, SKPD, program..." class="w-full rounded-md border border-slate-300 py-2 pl-9 pr-3 text-xs outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100 sm:w-64">
                </div>
                <select name="per_page" class="rounded-md border border-slate-300 px-3 py-2 text-xs outline-none focus:border-blue-600" aria-label="Jumlah data per halaman">
                    <?php foreach ($perPageOptions as $option): ?>
                        <option value="<?= $option ?>" <?= $perPage === $option ? 'selected' : '' ?>><?= $option ?> data</option>
                    <?php endforeach; ?>
                </select>
                <button class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Tampilkan</button>
                <?php if ($search !== ''): ?><a href="index.php?page=dab" class="inline-flex items-center justify-center rounded-md px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">Reset</a><?php endif; ?>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-[12px]">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="w-24 px-2.5 py-2.5 font-semibold">Kode</th>
                        <th class="px-2.5 py-2.5 font-semibold">Nama Bisnis &amp; Uraian</th>
                        <th class="px-2.5 py-2.5 font-semibold">Sasaran</th>
                        <th class="px-2.5 py-2.5 font-semibold">Indikator</th>
                        <th class="px-2.5 py-2.5 font-semibold">Target</th>
                        <th class="px-2.5 py-2.5 font-semibold">RAB</th>
                        <th class="px-2.5 py-2.5 font-semibold">SKPD/Program</th>
                        <!-- <th class="px-3 py-2.5 font-semibold">Jejak Digital</th> -->
                        <th class="w-28 px-2.5 py-2.5 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    <?php if (!$rows): ?>
                        <tr>
                            <td colspan="8" class="px-3 py-10 text-center text-sm text-slate-500">Belum ada data DAB.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($rows as $index => $row): ?>
                        <?php
                        $record = [
                            'id' => (int) $row['id'],
                            'id_skpd' => $row['id_skpd'] !== null ? (int) $row['id_skpd'] : '',
                            'id_program' => $row['id_program'] !== null ? (int) $row['id_program'] : '',
                            'id_rab' => $row['id_rab'] !== null ? (int) $row['id_rab'] : '',
                            'nama_bisnis' => (string) ($row['nama_bisnis'] ?? ''),
                            'uraian' => (string) ($row['uraian'] ?? ''),
                            'sasaran' => (string) ($row['sasaran'] ?? ''),
                            'indikator' => (string) ($row['indikator'] ?? ''),
                            'target' => (string) ($row['target'] ?? ''),
                            'realisasi' => (string) ($row['realisasi'] ?? ''),
                            'skpd_label' => dab_option_label($row['kode_skpd'], $row['nama_skpd']),
                            'program_label' => dab_option_label($row['kode_program'], $row['nama_program']),
                            'rab_label' => dab_option_label($row['kode_rab_4'], $row['nama_rab_4']),
                            'created_by_name' => (string) ($row['created_by_name'] ?? ''),
                            'updated_by_name' => (string) ($row['updated_by_name'] ?? ''),
                            'created_at' => (string) ($row['created_at'] ?? ''),
                            'updated_at' => (string) ($row['updated_at'] ?? ''),
                        ];
                        ?>
                        <tr class="align-top hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-3 py-2.5 font-semibold text-red-700"><?= e(sprintf('DAB-%03d', $record['id'])) ?></td>
                            <td class="max-w-[260px] px-3 py-2.5">
                                <p class="whitespace-normal break-words font-semibold text-slate-900"><?= e($record['nama_bisnis'] ?: '-') ?></p>
                                <?php if ($record['uraian'] !== ''): ?>
                                    <p class="mt-1 whitespace-normal break-words text-[11px] text-slate-500"><?= e($record['uraian']) ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="max-w-[180px] whitespace-normal break-words px-3 py-2.5 text-[11px] text-slate-600">
                                <?= e($record['sasaran'] ?: '-') ?>
                            </td>
                            <td class="max-w-[180px] whitespace-normal break-words px-3 py-2.5 text-[11px] text-slate-600">
                                <?= e($record['indikator'] ?: '-') ?>
                            </td>
                            <td class="max-w-[150px] whitespace-normal break-words px-3 py-2.5 text-[11px] text-slate-600">
                                <?= e($record['target'] ?: '-') ?>
                            </td>
                            <td class="max-w-[220px] px-3 py-2.5">
                                <p class="whitespace-normal break-words font-medium text-red-700"><?= e($record['rab_label'] ?: '-') ?></p>
                            </td>
                            <td class="max-w-[240px] px-3 py-2.5">
                                <p class="whitespace-normal break-words font-semibold text-slate-800"><?= e($record['skpd_label'] ?: '-') ?></p>
                                <p class="mt-1 whitespace-normal break-words text-[11px] text-slate-500"><?= e($record['program_label'] ?: '-') ?></p>
                            </td>
                            <td class="hidden min-w-[190px] whitespace-normal px-3 py-2.5 text-[11px] leading-5 text-slate-600">
                                <p>
                                    <span class="font-semibold text-slate-800">Dibuat:</span>
                                    <?= e($record['created_by_name'] ?: 'Data lama') ?>
                                    <?php if ($record['created_at'] !== ''): ?>
                                        <span class="block text-slate-400"><?= e(date('d/m/Y H:i', strtotime($record['created_at']))) ?></span>
                                    <?php endif; ?>
                                </p>
                                <p class="mt-1">
                                    <span class="font-semibold text-slate-800">Diubah:</span>
                                    <?= e($record['updated_by_name'] ?: '-') ?>
                                    <?php if ($record['updated_at'] !== ''): ?>
                                        <span class="block text-slate-400"><?= e(date('d/m/Y H:i', strtotime($record['updated_at']))) ?></span>
                                    <?php endif; ?>
                                </p>
                            </td>
                            <td class="px-3 py-2.5">
                                <div class="flex justify-end gap-1.5">
                                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-600 hover:bg-slate-50" title="Lihat" data-dab-view data-record="<?= e(json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
                                        <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                    </button>
                                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100" title="Edit" data-dab-edit data-record="<?= e(json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
                                        <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                    </button>
                                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-red-200 bg-red-50 text-red-700 hover:bg-red-100" title="Hapus" data-dab-delete data-id="<?= (int) $row['id'] ?>" data-name="<?= e($record['nama_bisnis']) ?>">
                                        <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalRows > 0): ?>
            <div class="flex flex-col gap-3 border-t border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <p class="text-sm text-slate-500">
                    Data <?= number_format($offset + 1, 0, ',', '.') ?>&ndash;<?= number_format(min($offset + $perPage, $totalRows), 0, ',', '.') ?>
                    dari <?= number_format($totalRows, 0, ',', '.') ?>
                </p>
                <?php render_numbered_pagination(
                    $currentPage,
                    $totalPages,
                    static fn(int $pageNumber): string => dab_page_url($pageNumber, $search, $perPage)
                ); ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
    [data-modal="dab-form"] input:not([type="hidden"]),
    [data-modal="dab-form"] select,
    [data-modal="dab-form"] textarea {
        padding: .5rem .75rem;
        font-size: .875rem;
    }
</style>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 px-3 py-4 backdrop-blur-sm sm:px-6" data-modal="dab-form" role="dialog" aria-modal="true" aria-labelledby="dab-form-title">
    <form method="post" class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= e($formMode) ?>" data-dab-action>
        <input type="hidden" name="id" value="<?= e($formState['id']) ?>" data-dab-field="id">

        <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white px-4 py-3 sm:px-5">
            <div class="flex min-w-0 items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-700 text-white shadow-sm ring-4 ring-red-50">
                    <i data-lucide="git-branch" class="h-4 w-4"></i>
                </span>
                <div class="min-w-0">
                    <h2 id="dab-form-title" class="text-base font-bold tracking-tight text-slate-900" data-dab-form-title>Tambah Data DAB</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Pilih SKPD, program, dan referensi bisnis yang digunakan.</p>
                </div>
            </div>
            <button type="button" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:bg-slate-50 hover:text-slate-800" data-modal-close aria-label="Tutup">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto bg-slate-50/70 p-4 sm:p-5">
            <div class="grid gap-3">
                <div class="grid gap-3 lg:grid-cols-2">
                    <label class="block">
                        <span class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">SKPD</span>
                        <select name="id_skpd" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-red-600 focus:ring-4 focus:ring-red-100/70" data-dab-field="id_skpd" required>
                            <option value="">Pilih SKPD</option>
                            <?php foreach ($skpdList as $skpd): ?>
                                <option value="<?= (int) $skpd['id'] ?>" <?= (string) $skpd['id'] === (string) $formState['id_skpd'] ? 'selected' : '' ?>>
                                    <?= e(dab_option_label($skpd['kode_skpd'], $skpd['nama_skpd'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Program</span>
                        <select name="id_program" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-red-600 focus:ring-4 focus:ring-red-100/70 disabled:bg-slate-100 disabled:text-slate-400" data-dab-field="id_program" required></select>
                    </label>
                </div>

                <label class="block">
                    <span class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Referensi RAB</span>
                    <select name="id_rab" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-red-600 focus:ring-4 focus:ring-red-100/70" data-dab-field="id_rab" required>
                        <option value="">Pilih RAB</option>
                        <?php foreach ($rabList as $rab): ?>
                            <option value="<?= (int) $rab['id'] ?>" <?= (string) $rab['id'] === (string) $formState['id_rab'] ? 'selected' : '' ?>>
                                <?= e(dab_option_label($rab['kode_rab_4'], $rab['nama_rab_4'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Nama Bisnis</span>
                    <input name="nama_bisnis" type="text" maxlength="255" value="<?= e($formState['nama_bisnis']) ?>" placeholder="Contoh: Pengelolaan perencanaan pembangunan daerah" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-300 focus:border-red-600 focus:ring-4 focus:ring-red-100/70" data-dab-field="nama_bisnis" required>
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Uraian</span>
                    <textarea name="uraian" rows="3" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-red-600 focus:ring-4 focus:ring-red-100/70" data-dab-field="uraian"><?= e($formState['uraian']) ?></textarea>
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Sasaran</span>
                    <input name="sasaran" type="text" value="<?= e($formState['sasaran']) ?>" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-red-600 focus:ring-4 focus:ring-red-100/70" data-dab-field="sasaran">
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Indikator</span>
                    <input name="indikator" type="text" value="<?= e($formState['indikator']) ?>" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-red-600 focus:ring-4 focus:ring-red-100/70" data-dab-field="indikator">
                </label>

                <div class="grid gap-3 lg:grid-cols-2">
                    <label class="block">
                        <span class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Target</span>
                        <input name="target" type="text" maxlength="255" value="<?= e($formState['target']) ?>" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-red-600 focus:ring-4 focus:ring-red-100/70" data-dab-field="target">
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Realisasi</span>
                        <input name="realisasi" type="text" maxlength="255" value="<?= e($formState['realisasi']) ?>" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-red-600 focus:ring-4 focus:ring-red-100/70" data-dab-field="realisasi">
                    </label>
                </div>
            </div>
        </div>

        <div class="flex shrink-0 flex-col-reverse gap-2 border-t border-slate-200 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <!-- <p class="hidden items-center gap-2 text-xs text-slate-500 sm:flex">
                <i data-lucide="info" class="h-4 w-4 text-slate-400"></i>
                RAB otomatis mengikuti program, namun tetap dapat disesuaikan sebelum simpan.
            </p> -->
            <div class="flex justify-end gap-2.5">
                <button type="button" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50" data-modal-close>Batal</button>
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-red-700 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-100">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    <span data-dab-submit-label>Simpan Data</span>
                </button>
            </div>
        </div>
    </form>
</div>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 px-3 py-4 backdrop-blur-sm sm:px-6" data-modal="dab-view" role="dialog" aria-modal="true" aria-labelledby="dab-view-title">
    <div class="flex max-h-[94vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white px-5 py-4 sm:px-6 sm:py-5">
            <div class="min-w-0">
                <h2 id="dab-view-title" class="text-lg font-bold tracking-tight text-slate-900">Detail Data DAB</h2>
                <p class="mt-1 truncate text-sm text-slate-500" data-view-field="nama_bisnis"></p>
            </div>
            <button type="button" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:bg-slate-50 hover:text-slate-800" data-modal-close aria-label="Tutup">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        <div class="grid flex-1 gap-4 overflow-y-auto bg-slate-50/70 p-4 sm:p-6 md:grid-cols-2">
            <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:col-span-2">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Relasi</p>
                <div class="mt-3 grid gap-3 md:grid-cols-3">
                    <p class="rounded-lg bg-slate-50 p-3 text-sm"><span class="block text-xs font-semibold uppercase text-slate-400">SKPD</span><span data-view-field="skpd_label"></span></p>
                    <p class="rounded-lg bg-slate-50 p-3 text-sm"><span class="block text-xs font-semibold uppercase text-slate-400">Program</span><span data-view-field="program_label"></span></p>
                    <p class="rounded-lg bg-slate-50 p-3 text-sm"><span class="block text-xs font-semibold uppercase text-slate-400">RAB</span><span data-view-field="rab_label"></span></p>
                </div>
            </section>
            <?php foreach (['uraian' => 'Uraian', 'sasaran' => 'Sasaran', 'indikator' => 'Indikator', 'target' => 'Target', 'realisasi' => 'Realisasi'] as $field => $label): ?>
                <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm <?= in_array($field, ['uraian', 'sasaran', 'indikator'], true) ? 'md:col-span-2' : '' ?>">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500"><?= e($label) ?></p>
                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-800" data-view-field="<?= e($field) ?>"></p>
                </section>
            <?php endforeach; ?>
        </div>
        <div class="flex shrink-0 justify-end border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-6">
            <button type="button" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-100" data-modal-close>Tutup</button>
        </div>
    </div>
</div>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 px-3 py-4 backdrop-blur-sm sm:px-6" data-modal="dab-delete" role="dialog" aria-modal="true" aria-labelledby="dab-delete-title">
    <form method="post" class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" data-delete-id>
        <div class="p-6 sm:p-7">
            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-red-50 text-red-700 ring-8 ring-red-50/60">
                <i data-lucide="trash-2" class="h-6 w-6"></i>
            </span>
            <h2 id="dab-delete-title" class="mt-5 text-xl font-bold tracking-tight text-slate-900">Hapus Data DAB?</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">Tindakan ini bersifat permanen dan tidak dapat dibatalkan.</p>
            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-sm font-semibold leading-5 text-slate-800" data-delete-name></p>
            </div>
        </div>
        <div class="flex justify-end gap-2.5 border-t border-slate-200 bg-slate-50 px-6 py-4">
            <button type="button" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-100" data-modal-close>Batal</button>
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-100">
                <i data-lucide="trash-2" class="h-4 w-4"></i>
                Ya, Hapus
            </button>
        </div>
    </form>
</div>

<script>
(() => {
    const modals = document.querySelectorAll('[data-modal]');
    const formModal = document.querySelector('[data-modal="dab-form"]');
    const viewModal = document.querySelector('[data-modal="dab-view"]');
    const deleteModal = document.querySelector('[data-modal="dab-delete"]');
    const fields = <?= json_encode(array_merge(['id'], $dabFields), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const programs = <?= json_encode($programJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const postedProgramId = <?= json_encode((string) $formState['id_program']) ?>;

    const openModal = (modal) => {
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    };
    const closeModal = (modal) => {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (![...modals].some((item) => !item.classList.contains('hidden'))) document.body.classList.remove('overflow-hidden');
    };

    const field = (name) => formModal.querySelector(`[data-dab-field="${name}"]`);
    const skpdSelect = field('id_skpd');
    const programSelect = field('id_program');
    const rabSelect = field('id_rab');

    const refreshPrograms = (selectedProgramId = '') => {
        const skpdId = Number(skpdSelect.value || 0);
        programSelect.innerHTML = '';
        const placeholder = new Option(skpdId ? 'Pilih Program' : 'Pilih SKPD terlebih dahulu', '');
        programSelect.add(placeholder);
        programSelect.disabled = !skpdId;

        programs
            .filter((program) => Number(program.skpd_id) === skpdId)
            .forEach((program) => {
                const option = new Option(program.label, String(program.id));
                option.dataset.rabId = program.rab_id ? String(program.rab_id) : '';
                programSelect.add(option);
            });

        if (selectedProgramId) {
            programSelect.value = String(selectedProgramId);
        }
    };

    const autoPickRab = () => {
        const option = programSelect.selectedOptions[0];
        const rabId = option?.dataset?.rabId || '';
        if (rabId) rabSelect.value = rabId;
    };

    const fillForm = (record = {}) => {
        fields.forEach((name) => {
            const input = field(name);
            if (input) input.value = record[name] || '';
        });
        refreshPrograms(record.id_program || '');
        if (record.id_rab) rabSelect.value = record.id_rab;
    };

    skpdSelect?.addEventListener('change', () => {
        refreshPrograms();
        rabSelect.value = '';
    });
    programSelect?.addEventListener('change', autoPickRab);

    document.querySelector('[data-dab-add]')?.addEventListener('click', () => {
        fillForm();
        formModal.querySelector('[data-dab-action]').value = 'create';
        formModal.querySelector('[data-dab-form-title]').textContent = 'Tambah Data DAB';
        formModal.querySelector('[data-dab-submit-label]').textContent = 'Simpan Data';
        openModal(formModal);
    });

    document.querySelectorAll('[data-dab-edit]').forEach((button) => {
        button.addEventListener('click', () => {
            const record = JSON.parse(button.dataset.record || '{}');
            fillForm(record);
            formModal.querySelector('[data-dab-action]').value = 'update';
            formModal.querySelector('[data-dab-form-title]').textContent = 'Edit Data DAB';
            formModal.querySelector('[data-dab-submit-label]').textContent = 'Simpan Perubahan';
            openModal(formModal);
        });
    });

    document.querySelectorAll('[data-dab-view]').forEach((button) => {
        button.addEventListener('click', () => {
            const record = JSON.parse(button.dataset.record || '{}');
            ['nama_bisnis', 'skpd_label', 'program_label', 'rab_label', 'uraian', 'sasaran', 'indikator', 'target', 'realisasi'].forEach((name) => {
                const target = viewModal.querySelector(`[data-view-field="${name}"]`);
                if (target) target.textContent = record[name] || '—';
            });
            openModal(viewModal);
        });
    });

    document.querySelectorAll('[data-dab-delete]').forEach((button) => {
        button.addEventListener('click', () => {
            deleteModal.querySelector('[data-delete-id]').value = button.dataset.id || '';
            deleteModal.querySelector('[data-delete-name]').textContent = button.dataset.name || '';
            openModal(deleteModal);
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach((button) => {
        button.addEventListener('click', () => closeModal(button.closest('[data-modal]')));
    });

    refreshPrograms(postedProgramId);
    <?php if ($openFormModal): ?>
    formModal.querySelector('[data-dab-form-title]').textContent = <?= json_encode($formMode === 'update' ? 'Edit Data DAB' : 'Tambah Data DAB') ?>;
    formModal.querySelector('[data-dab-submit-label]').textContent = <?= json_encode($formMode === 'update' ? 'Simpan Perubahan' : 'Simpan Data') ?>;
    openModal(formModal);
    <?php endif; ?>
})();
</script>
