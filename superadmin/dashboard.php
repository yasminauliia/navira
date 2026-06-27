<?php
// ════════════════════════════════════════════════════════
// SUPERADMIN — DASHBOARD
// ════════════════════════════════════════════════════════
include('auth.php');
include('../config/koneksi.php');
include('../config/payment_helper.php');

ensurePaymentColumns($conn);

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'super_admin'){
    die("Akses ditolak!");
}

// ── STATISTIK ──
$total_user      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role='user'"))['c'];
$total_admin     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role='admin'"))['c'];
$total_tiket     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM tickets WHERE " . sqlPaidTicketsCondition()))['c'];
$total_pelabuhan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM pelabuhan"))['c'];
$total_rute      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT CONCAT(asal_id,'-',tujuan_id)) AS c FROM harga"))['c'];
$total_tarif_kend= mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM harga_kendaraan"))['c'];

// ── TIKET TERBARU ──
$q_tiket = mysqli_query($conn,"
    SELECT t.kode_booking,
           COALESCE(a.nama_pelabuhan, '-') AS asal,
           COALESCE(b.nama_pelabuhan, '-') AS tujuan,
           t.tanggal, t.layanan, t.status, t.total_harga,
           COALESCE(t.payment_status, 'paid') AS payment_status, u.nama
    FROM tickets t
    LEFT JOIN users u ON u.id = t.user_id
    LEFT JOIN pelabuhan a ON a.id = t.asal_id
    LEFT JOIN pelabuhan b ON b.id = t.tujuan_id
    ORDER BY t.id_ticket DESC
    LIMIT 5
");
$rows_tiket = [];
while($r = mysqli_fetch_assoc($q_tiket)) $rows_tiket[] = $r;

// ── STATUS TIKET ──
$tiket_status = [];
$qs = mysqli_query($conn,"SELECT status, COUNT(*) AS c FROM tickets GROUP BY status");
while($r = mysqli_fetch_assoc($qs)) $tiket_status[$r['status']] = $r['c'];
$belum     = $tiket_status['BELUM DIGUNAKAN'] ?? 0;
$digunakan = $tiket_status['DIGUNAKAN']       ?? 0;
$maxVal    = max($belum, $digunakan, 1);

// ── PELABUHAN TERSIBUK ──
$q_sibuk = mysqli_query($conn,"
    SELECT COALESCE(p.nama_pelabuhan, '-') AS asal, COUNT(*) AS total
    FROM tickets t
    LEFT JOIN pelabuhan p ON p.id = t.asal_id
    GROUP BY t.asal_id, p.nama_pelabuhan
    ORDER BY total DESC
    LIMIT 4
");
$tersibuk = [];
while($r = mysqli_fetch_assoc($q_sibuk)) $tersibuk[] = $r;
$maxT = !empty($tersibuk) ? $tersibuk[0]['total'] : 1;

// ── NAMA ADMIN ──
$nama_admin = $_SESSION['nama'] ?? 'Super Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — Super Admin Navira</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="../assets/css/sidebar.css" rel="stylesheet">
<link href="../assets/css/responsive.css" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }

body {
    font-family:'Poppins','Segoe UI',sans-serif;
    background:linear-gradient(135deg, #020617, #0f172a, #1e3a8a);
    min-height:100vh;
    color:white;
    overflow-x:hidden;
}

/* ════ LAYOUT ════ */
.main-wrap {
    margin-left:240px;
    padding:30px;
    min-height:100vh;
}

/* ════ TOPBAR ════ */
.topbar {
    display:flex; justify-content:space-between; align-items:center;
    margin-bottom:28px; flex-wrap:wrap; gap:14px;
}
.topbar-left .greeting {
    font-size:20px; font-weight:700; color:white;
}
.topbar-left .sub {
    font-size:13px; color:#64748b; margin-top:4px;
}
.topbar-right {
    background:#0f172a;
    border:1px solid rgba(255,255,255,0.08);
    border-radius:14px; padding:11px 20px;
    text-align:right;
}
.topbar-right .clock {
    font-size:20px; font-weight:700; color:white;
    font-variant-numeric:tabular-nums; letter-spacing:1px;
}
.topbar-right .date {
    font-size:11px; color:#64748b; margin-top:2px;
}

.fdiv {
    border:none; border-top:1px solid rgba(255,255,255,0.06);
    margin:4px 0 24px;
}

/* ════ SECTION TITLE ════ */
.sec-title {
    font-size:12px; font-weight:700; color:#475569;
    text-transform:uppercase; letter-spacing:1px;
    margin-bottom:14px;
    display:flex; align-items:center; gap:8px;
}
.sec-title::after {
    content:''; flex:1; height:1px;
    background:rgba(255,255,255,0.06);
}

/* ════ STAT CARDS ════ */
.stats-grid {
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:14px;
    margin-bottom:26px;
}

.stat-card {
    background:#0f172a;
    border:1px solid rgba(255,255,255,0.07);
    border-radius:16px;
    padding:20px 18px;
    position:relative;
    transition:transform 0.25s, border-color 0.25s;
    cursor:default;
}
.stat-card:hover {
    transform:translateY(-3px);
    border-color:rgba(255,255,255,0.14);
}

.stat-icon-wrap {
    width:42px; height:42px; border-radius:12px;
    background:rgba(255,255,255,0.05);
    border:1px solid rgba(255,255,255,0.08);
    display:flex; align-items:center; justify-content:center;
    font-size:20px; margin-bottom:14px;
}

.stat-val {
    font-size:32px; font-weight:800; color:white;
    line-height:1; margin-bottom:5px;
    font-variant-numeric:tabular-nums;
}
.stat-lbl {
    font-size:12px; color:#475569; font-weight:500;
}
.stat-tag {
    position:absolute; top:14px; right:14px;
    font-size:9px; font-weight:700;
    background:rgba(255,255,255,0.05);
    border:1px solid rgba(255,255,255,0.08);
    color:#475569; border-radius:20px;
    padding:3px 9px; text-transform:uppercase; letter-spacing:0.5px;
}

/* ════ GLASS CARD ════ */
.gcard {
    background:#0f172a;
    border:1px solid rgba(255,255,255,0.07);
    border-radius:16px; padding:20px;
}
.gcard-title {
    font-size:12px; font-weight:700; color:#475569;
    text-transform:uppercase; letter-spacing:1px;
    margin-bottom:16px;
    display:flex; align-items:center; gap:8px;
    border-bottom:1px solid rgba(255,255,255,0.05);
    padding-bottom:12px;
}

/* ════ QUICK LINKS ════ */
.quick-grid {
    display:grid; grid-template-columns:repeat(4,1fr);
    gap:12px; margin-bottom:26px;
}
.quick-btn {
    background:#0f172a;
    border:1px solid rgba(255,255,255,0.07);
    border-radius:14px; padding:16px 14px;
    color:white; text-decoration:none;
    font-size:13px; font-weight:600;
    display:flex; flex-direction:column;
    align-items:center; gap:8px; text-align:center;
    transition:0.25s;
}
.quick-btn .q-icon {
    font-size:22px;
    width:44px; height:44px; border-radius:12px;
    background:rgba(255,255,255,0.05);
    border:1px solid rgba(255,255,255,0.08);
    display:flex; align-items:center; justify-content:center;
}
.quick-btn .q-lbl { font-size:12px; color:#94a3b8; }
.quick-btn:hover {
    background:#1e293b;
    border-color:rgba(255,255,255,0.14);
    color:white; transform:translateY(-2px);
}

/* ════ BOTTOM GRID ════ */
.bottom-grid {
    display:grid;
    grid-template-columns:1fr 320px;
    gap:16px;
}

/* ════ TABLE ════ */
.tbl-mini { width:100%; border-collapse:collapse; }
.tbl-mini thead th {
    font-size:10px; font-weight:700; color:#475569;
    text-transform:uppercase; letter-spacing:0.7px;
    padding:8px 12px; text-align:left;
    border-bottom:1px solid rgba(255,255,255,0.05);
    white-space:nowrap; background:transparent;
}
.tbl-mini tbody td {
    padding:10px 12px; font-size:12px; color:#cbd5e1;
    border-bottom:1px solid rgba(255,255,255,0.04);
    vertical-align:middle;
}
.tbl-mini tbody tr:last-child td { border-bottom:none; }
.tbl-mini tbody tr:hover { background:rgba(255,255,255,0.02); }

.s-badge {
    padding:2px 9px; border-radius:20px;
    font-size:10px; font-weight:700;
}
.s-belum    { background:rgba(255,255,255,0.06); color:#94a3b8; border:1px solid rgba(255,255,255,0.08); }
.s-digunakan{ background:rgba(255,255,255,0.06); color:#94a3b8; border:1px solid rgba(255,255,255,0.08); }

.l-badge {
    padding:2px 8px; border-radius:20px;
    font-size:10px; font-weight:700;
    background:rgba(255,255,255,0.05);
    border:1px solid rgba(255,255,255,0.08);
    color:#94a3b8;
}

/* ════ STATUS & PROGRESS ════ */
.status-list { display:flex; flex-direction:column; gap:16px; }
.status-item {
    display:flex; justify-content:space-between;
    align-items:center; margin-bottom:6px;
}
.status-item .s-lbl { font-size:13px; color:#94a3b8; }
.status-item .s-num { font-size:14px; font-weight:700; color:white; }
.s-progress {
    height:4px; border-radius:4px;
    background:rgba(255,255,255,0.06); overflow:hidden;
}
.s-fill {
    height:100%; border-radius:4px;
    background:rgba(255,255,255,0.25);
    transition:width 1.2s ease;
}

/* ════ TERSIBUK BARS ════ */
.tersibuk-list { display:flex; flex-direction:column; gap:14px; }
.t-header { display:flex; justify-content:space-between; margin-bottom:5px; }
.t-nama { font-size:12px; color:#94a3b8; font-weight:500; }
.t-num  { font-size:12px; color:white; font-weight:700; }
.t-bar  { height:4px; border-radius:4px; background:rgba(255,255,255,0.06); overflow:hidden; }
.t-fill {
    height:100%; border-radius:4px;
    background:rgba(255,255,255,0.2);
    transition:width 1.2s ease;
}

/* ════ EMPTY ════ */
.empty-mini {
    text-align:center; padding:30px 16px;
    color:#334155; font-size:13px;
}

/* ════ RESPONSIVE ════ */
@media(max-width:1100px){
    .stats-grid { grid-template-columns:repeat(2,1fr); }
    .quick-grid { grid-template-columns:repeat(2,1fr); }
    .bottom-grid { grid-template-columns:1fr; }
}
@media(max-width:768px){
    .main-wrap { margin-left:0; padding:16px; padding-top:72px; }
    .stats-grid { grid-template-columns:1fr 1fr; }
    .topbar { flex-direction:column; align-items:flex-start; }
}
@media(max-width:480px){
    .stats-grid { grid-template-columns:1fr; }
    .quick-grid { grid-template-columns:1fr 1fr; }
}
</style>
</head>
<body class="has-mobile-nav">

<?php include('sidebar.php'); ?>

<div class="main-wrap">

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="topbar-left">
            <div class="greeting">Selamat datang kembali, <?= htmlspecialchars($nama_admin) ?> 🚢</div>
            <div class="sub">Monitoring sistem tiket kapal NAVIRA — Panel Super Admin</div>
        </div>
        <div class="topbar-right">
            <div class="clock" id="liveClock">00:00:00</div>
            <div class="date"  id="liveDate">—</div>
        </div>
    </div>

    <hr class="fdiv">

    <!-- QUICK LINKS -->
    <div class="sec-title">Akses Cepat</div>
    <div class="quick-grid" style="margin-bottom:26px;">
        <a href="kelola_admin.php" class="quick-btn">
            <span class="q-icon">🛡️</span>
            <span class="q-lbl">Kelola Admin</span>
        </a>
        <a href="kelola_harga.php?tab=tiket" class="quick-btn">
            <span class="q-icon">💰</span>
            <span class="q-lbl">Kelola Harga</span>
        </a>
        <a href="kelola_pelabuhan.php" class="quick-btn">
            <span class="q-icon">⚓</span>
            <span class="q-lbl">Kelola Pelabuhan</span>
        </a>
        <a href="kelola_harga.php?tab=kendaraan" class="quick-btn">
            <span class="q-icon">🚗</span>
            <span class="q-lbl">Tarif Kendaraan</span>
        </a>
    </div>

    <!-- STAT CARDS -->
    <div class="sec-title">Statistik Sistem</div>
    <div class="stats-grid">

        <div class="stat-card">
            <span class="stat-tag">Users</span>
            <div class="stat-icon-wrap">👤</div>
            <div class="stat-val" data-target="<?= $total_user ?>">0</div>
            <div class="stat-lbl">Total Pengguna</div>
        </div>

        <div class="stat-card">
            <span class="stat-tag">Admin</span>
            <div class="stat-icon-wrap">🛡️</div>
            <div class="stat-val" data-target="<?= $total_admin ?>">0</div>
            <div class="stat-lbl">Total Admin</div>
        </div>

        <div class="stat-card">
            <span class="stat-tag">Tiket</span>
            <div class="stat-icon-wrap">🎫</div>
            <div class="stat-val" data-target="<?= $total_tiket ?>">0</div>
            <div class="stat-lbl">Total Tiket</div>
        </div>

        <div class="stat-card">
            <span class="stat-tag">Pelabuhan</span>
            <div class="stat-icon-wrap">⚓</div>
            <div class="stat-val" data-target="<?= $total_pelabuhan ?>">0</div>
            <div class="stat-lbl">Total Pelabuhan</div>
        </div>

        <div class="stat-card">
            <span class="stat-tag">Rute</span>
            <div class="stat-icon-wrap">🗺️</div>
            <div class="stat-val" data-target="<?= $total_rute ?>">0</div>
            <div class="stat-lbl">Rute Aktif</div>
        </div>

        <div class="stat-card">
            <span class="stat-tag">Kendaraan</span>
            <div class="stat-icon-wrap">🚗</div>
            <div class="stat-val" data-target="<?= $total_tarif_kend ?>">0</div>
            <div class="stat-lbl">Tarif Kendaraan</div>
        </div>

    </div>

    <!-- BOTTOM GRID -->
    <div class="sec-title">Detail Aktivitas</div>
    <div class="bottom-grid">

        <!-- TIKET TERBARU -->
        <div class="gcard">
            <div class="gcard-title">🎫 Tiket Terbaru</div>
            <?php if(empty($rows_tiket)): ?>
            <div class="empty-mini">
                <div style="font-size:30px;margin-bottom:8px;">🎫</div>
                Belum ada tiket yang dipesan
            </div>
            <?php else: ?>
            <div style="overflow-x:auto;">
            <table class="tbl-mini">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>User</th>
                        <th>Rute</th>
                        <th>Layanan</th>
                        <th>Total</th>
                        <th>Status Tiket</th>
                        <th>Pembayaran</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($rows_tiket as $t): ?>
                <tr>
                    <td style="font-weight:700;color:white;font-size:11px;letter-spacing:0.5px;">
                        <?= htmlspecialchars($t['kode_booking']) ?>
                    </td>
                    <td style="color:#64748b;">
                        <?= htmlspecialchars($t['nama'] ?? '—') ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($t['asal']) ?>
                        <span style="color:#334155;margin:0 3px;">→</span>
                        <?= htmlspecialchars($t['tujuan']) ?>
                    </td>
                    <td>
                        <span class="l-badge"><?= htmlspecialchars($t['layanan']) ?></span>
                    </td>
                    <td style="font-weight:600;color:white;">
                        Rp <?= number_format($t['total_harga'],0,',','.') ?>
                    </td>
                    <td>
                        <span class="s-badge <?= $t['status']==='BELUM DIGUNAKAN'?'s-belum':'s-digunakan' ?>">
                            <?= $t['status']==='BELUM DIGUNAKAN' ? 'Belum' : 'Digunakan' ?>
                        </span>
                    </td>
                    <td><?= paymentStatusBadgeHtml($t['payment_status'] ?? 'pending') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- SIDEBAR KANAN -->
        <div style="display:flex;flex-direction:column;gap:16px;">

            <!-- STATUS TIKET -->
            <div class="gcard">
                <div class="gcard-title">📈 Status Tiket</div>
                <div class="status-list">
                    <div>
                        <div class="status-item">
                            <span class="s-lbl">Belum Digunakan</span>
                            <span class="s-num"><?= $belum ?></span>
                        </div>
                        <div class="s-progress">
                            <div class="s-fill" data-width="<?= $maxVal>0?round($belum/$maxVal*100):0 ?>"></div>
                        </div>
                    </div>
                    <div>
                        <div class="status-item">
                            <span class="s-lbl">Sudah Digunakan</span>
                            <span class="s-num"><?= $digunakan ?></span>
                        </div>
                        <div class="s-progress">
                            <div class="s-fill" data-width="<?= $maxVal>0?round($digunakan/$maxVal*100):0 ?>"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PELABUHAN TERSIBUK -->
            <div class="gcard">
                <div class="gcard-title">🏆 Asal Terbanyak</div>
                <?php if(empty($tersibuk)): ?>
                <div class="empty-mini" style="padding:16px;">Belum ada data</div>
                <?php else: ?>
                <div class="tersibuk-list">
                    <?php foreach($tersibuk as $t):
                        $pct = $maxT > 0 ? round($t['total']/$maxT*100) : 0;
                    ?>
                    <div>
                        <div class="t-header">
                            <span class="t-nama">⚓ <?= htmlspecialchars($t['asal']) ?></span>
                            <span class="t-num"><?= $t['total'] ?> tiket</span>
                        </div>
                        <div class="t-bar">
                            <div class="t-fill" data-width="<?= $pct ?>"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <div style="text-align:center;margin-top:30px;padding-bottom:10px;font-size:11px;color:#1e293b;">
        NAVIRA Ferry Ticketing System — Super Admin Panel
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>

// ── REALTIME CLOCK ──
const HARI  = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
const BULAN = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

function updateClock(){
    const now = new Date();
    const hh  = String(now.getHours()).padStart(2,'0');
    const mm  = String(now.getMinutes()).padStart(2,'0');
    const ss  = String(now.getSeconds()).padStart(2,'0');
    document.getElementById('liveClock').textContent = `${hh}:${mm}:${ss}`;
    document.getElementById('liveDate').textContent  =
        `${HARI[now.getDay()]}, ${now.getDate()} ${BULAN[now.getMonth()]} ${now.getFullYear()}`;
}
updateClock();
setInterval(updateClock, 1000);

// ── COUNT-UP ──
function countUp(el){
    const target = parseInt(el.dataset.target) || 0;
    if(target === 0){ el.textContent = '0'; return; }
    const step = 16;
    const inc  = target / (1200 / step);
    let cur    = 0;
    const t    = setInterval(() => {
        cur += inc;
        if(cur >= target){ el.textContent = target.toLocaleString('id-ID'); clearInterval(t); }
        else              { el.textContent = Math.floor(cur).toLocaleString('id-ID'); }
    }, step);
}

const obs = new IntersectionObserver(entries => {
    entries.forEach(e => {
        if(e.isIntersecting){
            e.target.querySelectorAll('.stat-val[data-target]').forEach(el => {
                if(!el.dataset.done){ el.dataset.done='1'; countUp(el); }
            });
        }
    });
}, { threshold:0.2 });
document.querySelectorAll('.stat-card').forEach(c => obs.observe(c));

// ── PROGRESS BARS ──
window.addEventListener('load', () => {
    setTimeout(() => {
        document.querySelectorAll('.s-fill[data-width], .t-fill[data-width]').forEach(el => {
            el.style.width = el.dataset.width + '%';
        });
    }, 400);
});

</script>
<script src="../assets/js/mobile-nav.js"></script>
</body>
</html>