<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Navira — Tiket Kapal</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,700;1,400&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
    --blue:    #2563eb;
    --blue2:   #3b82f6;
    --blue3:   #60a5fa;
    --sky:     #0ea5e9;
    --sky2:    #38bdf8;
    --cyan:    #93c5fd;
    --dark:    #060c14;
    --dark2:   #0b1321;
    --dark3:   #111e2e;
    --muted:   #6b7280;
}
html{scroll-behavior:smooth}
body{
    font-family:'DM Sans',sans-serif;
    background:var(--dark);
    color:white;
    overflow-x:hidden;
}

/* ── NAVBAR ── */
nav{
    position:fixed;top:0;left:0;right:0;z-index:100;
    display:flex;align-items:center;justify-content:space-between;
    padding:20px 56px;
    transition:.3s;
}
nav.scrolled{
    background:rgba(6,12,20,.92);
    backdrop-filter:blur(20px);
    border-bottom:1px solid rgba(37,99,235,.15);
    padding:14px 56px;
}
.nav-brand{
    display:flex;align-items:center;gap:12px;
    text-decoration:none;
}
.nav-brand img{height:38px;object-fit:contain}
.nav-brand-text{}
.nav-brand-name{
    font-family:'DM Serif Display',serif;
    font-size:20px;color:white;letter-spacing:2px;
    display:block;line-height:1.1;
}
.nav-brand-sub{
    font-size:10px;color:var(--sky2);
    letter-spacing:1px;text-transform:uppercase;
    font-weight:500;display:block;margin-top:1px;
}
.nav-links{display:flex;align-items:center;gap:10px}
.nav-links a{
    text-decoration:none;font-size:14px;font-weight:500;
    padding:10px 24px;border-radius:8px;transition:.25s;
}
.btn-login{
    color:rgba(255,255,255,.7);
    background:rgba(255,255,255,.05);
    border:1px solid rgba(255,255,255,.1);
}
.btn-login:hover{background:rgba(255,255,255,.1);color:white}
.btn-daftar{
    background:linear-gradient(135deg,var(--blue),var(--sky));
    color:white;font-weight:600;
}
.btn-daftar:hover{
    transform:translateY(-2px);
    box-shadow:0 8px 20px rgba(37,99,235,.4);
}

/* ── HERO ── */
.hero{
    height:100vh;position:relative;overflow:hidden;
    display:flex;align-items:center;justify-content:center;
}
.hero video{
    position:absolute;inset:0;width:100%;height:100%;
    object-fit:cover;
    filter:brightness(.35) saturate(1.3) hue-rotate(190deg);
}
.hero-overlay{
    position:absolute;inset:0;
    background:
        radial-gradient(ellipse at 20% 50%, rgba(37,99,235,.25) 0%, transparent 60%),
        radial-gradient(ellipse at 80% 20%, rgba(14,165,233,.15) 0%, transparent 50%),
        linear-gradient(180deg, rgba(6,12,20,.2) 0%, rgba(6,12,20,.75) 100%);
    z-index:1;
}
.hero-grid{
    position:absolute;inset:0;z-index:1;
    background-image:
        linear-gradient(rgba(37,99,235,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(37,99,235,.06) 1px, transparent 1px);
    background-size:60px 60px;
}
.hero-content{
    position:relative;z-index:2;
    text-align:center;padding:0 24px;
    max-width:820px;
}
.hero-badge{
    display:inline-flex;align-items:center;gap:8px;
    background:rgba(37,99,235,.18);
    border:1px solid rgba(37,99,235,.35);
    border-radius:50px;padding:8px 20px;
    font-size:12px;font-weight:500;color:var(--cyan);
    margin-bottom:32px;letter-spacing:.5px;
}
.hero-badge::before{
    content:'';width:7px;height:7px;border-radius:50%;
    background:var(--sky2);
    box-shadow:0 0 8px var(--sky2);
    animation:pulse 2s ease infinite;
}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(.75)}}

