<?php
session_start();
include('../config/koneksi.php');
include('../config/payment_helper.php');

// ── Guard: harus login ──
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ================================================================
//  AMBIL & BERSIHKAN DATA POST
// ================================================================
$order_id       = trim($_POST['order_id']       ?? '');
$asal_id_post   = trim($_POST['asal_id']        ?? '');   // FIX: pakai asal_id bukan asal_nama
$tujuan_id_post = trim($_POST['tujuan_id']      ?? '');   // FIX: pakai tujuan_id bukan tujuan_nama
$tanggal        = trim($_POST['tanggal']        ?? '');
$jam            = trim($_POST['jam']            ?? '');
$layanan        = trim($_POST['layanan']        ?? 'reguler');
$jenis_pengguna = trim($_POST['jenis_pengguna'] ?? 'penumpang');
$total_harga    = (int)($_POST['total_harga']   ?? 0);

$nama_pemesan  = strtoupper(trim($_POST['nama_pemesan']  ?? ''));
$hp_pemesan    = preg_replace('/\D/', '', $_POST['hp_pemesan'] ?? '');
$email_pemesan = trim($_POST['email_pemesan']            ?? '');

$user_id = (int)$_SESSION['user_id'];

// ── Ambil asal_id & tujuan_id dari session (integer) ──
$asal_id   = (int)($_SESSION['order']['asal_id']   ?? 0);
$tujuan_id = (int)($_SESSION['order']['tujuan_id'] ?? 0);

// Jumlah penumpang dari session
$jml_dewasa      = (int)($_SESSION['order']['dewasa'] ?? 1);
$jml_anak        = (int)($_SESSION['order']['anak']   ?? 0);
$jml_bayi        = (int)($_SESSION['order']['bayi']   ?? 0);
$total_penumpang = $jml_dewasa + $jml_anak + $jml_bayi;

// ================================================================
//  VALIDASI SERVER-SIDE
// ================================================================

// FIX: cek asal_id_post & tujuan_id_post (bukan asal_nama/tujuan_nama yang tidak pernah kosong)
if (!$order_id || !$asal_id_post || !$tujuan_id_post || !$tanggal || !$jam) {
    $_SESSION['error'] = 'Data pesanan tidak lengkap. Silakan ulangi dari awal.';
    header("Location: beli_tiket.php");
    exit;
}

// FIX: kalau session asal_id/tujuan_id masih 0, fallback ke POST supaya tidak langsung balik ke beli_tiket
if ($asal_id === 0) $asal_id = (int)$asal_id_post;
if ($tujuan_id === 0) $tujuan_id = (int)$tujuan_id_post;

if ($asal_id === 0 || $tujuan_id === 0) {
    $_SESSION['error'] = 'Data pelabuhan tidak ditemukan. Silakan ulangi dari awal.';
    header("Location: beli_tiket.php");
    exit;
}

