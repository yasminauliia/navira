<?php
session_start();
include('../config/koneksi.php');

// ── Auth ──
if(!isset($_SESSION['user_id'])){ header("Location: login.php"); exit; }
if($_SERVER['REQUEST_METHOD'] !== 'POST'){ header("Location: beli_tiket.php"); exit; }

$uid = (int)$_SESSION['user_id'];

// ══════════════════════════════════════════════════════
// AMBIL DATA POST (aman)
// ══════════════════════════════════════════════════════
$asal_id    = (int)($_POST['asal_id']    ?? 0);
$tujuan_id  = (int)($_POST['tujuan_id'] ?? 0);
$asal_nama  = trim($_POST['asal_nama']  ?? '');
$tujuan_nama= trim($_POST['tujuan_nama']?? '');
$tanggal    = trim($_POST['tanggal']    ?? '');
$jam        = trim($_POST['jam']        ?? '');
$layanan    = trim($_POST['layanan']    ?? '');
$jenis      = trim($_POST['jenis_pengguna'] ?? '');
$golongan   = trim($_POST['golongan_kendaraan'] ?? '');
$plat       = strtoupper(trim($_POST['plat'] ?? ''));
$dewasa     = max(0, (int)($_POST['dewasa'] ?? 0));
$anak       = max(0, (int)($_POST['anak']   ?? 0));
$bayi       = max(0, (int)($_POST['bayi']   ?? 0));
$total_pax  = $dewasa + $anak + $bayi;

// ══════════════════════════════════════════════════════
// BATAS PENUMPANG PER GOLONGAN
// ══════════════════════════════════════════════════════
$batasGolongan = [
    'gol_1'  => ['maxTotal'=>1,  'maxDewasa'=>1,  'maxAnak'=>0,  'motorRule'=>false],
    'gol_2'  => ['maxTotal'=>4,  'maxDewasa'=>2,  'maxAnak'=>2,  'motorRule'=>true],
    'gol_3'  => ['maxTotal'=>4,  'maxDewasa'=>2,  'maxAnak'=>2,  'motorRule'=>true],
    'gol_4a' => ['maxTotal'=>7,  'maxDewasa'=>7,  'maxAnak'=>7,  'motorRule'=>false],
    'gol_4b' => ['maxTotal'=>3,  'maxDewasa'=>3,  'maxAnak'=>3,  'motorRule'=>false],
    'gol_5a' => ['maxTotal'=>20, 'maxDewasa'=>20, 'maxAnak'=>20, 'motorRule'=>false],
    'gol_5b' => ['maxTotal'=>3,  'maxDewasa'=>3,  'maxAnak'=>3,  'motorRule'=>false],
    'gol_6a' => ['maxTotal'=>50, 'maxDewasa'=>50, 'maxAnak'=>50, 'motorRule'=>false],
    'gol_6b' => ['maxTotal'=>3,  'maxDewasa'=>3,  'maxAnak'=>3,  'motorRule'=>false],
    'gol_7'  => ['maxTotal'=>3,  'maxDewasa'=>3,  'maxAnak'=>3,  'motorRule'=>false],
    'gol_8'  => ['maxTotal'=>3,  'maxDewasa'=>3,  'maxAnak'=>3,  'motorRule'=>false],
    'gol_9'  => ['maxTotal'=>3,  'maxDewasa'=>3,  'maxAnak'=>3,  'motorRule'=>false],
];

// ══════════════════════════════════════════════════════
// VALIDASI PHP
// ══════════════════════════════════════════════════════
$errors = [];

if($asal_id <= 0)    $errors[] = 'Pelabuhan asal tidak valid.';
if($tujuan_id <= 0)  $errors[] = 'Pelabuhan tujuan tidak valid.';
if(empty($tanggal))  $errors[] = 'Tanggal keberangkatan wajib diisi.';
if(empty($jam))      $errors[] = 'Jam check-in wajib dipilih.';
if(empty($layanan))  $errors[] = 'Layanan wajib dipilih.';
if(empty($jenis))    $errors[] = 'Jenis pengguna jasa wajib dipilih.';
if($total_pax <= 0)  $errors[] = 'Jumlah penumpang minimal 1 orang.';

