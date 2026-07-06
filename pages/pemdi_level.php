<?php

declare(strict_types=1);

function pemdi_level_int(mixed $value): ?int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id ? (int) $id : null;
}

function pemdi_level_ref_label(array $row): string
{
    $indicator = trim((string) ($row['nama_indikator'] ?? ''));
    $aspect = trim((string) ($row['nama_aspek'] ?? ''));
    $skpd = trim((string) ($row['nama_skpd'] ?? ''));
    $context = implode(' · ', array_filter([$aspect, $skpd]));
    return $indicator . ($context !== '' ? ' (' . $context . ')' : '');
}

function pemdi_level_page_url(int $number, string $search, int $perPage, ?int $indicatorId): string
{
    return 'index.php?' . http_build_query(array_filter([
        'page' => 'pemdi-level',
        'q' => $search,
        'id_indikator' => $indicatorId,
        'per_page' => $perPage,
        'p' => $number,
    ], static fn(mixed $value): bool => $value !== null && $value !== ''));
}

$indicatorList = db()->query(
    'SELECT i.id,i.nama_indikator,i.bobot,a.nama_aspek,s.kode_skpd,s.nama_skpd
     FROM indikator i
     INNER JOIN aspek a ON a.id=i.id_aspek
     LEFT JOIN skpd s ON s.id=i.id_skpd
     ORDER BY i.id ASC'
)->fetchAll();
$indicatorMap = [];
foreach ($indicatorList as $indicator) $indicatorMap[(int) $indicator['id']] = $indicator;
$formErrors = [];
$openFormModal = false;
$formMode = 'create';
$formState = ['id' => '', 'id_indikator' => '', 'level' => '', 'deskripsi' => '', 'kriteria' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if (in_array($action, ['create', 'update'], true)) {
        $formMode = $action;
        $openFormModal = true;
        $recordId = pemdi_level_int($_POST['id'] ?? null);
        $formState = [
            'id' => $recordId ?: '',
            'id_indikator' => trim((string) ($_POST['id_indikator'] ?? '')),
            'level' => trim((string) ($_POST['level'] ?? '')),
            'deskripsi' => trim((string) ($_POST['deskripsi'] ?? '')),
            'kriteria' => trim((string) ($_POST['kriteria'] ?? '')),
        ];
        $indicatorId = pemdi_level_int($formState['id_indikator']);
        $level = pemdi_level_int($formState['level']);

        if (!$indicatorId || !isset($indicatorMap[$indicatorId])) $formErrors[] = 'Indikator wajib dipilih.';
        if (!$level) $formErrors[] = 'Level harus berupa bilangan bulat mulai dari 1.';
        if ($action === 'update' && !$recordId) $formErrors[] = 'ID level tidak valid.';

        $oldValues = null;
        if (!$formErrors && $action === 'update') {
            $stmt = db()->prepare('SELECT * FROM pemdi_level WHERE id=:id');
            $stmt->execute(['id' => $recordId]);
            $oldValues = $stmt->fetch();
            if (!$oldValues) $formErrors[] = 'Level yang akan diubah tidak ditemukan.';
        }

        if (!$formErrors) {
            $params = [
                'id_indikator' => $indicatorId,
                'level' => $level,
                'deskripsi' => $formState['deskripsi'] ?: null,
                'kriteria' => $formState['kriteria'] ?: null,
                'updated_by' => (int) $user['id'],
            ];
            db()->beginTransaction();
            try {
                if ($action === 'create') {
                    $params['created_by'] = (int) $user['id'];
                    $columns = array_keys($params);
                    db()->prepare(
                        'INSERT INTO pemdi_level (' . implode(',', $columns) . ') VALUES (:' . implode(',:', $columns) . ')'
                    )->execute($params);
                    $recordId = (int) db()->lastInsertId();
                } else {
                    $params['id'] = $recordId;
                    $sets = [];
                    foreach (array_keys($params) as $field) if ($field !== 'id') $sets[] = "{$field}=:{$field}";
                    db()->prepare('UPDATE pemdi_level SET ' . implode(',', $sets) . ' WHERE id=:id')->execute($params);
                }
                $stmt = db()->prepare('SELECT * FROM pemdi_level WHERE id=:id');
                $stmt->execute(['id' => $recordId]);
                $newValues = $stmt->fetch() ?: $params;
                audit_log(
                    (int) $user['id'],
                    $action,
                    'pemdi_level',
                    $recordId,
                    ($action === 'create' ? 'Menambahkan ' : 'Mengubah ')
                        . 'PEMDI Level: ' . $indicatorMap[$indicatorId]['nama_indikator'] . ' - Level ' . $level,
                    $oldValues,
                    $newValues,
                    true
                );
                db()->commit();
                set_flash('success', $action === 'create' ? 'Level berhasil ditambahkan.' : 'Level berhasil diperbarui.');
                redirect('index.php?page=pemdi-level');
            } catch (PDOException $exception) {
                db()->rollBack();
                if ((string) $exception->getCode() === '23000') {
                    $formErrors[] = 'Level tersebut sudah tersedia pada indikator yang dipilih.';
                } else {
                    error_log('Penyimpanan PEMDI level gagal: ' . $exception->getMessage());
                    $formErrors[] = 'Level gagal disimpan. Silakan coba kembali.';
                }
            } catch (Throwable $exception) {
                db()->rollBack();
                error_log('Penyimpanan PEMDI level gagal: ' . $exception->getMessage());
                $formErrors[] = 'Level gagal disimpan. Silakan coba kembali.';
            }
        }
    } elseif ($action === 'delete') {
        $recordId = pemdi_level_int($_POST['id'] ?? null);
        $stmt = db()->prepare(
            'SELECT l.*,i.nama_indikator,
                (SELECT COUNT(*) FROM pemdi_evidence e WHERE e.id_pemdi_level=l.id) evidence_count
             FROM pemdi_level l
             INNER JOIN indikator i ON i.id=l.id_indikator
             WHERE l.id=:id'
        );
        $stmt->execute(['id' => $recordId]);
        $record = $stmt->fetch();
        if (!$recordId || !$record) {
            set_flash('error', 'Level tidak ditemukan.');
            redirect('index.php?page=pemdi-level');
        }
        if ((int) $record['evidence_count'] > 0) {
            set_flash('error', 'Level masih memiliki evidence dan tidak dapat dihapus.');
            redirect('index.php?page=pemdi-level');
        }

        db()->beginTransaction();
        try {
            db()->prepare('DELETE FROM pemdi_level WHERE id=:id')->execute(['id' => $recordId]);
            audit_log(
                (int) $user['id'],
                'delete',
                'pemdi_level',
                $recordId,
                'Menghapus PEMDI Level: ' . $record['nama_indikator'] . ' - Level ' . $record['level'],
                $record,
                null,
                true
            );
            db()->commit();
            set_flash('success', 'Level berhasil dihapus.');
        } catch (Throwable $exception) {
            db()->rollBack();
            error_log('Penghapusan PEMDI level gagal: ' . $exception->getMessage());
            set_flash('error', 'Level gagal dihapus.');
        }
        redirect('index.php?page=pemdi-level');
    }
}

