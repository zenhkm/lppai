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
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $nama = trim($_POST['nama_lengkap'] ?? '');
            $nim = trim($_POST['nim'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $noHp = trim($_POST['no_hp'] ?? '');
            $prodi = trim($_POST['program_studi'] ?? '');
            $fakultas = trim($_POST['fakultas'] ?? '');
            $role = $_POST['role'] ?? 'mahasiswa';

            if (empty($username) || empty($password) || empty($nama)) {
                $message = 'Username, password, dan nama harus diisi.';
                $msgType = 'danger';
            } else {
                $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $check->execute([$username]);
                if ($check->fetch()) {
                    $message = 'Username sudah digunakan.';
                    $msgType = 'danger';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, nim, email, no_hp, program_studi, fakultas, role) VALUES (?,?,?,?,?,?,?,?,?)");
                    $stmt->execute([$username, $hash, $nama, $nim, $email, $noHp, $prodi, $fakultas, $role]);
                    $message = 'User berhasil ditambahkan!';
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
            $newPass = password_hash('123', PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$newPass, $id]);
            $message = 'Password berhasil direset ke "123".';
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

<!-- Create User Form -->
<div class="card">
    <div class="card-header">➕ Tambah Pengguna Baru</div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="create">

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:16px;">
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" required placeholder="Username login">
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required placeholder="Password">
                </div>
                <div class="form-group">
                    <label>Nama Lengkap *</label>
                    <input type="text" name="nama_lengkap" required placeholder="Nama lengkap">
                </div>
                <div class="form-group">
                    <label>NIM</label>
                    <input type="text" name="nim" placeholder="NIM (untuk mahasiswa)">
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
                    <label>Fakultas</label>
                    <input type="text" name="fakultas" placeholder="Fakultas">
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
    <div class="card-header">📋 Daftar Pengguna (<?= count($users) ?>)</div>
    <div class="card-body">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Username</th>
                        <th>Nama</th>
                        <th>NIM</th>
                        <th>Prodi</th>
                        <th>Role</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $i => $u): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= sanitize($u['username']) ?></strong></td>
                        <td><?= sanitize($u['nama_lengkap']) ?></td>
                        <td><?= sanitize($u['nim'] ?? '-') ?></td>
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
                                <button type="submit" class="btn btn-sm btn-warning" data-confirm="Reset password ke '123'?">Reset Pass</button>
                            </form>
                            <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger" data-confirm="Yakin ingin menghapus user ini?">Hapus</button>
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
