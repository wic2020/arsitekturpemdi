<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_login();

$rows = db()->query(
    'SELECT
        d.id,
        d.nama_fasilitas_komputasi,
        d.bandwidth_intranet,
        d.bandwidth_internet,
        d.lokasi,
        d.status_kepemilikan,
        d.unit_pengelola,
        d.klasifikasi_tier,
        d.sistem_pengaman_fasilitas,
        r.kode_rai_1,
        r.nama_rai_1,
        r.kode_rai_2,
        r.nama_rai_2,
        r.kode_rai_3,
        r.nama_rai_3,
        r.kode_rai_4,
        r.nama_rai_4
     FROM dai_fasilitas_komputasi d
     LEFT JOIN rai r ON r.id = d.id_rai
     ORDER BY d.id ASC'
)->fetchAll();

function print_dai_facility_value(mixed $code, mixed $name): string
{
    $code = trim((string) $code);
    $name = trim((string) $name);
    return trim($code . ($code !== '' && $name !== '' ? ' - ' : '') . $name);
}

function print_dai_facility_bandwidth(mixed $value): string
{
    if ($value === null || $value === '') return '';
    return rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Fasilitas Komputasi - <?= e(APP_NAME) ?></title>
    <style>
        @page { size: A4 landscape; margin: 7mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111827; font-family: Arial, Helvetica, sans-serif; font-size: 7px; }
        .toolbar { display: flex; justify-content: flex-end; gap: 8px; margin-bottom: 12px; }
        .toolbar button, .toolbar a { border: 1px solid #cbd5e1; border-radius: 5px; background: #fff; padding: 7px 12px; color: #334155; font-size: 11px; font-weight: 700; text-decoration: none; cursor: pointer; }
        .toolbar .primary { border-color: #1d4ed8; background: #1d4ed8; color: #fff; }
        h1 { mmargin: 4px 0 10px; text-align: center; font-size: 14px; }
        .meta { margin: 4px 0 10px; text-align: center; color: #64748b; font-size: 8px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #475569; padding: 2px 3px; vertical-align: top; overflow-wrap: anywhere; line-height: 1.15; }
        th { background: #e2e8f0; text-align: center; font-size: 6px; }
        td { font-size: 5.5px; }
        tbody tr { break-inside: avoid; }
        .code { width: 4.5%; white-space: nowrap; }
        .number { text-align: right; white-space: nowrap; }
        .empty { padding: 20px; text-align: center; color: #64748b; }
        @media print {
            .toolbar { display: none; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
  
    <h1>Arsitektur Domain Infrastruktur - Fasilitas Komputasi</h1>
    <!-- <p class="meta">Jumlah data: <?= number_format(count($rows), 0, ',', '.') ?> &middot; Dicetak <?= e(date('d/m/Y H:i')) ?></p> -->

    <table>
        <thead>
            <tr>
                <th class="code">Kode</th>
                <th>Nama Fasilitas Komputasi</th>
                <th>Bandwidth Intranet (Mbps)</th>
                <th>Bandwidth Internet (Mbps)</th>
                <th>Lokasi</th>
                <th>Status Kepemilikan</th>
                <th>Unit Pengelola</th>
                <th>Klasifikasi Tier</th>
                <th>Sistem Pengamanan Fasilitas</th>
                <th>RAI Level 1</th>
                <th>RAI Level 2</th>
                <th>RAI Level 3</th>
                <th>RAI Level 4</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="13" class="empty">Belum ada data Fasilitas Komputasi.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="code"><?= e(sprintf('DAI-%03d', (int) $row['id'])) ?></td>
                        <td><?= e($row['nama_fasilitas_komputasi'] ?? '') ?></td>
                        <td class="number"><?= e(print_dai_facility_bandwidth($row['bandwidth_intranet'] ?? null)) ?></td>
                        <td class="number"><?= e(print_dai_facility_bandwidth($row['bandwidth_internet'] ?? null)) ?></td>
                        <td><?= e($row['lokasi'] ?? '') ?></td>
                        <td><?= e($row['status_kepemilikan'] ?? '') ?></td>
                        <td><?= e($row['unit_pengelola'] ?? '') ?></td>
                        <td><?= e($row['klasifikasi_tier'] ?? '') ?></td>
                        <td><?= e($row['sistem_pengaman_fasilitas'] ?? '') ?></td>
                        <?php for ($level = 1; $level <= 4; $level++): ?>
                            <td><?= e(print_dai_facility_value($row["kode_rai_{$level}"] ?? '', $row["nama_rai_{$level}"] ?? '')) ?></td>
                        <?php endfor; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
