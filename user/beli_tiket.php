<?php
session_start();
include('../config/koneksi.php');
if(!isset($_SESSION['user_id'])){ header("Location: login.php"); exit; }
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
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Poppins',sans-serif;color:white;min-height:100vh;overflow-x:hidden}
.bubble{position:fixed;bottom:-80px;border-radius:50%;background:rgba(255,255,255,0.05);animation:bup 12s infinite;pointer-events:none;z-index:0}
@keyframes bup{0%{transform:translateY(0) scale(1);opacity:.5}100%{transform:translateY(-110vh) scale(1.8);opacity:0}}
.topbar{background:var(--navy-topbar);backdrop-filter:blur(14px);border-bottom:1px solid rgba(255,255,255,.07);padding:14px 28px;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:300}
.brand-logo{height:40px;width:auto;object-fit:contain;display:block}
.brand{text-decoration:none;display:inline-flex;align-items:center;gap:10px}
.brand-name{font-size:22px;font-weight:700;color:white;letter-spacing:1.5px}
.btn-back{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:rgba(255,255,255,.75);border-radius:10px;padding:8px 18px;font-size:13px;text-decoration:none;font-family:'Poppins',sans-serif;transition:.2s}
.btn-back:hover{background:rgba(255,255,255,.12);color:white}
.hero{text-align:center;padding:48px 20px 80px;position:relative;z-index:1}
.hero h2{font-weight:700;font-size:28px;background:linear-gradient(to right,#38bdf8,#22d3ee);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.hero p{color:#64748b;font-size:14px;margin-top:6px}
.card-main{background:rgba(255,255,255,.04);backdrop-filter:blur(20px);border-radius:24px;padding:32px;margin-top:-52px;border:1px solid rgba(255,255,255,.09);box-shadow:0 0 60px rgba(0,0,0,.6);position:relative;z-index:1}
.flabel{font-size:11px;color:#64748b;margin-bottom:6px;display:block;text-transform:uppercase;letter-spacing:.8px;font-weight:600}
.form-control,.form-select{background:rgba(255,255,255,.06)!important;border:1px solid rgba(255,255,255,.1)!important;color:white!important;border-radius:12px!important;padding:11px 14px!important;font-family:'Poppins',sans-serif;font-size:14px;transition:.3s}
.form-control:focus,.form-select:focus{border-color:#38bdf8!important;box-shadow:0 0 0 3px rgba(56,189,248,.15)!important;outline:none}
.form-select option{background:#0f172a;color:white}
.form-select option:disabled{color:#475569 !important;background:#1e293b !important}
.form-control::placeholder{color:rgba(255,255,255,.25)}
.form-control:disabled,.form-select:disabled{opacity:.4!important;cursor:not-allowed}
input[type="date"]::-webkit-calendar-picker-indicator,input[type="time"]::-webkit-calendar-picker-indicator{filter:invert(1);opacity:.4}
.form-select.locked{opacity:.85!important;cursor:not-allowed;border-color:rgba(56,189,248,.35)!important;background:rgba(56,189,248,.07)!important}
.locked-badge{display:none;font-size:10px;color:#38bdf8;background:rgba(56,189,248,.1);border:1px solid rgba(56,189,248,.2);border-radius:6px;padding:2px 8px;margin-left:6px}
.locked-badge.show{display:inline-block}
.swap-btn{background:rgba(56,189,248,.1);border:1px solid rgba(56,189,248,.2);border-radius:50%;width:36px;height:36px;color:#38bdf8;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:.3s;align-self:flex-end;margin-bottom:2px}
.swap-btn:hover:not(:disabled){background:rgba(56,189,248,.25);transform:rotate(180deg)}
.swap-btn:disabled{opacity:.25;cursor:not-allowed}
.picker-btn{background:rgba(255,255,255,.06)!important;border:1px solid rgba(255,255,255,.1)!important;border-radius:12px!important;padding:11px 14px!important;color:white!important;font-family:'Poppins',sans-serif;font-size:14px;width:100%;cursor:pointer;text-align:left;display:flex;align-items:center;justify-content:space-between;transition:.3s}
.picker-btn:hover{border-color:#38bdf8!important}
.picker-btn.filled{border-color:rgba(56,189,248,.4)!important;background:rgba(56,189,248,.07)!important}
.rute-prev{background:rgba(56,189,248,.05);border:1px solid rgba(56,189,248,.15);border-radius:14px;padding:16px 20px;display:none;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:4px}
.rute-prev.show{display:flex}
.rp-port{text-align:center}
.rp-port .kota{font-size:14px;font-weight:700;color:white}
.rp-port .lok{font-size:11px;color:#64748b;margin-top:2px}
.rp-arrow{font-size:22px;color:#38bdf8;flex-shrink:0}
.rp-tag{background:rgba(56,189,248,.12);border:1px solid rgba(56,189,248,.2);border-radius:20px;padding:3px 12px;font-size:11px;color:#38bdf8;font-weight:600;margin-left:auto}

/* ── JAM ALERT ── */
.jam-alert{display:none;align-items:center;gap:10px;background:rgba(251,191,36,.07);border:1px solid rgba(251,191,36,.25);border-radius:10px;padding:10px 14px;margin-top:8px;font-size:12px;color:#fbbf24}
.jam-alert.show{display:flex}
.jam-alert.err{background:rgba(239,68,68,.07);border-color:rgba(239,68,68,.25);color:#f87171}
.jam-alert svg{flex-shrink:0;width:16px;height:16px}

/* Batas info */
.batas-info{font-size:11px;color:#64748b;margin-top:6px;padding:8px 12px;background:rgba(255,255,255,.03);border-radius:8px;border:1px solid rgba(255,255,255,.06)}
.batas-info.warn{color:#fbbf24;border-color:rgba(251,191,36,.2);background:rgba(251,191,36,.05)}

/* Counter penumpang */
.pax-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:8px}
.pax-item{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:12px 10px;text-align:center}
.pax-item .p-lbl{font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px}
.pax-item .p-ket{font-size:10px;color:#334155;margin-bottom:10px}
.pax-ctr{display:flex;align-items:center;justify-content:center;gap:8px}
.pax-ctr button{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);color:white;width:28px;height:28px;border-radius:7px;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:.2s;line-height:1}
.pax-ctr button:hover:not(:disabled){background:rgba(56,189,248,.15);border-color:#38bdf8}
.pax-ctr button:disabled{opacity:.25;cursor:not-allowed}
.pax-ctr input{width:44px;text-align:center;font-weight:700;font-size:16px;background:transparent;border:none;color:white;outline:none;font-family:'Poppins',sans-serif}

/* Harga */
.harga-box{background:rgba(56,189,248,.04);border:1px solid rgba(56,189,248,.12);border-radius:16px;padding:18px 22px;display:none}
.harga-box.show{display:block}
.h-row{display:flex;justify-content:space-between;padding:7px 0;font-size:14px}
.h-row .lbl{color:#64748b}
.h-row .val{color:white;font-weight:500}
.h-total{border-top:1px dashed rgba(56,189,248,.2);margin-top:10px;padding-top:12px}
.h-total .lbl{color:#38bdf8;font-weight:600;font-size:15px}
.h-total .val{color:#38bdf8;font-weight:700;font-size:22px}
.fdiv{border:none;border-top:1px solid rgba(255,255,255,.06);margin:20px 0}
.btn-submit{background:linear-gradient(135deg,#06b6d4,#3b82f6);border:none;border-radius:50px;color:white;font-family:'Poppins',sans-serif;font-weight:700;font-size:14px;letter-spacing:1px;text-transform:uppercase;padding:14px 40px;transition:.3s;cursor:pointer;width:100%}
.btn-submit:hover{transform:scale(1.02);box-shadow:0 0 30px rgba(56,189,248,.4)}
.btn-submit:disabled{opacity:.4;cursor:not-allowed;transform:none;box-shadow:none}
.modal-content{background:rgba(5,12,30,.98)!important;border:1px solid rgba(255,255,255,.1)!important;border-radius:22px!important;backdrop-filter:blur(20px);color:white}
.mclosebtn{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);border-radius:8px;width:30px;height:30px;color:#94a3b8;cursor:pointer;font-size:15px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
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
@media(max-width:768px){.card-main{padding:20px}.gol-grid{grid-template-columns:repeat(2,1fr)}.pax-grid{grid-template-columns:1fr 1fr}.topbar{padding:12px 16px}.brand-name{font-size:18px}.brand-logo{height:34px}.btn-back{padding:8px 12px;font-size:12px}.hero{padding:32px 16px 60px}.hero h2{font-size:22px}}
@media(max-width:480px){.gol-grid{grid-template-columns:1fr}.pax-grid{grid-template-columns:1fr}.brand-name{font-size:16px}}
</style>
</head>
<body class="bg-navy-animated">

<?php for($i=0;$i<6;$i++): ?>
<div class="bubble" style="left:<?= $i*17+4 ?>%;width:<?= 14+$i*4 ?>px;height:<?= 14+$i*4 ?>px;animation-delay:<?= $i*2 ?>s;animation-duration:<?= 11+$i*2 ?>s;"></div>
<?php endfor; ?>

<div class="topbar">
    <a href="../index.php" class="brand">
        <img src="../assets/logo.png" alt="Logo" class="brand-logo">
        <span class="brand-name">NAVIRA</span>
    </a>
    <a href="dashboard.php" class="btn-back">← Dashboard</a>
</div>

<div class="hero">
    <h2>🌊 Pemesanan Tiket Kapal</h2>
    <p>Pilih pelabuhan asal — tujuan otomatis terpilih</p>
</div>

<div class="container pb-5" style="position:relative;z-index:1;">
<div class="card-main">

<form id="formBeli" action="simpan_tiket.php" method="POST" onsubmit="return bukaModalSyarat(event)">

    <!-- ══ HIDDEN FIELDS ══ -->
    <input type="hidden" name="asal_id"          id="f_asal_id">
    <input type="hidden" name="tujuan_id"        id="f_tujuan_id">
    <input type="hidden" name="asal_nama"        id="f_asal_nama">
    <input type="hidden" name="tujuan_nama"      id="f_tujuan_nama">
    <input type="hidden" name="dewasa"           id="f_dewasa"  value="0">
    <input type="hidden" name="anak"             id="f_anak"    value="0">
    <input type="hidden" name="bayi"             id="f_bayi"    value="0">
    <input type="hidden" name="total_penumpang"  id="f_total_pax"   value="0">
    <input type="hidden" name="total_harga"      id="f_total_harga" value="0">
    <input type="hidden" name="golongan_kendaraan" id="f_golongan">

    <!-- BARIS 1: RUTE + LAYANAN -->
    <div class="row g-3 mb-3">
        <div class="col-md-5">
            <label class="flabel">Pelabuhan Asal</label>
            <div class="d-flex gap-2 align-items-end">
                <select id="selAsal" class="form-select flex-grow-1" required onchange="onAsalChange()">
                    <option value="">⚓ Pilih Pelabuhan Asal</option>
                </select>
                <button type="button" class="swap-btn" id="swapBtn" onclick="doSwap()" disabled>⇅</button>
            </div>
        </div>
        <div class="col-md-5">
            <label class="flabel">
                Pelabuhan Tujuan
                <span class="locked-badge" id="lockedBadge">🔒 Otomatis</span>
            </label>
            <select id="selTujuan" class="form-select locked" disabled>
                <option value="">— Pilih asal dulu —</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="flabel">Layanan</label>
            <select name="layanan" id="selLayanan" class="form-select" onchange="onLayananChange()">
                <option value="">— Pilih —</option>
                <option value="reguler">🪑 Reguler</option>
                <option value="express">⚡ Express</option>
            </select>
        </div>
    </div>

    <!-- RUTE PREVIEW -->
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

    <!-- BARIS 2: TANGGAL + JAM + JENIS -->
    <div class="row g-3 mb-1">
        <div class="col-md-4">
            <label class="flabel">Tanggal Keberangkatan</label>
            <input type="date" name="tanggal" id="inpTanggal" class="form-control" required
                min="<?= date('Y-m-d') ?>" onchange="onTanggalChange()">
        </div>
        <div class="col-md-3">
            <label class="flabel">Jam Check-In</label>
            <select name="jam" id="selJam" class="form-select" required onchange="onJamChange()">
                <option value="">— Pilih Tanggal Dulu —</option>
            </select>
            <!-- Alert jam sudah lewat / peringatan -->
            <div class="jam-alert" id="jamAlert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span id="jamAlertText"></span>
            </div>
        </div>
        <div class="col-md-5">
            <label class="flabel">Jenis Pengguna Jasa</label>
            <select name="jenis_pengguna" id="selJenis" class="form-select" onchange="toggleKendaraan()">
                <option value="">— Pilih Jenis —</option>
                <option value="penumpang">🚶 Pejalan Kaki</option>
                <option value="kendaraan">🚗 Berkendara</option>
            </select>
        </div>
    </div>

    <!-- BARIS 3: GOLONGAN + PLAT (kondisional) -->
    <div class="row g-3 mb-3" id="boxKendaraan" style="display:none;">
        <div class="col-md-5">
            <label class="flabel">Golongan Kendaraan</label>
            <button type="button" class="picker-btn" id="btnGol"
                data-bs-toggle="modal" data-bs-target="#modalGol">
                <span>🚗 <span id="lblGol">Pilih Golongan</span></span>
                <span style="font-size:11px;opacity:.5;">▼</span>
            </button>
        </div>
        <div class="col-md-4">
            <label class="flabel">Plat Nomor</label>
            <input type="text" name="plat" id="inpPlat" class="form-control"
                placeholder="B 1234 ABC" oninput="this.value=this.value.toUpperCase()">
        </div>
    </div>

    <!-- BARIS 4: PENUMPANG -->
    <div class="mb-3">
        <label class="flabel">
            Penumpang
            <span id="batasLabel" style="font-size:10px;color:#38bdf8;margin-left:8px;"></span>
        </label>

        <div class="pax-grid">
            <!-- DEWASA -->
            <div class="pax-item">
                <div class="p-lbl">Dewasa</div>
                <div class="p-ket">≥10 tahun</div>
                <div class="pax-ctr">
                    <button type="button" id="btn_m_dewasa" onclick="paxKurang('dewasa')" disabled>−</button>
                    <input type="number" id="inp_dewasa" value="0" min="0" max="99"
                        onchange="paxManual('dewasa',this.value)"
                        oninput="paxManual('dewasa',this.value)" disabled>
                    <button type="button" id="btn_p_dewasa" onclick="paxTambah('dewasa')" disabled>+</button>
                </div>
            </div>
            <!-- ANAK -->
            <div class="pax-item">
                <div class="p-lbl">Anak</div>
                <div class="p-ket">3–10 tahun</div>
                <div class="pax-ctr">
                    <button type="button" id="btn_m_anak" onclick="paxKurang('anak')" disabled>−</button>
                    <input type="number" id="inp_anak" value="0" min="0" max="99"
                        onchange="paxManual('anak',this.value)"
                        oninput="paxManual('anak',this.value)" disabled>
                    <button type="button" id="btn_p_anak" onclick="paxTambah('anak')" disabled>+</button>
                </div>
            </div>
            <!-- BAYI -->
            <div class="pax-item">
                <div class="p-lbl">Bayi</div>
                <div class="p-ket">&lt;3 tahun (Gratis)</div>
                <div class="pax-ctr">
                    <button type="button" id="btn_m_bayi" onclick="paxKurang('bayi')" disabled>−</button>
                    <input type="number" id="inp_bayi" value="0" min="0" max="99"
                        onchange="paxManual('bayi',this.value)"
                        oninput="paxManual('bayi',this.value)" disabled>
                    <button type="button" id="btn_p_bayi" onclick="paxTambah('bayi')" disabled>+</button>
                </div>
            </div>
        </div>

        <div class="batas-info" id="batasInfo" style="display:none;"></div>
    </div>

    <hr class="fdiv">

    <!-- ESTIMASI HARGA -->
    <div class="harga-box mb-4" id="hargaBox">
        <div style="font-size:11px;color:#38bdf8;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:12px;">
            💰 Estimasi Biaya
        </div>
        <div id="hargaDetail"></div>
        <div class="h-row h-total">
            <span class="lbl">Total Pembayaran</span>
            <span class="val" id="hargaTotal">Rp 0</span>
        </div>
    </div>

    <button type="submit" class="btn-submit" id="btnSubmit">🚀 Lanjut Pesan Tiket</button>

</form>
</div>
</div>


<!-- ══ MODAL GOLONGAN ══ -->
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


<!-- ══ MODAL SYARAT ══ -->
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
                <li>Jumlah penumpang tidak boleh melebihi kapasitas golongan kendaraan.</li>
                <li>Motor (Gol II & III): maks 2 dewasa + 2 anak.</li>
                <li>Golongan kendaraan harus sesuai dengan kendaraan yang dibawa.</li>
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
            <input type="checkbox" id="chkSyarat"
                onclick="event.stopPropagation();updateBtnSetuju()">
            <span>Saya telah membaca dan menyetujui seluruh <b>Syarat & Ketentuan</b> pemesanan tiket Navira</span>
        </div>
        <div class="d-flex gap-2 mt-3">
            <button type="button" class="btnm-sec flex-fill"
                data-bs-dismiss="modal" onclick="batalSyarat()">Batal</button>
            <button type="button" class="btnm flex-fill" id="btnSetuju"
                onclick="setujuLanjut()" disabled style="margin-top:0;">✅ Setuju & Lanjut</button>
        </div>
    </div>
</div>
</div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ══════════════════════════════════════════════════════
// DATA GOLONGAN + BATAS PENUMPANG
// ══════════════════════════════════════════════════════
const golData = [
    { id:'gol_1',  label:'Golongan I',    name:'Sepeda',           desc:'Sepeda kayuh, onthel, tanpa motor',    maxTotal:1,  maxDewasa:1,  maxAnak:0 },
    { id:'gol_2',  label:'Golongan II',   name:'Motor <500cc',     desc:'Honda, Yamaha, Suzuki <500cc',         maxTotal:4,  maxDewasa:2,  maxAnak:2,  motorRule:true },
    { id:'gol_3',  label:'Golongan III',  name:'Motor >500cc',     desc:'Ducati, Harley, motor 500cc, roda tiga',maxTotal:4, maxDewasa:2,  maxAnak:2,  motorRule:true },
    { id:'gol_4a', label:'Gol IVA',       name:'Mobil Penumpang',  desc:'Sedan, SUV, MPV, LCGC, Minibus ≤5m',  maxTotal:7,  maxDewasa:7,  maxAnak:7 },
    { id:'gol_4b', label:'Gol IVB',       name:'Mobil Barang ≤5m', desc:'Pick up, double cabin, bak terbuka ≤5m',maxTotal:3, maxDewasa:3,  maxAnak:3 },
    { id:'gol_5a', label:'Gol VA',        name:'Bus Sedang 5–7m',  desc:'Elf, Hiace, medium bus, ambulans',     maxTotal:20, maxDewasa:20, maxAnak:20 },
    { id:'gol_5b', label:'Gol VB',        name:'Truk Sedang 5–7m', desc:'Truk box, truk pasir 5–7m',            maxTotal:3,  maxDewasa:3,  maxAnak:3 },
    { id:'gol_6a', label:'Gol VIA',       name:'Bus Besar 7–10m',  desc:'Bis AKAP, pariwisata 52 seat',         maxTotal:50, maxDewasa:50, maxAnak:50 },
    { id:'gol_6b', label:'Gol VIB',       name:'Truk Besar 7–10m', desc:'Truk tangki, Fuso 7–10m',              maxTotal:3,  maxDewasa:3,  maxAnak:3 },
    { id:'gol_7',  label:'Golongan VII',  name:'Tronton 10–12m',   desc:'Tronton, alat berat, gandengan',        maxTotal:3,  maxDewasa:3,  maxAnak:3 },
    { id:'gol_8',  label:'Golongan VIII', name:'Tronton 12–16m',   desc:'Trailer, Lowbed, Flatbed 12–16m',      maxTotal:3,  maxDewasa:3,  maxAnak:3 },
    { id:'gol_9',  label:'Golongan IX',   name:'Tronton >16m',     desc:'Tangki gandeng, alat berat >16m',      maxTotal:3,  maxDewasa:3,  maxAnak:3 },
];

// ══ STATE ══
let dp             = { dewasa:0, anak:0, bayi:0 };
let selectedGol    = null;
let hasilHarga     = {};
let hasilHargaKend = {};
let activeTujuan   = { id:'', nama:'', lokasi:'', label:'' };
let layananAktif   = '';

// ══════════════════════════════════════════════════════
// WAKTU LOKAL BROWSER (real-time user)
// ══════════════════════════════════════════════════════
function getNowHM() {
    const now = new Date();
    return { h: now.getHours(), m: now.getMinutes() };
}

function getTodayStr() {
    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, '0');
    const d = String(now.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

/**
 * Apakah tanggal yang dipilih adalah hari ini (berdasarkan server)?
 */
function isToday(dateStr) {
    if(!dateStr) return false;
    return dateStr === getTodayStr();
}

// ══════════════════════════════════════════════════════
// POPULATE JAM CHECK-IN
// Dipanggil saat tanggal berubah.
// Jika hari ini: jam yang sudah lewat di-disabled.
// ══════════════════════════════════════════════════════
function populateJam(tanggalVal) {
    const sel     = document.getElementById('selJam');
    const alert_  = document.getElementById('jamAlert');
    const alertTx = document.getElementById('jamAlertText');

    sel.innerHTML = '';

    if(!tanggalVal) {
        sel.innerHTML = '<option value="">— Pilih Tanggal Dulu —</option>';
        alert_.classList.remove('show','err');
        return;
    }

    const today   = isToday(tanggalVal);
    const { h: nowH, m: nowM } = getNowHM();

    // Untuk hari ini, slot yang sudah lewat / sedang berjalan / kurang dari 60 menit lagi
    // dianggap tidak valid (aturan minimal pesan H-1 jam).
    let adaJamValid = false;
    let firstValid  = '';

    for(let h = 0; h < 24; h++) {
        const val   = String(h).padStart(2,'0') + ':00';
        const label = String(h).padStart(2,'0') + '.00 - ' + String(h===23 ? 0 : h+1).padStart(2,'0') + '.00';

        const opt  = document.createElement('option');
        opt.value  = val;

        if(today && h < nowH) {
            opt.textContent = '🚫 ' + label + ' (Sudah lewat)';
            opt.disabled    = true;
        } else if(today) {
            // Hitung selisih menit dari sekarang ke awal slot
            const diffMenit = (h * 60) - (nowH * 60 + nowM);
            if(diffMenit <= 60) {
                opt.textContent = '⏳ ' + label + ' (Minimal H-1 jam)';
                opt.disabled    = true;
            } else {
                opt.textContent = label;
                if(!adaJamValid) { adaJamValid = true; firstValid = val; }
            }
        } else {
            opt.textContent = label;
            if(!adaJamValid) { adaJamValid = true; firstValid = val; }
        }
        sel.appendChild(opt);
    }

    // Tambah placeholder di awal
    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = '— Pilih Jam Check-In —';
    placeholder.selected = true;
    sel.insertBefore(placeholder, sel.firstChild);

    if(!adaJamValid) {
        // Semua jam sudah lewat (sangat jarang, hanya mungkin tepat tengah malam)
        alert_.classList.add('show','err');
        alert_.classList.remove('warn'); // alias
        alertTx.textContent = 'Tidak ada jam tersedia untuk hari ini. Silakan pilih tanggal lain.';
        document.getElementById('btnSubmit').disabled = true;
    } else if(today) {
        alert_.classList.add('show');
        alert_.classList.remove('err');
        alertTx.textContent = 'Slot kurang dari 1 jam dari sekarang tidak dapat dipilih. Waktu sekarang: ' + String(nowH).padStart(2,'0') + ':' + String(nowM).padStart(2,'0') + ' WIB';
        document.getElementById('btnSubmit').disabled = false;
    } else {
        alert_.classList.remove('show','err');
        document.getElementById('btnSubmit').disabled = false;
    }
}

// ══════════════════════════════════════════════════════
// EVENT: TANGGAL BERUBAH
// ══════════════════════════════════════════════════════
function onTanggalChange() {
    const tgl = document.getElementById('inpTanggal').value;
    populateJam(tgl);
    onJamChange(); // reset alert jika jam berubah
}

// ══════════════════════════════════════════════════════
// EVENT: JAM BERUBAH — validasi ulang
// ══════════════════════════════════════════════════════
function onJamChange() {
    const sel     = document.getElementById('selJam');
    const alert_  = document.getElementById('jamAlert');
    const alertTx = document.getElementById('jamAlertText');
    const tgl     = document.getElementById('inpTanggal').value;
    const jamVal  = sel.value;

    if(!jamVal || !isToday(tgl)) {
        // Bukan hari ini atau belum pilih jam → bersihkan alert jam (kecuali kalau semua jam habis)
        if(isToday(tgl)) {
            // Sudah populateJam menangani
        } else {
            alert_.classList.remove('show','err');
        }
        return;
    }

    const { h: nowH } = getNowHM();
    const jamH = parseInt(jamVal.split(':')[0], 10);

    const diffMenit = (jamH * 60) - (nowH * 60 + nowM);
    if(diffMenit <= 60) {
        // Slot yang dipilih sudah berjalan/terlewat (edge case saat waktu terus berjalan)
        alert_.classList.add('show','err');
        alertTx.textContent = '⛔ Slot jam ini tidak valid (minimal pesan H-1 jam). Pilih jam berikutnya.';
        sel.value = '';
    } else {
        alert_.classList.remove('show','err');
    }
}

// ══════════════════════════════════════════════════════
// LOAD PELABUHAN ASAL
// ══════════════════════════════════════════════════════
fetch('get_pelabuhan.php')
    .then(r => r.json())
    .then(data => {
        if(!data.success) return;
        const sel = document.getElementById('selAsal');
        data.asals.forEach(p => {
            const o = new Option('🚢 ' + p.label, p.id);
            o.dataset.nama   = p.nama;
            o.dataset.lokasi = p.lokasi;
            o.dataset.label  = p.label;
            sel.appendChild(o);
        });
    })
    .catch(e => console.error('get_pelabuhan error:', e));

// ══════════════════════════════════════════════════════
// ASAL BERUBAH
// ══════════════════════════════════════════════════════
function onAsalChange() {
    const selAsal = document.getElementById('selAsal');
    const asalId  = selAsal.value;
    const asalOpt = selAsal.options[selAsal.selectedIndex];
    const selTuj  = document.getElementById('selTujuan');

    resetTujuan();
    if(!asalId) return;

    document.getElementById('f_asal_id').value   = asalId;
    document.getElementById('f_asal_nama').value = asalOpt.dataset.label || asalOpt.dataset.nama || '';

    selTuj.innerHTML = '<option value="">⏳ Mencari rute...</option>';

    fetch('get_tujuan.php?asal_id=' + asalId)
        .then(r => r.json())
        .then(data => {
            selTuj.innerHTML = '';
            if(!data.success || !data.tujuans || !data.tujuans.length) {
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
            console.error('get_tujuan error:', e);
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
    if(!opt?.value) return;
    activeTujuan = {
        id    : String(opt.value),
        nama  : opt.dataset.nama   || '',
        lokasi: opt.dataset.lokasi || '',
        label : opt.dataset.label  || ''
    };
    document.getElementById('f_tujuan_id').value   = activeTujuan.id;
    document.getElementById('f_tujuan_nama').value = activeTujuan.label;
}

// ══════════════════════════════════════════════════════
// SWAP
// ══════════════════════════════════════════════════════
function doSwap() {
    if(!activeTujuan.id) return;
    const selAsal = document.getElementById('selAsal');
    if(!selAsal.querySelector(`option[value="${activeTujuan.id}"]`)) {
        alert('⚠️ Rute sebaliknya belum tersedia.'); return;
    }
    selAsal.value = activeTujuan.id;
    onAsalChange();
}

// ══════════════════════════════════════════════════════
// PREVIEW RUTE
// ══════════════════════════════════════════════════════
function tampilPreview() {
    const selAsal = document.getElementById('selAsal');
    const asalOpt = selAsal.options[selAsal.selectedIndex];
    if(!asalOpt?.value || !activeTujuan.id) return;
    document.getElementById('pAsal').textContent      = asalOpt.dataset.nama   || '';
    document.getElementById('pAsalLok').textContent   = asalOpt.dataset.lokasi || '';
    document.getElementById('pTujuan').textContent    = activeTujuan.nama;
    document.getElementById('pTujuanLok').textContent = activeTujuan.lokasi;
    document.getElementById('pLayanan').textContent   = layananAktif || '—';
    document.getElementById('rutePrev').classList.add('show');
}

function onLayananChange() {
    layananAktif = document.getElementById('selLayanan').value;
    document.getElementById('pLayanan').textContent = layananAktif || '—';
    setPaxEnabled(!!layananAktif);
    hitungHarga();
}

// ══════════════════════════════════════════════════════
// PENUMPANG — AKTIFKAN / NONAKTIFKAN INPUT
// ══════════════════════════════════════════════════════
function setPaxEnabled(enabled) {
    ['dewasa','anak','bayi'].forEach(t => {
        document.getElementById('inp_'+t).disabled   = !enabled;
        document.getElementById('btn_p_'+t).disabled = !enabled;
        document.getElementById('btn_m_'+t).disabled = !enabled;
    });
    if(!enabled) {
        dp = { dewasa:0, anak:0, bayi:0 };
        syncPaxUI();
    }
    updateBatasInfo();
}

// ══════════════════════════════════════════════════════
// BATAS MAX per golongan
// ══════════════════════════════════════════════════════
function getBatas() {
    if(!selectedGol) return { maxTotal:999, maxDewasa:999, maxAnak:999 };
    return {
        maxTotal : selectedGol.maxTotal  ?? 999,
        maxDewasa: selectedGol.maxDewasa ?? 999,
        maxAnak  : selectedGol.maxAnak   ?? 999,
        motorRule: selectedGol.motorRule ?? false
    };
}

function updateBatasInfo() {
    const info = document.getElementById('batasInfo');
    const lbl  = document.getElementById('batasLabel');

    if(!layananAktif) {
        info.style.display = 'none';
        lbl.textContent = '';
        return;
    }

    if(!selectedGol) {
        info.style.display = 'block';
        info.className = 'batas-info';
        info.textContent = 'Pilih jenis kendaraan untuk melihat batas penumpang.';
        lbl.textContent = '';
        return;
    }

    const b = getBatas();
    const total = dp.dewasa + dp.anak + dp.bayi;
    const sisa  = b.maxTotal - total;

    info.style.display = 'block';
    lbl.textContent = `(Maks ${b.maxTotal} orang)`;

    let pesan = `${selectedGol.label} — maks ${b.maxTotal} penumpang total`;
    if(b.motorRule) pesan += ` | Maks 2 dewasa, 2 anak`;

    if(sisa <= 0) {
        info.className = 'batas-info warn';
        info.textContent = '⚠️ Kapasitas penuh! ' + pesan;
    } else {
        info.className = 'batas-info';
        info.textContent = `✅ Tersisa ${sisa} kursi — ` + pesan;
    }
}

// ══════════════════════════════════════════════════════
// PENUMPANG COUNTER
// ══════════════════════════════════════════════════════
function paxTambah(t) {
    if(!layananAktif) { alert('⚠️ Pilih layanan terlebih dahulu!'); return; }
    const b = getBatas();
    const total = dp.dewasa + dp.anak + dp.bayi;

    if(total >= b.maxTotal) {
        alert(`⚠️ Maksimal ${b.maxTotal} penumpang untuk ${selectedGol ? selectedGol.label : 'kendaraan ini'}!`);
        return;
    }
    if(t === 'dewasa' && dp.dewasa >= b.maxDewasa) {
        alert(`⚠️ Maksimal ${b.maxDewasa} dewasa untuk ${selectedGol ? selectedGol.label : 'kendaraan ini'}!`);
        return;
    }
    if(t === 'anak' && dp.anak >= b.maxAnak) {
        alert(`⚠️ Maksimal ${b.maxAnak} anak untuk ${selectedGol ? selectedGol.label : 'kendaraan ini'}!`);
        return;
    }

    dp[t]++;
    syncPaxUI();
}

function paxKurang(t) {
    if(dp[t] <= 0) return;
    dp[t]--;
    syncPaxUI();
}

function paxManual(t, val) {
    if(!layananAktif) {
        document.getElementById('inp_'+t).value = 0;
        alert('⚠️ Pilih layanan terlebih dahulu!');
        return;
    }

    let n = parseInt(val, 10);
    if(isNaN(n) || n < 0) n = 0;

    const b = getBatas();

    if(t === 'dewasa' && n > b.maxDewasa) n = b.maxDewasa;
    if(t === 'anak'   && n > b.maxAnak)   n = b.maxAnak;

    const totalBaru = (t === 'dewasa' ? n : dp.dewasa)
                    + (t === 'anak'   ? n : dp.anak)
                    + (t === 'bayi'   ? n : dp.bayi);

    if(totalBaru > b.maxTotal) {
        n = n - (totalBaru - b.maxTotal);
        if(n < 0) n = 0;
        alert(`⚠️ Total penumpang melebihi kapasitas (maks ${b.maxTotal})!`);
    }

    dp[t] = n;
    syncPaxUI();
}

function syncPaxUI() {
    ['dewasa','anak','bayi'].forEach(t => {
        document.getElementById('inp_'+t).value = dp[t];
    });
    const total = dp.dewasa + dp.anak + dp.bayi;
    document.getElementById('f_dewasa').value    = dp.dewasa;
    document.getElementById('f_anak').value      = dp.anak;
    document.getElementById('f_bayi').value      = dp.bayi;
    document.getElementById('f_total_pax').value = total;
    updateBatasInfo();
    hitungHarga();
}

// ══════════════════════════════════════════════════════
// TOGGLE KENDARAAN
// ══════════════════════════════════════════════════════
function toggleKendaraan() {
    const show = document.getElementById('selJenis').value === 'kendaraan';
    document.getElementById('boxKendaraan').style.display = show ? 'flex' : 'none';
    if(!show) {
        selectedGol = null;
        document.getElementById('f_golongan').value   = '';
        document.getElementById('lblGol').textContent = 'Pilih Golongan';
        document.getElementById('btnGol').classList.remove('filled');
        document.querySelectorAll('.gcard').forEach(c => c.classList.remove('selected'));
        document.getElementById('btnSimpanGol').disabled = true;
    }
    updateBatasInfo();
    hitungHarga();
}

// ══════════════════════════════════════════════════════
// RENDER GOLONGAN GRID
// ══════════════════════════════════════════════════════
const golGrid = document.getElementById('golGrid');
golData.forEach(g => {
    const lbl = document.createElement('label');
    lbl.className = 'gcard';
    lbl.innerHTML = `
        <input type="radio" name="gd" value="${g.id}">
        <div class="gbadge">${g.label}</div>
        <div class="gname">${g.name}</div>
        <div class="gdesc">${g.desc}</div>
        <div class="gmax">👥 Maks ${g.maxTotal} penumpang${g.motorRule ? ' (2D+2A)' : ''}</div>
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

function simpanGol() {
    if(!selectedGol) return;
    document.getElementById('f_golongan').value   = selectedGol.id;
    document.getElementById('lblGol').textContent = selectedGol.label + ' — ' + selectedGol.name;
    document.getElementById('btnGol').classList.add('filled');

    const b = getBatas();
    if(dp.dewasa > b.maxDewasa) { dp.dewasa = b.maxDewasa; }
    if(dp.anak   > b.maxAnak)   { dp.anak   = b.maxAnak; }
    const total = dp.dewasa + dp.anak + dp.bayi;
    if(total > b.maxTotal) {
        const lebih = total - b.maxTotal;
        dp.bayi = Math.max(0, dp.bayi - lebih);
        if(dp.dewasa + dp.anak + dp.bayi > b.maxTotal) {
            dp.anak = Math.max(0, dp.anak - (dp.dewasa + dp.anak + dp.bayi - b.maxTotal));
        }
    }
    syncPaxUI();
    updateBatasInfo();
    hitungHarga();
}

// ══════════════════════════════════════════════════════
// HITUNG HARGA
// ══════════════════════════════════════════════════════
function hitungHarga() {
    const layanan   = document.getElementById('selLayanan').value;
    const jenis     = document.getElementById('selJenis').value;
    const golongan  = document.getElementById('f_golongan').value;
    const tid       = activeTujuan.id;
    const paxBayar  = dp.dewasa + dp.anak;
    const paxTotal  = paxBayar + dp.bayi;

    if(!tid || !layanan || paxTotal === 0) {
        document.getElementById('hargaBox').classList.remove('show');
        document.getElementById('f_total_harga').value = 0;
        return;
    }

    const hargaRute = hasilHarga[tid];
    if(!hargaRute) {
        document.getElementById('hargaBox').classList.remove('show');
        return;
    }

    const hargaPax = parseInt(hargaRute[layanan] ?? 0, 10) || 0;
    let html = '';
    let totalPax = hargaPax * paxBayar;

    if(dp.dewasa) html += `<div class="h-row"><span class="lbl">Dewasa × ${dp.dewasa}</span><span class="val">${fmt(hargaPax * dp.dewasa)}</span></div>`;
    if(dp.anak)   html += `<div class="h-row"><span class="lbl">Anak × ${dp.anak}</span><span class="val">${fmt(hargaPax * dp.anak)}</span></div>`;
    if(dp.bayi)   html += `<div class="h-row"><span class="lbl">Bayi × ${dp.bayi}</span><span class="val" style="color:#4ade80;">Gratis</span></div>`;

    html += `<div class="h-row" style="font-size:12px;color:#475569;border-top:1px solid rgba(255,255,255,.04);padding-top:6px;margin-top:2px;">
        <span class="lbl">Tarif per orang (${layanan})</span>
        <span class="val">${fmt(hargaPax)}</span>
    </div>`;

    let biayaKend = 0;
    if(jenis === 'kendaraan' && golongan) {
        const golObj    = golData.find(g => g.id === golongan);
        const golLabel  = golObj ? `${golObj.label} — ${golObj.name}` : golongan;
        const ruteKend  = hasilHargaKend[tid] || {};
        const dataGol   = ruteKend[golongan];

        if(dataGol !== undefined && dataGol !== null) {
            biayaKend = parseInt(dataGol[layanan] ?? 0, 10) || 0;
            html += `<div class="h-row" style="border-top:1px solid rgba(255,255,255,.06);margin-top:8px;padding-top:8px;">
                <span class="lbl">🚗 Kendaraan (${golLabel})</span>
                <span class="val">${biayaKend === 0 ? '<span style="color:#4ade80;">Gratis</span>' : fmt(biayaKend)}</span>
            </div>`;
        } else {
            html += `<div class="h-row" style="border-top:1px solid rgba(255,255,255,.06);margin-top:8px;padding-top:8px;">
                <span class="lbl">🚗 Kendaraan (${golLabel})</span>
                <span class="val" style="color:#f59e0b;">Hubungi admin</span>
            </div>`;
        }
    }

    const grandTotal = totalPax + biayaKend;
    document.getElementById('hargaDetail').innerHTML  = html;
    document.getElementById('hargaTotal').textContent = fmt(grandTotal);
    document.getElementById('hargaBox').classList.add('show');
    document.getElementById('f_total_harga').value    = grandTotal;
}

function fmt(n) {
    const num = parseInt(n, 10);
    return 'Rp ' + (isNaN(num) ? 0 : num).toLocaleString('id-ID');
}

// ══════════════════════════════════════════════════════
// MODAL SYARAT
// ══════════════════════════════════════════════════════
function bukaModalSyarat(e) {
    e.preventDefault();

    const asal_id   = document.getElementById('f_asal_id').value;
    const tujuan_id = document.getElementById('f_tujuan_id').value;
    const tanggal   = document.getElementById('inpTanggal').value;
    const jamVal    = document.getElementById('selJam').value;
    const layanan   = document.getElementById('selLayanan').value;
    const jenis     = document.getElementById('selJenis').value;
    const golongan  = document.getElementById('f_golongan').value;
    const plat      = document.getElementById('inpPlat')?.value.trim() ?? '';
    const total_pax = dp.dewasa + dp.anak + dp.bayi;

    if(!asal_id)   { alert('⚠️ Pilih pelabuhan asal!'); return false; }
    if(!tujuan_id) { alert('⚠️ Tujuan belum tersedia!'); return false; }
    if(!tanggal)   { alert('⚠️ Pilih tanggal keberangkatan!'); return false; }

    // Validasi tanggal tidak boleh sebelum hari ini
    const serverDate = getTodayStr();
    if(tanggal < serverDate) { alert('⚠️ Tanggal tidak boleh sebelum hari ini!'); return false; }

    // Validasi jam — wajib dipilih
    if(!jamVal) { alert('⚠️ Pilih jam check-in!'); return false; }

    // Validasi jam tidak boleh sudah lewat (double-check saat submit)
    if(isToday(tanggal)) {
        const { h: nowH, m: nowM } = getNowHM();
        const jamH = parseInt(jamVal.split(':')[0], 10);
        const diffMenit = (jamH * 60) - (nowH * 60 + nowM);
        if(diffMenit <= 60) {
            alert('⛔ Jam check-in tidak valid.\nPemesanan minimal 1 jam sebelum slot dimulai.');
            // Reset pilihan jam dan refresh options
            populateJam(tanggal);
            return false;
        }
    }

    if(!layanan)   { alert('⚠️ Pilih layanan!'); return false; }
    if(total_pax <= 0) { alert('⚠️ Pilih minimal 1 penumpang!'); return false; }
    if(!jenis)     { alert('⚠️ Pilih jenis pengguna jasa!'); return false; }

    if(jenis === 'kendaraan') {
        if(!golongan) { alert('⚠️ Pilih golongan kendaraan!'); return false; }
        if(!plat)     { alert('⚠️ Masukkan plat nomor!'); return false; }

        const b = getBatas();
        if(total_pax > b.maxTotal) {
            alert(`⚠️ Jumlah penumpang (${total_pax}) melebihi kapasitas ${selectedGol.label} (maks ${b.maxTotal})!`);
            return false;
        }
        if(b.motorRule) {
            if(dp.dewasa > 2) { alert('⚠️ Motor maksimal 2 penumpang dewasa!'); return false; }
            if(dp.anak   > 2) { alert('⚠️ Motor maksimal 2 penumpang anak!'); return false; }
        }
    }

    // Sync hidden fields
    syncActiveTujuan();
    document.getElementById('f_dewasa').value    = dp.dewasa;
    document.getElementById('f_anak').value      = dp.anak;
    document.getElementById('f_bayi').value      = dp.bayi;
    document.getElementById('f_total_pax').value = dp.dewasa + dp.anak + dp.bayi;

    document.getElementById('chkSyarat').checked    = false;
    document.getElementById('btnSetuju').disabled   = true;
    document.getElementById('syaratBody').scrollTop = 0;
    document.getElementById('scrollNote').textContent = '↓ Gulir ke bawah untuk membaca semua ketentuan';
    document.getElementById('scrollNote').style.color = '#64748b';

    new bootstrap.Modal(document.getElementById('modalSyarat')).show();
    return false;
}

function cekScroll() {
    const el = document.getElementById('syaratBody');
    if(el.scrollTop + el.clientHeight >= el.scrollHeight - 10) {
        document.getElementById('scrollNote').textContent = '✓ Anda telah membaca seluruh ketentuan';
        document.getElementById('scrollNote').style.color = '#4ade80';
    }
}
function toggleSyarat()    { const cb = document.getElementById('chkSyarat'); cb.checked = !cb.checked; updateBtnSetuju(); }
function updateBtnSetuju() { document.getElementById('btnSetuju').disabled = !document.getElementById('chkSyarat').checked; }
function batalSyarat()     { document.getElementById('chkSyarat').checked = false; document.getElementById('btnSetuju').disabled = true; }
function setujuLanjut() {
    if(!document.getElementById('chkSyarat').checked) return;
    bootstrap.Modal.getInstance(document.getElementById('modalSyarat')).hide();
    setTimeout(() => document.getElementById('formBeli').submit(), 300);
}

// ══════════════════════════════════════════════════════
// REFRESH JAM OTOMATIS setiap menit
// (memperbarui opsi jam yang disabled jika hari ini)
// ══════════════════════════════════════════════════════
setInterval(() => {
    const tgl = document.getElementById('inpTanggal').value;
    if(tgl && isToday(tgl)) {
        const currentJam = document.getElementById('selJam').value;
        populateJam(tgl);
        // Pertahankan pilihan jika masih valid
        const selJam = document.getElementById('selJam');
        if(currentJam) {
            const opt = selJam.querySelector(`option[value="${currentJam}"]`);
            if(opt && !opt.disabled) {
                selJam.value = currentJam;
            } else if(opt && opt.disabled) {
                // Pilihan sudah kedaluwarsa
                selJam.value = '';
                document.getElementById('jamAlert').classList.add('show','err');
                document.getElementById('jamAlertText').textContent = '⛔ Jam yang Anda pilih tadi sudah lewat! Silakan pilih jam baru.';
            }
        }
    }
}, 5000); // cek setiap 5 detik agar benar-benar real-time

// ══ INIT ══
setPaxEnabled(false);
</script>
</body>
</html>