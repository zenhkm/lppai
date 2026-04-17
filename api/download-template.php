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
fputcsv($out, ['username', 'password', 'nama_lengkap', 'nim', 'email', 'no_hp', 'program_studi', 'fakultas', 'role']);

// Contoh data
fputcsv($out, ['mhs006', 'password123', 'Contoh Mahasiswa', '2024010006', 'contoh@mail.com', '081234567895', 'Teknik Informatika', 'Fakultas Teknik', 'mahasiswa']);
fputcsv($out, ['mhs007', 'password123', 'Contoh Kedua', '2024010007', 'contoh2@mail.com', '081234567896', 'Manajemen', 'Fakultas Ekonomi', 'mahasiswa']);

fclose($out);
exit;
