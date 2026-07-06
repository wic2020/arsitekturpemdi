<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_login();

$dakPrintConfigs = [
    'dak-audit-keamanan' => [
        'table' => 'dak_audit_keamanan',
        'title' => 'Audit Keamanan',
        'fields' => [
            'jenis_audit' => 'Jenis Audit',
            'tanggal' => 'Tanggal Audit',
            'hasil_audit' => 'Hasil Audit',
            'tindak_lanjut' => 'Tindak Lanjut',
        ],
    ],
    'dak-edukasi-kesadaran' => [
        'table' => 'dak_edukasi_kesadaran',
        'title' => 'Edukasi Kesadaran',
        'fields' => ['tanggal' => 'Tanggal Kegiatan'],
    ],
    'dak-identifikasi-kerentanan' => [
        'table' => 'dak_identifikasi_kerentanan',
        'title' => 'Identifikasi Kerentanan',
        'fields' => ['tanggal' => 'Tanggal Identifikasi'],
    ],
    'dak-kelaikan-keamanan' => [
        'table' => 'dak_kelaikan_keamanan',
        'title' => 'Kelaikan Keamanan',
        'fields' => ['tanggal' => 'Tanggal Pemeriksaan'],
    ],
    'dak-penanganan-insiden' => [
        'table' => 'dak_penanganan_insiden',
        'title' => 'Penanganan Insiden',
        'fields' => [
            'tanggal' => 'Tanggal Insiden',
            'nilai_kematangan' => 'Nilai Kematangan',
        ],
    ],
    'dak-peningkatan-keamanan' => [
        'table' => 'dak_peningkatan_keamanan',
        'title' => 'Peningkatan Keamanan',
        'fields' => ['tanggal' => 'Tanggal Kegiatan'],
    ],
    'dak-standar-keamanan' => [
        'table' => 'dak_standar_keamanan',
        'title' => 'Standar Keamanan',
        'fields' => [
            'jenis_standar_keamanan' => 'Jenis Standar Keamanan',
            'tanggal_mulai' => 'Tanggal Mulai',
            'tanggal_akhir' => 'Tanggal Akhir',
        ],
    ],
];

$type = strtolower(trim((string) ($_GET['jenis'] ?? '')));
$config = $dakPrintConfigs[$type] ?? null;
if (!$config) {
    http_response_code(404);
    exit('Jenis domain arsitektur keamanan tidak valid.');
}

$specificColumns = array_keys($config['fields']);
$selectColumns = array_map(
    static fn(string $column): string => 'd.' . $column,
    $specificColumns
);
$rows = db()->query(
    'SELECT d.id, d.nama, d.deskripsi' .
    ($selectColumns ? ', ' . implode(', ', $selectColumns) : '') . ',
        r.kode_rak_1, r.nama_rak_1,
        r.kode_rak_2, r.nama_rak_2,
        r.kode_rak_3, r.nama_rak_3,
        r.kode_rak_4, r.nama_rak_4
     FROM ' . $config['table'] . ' d
     LEFT JOIN rak r ON r.id = d.id_rak
     ORDER BY d.nama, d.id'
)->fetchAll();

function print_dak_value(mixed $code, mixed $name): string
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
    <title>Cetak DAK <?= e($config['title']) ?> - <?= e(APP_NAME) ?></title>
    <style>
        @page { size: A4 landscape; margin: 8mm; }
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
        .code { width: 6%; white-space: nowrap; }
        .empty { padding: 20px; text-align: center; color: #64748b; }
        @media print {
            .toolbar { display: none; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="index.php?page=<?= e($type) ?>">Kembali</a>
        <button type="button" class="primary" onclick="window.print()">Cetak Sekarang</button>
    </div>

    <h1>Domain Arsitektur Keamanan - <?= e($config['title']) ?></h1>
    <p class="meta">Jumlah data: <?= number_format(count($rows), 0, ',', '.') ?> &middot; Dicetak <?= e(date('d/m/Y H:i')) ?></p>

    <table>
        <thead>
            <tr>
                <th class="code">Kode</th>
                <th>Nama</th>
                <th>Deskripsi</th>
                <?php foreach ($config['fields'] as $label): ?>
                    <th><?= e($label) ?></th>
                <?php endforeach; ?>
                <th>RAK Level 1</th>
                <th>RAK Level 2</th>
                <th>RAK Level 3</th>
                <th>RAK Level 4</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="<?= 7 + count($specificColumns) ?>" class="empty">Belum ada data <?= e($config['title']) ?>.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="code"><?= e(sprintf('DAK-%03d', (int) $row['id'])) ?></td>
                        <td><?= e($row['nama'] ?? '') ?></td>
                        <td><?= e($row['deskripsi'] ?? '') ?></td>
                        <?php foreach ($specificColumns as $column): ?>
                            <td><?= e($row[$column] ?? '') ?></td>
                        <?php endforeach; ?>
                        <?php for ($level = 1; $level <= 4; $level++): ?>
                            <td><?= e(print_dak_value($row["kode_rak_{$level}"] ?? '', $row["nama_rak_{$level}"] ?? '')) ?></td>
                        <?php endfor; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
