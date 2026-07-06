<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_login();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    http_response_code(404);
    exit('Dokumen tidak ditemukan.');
}

$stmt = db()->prepare('SELECT nama_dokumen,file_upload FROM pemdi_evidence WHERE id=:id');
$stmt->execute(['id' => (int) $id]);
$record = $stmt->fetch();
if (!$record || empty($record['file_upload'])) {
    http_response_code(404);
    exit('Dokumen tidak ditemukan.');
}

$uploadRoot = realpath(__DIR__ . '/uploads/pemdi_evidence');
$filePath = realpath(__DIR__ . '/' . ltrim(str_replace('\\', '/', (string) $record['file_upload']), '/'));
$normalizedRoot = $uploadRoot !== false ? rtrim(str_replace('\\', '/', $uploadRoot), '/') . '/' : '';
$normalizedFile = $filePath !== false ? str_replace('\\', '/', $filePath) : '';
if ($uploadRoot === false || $filePath === false || !str_starts_with($normalizedFile, $normalizedRoot) || !is_file($filePath)) {
    http_response_code(404);
    exit('Dokumen tidak ditemukan.');
}

$downloadName = trim((string) preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $record['nama_dokumen']), '-');
if ($downloadName === '') $downloadName = 'pemdi-evidence-' . (int) $id;

header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($filePath));
header('Content-Disposition: attachment; filename="' . $downloadName . '.pdf"');
header('X-Content-Type-Options: nosniff');
readfile($filePath);
exit;
