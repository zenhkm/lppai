<?php
/**
 * LPPAI Corner - Admin: Kelola Hasil Pretes
 */
define('PAGE_TITLE', 'Kelola Hasil Pretes');
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

        if ($action === 'add_result') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $scheduleId = (int)($_POST['schedule_id'] ?? 0);
            $nilai = $_POST['nilai'] !== '' ? (float)$_POST['nilai'] : null;
            $status = $_POST['status_lulus'] ?? 'belum_diumumkan';
            $keterangan = trim($_POST['keterangan'] ?? '');

            if ($userId <= 0) {
                $message = 'Pilih mahasiswa.';
                $msgType = 'danger';
            } else {
                // Check if result exists
                $check = $pdo->prepare("SELECT id FROM pretes_results WHERE user_id = ?");
                $check->execute([$userId]);
                if ($check->fetch()) {
                    $stmt = $pdo->prepare("UPDATE pretes_results SET nilai = ?, status_lulus = ?, keterangan = ?, pretes_schedule_id = ? WHERE user_id = ?");
                    $stmt->execute([$nilai, $status, $keterangan, $scheduleId ?: null, $userId]);
                    $message = 'Hasil pretes berhasil diperbarui!';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO pretes_results (user_id, pretes_schedule_id, nilai, status_lulus, keterangan) VALUES (?,?,?,?,?)");
                    $stmt->execute([$userId, $scheduleId ?: null, $nilai, $status, $keterangan]);
                    $message = 'Hasil pretes berhasil ditambahkan!';
                }
                $msgType = 'success';
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare("DELETE FROM pretes_results WHERE id = ?")->execute([$id]);
            $message = 'Hasil pretes berhasil dihapus.';
            $msgType = 'success';
        }
    }
}

// Get all students
$students = $pdo->query("SELECT id, nama_lengkap, nim FROM users WHERE role='mahasiswa' ORDER BY nama_lengkap")->fetchAll();
$schedules = $pdo->query("SELECT * FROM pretes_schedules ORDER BY tanggal DESC")->fetchAll();

// Get all results
$results = $pdo->query("
    SELECT pr.*, u.nama_lengkap, u.nim, u.program_studi, ps.periode, ps.tanggal as tgl_pretes
    FROM pretes_results pr
    JOIN users u ON pr.user_id = u.id
    LEFT JOIN pretes_schedules ps ON pr.pretes_schedule_id = ps.id
    ORDER BY pr.created_at DESC
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?= $msgType ?>"><?= sanitize($message) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">➕ Input/Update Hasil Pretes</div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="add_result">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;">
                <div class="form-group">
                    <label>Mahasiswa *</label>
                    <select name="user_id" style="width:100%;padding:12px;border:2px solid #e0e0e0;border-radius:10px;" required>
                        <option value="">-- Pilih Mahasiswa --</option>
                        <?php foreach ($students as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= sanitize($s['nama_lengkap']) ?> (<?= sanitize($s['nim']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Jadwal Pretes</label>
                    <select name="schedule_id" style="width:100%;padding:12px;border:2px solid #e0e0e0;border-radius:10px;">
                        <option value="">-- Pilih Jadwal --</option>
                        <?php foreach ($schedules as $sc): ?>
                            <option value="<?= $sc['id'] ?>"><?= sanitize($sc['periode']) ?> - <?= date('d M Y', strtotime($sc['tanggal'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nilai</label>
                    <input type="number" name="nilai" step="0.01" min="0" max="100" placeholder="85.50">
                </div>
                <div class="form-group">
                    <label>Status *</label>
                    <select name="status_lulus" style="width:100%;padding:12px;border:2px solid #e0e0e0;border-radius:10px;" required>
                        <option value="belum_diumumkan">Belum Diumumkan</option>
                        <option value="lulus">Lulus</option>
                        <option value="tidak_lulus">Tidak Lulus</option>
                    </select>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>Keterangan</label>
                    <input type="text" name="keterangan" placeholder="Keterangan tambahan">
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:auto;margin-top:10px;">📝 Simpan Hasil</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">📋 Daftar Hasil Pretes (<?= count($results) ?>)</div>
    <div class="card-body">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>NIM</th>
                        <th>Prodi</th>
                        <th>Periode</th>
                        <th>Nilai</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= sanitize($r['nama_lengkap']) ?></strong></td>
                        <td><?= sanitize($r['nim']) ?></td>
                        <td><?= sanitize($r['program_studi']) ?></td>
                        <td><?= sanitize($r['periode'] ?? '-') ?></td>
                        <td><strong><?= $r['nilai'] !== null ? number_format($r['nilai'], 1) : '-' ?></strong></td>
                        <td>
                            <?php
                            $statusBadge = ['lulus' => 'badge-success', 'tidak_lulus' => 'badge-danger', 'belum_diumumkan' => 'badge-warning'];
                            ?>
                            <span class="badge <?= $statusBadge[$r['status_lulus']] ?? 'badge-info' ?>">
                                <?= ucfirst(str_replace('_', ' ', $r['status_lulus'])) ?>
                            </span>
                        </td>
                        <td><?= sanitize($r['keterangan'] ?? '-') ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger" data-confirm="Hapus hasil ini?" data-table="pretes_results" data-id="<?= $r['id'] ?>">Hapus</button>
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
