<?php

declare(strict_types=1);

$raaFields = [
    'kode_raa_1', 'nama_raa_1',
    'kode_raa_2', 'nama_raa_2',
    'kode_raa_3', 'nama_raa_3',
    'kode_raa_4', 'nama_raa_4',
];
$formErrors = [];
$openFormModal = false;
$formMode = 'create';
$formState = array_fill_keys($raaFields, '');
$formState['id'] = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if (in_array($action, ['create', 'update'], true)) {
        $formMode = $action;
        $openFormModal = true;
        $recordId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $formState['id'] = $recordId ?: '';

        foreach ($raaFields as $field) {
            $formState[$field] = trim((string) ($_POST[$field] ?? ''));
            $maxLength = str_starts_with($field, 'kode_') ? 50 : 255;
            $label = str_starts_with($field, 'kode_') ? 'Kode' : 'Nama';
            $level = substr($field, -1);

            $isOptionalLevel4 = $level === '4';
            if ($formState[$field] === '' && !$isOptionalLevel4) {
                $formErrors[] = "{$label} level {$level} wajib diisi.";
            } elseif (mb_strlen($formState[$field]) > $maxLength) {
                $formErrors[] = "{$label} level {$level} maksimal {$maxLength} karakter.";
            }
        }

        if ($action === 'update' && !$recordId) {
            $formErrors[] = 'ID data RAA tidak valid.';
        }

        if (($formState['kode_raa_4'] === '') !== ($formState['nama_raa_4'] === '')) {
            $formErrors[] = 'Kode dan nama level 4 harus diisi berpasangan.';
        }

        if (!$formErrors && $formState['kode_raa_4'] !== '') {
            $duplicateSql = 'SELECT id FROM raa WHERE kode_raa_4 = :kode_raa_4';
            $duplicateParams = ['kode_raa_4' => $formState['kode_raa_4']];
            if ($action === 'update') {
                $duplicateSql .= ' AND id <> :id';
                $duplicateParams['id'] = $recordId;
            }
            $duplicateSql .= ' LIMIT 1';
            $duplicateStmt = db()->prepare($duplicateSql);
            $duplicateStmt->execute($duplicateParams);

            if ($duplicateStmt->fetch()) {
                $formErrors[] = 'Kode RAA level 4 sudah digunakan.';
            }
        }

        $oldValues = null;
        if (!$formErrors && $action === 'update') {
            $oldStmt = db()->prepare(
                'SELECT id, kode_raa_1, nama_raa_1, kode_raa_2, nama_raa_2,
                        kode_raa_3, nama_raa_3, kode_raa_4, nama_raa_4, id_program
                 FROM raa WHERE id = :id LIMIT 1'
            );
            $oldStmt->execute(['id' => $recordId]);
            $oldValues = $oldStmt->fetch();

            if (!$oldValues) {
                $formErrors[] = 'Data RAA yang akan diubah tidak ditemukan.';
            }
        }

        if (!$formErrors) {
            db()->beginTransaction();
            try {
                $params = [];
                foreach ($raaFields as $field) {
                    $params[$field] = $formState[$field];
                }

                if ($action === 'create') {
                    $stmt = db()->prepare(
                        'INSERT INTO raa
                            (kode_raa_1, nama_raa_1, kode_raa_2, nama_raa_2,
                             kode_raa_3, nama_raa_3, kode_raa_4, nama_raa_4)
                         VALUES
                            (:kode_raa_1, :nama_raa_1, :kode_raa_2, :nama_raa_2,
                             :kode_raa_3, :nama_raa_3, :kode_raa_4, :nama_raa_4)'
                    );
                    $stmt->execute($params);
                    $recordId = (int) db()->lastInsertId();
                    $description = 'Menambahkan referensi aplikasi ' . ($formState['kode_raa_4'] ?: $formState['kode_raa_3']);
                    audit_log(
                        (int) $user['id'],
                        'create',
                        'raa',
                        $recordId,
                        $description,
                        null,
                        $params
                    );
                    $successMessage = 'Data RAA berhasil ditambahkan.';
                } else {
                    $params['id'] = $recordId;
                    $stmt = db()->prepare(
                        'UPDATE raa SET
                            kode_raa_1 = :kode_raa_1, nama_raa_1 = :nama_raa_1,
                            kode_raa_2 = :kode_raa_2, nama_raa_2 = :nama_raa_2,
                            kode_raa_3 = :kode_raa_3, nama_raa_3 = :nama_raa_3,
                            kode_raa_4 = :kode_raa_4, nama_raa_4 = :nama_raa_4
                         WHERE id = :id'
                    );
                    $stmt->execute($params);
                    $newValues = $params;
                    unset($newValues['id']);
                    audit_log(
                        (int) $user['id'],
                        'update',
                        'raa',
                        (int) $recordId,
                        'Mengubah referensi aplikasi ' . ($formState['kode_raa_4'] ?: $formState['kode_raa_3']),
                        $oldValues,
                        $newValues
                    );
                    $successMessage = 'Data RAA berhasil diperbarui.';
                }

                db()->commit();
                set_flash('success', $successMessage);
                redirect('index.php?page=raa');
            } catch (Throwable $exception) {
                db()->rollBack();
                error_log('Penyimpanan RAA gagal: ' . $exception->getMessage());
                $formErrors[] = 'Data RAA gagal disimpan. Silakan coba kembali.';
            }
        }
    } elseif ($action === 'delete') {
        $recordId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$recordId) {
            set_flash('error', 'ID data RAA tidak valid.');
            redirect('index.php?page=raa');
        }

        $stmt = db()->prepare(
            'SELECT id, kode_raa_1, nama_raa_1, kode_raa_2, nama_raa_2,
                    kode_raa_3, nama_raa_3, kode_raa_4, nama_raa_4, id_program
             FROM raa WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $recordId]);
        $record = $stmt->fetch();

        if (!$record) {
            set_flash('error', 'Data RAA tidak ditemukan.');
            redirect('index.php?page=raa');
        }

        $referenceStmt = db()->prepare(
            'SELECT COUNT(*) FROM daa WHERE id_raa = :daa_id'
        );
        $referenceStmt->execute(['daa_id' => $recordId]);
        $referenceCount = (int) $referenceStmt->fetchColumn();

        if ($referenceCount > 0) {
            $referenceCode = $record['kode_raa_4'] ?: $record['kode_raa_3'];
            set_flash('error', "Data {$referenceCode} tidak dapat dihapus karena masih digunakan oleh {$referenceCount} data terkait.");
            redirect('index.php?page=raa');
        }

        db()->beginTransaction();
        try {
            db()->prepare('DELETE FROM raa WHERE id = :id')->execute(['id' => $recordId]);
            audit_log(
                (int) $user['id'],
                'delete',
                'raa',
                (int) $recordId,
                'Menghapus referensi aplikasi ' . ($record['kode_raa_4'] ?: $record['kode_raa_3']),
                $record,
                null
            );
            db()->commit();
            set_flash('success', 'Data RAA berhasil dihapus.');
        } catch (Throwable $exception) {
            db()->rollBack();
            error_log('Penghapusan RAA gagal: ' . $exception->getMessage());
            set_flash('error', 'Data RAA gagal dihapus.');
        }
        redirect('index.php?page=raa');
    } else {
        set_flash('error', 'Aksi RAA tidak valid.');
        redirect('index.php?page=raa');
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
        r.kode_raa_1, r.nama_raa_1, r.kode_raa_2, r.nama_raa_2,
        r.kode_raa_3, r.nama_raa_3, r.kode_raa_4, r.nama_raa_4
    ) LIKE :search";
    $queryParams['search'] = '%' . $search . '%';
}

