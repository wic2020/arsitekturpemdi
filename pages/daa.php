<?php

declare(strict_types=1);

$securityFields = [
    'id_dak_audit_keamanan' => ['Audit Keamanan', 'dak_audit_keamanan'],
    'id_dak_edukasi_kesadaran' => ['Edukasi Kesadaran', 'dak_edukasi_kesadaran'],
    'id_dak_identifikasi_kerentanan' => ['Identifikasi Kerentanan', 'dak_identifikasi_kerentanan'],
    'id_dak_kelaikan_keamanan' => ['Kelaikan Keamanan', 'dak_kelaikan_keamanan'],
    'id_dak_penanganan_insiden' => ['Penanganan Insiden', 'dak_penanganan_insiden'],
    'id_dak_peningkatan_keamanan' => ['Peningkatan Keamanan', 'dak_peningkatan_keamanan'],
    'id_dak_standar_keamanan' => ['Standar Keamanan', 'dak_standar_keamanan'],
];
$textFields = [
    'nama_aplikasi', 'uraian_aplikasi', 'fungsi_aplikasi', 'luaran', 'basis_aplikasi',
    'tipe_lisensi_aplikasi', 'bahasa_pemrograman', 'kerangka_pengembangan', 'nama_basis_data',
];
$relationFields = array_merge([
    'id_skpd', 'id_raa', 'id_unit_kerja_operasional',
    'id_dai_komputasi_awan', 'id_dai_hardware_server', 'id_dai_jaringan_intra',
], array_keys($securityFields));
$daaFields = array_merge($textFields, $relationFields);
$multiFields = ['id_dab', 'id_dad', 'id_dal', 'id_dai_splp'];
$formErrors = [];
$openFormModal = false;
$formMode = 'create';
$formState = array_fill_keys($daaFields, '');
$formState['id'] = '';
foreach ($multiFields as $field) $formState[$field] = [];

function daa_int_or_null(mixed $value): ?int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id ? (int) $id : null;
}

function daa_label(?string $code, ?string $name): string
{
    $code = trim((string) $code);
    $name = trim((string) $name);
    return trim($code . ($code !== '' && $name !== '' ? ' - ' : '') . $name);
}

function daa_url(int $pageNumber, string $search, int $perPage): string
{
    return 'index.php?' . http_build_query(['page' => 'daa', 'q' => $search, 'per_page' => $perPage, 'p' => $pageNumber]);
}

function daa_posted_ids(string $field): array
{
    $values = is_array($_POST[$field] ?? null) ? $_POST[$field] : [];
    return array_values(array_unique(array_filter(array_map('daa_int_or_null', $values))));
}

function daa_posted_text(string $field): string
{
    $value = $_POST[$field] ?? '';
    if (is_array($value)) {
        $items = array_values(array_unique(array_filter(array_map(static fn(mixed $item): string => trim((string) $item), $value), static fn(string $item): bool => $item !== '')));
        return implode(', ', $items);
    }
    return trim((string) $value);
}

function daa_sync_links(string $table, string $ownerColumn, string $targetColumn, int $ownerId, array $targetIds): void
{
    db()->prepare("DELETE FROM {$table} WHERE {$ownerColumn} = :id")->execute(['id' => $ownerId]);
    if (!$targetIds) return;
    $stmt = db()->prepare("INSERT INTO {$table} ({$ownerColumn}, {$targetColumn}) VALUES (:owner, :target)");
    foreach ($targetIds as $targetId) $stmt->execute(['owner' => $ownerId, 'target' => $targetId]);
}

