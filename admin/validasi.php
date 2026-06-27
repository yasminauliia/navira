<?php
include('auth.php');
include('../config/koneksi.php');
include('../config/app.php');
include('../config/payment_helper.php');

ensurePaymentColumns($conn);

// Tabel pencatatan scan tiket A (verifikasi) dan B (checkout)
mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS boarding_scans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT NOT NULL,
        pax_no INT NOT NULL,
        lane ENUM('A','B') NOT NULL,
        scanned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_scan (ticket_id, pax_no, lane)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$rawKode = trim((string)($_GET['kode'] ?? ''));
$kode = $rawKode;
$lane = null;
$paxNo = null;
$showPrintButton = false;
$isBoardingScan = false;

// Parameter URL: ?kode=XXX&tipe=A&pax=1
$tipeParam = strtoupper(trim((string)($_GET['tipe'] ?? '')));
$paxParam  = (int)($_GET['pax'] ?? 0);
if ($tipeParam === 'A' || $tipeParam === 'B') {
    $lane  = $tipeParam;
    $paxNo = $paxParam > 0 ? $paxParam : null;
    $isBoardingScan = true;
}

// Toleransi hasil scan:
// - Jika QR berisi URL, ambil parameter ?kode= & ?tipe= & ?pax=
// - Format pipe: KODE|A|1 atau KODE|B|2
// - Format dash: KODE-A-1
if (filter_var($rawKode, FILTER_VALIDATE_URL)) {
    $parts = parse_url($rawKode);
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $qv);
        if (!empty($qv['kode'])) {
            $kode = (string)$qv['kode'];
        }
        if (!empty($qv['tipe']) && in_array(strtoupper((string)$qv['tipe']), ['A', 'B'], true)) {
            $lane = strtoupper((string)$qv['tipe']);
            $isBoardingScan = true;
        }
        if (!empty($qv['pax'])) {
            $paxNo = (int)$qv['pax'];
        }
    }
}

$qrPatterns = [
    '/^(.+)\|([AB])\|(\d+)$/i',
    '/^(.+)-([AB])-(\d+)$/i',
];
foreach ($qrPatterns as $pattern) {
    if (preg_match($pattern, $kode, $m)) {
        $kode  = trim($m[1]);
        $lane  = strtoupper($m[2]);
        $paxNo = (int)$m[3];
        $isBoardingScan = true;
        break;
    }
}

$kode = trim($kode);

$stmt = mysqli_prepare($conn, "
    SELECT t.*, u.nama AS nama_akun,
        CONCAT(
            COALESCE(a.nama_pelabuhan, '-'),
            IF(a.lokasi IS NOT NULL AND a.lokasi != '', CONCAT(', ', a.lokasi), '')
        ) AS asal,
        CONCAT(
            COALESCE(b.nama_pelabuhan, '-'),
            IF(b.lokasi IS NOT NULL AND b.lokasi != '', CONCAT(', ', b.lokasi), '')
        ) AS tujuan
    FROM tickets t
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN pelabuhan a ON a.id = t.asal_id
    LEFT JOIN pelabuhan b ON b.id = t.tujuan_id
    WHERE t.kode_booking = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, 's', $kode);
mysqli_stmt_execute($stmt);
$q = mysqli_stmt_get_result($stmt);

$d = mysqli_fetch_assoc($q);

/**
 * Ambil nama penumpang berdasarkan nomor urut (sesuai cetak_tiket.php)
 */
