<?php
/**
 * LPPAI Corner - Header Include
 */
if (!defined('PAGE_TITLE')) define('PAGE_TITLE', 'LPPAI Corner');
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize(PAGE_TITLE) ?> - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-wrapper">
    <div class="sidebar-overlay"></div>
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <div style="display:flex;align-items:center;gap:12px;">
                <button class="hamburger">&#9776;</button>
                <span class="page-title"><?= sanitize(PAGE_TITLE) ?></span>
            </div>
            <div class="user-info">
                <div>
                    <div class="name"><?= sanitize($currentUser['nama_lengkap']) ?></div>
                    <span class="role-badge"><?= $currentUser['role'] === 'admin' ? 'Admin' : 'Mahasiswa' ?></span>
                </div>
                <div class="avatar"><?= strtoupper(substr($currentUser['nama_lengkap'], 0, 1)) ?></div>
            </div>
        </div>
        <div class="content-area">