$skpdList = db()->query('SELECT id, kode_skpd AS kode, nama_skpd AS nama FROM skpd ORDER BY kode_skpd, nama_skpd')->fetchAll();
$raaList = db()->query('SELECT id, kode_raa_4 AS kode, nama_raa_4 AS nama FROM raa ORDER BY kode_raa_4, nama_raa_4')->fetchAll();
$dabList = db()->query('SELECT d.id, d.nama_bisnis AS nama, s.kode_skpd, s.nama_skpd, p.kode_program FROM dab d LEFT JOIN skpd s ON s.id=d.id_skpd LEFT JOIN program p ON p.id=d.id_program ORDER BY s.kode_skpd, p.kode_program, d.id')->fetchAll();
$dadList = db()->query('SELECT d.id, d.nama_data AS nama, s.kode_skpd, s.nama_skpd, p.kode_program FROM dad d LEFT JOIN skpd s ON s.id=d.id_skpd LEFT JOIN program p ON p.id=d.id_program ORDER BY s.kode_skpd, p.kode_program, d.id')->fetchAll();
$dalList = db()->query('SELECT d.id, d.nama_layanan AS nama, s.kode_skpd, s.nama_skpd, p.kode_program FROM dal d LEFT JOIN skpd s ON s.id=d.id_skpd LEFT JOIN program p ON p.id=d.id_program ORDER BY s.kode_skpd, p.kode_program, d.id')->fetchAll();
$cloudList = db()->query(
    "SELECT d.id, r.kode_rai_4 AS kode, d.nama_cloud AS nama
     FROM dai_komputasi_awan d
     LEFT JOIN rai r ON r.id = d.id_rai
     ORDER BY d.id"
)->fetchAll();
$serverList = db()->query(
    "SELECT d.id, r.kode_rai_4 AS kode, d.nama_server AS nama
     FROM dai_hardware_server d
     LEFT JOIN rai r ON r.id = d.id_rai
     ORDER BY d.id"
)->fetchAll();
$splpList = db()->query(
    "SELECT d.id, r.kode_rai_4 AS kode, d.nama_splp AS nama
     FROM dai_splp d
     LEFT JOIN rai r ON r.id = d.id_rai
     ORDER BY d.id"
)->fetchAll();
$networkList = db()->query(
    "SELECT d.id, r.kode_rai_4 AS kode, d.nama_jaringan AS nama
     FROM dai_jaringan_intra d
     LEFT JOIN rai r ON r.id = d.id_rai
     ORDER BY d.id"
)->fetchAll();
$securityLists = [];
foreach ($securityFields as $field => [$label, $table]) {
    $securityLists[$field] = db()->query("SELECT id, nama FROM {$table} ORDER BY nama")->fetchAll();
}
$validIds = [
    'id_skpd' => array_flip(array_map('intval', array_column($skpdList, 'id'))),
    'id_raa' => array_flip(array_map('intval', array_column($raaList, 'id'))),
    'id_dab' => array_flip(array_map('intval', array_column($dabList, 'id'))),
    'id_dad' => array_flip(array_map('intval', array_column($dadList, 'id'))),
    'id_dal' => array_flip(array_map('intval', array_column($dalList, 'id'))),
    'id_dai_komputasi_awan' => array_flip(array_map('intval', array_column($cloudList, 'id'))),
    'id_dai_hardware_server' => array_flip(array_map('intval', array_column($serverList, 'id'))),
    'id_dai_splp' => array_flip(array_map('intval', array_column($splpList, 'id'))),
    'id_dai_jaringan_intra' => array_flip(array_map('intval', array_column($networkList, 'id'))),
];
foreach ($securityLists as $field => $list) $validIds[$field] = array_flip(array_map('intval', array_column($list, 'id')));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if (in_array($action, ['create', 'update'], true)) {
        $formMode = $action;
        $openFormModal = true;
        $recordId = daa_int_or_null($_POST['id'] ?? null);
        $formState['id'] = $recordId ?: '';
        foreach ($daaFields as $field) $formState[$field] = daa_posted_text($field);
        foreach ($multiFields as $field) $formState[$field] = daa_posted_ids($field);

        $idSkpd = daa_int_or_null($formState['id_skpd']);
        $idRaa = daa_int_or_null($formState['id_raa']);
        if (!$idSkpd || !isset($validIds['id_skpd'][$idSkpd])) $formErrors[] = 'SKPD wajib dipilih.';
        if (!$idRaa || !isset($validIds['id_raa'][$idRaa])) $formErrors[] = 'Referensi RAA wajib dipilih.';
        if (!$formState['id_dab'] || array_diff($formState['id_dab'], array_keys($validIds['id_dab']))) $formErrors[] = 'Minimal satu DAB yang valid wajib dipilih.';
        if ($formState['nama_aplikasi'] === '') {
            $formErrors[] = 'Nama aplikasi wajib diisi.';
        } elseif (mb_strlen($formState['nama_aplikasi']) > 255) {
            $formErrors[] = 'Nama aplikasi maksimal 255 karakter.';
        }
        foreach (['basis_aplikasi','tipe_lisensi_aplikasi','bahasa_pemrograman','kerangka_pengembangan','nama_basis_data'] as $field) {
            if (mb_strlen($formState[$field]) > 255) $formErrors[] = ucfirst(str_replace('_', ' ', $field)) . ' maksimal 255 karakter.';
        }
        foreach ($relationFields as $field) {
            if ($field === 'id_unit_kerja_operasional') continue;
            $id = daa_int_or_null($formState[$field]);
            if ($id && isset($validIds[$field]) && !isset($validIds[$field][$id])) $formErrors[] = 'Pilihan ' . str_replace('_', ' ', $field) . ' tidak valid.';
        }
        $idUnitOperasional = daa_int_or_null($formState['id_unit_kerja_operasional']);
        if ($idUnitOperasional && !isset($validIds['id_skpd'][$idUnitOperasional])) $formErrors[] = 'Unit kerja operasional tidak valid.';
        if (array_diff($formState['id_dad'], array_keys($validIds['id_dad']))) $formErrors[] = 'Domain data tidak valid.';
        if (array_diff($formState['id_dal'], array_keys($validIds['id_dal']))) $formErrors[] = 'Domain layanan tidak valid.';
        if (array_diff($formState['id_dai_splp'], array_keys($validIds['id_dai_splp']))) $formErrors[] = 'Infrastruktur SPLP tidak valid.';
        if ($action === 'update' && !$recordId) $formErrors[] = 'ID data DAA tidak valid.';

        $oldValues = null;
        if (!$formErrors && $action === 'update') {
            $stmt = db()->prepare('SELECT * FROM daa WHERE id=:id');
            $stmt->execute(['id' => $recordId]);
            $oldValues = $stmt->fetch();
            if (!$oldValues) $formErrors[] = 'Data DAA tidak ditemukan.';
            else {
                foreach ([
                    'id_dab' => ['daa_dab','id_dab'],
                    'id_dad' => ['daa_dad','id_dad'],
                    'id_dal' => ['daa_dal','id_dal'],
                    'id_dai_splp' => ['daa_dai_splp','id_dai_splp'],
                ] as $field => [$table,$target]) {
                    $stmt = db()->prepare("SELECT {$target} FROM {$table} WHERE id_daa=:id ORDER BY {$target}");
                    $stmt->execute(['id' => $recordId]);
                    $oldValues[$field] = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
                }
            }
        }
        if (!$formErrors) {
            db()->beginTransaction();
            try {
                $params = [];
                foreach ($textFields as $field) $params[$field] = $formState[$field] !== '' ? $formState[$field] : null;
                foreach ($relationFields as $field) $params[$field] = daa_int_or_null($formState[$field]);
                $params['updated_by'] = (int) $user['id'];
                if ($action === 'create') {
                    $params['created_by'] = (int) $user['id'];
                    $columns = array_keys($params);
                    db()->prepare('INSERT INTO daa (' . implode(',', $columns) . ') VALUES (:' . implode(',:', $columns) . ')')->execute($params);
                    $recordId = (int) db()->lastInsertId();
                } else {
                    $params['id'] = $recordId;
                    $sets = [];
                    foreach (array_keys($params) as $field) if ($field !== 'id') $sets[] = "{$field}=:{$field}";
                    db()->prepare('UPDATE daa SET ' . implode(',', $sets) . ' WHERE id=:id')->execute($params);
                }
                daa_sync_links('daa_dab', 'id_daa', 'id_dab', $recordId, $formState['id_dab']);
                daa_sync_links('daa_dad', 'id_daa', 'id_dad', $recordId, $formState['id_dad']);
                daa_sync_links('daa_dal', 'id_daa', 'id_dal', $recordId, $formState['id_dal']);
                daa_sync_links('daa_dai_splp', 'id_daa', 'id_dai_splp', $recordId, $formState['id_dai_splp']);
                $stmt = db()->prepare('SELECT * FROM daa WHERE id=:id');
                $stmt->execute(['id' => $recordId]);
                $newValues = $stmt->fetch() ?: $params;
                foreach ($multiFields as $field) $newValues[$field] = $formState[$field];
                audit_log((int) $user['id'], $action, 'daa', $recordId, ($action === 'create' ? 'Menambahkan' : 'Mengubah') . ' domain aplikasi ' . $formState['nama_aplikasi'], $oldValues, $newValues, true);
                db()->commit();
                set_flash('success', $action === 'create' ? 'Data DAA berhasil ditambahkan.' : 'Data DAA berhasil diperbarui.');
                redirect('index.php?page=daa');
            } catch (Throwable $exception) {
                db()->rollBack();
                error_log('Penyimpanan DAA gagal: ' . $exception->getMessage());
                $formErrors[] = 'Data DAA gagal disimpan.';
            }
        }
    } elseif ($action === 'delete') {
        $recordId = daa_int_or_null($_POST['id'] ?? null);
        $stmt = db()->prepare('SELECT * FROM daa WHERE id=:id');
        $stmt->execute(['id' => $recordId]);
        $record = $stmt->fetch();
        if (!$recordId || !$record) {
            set_flash('error', 'Data DAA tidak ditemukan.');
            redirect('index.php?page=daa');
        }
        $external = db()->prepare(
            'SELECT (SELECT COUNT(*) FROM dai_komputasi_awan_daa WHERE id_daa=:cloud_id)
                  + (SELECT COUNT(*) FROM dai_splp_daa WHERE id_daa=:splp_id)'
        );
        $external->execute(['cloud_id' => $recordId, 'splp_id' => $recordId]);
        if ((int) $external->fetchColumn() > 0) {
            set_flash('error', 'Data DAA masih digunakan oleh domain infrastruktur dan tidak dapat dihapus.');
            redirect('index.php?page=daa');
        }
        foreach ([
            'id_dab' => ['daa_dab','id_dab'],
            'id_dad' => ['daa_dad','id_dad'],
            'id_dal' => ['daa_dal','id_dal'],
            'id_dai_splp' => ['daa_dai_splp','id_dai_splp'],
        ] as $field => [$table,$target]) {
            $stmt = db()->prepare("SELECT {$target} FROM {$table} WHERE id_daa=:id");
            $stmt->execute(['id' => $recordId]);
            $record[$field] = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        }
        db()->beginTransaction();
        try {
            daa_sync_links('daa_dab', 'id_daa', 'id_dab', $recordId, []);
            daa_sync_links('daa_dad', 'id_daa', 'id_dad', $recordId, []);
            daa_sync_links('daa_dal', 'id_daa', 'id_dal', $recordId, []);
            daa_sync_links('daa_dai_splp', 'id_daa', 'id_dai_splp', $recordId, []);
            db()->prepare('DELETE FROM daa WHERE id=:id')->execute(['id' => $recordId]);
            audit_log((int) $user['id'], 'delete', 'daa', $recordId, 'Menghapus domain aplikasi ' . $record['nama_aplikasi'], $record, null, true);
            db()->commit();
            set_flash('success', 'Data DAA berhasil dihapus.');
        } catch (Throwable $exception) {
            db()->rollBack();
            set_flash('error', 'Data DAA gagal dihapus.');
        }
        redirect('index.php?page=daa');
    }
}