// Validasi tanggal tidak boleh sebelum hari ini
if(!empty($tanggal)) {
    $tglObj  = DateTime::createFromFormat('Y-m-d', $tanggal);
    $today   = new DateTime(); $today->setTime(0,0,0);
    if(!$tglObj || $tglObj < $today) {
        $errors[] = 'Tanggal keberangkatan tidak boleh sebelum hari ini.';
    }
}

// Validasi kendaraan
if($jenis === 'kendaraan') {
    if(empty($golongan))                   $errors[] = 'Golongan kendaraan wajib dipilih.';
    if(empty($plat))                       $errors[] = 'Plat nomor wajib diisi.';
    if(!empty($golongan)) {
        if(!isset($batasGolongan[$golongan])) {
            $errors[] = 'Golongan kendaraan tidak valid.';
        } else {
            $b = $batasGolongan[$golongan];
            if($total_pax > $b['maxTotal']) {
                $errors[] = "Jumlah penumpang ({$total_pax}) melebihi kapasitas golongan ini (maks {$b['maxTotal']}).";
            }
            if($dewasa > $b['maxDewasa']) {
                $errors[] = "Dewasa ({$dewasa}) melebihi batas untuk golongan ini (maks {$b['maxDewasa']}).";
            }
            if($anak > $b['maxAnak']) {
                $errors[] = "Anak ({$anak}) melebihi batas untuk golongan ini (maks {$b['maxAnak']}).";
            }
            if($b['motorRule']) {
                if($dewasa > 2) $errors[] = 'Motor maksimal 2 penumpang dewasa.';
                if($anak   > 2) $errors[] = 'Motor maksimal 2 penumpang anak.';
            }
        }
    }
}

if(!empty($errors)) { errorPage($errors); exit; }

