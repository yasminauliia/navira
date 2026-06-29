<?php
if (!isset($current)) $current = basename($_SERVER['PHP_SELF']);
if (!isset($user)) {
    $user = ['nama' => 'User', 'email' => ''];
}
?>
<style>
/* ════ SIDEBAR BASE ════ */
.sidebar {
    width: 240px;
    height: 100vh;
    position: fixed;
    top: 0; left: 0;
    background: #020617;
    border-right: 1px solid rgba(255,255,255,.06);
    padding: 0;              /* hapus padding global, atur per-section */
    z-index: 200;
    display: flex;
    flex-direction: column;  /* ← kunci: flex column agar footer selalu di bawah */
    overflow: hidden;        /* cegah sidebar sendiri overflow */
}

/* ── LOGO ── */
.sidebar-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 24px 16px 16px;
    flex-shrink: 0;          /* tidak boleh diperkecil */
}
.sidebar-logo .brand-logo {
    width: 40px; height: 40px;
    object-fit: contain;
}
.sidebar-logo .logo-text {
    font-size: 16px; font-weight: 700; color: white;
    line-height: 1.2; letter-spacing: 1px;
}
.sidebar-logo .logo-sub {
    font-size: 10px; color: #475569; font-weight: 500;
}

/* ── SCROLLABLE AREA (menu + section label) ── */
.sidebar-scroll {
    flex: 1;                 /* ambil semua sisa tinggi */
    overflow-y: auto;        /* scroll di dalam jika konten panjang */
    overflow-x: hidden;
    padding: 0 16px;
}
.sidebar-scroll::-webkit-scrollbar { width: 3px; }
.sidebar-scroll::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,.08);
    border-radius: 4px;
}

.sidebar-section {
    font-size: 9px; font-weight: 700; color: #334155;
    text-transform: uppercase; letter-spacing: 1.2px;
    padding: 0 8px; margin: 16px 0 14px;
}

/* ── MENU LINKS ── */
.sidebar a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px;
    margin-bottom: 10px;
    border-radius: 14px;
    text-decoration: none;
    color: #94a3b8;
    font-size: 14px;
    font-weight: 600;
    background: rgba(255,255,255,.03);
    border: 1px solid rgba(255,255,255,.05);
    transition: .2s;
}
.sidebar a:last-of-type { margin-bottom: 0; }
.sidebar a .nav-icon {
    font-size: 17px; width: 30px;
    text-align: center; flex-shrink: 0;
}
.sidebar a:hover {
    background: rgba(255,255,255,.06);
    color: #e2e8f0;
    border-color: rgba(255,255,255,.1);
}
.sidebar a.active {
    background: linear-gradient(90deg,rgba(37,99,235,.25),rgba(56,189,248,.12));
    border: 1px solid rgba(56,189,248,.25);
    color: white;
    box-shadow: 0 0 16px rgba(56,189,248,.1);
}
.sidebar a.active .nav-icon { color: #38bdf8; }

/* Beli Tiket aksen ungu */
.sidebar a.btn-beli-nav {
    background: rgba(139,92,246,.08);
    border: 1px solid rgba(139,92,246,.18);
    color: #c4b5fd;
}
.sidebar a.btn-beli-nav:hover {
    background: rgba(139,92,246,.14);
    color: #ddd6fe;
}
.sidebar a.btn-beli-nav.active {
    background: linear-gradient(90deg,rgba(124,58,237,.3),rgba(139,92,246,.15));
    border: 1px solid rgba(139,92,246,.3);
    color: white;
    box-shadow: 0 0 16px rgba(139,92,246,.15);
}
.sidebar a.btn-beli-nav.active .nav-icon { color: #a78bfa; }

.online-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #22c55e; margin-left: auto;
    box-shadow: 0 0 6px #22c55e;
    animation: blink 2s ease infinite;
    flex-shrink: 0;
}
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:.4} }

