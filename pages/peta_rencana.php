<?php

declare(strict_types=1);

function peta_int(mixed $value): ?int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id ? (int) $id : null;
}

function peta_valid_date(string $value): bool
{
    if ($value === '') return true;
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}

function peta_label(mixed $code, mixed $name): string
{
    $code = trim((string) $code);
    $name = trim((string) $name);
    return trim($code . ($code !== '' && $name !== '' ? ' - ' : '') . $name);
}

function peta_page_url(
    int $pageNumber,
    string $search,
    int $perPage,
    ?int $skpdId,
    ?int $indicatorId,
    ?int $year
): string {
    return 'index.php?' . http_build_query(array_filter([
        'page' => 'peta-rencana',
        'q' => $search,
        'id_skpd' => $skpdId,
        'id_indikator' => $indicatorId,
        'tahun' => $year,
        'per_page' => $perPage,
        'p' => $pageNumber,
    ], static fn(mixed $value): bool => $value !== null && $value !== ''));
}

$years = [2026, 2027, 2028, 2029, 2030];
$targetFields = array_map(static fn(int $year): string => 'target_' . $year, $years);
$fields = array_merge(
    ['id_aspek', 'no_urut', 'nama_rencana', 'id_indikator'],
    $targetFields,
    ['tanggal_awal', 'tanggal_akhir', 'id_skpd', 'id_program', 'keterangan']
);
$aspekList = db()->query('SELECT id,nama_aspek FROM aspek ORDER BY nama_aspek')->fetchAll();
$indicatorList = db()->query(
    'SELECT i.id,i.nama_indikator,i.id_aspek,i.deskripsi_indikator,i.koordinator,i.bobot,
        a.nama_aspek,s.kode_skpd,s.nama_skpd
     FROM indikator i
     INNER JOIN aspek a ON a.id=i.id_aspek
     LEFT JOIN skpd s ON s.id=i.id_skpd
     ORDER BY i.id'
)->fetchAll();
$skpdList = db()->query('SELECT id,kode_skpd,nama_skpd FROM skpd ORDER BY kode_skpd,nama_skpd')->fetchAll();
$programList = db()->query('SELECT id,kode_program,nama_program,id_skpd FROM program ORDER BY kode_program,nama_program')->fetchAll();
$aspekMap = [];
foreach ($aspekList as $item) $aspekMap[(int) $item['id']] = $item;
$indicatorMap = [];
foreach ($indicatorList as $item) $indicatorMap[(int) $item['id']] = $item;
$skpdMap = [];
foreach ($skpdList as $item) $skpdMap[(int) $item['id']] = $item;
$programMap = [];
foreach ($programList as $item) $programMap[(int) $item['id']] = $item;
$contextIndicatorId = peta_int($_GET['id_indikator'] ?? null);
if ($contextIndicatorId && !isset($indicatorMap[$contextIndicatorId])) $contextIndicatorId = null;
$contextIndicator = $contextIndicatorId ? $indicatorMap[$contextIndicatorId] : null;
$returnUrl = 'index.php?' . http_build_query(array_filter([
    'page' => 'peta-rencana',
    'id_indikator' => $contextIndicatorId,
], static fn(mixed $value): bool => $value !== null));

