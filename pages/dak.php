<?php

declare(strict_types=1);

$dakConfigs = [
    'dak-audit-keamanan' => [
        'table' => 'dak_audit_keamanan', 'title' => 'Audit Keamanan', 'icon' => 'scan-search',
        'description' => 'Kelola pelaksanaan, hasil, dan tindak lanjut audit keamanan.',
        'reference_field' => 'id_dak_audit_keamanan',
        'fields' => [
            'jenis_audit' => ['label' => 'Jenis Audit', 'type' => 'text', 'maxlength' => 255],
            'tanggal' => ['label' => 'Tanggal Audit', 'type' => 'date'],
            'hasil_audit' => ['label' => 'Hasil Audit', 'type' => 'textarea'],
            'tindak_lanjut' => ['label' => 'Tindak Lanjut', 'type' => 'textarea'],
        ],
    ],
    'dak-edukasi-kesadaran' => [
        'table' => 'dak_edukasi_kesadaran', 'title' => 'Edukasi Kesadaran', 'icon' => 'graduation-cap',
        'description' => 'Kelola kegiatan edukasi dan peningkatan kesadaran keamanan.',
        'reference_field' => 'id_dak_edukasi_kesadaran',
        'fields' => ['tanggal' => ['label' => 'Tanggal Kegiatan', 'type' => 'date']],
    ],
    'dak-identifikasi-kerentanan' => [
        'table' => 'dak_identifikasi_kerentanan', 'title' => 'Identifikasi Kerentanan', 'icon' => 'bug',
        'description' => 'Kelola kegiatan identifikasi dan pencatatan kerentanan.',
        'reference_field' => 'id_dak_identifikasi_kerentanan',
        'fields' => ['tanggal' => ['label' => 'Tanggal Identifikasi', 'type' => 'date']],
    ],
    'dak-kelaikan-keamanan' => [
        'table' => 'dak_kelaikan_keamanan', 'title' => 'Kelaikan Keamanan', 'icon' => 'badge-check',
        'description' => 'Kelola pemeriksaan dan hasil kelaikan keamanan.',
        'reference_field' => 'id_dak_kelaikan_keamanan',
        'fields' => ['tanggal' => ['label' => 'Tanggal Pemeriksaan', 'type' => 'date']],
    ],
    'dak-penanganan-insiden' => [
        'table' => 'dak_penanganan_insiden', 'title' => 'Penanganan Insiden', 'icon' => 'siren',
        'description' => 'Kelola penanganan insiden dan nilai kematangan respons.',
        'reference_field' => 'id_dak_penanganan_insiden',
        'fields' => [
            'tanggal' => ['label' => 'Tanggal Insiden', 'type' => 'date'],
            'nilai_kematangan' => ['label' => 'Nilai Kematangan', 'type' => 'number', 'step' => '0.01'],
        ],
    ],
    'dak-peningkatan-keamanan' => [
        'table' => 'dak_peningkatan_keamanan', 'title' => 'Peningkatan Keamanan', 'icon' => 'shield-plus',
        'description' => 'Kelola rencana dan pelaksanaan peningkatan keamanan.',
        'reference_field' => 'id_dak_peningkatan_keamanan',
        'fields' => ['tanggal' => ['label' => 'Tanggal Kegiatan', 'type' => 'date']],
    ],
    'dak-standar-keamanan' => [
        'table' => 'dak_standar_keamanan', 'title' => 'Standar Keamanan', 'icon' => 'book-check',
        'description' => 'Kelola standar keamanan beserta masa berlakunya.',
        'reference_field' => 'id_dak_standar_keamanan',
        'fields' => [
            'jenis_standar_keamanan' => ['label' => 'Jenis Standar Keamanan', 'type' => 'text', 'maxlength' => 255],
            'tanggal_mulai' => ['label' => 'Tanggal Mulai', 'type' => 'date'],
            'tanggal_akhir' => ['label' => 'Tanggal Akhir', 'type' => 'date'],
        ],
    ],
];

