<?php

declare(strict_types=1);

$dalFields = [
    'id_skpd', 'id_program', 'id_ral', 'nama_layanan', 'tujuan_layanan', 'fungsi_layanan',
    'target_layanan', 'metode_layanan', 'potensi_manfaat', 'potensi_ekonomi',
    'potensi_risiko', 'mitigasi_risiko', 'id_penanggung_jawab', 'id_unit_kerja_pelaksana',
];
$formErrors = [];
$openFormModal = false;
$formMode = 'create';
$formState = array_fill_keys($dalFields, '');
$formState['id'] = '';
$formState['id_dab'] = [];

function dal_int_or_null(mixed $value): ?int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id ? (int) $id : null;
}

function dal_option_label(?string $code, ?string $name): string
{
    $code = trim((string) $code);
    $name = trim((string) $name);
    return trim($code . ($code !== '' && $name !== '' ? ' - ' : '') . $name);
}

function dal_page_url(int $page, string $search, int $perPage): string
{
    return 'index.php?' . http_build_query(['page' => 'dal', 'q' => $search, 'per_page' => $perPage, 'p' => $page]);
}

$skpdList = db()->query('SELECT id, kode_skpd, nama_skpd FROM skpd ORDER BY kode_skpd, nama_skpd')->fetchAll();
$programList = db()->query(
    'SELECT id, kode_program, nama_program, id_skpd, id_ral FROM program ORDER BY kode_program, nama_program'
)->fetchAll();
$ralList = db()->query(
    'SELECT id, kode_ral_4, nama_ral_4 FROM ral ORDER BY kode_ral_4, nama_ral_4'
)->fetchAll();
$dabList = db()->query(
    'SELECT d.id, d.nama_bisnis, s.kode_skpd
     FROM dab d LEFT JOIN skpd s ON s.id = d.id_skpd
     ORDER BY s.kode_skpd, d.nama_bisnis'
)->fetchAll();

