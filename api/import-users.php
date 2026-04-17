<?php
/**
 * LPPAI Corner - Import Users dari Excel
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
if (!in_array($ext, ['xlsx', 'xls'])) {
    echo json_encode(['success' => false, 'message' => 'File harus berformat Excel (.xlsx atau .xls).']);
    exit;
}

// Validasi ukuran maksimal 5MB
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'Ukuran file maksimal 5MB.']);
    exit;
}

// Cari autoload.php di berbagai kemungkinan path
$autoloadPaths = [
    '/public_html/vendor/autoload.php',
    __DIR__ . '/../../../../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
    dirname(__DIR__, 3) . '/vendor/autoload.php',
];
$autoloaded = false;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $autoloaded = true;
        break;
    }
}
if (!$autoloaded) {
    echo json_encode(['success' => false, 'message' => 'PhpSpreadsheet tidak ditemukan. Paths dicoba: ' . implode(', ', $autoloadPaths)]);
    exit;
}

use PhpOffice\PhpSpreadsheet\IOFactory;

try {
    $spreadsheet = IOFactory::load($file['tmp_name']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Gagal membaca file Excel: ' . $e->getMessage()]);
    exit;
}

$sheet = $spreadsheet->getActiveSheet();
$rows = $sheet->toArray(null, true, true, false);

if (empty($rows)) {
    echo json_encode(['success' => false, 'message' => 'File Excel kosong.']);
    exit;
}

// Baris pertama = header, normalize
$headerRow = array_map(fn($h) => strtolower(trim(str_replace([' (ddmmyyyy)', ' (mahasiswa/admin)', 'no. '], ['', '', 'no_'], (string)$h))), $rows[0]);
// Mapping lebih fleksibel
$colMap = [];
foreach ($headerRow as $idx => $h) {
    if (str_contains($h, 'nim')) $colMap['nim'] = $idx;
    elseif (str_contains($h, 'nama')) $colMap['nama_lengkap'] = $idx;
    elseif (str_contains($h, 'tanggal') || str_contains($h, 'lahir') || str_contains($h, 'tgl')) $colMap['tanggal_lahir'] = $idx;
    elseif (str_contains($h, 'email')) $colMap['email'] = $idx;
    elseif (str_contains($h, 'hp') || str_contains($h, 'phone') || str_contains($h, 'telp')) $colMap['no_hp'] = $idx;
    elseif (str_contains($h, 'prodi') || str_contains($h, 'program') || str_contains($h, 'studi')) $colMap['program_studi'] = $idx;
    elseif (str_contains($h, 'role') || str_contains($h, 'peran')) $colMap['role'] = $idx;
}

$required = ['nim', 'nama_lengkap', 'tanggal_lahir'];
$missing = array_diff($required, array_keys($colMap));
if (!empty($missing)) {
    echo json_encode(['success' => false, 'message' => 'Kolom wajib tidak ditemukan: ' . implode(', ', $missing) . '. Pastikan menggunakan template yang benar.']);
    exit;
}

$pdo = getDBConnection();
$imported = 0;
$skipped = 0;
$errors = [];

foreach (array_slice($rows, 1) as $rowNum => $row) {
    $dataRow = $rowNum + 2; // row number in Excel (1-based + header)

    $nim = trim((string)($row[$colMap['nim']] ?? ''));
    $nama = trim((string)($row[$colMap['nama_lengkap']] ?? ''));
    $tglRaw = trim((string)($row[$colMap['tanggal_lahir']] ?? ''));
    $email = trim((string)($row[$colMap['email'] ?? -1] ?? ''));
    $noHp = trim((string)($row[$colMap['no_hp'] ?? -1] ?? ''));
    $prodi = trim((string)($row[$colMap['program_studi'] ?? -1] ?? ''));
    $roleVal = strtolower(trim((string)($row[$colMap['role'] ?? -1] ?? '')));
    $role = in_array($roleVal, ['admin', 'mahasiswa']) ? $roleVal : 'mahasiswa';

    // Skip baris kosong
    if (empty($nim) && empty($nama)) continue;

    // Validasi wajib
    if (empty($nim) || empty($nama) || empty($tglRaw)) {
        $errors[] = "Baris $dataRow: nim, nama_lengkap, tanggal_lahir wajib diisi.";
        $skipped++;
        continue;
    }

    // Parse tanggal lahir - berbagai format
    $dt = false;
    // Jika angka (Excel serial date)
    if (is_numeric($tglRaw) && strlen($tglRaw) != 8) {
        // Excel date serial
        $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$tglRaw);
    } else {
        foreach (['dmY', 'd/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y', 'd-m-y', 'dmy'] as $fmt) {
            $dt = DateTime::createFromFormat($fmt, $tglRaw);
            if ($dt) break;
        }
    }

    if (!$dt) {
        $errors[] = "Baris $dataRow: format tanggal_lahir '$tglRaw' tidak dikenali (gunakan ddmmyyyy, contoh: 01031990).";
        $skipped++;
        continue;
    }

    $tglLahirDB = $dt->format('Y-m-d');
    $passwordRaw = $dt->format('dmY'); // ddmmyyyy
    $username = $nim;

    // Cek duplikat
    $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $check->execute([$username]);
    if ($check->fetch()) {
        $errors[] = "Baris $dataRow: NIM '$nim' sudah terdaftar, dilewati.";
        $skipped++;
        continue;
    }

    // Insert
    $hash = password_hash($passwordRaw, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, nim, email, no_hp, program_studi, tanggal_lahir, role) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$username, $hash, $nama, $nim, $email, $noHp, $prodi, $tglLahirDB, $role]);
    $imported++;
}

$message = "$imported pengguna berhasil diimport.";
if ($skipped > 0) $message .= " $skipped baris dilewati.";

echo json_encode([
    'success' => true,
    'message' => $message,
    'imported' => $imported,
    'skipped' => $skipped,
    'errors' => $errors,
]);
exit;
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

$expectedColumns = ['nim', 'nama_lengkap', 'tanggal_lahir', 'email', 'no_hp', 'program_studi', 'role'];

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

    $nim = trim($data['nim'] ?? '');
    $nama = trim($data['nama_lengkap'] ?? '');
    $tglLahir = trim($data['tanggal_lahir'] ?? '');
    $email = trim($data['email'] ?? '');
    $noHp = trim($data['no_hp'] ?? '');
    $prodi = trim($data['program_studi'] ?? '');
    $role = in_array(trim($data['role'] ?? ''), ['admin', 'mahasiswa']) ? trim($data['role']) : 'mahasiswa';
    $username = $nim; // username = NIM

    // Validasi wajib
    if (empty($nim) || empty($nama) || empty($tglLahir)) {
        $errors[] = "Baris $row: nim, nama_lengkap, tanggal_lahir wajib diisi.";
        $skipped++;
        continue;
    }

    // Parse tanggal_lahir (ddmmyyyy atau dd/mm/yyyy atau dd-mm-yyyy atau yyyy-mm-dd)
    $dt = false;
    foreach (['d/m/y', 'd-m-y', 'dmy', 'Y-m-d', 'd/m/Y', 'd-m-Y'] as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $tglLahir);
        if ($dt) break;
    }
    if (!$dt) {
        $errors[] = "Baris $row: format tanggal_lahir '$tglLahir' tidak dikenali (gunakan ddmmyyyy atau yyyy-mm-dd).";
        $skipped++;
        continue;
    }
    $tglLahirDB = $dt->format('Y-m-d');
    $passwordRaw = $dt->format('dmY'); // ddmmyyyy

    // Cek duplikat NIM/username
    $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $check->execute([$username]);
    if ($check->fetch()) {
        $errors[] = "Baris $row: NIM '$nim' sudah terdaftar, dilewati.";
        $skipped++;
        continue;
    }

    // Insert
    $hash = password_hash($passwordRaw, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, nim, email, no_hp, program_studi, tanggal_lahir, role) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$username, $hash, $nama, $nim, $email, $noHp, $prodi, $tglLahirDB, $role]);
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
