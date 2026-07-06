<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_login();

$rows = db()->query(
    'SELECT
        d.id, d.nama_layanan, d.tujuan_layanan, d.fungsi_layanan,
        d.target_layanan, d.metode_layanan, d.potensi_manfaat, d.potensi_ekonomi,
        d.potensi_risiko, d.mitigasi_risiko,
        pj.nama_skpd AS penanggung_jawab,
        uk.nama_skpd AS unit_pelaksana,
        p.nama_urusan,
        s.kode_skpd, s.nama_skpd,
        r.kode_ral_1, r.nama_ral_1,
        r.kode_ral_2, r.nama_ral_2,
        r.kode_ral_3, r.nama_ral_3,
        r.kode_ral_4, r.nama_ral_4,
        (SELECT GROUP_CONCAT(b.nama_bisnis ORDER BY b.nama_bisnis SEPARATOR " • ")
         FROM dal_dab dd
         JOIN dab b ON b.id = dd.id_dab
         WHERE dd.id_dal = d.id) AS proses_bisnis
     FROM dal d
     LEFT JOIN skpd s ON s.id = d.id_skpd
     LEFT JOIN program p ON p.id = d.id_program
     LEFT JOIN ral r ON r.id = d.id_ral
     LEFT JOIN skpd pj ON pj.id = d.id_penanggung_jawab
     LEFT JOIN skpd uk ON uk.id = d.id_unit_kerja_pelaksana
     ORDER BY s.kode_skpd ASC, p.kode_program ASC, d.id ASC'
)->fetchAll();

function print_dal_value(mixed $code, mixed $name): string
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
    <title>Cetak DAL - <?= e(APP_NAME) ?></title>
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
        th { background: #e2e8f0; text-align: center; font-size: 5.5px; }
        td { font-size: 5px; }
        tbody tr { break-inside: avoid; }
        .code { width: 4%; white-space: nowrap; }
        .empty { padding: 20px; text-align: center; color: #64748b; }
        @media print {
            .toolbar { display: none; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="index.php?page=dal">Kembali</a>
        <button type="button" class="primary" onclick="window.print()">Cetak Sekarang</button>
    </div>

    <h1>Domain Arsitektur Layanan (DAL)</h1>
    <p class="meta">Jumlah data: <?= number_format(count($rows), 0, ',', '.') ?> &middot; Dicetak <?= e(date('d/m/Y H:i')) ?></p>

    <table>
        <thead>
            <tr>
                <th class="code">Kode</th>
                <th>Nama Layanan</th>
                <th>Tujuan Layanan</th>
                <th>Fungsi Layanan</th>
                <th>Penanggung Jawab</th>
                <th>Unit Pelaksana</th>
                <th>Urusan</th>
                <th>Target Layanan</th>
                <th>Metode Layanan</th>
                <th>Potensi Manfaat</th>
                <th>Potensi Ekonomi</th>
                <th>Potensi Risiko</th>
                <th>Mitigasi Risiko</th>
                <th>Proses Bisnis</th>
                <th>SKPD</th>
                <th>RAL Level 1</th>
                <th>RAL Level 2</th>
                <th>RAL Level 3</th>
                <th>RAL Level 4</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="19" class="empty">Belum ada data DAL.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="code"><?= e(sprintf('DAL-%03d', (int) $row['id'])) ?></td>
                        <td><?= e($row['nama_layanan'] ?? '') ?></td>
                        <td><?= e($row['tujuan_layanan'] ?? '') ?></td>
                        <td><?= e($row['fungsi_layanan'] ?? '') ?></td>
                        <td><?= e($row['penanggung_jawab'] ?? '') ?></td>
                        <td><?= e($row['unit_pelaksana'] ?? '') ?></td>
                        <td><?= e($row['nama_urusan'] ?? '') ?></td>
                        <td><?= e($row['target_layanan'] ?? '') ?></td>
                        <td><?= e($row['metode_layanan'] ?? '') ?></td>
                        <td><?= e($row['potensi_manfaat'] ?? '') ?></td>
                        <td><?= e($row['potensi_ekonomi'] ?? '') ?></td>
                        <td><?= e($row['potensi_risiko'] ?? '') ?></td>
                        <td><?= e($row['mitigasi_risiko'] ?? '') ?></td>
                        <td><?= e($row['proses_bisnis'] ?? '') ?></td>
                        <td><?= e(print_dal_value($row['kode_skpd'] ?? '', $row['nama_skpd'] ?? '')) ?></td>
                        <?php for ($level = 1; $level <= 4; $level++): ?>
                            <td><?= e(print_dal_value($row["kode_ral_{$level}"] ?? '', $row["nama_ral_{$level}"] ?? '')) ?></td>
                        <?php endfor; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
