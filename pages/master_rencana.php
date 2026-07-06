<?php

declare(strict_types=1);

$masterConfigs = [
    'aspek' => [
        'table' => 'aspek',
        'title' => 'Aspek',
        'icon' => 'layers-3',
        'description' => 'Kelola kelompok aspek yang digunakan dalam penyusunan peta rencana.',
        'name_field' => 'nama_aspek',
        'name_label' => 'Nama Aspek',
    ],
    'indikator' => [
        'table' => 'indikator',
        'title' => 'Indikator',
        'icon' => 'list-checks',
        'description' => 'Kelola indikator peta rencana dan keterkaitannya dengan aspek.',
        'name_field' => 'nama_indikator',
        'name_label' => 'Nama Indikator',
    ],
];

$config = $masterConfigs[$page] ?? null;
if (!$config) {
    http_response_code(404);
    exit('Konfigurasi master peta rencana tidak ditemukan.');
}

function master_rencana_int(mixed $value): ?int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id ? (int) $id : null;
}

function master_rencana_label(mixed $code, mixed $name): string
{
    $code = trim((string) $code);
    $name = trim((string) $name);
    return trim($code . ($code !== '' && $name !== '' ? ' - ' : '') . $name);
}

function master_rencana_url(string $route, int $pageNumber, string $search, int $perPage): string
{
    return 'index.php?' . http_build_query([
        'page' => $route,
        'q' => $search,
        'per_page' => $perPage,
        'p' => $pageNumber,
    ]);
}

$table = $config['table'];
$nameField = $config['name_field'];
$isIndicator = $page === 'indikator';
$aspekList = db()->query('SELECT id, nama_aspek FROM aspek ORDER BY nama_aspek')->fetchAll();
$aspekIds = array_flip(array_map('intval', array_column($aspekList, 'id')));
$skpdList = $isIndicator
    ? db()->query('SELECT id, kode_skpd, nama_skpd FROM skpd ORDER BY kode_skpd, nama_skpd')->fetchAll()
    : [];