$countStmt = db()->prepare('SELECT COUNT(*) FROM raa r' . $whereSql);
$countStmt->execute($queryParams);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $perPage;

$listStmt = db()->prepare(
    'SELECT r.*,
        0 AS program_count,
        (SELECT COUNT(*) FROM daa d WHERE d.id_raa = r.id) AS business_count
     FROM raa r' . $whereSql . '
     ORDER BY r.kode_raa_4 ASC, r.id ASC
     LIMIT :limit OFFSET :offset'
);
foreach ($queryParams as $key => $value) {
    $listStmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
}
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$rows = $listStmt->fetchAll();

$stats = db()->query(
    'SELECT COUNT(*) total,
        COUNT(DISTINCT kode_raa_1) level_1,
        COUNT(DISTINCT kode_raa_2) level_2,
        COUNT(DISTINCT kode_raa_3) level_3
     FROM raa'
)->fetch();

function raa_page_url(int $pageNumber, string $search, int $perPage): string
{
    return 'index.php?' . http_build_query([
        'page' => 'raa',
        'q' => $search,
        'per_page' => $perPage,
        'p' => $pageNumber,
    ]);
}
?>

<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-sm font-medium text-red-700">Referensi Arsitektur</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Referensi Arsitektur Aplikasi (RAA)</h1>
        <p class="mt-1 text-sm text-slate-500">Kelola struktur referensi aplikasi pemerintahan hingga level 4.</p>
    </div>
    <div class="flex gap-2">
        <a href="cetak_referensi.php?jenis=raa" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-md border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
            <i data-lucide="printer" class="h-4 w-4"></i>Cetak
        </a>
        <button type="button" class="inline-flex items-center justify-center gap-2 rounded-md bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800" data-raa-add>
            <i data-lucide="plus" class="h-4 w-4"></i>Tambah RAA
        </button>
    </div>
