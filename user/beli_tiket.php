<?php
// ═══════════════════════════════════════════════════════════════
// beli_tiket.php — Halaman Pemesanan Tiket Kapal
// Cek sesi: jika belum login, redirect ke halaman login
// ═══════════════════════════════════════════════════════════════
session_start();
include('../config/koneksi.php');
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Beli Tiket - Navira</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="../assets/css/navy-theme.css" rel="stylesheet">
<style>
/* ── RESET DASAR ── */
*{margin:0;padding:0;box-sizing:border-box}

/* ── BODY: background animasi biru gelap ── */
body{font-family:'Poppins',sans-serif;color:white;min-height:100vh;overflow-x:hidden}

/* ── BUBBLE ANIMASI LATAR ── */
.bubble{position:fixed;bottom:-80px;border-radius:50%;background:rgba(255,255,255,0.05);animation:bup 12s infinite;pointer-events:none;z-index:0}
@keyframes bup{0%{transform:translateY(0) scale(1);opacity:.5}100%{transform:translateY(-110vh) scale(1.8);opacity:0}}

/* ── TOPBAR ── */
.topbar{background:var(--navy-topbar);backdrop-filter:blur(14px);border-bottom:1px solid rgba(255,255,255,.07);padding:14px 28px;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:300}
.brand-logo{height:40px;width:auto;object-fit:contain;display:block}
.brand{text-decoration:none;display:inline-flex;align-items:center;gap:10px}
.brand-name{font-size:22px;font-weight:700;color:white;letter-spacing:1.5px}
.btn-back{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:rgba(255,255,255,.75);border-radius:10px;padding:8px 18px;font-size:13px;text-decoration:none;font-family:'Poppins',sans-serif;transition:.2s}
.btn-back:hover{background:rgba(255,255,255,.12);color:white}

