<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_login();

$configs = [
    'komputasi-awan' => [
        'title' => 'Komputasi Awan',
        'route' => 'dai-komputasi-awan',
        'table' => 'dai_komputasi_awan',
        'select' => 'd.nama_cloud,d.deskripsi,d.tipe,d.status_kepemilikan,d.nama_pemilik,d.biaya_layanan,d.unit_pengembang,d.unit_operasional,d.jangka_waktu_layanan,n.id network_id,n.nama_jaringan network_name',
        'joins' => ' LEFT JOIN dai_jaringan_intra n ON n.id=d.id_dai_jaringan_intra',
        'rai' => true,
        'columns' => [
            ['label' => 'Nama Cloud', 'field' => 'nama_cloud'],
            ['label' => 'Deskripsi', 'field' => 'deskripsi'],
            ['label' => 'Tipe Cloud', 'field' => 'tipe'],
            ['label' => 'Status Kepemilikan', 'field' => 'status_kepemilikan'],
            ['label' => 'Nama Pemilik', 'field' => 'nama_pemilik'],
            ['label' => 'Biaya Layanan', 'field' => 'biaya_layanan'],
            ['label' => 'Unit Pengembang', 'field' => 'unit_pengembang'],
            ['label' => 'Unit Operasional', 'field' => 'unit_operasional'],
            ['label' => 'Jangka Waktu Layanan', 'field' => 'jangka_waktu_layanan'],
            ['label' => 'Jaringan Intra', 'type' => 'relation', 'id' => 'network_id', 'name' => 'network_name', 'prefix' => 'DAI'],
        ],
    ],
    'jaringan-intra' => [
        'title' => 'Jaringan Intra',
        'route' => 'dai-jaringan-intra',
        'table' => 'dai_jaringan_intra',
        'select' => 'd.nama_jaringan,d.deskripsi,d.jenis_jaringan,d.status_kepemilikan,d.bandwidth,d.tipe_media,d.media_lainnya,s.kode_skpd manager_code,s.nama_skpd manager_name',
        'joins' => ' LEFT JOIN skpd s ON s.id=d.id_unit_kerja_pengelola',
        'rai' => true,
        'columns' => [
            ['label' => 'Nama Jaringan', 'field' => 'nama_jaringan'],
            ['label' => 'Deskripsi', 'field' => 'deskripsi'],
            ['label' => 'Jenis Jaringan', 'field' => 'jenis_jaringan'],
            ['label' => 'Status Kepemilikan', 'field' => 'status_kepemilikan'],
            ['label' => 'Unit Kerja Pengelola', 'type' => 'label', 'code' => 'manager_code', 'name' => 'manager_name'],
            ['label' => 'Bandwidth (Mbps)', 'field' => 'bandwidth', 'type' => 'number'],
            ['label' => 'Tipe Media', 'field' => 'tipe_media'],
            ['label' => 'Media Lainnya', 'field' => 'media_lainnya'],
        ],
    ],
    'software' => [
        'title' => 'Software',
        'route' => 'dai-software',
        'table' => 'dai_software',
        'select' => 'd.nama_perangkat_lunak,d.deskripsi,d.tipe_perangkat_lunak,d.jenis_sistem_operasi,d.jenis_lisensi,d.nama_pemilik_lisensi,d.validitas_lisensi',
        'joins' => '',
        'rai' => true,
        'columns' => [
            ['label' => 'Nama Perangkat Lunak', 'field' => 'nama_perangkat_lunak'],
            ['label' => 'Deskripsi', 'field' => 'deskripsi'],
            ['label' => 'Tipe Perangkat Lunak', 'field' => 'tipe_perangkat_lunak'],
            ['label' => 'Jenis Sistem Operasi', 'field' => 'jenis_sistem_operasi'],
            ['label' => 'Jenis Lisensi', 'field' => 'jenis_lisensi'],
            ['label' => 'Nama Pemilik Lisensi', 'field' => 'nama_pemilik_lisensi'],
            ['label' => 'Validitas Lisensi', 'field' => 'validitas_lisensi'],
        ],
    ],
    'hardware-server' => [
        'title' => 'Hardware Server',
        'route' => 'dai-hardware-server',
        'table' => 'dai_hardware_server',
        'select' => 'd.nama_server,d.deskripsi,d.jenis_server,d.jenis_penggunaan,d.status_kepemilikan,d.nama_pemilik,d.unit_pengelola,d.lokasi,d.kapasitas_memori,d.kapasitas_prosesor,d.kapasitas_penyimpanan,sw.id software_id,sw.nama_perangkat_lunak software_name,b.id business_id,b.nama_bisnis business_name,f.id facility_id,f.nama_fasilitas_komputasi facility_name,n.id network_id,n.nama_jaringan network_name,n.tipe_media,n.media_lainnya',
        'joins' => ' LEFT JOIN dai_software sw ON sw.id=d.id_dai_software LEFT JOIN dab b ON b.id=d.id_dab LEFT JOIN dai_fasilitas_komputasi f ON f.id=d.id_dai_fasilitas_komputasi LEFT JOIN dai_jaringan_intra n ON n.id=d.id_dai_jaringan_intra',
        'rai' => true,
        'columns' => [
            ['label' => 'Nama Server', 'field' => 'nama_server'],
            ['label' => 'Deskripsi', 'field' => 'deskripsi'],
            ['label' => 'Jenis Server', 'field' => 'jenis_server'],
            ['label' => 'Jenis Penggunaan', 'field' => 'jenis_penggunaan'],
            ['label' => 'Status Kepemilikan', 'field' => 'status_kepemilikan'],
            ['label' => 'Nama Pemilik', 'field' => 'nama_pemilik'],
            ['label' => 'Unit Pengelola', 'field' => 'unit_pengelola'],
            ['label' => 'Lokasi', 'field' => 'lokasi'],
            ['label' => 'Kapasitas Memori', 'field' => 'kapasitas_memori'],
            ['label' => 'Kapasitas Prosesor', 'field' => 'kapasitas_prosesor'],
            ['label' => 'Kapasitas Penyimpanan', 'field' => 'kapasitas_penyimpanan'],
            ['label' => 'Software', 'type' => 'relation', 'id' => 'software_id', 'name' => 'software_name', 'prefix' => 'DAI'],
            ['label' => 'Domain Bisnis', 'type' => 'relation', 'id' => 'business_id', 'name' => 'business_name', 'prefix' => 'DAB'],
            ['label' => 'Fasilitas Komputasi', 'type' => 'relation', 'id' => 'facility_id', 'name' => 'facility_name', 'prefix' => 'DAI'],
            ['label' => 'Jaringan Intra', 'type' => 'relation', 'id' => 'network_id', 'name' => 'network_name', 'prefix' => 'DAI'],
            ['label' => 'Tipe Media', 'field' => 'tipe_media'],
            ['label' => 'Media Lainnya', 'field' => 'media_lainnya'],
        ],
    ],
    'hardware-jaringan' => [
        'title' => 'Hardware Jaringan',
        'route' => 'dai-hardware-jaringan',
        'table' => 'dai_hardware_jaringan',
        'select' => 'd.nama_device,d.deskripsi,d.tipe,d.status_kepemilikan,d.nama_pemilik,d.unit_pengelola,d.lokasi,f.id facility_id,f.nama_fasilitas_komputasi facility_name,sw.id software_id,sw.nama_perangkat_lunak software_name,n.id network_id,n.nama_jaringan network_name',
        'joins' => ' LEFT JOIN dai_fasilitas_komputasi f ON f.id=d.id_dai_fasilitas_komputasi LEFT JOIN dai_software sw ON sw.id=d.id_dai_software LEFT JOIN dai_jaringan_intra n ON n.id=d.id_dai_jaringan_intra',
        'rai' => true,
        'columns' => [
            ['label' => 'Nama Perangkat', 'field' => 'nama_device'],
            ['label' => 'Deskripsi', 'field' => 'deskripsi'],
            ['label' => 'Tipe', 'field' => 'tipe'],
            ['label' => 'Status Kepemilikan', 'field' => 'status_kepemilikan'],
            ['label' => 'Nama Pemilik', 'field' => 'nama_pemilik'],
            ['label' => 'Unit Pengelola', 'field' => 'unit_pengelola'],
            ['label' => 'Lokasi', 'field' => 'lokasi'],
            ['label' => 'Fasilitas Komputasi', 'type' => 'relation', 'id' => 'facility_id', 'name' => 'facility_name', 'prefix' => 'DAI'],
            ['label' => 'Software', 'type' => 'relation', 'id' => 'software_id', 'name' => 'software_name', 'prefix' => 'DAI'],
            ['label' => 'Jaringan Intra', 'type' => 'relation', 'id' => 'network_id', 'name' => 'network_name', 'prefix' => 'DAI'],
        ],
    ],
    'hardware-periferal' => [
        'title' => 'Hardware Periferal',
        'route' => 'dai-hardware-periferal',
        'table' => 'dai_hardware_periferal',
        'select' => 'd.nama_periferal,d.deskripsi,d.tipe,d.status_kepemilikan,d.nama_pemilik,d.unit_pengelola,d.lokasi,f.id facility_id,f.nama_fasilitas_komputasi facility_name,sw.id software_id,sw.nama_perangkat_lunak software_name,n.id network_id,n.nama_jaringan network_name',
        'joins' => ' LEFT JOIN dai_fasilitas_komputasi f ON f.id=d.id_dai_fasilitas_komputasi LEFT JOIN dai_software sw ON sw.id=d.id_dai_software LEFT JOIN dai_jaringan_intra n ON n.id=d.id_dai_jaringan_intra',
        'rai' => true,
        'columns' => [
            ['label' => 'Nama Perangkat', 'field' => 'nama_periferal'],
            ['label' => 'Deskripsi', 'field' => 'deskripsi'],
            ['label' => 'Tipe', 'field' => 'tipe'],
            ['label' => 'Status Kepemilikan', 'field' => 'status_kepemilikan'],
            ['label' => 'Nama Pemilik', 'field' => 'nama_pemilik'],
            ['label' => 'Unit Pengelola', 'field' => 'unit_pengelola'],
            ['label' => 'Lokasi', 'field' => 'lokasi'],
            ['label' => 'Fasilitas Komputasi', 'type' => 'relation', 'id' => 'facility_id', 'name' => 'facility_name', 'prefix' => 'DAI'],
            ['label' => 'Software', 'type' => 'relation', 'id' => 'software_id', 'name' => 'software_name', 'prefix' => 'DAI'],
            ['label' => 'Jaringan Intra', 'type' => 'relation', 'id' => 'network_id', 'name' => 'network_name', 'prefix' => 'DAI'],
        ],
    ],
    'hardware-storage' => [
        'title' => 'Hardware Storage',
        'route' => 'dai-hardware-storage',
        'table' => 'dai_hardware_storage',
        'select' => 'd.nama_storage,d.deskripsi,d.status_kepemilikan,d.nama_pemilik,d.unit_pengelola,d.lokasi,d.kapasitas_penyimpanan,d.metode_akses_data_sharing,f.id facility_id,f.nama_fasilitas_komputasi facility_name,sw.id software_id,sw.nama_perangkat_lunak software_name,n.id network_id,n.nama_jaringan network_name',
        'joins' => ' LEFT JOIN dai_fasilitas_komputasi f ON f.id=d.id_dai_fasilitas_komputasi LEFT JOIN dai_software sw ON sw.id=d.id_dai_software LEFT JOIN dai_jaringan_intra n ON n.id=d.id_dai_jaringan_intra',
        'rai' => true,
        'columns' => [
            ['label' => 'Nama Perangkat', 'field' => 'nama_storage'],
            ['label' => 'Deskripsi', 'field' => 'deskripsi'],
            ['label' => 'Status Kepemilikan', 'field' => 'status_kepemilikan'],
            ['label' => 'Nama Pemilik', 'field' => 'nama_pemilik'],
            ['label' => 'Unit Pengelola', 'field' => 'unit_pengelola'],
            ['label' => 'Lokasi', 'field' => 'lokasi'],
            ['label' => 'Kapasitas Penyimpanan', 'field' => 'kapasitas_penyimpanan'],
            ['label' => 'Metode Akses', 'field' => 'metode_akses_data_sharing'],
            ['label' => 'Fasilitas Komputasi', 'type' => 'relation', 'id' => 'facility_id', 'name' => 'facility_name', 'prefix' => 'DAI'],
            ['label' => 'Software', 'type' => 'relation', 'id' => 'software_id', 'name' => 'software_name', 'prefix' => 'DAI'],
            ['label' => 'Jaringan Intra', 'type' => 'relation', 'id' => 'network_id', 'name' => 'network_name', 'prefix' => 'DAI'],
        ],
    ],
    'hardware-keamanan' => [
        'title' => 'Hardware Keamanan',
        'route' => 'dai-hardware-keamanan',
        'table' => 'dai_hardware_keamanan',
        'select' => 'd.nama_device,d.deskripsi,d.tipe,d.status_kepemilikan,d.nama_pemilik,d.unit_pengelola,d.lokasi,f.id facility_id,f.nama_fasilitas_komputasi facility_name,sw.id software_id,sw.nama_perangkat_lunak software_name,n.id network_id,n.nama_jaringan network_name',
        'joins' => ' LEFT JOIN dai_fasilitas_komputasi f ON f.id=d.id_dai_fasilitas_komputasi LEFT JOIN dai_software sw ON sw.id=d.id_dai_software LEFT JOIN dai_jaringan_intra n ON n.id=d.id_dai_jaringan_intra',
        'rai' => true,
        'columns' => [
            ['label' => 'Nama Perangkat', 'field' => 'nama_device'],
            ['label' => 'Deskripsi', 'field' => 'deskripsi'],
            ['label' => 'Tipe', 'field' => 'tipe'],
            ['label' => 'Status Kepemilikan', 'field' => 'status_kepemilikan'],
            ['label' => 'Nama Pemilik', 'field' => 'nama_pemilik'],
            ['label' => 'Unit Pengelola', 'field' => 'unit_pengelola'],
            ['label' => 'Lokasi', 'field' => 'lokasi'],
            ['label' => 'Fasilitas Komputasi', 'type' => 'relation', 'id' => 'facility_id', 'name' => 'facility_name', 'prefix' => 'DAI'],
            ['label' => 'Software', 'type' => 'relation', 'id' => 'software_id', 'name' => 'software_name', 'prefix' => 'DAI'],
            ['label' => 'Jaringan Intra', 'type' => 'relation', 'id' => 'network_id', 'name' => 'network_name', 'prefix' => 'DAI'],
        ],
    ],
    'splp' => [
        'title' => 'SPLP',
        'route' => 'dai-splp',
        'table' => 'dai_splp',
        'select' => 'd.nama_splp,d.deskripsi,d.jenis_splp,d.status_kepemilikan,d.nama_pemilik,n.id network_id,n.nama_jaringan network_name',
        'joins' => ' LEFT JOIN dai_jaringan_intra n ON n.id=d.id_dai_jaringan_intra',
        'rai' => false,
        'columns' => [
            ['label' => 'Nama SPLP', 'field' => 'nama_splp'],
            ['label' => 'Deskripsi', 'field' => 'deskripsi'],
            ['label' => 'Jenis SPLP', 'field' => 'jenis_splp'],
            ['label' => 'Status Kepemilikan', 'field' => 'status_kepemilikan'],
            ['label' => 'Nama Pemilik', 'field' => 'nama_pemilik'],
            ['label' => 'Jaringan Intra', 'type' => 'relation', 'id' => 'network_id', 'name' => 'network_name', 'prefix' => 'DAI'],
        ],
    ],
];