$formErrors = [];
$openFormModal = false;
$formMode = 'create';
$formState = array_fill_keys($fields, '');
$formState['id'] = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if (in_array($action, ['create', 'update'], true)) {
        $formMode = $action;
        $openFormModal = true;
        $recordId = peta_int($_POST['id'] ?? null);
        $formState['id'] = $recordId ?: '';
        foreach ($fields as $field) $formState[$field] = trim((string) ($_POST[$field] ?? ''));
        if ($contextIndicator) {
            $formState['id_aspek'] = (string) $contextIndicator['id_aspek'];
            $formState['id_indikator'] = (string) $contextIndicator['id'];
        }

        $idAspek = peta_int($formState['id_aspek']);
        $idIndicator = peta_int($formState['id_indikator']);
        $idSkpd = peta_int($formState['id_skpd']);
        $idProgram = peta_int($formState['id_program']);
        $sequence = peta_int($formState['no_urut']);

        if (!$idAspek || !isset($aspekMap[$idAspek])) $formErrors[] = 'Aspek wajib dipilih.';
        if (!$idIndicator || !isset($indicatorMap[$idIndicator])) {
            $formErrors[] = 'Indikator wajib dipilih.';
        } elseif ($idAspek && (int) $indicatorMap[$idIndicator]['id_aspek'] !== $idAspek) {
            $formErrors[] = 'Indikator tidak sesuai dengan aspek.';
        }
        if (!$sequence) $formErrors[] = 'Nomor urut harus berupa bilangan bulat mulai dari 1.';
        if ($formState['nama_rencana'] === '') {
            $formErrors[] = 'Nama rencana wajib diisi.';
        } elseif (mb_strlen($formState['nama_rencana']) > 500) {
            $formErrors[] = 'Nama rencana terlalu panjang.';
        }
        if (!$idSkpd || !isset($skpdMap[$idSkpd])) $formErrors[] = 'SKPD wajib dipilih.';
        if (!$idProgram || !isset($programMap[$idProgram])) {
            $formErrors[] = 'Program wajib dipilih.';
        } elseif ($idSkpd && (int) $programMap[$idProgram]['id_skpd'] !== $idSkpd) {
            $formErrors[] = 'Program tidak sesuai dengan SKPD.';
        }
        foreach (['tanggal_awal' => 'Tanggal awal', 'tanggal_akhir' => 'Tanggal akhir'] as $field => $label) {
            if (!peta_valid_date($formState[$field])) $formErrors[] = $label . ' tidak valid.';
        }
        if ($formState['tanggal_awal'] !== '' && $formState['tanggal_akhir'] !== ''
            && $formState['tanggal_akhir'] < $formState['tanggal_awal']) {
            $formErrors[] = 'Tanggal akhir tidak boleh lebih awal dari tanggal awal.';
        }
        if ($action === 'update' && !$recordId) $formErrors[] = 'ID data tidak valid.';

        $oldValues = null;
        if (!$formErrors && $action === 'update') {
            $stmt = db()->prepare('SELECT * FROM peta_rencana WHERE id=:id');
            $stmt->execute(['id' => $recordId]);
            $oldValues = $stmt->fetch();
            if (!$oldValues) {
                $formErrors[] = 'Peta rencana yang akan diubah tidak ditemukan.';
            } elseif ($contextIndicatorId && (int) $oldValues['id_indikator'] !== $contextIndicatorId) {
                $formErrors[] = 'Peta rencana tidak sesuai dengan indikator terpilih.';
            }
        }

        if (!$formErrors) {
            $params = [
                'id_aspek' => $idAspek,
                'no_urut' => $sequence,
                'nama_rencana' => $formState['nama_rencana'],
                'id_indikator' => $idIndicator,
                'tanggal_awal' => $formState['tanggal_awal'] ?: null,
                'tanggal_akhir' => $formState['tanggal_akhir'] ?: null,
                'id_skpd' => $idSkpd,
                'id_program' => $idProgram,
                'keterangan' => $formState['keterangan'] ?: null,
                'updated_by' => (int) $user['id'],
            ];
            foreach ($targetFields as $field) $params[$field] = $formState[$field] ?: null;

            db()->beginTransaction();
            try {
                if ($action === 'create') {
                    $params['created_by'] = (int) $user['id'];
                    $columns = array_keys($params);
                    db()->prepare(
                        'INSERT INTO peta_rencana (' . implode(',', $columns) . ') VALUES (:' . implode(',:', $columns) . ')'
                    )->execute($params);
                    $recordId = (int) db()->lastInsertId();
                } else {
                    $params['id'] = $recordId;
                    $sets = [];
                    foreach (array_keys($params) as $field) if ($field !== 'id') $sets[] = "{$field}=:{$field}";
                    db()->prepare('UPDATE peta_rencana SET ' . implode(',', $sets) . ' WHERE id=:id')->execute($params);
                }

                $stmt = db()->prepare('SELECT * FROM peta_rencana WHERE id=:id');
                $stmt->execute(['id' => $recordId]);
                $newValues = $stmt->fetch() ?: $params;
                audit_log(
                    (int) $user['id'],
                    $action,
                    'peta_rencana',
                    $recordId,
                    ($action === 'create' ? 'Menambahkan ' : 'Mengubah ') . 'Peta Rencana: ' . $formState['nama_rencana'],
                    $oldValues,
                    $newValues,
                    true
                );
                db()->commit();
                set_flash('success', $action === 'create' ? 'Peta rencana berhasil ditambahkan.' : 'Peta rencana berhasil diperbarui.');
                redirect($returnUrl);
            } catch (Throwable $exception) {
                db()->rollBack();
                error_log('Penyimpanan peta rencana gagal: ' . $exception->getMessage());
                $formErrors[] = 'Peta rencana gagal disimpan. Silakan coba kembali.';
            }
        }
    } elseif ($action === 'delete') {
        $recordId = peta_int($_POST['id'] ?? null);
        $stmt = db()->prepare('SELECT * FROM peta_rencana WHERE id=:id');
        $stmt->execute(['id' => $recordId]);
        $record = $stmt->fetch();
        if (!$recordId || !$record) {
            set_flash('error', 'Peta rencana tidak ditemukan.');
            redirect('index.php?page=peta-rencana');
        }

        db()->beginTransaction();
        try {
            db()->prepare('DELETE FROM peta_rencana WHERE id=:id')->execute(['id' => $recordId]);
            audit_log(
                (int) $user['id'],
                'delete',
                'peta_rencana',
                $recordId,
                'Menghapus Peta Rencana: ' . $record['nama_rencana'],
                $record,
                null,
                true
            );
            db()->commit();
            set_flash('success', 'Peta rencana berhasil dihapus.');
        } catch (Throwable $exception) {
            db()->rollBack();
            error_log('Penghapusan peta rencana gagal: ' . $exception->getMessage());
            set_flash('error', 'Peta rencana gagal dihapus.');
        }
        redirect($returnUrl);
    }
}

