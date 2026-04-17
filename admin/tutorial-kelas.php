<?php
/**
 * LPPAI Corner - Admin: Kelola Kelas Tutorial
 */
define('PAGE_TITLE', 'Kelola Kelas Tutorial');
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pdo = getDBConnection();
$message = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrf($token)) {
        $message = 'Sesi tidak valid.';
        $msgType = 'danger';
    } else {
        $action = $_POST['action'];

        if ($action === 'create') {
            $namaKelas = trim($_POST['nama_kelas'] ?? '');
            $mataKuliah = trim($_POST['mata_kuliah'] ?? '');
            $dosen = trim($_POST['dosen_pengampu'] ?? '');
            $hari = trim($_POST['hari'] ?? '');
            $jam = trim($_POST['jam'] ?? '');
            $ruangan = trim($_POST['ruangan'] ?? '');
            $gelombang = $_POST['gelombang'] ?? '';
            $semester = trim($_POST['semester'] ?? '');
            $kuota = (int)($_POST['kuota'] ?? 0);

            if (empty($namaKelas) || empty($mataKuliah) || !in_array($gelombang, ['gel1','gel2','mandiri'])) {
                $message = 'Nama kelas, mata kuliah, dan gelombang harus diisi.';
                $msgType = 'danger';
            } else {
                $stmt = $pdo->prepare("INSERT INTO tutorial_classes (nama_kelas, mata_kuliah, dosen_pengampu, hari, jam, ruangan, gelombang, semester, kuota) VALUES (?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$namaKelas, $mataKuliah, $dosen, $hari, $jam, $ruangan, $gelombang, $semester, $kuota]);
                $message = 'Kelas tutorial berhasil ditambahkan!';
                $msgType = 'success';
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare("DELETE FROM tutorial_classes WHERE id = ?")->execute([$id]);
            $message = 'Kelas berhasil dihapus.';
            $msgType = 'success';
        }
    }
}

$classes = $pdo->query("SELECT * FROM tutorial_classes ORDER BY gelombang, nama_kelas")->fetchAll();
$gelLabels = ['gel1' => 'Gelombang 1 (Ganjil)', 'gel2' => 'Gelombang 2 (Genap)', 'mandiri' => 'Mandiri'];

include __DIR__ . '/../includes/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?= $msgType ?>"><?= sanitize($message) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">➕ Tambah Kelas Tutorial</div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="create">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;">
                <div class="form-group">
                    <label>Nama Kelas *</label>
                    <input type="text" name="nama_kelas" placeholder="Kelas A" required>
                </div>
                <div class="form-group">
                    <label>Mata Kuliah *</label>
                    <input type="text" name="mata_kuliah" placeholder="Bahasa Arab Dasar" required>
                </div>
                <div class="form-group">
                    <label>Dosen Pengampu</label>
                    <input type="text" name="dosen_pengampu" placeholder="Dr. Ahmad">
                </div>
                <div class="form-group">
                    <label>Gelombang *</label>
                    <select name="gelombang" style="width:100%;padding:12px;border:2px solid #e0e0e0;border-radius:10px;" required>
                        <option value="">-- Pilih --</option>
                        <option value="gel1">Gelombang 1 (Ganjil)</option>
                        <option value="gel2">Gelombang 2 (Genap)</option>
                        <option value="mandiri">Mandiri</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Hari</label>
                    <input type="text" name="hari" placeholder="Senin">
                </div>
                <div class="form-group">
                    <label>Jam</label>
                    <input type="text" name="jam" placeholder="08:00-09:30">
                </div>
                <div class="form-group">
                    <label>Ruangan</label>
                    <input type="text" name="ruangan" placeholder="Ruang 101">
                </div>
                <div class="form-group">
                    <label>Semester</label>
                    <input type="text" name="semester" placeholder="2025/2026-Ganjil">
                </div>
                <div class="form-group">
                    <label>Kuota</label>
                    <input type="number" name="kuota" min="0" placeholder="30">
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:auto;margin-top:10px;">🏫 Tambah Kelas</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">📋 Daftar Kelas Tutorial (<?= count($classes) ?>)</div>
    <div class="card-body">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Kelas</th>
                        <th>Mata Kuliah</th>
                        <th>Dosen</th>
                        <th>Gelombang</th>
                        <th>Jadwal</th>
                        <th>Ruangan</th>
                        <th>Kuota</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classes as $c): ?>
                    <tr>
                        <td><strong><?= sanitize($c['nama_kelas']) ?></strong></td>
                        <td><?= sanitize($c['mata_kuliah']) ?></td>
                        <td><?= sanitize($c['dosen_pengampu'] ?? '-') ?></td>
                        <td><span class="badge badge-primary"><?= $gelLabels[$c['gelombang']] ?? $c['gelombang'] ?></span></td>
                        <td><?= sanitize(($c['hari'] ?? '-') . ', ' . ($c['jam'] ?? '-')) ?></td>
                        <td><?= sanitize($c['ruangan'] ?? '-') ?></td>
                        <td><?= $c['kuota'] ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger" data-confirm="Hapus kelas ini?" data-table="tutorial_classes" data-id="<?= $c['id'] ?>">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