.hero-content h1{
    font-family:'DM Serif Display',serif;
    font-size:64px;line-height:1.12;font-weight:400;
    margin-bottom:24px;
}
.hero-content h1 i{
    font-style:italic;color:var(--sky2);
}
.hero-content p{
    font-size:18px;color:rgba(255,255,255,.58);
    margin-bottom:40px;line-height:1.75;
    max-width:520px;margin-left:auto;margin-right:auto;
}
.hero-btns{display:flex;justify-content:center;gap:14px;flex-wrap:wrap}
.btn-hero-main{
    display:inline-flex;align-items:center;gap:10px;
    background:linear-gradient(135deg,var(--blue),var(--sky));
    color:white;font-weight:600;font-size:15px;
    padding:15px 36px;border-radius:10px;
    text-decoration:none;transition:.3s;
}
.btn-hero-main:hover{
    transform:translateY(-3px);
    box-shadow:0 16px 36px rgba(37,99,235,.4);
}
.btn-hero-sec{
    display:inline-flex;align-items:center;gap:10px;
    background:transparent;color:white;
    font-weight:500;font-size:15px;
    padding:15px 36px;border-radius:10px;
    border:1px solid rgba(255,255,255,.18);
    text-decoration:none;transition:.3s;
}
.btn-hero-sec:hover{
    border-color:rgba(56,189,248,.4);
    background:rgba(37,99,235,.1);
}

.scroll-hint{
    position:absolute;bottom:40px;left:50%;transform:translateX(-50%);
    z-index:2;display:flex;flex-direction:column;align-items:center;gap:8px;
    color:rgba(255,255,255,.35);font-size:11px;letter-spacing:1px;
    text-transform:uppercase;
}
.scroll-line{
    width:1px;height:50px;
    background:linear-gradient(to bottom,rgba(56,189,248,.4),transparent);
    animation:scrollAnim 1.6s ease infinite;
}
@keyframes scrollAnim{
    0%{transform:scaleY(0);transform-origin:top}
    50%{transform:scaleY(1)}
    100%{transform:scaleY(0);transform-origin:bottom}
}

/* ── STATS BAR ── */
.stats-bar{
    background:var(--dark2);
    border-top:1px solid rgba(37,99,235,.15);
    border-bottom:1px solid rgba(37,99,235,.15);
    padding:36px 56px;
    display:grid;grid-template-columns:repeat(4,1fr);
    gap:24px;
}
.sbar-item{
    display:flex;flex-direction:column;align-items:center;
    padding:0 16px;
    border-right:1px solid rgba(255,255,255,.05);
}
.sbar-item:last-child{border-right:none}
.sbar-num{
    font-family:'DM Serif Display',serif;
    font-size:38px;color:var(--sky2);margin-bottom:6px;
}
.sbar-lbl{font-size:13px;color:var(--muted);text-align:center}

/* ── FITUR ALTERNATING ── */
.fitur-section{padding:100px 56px;max-width:1200px;margin:0 auto}
.fitur-row{
    display:grid;grid-template-columns:1fr 1fr;
    gap:64px;align-items:center;margin-bottom:80px;
}
.fitur-row:last-child{margin-bottom:0}
.fitur-row.reverse .fitur-visual{order:-1}

.fitur-tag{
    font-size:12px;color:var(--sky2);font-weight:600;
    text-transform:uppercase;letter-spacing:1.5px;margin-bottom:12px;
}
.fitur-text h2{
    font-family:'DM Serif Display',serif;
    font-size:38px;line-height:1.2;margin-bottom:16px;
}
.fitur-text p{
    font-size:16px;color:var(--muted);line-height:1.8;margin-bottom:28px;
}
.fitur-list{list-style:none;display:flex;flex-direction:column;gap:12px}
.fitur-list li{
    display:flex;align-items:flex-start;gap:12px;
    font-size:14px;color:rgba(255,255,255,.75);
}
.fitur-list li::before{
    content:'✓';
    width:22px;height:22px;border-radius:50%;
    background:rgba(37,99,235,.15);
    border:1px solid rgba(56,189,248,.3);
    color:var(--sky2);font-weight:700;font-size:12px;
    display:flex;align-items:center;justify-content:center;
    flex-shrink:0;margin-top:2px;
}
.fitur-visual{
    border-radius:24px;overflow:hidden;
    border:1px solid rgba(37,99,235,.15);
    position:relative;
}
.fitur-visual img{
    width:100%;height:280px;object-fit:cover;
    display:block;filter:brightness(.8) saturate(1.1);
}
.fitur-visual::after{
    content:'';position:absolute;inset:0;
    background:linear-gradient(135deg,rgba(37,99,235,.18),transparent 60%);
}