$skpdIds = array_flip(array_map('intval', array_column($skpdList, 'id')));
$formErrors = [];
$openFormModal = false;
$formMode = 'create';
$formState = ['id' => '', 'nama' => '', 'id_aspek' => '', 'id_skpd' => '', 'deskripsi_indikator' => '', 'koordinator' => '', 'bobot' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if (in_array($action, ['create', 'update'], true)) {
        $formMode = $action;
        $openFormModal = true;
        $recordId = master_rencana_int($_POST['id'] ?? null);
        $formState = [
            'id' => $recordId ?: '',
            'nama' => trim((string) ($_POST['nama'] ?? '')),
            'id_aspek' => trim((string) ($_POST['id_aspek'] ?? '')),
            'id_skpd' => trim((string) ($_POST['id_skpd'] ?? '')),
            'deskripsi_indikator' => trim((string) ($_POST['deskripsi_indikator'] ?? '')),
            'koordinator' => trim((string) ($_POST['koordinator'] ?? '')),
            'bobot' => trim((string) ($_POST['bobot'] ?? '')),
        ];
        $idAspek = $isIndicator ? master_rencana_int($formState['id_aspek']) : null;
        $idSkpd = $isIndicator ? master_rencana_int($formState['id_skpd']) : null;

        if ($formState['nama'] === '') {
            $formErrors[] = $config['name_label'] . ' wajib diisi.';
        } elseif (mb_strlen($formState['nama']) > ($isIndicator ? 500 : 255)) {
            $formErrors[] = $config['name_label'] . ' terlalu panjang.';
        }
        if ($isIndicator && (!$idAspek || !isset($aspekIds[$idAspek]))) {
            $formErrors[] = 'Aspek wajib dipilih.';
        }
        if ($isIndicator && (!$idSkpd || !isset($skpdIds[$idSkpd]))) {
            $formErrors[] = 'SKPD wajib dipilih.';
        }
        if ($isIndicator && mb_strlen($formState['koordinator']) > 255) {
            $formErrors[] = 'Koordinator terlalu panjang.';
        }
        if ($isIndicator && ($formState['bobot'] === '' || !is_numeric($formState['bobot'])
            || (float) $formState['bobot'] < 0 || (float) $formState['bobot'] > 100)) {
            $formErrors[] = 'Bobot harus berupa angka antara 0 sampai 100 persen.';
        }
        if ($action === 'update' && !$recordId) {
            $formErrors[] = 'ID data tidak valid.';
        }

        $oldValues = null;
        if (!$formErrors && $action === 'update') {
            $stmt = db()->prepare("SELECT * FROM {$table} WHERE id=:id");
            $stmt->execute(['id' => $recordId]);
            $oldValues = $stmt->fetch();
            if (!$oldValues) $formErrors[] = 'Data yang akan diubah tidak ditemukan.';
        }

        if (!$formErrors) {
            db()->beginTransaction();
            try {
                $params = [
                    $nameField => $formState['nama'],
                    'updated_by' => (int) $user['id'],
                ];
                if ($isIndicator) {
                    $params['id_aspek'] = $idAspek;
                    $params['id_skpd'] = $idSkpd;
                    $params['deskripsi_indikator'] = $formState['deskripsi_indikator'] ?: null;
                    $params['koordinator'] = $formState['koordinator'] ?: null;
                    $params['bobot'] = (float) $formState['bobot'];
                }

                if ($action === 'create') {
                    $params['created_by'] = (int) $user['id'];
                    $columns = array_keys($params);
                    db()->prepare(
                        "INSERT INTO {$table} (" . implode(',', $columns) . ') VALUES (:' . implode(',:', $columns) . ')'
                    )->execute($params);
                    $recordId = (int) db()->lastInsertId();
                } else {
                    $params['id'] = $recordId;
                    $sets = [];
                    foreach (array_keys($params) as $field) {
                        if ($field !== 'id') $sets[] = "{$field}=:{$field}";
                    }
                    db()->prepare("UPDATE {$table} SET " . implode(',', $sets) . ' WHERE id=:id')->execute($params);
                }

                $stmt = db()->prepare("SELECT * FROM {$table} WHERE id=:id");
                $stmt->execute(['id' => $recordId]);
                $newValues = $stmt->fetch() ?: $params;
                audit_log(
                    (int) $user['id'],
                    $action,
                    $table,
                    $recordId,
                    ($action === 'create' ? 'Menambahkan ' : 'Mengubah ') . $config['title'] . ': ' . $formState['nama'],
                    $oldValues,
                    $newValues,
                    true
                );
                db()->commit();
                set_flash('success', $action === 'create' ? 'Data berhasil ditambahkan.' : 'Data berhasil diperbarui.');
                redirect('index.php?page=' . $page);
            } catch (PDOException $exception) {
                db()->rollBack();
                if ((string) $exception->getCode() === '23000') {
                    $formErrors[] = $config['name_label'] . ' tersebut sudah tersedia.';
                } else {
                    error_log("Penyimpanan {$table} gagal: " . $exception->getMessage());
                    $formErrors[] = 'Data gagal disimpan. Silakan coba kembali.';
                }
            } catch (Throwable $exception) {
                db()->rollBack();
                error_log("Penyimpanan {$table} gagal: " . $exception->getMessage());
                $formErrors[] = 'Data gagal disimpan. Silakan coba kembali.';
            }
        }
    } elseif ($action === 'delete') {
        $recordId = master_rencana_int($_POST['id'] ?? null);
        $stmt = db()->prepare("SELECT * FROM {$table} WHERE id=:id");
        $stmt->execute(['id' => $recordId]);
        $record = $stmt->fetch();
        if (!$recordId || !$record) {
            set_flash('error', 'Data tidak ditemukan.');
            redirect('index.php?page=' . $page);
        }

        if ($isIndicator) {
            $stmt = db()->prepare(
                'SELECT (SELECT COUNT(*) FROM peta_rencana WHERE id_indikator=:id_rencana)
                    + (SELECT COUNT(*) FROM pemdi_level WHERE id_indikator=:id_level)'
            );
            $stmt->execute(['id_rencana' => $recordId, 'id_level' => $recordId]);
            $referenceCount = (int) $stmt->fetchColumn();
        } else {
            $stmt = db()->prepare(
                'SELECT (SELECT COUNT(*) FROM indikator WHERE id_aspek=:id_indikator)
                    + (SELECT COUNT(*) FROM peta_rencana WHERE id_aspek=:id_rencana)'
            );
            $stmt->execute(['id_indikator' => $recordId, 'id_rencana' => $recordId]);
            $referenceCount = (int) $stmt->fetchColumn();
        }
        if ($referenceCount > 0) {
            set_flash('error', 'Data masih digunakan dan tidak dapat dihapus.');
            redirect('index.php?page=' . $page);
        }

        db()->beginTransaction();
        try {
            db()->prepare("DELETE FROM {$table} WHERE id=:id")->execute(['id' => $recordId]);
            audit_log(
                (int) $user['id'],
                'delete',
                $table,
                $recordId,
                'Menghapus ' . $config['title'] . ': ' . $record[$nameField],
                $record,
                null,
                true
            );
            db()->commit();
            set_flash('success', 'Data berhasil dihapus.');
        } catch (Throwable $exception) {
            db()->rollBack();
            error_log("Penghapusan {$table} gagal: " . $exception->getMessage());
            set_flash('error', 'Data gagal dihapus.');
        }
        redirect('index.php?page=' . $page);
    }
}

