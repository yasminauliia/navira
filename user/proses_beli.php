<?php
session_start();
if(!isset($_SESSION['user_id'])){ header("Location: login.php"); exit; }
if($_SERVER['REQUEST_METHOD'] != 'POST'){ header("Location: beli_tiket.php"); exit; }

$data = $_POST;

// Hitung total dari hidden fields
$dewasa = (int)($data['dewasa'] ?? 0);
$lansia = (int)($data['lansia'] ?? 0);
$anak   = (int)($data['anak']   ?? 0);
$bayi   = (int)($data['bayi']   ?? 0);
$total_pax = $dewasa + $lansia + $anak + $bayi;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Konfirmasi Tiket - Navira</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="../assets/css/navy-theme.css" rel="stylesheet">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family:'Poppins',sans-serif;
    min-height:100vh; display:flex; align-items:center; justify-content:center;
    padding:30px 16px;
    color: white;
}
.card {
    background:rgba(255,255,255,0.06); backdrop-filter:blur(20px);
    border-radius:22px; padding:32px;
    max-width:520px; width:100%;
    border:1px solid rgba(255,255,255,0.1);
    box-shadow:0 20px 60px rgba(0,0,0,0.5);
    color:white; animation:fadeUp 0.5s ease;
}
@keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }

h2 { font-size:22px; font-weight:700; margin-bottom:20px;
     background:linear-gradient(to right,#38bdf8,#22d3ee);
     -webkit-background-clip:text; -webkit-text-fill-color:transparent; }

.row-info {
    display:flex; justify-content:space-between; align-items:center;
    padding:10px 14px; border-radius:10px;
    background:rgba(255,255,255,0.04); margin-bottom:8px;
    transition:0.2s;
}
.row-info:hover { background:rgba(255,255,255,0.07); }
.row-info .lbl { font-size:13px; color:#64748b; }
.row-info .val { font-size:14px; font-weight:600; color:white; }

hr { border:none; border-top:1px solid rgba(255,255,255,0.08); margin:16px 0; }

/* RUTE BOX */
.rute-box {
    background:rgba(56,189,248,0.08); border:1px solid rgba(56,189,248,0.2);
    border-radius:14px; padding:16px;
    display:flex; align-items:center; justify-content:center;
    gap:12px; margin-bottom:16px; font-size:15px;
}
.rute-port { text-align:center; }
.rute-kota { font-weight:700; font-size:16px; }
.rute-lok  { font-size:11px; color:#64748b; margin-top:2px; }
.rute-arr  { font-size:22px; color:#38bdf8; }

/* PENUMPANG CHIPS */
.pax-chips { display:flex; flex-wrap:wrap; gap:8px; margin-top:8px; }
.pax-chip {
    background:rgba(56,189,248,0.1); border:1px solid rgba(56,189,248,0.2);
    border-radius:20px; padding:4px 12px; font-size:12px; color:#38bdf8; font-weight:600;
}

/* TOTAL */
.total-box {
    background:rgba(56,189,248,0.06); border:1px solid rgba(56,189,248,0.2);
    border-radius:14px; padding:16px 20px;
    display:flex; justify-content:space-between; align-items:center;
    margin:16px 0;
}
.total-box .lbl { font-size:14px; color:#38bdf8; font-weight:600; }
.total-box .val { font-size:24px; font-weight:700; color:#38bdf8; }

/* BUTTONS */
.btn-group { display:flex; gap:10px; margin-top:4px; }
button {
    flex:1; padding:13px; border:none; border-radius:12px;
    cursor:pointer; font-size:14px; font-weight:700;
    font-family:'Poppins',sans-serif; transition:0.3s;
}
.btn-confirm {
    background:linear-gradient(135deg,#06b6d4,#3b82f6); color:white;
}
.btn-confirm:hover { transform:translateY(-2px); box-shadow:0 0 20px rgba(56,189,248,0.4); }
.btn-back-btn { background:rgba(255,255,255,0.08); color:rgba(255,255,255,0.7); }
.btn-back-btn:hover { background:rgba(255,255,255,0.12); color:white; }
</style>
</head>
<body class="bg-navy">
<div class="card">
    <h2>🌊 Konfirmasi Pemesanan</h2>

    <!-- RUTE VISUAL -->
    <?php
    $parsePort = function($str){
        $parts = explode(',', $str);
        return ['nama' => trim($parts[0]), 'lok' => isset($parts[1]) ? trim($parts[1]) : ''];
    };
    $pAsal   = $parsePort($data['asal'] ?? '');
    $pTujuan = $parsePort($data['tujuan'] ?? '');
    ?>
    <div class="rute-box">
        <div class="rute-port">
            <div class="rute-kota"><?= htmlspecialchars($pAsal['nama']) ?></div>
            <div class="rute-lok"><?= htmlspecialchars($pAsal['lok']) ?></div>
        </div>
        <div class="rute-arr">→</div>
        <div class="rute-port">
            <div class="rute-kota"><?= htmlspecialchars($pTujuan['nama']) ?></div>
            <div class="rute-lok"><?= htmlspecialchars($pTujuan['lok']) ?></div>
        </div>
    </div>

    <!-- DETAIL -->
    <div class="row-info"><span class="lbl">Tanggal</span><span class="val">📅 <?= date('d M Y', strtotime($data['tanggal'] ?? '')) ?></span></div>
    <div class="row-info"><span class="lbl">Jam Check-In</span><span class="val">⏰ <?= htmlspecialchars($data['jam'] ?? '') ?> WIB</span></div>
    <div class="row-info"><span class="lbl">Layanan</span><span class="val"><?= htmlspecialchars($data['layanan'] ?? '') ?></span></div>
    <div class="row-info"><span class="lbl">Jenis Pengguna</span><span class="val"><?= ucfirst($data['jenis_pengguna'] ?? '') ?></span></div>

    <?php if(!empty($data['golongan_kendaraan'])): ?>
    <div class="row-info"><span class="lbl">Golongan</span><span class="val">🚗 <?= htmlspecialchars($data['golongan_kendaraan']) ?></span></div>
    <?php endif; ?>
    <?php if(!empty($data['plat'])): ?>
    <div class="row-info"><span class="lbl">Plat Nomor</span><span class="val"><?= htmlspecialchars($data['plat']) ?></span></div>
    <?php endif; ?>

    <hr>

    <!-- PENUMPANG -->
    <div style="font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:8px;">Detail Penumpang</div>
    <div class="pax-chips">
        <?php if($dewasa): ?><span class="pax-chip">👤 Dewasa × <?= $dewasa ?></span><?php endif; ?>
        <?php if($lansia):  ?><span class="pax-chip">👴 Lansia × <?= $lansia ?></span><?php endif; ?>
        <?php if($anak):    ?><span class="pax-chip">👦 Anak × <?= $anak ?></span><?php endif; ?>
        <?php if($bayi):    ?><span class="pax-chip" style="color:#4ade80;border-color:rgba(74,222,128,0.2);background:rgba(74,222,128,0.08);">👶 Bayi × <?= $bayi ?> (Gratis)</span><?php endif; ?>
        <?php if($total_pax === 0): ?><span style="font-size:13px;color:#64748b;">Tidak ada data penumpang</span><?php endif; ?>
    </div>

    <hr>

    <!-- TOTAL -->
    <div class="total-box">
        <span class="lbl">Total Pembayaran</span>
        <span class="val">Rp <?= number_format((int)($data['total_harga'] ?? 0), 0, ',', '.') ?></span>
    </div>

    <!-- FORM SUBMIT -->
    <form action="simpan_tiket.php" method="POST">
        <?php foreach($data as $k => $v): ?>
            <?php if($k !== 'tujuan'): // skip tujuan duplikat ?>
            <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>">
            <?php endif; ?>
        <?php endforeach; ?>

        <div class="btn-group">
            <button type="button" class="btn-back-btn" onclick="history.back()">← Kembali</button>
            <button type="submit" class="btn-confirm">✅ Konfirmasi & Bayar</button>
        </div>
    </form>
</div>
</body>
</html>