<?php
if (!isset($current)) $current = basename($_SERVER['PHP_SELF']);
if (!isset($nama_admin)) $nama_admin = $_SESSION['admin_nama'] ?? $_SESSION['nama'] ?? 'Admin';
?>

<div class="mobile-header">
    <button type="button" class="menu-toggle" onclick="toggleSidebar()" aria-label="Buka menu">☰</button>
    <div class="mobile-brand">
        <img src="../assets/logo.png" alt="Logo">
        <span>NAVIRA</span>
    </div>
</div>
<div class="sidebar-overlay" onclick="closeSidebar()"></div>

<div class="sidebar">
    <div class="sidebar-logo">
        <img src="../assets/logo.png" alt="Logo" class="brand-logo">
        <div>
            <div class="logo-text">NAVIRA</div>
            <div class="logo-sub">Admin Panel</div>
        </div>
    </div>

    <div class="sidebar-section">Menu Utama</div>
    <a href="dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">
        <span class="nav-icon">📊</span> Dashboard
        <?php if ($current === 'dashboard.php'): ?><span class="online-dot"></span><?php endif; ?>
    </a>
    <a href="scan.php" class="<?= $current === 'scan.php' ? 'active' : '' ?>">
        <span class="nav-icon">📷</span> Scan QR Tiket
        <?php if ($current === 'scan.php'): ?><span class="online-dot"></span><?php endif; ?>
    </a>
    <a href="data_tiket.php" class="<?= $current === 'data_tiket.php' ? 'active' : '' ?>">
        <span class="nav-icon">🎫</span> Data Tiket
        <?php if ($current === 'data_tiket.php'): ?><span class="online-dot"></span><?php endif; ?>
    </a>

    <div class="sidebar-section">Laporan</div>
    <?php if (isset($from_display, $to_display, $status)): ?>
    <a href="export_excel.php?from=<?= urlencode($from_display) ?>&to=<?= urlencode($to_display) ?>&status=<?= urlencode($status) ?>" target="_blank">
        <span class="nav-icon">📥</span> Download Laporan
    </a>
    <?php else: ?>
    <a href="#" onclick="if(typeof bukaModal==='function'){bukaModal();}return false;">
        <span class="nav-icon">📥</span> Download Laporan
    </a>
    <?php endif; ?>

    <div class="sidebar-spacer"></div>
    <div class="sidebar-footer">
        <div class="user-info">
            Login sebagai:<br>
            <strong><?= htmlspecialchars($nama_admin) ?></strong>
        </div>
        <a href="logout.php"><span class="nav-icon">🚪</span> Logout</a>
    </div>
</div>
