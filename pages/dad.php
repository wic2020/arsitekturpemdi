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
$dadFields = array_merge([
    'id_skpd', 'id_program', 'id_rad', 'nama_data', 'uraian_data', 'tujuan_data',
    'sifat_data', 'jenis_data', 'validitas_data', 'penghasil_data', 'input_data',
    'output_data', 'interoperabilitas', 'id_dal',
], array_keys($securityFields));
$formErrors = [];
$openFormModal = false;
$formMode = 'create';
$formState = array_fill_keys($dadFields, '');
$formState['id'] = '';
$formState['id_dab'] = [];

function dad_int_or_null(mixed $value): ?int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id ? (int) $id : null;
}

function dad_label(?string $code, ?string $name): string
{
    $code = trim((string) $code);
    $name = trim((string) $name);
    return trim($code . ($code !== '' && $name !== '' ? ' - ' : '') . $name);
}

function dad_url(int $page, string $search, int $perPage): string
{
    return 'index.php?' . http_build_query(['page' => 'dad', 'q' => $search, 'per_page' => $perPage, 'p' => $page]);
}

$skpdList = db()->query('SELECT id, kode_skpd, nama_skpd FROM skpd ORDER BY kode_skpd, nama_skpd')->fetchAll();
$programList = db()->query('SELECT id, kode_program, nama_program, id_skpd, id_rad FROM program ORDER BY kode_program, nama_program')->fetchAll();
$radList = db()->query('SELECT id, kode_rad_4, nama_rad_4 FROM rad ORDER BY kode_rad_4, nama_rad_4')->fetchAll();
$dabList = db()->query('SELECT d.id, d.nama_bisnis, s.kode_skpd, s.nama_skpd, p.kode_program FROM dab d LEFT JOIN skpd s ON s.id=d.id_skpd LEFT JOIN program p ON p.id=d.id_program ORDER BY s.kode_skpd, p.kode_program, d.id')->fetchAll();
$dalList = db()->query(
    'SELECT d.id, d.id_program, d.nama_layanan
     FROM dal d
     INNER JOIN program p ON p.id = d.id_program
     ORDER BY d.id, d.nama_layanan'
)->fetchAll();
$securityLists = [];
foreach ($securityFields as $field => [$label, $table]) {
    $securityLists[$field] = db()->query("SELECT id, nama FROM {$table} ORDER BY nama")->fetchAll();
}
$validIds = [
    'id_skpd' => array_flip(array_map('intval', array_column($skpdList, 'id'))),
    'id_rad' => array_flip(array_map('intval', array_column($radList, 'id'))),
    'id_dal' => array_flip(array_map('intval', array_column($dalList, 'id'))),
    'id_dab' => array_flip(array_map('intval', array_column($dabList, 'id'))),
];
foreach ($securityLists as $field => $list) $validIds[$field] = array_flip(array_map('intval', array_column($list, 'id')));
$programMap = [];
foreach ($programList as $program) $programMap[(int) $program['id']] = $program;
$dalMap = [];
foreach ($dalList as $dal) $dalMap[(int) $dal['id']] = $dal;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if (in_array($action, ['create', 'update'], true)) {
        $formMode = $action;
        $openFormModal = true;
        $recordId = dad_int_or_null($_POST['id'] ?? null);
        $formState['id'] = $recordId ?: '';
        foreach ($dadFields as $field) $formState[$field] = trim((string) ($_POST[$field] ?? ''));
        $postedDabs = is_array($_POST['id_dab'] ?? null) ? $_POST['id_dab'] : [];
        $selectedDabs = array_values(array_unique(array_filter(array_map('dad_int_or_null', $postedDabs))));
        $formState['id_dab'] = $selectedDabs;

        $idSkpd = dad_int_or_null($formState['id_skpd']);
        $idProgram = dad_int_or_null($formState['id_program']);
        $idRad = dad_int_or_null($formState['id_rad']);
        if (!$idSkpd || !isset($validIds['id_skpd'][$idSkpd])) $formErrors[] = 'SKPD wajib dipilih.';
        if (!$idProgram || !isset($programMap[$idProgram])) $formErrors[] = 'Program wajib dipilih.';
        if (!$idRad || !isset($validIds['id_rad'][$idRad])) $formErrors[] = 'Referensi RAD wajib dipilih.';
        if ($idProgram && $idSkpd && isset($programMap[$idProgram]) && (int) $programMap[$idProgram]['id_skpd'] !== $idSkpd) $formErrors[] = 'Program tidak sesuai dengan SKPD.';
        if (!$selectedDabs || array_diff($selectedDabs, array_keys($validIds['id_dab']))) $formErrors[] = 'Minimal satu referensi DAB yang valid wajib dipilih.';
        if ($formState['nama_data'] === '') $formErrors[] = 'Nama data wajib diisi.';
        if (mb_strlen($formState['nama_data']) > 255) $formErrors[] = 'Nama data maksimal 255 karakter.';
        foreach (['sifat_data', 'jenis_data', 'validitas_data', 'penghasil_data', 'interoperabilitas'] as $field) {
            if (mb_strlen($formState[$field]) > 255) $formErrors[] = ucfirst(str_replace('_', ' ', $field)) . ' maksimal 255 karakter.';
        }
        foreach (array_merge(['id_dal'], array_keys($securityFields)) as $field) {
            $id = dad_int_or_null($formState[$field]);
            if ($id && !isset($validIds[$field][$id])) $formErrors[] = 'Pilihan ' . str_replace('id_', '', str_replace('_', ' ', $field)) . ' tidak valid.';
        }
        $idDal = dad_int_or_null($formState['id_dal']);
        if ($idDal && $idProgram && isset($dalMap[$idDal]) && (int) $dalMap[$idDal]['id_program'] !== $idProgram) {
            $formErrors[] = 'Layanan terkait tidak sesuai dengan program.';
        }
        if ($action === 'update' && !$recordId) $formErrors[] = 'ID data DAD tidak valid.';

        $oldValues = null;
        if (!$formErrors && $action === 'update') {
            $stmt = db()->prepare('SELECT * FROM dad WHERE id=:id LIMIT 1');
            $stmt->execute(['id' => $recordId]);
            $oldValues = $stmt->fetch();
            if (!$oldValues) $formErrors[] = 'Data DAD tidak ditemukan.';
            else {
                $stmt = db()->prepare('SELECT id_dab FROM dad_dab WHERE id_dad=:id ORDER BY id_dab');
                $stmt->execute(['id' => $recordId]);
                $oldValues['id_dab'] = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
            }
        }
        if (!$formErrors) {
            db()->beginTransaction();
            try {
                $params = [];
                foreach ($dadFields as $field) {
                    $params[$field] = str_starts_with($field, 'id_')
                        ? dad_int_or_null($formState[$field])
                        : ($formState[$field] !== '' ? $formState[$field] : null);
                }
                $params['updated_by'] = (int) $user['id'];
                if ($action === 'create') {
                    $params['created_by'] = (int) $user['id'];
                    $columns = array_keys($params);
                    $sql = 'INSERT INTO dad (' . implode(',', $columns) . ') VALUES (:' . implode(',:', $columns) . ')';
                    db()->prepare($sql)->execute($params);
                    $recordId = (int) db()->lastInsertId();
                } else {
                    $params['id'] = $recordId;
                    $sets = array_map(static fn(string $field): string => "{$field}=:{$field}", array_filter(array_keys($params), static fn(string $field): bool => $field !== 'id'));
                    db()->prepare('UPDATE dad SET ' . implode(',', $sets) . ' WHERE id=:id')->execute($params);
                    db()->prepare('DELETE FROM dad_dab WHERE id_dad=:id')->execute(['id' => $recordId]);
                }
                $link = db()->prepare('INSERT INTO dad_dab (id_dad,id_dab) VALUES (:id_dad,:id_dab)');
                foreach ($selectedDabs as $idDab) $link->execute(['id_dad' => $recordId, 'id_dab' => $idDab]);
                $stmt = db()->prepare('SELECT * FROM dad WHERE id=:id');
                $stmt->execute(['id' => $recordId]);
                $newValues = $stmt->fetch() ?: $params;
                $newValues['id_dab'] = $selectedDabs;
                audit_log((int) $user['id'], $action, 'dad', $recordId, ($action === 'create' ? 'Menambahkan' : 'Mengubah') . ' domain data ' . $formState['nama_data'], $oldValues, $newValues, true);
                db()->commit();
                set_flash('success', $action === 'create' ? 'Data DAD berhasil ditambahkan.' : 'Data DAD berhasil diperbarui.');
                redirect('index.php?page=dad');
            } catch (Throwable $exception) {
                db()->rollBack();
                error_log('Penyimpanan DAD gagal: ' . $exception->getMessage());
                $formErrors[] = 'Data DAD gagal disimpan.';
            }
        }
    } elseif ($action === 'delete') {
        $recordId = dad_int_or_null($_POST['id'] ?? null);
        $stmt = db()->prepare('SELECT * FROM dad WHERE id=:id');
        $stmt->execute(['id' => $recordId]);
        $record = $stmt->fetch();
        if (!$recordId || !$record) {
            set_flash('error', 'Data DAD tidak ditemukan.');
            redirect('index.php?page=dad');
        }
        $stmt = db()->prepare('SELECT id_dab FROM dad_dab WHERE id_dad=:id');
        $stmt->execute(['id' => $recordId]);
        $record['id_dab'] = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        db()->beginTransaction();
        try {
            db()->prepare('DELETE FROM dad_dab WHERE id_dad=:id')->execute(['id' => $recordId]);
            db()->prepare('DELETE FROM dad WHERE id=:id')->execute(['id' => $recordId]);
            audit_log((int) $user['id'], 'delete', 'dad', $recordId, 'Menghapus domain data ' . $record['nama_data'], $record, null, true);
            db()->commit();
            set_flash('success', 'Data DAD berhasil dihapus.');
        } catch (Throwable $exception) {
            db()->rollBack();
            set_flash('error', 'Data DAD gagal dihapus.');
        }
        redirect('index.php?page=dad');
    }
}

