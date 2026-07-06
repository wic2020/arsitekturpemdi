<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_login();

$type = (string) ($_GET['type'] ?? '');
$search = trim((string) ($_GET['q'] ?? ''));
$configs = [
    'aspek' => [
        'title' => 'Aspek Peta Rencana',
        'back' => 'aspek',
        'sql' => 'SELECT a.nama_aspek,
            (SELECT COUNT(*) FROM indikator i WHERE i.id_aspek=a.id) jumlah_indikator,
            (SELECT COALESCE(SUM(i.bobot),0) FROM indikator i WHERE i.id_aspek=a.id) total_bobot
            FROM aspek a',
        'search' => 'a.nama_aspek LIKE :search',
        'order' => 'a.nama_aspek,a.id',
        'headers' => ['Nama Aspek', 'Jumlah Indikator', 'Bobot'],
    ],
    'indikator' => [
        'title' => 'Indikator Peta Rencana',
        'back' => 'indikator',
        'sql' => 'SELECT i.nama_indikator,i.deskripsi_indikator,i.koordinator,i.bobot,a.nama_aspek,s.kode_skpd,s.nama_skpd,
            (SELECT COALESCE(SUM(e.skor),0)
             FROM pemdi_level l
             INNER JOIN pemdi_evidence e ON e.id_pemdi_level=l.id
             WHERE l.id_indikator=i.id) total_skor
            FROM indikator i
            INNER JOIN aspek a ON a.id=i.id_aspek
            LEFT JOIN skpd s ON s.id=i.id_skpd',
        'search' => 'CONCAT_WS(" ",i.nama_indikator,i.deskripsi_indikator,i.koordinator,a.nama_aspek,s.kode_skpd,s.nama_skpd) LIKE :search',
        'order' => 'a.nama_aspek,s.kode_skpd,i.nama_indikator,i.id',
        'headers' => ['Nama Indikator', 'Deskripsi Indikator', 'Aspek', 'SKPD & Koordinator', 'Bobot', 'Skor'],
    ],
];
$config = $configs[$type] ?? null;
if (!$config) {
    http_response_code(404);
    exit('Jenis cetak tidak ditemukan.');
}

$sql = $config['sql'];
$params = [];
if ($search !== '') {
    $sql .= ' WHERE ' . $config['search'];
    $params['search'] = '%' . $search . '%';
}
$stmt = db()->prepare($sql . ' ORDER BY ' . $config['order']);
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak <?= e($config['title']) ?> - <?= e(APP_NAME) ?></title>
    <style>
        @page { size: A4 portrait; margin: 12mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111827; font-family: Arial, Helvetica, sans-serif; font-size: 10px; }
        .toolbar { display: flex; justify-content: flex-end; gap: 8px; margin-bottom: 14px; }
        .toolbar button,.toolbar a { border: 1px solid #cbd5e1; border-radius: 5px; background: #fff; padding: 7px 12px; color: #334155; font-size: 11px; font-weight: 700; text-decoration: none; cursor: pointer; }
        .toolbar .primary { border-color: #1d4ed8; background: #1d4ed8; color: #fff; }
        h1 { margin: 0; text-align: center; font-size: 16px; }
        .meta { margin: 5px 0 14px; text-align: center; color: #64748b; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th,td { border: 1px solid #475569; padding: 6px; vertical-align: top; overflow-wrap: anywhere; }
        th { background: #e2e8f0; text-align: center; font-size: 9px; }
        .number { width: 7%; text-align: center; }
        .empty { padding: 24px; text-align: center; color: #64748b; }
        @media print { .toolbar { display: none; } body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }
    </style>
</head>
<body>
    <div class="toolbar"><a href="../index.php?page=<?= e($config['back']) ?>">Kembali</a><button type="button" class="primary" onclick="window.print()">Cetak Sekarang</button></div>
    <h1><?= e($config['title']) ?></h1>
    <p class="meta">Jumlah data: <?= number_format(count($rows), 0, ',', '.') ?><?= $search !== '' ? ' · Pencarian: ' . e($search) : '' ?> · Dicetak <?= e(date('d/m/Y H:i')) ?></p>
    <table>
        <thead><tr><th class="number">No</th><?php foreach ($config['headers'] as $header): ?><th><?= e($header) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
            <?php if (!$rows): ?><tr><td colspan="<?= $type === 'aspek' ? 4 : 7 ?>" class="empty">Belum ada data.</td></tr><?php endif; ?>
            <?php foreach ($rows as $index => $row): ?><tr><td class="number"><?= $index + 1 ?></td><?php if ($type === 'aspek'): ?><td><?= e($row['nama_aspek']) ?></td><td class="number"><?= (int) $row['jumlah_indikator'] ?></td><td><?= e(number_format((float) $row['total_bobot'], 2, ',', '.')) ?>%</td><?php else: ?><td><?= e($row['nama_indikator']) ?></td><td><?= e($row['deskripsi_indikator'] ?? '') ?></td><td><?= e($row['nama_aspek']) ?></td><td><?= e(trim((string) ($row['kode_skpd'] ?? '') . ((!empty($row['kode_skpd']) && !empty($row['nama_skpd'])) ? ' - ' : '') . (string) ($row['nama_skpd'] ?? ''))) ?><br><?= e($row['koordinator'] ?? '') ?></td><td><?= e(number_format((float) $row['bobot'], 2, ',', '.')) ?>%</td><td><?= e(number_format((float) $row['total_skor'], 2, ',', '.')) ?></td><?php endif; ?></tr><?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