$search = trim((string) ($_GET['q'] ?? ''));
$filterSkpd = peta_int($_GET['id_skpd'] ?? null);
if ($filterSkpd && !isset($skpdMap[$filterSkpd])) $filterSkpd = null;
$filterIndicator = $contextIndicatorId;
$selectedIndicator = $filterIndicator ? $indicatorMap[$filterIndicator] : null;
$filterYear = (int) ($_GET['tahun'] ?? 0);
if (!in_array($filterYear, $years, true)) $filterYear = null;
$perPageOptions = [10, 25, 50];
$perPage = (int) ($_GET['per_page'] ?? 10);
if (!in_array($perPage, $perPageOptions, true)) $perPage = 10;
$currentPage = max(1, (int) ($_GET['p'] ?? 1));

$conditions = [];
$queryParams = [];
if ($search !== '') {
    $conditions[] = "CONCAT_WS(' ',pr.nama_rencana,pr.keterangan,a.nama_aspek,i.nama_indikator,s.kode_skpd,s.nama_skpd,p.kode_program,p.nama_program) LIKE :search";
    $queryParams['search'] = '%' . $search . '%';
}
if ($filterSkpd) {
    $conditions[] = 'pr.id_skpd=:filter_skpd';
    $queryParams['filter_skpd'] = $filterSkpd;
}
if ($filterIndicator) {
    $conditions[] = 'pr.id_indikator=:filter_indikator';
    $queryParams['filter_indikator'] = $filterIndicator;
}
if ($filterYear) $conditions[] = "NULLIF(TRIM(pr.target_{$filterYear}),'') IS NOT NULL";
$whereSql = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
$joins = ' FROM peta_rencana pr
    INNER JOIN aspek a ON a.id=pr.id_aspek
    INNER JOIN indikator i ON i.id=pr.id_indikator
    INNER JOIN skpd s ON s.id=pr.id_skpd
    INNER JOIN program p ON p.id=pr.id_program';
