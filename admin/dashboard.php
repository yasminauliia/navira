<?php
// ════════════════════════════════════════════════════════
// ADMIN — DASHBOARD  (updated: total_penumpang + export)
// ════════════════════════════════════════════════════════
include('auth.php');
include('../config/koneksi.php');
include('../config/payment_helper.php');

ensurePaymentColumns($conn);

function safeCount($conn, $sql) {
    $r = mysqli_query($conn, $sql);
    if (!$r) return 0;
    return (int)(mysqli_fetch_assoc($r)['c'] ?? 0);
}

$paidCond    = sqlPaidTicketsCondition('t');
$total_tiket = safeCount($conn, "SELECT COUNT(*) AS c FROM tickets t WHERE {$paidCond}");
$terpakai    = safeCount($conn, "SELECT COUNT(*) AS c FROM tickets WHERE status='DIGUNAKAN' AND " . sqlPaidTicketsCondition());
$belum       = safeCount($conn, "SELECT COUNT(*) AS c FROM tickets WHERE status='BELUM DIGUNAKAN' AND " . sqlPaidTicketsCondition());
$persen      = $total_tiket > 0 ? round(($terpakai / $total_tiket) * 100) : 0;
$total_user  = safeCount($conn, "SELECT COUNT(*) AS c FROM users WHERE role='user'");
$hari_ini    = safeCount($conn, "SELECT COUNT(*) AS c FROM tickets WHERE tanggal=CURDATE() AND " . sqlPaidTicketsCondition());
$pending_pay = safeCount($conn, "SELECT COUNT(*) AS c FROM tickets WHERE " . sqlPendingPaymentCondition());
$maxVal      = max($terpakai, $belum, 1);

// Total pendapatan & penumpang (hanya transaksi paid)
$r_rp  = mysqli_query($conn, "SELECT COALESCE(SUM(total_harga),0) AS t FROM tickets WHERE " . sqlPaidTicketsCondition());
$total_pendapatan = (int)(mysqli_fetch_assoc($r_rp)['t'] ?? 0);

$r_px  = mysqli_query($conn, "SELECT COALESCE(SUM(total_penumpang),0) AS t FROM tickets WHERE " . sqlPaidTicketsCondition());
$total_penumpang_all = (int)(mysqli_fetch_assoc($r_px)['t'] ?? 0);