// ══════════════════════════════════════════════════════
// AMBIL HARGA DARI DB
// ══════════════════════════════════════════════════════
$stmt_h = mysqli_prepare($conn,"
    SELECT h.harga,
           pa.nama_pelabuhan AS nm_asal,   pa.lokasi AS lok_asal,
           pt.nama_pelabuhan AS nm_tujuan, pt.lokasi AS lok_tujuan
    FROM harga h
    JOIN pelabuhan pa ON pa.id = h.asal_id
    JOIN pelabuhan pt ON pt.id = h.tujuan_id
    WHERE h.asal_id=? AND h.tujuan_id=? AND LOWER(h.layanan)=LOWER(?)
    LIMIT 1
");
if(!$stmt_h) { errorPage(['DB prepare error: '.mysqli_error($conn)]); exit; }

mysqli_stmt_bind_param($stmt_h, 'iis', $asal_id, $tujuan_id, $layanan);
mysqli_stmt_execute($stmt_h);
$res_h = mysqli_stmt_get_result($stmt_h);
$hRow  = mysqli_fetch_assoc($res_h);
mysqli_stmt_close($stmt_h);

if(!$hRow) {
    errorPage(["Harga rute <b>$asal_nama → $tujuan_nama</b> layanan <b>$layanan</b> belum diatur. Minta admin untuk menambahkan data harga."]);
    exit;
}

$harga_pax     = (int)$hRow['harga'];
$nama_asal_db  = $hRow['nm_asal']   . ($hRow['lok_asal']   ? ', '.$hRow['lok_asal']   : '');
$nama_tujuan_db= $hRow['nm_tujuan'] . ($hRow['lok_tujuan'] ? ', '.$hRow['lok_tujuan'] : '');

// ── Hitung total harga penumpang (bayi gratis) ──
$pax_bayar       = $dewasa + $anak;
$total_pax_harga = $harga_pax * $pax_bayar;

// ── Harga kendaraan (dari tabel harga_kendaraan) ──
$biaya_kendaraan = 0;
if($jenis === 'kendaraan' && !empty($golongan)) {
    $stmt_k = mysqli_prepare($conn,"
        SELECT
            CASE WHEN LOWER(?) = 'express' THEN harga_express ELSE harga_reguler END AS harga
        FROM harga_kendaraan
        WHERE asal_id = ? AND tujuan_id = ? AND golongan = ?
        LIMIT 1
    ");
    if($stmt_k) {
        mysqli_stmt_bind_param($stmt_k, 'siis', $layanan, $asal_id, $tujuan_id, $golongan);
        mysqli_stmt_execute($stmt_k);
        $res_k = mysqli_stmt_get_result($stmt_k);
        $kRow  = mysqli_fetch_assoc($res_k);
        mysqli_stmt_close($stmt_k);
        if($kRow) $biaya_kendaraan = (int)$kRow['harga'];
    }
}

$total_harga = $total_pax_harga + $biaya_kendaraan;

// ══════════════════════════════════════════════════════
// KODE BOOKING UNIK
// ══════════════════════════════════════════════════════
do {
    $kode = 'TKT' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
    $cek  = mysqli_prepare($conn, "SELECT id_ticket FROM tickets WHERE kode_booking=? LIMIT 1");
    mysqli_stmt_bind_param($cek, 's', $kode);
    mysqli_stmt_execute($cek);
    mysqli_stmt_store_result($cek);
    $exists = mysqli_stmt_num_rows($cek) > 0;
    mysqli_stmt_close($cek);
} while($exists);

// ══════════════════════════════════════════════════════
// SIMPAN ORDER KE SESSION → STEP 1 (Isi Data Diri)
// ══════════════════════════════════════════════════════
$_SESSION['order'] = [
    'order_id'       => (string)$kode,
    'asal_id'        => (int)$asal_id,
    'tujuan_id'      => (int)$tujuan_id,
    'asal_nama'      => (string)$nama_asal_db,
    'tujuan_nama'    => (string)$nama_tujuan_db,
    'tanggal'        => (string)$tanggal,
    'jam'            => (string)$jam,
    'layanan'        => (string)$layanan,
    'jenis_pengguna' => (string)$jenis,
    'total_harga'    => (int)$total_harga,
    'dewasa'         => (int)$dewasa,
    'anak'           => (int)$anak,
    'bayi'           => (int)$bayi,
];

if ($jenis === 'kendaraan') {
    $_SESSION['order']['kendaraan'] = (string)$golongan; // konsisten dengan penumpang_detail.php
    $_SESSION['order']['golongan']  = (string)$golongan;
    $_SESSION['order']['plat']      = (string)$plat;
}

unset($_SESSION['order']['ticket_id'], $_SESSION['order']['verif_expires_at'], $_SESSION['order']['pay_expires_at']);

header("Location: isi_data_penumpang.php");
exit;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Memproses Tiket — Navira</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link href="../assets/css/navy-theme.css" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Poppins',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;color:white}
.box{text-align:center;padding:44px 36px;background:rgba(255,255,255,.05);backdrop-filter:blur(14px);border-radius:24px;border:1px solid rgba(255,255,255,.1);box-shadow:0 20px 60px rgba(0,0,0,.5);color:white;animation:up .5s ease;max-width:440px;width:100%}
@keyframes up{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
.loader{width:64px;height:64px;border-radius:50%;border:5px solid rgba(255,255,255,.1);border-top:5px solid #38bdf8;animation:spin 1s linear infinite;margin:0 auto 22px}
@keyframes spin{to{transform:rotate(360deg)}}
h2{font-size:20px;font-weight:700;margin-bottom:6px}
.sub{color:#64748b;font-size:13px;margin-bottom:22px}
.igrid{display:grid;grid-template-columns:1fr 1fr;gap:10px;text-align:left}
.iitem{background:rgba(255,255,255,.05);border-radius:10px;padding:10px 14px}
.iitem .lbl{font-size:10px;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px}
.iitem .val{font-size:13px;font-weight:600;color:white}
.kode-box{background:rgba(56,189,248,.08);border:1px solid rgba(56,189,248,.2);border-radius:12px;padding:14px;margin-top:14px}
.kode-box .lbl{font-size:10px;color:#38bdf8;letter-spacing:1.5px;margin-bottom:5px}
.kode-box .val{font-size:20px;font-weight:700;letter-spacing:3px;color:white}
.pgbar{height:3px;background:rgba(255,255,255,.08);border-radius:4px;margin-top:20px;overflow:hidden}
.pgfill{height:100%;background:linear-gradient(90deg,#38bdf8,#3b82f6);border-radius:4px;animation:pg 2.3s linear forwards}
@keyframes pg{from{width:0%}to{width:100%}}
</style>
</head>
<body class="bg-navy">
<div class="box">
    <div class="loader"></div>
    <h2>Tiket Dibuat! 🎉</h2>
    <div class="sub">Mengalihkan ke halaman e-tiket...</div>
    <div class="igrid">
        <div class="iitem">
            <div class="lbl">Rute</div>
            <div class="val"><?= htmlspecialchars($nama_asal_db) ?> → <?= htmlspecialchars($nama_tujuan_db) ?></div>
        </div>
        <div class="iitem">
            <div class="lbl">Tanggal</div>
            <div class="val"><?= date('d M Y', strtotime($tanggal)) ?></div>
        </div>
        <div class="iitem">
            <div class="lbl">Penumpang</div>
            <div class="val"><?= $total_pax ?> orang</div>
        </div>
        <div class="iitem">
            <div class="lbl">Total Bayar</div>
            <div class="val" style="color:#38bdf8;">Rp <?= number_format($total_harga,0,',','.') ?></div>
        </div>
    </div>
    <div class="kode-box">
        <div class="lbl">KODE BOOKING</div>
        <div class="val"><?= $kode ?></div>
    </div>
    <div class="pgbar"><div class="pgfill"></div></div>
</div>
<script>
setTimeout(() => { window.location.href = 'hasil_tiket.php?kode=<?= $kode ?>'; }, 2300);
</script>
</body>
</html>
<?php

// ══════════════════════════════════════════════════════
// HELPER ERROR PAGE
// ══════════════════════════════════════════════════════
function errorPage($errors) {
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Error Pemesanan — Navira</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link href="../assets/css/navy-theme.css" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Poppins',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;color:white}
.box{background:rgba(255,255,255,.05);backdrop-filter:blur(14px);border-radius:24px;border:1px solid rgba(239,68,68,.2);box-shadow:0 20px 60px rgba(0,0,0,.5);padding:36px;max-width:520px;width:100%;color:white;animation:up .4s ease}
@keyframes up{from{opacity:0;transform:translateY(15px)}to{opacity:1;transform:translateY(0)}}
.ico{font-size:48px;text-align:center;margin-bottom:14px}
h3{font-size:20px;font-weight:700;text-align:center;color:#f87171;margin-bottom:5px}
.sub{font-size:13px;color:#64748b;text-align:center;margin-bottom:20px}
.elist{background:rgba(239,68,68,.07);border:1px solid rgba(239,68,68,.15);border-radius:12px;padding:16px 20px;margin-bottom:22px;list-style:none}
.elist li{font-size:13px;color:#fca5a5;padding:7px 0;border-bottom:1px solid rgba(239,68,68,.08);display:flex;align-items:flex-start;gap:8px}
.elist li:last-child{border-bottom:none}
.elist li::before{content:'⚠️';flex-shrink:0;font-size:11px;margin-top:2px}
a.abtn{display:block;padding:13px;background:linear-gradient(135deg,#06b6d4,#3b82f6);border:none;border-radius:12px;color:white;font-family:'Poppins',sans-serif;font-weight:700;font-size:14px;cursor:pointer;text-align:center;text-decoration:none;transition:.3s}
a.abtn:hover{opacity:.9;color:white}
</style>
</head>
<body class="bg-navy">
<div class="box">
    <div class="ico">🚫</div>
    <h3>Pemesanan Tidak Berhasil</h3>
    <p class="sub">Terdapat masalah pada data yang dikirim:</p>
    <ul class="elist">
        <?php foreach((array)$errors as $e): ?><li><?= $e ?></li><?php endforeach; ?>
    </ul>
    <a href="beli_tiket.php" class="abtn">← Kembali & Isi Ulang</a>
</div>
</body>
</html>
<?php
}