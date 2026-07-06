<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_login();

function print_peta_int(mixed $value): ?int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id ? (int) $id : null;
}

function print_peta_label(mixed $code, mixed $name): string
{
    $code = trim((string) $code);
    $name = trim((string) $name);
    return trim($code . ($code !== '' && $name !== '' ? ' - ' : '') . $name);
}

$years = [2026, 2027, 2028, 2029, 2030];
$search = trim((string) ($_GET['q'] ?? ''));
$filterSkpd = print_peta_int($_GET['id_skpd'] ?? null);
$filterYear = (int) ($_GET['tahun'] ?? 0);
if (!in_array($filterYear, $years, true)) $filterYear = null;
$conditions = [];
$params = [];
if ($search !== '') {
    $conditions[] = "CONCAT_WS(' ',pr.nama_rencana,pr.keterangan,a.nama_aspek,i.nama_indikator,s.kode_skpd,s.nama_skpd,p.kode_program,p.nama_program) LIKE :search";
    $params['search'] = '%' . $search . '%';
}
if ($filterSkpd) {
    $conditions[] = 'pr.id_skpd=:id_skpd';
    $params['id_skpd'] = $filterSkpd;
}
if ($filterYear) $conditions[] = "NULLIF(TRIM(pr.target_{$filterYear}),'') IS NOT NULL";
$where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
$stmt = db()->prepare(
    'SELECT pr.*,a.nama_aspek,i.nama_indikator,s.kode_skpd,s.nama_skpd,p.kode_program,p.nama_program
     FROM peta_rencana pr
     INNER JOIN aspek a ON a.id=pr.id_aspek
     INNER JOIN indikator i ON i.id=pr.id_indikator
     INNER JOIN skpd s ON s.id=pr.id_skpd
     INNER JOIN program p ON p.id=pr.id_program'
    . $where . ' ORDER BY pr.id_aspek,pr.id_indikator,pr.no_urut,pr.id'
);
$stmt->execute($params);
$rows = $stmt->fetchAll();
$backUrl = 'index.php?' . http_build_query(array_filter([
    'page' => 'peta-rencana',
    'q' => $search,
    'id_skpd' => $filterSkpd,
    'tahun' => $filterYear,
], static fn(mixed $value): bool => $value !== null && $value !== ''));
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Peta Rencana - <?= e(APP_NAME) ?></title>
    <style>
        @page { size: A4 landscape; margin: 8mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111827; font-family: Arial, Helvetica, sans-serif; font-size: 7px; }
        .toolbar { display: flex; justify-content: flex-end; gap: 8px; margin-bottom: 12px; }
        .toolbar button,.toolbar a { border: 1px solid #cbd5e1; border-radius: 5px; background: #fff; padding: 7px 12px; color: #334155; font-size: 11px; font-weight: 700; text-decoration: none; cursor: pointer; }
        .toolbar .primary { border-color: #1d4ed8; background: #1d4ed8; color: #fff; }
        h1 { margin: 0; text-align: center; font-size: 14px; }
        .meta { margin: 4px 0 10px; text-align: center; color: #64748b; font-size: 8px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th,td { border: 1px solid #475569; padding: 3px 4px; vertical-align: top; overflow-wrap: anywhere; line-height: 1.2; }
        th { background: #e2e8f0; text-align: center; font-size: 6.5px; }
        td { font-size: 6.5px; }
        tbody tr { break-inside: avoid; }
        .number { width: 3%; text-align: center; }
        .date { width: 5%; }
        .target { width: 6%; }
        .empty { padding: 20px; text-align: center; color: #64748b; }
        .aspect-row td { border-color: #7f1d1d; background: #991b1b; padding: 5px 6px; color: #fff; font-size: 8px; font-weight: 700; }
        .indicator-row td { background: #fef3c7; padding: 4px 6px; color: #78350f; font-size: 7px; font-weight: 700; }
        .group-code { display: inline-block; min-width: 58px; margin-right: 5px; }
        @media print { .toolbar { display: none; } body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }
    </style>
</head>
<body>
    <div class="toolbar"><a href="<?= e($backUrl) ?>">Kembali</a><button type="button" class="primary" onclick="window.print()">Cetak Sekarang</button></div>
    <h1>Peta Rencana SPBE</h1>
    <p class="meta">Jumlah data: <?= number_format(count($rows), 0, ',', '.') ?><?= $filterYear ? ' · Tahun target: ' . $filterYear : '' ?> · Dicetak <?= e(date('d/m/Y H:i')) ?></p>
    <table>
        <thead><tr><th class="number">No.</th><th>Nama Rencana</th><?php foreach ($years as $year): if ($filterYear && $filterYear !== $year) continue; ?><th class="target">Target <?= $year ?></th><?php endforeach; ?><th class="date">Tanggal Awal</th><th class="date">Tanggal Akhir</th><th>SKPD</th><th>Program</th><th>Keterangan</th></tr></thead>
        <tbody>
            <?php $columnCount = 7 + ($filterYear ? 1 : 5); ?>
            <?php if (!$rows): ?><tr><td colspan="<?= $columnCount ?>" class="empty">Belum ada peta rencana sesuai filter.</td></tr><?php endif; ?>
            <?php
            $currentAspectId = null;
            $currentIndicatorId = null;
            foreach ($rows as $row):
                $aspectId = (int) $row['id_aspek'];
                $indicatorId = (int) $row['id_indikator'];
                if ($currentAspectId !== $aspectId):
                    $currentAspectId = $aspectId;
                    $currentIndicatorId = null;
            ?>
            <tr class="aspect-row"><td colspan="<?= $columnCount ?>"><span class="group-code">Aspek <?= $aspectId ?></span><?= e($row['nama_aspek']) ?></td></tr>
            <?php endif; ?>
            <?php if ($currentIndicatorId !== $indicatorId): $currentIndicatorId = $indicatorId; ?>
            <tr class="indicator-row"><td colspan="<?= $columnCount ?>"><span class="group-code">Indikator <?= $indicatorId ?></span><?= e($row['nama_indikator']) ?></td></tr>
            <?php endif; ?>
            <tr><td class="number"><?= (int) $row['no_urut'] ?></td><td><?= e($row['nama_rencana']) ?></td><?php foreach ($years as $year): if ($filterYear && $filterYear !== $year) continue; ?><td><?= e($row['target_' . $year] ?? '') ?></td><?php endforeach; ?><td><?= e($row['tanggal_awal'] ?? '') ?></td><td><?= e($row['tanggal_akhir'] ?? '') ?></td><td><?= e(print_peta_label($row['kode_skpd'], $row['nama_skpd'])) ?></td><td><?= e(print_peta_label($row['kode_program'], $row['nama_program'])) ?></td><td><?= e($row['keterangan'] ?? '') ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