$search = trim((string) ($_GET['q'] ?? ''));
$filterIndicator = pemdi_level_int($_GET['id_indikator'] ?? null);
if ($filterIndicator && !isset($indicatorMap[$filterIndicator])) $filterIndicator = null;
$perPageOptions = [10, 20, 25, 50];
$perPage = (int) ($_GET['per_page'] ?? 20);
if (!in_array($perPage, $perPageOptions, true)) $perPage = 20;
$currentPage = max(1, (int) ($_GET['p'] ?? 1));
$conditions = [];
$queryParams = [];
if ($search !== '') {
    $conditions[] = "CONCAT_WS(' ',i.nama_indikator,a.nama_aspek,s.nama_skpd,l.level,l.deskripsi,l.kriteria) LIKE :search";
    $queryParams['search'] = '%' . $search . '%';
}
if ($filterIndicator) {
    $conditions[] = 'l.id_indikator=:filter_indikator';
    $queryParams['filter_indikator'] = $filterIndicator;
}
$whereSql = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
$joins = ' FROM pemdi_level l
    INNER JOIN indikator i ON i.id=l.id_indikator
    INNER JOIN aspek a ON a.id=i.id_aspek
    LEFT JOIN skpd s ON s.id=i.id_skpd';
$stmt = db()->prepare('SELECT COUNT(*)' . $joins . $whereSql);
$stmt->execute($queryParams);
$totalRows = (int) $stmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $perPage;
$stmt = db()->prepare(
    'SELECT l.*,i.nama_indikator,i.bobot,a.nama_aspek,s.kode_skpd,s.nama_skpd,
        (SELECT COUNT(*) FROM pemdi_evidence e WHERE e.id_pemdi_level=l.id) evidence_count'
    . $joins . $whereSql . ' ORDER BY i.id ASC,l.level ASC,l.id ASC LIMIT :limit OFFSET :offset'
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
    <div class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div><p class="text-xs font-semibold uppercase tracking-wider text-red-700">Peta Rencana</p><h1 class="mt-1 text-xl font-bold">PEMDI Level</h1><p class="mt-1 text-sm text-slate-500">Kelola tingkat kematangan, deskripsi, dan kriteria untuk setiap indikator.</p></div>
        <button type="button" class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800 disabled:cursor-not-allowed disabled:bg-slate-300" data-level-add <?= !$indicatorList ? 'disabled title="Belum ada indikator"' : '' ?>><i data-lucide="plus" class="h-4 w-4"></i>Tambah Level</button>
    </div>

    <?php if ($formErrors): ?><div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"><p class="font-semibold">Level belum bisa disimpan.</p><ul class="mt-1 list-disc pl-5"><?php foreach ($formErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

    <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b p-4 lg:flex-row lg:items-center lg:justify-between">
            <div><h2 class="font-semibold">Daftar PEMDI Level</h2><p class="mt-1 text-sm text-slate-500"><?= number_format($totalRows, 0, ',', '.') ?> level.</p></div>
            <form method="get" class="flex flex-col gap-2 sm:flex-row">
                <input type="hidden" name="page" value="pemdi-level">
                <div class="relative"><i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i><input type="search" name="q" value="<?= e($search) ?>" placeholder="Cari indikator, level, kriteria..." class="level-control pl-9 sm:w-64"></div>
                <select name="id_indikator" class="level-control sm:w-56"><option value="">Semua Indikator</option><?php foreach ($indicatorList as $indicator): ?><option value="<?= (int) $indicator['id'] ?>" <?= $filterIndicator === (int) $indicator['id'] ? 'selected' : '' ?>><?= e($indicator['nama_indikator']) ?></option><?php endforeach; ?></select>
                <select name="per_page" class="level-control sm:w-28"><?php foreach ($perPageOptions as $option): ?><option value="<?= $option ?>" <?= $perPage === $option ? 'selected' : '' ?>><?= $option ?> data</option><?php endforeach; ?></select>
                <button class="rounded-md border border-slate-300 px-3 py-2 text-xs font-semibold hover:bg-slate-50">Tampilkan</button>
                <a href="index.php?page=pemdi-level" class="inline-flex h-9 w-9 items-center justify-center rounded-md text-red-700 hover:bg-red-50" title="Reset"><i data-lucide="rotate-ccw" class="h-4 w-4"></i></a>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500"><tr><th class="px-3 py-3">No</th><th class="px-3 py-3">Indikator</th><th class="px-3 py-3">Level</th><th class="px-3 py-3">Deskripsi</th><th class="px-3 py-3">Kriteria</th><th class="px-3 py-3">Evidence</th><th class="px-3 py-3 text-right">Aksi</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!$rows): ?><tr><td colspan="7" class="p-10 text-center text-sm text-slate-500">Belum ada level PEMDI.</td></tr><?php endif; ?>
                    <?php foreach ($rows as $index => $row):
                        $record = [
                            'id' => (int) $row['id'],
                            'id_indikator' => (int) $row['id_indikator'],
                            'level' => (int) $row['level'],
                            'deskripsi' => (string) ($row['deskripsi'] ?? ''),
                            'kriteria' => (string) ($row['kriteria'] ?? ''),
                            'indikator_label' => (string) $row['nama_indikator'],
                            'aspek_label' => (string) $row['nama_aspek'],
                            'skpd_label' => trim((string) ($row['kode_skpd'] ?? '') . ((!empty($row['kode_skpd']) && !empty($row['nama_skpd'])) ? ' - ' : '') . (string) ($row['nama_skpd'] ?? '')),
                            'bobot_label' => number_format((float) $row['bobot'], 2, ',', '.') . '%',
                            'evidence_count' => (int) $row['evidence_count'],
                        ];
                    ?>
                        <tr class="align-top hover:bg-slate-50/70">
                            <td class="px-3 py-3 text-slate-500"><?= $offset + $index + 1 ?></td>
                            <td class="max-w-[300px] whitespace-normal px-3 py-3"><p class="font-semibold"><?= e($row['nama_indikator']) ?></p><p class="mt-1 text-[11px] text-slate-500"><?= e($row['nama_aspek']) ?> · Bobot <?= e(number_format((float) $row['bobot'], 2, ',', '.')) ?>%</p></td>
                            <td class="px-3 py-3 text-center"><span class="inline-flex h-8 min-w-8 items-center justify-center rounded-md bg-red-50 px-2 font-bold text-red-700"><?= (int) $row['level'] ?></span></td>
                            <td class="max-w-[260px] whitespace-pre-line px-3 py-3 text-slate-600"><?= e($row['deskripsi'] ?: '-') ?></td>
                            <td class="max-w-[300px] whitespace-pre-line px-3 py-3 text-slate-600"><?= e($row['kriteria'] ?: '-') ?></td>
                            <td class="px-3 py-3 text-center font-semibold"><?= (int) $row['evidence_count'] ?></td>
                            <td class="px-3 py-3"><div class="flex justify-end gap-1.5"><button type="button" class="level-action" title="Lihat" data-level-view data-record="<?= e(json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"><i data-lucide="eye"></i></button><button type="button" class="level-action border-blue-200 bg-blue-50 text-blue-700" title="Edit" data-level-edit data-record="<?= e(json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"><i data-lucide="pencil"></i></button><button type="button" class="level-action border-red-200 bg-red-50 text-red-700 disabled:opacity-40" title="<?= (int) $row['evidence_count'] ? 'Masih memiliki evidence' : 'Hapus' ?>" data-level-delete data-id="<?= (int) $row['id'] ?>" data-name="<?= e($row['nama_indikator'] . ' - Level ' . $row['level']) ?>" <?= (int) $row['evidence_count'] ? 'disabled' : '' ?>><i data-lucide="trash-2"></i></button></div></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-3 border-t p-4 text-sm text-slate-500"><p>Menampilkan <?= $startRow ?>-<?= $endRow ?> dari <?= $totalRows ?> data</p><?php render_numbered_pagination($currentPage, $totalPages, static fn(int $number): string => pemdi_level_page_url($number, $search, $perPage, $filterIndicator)); ?></div>
    </div>
</section>

<style>
.level-control{height:2.25rem;border:1px solid #cbd5e1;border-radius:.375rem;background:#fff;padding:.45rem .7rem;font-size:.75rem;outline:none}.level-control:focus,.level-form-control:focus{border-color:#dc2626;box-shadow:0 0 0 3px rgb(254 226 226/.7)}.level-label{display:block;margin-bottom:.3rem;font-size:.68rem;font-weight:700;text-transform:uppercase;color:#64748b}.level-form-control{width:100%;border:1px solid #cbd5e1;border-radius:.5rem;background:#fff;padding:.625rem .75rem;font-size:.875rem;outline:none}.level-action{display:inline-flex;width:2rem;height:2rem;align-items:center;justify-content:center;border-width:1px;border-radius:.375rem}.level-action svg{width:.875rem;height:.875rem}
</style>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 p-4" data-modal="level-form">
    <form method="post" class="flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-lg bg-white shadow-2xl">
        <?= csrf_field() ?><input type="hidden" name="action" value="<?= e($formMode) ?>" data-level-action><input type="hidden" name="id" value="<?= e($formState['id']) ?>" data-level-field="id">
        <header class="flex items-center justify-between border-b px-5 py-4"><div class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-700 text-white"><i data-lucide="list-ordered" class="h-4 w-4"></i></span><h2 class="font-bold" data-level-title>Tambah PEMDI Level</h2></div><button type="button" data-modal-close><i data-lucide="x"></i></button></header>
        <div class="grid flex-1 gap-4 overflow-y-auto bg-slate-50 p-5 md:grid-cols-2">
            <label class="md:col-span-2"><span class="level-label">Indikator</span><select name="id_indikator" class="level-form-control" data-level-field="id_indikator" required><option value="">Pilih Indikator</option><?php foreach ($indicatorList as $indicator): ?><option value="<?= (int) $indicator['id'] ?>"><?= e(pemdi_level_ref_label($indicator)) ?></option><?php endforeach; ?></select></label>
            <label><span class="level-label">Level</span><input name="level" type="number" min="1" step="1" class="level-form-control" data-level-field="level" required></label>
            <label class="md:col-span-2"><span class="level-label">Deskripsi</span><textarea name="deskripsi" rows="4" class="level-form-control" data-level-field="deskripsi"></textarea></label>
            <label class="md:col-span-2"><span class="level-label">Kriteria</span><textarea name="kriteria" rows="5" class="level-form-control" data-level-field="kriteria"></textarea></label>
        </div>
        <footer class="flex justify-end gap-2 border-t px-5 py-3"><button type="button" class="rounded-lg border px-4 py-2 text-sm font-semibold" data-modal-close>Batal</button><button class="rounded-lg bg-red-700 px-5 py-2 text-sm font-semibold text-white">Simpan Level</button></footer>
    </form>
</div>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 p-4" data-modal="level-view">
    <div class="flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-lg bg-white"><header class="flex items-center justify-between border-b p-4"><h2 class="font-bold">Detail PEMDI Level</h2><button type="button" data-modal-close><i data-lucide="x"></i></button></header><div class="grid flex-1 gap-3 overflow-y-auto bg-slate-50 p-5 md:grid-cols-2"><?php foreach (['indikator_label'=>'Indikator','aspek_label'=>'Aspek','skpd_label'=>'SKPD','bobot_label'=>'Bobot Indikator','level'=>'Level','evidence_count'=>'Jumlah Evidence','deskripsi'=>'Deskripsi','kriteria'=>'Kriteria'] as $field=>$label): ?><div class="rounded-lg border bg-white p-4 <?= in_array($field, ['indikator_label','deskripsi','kriteria'], true) ? 'md:col-span-2' : '' ?>"><b class="text-xs uppercase text-slate-500"><?= e($label) ?></b><p class="mt-1 whitespace-pre-line" data-view-field="<?= e($field) ?>"></p></div><?php endforeach; ?></div></div>
</div>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 p-4" data-modal="level-delete">
    <form method="post" class="w-full max-w-md rounded-lg bg-white p-6"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" data-delete-id><h2 class="text-xl font-bold">Hapus PEMDI Level?</h2><p class="mt-2 text-sm text-slate-500">Level hanya dapat dihapus jika belum memiliki evidence.</p><p class="mt-4 rounded-lg bg-slate-50 p-4 font-semibold" data-delete-name></p><div class="mt-5 flex justify-end gap-2"><button type="button" class="rounded-lg border px-4 py-2" data-modal-close>Batal</button><button class="rounded-lg bg-red-700 px-4 py-2 font-semibold text-white">Ya, Hapus</button></div></form>
</div>

<script>
(() => {
    const modals=document.querySelectorAll('[data-modal]'),form=document.querySelector('[data-modal="level-form"]'),view=document.querySelector('[data-modal="level-view"]'),del=document.querySelector('[data-modal="level-delete"]');
    const fields=['id','id_indikator','level','deskripsi','kriteria'],posted=<?= json_encode($formState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const input=name=>form.querySelector(`[data-level-field="${name}"]`),open=modal=>{modal.classList.remove('hidden');modal.classList.add('flex');document.body.classList.add('overflow-hidden')},close=modal=>{modal.classList.add('hidden');modal.classList.remove('flex');if(![...modals].some(item=>!item.classList.contains('hidden')))document.body.classList.remove('overflow-hidden')};
    const fill=record=>fields.forEach(name=>{if(input(name))input(name).value=record[name]||''});
    document.querySelector('[data-level-add]')?.addEventListener('click',()=>{fill({});form.querySelector('[data-level-action]').value='create';form.querySelector('[data-level-title]').textContent='Tambah PEMDI Level';open(form)});
    document.querySelectorAll('[data-level-edit]').forEach(button=>button.addEventListener('click',()=>{fill(JSON.parse(button.dataset.record||'{}'));form.querySelector('[data-level-action]').value='update';form.querySelector('[data-level-title]').textContent='Edit PEMDI Level';open(form)}));
    document.querySelectorAll('[data-level-view]').forEach(button=>button.addEventListener('click',()=>{const record=JSON.parse(button.dataset.record||'{}');view.querySelectorAll('[data-view-field]').forEach(element=>element.textContent=record[element.dataset.viewField]||'-');open(view)}));
    document.querySelectorAll('[data-level-delete]').forEach(button=>button.addEventListener('click',()=>{del.querySelector('[data-delete-id]').value=button.dataset.id;del.querySelector('[data-delete-name]').textContent=button.dataset.name;open(del)}));
    document.querySelectorAll('[data-modal-close]').forEach(button=>button.addEventListener('click',()=>close(button.closest('[data-modal]'))));modals.forEach(modal=>modal.addEventListener('click',event=>{if(event.target===modal)close(modal)}));document.addEventListener('keydown',event=>{if(event.key==='Escape')modals.forEach(close)});
    <?php if ($openFormModal): ?>fill(posted);form.querySelector('[data-level-action]').value=<?= json_encode($formMode) ?>;form.querySelector('[data-level-title]').textContent=<?= json_encode(($formMode === 'update' ? 'Edit ' : 'Tambah ') . 'PEMDI Level') ?>;open(form);<?php endif; ?>
})();
</script>
