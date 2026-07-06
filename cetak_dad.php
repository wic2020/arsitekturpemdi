<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_login();

$rows = db()->query(
    'SELECT
        d.id, d.id_dal, d.nama_data, d.uraian_data, d.tujuan_data, d.sifat_data, d.jenis_data,
        d.validitas_data, d.penghasil_data, d.input_data, d.output_data, d.interoperabilitas,
        l.nama_layanan,
        s.kode_skpd, s.nama_skpd,
        r.kode_rad_1, r.nama_rad_1,
        r.kode_rad_2, r.nama_rad_2,
        r.kode_rad_3, r.nama_rad_3,
        r.kode_rad_4, r.nama_rad_4
     FROM dad d
     LEFT JOIN dal l ON l.id = d.id_dal
     LEFT JOIN skpd s ON s.id = d.id_skpd
     LEFT JOIN program p ON p.id = d.id_program
     LEFT JOIN rad r ON r.id = d.id_rad
     ORDER BY s.kode_skpd ASC, p.kode_program ASC, d.id ASC'
)->fetchAll();

function print_dad_value(mixed $code, mixed $name): string
{
    $code = trim((string) $code);
    $name = trim((string) $name);
    return trim($code . ($code !== '' && $name !== '' ? ' - ' : '') . $name);
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak DAD - <?= e(APP_NAME) ?></title>
    <style>
        @page { size: A4 landscape; margin: 7mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111827; font-family: Arial, Helvetica, sans-serif; font-size: 7px; }
        .toolbar { display: flex; justify-content: flex-end; gap: 8px; margin-bottom: 12px; }
        .toolbar button, .toolbar a { border: 1px solid #cbd5e1; border-radius: 5px; background: #fff; padding: 7px 12px; color: #334155; font-size: 11px; font-weight: 700; text-decoration: none; cursor: pointer; }
        .toolbar .primary { border-color: #1d4ed8; background: #1d4ed8; color: #fff; }
        h1 { margin: 0; text-align: center; font-size: 14px; }
        .meta { margin: 4px 0 10px; text-align: center; color: #64748b; font-size: 8px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #475569; padding: 2px 3px; vertical-align: top; overflow-wrap: anywhere; line-height: 1.15; }
        th { background: #e2e8f0; text-align: center; font-size: 6px; }
        td { font-size: 5.5px; }
        tbody tr { break-inside: avoid; }
        .code { width: 4.5%; white-space: nowrap; }
        .empty { padding: 20px; text-align: center; color: #64748b; }
        @media print {
            .toolbar { display: none; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="index.php?page=dad">Kembali</a>
        <button type="button" class="primary" onclick="window.print()">Cetak Sekarang</button>
    </div>

    <h1>Domain Arsitektur Data (DAD)</h1>
    <p class="meta">Jumlah data: <?= number_format(count($rows), 0, ',', '.') ?> &middot; Dicetak <?= e(date('d/m/Y H:i')) ?></p>

    <table>
        <thead>
            <tr>
                <th class="code">Kode</th>
                <th>Nama Data</th>
                <th>Uraian Data</th>
                <th>Tujuan Data</th>
                <th>Sifat Data</th>
                <th>Jenis Data</th>
                <th>Validitas Data</th>
                <th>Penghasil Data</th>
                <th>Input Data</th>
                <th>Output Data</th>
                <th>Interoperabilitas</th>
                <th>Layanan</th>
                <th>SKPD</th>
                <th>RAD Level 1</th>
                <th>RAD Level 2</th>
                <th>RAD Level 3</th>
                <th>RAD Level 4</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="17" class="empty">Belum ada data DAD.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="code"><?= e(sprintf('DAD-%03d', (int) $row['id'])) ?></td>
                        <td><?= e($row['nama_data'] ?? '') ?></td>
                        <td><?= e($row['uraian_data'] ?? '') ?></td>
                        <td><?= e($row['tujuan_data'] ?? '') ?></td>
                        <td><?= e($row['sifat_data'] ?? '') ?></td>
                        <td><?= e($row['jenis_data'] ?? '') ?></td>
                        <td><?= e($row['validitas_data'] ?? '') ?></td>
                        <td><?= e($row['penghasil_data'] ?? '') ?></td>
                        <td><?= e($row['input_data'] ?? '') ?></td>
                        <td><?= e($row['output_data'] ?? '') ?></td>
                        <td><?= e($row['interoperabilitas'] ?? '') ?></td>
                        <td><?= $row['id_dal'] !== null
                            ? e(sprintf('DAL-%03d %s', (int) $row['id_dal'], trim((string) ($row['nama_layanan'] ?? ''))))
                            : '' ?></td>
                        <td><?= e(print_dad_value($row['kode_skpd'] ?? '', $row['nama_skpd'] ?? '')) ?></td>
                        <?php for ($level = 1; $level <= 4; $level++): ?>
                            <td><?= e(print_dad_value($row["kode_rad_{$level}"] ?? '', $row["nama_rad_{$level}"] ?? '')) ?></td>
                        <?php endfor; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