/* ── FOOTER — selalu nempel di bawah ── */
.sidebar-footer {
    flex-shrink: 0;          /* tidak boleh diperkecil/terdorong */
    border-top: 1px solid rgba(255,255,255,.06);
    padding: 14px 16px 20px;
    background: #020617;     /* tutup konten scroll di belakangnya */
}
.sidebar-footer .user-info {
    font-size: 11px; color: #334155;
    padding: 0 4px; margin-bottom: 12px;
    line-height: 1.6;
}
.sidebar-footer .user-info strong { color: #94a3b8; }

/* Tombol logout di footer — TIDAK pakai .sidebar a supaya tidak kena style atas */
.sidebar-footer .btn-logout {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 13px 14px;
    border-radius: 12px;
    text-decoration: none;
    font-size: 13px; font-weight: 600;
    background: rgba(239,68,68,.1);
    border: 1px solid rgba(239,68,68,.2);
    color: #f87171;
    transition: .2s;
    width: 100%;
    cursor: pointer;
}
.sidebar-footer .btn-logout:hover {
    background: rgba(239,68,68,.18);
    color: #fca5a5;
    border-color: rgba(239,68,68,.3);
}

/* ════ MOBILE HEADER ════ */
.mobile-header {
    display: none;
    position: sticky;
    top: 0; left: 0; right: 0;
    background: #020617;
    border-bottom: 1px solid rgba(255,255,255,.06);
    padding: 14px 16px;
    align-items: center;
    gap: 14px;
    z-index: 250;
}
.menu-toggle {
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.1);
    color: white;
    width: 38px; height: 38px;
    border-radius: 10px;
    font-size: 18px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.mobile-brand {
    display: flex; align-items: center; gap: 8px;
}
.mobile-brand img { width: 28px; height: 28px; object-fit: contain; }
.mobile-brand span {
    font-size: 15px; font-weight: 700; color: white; letter-spacing: 1px;
}

/* Overlay gelap saat sidebar mobile terbuka */
.sidebar-overlay {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0,0,0,.6);
    z-index: 199;
}

/* ════ RESPONSIVE ════ */
@media (max-width: 768px) {
    .mobile-header { display: flex; }

    .sidebar {
        /* di mobile sidebar jadi panel geser, tinggi = 100dvh agar pas layar */
        height: 100dvh;      /* dvh = dynamic viewport height, aman di Android/iOS */
        transform: translateX(-100%);
        transition: transform .25s ease;
        box-shadow: 0 0 40px rgba(0,0,0,.5);
    }
    .sidebar.open {
        transform: translateX(0);
    }
    .sidebar-overlay.open { display: block; }

    /*
     * Di mobile: sidebar-scroll hanya boleh setinggi sisa ruang
     * antara logo dan footer. max-height diatur lewat calc.
     * Angka 160px = perkiraan tinggi logo (88px) + footer (72px).
     * Ini memaksa scroll hanya di area menu, bukan seluruh sidebar.
     */
    .sidebar-scroll {
        max-height: calc(100dvh - 160px);
    }
}

/* Fallback untuk browser yang belum support dvh */
@supports not (height: 100dvh) {
    @media (max-width: 768px) {
        .sidebar            { height: 100vh; }
        .sidebar-scroll     { max-height: calc(100vh - 160px); }
    }
}
</style>

<!-- ══ MOBILE HEADER ══ -->
<div class="mobile-header">
    <button type="button" class="menu-toggle" onclick="toggleSidebar()" aria-label="Buka menu">☰</button>
    <div class="mobile-brand">
        <img src="../assets/logo.png" alt="Logo">
        <span>NAVIRA</span>
    </div>
</div>

<!-- ══ OVERLAY ══ -->
<div class="sidebar-overlay" onclick="closeSidebar()"></div>

<!-- ══ SIDEBAR ══ -->
<div class="sidebar" id="sidebarPanel">

    <!-- LOGO — tidak ikut scroll -->
    <div class="sidebar-logo">
        <img src="../assets/logo.png" alt="Logo" class="brand-logo">
        <div>
            <div class="logo-text">NAVIRA</div>
            <div class="logo-sub">Pemesanan Tiket Kapal</div>
        </div>
    </div>

    <!-- MENU — bisa scroll kalau konten panjang -->
    <div class="sidebar-scroll">
        <div class="sidebar-section">Menu Utama</div>

        <a href="dashboard.php"
           class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">
            <span class="nav-icon">🎫</span>
            Pesanan Saya
            <?php if ($current === 'dashboard.php'): ?>
                <span class="online-dot"></span>
            <?php endif; ?>
        </a>

        <a href="beli_tiket.php"
           class="btn-beli-nav <?= $current === 'beli_tiket.php' ? 'active' : '' ?>">
            <span class="nav-icon">➕</span>
            Beli Tiket
        </a>
    </div>

    <!-- FOOTER — selalu di bawah, tidak ikut scroll -->
    <div class="sidebar-footer">
        <div class="user-info">
            Login sebagai:<br>
            <strong><?= htmlspecialchars($user['nama'] ?? 'User') ?></strong>
        </div>
        <a href="logout.php" class="btn-logout">
            <span class="nav-icon">🚪</span>
            Logout
        </a>
    </div>

</div>

<script>
function toggleSidebar() {
    document.getElementById('sidebarPanel').classList.toggle('open');
    document.querySelector('.sidebar-overlay').classList.toggle('open');
}
function closeSidebar() {
    document.getElementById('sidebarPanel').classList.remove('open');
    document.querySelector('.sidebar-overlay').classList.remove('open');
}

// Tutup sidebar kalau tekan ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeSidebar();
});

// Tutup sidebar kalau resize ke desktop
window.addEventListener('resize', function() {
    if (window.innerWidth > 768) closeSidebar();
});
</script>