$search = trim((string) ($_GET['q'] ?? ''));
$perPageOptions = [10, 20, 25, 50];
$defaultPerPage = $isIndicator ? 20 : 10;
$perPage = (int) ($_GET['per_page'] ?? $defaultPerPage);
if (!in_array($perPage, $perPageOptions, true)) $perPage = $defaultPerPage;
$currentPage = max(1, (int) ($_GET['p'] ?? 1));
$whereSql = '';
$queryParams = [];
if ($search !== '') {
    $whereSql = $isIndicator
        ? ' WHERE CONCAT_WS(" ",m.nama_indikator,m.deskripsi_indikator,m.koordinator,a.nama_aspek,s.kode_skpd,s.nama_skpd) LIKE :search'
        : ' WHERE m.nama_aspek LIKE :search';
    $queryParams['search'] = '%' . $search . '%';
}
$joins = $isIndicator
    ? ' FROM indikator m INNER JOIN aspek a ON a.id=m.id_aspek LEFT JOIN skpd s ON s.id=m.id_skpd'
    : ' FROM aspek m';
$stmt = db()->prepare('SELECT COUNT(*)' . $joins . $whereSql);
$stmt->execute($queryParams);
$totalRows = (int) $stmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $perPage;

$selectSql = $isIndicator
    ? 'SELECT m.*,a.nama_aspek,s.kode_skpd,s.nama_skpd,
        (SELECT COALESCE(SUM(e.skor),0)
         FROM pemdi_level l
         INNER JOIN pemdi_evidence e ON e.id_pemdi_level=l.id
         WHERE l.id_indikator=m.id) skor_maksimal,
        (SELECT COALESCE(SUM(CASE
            WHEN e.status_upload="sudah_diunggah"
                AND e.file_upload IS NOT NULL
                AND TRIM(e.file_upload)<>""
            THEN e.skor ELSE 0 END),0)
         FROM pemdi_level l
         INNER JOIN pemdi_evidence e ON e.id_pemdi_level=l.id
         WHERE l.id_indikator=m.id) capaian_skor,
        ((SELECT COUNT(*) FROM peta_rencana p WHERE p.id_indikator=m.id)
        +(SELECT COUNT(*) FROM pemdi_level l WHERE l.id_indikator=m.id)) reference_count'
    : 'SELECT m.*,
        ((SELECT COUNT(*) FROM indikator i WHERE i.id_aspek=m.id)
        +(SELECT COUNT(*) FROM peta_rencana p WHERE p.id_aspek=m.id)) reference_count,
        (SELECT COUNT(*) FROM indikator i WHERE i.id_aspek=m.id) indikator_count,
        (SELECT COALESCE(SUM(i.bobot),0) FROM indikator i WHERE i.id_aspek=m.id) total_bobot';
$orderSql = $isIndicator ? 'a.id ASC,m.id ASC' : 'm.id ASC';
$stmt = db()->prepare($selectSql . $joins . $whereSql . " ORDER BY {$orderSql} LIMIT :limit OFFSET :offset");
foreach ($queryParams as $key => $value) $stmt->bindValue(':' . $key, $value);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();
$startRow = $totalRows ? $offset + 1 : 0;
$endRow = min($offset + $perPage, $totalRows);
?>