$search = trim((string) ($_GET['q'] ?? ''));
$perPage = (int) ($_GET['per_page'] ?? 10);
if (!in_array($perPage, [10,25,50], true)) $perPage = 10;
$currentPage = max(1, (int) ($_GET['p'] ?? 1));
$where = '';
$query = [];
if ($search !== '') {
    $where = " WHERE CONCAT_WS(' ',d.nama_aplikasi,d.uraian_aplikasi,d.fungsi_aplikasi,d.basis_aplikasi,d.bahasa_pemrograman,s.nama_skpd,r.nama_raa_4) LIKE :search";
    $query['search'] = "%{$search}%";
}
$joins = ' FROM daa d LEFT JOIN skpd s ON s.id=d.id_skpd LEFT JOIN raa r ON r.id=d.id_raa LEFT JOIN users c ON c.id=d.created_by LEFT JOIN users u ON u.id=d.updated_by';
$stmt = db()->prepare('SELECT COUNT(*)' . $joins . $where);
$stmt->execute($query);
$totalRows = (int) $stmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $perPage;
$stmt = db()->prepare('SELECT d.*,s.kode_skpd,s.nama_skpd,r.kode_raa_4,r.nama_raa_4,c.name created_by_name,u.name updated_by_name,
    (SELECT GROUP_CONCAT(x.id_dab ORDER BY x.id_dab) FROM daa_dab x WHERE x.id_daa=d.id) dab_ids,
    (SELECT GROUP_CONCAT(CONCAT("DAB-", LPAD(b.id, 3, "0"), " - ", b.nama_bisnis) ORDER BY b.id SEPARATOR " • ") FROM daa_dab x JOIN dab b ON b.id=x.id_dab WHERE x.id_daa=d.id) dab_labels,
    (SELECT GROUP_CONCAT(x.id_dad ORDER BY x.id_dad) FROM daa_dad x WHERE x.id_daa=d.id) dad_ids,
    (SELECT GROUP_CONCAT(CONCAT("DAD-", LPAD(dd.id, 3, "0"), " - ", dd.nama_data) ORDER BY dd.id SEPARATOR " • ") FROM daa_dad x JOIN dad dd ON dd.id=x.id_dad WHERE x.id_daa=d.id) dad_labels,
    (SELECT GROUP_CONCAT(x.id_dal ORDER BY x.id_dal) FROM daa_dal x WHERE x.id_daa=d.id) dal_ids,
    (SELECT GROUP_CONCAT(CONCAT("DAL-", LPAD(dl.id, 3, "0"), " - ", dl.nama_layanan) ORDER BY dl.id SEPARATOR " • ") FROM daa_dal x JOIN dal dl ON dl.id=x.id_dal WHERE x.id_daa=d.id) dal_labels,
    (SELECT GROUP_CONCAT(x.id_dai_splp ORDER BY x.id_dai_splp) FROM daa_dai_splp x WHERE x.id_daa=d.id) splp_ids,
    (SELECT GROUP_CONCAT(CONCAT("DAI-", LPAD(sp.id, 3, "0"), " - ", sp.nama_splp) ORDER BY sp.id SEPARATOR " • ") FROM daa_dai_splp x JOIN dai_splp sp ON sp.id=x.id_dai_splp WHERE x.id_daa=d.id) splp_labels'
    . $joins . $where . ' ORDER BY s.kode_skpd ASC, d.id ASC LIMIT :limit OFFSET :offset');
