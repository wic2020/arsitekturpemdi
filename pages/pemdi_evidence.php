<?php

declare(strict_types=1);

const PEMDI_EVIDENCE_MAX_FILE_SIZE = 10 * 1024 * 1024;

function evidence_int(mixed $value): ?int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id ? (int) $id : null;
}

function evidence_level_label(array $row): string
{
    return trim((string) ($row['nama_indikator'] ?? '')) . ' - Level ' . (int) ($row['level'] ?? 0);
}

function evidence_page_url(int $number, string $search, int $perPage, ?int $indicatorId): string
{
    return 'index.php?' . http_build_query(array_filter([
        'page' => 'pemdi-evidence',
        'q' => $search,
        'id_indikator' => $indicatorId,
        'per_page' => $perPage,
        'p' => $number,
    ], static fn(mixed $value): bool => $value !== null && $value !== ''));
}

function evidence_status_label(string $status): string
{
    return $status === 'sudah_diunggah' ? 'Sudah Diunggah' : 'Belum Diunggah';
}

function evidence_file_path(?string $storedPath): ?string
{
    if ($storedPath === null || trim($storedPath) === '') return null;
    $uploadRoot = realpath(dirname(__DIR__) . '/uploads/pemdi_evidence');
    $candidate = realpath(dirname(__DIR__) . '/' . ltrim(str_replace('\\', '/', $storedPath), '/'));
    if ($uploadRoot === false || $candidate === false) return null;
    $prefix = rtrim(str_replace('\\', '/', $uploadRoot), '/') . '/';
    $normalized = str_replace('\\', '/', $candidate);
    return str_starts_with($normalized, $prefix) ? $candidate : null;
}

function evidence_level_upload_unlocked(int $levelId): bool
{
    $stmt = db()->prepare(
        'SELECT NOT EXISTS (
            SELECT 1
            FROM pemdi_level previous_level
            INNER JOIN pemdi_evidence previous_evidence
                ON previous_evidence.id_pemdi_level=previous_level.id
            WHERE previous_level.id_indikator=current_level.id_indikator
                AND previous_level.level<current_level.level
                AND (
                    previous_evidence.status_upload<>"sudah_diunggah"
                    OR previous_evidence.file_upload IS NULL
                    OR TRIM(previous_evidence.file_upload)=""
                )
        )
        FROM pemdi_level current_level
        WHERE current_level.id=:id'
    );
    $stmt->execute(['id' => $levelId]);
    return (bool) $stmt->fetchColumn();
}

function evidence_receive_pdf(array $file, array &$errors): ?array
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) return null;
    if ($error !== UPLOAD_ERR_OK) {
        $errors[] = in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            ? 'Ukuran PDF melebihi batas unggah.'
            : 'PDF gagal diunggah. Silakan coba kembali.';
        return null;
    }

    $temporaryPath = (string) ($file['tmp_name'] ?? '');
    $originalName = (string) ($file['name'] ?? '');
    $size = (int) ($file['size'] ?? 0);
    if ($size < 1 || $size > PEMDI_EVIDENCE_MAX_FILE_SIZE) {
        $errors[] = 'PDF wajib berukuran maksimal 10 MB.';
        return null;
    }
    if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'pdf') {
        $errors[] = 'File evidence harus berformat PDF.';
        return null;
    }

    $signature = @file_get_contents($temporaryPath, false, null, 0, 5);
    $mime = class_exists('finfo') ? (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath) : null;
    if ($signature !== '%PDF-' || ($mime !== null && !in_array($mime, ['application/pdf', 'application/x-pdf'], true))) {
        $errors[] = 'Isi file tidak dikenali sebagai PDF yang valid.';
        return null;
    }

    $directory = dirname(__DIR__) . '/uploads/pemdi_evidence';
    if (!is_dir($directory) || !is_writable($directory)) {
        $errors[] = 'Direktori penyimpanan evidence tidak tersedia.';
        return null;
    }
    $filename = bin2hex(random_bytes(20)) . '.pdf';
    $absolutePath = $directory . '/' . $filename;
    if (!move_uploaded_file($temporaryPath, $absolutePath)) {
        $errors[] = 'PDF gagal disimpan.';
        return null;
    }
    return [
        'stored' => 'uploads/pemdi_evidence/' . $filename,
        'absolute' => $absolutePath,
    ];
}