/* ── JADWAL ── */
.jadwal-section{
    background:var(--dark2);
    padding:80px 56px;
}
.sec-header{text-align:center;margin-bottom:52px}
.sec-header .tag{
    font-size:12px;color:var(--sky2);font-weight:600;
    text-transform:uppercase;letter-spacing:1.5px;margin-bottom:10px;
}
.sec-header h2{
    font-family:'DM Serif Display',serif;
    font-size:42px;margin-bottom:12px;
}
.sec-header p{font-size:16px;color:var(--muted)}

.jadwal-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:20px;max-width:1100px;margin:0 auto;
}
.jcard{
    background:var(--dark3);
    border:1px solid rgba(255,255,255,.06);
    border-radius:20px;overflow:hidden;transition:.3s;
}
.jcard:hover{
    border-color:rgba(37,99,235,.3);
    transform:translateY(-4px);
    box-shadow:0 20px 48px rgba(0,0,0,.45);
}
.jcard img{
    width:100%;height:168px;object-fit:cover;
    filter:brightness(.75) saturate(1.1);display:block;
}
.jcard-body{padding:22px}
.rute{display:flex;align-items:center;gap:8px;margin-bottom:6px}
.rute span{font-weight:700;font-size:15px}
.rute-arrow{color:var(--sky2);font-size:18px}
.jdate{font-size:12px;color:var(--muted);margin-bottom:14px}
.price{font-size:22px;font-weight:700;color:var(--sky2);margin-bottom:16px}
.price small{font-size:13px;color:var(--muted);font-weight:400}
.btn-jcard{
    display:block;text-align:center;
    border:1.5px solid var(--blue2);
    color:var(--blue3);font-weight:600;font-size:14px;
    padding:11px;border-radius:10px;
    text-decoration:none;transition:.3s;
}
.btn-jcard:hover{
    background:linear-gradient(135deg,var(--blue),var(--sky));
    color:white;border-color:transparent;
    box-shadow:0 8px 20px rgba(37,99,235,.35);
}

/* ── CTA ── */
.cta-section{
    padding:100px 56px;text-align:center;
    position:relative;overflow:hidden;
}
.cta-section::before{
    content:'';position:absolute;
    top:50%;left:50%;transform:translate(-50%,-50%);
    width:600px;height:600px;border-radius:50%;
    background:radial-gradient(circle,rgba(37,99,235,.1) 0%,transparent 70%);
}
.cta-section::after{
    content:'⚓';position:absolute;
    top:50%;left:50%;transform:translate(-50%,-50%);
    font-size:240px;opacity:.025;line-height:1;
    pointer-events:none;
}
.cta-section h2{
    font-family:'DM Serif Display',serif;
    font-size:52px;margin-bottom:20px;
    position:relative;z-index:1;
}
.cta-section p{
    font-size:18px;color:var(--muted);margin-bottom:40px;
    position:relative;z-index:1;
}
.cta-btns{
    display:flex;justify-content:center;gap:14px;
    flex-wrap:wrap;position:relative;z-index:1;
}

