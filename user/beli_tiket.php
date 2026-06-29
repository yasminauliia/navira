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

/* ── BODY ── */
body{font-family:'Poppins',sans-serif;color:white;min-height:100vh;overflow-x:hidden}

/* ── BUBBLE ANIMASI LATAR BELAKANG ── */
.bubble{position:fixed;bottom:-80px;border-radius:50%;background:rgba(255,255,255,0.05);animation:bup 12s infinite;pointer-events:none;z-index:0}
@keyframes bup{0%{transform:translateY(0) scale(1);opacity:.5}100%{transform:translateY(-110vh) scale(1.8);opacity:0}}

/* ── TOPBAR NAVIGASI ATAS ── */
.topbar{background:var(--navy-topbar);backdrop-filter:blur(14px);border-bottom:1px solid rgba(255,255,255,.07);padding:14px 28px;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:300}
.brand-logo{height:40px;width:auto;object-fit:contain;display:block}
.brand{text-decoration:none;display:inline-flex;align-items:center;gap:10px}
.brand-name{font-size:22px;font-weight:700;color:white;letter-spacing:1.5px}
.btn-back{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:rgba(255,255,255,.75);border-radius:10px;padding:8px 18px;font-size:13px;text-decoration:none;font-family:'Poppins',sans-serif;transition:.2s}
.btn-back:hover{background:rgba(255,255,255,.12);color:white}