function getPassengerName(mysqli $conn, array $ticket, int $paxNo): string {
    $ticketId = (int)$ticket['id_ticket'];
    $list = [];

    $paxQ = mysqli_query($conn, "
        SELECT kategori, jumlah, nama_lengkap
        FROM penumpang_detail
        WHERE ticket_id = {$ticketId}
        ORDER BY FIELD(kategori,'dewasa','anak','bayi'), jumlah ASC, id ASC
    ");

    while ($row = mysqli_fetch_assoc($paxQ)) {
        $kat = strtolower((string)($row['kategori'] ?? 'penumpang'));
        $nama = trim((string)($row['nama_lengkap'] ?? ''));
        $hasDetail = ($nama !== '');

        if ($hasDetail) {
            $list[] = $nama !== '' ? $nama : ucfirst($kat);
        } else {
            $jml = max(0, (int)($row['jumlah'] ?? 0));
            for ($i = 1; $i <= $jml; $i++) {
                $list[] = ucfirst($kat) . ' ' . $i;
            }
        }
    }

    if (count($list) === 0) {
        return $ticket['nama_akun'] ?? 'Penumpang';
    }

    $idx = $paxNo - 1;
    return $list[$idx] ?? ('Penumpang ' . $paxNo);
}

// Nama yang ditampilkan
$namaTampil = '';
if ($d) {
    if ($lane && $paxNo) {
        $namaTampil = getPassengerName($conn, $d, $paxNo);
    } else {
        $namaTampil = trim((string)($d['nama_pemesan'] ?? ''));
        if ($namaTampil === '') {
            $tid = (int)$d['id_ticket'];
            $paxQ = mysqli_query($conn, "
                SELECT nama_lengkap FROM penumpang_detail
                WHERE ticket_id = {$tid} AND TRIM(nama_lengkap) != ''
                ORDER BY FIELD(kategori,'dewasa','anak','bayi'), jumlah ASC, id ASC
                LIMIT 1
            ");
            if ($paxQ && ($row = mysqli_fetch_assoc($paxQ))) {
                $namaTampil = trim((string)$row['nama_lengkap']);
            }
        }
        if ($namaTampil === '') {
            $namaTampil = trim((string)($d['nama_akun'] ?? ''));
        }
    }
}

$status  = "";
$pesan   = "";
$warna   = "";
$success = true;

if ($d) {

    if (!isTicketPaid($d)) {
        $status  = 'PEMBAYARAN BELUM LUNAS';
        $pesan   = 'Tiket belum dibayar. Scan ditolak hingga status pembayaran lunas.';
        $warna   = '#ef4444';
        $success = false;
        $d       = null;
    }
}

if ($d) {

    // ── SCAN TIKET A / B (thermal boarding pass) ──
    if ($lane === 'A' || $lane === 'B') {

        $ticketId = (int)$d['id_ticket'];

        if (!$paxNo || $paxNo < 1) {
            $status  = "QR TIDAK VALID";
            $pesan   = "Data penumpang pada QR tidak lengkap.";
            $warna   = "#ef4444";
            $success = false;

        } else {
            // Otomatis tandai check-in jika belum dilakukan
            if ($d['status'] !== 'DIGUNAKAN') {
                $u = mysqli_prepare($conn, "UPDATE tickets SET status='DIGUNAKAN' WHERE kode_booking=?");
                mysqli_stmt_bind_param($u, 's', $kode);
                mysqli_stmt_execute($u);
                $d['status'] = 'DIGUNAKAN';
            }
            // Cek apakah lane ini sudah pernah di-scan
            $cek = mysqli_prepare($conn, "
                SELECT id FROM boarding_scans
                WHERE ticket_id = ? AND pax_no = ? AND lane = ?
                LIMIT 1
            ");
            mysqli_stmt_bind_param($cek, 'iis', $ticketId, $paxNo, $lane);
            mysqli_stmt_execute($cek);
            $cekRes = mysqli_stmt_get_result($cek);
            $sudahScan = mysqli_fetch_assoc($cekRes);

            if ($lane === 'A') {

                if ($sudahScan) {
                    $status  = "SUDAH DIVERIFIKASI";
                    $pesan   = "Tiket A penumpang #{$paxNo} sudah diverifikasi sebelumnya.";
                    $warna   = "#f97316";
                    $success = false;
                } else {
                    $ins = mysqli_prepare($conn, "
                        INSERT INTO boarding_scans (ticket_id, pax_no, lane) VALUES (?, ?, 'A')
                    ");
                    mysqli_stmt_bind_param($ins, 'ii', $ticketId, $paxNo);
                    mysqli_stmt_execute($ins);

                    $status  = "VERIFIKASI BERHASIL";
                    $pesan   = "Tiket A penumpang #{$paxNo} — verifikasi berhasil ✔";
                    $warna   = "#22c55e";
                    $success = true;
                }

            } else { // lane B

                // Tiket B hanya boleh setelah tiket A diverifikasi
                $cekA = mysqli_prepare($conn, "
                    SELECT id FROM boarding_scans
                    WHERE ticket_id = ? AND pax_no = ? AND lane = 'A'
                    LIMIT 1
                ");
                mysqli_stmt_bind_param($cekA, 'ii', $ticketId, $paxNo);
                mysqli_stmt_execute($cekA);
                $cekARes = mysqli_stmt_get_result($cekA);
                $adaA = mysqli_fetch_assoc($cekARes);

                if (!$adaA) {
                    $status  = "BELUM VERIFIKASI";
                    $pesan   = "Scan Tiket A terlebih dahulu sebelum checkout.";
                    $warna   = "#f97316";
                    $success = false;

                } elseif ($sudahScan) {
                    $status  = "SUDAH CHECKOUT";
                    $pesan   = "Tiket B penumpang #{$paxNo} sudah checkout sebelumnya.";
                    $warna   = "#f97316";
                    $success = false;

                } else {
                    $ins = mysqli_prepare($conn, "
                        INSERT INTO boarding_scans (ticket_id, pax_no, lane) VALUES (?, ?, 'B')
                    ");
                    mysqli_stmt_bind_param($ins, 'ii', $ticketId, $paxNo);
                    mysqli_stmt_execute($ins);

                    $status  = "CHECKOUT BERHASIL";
                    $pesan   = "Tiket B penumpang #{$paxNo} — checkout berhasil ✔";
                    $warna   = "#22c55e";
                    $success = true;
                }
            }
        }

    // ── SCAN E-TICKET (check-in pelabuhan) ──
    } else {

        $tz    = new DateTimeZone('Asia/Jakarta');
        $now   = new DateTime('now', $tz);
        $mulai = DateTime::createFromFormat('Y-m-d H:i:s', $d['tanggal'].' '.$d['jam'], $tz);

        if ($mulai) {
            $selesai = clone $mulai;
            $selesai->modify('+1 hour');
        } else {
            $selesai = null;
        }

        if ($selesai && $now > $selesai) {
            $status  = "KADALUARSA";
            $pesan   = "Jam check-in untuk tiket ini sudah lewat.";
            $warna   = "#ef4444";
            $success = false;

        } elseif ($d['status'] == 'BELUM DIGUNAKAN') {
            $u = mysqli_prepare($conn, "UPDATE tickets SET status='DIGUNAKAN' WHERE kode_booking=?");
            mysqli_stmt_bind_param($u, 's', $kode);
            mysqli_stmt_execute($u);

            $status  = "VALID";
            $pesan   = "Silakan masuk 🚢";
            $warna   = "#22c55e";
            $success = true;
            $showPrintButton = true;

        } else {
            $status  = "SUDAH DIGUNAKAN";
            $pesan   = "Tiket sudah dipakai!";
            $warna   = "#f97316";
            $success = false;
        }
    }

} else {
    $status  = "TIDAK DITEMUKAN";
    $pesan   = "QR tidak valid!";
    $warna   = "#ef4444";
    $success = false;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Validasi Tiket</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#0f172a;
    display:flex;
    align-items:center;
    justify-content:center;
    height:100vh;
    font-family:Poppins;
}
.box{
    background:white;
    padding:30px;
    border-radius:20px;
    width:380px;
    text-align:center;
}
.ticket-brand{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    margin-bottom:14px;
}
.ticket-brand .brand-name{
    font-size:18px;
    font-weight:700;
    letter-spacing:2px;
    color:#1E2E4F;
}
.badge-status{
    padding:8px 15px;
    border-radius:10px;
    color:white;
    font-weight:bold;
}
.btn-print{
    background:#0ea5e9;
    color:white;
    border:none;
}
.btn-print:hover{
    background:#0284c7;
    color:white;
}
</style>
</head>

<body>

<div class="box">

<div class="ticket-brand">
    <?= naviraLogoImg('../', 40) ?>
    <span class="brand-name">NAVIRA</span>
</div>

<div class="badge-status mb-3" style="background:<?= $warna ?>">
    <?= $status ?>
</div>

<p><?= $pesan ?></p>

<?php if($d): ?>
<p><b><?= htmlspecialchars($namaTampil) ?></b></p>
<p><?= $d['asal'] ?> → <?= $d['tujuan'] ?></p>
<?php if($lane && $paxNo): ?>
<p class="text-muted small">Tiket <?= htmlspecialchars($lane) ?> • Penumpang #<?= (int)$paxNo ?></p>
<?php endif; ?>
<?php endif; ?>

<?php if($d && $success && $showPrintButton): ?>
<div class="d-grid gap-2 mt-3">
    <a href="cetak_tiket.php?kode=<?= urlencode($kode) ?>&auto=1" target="_blank" class="btn btn-print">🖨️ Cetak Tiket (A + B)</a>
</div>
<?php endif; ?>

<a href="dashboard.php" class="btn btn-success w-100 mt-2">📊 Dashboard</a>
<a href="scan.php" class="btn btn-primary w-100 mt-2">📷 Scan Lagi</a>

</div>

<script>
localStorage.setItem("updateDashboard", Date.now());

let audio = new Audio(
<?= $success 
    ? "'https://actions.google.com/sounds/v1/cartoon/clang_and_wobble.ogg'" 
    : "'https://actions.google.com/sounds/v1/alarms/beep_short.ogg'" ?>
);
audio.play();

<?php if(!$success && !$isBoardingScan): ?>
setTimeout(()=>{
    window.location.href = "scan.php";
}, 2000);
<?php endif; ?>

</script>

</body>
</html>