$search = trim((string) ($_GET['q'] ?? ''));
$perPage = (int) ($_GET['per_page'] ?? 10);
if (!in_array($perPage, [10, 25, 50], true)) $perPage = 10;
$currentPage = max(1, (int) ($_GET['p'] ?? 1));
$where = '';
$query = [];
if ($search !== '') {
    $where = " WHERE CONCAT_WS(' ',d.nama_data,d.uraian_data,d.tujuan_data,d.jenis_data,d.penghasil_data,s.nama_skpd,p.nama_program,r.nama_rad_4,l.nama_layanan) LIKE :search";
    $query['search'] = "%{$search}%";
}
$joins = ' FROM dad d LEFT JOIN skpd s ON s.id=d.id_skpd LEFT JOIN program p ON p.id=d.id_program LEFT JOIN rad r ON r.id=d.id_rad LEFT JOIN dal l ON l.id=d.id_dal LEFT JOIN users c ON c.id=d.created_by LEFT JOIN users u ON u.id=d.updated_by';
$stmt = db()->prepare('SELECT COUNT(*)' . $joins . $where);
$stmt->execute($query);
$totalRows = (int) $stmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $perPage;
$stmt = db()->prepare('SELECT d.*,s.kode_skpd,s.nama_skpd,p.kode_program,p.nama_program,r.kode_rad_4,r.nama_rad_4,l.nama_layanan,c.name created_by_name,u.name updated_by_name,
    (SELECT GROUP_CONCAT(x.id_dab ORDER BY x.id_dab) FROM dad_dab x WHERE x.id_dad=d.id) dab_ids,
    (SELECT MIN(x.id_dab) FROM dad_dab x WHERE x.id_dad=d.id) first_dab_id,
    (SELECT GROUP_CONCAT(b.nama_bisnis ORDER BY b.nama_bisnis SEPARATOR " • ") FROM dad_dab x JOIN dab b ON b.id=x.id_dab WHERE x.id_dad=d.id) dab_labels'
    . $joins . $where . ' ORDER BY s.kode_skpd ASC, p.kode_program ASC, d.id ASC LIMIT :limit OFFSET :offset');
