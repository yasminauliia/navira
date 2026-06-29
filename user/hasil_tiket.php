<?php
session_start();
include('../config/koneksi.php');
include('../config/app.php');
include('../config/tiket_helper.php');
include('../config/payment_helper.php');
include('../config/whatsapp_helper.php');

if (!isset($_GET['kode'])) {
    die('Kode tiket tidak ditemukan!');
}

$kode = trim((string)$_GET['kode']);
if ($kode === '') {
    die('Kode tiket tidak ditemukan!');
}

ensurePaymentColumns($conn);
ensureTiketPdfColumns($conn);

$data = loadTicketByKode($conn, $kode);
if (!$data) {
    die('Tiket tidak ditemukan!');
}

$paymentStatus = normalizePaymentStatus($data['payment_status'] ?? '');
$payMeta       = getPaymentStatusMeta($paymentStatus);
$showTicket    = canShowTicketContent($data);

$waSentAt = trim((string)($data['wa_sent_at'] ?? ''));
$waAlreadySent = $waSentAt !== '';

// Otomatis kirim WA sekali setelah pembayaran lunas
if ($showTicket && !$waAlreadySent) {
    triggerWhatsAppTiketAfterPayment($conn, $kode);
}

$penumpang = null;
if ($showTicket) {
    $penumpang = mysqli_query($conn, "SELECT * FROM penumpang_detail WHERE ticket_id='" . (int)$data['id_ticket'] . "'");
}