$levelList = db()->query(
    'SELECT l.id,l.level,l.deskripsi,l.kriteria,i.id id_indikator,i.nama_indikator,
        i.deskripsi_indikator,i.koordinator,i.bobot,a.nama_aspek,s.kode_skpd,s.nama_skpd
     FROM pemdi_level l
     INNER JOIN indikator i ON i.id=l.id_indikator
     INNER JOIN aspek a ON a.id=i.id_aspek
     LEFT JOIN skpd s ON s.id=i.id_skpd
     ORDER BY i.id,l.level,l.id'
)->fetchAll();
$levelMap = [];
foreach ($levelList as $level) {
    $levelMap[(int) $level['id']] = $level;
}
$indicatorRows = db()->query(
    'SELECT i.id,i.nama_indikator,i.deskripsi_indikator,i.koordinator,i.bobot,
        a.nama_aspek,s.kode_skpd,s.nama_skpd
     FROM indikator i
     INNER JOIN aspek a ON a.id=i.id_aspek
     LEFT JOIN skpd s ON s.id=i.id_skpd
     ORDER BY i.id'
)->fetchAll();
$indicatorOptions = [];
foreach ($indicatorRows as $indicator) $indicatorOptions[(int) $indicator['id']] = $indicator;
$contextIndicatorId = evidence_int($_GET['id_indikator'] ?? null);
if ($contextIndicatorId && !isset($indicatorOptions[$contextIndicatorId])) $contextIndicatorId = null;
$contextIndicator = $contextIndicatorId ? $indicatorOptions[$contextIndicatorId] : null;
$returnUrl = 'index.php?' . http_build_query(array_filter([
    'page' => 'pemdi-evidence',
    'id_indikator' => $contextIndicatorId,
], static fn(mixed $value): bool => $value !== null));
$formErrors = [];
$openFormModal = false;
$formMode = 'create';
$formState = ['id' => '', 'id_pemdi_level' => '', 'nama_dokumen' => '', 'skor' => '', 'penjelasan' => '', 'file_upload' => '', 'file_upload_unlocked' => true];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if (in_array($action, ['create', 'update'], true)) {
        $formMode = $action;
        $openFormModal = true;
        $recordId = evidence_int($_POST['id'] ?? null);
        $formState = [
            'id' => $recordId ?: '',
            'id_pemdi_level' => trim((string) ($_POST['id_pemdi_level'] ?? '')),
            'nama_dokumen' => trim((string) ($_POST['nama_dokumen'] ?? '')),
            'skor' => trim((string) ($_POST['skor'] ?? '')),
            'penjelasan' => trim((string) ($_POST['penjelasan'] ?? '')),
            'file_upload' => '',
            'file_upload_unlocked' => true,
        ];
        $levelId = evidence_int($formState['id_pemdi_level']);
        $removeFile = isset($_POST['remove_file']) && $_POST['remove_file'] === '1';

        if (!$levelId || !isset($levelMap[$levelId])) $formErrors[] = 'Indikator dan level wajib dipilih.';
        if ($contextIndicatorId && $levelId && isset($levelMap[$levelId])
            && (int) $levelMap[$levelId]['id_indikator'] !== $contextIndicatorId) {
            $formErrors[] = 'Level tidak sesuai dengan indikator terpilih.';
        }
        if ($formState['nama_dokumen'] === '') {
            $formErrors[] = 'Nama dokumen wajib diisi.';
        } elseif (mb_strlen($formState['nama_dokumen']) > 255) {
            $formErrors[] = 'Nama dokumen terlalu panjang.';
        }
        if ($formState['skor'] !== '' && (!is_numeric($formState['skor'])
            || (float) $formState['skor'] < 0 || (float) $formState['skor'] > 100)) {
            $formErrors[] = 'Skor harus berupa angka desimal antara 0 sampai 100.';
        }
        if ($action === 'update' && !$recordId) $formErrors[] = 'ID evidence tidak valid.';

        $oldValues = null;
        if (!$formErrors && $action === 'update') {
            $stmt = db()->prepare('SELECT * FROM pemdi_evidence WHERE id=:id');
            $stmt->execute(['id' => $recordId]);
            $oldValues = $stmt->fetch();
            if (!$oldValues) {
                $formErrors[] = 'Evidence yang akan diubah tidak ditemukan.';
            } else {
                if ($contextIndicatorId) {
                    $formState['id_pemdi_level'] = (string) $oldValues['id_pemdi_level'];
                    $formState['nama_dokumen'] = (string) $oldValues['nama_dokumen'];
                    $formState['skor'] = $oldValues['skor'] !== null ? (string) $oldValues['skor'] : '';
                    $levelId = (int) $oldValues['id_pemdi_level'];
                }
                $formState['file_upload'] = (string) ($oldValues['file_upload'] ?? '');
                $oldLevel = $levelMap[(int) $oldValues['id_pemdi_level']] ?? null;
                $formState['file_upload_unlocked'] = ($user['role'] ?? '') !== 'user'
                    || evidence_level_upload_unlocked((int) $oldValues['id_pemdi_level']);
                if ($contextIndicatorId && (!$oldLevel || (int) $oldLevel['id_indikator'] !== $contextIndicatorId)) {
                    $formErrors[] = 'Evidence tidak termasuk dalam indikator terpilih.';
                }
                $fileChangeRequested = $removeFile
                    || (int) ($_FILES['file_upload']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
                if (($user['role'] ?? '') === 'user'
                    && $fileChangeRequested
                    && !$formState['file_upload_unlocked']) {
                    $formErrors[] = 'Unggah atau hapus file level ini dikunci. Lengkapi seluruh bukti dukung pada level sebelumnya terlebih dahulu.';
                }
            }
        }

        $uploadedFile = null;
        if (!$formErrors) $uploadedFile = evidence_receive_pdf($_FILES['file_upload'] ?? [], $formErrors);
        if (!$formErrors) {
            $oldStoredPath = $oldValues['file_upload'] ?? null;
            $storedPath = $uploadedFile['stored'] ?? ($removeFile ? null : $oldStoredPath);
            $params = [
                'id_pemdi_level' => $levelId,
                'nama_dokumen' => $formState['nama_dokumen'],
                'skor' => $formState['skor'] !== '' ? (float) $formState['skor'] : null,
                'penjelasan' => $formState['penjelasan'] !== '' ? $formState['penjelasan'] : null,
                'file_upload' => $storedPath,
                'status_upload' => $storedPath ? 'sudah_diunggah' : 'belum_diunggah',
                'updated_by' => (int) $user['id'],
            ];

            db()->beginTransaction();
            try {
                if ($action === 'create') {
                    $params['created_by'] = (int) $user['id'];
                    $columns = array_keys($params);
                    db()->prepare(
                        'INSERT INTO pemdi_evidence (' . implode(',', $columns) . ') VALUES (:' . implode(',:', $columns) . ')'
                    )->execute($params);
                    $recordId = (int) db()->lastInsertId();
                } else {
                    $params['id'] = $recordId;
                    $sets = [];
                    foreach (array_keys($params) as $field) if ($field !== 'id') $sets[] = "{$field}=:{$field}";
                    db()->prepare('UPDATE pemdi_evidence SET ' . implode(',', $sets) . ' WHERE id=:id')->execute($params);
                }
                $stmt = db()->prepare('SELECT * FROM pemdi_evidence WHERE id=:id');
                $stmt->execute(['id' => $recordId]);
                $newValues = $stmt->fetch() ?: $params;
                audit_log(
                    (int) $user['id'],
                    $action,
                    'pemdi_evidence',
                    $recordId,
                    ($action === 'create' ? 'Menambahkan ' : 'Mengubah ') . 'PEMDI Evidence: ' . $formState['nama_dokumen'],
                    $oldValues,
                    $newValues,
                    true
                );
                db()->commit();

                if ($oldStoredPath && $oldStoredPath !== $storedPath) {
                    $oldFile = evidence_file_path((string) $oldStoredPath);
                    if ($oldFile !== null && is_file($oldFile)) @unlink($oldFile);
                }
                set_flash('success', $action === 'create' ? 'Evidence berhasil ditambahkan.' : 'Evidence berhasil diperbarui.');
                redirect($returnUrl);
            } catch (Throwable $exception) {
                db()->rollBack();
                if ($uploadedFile && is_file($uploadedFile['absolute'])) @unlink($uploadedFile['absolute']);
                error_log('Penyimpanan PEMDI evidence gagal: ' . $exception->getMessage());
                $formErrors[] = 'Evidence gagal disimpan. Silakan coba kembali.';
            }
        } elseif ($uploadedFile && is_file($uploadedFile['absolute'])) {
            @unlink($uploadedFile['absolute']);
        }
    } elseif ($action === 'delete') {
        $recordId = evidence_int($_POST['id'] ?? null);
        $stmt = db()->prepare('SELECT * FROM pemdi_evidence WHERE id=:id');
        $stmt->execute(['id' => $recordId]);
        $record = $stmt->fetch();
        if (!$recordId || !$record) {
            set_flash('error', 'Evidence tidak ditemukan.');
            redirect($returnUrl);
        }
        $recordLevel = $levelMap[(int) $record['id_pemdi_level']] ?? null;
        if ($contextIndicatorId && (!$recordLevel || (int) $recordLevel['id_indikator'] !== $contextIndicatorId)) {
            set_flash('error', 'Evidence tidak termasuk dalam indikator terpilih.');
            redirect($returnUrl);
        }

        db()->beginTransaction();
        try {
            db()->prepare('DELETE FROM pemdi_evidence WHERE id=:id')->execute(['id' => $recordId]);
            audit_log(
                (int) $user['id'],
                'delete',
                'pemdi_evidence',
                $recordId,
                'Menghapus PEMDI Evidence: ' . $record['nama_dokumen'],
                $record,
                null,
                true
            );
            db()->commit();
            $file = evidence_file_path((string) ($record['file_upload'] ?? ''));
            if ($file !== null && is_file($file)) @unlink($file);
            set_flash('success', 'Evidence berhasil dihapus.');
        } catch (Throwable $exception) {
            db()->rollBack();
            error_log('Penghapusan PEMDI evidence gagal: ' . $exception->getMessage());
            set_flash('error', 'Evidence gagal dihapus.');
        }
        redirect($returnUrl);
    }
}