$stmt = db()->prepare('SELECT COUNT(*)' . $joins . $whereSql);
$stmt->execute($queryParams);
$totalRows = (int) $stmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $perPage;
$stmt = db()->prepare(
    'SELECT pr.*,a.nama_aspek,i.nama_indikator,s.kode_skpd,s.nama_skpd,p.kode_program,p.nama_program'
    . $joins . $whereSql
    . ' ORDER BY pr.no_urut ASC,pr.id ASC LIMIT :limit OFFSET :offset'
);
foreach ($queryParams as $key => $value) $stmt->bindValue(':' . $key, $value);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();
$startRow = $totalRows ? $offset + 1 : 0;
$endRow = min($offset + $perPage, $totalRows);
$printUrl = 'cetak_peta_rencana.php?' . http_build_query(array_filter([
    'q' => $search,
    'id_skpd' => $filterSkpd,
    'id_indikator' => $filterIndicator,
    'tahun' => $filterYear,
], static fn(mixed $value): bool => $value !== null && $value !== ''));
$indicatorJson = array_map(static fn(array $item): array => [
    'id' => (int) $item['id'],
    'aspek_id' => (int) $item['id_aspek'],
    'label' => (string) $item['nama_indikator'],
], $indicatorList);
$programJson = array_map(static fn(array $item): array => [
    'id' => (int) $item['id'],
    'skpd_id' => (int) $item['id_skpd'],
    'label' => peta_label($item['kode_program'], $item['nama_program']),
], $programList);
?>