$config = $dakConfigs[$page] ?? null;
if (!$config) {
    http_response_code(404);
    exit('Konfigurasi halaman keamanan tidak ditemukan.');
}

$commonFields = [
    'id_rak' => ['label' => 'Referensi RAK', 'type' => 'select', 'required' => true],
    'nama' => ['label' => 'Nama', 'type' => 'text', 'required' => true, 'maxlength' => 255],
    'deskripsi' => ['label' => 'Deskripsi', 'type' => 'textarea'],
];
$fieldConfigs = array_merge($commonFields, $config['fields']);
$fieldNames = array_keys($fieldConfigs);
$formErrors = [];
$openFormModal = false;
$formMode = 'create';
$formState = array_fill_keys($fieldNames, '');
$formState['id'] = '';

function dak_int_or_null(mixed $value): ?int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id ? (int) $id : null;
}

function dak_rak_label(array $row): string
{
    $code = trim((string) ($row['kode_rak_4'] ?? ''));
    $name = trim((string) ($row['nama_rak_4'] ?? ''));
    return trim($code . ($code !== '' && $name !== '' ? ' - ' : '') . $name);
}

function dak_page_url(string $route, int $pageNumber, string $search, int $perPage): string
{
    return 'index.php?' . http_build_query(['page' => $route, 'q' => $search, 'per_page' => $perPage, 'p' => $pageNumber]);
}

function dak_valid_date(string $value): bool
{
    if ($value === '') return true;
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}