$search = trim((string) ($_GET['q'] ?? ''));
$filterIndicator = $contextIndicatorId;
$selectedIndicator = $contextIndicator;
$selectedStats = ['maximum_score' => 0, 'achieved_score' => 0, 'evidence_count' => 0, 'uploaded_count' => 0];
if ($filterIndicator) {
    $stmt = db()->prepare(
        'SELECT COALESCE(SUM(e.skor),0) maximum_score,
            COALESCE(SUM(CASE
                WHEN e.status_upload="sudah_diunggah"
                    AND e.file_upload IS NOT NULL
                    AND TRIM(e.file_upload)<>""
                THEN e.skor ELSE 0 END),0) achieved_score,
            COUNT(*) evidence_count,
            SUM(CASE
                WHEN e.status_upload="sudah_diunggah"
                    AND e.file_upload IS NOT NULL
                    AND TRIM(e.file_upload)<>""
                THEN 1 ELSE 0 END) uploaded_count
         FROM pemdi_evidence e
         INNER JOIN pemdi_level l ON l.id=e.id_pemdi_level
         WHERE l.id_indikator=:id'
    );
    $stmt->execute(['id' => $filterIndicator]);
    $selectedStats = $stmt->fetch() ?: $selectedStats;
}
$addEvidenceDisabled = $selectedIndicator !== null || !$levelList;
$addEvidenceTitle = $selectedIndicator
    ? 'Penambahan evidence dinonaktifkan untuk indikator terpilih'
    : 'Belum ada level indikator';
