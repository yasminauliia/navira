<?php
// ════════════════════════════════════════════════════════
// ADMIN — DATA TIKET
// Fixed: sesuai struktur DB (users: id,nama,email,password,role)
// tickets: id_ticket,user_id,kode_booking,status,tanggal,asal_id,tujuan_id,
//          jam,layanan,jenis_pengguna,kendaraan,golongan,plat,
//          total_harga,total_penumpang
// harga_kendaraan: id,asal_id,tujuan_id,golongan,harga_reguler,harga_express
// ════════════════════════════════════════════════════════
include('auth.php');
include('../config/koneksi.php');
include('../config/payment_helper.php');

ensurePaymentColumns($conn);

$nama_admin = $_SESSION['admin_nama'] ?? $_SESSION['nama'] ?? 'Admin';
$current    = basename($_SERVER['PHP_SELF']);

// ── Total semua tiket di DB ──
$r_tot        = mysqli_query($conn, "SELECT COUNT(*) AS c FROM tickets");
$total_all_db = $r_tot ? (int)(mysqli_fetch_assoc($r_tot)['c'] ?? 0) : 0;

// ── Parameter filter ──
$from     = $_GET['from']   ?? '';
$to       = $_GET['to']     ?? '';
$status      = $_GET['status'] ?? '';
$pay_status  = $_GET['pay_status'] ?? '';
$jenis       = $_GET['jenis']  ?? '';
$cari     = trim($_GET['cari'] ?? '');
$show_all = isset($_GET['all']);

// Tidak ada GET sama sekali → tampilkan semua
$no_filter = ($from === '' && $to === '' && $status === '' && $pay_status === '' && $jenis === '' && $cari === '' && !$show_all);

// Default tanggal untuk input display
$from_display = ($from !== '') ? $from : date('Y-m-01');
$to_display   = ($to   !== '') ? $to   : date('Y-m-d');

// Validasi format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from_display)) $from_display = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to_display))   $to_display   = date('Y-m-d');
if ($from_display > $to_display) [$from_display, $to_display] = [$to_display, $from_display];

// ── Build WHERE ──
$clauses = [];

// Filter tanggal hanya kalau user eksplisit set (ada GET from/to dan bukan mode all)
if ($from !== '' && !$show_all && !$no_filter) {
    $fe = mysqli_real_escape_string($conn, $from_display);
    $te = mysqli_real_escape_string($conn, $to_display);
    $clauses[] = "t.tanggal BETWEEN '$fe' AND '$te'";
}

if ($status !== '') {
    $s = mysqli_real_escape_string($conn, $status);
    $clauses[] = "t.status = '$s'";
}
if ($pay_status !== '') {
    $ps = mysqli_real_escape_string($conn, normalizePaymentStatus($pay_status));
    $clauses[] = "t.payment_status = '$ps'";
}
if ($jenis !== '') {
    $j = mysqli_real_escape_string($conn, $jenis);
    $clauses[] = "t.jenis_pengguna = '$j'";
}
if ($cari !== '') {
    $c = mysqli_real_escape_string($conn, $cari);
    $clauses[] = "(t.kode_booking LIKE '%$c%'
                   OR u.nama   LIKE '%$c%'
                   OR u.email  LIKE '%$c%'
                   OR a.nama_pelabuhan LIKE '%$c%'
                   OR b.nama_pelabuhan LIKE '%$c%'
                   OR t.plat   LIKE '%$c%')";
}

$where = empty($clauses) ? '' : 'WHERE ' . implode(' AND ', $clauses);

$sql = "
    SELECT
        t.id_ticket,
        t.kode_booking,
        COALESCE(u.nama, 'N/A')        AS nama_user,
        COALESCE(u.email, '-')         AS email,
        COALESCE(a.nama_pelabuhan, '-') AS asal,
        COALESCE(b.nama_pelabuhan, '-') AS tujuan,
        t.tanggal,
        t.jam,
        t.layanan,
        t.jenis_pengguna,
        COALESCE(t.kendaraan, '') AS kendaraan,
        COALESCE(t.golongan,  '') AS golongan,
        COALESCE(t.plat,      '') AS plat,
        COALESCE(t.total_penumpang, 0) AS total_penumpang,
        COALESCE(t.total_harga,     0) AS total_harga,
        t.status,
        COALESCE(t.payment_status, 'paid') AS payment_status,
        t.paid_at
    FROM tickets t
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN pelabuhan a ON a.id = t.asal_id
    LEFT JOIN pelabuhan b ON b.id = t.tujuan_id
    $where
    ORDER BY t.id_ticket DESC
";

$result    = mysqli_query($conn, $sql);
$sql_error = mysqli_error($conn);

$rows    = [];
$grand   = 0;
$totalPx = 0;

if ($result) {
    while ($r = mysqli_fetch_assoc($result)) {
        $rows[]  = $r;
        $grand   += (int)$r['total_harga'];
        $totalPx += (int)$r['total_penumpang'];
    }
}