/* ── FOOTER ── */
footer{
    background:var(--dark2);
    border-top:1px solid rgba(37,99,235,.1);
    padding:28px 56px;
    display:flex;justify-content:space-between;align-items:center;
    flex-wrap:wrap;gap:12px;
}
.f-brand{
    font-family:'DM Serif Display',serif;
    font-size:18px;color:var(--sky2);
}
footer small{color:#374151;font-size:13px}

/* ── RESPONSIVE ── */
@media(max-width:900px){
    nav,nav.scrolled{padding:14px 20px}
    .hero-content h1{font-size:36px}
    .stats-bar{grid-template-columns:1fr 1fr;padding:28px 24px}
    .fitur-section{padding:60px 24px}
    .fitur-row{grid-template-columns:1fr;gap:36px;margin-bottom:52px}
    .fitur-row.reverse .fitur-visual{order:0}
    .fitur-text h2{font-size:28px}
    .jadwal-section{padding:60px 24px}
    .jadwal-grid{grid-template-columns:1fr}
    .cta-section{padding:70px 24px}
    .cta-section h2{font-size:32px}
    footer{padding:24px;flex-direction:column;text-align:center}
}
@media(max-width:480px){
    .hero-content h1{font-size:28px}
    .hero-btns{flex-direction:column;align-items:center}
    .btn-hero-main,.btn-hero-sec{width:100%;max-width:300px;justify-content:center}
    .stats-bar{grid-template-columns:1fr}
    .sbar-item{border-right:none;border-bottom:1px solid rgba(255,255,255,.05);padding-bottom:20px;margin-bottom:4px}
    .sbar-item:last-child{border-bottom:none}
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav id="navbar">
    <a class="nav-brand" href="#">
        <img src="assets/logo.png" alt="Navira">
        <div class="nav-brand-text">
            <span class="nav-brand-name">NAVIRA</span>
            <span class="nav-brand-sub">Pemesanan Tiket Kapal</span>
        </div>
    </a>
    <div class="nav-links">
        <a href="user/login.php" class="btn-login">Masuk</a>
        <a href="user/register.php" class="btn-daftar">Mulai Gratis</a>
    </div>
</nav>

<!-- HERO -->
<div class="hero">
    <video autoplay muted loop playsinline>
        <source src="assets/video/laut.mp4" type="video/mp4">
    </video>
    <div class="hero-overlay"></div>
    <div class="hero-grid"></div>

    <div class="hero-content">
        <h1>
            Beli tiket kapal tanpa<br>
            perlu akrab sama<br>
            <i>petugas loket.</i>
        </h1>
        <p>Pelabuhan boleh ramai, tapi pesan tiket tetap santai — cukup pilih rute, klik, dan berangkat.</p>
        <div class="hero-btns">
            <a href="user/beli_tiket.php" class="btn-hero-main">🔍 Cari Jadwal Sekarang</a>
            <a href="user/register.php" class="btn-hero-sec">Daftar Sekarang →</a>
        </div>
    </div>

    <div class="scroll-hint">
        <div class="scroll-line"></div>
        Scroll
    </div>
</div>

<!-- STATS BAR -->
<div class="stats-bar">
    <div class="sbar-item">
        <div class="sbar-num">3+</div>
        <div class="sbar-lbl">Rute Aktif</div>
    </div>
    <div class="sbar-item">
        <div class="sbar-num">2.000+</div>
        <div class="sbar-lbl">Calon Penumpang Terlayani</div>
    </div>
    <div class="sbar-item">
        <div class="sbar-num">99%</div>
        <div class="sbar-lbl">Target Tingkat Kepuasan</div>
    </div>
    <div class="sbar-item">
        <div class="sbar-num">24/7</div>
        <div class="sbar-lbl">Layanan Aktif</div>
    </div>
</div>

<!-- FITUR ALTERNATING -->
<div class="fitur-section">
    <div class="fitur-row">
        <div class="fitur-text">
            <div class="fitur-tag">Kemudahan Booking</div>
            <h2>Pesan dalam 3 langkah mudah</h2>
            <p>Tidak perlu antri, tidak perlu ke loket. Cukup pilih rute, isi data, dan tiket langsung ada di tanganmu.</p>
            <ul class="fitur-list">
                <li>Pilih pelabuhan asal dan tujuan</li>
                <li>Tentukan tanggal dan jumlah penumpang</li>
                <li>Konfirmasi dan terima e-tiket QR</li>
            </ul>
        </div>
        <div class="fitur-visual">
            <img src="assets/foto/step.png" alt="Booking">
        </div>
    </div>

    <div class="fitur-row reverse">
        <div class="fitur-text">
            <div class="fitur-tag">Kendaraan Turut Serta</div>
            <h2>Bawa motor atau mobil dengan mudah</h2>
            <p>Pilih golongan kendaraan dan harga menyesuaikan otomatis sesuai regulasi resmi PM 66 Tahun 2019.</p>
            <ul class="fitur-list">
                <li>9 golongan kendaraan tersedia</li>
                <li>Harga transparan tanpa biaya tersembunyi</li>
                <li>Plat nomor tercatat dan tervalidasi</li>
            </ul>
        </div>
        <div class="fitur-visual">
            <img src="assets/foto/golongan.png" alt="Kendaraan">
        </div>
    </div>
</div>

<!-- JADWAL -->
<div class="jadwal-section">
    <div class="sec-header">
        <div class="tag">Jadwal Terbaru</div>
        <h2>Rute Pelabuhan</h2>
        <p>Temukan jadwal keberangkatan terbaik untukmu</p>
    </div>
    <div class="jadwal-grid">
        <div class="jcard">
            <img src="assets/foto/dashboard1.jpg" alt="JKT-SUB">
            <div class="jcard-body">
                <div class="rute">
                    <span>Jakarta</span>
                    <span class="rute-arrow">→</span>
                    <span>Surabaya</span>
                </div>
                <div class="jdate">📅 25 April 2026 &nbsp;·&nbsp; ⏰ 08:00 WIB</div>
                <div class="price">Rp 350.000 <small>/ orang</small></div>
                <a href="user/beli_tiket.php" class="btn-jcard">Pesan Sekarang</a>
            </div>
        </div>
        <div class="jcard">
            <img src="assets/foto/dashboard2.jpg" alt="SUB-MKS">
            <div class="jcard-body">
                <div class="rute">
                    <span>Surabaya</span>
                    <span class="rute-arrow">→</span>
                    <span>Makassar</span>
                </div>
                <div class="jdate">📅 28 April 2026 &nbsp;·&nbsp; ⏰ 10:00 WIB</div>
                <div class="price">Rp 450.000 <small>/ orang</small></div>
                <a href="user/beli_tiket.php" class="btn-jcard">Pesan Sekarang</a>
            </div>
        </div>
        <div class="jcard">
            <img src="assets/foto/dashboard3.jpg" alt="MKS-DPS">
            <div class="jcard-body">
                <div class="rute">
                    <span>Makassar</span>
                    <span class="rute-arrow">→</span>
                    <span>Bali</span>
                </div>
                <div class="jdate">📅 2 Mei 2026 &nbsp;·&nbsp; ⏰ 07:00 WIB</div>
                <div class="price">Rp 400.000 <small>/ orang</small></div>
                <a href="user/beli_tiket.php" class="btn-jcard">Pesan Sekarang</a>
            </div>
        </div>
    </div>
</div>

<!-- CTA -->
<div class="cta-section">
    <h2>Siap berlayar<br>bersama Navira?</h2>
    <p>Daftar gratis sekarang dan nikmati kemudahan memesan tiket kapal laut.</p>
    <div class="cta-btns">
        <a href="user/register.php" class="btn-hero-main">Daftar Gratis Sekarang</a>
        <a href="user/beli_tiket.php" class="btn-hero-sec">Lihat Semua Rute</a>
    </div>
</div>

<!-- FOOTER -->
<footer>
    <div class="f-brand">⚓ NAVIRA</div>
    <small>© 2026 Navira — Sistem Tiket Kapal · Testing Kelompok 2</small>
</footer>

<script>
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => {
    navbar.classList.toggle('scrolled', window.scrollY > 60);
});
</script>
</body>
</html>