<?php
/**
 * LPPAI Corner - Admin: Data Peserta Pretes
 */
define('PAGE_TITLE', 'Data Peserta Pretes');
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pdo = getDBConnection();

$participants = $pdo->query("
    SELECT pr.*, u.nama_lengkap, u.nim, u.program_studi, u.fakultas, u.email, u.no_hp
    FROM pretes_registrations pr
    JOIN users u ON pr.user_id = u.id
    ORDER BY pr.tanggal_daftar DESC
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">👥 Data Peserta Pretes (<?= count($participants) ?> peserta)</div>
    <div class="card-body">
        <?php if (empty($participants)): ?>
            <div class="empty-state">
                <div class="icon">👥</div>
                <h3>Belum ada peserta</h3>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>NIM</th>
                        <th>Prodi</th>
                        <th>Fakultas</th>
                        <th>Email</th>
                        <th>No HP</th>
                        <th>Periode</th>
                        <th>Tgl Daftar</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($participants as $i => $p): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= sanitize($p['nama_lengkap']) ?></strong></td>
                        <td><?= sanitize($p['nim']) ?></td>
                        <td><?= sanitize($p['program_studi']) ?></td>
                        <td><?= sanitize($p['fakultas']) ?></td>
                        <td><?= sanitize($p['email']) ?></td>
                        <td><?= sanitize($p['no_hp']) ?></td>
                        <td><?= sanitize($p['periode']) ?></td>
                        <td><?= date('d M Y', strtotime($p['tanggal_daftar'])) ?></td>
                        <td><span class="badge badge-success"><?= ucfirst($p['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