$skpdIds = array_flip(array_map('intval', array_column($skpdList, 'id')));
$ralIds = array_flip(array_map('intval', array_column($ralList, 'id')));
$dabIds = array_flip(array_map('intval', array_column($dabList, 'id')));
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
        $recordId = dal_int_or_null($_POST['id'] ?? null);
        $formState['id'] = $recordId ?: '';
        foreach ($dalFields as $field) {
            $formState[$field] = trim((string) ($_POST[$field] ?? ''));
        }
        $postedDabIds = is_array($_POST['id_dab'] ?? null) ? $_POST['id_dab'] : [];
        $selectedDabIds = array_values(array_unique(array_filter(array_map('dal_int_or_null', $postedDabIds))));
        $formState['id_dab'] = $selectedDabIds;

        $idSkpd = dal_int_or_null($formState['id_skpd']);
        $idProgram = dal_int_or_null($formState['id_program']);
        $idRal = dal_int_or_null($formState['id_ral']);
        $idPenanggungJawab = dal_int_or_null($formState['id_penanggung_jawab']);
        $idUnitPelaksana = dal_int_or_null($formState['id_unit_kerja_pelaksana']);

        if (!$idSkpd || !isset($skpdIds[$idSkpd])) $formErrors[] = 'SKPD wajib dipilih.';
        if (!$idProgram || !isset($programMap[$idProgram])) $formErrors[] = 'Program wajib dipilih.';
        if (!$idRal || !isset($ralIds[$idRal])) $formErrors[] = 'Referensi RAL wajib dipilih.';
        if (!$idPenanggungJawab || !isset($skpdIds[$idPenanggungJawab])) $formErrors[] = 'Penanggung jawab wajib dipilih dari SKPD.';
        if (!$idUnitPelaksana || !isset($skpdIds[$idUnitPelaksana])) $formErrors[] = 'Unit kerja pelaksana wajib dipilih dari SKPD.';
        if ($idProgram && $idSkpd && isset($programMap[$idProgram]) && (int) $programMap[$idProgram]['id_skpd'] !== $idSkpd) {
            $formErrors[] = 'Program yang dipilih tidak sesuai dengan SKPD.';
        }
        if (!$selectedDabIds) {
            $formErrors[] = 'Minimal satu DAB wajib dipilih.';
        } elseif (array_diff($selectedDabIds, array_keys($dabIds))) {
            $formErrors[] = 'Referensi DAB tidak valid.';
        }
        if ($formState['nama_layanan'] === '') {
            $formErrors[] = 'Nama layanan wajib diisi.';
        } elseif (mb_strlen($formState['nama_layanan']) > 255) {
            $formErrors[] = 'Nama layanan maksimal 255 karakter.';
        }
        foreach (['target_layanan', 'metode_layanan'] as $field) {
            if (mb_strlen($formState[$field]) > 255) $formErrors[] = ucfirst(str_replace('_', ' ', $field)) . ' maksimal 255 karakter.';
        }
        if ($action === 'update' && !$recordId) $formErrors[] = 'ID data DAL tidak valid.';

        $oldValues = null;
        if (!$formErrors && $action === 'update') {
            $oldStmt = db()->prepare('SELECT * FROM dal WHERE id = :id LIMIT 1');
            $oldStmt->execute(['id' => $recordId]);
            $oldValues = $oldStmt->fetch();
            if (!$oldValues) {
                $formErrors[] = 'Data DAL yang akan diubah tidak ditemukan.';
            } else {
                $linkStmt = db()->prepare('SELECT id_dab FROM dal_dab WHERE id_dal = :id ORDER BY id_dab');
                $linkStmt->execute(['id' => $recordId]);
                $oldValues['id_dab'] = array_map('intval', $linkStmt->fetchAll(PDO::FETCH_COLUMN));
            }
        }

        if (!$formErrors) {
            db()->beginTransaction();
            try {
                $params = [
                    'id_skpd' => $idSkpd, 'id_program' => $idProgram, 'id_ral' => $idRal,
                    'nama_layanan' => $formState['nama_layanan'],
                    'tujuan_layanan' => $formState['tujuan_layanan'] ?: null,
                    'fungsi_layanan' => $formState['fungsi_layanan'] ?: null,
                    'target_layanan' => $formState['target_layanan'] ?: null,
                    'metode_layanan' => $formState['metode_layanan'] ?: null,
                    'potensi_manfaat' => $formState['potensi_manfaat'] ?: null,
                    'potensi_ekonomi' => $formState['potensi_ekonomi'] ?: null,
                    'potensi_risiko' => $formState['potensi_risiko'] ?: null,
                    'mitigasi_risiko' => $formState['mitigasi_risiko'] ?: null,
                    'id_penanggung_jawab' => $idPenanggungJawab,
                    'id_unit_kerja_pelaksana' => $idUnitPelaksana,
                    'updated_by' => (int) $user['id'],
                ];
                if ($action === 'create') {
                    $params['created_by'] = (int) $user['id'];
                    $stmt = db()->prepare(
                        'INSERT INTO dal
                         (id_skpd, id_program, id_ral, nama_layanan, tujuan_layanan, fungsi_layanan,
                          target_layanan, metode_layanan, potensi_manfaat, potensi_ekonomi, potensi_risiko,
                          mitigasi_risiko, id_penanggung_jawab, id_unit_kerja_pelaksana, created_by, updated_by)
                         VALUES
                         (:id_skpd, :id_program, :id_ral, :nama_layanan, :tujuan_layanan, :fungsi_layanan,
                          :target_layanan, :metode_layanan, :potensi_manfaat, :potensi_ekonomi, :potensi_risiko,
                          :mitigasi_risiko, :id_penanggung_jawab, :id_unit_kerja_pelaksana, :created_by, :updated_by)'
                    );
                    $stmt->execute($params);
                    $recordId = (int) db()->lastInsertId();
                } else {
                    $params['id'] = $recordId;
                    $stmt = db()->prepare(
                        'UPDATE dal SET id_skpd=:id_skpd, id_program=:id_program, id_ral=:id_ral,
                         nama_layanan=:nama_layanan, tujuan_layanan=:tujuan_layanan, fungsi_layanan=:fungsi_layanan,
                         target_layanan=:target_layanan, metode_layanan=:metode_layanan,
                         potensi_manfaat=:potensi_manfaat, potensi_ekonomi=:potensi_ekonomi,
                         potensi_risiko=:potensi_risiko, mitigasi_risiko=:mitigasi_risiko,
                         id_penanggung_jawab=:id_penanggung_jawab,
                         id_unit_kerja_pelaksana=:id_unit_kerja_pelaksana, updated_by=:updated_by
                         WHERE id=:id'
                    );
                    $stmt->execute($params);
                    db()->prepare('DELETE FROM dal_dab WHERE id_dal = :id')->execute(['id' => $recordId]);
                }
                $linkStmt = db()->prepare('INSERT INTO dal_dab (id_dal, id_dab) VALUES (:id_dal, :id_dab)');
                foreach ($selectedDabIds as $idDab) $linkStmt->execute(['id_dal' => $recordId, 'id_dab' => $idDab]);

                $newStmt = db()->prepare('SELECT * FROM dal WHERE id = :id LIMIT 1');
                $newStmt->execute(['id' => $recordId]);
                $newValues = $newStmt->fetch() ?: $params;
                $newValues['id_dab'] = $selectedDabIds;
                audit_log(
                    (int) $user['id'], $action, 'dal', (int) $recordId,
                    ($action === 'create' ? 'Menambahkan' : 'Mengubah') . ' domain layanan ' . $formState['nama_layanan'],
                    $oldValues, $newValues, true
                );
                db()->commit();
                set_flash('success', $action === 'create' ? 'Data DAL berhasil ditambahkan.' : 'Data DAL berhasil diperbarui.');
                redirect('index.php?page=dal');
            } catch (Throwable $exception) {
                db()->rollBack();
                error_log('Penyimpanan DAL gagal: ' . $exception->getMessage());
                $formErrors[] = 'Data DAL gagal disimpan. Silakan coba kembali.';
            }
        }
    } elseif ($action === 'delete') {
        $recordId = dal_int_or_null($_POST['id'] ?? null);
        $stmt = db()->prepare('SELECT * FROM dal WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $recordId]);
        $record = $stmt->fetch();
        if (!$recordId || !$record) {
            set_flash('error', 'Data DAL tidak ditemukan.');
            redirect('index.php?page=dal');
        }
        $linkStmt = db()->prepare('SELECT id_dab FROM dal_dab WHERE id_dal = :id ORDER BY id_dab');
        $linkStmt->execute(['id' => $recordId]);
        $record['id_dab'] = array_map('intval', $linkStmt->fetchAll(PDO::FETCH_COLUMN));
        db()->beginTransaction();
        try {
            db()->prepare('DELETE FROM dal_dab WHERE id_dal = :id')->execute(['id' => $recordId]);
            db()->prepare('DELETE FROM dal WHERE id = :id')->execute(['id' => $recordId]);
            audit_log((int) $user['id'], 'delete', 'dal', $recordId, 'Menghapus domain layanan ' . $record['nama_layanan'], $record, null, true);
            db()->commit();
            set_flash('success', 'Data DAL berhasil dihapus.');
        } catch (Throwable $exception) {
            db()->rollBack();
            error_log('Penghapusan DAL gagal: ' . $exception->getMessage());
            set_flash('error', 'Data DAL gagal dihapus.');
        }
        redirect('index.php?page=dal');
    }
}

