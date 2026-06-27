<?php
if (!isset($current)) $current = basename($_SERVER['PHP_SELF']);
if (!isset($nama_admin)) $nama_admin = $_SESSION['nama'] ?? 'Super Admin';
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
            <div class="logo-sub">Super Admin Panel</div>
        </div>
    </div>

    <div class="sidebar-section">Menu Utama</div>
    <a href="dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">
        <span class="nav-icon">📊</span> Dashboard
        <?php if ($current === 'dashboard.php'): ?><span class="online-dot"></span><?php endif; ?>
    </a>
    <a href="kelola_admin.php" class="<?= $current === 'kelola_admin.php' ? 'active' : '' ?>">
        <span class="nav-icon">🛡️</span> Kelola Admin
        <?php if ($current === 'kelola_admin.php'): ?><span class="online-dot"></span><?php endif; ?>
    </a>
    <a href="kelola_harga.php" class="<?= $current === 'kelola_harga.php' ? 'active' : '' ?>">
        <span class="nav-icon">💰</span> Kelola Harga
        <?php if ($current === 'kelola_harga.php'): ?><span class="online-dot"></span><?php endif; ?>
    </a>
    <a href="kelola_pelabuhan.php" class="<?= $current === 'kelola_pelabuhan.php' ? 'active' : '' ?>">
        <span class="nav-icon">⚓</span> Kelola Pelabuhan
        <?php if ($current === 'kelola_pelabuhan.php'): ?><span class="online-dot"></span><?php endif; ?>
    </a>

    <div class="sidebar-spacer"></div>
    <div class="sidebar-footer">
        <div class="user-info">
            Login sebagai:<br>
            <strong><?= htmlspecialchars($nama_admin) ?></strong>
        </div>
        <a href="logout.php"><span class="nav-icon">🚪</span> Logout</a>
    </div>
</div>