/* ── HERO SECTION ── */
.hero{text-align:center;padding:48px 20px 80px;position:relative;z-index:1}
.hero h2{font-weight:700;font-size:28px;background:linear-gradient(to right,#38bdf8,#22d3ee);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.hero p{color:#64748b;font-size:14px;margin-top:6px}

/* ── KARTU FORM UTAMA ── */
.card-main{background:rgba(255,255,255,.04);backdrop-filter:blur(20px);border-radius:24px;padding:32px;margin-top:-52px;border:1px solid rgba(255,255,255,.09);box-shadow:0 0 60px rgba(0,0,0,.6);position:relative;z-index:1}

/* ── LABEL FORM ── */
.flabel{font-size:11px;color:#64748b;margin-bottom:6px;display:block;text-transform:uppercase;letter-spacing:.8px;font-weight:600}

/* ── INPUT & SELECT ── */
.form-control,.form-select{background:rgba(255,255,255,.06)!important;border:1px solid rgba(255,255,255,.1)!important;color:white!important;border-radius:12px!important;padding:11px 14px!important;font-family:'Poppins',sans-serif;font-size:14px;transition:.3s}
.form-control:focus,.form-select:focus{border-color:#38bdf8!important;box-shadow:0 0 0 3px rgba(56,189,248,.15)!important;outline:none}
.form-select option{background:#0f172a;color:white}
.form-select option:disabled{color:#475569!important;background:#1e293b!important}
.form-control::placeholder{color:rgba(255,255,255,.25)}
.form-control:disabled,.form-select:disabled{opacity:.4!important;cursor:not-allowed}
input[type="date"]::-webkit-calendar-picker-indicator{filter:invert(1);opacity:.4}

/* ── SELECT TUJUAN TERKUNCI ── */
.form-select.locked{opacity:.85!important;cursor:not-allowed;border-color:rgba(56,189,248,.35)!important;background:rgba(56,189,248,.07)!important}

/* ── BADGE OTOMATIS ── */
.locked-badge{display:none;font-size:10px;color:#38bdf8;background:rgba(56,189,248,.1);border:1px solid rgba(56,189,248,.2);border-radius:6px;padding:2px 8px;margin-left:6px}
.locked-badge.show{display:inline-block}

/* ── TOMBOL SWAP ── */
.swap-btn{background:rgba(56,189,248,.1);border:1px solid rgba(56,189,248,.2);border-radius:50%;width:36px;height:36px;color:#38bdf8;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:.3s;align-self:flex-end;margin-bottom:2px}
.swap-btn:hover:not(:disabled){background:rgba(56,189,248,.25);transform:rotate(180deg)}
.swap-btn:disabled{opacity:.25;cursor:not-allowed}

/* ── TOMBOL PICKER GOLONGAN ── */
.picker-btn{background:rgba(255,255,255,.06)!important;border:1px solid rgba(255,255,255,.1)!important;border-radius:12px!important;padding:11px 14px!important;color:white!important;font-family:'Poppins',sans-serif;font-size:14px;width:100%;cursor:pointer;text-align:left;display:flex;align-items:center;justify-content:space-between;transition:.3s}
.picker-btn:hover{border-color:#38bdf8!important}
.picker-btn.filled{border-color:rgba(56,189,248,.4)!important;background:rgba(56,189,248,.07)!important}

/* ── PREVIEW RUTE ── */
.rute-prev{background:rgba(56,189,248,.05);border:1px solid rgba(56,189,248,.15);border-radius:14px;padding:16px 20px;display:none;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:4px}
.rute-prev.show{display:flex}
.rp-port{text-align:center}
.rp-port .kota{font-size:14px;font-weight:700;color:white}
.rp-port .lok{font-size:11px;color:#64748b;margin-top:2px}
.rp-arrow{font-size:22px;color:#38bdf8;flex-shrink:0}
.rp-tag{background:rgba(56,189,248,.12);border:1px solid rgba(56,189,248,.2);border-radius:20px;padding:3px 12px;font-size:11px;color:#38bdf8;font-weight:600;margin-left:auto}

/* ── ALERT JAM ── */
.jam-alert{display:none;align-items:center;gap:10px;background:rgba(251,191,36,.07);border:1px solid rgba(251,191,36,.25);border-radius:10px;padding:10px 14px;margin-top:8px;font-size:12px;color:#fbbf24}
.jam-alert.show{display:flex}
.jam-alert.err{background:rgba(239,68,68,.07);border-color:rgba(239,68,68,.25);color:#f87171}
.jam-alert svg{flex-shrink:0;width:16px;height:16px}

/* ── INFO BATAS KAPASITAS ── */
.batas-info{font-size:11px;color:#64748b;margin-top:6px;padding:8px 12px;background:rgba(255,255,255,.03);border-radius:8px;border:1px solid rgba(255,255,255,.06)}
.batas-info.warn{color:#fbbf24;border-color:rgba(251,191,36,.2);background:rgba(251,191,36,.05)}

/* ════════════════════════════════════════════════════════════════
   GRID PENUMPANG
   - Desktop: 3 kolom
   - Tablet : 2 kolom
   - Mobile : 1 kolom
   ════════════════════════════════════════════════════════════════ */
.pax-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:8px}

/* ── KARTU TIAP TIPE PENUMPANG ── */
.pax-item{
    background:rgba(255,255,255,.04);
    border:1px solid rgba(255,255,255,.08);
    border-radius:12px;padding:12px 10px;
    text-align:center;transition:.3s;
}

/* ── STATE TERKUNCI: penampilan abu-abu, tidak bisa diklik ── */
.pax-item.locked-pax{
    opacity:.4;           /* redup */
    pointer-events:none;  /* semua interaksi diblokir */
    cursor:not-allowed;
}
.pax-item.locked-pax .p-lbl{color:#334155}

/* ── LABEL & KETERANGAN PENUMPANG ── */
.pax-item .p-lbl{font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px}
.pax-item .p-ket{font-size:10px;color:#334155;margin-bottom:10px;line-height:1.5}

/* ════════════════════════════════════════════════════════════════
   BADGE SYARAT PENDAMPING
   ════════════════════════════════════════════════════════════════ */
.dep-badge{
    display:inline-block;
    margin-top:4px;
    font-size:9px;font-weight:700;
    color:#f59e0b;
    background:rgba(245,158,11,.1);
    border:1px solid rgba(245,158,11,.25);
    border-radius:20px;padding:2px 7px;
    letter-spacing:.3px;
}
.dep-badge.ok{
    color:#4ade80;
    background:rgba(74,222,128,.1);
    border-color:rgba(74,222,128,.25);
}
.dep-badge.hidden-badge{display:none}

/* ── COUNTER − ANGKA + ── */
.pax-ctr{display:flex;align-items:center;justify-content:center;gap:8px}
.pax-ctr button{
    background:rgba(255,255,255,.08);
    border:1px solid rgba(255,255,255,.12);
    color:white;width:28px;height:28px;
    border-radius:7px;font-size:16px;
    cursor:pointer;display:flex;align-items:center;justify-content:center;
    transition:.2s;line-height:1;
}
.pax-ctr button:hover:not(:disabled){background:rgba(56,189,248,.15);border-color:#38bdf8}
.pax-ctr button:disabled{opacity:.25;cursor:not-allowed}
.pax-ctr input{
    width:44px;text-align:center;font-weight:700;
    font-size:16px;background:transparent;
    border:none;color:white;outline:none;
    font-family:'Poppins',sans-serif;
}

/* ── TOAST MINI ── */
#miniToast{
    position:fixed;bottom:24px;left:50%;
    transform:translateX(-50%) translateY(20px);
    background:rgba(5,12,30,.97);
    border:1px solid rgba(255,255,255,.1);
    color:white;padding:11px 22px;
    border-radius:12px;font-size:13px;
    font-family:'Poppins',sans-serif;
    z-index:9999;transition:all .3s ease;
    opacity:0;pointer-events:none;
    backdrop-filter:blur(14px);
    box-shadow:0 8px 30px rgba(0,0,0,.4);
    white-space:nowrap;
}

/* ── BOX ESTIMASI HARGA ── */
.harga-box{background:rgba(56,189,248,.04);border:1px solid rgba(56,189,248,.12);border-radius:16px;padding:18px 22px;display:none}
.harga-box.show{display:block}
.h-row{display:flex;justify-content:space-between;padding:7px 0;font-size:14px}
.h-row .lbl{color:#64748b}
.h-row .val{color:white;font-weight:500}
.h-total{border-top:1px dashed rgba(56,189,248,.2);margin-top:10px;padding-top:12px}
.h-total .lbl{color:#38bdf8;font-weight:600;font-size:15px}
.h-total .val{color:#38bdf8;font-weight:700;font-size:22px}

/* ── DIVIDER ── */
.fdiv{border:none;border-top:1px solid rgba(255,255,255,.06);margin:20px 0}

/* ── TOMBOL SUBMIT ── */
.btn-submit{background:linear-gradient(135deg,#06b6d4,#3b82f6);border:none;border-radius:50px;color:white;font-family:'Poppins',sans-serif;font-weight:700;font-size:14px;letter-spacing:1px;text-transform:uppercase;padding:14px 40px;transition:.3s;cursor:pointer;width:100%}
.btn-submit:hover{transform:scale(1.02);box-shadow:0 0 30px rgba(56,189,248,.4)}
.btn-submit:disabled{opacity:.4;cursor:not-allowed;transform:none;box-shadow:none}

/* ── MODAL ── */
.modal-content{background:rgba(5,12,30,.98)!important;border:1px solid rgba(255,255,255,.1)!important;border-radius:22px!important;backdrop-filter:blur(20px);color:white}
.mclosebtn{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);border-radius:8px;width:30px;height:30px;color:#94a3b8;cursor:pointer;font-size:15px;display:flex;align-items:center;justify-content:center;flex-shrink:0}

/* ── GRID GOLONGAN ── */
.gol-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;max-height:340px;overflow-y:auto;padding-right:4px}
.gol-grid::-webkit-scrollbar{width:4px}
.gol-grid::-webkit-scrollbar-thumb{background:rgba(56,189,248,.3);border-radius:4px}
.gcard{background:rgba(255,255,255,.04);border:2px solid rgba(255,255,255,.07);border-radius:12px;padding:12px 10px;cursor:pointer;transition:.2s;position:relative}
.gcard:hover{border-color:rgba(56,189,248,.4);background:rgba(56,189,248,.05)}
.gcard.selected{border-color:#38bdf8;background:rgba(56,189,248,.12)}
.gcard input[type=radio]{position:absolute;opacity:0;width:0;height:0}
.gbadge{font-size:9px;font-weight:700;color:#38bdf8;text-transform:uppercase;margin-bottom:3px}
.gname{font-size:12px;font-weight:600;color:white;margin-bottom:4px}
.gdesc{font-size:10px;color:#64748b;line-height:1.4}
.gmax{font-size:10px;color:#38bdf8;margin-top:4px;font-weight:600}
.gcheck{position:absolute;top:7px;right:7px;width:15px;height:15px;border-radius:50%;background:#38bdf8;display:none;align-items:center;justify-content:center;font-size:9px}
.gcard.selected .gcheck{display:flex}

/* ── SYARAT ── */
.syarat-body{max-height:270px;overflow-y:auto;font-size:13px;color:#94a3b8;line-height:1.8;padding-right:8px}
.syarat-body::-webkit-scrollbar{width:4px}
.syarat-body::-webkit-scrollbar-thumb{background:rgba(56,189,248,.3);border-radius:4px}
.syarat-body h6{color:#38bdf8;font-size:13px;font-weight:700;margin-top:16px;margin-bottom:6px}
.syarat-body ul{padding-left:18px}
.syarat-body ul li{margin-bottom:5px}
.scroll-note{font-size:11px;color:#64748b;text-align:center;padding:7px;border-top:1px solid rgba(255,255,255,.06);margin-top:6px}
.syarat-check{background:rgba(56,189,248,.06);border:1px solid rgba(56,189,248,.15);border-radius:12px;padding:14px 16px;display:flex;align-items:flex-start;gap:12px;cursor:pointer;margin-top:14px}
.syarat-check input[type=checkbox]{width:18px;height:18px;accent-color:#38bdf8;flex-shrink:0;margin-top:2px}
.syarat-check span{font-size:13px;color:#94a3b8;line-height:1.6}
.syarat-check span b{color:white}
.btnm{background:linear-gradient(135deg,#06b6d4,#3b82f6);border:none;border-radius:12px;color:white;font-family:'Poppins',sans-serif;font-weight:700;font-size:14px;padding:13px;width:100%;cursor:pointer;transition:.3s;margin-top:14px}
.btnm:hover{opacity:.9}
.btnm:disabled{opacity:.35;cursor:not-allowed}
.btnm-sec{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);border-radius:12px;color:rgba(255,255,255,.6);font-family:'Poppins',sans-serif;font-weight:600;font-size:14px;padding:13px;cursor:pointer;transition:.2s}
.btnm-sec:hover{background:rgba(255,255,255,.12);color:white}

/* ── RESPONSIVE ── */
@media(max-width:768px){
    .card-main{padding:20px}
    .gol-grid{grid-template-columns:repeat(2,1fr)}
    .pax-grid{grid-template-columns:1fr 1fr}
    .topbar{padding:12px 16px}
    .brand-name{font-size:18px}
    .brand-logo{height:34px}
}
@media(max-width:480px){
    .gol-grid{grid-template-columns:1fr}
    .pax-grid{grid-template-columns:1fr}
}
</style>
</head>
<body class="bg-navy-animated">

<!-- TOAST: notifikasi kecil di bagian bawah layar -->
<div id="miniToast"></div>

<!-- BUBBLE ANIMASI LATAR (dibuat via PHP loop) -->
<?php for ($i = 0; $i < 6; $i++): ?>
<div class="bubble" style="left:<?= $i*17+4 ?>%;width:<?= 14+$i*4 ?>px;height:<?= 14+$i*4 ?>px;animation-delay:<?= $i*2 ?>s;animation-duration:<?= 11+$i*2 ?>s;"></div>
<?php endfor; ?>

<!-- TOPBAR NAVIGASI -->
<div class="topbar">
    <a href="../index.php" class="brand">
        <img src="../assets/logo.png" alt="Logo" class="brand-logo">
        <span class="brand-name">NAVIRA</span>
    </a>
    <a href="dashboard.php" class="btn-back">← Dashboard</a>
</div>

<!-- HERO JUDUL -->
<div class="hero">
    <h2>🌊 Pemesanan Tiket Kapal</h2>
    <p>Pilih pelabuhan asal — tujuan otomatis terpilih</p>
</div>

<!-- KONTEN UTAMA -->
<div class="container pb-5" style="position:relative;z-index:1;">
<div class="card-main">

<!-- FORM: validasi JS sebelum submit, action ke simpan_tiket.php -->
<form id="formBeli" action="simpan_tiket.php" method="POST" onsubmit="return bukaModalSyarat(event)">

    <!-- ═══ HIDDEN FIELDS ═══ -->
    <input type="hidden" name="asal_id"            id="f_asal_id">
    <input type="hidden" name="tujuan_id"          id="f_tujuan_id">
    <input type="hidden" name="asal_nama"          id="f_asal_nama">
    <input type="hidden" name="tujuan_nama"        id="f_tujuan_nama">
    <input type="hidden" name="dewasa"             id="f_dewasa"      value="0">
    <input type="hidden" name="anak"               id="f_anak"        value="0">
    <input type="hidden" name="bayi"               id="f_bayi"        value="0">
    <input type="hidden" name="total_penumpang"    id="f_total_pax"   value="0">
    <input type="hidden" name="total_harga"        id="f_total_harga" value="0">
    <input type="hidden" name="golongan_kendaraan" id="f_golongan">

    <!-- ═══ BARIS 1: RUTE & LAYANAN ═══ -->
    <div class="row g-3 mb-3">
        <!-- Pelabuhan Asal -->
        <div class="col-md-5">
            <label class="flabel">Pelabuhan Asal</label>
            <div class="d-flex gap-2 align-items-end">
                <select id="selAsal" class="form-select flex-grow-1" required onchange="onAsalChange()">
                    <option value="">⚓ Pilih Pelabuhan Asal</option>
                </select>
                <button type="button" class="swap-btn" id="swapBtn" onclick="doSwap()" disabled>⇅</button>
            </div>
        </div>

        <!-- Pelabuhan Tujuan (otomatis) -->
        <div class="col-md-5">
            <label class="flabel">
                Pelabuhan Tujuan
                <span class="locked-badge" id="lockedBadge">🔒 Otomatis</span>
            </label>
            <select id="selTujuan" class="form-select locked" disabled>
                <option value="">— Pilih asal dulu —</option>
            </select>
        </div>

        <!-- Layanan -->
        <div class="col-md-2">
            <label class="flabel">Layanan</label>
            <select name="layanan" id="selLayanan" class="form-select" onchange="onLayananChange()">
                <option value="">— Pilih —</option>
                <option value="reguler">🪑 Reguler</option>
                <option value="express">⚡ Express</option>
            </select>
        </div>
    </div>

    <!-- PREVIEW RUTE -->
    <div class="rute-prev mb-3" id="rutePrev">
        <div class="rp-port">
            <div class="kota" id="pAsal">—</div>
            <div class="lok"  id="pAsalLok"></div>
        </div>
        <div class="rp-arrow">→</div>
        <div class="rp-port">
            <div class="kota" id="pTujuan">—</div>
            <div class="lok"  id="pTujuanLok"></div>
        </div>
        <span class="rp-tag" id="pLayanan">—</span>
    </div>

    <!-- ═══ BARIS 2: TANGGAL, JAM, JENIS ═══ -->
    <div class="row g-3 mb-1">
        <!-- Tanggal: min = hari ini -->
        <div class="col-md-4">
            <label class="flabel">Tanggal Keberangkatan</label>
            <input type="date" name="tanggal" id="inpTanggal" class="form-control" required
                min="<?= date('Y-m-d') ?>" onchange="onTanggalChange()">
        </div>

        <!-- Jam: diisi otomatis setelah tanggal dipilih -->
        <div class="col-md-3">
            <label class="flabel">Jam Check-In</label>
            <select name="jam" id="selJam" class="form-select" required onchange="onJamChange()">
                <option value="">— Pilih Tanggal Dulu —</option>
            </select>
            <!-- Alert peringatan jika jam sudah lewat -->
            <div class="jam-alert" id="jamAlert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <span id="jamAlertText"></span>
            </div>
        </div>

        <!-- Jenis Pengguna: KUNCI UTAMA logika anak/bayi -->
        <div class="col-md-5">
            <label class="flabel">Jenis Pengguna Jasa</label>
            <select name="jenis_pengguna" id="selJenis" class="form-select" onchange="onJenisChange()">
                <option value="">— Pilih Jenis —</option>
                <option value="penumpang">🚶 Pejalan Kaki</option>
                <option value="kendaraan">🚗 Berkendara</option>
            </select>
        </div>
    </div>

    <!-- ═══ GOLONGAN & PLAT (hanya muncul jika berkendara) ═══ -->
    <div class="row g-3 mb-3" id="boxKendaraan" style="display:none;">
        <div class="col-md-5">
            <label class="flabel">Golongan Kendaraan</label>
            <button type="button" class="picker-btn" id="btnGol"
                data-bs-toggle="modal" data-bs-target="#modalGol">
                <span>🚗 <span id="lblGol">Pilih Golongan</span></span>
                <span style="font-size:11px;opacity:.5;">▼</span>
            </button>
        </div>
        <!-- ═══ PLAT NOMOR: disembunyikan otomatis jika Gol I (Sepeda) ═══ -->
        <div class="col-md-4" id="boxPlat">
            <label class="flabel">Plat Nomor</label>
            <input type="text" name="plat" id="inpPlat" class="form-control"
                placeholder="B 1234 ABC" oninput="this.value=this.value.toUpperCase()">
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         GRID PENUMPANG
         ★ LOGIKA UTAMA:
           - PEJALAN KAKI : dewasa & anak bebas dipilih tanpa syarat
                            BAYI: wajib ada minimal 1 DEWASA
           - BERKENDARA   : anak & bayi hanya bisa dipilih SETELAH
                            ada minimal 1 penumpang DEWASA
         ★ Sebelum jenis dipilih: semua input disabled (abu-abu)
         ═══════════════════════════════════════════════════════════ -->
    <div class="mb-3">
        <label class="flabel">
            Penumpang
            <span id="batasLabel" style="font-size:10px;color:#38bdf8;margin-left:8px;"></span>
        </label>

        <!-- Info sebelum jenis dipilih -->
        <div id="infoPilihJenis" style="font-size:12px;color:#64748b;margin-bottom:8px;display:none;">
            ℹ️ Pilih <b>Jenis Pengguna Jasa</b> di atas untuk mengisi penumpang.
        </div>

        <div class="pax-grid">

            <!-- ── DEWASA: selalu bisa dipilih setelah jenis dipilih ── -->
            <div class="pax-item" id="item_dewasa">
                <div class="p-lbl">Dewasa</div>
                <div class="p-ket">≥ 17 tahun</div>
                <div class="pax-ctr">
                    <button type="button" id="btn_m_dewasa" onclick="paxKurang('dewasa')" disabled>−</button>
                    <input type="number" id="inp_dewasa" value="0" min="0" max="99"
                        onchange="paxManual('dewasa',this.value)"
                        oninput="paxManual('dewasa',this.value)" disabled>
                    <button type="button" id="btn_p_dewasa" onclick="paxTambah('dewasa')" disabled>+</button>
                </div>
            </div>

            <!-- ── ANAK:
                 Pejalan kaki  → langsung bisa dipilih setelah layanan dipilih
                 Berkendara    → butuh minimal 1 dewasa dulu                    -->
            <div class="pax-item" id="item_anak">
                <div class="p-lbl">Anak</div>
                <div class="p-ket">
                    3 – 16 tahun<br>
                    <!--
                        Badge HANYA muncul di mode BERKENDARA.
                        Di mode pejalan kaki, badge ini disembunyikan.
                    -->
                    <span class="dep-badge hidden-badge" id="anakBadge">⚠ Perlu 1 dewasa</span>
                </div>
                <div class="pax-ctr">
                    <button type="button" id="btn_m_anak" onclick="paxKurang('anak')" disabled>−</button>
                    <input type="number" id="inp_anak" value="0" min="0" max="99"
                        onchange="paxManual('anak',this.value)"
                        oninput="paxManual('anak',this.value)" disabled>
                    <button type="button" id="btn_p_anak" onclick="paxTambah('anak')" disabled>+</button>
                </div>
            </div>

            <!-- ── BAYI:
                 Pejalan kaki  → wajib ada minimal 1 DEWASA dulu
                 Berkendara    → wajib ada minimal 1 DEWASA dulu             -->
            <div class="pax-item" id="item_bayi">
                <div class="p-lbl">Bayi</div>
                <div class="p-ket">
                    &lt; 3 tahun (Gratis)<br>
                    <!--
                        Badge SELALU muncul setelah jenis dipilih
                        (baik pejalan kaki maupun berkendara),
                        karena bayi selalu butuh pendamping dewasa.
                    -->
                    <span class="dep-badge hidden-badge" id="bayiBadge">⚠ Perlu 1 dewasa</span>
                </div>
                <div class="pax-ctr">
                    <button type="button" id="btn_m_bayi" onclick="paxKurang('bayi')" disabled>−</button>
                    <input type="number" id="inp_bayi" value="0" min="0" max="99"
                        onchange="paxManual('bayi',this.value)"
                        oninput="paxManual('bayi',this.value)" disabled>
                    <button type="button" id="btn_p_bayi" onclick="paxTambah('bayi')" disabled>+</button>
                </div>
            </div>

        </div><!-- /.pax-grid -->

        <!-- Info kapasitas kendaraan (muncul setelah golongan dipilih) -->
        <div class="batas-info" id="batasInfo" style="display:none;"></div>
    </div>

    <hr class="fdiv">

    <!-- ESTIMASI HARGA -->
    <div class="harga-box mb-4" id="hargaBox">
        <div style="font-size:11px;color:#38bdf8;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:12px;">💰 Estimasi Biaya</div>
        <div id="hargaDetail"></div>
        <div class="h-row h-total">
            <span class="lbl">Total Pembayaran</span>
            <span class="val" id="hargaTotal">Rp 0</span>
        </div>
    </div>

    <!-- TOMBOL SUBMIT -->
    <button type="submit" class="btn-submit" id="btnSubmit">🚀 Lanjut Pesan Tiket</button>

</form>
</div><!-- /.card-main -->
</div><!-- /.container -->


<!-- ═══ MODAL GOLONGAN KENDARAAN ═══ -->
<div class="modal fade" id="modalGol" tabindex="-1">
<div class="modal-dialog modal-dialog-centered modal-lg">
<div class="modal-content">
    <div class="d-flex justify-content-between align-items-start p-4 pb-0">
        <div>
            <div style="font-size:17px;font-weight:700;">🚗 Pilih Golongan Kendaraan</div>
            <div style="font-size:12px;color:#64748b;margin-top:3px;">Berdasarkan PM 66 Tahun 2019</div>
        </div>
        <button class="mclosebtn" data-bs-dismiss="modal">✕</button>
    </div>
    <div class="p-4">
        <div class="gol-grid" id="golGrid"></div>
        <button type="button" class="btnm" id="btnSimpanGol"
            data-bs-dismiss="modal" onclick="simpanGol()" disabled>
            ✅ Simpan Pilihan Kendaraan
        </button>
    </div>
</div>
</div>
</div>


<!-- ═══ MODAL SYARAT & KETENTUAN ═══ -->
<div class="modal fade" id="modalSyarat" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">
    <div class="p-4 pb-2">
        <div style="font-size:17px;font-weight:700;">📋 Syarat & Ketentuan</div>
        <div style="font-size:12px;color:#64748b;margin-top:3px;">Baca sebelum melanjutkan pemesanan</div>
    </div>
    <div class="p-4 pt-2">
        <div class="syarat-body" id="syaratBody" onscroll="cekScroll()">
            <h6>1. Pemesanan Tiket</h6>
            <ul>
                <li>Tiket yang dipesan <b>tidak dapat di-refund</b> atau dikembalikan.</li>
                <li>Tiket berlaku hanya untuk jadwal yang tercantum.</li>
                <li>Satu kode booking hanya berlaku untuk satu kali perjalanan.</li>
            </ul>
            <h6>2. Check-In Pelabuhan</h6>
            <ul>
                <li>Pejalan kaki wajib hadir <b>minimal 60 menit</b> sebelum berangkat.</li>
                <li>Pengguna kendaraan wajib hadir <b>minimal 90 menit</b> sebelumnya.</li>
                <li>QR code pada e-tiket harus dapat discan.</li>
            </ul>
            <h6>3. Penumpang & Kendaraan</h6>
            <ul>
                <li><b>Pejalan kaki:</b> anak boleh tanpa pendamping dewasa dalam satu tiket.</li>
                <li><b>Pejalan kaki:</b> bayi wajib didampingi minimal 1 penumpang dewasa.</li>
                <li><b>Berkendara:</b> anak & bayi wajib didampingi minimal 1 penumpang dewasa.</li>
                <li>Jumlah penumpang tidak boleh melebihi kapasitas golongan kendaraan.</li>
                <li>Motor (Gol II & III): maks 2 dewasa + 2 anak.</li>
                <li>Golongan kendaraan harus sesuai dengan kendaraan yang dibawa.</li>
                <li>Sepeda (Gol I): tidak perlu mencantumkan plat nomor.</li>
            </ul>
            <h6>4. Keselamatan</h6>
            <ul>
                <li>Dilarang membawa barang berbahaya atau terlarang.</li>
                <li>Ikuti seluruh instruksi petugas pelabuhan dan awak kapal.</li>
            </ul>
            <h6>5. Force Majeure</h6>
            <ul>
                <li>Jadwal dapat berubah akibat cuaca buruk atau darurat.</li>
                <li>Pengelola tidak bertanggung jawab atas keterlambatan force majeure.</li>
            </ul>
            <div style="height:8px;"></div>
        </div>
        <div class="scroll-note" id="scrollNote">↓ Gulir ke bawah untuk membaca semua ketentuan</div>
        <div class="syarat-check" onclick="toggleSyarat()">
            <input type="checkbox" id="chkSyarat" onclick="event.stopPropagation();updateBtnSetuju()">
            <span>Saya telah membaca dan menyetujui seluruh <b>Syarat & Ketentuan</b> pemesanan tiket Navira</span>
        </div>
        <div class="d-flex gap-2 mt-3">
            <button type="button" class="btnm-sec flex-fill" data-bs-dismiss="modal" onclick="batalSyarat()">Batal</button>
            <button type="button" class="btnm flex-fill" id="btnSetuju" onclick="setujuLanjut()" disabled style="margin-top:0;">✅ Setuju & Lanjut</button>
        </div>
    </div>
</div>
</div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ════════════════════════════════════════════════════════════════════
// DATA GOLONGAN KENDARAAN (PM 66 Tahun 2019)
// motorRule = true → aturan khusus: maks 2 dewasa + 2 anak
// noPlat    = true → tidak perlu plat nomor (Sepeda Gol I)
// ════════════════════════════════════════════════════════════════════
const golData = [
    { id:'gol_1',  label:'Golongan I',    name:'Sepeda',           desc:'Sepeda kayuh, onthel',           maxTotal:1,  maxDewasa:1,  maxAnak:0,              noPlat:true  },
    { id:'gol_2',  label:'Golongan II',   name:'Motor <500cc',     desc:'Honda, Yamaha, Suzuki <500cc',   maxTotal:4,  maxDewasa:2,  maxAnak:2, motorRule:true              },
    { id:'gol_3',  label:'Golongan III',  name:'Motor >500cc',     desc:'Ducati, Harley, roda tiga',      maxTotal:4,  maxDewasa:2,  maxAnak:2, motorRule:true              },
    { id:'gol_4a', label:'Gol IVA',       name:'Mobil Penumpang',  desc:'Sedan, SUV, MPV, LCGC ≤5m',     maxTotal:7,  maxDewasa:7,  maxAnak:7                              },
    { id:'gol_4b', label:'Gol IVB',       name:'Mobil Barang ≤5m', desc:'Pick up, double cabin ≤5m',     maxTotal:3,  maxDewasa:3,  maxAnak:3                              },
    { id:'gol_5a', label:'Gol VA',        name:'Bus Sedang 5–7m',  desc:'Elf, Hiace, medium bus',        maxTotal:20, maxDewasa:20, maxAnak:20                             },
    { id:'gol_5b', label:'Gol VB',        name:'Truk Sedang 5–7m', desc:'Truk box, truk pasir 5–7m',     maxTotal:3,  maxDewasa:3,  maxAnak:3                              },
    { id:'gol_6a', label:'Gol VIA',       name:'Bus Besar 7–10m',  desc:'Bis AKAP, pariwisata 52 seat',  maxTotal:50, maxDewasa:50, maxAnak:50                             },
    { id:'gol_6b', label:'Gol VIB',       name:'Truk Besar 7–10m', desc:'Truk tangki, Fuso 7–10m',       maxTotal:3,  maxDewasa:3,  maxAnak:3                              },
    { id:'gol_7',  label:'Golongan VII',  name:'Tronton 10–12m',   desc:'Tronton, alat berat',           maxTotal:3,  maxDewasa:3,  maxAnak:3                              },
    { id:'gol_8',  label:'Golongan VIII', name:'Tronton 12–16m',   desc:'Trailer, Lowbed 12–16m',        maxTotal:3,  maxDewasa:3,  maxAnak:3                              },
    { id:'gol_9',  label:'Golongan IX',   name:'Tronton >16m',     desc:'Tangki gandeng >16m',           maxTotal:3,  maxDewasa:3,  maxAnak:3                              },
];

// ════════════════════════════════════════════════════════════════════
// STATE GLOBAL APLIKASI
// ════════════════════════════════════════════════════════════════════
let dp             = { dewasa:0, anak:0, bayi:0 };
let selectedGol    = null;
let hasilHarga     = {};
let hasilHargaKend = {};
let activeTujuan   = { id:'', nama:'', lokasi:'', label:'' };
let layananAktif   = '';

// ════════════════════════════════════════════════════════════════════
// FUNGSI: showToast(msg, tipe)
// ════════════════════════════════════════════════════════════════════
function showToast(msg, tipe) {
    const t = document.getElementById('miniToast');
    t.textContent = msg;
    t.style.borderColor = tipe === 'warn' ? 'rgba(251,191,36,.35)' :
                          tipe === 'ok'   ? 'rgba(74,222,128,.35)' :
                                            'rgba(56,189,248,.3)';
    t.style.opacity   = '1';
    t.style.transform = 'translateX(-50%) translateY(0)';
    clearTimeout(t._timer);
    t._timer = setTimeout(() => {
        t.style.opacity   = '0';
        t.style.transform = 'translateX(-50%) translateY(20px)';
    }, 2800);
}

// ════════════════════════════════════════════════════════════════════
// FUNGSI WAKTU
// ════════════════════════════════════════════════════════════════════
function getNowHM()    { const n=new Date(); return {h:n.getHours(),m:n.getMinutes()}; }
function getTodayStr() { const n=new Date(); return `${n.getFullYear()}-${String(n.getMonth()+1).padStart(2,'0')}-${String(n.getDate()).padStart(2,'0')}`; }
function isToday(d)    { return d === getTodayStr(); }

// ════════════════════════════════════════════════════════════════════
// FUNGSI: populateJam(tanggalVal)
// ════════════════════════════════════════════════════════════════════
function populateJam(tanggalVal) {
    const sel     = document.getElementById('selJam');
    const alertEl = document.getElementById('jamAlert');
    const alertTx = document.getElementById('jamAlertText');

    sel.innerHTML = '';

    if (!tanggalVal) {
        sel.innerHTML = '<option value="">— Pilih Tanggal Dulu —</option>';
        alertEl.classList.remove('show','err');
        return;
    }

    const today = isToday(tanggalVal);
    const { h: nowH, m: nowM } = getNowHM();
    let adaValid = false;

    for (let h = 0; h < 24; h++) {
        const val   = String(h).padStart(2,'0') + ':00';
        const label = String(h).padStart(2,'0') + '.00 - ' + String(h===23?0:h+1).padStart(2,'0') + '.00';
        const opt   = document.createElement('option');
        opt.value   = val;

        if (today && h < nowH) {
            opt.textContent = '🚫 ' + label + ' (Sudah lewat)';
            opt.disabled    = true;
        } else if (today && ((h*60) - (nowH*60+nowM)) <= 60) {
            opt.textContent = '⏳ ' + label + ' (Terlalu dekat)';
            opt.disabled    = true;
        } else {
            opt.textContent = label;
            adaValid = true;
        }
        sel.appendChild(opt);
    }

    const ph = new Option('— Pilih Jam Check-In —', '');
    ph.selected = true;
    sel.insertBefore(ph, sel.firstChild);

    if (!adaValid) {
        alertEl.classList.add('show','err');
        alertTx.textContent = 'Tidak ada jam tersedia hari ini. Pilih tanggal lain.';
    } else if (today) {
        alertEl.classList.add('show'); alertEl.classList.remove('err');
        alertTx.textContent = 'Slot < 1 jam dari sekarang tidak tersedia. Waktu: '
            + String(nowH).padStart(2,'0') + ':' + String(nowM).padStart(2,'0') + ' WIB';
    } else {
        alertEl.classList.remove('show','err');
    }
}

function onTanggalChange() { populateJam(document.getElementById('inpTanggal').value); }

function onJamChange() {
    const tgl = document.getElementById('inpTanggal').value;
    const jam = document.getElementById('selJam').value;
    if (!jam || !isToday(tgl)) return;
    const { h: nowH, m: nowM } = getNowHM();
    const jamH = parseInt(jam.split(':')[0], 10);
    if ((jamH*60) - (nowH*60+nowM) <= 60) {
        document.getElementById('jamAlert').classList.add('show','err');
        document.getElementById('jamAlertText').textContent = '⛔ Slot ini tidak valid. Pilih jam berikutnya.';
        document.getElementById('selJam').value = '';
    }
}

// ════════════════════════════════════════════════════════════════════
// LOAD PELABUHAN ASAL dari API
// ════════════════════════════════════════════════════════════════════
fetch('get_pelabuhan.php')
    .then(r => r.json())
    .then(data => {
        if (!data.success) return;
        const sel = document.getElementById('selAsal');
        data.asals.forEach(p => {
            const o = new Option('🚢 ' + p.label, p.id);
            o.dataset.nama   = p.nama;
            o.dataset.lokasi = p.lokasi;
            o.dataset.label  = p.label;
            sel.appendChild(o);
        });
    })
    .catch(e => console.error('get_pelabuhan:', e));

// ════════════════════════════════════════════════════════════════════
// FUNGSI: onAsalChange()
// ════════════════════════════════════════════════════════════════════
function onAsalChange() {
    const selAsal = document.getElementById('selAsal');
    const asalId  = selAsal.value;
    const asalOpt = selAsal.options[selAsal.selectedIndex];
    const selTuj  = document.getElementById('selTujuan');

    resetTujuan();
    if (!asalId) return;

    document.getElementById('f_asal_id').value   = asalId;
    document.getElementById('f_asal_nama').value = asalOpt.dataset.label || asalOpt.dataset.nama || '';
    selTuj.innerHTML = '<option value="">⏳ Mencari rute...</option>';

    fetch('get_tujuan.php?asal_id=' + asalId)
        .then(r => r.json())
        .then(data => {
            selTuj.innerHTML = '';
            if (!data.success || !data.tujuans?.length) {
                selTuj.innerHTML = '<option value="">❌ Rute belum tersedia</option>';
                return;
            }

            hasilHarga     = {};
            hasilHargaKend = {};
            Object.entries(data.harga_data      || {}).forEach(([k,v]) => hasilHarga[String(k)]     = v);
            Object.entries(data.harga_kendaraan || {}).forEach(([k,v]) => hasilHargaKend[String(k)] = v);

            data.tujuans.forEach(t => {
                const o = new Option('🚢 ' + t.label, t.id);
                o.dataset.nama   = t.nama;
                o.dataset.lokasi = t.lokasi;
                o.dataset.label  = t.label;
                selTuj.appendChild(o);
            });

            selTuj.selectedIndex = 0;
            selTuj.disabled      = true;
            document.getElementById('lockedBadge').classList.add('show');
            document.getElementById('swapBtn').disabled = false;

            syncActiveTujuan();
            tampilPreview();
            hitungHarga();
        })
        .catch(e => {
            console.error('get_tujuan:', e);
            selTuj.innerHTML = '<option value="">❌ Error koneksi</option>';
        });
}

function resetTujuan() {
    const selTuj = document.getElementById('selTujuan');
    selTuj.innerHTML = '<option value="">— Pilih asal dulu —</option>';
    selTuj.disabled  = true;
    document.getElementById('lockedBadge').classList.remove('show');
    document.getElementById('swapBtn').disabled = true;
    document.getElementById('rutePrev').classList.remove('show');
    document.getElementById('hargaBox').classList.remove('show');
    hasilHarga = {}; hasilHargaKend = {};
    activeTujuan = { id:'', nama:'', lokasi:'', label:'' };
    document.getElementById('f_tujuan_id').value   = '';
    document.getElementById('f_tujuan_nama').value = '';
}

function syncActiveTujuan() {
    const sel = document.getElementById('selTujuan');
    const opt = sel.options[sel.selectedIndex];
    if (!opt?.value) return;
    activeTujuan = { id:String(opt.value), nama:opt.dataset.nama||'', lokasi:opt.dataset.lokasi||'', label:opt.dataset.label||'' };
    document.getElementById('f_tujuan_id').value   = activeTujuan.id;
    document.getElementById('f_tujuan_nama').value = activeTujuan.label;
}

function tampilPreview() {
    const selAsal = document.getElementById('selAsal');
    const asalOpt = selAsal.options[selAsal.selectedIndex];
    if (!asalOpt?.value || !activeTujuan.id) return;
    document.getElementById('pAsal').textContent      = asalOpt.dataset.nama   || '';
    document.getElementById('pAsalLok').textContent   = asalOpt.dataset.lokasi || '';
    document.getElementById('pTujuan').textContent    = activeTujuan.nama;
    document.getElementById('pTujuanLok').textContent = activeTujuan.lokasi;
    document.getElementById('pLayanan').textContent   = layananAktif || '—';
    document.getElementById('rutePrev').classList.add('show');
}

function doSwap() {
    if (!activeTujuan.id) return;
    const selAsal = document.getElementById('selAsal');
    if (!selAsal.querySelector(`option[value="${activeTujuan.id}"]`)) {
        showToast('⚠️ Rute sebaliknya belum tersedia.', 'warn'); return;
    }
    selAsal.value = activeTujuan.id;
    onAsalChange();
}

function onLayananChange() {
    layananAktif = document.getElementById('selLayanan').value;
    document.getElementById('pLayanan').textContent = layananAktif || '—';
    updatePaxState();
    hitungHarga();
}

// ════════════════════════════════════════════════════════════════════
// FUNGSI: onJenisChange()
// DIPANGGIL SAAT JENIS PENGGUNA BERUBAH
//
// ★★ LOGIKA PENUMPANG: ★★
//
// Mode PEJALAN KAKI:
//   - Dewasa : bebas dipilih
//   - Anak   : bebas dipilih tanpa syarat dewasa
//   - Bayi   : WAJIB ada minimal 1 DEWASA terlebih dahulu
//
// Mode BERKENDARA:
//   - Dewasa : bebas dipilih
//   - Anak   : WAJIB ada minimal 1 DEWASA terlebih dahulu
//   - Bayi   : WAJIB ada minimal 1 DEWASA terlebih dahulu
// ════════════════════════════════════════════════════════════════════
function onJenisChange() {
    const jenis = document.getElementById('selJenis').value;

    const showKendaraan = (jenis === 'kendaraan');
    document.getElementById('boxKendaraan').style.display = showKendaraan ? 'flex' : 'none';

    if (!showKendaraan) {
        selectedGol = null;
        document.getElementById('f_golongan').value    = '';
        document.getElementById('lblGol').textContent  = 'Pilih Golongan';
        document.getElementById('btnGol').classList.remove('filled');
        document.querySelectorAll('.gcard').forEach(c => c.classList.remove('selected'));
        document.getElementById('btnSimpanGol').disabled = true;
        // Kembalikan tampilan plat ke default saat beralih ke pejalan kaki
        document.getElementById('boxPlat').style.display = 'block';
        document.getElementById('inpPlat').value = '';
        document.getElementById('inpPlat').setAttribute('required', '');
    }

    updatePaxState();
    updateBatasInfo();
    hitungHarga();
}

// ════════════════════════════════════════════════════════════════════
// FUNGSI INTI: updatePaxState()
// Menentukan mana yang bisa diklik berdasarkan:
//   1. Apakah layanan sudah dipilih?
//   2. Apakah jenis sudah dipilih?
//   3. Mode pejalan kaki atau berkendara?
//   4. Apakah sudah ada dewasa? (untuk anak berkendara & semua bayi)
// ════════════════════════════════════════════════════════════════════
function updatePaxState() {
    const jenis      = document.getElementById('selJenis').value;
    const adaLayanan = !!layananAktif;
    const adaJenis   = !!jenis;
    const baseOk     = adaLayanan && adaJenis;
    const isPenumpang = (jenis === 'penumpang');
    const adaDewasa  = dp.dewasa > 0;

    // ─── DEWASA: selalu aktif jika layanan & jenis ok ───
    const dewasaOk = baseOk;
    setItemEnabled('dewasa', dewasaOk);

    // ─── ANAK:
    //   Pejalan kaki  → bebas (tidak perlu dewasa)
    //   Berkendara    → butuh dewasa
    const anakOk = isPenumpang
        ? baseOk
        : baseOk && adaDewasa;
    setItemEnabled('anak', anakOk);

    // ─── BAYI:
    //   Pejalan kaki  → WAJIB ada dewasa (perubahan baru)
    //   Berkendara    → WAJIB ada dewasa
    //   → Kedua mode sama: selalu butuh dewasa
    const bayiOk = baseOk && adaDewasa;
    setItemEnabled('bayi', bayiOk);

    // ─── UPDATE BADGE SYARAT ───
    updateBadgeSyarat(jenis, adaDewasa);
}

// ════════════════════════════════════════════════════════════════════
// FUNGSI: setItemEnabled(type, enabled)
// ════════════════════════════════════════════════════════════════════
function setItemEnabled(type, enabled) {
    document.getElementById('btn_p_' + type).disabled = !enabled;
    document.getElementById('btn_m_' + type).disabled = !enabled;
    document.getElementById('inp_'   + type).disabled = !enabled;
    document.getElementById('item_' + type).classList.toggle('locked-pax', !enabled);
}

// ════════════════════════════════════════════════════════════════════
// FUNGSI: updateBadgeSyarat(jenis, adaDewasa)
// Badge anak:
//   Pejalan kaki  → disembunyikan (bebas)
//   Berkendara    → kuning/hijau tergantung ada/tidaknya dewasa
// Badge bayi:
//   Semua mode    → selalu tampil (karena bayi selalu butuh dewasa)
//   Disembunyikan hanya jika jenis belum dipilih
// ════════════════════════════════════════════════════════════════════
function updateBadgeSyarat(jenis, adaDewasa) {
    const anakBadge = document.getElementById('anakBadge');
    const bayiBadge = document.getElementById('bayiBadge');

    if (jenis === 'penumpang') {
        // ── PEJALAN KAKI ──
        // Anak: bebas, sembunyikan badge
        anakBadge.className = 'dep-badge hidden-badge';

        // Bayi: selalu butuh dewasa, tampilkan badge
        if (adaDewasa) {
            bayiBadge.textContent = '✓ Pendamping ada';
            bayiBadge.className   = 'dep-badge ok';
        } else {
            bayiBadge.textContent = '⚠ Perlu 1 dewasa';
            bayiBadge.className   = 'dep-badge';
        }

    } else if (jenis === 'kendaraan') {
        // ── BERKENDARA ──
        // Anak: butuh dewasa
        if (adaDewasa) {
            anakBadge.textContent = '✓ Pendamping ada';
            anakBadge.className   = 'dep-badge ok';
        } else {
            anakBadge.textContent = '⚠ Perlu 1 dewasa';
            anakBadge.className   = 'dep-badge';
        }
        // Bayi: butuh dewasa
        if (adaDewasa) {
            bayiBadge.textContent = '✓ Pendamping ada';
            bayiBadge.className   = 'dep-badge ok';
        } else {
            bayiBadge.textContent = '⚠ Perlu 1 dewasa';
            bayiBadge.className   = 'dep-badge';
        }

    } else {
        // ── BELUM PILIH JENIS: sembunyikan semua badge ──
        anakBadge.className = 'dep-badge hidden-badge';
        bayiBadge.className = 'dep-badge hidden-badge';
    }
}

// ════════════════════════════════════════════════════════════════════
// FUNGSI: getBatas()
// ════════════════════════════════════════════════════════════════════
function getBatas() {
    if (!selectedGol) return { maxTotal:999, maxDewasa:999, maxAnak:999, motorRule:false };
    return {
        maxTotal  : selectedGol.maxTotal   ?? 999,
        maxDewasa : selectedGol.maxDewasa  ?? 999,
        maxAnak   : selectedGol.maxAnak    ?? 999,
        motorRule : selectedGol.motorRule  ?? false,
    };
}

// ════════════════════════════════════════════════════════════════════
// FUNGSI: updateBatasInfo()
// ════════════════════════════════════════════════════════════════════
function updateBatasInfo() {
    const info  = document.getElementById('batasInfo');
    const lbl   = document.getElementById('batasLabel');
    const jenis = document.getElementById('selJenis').value;

    if (jenis !== 'kendaraan' || !selectedGol) {
        info.style.display = 'none';
        lbl.textContent    = '';
        return;
    }

    const b     = getBatas();
    const total = dp.dewasa + dp.anak + dp.bayi;
    const sisa  = b.maxTotal - total;

    info.style.display = 'block';
    lbl.textContent    = `(Maks ${b.maxTotal} orang)`;

    let teks = `${selectedGol.label} — maks ${b.maxTotal} penumpang total`;
    if (b.motorRule) teks += ' | Maks 2 dewasa, 2 anak';

    info.className   = sisa <= 0 ? 'batas-info warn' : 'batas-info';
    info.textContent = sisa <= 0
        ? '⚠️ Kapasitas penuh! ' + teks
        : `✅ Tersisa ${sisa} kursi — ` + teks;
}

// ════════════════════════════════════════════════════════════════════
// FUNGSI: paxTambah(t)
// ════════════════════════════════════════════════════════════════════
function paxTambah(t) {
    const jenis = document.getElementById('selJenis').value;

    if (!layananAktif) { showToast('⚠️ Pilih layanan terlebih dahulu!', 'warn'); return; }
    if (!jenis)         { showToast('⚠️ Pilih jenis pengguna jasa!', 'warn');    return; }

    // Validasi BERKENDARA: anak/bayi butuh dewasa
    if (jenis === 'kendaraan') {
        if ((t === 'anak' || t === 'bayi') && dp.dewasa <= 0) {
            const label = t === 'anak' ? '👦 Anak' : '👶 Bayi';
            showToast(`${label} wajib didampingi minimal 1 penumpang dewasa!`, 'warn');
            highlightCard('item_dewasa');
            return;
        }
    }

    // Validasi PEJALAN KAKI: bayi butuh dewasa
    if (jenis === 'penumpang' && t === 'bayi' && dp.dewasa <= 0) {
        showToast('👶 Bayi wajib didampingi minimal 1 penumpang dewasa!', 'warn');
        highlightCard('item_dewasa');
        return;
    }

    const b     = getBatas();
    const total = dp.dewasa + dp.anak + dp.bayi;

    if (total >= b.maxTotal) {
        showToast(`⚠️ Maks ${b.maxTotal} penumpang untuk ${selectedGol ? selectedGol.label : 'kendaraan ini'}!`, 'warn');
        return;
    }
    if (t === 'dewasa' && dp.dewasa >= b.maxDewasa) {
        showToast(`⚠️ Maks ${b.maxDewasa} dewasa!`, 'warn'); return;
    }
    if (t === 'anak' && dp.anak >= b.maxAnak) {
        showToast(`⚠️ Maks ${b.maxAnak} anak!`, 'warn'); return;
    }

    dp[t]++;
    syncPaxUI();
}

// ════════════════════════════════════════════════════════════════════
// FUNGSI: paxKurang(t)
// ★ Jika dewasa = 0:
//   - Mode BERKENDARA : anak & bayi direset
//   - Mode PEJALAN KAKI: hanya bayi direset (anak tetap)
// ════════════════════════════════════════════════════════════════════
function paxKurang(t) {
    if (dp[t] <= 0) return;
    dp[t]--;

    const jenis = document.getElementById('selJenis').value;

    if (t === 'dewasa' && dp.dewasa <= 0) {
        let reset = [];
        if (jenis === 'kendaraan') {
            // Berkendara: reset anak dan bayi
            if (dp.anak > 0) { dp.anak = 0; reset.push('anak'); }
            if (dp.bayi > 0) { dp.bayi = 0; reset.push('bayi'); }
        } else if (jenis === 'penumpang') {
            // Pejalan kaki: hanya reset bayi
            if (dp.bayi > 0) { dp.bayi = 0; reset.push('bayi'); }
        }
        if (reset.length) {
            showToast(`⚠️ ${reset.join(' & ')} direset — tidak ada pendamping dewasa.`, 'warn');
        }
    }

    syncPaxUI();
}

// ════════════════════════════════════════════════════════════════════
// FUNGSI: paxManual(t, val)
// ════════════════════════════════════════════════════════════════════
function paxManual(t, val) {
    const jenis = document.getElementById('selJenis').value;
    if (!layananAktif || !jenis) {
        document.getElementById('inp_' + t).value = 0;
        showToast('⚠️ Pilih layanan dan jenis pengguna jasa!', 'warn');
        return;
    }

    let n = parseInt(val, 10);
    if (isNaN(n) || n < 0) n = 0;

    // Validasi BERKENDARA: anak/bayi tidak boleh > 0 tanpa dewasa
    if (jenis === 'kendaraan' && (t === 'anak' || t === 'bayi') && n > 0 && dp.dewasa <= 0) {
        const label = t === 'anak' ? '👦 Anak' : '👶 Bayi';
        showToast(`${label} wajib didampingi minimal 1 penumpang dewasa!`, 'warn');
        document.getElementById('inp_' + t).value = 0;
        dp[t] = 0;
        syncPaxUI();
        return;
    }

    // Validasi PEJALAN KAKI: bayi tidak boleh > 0 tanpa dewasa
    if (jenis === 'penumpang' && t === 'bayi' && n > 0 && dp.dewasa <= 0) {
        showToast('👶 Bayi wajib didampingi minimal 1 penumpang dewasa!', 'warn');
        document.getElementById('inp_bayi').value = 0;
        dp.bayi = 0;
        syncPaxUI();
        return;
    }

    const b = getBatas();
    if (t === 'dewasa') n = Math.min(n, b.maxDewasa);
    if (t === 'anak')   n = Math.min(n, b.maxAnak);

    const totalBaru = (t==='dewasa'?n:dp.dewasa) + (t==='anak'?n:dp.anak) + (t==='bayi'?n:dp.bayi);
    if (totalBaru > b.maxTotal) {
        n -= (totalBaru - b.maxTotal);
        if (n < 0) n = 0;
        showToast(`⚠️ Total melebihi kapasitas (maks ${b.maxTotal})!`, 'warn');
    }

    dp[t] = n;

    // Reset saat dewasa di-nol-kan
    if (t === 'dewasa' && n <= 0) {
        let reset = [];
        if (jenis === 'kendaraan') {
            if (dp.anak > 0) { dp.anak = 0; reset.push('anak'); }
            if (dp.bayi > 0) { dp.bayi = 0; reset.push('bayi'); }
        } else if (jenis === 'penumpang') {
            if (dp.bayi > 0) { dp.bayi = 0; reset.push('bayi'); }
        }
        if (reset.length) showToast(`⚠️ ${reset.join(' & ')} direset — tidak ada pendamping.`, 'warn');
    }

    syncPaxUI();
}

function highlightCard(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.style.border     = '1.5px solid rgba(251,191,36,.6)';
    el.style.background = 'rgba(251,191,36,.06)';
    setTimeout(() => { el.style.border = ''; el.style.background = ''; }, 1500);
}

// ════════════════════════════════════════════════════════════════════
// FUNGSI: syncPaxUI()
// ════════════════════════════════════════════════════════════════════
function syncPaxUI() {
    ['dewasa','anak','bayi'].forEach(t => {
        document.getElementById('inp_'+t).value = dp[t];
    });

    const total = dp.dewasa + dp.anak + dp.bayi;

    document.getElementById('f_dewasa').value    = dp.dewasa;
    document.getElementById('f_anak').value      = dp.anak;
    document.getElementById('f_bayi').value      = dp.bayi;
    document.getElementById('f_total_pax').value = total;

    updatePaxState();
    updateBatasInfo();
    hitungHarga();
}

// ════════════════════════════════════════════════════════════════════
// RENDER GRID GOLONGAN KENDARAAN
// ════════════════════════════════════════════════════════════════════
const golGrid = document.getElementById('golGrid');
golData.forEach(g => {
    const lbl = document.createElement('label');
    lbl.className = 'gcard';
    lbl.innerHTML = `
        <input type="radio" name="gd" value="${g.id}">
        <div class="gbadge">${g.label}</div>
        <div class="gname">${g.name}</div>
        <div class="gdesc">${g.desc}</div>
        <div class="gmax">👥 Maks ${g.maxTotal} penumpang${g.motorRule ? ' (2D+2A)' : ''}${g.noPlat ? ' | 🚲 Tanpa plat' : ''}</div>
        <div class="gcheck">✓</div>`;
    lbl.addEventListener('click', () => {
        document.querySelectorAll('.gcard').forEach(c => c.classList.remove('selected'));
        lbl.classList.add('selected');
        lbl.querySelector('input').checked = true;
        selectedGol = g;
        document.getElementById('btnSimpanGol').disabled = false;
    });
    golGrid.appendChild(lbl);
});

// ════════════════════════════════════════════════════════════════════
// FUNGSI: simpanGol()
// ★ PERUBAHAN: Jika Golongan I (Sepeda), sembunyikan field plat nomor
//              dan hapus atribut required agar form bisa disubmit.
// ════════════════════════════════════════════════════════════════════
function simpanGol() {
    if (!selectedGol) return;
    document.getElementById('f_golongan').value = selectedGol.id;
    document.getElementById('lblGol').textContent = selectedGol.label + ' — ' + selectedGol.name;
    document.getElementById('btnGol').classList.add('filled');

    // ── Atur tampilan plat nomor berdasarkan golongan ──
    const isSepeda = selectedGol.noPlat === true; // Golongan I (Sepeda)
    const boxPlat  = document.getElementById('boxPlat');
    const inpPlat  = document.getElementById('inpPlat');

    if (isSepeda) {
        // Sembunyikan field plat & hapus required
        boxPlat.style.display = 'none';
        inpPlat.value         = '';
        inpPlat.removeAttribute('required');
    } else {
        // Tampilkan field plat & pasang required kembali
        boxPlat.style.display = 'block';
        inpPlat.setAttribute('required', '');
    }

    // Pangkas penumpang yang melebihi batas golongan baru
    const b = getBatas();
    if (dp.dewasa > b.maxDewasa) dp.dewasa = b.maxDewasa;
    if (dp.anak   > b.maxAnak)   dp.anak   = b.maxAnak;
    let tot = dp.dewasa + dp.anak + dp.bayi;
    if (tot > b.maxTotal) {
        dp.bayi = Math.max(0, dp.bayi - (tot - b.maxTotal));
        tot = dp.dewasa + dp.anak + dp.bayi;
        if (tot > b.maxTotal) dp.anak = Math.max(0, dp.anak - (tot - b.maxTotal));
    }

    syncPaxUI();
    updateBatasInfo();
    hitungHarga();
}

// ════════════════════════════════════════════════════════════════════
// FUNGSI: hitungHarga()
// ════════════════════════════════════════════════════════════════════
function hitungHarga() {
    const layanan  = document.getElementById('selLayanan').value;
    const jenis    = document.getElementById('selJenis').value;
    const golongan = document.getElementById('f_golongan').value;
    const tid      = activeTujuan.id;
    const paxBayar = dp.dewasa + dp.anak;
    const paxTotal = paxBayar + dp.bayi;

    if (!tid || !layanan || paxTotal === 0) {
        document.getElementById('hargaBox').classList.remove('show');
        document.getElementById('f_total_harga').value = 0;
        return;
    }

    const hargaRute = hasilHarga[tid];
    if (!hargaRute) { document.getElementById('hargaBox').classList.remove('show'); return; }

    const hargaPax = parseInt(hargaRute[layanan] ?? 0, 10) || 0;
    let html = '';
    let totalPax = hargaPax * paxBayar;

    if (dp.dewasa) html += row(`Dewasa × ${dp.dewasa}`, fmt(hargaPax * dp.dewasa));
    if (dp.anak)   html += row(`Anak × ${dp.anak}`,     fmt(hargaPax * dp.anak));
    if (dp.bayi)   html += `<div class="h-row"><span class="lbl">Bayi × ${dp.bayi}</span><span class="val" style="color:#4ade80;">Gratis</span></div>`;

    html += `<div class="h-row" style="font-size:12px;color:#475569;border-top:1px solid rgba(255,255,255,.04);padding-top:6px;margin-top:2px;">
        <span class="lbl">Tarif per orang (${layanan})</span><span class="val">${fmt(hargaPax)}</span></div>`;

    let biayaKend = 0;
    if (jenis === 'kendaraan' && golongan) {
        const golObj  = golData.find(g => g.id === golongan);
        const golLbl  = golObj ? `${golObj.label} — ${golObj.name}` : golongan;
        const dataGol = (hasilHargaKend[tid] || {})[golongan];
        html += `<div class="h-row" style="border-top:1px solid rgba(255,255,255,.06);margin-top:8px;padding-top:8px;">
            <span class="lbl">🚗 Kendaraan (${golLbl})</span>`;
        if (dataGol !== undefined && dataGol !== null) {
            biayaKend = parseInt(dataGol[layanan] ?? 0, 10) || 0;
            html += `<span class="val">${biayaKend === 0 ? '<span style="color:#4ade80;">Gratis</span>' : fmt(biayaKend)}</span>`;
        } else {
            html += `<span class="val" style="color:#f59e0b;">Hubungi admin</span>`;
        }
        html += `</div>`;
    }

    const grand = totalPax + biayaKend;
    document.getElementById('hargaDetail').innerHTML  = html;
    document.getElementById('hargaTotal').textContent = fmt(grand);
    document.getElementById('hargaBox').classList.add('show');
    document.getElementById('f_total_harga').value    = grand;
}

function row(lbl, val) { return `<div class="h-row"><span class="lbl">${lbl}</span><span class="val">${val}</span></div>`; }
function fmt(n) { return 'Rp ' + (parseInt(n,10)||0).toLocaleString('id-ID'); }

// ════════════════════════════════════════════════════════════════════
// FUNGSI: bukaModalSyarat(e)
// Validasi seluruh form sebelum submit
// ════════════════════════════════════════════════════════════════════
function bukaModalSyarat(e) {
    e.preventDefault();
    const jenis     = document.getElementById('selJenis').value;
    const golongan  = document.getElementById('f_golongan').value;
    const plat      = document.getElementById('inpPlat')?.value.trim() ?? '';
    const total_pax = dp.dewasa + dp.anak + dp.bayi;

    if (!document.getElementById('f_asal_id').value)   { showToast('⚠️ Pilih pelabuhan asal!','warn');    return false; }
    if (!document.getElementById('f_tujuan_id').value) { showToast('⚠️ Tujuan belum tersedia!','warn');   return false; }
    if (!document.getElementById('inpTanggal').value)  { showToast('⚠️ Pilih tanggal!','warn');           return false; }

    if (document.getElementById('inpTanggal').value < getTodayStr()) {
        showToast('⚠️ Tanggal tidak boleh sebelum hari ini!','warn'); return false;
    }

    if (!document.getElementById('selJam').value) { showToast('⚠️ Pilih jam check-in!','warn'); return false; }

    if (isToday(document.getElementById('inpTanggal').value)) {
        const { h:nowH, m:nowM } = getNowHM();
        const jamH = parseInt(document.getElementById('selJam').value.split(':')[0], 10);
        if ((jamH*60)-(nowH*60+nowM) <= 60) {
            showToast('⛔ Jam tidak valid. Minimal pesan H-1 jam.','warn');
            populateJam(document.getElementById('inpTanggal').value);
            return false;
        }
    }

    if (!document.getElementById('selLayanan').value) { showToast('⚠️ Pilih layanan!','warn');             return false; }
    if (total_pax <= 0)                               { showToast('⚠️ Pilih minimal 1 penumpang!','warn'); return false; }
    if (!jenis)                                       { showToast('⚠️ Pilih jenis pengguna jasa!','warn'); return false; }

    // ── Validasi bayi selalu butuh dewasa (berlaku untuk semua jenis) ──
    if (dp.bayi > 0 && dp.dewasa <= 0) {
        showToast('👶 Bayi wajib didampingi minimal 1 penumpang dewasa!', 'warn');
        return false;
    }

    if (jenis === 'kendaraan') {
        // Validasi anak berkendara butuh dewasa
        if (dp.anak > 0 && dp.dewasa <= 0) {
            showToast('👦 Anak wajib didampingi minimal 1 penumpang dewasa!','warn'); return false;
        }
        if (!golongan) { showToast('⚠️ Pilih golongan kendaraan!','warn'); return false; }

        // Validasi plat: hanya wajib jika bukan Sepeda (Gol I)
        const isSepeda = selectedGol && selectedGol.noPlat === true;
        if (!isSepeda && !plat) {
            showToast('⚠️ Masukkan plat nomor!','warn'); return false;
        }

        const b = getBatas();
        if (total_pax > b.maxTotal) {
            showToast(`⚠️ Maks ${b.maxTotal} penumpang untuk ${selectedGol.label}!`,'warn'); return false;
        }
        if (b.motorRule && dp.dewasa > 2) { showToast('⚠️ Motor maks 2 dewasa!','warn'); return false; }
        if (b.motorRule && dp.anak   > 2) { showToast('⚠️ Motor maks 2 anak!','warn');   return false; }
    }

    syncActiveTujuan();
    document.getElementById('f_dewasa').value    = dp.dewasa;
    document.getElementById('f_anak').value      = dp.anak;
    document.getElementById('f_bayi').value      = dp.bayi;
    document.getElementById('f_total_pax').value = dp.dewasa + dp.anak + dp.bayi;

    document.getElementById('chkSyarat').checked    = false;
    document.getElementById('btnSetuju').disabled   = true;
    document.getElementById('syaratBody').scrollTop = 0;
    document.getElementById('scrollNote').textContent = '↓ Gulir ke bawah untuk membaca semua ketentuan';
    document.getElementById('scrollNote').style.color  = '#64748b';

    new bootstrap.Modal(document.getElementById('modalSyarat')).show();
    return false;
}

// ── Fungsi modal syarat ──
function cekScroll() {
    const el = document.getElementById('syaratBody');
    if (el.scrollTop + el.clientHeight >= el.scrollHeight - 10) {
        document.getElementById('scrollNote').textContent = '✓ Anda telah membaca seluruh ketentuan';
        document.getElementById('scrollNote').style.color = '#4ade80';
    }
}
function toggleSyarat()    { const cb=document.getElementById('chkSyarat'); cb.checked=!cb.checked; updateBtnSetuju(); }
function updateBtnSetuju() { document.getElementById('btnSetuju').disabled = !document.getElementById('chkSyarat').checked; }
function batalSyarat()     { document.getElementById('chkSyarat').checked=false; document.getElementById('btnSetuju').disabled=true; }
function setujuLanjut() {
    if (!document.getElementById('chkSyarat').checked) return;
    bootstrap.Modal.getInstance(document.getElementById('modalSyarat')).hide();
    setTimeout(() => document.getElementById('formBeli').submit(), 300);
}

// ── Auto-refresh jam setiap 5 detik jika tanggal = hari ini ──
setInterval(() => {
    const tgl = document.getElementById('inpTanggal').value;
    if (tgl && isToday(tgl)) {
        const prev = document.getElementById('selJam').value;
        populateJam(tgl);
        if (prev) {
            const opt = document.getElementById('selJam').querySelector(`option[value="${prev}"]`);
            if (opt && !opt.disabled) {
                document.getElementById('selJam').value = prev;
            } else if (opt?.disabled) {
                document.getElementById('selJam').value = '';
                document.getElementById('jamAlert').classList.add('show','err');
                document.getElementById('jamAlertText').textContent = '⛔ Jam yang dipilih sudah lewat!';
            }
        }
    }
}, 5000);

// ── INISIALISASI ──
updatePaxState();
</script>
</body>
</html>