$search = trim((string) ($_GET['q'] ?? ''));
$perPageOptions = [10, 25, 50];
$perPage = (int) ($_GET['per_page'] ?? 10);
if (!in_array($perPage, $perPageOptions, true)) $perPage = 10;
$currentPage = max(1, (int) ($_GET['p'] ?? 1));
$whereSql = '';
$queryParams = [];
if ($search !== '') {
    $whereSql = " WHERE CONCAT_WS(' ', d.nama_layanan, d.tujuan_layanan, d.fungsi_layanan,
        s.kode_skpd, s.nama_skpd, p.kode_program, p.nama_program, r.kode_ral_4, r.nama_ral_4,
        pj.nama_skpd, uk.nama_skpd,
        (SELECT GROUP_CONCAT(b.nama_bisnis SEPARATOR ' ') FROM dal_dab x JOIN dab b ON b.id=x.id_dab WHERE x.id_dal=d.id)
    ) LIKE :search";
    $queryParams['search'] = '%' . $search . '%';
}
$joins = ' FROM dal d
    LEFT JOIN skpd s ON s.id=d.id_skpd
    LEFT JOIN program p ON p.id=d.id_program
    LEFT JOIN ral r ON r.id=d.id_ral
    LEFT JOIN skpd pj ON pj.id=d.id_penanggung_jawab
    LEFT JOIN skpd uk ON uk.id=d.id_unit_kerja_pelaksana
    LEFT JOIN users creator ON creator.id=d.created_by
    LEFT JOIN users updater ON updater.id=d.updated_by';
