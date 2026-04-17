<?php
/**
 * LPPAI Corner - Import Users dari CSV
 */
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method tidak valid.']);
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!verifyCsrf($token)) {
    echo json_encode(['success' => false, 'message' => 'Sesi tidak valid.']);
    exit;
}

if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'File tidak ditemukan atau gagal diupload.']);
    exit;
}

$file = $_FILES['csv_file'];

// Validasi ekstensi
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['csv', 'txt'])) {
    echo json_encode(['success' => false, 'message' => 'File harus berformat CSV.']);
    exit;
}

// Validasi ukuran maksimal 2MB
if ($file['size'] > 2 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'Ukuran file maksimal 2MB.']);
    exit;
}

$handle = fopen($file['tmp_name'], 'r');
if (!$handle) {
    echo json_encode(['success' => false, 'message' => 'Gagal membaca file.']);
    exit;
}

// Hapus BOM UTF-8 jika ada
$bom = fread($handle, 3);
if ($bom !== "\xEF\xBB\xBF") {
    rewind($handle);
}

$pdo = getDBConnection();
$header = null;
$imported = 0;
$skipped = 0;
$errors = [];
$row = 0;

$expectedColumns = ['username', 'password', 'nama_lengkap', 'nim', 'email', 'no_hp', 'program_studi', 'fakultas', 'role'];

while (($line = fgetcsv($handle, 1000)) !== false) {
    $row++;

    // Baris pertama = header
    if ($row === 1) {
        // Normalize header
        $header = array_map(fn($h) => strtolower(trim($h)), $line);
        // Validasi kolom
        $missing = array_diff($expectedColumns, $header);
        if (!empty($missing)) {
            fclose($handle);
            echo json_encode(['success' => false, 'message' => 'Kolom tidak sesuai template. Kolom yang kurang: ' . implode(', ', $missing)]);
            exit;
        }
        continue;
    }

    // Skip baris kosong
    if (empty(array_filter($line))) continue;

    // Map ke kolom
    $data = array_combine($header, array_pad($line, count($header), ''));

    $username = trim($data['username'] ?? '');
    $password = trim($data['password'] ?? '');
    $nama = trim($data['nama_lengkap'] ?? '');
    $nim = trim($data['nim'] ?? '');
    $email = trim($data['email'] ?? '');
    $noHp = trim($data['no_hp'] ?? '');
    $prodi = trim($data['program_studi'] ?? '');
    $fakultas = trim($data['fakultas'] ?? '');
    $role = in_array(trim($data['role'] ?? ''), ['admin', 'mahasiswa']) ? trim($data['role']) : 'mahasiswa';

    // Validasi wajib
    if (empty($username) || empty($password) || empty($nama)) {
        $errors[] = "Baris $row: username, password, nama_lengkap wajib diisi.";
        $skipped++;
        continue;
    }

    // Cek duplikat username
    $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $check->execute([$username]);
    if ($check->fetch()) {
        $errors[] = "Baris $row: username '$username' sudah digunakan, dilewati.";
        $skipped++;
        continue;
    }

    // Insert
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, nim, email, no_hp, program_studi, fakultas, role) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$username, $hash, $nama, $nim, $email, $noHp, $prodi, $fakultas, $role]);
    $imported++;
}

fclose($handle);

$message = "$imported pengguna berhasil diimport.";
if ($skipped > 0) {
    $message .= " $skipped baris dilewati.";
}

echo json_encode([
    'success' => true,
    'message' => $message,
    'imported' => $imported,
    'skipped' => $skipped,
    'errors' => $errors,
]);
exit;