$perPageOptions = [10, 20, 25, 50];
$perPage = (int) ($_GET['per_page'] ?? 20);
if (!in_array($perPage, $perPageOptions, true)) $perPage = 20;
$currentPage = max(1, (int) ($_GET['p'] ?? 1));
$conditions = [];
$queryParams = [];
if ($search !== '') {
    $conditions[] = "CONCAT_WS(' ',e.nama_dokumen,e.penjelasan,e.status_upload,i.nama_indikator,l.level,l.deskripsi,l.kriteria) LIKE :search";
    $queryParams['search'] = '%' . $search . '%';
}
if ($filterIndicator) {
    $conditions[] = 'i.id=:filter_indikator';
    $queryParams['filter_indikator'] = $filterIndicator;
}
$whereSql = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
$joins = ' FROM pemdi_evidence e
    INNER JOIN pemdi_level l ON l.id=e.id_pemdi_level
    INNER JOIN indikator i ON i.id=l.id_indikator
    INNER JOIN aspek a ON a.id=i.id_aspek
    LEFT JOIN skpd s ON s.id=i.id_skpd';
$stmt = db()->prepare('SELECT COUNT(*)' . $joins . $whereSql);
$stmt->execute($queryParams);
$totalRows = (int) $stmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $perPage;
$orderSql = $filterIndicator ? 'l.level ASC,e.id ASC' : 'e.id ASC';
$stmt = db()->prepare(
    'SELECT e.*,l.level,l.deskripsi level_deskripsi,l.kriteria,i.id id_indikator,i.nama_indikator,
        a.nama_aspek,s.kode_skpd,s.nama_skpd'
    . $joins . $whereSql . " ORDER BY {$orderSql} LIMIT :limit OFFSET :offset"
);
foreach ($queryParams as $key => $value) $stmt->bindValue(':' . $key, $value);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();
$levelUploadUnlocked = [];
$levelUnlockStmt = db()->query(
    'SELECT current_level.id,
        NOT EXISTS (
            SELECT 1
            FROM pemdi_level previous_level
            INNER JOIN pemdi_evidence previous_evidence
                ON previous_evidence.id_pemdi_level=previous_level.id
            WHERE previous_level.id_indikator=current_level.id_indikator
                AND previous_level.level<current_level.level
                AND (
                    previous_evidence.status_upload<>"sudah_diunggah"
                    OR previous_evidence.file_upload IS NULL
                    OR TRIM(previous_evidence.file_upload)=""
                )
        ) upload_unlocked
     FROM pemdi_level current_level'
);
foreach ($levelUnlockStmt->fetchAll() as $levelUnlock) {
    $levelUploadUnlocked[(int) $levelUnlock['id']] = (bool) $levelUnlock['upload_unlocked'];
}
$startRow = $totalRows ? $offset + 1 : 0;
$endRow = min($offset + $perPage, $totalRows);
?>