$jumlah    = count($rows);
$digunakan = count(array_filter($rows, fn($r) => strtoupper(trim($r['status'])) === 'DIGUNAKAN'));
$belum     = $jumlah - $digunakan;

// ── Label golongan ──
function golLabel(string $g): string {
    $map = [
        'gol_1'=>'Gol I — Sepeda','gol_2'=>'Gol II — Motor <500cc',
        'gol_3'=>'Gol III — Motor >500cc','gol_4a'=>'Gol IVA — Mobil Penumpang',
        'gol_4b'=>'Gol IVB — Mobil Barang','gol_5a'=>'Gol VA — Bus Sedang',
        'gol_5b'=>'Gol VB — Truk Sedang','gol_6a'=>'Gol VIA — Bus Besar',
        'gol_6b'=>'Gol VIB — Truk Besar','gol_7'=>'Gol VII — Tronton 10–12m',
        'gol_8'=>'Gol VIII — Tronton 12–16m','gol_9'=>'Gol IX — Tronton >16m',
    ];
    return $map[$g] ?? $g;
}

// ── Helper tanggal Indonesia ──
$bulan = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
function tglPendek(string $d, array $bl): string {
    if (!$d || $d === '0000-00-00') return '—';
    $p = explode('-', $d);
    if (count($p) < 3) return $d;
    return sprintf('%02d', (int)$p[2]).' '.$bl[(int)$p[1]].' '.$p[0];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Tiket — Navira Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="../assets/css/sidebar.css" rel="stylesheet">
<link href="../assets/css/responsive.css" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Poppins','Segoe UI',sans-serif;background:linear-gradient(135deg,#020617,#0f172a,#1e3a8a);min-height:100vh;color:white;overflow-x:hidden}

/* ════ LAYOUT ════ */
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:14px}
.topbar-left .title{font-size:20px;font-weight:700}
.topbar-left .sub{font-size:12px;color:#475569;margin-top:4px}
.topbar-right{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.fdiv{border:none;border-top:1px solid rgba(255,255,255,.05);margin:0 0 22px}
.sec-title{font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:1px;margin-bottom:12px;display:flex;align-items:center;gap:8px}
.sec-title::after{content:'';flex:1;height:1px;background:rgba(255,255,255,.05)}

/* ════ MINI STAT CARDS ════ */
.mini-stats{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:22px}
.mstat{background:#0f172a;border:1px solid rgba(255,255,255,.06);border-radius:14px;padding:14px 16px;position:relative;overflow:hidden;transition:.25s}
.mstat::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;border-radius:14px 14px 0 0}
.mstat:hover{transform:translateY(-2px);border-color:rgba(255,255,255,.1)}
.mstat.c-blue::before  {background:linear-gradient(90deg,#2563eb,#38bdf8)}
.mstat.c-green::before {background:linear-gradient(90deg,#16a34a,#4ade80)}
.mstat.c-red::before   {background:linear-gradient(90deg,#dc2626,#f87171)}
.mstat.c-purple::before{background:linear-gradient(90deg,#7c3aed,#a78bfa)}
.mstat.c-amber::before {background:linear-gradient(90deg,#d97706,#fbbf24)}
.mstat-icon{font-size:18px;margin-bottom:8px;display:block}
.mstat-val{font-size:20px;font-weight:800;color:white;line-height:1;margin-bottom:3px;font-variant-numeric:tabular-nums}
.mstat-val.sm{font-size:13px}
.mstat-lbl{font-size:10px;color:#475569;font-weight:500}

/* ════ FILTER CARD ════ */
.filter-card{background:#0f172a;border:1px solid rgba(255,255,255,.06);border-radius:16px;padding:18px 20px;margin-bottom:20px}
.filter-card form{display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap}
.fg{display:flex;flex-direction:column;gap:5px}
.flabel{font-size:10px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.7px}
.finput,.fselect{background:rgba(255,255,255,.05);border:1.5px solid rgba(255,255,255,.08);border-radius:10px;color:white;padding:9px 13px;font-family:'Poppins',sans-serif;font-size:13px;outline:none;transition:.25s}
.finput:focus,.fselect:focus{border-color:#38bdf8;box-shadow:0 0 0 3px rgba(56,189,248,.12)}
.finput::placeholder{color:rgba(255,255,255,.25)}
.fselect option{background:#0f172a;color:white}
input[type="date"]::-webkit-calendar-picker-indicator{filter:invert(1);opacity:.4}
.preset-row{width:100%;display:flex;gap:6px;flex-wrap:wrap;padding-top:10px;border-top:1px solid rgba(255,255,255,.05);margin-top:6px;}

/* ════ BUTTONS ════ */
.btn-filter{background:linear-gradient(135deg,#2563eb,#38bdf8);border:none;border-radius:10px;color:white;font-family:'Poppins',sans-serif;font-weight:600;font-size:13px;padding:9px 18px;cursor:pointer;transition:.25s;display:flex;align-items:center;gap:6px;white-space:nowrap}
.btn-filter:hover{opacity:.88;transform:translateY(-1px)}
.btn-reset{background:rgba(255,255,255,.06);border:1.5px solid rgba(255,255,255,.08);color:#94a3b8;border-radius:10px;font-family:'Poppins',sans-serif;font-weight:600;font-size:13px;padding:9px 14px;cursor:pointer;transition:.25s;text-decoration:none;display:flex;align-items:center;gap:6px}
.btn-reset:hover{background:rgba(255,255,255,.1);color:white;border-color:rgba(255,255,255,.15)}
.btn-excel{background:linear-gradient(135deg,#16a34a,#22c55e);border:none;border-radius:10px;color:white;font-family:'Poppins',sans-serif;font-weight:600;font-size:13px;padding:9px 18px;cursor:pointer;transition:.25s;display:flex;align-items:center;gap:6px;text-decoration:none;white-space:nowrap}
.btn-excel:hover{opacity:.88;color:white;box-shadow:0 0 16px rgba(34,197,94,.3)}
.btn-detail{background:rgba(56,189,248,.1);border:1px solid rgba(56,189,248,.2);color:#38bdf8;border-radius:8px;padding:4px 12px;font-size:11px;font-weight:700;cursor:pointer;transition:.2s;font-family:'Poppins',sans-serif}
.btn-detail:hover{background:rgba(56,189,248,.2);color:#7dd3fc}
.pbtn{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);color:#94a3b8;border-radius:8px;padding:6px 12px;font-size:12px;font-weight:600;cursor:pointer;transition:.2s;font-family:'Poppins',sans-serif}
.pbtn:hover{background:rgba(56,189,248,.1);border-color:rgba(56,189,248,.2);color:#38bdf8}
.pbtn.active{background:rgba(56,189,248,.15);border-color:#38bdf8;color:#38bdf8}
.pbtn:disabled{opacity:.3;cursor:not-allowed}

/* ════ ALERT BARS ════ */
.alert-bar{border-radius:14px;padding:14px 20px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px}
.alert-warn{background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2)}
.alert-info{background:rgba(56,189,248,.06);border:1px solid rgba(56,189,248,.15)}
.alert-err{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2)}

/* ════ TABLE ════ */
.tbl-card{background:#0f172a;border:1px solid rgba(255,255,255,.06);border-radius:16px;overflow:hidden}
.tbl-header{display:flex;justify-content:space-between;align-items:center;padding:18px 20px;border-bottom:1px solid rgba(255,255,255,.05);flex-wrap:wrap;gap:10px}
.tbl-title{font-size:14px;font-weight:700;color:white;display:flex;align-items:center;gap:8px}
.tbl-sub{font-size:11px;color:#475569;margin-top:2px}
.tbl-wrap{overflow-x:auto;max-height:520px;overflow-y:auto}
.tbl-wrap::-webkit-scrollbar{width:4px;height:4px}
.tbl-wrap::-webkit-scrollbar-thumb{background:rgba(255,255,255,.1);border-radius:4px}
table{width:100%;border-collapse:collapse;min-width:1080px}
thead th{background:#020617;position:sticky;top:0;z-index:10;font-size:10px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.8px;padding:13px 14px;text-align:left;border-bottom:1px solid rgba(255,255,255,.05);white-space:nowrap;cursor:pointer;user-select:none}
thead th:hover{color:#94a3b8}
thead th.sorted{color:#38bdf8}
thead th .si{margin-left:4px;opacity:.4;font-size:9px}
thead th.sorted .si{opacity:1;color:#38bdf8}
tbody td{padding:12px 14px;font-size:13px;color:#cbd5e1;border-bottom:1px solid rgba(255,255,255,.04);vertical-align:middle}
tbody tr:hover{background:rgba(255,255,255,.025)}
tbody tr:last-child td{border-bottom:none}
.no-data{text-align:center;padding:52px 20px;color:#334155}
.no-data .icon{font-size:40px;margin-bottom:10px}

/* ════ BADGES — FIX: nowrap supaya icon+teks tidak kepotong/wrap dua baris ════ */
.badge-used,.badge-new{
    display:inline-flex;align-items:center;gap:4px;white-space:nowrap;
    border-radius:20px;padding:3px 12px;font-size:11px;font-weight:700;line-height:1.4;
}
.badge-used{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.2);color:#f87171}
.badge-new {background:rgba(34,197,94,.1); border:1px solid rgba(34,197,94,.2); color:#4ade80}

.badge-lay,.badge-exp,.badge-kend,.badge-walk{
    display:inline-flex;align-items:center;gap:4px;white-space:nowrap;
    border-radius:20px;padding:3px 11px;font-size:10.5px;font-weight:700;line-height:1.4;
}
.badge-lay {background:rgba(56,189,248,.1); border:1px solid rgba(56,189,248,.15); color:#38bdf8}
.badge-exp {background:rgba(251,191,36,.1); border:1px solid rgba(251,191,36,.15); color:#fbbf24}
.badge-kend{background:rgba(251,191,36,.08);border:1px solid rgba(251,191,36,.15); color:#fbbf24}
.badge-walk{background:rgba(167,139,250,.08);border:1px solid rgba(167,139,250,.15);color:#a78bfa}

.kode-cell{font-weight:700;color:white;font-size:11px;letter-spacing:.5px;font-variant-numeric:tabular-nums}
.rute-arr{color:#334155;margin:0 4px}
.harga-cell{color:#38bdf8;font-weight:600;font-size:13px;white-space:nowrap}
.pax-cell{display:inline-flex;align-items:center;gap:4px;white-space:nowrap;background:rgba(56,189,248,.07);border:1px solid rgba(56,189,248,.12);border-radius:20px;padding:2px 10px;font-size:11px;font-weight:700;color:#7dd3fc}
.cnt-badge{background:rgba(56,189,248,.1);border:1px solid rgba(56,189,248,.15);color:#38bdf8;border-radius:20px;padding:2px 10px;font-size:11px;font-weight:700}

/* Kolom layanan/jenis/status diberi lebar minimum supaya badge tak terjepit */
th:nth-child(7), td:nth-child(7){min-width:108px}
th:nth-child(8), td:nth-child(8){min-width:118px}
th:nth-child(11),td:nth-child(11){min-width:118px}
th:nth-child(12),td:nth-child(12){min-width:150px}

/* ════ PAGINATION ════ */
.pagi-wrap{display:flex;justify-content:space-between;align-items:center;padding:14px 20px;border-top:1px solid rgba(255,255,255,.05);flex-wrap:wrap;gap:10px}
.pagi-info{font-size:12px;color:#475569}
.pagi-btns{display:flex;gap:6px;flex-wrap:wrap}

/* ════ MODAL ════ */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:9999;align-items:center;justify-content:center;padding:20px}
.modal-overlay.open{display:flex}
.modal-box{background:#0f172a;border:1px solid rgba(255,255,255,.1);border-radius:20px;color:white;max-width:520px;width:100%;padding:0;animation:mUp .25s ease;overflow:hidden}
@keyframes mUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.modal-head{padding:20px 24px;border-bottom:1px solid rgba(255,255,255,.06);display:flex;justify-content:space-between;align-items:center}
.modal-head h3{font-size:16px;font-weight:700}
.modal-body{padding:20px 24px;max-height:70vh;overflow-y:auto}
.modal-body::-webkit-scrollbar{width:4px}
.modal-body::-webkit-scrollbar-thumb{background:rgba(56,189,248,.3);border-radius:4px}
.drow{display:flex;justify-content:space-between;align-items:flex-start;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.04)}
.drow:last-child{border-bottom:none}
.dlbl{font-size:12px;color:#64748b;flex-shrink:0;width:130px}
.dval{font-size:13px;color:white;font-weight:500;text-align:right;word-break:break-word;flex:1}
.mkode{background:rgba(56,189,248,.07);border:1px solid rgba(56,189,248,.15);border-radius:12px;padding:14px 18px;text-align:center;margin-bottom:16px}
.mkode-big{font-size:18px;font-weight:800;letter-spacing:3px;color:white}
.mkode-sub{font-size:11px;color:#64748b;margin-top:4px}
.mbtn-close{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);border-radius:8px;width:30px;height:30px;color:#94a3b8;cursor:pointer;font-size:15px;display:flex;align-items:center;justify-content:center;transition:.2s}
.mbtn-close:hover{background:rgba(239,68,68,.1);border-color:rgba(239,68,68,.2);color:#f87171}

/* ════ RESPONSIVE ════ */
@media(max-width:1100px){.mini-stats{grid-template-columns:repeat(3,1fr)}}
@media(max-width:768px){
    .main-wrap{margin-left:0;padding:16px;padding-top:72px}
    .mini-stats{grid-template-columns:1fr 1fr}
    .filter-bar{flex-direction:column;align-items:stretch}
    .topbar{flex-direction:column;align-items:flex-start;gap:12px}
}
@media(max-width:480px){
    .mini-stats{grid-template-columns:1fr}
}
</style>
</head>
<body class="has-mobile-nav">

<?php include('sidebar.php'); ?>

<!-- ══ MAIN ══ -->
<div class="main-wrap">

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="topbar-left">
            <div class="title">🎫 Data Tiket</div>
            <div class="sub">
                <?php if ($no_filter || $show_all): ?>
                    Semua data &nbsp;·&nbsp;
                <?php else: ?>
                    <?= tglPendek($from_display,$bulan) ?> — <?= tglPendek($to_display,$bulan) ?> &nbsp;·&nbsp;
                <?php endif; ?>
                <b style="color:white;"><?= $jumlah ?></b> tiket ditampilkan
                <?php if ($jumlah < $total_all_db): ?>
                    <span style="color:#334155;"> (total DB: <?= $total_all_db ?>)</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="topbar-right">
            <a href="export_excel.php?from=<?= urlencode($from_display) ?>&to=<?= urlencode($to_display) ?>&status=<?= urlencode($status) ?>"
               target="_blank" class="btn-excel">📥 Export Excel</a>
            <a href="dashboard.php" class="btn-reset">← Dashboard</a>
        </div>
    </div>

    <hr class="fdiv">

    <!-- ALERTS -->
    <?php if ($total_all_db === 0): ?>
    <div class="alert-bar alert-warn">
        <span style="font-size:20px;">⚠️</span>
        <div>
            <div style="font-size:13px;font-weight:700;color:#fbbf24;">Tabel tickets masih kosong</div>
            <div style="font-size:12px;color:#94a3b8;margin-top:2px;">Belum ada tiket yang tersimpan. Silakan beli tiket terlebih dahulu.</div>
        </div>
    </div>
    <?php elseif ($sql_error): ?>
    <div class="alert-bar alert-err" style="flex-direction:column;align-items:flex-start;">
        <div style="font-size:13px;font-weight:700;color:#f87171;">❌ SQL Error</div>
        <code style="font-size:11px;color:#fca5a5;margin-top:5px;word-break:break-all;"><?= htmlspecialchars($sql_error) ?></code>
    </div>
    <?php elseif ($jumlah === 0 && $total_all_db > 0): ?>
    <div class="alert-bar alert-info">
        <div style="display:flex;align-items:center;gap:10px;">
            <span style="font-size:20px;">ℹ️</span>
            <div>
                <div style="font-size:13px;font-weight:700;color:#38bdf8;">Tidak ada data untuk filter ini</div>
                <div style="font-size:12px;color:#64748b;margin-top:2px;">
                    Total di DB: <b style="color:white;"><?= $total_all_db ?> tiket</b>
                </div>
            </div>
        </div>
        <a href="data_tiket.php?all=1" style="background:rgba(56,189,248,.1);border:1px solid rgba(56,189,248,.2);color:#38bdf8;border-radius:8px;padding:7px 16px;font-size:12px;font-weight:700;text-decoration:none;">
            🔍 Tampilkan Semua
        </a>
    </div>
    <?php endif; ?>

    <!-- MINI STATS -->
    <div class="sec-title">Ringkasan <?= ($no_filter||$show_all)?'Semua Data':'Periode' ?></div>
    <div class="mini-stats">
        <div class="mstat c-blue">
            <span class="mstat-icon">🎫</span>
            <div class="mstat-val"><?= $jumlah ?></div>
            <div class="mstat-lbl">Tiket Tampil</div>
        </div>
        <div class="mstat c-green">
            <span class="mstat-icon">🟢</span>
            <div class="mstat-val"><?= $belum ?></div>
            <div class="mstat-lbl">Belum Digunakan</div>
        </div>
        <div class="mstat c-red">
            <span class="mstat-icon">✅</span>
            <div class="mstat-val"><?= $digunakan ?></div>
            <div class="mstat-lbl">Sudah Digunakan</div>
        </div>
        <div class="mstat c-purple">
            <span class="mstat-icon">👥</span>
            <div class="mstat-val"><?= number_format($totalPx,0,',','.') ?></div>
            <div class="mstat-lbl">Total Penumpang</div>
        </div>
        <div class="mstat c-amber">
            <span class="mstat-icon">💰</span>
            <div class="mstat-val sm">Rp <?= number_format($grand,0,',','.') ?></div>
            <div class="mstat-lbl">Total Pendapatan</div>
        </div>
    </div>

    <!-- FILTER -->
    <div class="filter-card">
        <form method="GET" id="formFilter">
            <div class="fg">
                <span class="flabel">Dari</span>
                <input type="date" name="from" class="finput" value="<?= htmlspecialchars($from_display) ?>">
            </div>
            <div class="fg">
                <span class="flabel">Sampai</span>
                <input type="date" name="to" class="finput" value="<?= htmlspecialchars($to_display) ?>">
            </div>
            <div class="fg">
                <span class="flabel">Status Tiket</span>
                <select name="status" class="fselect">
                    <option value="">Semua Status</option>
                    <option value="BELUM DIGUNAKAN" <?= $status==='BELUM DIGUNAKAN'?'selected':'' ?>>Belum Digunakan</option>
                    <option value="DIGUNAKAN"       <?= $status==='DIGUNAKAN'?'selected':'' ?>>Sudah Digunakan</option>
                </select>
            </div>
            <div class="fg">
                <span class="flabel">Status Pembayaran</span>
                <select name="pay_status" class="fselect">
                    <option value="">Semua Pembayaran</option>
                    <?php foreach (PAYMENT_STATUSES_ALL as $ps): $pm = getPaymentStatusMeta($ps); ?>
                    <option value="<?= htmlspecialchars($ps) ?>" <?= $pay_status===$ps?'selected':'' ?>><?= htmlspecialchars($pm['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg">
                <span class="flabel">Jenis</span>
                <select name="jenis" class="fselect">
                    <option value="">Semua Jenis</option>
                    <option value="penumpang" <?= $jenis==='penumpang'?'selected':'' ?>>🚶 Pejalan Kaki</option>
                    <option value="kendaraan" <?= $jenis==='kendaraan'?'selected':'' ?>>🚗 Berkendara</option>
                </select>
            </div>
            <div class="fg" style="flex:1;min-width:160px;">
                <span class="flabel">Cari</span>
                <input type="text" name="cari" class="finput" style="width:100%;"
                    placeholder="Kode, nama, email, rute, plat..."
                    value="<?= htmlspecialchars($cari) ?>">
            </div>
            <div class="fg" style="justify-content:flex-end;">
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="btn-filter">🔍 Filter</button>
                    <a href="data_tiket.php?all=1" class="btn-reset">Semua</a>
                    <a href="data_tiket.php" class="btn-reset">✕ Reset</a>
                </div>
            </div>
            <div class="preset-row">
                <span style="font-size:10px;color:#475569;font-weight:700;align-self:center;text-transform:uppercase;letter-spacing:.5px;">Preset:</span>
                <button type="button" class="pbtn" onclick="setPreset('today')">Hari Ini</button>
                <button type="button" class="pbtn" onclick="setPreset('week')">7 Hari</button>
                <button type="button" class="pbtn" onclick="setPreset('month')">Bulan Ini</button>
                <button type="button" class="pbtn" onclick="setPreset('all')">Semua Data</button>
            </div>
        </form>
    </div>

    <!-- TABEL -->
    <div class="tbl-card">
        <div class="tbl-header">
            <div>
                <div class="tbl-title">
                    📋 Daftar Tiket
                    <span class="cnt-badge"><?= $jumlah ?> data</span>
                </div>
                <div class="tbl-sub">Klik header kolom untuk sort &nbsp;·&nbsp; Klik Detail untuk info lengkap</div>
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
                <span style="font-size:11px;color:#475569;">Tampilkan:</span>
                <select id="perPageSel" class="fselect" style="width:auto;padding:6px 10px;font-size:12px;"
                    onchange="changePerPage(this.value)">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="all">Semua</option>
                </select>
            </div>
        </div>

        <div class="tbl-wrap">
        <table id="mainTable">
            <thead>
                <tr>
                    <th onclick="sortTable(0)">No <span class="si">↕</span></th>
                    <th onclick="sortTable(1)">Kode <span class="si">↕</span></th>
                    <th onclick="sortTable(2)">Nama User <span class="si">↕</span></th>
                    <th onclick="sortTable(3)">Rute <span class="si">↕</span></th>
                    <th onclick="sortTable(4)">Tanggal <span class="si">↕</span></th>
                    <th>Jam</th>
                    <th onclick="sortTable(6)">Layanan <span class="si">↕</span></th>
                    <th>Jenis</th>
                    <th style="text-align:center;" onclick="sortTable(8)">Pax <span class="si">↕</span></th>
                    <th onclick="sortTable(9)">Total Harga <span class="si">↕</span></th>
                    <th onclick="sortTable(10)">Status Tiket <span class="si">↕</span></th>
                    <th onclick="sortTable(11)">Status Pembayaran <span class="si">↕</span></th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody id="tableBody">
            <?php if (empty($rows)): ?>
                <tr><td colspan="13">
                    <div class="no-data">
                        <div class="icon">🎫</div>
                        <?php if ($total_all_db > 0): ?>
                            Tidak ada data sesuai filter.
                            <a href="data_tiket.php?all=1" style="color:#38bdf8;">Tampilkan semua</a>
                        <?php else: ?>
                            Belum ada tiket di database.
                        <?php endif; ?>
                    </div>
                </td></tr>
            <?php else: ?>
                <?php foreach ($rows as $i => $d):
                    $isDig      = strtoupper(trim($d['status'])) === 'DIGUNAKAN';
                    $harga_fmt  = 'Rp '.number_format((int)$d['total_harga'],0,',','.');
                    $lay_lower  = strtolower($d['layanan'] ?? '');
                    $lay_cap    = ucfirst($lay_lower);
                    $isExpress  = strpos($lay_lower,'express') !== false;
                    $jenis_val  = strtolower($d['jenis_pengguna'] ?? '');
                    $tgl_fmt    = tglPendek($d['tanggal'] ?? '', $bulan);
                    $gol_label  = $d['golongan'] ? golLabel($d['golongan']) : '—';
                    $rute_plain = htmlspecialchars($d['asal']).' → '.htmlspecialchars($d['tujuan']);
                ?>
                <tr class="data-row">
                    <td style="color:#475569;font-size:12px;"><?= $i+1 ?></td>
                    <td><span class="kode-cell"><?= htmlspecialchars($d['kode_booking']) ?></span></td>
                    <td>
                        <div style="font-weight:600;color:white;font-size:13px;"><?= htmlspecialchars($d['nama_user']) ?></div>
                        <div style="font-size:11px;color:#475569;"><?= htmlspecialchars($d['email']) ?></div>
                    </td>
                    <td style="font-size:12px;color:#94a3b8;">
                        <?= htmlspecialchars($d['asal']) ?>
                        <span class="rute-arr">→</span>
                        <?= htmlspecialchars($d['tujuan']) ?>
                    </td>
                    <td style="color:#94a3b8;font-size:12px;white-space:nowrap;"><?= $tgl_fmt ?></td>
                    <td style="color:#64748b;font-size:12px;"><?= htmlspecialchars($d['jam'] ?: '—') ?></td>
                    <td>
                        <?php if($isExpress): ?>
                            <span class="badge-exp">⚡ Express</span>
                        <?php else: ?>
                            <span class="badge-lay">🪑 Reguler</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($jenis_val === 'kendaraan'): ?>
                            <span class="badge-kend">🚗 Kendaraan</span>
                        <?php else: ?>
                            <span class="badge-walk">🚶 Jalan Kaki</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;">
                        <span class="pax-cell">👥 <?= (int)$d['total_penumpang'] ?></span>
                    </td>
                    <td class="harga-cell"><?= $harga_fmt ?></td>
                    <td>
                        <?= $isDig
                            ? '<span class="badge-used">✅ Digunakan</span>'
                            : '<span class="badge-new">🟢 Aktif</span>'
                        ?>
                    </td>
                    <td><?= paymentStatusBadgeHtml($d['payment_status'] ?? 'pending') ?></td>
                    <td>
                        <button class="btn-detail" onclick='bukaDetail(<?= json_encode([
                            "kode"      => $d["kode_booking"],
                            "nama"      => $d["nama_user"],
                            "email"     => $d["email"],
                            "rute"      => $rute_plain,
                            "tanggal"   => $tgl_fmt,
                            "jam"       => $d["jam"] ?: "—",
                            "layanan"   => $lay_cap,
                            "jenis"     => $jenis_val === "kendaraan" ? "Berkendara" : "Pejalan Kaki",
                            "golongan"  => $gol_label,
                            "plat"      => $d["plat"] ?: "—",
                            "penumpang" => (int)$d["total_penumpang"],
                            "harga"     => $harga_fmt,
                            "status"    => $isDig ? "Sudah Digunakan" : "Belum Digunakan",
                            "payment"   => getPaymentStatusMeta($d["payment_status"] ?? "pending")["label"],
                            "ok"        => $isDig,
                        ], JSON_UNESCAPED_UNICODE) ?>)'>Detail</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
        </div>

        <!-- PAGINATION -->
        <div class="pagi-wrap">
            <div class="pagi-info" id="pagiInfo">—</div>
            <div class="pagi-btns" id="pagiBtns"></div>
        </div>
    </div>

</div><!-- /main-wrap -->

<!-- ══ MODAL DETAIL ══ -->
<div class="modal-overlay" id="modalDetail" onclick="if(event.target===this)tutupDetail()">
<div class="modal-box">
    <div class="modal-head">
        <h3>🎫 Detail Tiket</h3>
        <button class="mbtn-close" onclick="tutupDetail()">✕</button>
    </div>
    <div class="modal-body" id="modalBody"></div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ════ STATE ════
const allRows   = Array.from(document.querySelectorAll('.data-row'));
let perPage     = 25;
let currentPage = 1;
let sortCol     = -1;
let sortDir     = 1;

// ════ PAGINATION ════
function renderPage() {
    const total = allRows.length;
    const pg    = perPage >= 999999 ? total : perPage;
    const start = (currentPage - 1) * pg;
    const end   = Math.min(start + pg, total);

    allRows.forEach((r, i) => {
        r.style.display = (i >= start && i < end) ? '' : 'none';
    });

    const info = document.getElementById('pagiInfo');
    if (info) {
        info.textContent = total === 0
            ? 'Tidak ada data'
            : `Menampilkan ${start + 1}–${end} dari ${total} data`;
    }

    const btns = document.getElementById('pagiBtns');
    if (!btns) return;
    btns.innerHTML = '';
    if (perPage >= 999999 || total === 0) return;

    const totalPages = Math.ceil(total / pg);
    const mkBtn = (label, page, disabled, active) => {
        const b = document.createElement('button');
        b.className   = 'pbtn' + (active ? ' active' : '');
        b.textContent = label;
        b.disabled    = disabled;
        b.onclick     = () => { currentPage = page; renderPage(); };
        btns.appendChild(b);
    };

    mkBtn('‹', currentPage - 1, currentPage === 1, false);
    let sP = Math.max(1, currentPage - 2);
    let eP = Math.min(totalPages, sP + 4);
    if (eP - sP < 4) sP = Math.max(1, eP - 4);
    for (let p = sP; p <= eP; p++) mkBtn(p, p, false, p === currentPage);
    mkBtn('›', currentPage + 1, currentPage >= totalPages, false);
}

function changePerPage(val) {
    perPage     = val === 'all' ? 999999 : parseInt(val);
    currentPage = 1;
    renderPage();
}

// ════ SORT ════
function sortTable(col) {
    const tbody = document.getElementById('tableBody');
    const rows  = Array.from(tbody.querySelectorAll('.data-row'));
    sortDir = (sortCol === col) ? sortDir * -1 : 1;
    sortCol = col;

    document.querySelectorAll('thead th').forEach((th, i) => {
        th.classList.toggle('sorted', i === col);
        const ic = th.querySelector('.si');
        if (ic) ic.textContent = (i === col) ? (sortDir === 1 ? '↑' : '↓') : '↕';
    });

    rows.sort((a, b) => {
        const va = a.querySelectorAll('td')[col]?.textContent.trim() ?? '';
        const vb = b.querySelectorAll('td')[col]?.textContent.trim() ?? '';
        const na = parseFloat(va.replace(/[^0-9]/g, ''));
        const nb = parseFloat(vb.replace(/[^0-9]/g, ''));
        if (!isNaN(na) && !isNaN(nb)) return (na - nb) * sortDir;
        return va.localeCompare(vb, 'id') * sortDir;
    });

    rows.forEach((r, i) => {
        tbody.appendChild(r);
        const td = r.querySelector('td');
        if (td) td.textContent = i + 1;
    });

    currentPage = 1;
    renderPage();
}

// ════ MODAL DETAIL ════
function bukaDetail(d) {
    const sc = d.ok ? '#4ade80' : '#38bdf8';
    const sb = d.ok ? 'rgba(74,222,128,.08)' : 'rgba(56,189,248,.08)';
    const se = d.ok ? 'rgba(74,222,128,.2)'  : 'rgba(56,189,248,.2)';
    const dr = (l, v) => `<div class="drow"><span class="dlbl">${l}</span><span class="dval">${v}</span></div>`;

    let html = `
        <div class="mkode">
            <div class="mkode-big">${d.kode}</div>
            <div class="mkode-sub">Kode Booking Tiket</div>
        </div>
        ${dr('👤 Nama', d.nama)}
        ${dr('📧 Email', d.email)}
        ${dr('🗺️ Rute', d.rute)}
        ${dr('📅 Tanggal', d.tanggal)}
        ${dr('⏰ Jam', d.jam)}
        ${dr('🚢 Layanan', d.layanan)}
        ${dr('👣 Jenis', d.jenis)}`;

    if (d.jenis === 'Berkendara') {
        html += dr('🚗 Golongan', d.golongan);
        if (d.plat !== '—') html += dr('🔢 Plat', d.plat);
    }

    html += `
        ${dr('👥 Penumpang', d.penumpang + ' orang')}
        ${dr('💰 Total Harga', `<span style="color:#38bdf8;font-weight:700;">${d.harga}</span>`)}
        <div class="drow">
            <span class="dlbl">🔖 Status Tiket</span>
            <span style="background:${sb};border:1px solid ${se};color:${sc};border-radius:20px;padding:3px 14px;font-size:12px;font-weight:700;">${d.status}</span>
        </div>
        ${dr('💳 Status Pembayaran', d.payment || '—')}`;

    document.getElementById('modalBody').innerHTML = html;
    document.getElementById('modalDetail').classList.add('open');
}

function tutupDetail() {
    document.getElementById('modalDetail').classList.remove('open');
}

// ════ PRESET TANGGAL ════
function setPreset(tipe) {
    const today = new Date();
    const fmt   = d => d.toISOString().split('T')[0];
    const form  = document.getElementById('formFilter');

    if (tipe === 'all') { window.location.href = 'data_tiket.php?all=1'; return; }

    const fFrom = form.querySelector('input[name="from"]');
    const fTo   = form.querySelector('input[name="to"]');

    if (tipe === 'today') {
        fFrom.value = fmt(today); fTo.value = fmt(today);
    } else if (tipe === 'week') {
        const w = new Date(today); w.setDate(w.getDate() - 6);
        fFrom.value = fmt(w); fTo.value = fmt(today);
    } else if (tipe === 'month') {
        fFrom.value = fmt(new Date(today.getFullYear(), today.getMonth(), 1));
        fTo.value   = fmt(today);
    }
    form.submit();
}

// ════ INIT ════
renderPage();

// Close modal dengan ESC
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') tutupDetail();
});
</script>
<script src="../assets/js/mobile-nav.js"></script>
</body>
</html>