<section class="space-y-5">
    <div class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-red-700">Peta Rencana</p>
            <h1 class="mt-1 text-xl font-bold"><?= e($config['title']) ?></h1>
            <p class="mt-1 text-sm text-slate-500"><?= e($config['description']) ?></p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="cetak/cetak_master_rencana.php?<?= e(http_build_query(['type' => $page, 'q' => $search ?? ''])) ?>" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i data-lucide="printer" class="h-4 w-4"></i><?= $isIndicator ? 'Cetak Indikator' : 'Cetak' ?></a>
            <?php if ($isIndicator): ?><a href="cetak/cetak_peta_rencana.php" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i data-lucide="map" class="h-4 w-4"></i>Cetak Peta Rencana</a><?php endif; ?>
            <button type="button" class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-500" data-master-add <?= $isIndicator ? 'disabled aria-disabled="true" title="Penambahan indikator dinonaktifkan"' : '' ?>>
                <i data-lucide="plus" class="h-4 w-4"></i>Tambah Data
            </button>
        </div>
    </div>

    <?php if ($formErrors): ?>
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <p class="font-semibold">Data belum bisa disimpan.</p>
            <ul class="mt-1 list-disc pl-5"><?php foreach ($formErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-slate-900">Daftar <?= e($config['title']) ?></h2>
                <p class="mt-1 text-sm text-slate-500"><?= number_format($totalRows, 0, ',', '.') ?> data.</p>
            </div>
            <form method="get" class="flex flex-col gap-2 sm:flex-row">
                <input type="hidden" name="page" value="<?= e($page) ?>">
                <div class="relative">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input type="search" name="q" value="<?= e($search) ?>" placeholder="Cari <?= e(strtolower($config['title'])) ?>..." class="w-full rounded-md border border-slate-300 py-2 pl-9 pr-3 text-xs outline-none focus:border-blue-600 sm:w-64">
                </div>
                <select name="per_page" class="rounded-md border border-slate-300 px-3 py-2 text-xs">
                    <?php foreach ($perPageOptions as $option): ?><option value="<?= $option ?>" <?= $perPage === $option ? 'selected' : '' ?>><?= $option ?> data</option><?php endforeach; ?>
                </select>
                <button class="rounded-md border border-slate-300 px-3 py-2 text-xs font-semibold hover:bg-slate-50">Tampilkan</button>
                <?php if ($search !== ''): ?><a href="index.php?page=<?= e($page) ?>" class="inline-flex items-center justify-center px-3 py-2 text-xs font-semibold text-red-700">Reset</a><?php endif; ?>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">No</th>
                        <th class="px-4 py-3"><?= e($config['name_label']) ?></th>
                        <?php if ($isIndicator): ?><th class="px-4 py-3">SKPD & Koordinator</th><th class="px-4 py-3">Bobot</th><th class="px-4 py-3">Skor</th><?php else: ?><th class="px-4 py-3">Jumlah Indikator</th><th class="px-4 py-3">Bobot</th><?php endif; ?>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php $tableColumnCount = $isIndicator ? 6 : 5; ?>
                    <?php if (!$rows): ?><tr><td colspan="<?= $tableColumnCount ?>" class="p-10 text-center text-slate-500">Belum ada data.</td></tr><?php endif; ?>
                    <?php $currentAspectId = null; foreach ($rows as $index => $row):
                        $record = [
                            'id' => (int) $row['id'],
                            'nama' => (string) $row[$nameField],
                            'id_aspek' => $isIndicator ? (int) $row['id_aspek'] : '',
                            'nama_aspek' => $isIndicator ? (string) $row['nama_aspek'] : '',
                            'id_skpd' => $isIndicator ? (string) ($row['id_skpd'] ?? '') : '',
                            'skpd_label' => $isIndicator ? master_rencana_label($row['kode_skpd'] ?? '', $row['nama_skpd'] ?? '') : '',
                            'deskripsi_indikator' => $isIndicator ? (string) ($row['deskripsi_indikator'] ?? '') : '',
                            'koordinator' => $isIndicator ? (string) ($row['koordinator'] ?? '') : '',
                            'bobot' => $isIndicator ? (string) ($row['bobot'] ?? '0') : '',
                        ];
                    ?>
                        <?php if ($isIndicator && $currentAspectId !== (int) $row['id_aspek']): $currentAspectId = (int) $row['id_aspek']; ?>
                            <tr class="bg-slate-100/90">
                                <td colspan="<?= $tableColumnCount ?>" class="border-y border-slate-200 px-4 py-2.5">
                                    <div class="flex items-center gap-2"><i data-lucide="layers-3" class="h-4 w-4 text-red-700"></i><span class="text-xs font-bold uppercase text-slate-700">Aspek: <?= e($row['nama_aspek']) ?></span></div>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 text-slate-500"><?= $offset + $index + 1 ?></td>
                            <td class="max-w-xl whitespace-normal px-4 py-3"><p class="font-semibold text-slate-900"><?= e($row[$nameField]) ?></p><?php if ($isIndicator && !empty($row['deskripsi_indikator'])): ?><p class="mt-1 text-xs text-slate-500"><?= e($row['deskripsi_indikator']) ?></p><?php endif; ?></td>
                            <?php if ($isIndicator): ?><td class="max-w-xs whitespace-normal px-4 py-3 text-slate-600"><p class="text-xs font-medium text-slate-800"><?= e(master_rencana_label($row['kode_skpd'] ?? '', $row['nama_skpd'] ?? '') ?: '-') ?></p><p class="mt-1 text-xs text-slate-500"><?= e($row['koordinator'] ?: '-') ?></p></td><td class="px-4 py-3 font-semibold text-red-700"><?= e(number_format((float) $row['bobot'], 2, ',', '.')) ?>%</td><td class="whitespace-nowrap px-4 py-3 font-semibold text-amber-700" title="Capaian Skor / Skor Maksimal"><?= e(number_format((float) $row['capaian_skor'], 2, ',', '.')) ?>/<?= e(number_format((float) $row['skor_maksimal'], 2, ',', '.')) ?></td><?php else: ?><td class="px-4 py-3 text-slate-600"><?= (int) $row['indikator_count'] ?></td><td class="px-4 py-3 font-semibold text-red-700"><?= e(number_format((float) $row['total_bobot'], 2, ',', '.')) ?>%</td><?php endif; ?>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1.5">
                                    <?php if ($isIndicator): ?>
                                        <a href="index.php?page=peta-rencana&amp;id_indikator=<?= (int) $row['id'] ?>" class="master-action border-emerald-200 bg-emerald-50 text-emerald-700" title="Peta Rencana" aria-label="Peta Rencana <?= e($row[$nameField]) ?>"><i data-lucide="map"></i></a>
                                        <a href="index.php?page=pemdi-evidence&amp;id_indikator=<?= (int) $row['id'] ?>" class="master-action border-amber-200 bg-amber-50 text-amber-700" title="Bukti Dukung" aria-label="Bukti Dukung <?= e($row[$nameField]) ?>"><i data-lucide="file-check-2"></i></a>
                                        <button type="button" class="master-action border-blue-200 bg-blue-50 text-blue-700" title="Edit" data-master-edit data-record="<?= e(json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"><i data-lucide="pencil"></i></button>
                                    <?php else: ?>
                                        <button type="button" class="master-action" title="Lihat" data-master-view data-record="<?= e(json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"><i data-lucide="eye"></i></button>
                                        <button type="button" class="master-action border-blue-200 bg-blue-50 text-blue-700" title="Edit" data-master-edit data-record="<?= e(json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"><i data-lucide="pencil"></i></button>
                                        <button type="button" class="master-action border-red-200 bg-red-50 text-red-700 disabled:opacity-40" title="<?= (int) $row['reference_count'] ? 'Data masih digunakan' : 'Hapus' ?>" data-master-delete data-id="<?= (int) $row['id'] ?>" data-name="<?= e($row[$nameField]) ?>" <?= (int) $row['reference_count'] ? 'disabled' : '' ?>><i data-lucide="trash-2"></i></button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t p-4 text-sm text-slate-500">
            <p>Menampilkan <?= $startRow ?>-<?= $endRow ?> dari <?= $totalRows ?> data</p>
            <?php render_numbered_pagination(
                $currentPage,
                $totalPages,
                static fn(int $pageNumber): string => master_rencana_url($page, $pageNumber, $search, $perPage)
            ); ?>
        </div>
    </div>
</section>

<style>
.master-control{width:100%;border:1px solid #cbd5e1;border-radius:.5rem;background:#fff;padding:.625rem .75rem;font-size:.875rem}.master-control:focus{border-color:#dc2626;box-shadow:0 0 0 4px rgb(254 226 226/.7);outline:none}.master-action{display:inline-flex;width:2rem;height:2rem;align-items:center;justify-content:center;border-width:1px;border-radius:.375rem}.master-action svg{width:.875rem;height:.875rem}
</style>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 p-4" data-modal="master-form">
    <form method="post" class="w-full max-w-xl overflow-hidden rounded-lg bg-white shadow-2xl">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= e($formMode) ?>" data-master-action>
        <input type="hidden" name="id" value="<?= e($formState['id']) ?>" data-master-field="id">
        <header class="flex items-center justify-between border-b px-5 py-4">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-700 text-white"><i data-lucide="<?= e($config['icon']) ?>" class="h-4 w-4"></i></span>
                <h2 class="font-bold" data-master-title>Tambah <?= e($config['title']) ?></h2>
            </div>
            <button type="button" data-modal-close aria-label="Tutup"><i data-lucide="x"></i></button>
        </header>
        <div class="space-y-4 bg-slate-50 p-5">
            <?php if ($isIndicator): ?>
                <label class="block"><span class="mb-1 block text-xs font-semibold uppercase text-slate-500">Aspek</span><select name="id_aspek" class="master-control" data-master-field="id_aspek" required><option value="">Pilih Aspek</option><?php foreach ($aspekList as $aspek): ?><option value="<?= (int) $aspek['id'] ?>"><?= e($aspek['nama_aspek']) ?></option><?php endforeach; ?></select></label>
                <label class="block"><span class="mb-1 block text-xs font-semibold uppercase text-slate-500">SKPD</span><select name="id_skpd" class="master-control" data-master-field="id_skpd" required><option value="">Pilih SKPD</option><?php foreach ($skpdList as $skpd): ?><option value="<?= (int) $skpd['id'] ?>"><?= e(master_rencana_label($skpd['kode_skpd'], $skpd['nama_skpd'])) ?></option><?php endforeach; ?></select></label>
            <?php endif; ?>
            <label class="block"><span class="mb-1 block text-xs font-semibold uppercase text-slate-500"><?= e($config['name_label']) ?></span><textarea name="nama" rows="<?= $isIndicator ? 3 : 2 ?>" maxlength="<?= $isIndicator ? 500 : 255 ?>" class="master-control" data-master-field="nama" required></textarea></label>
            <?php if ($isIndicator): ?>
                <label class="block"><span class="mb-1 block text-xs font-semibold uppercase text-slate-500">Deskripsi Indikator</span><textarea name="deskripsi_indikator" rows="3" class="master-control" data-master-field="deskripsi_indikator"></textarea></label>
                <label class="block"><span class="mb-1 block text-xs font-semibold uppercase text-slate-500">Koordinator</span><input name="koordinator" type="text" maxlength="255" class="master-control" data-master-field="koordinator"></label>
                <label class="block"><span class="mb-1 block text-xs font-semibold uppercase text-slate-500">Bobot (%)</span><input name="bobot" type="number" min="0" max="100" step="0.01" class="master-control" data-master-field="bobot" required></label>
            <?php endif; ?>
        </div>
        <footer class="flex justify-end gap-2 border-t px-5 py-3">
            <button type="button" class="rounded-lg border px-4 py-2 text-sm font-semibold" data-modal-close>Batal</button>
            <button class="rounded-lg bg-red-700 px-5 py-2 text-sm font-semibold text-white">Simpan</button>
        </footer>
    </form>
</div>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 p-4" data-modal="master-view">
    <div class="w-full max-w-xl overflow-hidden rounded-lg bg-white">
        <header class="flex items-center justify-between border-b p-4"><h2 class="font-bold">Detail <?= e($config['title']) ?></h2><button type="button" data-modal-close><i data-lucide="x"></i></button></header>
        <div class="space-y-3 bg-slate-50 p-5">
            <?php if ($isIndicator): ?><div class="rounded-lg border bg-white p-4"><b class="text-xs uppercase text-slate-500">Aspek</b><p class="mt-1" data-view-field="nama_aspek"></p></div><div class="rounded-lg border bg-white p-4"><b class="text-xs uppercase text-slate-500">SKPD</b><p class="mt-1" data-view-field="skpd_label"></p></div><?php endif; ?>
            <div class="rounded-lg border bg-white p-4"><b class="text-xs uppercase text-slate-500"><?= e($config['name_label']) ?></b><p class="mt-1 whitespace-pre-line" data-view-field="nama"></p></div>
            <?php if ($isIndicator): ?><div class="rounded-lg border bg-white p-4"><b class="text-xs uppercase text-slate-500">Deskripsi Indikator</b><p class="mt-1 whitespace-pre-line" data-view-field="deskripsi_indikator"></p></div><div class="rounded-lg border bg-white p-4"><b class="text-xs uppercase text-slate-500">Koordinator</b><p class="mt-1" data-view-field="koordinator"></p></div><div class="rounded-lg border bg-white p-4"><b class="text-xs uppercase text-slate-500">Bobot</b><p class="mt-1"><span data-view-field="bobot"></span>%</p></div><?php endif; ?>
        </div>
    </div>
</div>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 p-4" data-modal="master-delete">
    <form method="post" class="w-full max-w-md rounded-lg bg-white p-6">
        <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" data-delete-id>
        <h2 class="text-xl font-bold">Hapus <?= e($config['title']) ?>?</h2>
        <p class="mt-2 text-sm text-slate-500">Tindakan ini permanen dan dicatat dalam audit trail.</p>
        <p class="mt-4 rounded-lg bg-slate-50 p-4 font-semibold" data-delete-name></p>
        <div class="mt-5 flex justify-end gap-2"><button type="button" class="rounded-lg border px-4 py-2" data-modal-close>Batal</button><button class="rounded-lg bg-red-700 px-4 py-2 font-semibold text-white">Ya, Hapus</button></div>
    </form>
</div>

<script>
(() => {
    const modals = document.querySelectorAll('[data-modal]');
    const form = document.querySelector('[data-modal="master-form"]');
    const view = document.querySelector('[data-modal="master-view"]');
    const del = document.querySelector('[data-modal="master-delete"]');
    const fields = ['id', 'nama', 'id_aspek', 'id_skpd', 'deskripsi_indikator', 'koordinator', 'bobot'];
    const posted = <?= json_encode($formState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const input = name => form.querySelector(`[data-master-field="${name}"]`);
    const open = modal => { modal.classList.remove('hidden'); modal.classList.add('flex'); document.body.classList.add('overflow-hidden'); };
    const close = modal => { modal.classList.add('hidden'); modal.classList.remove('flex'); if (![...modals].some(item => !item.classList.contains('hidden'))) document.body.classList.remove('overflow-hidden'); };
    const fill = record => fields.forEach(name => { if (input(name)) input(name).value = record[name] || ''; });

    document.querySelector('[data-master-add]')?.addEventListener('click', () => {
        fill({});
        form.querySelector('[data-master-action]').value = 'create';
        form.querySelector('[data-master-title]').textContent = <?= json_encode('Tambah ' . $config['title']) ?>;
        open(form);
    });
    document.querySelectorAll('[data-master-edit]').forEach(button => button.addEventListener('click', () => {
        fill(JSON.parse(button.dataset.record || '{}'));
        form.querySelector('[data-master-action]').value = 'update';
        form.querySelector('[data-master-title]').textContent = <?= json_encode('Edit ' . $config['title']) ?>;
        open(form);
    }));
    document.querySelectorAll('[data-master-view]').forEach(button => button.addEventListener('click', () => {
        const record = JSON.parse(button.dataset.record || '{}');
        view.querySelectorAll('[data-view-field]').forEach(element => element.textContent = record[element.dataset.viewField] || '-');
        open(view);
    }));
    document.querySelectorAll('[data-master-delete]').forEach(button => button.addEventListener('click', () => {
        del.querySelector('[data-delete-id]').value = button.dataset.id;
        del.querySelector('[data-delete-name]').textContent = button.dataset.name;
        open(del);
    }));
    document.querySelectorAll('[data-modal-close]').forEach(button => button.addEventListener('click', () => close(button.closest('[data-modal]'))));
    modals.forEach(modal => modal.addEventListener('click', event => { if (event.target === modal) close(modal); }));
    document.addEventListener('keydown', event => { if (event.key === 'Escape') modals.forEach(close); });
    <?php if ($openFormModal): ?>
    fill(posted);
    form.querySelector('[data-master-action]').value = <?= json_encode($formMode) ?>;
    form.querySelector('[data-master-title]').textContent = <?= json_encode(($formMode === 'update' ? 'Edit ' : 'Tambah ') . $config['title']) ?>;
    open(form);
    <?php endif; ?>
})();
</script>