if (!$nama_pemesan || strlen($hp_pemesan) < 9 || !filter_var($email_pemesan, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Data pemesan tidak lengkap atau tidak valid.';
    header("Location: isi_data_penumpang.php");
    exit;
}

// Data kendaraan (jika jenis = kendaraan)
$kendaraan = '';
$golongan  = '';
$plat      = '';
if ($jenis_pengguna === 'kendaraan') {
    $kendaraan = $_SESSION['order']['kendaraan'] ?? '';
    $golongan  = $_SESSION['order']['golongan']  ?? '';
    $plat      = strtoupper(trim($_SESSION['order']['plat'] ?? ''));
}

// ================================================================
//  TRANSAKSI DATABASE
// ================================================================
try {
    ensurePaymentColumns($conn);
    $conn->begin_transaction();

    // ── 1. Cek duplikat kode_booking ──
    $cek = $conn->prepare("SELECT id_ticket FROM tickets WHERE kode_booking = ?");
    $cek->bind_param("s", $order_id);
    $cek->execute();
    $cek->store_result();
    if ($cek->num_rows > 0) {
        throw new Exception("Kode booking sudah digunakan. Silakan coba lagi.");
    }
    $cek->close();

    // ── 2. INSERT ke tabel tickets ──
    $sql_ticket = "INSERT INTO tickets
        (user_id, nama_pemesan, hp_pemesan, email_pemesan, kode_booking, status, payment_status, tanggal, asal_id, tujuan_id, jam,
         layanan, jenis_pengguna, kendaraan, golongan, plat,
         total_harga, total_penumpang)
        VALUES (?, ?, ?, ?, ?, 'BELUM DIGUNAKAN', 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql_ticket);
    if (!$stmt) throw new Exception("Prepare tickets gagal: " . $conn->error);

    $stmt->bind_param(
        "isssssiissssssii",
        $user_id,
        $nama_pemesan,
        $hp_pemesan,
        $email_pemesan,
        $order_id,
        $tanggal,
        $asal_id,
        $tujuan_id,
        $jam,
        $layanan,
        $jenis_pengguna,
        $kendaraan,
        $golongan,
        $plat,
        $total_harga,
        $total_penumpang
    );
    $stmt->execute();
    $ticket_id = (int)$conn->insert_id;
    $stmt->close();

    // ── 3. Prepare INSERT penumpang_detail ──
    $sql_pax = "INSERT INTO penumpang_detail
        (ticket_id, kategori, jumlah, titel, nama_lengkap, jenis_id, nomor_id, usia, kota_asal, no_tlp)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt_pax = $conn->prepare($sql_pax);
    if (!$stmt_pax) throw new Exception("Prepare penumpang_detail gagal: " . $conn->error);

    // ── 4. Dewasa ──
    for ($i = 1; $i <= $jml_dewasa; $i++) {
        $kategori = 'dewasa';
        $urutan   = $i;
        $titel    = trim($_POST["titel_dewasa_$i"]    ?? 'Tuan');
        $nama     = strtoupper(trim($_POST["nama_dewasa_$i"] ?? ''));
        $jenis_id = trim($_POST["jenis_id_dewasa_$i"] ?? 'KTP');
        $nomor_id = preg_replace('/\D/', '', $_POST["no_id_dewasa_$i"] ?? '');
        $usia     = (int)($_POST["usia_dewasa_$i"]    ?? 0);
        $kota     = ucwords(strtolower(trim($_POST["kota_dewasa_$i"] ?? '')));

        $no_tlp = 0;
        $stmt_pax->bind_param("isissssisi",
            $ticket_id, $kategori, $urutan,
            $titel, $nama, $jenis_id, $nomor_id,
            $usia, $kota, $no_tlp
        );
        $stmt_pax->execute();
    }

    // ── 5. Anak ──
    for ($i = 1; $i <= $jml_anak; $i++) {
        $kategori = 'anak';
        $urutan   = $i;
        $titel    = trim($_POST["titel_anak_$i"]    ?? 'Ananda');
        $nama     = strtoupper(trim($_POST["nama_anak_$i"] ?? ''));
        $jenis_id = trim($_POST["jenis_id_anak_$i"] ?? 'Akta');
        $nomor_id = trim($_POST["no_id_anak_$i"]    ?? '');
        $usia     = (int)($_POST["usia_anak_$i"]    ?? 0);
        $kota     = ucwords(strtolower(trim($_POST["kota_anak_$i"] ?? '')));

        $no_tlp = 0;
        $stmt_pax->bind_param("isissssisi",
            $ticket_id, $kategori, $urutan,
            $titel, $nama, $jenis_id, $nomor_id,
            $usia, $kota, $no_tlp
        );
        $stmt_pax->execute();
    }

    // ── 6. Bayi (tanpa ID, tanpa kota) ──
    for ($i = 1; $i <= $jml_bayi; $i++) {
        $kategori = 'bayi';
        $urutan   = $i;
        $titel    = 'Ananda';
        $nama     = strtoupper(trim($_POST["nama_bayi_$i"] ?? ''));
        $jenis_id = '-';
        $nomor_id = '-';
        $usia     = (int)($_POST["usia_bayi_$i"] ?? 0);
        $kota     = '-';

        $no_tlp = 0;
        $stmt_pax->bind_param("isissssisi",
            $ticket_id, $kategori, $urutan,
            $titel, $nama, $jenis_id, $nomor_id,
            $usia, $kota, $no_tlp
        );
        $stmt_pax->execute();
    }

    $stmt_pax->close();

    // ── 7. Update session untuk halaman berikutnya ──
    $_SESSION['order']['order_id']      = $order_id;
    $_SESSION['order']['ticket_id']     = $ticket_id;
    $_SESSION['order']['nama_pemesan']  = $nama_pemesan;
    $_SESSION['order']['hp_pemesan']    = $hp_pemesan;
    $_SESSION['order']['email_pemesan'] = $email_pemesan;
    $_SESSION['order']['total_harga']   = $total_harga;

    $conn->commit();

    // ── 8. Lanjut ke Step 2: Verifikasi Data ──
    header("Location: verifikasi_data.php");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = 'Gagal menyimpan data: ' . $e->getMessage();
    header("Location: isi_data_penumpang.php");
    exit;
}
?>