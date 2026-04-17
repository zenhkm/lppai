<?php
/**
 * LPPAI Corner - Download Template Import User (CSV)
 */
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="template_import_users.csv"');
header('Cache-Control: no-cache');

// BOM UTF-8 agar Excel bisa baca karakter Indonesia
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

// Header row
fputcsv($out, ['nim', 'nama_lengkap', 'tanggal_lahir', 'email', 'no_hp', 'program_studi', 'role']);

// Contoh data (tanggal_lahir format ddmmyy atau yyyy-mm-dd)
fputcsv($out, ['2024010006', 'Contoh Mahasiswa', '010390', 'contoh@mail.com', '081234567895', 'Teknik Informatika', 'mahasiswa']);
fputcsv($out, ['2024010007', 'Contoh Kedua', '150595', 'contoh2@mail.com', '081234567896', 'Manajemen', 'mahasiswa']);

fclose($out);
exit;
