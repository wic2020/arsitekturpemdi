<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_login();

$rows = db()->query(
    'SELECT
        d.id, d.nama_aplikasi, d.uraian_aplikasi, d.fungsi_aplikasi, d.luaran,
        d.basis_aplikasi, d.tipe_lisensi_aplikasi, d.bahasa_pemrograman,
        d.kerangka_pengembangan, d.nama_basis_data,
        uo.kode_skpd AS operasional_kode, uo.nama_skpd AS operasional_nama,
        rc.kode_rai_4 AS cloud_kode, cloud.nama_cloud,
        rs.kode_rai_4 AS server_kode, server.nama_server,
        rn.kode_rai_4 AS jaringan_kode, jaringan.nama_jaringan,
        s.kode_skpd, s.nama_skpd,
        a.kode_raa_1, a.nama_raa_1,
        a.kode_raa_2, a.nama_raa_2,
        a.kode_raa_3, a.nama_raa_3,
        a.kode_raa_4, a.nama_raa_4
     FROM daa d
     LEFT JOIN skpd s ON s.id = d.id_skpd
     LEFT JOIN skpd uo ON uo.id = d.id_unit_kerja_operasional
     LEFT JOIN raa a ON a.id = d.id_raa
     LEFT JOIN dai_komputasi_awan cloud ON cloud.id = d.id_dai_komputasi_awan
     LEFT JOIN rai rc ON rc.id = cloud.id_rai
     LEFT JOIN dai_hardware_server server ON server.id = d.id_dai_hardware_server
     LEFT JOIN rai rs ON rs.id = server.id_rai
     LEFT JOIN dai_jaringan_intra jaringan ON jaringan.id = d.id_dai_jaringan_intra
     LEFT JOIN rai rn ON rn.id = jaringan.id_rai
     ORDER BY s.kode_skpd, d.nama_aplikasi, d.id'
)->fetchAll();

function print_daa_value(mixed $code, mixed $name): string
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
    <title>Cetak DAA - <?= e(APP_NAME) ?></title>
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
        <a href="../index.php?page=daa">Kembali</a>
        <button type="button" class="primary" onclick="window.print()">Cetak Sekarang</button>
    </div>

    <h1>Domain Arsitektur Aplikasi (DAA)</h1>
    <p class="meta">Jumlah data: <?= number_format(count($rows), 0, ',', '.') ?> &middot; Dicetak <?= e(date('d/m/Y H:i')) ?></p>

    <table>
        <thead>
            <tr>
                <th class="code">Kode</th>
                <th>Nama Aplikasi</th>
                <th>Uraian Aplikasi</th>
                <th>Fungsi Aplikasi</th>
                <th>Luaran</th>
                <th>Basis Aplikasi</th>
                <th>Tipe Lisensi Aplikasi</th>
                <th>Bahasa Pemrograman</th>
                <th>Kerangka Pengembangan</th>
                <th>Nama Basis Data</th>
                <th>Unit Kerja Operasional</th>
                <th>Infrastruktur Komputasi Awan</th>
                <th>Infrastruktur Server</th>
                <th>Infrastruktur Jaringan Intra</th>
                <th>SKPD</th>
                <th>RAA Level 1</th>
                <th>RAA Level 2</th>
                <th>RAA Level 3</th>
                <th>RAA Level 4</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="19" class="empty">Belum ada data DAA.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="code"><?= e(sprintf('DAA-%03d', (int) $row['id'])) ?></td>
                        <td><?= e($row['nama_aplikasi'] ?? '') ?></td>
                        <td><?= e($row['uraian_aplikasi'] ?? '') ?></td>
                        <td><?= e($row['fungsi_aplikasi'] ?? '') ?></td>
                        <td><?= e($row['luaran'] ?? '') ?></td>
                        <td><?= e($row['basis_aplikasi'] ?? '') ?></td>
                        <td><?= e($row['tipe_lisensi_aplikasi'] ?? '') ?></td>
                        <td><?= e($row['bahasa_pemrograman'] ?? '') ?></td>
                        <td><?= e($row['kerangka_pengembangan'] ?? '') ?></td>
                        <td><?= e($row['nama_basis_data'] ?? '') ?></td>
                        <td><?= e(print_daa_value($row['operasional_kode'] ?? '', $row['operasional_nama'] ?? '')) ?></td>
                        <td><?= e(print_daa_value($row['cloud_kode'] ?? '', $row['nama_cloud'] ?? '')) ?></td>
                        <td><?= e(print_daa_value($row['server_kode'] ?? '', $row['nama_server'] ?? '')) ?></td>
                        <td><?= e(print_daa_value($row['jaringan_kode'] ?? '', $row['nama_jaringan'] ?? '')) ?></td>
                        <td><?= e(print_daa_value($row['kode_skpd'] ?? '', $row['nama_skpd'] ?? '')) ?></td>
                        <?php for ($level = 1; $level <= 4; $level++): ?>
                            <td><?= e(print_daa_value($row["kode_raa_{$level}"] ?? '', $row["nama_raa_{$level}"] ?? '')) ?></td>
                        <?php endfor; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