<section class="space-y-5">
    <div class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-red-700">Perencanaan SPBE</p>
            <h1 class="mt-1 text-xl font-bold">Peta Rencana</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola rencana, indikator, target tahunan, periode, dan unit pelaksana.</p>
        </div>
        <div class="flex gap-2">
            <a href="<?= e($printUrl) ?>" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i data-lucide="printer" class="h-4 w-4"></i>Cetak</a>
            <button type="button" class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800" data-peta-add><i data-lucide="plus" class="h-4 w-4"></i>Tambah Data</button>
        </div>
    </div>

    <?php if ($formErrors): ?>
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"><p class="font-semibold">Data belum bisa disimpan.</p><ul class="mt-1 list-disc pl-5"><?php foreach ($formErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <?php if ($selectedIndicator): ?>
        <div class="rounded-lg border border-red-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase text-red-700">Indikator Terpilih</p>
                    <h2 class="mt-1 text-lg font-bold"><span class="text-blue-600">#<?= e($selectedIndicator['id']) ?></span> <?= e($selectedIndicator['nama_indikator']) ?></h2>
                    <p class="mt-1 text-sm text-slate-500"><?= e($selectedIndicator['nama_aspek']) ?></p>
                    <?php if (!empty($selectedIndicator['deskripsi_indikator'])): ?><p class="mt-3 max-w-3xl text-sm leading-relaxed text-slate-600"><?= e($selectedIndicator['deskripsi_indikator']) ?></p><?php endif; ?>
                </div>
                <div class="grid shrink-0 grid-cols-2 gap-3 text-sm">
                    <div class="rounded-md bg-slate-50 px-4 py-3"><span class="block text-[10px] font-semibold uppercase text-slate-500">Bobot</span><strong class="mt-1 block text-red-700"><?= e(number_format((float) $selectedIndicator['bobot'], 2, ',', '.')) ?>%</strong></div>
                    <div class="rounded-md bg-slate-50 px-4 py-3"><span class="block text-[10px] font-semibold uppercase text-slate-500">Koordinator</span><strong class="mt-1 block"><?= e($selectedIndicator['koordinator'] ?: '-') ?></strong></div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-4">
            <div class="mb-3"><h2 class="font-semibold">Daftar Peta Rencana</h2><p class="mt-1 text-sm text-slate-500"><?= number_format($totalRows, 0, ',', '.') ?> data sesuai filter.</p></div>
            <form method="get" class="grid gap-2 md:grid-cols-2 xl:grid-cols-[minmax(220px,1fr)_220px_220px_130px_110px_auto]">
                <input type="hidden" name="page" value="peta-rencana">
                <div class="relative"><i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i><input type="search" name="q" value="<?= e($search) ?>" placeholder="Cari rencana, aspek, SKPD..." class="peta-control pl-9"></div>
                <select name="id_skpd" class="peta-control"><option value="">Semua SKPD</option><?php foreach ($skpdList as $item): ?><option value="<?= (int) $item['id'] ?>" <?= $filterSkpd === (int) $item['id'] ? 'selected' : '' ?>><?= e(peta_label($item['kode_skpd'], $item['nama_skpd'])) ?></option><?php endforeach; ?></select>
                <select name="id_indikator" class="peta-control"><option value="">Semua Indikator</option><?php foreach ($indicatorList as $item): ?><option value="<?= (int) $item['id'] ?>" <?= $filterIndicator === (int) $item['id'] ? 'selected' : '' ?>><?= e($item['nama_indikator']) ?></option><?php endforeach; ?></select>
                <select name="tahun" class="peta-control"><option value="">Semua Tahun</option><?php foreach ($years as $year): ?><option value="<?= $year ?>" <?= $filterYear === $year ? 'selected' : '' ?>><?= $year ?></option><?php endforeach; ?></select>
                <select name="per_page" class="peta-control"><?php foreach ($perPageOptions as $option): ?><option value="<?= $option ?>" <?= $perPage === $option ? 'selected' : '' ?>><?= $option ?> data</option><?php endforeach; ?></select>
                <div class="flex gap-2"><button class="inline-flex flex-1 items-center justify-center rounded-md border border-slate-300 px-3 py-2 text-xs font-semibold hover:bg-slate-50">Tampilkan</button><a href="index.php?page=peta-rencana" class="inline-flex h-9 w-9 items-center justify-center rounded-md text-red-700 hover:bg-red-50" title="Reset filter"><i data-lucide="rotate-ccw" class="h-4 w-4"></i></a></div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500"><tr><th class="px-3 py-3">No. Urut</th><th class="px-3 py-3">Rencana & Periode</th><?php if (!$selectedIndicator): ?><th class="px-3 py-3">Aspek & Indikator</th><?php endif; ?><th class="px-3 py-3">SKPD & Program</th><th class="px-3 py-3">Target</th><th class="px-3 py-3 text-right">Aksi</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!$rows): ?><tr><td colspan="<?= $selectedIndicator ? 5 : 6 ?>" class="p-10 text-center text-sm text-slate-500">Belum ada peta rencana sesuai filter.</td></tr><?php endif; ?>
                    <?php foreach ($rows as $row):
                        $record = ['id' => (int) $row['id']];
                        foreach ($fields as $field) $record[$field] = (string) ($row[$field] ?? '');
                        $record += [
                            'aspek_label' => (string) $row['nama_aspek'],
                            'indikator_label' => (string) $row['nama_indikator'],
                            'skpd_label' => peta_label($row['kode_skpd'], $row['nama_skpd']),
                            'program_label' => peta_label($row['kode_program'], $row['nama_program']),
                        ];
                    ?>
                        <tr class="align-top hover:bg-slate-50/70">
                            <td class="px-3 py-3 text-center text-sm font-bold text-red-700"><?= (int) $row['no_urut'] ?></td>
                            <td class="max-w-[280px] whitespace-normal px-3 py-3"><p class="font-semibold text-slate-900"><?= e($row['nama_rencana']) ?></p><p class="mt-1 text-[11px] text-slate-500"><?= e(($row['tanggal_awal'] ?: '-') . ' s.d. ' . ($row['tanggal_akhir'] ?: '-')) ?></p></td>
                            <?php if (!$selectedIndicator): ?><td class="max-w-[260px] whitespace-normal px-3 py-3"><p class="font-semibold"><?= e($row['nama_aspek']) ?></p><p class="mt-1 text-[11px] text-slate-500"><?= e($row['nama_indikator']) ?></p></td><?php endif; ?>
                            <td class="max-w-[260px] whitespace-normal px-3 py-3"><p class="font-semibold"><?= e(peta_label($row['kode_skpd'], $row['nama_skpd'])) ?></p><p class="mt-1 text-[11px] text-slate-500"><?= e(peta_label($row['kode_program'], $row['nama_program'])) ?></p></td>
                            <td class="min-w-[170px] px-3 py-3"><?php foreach ($years as $year): $target = trim((string) ($row['target_' . $year] ?? '')); if ($target === '' || ($filterYear && $filterYear !== $year)) continue; ?><p class="mb-1"><span class="font-semibold text-red-700"><?= $year ?></span> · <?= e($target) ?></p><?php endforeach; ?><?php if ($filterYear && trim((string) $row['target_' . $filterYear]) === ''): ?>-<?php endif; ?></td>
                            <td class="px-3 py-3"><div class="flex justify-end gap-1.5"><button type="button" class="peta-action" title="Lihat" data-peta-view data-record="<?= e(json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"><i data-lucide="eye"></i></button><button type="button" class="peta-action border-blue-200 bg-blue-50 text-blue-700" title="Edit" data-peta-edit data-record="<?= e(json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"><i data-lucide="pencil"></i></button><button type="button" class="peta-action border-red-200 bg-red-50 text-red-700" title="Hapus" data-peta-delete data-id="<?= (int) $row['id'] ?>" data-name="<?= e($row['nama_rencana']) ?>"><i data-lucide="trash-2"></i></button></div></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-3 border-t p-4 text-sm text-slate-500">
            <p>Menampilkan <?= $startRow ?>-<?= $endRow ?> dari <?= $totalRows ?> data</p>
            <?php render_numbered_pagination($currentPage, $totalPages, static fn(int $number): string => peta_page_url($number, $search, $perPage, $filterSkpd, $filterIndicator, $filterYear)); ?>
        </div>
    </div>
