<?php
/**
 * LPPAI Corner - Admin: Kelola Jadwal Pretes
 */
define('PAGE_TITLE', 'Kelola Jadwal Pretes');
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
            $periode = trim($_POST['periode'] ?? '');
            $tanggal = $_POST['tanggal'] ?? '';
            $waktuMulai = $_POST['waktu_mulai'] ?? '';
            $waktuSelesai = $_POST['waktu_selesai'] ?? '';
            $ruangan = trim($_POST['ruangan'] ?? '');
            $kuota = (int)($_POST['kuota'] ?? 0);

            if (empty($periode) || empty($tanggal) || empty($waktuMulai) || empty($waktuSelesai) || empty($ruangan) || $kuota <= 0) {
                $message = 'Semua field harus diisi.';
                $msgType = 'danger';
            } else {
                $stmt = $pdo->prepare("INSERT INTO pretes_schedules (periode, tanggal, waktu_mulai, waktu_selesai, ruangan, kuota) VALUES (?,?,?,?,?,?)");
                $stmt->execute([$periode, $tanggal, $waktuMulai, $waktuSelesai, $ruangan, $kuota]);
                $message = 'Jadwal pretes berhasil ditambahkan!';
                $msgType = 'success';
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare("DELETE FROM pretes_schedules WHERE id = ?")->execute([$id]);
            $message = 'Jadwal berhasil dihapus.';
            $msgType = 'success';
        } elseif ($action === 'update_status') {
            $id = (int)($_POST['id'] ?? 0);
            $status = $_POST['status'] ?? '';
            if (in_array($status, ['aktif', 'selesai', 'dibatalkan'])) {
                $pdo->prepare("UPDATE pretes_schedules SET status = ? WHERE id = ?")->execute([$status, $id]);
                $message = 'Status jadwal diperbarui.';
                $msgType = 'success';
            }
        }
    }
}

$schedules = $pdo->query("SELECT * FROM pretes_schedules ORDER BY tanggal DESC, waktu_mulai")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?= $msgType ?>"><?= sanitize($message) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">➕ Tambah Jadwal Pretes</div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="create">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;">
                <div class="form-group">
                    <label>Periode</label>
                    <input type="text" name="periode" placeholder="2025/2026-Ganjil" required>
                </div>
                <div class="form-group">
                    <label>Tanggal</label>
                    <input type="date" name="tanggal" required>
                </div>
                <div class="form-group">
                    <label>Waktu Mulai</label>
                    <input type="time" name="waktu_mulai" required>
                </div>
                <div class="form-group">
                    <label>Waktu Selesai</label>
                    <input type="time" name="waktu_selesai" required>
                </div>
                <div class="form-group">
                    <label>Ruangan</label>
                    <input type="text" name="ruangan" placeholder="Gedung A - Ruang 101" required>
                </div>
                <div class="form-group">
                    <label>Kuota</label>
                    <input type="number" name="kuota" min="1" placeholder="50" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:auto;margin-top:10px;">📅 Tambah Jadwal</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">📋 Daftar Jadwal Pretes</div>
    <div class="card-body">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Periode</th>
                        <th>Tanggal</th>
                        <th>Waktu</th>
                        <th>Ruangan</th>
                        <th>Kuota</th>
                        <th>Terisi</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $s): ?>
                    <tr>
                        <td><?= sanitize($s['periode']) ?></td>
                        <td><?= date('d M Y', strtotime($s['tanggal'])) ?></td>
                        <td><?= date('H:i', strtotime($s['waktu_mulai'])) ?> - <?= date('H:i', strtotime($s['waktu_selesai'])) ?></td>
                        <td><?= sanitize($s['ruangan']) ?></td>
                        <td><?= $s['kuota'] ?></td>
                        <td><?= $s['terisi'] ?></td>
                        <td>
                            <?php
                            $badges = ['aktif' => 'badge-success', 'selesai' => 'badge-info', 'dibatalkan' => 'badge-danger'];
                            ?>
                            <span class="badge <?= $badges[$s['status']] ?? 'badge-info' ?>"><?= ucfirst($s['status']) ?></span>
                        </td>
                        <td style="display:flex;gap:4px;flex-wrap:wrap;">
                            <?php foreach (['aktif', 'selesai', 'dibatalkan'] as $st): ?>
                                <?php if ($s['status'] !== $st): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                    <input type="hidden" name="status" value="<?= $st ?>">
                                    <button type="submit" class="btn btn-sm btn-secondary"><?= ucfirst($st) ?></button>
                                </form>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger" data-confirm="Hapus jadwal ini?">Hapus</button>
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