$rakList = db()->query(
    'SELECT id, kode_rak_4, nama_rak_4 FROM rak ORDER BY kode_rak_4, nama_rak_4'
)->fetchAll();
$rakIds = array_flip(array_map('intval', array_column($rakList, 'id')));
$table = $config['table'];
$referenceField = $config['reference_field'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if (in_array($action, ['create', 'update'], true)) {
        $formMode = $action;
        $openFormModal = true;
        $recordId = dak_int_or_null($_POST['id'] ?? null);
        $formState['id'] = $recordId ?: '';
        foreach ($fieldNames as $field) $formState[$field] = trim((string) ($_POST[$field] ?? ''));

        $idRak = dak_int_or_null($formState['id_rak']);
        if (!$idRak || !isset($rakIds[$idRak])) $formErrors[] = 'Referensi RAK wajib dipilih.';
        if ($formState['nama'] === '') {
            $formErrors[] = 'Nama wajib diisi.';
        } elseif (mb_strlen($formState['nama']) > 255) {
            $formErrors[] = 'Nama maksimal 255 karakter.';
        }
        foreach ($fieldConfigs as $field => $fieldConfig) {
            $value = $formState[$field];
            if (($fieldConfig['type'] ?? '') === 'date' && !dak_valid_date($value)) {
                $formErrors[] = $fieldConfig['label'] . ' tidak valid.';
            }
            if (isset($fieldConfig['maxlength']) && mb_strlen($value) > (int) $fieldConfig['maxlength']) {
                $formErrors[] = $fieldConfig['label'] . ' maksimal ' . (int) $fieldConfig['maxlength'] . ' karakter.';
            }
            if (($fieldConfig['type'] ?? '') === 'number' && $value !== '' && !is_numeric($value)) {
                $formErrors[] = $fieldConfig['label'] . ' harus berupa angka.';
            }
        }
        if (isset($fieldConfigs['tanggal_mulai'], $fieldConfigs['tanggal_akhir'])
            && $formState['tanggal_mulai'] !== '' && $formState['tanggal_akhir'] !== ''
            && $formState['tanggal_akhir'] < $formState['tanggal_mulai']) {
            $formErrors[] = 'Tanggal akhir tidak boleh lebih awal dari tanggal mulai.';
        }
        if ($action === 'update' && !$recordId) $formErrors[] = 'ID data tidak valid.';

        $oldValues = null;
        if (!$formErrors && $action === 'update') {
            $stmt = db()->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $recordId]);
            $oldValues = $stmt->fetch();
            if (!$oldValues) $formErrors[] = 'Data yang akan diubah tidak ditemukan.';
        }

        if (!$formErrors) {
            db()->beginTransaction();
            try {
                $params = [];
                foreach ($fieldConfigs as $field => $fieldConfig) {
                    $value = $formState[$field];
                    if ($field === 'id_rak') $params[$field] = $idRak;
                    elseif (($fieldConfig['type'] ?? '') === 'number') $params[$field] = $value !== '' ? (float) $value : null;
                    else $params[$field] = $value !== '' ? $value : null;
                }
                $params['updated_by'] = (int) $user['id'];
                if ($action === 'create') {
                    $params['created_by'] = (int) $user['id'];
                    $columns = array_keys($params);
                    $sql = "INSERT INTO {$table} (" . implode(',', $columns) . ') VALUES (:' . implode(',:', $columns) . ')';
                    db()->prepare($sql)->execute($params);
                    $recordId = (int) db()->lastInsertId();
                } else {
                    $params['id'] = $recordId;
                    $sets = [];
                    foreach (array_keys($params) as $field) if ($field !== 'id') $sets[] = "{$field} = :{$field}";
                    db()->prepare("UPDATE {$table} SET " . implode(', ', $sets) . ' WHERE id = :id')->execute($params);
                }
                $stmt = db()->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1");
                $stmt->execute(['id' => $recordId]);
                $newValues = $stmt->fetch() ?: $params;
                audit_log(
                    (int) $user['id'], $action, $table, (int) $recordId,
                    ($action === 'create' ? 'Menambahkan ' : 'Mengubah ') . $config['title'] . ': ' . $formState['nama'],
                    $oldValues, $newValues, true
                );
                db()->commit();
                set_flash('success', $action === 'create' ? 'Data berhasil ditambahkan.' : 'Data berhasil diperbarui.');
                redirect('index.php?page=' . $page);
            } catch (Throwable $exception) {
                db()->rollBack();
                error_log('Penyimpanan ' . $table . ' gagal: ' . $exception->getMessage());
                $formErrors[] = 'Data gagal disimpan. Silakan coba kembali.';
            }
        }
    } elseif ($action === 'delete') {
        $recordId = dak_int_or_null($_POST['id'] ?? null);
        $stmt = db()->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $recordId]);
        $record = $stmt->fetch();
        if (!$recordId || !$record) {
            set_flash('error', 'Data tidak ditemukan.');
            redirect('index.php?page=' . $page);
        }
        $referenceStmt = db()->prepare("SELECT COUNT(*) FROM dad WHERE {$referenceField} = :id");
        $referenceStmt->execute(['id' => $recordId]);
        if ((int) $referenceStmt->fetchColumn() > 0) {
            set_flash('error', 'Data masih digunakan pada Domain Arsitektur Data dan tidak dapat dihapus.');
            redirect('index.php?page=' . $page);
        }
        db()->beginTransaction();
        try {
            db()->prepare("DELETE FROM {$table} WHERE id = :id")->execute(['id' => $recordId]);
            audit_log((int) $user['id'], 'delete', $table, $recordId, 'Menghapus ' . $config['title'] . ': ' . $record['nama'], $record, null, true);
            db()->commit();
            set_flash('success', 'Data berhasil dihapus.');
        } catch (Throwable $exception) {
            db()->rollBack();
            error_log('Penghapusan ' . $table . ' gagal: ' . $exception->getMessage());
            set_flash('error', 'Data gagal dihapus.');
        }
        redirect('index.php?page=' . $page);
    } else {
        set_flash('error', 'Aksi tidak valid.');
        redirect('index.php?page=' . $page);
    }
}

