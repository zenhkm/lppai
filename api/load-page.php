<?php
/**
 * LPPAI Corner - AJAX Page Loader
 * Load page content without header/sidebar/footer wrapper
 * Used for SPA-style navigation without full page reload
 */
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../includes/auth.php';
    requireUser(); // Login required

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        echo json_encode(['success' => false, 'message' => 'Method tidak valid.']);
        exit;
    }

    $page = trim($_GET['page'] ?? '');
    
    // Security: whitelist allowed pages
    $allowedPages = [
        'dashboard'                   => '/dashboard.php',
        'pretes-peserta'             => '/pretes-peserta.php',
        'pretes-hasil'               => '/pretes-hasil.php',
        'pretes-daftar'              => '/pretes-daftar.php',
        'tutorial-gel1-pendaftaran'  => '/tutorial-gel1-pendaftaran.php',
        'tutorial-gel1-pembagian'    => '/tutorial-gel1-pembagian.php',
        'tutorial-gel1-kelulusan'    => '/tutorial-gel1-kelulusan.php',
        'tutorial-gel2-pendaftaran'  => '/tutorial-gel2-pendaftaran.php',
        'tutorial-gel2-pembagian'    => '/tutorial-gel2-pembagian.php',
        'tutorial-gel2-kelulusan'    => '/tutorial-gel2-kelulusan.php',
        'tutorial-mandiri-pendaftaran' => '/tutorial-mandiri-pendaftaran.php',
        'tutorial-mandiri-pembagian'   => '/tutorial-mandiri-pembagian.php',
        // Admin pages
        'admin-dashboard'            => '/admin/dashboard.php',
        'admin-users'                => '/admin/users.php',
        'admin-pengumuman'           => '/admin/pengumuman.php',
        'admin-pretes-peserta'       => '/admin/pretes-peserta.php',
        'admin-pretes-jadwal'        => '/admin/pretes-jadwal.php',
        'admin-pretes-hasil'         => '/admin/pretes-hasil.php',
        'admin-tutorial-peserta'     => '/admin/tutorial-peserta.php',
        'admin-tutorial-kelas'       => '/admin/tutorial-kelas.php',
    ];

    if (!array_key_exists($page, $allowedPages)) {
        echo json_encode(['success' => false, 'message' => 'Halaman tidak ditemukan.']);
        exit;
    }

    $filePath = __DIR__ . '/..' . $allowedPages[$page];
    if (!file_exists($filePath)) {
        echo json_encode(['success' => false, 'message' => 'File halaman tidak ada.']);
        exit;
    }

    // Buffer output to capture page content
    ob_start();
    $pageTitle = 'LPPAI Corner';
    include $filePath;
    $output = ob_get_clean();

    // Get page title from constant if defined
    if (defined('PAGE_TITLE')) {
        $pageTitle = PAGE_TITLE;
    }
    
    // Extract content from between content-area div
    preg_match('/<div[^>]*class="[^"]*content-area[^"]*"[^>]*>(.*)/is', $output, $matches);
    $contentHTML = isset($matches[1]) ? trim($matches[1]) : $output;
    
    // Remove closing divs from end if present
    $contentHTML = preg_replace('/<\/div>\s*<div class="footer">.*$/is', '', $contentHTML);
    $contentHTML = trim($contentHTML);

    echo json_encode([
        'success'  => true,
        'title'    => $pageTitle,
        'content'  => $contentHTML,
        'page'     => $page
    ]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
exit;