foreach ($query as $key => $value) $stmt->bindValue(":{$key}", $value);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();
?>

<section class="space-y-5">
    <div class="flex flex-col gap-3 rounded-2xl border bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between"><div><p class="text-xs font-semibold uppercase text-red-700">Domain Arsitektur</p><h1 class="mt-1 text-xl font-bold">Domain Arsitektur Aplikasi</h1><p class="mt-1 text-sm text-slate-500">Kelola aplikasi, teknologi, data, unit kerja, infrastruktur, dan kontrol keamanannya.</p></div><div class="flex gap-2"><a href="cetak_daa.php" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"><i data-lucide="printer" class="h-4 w-4"></i>Cetak</a><button class="rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white" data-daa-add><i data-lucide="plus" class="mr-1 inline h-4 w-4"></i>Tambah DAA</button></div></div>
    <?php if ($formErrors): ?><div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><b>Data belum bisa disimpan.</b><ul class="mt-1 list-disc pl-5"><?php foreach($formErrors as $error):?><li><?= e($error) ?></li><?php endforeach;?></ul></div><?php endif;?>
    <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-4 py-3 lg:flex-row lg:items-center lg:justify-between">
            <div><h2 class="font-semibold text-slate-900">Daftar Domain Aplikasi</h2><p class="mt-1 text-sm text-slate-500">Menampilkan <?= number_format($totalRows, 0, ',', '.') ?> data<?= $search !== '' ? ' hasil pencarian' : '' ?>.</p></div>
            <form method="get" class="flex flex-col gap-2 sm:flex-row"><input type="hidden" name="page" value="daa"><div class="relative"><i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i><input type="search" name="q" value="<?= e($search) ?>" placeholder="Cari aplikasi, SKPD, atau RAA..." class="w-full rounded-md border border-slate-300 py-2 pl-9 pr-3 text-xs outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100 sm:w-64"></div><select name="per_page" class="rounded-md border border-slate-300 px-3 py-2 text-xs outline-none focus:border-blue-600" aria-label="Jumlah data per halaman"><?php foreach([10,25,50] as $n):?><option value="<?= $n ?>" <?= $perPage===$n?'selected':'' ?>><?= $n ?> data</option><?php endforeach;?></select><button class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Tampilkan</button><?php if ($search !== ''): ?><a href="index.php?page=daa" class="inline-flex items-center justify-center rounded-md px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">Reset</a><?php endif; ?></form>
        </div>
        <div class="overflow-x-auto"><table class="min-w-full divide-y text-left text-xs"><thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500"><tr><th class="px-2.5 py-2.5">Kode</th><th class="px-2.5 py-2.5">Nama Aplikasi &amp; Uraian</th><th class="px-2.5 py-2.5">RAA</th><th class="px-2.5 py-2.5">SKPD</th><th class="px-2.5 py-2.5 text-right">Aksi</th></tr></thead><tbody class="divide-y">
        <?php if(!$rows):?><tr><td colspan="5" class="p-10 text-center text-slate-500">Belum ada data DAA.</td></tr><?php endif;?>
        <?php foreach($rows as $i=>$row):
            $record=['id'=>(int)$row['id']];
            foreach($daaFields as $field)$record[$field]=$row[$field]??'';
            $record['id_dab']=$row['dab_ids']?array_map('intval',explode(',',$row['dab_ids'])):[];
            $record['id_dad']=$row['dad_ids']?array_map('intval',explode(',',$row['dad_ids'])):[];
            $record['id_dal']=$row['dal_ids']?array_map('intval',explode(',',$row['dal_ids'])):[];
            $record['id_dai_splp']=$row['splp_ids']?array_map('intval',explode(',',$row['splp_ids'])):[];
            $record+=['skpd_label'=>daa_label($row['kode_skpd'],$row['nama_skpd']),'raa_label'=>daa_label($row['kode_raa_4'],$row['nama_raa_4']),'dab_labels'=>(string)($row['dab_labels']??''),'dad_labels'=>(string)($row['dad_labels']??''),'dal_labels'=>(string)($row['dal_labels']??''),'splp_labels'=>(string)($row['splp_labels']??'')];
        ?><tr class="align-top hover:bg-slate-50">
            <td class="whitespace-nowrap p-3 font-semibold text-red-700"><?= e(sprintf('DAA-%03d', $record['id'])) ?></td>
            <td class="max-w-[320px] whitespace-normal break-words p-3"><b><?= e($record['nama_aplikasi']) ?></b><p class="mt-1 text-[11px] leading-relaxed text-slate-500"><?= e($record['uraian_aplikasi'] ?: '-') ?></p></td>
            <td class="max-w-[260px] whitespace-normal break-words p-3 text-red-700"><?= e($record['raa_label'] ?: '-') ?></td>
            <td class="max-w-[260px] whitespace-normal break-words p-3 font-semibold"><?= e($record['skpd_label'] ?: '-') ?></td>
            <td class="p-3"><div class="flex justify-end gap-1"><button class="daa-action" data-daa-view data-record="<?= e(json_encode($record,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)) ?>"><i data-lucide="eye"></i></button><button class="daa-action border-blue-200 bg-blue-50 text-blue-700" data-daa-edit data-record="<?= e(json_encode($record,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)) ?>"><i data-lucide="pencil"></i></button><button class="daa-action border-red-200 bg-red-50 text-red-700" data-daa-delete data-id="<?= $record['id'] ?>" data-name="<?= e($record['nama_aplikasi']) ?>"><i data-lucide="trash-2"></i></button></div></td>
        </tr><?php endforeach;?></tbody></table></div>
        <div class="flex justify-between border-t p-4 text-sm text-slate-500"><span>Menampilkan <?= $totalRows?$offset+1:0 ?>-<?= min($offset+$perPage,$totalRows) ?> dari <?= $totalRows ?> data</span><span><?php if($currentPage>1):?><a class="mr-2 rounded border p-2" href="<?= e(daa_url($currentPage-1,$search,$perPage)) ?>">Sebelumnya</a><?php endif;?><?php if($currentPage<$totalPages):?><a class="rounded border p-2" href="<?= e(daa_url($currentPage+1,$search,$perPage)) ?>">Berikutnya</a><?php endif;?></span></div>
    </div>
