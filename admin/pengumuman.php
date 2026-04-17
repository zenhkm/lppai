<?php
/**
 * LPPAI Corner - Admin: Kelola Pengumuman
 */
define('PAGE_TITLE', 'Kelola Pengumuman');
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pdo = getDBConnection();
$user = getCurrentUser();
$message = '';
$msgType = '';

$tipeOptions = [
    'pendaftaran_gel1' => 'Pendaftaran Tutorial Gel. 1 (Ganjil)',
    'pembagian_kelas_gel1' => 'Pembagian Kelas Gel. 1 (Ganjil)',
    'kelulusan_gel1' => 'Kelulusan Gel. 1 (Ganjil)',
    'pendaftaran_gel2' => 'Pendaftaran Tutorial Gel. 2 (Genap)',
    'pembagian_kelas_gel2' => 'Pembagian Kelas Gel. 2 (Genap)',
    'kelulusan_gel2' => 'Kelulusan Gel. 2 (Genap)',
    'pendaftaran_mandiri' => 'Pendaftaran Tutorial Mandiri',
    'pembagian_kelas_mandiri' => 'Pembagian Kelas Mandiri',
    'umum' => 'Pengumuman Umum',
];

// Handle create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrf($token)) {
        $message = 'Sesi tidak valid.';
        $msgType = 'danger';
    } else {
        $action = $_POST['action'];

        if ($action === 'create') {
            $judul = trim($_POST['judul'] ?? '');
            $konten = trim($_POST['konten'] ?? '');
            $tipe = $_POST['tipe'] ?? '';

            if (empty($judul) || empty($konten) || !isset($tipeOptions[$tipe])) {
                $message = 'Semua field harus diisi dengan benar.';
                $msgType = 'danger';
            } else {
                $stmt = $pdo->prepare("INSERT INTO announcements (judul, konten, tipe, created_by) VALUES (?, ?, ?, ?)");
                $stmt->execute([$judul, $konten, $tipe, $user['id']]);
                $message = 'Pengumuman berhasil ditambahkan!';
                $msgType = 'success';
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare("DELETE FROM announcements WHERE id = ?")->execute([$id]);
            $message = 'Pengumuman berhasil dihapus.';
            $msgType = 'success';
        } elseif ($action === 'toggle') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare("UPDATE announcements SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
            $message = 'Status pengumuman diperbarui.';
            $msgType = 'success';
        }
    }
}

$announcements = $pdo->query("SELECT a.*, u.nama_lengkap as author FROM announcements a LEFT JOIN users u ON a.created_by = u.id ORDER BY a.created_at DESC")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?= $msgType ?>"><?= sanitize($message) ?></div>
<?php endif; ?>

<!-- Create Form -->
<div class="card">
    <div class="card-header">➕ Tambah Pengumuman Baru</div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="create">

            <div class="form-group">
                <label>Tipe Pengumuman</label>
                <select name="tipe" style="width:100%;padding:12px;border:2px solid #e0e0e0;border-radius:10px;font-size:14px;" required>
                    <option value="">-- Pilih Tipe --</option>
                    <?php foreach ($tipeOptions as $val => $label): ?>
                        <option value="<?= $val ?>"><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Judul</label>
                <input type="text" name="judul" placeholder="Judul pengumuman" required>
            </div>

            <div class="form-group">
                <label>Konten</label>
                <textarea name="konten" rows="5" placeholder="Isi pengumuman..." required
                    style="width:100%;padding:12px;border:2px solid #e0e0e0;border-radius:10px;font-size:14px;resize:vertical;"></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width:auto;">📢 Simpan Pengumuman</button>
        </form>
    </div>
</div>

<!-- List -->
<div class="card">
    <div class="card-header">📋 Daftar Pengumuman (<?= count($announcements) ?>)</div>
    <div class="card-body">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Tipe</th>
                        <th>Status</th>
                        <th>Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($announcements as $a): ?>
                    <tr>
                        <td><strong><?= sanitize($a['judul']) ?></strong></td>
                        <td><span class="badge badge-info"><?= $tipeOptions[$a['tipe']] ?? $a['tipe'] ?></span></td>
                        <td>
                            <?php if ($a['is_active']): ?>
                                <span class="badge badge-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d M Y', strtotime($a['created_at'])) ?></td>
                        <td style="display:flex;gap:6px;">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-warning">
                                    <?= $a['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                                </button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger" data-confirm="Yakin ingin menghapus pengumuman ini?">Hapus</button>
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