<section class="space-y-5">
    <div
        class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-red-700">Peta Rencana</p>
            <h1 class="mt-1 text-xl font-bold">PEMDI Evidence</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola dokumen evidence, skor, dan status unggah untuk setiap level
                indikator.</p>
        </div>
        <button type="button"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800 disabled:cursor-not-allowed disabled:bg-slate-300"
            data-evidence-add <?= $addEvidenceDisabled ? 'disabled aria-disabled="true" title="' . e($addEvidenceTitle) . '"' : '' ?>><i
                data-lucide="plus" class="h-4 w-4"></i>Tambah Evidence</button>
    </div>

    <?php if ($formErrors): ?><div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        <p class="font-semibold">Evidence belum bisa disimpan.</p>
        <ul class="mt-1 list-disc pl-5"><?php foreach ($formErrors as $error): ?><li><?= e($error) ?></li>
            <?php endforeach; ?></ul>
    </div><?php endif; ?>

    <?php if ($selectedIndicator): ?>
    <div class="rounded-lg border border-amber-200 bg-white p-5 shadow-sm">
        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-start">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase text-amber-700">Bukti Dukung Indikator</p>
                <h2 class="mt-1 text-lg font-bold"><?= e($selectedIndicator['nama_indikator']) ?></h2>
                <p class="mt-1 text-sm text-slate-500"><?= e($selectedIndicator['nama_aspek']) ?></p>
                <?php if ($selectedIndicator['deskripsi_indikator'] !== ''): ?><p
                    class="mt-3 max-w-3xl text-sm leading-relaxed text-slate-600">
                    <?= e($selectedIndicator['deskripsi_indikator']) ?></p><?php endif; ?>
            </div>
            <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                <div class="rounded-md bg-amber-50 px-4 py-3"><span
                        class="block text-[10px] font-semibold uppercase text-amber-700">Skor Maksimal</span><strong
                        class="mt-1 block text-lg text-amber-800"><?= e(number_format((float) $selectedStats['maximum_score'], 2, ',', '.')) ?></strong>
                </div>
                <div class="rounded-md bg-red-50 px-4 py-3"><span
                        class="block text-[10px] font-semibold uppercase text-red-700">Capaian Skor</span><strong
                        class="mt-1 block text-lg text-red-800"><?= e(number_format((float) $selectedStats['achieved_score'], 2, ',', '.')) ?></strong>
                </div>
                <div class="rounded-md bg-slate-50 px-4 py-3"><span
                        class="block text-[10px] font-semibold uppercase text-slate-500">Evidence</span><strong
                        class="mt-1 block text-lg"><?= (int) $selectedStats['evidence_count'] ?></strong></div>
                <div class="rounded-md bg-slate-50 px-4 py-3"><span
                        class="block text-[10px] font-semibold uppercase text-slate-500">Terunggah</span><strong
                        class="mt-1 block text-lg"><?= (int) $selectedStats['uploaded_count'] ?></strong></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b p-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="font-semibold">Daftar Evidence</h2>
                <p class="mt-1 text-sm text-slate-500"><?= number_format($totalRows, 0, ',', '.') ?> dokumen.</p>
            </div>
            <form method="get" class="flex flex-col gap-2 sm:flex-row">
                <input type="hidden" name="page" value="pemdi-evidence">
                <div class="relative"><i data-lucide="search"
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i><input
                        type="search" name="q" value="<?= e($search) ?>" placeholder="Cari dokumen atau indikator..."
                        class="evidence-control pl-9 sm:w-64"></div>
                <select name="id_indikator" class="evidence-control sm:w-56">
                    <option value="">Semua Indikator</option>
                    <?php foreach ($indicatorOptions as $indicatorId => $indicator): ?><option
                        value="<?= $indicatorId ?>" <?= $filterIndicator === $indicatorId ? 'selected' : '' ?>>
                        <?= e($indicator['nama_indikator']) ?></option><?php endforeach; ?>
                </select>
                <select name="per_page" class="evidence-control sm:w-28"><?php foreach ($perPageOptions as $option): ?>
                    <option value="<?= $option ?>" <?= $perPage === $option ? 'selected' : '' ?>><?= $option ?> data
                    </option><?php endforeach; ?></select>
                <button
                    class="rounded-md border border-slate-300 px-3 py-2 text-xs font-semibold hover:bg-slate-50">Tampilkan</button>
                <a href="index.php?page=pemdi-evidence"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-md text-red-700 hover:bg-red-50"
                    title="Reset"><i data-lucide="rotate-ccw" class="h-4 w-4"></i></a>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <colgroup>
                    <col style="width:3.5rem">
                </colgroup>
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="w-14 min-w-14 max-w-14 px-3 py-3">No</th>
                        <th class="px-3 py-3">Dokumen</th><?php if (!$selectedIndicator): ?><th class="px-3 py-3">
                            Indikator - Level</th>
                        <th class="px-3 py-3">Skor</th><?php endif; ?><th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3">Unduh</th>
                        <th class="px-3 py-3">Penjelasan</th>
                        <th class="px-3 py-3">Capaian Skor</th>
                        <th class="px-3 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!$rows): ?><tr>
                        <td colspan="<?= $selectedIndicator ? 7 : 9 ?>" class="p-10 text-center text-sm text-slate-500">
                            Belum ada evidence.</td>
                    </tr><?php endif; ?>
                    <?php $currentLevelId = null; foreach ($rows as $index => $row):
                        $record = [
                            'id' => (int) $row['id'],
                            'id_pemdi_level' => (int) $row['id_pemdi_level'],
                            'nama_dokumen' => (string) $row['nama_dokumen'],
                            'skor' => $row['skor'] !== null ? (string) $row['skor'] : '',
                            'penjelasan' => (string) ($row['penjelasan'] ?? ''),
                            'file_upload' => (string) ($row['file_upload'] ?? ''),
                            'status_label' => evidence_status_label((string) $row['status_upload']),
                            'level_label' => evidence_level_label($row),
                            'aspek_label' => (string) $row['nama_aspek'],
                            'level_deskripsi' => (string) ($row['level_deskripsi'] ?? ''),
                            'kriteria' => (string) ($row['kriteria'] ?? ''),
                            'file_upload_unlocked' => ($user['role'] ?? '') !== 'user'
                                || ($levelUploadUnlocked[(int) $row['id_pemdi_level']] ?? false),
                        ];
                        $hasUploadedFile = $row['status_upload'] === 'sudah_diunggah'
                            && trim((string) ($row['file_upload'] ?? '')) !== '';
                        $achievedScore = $hasUploadedFile && $row['skor'] !== null ? (float) $row['skor'] : 0.0;
                    ?>
                    <?php if ($selectedIndicator && $currentLevelId !== (int) $row['id_pemdi_level']): $currentLevelId = (int) $row['id_pemdi_level']; ?>
                    <tr class="bg-slate-100/90">
                        <!-- <td class="w-14 min-w-14 max-w-14 border-y border-slate-200 p-0"></td> -->
                        <td colspan="7" class="border-y border-slate-200 px-3 py-3">
                            <div class="min-w-0 space-y-2">
                                <span class="inline-flex min-h-7 items-center rounded-md bg-amber-100 px-2.5 py-1 font-bold text-amber-800">
                                    Level <?= (int) $row['level'] ?><?= $row['level_deskripsi'] ? ' (' . e($row['level_deskripsi']) . ')' : '' ?>
                                </span>
                                <p class="whitespace-normal break-words text-xs leading-relaxed text-slate-600">
                                    <b class="text-slate-700">Kriteria:</b> <?= e($row['kriteria'] ?: '-') ?>
                                </p>
                                <?php if (($user['role'] ?? '') === 'user' && !$record['file_upload_unlocked']): ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr class="align-top hover:bg-slate-50/70">
                        <td class="w-14 min-w-14 max-w-14 px-3 py-3 text-slate-500"><?= $offset + $index + 1 ?></td>
                        <td class="max-w-[260px] whitespace-normal px-3 py-3 font-semibold text-slate-900">
                            <?= e($row['nama_dokumen']) ?>
                            <?php if ($row['skor'] !== null): ?>
                                <span class="ml-1 whitespace-nowrap text-red-700">(<?= e(number_format((float) $row['skor'], 2, ',', '.')) ?>)</span>
                            <?php endif; ?>
                        </td>
                        <?php if (!$selectedIndicator): ?><td class="max-w-[300px] whitespace-normal px-3 py-3">
                            <p class="font-semibold"><?= e(evidence_level_label($row)) ?></p>
                            <p class="mt-1 text-[11px] text-slate-500"><?= e($row['nama_aspek']) ?></p>
                        </td>
                        <td class="px-3 py-3 font-semibold">
                            <?= $row['skor'] !== null ? e(number_format((float) $row['skor'], 2, ',', '.')) : '-' ?>
                        </td><?php endif; ?>
                        <td class="px-3 py-3"><span
                            class="inline-flex rounded-full px-2 py-1 text-[11px] font-semibold <?= $row['status_upload'] === 'sudah_diunggah' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-50 text-red-600' ?>"><?= e(evidence_status_label((string) $row['status_upload'])) ?></span>
                        </td>
                        <td class="px-3 py-3">
                            <?php if ($row['file_upload']): ?><a
                                href="download_pemdi_evidence.php?id=<?= (int) $row['id'] ?>"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-blue-200 bg-blue-50 text-blue-700"
                                title="Unduh PDF"><i data-lucide="download"
                                    class="h-4 w-4"></i></a><?php else: ?>-<?php endif; ?>
                        </td>
                        <td class="max-w-[300px] whitespace-normal px-3 py-3 text-slate-600">
                            <?= e($row['penjelasan'] ?: '-') ?>
                        </td>
                        <td class="whitespace-nowrap px-3 py-3 font-bold text-red-700">
                            <?= e(number_format($achievedScore, 2, ',', '.')) ?>
                        </td>
                        <td class="px-3 py-3">
                            <div class="flex justify-end gap-1.5"><button type="button" class="evidence-action"
                                    title="Lihat" data-evidence-view
                                    data-record="<?= e(json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"><i
                                        data-lucide="eye"></i></button><button type="button"
                                    class="evidence-action border-blue-200 bg-blue-50 text-blue-700" title="Edit"
                                    data-evidence-edit
                                    data-record="<?= e(json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"><i
                                        data-lucide="pencil"></i></button><button type="button"
                                    class="evidence-action border-red-200 bg-red-50 text-red-700" title="Hapus"
                                    data-evidence-delete data-id="<?= (int) $row['id'] ?>"
                                    data-name="<?= e($row['nama_dokumen']) ?>"><i data-lucide="trash-2"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-3 border-t p-4 text-sm text-slate-500">
            <p>Menampilkan <?= $startRow ?>-<?= $endRow ?> dari <?= $totalRows ?> data</p>
            <?php render_numbered_pagination($currentPage, $totalPages, static fn(int $number): string => evidence_page_url($number, $search, $perPage, $filterIndicator)); ?>
        </div>
    </div>