$search = trim((string) ($_GET['q'] ?? ''));
$perPageOptions = [10, 25, 50];
$perPage = (int) ($_GET['per_page'] ?? 10);
if (!in_array($perPage, $perPageOptions, true)) $perPage = 10;
$currentPage = max(1, (int) ($_GET['p'] ?? 1));
$searchColumns = array_map(static fn(string $field): string => "d.{$field}", array_filter($fieldNames, static fn(string $field): bool => $field !== 'id_rak'));
$whereSql = '';
$queryParams = [];
if ($search !== '') {
    $whereSql = " WHERE CONCAT_WS(' ', " . implode(', ', $searchColumns) . ", r.kode_rak_4, r.nama_rak_4) LIKE :search";
    $queryParams['search'] = '%' . $search . '%';
}
$joins = " FROM {$table} d
    LEFT JOIN rak r ON r.id = d.id_rak
    LEFT JOIN users creator ON creator.id = d.created_by
    LEFT JOIN users updater ON updater.id = d.updated_by";
$stmt = db()->prepare('SELECT COUNT(*)' . $joins . $whereSql);
$stmt->execute($queryParams);
$totalRows = (int) $stmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $perPage;
$stmt = db()->prepare(
    "SELECT d.*, r.kode_rak_4, r.nama_rak_4,
        creator.name AS created_by_name, updater.name AS updated_by_name,
        (SELECT COUNT(*) FROM dad WHERE {$referenceField} = d.id) AS reference_count"
    . $joins . $whereSql . ' ORDER BY d.id DESC LIMIT :limit OFFSET :offset'
);
foreach ($queryParams as $key => $value) $stmt->bindValue(':' . $key, $value);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();
$startRow = $totalRows ? $offset + 1 : 0;
$endRow = min($offset + $perPage, $totalRows);
?>