$type = (string) ($_GET['type'] ?? '');
$config = $configs[$type] ?? null;
if (!$config) {
    http_response_code(404);
    exit('Konfigurasi cetak DAI tidak ditemukan.');
}

$raiSelect = '';
if ($config['rai']) {
    $raiSelect = ',r.kode_rai_1,r.nama_rai_1,r.kode_rai_2,r.nama_rai_2,r.kode_rai_3,r.nama_rai_3,r.kode_rai_4,r.nama_rai_4';
}
$sql = "SELECT d.id,{$config['select']}{$raiSelect} FROM {$config['table']} d";
if ($config['rai']) $sql .= ' LEFT JOIN rai r ON r.id=d.id_rai';
$sql .= $config['joins'] . ' ORDER BY d.id ASC';
$rows = db()->query($sql)->fetchAll();

$columns = array_merge([['label' => 'Kode', 'type' => 'code']], $config['columns']);
if ($config['rai']) {
    for ($level = 1; $level <= 4; $level++) {
        $columns[] = ['label' => "RAI Level {$level}", 'type' => 'label', 'code' => "kode_rai_{$level}", 'name' => "nama_rai_{$level}"];
    }
}

function print_dai_join_label(mixed $code, mixed $name): string
{
    $code = trim((string) $code);
    $name = trim((string) $name);
    return trim($code . ($code !== '' && $name !== '' ? ' - ' : '') . $name);
}

