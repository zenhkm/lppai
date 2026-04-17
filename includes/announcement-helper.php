<?php
/**
 * LPPAI Corner - Helper untuk halaman pengumuman tutorial
 */

function renderAnnouncementPage($tipe, $gelombang, $title) {
    $pdo = getDBConnection();
    $user = getCurrentUser();

    // Get announcements
    $stmt = $pdo->prepare("SELECT * FROM announcements WHERE tipe = ? AND is_active = 1 ORDER BY created_at DESC");
    $stmt->execute([$tipe]);
    $announcements = $stmt->fetchAll();

    // Get classes if applicable (pembagian kelas)
    $classes = [];
    $myClass = null;
    if (strpos($tipe, 'pembagian') !== false) {
        $stmt = $pdo->prepare("SELECT * FROM tutorial_classes WHERE gelombang = ? ORDER BY nama_kelas");
        $stmt->execute([$gelombang]);
        $classes = $stmt->fetchAll();

        // Get user's class assignment
        $stmt = $pdo->prepare("
            SELECT tr.*, tc.nama_kelas, tc.mata_kuliah, tc.dosen_pengampu, tc.hari, tc.jam, tc.ruangan
            FROM tutorial_registrations tr
            JOIN tutorial_classes tc ON tr.tutorial_class_id = tc.id
            WHERE tr.user_id = ? AND tc.gelombang = ?
        ");
        $stmt->execute([$user['id'], $gelombang]);
        $myClass = $stmt->fetch();
    }

    // Get graduation results if applicable
    $graduationResults = [];
    $myGraduation = null;
    if (strpos($tipe, 'kelulusan') !== false) {
        $stmt = $pdo->prepare("
            SELECT tr.*, tc.nama_kelas, tc.mata_kuliah, u.nama_lengkap, u.nim, u.program_studi
            FROM tutorial_registrations tr
            JOIN tutorial_classes tc ON tr.tutorial_class_id = tc.id
            JOIN users u ON tr.user_id = u.id
            WHERE tc.gelombang = ? AND tr.status IN ('lulus', 'tidak_lulus')
            ORDER BY tr.status ASC, u.nama_lengkap
        ");
        $stmt->execute([$gelombang]);
        $graduationResults = $stmt->fetchAll();

        $stmt = $pdo->prepare("
            SELECT tr.*, tc.nama_kelas, tc.mata_kuliah
            FROM tutorial_registrations tr
            JOIN tutorial_classes tc ON tr.tutorial_class_id = tc.id
            WHERE tr.user_id = ? AND tc.gelombang = ?
        ");
        $stmt->execute([$user['id'], $gelombang]);
        $myGraduation = $stmt->fetch();
    }

    ?>
    <!-- Announcements -->
    <?php if (!empty($announcements)): ?>
        <?php foreach ($announcements as $ann): ?>
        <div class="announcement-card">
            <div class="ann-title"><?= sanitize($ann['judul']) ?></div>
            <div class="ann-date">🕐 <?= date('d M Y, H:i', strtotime($ann['created_at'])) ?></div>
            <div class="ann-content"><?= nl2br(sanitize($ann['konten'])) ?></div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <div class="empty-state">
                    <div class="icon">📢</div>
                    <h3>Belum ada pengumuman</h3>
                    <p>Pengumuman akan ditampilkan ketika tersedia.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Class Assignment (Pembagian Kelas) -->
    <?php if (strpos($tipe, 'pembagian') !== false): ?>
        <?php if ($myClass): ?>
        <div class="card" style="border-left:4px solid var(--primary);">
            <div class="card-header">🏫 Kelas Anda</div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
                    <div>
                        <strong style="color:var(--text-muted);font-size:12px;">KELAS</strong>
                        <p style="font-size:18px;font-weight:700;"><?= sanitize($myClass['nama_kelas']) ?></p>
                    </div>
                    <div>
                        <strong style="color:var(--text-muted);font-size:12px;">MATA KULIAH</strong>
                        <p style="font-size:16px;"><?= sanitize($myClass['mata_kuliah']) ?></p>
                    </div>
                    <div>
                        <strong style="color:var(--text-muted);font-size:12px;">DOSEN</strong>
                        <p><?= sanitize($myClass['dosen_pengampu']) ?></p>
                    </div>
                    <div>
                        <strong style="color:var(--text-muted);font-size:12px;">JADWAL</strong>
                        <p><?= sanitize($myClass['hari']) ?>, <?= sanitize($myClass['jam']) ?></p>
                    </div>
                    <div>
                        <strong style="color:var(--text-muted);font-size:12px;">RUANGAN</strong>
                        <p><?= sanitize($myClass['ruangan']) ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($classes)): ?>
        <div class="card">
            <div class="card-header">📋 Daftar Kelas Tutorial</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Kelas</th>
                                <th>Mata Kuliah</th>
                                <th>Dosen</th>
                                <th>Hari</th>
                                <th>Jam</th>
                                <th>Ruangan</th>
                                <th>Kuota</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($classes as $c): ?>
                            <tr>
                                <td><?= sanitize($c['nama_kelas']) ?></td>
                                <td><?= sanitize($c['mata_kuliah']) ?></td>
                                <td><?= sanitize($c['dosen_pengampu']) ?></td>
                                <td><?= sanitize($c['hari']) ?></td>
                                <td><?= sanitize($c['jam']) ?></td>
                                <td><?= sanitize($c['ruangan']) ?></td>
                                <td><?= $c['kuota'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Graduation Results -->
    <?php if (strpos($tipe, 'kelulusan') !== false): ?>
        <?php if ($myGraduation): ?>
        <div class="card" style="border-left:4px solid <?= $myGraduation['status'] === 'lulus' ? '#28a745' : '#dc3545' ?>;">
            <div class="card-header">🎓 Status Kelulusan Anda</div>
            <div class="card-body">
                <div style="text-align:center;padding:20px;">
                    <?php if ($myGraduation['status'] === 'lulus'): ?>
                        <span style="font-size:48px;">🎉</span>
                        <h2 style="color:#28a745;margin:10px 0;">SELAMAT, ANDA LULUS!</h2>
                        <p>Kelas: <?= sanitize($myGraduation['nama_kelas']) ?> - <?= sanitize($myGraduation['mata_kuliah']) ?></p>
                        <?php if ($myGraduation['nilai_akhir']): ?>
                        <p style="font-size:24px;font-weight:700;color:var(--primary);">Nilai: <?= number_format($myGraduation['nilai_akhir'], 1) ?></p>
                        <?php endif; ?>
                    <?php elseif ($myGraduation['status'] === 'tidak_lulus'): ?>
                        <span style="font-size:48px;">📚</span>
                        <h2 style="color:#dc3545;margin:10px 0;">BELUM LULUS</h2>
                        <p>Silakan mendaftar ulang pada gelombang berikutnya.</p>
                    <?php else: ?>
                        <span style="font-size:48px;">⏳</span>
                        <h2 style="color:var(--text-muted);margin:10px 0;">Belum Diumumkan</h2>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($graduationResults)): ?>
        <div class="card">
            <div class="card-header">📋 Daftar Kelulusan</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>NIM</th>
                                <th>Program Studi</th>
                                <th>Kelas</th>
                                <th>Nilai</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($graduationResults as $i => $r): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= sanitize($r['nama_lengkap']) ?></td>
                                <td><?= sanitize($r['nim']) ?></td>
                                <td><?= sanitize($r['program_studi']) ?></td>
                                <td><?= sanitize($r['nama_kelas']) ?></td>
                                <td><strong><?= $r['nilai_akhir'] ? number_format($r['nilai_akhir'], 1) : '-' ?></strong></td>
                                <td>
                                    <span class="badge <?= $r['status'] === 'lulus' ? 'badge-success' : 'badge-danger' ?>">
                                        <?= ucfirst(str_replace('_', ' ', $r['status'])) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
    <?php
}
