<?php
/**
 * LPPAI Corner - Admin: Kelola Users
 */
define('PAGE_TITLE', 'Kelola Pengguna');
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pdo = getDBConnection();
$message = '';
$msgType = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrf($token)) {
        $message = 'Sesi tidak valid.';
        $msgType = 'danger';
    } else {
        $action = $_POST['action'];

        if ($action === 'create') {
            $nama = trim($_POST['nama_lengkap'] ?? '');
            $nim = trim($_POST['nim'] ?? '');
            $tglLahir = trim($_POST['tanggal_lahir'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $noHp = trim($_POST['no_hp'] ?? '');
            $prodi = trim($_POST['program_studi'] ?? '');
            $role = $_POST['role'] ?? 'mahasiswa';

            if (empty($nim) || empty($nama) || empty($tglLahir)) {
                $message = 'NIM, nama lengkap, dan tanggal lahir harus diisi.';
                $msgType = 'danger';
            } else {
                $username = $nim; // username otomatis = NIM
                $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $check->execute([$username]);
                if ($check->fetch()) {
                    $message = 'NIM sudah terdaftar.';
                    $msgType = 'danger';
                } else {
                    $dt = DateTime::createFromFormat('Y-m-d', $tglLahir);
                    $passwordRaw = $dt ? $dt->format('dmY') : str_replace('-', '', $tglLahir);
                    $hash = password_hash($passwordRaw, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, nim, email, no_hp, program_studi, tanggal_lahir, role) VALUES (?,?,?,?,?,?,?,?,?)");
                    $stmt->execute([$username, $hash, $nama, $nim, $email, $noHp, $prodi, $tglLahir, $role]);
                    $message = "User berhasil ditambahkan! Login: Username=<strong>$nim</strong>, Password=<strong>$passwordRaw</strong> (ddmmyyyy)";
                    $msgType = 'success';
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id === (int)$_SESSION['user_id']) {
                $message = 'Tidak bisa menghapus akun sendiri.';
                $msgType = 'danger';
            } else {
                $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
                $message = 'User berhasil dihapus.';
                $msgType = 'success';
            }
        } elseif ($action === 'reset_password') {
            $id = (int)($_POST['id'] ?? 0);
            $userRow = $pdo->prepare("SELECT tanggal_lahir FROM users WHERE id = ?");
            $userRow->execute([$id]);
            $userData = $userRow->fetch();
            if ($userData && !empty($userData['tanggal_lahir'])) {
                $dt = new DateTime($userData['tanggal_lahir']);
                $passwordRaw = $dt->format('dmy');
            } else {
                $passwordRaw = '123456';
            }
            $newPass = password_hash($passwordRaw, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$newPass, $id]);
            $message = 'Password berhasil direset ke tanggal lahir (ddmmyyyy): <strong>' . sanitize($passwordRaw) . '</strong>.';
            $msgType = 'success';
        }
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY role, nama_lengkap")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?= $msgType ?>"><?= sanitize($message) ?></div>
<?php endif; ?>

<!-- Import & Download Template -->
<div class="card">
    <div class="card-header">📥 Import Pengguna dari Excel</div>
    <div class="card-body" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
        <a href="<?= BASE_URL ?>/api/download-template.php" class="btn btn-primary" style="width:auto;">
            📄 Download Template Excel
        </a>
        <button type="button" class="btn btn-primary" style="width:auto;background:#2d7a4a;" onclick="document.getElementById('modal-import').style.display='flex'">
            📤 Import dari Excel
        </button>
        <small style="color:#888;">Download template .xlsx, isi data, lalu upload kembali.</small>
    </div>
</div>

<!-- Modal Import -->
<div id="modal-import" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;padding:32px;width:100%;max-width:480px;box-shadow:0 8px 40px rgba(0,0,0,0.2);">
        <h3 style="margin-bottom:16px;">📤 Import Pengguna dari Excel</h3>
        <p style="margin-bottom:16px;color:#666;font-size:14px;">
            Upload file Excel (.xlsx) sesuai template. Kolom wajib: <strong>nim, nama_lengkap, tanggal_lahir</strong>.<br>
            Username otomatis = NIM. Password otomatis = tanggal lahir format <strong>ddmmyyyy</strong>.<br>
            NIM yang sudah terdaftar akan dilewati.
        </p>
        <form id="form-import">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <div class="form-group">
                <label>Pilih File CSV</label>
                <input type="file" name="csv_file" id="csv_file" accept=".xlsx,.xls" required
                    style="padding:10px;border:2px dashed #ccc;border-radius:10px;width:100%;cursor:pointer;">
            </div>
            <div id="import-result" style="display:none;margin-bottom:16px;padding:12px;border-radius:10px;font-size:14px;"></div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px;">
                <button type="button" class="btn btn-sm btn-warning" onclick="document.getElementById('modal-import').style.display='none'">
                    Batal
                </button>
                <button type="submit" class="btn btn-primary" id="btn-import" style="width:auto;">
                    📤 Mulai Import
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('form-import').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const btn = document.getElementById('btn-import');
    const result = document.getElementById('import-result');

    btn.disabled = true;
    btn.textContent = 'Mengimport...';
    result.style.display = 'none';

    fetch('<?= BASE_URL ?>/api/import-users.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.text())
    .then(text => {
        result.style.display = 'block';
        let data;
        try {
            data = JSON.parse(text);
        } catch(e) {
            result.style.background = '#f8d7da';
            result.style.color = '#721c24';
            result.innerHTML = '<strong>❌ Server Error (bukan JSON):</strong><br><pre style="font-size:11px;white-space:pre-wrap;margin-top:8px;">' + text.substring(0, 1000) + '</pre>';
            return;
        }
        if (data.success) {
            result.style.background = '#d4edda';
            result.style.color = '#155724';
            let html = '<strong>✅ ' + data.message + '</strong>';
            if (data.errors && data.errors.length > 0) {
                html += '<ul style="margin-top:8px;padding-left:20px;">';
                data.errors.forEach(err => html += '<li>' + err + '</li>');
                html += '</ul>';
            }
            result.innerHTML = html;
            if (data.imported > 0) {
                setTimeout(() => location.reload(), 2000);
            }
        } else {
            result.style.background = '#f8d7da';
            result.style.color = '#721c24';
            result.innerHTML = '<strong>❌ ' + data.message + '</strong>';
        }
    })
    .catch(err => {
        result.style.display = 'block';
        result.style.background = '#f8d7da';
        result.style.color = '#721c24';
        result.innerHTML = '<strong>❌ Network error: ' + err.message + '</strong>';
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = '📤 Mulai Import';
    });
});

// Tutup modal jika klik luar
document.getElementById('modal-import').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>

<!-- Create User Form -->
<div class="card">
    <div class="card-header">➕ Tambah Pengguna Baru</div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="create">

            <div style="margin-bottom:12px;padding:12px;background:#e8f5e9;border-radius:10px;font-size:13px;color:#155724;">
                ℹ️ <strong>Username otomatis = NIM</strong>, <strong>Password otomatis = tanggal lahir format ddmmyyyy</strong> (contoh: 01031990)
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:16px;">
                <div class="form-group">
                    <label>Nama Lengkap *</label>
                    <input type="text" name="nama_lengkap" required placeholder="Nama lengkap">
                </div>
                <div class="form-group">
                    <label>NIM * <small style="color:#888;">(digunakan sebagai username login)</small></label>
                    <input type="text" name="nim" required placeholder="Nomor Induk Mahasiswa">
                </div>
                <div class="form-group">
                    <label>Tanggal Lahir * <small style="color:#888;">(digunakan sebagai password)</small></label>
                    <input type="date" name="tanggal_lahir" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="Email">
                </div>
                <div class="form-group">
                    <label>No. HP</label>
                    <input type="text" name="no_hp" placeholder="No. HP">
                </div>
                <div class="form-group">
                    <label>Program Studi</label>
                    <input type="text" name="program_studi" placeholder="Program studi">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" style="width:100%;padding:12px;border:2px solid #e0e0e0;border-radius:10px;">
                        <option value="mahasiswa">Mahasiswa</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width:auto;margin-top:10px;">👤 Tambah User</button>
        </form>
    </div>
</div>

<!-- User List -->
<div class="card">
    <div class="card-header">📋 Daftar Pengguna</div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="table-users" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>NIM (Username)</th>
                        <th>Nama</th>
                        <th>Tgl Lahir (Password)</th>
                        <th>Prodi</th>
                        <th>Role</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $i => $u): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= sanitize($u['nim'] ?? $u['username']) ?></strong></td>
                        <td><?= sanitize($u['nama_lengkap']) ?></td>
                        <td><?= !empty($u['tanggal_lahir']) ? date('d/m/Y', strtotime($u['tanggal_lahir'])) : '-' ?></td>
                        <td><?= sanitize($u['program_studi'] ?? '-') ?></td>
                        <td>
                            <span class="badge <?= $u['role'] === 'admin' ? 'badge-danger' : 'badge-primary' ?>">
                                <?= ucfirst($u['role']) ?>
                            </span>
                        </td>
                        <td style="display:flex;gap:6px;">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="action" value="reset_password">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-warning" data-confirm="Reset password ke tanggal lahir (ddmmyyyy)?">Reset Pass</button>
                            </form>
                            <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger" data-confirm="Yakin ingin menghapus user ini?" data-table="users" data-id="<?= $u['id'] ?>">Hapus</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