/* ── HERO JUDUL HALAMAN ── */
.hero{text-align:center;padding:48px 20px 80px;position:relative;z-index:1}
.hero h2{font-weight:700;font-size:28px;background:linear-gradient(to right,#38bdf8,#22d3ee);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.hero p{color:#64748b;font-size:14px;margin-top:6px}

/* ── KARTU FORM UTAMA ── */
.card-main{background:rgba(255,255,255,.04);backdrop-filter:blur(20px);border-radius:24px;padding:32px;margin-top:-52px;border:1px solid rgba(255,255,255,.09);box-shadow:0 0 60px rgba(0,0,0,.6);position:relative;z-index:1}

/* ── LABEL FIELD FORM ── */
.flabel{font-size:11px;color:#64748b;margin-bottom:6px;display:block;text-transform:uppercase;letter-spacing:.8px;font-weight:600}

/* ── INPUT & SELECT FORM ── */
.form-control,.form-select{background:rgba(255,255,255,.06)!important;border:1px solid rgba(255,255,255,.1)!important;color:white!important;border-radius:12px!important;padding:11px 14px!important;font-family:'Poppins',sans-serif;font-size:14px;transition:.3s}
.form-control:focus,.form-select:focus{border-color:#38bdf8!important;box-shadow:0 0 0 3px rgba(56,189,248,.15)!important;outline:none}
.form-select option{background:#0f172a;color:white}
.form-select option:disabled{color:#475569!important;background:#1e293b!important}
.form-control::placeholder{color:rgba(255,255,255,.25)}
.form-control:disabled,.form-select:disabled{opacity:.4!important;cursor:not-allowed}
input[type="date"]::-webkit-calendar-picker-indicator{filter:invert(1);opacity:.4}

/* ── SELECT TUJUAN YANG DIKUNCI OTOMATIS ── */
.form-select.locked{opacity:.85!important;cursor:not-allowed;border-color:rgba(56,189,248,.35)!important;background:rgba(56,189,248,.07)!important}

/* ── BADGE "OTOMATIS" PADA TUJUAN ── */
.locked-badge{display:none;font-size:10px;color:#38bdf8;background:rgba(56,189,248,.1);border:1px solid rgba(56,189,248,.2);border-radius:6px;padding:2px 8px;margin-left:6px}
.locked-badge.show{display:inline-block}

/* ── TOMBOL TUKAR ASAL-TUJUAN ── */
.swap-btn{background:rgba(56,189,248,.1);border:1px solid rgba(56,189,248,.2);border-radius:50%;width:36px;height:36px;color:#38bdf8;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:.3s;align-self:flex-end;margin-bottom:2px}
.swap-btn:hover:not(:disabled){background:rgba(56,189,248,.25);transform:rotate(180deg)}
.swap-btn:disabled{opacity:.25;cursor:not-allowed}

/* ── TOMBOL PICKER GOLONGAN ── */
.picker-btn{background:rgba(255,255,255,.06)!important;border:1px solid rgba(255,255,255,.1)!important;border-radius:12px!important;padding:11px 14px!important;color:white!important;font-family:'Poppins',sans-serif;font-size:14px;width:100%;cursor:pointer;text-align:left;display:flex;align-items:center;justify-content:space-between;transition:.3s}
.picker-btn:hover{border-color:#38bdf8!important}
.picker-btn.filled{border-color:rgba(56,189,248,.4)!important;background:rgba(56,189,248,.07)!important}

/* ── PREVIEW RUTE ASAL → TUJUAN ── */
.rute-prev{background:rgba(56,189,248,.05);border:1px solid rgba(56,189,248,.15);border-radius:14px;padding:16px 20px;display:none;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:4px}
.rute-prev.show{display:flex}
.rp-port{text-align:center}
.rp-port .kota{font-size:14px;font-weight:700;color:white}
.rp-port .lok{font-size:11px;color:#64748b;margin-top:2px}
.rp-arrow{font-size:22px;color:#38bdf8;flex-shrink:0}
.rp-tag{background:rgba(56,189,248,.12);border:1px solid rgba(56,189,248,.2);border-radius:20px;padding:3px 12px;font-size:11px;color:#38bdf8;font-weight:600;margin-left:auto}

/* ── ALERT PERINGATAN JAM ── */
.jam-alert{display:none;align-items:center;gap:10px;background:rgba(251,191,36,.07);border:1px solid rgba(251,191,36,.25);border-radius:10px;padding:10px 14px;margin-top:8px;font-size:12px;color:#fbbf24}
.jam-alert.show{display:flex}
.jam-alert.err{background:rgba(239,68,68,.07);border-color:rgba(239,68,68,.25);color:#f87171}
.jam-alert svg{flex-shrink:0;width:16px;height:16px}

/* ── INFO BATAS KAPASITAS KENDARAAN ── */
.batas-info{font-size:11px;color:#64748b;margin-top:6px;padding:8px 12px;background:rgba(255,255,255,.03);border-radius:8px;border:1px solid rgba(255,255,255,.06)}
.batas-info.warn{color:#fbbf24;border-color:rgba(251,191,36,.2);background:rgba(251,191,36,.05)}

/* ════════════════════════════════════════════════════════════════
   GRID PENUMPANG
   FIX SEJAJAR:
   - align-items:stretch → semua card sama tingginya
   - .pax-item memakai flex column → counter selalu di bawah
   Desktop: 3 kolom | Tablet: 2 kolom | Mobile: 1 kolom
   ════════════════════════════════════════════════════════════════ */
.pax-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:12px;
    margin-top:8px;
    align-items:stretch;
}

/* ── KARTU PENUMPANG: flex column agar konten tersebar merata ── */
.pax-item{
    background:rgba(255,255,255,.04);
    border:1px solid rgba(255,255,255,.08);
    border-radius:12px;
    padding:14px 10px;
    text-align:center;
    transition:.3s;
    display:flex;
    flex-direction:column;
    align-items:center;
}

/* ── STATE TERKUNCI (abu-abu, tidak bisa diklik) ── */
.pax-item.locked-pax{opacity:.4;pointer-events:none;cursor:not-allowed}
.pax-item.locked-pax .p-lbl{color:#334155}

/* ── LABEL NAMA KATEGORI (DEWASA/ANAK/BAYI) ── */
.pax-item .p-lbl{font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px}

/* ── TEKS KETERANGAN USIA ── */
.pax-item .p-ket{
    font-size:10px;color:#334155;line-height:1.5;
    flex:1;display:flex;flex-direction:column;
    align-items:center;justify-content:flex-end;
    padding-bottom:10px;min-height:44px;
}

/* ════════════════════════════════════════════════════════════════
   BADGE SYARAT PENDAMPING (⚠ / ✓)
   Pakai visibility (bukan display:none) agar tinggi card
   tidak berubah saat badge muncul/hilang → counter tetap sejajar
   ════════════════════════════════════════════════════════════════ */
.dep-badge{
    display:inline-block;margin-top:6px;
    font-size:9px;font-weight:700;
    color:#f59e0b;background:rgba(245,158,11,.1);
    border:1px solid rgba(245,158,11,.25);
    border-radius:20px;padding:2px 8px;letter-spacing:.3px;
    visibility:visible;
}
/* ── Badge hijau: syarat terpenuhi ── */
.dep-badge.ok{color:#4ade80;background:rgba(74,222,128,.1);border-color:rgba(74,222,128,.25)}
/* ── Badge tersembunyi tapi tetap ambil ruang (jaga tinggi card) ── */
.dep-badge.invisible{visibility:hidden}

/* ── COUNTER − ANGKA + ── */
.pax-ctr{display:flex;align-items:center;justify-content:center;gap:8px;margin-top:auto;padding-top:8px}
.pax-ctr button{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);color:white;width:30px;height:30px;border-radius:8px;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:.2s;line-height:1}
.pax-ctr button:hover:not(:disabled){background:rgba(56,189,248,.15);border-color:#38bdf8}
.pax-ctr button:disabled{opacity:.25;cursor:not-allowed}
.pax-ctr input{width:46px;text-align:center;font-weight:700;font-size:18px;background:transparent;border:none;color:white;outline:none;font-family:'Poppins',sans-serif}

/* ── TOAST MINI (NOTIFIKASI KECIL BAWAH) ── */
#miniToast{
    position:fixed;bottom:24px;left:50%;
    transform:translateX(-50%) translateY(20px);
    background:rgba(5,12,30,.97);border:1px solid rgba(255,255,255,.1);
    color:white;padding:11px 22px;border-radius:12px;font-size:13px;
    font-family:'Poppins',sans-serif;z-index:9999;transition:all .3s ease;
    opacity:0;pointer-events:none;backdrop-filter:blur(14px);
    box-shadow:0 8px 30px rgba(0,0,0,.4);white-space:nowrap;
}

/* ── BOX ESTIMASI BIAYA ── */
.harga-box{background:rgba(56,189,248,.04);border:1px solid rgba(56,189,248,.12);border-radius:16px;padding:18px 22px;display:none}
.harga-box.show{display:block}
.h-row{display:flex;justify-content:space-between;padding:7px 0;font-size:14px}
.h-row .lbl{color:#64748b}
.h-row .val{color:white;font-weight:500}
.h-total{border-top:1px dashed rgba(56,189,248,.2);margin-top:10px;padding-top:12px}
.h-total .lbl{color:#38bdf8;font-weight:600;font-size:15px}
.h-total .val{color:#38bdf8;font-weight:700;font-size:22px}

/* ── DIVIDER TIPIS ── */
.fdiv{border:none;border-top:1px solid rgba(255,255,255,.06);margin:20px 0}

/* ── TOMBOL SUBMIT UTAMA ── */
.btn-submit{background:linear-gradient(135deg,#06b6d4,#3b82f6);border:none;border-radius:50px;color:white;font-family:'Poppins',sans-serif;font-weight:700;font-size:14px;letter-spacing:1px;text-transform:uppercase;padding:14px 40px;transition:.3s;cursor:pointer;width:100%}
.btn-submit:hover{transform:scale(1.02);box-shadow:0 0 30px rgba(56,189,248,.4)}
.btn-submit:disabled{opacity:.4;cursor:not-allowed;transform:none;box-shadow:none}

/* ── MODAL ── */
.modal-content{background:rgba(5,12,30,.98)!important;border:1px solid rgba(255,255,255,.1)!important;border-radius:22px!important;backdrop-filter:blur(20px);color:white}
.mclosebtn{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);border-radius:8px;width:30px;height:30px;color:#94a3b8;cursor:pointer;font-size:15px;display:flex;align-items:center;justify-content:center;flex-shrink:0}

/* ── GRID PILIHAN GOLONGAN KENDARAAN ── */
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

/* ── ISI MODAL SYARAT & KETENTUAN ── */
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

/* ── TOMBOL MODAL ── */
.btnm{background:linear-gradient(135deg,#06b6d4,#3b82f6);border:none;border-radius:12px;color:white;font-family:'Poppins',sans-serif;font-weight:700;font-size:14px;padding:13px;width:100%;cursor:pointer;transition:.3s;margin-top:14px}
.btnm:hover{opacity:.9}
.btnm:disabled{opacity:.35;cursor:not-allowed}
.btnm-sec{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);border-radius:12px;color:rgba(255,255,255,.6);font-family:'Poppins',sans-serif;font-weight:600;font-size:14px;padding:13px;cursor:pointer;transition:.2s}
.btnm-sec:hover{background:rgba(255,255,255,.12);color:white}

/* ── RESPONSIVE ── */
@media(max-width:768px){.card-main{padding:20px}.gol-grid{grid-template-columns:repeat(2,1fr)}.pax-grid{grid-template-columns:1fr 1fr}.topbar{padding:12px 16px}.brand-name{font-size:18px}.brand-logo{height:34px}}
@media(max-width:480px){.gol-grid{grid-template-columns:1fr}.pax-grid{grid-template-columns:1fr}}
</style>
</head>
<body class="bg-navy-animated">

<!-- TOAST: notifikasi mini di bagian bawah layar -->
<div id="miniToast"></div>

<!-- BUBBLE ANIMASI LATAR (6 bola mengambang, dibuat via PHP loop) -->
<?php for ($i = 0; $i < 6; $i++): ?>
<div class="bubble" style="left:<?= $i*17+4 ?>%;width:<?= 14+$i*4 ?>px;height:<?= 14+$i*4 ?>px;animation-delay:<?= $i*2 ?>s;animation-duration:<?= 11+$i*2 ?>s;"></div>
<?php endfor; ?>

<!-- TOPBAR -->
<div class="topbar">
    <a href="../index.php" class="brand">
        <img src="../assets/logo.png" alt="Logo" class="brand-logo">
        <span class="brand-name">NAVIRA</span>
    </a>
    <a href="dashboard.php" class="btn-back">← Dashboard</a>
</div>

<!-- HERO -->
<div class="hero">
    <h2>🌊 Pemesanan Tiket Kapal</h2>
    <p>Pilih pelabuhan asal — tujuan otomatis terpilih</p>
</div>

<!-- KONTEN UTAMA -->
<div class="container pb-5" style="position:relative;z-index:1;">
<div class="card-main">

<!-- FORM: onsubmit menjalankan bukaModalSyarat() sebelum submit ke server -->
<form id="formBeli" action="simpan_tiket.php" method="POST" onsubmit="return bukaModalSyarat(event)">

    <!-- ═══ HIDDEN FIELDS: dikirim ke server saat submit ═══ -->
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
        <!-- Pelabuhan Asal: opsi diisi JS dari get_pelabuhan.php -->
        <div class="col-md-5">
            <label class="flabel">Pelabuhan Asal</label>
            <div class="d-flex gap-2 align-items-end">
                <select id="selAsal" class="form-select flex-grow-1" required onchange="onAsalChange()">
                    <option value="">⚓ Pilih Pelabuhan Asal</option>
                </select>
                <!-- Tombol swap aktif setelah asal dipilih -->
                <button type="button" class="swap-btn" id="swapBtn" onclick="doSwap()" disabled>⇅</button>
            </div>
        </div>

        <!-- Pelabuhan Tujuan: dikunci otomatis berdasarkan asal -->
        <div class="col-md-5">
            <label class="flabel">
                Pelabuhan Tujuan
                <span class="locked-badge" id="lockedBadge">🔒 Otomatis</span>
            </label>
            <select id="selTujuan" class="form-select locked" disabled>
                <option value="">— Pilih asal dulu —</option>
            </select>
        </div>

        <!-- Layanan: reguler atau express -->
        <div class="col-md-2">
            <label class="flabel">Layanan</label>
            <select name="layanan" id="selLayanan" class="form-select" onchange="onLayananChange()">
                <option value="">— Pilih —</option>
                <option value="reguler">🪑 Reguler</option>
                <option value="express">⚡ Express</option>
            </select>
        </div>
    </div>

    <!-- CARD PREVIEW RUTE (muncul setelah asal dipilih) -->
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
        <!-- Tanggal: tidak boleh sebelum hari ini -->
        <div class="col-md-4">
            <label class="flabel">Tanggal Keberangkatan</label>
            <input type="date" name="tanggal" id="inpTanggal" class="form-control" required
                min="<?= date('Y-m-d') ?>" onchange="onTanggalChange()">
        </div>

        <!-- Jam: dropdown diisi JS setelah tanggal dipilih -->
        <div class="col-md-3">
            <label class="flabel">Jam Check-In</label>
            <select name="jam" id="selJam" class="form-select" required onchange="onJamChange()">
                <option value="">— Pilih Tanggal Dulu —</option>
            </select>
            <!-- Alert muncul jika jam sudah lewat / terlalu dekat -->
            <div class="jam-alert" id="jamAlert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <span id="jamAlertText"></span>
            </div>
        </div>

        <!-- Jenis Pengguna: menentukan logika anak & bayi -->
        <div class="col-md-5">
            <label class="flabel">Jenis Pengguna Jasa</label>
            <select name="jenis_pengguna" id="selJenis" class="form-select" onchange="onJenisChange()">
                <option value="">— Pilih Jenis —</option>
                <option value="penumpang">🚶 Pejalan Kaki</option>
                <option value="kendaraan">🚗 Berkendara</option>
            </select>
        </div>
    </div>

    <!-- ═══ GOLONGAN & PLAT NOMOR (hanya tampil jika berkendara) ═══ -->
    <div class="row g-3 mb-3" id="boxKendaraan" style="display:none;">
        <!-- Tombol buka modal pilih golongan -->
        <div class="col-md-5">
            <label class="flabel">Golongan Kendaraan</label>
            <button type="button" class="picker-btn" id="btnGol"
                data-bs-toggle="modal" data-bs-target="#modalGol">
                <span>🚗 <span id="lblGol">Pilih Golongan</span></span>
                <span style="font-size:11px;opacity:.5;">▼</span>
            </button>
        </div>

        <!--
        ═══════════════════════════════════════════════════════
        KOLOM PLAT NOMOR
        id="boxPlat" → dipakai JS untuk show/hide seluruh kolom
        id="inpPlat" → input plat yang dikirim ke server

        LOGIKA PLAT SEPEDA (Golongan I):
          1. Kolom ini DISEMBUNYIKAN (display:none) agar user
             tidak perlu mengisi plat secara manual.
          2. Namun input #inpPlat TETAP ADA di DOM (tersembunyi),
             dan JS akan otomatis mengisi nilainya dengan "SEPEDA".
          3. Nilai "SEPEDA" ini yang akan terkirim ke database,
             sehingga validasi plat di server tetap terpenuhi
             (database wajib ada isi di kolom plat).

        Untuk golongan lain: kolom tampil dan user wajib isi manual.
        ═══════════════════════════════════════════════════════
        -->
        <div class="col-md-4" id="boxPlat">
            <label class="flabel">Plat Nomor</label>
            <input type="text" name="plat" id="inpPlat" class="form-control"
                placeholder="B 1234 ABC" oninput="this.value=this.value.toUpperCase()">
        </div>
    </div>

    <!-- ═══ GRID PENUMPANG ═══
         LOGIKA UTAMA ANAK & BAYI:
           PEJALAN KAKI:
             - Dewasa → bebas
             - Anak   → bebas (TIDAK perlu dewasa)
             - Bayi   → WAJIB ada minimal 1 dewasa
           BERKENDARA:
             - Dewasa → bebas
             - Anak   → WAJIB ada minimal 1 dewasa
             - Bayi   → WAJIB ada minimal 1 dewasa
    ═══ -->
    <div class="mb-3">
        <label class="flabel">
            Penumpang
            <span id="batasLabel" style="font-size:10px;color:#38bdf8;margin-left:8px;"></span>
        </label>

        <div class="pax-grid">

            <!-- ── DEWASA ── Tidak ada syarat khusus -->
            <div class="pax-item" id="item_dewasa">
                <div class="p-lbl">Dewasa</div>
                <div class="p-ket">
                    ≥ 17 tahun
                    <!-- Badge invisible: hanya sebagai spacer agar tinggi card sama -->
                    <span class="dep-badge invisible" id="dewasaBadge">placeholder</span>
                </div>
                <div class="pax-ctr">
                    <button type="button" id="btn_m_dewasa" onclick="paxKurang('dewasa')" disabled>−</button>
                    <input type="number" id="inp_dewasa" value="0" min="0" max="99"
                        onchange="paxManual('dewasa',this.value)"
                        oninput="paxManual('dewasa',this.value)" disabled>
                    <button type="button" id="btn_p_dewasa" onclick="paxTambah('dewasa')" disabled>+</button>
                </div>
            </div>

            <!-- ── ANAK ──
                 Pejalan kaki : bebas (badge invisible)
                 Berkendara   : butuh dewasa (badge kuning/hijau) -->
            <div class="pax-item" id="item_anak">
                <div class="p-lbl">Anak</div>
                <div class="p-ket">
                    3 – 16 tahun
                    <!-- Badge awalnya invisible, JS ubah sesuai jenis pengguna -->
                    <span class="dep-badge invisible" id="anakBadge">⚠ Perlu 1 dewasa</span>
                </div>
                <div class="pax-ctr">
                    <button type="button" id="btn_m_anak" onclick="paxKurang('anak')" disabled>−</button>
                    <input type="number" id="inp_anak" value="0" min="0" max="99"
                        onchange="paxManual('anak',this.value)"
                        oninput="paxManual('anak',this.value)" disabled>
                    <button type="button" id="btn_p_anak" onclick="paxTambah('anak')" disabled>+</button>
                </div>
            </div>

            <!-- ── BAYI ── Selalu wajib dewasa di semua mode -->
            <div class="pax-item" id="item_bayi">
                <div class="p-lbl">Bayi</div>
                <div class="p-ket">
                    &lt; 3 tahun (Gratis)
                    <!-- Badge selalu tampil setelah jenis dipilih -->
                    <span class="dep-badge invisible" id="bayiBadge">⚠ Perlu 1 dewasa</span>
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

        <!-- Info kapasitas (muncul setelah golongan kendaraan dipilih) -->
        <div class="batas-info" id="batasInfo" style="display:none;"></div>
    </div>

    <hr class="fdiv">

    <!-- ESTIMASI BIAYA -->
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
</div>
</div>


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
                <li><b>Semua mode:</b> bayi wajib didampingi minimal 1 dewasa.</li>
                <li><b>Berkendara:</b> anak & bayi wajib didampingi minimal 1 dewasa.</li>
                <li>Sepeda (Gol I): tidak perlu mencantumkan plat nomor secara manual.</li>
                <li>Motor (Gol II & III): maks 2 dewasa + 2 anak.</li>
                <li>Jumlah penumpang tidak boleh melebihi kapasitas golongan kendaraan.</li>
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
//
// Properti tiap objek:
//   id         → kode unik golongan (dikirim ke server)
//   label      → nama resmi (ditampilkan ke user)
//   name       → jenis kendaraan singkat
//   desc       → contoh kendaraan
//   maxTotal   → maks total penumpang (dewasa + anak + bayi)
//   maxDewasa  → maks penumpang dewasa
//   maxAnak    → maks penumpang anak
//   noPlat     → true jika tidak memerlukan plat nomor (khusus Sepeda Gol I)
//   motorRule  → true jika berlaku aturan motor (maks 2 dewasa + 2 anak)
//
// ★ LOGIKA SEPEDA (noPlat:true):
//   Karena database mewajibkan kolom plat terisi,
//   saat user pilih Golongan I maka JS otomatis mengisi
//   field plat (yang disembunyikan) dengan nilai "SEPEDA".
//   Field plat tersembunyi tetap ikut terkirim ke server.
// ════════════════════════════════════════════════════════════════════
const golData = [
    { id:'gol_1',  label:'Golongan I',    name:'Sepeda',           desc:'Sepeda kayuh, onthel',           maxTotal:1,  maxDewasa:1,  maxAnak:0,  noPlat:true               },
    { id:'gol_2',  label:'Golongan II',   name:'Motor <500cc',     desc:'Honda, Yamaha, Suzuki <500cc',   maxTotal:4,  maxDewasa:2,  maxAnak:2,  motorRule:true             },
    { id:'gol_3',  label:'Golongan III',  name:'Motor >500cc',     desc:'Ducati, Harley, roda tiga',      maxTotal:4,  maxDewasa:2,  maxAnak:2,  motorRule:true             },
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

// dp = jumlah penumpang per kategori (selalu sinkron dgn tampilan UI)
let dp = { dewasa:0, anak:0, bayi:0 };

// selectedGol = objek golongan yang sedang dipilih di modal, atau null
let selectedGol = null;

// hasilHarga = data tarif penumpang dari API { tujuan_id: {reguler:X, express:Y} }
let hasilHarga = {};

// hasilHargaKend = data tarif kendaraan dari API
let hasilHargaKend = {};

// activeTujuan = detail tujuan yang sedang aktif
let activeTujuan = { id:'', nama:'', lokasi:'', label:'' };

// layananAktif = layanan yang dipilih user ('reguler' / 'express' / '')
let layananAktif = '';


// ════════════════════════════════════════════════════════════════════
// FUNGSI: showToast(msg, tipe)
// Tampilkan notifikasi kecil di bawah layar selama ~2.8 detik.
// tipe: 'warn'=kuning | 'ok'=hijau | default=biru
// ════════════════════════════════════════════════════════════════════
function showToast(msg, tipe) {
    const t = document.getElementById('miniToast'); // ambil elemen toast

    t.textContent = msg; // isi teks notifikasi

    // Pilih warna border sesuai jenis notifikasi
    t.style.borderColor = tipe==='warn' ? 'rgba(251,191,36,.35)' :
                          tipe==='ok'   ? 'rgba(74,222,128,.35)' :
                                          'rgba(56,189,248,.3)';

    // Animasi slide naik: ubah opacity dan transform
    t.style.opacity   = '1';
    t.style.transform = 'translateX(-50%) translateY(0)';

    clearTimeout(t._timer); // batalkan timer sebelumnya agar tidak tumpang tindih

    // Sembunyikan otomatis setelah 2.8 detik
    t._timer = setTimeout(() => {
        t.style.opacity   = '0';
        t.style.transform = 'translateX(-50%) translateY(20px)';
    }, 2800);
}


// ════════════════════════════════════════════════════════════════════
// FUNGSI WAKTU & TANGGAL — helper untuk validasi jam & tanggal
// ════════════════════════════════════════════════════════════════════

// Kembalikan { h, m } = jam dan menit sekarang
function getNowHM() { const n=new Date(); return {h:n.getHours(), m:n.getMinutes()}; }

// Kembalikan string tanggal hari ini format 'YYYY-MM-DD'
function getTodayStr() {
    const n=new Date();
    return `${n.getFullYear()}-${String(n.getMonth()+1).padStart(2,'0')}-${String(n.getDate()).padStart(2,'0')}`;
}

// Cek apakah string tanggal d sama dengan hari ini
function isToday(d) { return d === getTodayStr(); }


// ════════════════════════════════════════════════════════════════════
// FUNGSI: populateJam(tanggalVal)
// Isi dropdown jam berdasarkan tanggal yang dipilih.
// Hari ini: jam yang sudah lewat atau < 1 jam dari sekarang di-disable.
// ════════════════════════════════════════════════════════════════════
function populateJam(tanggalVal) {
    const sel     = document.getElementById('selJam');       // dropdown jam
    const alertEl = document.getElementById('jamAlert');     // kotak peringatan
    const alertTx = document.getElementById('jamAlertText'); // teks peringatan

    sel.innerHTML = ''; // bersihkan opsi lama

    if (!tanggalVal) {
        // Tanggal belum dipilih: tampilkan placeholder saja
        sel.innerHTML = '<option value="">— Pilih Tanggal Dulu —</option>';
        alertEl.classList.remove('show','err');
        return;
    }

    const today = isToday(tanggalVal);        // apakah tanggal = hari ini?
    const { h:nowH, m:nowM } = getNowHM();   // jam & menit sekarang
    let adaValid = false;                     // flag: ada jam valid?

    for (let h = 0; h < 24; h++) {
        // Format nilai (mis. "08:00") dan label tampilan (mis. "08.00 - 09.00")
        const val   = String(h).padStart(2,'0') + ':00';
        const label = String(h).padStart(2,'0') + '.00 - ' + String(h===23?0:h+1).padStart(2,'0') + '.00';
        const opt   = document.createElement('option');
        opt.value   = val;

        if (today && h < nowH) {
            // Jam sudah terlewat (sebelum jam sekarang)
            opt.textContent = '🚫 ' + label + ' (Sudah lewat)';
            opt.disabled    = true;
        } else if (today && ((h*60)-(nowH*60+nowM)) <= 60) {
            // Kurang dari 1 jam dari sekarang: terlalu dekat untuk check-in
            opt.textContent = '⏳ ' + label + ' (Terlalu dekat)';
            opt.disabled    = true;
        } else {
            opt.textContent = label; // jam valid, bisa dipilih
            adaValid = true;
        }
        sel.appendChild(opt);
    }

    // Tambah placeholder di posisi pertama dropdown
    const ph = new Option('— Pilih Jam Check-In —', '');
    ph.selected = true;
    sel.insertBefore(ph, sel.firstChild);

    // Tampilkan alert yang sesuai kondisi
    if (!adaValid) {
        // Tidak ada jam tersedia hari ini (terlalu malam)
        alertEl.classList.add('show','err');
        alertTx.textContent = 'Tidak ada jam tersedia hari ini. Pilih tanggal lain.';
    } else if (today) {
        // Ada jam valid tapi hari ini: beri peringatan batas waktu
        alertEl.classList.add('show'); alertEl.classList.remove('err');
        alertTx.textContent = 'Slot < 1 jam dari sekarang tidak tersedia. Waktu: '
            + String(nowH).padStart(2,'0') + ':' + String(nowM).padStart(2,'0') + ' WIB';
    } else {
        // Tanggal mendatang: semua jam tersedia, hapus alert
        alertEl.classList.remove('show','err');
    }
}

// Dipanggil saat tanggal berubah: refresh daftar jam
function onTanggalChange() { populateJam(document.getElementById('inpTanggal').value); }

// Dipanggil saat jam dipilih: validasi ulang apakah jam masih valid
function onJamChange() {
    const tgl = document.getElementById('inpTanggal').value;
    const jam = document.getElementById('selJam').value;
    if (!jam || !isToday(tgl)) return; // hanya validasi jika hari ini

    const { h:nowH, m:nowM } = getNowHM();
    const jamH = parseInt(jam.split(':')[0], 10); // jam dalam angka

    // Jika selisih ≤ 60 menit: slot tidak valid, reset dropdown
    if ((jamH*60)-(nowH*60+nowM) <= 60) {
        document.getElementById('jamAlert').classList.add('show','err');
        document.getElementById('jamAlertText').textContent = '⛔ Slot ini tidak valid. Pilih jam berikutnya.';
        document.getElementById('selJam').value = ''; // kosongkan pilihan
    }
}


// ════════════════════════════════════════════════════════════════════
// LOAD PELABUHAN ASAL dari API get_pelabuhan.php
// Dipanggil saat halaman dimuat (fetch otomatis saat script dijalankan)
// ════════════════════════════════════════════════════════════════════
fetch('get_pelabuhan.php')
    .then(r => r.json())
    .then(data => {
        if (!data.success) return; // hentikan jika API gagal

        const sel = document.getElementById('selAsal'); // dropdown asal

        // Tambahkan setiap pelabuhan sebagai opsi dropdown
        data.asals.forEach(p => {
            const o = new Option('🚢 ' + p.label, p.id);
            o.dataset.nama   = p.nama;   // simpan nama di data attribute
            o.dataset.lokasi = p.lokasi; // simpan lokasi di data attribute
            o.dataset.label  = p.label;  // simpan label lengkap
            sel.appendChild(o);
        });
    })
    .catch(e => console.error('get_pelabuhan error:', e));


// ════════════════════════════════════════════════════════════════════
// FUNGSI: onAsalChange()
// Dipanggil saat asal berubah.
// Fetch tujuan yang tersedia + data harga dari API.
// ════════════════════════════════════════════════════════════════════
function onAsalChange() {
    const selAsal = document.getElementById('selAsal');
    const asalId  = selAsal.value;
    const asalOpt = selAsal.options[selAsal.selectedIndex]; // opsi yang aktif
    const selTuj  = document.getElementById('selTujuan');

    resetTujuan(); // bersihkan state tujuan sebelumnya
    if (!asalId) return; // batal jika tidak ada asal

    // Simpan ID dan nama asal ke hidden field
    document.getElementById('f_asal_id').value   = asalId;
    document.getElementById('f_asal_nama').value = asalOpt.dataset.label || asalOpt.dataset.nama || '';

    selTuj.innerHTML = '<option value="">⏳ Mencari rute...</option>'; // tampilkan loading

    // Fetch tujuan dari API berdasarkan asal yang dipilih
    fetch('get_tujuan.php?asal_id=' + asalId)
        .then(r => r.json())
        .then(data => {
            selTuj.innerHTML = ''; // hapus loading

            if (!data.success || !data.tujuans?.length) {
                // Tidak ada rute dari asal ini
                selTuj.innerHTML = '<option value="">❌ Rute belum tersedia</option>';
                return;
            }

            // Normalisasi key ke String agar lookup konsisten
            hasilHarga     = {};
            hasilHargaKend = {};
            Object.entries(data.harga_data      || {}).forEach(([k,v]) => hasilHarga[String(k)]     = v);
            Object.entries(data.harga_kendaraan || {}).forEach(([k,v]) => hasilHargaKend[String(k)] = v);

            // Isi dropdown tujuan dengan data dari API
            data.tujuans.forEach(t => {
                const o = new Option('🚢 ' + t.label, t.id);
                o.dataset.nama   = t.nama;
                o.dataset.lokasi = t.lokasi;
                o.dataset.label  = t.label;
                selTuj.appendChild(o);
            });

            selTuj.selectedIndex = 0;    // pilih tujuan pertama otomatis
            selTuj.disabled      = true; // kunci dropdown (otomatis, tidak bisa ganti)

            document.getElementById('lockedBadge').classList.add('show'); // tampilkan badge kunci
            document.getElementById('swapBtn').disabled = false;           // aktifkan tombol swap

            syncActiveTujuan(); // sinkronkan tujuan ke state & hidden field
            tampilPreview();    // tampilkan card preview rute
            hitungHarga();      // hitung estimasi biaya
        })
        .catch(e => {
            console.error('get_tujuan error:', e);
            selTuj.innerHTML = '<option value="">❌ Error koneksi</option>';
        });
}


// ════════════════════════════════════════════════════════════════════
// FUNGSI: resetTujuan()
// Kembalikan semua state & UI tujuan ke kondisi awal.
// Dipanggil sebelum memuat tujuan baru.
// ════════════════════════════════════════════════════════════════════
function resetTujuan() {
    const selTuj = document.getElementById('selTujuan');
    selTuj.innerHTML = '<option value="">— Pilih asal dulu —</option>'; // reset dropdown
    selTuj.disabled  = true;                                            // kunci kembali

    document.getElementById('lockedBadge').classList.remove('show'); // sembunyikan badge otomatis
    document.getElementById('swapBtn').disabled = true;               // nonaktifkan swap
    document.getElementById('rutePrev').classList.remove('show');     // sembunyikan preview
    document.getElementById('hargaBox').classList.remove('show');     // sembunyikan estimasi

    hasilHarga = {}; hasilHargaKend = {}; // bersihkan cache harga

    // Reset state tujuan aktif
    activeTujuan = { id:'', nama:'', lokasi:'', label:'' };

    // Kosongkan hidden field tujuan
    document.getElementById('f_tujuan_id').value   = '';
    document.getElementById('f_tujuan_nama').value = '';
}


// ════════════════════════════════════════════════════════════════════
// FUNGSI: syncActiveTujuan()
// Baca tujuan aktif dari dropdown → simpan ke state & hidden field.
// ════════════════════════════════════════════════════════════════════
function syncActiveTujuan() {
    const sel = document.getElementById('selTujuan');
    const opt = sel.options[sel.selectedIndex]; // opsi yang sedang aktif
    if (!opt?.value) return; // keluar jika kosong

    // Simpan ke state global
    activeTujuan = {
        id    : String(opt.value),
        nama  : opt.dataset.nama   || '',
        lokasi: opt.dataset.lokasi || '',
        label : opt.dataset.label  || ''
    };

    // Sinkronkan ke hidden field
    document.getElementById('f_tujuan_id').value   = activeTujuan.id;
    document.getElementById('f_tujuan_nama').value = activeTujuan.label;
}


// ════════════════════════════════════════════════════════════════════
// FUNGSI: tampilPreview()
// Tampilkan card preview Asal → Tujuan dengan layanan yang dipilih.
// ════════════════════════════════════════════════════════════════════
function tampilPreview() {
    const selAsal = document.getElementById('selAsal');
    const asalOpt = selAsal.options[selAsal.selectedIndex];
    if (!asalOpt?.value || !activeTujuan.id) return; // butuh keduanya

    // Isi setiap elemen teks di card preview
    document.getElementById('pAsal').textContent      = asalOpt.dataset.nama   || '';
    document.getElementById('pAsalLok').textContent   = asalOpt.dataset.lokasi || '';
    document.getElementById('pTujuan').textContent    = activeTujuan.nama;
    document.getElementById('pTujuanLok').textContent = activeTujuan.lokasi;
    document.getElementById('pLayanan').textContent   = layananAktif || '—';

    document.getElementById('rutePrev').classList.add('show'); // tampilkan card
}


// ════════════════════════════════════════════════════════════════════
// FUNGSI: doSwap()
// Tukar posisi asal dan tujuan (rute sebaliknya).
// ════════════════════════════════════════════════════════════════════
function doSwap() {
    if (!activeTujuan.id) return; // tidak ada tujuan, batal

    const selAsal = document.getElementById('selAsal');

    // Cek apakah tujuan aktif tersedia sebagai opsi di dropdown asal
    if (!selAsal.querySelector(`option[value="${activeTujuan.id}"]`)) {
        showToast('⚠️ Rute sebaliknya belum tersedia.', 'warn');
        return;
    }

    selAsal.value = activeTujuan.id; // set asal ke tujuan sebelumnya
    onAsalChange();                  // fetch ulang tujuan dari asal baru
}


// ════════════════════════════════════════════════════════════════════
// FUNGSI: onLayananChange()
// Dipanggil saat layanan berubah.
// Refresh state penumpang dan hitung ulang estimasi biaya.
// ════════════════════════════════════════════════════════════════════
function onLayananChange() {
    layananAktif = document.getElementById('selLayanan').value; // simpan ke state

    document.getElementById('pLayanan').textContent = layananAktif || '—'; // update preview

    updatePaxState(); // refresh enable/disable penumpang sesuai layanan baru
    hitungHarga();    // hitung ulang estimasi biaya
}


// ════════════════════════════════════════════════════════════════════
// FUNGSI: onJenisChange()
// ★ FUNGSI KUNCI — dipanggil saat jenis pengguna berubah ★
//
// LOGIKA ANAK & BAYI (berdasarkan jenis):
//   PEJALAN KAKI ('penumpang'):
//     - Anak  → bebas, TIDAK perlu dewasa
//     - Bayi  → WAJIB ada minimal 1 dewasa
//   BERKENDARA ('kendaraan'):
//     - Anak  → WAJIB ada minimal 1 dewasa
//     - Bayi  → WAJIB ada minimal 1 dewasa
//
// LOGIKA KENDARAAN:
//   - Tampilkan area golongan & plat jika berkendara
//   - Sembunyikan jika pejalan kaki + reset semua data kendaraan
// ════════════════════════════════════════════════════════════════════
function onJenisChange() {
    const jenis = document.getElementById('selJenis').value; // jenis yang dipilih

    // Tampilkan/sembunyikan area golongan & plat nomor
    const showKendaraan = (jenis === 'kendaraan');
    document.getElementById('boxKendaraan').style.display = showKendaraan ? 'flex' : 'none';

    if (!showKendaraan) {
        // Beralih ke pejalan kaki: reset semua data kendaraan
        selectedGol = null; // hapus golongan dari state
        document.getElementById('f_golongan').value    = '';               // kosongkan hidden field golongan
        document.getElementById('lblGol').textContent  = 'Pilih Golongan'; // reset label tombol picker
        document.getElementById('btnGol').classList.remove('filled');      // hapus style "terisi"
        document.querySelectorAll('.gcard').forEach(c => c.classList.remove('selected')); // deselect kartu di grid
        document.getElementById('btnSimpanGol').disabled = true;           // nonaktifkan tombol simpan golongan

        // ★ Reset field plat ke kondisi default (tidak tersembunyi, nilai kosong) ★
        // Ini penting agar saat user balik ke berkendara, plat muncul kembali
        document.getElementById('boxPlat').style.display = 'block'; // tampilkan kolom plat
        document.getElementById('inpPlat').value         = '';       // kosongkan isi plat
        document.getElementById('inpPlat').removeAttribute('required'); // hapus validasi required
    }

    updatePaxState();  // refresh state enable/disable penumpang
    updateBatasInfo(); // refresh info kapasitas kendaraan
    hitungHarga();     // hitung ulang estimasi
}


// ════════════════════════════════════════════════════════════════════
// FUNGSI: updatePaxState()
// ★ FUNGSI INTI PENUMPANG ★
// Menentukan mana input penumpang yang aktif/terkunci berdasarkan:
//   1. Layanan sudah dipilih?
//   2. Jenis pengguna sudah dipilih?
//   3. Mode pejalan kaki atau berkendara?
//   4. Sudah ada dewasa? (untuk anak berkendara & semua bayi)
// ════════════════════════════════════════════════════════════════════
function updatePaxState() {
    const jenis      = document.getElementById('selJenis').value;
    const adaLayanan = !!layananAktif;         // true jika layanan sudah dipilih
    const adaJenis   = !!jenis;                // true jika jenis sudah dipilih
    const baseOk     = adaLayanan && adaJenis; // syarat dasar: keduanya harus ada
    const isPenumpang= (jenis === 'penumpang');// mode pejalan kaki?
    const adaDewasa  = dp.dewasa > 0;          // sudah ada penumpang dewasa?

    // ── DEWASA: aktif jika layanan & jenis sudah dipilih ──
    setItemEnabled('dewasa', baseOk);

    // ── ANAK:
    //   Pejalan kaki → tidak perlu dewasa, cukup syarat dasar
    //   Berkendara   → perlu dewasa (baseOk + adaDewasa)
    const anakOk = isPenumpang ? baseOk : (baseOk && adaDewasa);
    setItemEnabled('anak', anakOk);

    // ── BAYI: selalu butuh dewasa di semua mode ──
    const bayiOk = baseOk && adaDewasa;
    setItemEnabled('bayi', bayiOk);

    // ── Perbarui badge syarat di tiap card ──
    updateBadgeSyarat(jenis, adaDewasa, adaJenis);
}


// ════════════════════════════════════════════════════════════════════
// FUNGSI: setItemEnabled(type, enabled)
// Aktifkan atau nonaktifkan satu card penumpang secara lengkap.
// Mengubah: tombol +, tombol −, input angka, dan visual locked-pax.
// ════════════════════════════════════════════════════════════════════
function setItemEnabled(type, enabled) {
    document.getElementById('btn_p_' + type).disabled = !enabled; // tombol tambah
    document.getElementById('btn_m_' + type).disabled = !enabled; // tombol kurang
    document.getElementById('inp_'   + type).disabled = !enabled; // input angka
    // Terapkan/hapus class abu-abu (terkunci)
    document.getElementById('item_' + type).classList.toggle('locked-pax', !enabled);
}


// ════════════════════════════════════════════════════════════════════
// FUNGSI: updateBadgeSyarat(jenis, adaDewasa, adaJenis)
// Atur tampilan badge ⚠/✓ pada tiap card penumpang.
//
// ★ KEY: badge menggunakan visibility (bukan display:none) agar
//   tinggi card tidak berubah saat badge muncul/hilang.
//   Ini mencegah counter bergeser dan card tampak tidak sejajar.
//
// Dewasa  → badge selalu invisible (tidak punya syarat pendamping)
// Anak    →
//   Pejalan kaki → invisible (bebas, tidak perlu badge)
//   Berkendara   → kuning/hijau sesuai ada/tidaknya dewasa
// Bayi    →
//   Belum pilih jenis → invisible
//   Setelah pilih     → kuning/hijau (selalu butuh dewasa)
// ════════════════════════════════════════════════════════════════════
function updateBadgeSyarat(jenis, adaDewasa, adaJenis) {
    const anakBadge = document.getElementById('anakBadge'); // badge card anak
    const bayiBadge = document.getElementById('bayiBadge'); // badge card bayi
    // dewasaBadge selalu invisible (tidak ada syarat pendamping untuk dewasa)

    if (!adaJenis) {
        // Jenis belum dipilih: sembunyikan semua badge
        anakBadge.className = 'dep-badge invisible';
        bayiBadge.className = 'dep-badge invisible';
        return;
    }

    if (jenis === 'penumpang') {
        // ── MODE PEJALAN KAKI ──
        // Anak: bebas, tidak perlu badge syarat pendamping
        anakBadge.className = 'dep-badge invisible';

        // Bayi: tetap butuh dewasa, tampilkan badge
        bayiBadge.textContent = adaDewasa ? '✓ Pendamping ada' : '⚠ Perlu 1 dewasa';
        bayiBadge.className   = adaDewasa ? 'dep-badge ok'     : 'dep-badge';

    } else if (jenis === 'kendaraan') {
        // ── MODE BERKENDARA ──
        // Anak: butuh dewasa, tampilkan badge
        anakBadge.textContent = adaDewasa ? '✓ Pendamping ada' : '⚠ Perlu 1 dewasa';
        anakBadge.className   = adaDewasa ? 'dep-badge ok'     : 'dep-badge';

        // Bayi: butuh dewasa, tampilkan badge
        bayiBadge.textContent = adaDewasa ? '✓ Pendamping ada' : '⚠ Perlu 1 dewasa';
        bayiBadge.className   = adaDewasa ? 'dep-badge ok'     : 'dep-badge';
    }
}


// ════════════════════════════════════════════════════════════════════
// FUNGSI: getBatas()
// Kembalikan batas kapasitas dari golongan yang dipilih.
// Jika belum ada golongan, kembalikan 999 (praktis tidak terbatas).
// ════════════════════════════════════════════════════════════════════
function getBatas() {
    if (!selectedGol) return { maxTotal:999, maxDewasa:999, maxAnak:999, motorRule:false };
    return {
        maxTotal  : selectedGol.maxTotal  ?? 999, // maks semua penumpang
        maxDewasa : selectedGol.maxDewasa ?? 999, // maks dewasa
        maxAnak   : selectedGol.maxAnak   ?? 999, // maks anak
        motorRule : selectedGol.motorRule ?? false // aturan khusus motor?
    };
}


// ════════════════════════════════════════════════════════════════════
// FUNGSI: updateBatasInfo()
// Tampilkan info sisa kapasitas penumpang di bawah grid.
// Hanya tampil saat mode berkendara dan golongan sudah dipilih.
// ════════════════════════════════════════════════════════════════════
function updateBatasInfo() {
    const info  = document.getElementById('batasInfo');  // elemen info
    const lbl   = document.getElementById('batasLabel'); // label di heading penumpang
    const jenis = document.getElementById('selJenis').value;

    // Hanya tampil saat berkendara + golongan sudah dipilih
    if (jenis !== 'kendaraan' || !selectedGol) {
        info.style.display = 'none';
        lbl.textContent    = '';
        return;
    }

    const b     = getBatas();
    const total = dp.dewasa + dp.anak + dp.bayi; // total penumpang saat ini
    const sisa  = b.maxTotal - total;            // sisa kapasitas

    info.style.display = 'block';
    lbl.textContent    = `(Maks ${b.maxTotal} orang)`;

    let teks = `${selectedGol.label} — maks ${b.maxTotal} penumpang total`;
    if (b.motorRule) teks += ' | Maks 2 dewasa, 2 anak'; // tambahan aturan motor

    // Warna kuning jika penuh, normal jika masih ada sisa
    info.className   = sisa <= 0 ? 'batas-info warn' : 'batas-info';
    info.textContent = sisa <= 0
        ? '⚠️ Kapasitas penuh! ' + teks
        : `✅ Tersisa ${sisa} kursi — ` + teks;
}


// ════════════════════════════════════════════════════════════════════
// FUNGSI: paxTambah(t)
// Tambah 1 penumpang tipe t ('dewasa'/'anak'/'bayi').
// Validasi disesuaikan dengan mode jenis pengguna.
// ════════════════════════════════════════════════════════════════════
function paxTambah(t) {
    const jenis = document.getElementById('selJenis').value;

    // Syarat dasar: layanan & jenis harus sudah dipilih
    if (!layananAktif) { showToast('⚠️ Pilih layanan terlebih dahulu!','warn'); return; }
    if (!jenis)        { showToast('⚠️ Pilih jenis pengguna jasa!','warn');    return; }

    // BERKENDARA: anak & bayi wajib ada dewasa
    if (jenis==='kendaraan' && (t==='anak'||t==='bayi') && dp.dewasa<=0) {
        showToast(`${t==='anak'?'👦 Anak':'👶 Bayi'} wajib didampingi minimal 1 penumpang dewasa!`,'warn');
        highlightCard('item_dewasa'); // kedipkan card dewasa sebagai petunjuk visual
        return;
    }

    // PEJALAN KAKI: hanya bayi yang wajib ada dewasa
    if (jenis==='penumpang' && t==='bayi' && dp.dewasa<=0) {
        showToast('👶 Bayi wajib didampingi minimal 1 penumpang dewasa!','warn');
        highlightCard('item_dewasa'); // kedipkan card dewasa
        return;
    }

    // Validasi kapasitas golongan kendaraan
    const b     = getBatas();
    const total = dp.dewasa + dp.anak + dp.bayi; // total saat ini

    // Cek batas total
    if (total >= b.maxTotal) {
        showToast(`⚠️ Maks ${b.maxTotal} penumpang untuk ${selectedGol?selectedGol.label:'kendaraan ini'}!`,'warn');
        return;
    }

    // Cek batas per kategori
    if (t==='dewasa' && dp.dewasa>=b.maxDewasa) { showToast(`⚠️ Maks ${b.maxDewasa} dewasa!`,'warn'); return; }
    if (t==='anak'   && dp.anak  >=b.maxAnak)   { showToast(`⚠️ Maks ${b.maxAnak} anak!`,  'warn'); return; }

    dp[t]++;     // tambah hitungan kategori ini
    syncPaxUI(); // perbarui tampilan & hidden field
}


// ════════════════════════════════════════════════════════════════════
// FUNGSI: paxKurang(t)
// Kurangi 1 penumpang tipe t.
//
// ★ LOGIKA RESET SAAT DEWASA = 0:
//   BERKENDARA   → anak & bayi direset (keduanya butuh dewasa)
//   PEJALAN KAKI → hanya bayi direset (anak bebas tanpa dewasa)
// ════════════════════════════════════════════════════════════════════
function paxKurang(t) {
    if (dp[t] <= 0) return; // tidak bisa kurang dari 0
    dp[t]--; // kurangi 1

    const jenis = document.getElementById('selJenis').value;

    // Jika dewasa dikurangi hingga 0, periksa apakah anak/bayi perlu direset
    if (t==='dewasa' && dp.dewasa<=0) {
        let reset = []; // kumpulkan nama kategori yang direset

        if (jenis==='kendaraan') {
            // Berkendara: anak & bayi sama-sama butuh dewasa → reset keduanya
            if (dp.anak>0) { dp.anak=0; reset.push('anak'); }
            if (dp.bayi>0) { dp.bayi=0; reset.push('bayi'); }
        } else if (jenis==='penumpang') {
            // Pejalan kaki: anak bebas, hanya bayi yang perlu dewasa → reset bayi saja
            if (dp.bayi>0) { dp.bayi=0; reset.push('bayi'); }
        }

        // Beri tahu user kategori apa saja yang direset
        if (reset.length) {
            showToast(`⚠️ ${reset.join(' & ')} direset — tidak ada pendamping dewasa.`,'warn');
        }
    }

    syncPaxUI(); // perbarui tampilan
}


// ════════════════════════════════════════════════════════════════════
// FUNGSI: paxManual(t, val)
// Tangani input langsung angka pada field counter penumpang.
// Aturan validasi sama dengan paxTambah().
// ════════════════════════════════════════════════════════════════════
function paxManual(t, val) {
    const jenis = document.getElementById('selJenis').value;

    // Layanan & jenis harus ada dulu
    if (!layananAktif || !jenis) {
        document.getElementById('inp_'+t).value = 0; // kembalikan ke 0
        showToast('⚠️ Pilih layanan dan jenis pengguna jasa!','warn');
        return;
    }

    let n = parseInt(val, 10);      // parse input ke angka
    if (isNaN(n) || n<0) n = 0;    // pastikan non-negatif

    // BERKENDARA: anak/bayi tidak boleh diisi jika belum ada dewasa
    if (jenis==='kendaraan' && (t==='anak'||t==='bayi') && n>0 && dp.dewasa<=0) {
        showToast(`${t==='anak'?'👦 Anak':'👶 Bayi'} wajib didampingi minimal 1 penumpang dewasa!`,'warn');
        document.getElementById('inp_'+t).value = 0; // kembalikan ke 0
        dp[t] = 0;
        syncPaxUI();
        return;
    }

    // PEJALAN KAKI: bayi tidak boleh diisi jika belum ada dewasa
    if (jenis==='penumpang' && t==='bayi' && n>0 && dp.dewasa<=0) {
        showToast('👶 Bayi wajib didampingi minimal 1 penumpang dewasa!','warn');
        document.getElementById('inp_bayi').value = 0;
        dp.bayi = 0;
        syncPaxUI();
        return;
    }

    // Batasi sesuai kapasitas golongan
    const b = getBatas();
    if (t==='dewasa') n = Math.min(n, b.maxDewasa); // tidak boleh melebihi maks dewasa
    if (t==='anak')   n = Math.min(n, b.maxAnak);   // tidak boleh melebihi maks anak

    // Cek total penumpang baru tidak melebihi kapasitas
    const totalBaru = (t==='dewasa'?n:dp.dewasa) + (t==='anak'?n:dp.anak) + (t==='bayi'?n:dp.bayi);
    if (totalBaru > b.maxTotal) {
        n -= (totalBaru - b.maxTotal); // pangkas agar tidak melebihi kapasitas
        if (n<0) n=0;
        showToast(`⚠️ Total melebihi kapasitas (maks ${b.maxTotal})!`,'warn');
    }

    dp[t] = n; // simpan nilai ke state

    // Jika dewasa di-nol-kan via input manual, reset anak/bayi yang bergantung
    if (t==='dewasa' && n<=0) {
        let reset=[];
        if (jenis==='kendaraan') {
            if (dp.anak>0){dp.anak=0;reset.push('anak');}
            if (dp.bayi>0){dp.bayi=0;reset.push('bayi');}
        } else if (jenis==='penumpang') {
            if (dp.bayi>0){dp.bayi=0;reset.push('bayi');}
        }
        if (reset.length) showToast(`⚠️ ${reset.join(' & ')} direset — tidak ada pendamping.`,'warn');
    }

    syncPaxUI(); // perbarui tampilan
}


// ════════════════════════════════════════════════════════════════════
// FUNGSI: highlightCard(id)
// Efek kedip kuning pada card untuk mengarahkan perhatian user.
// Dipakai untuk menunjuk card dewasa saat anak/bayi gagal ditambah.
// ════════════════════════════════════════════════════════════════════
function highlightCard(id) {
    const el = document.getElementById(id); // ambil elemen card
    if (!el) return;

    // Terapkan border & latar kuning
    el.style.border     = '1.5px solid rgba(251,191,36,.6)';
    el.style.background = 'rgba(251,191,36,.06)';

    // Hapus highlight setelah 1.5 detik
    setTimeout(() => { el.style.border=''; el.style.background=''; }, 1500);
}


// ════════════════════════════════════════════════════════════════════
// FUNGSI: syncPaxUI()
// Sinkronkan tampilan angka di tiap card dan hidden field
// dengan state dp. Dipanggil setiap kali dp berubah.
// ════════════════════════════════════════════════════════════════════
function syncPaxUI() {
    // Update nilai di tiap input angka counter
    ['dewasa','anak','bayi'].forEach(t => { document.getElementById('inp_'+t).value = dp[t]; });

    const total = dp.dewasa + dp.anak + dp.bayi; // total semua kategori

    // Update hidden field yang dikirim ke server saat submit
    document.getElementById('f_dewasa').value    = dp.dewasa;
    document.getElementById('f_anak').value      = dp.anak;
    document.getElementById('f_bayi').value      = dp.bayi;
    document.getElementById('f_total_pax').value = total;

    // ★ Refresh state enable/disable & badge setiap kali penumpang berubah ★
    updatePaxState();
    updateBatasInfo();
    hitungHarga();
}


// ════════════════════════════════════════════════════════════════════
// RENDER GRID PILIHAN GOLONGAN KENDARAAN
// Dibuat dinamis dari array golData di atas.
// ════════════════════════════════════════════════════════════════════
const golGrid = document.getElementById('golGrid'); // container grid
golData.forEach(g => {
    const lbl = document.createElement('label'); // setiap kartu adalah label
    lbl.className = 'gcard';
    lbl.innerHTML = `
        <input type="radio" name="gd" value="${g.id}">
        <div class="gbadge">${g.label}</div>
        <div class="gname">${g.name}</div>
        <div class="gdesc">${g.desc}</div>
        <div class="gmax">👥 Maks ${g.maxTotal} penumpang${g.motorRule?' (2D+2A)':''}${g.noPlat?' | 🚲 Tanpa plat':''}</div>
        <div class="gcheck">✓</div>`;

    // Listener klik: pilih kartu dan simpan golongan ke state
    lbl.addEventListener('click', () => {
        document.querySelectorAll('.gcard').forEach(c => c.classList.remove('selected')); // deselect semua
        lbl.classList.add('selected');              // tandai kartu ini sebagai terpilih
        lbl.querySelector('input').checked = true;  // centang radio input
        selectedGol = g;                            // simpan golongan ke state global
        document.getElementById('btnSimpanGol').disabled = false; // aktifkan tombol simpan
    });
    golGrid.appendChild(lbl); // tambahkan kartu ke grid
});


// ════════════════════════════════════════════════════════════════════
// FUNGSI: simpanGol()
// Dipanggil saat tombol "Simpan Pilihan Kendaraan" diklik di modal.
//
// ★ LOGIKA PLAT SEPEDA (KUNCI UTAMA PERUBAHAN) ★
// ═══════════════════════════════════════════════
// Masalah: database mewajibkan kolom "plat" terisi, tapi sepeda
//          tidak punya plat nomor.
//
// Solusi yang diterapkan di sini:
//   1. Jika golongan = Gol I (noPlat:true):
//      → SEMBUNYIKAN kolom plat agar user tidak perlu mengisi manual
//      → OTOMATIS isi inpPlat dengan nilai "SEPEDA"
//      → Field plat (tersembunyi) tetap ikut terkirim ke server
//        sehingga database menerima nilai "SEPEDA" di kolom plat
//      → Tidak perlu required karena sudah otomatis terisi
//
//   2. Jika golongan lain (kendaraan bermotor/bus/truk):
//      → TAMPILKAN kolom plat
//      → Kosongkan isi plat (user harus isi manual)
//      → Pasang required (wajib diisi sebelum submit)
// ════════════════════════════════════════════════════════════════════
function simpanGol() {
    if (!selectedGol) return; // belum ada pilihan, keluar

    // Simpan ID golongan ke hidden field
    document.getElementById('f_golongan').value = selectedGol.id;

    // Update label tombol picker golongan
    document.getElementById('lblGol').textContent = selectedGol.label + ' — ' + selectedGol.name;
    document.getElementById('btnGol').classList.add('filled'); // beri style "terisi"

    const isSepeda = selectedGol.noPlat === true; // cek apakah Golongan I (Sepeda)
    const boxPlat  = document.getElementById('boxPlat');  // elemen kolom plat
    const inpPlat  = document.getElementById('inpPlat');  // elemen input plat

    if (isSepeda) {
        // ── GOL I SEPEDA: sembunyikan kolom, isi otomatis "SEPEDA" ──
        boxPlat.style.display = 'none';         // sembunyikan kolom plat dari tampilan
        inpPlat.value         = 'SEPEDA';        // ★ ISI OTOMATIS dengan "SEPEDA" ★
        inpPlat.removeAttribute('required');     // hapus required (sudah terisi otomatis)
        // CATATAN: inpPlat TETAP ada di DOM (hanya tersembunyi via CSS),
        // sehingga nilai "SEPEDA" tetap ikut terkirim ke server saat form submit.
    } else {
        // ── GOLONGAN LAIN: tampilkan kolom, user isi manual ──
        boxPlat.style.display = 'block';         // tampilkan kolom plat kembali
        inpPlat.value         = '';              // kosongkan (agar user isi fresh)
        inpPlat.setAttribute('required', '');    // wajib diisi sebelum submit
    }

    // Pangkas jumlah penumpang jika melebihi batas golongan baru
    const b = getBatas();
    if (dp.dewasa > b.maxDewasa) dp.dewasa = b.maxDewasa; // kurangi dewasa jika lebih
    if (dp.anak   > b.maxAnak)   dp.anak   = b.maxAnak;   // kurangi anak jika lebih

    // Kurangi bayi/anak jika total masih melebihi batas
    let tot = dp.dewasa + dp.anak + dp.bayi;
    if (tot > b.maxTotal) {
        dp.bayi = Math.max(0, dp.bayi-(tot-b.maxTotal)); // kurangi bayi dulu
        tot = dp.dewasa + dp.anak + dp.bayi;
        if (tot > b.maxTotal) dp.anak = Math.max(0, dp.anak-(tot-b.maxTotal)); // lalu anak
    }

    syncPaxUI();       // perbarui tampilan counter
    updateBatasInfo(); // perbarui info kapasitas
    hitungHarga();     // hitung ulang estimasi harga
}


// ════════════════════════════════════════════════════════════════════
// FUNGSI: hitungHarga()
// Hitung estimasi total biaya dari tarif penumpang + kendaraan.
// Bayi gratis, dewasa & anak membayar sesuai tarif.
// ════════════════════════════════════════════════════════════════════
function hitungHarga() {
    const layanan  = document.getElementById('selLayanan').value; // layanan aktif
    const jenis    = document.getElementById('selJenis').value;   // jenis pengguna
    const golongan = document.getElementById('f_golongan').value; // ID golongan
    const tid      = activeTujuan.id;                             // ID tujuan aktif

    const paxBayar = dp.dewasa + dp.anak; // kategori yang membayar
    const paxTotal = paxBayar + dp.bayi;  // total termasuk bayi (gratis)

    // Sembunyikan estimasi jika data belum lengkap
    if (!tid || !layanan || paxTotal===0) {
        document.getElementById('hargaBox').classList.remove('show');
        document.getElementById('f_total_harga').value = 0;
        return;
    }

    const hargaRute = hasilHarga[tid]; // data harga rute dari API
    if (!hargaRute) { document.getElementById('hargaBox').classList.remove('show'); return; }

    const hargaPax = parseInt(hargaRute[layanan]??0, 10) || 0; // tarif per orang
    let html = '';
    let totalPax = hargaPax * paxBayar; // subtotal penumpang bayar

    // Baris detail per kategori penumpang
    if (dp.dewasa) html += row(`Dewasa × ${dp.dewasa}`, fmt(hargaPax*dp.dewasa)); // baris dewasa
    if (dp.anak)   html += row(`Anak × ${dp.anak}`,     fmt(hargaPax*dp.anak));   // baris anak
    if (dp.bayi)   html += `<div class="h-row"><span class="lbl">Bayi × ${dp.bayi}</span><span class="val" style="color:#4ade80;">Gratis</span></div>`; // bayi gratis

    // Baris keterangan tarif per orang
    html += `<div class="h-row" style="font-size:12px;color:#475569;border-top:1px solid rgba(255,255,255,.04);padding-top:6px;margin-top:2px;">
        <span class="lbl">Tarif per orang (${layanan})</span><span class="val">${fmt(hargaPax)}</span></div>`;

    let biayaKend = 0; // biaya kendaraan, default 0

    // Tambahkan baris kendaraan jika berkendara dan golongan sudah dipilih
    if (jenis==='kendaraan' && golongan) {
        const golObj  = golData.find(g=>g.id===golongan); // cari objek golongan dari array
        const golLbl  = golObj ? `${golObj.label} — ${golObj.name}` : golongan;
        const dataGol = (hasilHargaKend[tid]||{})[golongan]; // tarif kendaraan dari API

        html += `<div class="h-row" style="border-top:1px solid rgba(255,255,255,.06);margin-top:8px;padding-top:8px;">
            <span class="lbl">🚗 Kendaraan (${golLbl})</span>`;

        if (dataGol!==undefined && dataGol!==null) {
            biayaKend = parseInt(dataGol[layanan]??0,10)||0; // ambil tarif kendaraan
            html += `<span class="val">${biayaKend===0?'<span style="color:#4ade80;">Gratis</span>':fmt(biayaKend)}</span>`;
        } else {
            html += `<span class="val" style="color:#f59e0b;">Hubungi admin</span>`; // tarif belum ada
        }
        html += `</div>`;
    }

    const grand = totalPax + biayaKend; // grand total keseluruhan

    // Tampilkan estimasi biaya
    document.getElementById('hargaDetail').innerHTML  = html;         // detail per item
    document.getElementById('hargaTotal').textContent = fmt(grand);   // total tampilan
    document.getElementById('hargaBox').classList.add('show');        // tampilkan box
    document.getElementById('f_total_harga').value    = grand;        // simpan ke hidden field
}

// Helper: buat baris estimasi harga (label + nilai)
function row(lbl, val) { return `<div class="h-row"><span class="lbl">${lbl}</span><span class="val">${val}</span></div>`; }

// Helper: format angka ke string rupiah (mis. 50000 → "Rp 50.000")
function fmt(n) { return 'Rp '+(parseInt(n,10)||0).toLocaleString('id-ID'); }


// ════════════════════════════════════════════════════════════════════
// FUNGSI: bukaModalSyarat(e)
// Validasi seluruh form sebelum submit, lalu buka modal syarat.
// Return false = cegah submit default HTML.
//
// URUTAN VALIDASI:
//   1. Pelabuhan asal ada?
//   2. Tujuan tersedia?
//   3. Tanggal dipilih & tidak di masa lalu?
//   4. Jam dipilih & valid (tidak < 1 jam dari sekarang)?
//   5. Layanan dipilih?
//   6. Ada minimal 1 penumpang?
//   7. Jenis pengguna dipilih?
//   8. Bayi ada dewasa? (semua mode)
//   9. Jika berkendara:
//      a. Anak ada dewasa?
//      b. Golongan dipilih?
//      c. Plat diisi? (kecuali Sepeda: sudah otomatis "SEPEDA")
//      d. Total tidak melebihi kapasitas?
//      e. Aturan motor terpenuhi?
//  10. Buka modal syarat
// ════════════════════════════════════════════════════════════════════
function bukaModalSyarat(e) {
    e.preventDefault(); // cegah submit default HTML

    const jenis     = document.getElementById('selJenis').value;
    const golongan  = document.getElementById('f_golongan').value;
    const plat      = document.getElementById('inpPlat')?.value.trim() ?? '';
    const total_pax = dp.dewasa + dp.anak + dp.bayi;

    // ── Validasi field wajib ──
    if (!document.getElementById('f_asal_id').value)   { showToast('⚠️ Pilih pelabuhan asal!',   'warn'); return false; }
    if (!document.getElementById('f_tujuan_id').value) { showToast('⚠️ Tujuan belum tersedia!',  'warn'); return false; }
    if (!document.getElementById('inpTanggal').value)  { showToast('⚠️ Pilih tanggal!',          'warn'); return false; }

    // Tanggal tidak boleh sebelum hari ini
    if (document.getElementById('inpTanggal').value < getTodayStr()) {
        showToast('⚠️ Tanggal tidak boleh sebelum hari ini!','warn'); return false;
    }

    if (!document.getElementById('selJam').value) { showToast('⚠️ Pilih jam check-in!','warn'); return false; }

    // Validasi jam ulang jika hari ini (slot bisa expired saat user lambat)
    if (isToday(document.getElementById('inpTanggal').value)) {
        const {h:nowH, m:nowM} = getNowHM();
        const jamH = parseInt(document.getElementById('selJam').value.split(':')[0], 10);
        if ((jamH*60)-(nowH*60+nowM) <= 60) {
            showToast('⛔ Jam tidak valid. Minimal pesan H-1 jam.','warn');
            populateJam(document.getElementById('inpTanggal').value); // refresh dropdown jam
            return false;
        }
    }

    if (!document.getElementById('selLayanan').value) { showToast('⚠️ Pilih layanan!',            'warn'); return false; }
    if (total_pax <= 0)                               { showToast('⚠️ Pilih minimal 1 penumpang!', 'warn'); return false; }
    if (!jenis)                                       { showToast('⚠️ Pilih jenis pengguna jasa!', 'warn'); return false; }

    // ── Bayi selalu wajib ada dewasa di semua mode ──
    if (dp.bayi>0 && dp.dewasa<=0) { showToast('👶 Bayi wajib didampingi minimal 1 penumpang dewasa!','warn'); return false; }

    if (jenis==='kendaraan') {
        // ── Berkendara: anak juga wajib ada dewasa ──
        if (dp.anak>0 && dp.dewasa<=0) { showToast('👦 Anak wajib didampingi minimal 1 penumpang dewasa!','warn'); return false; }

        // ── Golongan harus dipilih ──
        if (!golongan) { showToast('⚠️ Pilih golongan kendaraan!','warn'); return false; }

        // ── Validasi plat: LEWATI jika Sepeda (sudah terisi "SEPEDA" otomatis) ──
        const isSepeda = selectedGol && selectedGol.noPlat === true; // cek flag sepeda

        if (!isSepeda && !plat) {
            // Bukan sepeda dan plat kosong: wajib diisi user
            showToast('⚠️ Masukkan plat nomor!','warn'); return false;
        }
        // Jika sepeda: plat sudah otomatis "SEPEDA" → tidak perlu validasi, langsung lolos

        const b = getBatas();

        // Total tidak boleh melebihi kapasitas golongan
        if (total_pax > b.maxTotal) { showToast(`⚠️ Maks ${b.maxTotal} penumpang untuk ${selectedGol.label}!`,'warn'); return false; }

        // Aturan motor (Gol II & III): maks 2 dewasa + 2 anak
        if (b.motorRule && dp.dewasa>2) { showToast('⚠️ Motor maks 2 dewasa!','warn'); return false; }
        if (b.motorRule && dp.anak  >2) { showToast('⚠️ Motor maks 2 anak!',  'warn'); return false; }
    }
    // Pejalan kaki: tidak ada validasi kendaraan → langsung lanjut ke modal syarat

    // ── Sinkronkan hidden field sebelum membuka modal ──
    syncActiveTujuan(); // pastikan tujuan tersinkron
    document.getElementById('f_dewasa').value    = dp.dewasa;                    // jumlah dewasa
    document.getElementById('f_anak').value      = dp.anak;                      // jumlah anak
    document.getElementById('f_bayi').value      = dp.bayi;                      // jumlah bayi
    document.getElementById('f_total_pax').value = dp.dewasa + dp.anak + dp.bayi; // total penumpang

    // ── Reset modal syarat ke kondisi awal ──
    document.getElementById('chkSyarat').checked    = false;      // uncheck checkbox
    document.getElementById('btnSetuju').disabled   = true;       // nonaktifkan tombol setuju
    document.getElementById('syaratBody').scrollTop = 0;          // scroll ke atas
    document.getElementById('scrollNote').textContent = '↓ Gulir ke bawah untuk membaca semua ketentuan';
    document.getElementById('scrollNote').style.color  = '#64748b'; // reset warna teks scroll

    // ── Tampilkan modal syarat ──
    new bootstrap.Modal(document.getElementById('modalSyarat')).show();
    return false; // cegah submit form sampai user setujui syarat
}


// ════════════════════════════════════════════════════════════════════
// FUNGSI-FUNGSI MODAL SYARAT & KETENTUAN
// ════════════════════════════════════════════════════════════════════

// Cek apakah user sudah scroll ke bawah (toleransi 10px)
// Jika sudah: ubah teks scroll-note jadi hijau ✓
function cekScroll() {
    const el = document.getElementById('syaratBody');
    if (el.scrollTop+el.clientHeight >= el.scrollHeight-10) {
        document.getElementById('scrollNote').textContent = '✓ Anda telah membaca seluruh ketentuan';
        document.getElementById('scrollNote').style.color = '#4ade80'; // hijau
    }
}

// Toggle checkbox saat area klik (bukan checkbox langsung) ditekan
function toggleSyarat() {
    const cb=document.getElementById('chkSyarat');
    cb.checked=!cb.checked; // balik status centang
    updateBtnSetuju();       // update tombol setuju
}

// Update disabled/enabled tombol "Setuju & Lanjut" sesuai checkbox
function updateBtnSetuju() {
    document.getElementById('btnSetuju').disabled = !document.getElementById('chkSyarat').checked;
}

// Reset checkbox saat tombol "Batal" diklik
function batalSyarat() {
    document.getElementById('chkSyarat').checked  = false; // uncheck
    document.getElementById('btnSetuju').disabled = true;  // nonaktifkan tombol
}

// Submit form setelah user menyetujui syarat
function setujuLanjut() {
    if (!document.getElementById('chkSyarat').checked) return; // double-check

    bootstrap.Modal.getInstance(document.getElementById('modalSyarat')).hide(); // tutup modal

    // Delay 300ms agar animasi close modal selesai sebelum submit
    setTimeout(() => document.getElementById('formBeli').submit(), 300);
}


// ════════════════════════════════════════════════════════════════════
// AUTO-REFRESH JAM SETIAP 5 DETIK
// Memperbarui dropdown jam jika tanggal = hari ini,
// agar slot yang baru saja terlewat otomatis dikunci.
// ════════════════════════════════════════════════════════════════════
setInterval(() => {
    const tgl = document.getElementById('inpTanggal').value;
    if (!tgl || !isToday(tgl)) return; // hanya refresh jika hari ini

    const prev = document.getElementById('selJam').value; // simpan pilihan sebelumnya
    populateJam(tgl); // rebuild dropdown

    if (prev) {
        const opt = document.getElementById('selJam').querySelector(`option[value="${prev}"]`);
        if (opt && !opt.disabled) {
            // Jam sebelumnya masih valid: pertahankan pilihan user
            document.getElementById('selJam').value = prev;
        } else if (opt?.disabled) {
            // Jam sudah tidak valid: reset dan beri peringatan
            document.getElementById('selJam').value = '';
            document.getElementById('jamAlert').classList.add('show','err');
            document.getElementById('jamAlertText').textContent = '⛔ Jam yang dipilih sudah lewat!';
        }
    }
}, 5000); // interval 5 detik


// ════════════════════════════════════════════════════════════════════
// INISIALISASI
// Nonaktifkan semua input penumpang saat halaman pertama dimuat.
// Penumpang baru bisa diisi setelah layanan & jenis pengguna dipilih.
// ════════════════════════════════════════════════════════════════════
updatePaxState(); // jalankan sekali saat load untuk set kondisi awal
</script>
</body>
</html>