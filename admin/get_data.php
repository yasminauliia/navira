<?php
// ═══════════════════════════════════════════════════════════════
// get_data.php  —  Return JSON statistik tiket
// Dipanggil AJAX setiap 3 detik oleh dashboard.php
// ═══════════════════════════════════════════════════════════════

include('auth.php');
include('../config/koneksi.php');
include('../config/payment_helper.php');

ensurePaymentColumns($conn);

header('Content-Type: application/json');

function q(mysqli $conn, string $sql): int {
    $r = mysqli_query($conn, $sql);
    if (!$r) return 0;
    $row = mysqli_fetch_assoc($r);
    return (int)($row['c'] ?? 0);
}

$paidSql  = sqlPaidTicketsCondition();
$total    = q($conn, "SELECT COUNT(*) AS c FROM tickets WHERE {$paidSql}");
$digunakan = q($conn, "SELECT COUNT(*) AS c FROM tickets WHERE status = 'DIGUNAKAN' AND {$paidSql}");
$belum    = q($conn, "SELECT COUNT(*) AS c FROM tickets WHERE status = 'BELUM DIGUNAKAN' AND {$paidSql}");
$hari_ini = q($conn, "SELECT COUNT(*) AS c FROM tickets WHERE tanggal = CURDATE() AND {$paidSql}");
$pending  = q($conn, "SELECT COUNT(*) AS c FROM tickets WHERE " . sqlPendingPaymentCondition());

// Total pendapatan keseluruhan (hanya paid)
$r_harga  = mysqli_query($conn, "SELECT COALESCE(SUM(total_harga), 0) AS total FROM tickets WHERE {$paidSql}");
$total_pendapatan = 0;
if ($r_harga) {
    $row_h = mysqli_fetch_assoc($r_harga);
    $total_pendapatan = (int)($row_h['total'] ?? 0);
}

// Total penumpang keseluruhan (hanya paid)
$r_pax   = mysqli_query($conn, "SELECT COALESCE(SUM(total_penumpang), 0) AS total FROM tickets WHERE {$paidSql}");
$total_pax = 0;
if ($r_pax) {
    $row_p = mysqli_fetch_assoc($r_pax);
    $total_pax = (int)($row_p['total'] ?? 0);
}

echo json_encode([
    'total'             => $total,
    'pakai'             => $digunakan,
    'belum'             => $belum,
    'hari_ini'          => $hari_ini,
    'pending'           => $pending,
    'total_pendapatan'  => $total_pendapatan,
    'total_pax'         => $total_pax,
]);