foreach ($query as $key => $value) $stmt->bindValue(":{$key}", $value);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();
$programJson = array_map(static fn(array $p): array => ['id'=>(int)$p['id'],'skpd_id'=>(int)$p['id_skpd'],'rad_id'=>$p['id_rad'] ? (int)$p['id_rad'] : null,'label'=>dad_label($p['kode_program'],$p['nama_program'])], $programList);
$dalJson = array_map(static fn(array $d): array => [
    'id' => (int) $d['id'],
    'program_id' => (int) $d['id_program'],
    'label' => sprintf('DAL-%03d %s', (int) $d['id'], trim((string) $d['nama_layanan'])),
], $dalList);
$dabLabelMap = [];
foreach ($dabList as $dab) $dabLabelMap[(int) $dab['id']] = sprintf('DAB-%03d - %s', (int) $dab['id'], (string) $dab['nama_bisnis']);
?>

<section class="space-y-5">
    <div class="flex flex-col gap-3 rounded-2xl border bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div><p class="text-xs font-semibold uppercase text-red-700">Domain Arsitektur</p><h1 class="mt-1 text-xl font-bold">Domain Arsitektur Data</h1><p class="mt-1 text-sm text-slate-500">Kelola data, referensi, pertukaran, layanan, dan kontrol keamanannya.</p></div>
        <div class="flex gap-2">
            <a href="cetak/cetak_dad.php" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"><i data-lucide="printer" class="h-4 w-4"></i>Cetak</a>
            <button class="rounded-lg bg-red-700 px-4 py-2.5 font-semibold text-white" data-dad-add><i data-lucide="plus" class="mr-1 inline h-4 w-4"></i>Tambah DAD</button>
        </div>
    </div>
    <?php if ($formErrors): ?><div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><b>Data belum bisa disimpan.</b><ul class="mt-1 list-disc pl-5"><?php foreach ($formErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-4 py-3 lg:flex-row lg:items-center lg:justify-between">
            <div><h2 class="font-semibold text-slate-900">Daftar Domain Data</h2><p class="mt-1 text-sm text-slate-500">Menampilkan <?= number_format($totalRows, 0, ',', '.') ?> data<?= $search !== '' ? ' hasil pencarian' : '' ?>.</p></div>
            <form method="get" class="flex flex-col gap-2 sm:flex-row"><input type="hidden" name="page" value="dad"><div class="relative"><i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i><input type="search" name="q" value="<?= e($search) ?>" placeholder="Cari nama data, SKPD, program..." class="w-full rounded-md border border-slate-300 py-2 pl-9 pr-3 text-xs outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100 sm:w-64"></div><select name="per_page" class="rounded-md border border-slate-300 px-3 py-2 text-xs outline-none focus:border-blue-600" aria-label="Jumlah data per halaman"><?php foreach ([10,25,50] as $n): ?><option value="<?= $n ?>" <?= $perPage===$n?'selected':'' ?>><?= $n ?> data</option><?php endforeach; ?></select><button class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Tampilkan</button><?php if ($search !== ''): ?><a href="index.php?page=dad" class="inline-flex items-center justify-center rounded-md px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">Reset</a><?php endif; ?></form>
        </div>
        <div class="overflow-x-auto"><table class="min-w-full divide-y text-left text-xs"><thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500"><tr><th class="px-2.5 py-2.5">Kode</th><th class="px-2.5 py-2.5">Nama Data</th><th class="px-2.5 py-2.5">Sifat Data</th><th class="px-2.5 py-2.5">Jenis Data</th><th class="px-2.5 py-2.5">RAD</th><th class="px-2.5 py-2.5">SKPD / Program</th><th class="px-2.5 py-2.5 text-right">Aksi</th></tr></thead><tbody class="divide-y">
        <?php if (!$rows): ?><tr><td colspan="7" class="p-10 text-center text-slate-500">Belum ada data DAD.</td></tr><?php endif; ?>
        <?php foreach ($rows as $i => $row):
            $record = [];
            foreach (array_merge(['id'], $dadFields) as $field) $record[$field] = $row[$field] ?? '';
            $record['id_dab'] = $row['dab_ids'] ? array_map('intval', explode(',', $row['dab_ids'])) : [];
            $recordDabLabels = array_values(array_filter(array_map(static fn(int $id): string => $dabLabelMap[$id] ?? '', $record['id_dab'])));
            $record += ['skpd_label'=>dad_label($row['kode_skpd'],$row['nama_skpd']),'program_label'=>dad_label($row['kode_program'],$row['nama_program']),'rad_label'=>dad_label($row['kode_rad_4'],$row['nama_rad_4']),'dal_label'=>$row['id_dal'] ? sprintf('DAL-%03d - %s', (int) $row['id_dal'], (string) ($row['nama_layanan'] ?? '')) : '','dab_labels'=>implode(' • ', $recordDabLabels)];
        ?><tr class="align-top"><?php
            $cells = [
                '<b class="whitespace-nowrap text-red-700">'.e(sprintf('DAD-%03d', (int) $record['id'])).'</b>',
                '<b>'.e($record['nama_data']).'</b><br><span class="text-slate-500">'.e($record['uraian_data'] ?: '-').'</span>',
                e($record['sifat_data'] ?: '-'),
                e($record['jenis_data'] ?: '-'),
                '<span class="text-red-700">'.e($record['rad_label'] ?: '-').'</span>',
                '<b>'.e($record['skpd_label'] ?: '-').'</b><br><span class="text-slate-500">'.e($record['program_label'] ?: '-').'</span>',
            ];
            foreach ($cells as $cell): ?><td class="max-w-[230px] whitespace-normal break-words p-3"><?= $cell ?></td><?php endforeach; ?>
            <td class="p-3"><div class="flex justify-end gap-1"><button data-dad-view data-record="<?= e(json_encode($record, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)) ?>" title="Lihat" class="h-8 w-8 rounded border"><i data-lucide="eye" class="mx-auto h-4 w-4"></i></button><button data-dad-edit data-record="<?= e(json_encode($record, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)) ?>" title="Edit" class="h-8 w-8 rounded border border-blue-200 bg-blue-50 text-blue-700"><i data-lucide="pencil" class="mx-auto h-4 w-4"></i></button><button data-dad-delete data-id="<?= (int)$row['id'] ?>" data-name="<?= e($row['nama_data']) ?>" title="Hapus" class="h-8 w-8 rounded border border-red-200 bg-red-50 text-red-700"><i data-lucide="trash-2" class="mx-auto h-4 w-4"></i></button></div></td>
        </tr><?php endforeach; ?></tbody></table></div>
        <?php if ($totalRows > 0): ?>
            <div class="flex flex-col gap-3 border-t border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <p class="text-sm text-slate-500">
                    Data <?= number_format($offset + 1, 0, ',', '.') ?>&ndash;<?= number_format(min($offset + $perPage, $totalRows), 0, ',', '.') ?>
                    dari <?= number_format($totalRows, 0, ',', '.') ?>
                </p>
                <?php render_numbered_pagination(
                    $currentPage,
                    $totalPages,
                    static fn(int $pageNumber): string => dad_url($pageNumber, $search, $perPage)
                ); ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
    .form-label { display: block; margin-bottom: .25rem; font-size: 11px; font-weight: 600; letter-spacing: .025em; text-transform: uppercase; color: #64748b; }
    .form-control { width: 100%; border: 1px solid #cbd5e1; border-radius: .5rem; background: #fff; padding: .5rem .75rem; font-size: .875rem; color: #0f172a; outline: none; transition: border-color .15s, box-shadow .15s; }
    .form-control:focus { border-color: #dc2626; box-shadow: 0 0 0 4px rgb(254 226 226 / .7); }
</style>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 px-3 py-4" data-modal="dad-form"><form method="post" class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"><?= csrf_field() ?><input type="hidden" name="action" data-dad-action><input type="hidden" name="id" data-dad-field="id"><header class="flex items-start justify-between border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white px-4 py-3 sm:px-5"><div class="flex items-start gap-3"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-700 text-white shadow-sm ring-4 ring-red-50"><i data-lucide="database" class="h-4 w-4"></i></span><div><h2 class="text-base font-bold tracking-tight" data-dad-title>Tambah Data DAD</h2><p class="mt-0.5 text-xs text-slate-500">Lengkapi identitas dan tata kelola data.</p></div></div><button type="button" class="h-9 w-9 rounded-lg border border-slate-200 bg-white text-slate-500" data-close><i data-lucide="x" class="mx-auto h-4 w-4"></i></button></header><div class="grid flex-1 gap-3 overflow-y-auto bg-slate-50/70 p-4 sm:p-5">
    <div class="grid gap-3 lg:grid-cols-2"><label><span class="form-label">SKPD</span><select name="id_skpd" data-dad-field="id_skpd" required class="form-control"><option value="">Pilih SKPD</option><?php foreach($skpdList as $x):?><option value="<?= $x['id'] ?>"><?= e(dad_label($x['kode_skpd'],$x['nama_skpd'])) ?></option><?php endforeach;?></select></label><label><span class="form-label">Program</span><select name="id_program" data-dad-field="id_program" required class="form-control"></select></label></div>
    <label><span class="form-label">Referensi RAD</span><select name="id_rad" data-dad-field="id_rad" required class="form-control"><option value="">Pilih RAD</option><?php foreach($radList as $x):?><option value="<?= $x['id'] ?>"><?= e(dad_label($x['kode_rad_4'],$x['nama_rad_4'])) ?></option><?php endforeach;?></select></label>
    <label><span class="form-label">DAB Terkait</span><div class="rounded-lg border border-slate-200 bg-white p-3"><div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"><div class="space-y-1 text-sm text-slate-600" data-dad-dab-summary>Belum ada DAB dipilih.</div><button type="button" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-100" data-dad-dab-open><i data-lucide="list-checks" class="h-4 w-4"></i>Pilih DAB</button></div><select name="id_dab[]" data-dad-dab multiple required class="sr-only" aria-label="DAB terkait"><?php foreach($dabList as $x):?><option value="<?= $x['id'] ?>"><?= e(sprintf('DAB-%03d', (int) $x['id']) . ' - ' . $x['nama_bisnis']) ?></option><?php endforeach;?></select></div></label>
    <label><span class="form-label">Nama Data</span><input name="nama_data" data-dad-field="nama_data" maxlength="255" required class="form-control"></label>
    <?php foreach(['uraian_data'=>'Uraian Data','tujuan_data'=>'Tujuan Data','input_data'=>'Input Data','output_data'=>'Output Data'] as $f=>$l):?><label><span class="form-label"><?= e($l) ?></span><textarea name="<?= $f ?>" data-dad-field="<?= $f ?>" rows="2" class="form-control"></textarea></label><?php endforeach;?>
    <div class="grid gap-3 lg:grid-cols-2">
        <label><span class="form-label">Sifat Data</span><select name="sifat_data" data-dad-field="sifat_data" class="form-control"><option value="">Pilih Sifat Data</option><?php foreach(['publik'=>'Publik','privat'=>'Privat'] as $value=>$label):?><option value="<?= e($value) ?>"><?= e($label) ?></option><?php endforeach;?></select></label>
        <label><span class="form-label">Jenis Data</span><select name="jenis_data" data-dad-field="jenis_data" class="form-control"><option value="">Pilih Jenis Data</option><?php foreach(['tabular'=>'Tabular','geospasial'=>'Geospasial'] as $value=>$label):?><option value="<?= e($value) ?>"><?= e($label) ?></option><?php endforeach;?></select></label>
        <label><span class="form-label">Validitas Data</span><select name="validitas_data" data-dad-field="validitas_data" class="form-control"><option value="">Pilih Validitas Data</option><?php foreach(['terverifikasi'=>'Terverifikasi','belum terverifikasi'=>'Belum Terverifikasi'] as $value=>$label):?><option value="<?= e($value) ?>"><?= e($label) ?></option><?php endforeach;?></select></label>
        <label><span class="form-label">Penghasil Data</span><select name="penghasil_data" data-dad-field="penghasil_data" class="form-control"><option value="">Pilih Penghasil Data</option><?php foreach($skpdList as $x): $label = dad_label($x['kode_skpd'],$x['nama_skpd']); ?><option value="<?= e($label) ?>"><?= e($label) ?></option><?php endforeach;?></select></label>
        <label><span class="form-label">Interoperabilitas</span><input name="interoperabilitas" data-dad-field="interoperabilitas" maxlength="255" class="form-control"></label>
    </div>
    <label><span class="form-label">Layanan Terkait</span><select name="id_dal" data-dad-field="id_dal" class="form-control"><option value="">Pilih program terlebih dahulu</option></select><span class="mt-1 block text-[11px] text-slate-500">Pilihan layanan disesuaikan dengan program yang dipilih.</span></label>
    <section class="rounded-xl border border-slate-200 bg-white p-3"><h3 class="text-sm font-semibold">Kontrol Keamanan <span class="font-normal text-slate-400">(opsional)</span></h3><div class="mt-3 grid gap-3 lg:grid-cols-2"><?php foreach($securityFields as $f=>[$l,$t]):?><label><span class="form-label"><?= e($l) ?></span><select name="<?= $f ?>" data-dad-field="<?= $f ?>" class="form-control"><option value="">Tidak ada</option><?php foreach($securityLists[$f] as $x):?><option value="<?= $x['id'] ?>"><?= e($x['nama']) ?></option><?php endforeach;?></select></label><?php endforeach;?></div></section>
    </div><footer class="flex justify-end gap-2 border-t border-slate-200 bg-white px-4 py-3 sm:px-5"><button type="button" data-close class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold">Batal</button><button class="rounded-lg bg-red-700 px-5 py-2 text-sm font-semibold text-white" data-dad-submit>Simpan Data</button></footer></form></div>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 p-4" data-modal="dad-dab"><div class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"><header class="flex items-start justify-between border-b border-slate-200 p-4 sm:p-5"><div><h2 class="text-lg font-bold">DAB</h2><p class="mt-1 text-sm text-slate-500">Pilih satu atau lebih domain arsitektur bisnis.</p></div><button type="button" class="h-9 w-9 rounded-lg border border-slate-200 bg-white text-slate-500" data-close><i data-lucide="x" class="mx-auto h-4 w-4"></i></button></header><div class="border-b border-slate-200 bg-slate-50 p-4"><div class="relative"><i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i><input type="search" class="form-control pl-9" placeholder="Cari SKPD atau DAB..." data-dad-dab-search></div></div><div class="flex-1 overflow-y-auto"><table class="min-w-full divide-y text-left text-sm"><thead class="sticky top-0 bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500"><tr><th class="w-12 px-4 py-3">Pilih</th><th class="px-4 py-3">Kode &amp; Nama SKPD</th><th class="px-4 py-3">Kode &amp; Nama DAB 4</th></tr></thead><tbody class="divide-y" data-dad-dab-rows><?php foreach($dabList as $x): $dabCode = sprintf('DAB-%03d', (int) $x['id']); $skpdLabel = dad_label($x['kode_skpd'] ?? null, $x['nama_skpd'] ?? null); $dabLabel = $dabCode . ' - ' . $x['nama_bisnis']; ?><tr data-dab-row data-search="<?= e(mb_strtolower($skpdLabel . ' ' . $dabLabel)) ?>"><td class="px-4 py-3"><input type="checkbox" value="<?= (int) $x['id'] ?>" class="h-4 w-4 rounded border-slate-300 text-red-700 focus:ring-red-600" data-dab-check></td><td class="px-4 py-3"><?= e($skpdLabel ?: '-') ?></td><td class="px-4 py-3"><span class="font-semibold text-red-700"><?= e($dabCode) ?></span><span class="text-slate-700"> - <?= e($x['nama_bisnis']) ?></span></td></tr><?php endforeach; ?></tbody></table><p class="hidden p-8 text-center text-sm text-slate-500" data-dad-dab-empty>DAB tidak ditemukan.</p></div><footer class="flex flex-col gap-2 border-t border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between"><span class="text-sm text-slate-500" data-dad-dab-count>0 DAB dipilih</span><button type="button" class="rounded-lg bg-red-700 px-5 py-2 text-sm font-semibold text-white" data-dad-dab-apply>Terapkan</button></footer></div></div>
<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 p-4" data-modal="dad-view"><div class="flex max-h-[90vh] w-full max-w-4xl flex-col rounded-2xl bg-white"><header class="flex justify-between border-b p-5"><h2 class="text-lg font-bold">Detail DAD</h2><button data-close><i data-lucide="x"></i></button></header><div class="grid flex-1 gap-3 overflow-y-auto bg-slate-50 p-5 md:grid-cols-2"><?php foreach(['nama_data'=>'Nama Data','skpd_label'=>'SKPD','program_label'=>'Program','rad_label'=>'RAD','dab_labels'=>'DAB','dal_label'=>'Layanan','uraian_data'=>'Uraian','tujuan_data'=>'Tujuan','sifat_data'=>'Sifat','jenis_data'=>'Jenis','validitas_data'=>'Validitas','penghasil_data'=>'Penghasil','input_data'=>'Input','output_data'=>'Output','interoperabilitas'=>'Interoperabilitas'] as $f=>$l):?><div class="rounded border bg-white p-3"><b class="text-xs uppercase text-slate-500"><?= $l ?></b><p class="mt-1 whitespace-pre-line" data-view="<?= $f ?>"></p></div><?php endforeach;?></div></div></div>
<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 p-4" data-modal="dad-delete"><form method="post" class="w-full max-w-md rounded-2xl bg-white p-6"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" data-delete-id><h2 class="text-xl font-bold">Hapus Data DAD?</h2><p class="mt-3 rounded bg-slate-50 p-4" data-delete-name></p><div class="mt-4 flex justify-end gap-2"><button type="button" data-close class="rounded border px-4 py-2">Batal</button><button class="rounded bg-red-700 px-4 py-2 text-white">Ya, Hapus</button></div></form></div>

<script>
(()=>{const modals=document.querySelectorAll('[data-modal]'),form=document.querySelector('[data-modal="dad-form"]'),view=document.querySelector('[data-modal="dad-view"]'),del=document.querySelector('[data-modal="dad-delete"]'),dabModal=document.querySelector('[data-modal="dad-dab"]'),fields=<?= json_encode(array_merge(['id'],$dadFields)) ?>,programs=<?= json_encode($programJson,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,dals=<?= json_encode($dalJson,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,posted=<?= json_encode($formState,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
const f=n=>form.querySelector(`[data-dad-field="${n}"]`),skpd=f('id_skpd'),program=f('id_program'),rad=f('id_rad'),dal=f('id_dal'),penghasil=f('penghasil_data'),dabs=form.querySelector('[data-dad-dab]'),dabSummary=form.querySelector('[data-dad-dab-summary]'),dabSearch=dabModal.querySelector('[data-dad-dab-search]'),dabRows=dabModal.querySelectorAll('[data-dab-row]'),dabChecks=dabModal.querySelectorAll('[data-dab-check]'),dabEmpty=dabModal.querySelector('[data-dad-dab-empty]'),dabCount=dabModal.querySelector('[data-dad-dab-count]'),open=m=>{m.classList.remove('hidden');m.classList.add('flex')},close=m=>{m.classList.add('hidden');m.classList.remove('flex')};
const selectedDabIds=()=>[...dabs.selectedOptions].map(o=>Number(o.value));
const syncDabUi=()=>{const ids=selectedDabIds(),labels=[...dabs.selectedOptions].map(o=>o.textContent.trim());dabChecks.forEach(c=>c.checked=ids.includes(Number(c.value)));dabSummary.replaceChildren(...(labels.length?labels.map(label=>{const row=document.createElement('div');row.textContent=label;return row;}):[document.createTextNode('Belum ada DAB dipilih.')]));dabCount.textContent=`${labels.length} DAB dipilih`};
const filterDabs=()=>{const keyword=dabSearch.value.trim().toLowerCase();let shown=0;dabRows.forEach(row=>{const match=!keyword||row.dataset.search.includes(keyword);row.classList.toggle('hidden',!match);if(match)shown++});dabEmpty.classList.toggle('hidden',shown>0)};
const refresh=s=>{program.innerHTML='';program.add(new Option(skpd.value?'Pilih Program':'Pilih SKPD terlebih dahulu',''));programs.filter(x=>String(x.skpd_id)===skpd.value).forEach(x=>{let o=new Option(x.label,x.id);o.dataset.rad=x.rad_id||'';program.add(o)});program.disabled=!skpd.value;if(s)program.value=s};
const refreshDal=s=>{const programId=Number(program.value||0);dal.innerHTML='';dal.add(new Option(programId?'Tidak ada layanan terkait':'Pilih program terlebih dahulu',''));dals.filter(x=>Number(x.program_id)===programId).forEach(x=>dal.add(new Option(x.label,String(x.id))));dal.disabled=!programId;if(s)dal.value=String(s)};
const normalizeChoice=(n,v)=>['sifat_data','jenis_data','validitas_data'].includes(n)?String(v||'').toLowerCase():v;
const fill=r=>{fields.forEach(n=>{if(f(n))f(n).value=normalizeChoice(n,r[n])||''});refresh(String(r.id_program||''));refreshDal(String(r.id_dal||''));[...dabs.options].forEach(o=>o.selected=(r.id_dab||[]).map(Number).includes(Number(o.value)));syncDabUi()};
skpd.onchange=()=>{refresh('');refreshDal('');penghasil.value=skpd.selectedOptions[0]?.textContent.trim()||''};program.onchange=()=>{let x=program.selectedOptions[0]?.dataset.rad;if(x)rad.value=x;refreshDal('')};
dabChecks.forEach(c=>c.onchange=()=>{const option=[...dabs.options].find(o=>o.value===c.value);if(option)option.selected=c.checked;syncDabUi()});
dabSearch.oninput=filterDabs;
form.querySelector('[data-dad-dab-open]').onclick=()=>{syncDabUi();filterDabs();open(dabModal);dabSearch.focus()};
dabModal.querySelector('[data-dad-dab-apply]').onclick=()=>close(dabModal);
document.querySelector('[data-dad-add]').onclick=()=>{fill({id_dab:[]});form.querySelector('[data-dad-action]').value='create';form.querySelector('[data-dad-title]').textContent='Tambah Data DAD';form.querySelector('[data-dad-submit]').textContent='Simpan Data';open(form)};
document.querySelectorAll('[data-dad-edit]').forEach(b=>b.onclick=()=>{fill(JSON.parse(b.dataset.record));form.querySelector('[data-dad-action]').value='update';form.querySelector('[data-dad-title]').textContent='Edit Data DAD';form.querySelector('[data-dad-submit]').textContent='Simpan Perubahan';open(form)});
document.querySelectorAll('[data-dad-view]').forEach(b=>b.onclick=()=>{let r=JSON.parse(b.dataset.record);view.querySelectorAll('[data-view]').forEach(x=>x.textContent=r[x.dataset.view]||'—');open(view)});
document.querySelectorAll('[data-dad-delete]').forEach(b=>b.onclick=()=>{del.querySelector('[data-delete-id]').value=b.dataset.id;del.querySelector('[data-delete-name]').textContent=b.dataset.name;open(del)});
document.querySelectorAll('[data-close]').forEach(b=>b.onclick=()=>close(b.closest('[data-modal]')));refresh(String(posted.id_program||''));refreshDal(String(posted.id_dal||''));syncDabUi();<?php if($openFormModal):?>fill(posted);form.querySelector('[data-dad-action]').value=<?= json_encode($formMode) ?>;open(form);<?php endif;?>})();
</script>
