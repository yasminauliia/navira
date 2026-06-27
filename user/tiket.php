<?php
session_start();
include __DIR__ . '/../config/koneksi.php';
include __DIR__ . '/../config/app.php';
include __DIR__ . '/../phpqrcode/qrlib.php';

if (!isset($_GET['kode'])) {
    die("Kode tidak ditemukan!");
}

$kode = $_GET['kode'];

// ambil data
$query = mysqli_query($conn, "
    SELECT t.*,
        COALESCE(a.nama_pelabuhan, '-') AS asal,
        COALESCE(b.nama_pelabuhan, '-') AS tujuan
    FROM tickets t
    LEFT JOIN pelabuhan a ON a.id = t.asal_id
    LEFT JOIN pelabuhan b ON b.id = t.tujuan_id
    WHERE t.kode_booking='$kode'
");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    die("Tiket tidak ditemukan!");
}

// ================= QR =================
$folder = __DIR__ . '/../qrcode/';
if (!file_exists($folder)) {
    mkdir($folder);
}

$file_qr = $folder . $kode . ".png";

// isi QR
$isi_qr = $kode;

// generate QR
if (!file_exists($file_qr)) {
    QRcode::png($isi_qr, $file_qr, QR_ECLEVEL_L, 5);
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Tiket</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../assets/css/navy-theme.css" rel="stylesheet">
</head>

<body class="bg-navy">
<div class="container mt-5">
<div class="card p-4 text-white" style="border-radius:20px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);">

<div class="text-center mb-3 d-flex align-items-center justify-content-center gap-2">
    <?= naviraLogoImg('../', 44) ?>
    <span style="font-size:22px;font-weight:700;letter-spacing:2px;">NAVIRA</span>
</div>

<h3 class="text-center">Tiket Kapal</h3>
<h5 class="text-center"><?= $kode ?></h5>

<hr>

<p><b>Tujuan:</b> <?= $data['tujuan'] ?></p>
<p><b>Tanggal:</b> <?= $data['tanggal'] ?></p>
<p><b>Status:</b> <?= $data['status'] ?></p>

<div class="text-center mt-3">
<img src="../qrcode/<?= $kode ?>.png" width="150">
</div>

<br>

<a href="download_tiket.php?kode=<?= $kode ?>" class="btn btn-danger w-100 mb-2">
📄 Download PDF
</a>

</div>
</div>
</body>
</html>
