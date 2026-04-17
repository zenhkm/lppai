<?php
/**
 * LPPAI Corner - Admin: Data Peserta Tutorial
 */
define('PAGE_TITLE', 'Data Peserta Tutorial');
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

        if ($action === 'assign') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $classId = (int)($_POST['class_id'] ?? 0);
            if ($userId > 0 && $classId > 0) {
                $check = $pdo->prepare("SELECT id FROM tutorial_registrations WHERE user_id = ? AND tutorial_class_id = ?");
                $check->execute([$userId, $classId]);
                if ($check->fetch()) {
                    $message = 'Mahasiswa sudah terdaftar di kelas ini.';
                    $msgType = 'warning';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO tutorial_registrations (user_id, tutorial_class_id, status) VALUES (?, ?, 'terdaftar')");
                    $stmt->execute([$userId, $classId]);
                    $message = 'Mahasiswa berhasil ditambahkan ke kelas!';
                    $msgType = 'success';
                }
            }
        } elseif ($action === 'update_status') {
            $regId = (int)($_POST['reg_id'] ?? 0);
            $status = $_POST['status'] ?? '';
            $nilai = $_POST['nilai'] !== '' ? (float)$_POST['nilai'] : null;
            if (in_array($status, ['terdaftar', 'aktif', 'lulus', 'tidak_lulus', 'mengundurkan_diri'])) {
                $stmt = $pdo->prepare("UPDATE tutorial_registrations SET status = ?, nilai_akhir = ? WHERE id = ?");
                $stmt->execute([$status, $nilai, $regId]);
                $message = 'Status berhasil diperbarui.';
                $msgType = 'success';
            }
        } elseif ($action === 'delete') {
            $regId = (int)($_POST['reg_id'] ?? 0);
            $pdo->prepare("DELETE FROM tutorial_registrations WHERE id = ?")->execute([$regId]);
            $message = 'Data berhasil dihapus.';
            $msgType = 'success';
        }
    }
}

$students = $pdo->query("SELECT id, nama_lengkap, nim FROM users WHERE role='mahasiswa' ORDER BY nama_lengkap")->fetchAll();
$classes = $pdo->query("SELECT * FROM tutorial_classes ORDER BY gelombang, nama_kelas")->fetchAll();
$gelLabels = ['gel1' => 'Gel.1', 'gel2' => 'Gel.2', 'mandiri' => 'Mandiri'];

$registrations = $pdo->query("
    SELECT tr.*, u.nama_lengkap, u.nim, u.program_studi, tc.nama_kelas, tc.mata_kuliah, tc.gelombang
    FROM tutorial_registrations tr
    JOIN users u ON tr.user_id = u.id
    JOIN tutorial_classes tc ON tr.tutorial_class_id = tc.id
    ORDER BY tc.gelombang, tc.nama_kelas, u.nama_lengkap
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?= $msgType ?>"><?= sanitize($message) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">➕ Tambah Peserta ke Kelas</div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="assign">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:16px;">
                <div class="form-group">
                    <label>Mahasiswa</label>
                    <select name="user_id" style="width:100%;padding:12px;border:2px solid #e0e0e0;border-radius:10px;" required>
                        <option value="">-- Pilih Mahasiswa --</option>
                        <?php foreach ($students as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= sanitize($s['nama_lengkap']) ?> (<?= sanitize($s['nim']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Kelas Tutorial</label>
                    <select name="class_id" style="width:100%;padding:12px;border:2px solid #e0e0e0;border-radius:10px;" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>">[<?= $gelLabels[$c['gelombang']] ?>] <?= sanitize($c['nama_kelas']) ?> - <?= sanitize($c['mata_kuliah']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:auto;margin-top:10px;">📋 Tambah Peserta</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">📋 Daftar Peserta Tutorial (<?= count($registrations) ?>)</div>
    <div class="card-body">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>NIM</th>
                        <th>Prodi</th>
                        <th>Kelas</th>
                        <th>Gel.</th>
                        <th>Status</th>
                        <th>Nilai</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrations as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= sanitize($r['nama_lengkap']) ?></strong></td>
                        <td><?= sanitize($r['nim']) ?></td>
                        <td><?= sanitize($r['program_studi']) ?></td>
                        <td><?= sanitize($r['nama_kelas']) ?> - <?= sanitize($r['mata_kuliah']) ?></td>
                        <td><span class="badge badge-primary"><?= $gelLabels[$r['gelombang']] ?></span></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="reg_id" value="<?= $r['id'] ?>">
                                <select name="status" onchange="this.form.submit()" style="padding:4px 8px;border-radius:6px;border:1px solid #ddd;font-size:12px;">
                                    <?php foreach (['terdaftar','aktif','lulus','tidak_lulus','mengundurkan_diri'] as $st): ?>
                                        <option value="<?= $st ?>" <?= $r['status'] === $st ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$st)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="nilai" value="<?= $r['nilai_akhir'] ?? '' ?>">
                            </form>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="reg_id" value="<?= $r['id'] ?>">
                                <input type="hidden" name="status" value="<?= $r['status'] ?>">
                                <input type="number" name="nilai" step="0.1" min="0" max="100" value="<?= $r['nilai_akhir'] ?? '' ?>"
                                    style="width:70px;padding:4px;border-radius:6px;border:1px solid #ddd;font-size:12px;"
                                    onchange="this.form.submit()" placeholder="-">
                            </form>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="reg_id" value="<?= $r['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger" data-confirm="Hapus data ini?" data-table="tutorial_registrations" data-id="<?= $r['id'] ?>">Hapus</button>
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
