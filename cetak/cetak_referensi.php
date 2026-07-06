<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_login();

$references = [
    'rab' => ['label' => 'Referensi Arsitektur Bisnis', 'short' => 'RAB'],
    'ral' => ['label' => 'Referensi Arsitektur Layanan', 'short' => 'RAL'],
    'rad' => ['label' => 'Referensi Arsitektur Data', 'short' => 'RAD'],
    'raa' => ['label' => 'Referensi Arsitektur Aplikasi', 'short' => 'RAA'],
    'rai' => ['label' => 'Referensi Arsitektur Infrastruktur', 'short' => 'RAI'],
    'rak' => ['label' => 'Referensi Arsitektur Keamanan', 'short' => 'RAK'],
];

$type = strtolower(trim((string) ($_GET['jenis'] ?? '')));
$reference = $references[$type] ?? null;
if (!$reference) {
    http_response_code(404);
    exit('Jenis referensi arsitektur tidak valid.');
}

$columns = [];
for ($level = 1; $level <= 4; $level++) {
    $columns[] = "kode_{$type}_{$level}";
    $columns[] = "nama_{$type}_{$level}";
}
$rows = db()->query(
    'SELECT ' . implode(', ', $columns) .
    " FROM {$type}
      ORDER BY kode_{$type}_1, kode_{$type}_2, kode_{$type}_3, kode_{$type}_4"
)->fetchAll();

function print_reference_value(array $row, string $type, int $level): string
{
    $code = trim((string) ($row["kode_{$type}_{$level}"] ?? ''));
    $name = trim((string) ($row["nama_{$type}_{$level}"] ?? ''));
    return trim($code . ($code !== '' && $name !== '' ? ' ' : '') . $name);
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak <?= e($reference['short']) ?> - <?= e(APP_NAME) ?></title>
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
        th, td { border: 1px solid #475569; padding: 4px 5px; vertical-align: top; overflow-wrap: anywhere; line-height: 1.25; }
        th { background: #e2e8f0; text-align: center; font-size: 8px; }
        td { font-size: 7.5px; }
        tbody tr { break-inside: avoid; }
        .empty { padding: 20px; text-align: center; color: #64748b; }
        @media print {
            .toolbar { display: none; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="../index.php?page=<?= e($type) ?>">Kembali</a>
        <button type="button" class="primary" onclick="window.print()">Cetak Sekarang</button>
    </div>

    <h1><?= e($reference['label']) ?> (<?= e($reference['short']) ?>)</h1>
    <p class="meta">Jumlah data: <?= number_format(count($rows), 0, ',', '.') ?> &middot; Dicetak <?= e(date('d/m/Y H:i')) ?></p>

    <table>
        <thead>
            <tr>
                <th>Level 1</th>
                <th>Level 2</th>
                <th>Level 3</th>
                <th>Level 4</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="4" class="empty">Belum ada data referensi.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <?php for ($level = 1; $level <= 4; $level++): ?>
                            <td><?= e(print_reference_value($row, $type, $level)) ?></td>
                        <?php endfor; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