function print_dai_column(array $row, array $column): string
{
    $columnType = $column['type'] ?? 'field';
    if ($columnType === 'code') {
        return sprintf('DAI-%03d', (int) $row['id']);
    }
    if ($columnType === 'relation') {
        $id = (int) ($row[$column['id']] ?? 0);
        if ($id < 1) return '';
        return sprintf('%s-%03d', $column['prefix'], $id)
            . (($row[$column['name']] ?? '') !== '' ? ' - ' . $row[$column['name']] : '');
    }
    if ($columnType === 'label') {
        return print_dai_join_label($row[$column['code']] ?? '', $row[$column['name']] ?? '');
    }
    if ($columnType === 'number') {
        $value = $row[$column['field']] ?? null;
        if ($value === null || $value === '') return '';
        return rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
    }
    return (string) ($row[$column['field']] ?? '');
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak <?= e($config['title']) ?> - <?= e(APP_NAME) ?></title>
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
        <a href="index.php?page=<?= e($config['route']) ?>">Kembali</a>
        <button type="button" class="primary" onclick="window.print()">Cetak Sekarang</button>
    </div>

    <h1>Arsitektur Domain Infrastruktur - <?= e($config['title']) ?></h1>
    <p class="meta">Jumlah data: <?= number_format(count($rows), 0, ',', '.') ?> &middot; Dicetak <?= e(date('d/m/Y H:i')) ?></p>

    <table>
        <thead><tr><?php foreach ($columns as $column): ?><th class="<?= ($column['type'] ?? '') === 'code' ? 'code' : '' ?>"><?= e($column['label']) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="<?= count($columns) ?>" class="empty">Belum ada data <?= e($config['title']) ?>.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?><tr><?php foreach ($columns as $column): ?><td class="<?= ($column['type'] ?? '') === 'code' ? 'code' : '' ?>"><?= e(print_dai_column($row, $column)) ?></td><?php endforeach; ?></tr><?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