$status_db = !empty($data['status']) ? $data['status'] : 'Belum Scan';
if ($status_db === 'Sudah Scan' || $status_db === 'DIGUNAKAN') {
    $status_text  = '✔ Sudah Digunakan';
    $status_color = '#ef4444';
} else {
    $status_text  = '● Belum Digunakan';
    $status_color = '#22c55e';
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<title><?= $showTicket ? 'Tiket Kamu' : h($payMeta['title']) ?> — Navira</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php if ($showTicket): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<?php endif; ?>
<link href="../assets/css/navy-theme.css" rel="stylesheet">
<style>
body{font-family:'Poppins',sans-serif;color:white;min-height:100vh}
.card{background:rgba(255,255,255,0.05);backdrop-filter:blur(20px);border-radius:20px;max-width:520px;margin:40px auto;padding:25px;border:1px solid rgba(255,255,255,0.1);box-shadow:0 0 40px rgba(0,0,0,0.6);text-align:center}
h2{background:linear-gradient(to right,#38bdf8,#22d3ee);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.info{text-align:left;margin-top:15px}
.info p{margin:6px 0}
.penumpang{background:rgba(255,255,255,0.05);padding:12px;border-radius:12px}
.qr-wrapper{display:flex;justify-content:center;margin-top:15px}
#qrcode{padding:15px;background:white;border-radius:15px;box-shadow:0 0 20px rgba(56,189,248,0.5)}
.status{padding:6px 12px;border-radius:10px;display:inline-block;margin-top:5px;font-weight:bold}
.pay-state-icon{font-size:48px;margin-bottom:8px}
.pay-state-msg{color:#cbd5e1;font-size:14px;line-height:1.6;margin:12px 0 18px}
.pay-badge{padding:6px 12px;border-radius:999px;font-size:12px;font-weight:800;display:inline-block;margin-top:6px}
.summary{background:rgba(255,255,255,0.04);border-radius:14px;padding:14px;text-align:left;margin-top:14px;font-size:13px}
.summary p{margin:4px 0;color:#cbd5e1}
.ticket-brand{display:flex;align-items:center;justify-content:center;gap:12px;margin-bottom:12px}
.ticket-brand .brand-name{font-size:22px;font-weight:700;letter-spacing:2px;color:#fff}

/* ══════════════════════════════════════
   FOOTER ACTIONS — fixed centering
   Kunci: box-sizing border-box di semua tombol,
   width:100% dihitung termasuk padding+border,
   sehingga lebar kedua tombol identik dan
   teks selalu center sempurna.
═══════════════════════════════════════ */
.ticket-actions{
    margin-top:24px;
    padding-top:18px;
    border-top:1px solid rgba(255,255,255,0.08);
    display:flex;
    flex-direction:column;
    gap:10px;
}

.btn-primary-action,
.btn-secondary-action{
    box-sizing:border-box;
    width:100%;
    display:flex;
    align-items:center;
    justify-content:center;
    text-align:center;
    gap:8px;
    text-decoration:none;
    cursor:pointer;
    font-family:'Poppins',sans-serif;
    transition:.25s;
}

.btn-primary-action{
    padding:14px 20px;
    border-radius:14px;
    border:none;
    background:linear-gradient(135deg,#3b82f6,#06b6d4);
    color:white;
    font-weight:700;
    font-size:14px;
    box-shadow:0 4px 18px rgba(56,189,248,0.25);
}
.btn-primary-action:hover{
    transform:translateY(-2px);
    box-shadow:0 6px 24px rgba(56,189,248,0.4);
    color:white;
}

.btn-secondary-action{
    padding:11px 18px;
    border-radius:12px;
    border:1px solid rgba(255,255,255,0.08);
    background:rgba(255,255,255,0.02);
    color:rgba(255,255,255,0.55);
    font-weight:500;
    font-size:12.5px;
}
.btn-secondary-action:hover{
    background:rgba(255,255,255,0.06);
    border-color:rgba(255,255,255,0.15);
    color:rgba(255,255,255,0.85);
}
</style>
</head>
<body class="bg-navy-animated">

<div class="card">

<div class="ticket-brand">
    <?= naviraLogoImg('../', 48) ?>
    <span class="brand-name">NAVIRA</span>
</div>

<?php if ($showTicket): ?>

<h2>🎫 Tiket Kamu</h2>
<p><?= h($data['kode_booking']) ?></p>
<span class="pay-badge" style="background:<?= h($payMeta['color']) ?>22;color:<?= h($payMeta['color']) ?>;border:1px solid <?= h($payMeta['color']) ?>44;">
    <?= h($payMeta['label']) ?>
</span>

<div class="info">
<p><b>Asal:</b> <?= h($data['asal']) ?></p>
<p><b>Tujuan:</b> <?= h($data['tujuan']) ?></p>
<p><b>Tanggal:</b> <?= h($data['tanggal']) ?></p>
<p><b>Jam:</b> <?= h($data['jam']) ?></p>
<p><b>Layanan:</b> <?= h($data['layanan']) ?></p>
<p><b>Pengguna Jasa:</b> <?= isTiketKendaraan($data) ? 'Berkendara' : 'Pejalan Kaki' ?></p>
<p><b>Status Check-in:</b><br>
<span class="status" style="background:<?= $status_color ?>20;color:<?= $status_color ?>"><?= $status_text ?></span></p>
<?php if (isTiketKendaraan($data)): ?>
<p><b>Golongan Kendaraan:</b> <?= h(getGolonganTiket($data)) ?></p>
<p><b>Plat Nomor:</b> <?= h(getPlatTiket($data)) ?></p>
<?php endif; ?>
</div>

<hr style="border-color:rgba(255,255,255,0.1)">
<h4>Detail Penumpang</h4>
<div class="penumpang">
<?php while ($p = mysqli_fetch_assoc($penumpang)): ?>
<p><?= h(ucfirst($p['kategori'])) ?> : <?= h($p['nama_lengkap']) ?></p>
<?php endwhile; ?>
</div>

<hr style="border-color:rgba(255,255,255,0.1)">
<h4>Scan QR Saat Check-in</h4>
<div class="qr-wrapper"><div id="qrcode"></div></div>
<script>
new QRCode(document.getElementById('qrcode'), {
    text: <?= json_encode($data['kode_booking']) ?>,
    width: 200,
    height: 200
});
</script>

<div class="ticket-actions">
    <a href="download_tiket.php?kode=<?= urlencode($data['kode_booking']) ?>" class="btn-primary-action">
        ⬇ Download PDF
    </a>
    <a href="dashboard.php" class="btn-secondary-action">
        ← Kembali ke Dashboard
    </a>
</div>

<?php else: ?>

<div class="pay-state-icon">
    <?= $payMeta['view'] === 'pending' ? '⏳' : '⚠️' ?>
</div>
<h2><?= h($payMeta['title']) ?></h2>
<span class="pay-badge" style="background:<?= h($payMeta['color']) ?>22;color:<?= h($payMeta['color']) ?>;border:1px solid <?= h($payMeta['color']) ?>44;">
    <?= h($payMeta['label']) ?>
</span>
<p class="pay-state-msg"><?= h($payMeta['message']) ?></p>

<div class="summary">
    <p><b>Kode Booking:</b> <?= h($data['kode_booking']) ?></p>
    <p><b>Rute:</b> <?= h($data['asal']) ?> → <?= h($data['tujuan']) ?></p>
    <p><b>Tanggal:</b> <?= h($data['tanggal']) ?> · <?= h($data['jam']) ?></p>
    <p><b>Total:</b> Rp <?= number_format((int)$data['total_harga'], 0, ',', '.') ?></p>
</div>

<div class="ticket-actions">
    <?php if ($payMeta['show_pay_button']): ?>
    <a href="pembayaran.php?kode=<?= urlencode($data['kode_booking']) ?>" class="btn-primary-action">
        💳 Lanjutkan Pembayaran
    </a>
    <?php endif; ?>
    <a href="dashboard.php" class="btn-secondary-action">
        ← Kembali ke Dashboard
    </a>
</div>

<?php endif; ?>

</div>
</body>
</html>