</section>

<style>
.evidence-control {
    height: 2.25rem;
    border: 1px solid #cbd5e1;
    border-radius: .375rem;
    background: #fff;
    padding: .45rem .7rem;
    font-size: .75rem;
    outline: none
}

.evidence-control:focus {
    border-color: #dc2626;
    box-shadow: 0 0 0 3px rgb(254 226 226/.7)
}

.evidence-textarea {
    height: auto;
    line-height: 1.55;
    padding: .75rem;
}

.dropify-wrapper {
    border: 2px dashed #cbd5e1;
    border-radius: .75rem;
    background: #fff;
    color: #334155;
    transition: border-color .15s, background-color .15s, opacity .15s;
}

.dropify-wrapper:hover {
    border-color: #fca5a5;
    background: #fffafa;
}

.dropify-wrapper .dropify-message p {
    color: #475569;
    font-size: .875rem;
}

.dropify-wrapper .dropify-message span.file-icon {
    color: #b91c1c;
}

.dropify-wrapper.evidence-upload-disabled {
    pointer-events: none;
    border-color: #fcd34d;
    background: #fffbeb;
    opacity: .72;
}

.evidence-label {
    display: block;
    margin-bottom: .3rem;
    font-size: .68rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #64748b
}

.evidence-action {
    display: inline-flex;
    width: 2rem;
    height: 2rem;
    align-items: center;
    justify-content: center;
    border-width: 1px;
    border-radius: .375rem
}