$countStmt = db()->prepare('SELECT COUNT(*)' . $joins . $whereSql);
$countStmt->execute($queryParams);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $perPage;
$dataStmt = db()->prepare(
    'SELECT d.*, s.kode_skpd, s.nama_skpd, p.kode_program, p.nama_program,
        r.kode_ral_4, r.nama_ral_4,
        pj.kode_skpd AS pj_kode, pj.nama_skpd AS pj_nama,
        uk.kode_skpd AS uk_kode, uk.nama_skpd AS uk_nama,
        creator.name AS created_by_name, updater.name AS updated_by_name,
        (SELECT GROUP_CONCAT(x.id_dab ORDER BY x.id_dab) FROM dal_dab x WHERE x.id_dal=d.id) AS dab_ids,
        (SELECT GROUP_CONCAT(b.nama_bisnis ORDER BY b.nama_bisnis SEPARATOR " • ")
         FROM dal_dab x JOIN dab b ON b.id=x.id_dab WHERE x.id_dal=d.id) AS dab_labels'
    . $joins . $whereSql . ' ORDER BY s.kode_skpd ASC, p.kode_program ASC, d.id ASC LIMIT :limit OFFSET :offset'
);
foreach ($queryParams as $key => $value) $dataStmt->bindValue(':' . $key, $value);
$dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$dataStmt->execute();
$rows = $dataStmt->fetchAll();
$startRow = $totalRows ? $offset + 1 : 0;
$endRow = min($offset + $perPage, $totalRows);
$programJson = array_map(static fn(array $p): array => [
    'id' => (int) $p['id'], 'skpd_id' => (int) $p['id_skpd'],
    'ral_id' => $p['id_ral'] !== null ? (int) $p['id_ral'] : null,
    'label' => dal_option_label($p['kode_program'], $p['nama_program']),
], $programList);
?>