<section class="space-y-5">
    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-red-700">Domain Arsitektur Keamanan</p>
            <h1 class="mt-1 text-xl font-bold tracking-tight"><?= e($config['title']) ?></h1>
            <p class="mt-1 text-sm text-slate-500"><?= e($config['description']) ?></p>
        </div>
        <div class="flex gap-2">
            <a href="cetak/cetak_dak.php?jenis=<?= e($page) ?>" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                <i data-lucide="printer" class="h-4 w-4"></i>Cetak
            </a>
            <button type="button" class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-800" data-dak-add>
                <i data-lucide="plus" class="h-4 w-4"></i>Tambah Data
            </button>
        </div>
    </div>

    <?php if ($formErrors): ?>
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <p class="font-semibold">Data belum bisa disimpan.</p>
            <ul class="mt-1 list-disc pl-5"><?php foreach ($formErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-4 py-3 lg:flex-row lg:items-center lg:justify-between">
            <div><h2 class="font-semibold text-slate-900">Daftar <?= e($config['title']) ?></h2><p class="mt-1 text-sm text-slate-500">Menampilkan <?= number_format($totalRows, 0, ',', '.') ?> data<?= $search !== '' ? ' hasil pencarian' : '' ?>.</p></div>
            <form method="get" class="flex flex-col gap-2 sm:flex-row">
                <input type="hidden" name="page" value="<?= e($page) ?>">
                <div class="relative"><i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i><input type="search" name="q" value="<?= e($search) ?>" placeholder="Cari nama, deskripsi, atau RAK..." class="w-full rounded-md border border-slate-300 py-2 pl-9 pr-3 text-xs outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100 sm:w-64"></div>
                <select name="per_page" class="rounded-md border border-slate-300 px-3 py-2 text-xs outline-none focus:border-blue-600" aria-label="Jumlah data per halaman"><?php foreach ($perPageOptions as $option): ?><option value="<?= $option ?>" <?= $perPage === $option ? 'selected' : '' ?>><?= $option ?> data</option><?php endforeach; ?></select>
                <button class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Tampilkan</button>
                <?php if ($search !== ''): ?><a href="index.php?page=<?= e($page) ?>" class="inline-flex items-center justify-center rounded-md px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">Reset</a><?php endif; ?>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500"><tr><th class="px-2.5 py-2.5">Kode</th><th class="px-2.5 py-2.5">Nama</th><th class="px-2.5 py-2.5">Deskripsi</th><th class="px-2.5 py-2.5">Tanggal</th><th class="px-2.5 py-2.5">RAK</th><th class="px-2.5 py-2.5 text-right">Aksi</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                <?php if (!$rows): ?><tr><td colspan="6" class="p-10 text-center text-sm text-slate-500">Belum ada data <?= e($config['title']) ?>.</td></tr><?php endif; ?>
                <?php foreach ($rows as $index => $row):
                    $record = ['id' => (int) $row['id'], 'rak_label' => dak_rak_label($row)];
                    foreach ($fieldNames as $field) $record[$field] = (string) ($row[$field] ?? '');
                    $dateDisplay = $table === 'dak_standar_keamanan'
                        ? trim((string) ($row['tanggal_mulai'] ?? '') . (($row['tanggal_mulai'] ?? '') !== '' && ($row['tanggal_akhir'] ?? '') !== '' ? ' s.d. ' : '') . (string) ($row['tanggal_akhir'] ?? ''))
                        : (string) ($row['tanggal'] ?? '');
                ?>
                    <tr class="align-top hover:bg-slate-50/70">
                        <td class="whitespace-nowrap p-3 font-semibold text-red-700"><?= e(sprintf('DAK-%03d', (int) $row['id'])) ?></td>
                        <td class="max-w-[240px] whitespace-normal break-words p-3 font-semibold"><?= e($record['nama'] ?: '-') ?></td>
                        <td class="max-w-[280px] whitespace-normal break-words p-3 text-slate-600"><?= e($record['deskripsi'] ?: '-') ?></td>
                        <td class="whitespace-nowrap p-3 text-slate-600"><?= e($dateDisplay ?: '-') ?></td>
                        <td class="max-w-[240px] whitespace-normal break-words p-3 font-medium text-red-700"><?= e($record['rak_label'] ?: '-') ?></td>
                        <td class="p-3"><div class="flex justify-end gap-1.5">
                            <button type="button" class="dak-action" title="Lihat" data-dak-view data-record="<?= e(json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"><i data-lucide="eye"></i></button>
                            <button type="button" class="dak-action border-blue-200 bg-blue-50 text-blue-700" title="Edit" data-dak-edit data-record="<?= e(json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"><i data-lucide="pencil"></i></button>
                            <button type="button" class="dak-action border-red-200 bg-red-50 text-red-700 disabled:cursor-not-allowed disabled:opacity-40" title="<?= (int) $row['reference_count'] ? 'Masih digunakan DAD' : 'Hapus' ?>" data-dak-delete data-id="<?= (int) $row['id'] ?>" data-name="<?= e($row['nama']) ?>" <?= (int) $row['reference_count'] ? 'disabled' : '' ?>><i data-lucide="trash-2"></i></button>
                        </div></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 p-4 text-sm text-slate-500"><p>Menampilkan <?= $startRow ?>-<?= $endRow ?> dari <?= $totalRows ?> data</p><div class="flex gap-2"><?php if ($currentPage > 1): ?><a class="rounded-md border px-3 py-2" href="<?= e(dak_page_url($page, $currentPage - 1, $search, $perPage)) ?>">Sebelumnya</a><?php endif; ?><?php if ($currentPage < $totalPages): ?><a class="rounded-md border px-3 py-2" href="<?= e(dak_page_url($page, $currentPage + 1, $search, $perPage)) ?>">Berikutnya</a><?php endif; ?></div></div>
    </div>
</section>

<style>
    .dak-label { display:block; margin-bottom:.25rem; font-size:11px; font-weight:600; letter-spacing:.025em; text-transform:uppercase; color:#64748b; }
    .dak-control { width:100%; border:1px solid #cbd5e1; border-radius:.5rem; background:#fff; padding:.5rem .75rem; font-size:.875rem; color:#0f172a; outline:none; }
    .dak-control:focus { border-color:#dc2626; box-shadow:0 0 0 4px rgb(254 226 226 / .7); }
    .dak-action { display:inline-flex; width:2rem; height:2rem; align-items:center; justify-content:center; border-width:1px; border-radius:.375rem; }
    .dak-action svg { width:.875rem; height:.875rem; }
</style>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 px-3 py-4 backdrop-blur-sm" data-modal="dak-form">
    <form method="post" class="flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <?= csrf_field() ?><input type="hidden" name="action" value="<?= e($formMode) ?>" data-dak-action><input type="hidden" name="id" value="<?= e($formState['id']) ?>" data-dak-field="id">
        <header class="flex items-start justify-between border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white px-4 py-3 sm:px-5">
            <div class="flex items-start gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-700 text-white ring-4 ring-red-50"><i data-lucide="<?= e($config['icon']) ?>" class="h-4 w-4"></i></span><div><h2 class="text-base font-bold" data-dak-title>Tambah <?= e($config['title']) ?></h2><p class="mt-0.5 text-xs text-slate-500">Lengkapi informasi dan referensi arsitektur keamanan.</p></div></div>
            <button type="button" class="h-9 w-9 rounded-lg border border-slate-200 bg-white text-slate-500" data-modal-close><i data-lucide="x" class="mx-auto h-4 w-4"></i></button>
        </header>
        <div class="grid flex-1 gap-3 overflow-y-auto bg-slate-50/70 p-4 sm:p-5 md:grid-cols-2">
            <?php foreach ($fieldConfigs as $field => $fieldConfig):
                $type = $fieldConfig['type'];
                $fullWidth = in_array($type, ['textarea', 'select'], true) || $field === 'nama';
            ?>
                <label class="<?= $fullWidth ? 'md:col-span-2' : '' ?>">
                    <span class="dak-label"><?= e($fieldConfig['label']) ?></span>
                    <?php if ($type === 'select'): ?>
                        <select name="<?= e($field) ?>" class="dak-control" data-dak-field="<?= e($field) ?>" required>
                            <option value="">Pilih RAK</option>
                            <?php foreach ($rakList as $rak): ?><option value="<?= (int) $rak['id'] ?>"><?= e(dak_rak_label($rak)) ?></option><?php endforeach; ?>
                        </select>
                    <?php elseif ($type === 'textarea'): ?>
                        <textarea name="<?= e($field) ?>" rows="3" class="dak-control" data-dak-field="<?= e($field) ?>"></textarea>
                    <?php else: ?>
                        <input name="<?= e($field) ?>" type="<?= e($type) ?>" class="dak-control" data-dak-field="<?= e($field) ?>" <?= !empty($fieldConfig['required']) ? 'required' : '' ?> <?= isset($fieldConfig['maxlength']) ? 'maxlength="' . (int) $fieldConfig['maxlength'] . '"' : '' ?> <?= isset($fieldConfig['step']) ? 'step="' . e($fieldConfig['step']) . '"' : '' ?>>
                    <?php endif; ?>
                </label>
            <?php endforeach; ?>
        </div>
        <footer class="flex justify-end gap-2 border-t border-slate-200 px-4 py-3 sm:px-5"><button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold" data-modal-close>Batal</button><button class="rounded-lg bg-red-700 px-5 py-2 text-sm font-semibold text-white"><span data-dak-submit>Simpan Data</span></button></footer>
    </form>
</div>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 px-3 py-4" data-modal="dak-view">
    <div class="flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"><header class="flex justify-between border-b p-4 sm:px-5"><h2 class="text-lg font-bold">Detail <?= e($config['title']) ?></h2><button data-modal-close><i data-lucide="x"></i></button></header><div class="grid flex-1 gap-3 overflow-y-auto bg-slate-50 p-4 md:grid-cols-2"><div class="dak-detail"><b>Referensi RAK</b><p data-view-field="rak_label"></p></div><?php foreach ($fieldConfigs as $field => $fieldConfig): ?><div class="dak-detail <?= ($fieldConfig['type'] ?? '') === 'textarea' ? 'md:col-span-2' : '' ?>"><b><?= e($fieldConfig['label']) ?></b><p class="whitespace-pre-line" data-view-field="<?= e($field) ?>"></p></div><?php endforeach; ?></div></div>
</div>
<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 px-3 py-4" data-modal="dak-delete"><form method="post" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" data-delete-id><h2 class="text-xl font-bold">Hapus Data?</h2><p class="mt-2 text-sm text-slate-500">Tindakan ini permanen dan dicatat dalam audit trail.</p><p class="mt-4 rounded-xl bg-slate-50 p-4 font-semibold" data-delete-name></p><div class="mt-5 flex justify-end gap-2"><button type="button" class="rounded-lg border px-4 py-2" data-modal-close>Batal</button><button class="rounded-lg bg-red-700 px-4 py-2 font-semibold text-white">Ya, Hapus</button></div></form></div>
<style>.dak-detail{border:1px solid #e2e8f0;border-radius:.75rem;background:#fff;padding:1rem}.dak-detail b{font-size:.75rem;text-transform:uppercase;color:#64748b}.dak-detail p{margin-top:.5rem;font-size:.875rem}</style>

<script>
(() => {
    const modals=document.querySelectorAll('[data-modal]'),form=document.querySelector('[data-modal="dak-form"]'),view=document.querySelector('[data-modal="dak-view"]'),del=document.querySelector('[data-modal="dak-delete"]');
    const fields=<?= json_encode(array_merge(['id'], $fieldNames), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,posted=<?= json_encode($formState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const field=name=>form.querySelector(`[data-dak-field="${name}"]`),open=modal=>{modal.classList.remove('hidden');modal.classList.add('flex');document.body.classList.add('overflow-hidden')},close=modal=>{modal.classList.add('hidden');modal.classList.remove('flex');if(![...modals].some(x=>!x.classList.contains('hidden')))document.body.classList.remove('overflow-hidden')};
    const fill=record=>fields.forEach(name=>{if(field(name))field(name).value=record[name]||''});
    document.querySelector('[data-dak-add]')?.addEventListener('click',()=>{fill({});form.querySelector('[data-dak-action]').value='create';form.querySelector('[data-dak-title]').textContent=<?= json_encode('Tambah ' . $config['title']) ?>;form.querySelector('[data-dak-submit]').textContent='Simpan Data';open(form)});
    document.querySelectorAll('[data-dak-edit]').forEach(button=>button.addEventListener('click',()=>{fill(JSON.parse(button.dataset.record||'{}'));form.querySelector('[data-dak-action]').value='update';form.querySelector('[data-dak-title]').textContent=<?= json_encode('Edit ' . $config['title']) ?>;form.querySelector('[data-dak-submit]').textContent='Simpan Perubahan';open(form)}));
    document.querySelectorAll('[data-dak-view]').forEach(button=>button.addEventListener('click',()=>{const record=JSON.parse(button.dataset.record||'{}');view.querySelectorAll('[data-view-field]').forEach(el=>el.textContent=record[el.dataset.viewField]||'—');open(view)}));
    document.querySelectorAll('[data-dak-delete]').forEach(button=>button.addEventListener('click',()=>{del.querySelector('[data-delete-id]').value=button.dataset.id;del.querySelector('[data-delete-name]').textContent=button.dataset.name;open(del)}));
    document.querySelectorAll('[data-modal-close]').forEach(button=>button.addEventListener('click',()=>close(button.closest('[data-modal]'))));
    modals.forEach(modal=>modal.addEventListener('click',event=>{if(event.target===modal)close(modal)}));
    document.addEventListener('keydown',event=>{if(event.key==='Escape')modals.forEach(close)});
    <?php if ($openFormModal): ?>fill(posted);form.querySelector('[data-dak-action]').value=<?= json_encode($formMode) ?>;form.querySelector('[data-dak-title]').textContent=<?= json_encode(($formMode === 'update' ? 'Edit ' : 'Tambah ') . $config['title']) ?>;open(form);<?php endif; ?>
})();
</script>