</section>

<style>
.daa-label{display:block;margin-bottom:.25rem;font-size:11px;font-weight:600;letter-spacing:.025em;text-transform:uppercase;color:#64748b}.daa-control{width:100%;border:1px solid #cbd5e1;border-radius:.5rem;background:#fff;padding:.5rem .75rem;font-size:.875rem;outline:none}.daa-control:focus{border-color:#dc2626;box-shadow:0 0 0 4px rgb(254 226 226/.7)}.daa-box{border:1px solid #e2e8f0;border-radius:.75rem;background:#fff;padding:.75rem}.daa-action{display:inline-flex;width:2rem;height:2rem;align-items:center;justify-content:center;border-width:1px;border-radius:.375rem}.daa-action svg{width:.875rem;height:.875rem}
</style>
<?php
$selectGroups=[
    'id_unit_kerja_operasional'=>['Unit Kerja Operasional',$skpdList],
    'id_dai_komputasi_awan'=>['Komputasi Awan',$cloudList],'id_dai_hardware_server'=>['Hardware Server',$serverList],
    'id_dai_jaringan_intra'=>['Jaringan Intra',$networkList],
];
$basisOptions = ['web' => 'Web', 'mobile' => 'Mobile', 'desktop' => 'Desktop', 'lainnya' => 'Lainnya'];
$licenseOptions = ['Open Source', 'Propietary'];
$languageOptions = ['PHP', 'JavaScript/TypeScript', 'Python', 'Java', 'C#', 'GoLang', 'Swift', 'Dart', 'Lainnya'];
$relationPickers = [
    'id_dab' => ['title' => 'DAB', 'label' => 'DAB Terkait', 'context_label' => 'Kode / Nama SKPD', 'item_label' => 'Kode / Nama Bisnis', 'required' => true, 'items' => array_map(static fn(array $x): array => ['id' => (int) $x['id'], 'context' => daa_label($x['kode_skpd'] ?? null, $x['nama_skpd'] ?? null), 'code' => sprintf('DAB-%03d', (int) $x['id']), 'name' => (string) $x['nama']], $dabList)],
    'id_dad' => ['title' => 'Domain Data', 'label' => 'Domain Data', 'context_label' => 'Kode / Nama SKPD', 'item_label' => 'Kode / Nama Data', 'required' => false, 'items' => array_map(static fn(array $x): array => ['id' => (int) $x['id'], 'context' => daa_label($x['kode_skpd'] ?? null, $x['nama_skpd'] ?? null), 'code' => sprintf('DAD-%03d', (int) $x['id']), 'name' => (string) $x['nama']], $dadList)],
    'id_dal' => ['title' => 'Domain Layanan', 'label' => 'Domain Layanan', 'context_label' => 'Kode / Nama SKPD', 'item_label' => 'Kode / Nama Layanan', 'required' => false, 'items' => array_map(static fn(array $x): array => ['id' => (int) $x['id'], 'context' => daa_label($x['kode_skpd'] ?? null, $x['nama_skpd'] ?? null), 'code' => sprintf('DAL-%03d', (int) $x['id']), 'name' => (string) $x['nama']], $dalList)],
    'id_dai_splp' => ['title' => 'Infrastruktur SPLP', 'label' => 'Infrastruktur SPLP', 'item_label' => 'Kode / Nama SPLP', 'required' => false, 'items' => array_map(static fn(array $x): array => ['id' => (int) $x['id'], 'code' => sprintf('DAI-%03d', (int) $x['id']), 'name' => (string) $x['nama']], $splpList)],
];
?>
<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 px-3 py-4" data-modal="daa-form"><form method="post" class="flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border bg-white shadow-2xl"><?= csrf_field() ?><input type="hidden" name="action" data-daa-action><input type="hidden" name="id" data-daa-field="id"><header class="flex justify-between border-b bg-gradient-to-r from-slate-50 to-white px-5 py-3"><div class="flex gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-700 text-white"><i data-lucide="app-window" class="h-4 w-4"></i></span><div><h2 class="text-base font-bold" data-daa-title>Tambah Data DAA</h2><p class="text-xs text-slate-500">Lengkapi identitas, teknologi, relasi, dan kontrol aplikasi.</p></div></div><button type="button" data-close class="h-9 w-9 rounded-lg border bg-white"><i data-lucide="x" class="mx-auto h-4 w-4"></i></button></header><div class="grid flex-1 gap-3 overflow-y-auto bg-slate-50/70 p-4 sm:p-5">
    <section class="daa-box"><h3 class="text-sm font-semibold">Identitas Aplikasi</h3><div class="mt-3 grid gap-3 md:grid-cols-2"><label><span class="daa-label">SKPD</span><select name="id_skpd" data-daa-field="id_skpd" class="daa-control" required><option value="">Pilih SKPD</option><?php foreach($skpdList as $x):?><option value="<?= $x['id'] ?>"><?= e(daa_label($x['kode'],$x['nama'])) ?></option><?php endforeach;?></select></label><label><span class="daa-label">Referensi RAA</span><select name="id_raa" data-daa-field="id_raa" class="daa-control" required><option value="">Pilih RAA</option><?php foreach($raaList as $x):?><option value="<?= $x['id'] ?>"><?= e(daa_label($x['kode'],$x['nama'])) ?></option><?php endforeach;?></select></label><div class="md:col-span-2"><?php $picker=$relationPickers['id_dab']; ?><span class="daa-label"><?= e($picker['label']) ?></span><div class="rounded-lg border border-slate-200 bg-white p-3"><div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"><div class="space-y-1 text-sm text-slate-600" data-daa-picker-summary="id_dab">Belum ada DAB dipilih.</div><button type="button" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-100" data-daa-picker-open="id_dab"><i data-lucide="list-checks" class="h-4 w-4"></i>Pilih DAB</button></div><select name="id_dab[]" data-daa-multi="id_dab" multiple required class="sr-only" aria-label="DAB terkait"><?php foreach($picker['items'] as $item):?><option value="<?= (int) $item['id'] ?>"><?= e($item['code'] . ' - ' . $item['name']) ?></option><?php endforeach;?></select></div></div><label class="md:col-span-2"><span class="daa-label">Nama Aplikasi</span><input name="nama_aplikasi" data-daa-field="nama_aplikasi" class="daa-control" required></label><?php foreach(['uraian_aplikasi'=>'Uraian Aplikasi','fungsi_aplikasi'=>'Fungsi Aplikasi','luaran'=>'Luaran'] as $f=>$l):?><label class="md:col-span-2"><span class="daa-label"><?= $l ?></span><textarea name="<?= $f ?>" data-daa-field="<?= $f ?>" rows="2" class="daa-control"></textarea></label><?php endforeach;?></div></section>
    <section class="daa-box"><h3 class="text-sm font-semibold">Teknologi</h3><div class="mt-3 grid gap-4"><fieldset><legend class="daa-label">Basis Aplikasi</legend><div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4"><?php foreach($basisOptions as $value=>$label):?><label class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"><input type="checkbox" name="basis_aplikasi[]" value="<?= e($value) ?>" data-daa-text-multi="basis_aplikasi" class="h-4 w-4 rounded border-slate-300 text-red-700 focus:ring-red-600"><span><?= e($label) ?></span></label><?php endforeach;?></div></fieldset><fieldset><legend class="daa-label">Tipe Lisensi</legend><div class="grid gap-2 sm:grid-cols-2"><?php foreach($licenseOptions as $option):?><label class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"><input type="radio" name="tipe_lisensi_aplikasi" value="<?= e($option) ?>" data-daa-field="tipe_lisensi_aplikasi" class="h-4 w-4 border-slate-300 text-red-700 focus:ring-red-600"><span><?= e($option) ?></span></label><?php endforeach;?></div></fieldset><fieldset><legend class="daa-label">Bahasa Pemrograman</legend><div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3"><?php foreach($languageOptions as $option):?><label class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"><input type="checkbox" name="bahasa_pemrograman[]" value="<?= e($option) ?>" data-daa-text-multi="bahasa_pemrograman" class="h-4 w-4 rounded border-slate-300 text-red-700 focus:ring-red-600"><span><?= e($option) ?></span></label><?php endforeach;?></div></fieldset><div class="grid gap-3 md:grid-cols-2"><?php foreach(['kerangka_pengembangan'=>'Kerangka Pengembangan','nama_basis_data'=>'Nama Basis Data'] as $f=>$l):?><label><span class="daa-label"><?= $l ?></span><input name="<?= $f ?>" data-daa-field="<?= $f ?>" class="daa-control"></label><?php endforeach;?></div></div></section>
    <section class="daa-box"><h3 class="text-sm font-semibold">Relasi Data, Layanan, dan SPLP <span class="font-normal text-slate-400">(opsional)</span></h3><div class="mt-3 grid gap-3"><?php foreach(['id_dad','id_dal','id_dai_splp'] as $pickerField): $picker=$relationPickers[$pickerField]; ?><div><span class="daa-label"><?= e($picker['label']) ?></span><div class="rounded-lg border border-slate-200 bg-white p-3"><div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"><div class="min-h-5 space-y-1 text-sm text-slate-600" data-daa-picker-summary="<?= e($pickerField) ?>">Belum ada pilihan.</div><button type="button" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-100" data-daa-picker-open="<?= e($pickerField) ?>"><i data-lucide="list-checks" class="h-4 w-4"></i>Pilih</button></div><select name="<?= e($pickerField) ?>[]" data-daa-multi="<?= e($pickerField) ?>" multiple class="sr-only" aria-label="<?= e($picker['label']) ?>"><?php foreach($picker['items'] as $item):?><option value="<?= (int) $item['id'] ?>"><?= e($item['code'] . ' - ' . $item['name']) ?></option><?php endforeach;?></select></div></div><?php endforeach;?></div></section>
    <section class="daa-box"><h3 class="text-sm font-semibold">Unit Kerja dan Infrastruktur <span class="font-normal text-slate-400">(opsional)</span></h3><div class="mt-3 grid gap-3 md:grid-cols-2"><?php foreach($selectGroups as $f=>[$l,$list]):?><label><span class="daa-label"><?= $l ?></span><select name="<?= $f ?>" data-daa-field="<?= $f ?>" class="daa-control"><option value="">Tidak ada</option><?php foreach($list as $x):?><option value="<?= $x['id'] ?>"><?php $isDai = str_starts_with($f, 'id_dai_'); ?><?= e($isDai ? sprintf('DAI-%03d - %s', (int) $x['id'], (string) $x['nama']) : daa_label($x['kode']??null,$x['nama'])) ?></option><?php endforeach;?></select></label><?php endforeach;?></div></section>
    <section class="daa-box"><h3 class="text-sm font-semibold">Kontrol Keamanan <span class="font-normal text-slate-400">(opsional)</span></h3><div class="mt-3 grid gap-3 md:grid-cols-2"><?php foreach($securityFields as $f=>[$l,$table]):?><label><span class="daa-label"><?= e($l) ?></span><select name="<?= $f ?>" data-daa-field="<?= $f ?>" class="daa-control"><option value="">Tidak ada</option><?php foreach($securityLists[$f] as $x):?><option value="<?= $x['id'] ?>"><?= e($x['nama']) ?></option><?php endforeach;?></select></label><?php endforeach;?></div></section>
    </div><footer class="flex justify-end gap-2 border-t px-5 py-3"><button type="button" data-close class="rounded-lg border px-4 py-2 text-sm font-semibold">Batal</button><button class="rounded-lg bg-red-700 px-5 py-2 text-sm font-semibold text-white" data-daa-submit>Simpan Data</button></footer></form></div>

<?php foreach($relationPickers as $pickerField => $picker): ?>
<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 p-4" data-modal="daa-picker-<?= e($pickerField) ?>"><div class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"><header class="flex items-start justify-between border-b border-slate-200 p-4 sm:p-5"><div><h2 class="text-lg font-bold"><?= e($picker['title']) ?></h2><p class="mt-1 text-sm text-slate-500">Pilih satu atau lebih data terkait.</p></div><button type="button" class="h-9 w-9 rounded-lg border border-slate-200 bg-white text-slate-500" data-close><i data-lucide="x" class="mx-auto h-4 w-4"></i></button></header><div class="border-b border-slate-200 bg-slate-50 p-4"><div class="relative"><i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i><input type="search" class="daa-control pl-9" placeholder="Cari kode atau nama..." data-daa-picker-search="<?= e($pickerField) ?>"></div></div><div class="flex-1 overflow-y-auto"><table class="min-w-full divide-y text-left text-sm"><thead class="sticky top-0 bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500"><tr><th class="w-12 px-4 py-3">Pilih</th><?php if (!empty($picker['context_label'])): ?><th class="px-4 py-3"><?= e($picker['context_label']) ?></th><?php endif; ?><th class="px-4 py-3"><?= e($picker['item_label']) ?></th></tr></thead><tbody class="divide-y"><?php foreach($picker['items'] as $item): $label = trim(($item['context'] ?? '') . ' ' . $item['code'] . ' ' . $item['name']); ?><tr data-daa-picker-row="<?= e($pickerField) ?>" data-search="<?= e(mb_strtolower($label)) ?>"><td class="px-4 py-3"><input type="checkbox" value="<?= (int) $item['id'] ?>" class="h-4 w-4 rounded border-slate-300 text-red-700 focus:ring-red-600" data-daa-picker-check="<?= e($pickerField) ?>"></td><?php if (!empty($picker['context_label'])): ?><td class="px-4 py-3"><?= e($item['context'] ?: '-') ?></td><?php endif; ?><td class="px-4 py-3"><span class="font-semibold text-red-700"><?= e($item['code']) ?></span> - <?= e($item['name']) ?></td></tr><?php endforeach; ?></tbody></table><p class="hidden p-8 text-center text-sm text-slate-500" data-daa-picker-empty="<?= e($pickerField) ?>">Data tidak ditemukan.</p></div><footer class="flex flex-col gap-2 border-t border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between"><span class="text-sm text-slate-500" data-daa-picker-count="<?= e($pickerField) ?>">0 data dipilih</span><button type="button" class="rounded-lg bg-red-700 px-5 py-2 text-sm font-semibold text-white" data-daa-picker-apply="<?= e($pickerField) ?>">Terapkan</button></footer></div></div>
<?php endforeach; ?>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 p-4" data-modal="daa-view"><div class="flex max-h-[90vh] w-full max-w-4xl flex-col rounded-2xl bg-white"><header class="flex justify-between border-b p-4"><h2 class="text-lg font-bold">Detail DAA</h2><button data-close><i data-lucide="x"></i></button></header><div class="grid flex-1 gap-3 overflow-y-auto bg-slate-50 p-4 md:grid-cols-2"><?php foreach(['nama_aplikasi'=>'Nama Aplikasi','skpd_label'=>'SKPD','raa_label'=>'RAA','dab_labels'=>'DAB','dad_labels'=>'Domain Data','dal_labels'=>'Domain Layanan','splp_labels'=>'Infrastruktur SPLP','uraian_aplikasi'=>'Uraian','fungsi_aplikasi'=>'Fungsi','luaran'=>'Luaran','basis_aplikasi'=>'Basis','tipe_lisensi_aplikasi'=>'Lisensi','bahasa_pemrograman'=>'Bahasa Pemrograman','kerangka_pengembangan'=>'Kerangka','nama_basis_data'=>'Basis Data'] as $f=>$l):?><div class="daa-box"><b class="text-xs uppercase text-slate-500"><?= $l ?></b><p class="mt-1 whitespace-pre-line text-sm" data-view="<?= $f ?>"></p></div><?php endforeach;?></div></div></div>
<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 p-4" data-modal="daa-delete"><form method="post" class="w-full max-w-md rounded-2xl bg-white p-6"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" data-delete-id><h2 class="text-xl font-bold">Hapus Data DAA?</h2><p class="mt-2 text-sm text-slate-500">Tindakan permanen dan tercatat pada audit trail.</p><p class="mt-4 rounded-xl bg-slate-50 p-4 font-semibold" data-delete-name></p><div class="mt-5 flex justify-end gap-2"><button type="button" data-close class="rounded border px-4 py-2">Batal</button><button class="rounded bg-red-700 px-4 py-2 text-white">Ya, Hapus</button></div></form></div>

<script>
(()=>{const modals=document.querySelectorAll('[data-modal]'),form=document.querySelector('[data-modal="daa-form"]'),view=document.querySelector('[data-modal="daa-view"]'),del=document.querySelector('[data-modal="daa-delete"]'),fields=<?= json_encode(array_merge(['id'],$daaFields)) ?>,multiFields=<?= json_encode($multiFields) ?>,textMultiFields=['basis_aplikasi','bahasa_pemrograman'],posted=<?= json_encode($formState,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
const f=n=>form.querySelector(`[data-daa-field="${n}"]`),open=m=>{m.classList.remove('hidden');m.classList.add('flex')},close=m=>{m.classList.add('hidden');m.classList.remove('flex')};
const splitTextChoices=v=>String(v||'').split(',').map(x=>x.trim()).filter(Boolean),selectedIds=n=>[...form.querySelectorAll(`[data-daa-multi="${n}"] option:checked`)].map(o=>Number(o.value));
const syncPicker=n=>{const ids=selectedIds(n),select=form.querySelector(`[data-daa-multi="${n}"]`),summary=form.querySelector(`[data-daa-picker-summary="${n}"]`),count=document.querySelector(`[data-daa-picker-count="${n}"]`);document.querySelectorAll(`[data-daa-picker-check="${n}"]`).forEach(c=>c.checked=ids.includes(Number(c.value)));const labels=[...select.selectedOptions].map(o=>o.textContent.trim());summary?.replaceChildren(...(labels.length?labels.map(label=>{const row=document.createElement('div');row.textContent=label;return row;}):[document.createTextNode(n==='id_dab'?'Belum ada DAB dipilih.':'Belum ada pilihan.')]));if(count)count.textContent=`${labels.length} data dipilih`};
const filterPicker=n=>{const input=document.querySelector(`[data-daa-picker-search="${n}"]`),empty=document.querySelector(`[data-daa-picker-empty="${n}"]`),keyword=(input?.value||'').trim().toLowerCase();let shown=0;document.querySelectorAll(`[data-daa-picker-row="${n}"]`).forEach(row=>{const match=!keyword||row.dataset.search.includes(keyword);row.classList.toggle('hidden',!match);if(match)shown++});empty?.classList.toggle('hidden',shown>0)};
const fill=r=>{fields.forEach(n=>{const input=f(n);if(input&&input.type!=='radio')input.value=r[n]||''});form.querySelectorAll('[name="tipe_lisensi_aplikasi"]').forEach(x=>x.checked=x.value===(r.tipe_lisensi_aplikasi||''));multiFields.forEach(n=>{let values=(r[n]||[]).map(Number);form.querySelectorAll(`[data-daa-multi="${n}"] option`).forEach(o=>o.selected=values.includes(Number(o.value)));syncPicker(n)});textMultiFields.forEach(n=>{let values=splitTextChoices(r[n]).map(x=>x.toLowerCase());form.querySelectorAll(`[data-daa-text-multi="${n}"]`).forEach(o=>o.checked=values.includes(o.value.toLowerCase()))})};
multiFields.forEach(n=>{document.querySelectorAll(`[data-daa-picker-check="${n}"]`).forEach(c=>c.onchange=()=>{const option=[...form.querySelectorAll(`[data-daa-multi="${n}"] option`)].find(o=>o.value===c.value);if(option)option.selected=c.checked;syncPicker(n)});document.querySelector(`[data-daa-picker-search="${n}"]`)?.addEventListener('input',()=>filterPicker(n));document.querySelector(`[data-daa-picker-open="${n}"]`)?.addEventListener('click',()=>{syncPicker(n);filterPicker(n);const modal=document.querySelector(`[data-modal="daa-picker-${n}"]`);open(modal);modal.querySelector('input[type="search"]')?.focus()});document.querySelector(`[data-daa-picker-apply="${n}"]`)?.addEventListener('click',()=>close(document.querySelector(`[data-modal="daa-picker-${n}"]`)))});
document.querySelector('[data-daa-add]').onclick=()=>{fill({});form.querySelector('[data-daa-action]').value='create';form.querySelector('[data-daa-title]').textContent='Tambah Data DAA';form.querySelector('[data-daa-submit]').textContent='Simpan Data';open(form)};
document.querySelectorAll('[data-daa-edit]').forEach(b=>b.onclick=()=>{fill(JSON.parse(b.dataset.record));form.querySelector('[data-daa-action]').value='update';form.querySelector('[data-daa-title]').textContent='Edit Data DAA';form.querySelector('[data-daa-submit]').textContent='Simpan Perubahan';open(form)});
document.querySelectorAll('[data-daa-view]').forEach(b=>b.onclick=()=>{let r=JSON.parse(b.dataset.record);view.querySelectorAll('[data-view]').forEach(x=>x.textContent=r[x.dataset.view]||'—');open(view)});
document.querySelectorAll('[data-daa-delete]').forEach(b=>b.onclick=()=>{del.querySelector('[data-delete-id]').value=b.dataset.id;del.querySelector('[data-delete-name]').textContent=b.dataset.name;open(del)});
document.querySelectorAll('[data-close]').forEach(b=>b.onclick=()=>close(b.closest('[data-modal]')));modals.forEach(m=>m.onclick=e=>{if(e.target===m)close(m)});fill(posted);<?php if($openFormModal):?>form.querySelector('[data-daa-action]').value=<?= json_encode($formMode) ?>;open(form);<?php endif;?>})();
</script>