</div>

<?php if ($formErrors): ?>
    <div class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
        <?= e($formErrors[0]) ?>
    </div>
<?php endif; ?>

<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <?php
    $summaryCards = [
        ['label' => 'Total RAA', 'value' => $stats['total'], 'icon' => 'layers-3'],
        ['label' => 'Kelompok Level 1', 'value' => $stats['level_1'], 'icon' => 'folder-tree'],
        ['label' => 'Kelompok Level 2', 'value' => $stats['level_2'], 'icon' => 'folder-open'],
        ['label' => 'Kelompok Level 3', 'value' => $stats['level_3'], 'icon' => 'git-branch'],
    ];
    ?>
    <?php foreach ($summaryCards as $card): ?>
        <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                    <i data-lucide="<?= e($card['icon']) ?>" class="h-5 w-5"></i>
                </span>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500"><?= e($card['label']) ?></p>
                    <p class="mt-0.5 text-2xl font-bold text-slate-900"><?= number_format((int) $card['value'], 0, ',', '.') ?></p>
                </div>
            </div>
        </section>
    <?php endforeach; ?>
</div>

<section class="rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-3 border-b border-slate-200 px-4 py-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="font-semibold text-slate-900">Daftar Referensi Aplikasi</h2>
            <p class="mt-1 text-sm text-slate-500">Menampilkan <?= number_format($totalRows, 0, ',', '.') ?> data<?= $search !== '' ? ' hasil pencarian' : '' ?>.</p>
        </div>
        <form method="get" class="flex flex-col gap-2 sm:flex-row">
            <input type="hidden" name="page" value="raa">
            <div class="relative">
                <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                <input type="search" name="q" value="<?= e($search) ?>" placeholder="Cari kode atau nama..." class="w-full rounded-md border border-slate-300 py-2 pl-9 pr-3 text-xs outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100 sm:w-64">
            </div>
            <select name="per_page" class="rounded-md border border-slate-300 px-3 py-2 text-xs outline-none focus:border-blue-600" aria-label="Jumlah data per halaman">
                <?php foreach ($perPageOptions as $option): ?>
                    <option value="<?= $option ?>" <?= $perPage === $option ? 'selected' : '' ?>><?= $option ?> data</option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Tampilkan</button>
            <?php if ($search !== ''): ?>
                <a href="index.php?page=raa" class="inline-flex items-center justify-center rounded-md px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="overflow-x-auto xl:overflow-x-visible">
        <table class="w-full min-w-[1050px] text-left text-xs xl:min-w-0 xl:table-fixed">
            <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="w-10 px-2 py-2.5 text-center">No</th>
                    <th class="w-[17%] px-2.5 py-2.5">Level 1</th>
                    <th class="w-[17%] px-2.5 py-2.5">Level 2</th>
                    <th class="w-[20%] px-2.5 py-2.5">Level 3</th>
                    <th class="w-[28%] px-2.5 py-2.5">Level 4</th>
                    <th class="w-28 px-2 py-2.5 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-slate-500">
                            <i data-lucide="search-x" class="mx-auto mb-2 h-7 w-7 text-slate-400"></i>
                            Data RAA tidak ditemukan.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($rows as $index => $row): ?>
                    <?php
                    $displayCode = $row['kode_raa_4'] ?: $row['kode_raa_3'];
                    $displayName = $row['nama_raa_4'] ?: $row['nama_raa_3'];
                    $recordPayload = [
                        'id' => (int) $row['id'],
                        'kode_raa_1' => $row['kode_raa_1'],
                        'nama_raa_1' => $row['nama_raa_1'],
                        'kode_raa_2' => $row['kode_raa_2'],
                        'nama_raa_2' => $row['nama_raa_2'],
                        'kode_raa_3' => $row['kode_raa_3'],
                        'nama_raa_3' => $row['nama_raa_3'],
                        'kode_raa_4' => $row['kode_raa_4'],
                        'nama_raa_4' => $row['nama_raa_4'],
                        'program_count' => (int) $row['program_count'],
                        'business_count' => (int) $row['business_count'],
                        'display_code' => $displayCode,
                        'display_name' => $displayName,
                    ];
                    $recordJson = json_encode($recordPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    ?>
                    <tr class="align-top hover:bg-slate-50">
                        <td class="px-2 py-3 text-center font-medium text-slate-500"><?= $offset + $index + 1 ?></td>
                        <?php for ($level = 1; $level <= 4; $level++): ?>
                            <td class="break-words px-2.5 py-3">
                                <span class="inline-flex max-w-full break-all rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] font-semibold leading-4 text-slate-700"><?= e($row["kode_raa_{$level}"]) ?></span>
                                <p class="mt-1 text-[11px] leading-4 text-slate-700"><?= e($row["nama_raa_{$level}"]) ?></p>
                            </td>
                        <?php endfor; ?>
                        <td class="px-2 py-3">
                            <div class="flex justify-center gap-1">
                                <button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded border border-slate-300 text-slate-600 hover:bg-slate-100" title="Lihat RAA" aria-label="Lihat <?= e($displayCode) ?>" data-raa-view data-record="<?= e($recordJson) ?>">
                                    <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                </button>
                                <button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded border border-blue-200 text-blue-700 hover:bg-blue-50" title="Edit RAA" aria-label="Edit <?= e($displayCode) ?>" data-raa-edit data-record="<?= e($recordJson) ?>">
                                    <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                </button>
                                <button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded border border-red-200 text-red-700 hover:bg-red-50" title="Hapus RAA" aria-label="Hapus <?= e($displayCode) ?>" data-raa-delete data-id="<?= (int) $row['id'] ?>" data-code="<?= e($displayCode) ?>" data-name="<?= e($displayName) ?>" data-references="<?= (int) $row['program_count'] + (int) $row['business_count'] ?>">
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
                Data <?= number_format($offset + 1, 0, ',', '.') ?>–<?= number_format(min($offset + $perPage, $totalRows), 0, ',', '.') ?>
                dari <?= number_format($totalRows, 0, ',', '.') ?>
            </p>
            <?php render_numbered_pagination(
                $currentPage,
                $totalPages,
                static fn(int $pageNumber): string => raa_page_url($pageNumber, $search, $perPage)
            ); ?>
        </div>
    <?php endif; ?>
</section>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 px-3 py-4 backdrop-blur-sm sm:px-6" data-modal="raa-form" role="dialog" aria-modal="true" aria-labelledby="raa-form-title">
    <form method="post" class="flex max-h-[94vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= e($formMode) ?>" data-raa-action>
        <input type="hidden" name="id" value="<?= e($formState['id']) ?>" data-raa-id>

        <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white px-5 py-4 sm:px-6 sm:py-5">
            <div class="flex min-w-0 items-start gap-3.5">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-700 text-white shadow-sm ring-4 ring-blue-50">
                    <i data-lucide="network" class="h-5 w-5"></i>
                </span>
                <div class="min-w-0">
                    <h2 id="raa-form-title" class="text-lg font-bold tracking-tight text-slate-900" data-raa-form-title>Tambah Data RAA</h2>
                    <p class="mt-1 text-sm text-slate-500">Isi kode dan nama referensi untuk setiap level.</p>
                </div>
            </div>
            <button type="button" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-800" data-modal-close aria-label="Tutup">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto bg-slate-50/70 p-4 sm:p-6">
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="hidden grid-cols-[100px_210px_minmax(0,1fr)] items-center gap-4 border-b border-slate-200 bg-slate-50 px-4 py-2.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500 sm:grid">
                    <span>Hierarki</span>
                    <span>Kode</span>
                    <span>Nama Referensi</span>
                </div>

                <?php for ($level = 1; $level <= 4; $level++): ?>
                    <fieldset class="grid gap-3 border-b border-slate-100 px-4 py-3.5 last:border-b-0 sm:grid-cols-[100px_210px_minmax(0,1fr)] sm:items-center sm:gap-4">
                        <legend class="sr-only">Level <?= $level ?></legend>
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-900 text-xs font-bold text-white"><?= $level ?></span>
                            <span class="text-sm font-semibold text-slate-800">Level <?= $level ?></span>
                        </div>

                        <div>
                            <label for="kode_raa_<?= $level ?>" class="mb-1.5 block text-[10px] font-semibold uppercase tracking-wide text-slate-500 sm:sr-only">Kode level <?= $level ?></label>
                            <div class="relative">
                                <i data-lucide="hash" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                                <input id="kode_raa_<?= $level ?>" name="kode_raa_<?= $level ?>" type="text" maxlength="50" value="<?= e($formState["kode_raa_{$level}"]) ?>" placeholder="RAA.00" class="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-9 pr-3 font-mono text-sm uppercase text-slate-900 shadow-sm outline-none transition placeholder:text-slate-300 focus:border-blue-600 focus:ring-4 focus:ring-blue-100/70" data-raa-field="kode_raa_<?= $level ?>" <?= $level < 4 ? 'required' : '' ?>>
                            </div>
                        </div>

                        <div>
                            <label for="nama_raa_<?= $level ?>" class="mb-1.5 block text-[10px] font-semibold uppercase tracking-wide text-slate-500 sm:sr-only">Nama referensi level <?= $level ?></label>
                            <input id="nama_raa_<?= $level ?>" name="nama_raa_<?= $level ?>" type="text" maxlength="255" value="<?= e($formState["nama_raa_{$level}"]) ?>" placeholder="Masukkan nama referensi level <?= $level ?>" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-300 focus:border-blue-600 focus:ring-4 focus:ring-blue-100/70" data-raa-field="nama_raa_<?= $level ?>" <?= $level < 4 ? 'required' : '' ?>>
                        </div>
                    </fieldset>
                <?php endfor; ?>
            </div>
        </div>

        <div class="flex shrink-0 flex-col-reverse gap-2 border-t border-slate-200 bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <p class="hidden items-center gap-2 text-xs text-slate-500 sm:flex">
                <i data-lucide="info" class="h-4 w-4 text-slate-400"></i>
                Level 1–3 wajib diisi; level 4 bersifat opsional.
            </p>
            <div class="flex justify-end gap-2.5">
                <button type="button" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50" data-modal-close>Batal</button>
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-100">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    <span data-raa-submit-label>Simpan Data</span>
                </button>
            </div>
        </div>
    </form>
</div>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 px-3 py-4 backdrop-blur-sm sm:px-6" data-modal="raa-view" role="dialog" aria-modal="true" aria-labelledby="raa-view-title">
    <div class="flex max-h-[94vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white px-5 py-4 sm:px-6 sm:py-5">
            <div class="flex min-w-0 items-start gap-3.5">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white shadow-sm ring-4 ring-slate-100">
                    <i data-lucide="file-search-2" class="h-5 w-5"></i>
                </span>
                <div class="min-w-0">
                    <h2 id="raa-view-title" class="text-lg font-bold tracking-tight text-slate-900">Detail Data RAA</h2>
                    <p class="mt-1 truncate font-mono text-xs font-semibold text-red-700 sm:text-sm" data-view-code></p>
                </div>
            </div>
            <button type="button" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:bg-slate-50 hover:text-slate-800" data-modal-close aria-label="Tutup">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        <div class="grid flex-1 gap-4 overflow-y-auto bg-slate-50/70 p-4 sm:p-6 md:grid-cols-2">
            <?php for ($level = 1; $level <= 4; $level++): ?>
                <section class="relative overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <span class="absolute right-3 top-2 text-5xl font-black text-slate-50" aria-hidden="true"><?= $level ?></span>
                    <div class="relative">
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-900 text-xs font-bold text-white"><?= $level ?></span>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Hierarki Level <?= $level ?></p>
                        </div>
                        <p class="mt-4 inline-flex rounded-md bg-red-50 px-2 py-1 font-mono text-xs font-bold text-red-700 ring-1 ring-inset ring-red-100" data-view-field="kode_raa_<?= $level ?>"></p>
                        <p class="mt-2 text-sm font-medium leading-6 text-slate-800" data-view-field="nama_raa_<?= $level ?>"></p>
                    </div>
                </section>
            <?php endfor; ?>
        </div>
        <div class="grid shrink-0 gap-3 border-t border-slate-200 bg-white px-5 py-4 text-sm sm:grid-cols-2 sm:px-6">
            <div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-blue-700 shadow-sm"><i data-lucide="clipboard-list" class="h-4 w-4"></i></span>
                <p class="text-slate-600">Relasi program <strong class="ml-1 text-slate-900" data-view-programs>0</strong></p>
            </div>
            <div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-emerald-700 shadow-sm"><i data-lucide="workflow" class="h-4 w-4"></i></span>
                <p class="text-slate-600">Domain aplikasi terkait <strong class="ml-1 text-slate-900" data-view-businesses>0</strong></p>
            </div>
        </div>
        <div class="flex shrink-0 justify-end border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-6">
            <button type="button" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-100" data-modal-close>Tutup</button>
        </div>
    </div>
</div>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 px-3 py-4 backdrop-blur-sm sm:px-6" data-modal="raa-delete" role="dialog" aria-modal="true" aria-labelledby="raa-delete-title">
    <form method="post" class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" data-delete-id>
        <div class="p-6 sm:p-7">
            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-red-50 text-red-700 ring-8 ring-red-50/60">
                <i data-lucide="trash-2" class="h-6 w-6"></i>
            </span>
            <h2 id="raa-delete-title" class="mt-5 text-xl font-bold tracking-tight text-slate-900">Hapus Data RAA?</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">Tindakan ini bersifat permanen dan tidak dapat dibatalkan.</p>
            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="font-mono text-xs font-bold text-red-700" data-delete-code></p>
                <p class="mt-1.5 text-sm font-medium leading-5 text-slate-800" data-delete-name></p>
            </div>
            <p class="mt-4 hidden items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3.5 py-3 text-sm leading-5 text-amber-800" data-delete-warning>
                <i data-lucide="shield-alert" class="mt-0.5 h-4 w-4 shrink-0"></i>
                <span>Data ini masih memiliki <strong data-delete-references></strong> relasi sehingga tidak dapat dihapus.</span>
            </p>
        </div>
        <div class="flex justify-end gap-2.5 border-t border-slate-200 bg-slate-50 px-6 py-4">
            <button type="button" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-100" data-modal-close>Batal</button>
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-100 disabled:cursor-not-allowed disabled:opacity-50" data-delete-submit>
                <i data-lucide="trash-2" class="h-4 w-4"></i>
                Ya, Hapus
            </button>
        </div>
    </form>
</div>

<script>
(() => {
    const modals = document.querySelectorAll('[data-modal]');
    const formModal = document.querySelector('[data-modal="raa-form"]');
    const viewModal = document.querySelector('[data-modal="raa-view"]');
    const deleteModal = document.querySelector('[data-modal="raa-delete"]');
    const fields = <?= json_encode($raaFields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

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
        if (![...modals].some((item) => !item.classList.contains('hidden'))) {
            document.body.classList.remove('overflow-hidden');
        }
    };

    const fillForm = (record = {}) => {
        fields.forEach((field) => {
            const input = formModal.querySelector(`[data-raa-field="${field}"]`);
            if (input) input.value = record[field] || '';
        });
        formModal.querySelector('[data-raa-id]').value = record.id || '';
    };

    document.querySelector('[data-raa-add]')?.addEventListener('click', () => {
        fillForm();
        formModal.querySelector('[data-raa-action]').value = 'create';
        formModal.querySelector('[data-raa-form-title]').textContent = 'Tambah Data RAA';
        formModal.querySelector('[data-raa-submit-label]').textContent = 'Simpan Data';
        openModal(formModal);
    });

    document.querySelectorAll('[data-raa-edit]').forEach((button) => {
        button.addEventListener('click', () => {
            const record = JSON.parse(button.dataset.record || '{}');
            fillForm(record);
            formModal.querySelector('[data-raa-action]').value = 'update';
            formModal.querySelector('[data-raa-form-title]').textContent = 'Edit Data RAA';
            formModal.querySelector('[data-raa-submit-label]').textContent = 'Simpan Perubahan';
            openModal(formModal);
        });
    });

    document.querySelectorAll('[data-raa-view]').forEach((button) => {
        button.addEventListener('click', () => {
            const record = JSON.parse(button.dataset.record || '{}');
            viewModal.querySelector('[data-view-code]').textContent = `${record.display_code || ''} · ${record.display_name || ''}`;
            fields.forEach((field) => {
                const target = viewModal.querySelector(`[data-view-field="${field}"]`);
                if (target) target.textContent = record[field] || '—';
            });
            viewModal.querySelector('[data-view-programs]').textContent = record.program_count || 0;
            viewModal.querySelector('[data-view-businesses]').textContent = record.business_count || 0;
            openModal(viewModal);
        });
    });

    document.querySelectorAll('[data-raa-delete]').forEach((button) => {
        button.addEventListener('click', () => {
            const references = Number(button.dataset.references || 0);
            deleteModal.querySelector('[data-delete-id]').value = button.dataset.id || '';
            deleteModal.querySelector('[data-delete-code]').textContent = button.dataset.code || '';
            deleteModal.querySelector('[data-delete-name]').textContent = button.dataset.name || '';
            deleteModal.querySelector('[data-delete-references]').textContent = references;
            const warning = deleteModal.querySelector('[data-delete-warning]');
            warning.classList.toggle('hidden', references === 0);
            warning.classList.toggle('flex', references > 0);
            deleteModal.querySelector('[data-delete-submit]').disabled = references > 0;
            openModal(deleteModal);
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach((button) => {
        button.addEventListener('click', () => closeModal(button.closest('[data-modal]')));
    });

    modals.forEach((modal) => {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) closeModal(modal);
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            modals.forEach(closeModal);
        }
    });

    <?php if ($openFormModal): ?>
    formModal.querySelector('[data-raa-form-title]').textContent = <?= json_encode($formMode === 'update' ? 'Edit Data RAA' : 'Tambah Data RAA') ?>;
    formModal.querySelector('[data-raa-submit-label]').textContent = <?= json_encode($formMode === 'update' ? 'Simpan Perubahan' : 'Simpan Data') ?>;
    openModal(formModal);
    <?php endif; ?>
})();
</script>
