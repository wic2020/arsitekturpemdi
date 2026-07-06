<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_login();

$rows = db()->query(
    'SELECT
        d.nama_bisnis, d.uraian, d.sasaran, d.indikator, d.target, d.realisasi,
        s.kode_skpd, s.nama_skpd,
        r.kode_rab_1, r.nama_rab_1,
        r.kode_rab_2, r.nama_rab_2,
        r.kode_rab_3, r.nama_rab_3,
        r.kode_rab_4, r.nama_rab_4
     FROM dab d
     LEFT JOIN skpd s ON s.id = d.id_skpd
     LEFT JOIN program p ON p.id = d.id_program
     LEFT JOIN rab r ON r.id = d.id_rab
     ORDER BY s.kode_skpd ASC, p.kode_program ASC, d.id ASC'
)->fetchAll();

function print_dab_value(mixed $code, mixed $name): string
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
    <title>Cetak DAB - <?= e(APP_NAME) ?></title>
    <style>
        @page { size: A4 landscape; margin: 9mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111827; font-family: Arial, Helvetica, sans-serif; font-size: 8px; }
        .toolbar { display: flex; justify-content: flex-end; gap: 8px; margin-bottom: 12px; }
        .toolbar button, .toolbar a { border: 1px solid #cbd5e1; border-radius: 5px; background: #fff; padding: 7px 12px; color: #334155; font-size: 11px; font-weight: 700; text-decoration: none; cursor: pointer; }
        .toolbar .primary { border-color: #1d4ed8; background: #1d4ed8; color: #fff; }
        h1 { margin: 0; text-align: center; font-size: 14px; }
        .meta { margin: 4px 0 10px; text-align: center; color: #64748b; font-size: 8px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #475569; padding: 3px 4px; vertical-align: top; overflow-wrap: anywhere; line-height: 1.2; }
        th { background: #e2e8f0; text-align: center; font-size: 7px; }
        td { font-size: 6.5px; }
        tbody tr { break-inside: avoid; }
        .number { width: 3%; text-align: center; }
        .empty { padding: 20px; text-align: center; color: #64748b; }
        @media print {
            .toolbar { display: none; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="../index.php?page=dab">Kembali</a>
        <button type="button" class="primary" onclick="window.print()">Cetak Sekarang</button>
    </div>

    <h1>Domain Arsitektur Bisnis (DAB)</h1>
    <p class="meta">Jumlah data: <?= number_format(count($rows), 0, ',', '.') ?> &middot; Dicetak <?= e(date('d/m/Y H:i')) ?></p>

    <table>
        <thead>
            <tr>
                <th class="number">No</th>
                <th>Nama Bisnis</th>
                <th>Uraian</th>
                <th>Sasaran</th>
                <th>Indikator</th>
                <th>Target</th>
                <th>Realisasi</th>
                <th>SKPD</th>
                <th>RAB Level 1</th>
                <th>RAB Level 2</th>
                <th>RAB Level 3</th>
                <th>RAB Level 4</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="12" class="empty">Belum ada data DAB.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $index => $row): ?>
                    <tr>
                        <td class="number"><?= $index + 1 ?></td>
                        <td><?= e($row['nama_bisnis'] ?? '') ?></td>
                        <td><?= e($row['uraian'] ?? '') ?></td>
                        <td><?= e($row['sasaran'] ?? '') ?></td>
                        <td><?= e($row['indikator'] ?? '') ?></td>
                        <td><?= e($row['target'] ?? '') ?></td>
                        <td><?= e($row['realisasi'] ?? '') ?></td>
                        <td><?= e(print_dab_value($row['kode_skpd'] ?? '', $row['nama_skpd'] ?? '')) ?></td>
                        <?php for ($level = 1; $level <= 4; $level++): ?>
                            <td><?= e(print_dab_value($row["kode_rab_{$level}"] ?? '', $row["nama_rab_{$level}"] ?? '')) ?></td>
                        <?php endfor; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
