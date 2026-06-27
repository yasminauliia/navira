<?php
session_start();
include('../config/koneksi.php');
include('../config/payment_helper.php');

ensurePaymentColumns($conn);

// CEK LOGIN
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// AMBIL DATA USER
$user = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM users WHERE id='$user_id'"));

// QUERY TIKET
$rute_sql = "
    CONCAT(
        COALESCE(a.nama_pelabuhan, '-'),
        IF(a.lokasi IS NOT NULL AND a.lokasi != '', CONCAT(', ', a.lokasi), '')
    ) AS asal,
    CONCAT(
        COALESCE(b.nama_pelabuhan, '-'),
        IF(b.lokasi IS NOT NULL AND b.lokasi != '', CONCAT(', ', b.lokasi), '')
    ) AS tujuan
";

$belum = mysqli_query($conn,"SELECT t.*, $rute_sql
FROM tickets t
LEFT JOIN pelabuhan a ON a.id = t.asal_id
LEFT JOIN pelabuhan b ON b.id = t.tujuan_id
WHERE t.user_id='$user_id'
AND (t.status='BELUM DIGUNAKAN' OR t.status IS NULL OR t.status='')
AND (" . sqlPaidTicketsCondition('t') . ")
ORDER BY t.id_ticket DESC");

$belumBayar = mysqli_query($conn,"SELECT t.*, $rute_sql
FROM tickets t
LEFT JOIN pelabuhan a ON a.id = t.asal_id
LEFT JOIN pelabuhan b ON b.id = t.tujuan_id
WHERE t.user_id='$user_id'
AND " . sqlPendingPaymentCondition('t') . "
ORDER BY t.id_ticket DESC");

$sudah = mysqli_query($conn,"SELECT t.*, $rute_sql
FROM tickets t
LEFT JOIN pelabuhan a ON a.id = t.asal_id
LEFT JOIN pelabuhan b ON b.id = t.tujuan_id
WHERE t.user_id='$user_id'
AND t.status='DIGUNAKAN'
ORDER BY t.id_ticket DESC");

$current = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard User</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="../assets/css/sidebar.css" rel="stylesheet">
<link href="../assets/css/responsive.css" rel="stylesheet">
<link href="../assets/css/navy-theme.css" rel="stylesheet">

<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Poppins', sans-serif;
    color: white;
    overflow-x: hidden;
    min-height: 100vh;
}

.page-header {
    margin-bottom: 24px;
}

.page-header .title {
    font-size: 20px;
    font-weight: 700;
    color: white;
}

.page-header .sub {
    font-size: 12px;
    color: #475569;
    margin-top: 4px;
}

/* TABS */
.tabs {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.tabs button {
    border: none;
    background: rgba(255, 255, 255, 0.05);
    color: #64748b;
    padding: 10px 18px;
    border-radius: 11px;
    font-family: 'Poppins', sans-serif;
    font-size: 13px;
    font-weight: 500;
    transition: 0.2s;
    cursor: pointer;
}

.tabs button:hover {
    background: rgba(255, 255, 255, 0.08);
    color: #94a3b8;
}

.tabs button.active {
    background: linear-gradient(90deg, rgba(37, 99, 235, 0.3), rgba(56, 189, 248, 0.15));
    border: 1px solid rgba(56, 189, 248, 0.2);
    color: white;
    box-shadow: 0 0 16px rgba(56, 189, 248, 0.1);
}

/* CARD TIKET */
.card-tiket {
    background: rgba(255, 255, 255, 0.08);
    padding: 16px 18px;
    border-radius: 16px;
    margin-bottom: 10px;
    border: 1px solid rgba(255, 255, 255, 0.06);
    cursor: pointer;
    transition: 0.25s;
    font-size: 14px;
    line-height: 1.6;
}

.card-tiket:hover {
    transform: translateY(-2px);
    border-color: rgba(56, 189, 248, 0.2);
    box-shadow: 0 8px 24px rgba(14, 165, 233, 0.15);
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #475569;
    font-size: 14px;
}

.status-belum {
    color: #38bdf8;
    font-weight: 600;
    font-size: 12px;
}

.status-sudah {
    color: #f87171;
    font-weight: 600;
    font-size: 12px;
}

.status-bayar {
    color: #fbbf24;
    font-weight: 600;
    font-size: 12px;
}

@media (max-width: 768px) {
    .tabs button {
        flex: 1;
        min-width: calc(50% - 6px);
    }
    .card-tiket:hover {
        transform: none;
    }
}

@media (max-width: 480px) {
    .tabs button {
        min-width: 100%;
    }
}
</style>
</head>

<body class="has-mobile-nav bg-navy">

<?php include('sidebar.php'); ?>

<div class="main-wrap">

<div class="page-header">
    <div class="title">Tiket Saya 🎫</div>
    <div class="sub">Kelola pesanan tiket kapal Anda</div>
</div>

<div class="tabs">
    <button onclick="showTab('belum')" class="active" id="btn-belum">Belum Digunakan</button>
    <button onclick="showTab('sudah')" id="btn-sudah">Sudah Digunakan</button>
    <?php if(mysqli_num_rows($belumBayar) > 0): ?>
    <button onclick="showTab('bayar')" id="btn-bayar">Menunggu Bayar</button>
    <?php endif; ?>
</div>

<!-- MENUNGGU BAYAR -->
<?php if(mysqli_num_rows($belumBayar) > 0): ?>
<div id="bayar" style="display:none;">
<?php while($row = mysqli_fetch_assoc($belumBayar)): ?>
    <div class="card-tiket" onclick="bayar('<?= $row['kode_booking'] ?>')">
        <b><?= $row['asal']; ?> → <?= $row['tujuan']; ?></b><br>
        Kode: <?= $row['kode_booking']; ?><br>
        Tanggal: <?= $row['tanggal']; ?><br>
        Total: Rp <?= number_format((int)$row['total_harga'], 0, ',', '.'); ?><br><br>
        <span class="status-bayar">⏳ MENUNGGU PEMBAYARAN</span>
    </div>
<?php endwhile; ?>
</div>
<?php endif; ?>

<!-- BELUM -->
<div id="belum">
<?php if(mysqli_num_rows($belum) > 0){ ?>
    <?php while($row = mysqli_fetch_assoc($belum)){ ?>
        
        <div class="card-tiket" onclick="lihat('<?= $row['kode_booking'] ?>')">
            <b><?= $row['asal']; ?> → <?= $row['tujuan']; ?></b><br>
            Kode: <?= $row['kode_booking']; ?><br>
            Tanggal: <?= $row['tanggal']; ?><br><br>

            <span class="status-belum">● BELUM DIGUNAKAN</span>
        </div>

    <?php } ?>
<?php } else { ?>
    <div class="empty-state">Tidak ada tiket</div>
<?php } ?>
</div>

<!-- SUDAH -->
<div id="sudah" style="display:none;">
<?php if(mysqli_num_rows($sudah) > 0){ ?>
    <?php while($row = mysqli_fetch_assoc($sudah)){ ?>
        
        <div class="card-tiket" onclick="lihat('<?= $row['kode_booking'] ?>')">
            <b><?= $row['asal']; ?> → <?= $row['tujuan']; ?></b><br>
            Kode: <?= $row['kode_booking']; ?><br>
            Tanggal: <?= $row['tanggal']; ?><br><br>

            <span class="status-sudah">✔ DIGUNAKAN</span>
        </div>

    <?php } ?>
<?php } else { ?>
    <div class="empty-state">Belum ada tiket digunakan</div>
<?php } ?>
</div>

</div>

<script src="../assets/js/mobile-nav.js"></script>
<script>
function showTab(tab){
    document.getElementById("belum").style.display = tab === "belum" ? "block" : "none";
    document.getElementById("sudah").style.display = tab === "sudah" ? "block" : "none";
    const bayarEl = document.getElementById("bayar");
    if (bayarEl) bayarEl.style.display = tab === "bayar" ? "block" : "none";

    document.getElementById("btn-belum").classList.remove("active");
    document.getElementById("btn-sudah").classList.remove("active");
    const btnBayar = document.getElementById("btn-bayar");
    if (btnBayar) btnBayar.classList.remove("active");

    document.getElementById("btn-"+tab).classList.add("active");
}

function lihat(kode){
    window.location.href = "hasil_tiket.php?kode=" + kode;
}

function bayar(kode){
    window.location.href = "pembayaran.php?kode=" + encodeURIComponent(kode);
}
</script>

</body>
</html>