<section class="space-y-5">
    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-red-700">Domain Arsitektur</p>
            <h1 class="mt-1 text-xl font-bold tracking-tight text-slate-900">Domain Arsitektur Layanan</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola layanan, relasi proses bisnis, penanggung jawab, dan unit pelaksana.</p>
        </div>
        <div class="flex gap-2">
            <a href="cetak/cetak_dal.php" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                <i data-lucide="printer" class="h-4 w-4"></i>Cetak
            </a>
            <button type="button" class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800" data-dal-add>
                <i data-lucide="plus" class="h-4 w-4"></i>Tambah DAL
            </button>
        </div>
    </div>

    <?php if ($formErrors): ?>
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-semibold">Data belum bisa disimpan.</p>
            <ul class="mt-1 list-disc pl-5"><?php foreach ($formErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-4 py-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="font-semibold text-slate-900">Daftar Domain Layanan</h2>
                <p class="mt-1 text-sm text-slate-500">Menampilkan <?= number_format($totalRows, 0, ',', '.') ?> data<?= $search !== '' ? ' hasil pencarian' : '' ?>.</p>
            </div>
            <form method="get" class="flex flex-col gap-2 sm:flex-row">
                <input type="hidden" name="page" value="dal">
                <div class="relative"><i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i><input type="search" name="q" value="<?= e($search) ?>" placeholder="Cari layanan, DAB, SKPD..." class="w-full rounded-md border border-slate-300 py-2 pl-9 pr-3 text-xs outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100 sm:w-64"></div>
                <select name="per_page" class="rounded-md border border-slate-300 px-3 py-2 text-xs outline-none focus:border-blue-600" aria-label="Jumlah data per halaman">
                    <?php foreach ($perPageOptions as $option): ?><option value="<?= $option ?>" <?= $perPage === $option ? 'selected' : '' ?>><?= $option ?> data</option><?php endforeach; ?>
                </select>
                <button class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Tampilkan</button>
                <?php if ($search !== ''): ?><a href="index.php?page=dal" class="inline-flex items-center justify-center rounded-md px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">Reset</a><?php endif; ?>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-[12px]">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-2.5 py-2.5">Kode</th>
                        <th class="px-2.5 py-2.5">Nama Layanan</th>
                        <th class="px-2.5 py-2.5">Tujuan Layanan</th>
                        <th class="px-2.5 py-2.5">Fungsi Layanan</th>
                        <th class="px-2.5 py-2.5">Target Layanan</th>
                        <th class="px-2.5 py-2.5">Metode Layanan</th>
                        <th class="px-2.5 py-2.5">RAL</th>
                        <th class="px-2.5 py-2.5">SKPD / Program</th>
                        <th class="px-2.5 py-2.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                <?php if (!$rows): ?><tr><td colspan="9" class="px-3 py-10 text-center text-sm text-slate-500">Belum ada data DAL.</td></tr><?php endif; ?>
                <?php foreach ($rows as $index => $row):
                    $record = [
                        'id' => (int) $row['id'], 'id_skpd' => (int) $row['id_skpd'], 'id_program' => (int) $row['id_program'],
                        'id_ral' => (int) $row['id_ral'], 'nama_layanan' => (string) $row['nama_layanan'],
                        'tujuan_layanan' => (string) ($row['tujuan_layanan'] ?? ''), 'fungsi_layanan' => (string) ($row['fungsi_layanan'] ?? ''),
                        'target_layanan' => (string) ($row['target_layanan'] ?? ''), 'metode_layanan' => (string) ($row['metode_layanan'] ?? ''),
                        'potensi_manfaat' => (string) ($row['potensi_manfaat'] ?? ''), 'potensi_ekonomi' => (string) ($row['potensi_ekonomi'] ?? ''),
                        'potensi_risiko' => (string) ($row['potensi_risiko'] ?? ''), 'mitigasi_risiko' => (string) ($row['mitigasi_risiko'] ?? ''),
                        'id_penanggung_jawab' => (int) $row['id_penanggung_jawab'],
                        'id_unit_kerja_pelaksana' => (int) $row['id_unit_kerja_pelaksana'],
                        'id_dab' => $row['dab_ids'] ? array_map('intval', explode(',', $row['dab_ids'])) : [],
                        'skpd_label' => dal_option_label($row['kode_skpd'], $row['nama_skpd']),
                        'program_label' => dal_option_label($row['kode_program'], $row['nama_program']),
                        'ral_label' => dal_option_label($row['kode_ral_4'], $row['nama_ral_4']),
                        'dab_labels' => (string) ($row['dab_labels'] ?? ''),
                        'pj_label' => dal_option_label($row['pj_kode'], $row['pj_nama']),
                        'uk_label' => dal_option_label($row['uk_kode'], $row['uk_nama']),
                    ];
                ?>
                    <tr class="align-top hover:bg-slate-50/70">
                        <td class="whitespace-nowrap px-3 py-2.5 font-semibold text-red-700"><?= e(sprintf('DAL-%03d', $record['id'])) ?></td>
                        <td class="max-w-[230px] whitespace-normal break-words px-3 py-2.5 font-semibold"><?= e($record['nama_layanan']) ?></td>
                        <td class="max-w-[220px] whitespace-normal break-words px-3 py-2.5"><?= e($record['tujuan_layanan'] ?: '-') ?></td>
                        <td class="max-w-[220px] whitespace-normal break-words px-3 py-2.5"><?= e($record['fungsi_layanan'] ?: '-') ?></td>
                        <td class="max-w-[160px] whitespace-normal break-words px-3 py-2.5"><?= e($record['target_layanan'] ?: '-') ?></td>
                        <td class="max-w-[160px] whitespace-normal break-words px-3 py-2.5"><?= e($record['metode_layanan'] ?: '-') ?></td>
                        <td class="max-w-[190px] whitespace-normal break-words px-3 py-2.5 text-red-700"><?= e($record['ral_label'] ?: '-') ?></td>
                        <td class="max-w-[230px] whitespace-normal break-words px-3 py-2.5"><p class="font-semibold"><?= e($record['skpd_label'] ?: '-') ?></p><p class="mt-1 text-slate-500"><?= e($record['program_label'] ?: '-') ?></p></td>
                        <td class="px-3 py-2.5"><div class="flex justify-end gap-1.5">
                            <button type="button" class="h-8 w-8 rounded-md border border-slate-200" title="Lihat" data-dal-view data-record="<?= e(json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"><i data-lucide="eye" class="mx-auto h-3.5 w-3.5"></i></button>
                            <button type="button" class="h-8 w-8 rounded-md border border-blue-200 bg-blue-50 text-blue-700" title="Edit" data-dal-edit data-record="<?= e(json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"><i data-lucide="pencil" class="mx-auto h-3.5 w-3.5"></i></button>
                            <button type="button" class="h-8 w-8 rounded-md border border-red-200 bg-red-50 text-red-700" title="Hapus" data-dal-delete data-id="<?= $record['id'] ?>" data-name="<?= e($record['nama_layanan']) ?>"><i data-lucide="trash-2" class="mx-auto h-3.5 w-3.5"></i></button>
                        </div></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-4 py-3 text-sm text-slate-500">
            <p>Menampilkan <?= $startRow ?>-<?= $endRow ?> dari <?= $totalRows ?> data</p>
            <div class="flex gap-2">
                <?php if ($currentPage > 1): ?><a href="<?= e(dal_page_url($currentPage - 1, $search, $perPage)) ?>" class="rounded-md border px-3 py-2">Sebelumnya</a><?php endif; ?>
                <?php if ($currentPage < $totalPages): ?><a href="<?= e(dal_page_url($currentPage + 1, $search, $perPage)) ?>" class="rounded-md border px-3 py-2">Berikutnya</a><?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
    [data-modal="dal-form"] input:not([type="hidden"]),
    [data-modal="dal-form"] select,
    [data-modal="dal-form"] textarea {
        border-color: #cbd5e1;
        color: #0f172a;
        outline: none;
        transition: border-color .15s, box-shadow .15s;
    }
    [data-modal="dal-form"] input:focus,
    [data-modal="dal-form"] select:focus,
    [data-modal="dal-form"] textarea:focus {
        border-color: #dc2626;
        box-shadow: 0 0 0 4px rgb(254 226 226 / .7);
    }
</style>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 px-3 py-4" data-modal="dal-form">
    <form method="post" class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <?= csrf_field() ?><input type="hidden" name="action" value="<?= e($formMode) ?>" data-dal-action><input type="hidden" name="id" value="<?= e($formState['id']) ?>" data-dal-field="id">
        <div class="flex items-start justify-between border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white px-4 py-3 sm:px-5">
            <div class="flex items-start gap-3"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-700 text-white shadow-sm ring-4 ring-red-50"><i data-lucide="handshake" class="h-4 w-4"></i></span><div><h2 class="text-base font-bold tracking-tight" data-dal-form-title>Tambah Data DAL</h2><p class="mt-0.5 text-xs text-slate-500">Lengkapi identitas, relasi, dan informasi layanan.</p></div></div>
            <button type="button" class="h-9 w-9 rounded-lg border border-slate-200 bg-white text-slate-500" data-modal-close><i data-lucide="x" class="mx-auto h-4 w-4"></i></button>
        </div>
        <div class="grid flex-1 gap-3 overflow-y-auto bg-slate-50/70 p-4 sm:p-5">
            <div class="grid gap-3 lg:grid-cols-2">
                <label><span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">SKPD</span><select name="id_skpd" class="w-full rounded-lg border bg-white px-3 py-2 text-sm" data-dal-field="id_skpd" required><option value="">Pilih SKPD</option><?php foreach ($skpdList as $item): ?><option value="<?= (int) $item['id'] ?>"><?= e(dal_option_label($item['kode_skpd'], $item['nama_skpd'])) ?></option><?php endforeach; ?></select></label>
                <label><span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Program</span><select name="id_program" class="w-full rounded-lg border bg-white px-3 py-2 text-sm" data-dal-field="id_program" required></select></label>
            </div>
            <label><span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Referensi RAL</span><select name="id_ral" class="w-full rounded-lg border bg-white px-3 py-2 text-sm" data-dal-field="id_ral" required><option value="">Pilih RAL</option><?php foreach ($ralList as $item): ?><option value="<?= (int) $item['id'] ?>"><?= e(dal_option_label($item['kode_ral_4'], $item['nama_ral_4'])) ?></option><?php endforeach; ?></select></label>
            <label><span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">DAB Terkait</span><select name="id_dab[]" multiple size="4" class="w-full rounded-lg border bg-white px-3 py-2 text-sm" data-dal-dab required><?php foreach ($dabList as $item): ?><option value="<?= (int) $item['id'] ?>"><?= e(trim(($item['kode_skpd'] ? $item['kode_skpd'] . ' - ' : '') . $item['nama_bisnis'])) ?></option><?php endforeach; ?></select><span class="mt-1 block text-[11px] text-slate-500">Gunakan Ctrl/Cmd untuk memilih lebih dari satu.</span></label>
            <label><span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Nama Layanan</span><input name="nama_layanan" maxlength="255" class="w-full rounded-lg border px-3 py-2 text-sm" data-dal-field="nama_layanan" required></label>
            <?php foreach ([
                'tujuan_layanan'=>'Tujuan Layanan', 'fungsi_layanan'=>'Fungsi Layanan',
                'potensi_manfaat'=>'Potensi Manfaat', 'potensi_ekonomi'=>'Potensi Ekonomi',
                'potensi_risiko'=>'Potensi Risiko', 'mitigasi_risiko'=>'Mitigasi Risiko'
            ] as $field => $label): ?>
                <label><span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500"><?= e($label) ?></span><textarea name="<?= e($field) ?>" rows="2" class="w-full rounded-lg border px-3 py-2 text-sm" data-dal-field="<?= e($field) ?>"></textarea></label>
            <?php endforeach; ?>
            <div class="grid gap-3 lg:grid-cols-2">
                <label><span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Target Layanan</span><input name="target_layanan" maxlength="255" class="w-full rounded-lg border px-3 py-2 text-sm" data-dal-field="target_layanan"></label>
                <label><span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Metode Layanan</span><input name="metode_layanan" maxlength="255" class="w-full rounded-lg border px-3 py-2 text-sm" data-dal-field="metode_layanan"></label>
            </div>
            <div class="grid gap-3 lg:grid-cols-2">
                <?php foreach (['id_penanggung_jawab'=>'Penanggung Jawab', 'id_unit_kerja_pelaksana'=>'Unit Kerja Pelaksana'] as $field => $label): ?>
                    <label><span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500"><?= e($label) ?></span><select name="<?= e($field) ?>" class="w-full rounded-lg border bg-white px-3 py-2 text-sm" data-dal-field="<?= e($field) ?>" required><option value="">Pilih SKPD</option><?php foreach ($skpdList as $item): ?><option value="<?= (int) $item['id'] ?>"><?= e(dal_option_label($item['kode_skpd'], $item['nama_skpd'])) ?></option><?php endforeach; ?></select></label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="flex justify-end gap-2 border-t bg-white px-4 py-3 sm:px-5"><button type="button" class="rounded-lg border px-4 py-2 text-sm font-semibold" data-modal-close>Batal</button><button class="rounded-lg bg-red-700 px-5 py-2 text-sm font-semibold text-white"><span data-dal-submit-label>Simpan Data</span></button></div>
    </form>
</div>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 px-3 py-4" data-modal="dal-view">
    <div class="flex max-h-[94vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex justify-between border-b px-6 py-4"><div><h2 class="text-lg font-bold">Detail Data DAL</h2><p class="text-sm text-slate-500" data-view-field="nama_layanan"></p></div><button data-modal-close><i data-lucide="x"></i></button></div>
        <div class="grid flex-1 gap-3 overflow-y-auto bg-slate-50 p-5 md:grid-cols-2">
            <?php foreach ([
                'skpd_label'=>'SKPD', 'program_label'=>'Program', 'ral_label'=>'Referensi RAL', 'dab_labels'=>'DAB Terkait',
                'pj_label'=>'Penanggung Jawab', 'uk_label'=>'Unit Kerja Pelaksana', 'tujuan_layanan'=>'Tujuan',
                'fungsi_layanan'=>'Fungsi', 'target_layanan'=>'Target', 'metode_layanan'=>'Metode',
                'potensi_manfaat'=>'Potensi Manfaat', 'potensi_ekonomi'=>'Potensi Ekonomi',
                'potensi_risiko'=>'Potensi Risiko', 'mitigasi_risiko'=>'Mitigasi Risiko'
            ] as $field => $label): ?><section class="rounded-xl border bg-white p-4"><p class="text-xs font-semibold uppercase text-slate-500"><?= e($label) ?></p><p class="mt-2 whitespace-pre-line text-sm" data-view-field="<?= e($field) ?>"></p></section><?php endforeach; ?>
        </div><div class="flex justify-end border-t p-4"><button class="rounded-lg border px-4 py-2" data-modal-close>Tutup</button></div>
    </div>
</div>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 px-3 py-4" data-modal="dal-delete">
    <form method="post" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" data-delete-id><h2 class="text-xl font-bold">Hapus Data DAL?</h2><p class="mt-2 text-sm text-slate-500">Tindakan ini permanen dan tercatat pada audit trail.</p><p class="mt-4 rounded-xl bg-slate-50 p-4 font-semibold" data-delete-name></p><div class="mt-5 flex justify-end gap-2"><button type="button" class="rounded-lg border px-4 py-2.5" data-modal-close>Batal</button><button class="rounded-lg bg-red-700 px-4 py-2.5 font-semibold text-white">Ya, Hapus</button></div></form>
</div>

<script>
(() => {
    const modals = document.querySelectorAll('[data-modal]');
    const formModal = document.querySelector('[data-modal="dal-form"]');
    const viewModal = document.querySelector('[data-modal="dal-view"]');
    const deleteModal = document.querySelector('[data-modal="dal-delete"]');
    const fields = <?= json_encode(array_merge(['id'], $dalFields), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const programs = <?= json_encode($programJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const posted = <?= json_encode($formState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const field = name => formModal.querySelector(`[data-dal-field="${name}"]`);
    const skpd = field('id_skpd'), program = field('id_program'), ral = field('id_ral'), dab = formModal.querySelector('[data-dal-dab]');
    const open = modal => { modal?.classList.remove('hidden'); modal?.classList.add('flex'); document.body.classList.add('overflow-hidden'); };
    const close = modal => { modal?.classList.add('hidden'); modal?.classList.remove('flex'); if (![...modals].some(x => !x.classList.contains('hidden'))) document.body.classList.remove('overflow-hidden'); };
    const refreshPrograms = selected => {
        const skpdId = Number(skpd.value || 0); program.innerHTML = '';
        program.add(new Option(skpdId ? 'Pilih Program' : 'Pilih SKPD terlebih dahulu', ''));
        programs.filter(x => Number(x.skpd_id) === skpdId).forEach(x => { const option = new Option(x.label, String(x.id)); option.dataset.ralId = x.ral_id || ''; program.add(option); });
        program.disabled = !skpdId; if (selected) program.value = String(selected);
    };
    const fill = record => {
        fields.forEach(name => { const input = field(name); if (input) input.value = record[name] || ''; });
        refreshPrograms(record.id_program || '');
        [...dab.options].forEach(option => option.selected = (record.id_dab || []).map(Number).includes(Number(option.value)));
    };
    skpd.addEventListener('change', () => refreshPrograms(''));
    program.addEventListener('change', () => { const id = program.selectedOptions[0]?.dataset.ralId; if (id) ral.value = id; });
    document.querySelector('[data-dal-add]')?.addEventListener('click', () => { fill({id_dab: []}); formModal.querySelector('[data-dal-action]').value='create'; formModal.querySelector('[data-dal-form-title]').textContent='Tambah Data DAL'; formModal.querySelector('[data-dal-submit-label]').textContent='Simpan Data'; open(formModal); });
    document.querySelectorAll('[data-dal-edit]').forEach(button => button.addEventListener('click', () => { fill(JSON.parse(button.dataset.record || '{}')); formModal.querySelector('[data-dal-action]').value='update'; formModal.querySelector('[data-dal-form-title]').textContent='Edit Data DAL'; formModal.querySelector('[data-dal-submit-label]').textContent='Simpan Perubahan'; open(formModal); }));
    document.querySelectorAll('[data-dal-view]').forEach(button => button.addEventListener('click', () => { const record=JSON.parse(button.dataset.record || '{}'); viewModal.querySelectorAll('[data-view-field]').forEach(el => el.textContent=record[el.dataset.viewField] || '—'); open(viewModal); }));
    document.querySelectorAll('[data-dal-delete]').forEach(button => button.addEventListener('click', () => { deleteModal.querySelector('[data-delete-id]').value=button.dataset.id; deleteModal.querySelector('[data-delete-name]').textContent=button.dataset.name; open(deleteModal); }));
    document.querySelectorAll('[data-modal-close]').forEach(button => button.addEventListener('click', () => close(button.closest('[data-modal]'))));
    modals.forEach(modal => modal.addEventListener('click', event => { if (event.target === modal) close(modal); }));
    document.addEventListener('keydown', event => { if (event.key === 'Escape') modals.forEach(close); });
    refreshPrograms(posted.id_program || '');
    <?php if ($openFormModal): ?>fill(posted); formModal.querySelector('[data-dal-action]').value=<?= json_encode($formMode) ?>; formModal.querySelector('[data-dal-form-title]').textContent=<?= json_encode($formMode === 'update' ? 'Edit Data DAL' : 'Tambah Data DAL') ?>; open(formModal);<?php endif; ?>
})();
</script>