</section>

<style>
.peta-control{width:100%;height:2.25rem;border:1px solid #cbd5e1;border-radius:.375rem;background:#fff;padding:.45rem .7rem;font-size:.75rem;outline:none}.peta-control:focus{border-color:#dc2626;box-shadow:0 0 0 3px rgb(254 226 226/.7)}textarea.peta-control{height:auto}.peta-label{display:block;margin-bottom:.3rem;font-size:.68rem;font-weight:700;text-transform:uppercase;color:#64748b}.peta-action{display:inline-flex;width:2rem;height:2rem;align-items:center;justify-content:center;border-width:1px;border-radius:.375rem}.peta-action svg{width:.875rem;height:.875rem}
</style>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 p-3" data-modal="peta-form">
    <form method="post" action="<?= e($returnUrl) ?>" class="flex max-h-[94vh] w-full max-w-5xl flex-col overflow-hidden rounded-lg bg-white shadow-2xl">
        <?= csrf_field() ?><input type="hidden" name="action" value="<?= e($formMode) ?>" data-peta-action><input type="hidden" name="id" value="<?= e($formState['id']) ?>" data-peta-field="id">
        <header class="flex items-center justify-between border-b px-5 py-4"><div class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-700 text-white"><i data-lucide="map" class="h-4 w-4"></i></span><div><h2 class="font-bold" data-peta-title>Tambah Peta Rencana</h2><p class="text-xs text-slate-500">Lengkapi relasi, periode, dan target tahunan.</p></div></div><button type="button" data-modal-close><i data-lucide="x"></i></button></header>
        <div class="flex-1 space-y-5 overflow-y-auto bg-slate-50 p-5">
            <fieldset><legend class="text-sm font-semibold">Identitas Rencana</legend><div class="mt-3 grid gap-3 md:grid-cols-2">
                <?php if ($selectedIndicator): ?>
                    <input type="hidden" name="id_aspek" value="<?= (int) $selectedIndicator['id_aspek'] ?>" data-peta-field="id_aspek">
                    <input type="hidden" name="id_indikator" value="<?= (int) $selectedIndicator['id'] ?>" data-peta-field="id_indikator">
                    <div class="rounded-lg border border-red-200 bg-white p-4 md:col-span-2">
                        <span class="peta-label">Indikator</span>
                        <p class="font-bold text-slate-900">ID <?= (int) $selectedIndicator['id'] ?> - <?= e($selectedIndicator['nama_indikator']) ?></p>
                    </div>
                <?php else: ?>
                    <label><span class="peta-label">Aspek</span><select name="id_aspek" class="peta-control" data-peta-field="id_aspek" required><option value="">Pilih Aspek</option><?php foreach ($aspekList as $item): ?><option value="<?= (int) $item['id'] ?>"><?= e($item['nama_aspek']) ?></option><?php endforeach; ?></select></label>
                    <label><span class="peta-label">Indikator</span><select name="id_indikator" class="peta-control" data-peta-field="id_indikator" required></select></label>
                <?php endif; ?>
                <label><span class="peta-label">Nomor Urut</span><input name="no_urut" type="number" min="1" class="peta-control" data-peta-field="no_urut" required></label>
                <label class="md:col-span-2"><span class="peta-label">Nama Rencana</span><textarea name="nama_rencana" rows="2" maxlength="500" class="peta-control" data-peta-field="nama_rencana" required></textarea></label>
            </div></fieldset>
            <fieldset class="border-t border-slate-200 pt-4"><legend class="text-sm font-semibold">Target Tahunan</legend><div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-5"><?php foreach ($years as $year): ?><label><span class="peta-label">Target <?= $year ?></span><textarea name="target_<?= $year ?>" rows="3" class="peta-control" data-peta-field="target_<?= $year ?>"></textarea></label><?php endforeach; ?></div></fieldset>
            <fieldset class="border-t border-slate-200 pt-4"><legend class="text-sm font-semibold">Pelaksanaan</legend><div class="mt-3 grid gap-3 md:grid-cols-2">
                <label><span class="peta-label">Tanggal Awal</span><input name="tanggal_awal" type="date" class="peta-control" data-peta-field="tanggal_awal"></label>
                <label><span class="peta-label">Tanggal Akhir</span><input name="tanggal_akhir" type="date" class="peta-control" data-peta-field="tanggal_akhir"></label>
                <label><span class="peta-label">SKPD</span><select name="id_skpd" class="peta-control" data-peta-field="id_skpd" required><option value="">Pilih SKPD</option><?php foreach ($skpdList as $item): ?><option value="<?= (int) $item['id'] ?>"><?= e(peta_label($item['kode_skpd'], $item['nama_skpd'])) ?></option><?php endforeach; ?></select></label>
                <label><span class="peta-label">Program</span><select name="id_program" class="peta-control" data-peta-field="id_program" required></select></label>
                <label class="md:col-span-2"><span class="peta-label">Keterangan</span><textarea name="keterangan" rows="3" class="peta-control" data-peta-field="keterangan"></textarea></label>
            </div></fieldset>
        </div>
        <footer class="flex justify-end gap-2 border-t px-5 py-3"><button type="button" class="rounded-lg border px-4 py-2 text-sm font-semibold" data-modal-close>Batal</button><button class="rounded-lg bg-red-700 px-5 py-2 text-sm font-semibold text-white">Simpan Data</button></footer>
    </form>
</div>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 p-3" data-modal="peta-view">
    <div class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-lg bg-white">
        <header class="flex items-center justify-between border-b p-4"><h2 class="font-bold">Detail Peta Rencana</h2><button type="button" data-modal-close><i data-lucide="x"></i></button></header>
        <div class="grid flex-1 gap-3 overflow-y-auto bg-slate-50 p-5 md:grid-cols-2"><?php foreach ([
            'no_urut'=>'Nomor Urut','nama_rencana'=>'Nama Rencana','aspek_label'=>'Aspek','indikator_label'=>'Indikator',
            'tanggal_awal'=>'Tanggal Awal','tanggal_akhir'=>'Tanggal Akhir','skpd_label'=>'SKPD','program_label'=>'Program',
            'target_2026'=>'Target 2026','target_2027'=>'Target 2027','target_2028'=>'Target 2028','target_2029'=>'Target 2029','target_2030'=>'Target 2030','keterangan'=>'Keterangan'
        ] as $field=>$label): ?><div class="rounded-lg border bg-white p-4 <?= in_array($field, ['nama_rencana','indikator_label','keterangan'], true) ? 'md:col-span-2' : '' ?>"><b class="text-xs uppercase text-slate-500"><?= e($label) ?></b><p class="mt-1 whitespace-pre-line" data-view-field="<?= e($field) ?>"></p></div><?php endforeach; ?></div>
    </div>
</div>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 p-3" data-modal="peta-delete">
    <form method="post" class="w-full max-w-md rounded-lg bg-white p-6"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" data-delete-id><h2 class="text-xl font-bold">Hapus Peta Rencana?</h2><p class="mt-2 text-sm text-slate-500">Tindakan ini permanen dan dicatat dalam audit trail.</p><p class="mt-4 rounded-lg bg-slate-50 p-4 font-semibold" data-delete-name></p><div class="mt-5 flex justify-end gap-2"><button type="button" class="rounded-lg border px-4 py-2" data-modal-close>Batal</button><button class="rounded-lg bg-red-700 px-4 py-2 font-semibold text-white">Ya, Hapus</button></div></form>
</div>

<script>
(() => {
    const modals = document.querySelectorAll('[data-modal]');
    const form = document.querySelector('[data-modal="peta-form"]');
    const view = document.querySelector('[data-modal="peta-view"]');
    const del = document.querySelector('[data-modal="peta-delete"]');
    const fields = <?= json_encode(array_merge(['id'], $fields)) ?>;
    const posted = <?= json_encode($formState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const indicators = <?= json_encode($indicatorJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const programs = <?= json_encode($programJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const lockedIndicator = <?= json_encode($selectedIndicator ? [
        'id' => (int) $selectedIndicator['id'],
        'aspek_id' => (int) $selectedIndicator['id_aspek'],
    ] : null) ?>;
    const input = name => form.querySelector(`[data-peta-field="${name}"]`);
    const aspect = input('id_aspek'), indicator = input('id_indikator'), skpd = input('id_skpd'), program = input('id_program');
    const open = modal => { modal.classList.remove('hidden'); modal.classList.add('flex'); document.body.classList.add('overflow-hidden'); };
    const close = modal => { modal.classList.add('hidden'); modal.classList.remove('flex'); if (![...modals].some(item => !item.classList.contains('hidden'))) document.body.classList.remove('overflow-hidden'); };
    const refreshIndicators = selected => {
        if (lockedIndicator) {
            aspect.value = String(lockedIndicator.aspek_id);
            indicator.value = String(lockedIndicator.id);
            return;
        }
        indicator.innerHTML = '';
        indicator.add(new Option(aspect.value ? 'Pilih Indikator' : 'Pilih Aspek terlebih dahulu', ''));
        indicators.filter(item => String(item.aspek_id) === aspect.value).forEach(item => indicator.add(new Option(item.label, String(item.id))));
        indicator.disabled = !aspect.value;
        if (selected) indicator.value = String(selected);
    };
    const refreshPrograms = selected => {
        program.innerHTML = '';
        program.add(new Option(skpd.value ? 'Pilih Program' : 'Pilih SKPD terlebih dahulu', ''));
        programs.filter(item => String(item.skpd_id) === skpd.value).forEach(item => program.add(new Option(item.label, String(item.id))));
        program.disabled = !skpd.value;
        if (selected) program.value = String(selected);
    };
    const fill = record => {
        fields.forEach(name => { if (input(name) && !['id_indikator','id_program'].includes(name)) input(name).value = record[name] || ''; });
        refreshIndicators(lockedIndicator?.id || record.id_indikator || '');
        refreshPrograms(record.id_program || '');
    };
    aspect.addEventListener('change', () => refreshIndicators(''));
    skpd.addEventListener('change', () => refreshPrograms(''));
    document.querySelector('[data-peta-add]')?.addEventListener('click', () => { fill({}); form.querySelector('[data-peta-action]').value='create'; form.querySelector('[data-peta-title]').textContent='Tambah Peta Rencana'; open(form); });
    document.querySelectorAll('[data-peta-edit]').forEach(button => button.addEventListener('click', () => { fill(JSON.parse(button.dataset.record || '{}')); form.querySelector('[data-peta-action]').value='update'; form.querySelector('[data-peta-title]').textContent='Edit Peta Rencana'; open(form); }));
    document.querySelectorAll('[data-peta-view]').forEach(button => button.addEventListener('click', () => { const record=JSON.parse(button.dataset.record || '{}'); view.querySelectorAll('[data-view-field]').forEach(element => element.textContent=record[element.dataset.viewField] || '-'); open(view); }));
    document.querySelectorAll('[data-peta-delete]').forEach(button => button.addEventListener('click', () => { del.querySelector('[data-delete-id]').value=button.dataset.id; del.querySelector('[data-delete-name]').textContent=button.dataset.name; open(del); }));
    document.querySelectorAll('[data-modal-close]').forEach(button => button.addEventListener('click', () => close(button.closest('[data-modal]'))));
    modals.forEach(modal => modal.addEventListener('click', event => { if (event.target === modal) close(modal); }));
    document.addEventListener('keydown', event => { if (event.key === 'Escape') modals.forEach(close); });
    refreshIndicators('');
    refreshPrograms('');
    <?php if ($openFormModal): ?>fill(posted);form.querySelector('[data-peta-action]').value=<?= json_encode($formMode) ?>;form.querySelector('[data-peta-title]').textContent=<?= json_encode(($formMode === 'update' ? 'Edit ' : 'Tambah ') . 'Peta Rencana') ?>;open(form);<?php endif; ?>
})();
</script>