.evidence-action svg {
    width: .875rem;
    height: .875rem
}
</style>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 p-4" data-modal="evidence-form">
    <form method="post" action="<?= e($returnUrl) ?>" enctype="multipart/form-data"
        class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-lg bg-white shadow-2xl">
        <?= csrf_field() ?><input type="hidden" name="action" value="<?= e($formMode) ?>" data-evidence-action><input
            type="hidden" name="id" value="<?= e($formState['id']) ?>" data-evidence-field="id">
        <header class="flex items-center justify-between border-b px-5 py-4">
            <div class="flex items-center gap-3"><span
                    class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-700 text-white"><i
                        data-lucide="file-check-2" class="h-4 w-4"></i></span>
                <h2 class="font-bold" data-evidence-title>Tambah Evidence</h2>
            </div><button type="button" data-modal-close><i data-lucide="x"></i></button>
        </header>
        <div class="grid flex-1 gap-4 overflow-y-auto bg-slate-50 p-5 md:grid-cols-2">
            <label class="md:col-span-2"><span class="evidence-label">Indikator - Level</span><select
                    name="id_pemdi_level" class="evidence-control w-full disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500" data-evidence-field="id_pemdi_level" required
                    <?= $selectedIndicator ? 'disabled' : '' ?>>
                    <option value="">Pilih Indikator - Level</option><?php foreach ($levelList as $level): if ($selectedIndicator && (int) $level['id_indikator'] !== (int) $selectedIndicator['id']) continue; ?><option
                        value="<?= (int) $level['id'] ?>"><?= e(evidence_level_label($level)) ?></option>
                    <?php endforeach; ?>
                </select><?php if ($selectedIndicator): ?><input type="hidden" name="id_pemdi_level"
                    data-evidence-level-hidden><?php endif; ?></label>
            <label class="md:col-span-2"><span class="evidence-label">Nama Dokumen</span><textarea name="nama_dokumen"
                    rows="3" maxlength="255" class="evidence-control evidence-textarea min-h-24 w-full resize-y read-only:cursor-not-allowed read-only:bg-slate-100 read-only:text-slate-500"
                    data-evidence-field="nama_dokumen" placeholder="Tuliskan nama dokumen bukti dukung secara jelas"
                    required <?= $selectedIndicator ? 'readonly' : '' ?>></textarea></label>
            <label><span class="evidence-label">Skor</span><input name="skor" type="number" min="0" max="100"
                    step="0.01" class="evidence-control w-full read-only:cursor-not-allowed read-only:bg-slate-100 read-only:text-slate-500" data-evidence-field="skor"
                    <?= $selectedIndicator ? 'readonly' : '' ?>></label>
            <label class="md:col-span-2"><span class="evidence-label">Penjelasan</span><textarea name="penjelasan"
                    rows="7" class="evidence-control evidence-textarea min-h-44 w-full resize-y"
                    data-evidence-field="penjelasan"
                    placeholder="Jelaskan isi, relevansi, dan konteks bukti dukung yang diunggah"></textarea></label>
            <div class="md:col-span-2">
                <span class="evidence-label">Unggah PDF</span>
                <input name="file_upload" type="file" accept="application/pdf,.pdf"
                    class="dropify" data-evidence-file data-height="150"
                    data-allowed-file-extensions="pdf" data-max-file-size="10M">
                <div class="mt-2 hidden rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-700"
                    data-current-file>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <span class="inline-flex items-center gap-2 font-semibold"><i data-lucide="file-check-2" class="h-4 w-4"></i>PDF saat ini tersedia.</span>
                        <label class="inline-flex items-center gap-2 font-semibold"><input type="checkbox"
                                name="remove_file" value="1">Hapus PDF</label>
                    </div>
                </div>
                <div class="mt-2 hidden rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800"
                    data-evidence-upload-lock>
                    <span class="inline-flex items-start gap-2"><i data-lucide="triangle-alert" class="mt-0.5 h-4 w-4 shrink-0"></i>
                        Unggah dokumen dinonaktifkan. Seluruh bukti dukung pada level sebelumnya harus diunggah terlebih dahulu.
                    </span>
                </div>
            </div>
        </div>
        <footer class="flex justify-end gap-2 border-t px-5 py-3"><button type="button"
                class="rounded-lg border px-4 py-2 text-sm font-semibold" data-modal-close>Batal</button><button
                class="rounded-lg bg-red-700 px-5 py-2 text-sm font-semibold text-white">Simpan Evidence</button>
        </footer>
    </form>
</div>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 p-4" data-modal="evidence-view">
    <div class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-lg bg-white">
        <header class="flex items-center justify-between border-b p-4">
            <h2 class="font-bold">Detail Evidence</h2><button type="button" data-modal-close><i
                    data-lucide="x"></i></button>
        </header>
        <div class="grid flex-1 gap-3 overflow-y-auto bg-slate-50 p-5 md:grid-cols-2">
            <?php foreach (['nama_dokumen'=>'Nama Dokumen','level_label'=>'Indikator - Level','aspek_label'=>'Aspek','skor'=>'Skor','penjelasan'=>'Penjelasan','status_label'=>'Status Upload','level_deskripsi'=>'Deskripsi Level','kriteria'=>'Kriteria'] as $field=>$label): ?>
            <div
                class="rounded-lg border bg-white p-4 <?= in_array($field, ['nama_dokumen','level_label','penjelasan','level_deskripsi','kriteria'], true) ? 'md:col-span-2' : '' ?>">
                <b class="text-xs uppercase text-slate-500"><?= e($label) ?></b>
                <p class="mt-1 whitespace-pre-line" data-view-field="<?= e($field) ?>"></p>
            </div><?php endforeach; ?><a
                class="hidden items-center justify-center gap-2 rounded-lg border border-blue-200 bg-blue-50 p-3 font-semibold text-blue-700 md:col-span-2"
                data-view-download><i data-lucide="download" class="h-4 w-4"></i>Unduh PDF</a></div>
    </div>
</div>

<div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 p-4" data-modal="evidence-delete">
    <form method="post" action="<?= e($returnUrl) ?>" class="w-full max-w-md rounded-lg bg-white p-6"><?= csrf_field() ?><input type="hidden"
            name="action" value="delete"><input type="hidden" name="id" data-delete-id>
        <h2 class="text-xl font-bold">Hapus Evidence?</h2>
        <p class="mt-2 text-sm text-slate-500">Data dan PDF terkait akan dihapus permanen.</p>
        <p class="mt-4 rounded-lg bg-slate-50 p-4 font-semibold" data-delete-name></p>
        <div class="mt-5 flex justify-end gap-2"><button type="button" class="rounded-lg border px-4 py-2"
                data-modal-close>Batal</button><button
                class="rounded-lg bg-red-700 px-4 py-2 font-semibold text-white">Ya, Hapus</button></div>
    </form>