$nama_admin = $_SESSION['admin_nama'] ?? $_SESSION['nama'] ?? 'Admin';
$current    = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin — Navira</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="../assets/css/sidebar.css" rel="stylesheet">
<link href="../assets/css/responsive.css" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Poppins','Segoe UI',sans-serif;background:linear-gradient(135deg,#020617,#0f172a,#1e3a8a);min-height:100vh;color:white;overflow-x:hidden}
/* MAIN */
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:26px;flex-wrap:wrap;gap:14px}
.topbar-left .title{font-size:20px;font-weight:700;color:white}
.topbar-left .sub{font-size:12px;color:#475569;margin-top:4px}
.topbar-right{display:flex;align-items:center;gap:12px}
.clock-box{background:#0f172a;border:1px solid rgba(255,255,255,.07);border-radius:12px;padding:10px 18px;text-align:right}
.clock-box .clock{font-size:18px;font-weight:700;color:white;font-variant-numeric:tabular-nums;letter-spacing:1px}
.clock-box .cdate{font-size:10px;color:#475569;margin-top:2px}
.fdiv{border:none;border-top:1px solid rgba(255,255,255,.05);margin:0 0 22px}
.sec-title{font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:1px;margin-bottom:12px;display:flex;align-items:center;gap:8px}
.sec-title::after{content:'';flex:1;height:1px;background:rgba(255,255,255,.05)}
/* STAT CARDS */
.stats-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:10px;margin-bottom:24px}
.stat-card{background:#0f172a;border:1px solid rgba(255,255,255,.06);border-radius:16px;padding:16px 14px;position:relative;transition:.25s;cursor:default;overflow:hidden}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;border-radius:16px 16px 0 0;opacity:0;transition:.3s}
.stat-card:hover{transform:translateY(-3px);border-color:rgba(255,255,255,.12)}
.stat-card:hover::before{opacity:1}
.stat-card.c-blue::before{background:linear-gradient(90deg,#2563eb,#38bdf8)}
.stat-card.c-red::before{background:linear-gradient(90deg,#dc2626,#f87171)}
.stat-card.c-green::before{background:linear-gradient(90deg,#16a34a,#4ade80)}
.stat-card.c-amber::before{background:linear-gradient(90deg,#d97706,#fbbf24)}
.stat-card.c-purple::before{background:linear-gradient(90deg,#7c3aed,#a78bfa)}
.stat-card.c-cyan::before{background:linear-gradient(90deg,#0891b2,#22d3ee)}
.stat-card.c-rose::before{background:linear-gradient(90deg,#e11d48,#fb7185)}
.stat-icon{font-size:20px;margin-bottom:10px;display:block}
.stat-tag{position:absolute;top:10px;right:10px;font-size:8px;font-weight:700;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);color:#334155;border-radius:20px;padding:2px 7px;text-transform:uppercase;letter-spacing:.5px}
.stat-val{font-size:22px;font-weight:800;color:white;line-height:1;margin-bottom:4px;font-variant-numeric:tabular-nums}
.stat-val.small-val{font-size:14px;word-break:break-all}
.stat-lbl{font-size:10px;color:#475569;font-weight:500}
/* GCARD */
.gcard{background:#0f172a;border:1px solid rgba(255,255,255,.06);border-radius:16px;padding:20px;margin-bottom:20px}
.gcard-header{display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid rgba(255,255,255,.05);padding-bottom:14px;margin-bottom:18px;flex-wrap:wrap;gap:10px}
.gcard-title{font-size:13px;font-weight:700;color:white;display:flex;align-items:center;gap:8px}
.gcard-sub{font-size:11px;color:#475569;margin-top:2px}
/* PROGRESS */
.status-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:22px}
.status-row{display:flex;justify-content:space-between;margin-bottom:7px}
.status-row .lbl{font-size:12px;color:#64748b}
.status-row .val{font-size:13px;font-weight:700;color:white}
.prog-bar{height:5px;border-radius:5px;background:rgba(255,255,255,.06);overflow:hidden}
.prog-fill{height:100%;border-radius:5px;transition:width 1.2s ease}
.fill-red{background:linear-gradient(90deg,#dc2626,#f87171)}
.fill-green{background:linear-gradient(90deg,#16a34a,#4ade80)}
/* TOOLBAR */
.toolbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
.search-wrap{position:relative}
.search-wrap input{background:rgba(255,255,255,.05);border:1.5px solid rgba(255,255,255,.08);border-radius:10px;color:white;padding:9px 14px 9px 36px;font-family:'Poppins',sans-serif;font-size:13px;outline:none;transition:.25s;width:220px}
.search-wrap input:focus{border-color:#38bdf8;box-shadow:0 0 0 3px rgba(56,189,248,.1)}
.search-wrap input::placeholder{color:rgba(255,255,255,.25)}
.search-icon{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#475569;font-size:14px}
.btn-excel{background:linear-gradient(135deg,#16a34a,#22c55e);border:none;border-radius:10px;color:white;font-family:'Poppins',sans-serif;font-weight:600;font-size:13px;padding:9px 18px;cursor:pointer;transition:.25s;display:flex;align-items:center;gap:7px}
.btn-excel:hover{opacity:.88;transform:translateY(-1px);box-shadow:0 0 16px rgba(34,197,94,.35)}
.btn-scan{background:linear-gradient(135deg,#2563eb,#38bdf8);border:none;border-radius:10px;color:white;font-family:'Poppins',sans-serif;font-weight:600;font-size:13px;padding:9px 18px;cursor:pointer;transition:.25s;text-decoration:none;display:flex;align-items:center;gap:7px}
.btn-scan:hover{opacity:.88;transform:translateY(-1px);box-shadow:0 0 16px rgba(56,189,248,.35);color:white}
.live-badge{display:flex;align-items:center;gap:6px;background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.2);color:#4ade80;border-radius:20px;padding:5px 12px;font-size:11px;font-weight:700}
.live-dot{width:7px;height:7px;border-radius:50%;background:#22c55e;animation:blink 1.5s ease infinite}
/* TABLE */
.tbl-wrap{overflow-x:auto;max-height:440px;overflow-y:auto}
.tbl-wrap::-webkit-scrollbar{width:4px;height:4px}
.tbl-wrap::-webkit-scrollbar-thumb{background:rgba(255,255,255,.1);border-radius:4px}
table{width:100%;border-collapse:collapse;min-width:900px}
thead th{background:#020617;position:sticky;top:0;z-index:10;font-size:10px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.8px;padding:12px 14px;text-align:left;border-bottom:1px solid rgba(255,255,255,.05);white-space:nowrap}
tbody td{padding:11px 14px;font-size:13px;color:#cbd5e1;border-bottom:1px solid rgba(255,255,255,.04);vertical-align:middle}
tbody tr:hover{background:rgba(255,255,255,.02)}
tbody tr:last-child td{border-bottom:none}
.badge-used{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.2);color:#f87171;border-radius:20px;padding:3px 12px;font-size:11px;font-weight:700}
.badge-new{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.2);color:#4ade80;border-radius:20px;padding:3px 12px;font-size:11px;font-weight:700}
/* MODAL EXPORT */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:9999;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal-box{background:rgba(5,12,30,.98);border:1px solid rgba(255,255,255,.1);border-radius:20px;backdrop-filter:blur(20px);color:white;max-width:460px;width:90%;padding:28px;animation:mUp .25s ease}
@keyframes mUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.flabel{font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;display:block}
.finput{width:100%;background:rgba(255,255,255,.06);border:1.5px solid rgba(255,255,255,.1);border-radius:10px;color:white;padding:10px 13px;font-family:'Poppins',sans-serif;font-size:13px;outline:none;transition:.25s}
.finput:focus{border-color:#38bdf8;box-shadow:0 0 0 3px rgba(56,189,248,.12)}
input[type="date"]::-webkit-calendar-picker-indicator{filter:invert(1);opacity:.4}
.preset-btn{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:#94a3b8;border-radius:8px;padding:6px 14px;font-size:12px;font-family:'Poppins',sans-serif;cursor:pointer;transition:.2s;font-weight:600}
.preset-btn:hover,.preset-btn.active-p{background:rgba(56,189,248,.1);border-color:rgba(56,189,248,.2);color:#38bdf8}
.empty-state{text-align:center;padding:40px 20px;color:#334155}
.empty-state .eicon{font-size:36px;margin-bottom:10px}
@media(max-width:1400px){.stats-grid{grid-template-columns:repeat(4,1fr)}}
@media(max-width:1000px){.stats-grid{grid-template-columns:repeat(3,1fr)}.status-grid{grid-template-columns:1fr}}
@media(max-width:768px){.main-wrap{margin-left:0;padding:16px;padding-top:72px}.stats-grid{grid-template-columns:1fr 1fr}.topbar{flex-direction:column;align-items:flex-start}.topbar-right{width:100%;justify-content:space-between}}
@media(max-width:480px){.stats-grid{grid-template-columns:1fr}}
</style>
</head>
<body class="has-mobile-nav">

<?php include('sidebar.php'); ?>

<!-- MAIN -->
<div class="main-wrap">

    <div class="topbar">
        <div class="topbar-left">
            <div class="title">Dashboard Admin 🌊</div>
            <div class="sub">Pantau tiket dan aktivitas pelabuhan secara realtime</div>
        </div>
        <div class="topbar-right">
            <a href="scan.php" class="btn-scan">📷 Scan QR</a>
            <div class="clock-box">
                <div class="clock" id="liveClock">00:00:00</div>
                <div class="cdate" id="liveDate">—</div>
            </div>
        </div>
    </div>

    <hr class="fdiv">

    <!-- STAT CARDS -->
    <div class="sec-title">Statistik Tiket</div>
    <div class="stats-grid">
        <div class="stat-card c-blue">
            <span class="stat-tag">Total</span>
            <span class="stat-icon">🎫</span>
            <div class="stat-val" id="statTotal"><?= $total_tiket ?></div>
            <div class="stat-lbl">Total Tiket</div>
        </div>
        <div class="stat-card c-red">
            <span class="stat-tag">Pakai</span>
            <span class="stat-icon">✅</span>
            <div class="stat-val" id="statPakai"><?= $terpakai ?></div>
            <div class="stat-lbl">Sudah Digunakan</div>
        </div>
        <div class="stat-card c-green">
            <span class="stat-tag">Aktif</span>
            <span class="stat-icon">🟢</span>
            <div class="stat-val" id="statBelum"><?= $belum ?></div>
            <div class="stat-lbl">Belum Digunakan</div>
        </div>
        <div class="stat-card c-amber">
            <span class="stat-tag">%</span>
            <span class="stat-icon">📊</span>
            <div class="stat-val" id="statPersen"><?= $persen ?>%</div>
            <div class="stat-lbl">Tingkat Pemakaian</div>
        </div>
        <div class="stat-card c-cyan">
            <span class="stat-tag">Hari Ini</span>
            <span class="stat-icon">📅</span>
            <div class="stat-val" id="statHariIni"><?= $hari_ini ?></div>
            <div class="stat-lbl">Tiket Hari Ini</div>
        </div>
        <div class="stat-card c-purple">
            <span class="stat-tag">Penumpang</span>
            <span class="stat-icon">👥</span>
            <div class="stat-val" id="statTotalPax"><?= number_format($total_penumpang_all, 0, ',', '.') ?></div>
            <div class="stat-lbl">Total Penumpang</div>
        </div>
        <div class="stat-card c-rose">
            <span class="stat-tag">Pendapatan</span>
            <span class="stat-icon">💰</span>
            <div class="stat-val small-val" id="statPendapatan">
                Rp <?= number_format($total_pendapatan, 0, ',', '.') ?>
            </div>
            <div class="stat-lbl">Total Pendapatan</div>
        </div>
    </div>

    <!-- STATUS PROGRESS + FILTER CEPAT -->
    <div class="status-grid">
        <div class="gcard" style="margin-bottom:0;">
            <div style="font-size:12px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.8px;margin-bottom:14px;">
                📈 Status Tiket
            </div>
            <div style="margin-bottom:14px;">
                <div class="status-row">
                    <span class="lbl">Sudah Digunakan</span>
                    <span class="val" id="pr-pakai"><?= $terpakai ?></span>
                </div>
                <div class="prog-bar">
                    <div class="prog-fill fill-red" id="pfill-pakai"
                         style="width:<?= $maxVal>0 ? round($terpakai/$maxVal*100) : 0 ?>%"></div>
                </div>
            </div>
            <div>
                <div class="status-row">
                    <span class="lbl">Belum Digunakan</span>
                    <span class="val" id="pr-belum"><?= $belum ?></span>
                </div>
                <div class="prog-bar">
                    <div class="prog-fill fill-green" id="pfill-belum"
                         style="width:<?= $maxVal>0 ? round($belum/$maxVal*100) : 0 ?>%"></div>
                </div>
            </div>
        </div>

        <div class="gcard" style="margin-bottom:0;">
            <div style="font-size:12px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.8px;margin-bottom:14px;">
                📅 Export Cepat
            </div>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <div style="display:flex;gap:8px;">
                    <div style="flex:1;">
                        <label class="flabel">Dari</label>
                        <input type="date" id="quickFrom" class="finput" value="<?= date('Y-m-01') ?>">
                    </div>
                    <div style="flex:1;">
                        <label class="flabel">Sampai</label>
                        <input type="date" id="quickTo" class="finput" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <button class="btn-excel" style="justify-content:center;" onclick="exportQuick()">
                    📥 Export Rentang Ini
                </button>
            </div>
        </div>
    </div>

    <!-- TABEL REALTIME -->
    <div class="gcard">
        <div class="gcard-header">
            <div>
                <div class="gcard-title">
                    📋 Data Tiket
                    <div class="live-badge"><div class="live-dot"></div> LIVE</div>
                </div>
                <div class="gcard-sub">Diperbarui otomatis setiap 3 detik</div>
            </div>
            <div class="toolbar">
                <div class="search-wrap">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="searchInput" placeholder="Cari kode, nama, rute..." oninput="filterTable()">
                </div>
                <button class="btn-excel" onclick="bukaModal()">📥 Export Excel</button>
            </div>
        </div>

        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Kode Booking</th>
                        <th>Nama</th>
                        <th>Rute</th>
                        <th>Tanggal</th>
                        <th>Layanan</th>
                        <th style="text-align:center;">Penumpang</th>
                        <th>Total Harga</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="tabel-data">
                    <tr><td colspan="8">
                        <div class="empty-state"><div class="eicon">⏳</div>Memuat data...</div>
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /main-wrap -->


<!-- MODAL EXPORT EXCEL -->
<div class="modal-overlay" id="modalOverlay" onclick="if(event.target===this)tutupModal()">
<div class="modal-box">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <div>
            <div style="font-size:17px;font-weight:700;">📥 Download Laporan Excel</div>
            <div style="font-size:12px;color:#64748b;margin-top:4px;">Pilih rentang tanggal yang diinginkan</div>
        </div>
        <button onclick="tutupModal()" style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);border-radius:8px;width:30px;height:30px;color:#94a3b8;cursor:pointer;font-size:15px;">✕</button>
    </div>

    <!-- PRESET -->
    <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
        <button class="preset-btn" onclick="setPreset('today',this)">Hari Ini</button>
        <button class="preset-btn" onclick="setPreset('week',this)">7 Hari</button>
        <button class="preset-btn" onclick="setPreset('month',this)">Bulan Ini</button>
        <button class="preset-btn" onclick="setPreset('all',this)">Semua Data</button>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
        <div>
            <label class="flabel">Tanggal Mulai</label>
            <input type="date" id="exFrom" class="finput" value="<?= date('Y-m-01') ?>">
        </div>
        <div>
            <label class="flabel">Tanggal Akhir</label>
            <input type="date" id="exTo" class="finput" value="<?= date('Y-m-d') ?>">
        </div>
    </div>

    <div style="margin-bottom:20px;">
        <label class="flabel">Filter Status (opsional)</label>
        <select id="exStatus" class="finput" style="cursor:pointer;">
            <option value="">Semua Status</option>
            <option value="BELUM DIGUNAKAN">Belum Digunakan</option>
            <option value="DIGUNAKAN">Sudah Digunakan</option>
        </select>
    </div>

    <div style="display:flex;gap:10px;">
        <button onclick="tutupModal()" style="flex:1;padding:13px;border-radius:10px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.6);font-family:'Poppins',sans-serif;font-weight:600;cursor:pointer;">
            Batal
        </button>
        <button onclick="doExport()" style="flex:2;padding:13px;border-radius:10px;background:linear-gradient(135deg,#16a34a,#22c55e);border:none;color:white;font-family:'Poppins',sans-serif;font-weight:700;cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;gap:8px;">
            📥 Download Excel
        </button>
    </div>
</div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ══ CLOCK ══
const HARI  = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
const BULAN_ARR = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
function updateClock() {
    const now = new Date();
    const pad = n => String(n).padStart(2,'0');
    document.getElementById('liveClock').textContent = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
    document.getElementById('liveDate').textContent  = `${HARI[now.getDay()]}, ${now.getDate()} ${BULAN_ARR[now.getMonth()]} ${now.getFullYear()}`;
}
updateClock();
setInterval(updateClock, 1000);

// ══ STATS POLLING ══
let lastPakai = <?= $terpakai ?>;

function loadStats() {
    fetch('get_data.php')
    .then(r => r.json())
    .then(d => {
        const total   = parseInt(d.total)  || 0;
        const pakai   = parseInt(d.pakai)  || 0;
        const belum   = parseInt(d.belum)  || 0;
        const hariIni = parseInt(d.hari_ini) || 0;
        const pax     = parseInt(d.total_pax) || 0;
        const pendp   = parseInt(d.total_pendapatan) || 0;
        const persen  = total > 0 ? Math.round((pakai/total)*100) : 0;

        document.getElementById('statTotal').textContent    = total;
        document.getElementById('statPakai').textContent    = pakai;
        document.getElementById('statBelum').textContent    = belum;
        document.getElementById('statPersen').textContent   = persen + '%';
        document.getElementById('statHariIni').textContent  = hariIni;
        document.getElementById('statTotalPax').textContent = pax.toLocaleString('id-ID');
        document.getElementById('statPendapatan').textContent = 'Rp ' + pendp.toLocaleString('id-ID');

        // Progress bar
        const maxV = Math.max(pakai, belum, 1);
        document.getElementById('pr-pakai').textContent = pakai;
        document.getElementById('pr-belum').textContent = belum;
        document.getElementById('pfill-pakai').style.width = Math.round(pakai/maxV*100) + '%';
        document.getElementById('pfill-belum').style.width = Math.round(belum/maxV*100) + '%';

        // Notif suara saat tiket baru di-scan
        if (lastPakai !== 0 && pakai > lastPakai) {
            try { new Audio("https://actions.google.com/sounds/v1/cartoon/clang_and_wobble.ogg").play(); } catch(e){}
        }
        lastPakai = pakai;
    })
    .catch(e => console.warn('get_data.php error:', e));
}

// ══ TABEL POLLING ══
function loadTabel() {
    fetch('get_tiket.php')
    .then(r => r.text())
    .then(html => {
        document.getElementById('tabel-data').innerHTML = html;
        filterTable();
    })
    .catch(e => console.warn('get_tiket.php error:', e));
}

function filterTable() {
    const kw = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#tabel-data tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(kw) ? '' : 'none';
    });
}

// Mulai polling
loadStats();
loadTabel();
setInterval(() => { loadStats(); loadTabel(); }, 3000);

// ══ MODAL EXPORT ══
function bukaModal()  { document.getElementById('modalOverlay').classList.add('open'); }
function tutupModal() { document.getElementById('modalOverlay').classList.remove('open'); }

function setPreset(tipe, btn) {
    document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active-p'));
    btn.classList.add('active-p');
    const today = new Date();
    const fmt   = d => d.toISOString().split('T')[0];
    const from  = document.getElementById('exFrom');
    const to    = document.getElementById('exTo');
    if (tipe === 'today') {
        from.value = fmt(today); to.value = fmt(today);
    } else if (tipe === 'week') {
        const w = new Date(today); w.setDate(w.getDate() - 6);
        from.value = fmt(w); to.value = fmt(today);
    } else if (tipe === 'month') {
        from.value = fmt(new Date(today.getFullYear(), today.getMonth(), 1));
        to.value   = fmt(today);
    } else if (tipe === 'all') {
        from.value = '2020-01-01'; to.value = fmt(today);
    }
}

function doExport() {
    const from   = document.getElementById('exFrom').value;
    const to     = document.getElementById('exTo').value;
    const status = document.getElementById('exStatus').value;
    if (!from || !to) { alert('⚠️ Pilih rentang tanggal!'); return; }
    if (from > to)    { alert('⚠️ Tanggal mulai tidak boleh lebih dari tanggal akhir!'); return; }
    window.open(`export_excel.php?from=${from}&to=${to}&status=${encodeURIComponent(status)}`, '_blank');
    tutupModal();
}

function exportQuick() {
    const from = document.getElementById('quickFrom').value;
    const to   = document.getElementById('quickTo').value;
    if (!from || !to) { alert('⚠️ Isi rentang tanggal!'); return; }
    window.open(`export_excel.php?from=${from}&to=${to}`, '_blank');
}
</script>
<script src="../assets/js/mobile-nav.js"></script>
</body>
</html>