</div>

<script>
(() => {
    const modals = document.querySelectorAll('[data-modal]'),
        form = document.querySelector('[data-modal="evidence-form"]'),
        view = document.querySelector('[data-modal="evidence-view"]'),
        del = document.querySelector('[data-modal="evidence-delete"]');
    const fields = ['id', 'id_pemdi_level', 'nama_dokumen', 'skor', 'penjelasan'],
        posted = <?= json_encode($formState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const input = name => form.querySelector(`[data-evidence-field="${name}"]`),
        fileInput = form.querySelector('[data-evidence-file]'),
        currentFile = form.querySelector('[data-current-file]'),
        removeFileInput = form.querySelector('[name="remove_file"]'),
        uploadLock = form.querySelector('[data-evidence-upload-lock]');
    let dropifyInstance = null;
    if (window.jQuery && window.jQuery.fn.dropify) {
        const dropifyElement = window.jQuery(fileInput).dropify({
            messages: {
                default: 'Pilih atau seret dokumen PDF ke area ini',
                replace: 'Pilih atau seret PDF lain untuk mengganti',
                remove: 'Hapus pilihan',
                error: 'File tidak dapat digunakan'
            },
            error: {
                fileSize: 'Ukuran file maksimal 10 MB.',
                fileExtension: 'File harus berformat PDF.'
            }
        });
        dropifyInstance = dropifyElement.data('dropify')
    }
    const open = modal => {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden')
        },
        close = modal => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            if (![...modals].some(item => !item.classList.contains('hidden'))) document.body.classList.remove(
                'overflow-hidden')
        };
    const fill = record => {
        fields.forEach(name => {
            if (input(name)) input(name).value = record[name] || ''
        });
        const hiddenLevel = form.querySelector('[data-evidence-level-hidden]');
        if (hiddenLevel) hiddenLevel.value = record.id_pemdi_level || '';
        if (dropifyInstance) {
            dropifyInstance.resetPreview();
            dropifyInstance.clearElement()
        } else {
            fileInput.value = ''
        }
        removeFileInput.checked = false;
        const fileUploadUnlocked = record.file_upload_unlocked !== false;
        fileInput.disabled = !fileUploadUnlocked;
        removeFileInput.disabled = !fileUploadUnlocked;
        uploadLock.classList.toggle('hidden', fileUploadUnlocked);
        currentFile.classList.toggle('hidden', !record.file_upload);
        const dropifyWrapper = fileInput.closest('.dropify-wrapper');
        dropifyWrapper?.classList.toggle('evidence-upload-disabled', !fileUploadUnlocked);
        dropifyWrapper?.setAttribute('aria-disabled', fileUploadUnlocked ? 'false' : 'true');
        const dropifyMessage = dropifyWrapper?.querySelector('.dropify-message p');
        if (dropifyMessage) {
            dropifyMessage.textContent = !fileUploadUnlocked
                ? 'Upload dokumen dikunci sampai level sebelumnya lengkap'
                : (record.file_upload
                    ? 'Pilih atau seret PDF pengganti ke area ini'
                    : 'Pilih atau seret dokumen PDF ke area ini')
        }
    };
    document.querySelector('[data-evidence-add]')?.addEventListener('click', () => {
        fill({});
        form.querySelector('[data-evidence-action]').value = 'create';
        form.querySelector('[data-evidence-title]').textContent = 'Tambah Evidence';
        open(form)
    });
    document.querySelectorAll('[data-evidence-edit]').forEach(button => button.addEventListener('click', () => {
        fill(JSON.parse(button.dataset.record || '{}'));
        form.querySelector('[data-evidence-action]').value = 'update';
        form.querySelector('[data-evidence-title]').textContent = 'Edit Evidence';
        open(form)
    }));
    document.querySelectorAll('[data-evidence-view]').forEach(button => button.addEventListener('click', () => {
        const record = JSON.parse(button.dataset.record || '{}');
        view.querySelectorAll('[data-view-field]').forEach(element => element.textContent = record[
            element.dataset.viewField] || '-');
        const link = view.querySelector('[data-view-download]');
        link.classList.toggle('hidden', !record.file_upload);
        link.classList.toggle('flex', !!record.file_upload);
        link.href = record.file_upload ? 'download_pemdi_evidence.php?id=' + record.id : '#';
        open(view)
    }));
    document.querySelectorAll('[data-evidence-delete]').forEach(button => button.addEventListener('click', () => {
        del.querySelector('[data-delete-id]').value = button.dataset.id;
        del.querySelector('[data-delete-name]').textContent = button.dataset.name;
        open(del)
    }));
    document.querySelectorAll('[data-modal-close]').forEach(button => button.addEventListener('click', () => close(
        button.closest('[data-modal]'))));
    <?php if ($openFormModal): ?>fill(posted);
    form.querySelector('[data-evidence-action]').value = <?= json_encode($formMode) ?>;
    form.querySelector('[data-evidence-title]').textContent =
        <?= json_encode(($formMode === 'update' ? 'Edit ' : 'Tambah ') . 'Evidence') ?>;
    open(form);
    <?php endif; ?>
})();
</script>
