<?php
// ════════════════════════════════════════════════════════
// SUPERADMIN — KELOLA PELABUHAN
// ════════════════════════════════════════════════════════
include('auth.php');
include('../config/koneksi.php');

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'super_admin'){
    die("Akses ditolak!");
}

$msg   = '';
$error = '';

// ═══════════════════════════════════════════
// TAMBAH PELABUHAN
// ═══════════════════════════════════════════
if(isset($_POST['tambah_pelabuhan'])){
    $nama        = trim($_POST['nama_pelabuhan'] ?? '');
    $lokasi      = trim($_POST['lokasi'] ?? '');
    $pairNama    = trim($_POST['t_pair_name'] ?? '');
    $pairLokasi  = trim($_POST['t_lokasi_tujuan'] ?? '');

    // Semua field wajib diisi
    if($nama === '' || $lokasi === '' || $pairNama === '' || $pairLokasi === ''){
        $error = 'Semua field wajib diisi termasuk pelabuhan tujuan dan lokasinya!';
    } elseif(strtolower($nama) === strtolower($pairNama)){
        $error = 'Nama pelabuhan asal dan tujuan tidak boleh sama!';
    } else {
        // Cek duplikat nama asal
        $cek = mysqli_prepare($conn, "SELECT id FROM pelabuhan WHERE LOWER(nama_pelabuhan) = LOWER(?)");
        mysqli_stmt_bind_param($cek, 's', $nama);
        mysqli_stmt_execute($cek);
        mysqli_stmt_store_result($cek);

        if(mysqli_stmt_num_rows($cek) > 0){
            $error = "Pelabuhan <b>" . htmlspecialchars($nama) . "</b> sudah ada di database!";
            mysqli_stmt_close($cek);
        } else {
            mysqli_stmt_close($cek);

            // Cek apakah pelabuhan tujuan sudah ada di database
            $cekPair = mysqli_prepare($conn, "SELECT id FROM pelabuhan WHERE LOWER(nama_pelabuhan) = LOWER(?) LIMIT 1");
            mysqli_stmt_bind_param($cekPair, 's', $pairNama);
            mysqli_stmt_execute($cekPair);
            mysqli_stmt_bind_result($cekPair, $existingPairId);
            $pairId = null;
            if(mysqli_stmt_fetch($cekPair)) $pairId = (int)$existingPairId;
            mysqli_stmt_close($cekPair);

            if($pairId){
                // Pelabuhan tujuan sudah ada — insert asal saja, pair ke yang sudah ada
                $stmt = mysqli_prepare($conn, "INSERT INTO pelabuhan (nama_pelabuhan, lokasi, pair_id) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($stmt, 'ssi', $nama, $lokasi, $pairId);
                mysqli_stmt_execute($stmt);
                $newId = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);

                // Update pair_id pelabuhan tujuan agar balik ke pelabuhan baru
                $upd = mysqli_prepare($conn, "UPDATE pelabuhan SET pair_id=? WHERE id=?");
                mysqli_stmt_bind_param($upd, 'ii', $newId, $pairId);
                mysqli_stmt_execute($upd);
                mysqli_stmt_close($upd);

            } else {
                // Pelabuhan tujuan belum ada — insert tujuan dulu (pair_id sementara NULL)
                $stmtPair = mysqli_prepare($conn, "INSERT INTO pelabuhan (nama_pelabuhan, lokasi, pair_id) VALUES (?, ?, NULL)");
                mysqli_stmt_bind_param($stmtPair, 'ss', $pairNama, $pairLokasi);
                mysqli_stmt_execute($stmtPair);
                $pairId = mysqli_insert_id($conn);
                mysqli_stmt_close($stmtPair);

                // Insert pelabuhan asal dengan pair_id ke tujuan
                $stmt = mysqli_prepare($conn, "INSERT INTO pelabuhan (nama_pelabuhan, lokasi, pair_id) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($stmt, 'ssi', $nama, $lokasi, $pairId);
                mysqli_stmt_execute($stmt);
                $newId = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);

                // Update pair_id pelabuhan tujuan balik ke asal
                $upd = mysqli_prepare($conn, "UPDATE pelabuhan SET pair_id=? WHERE id=?");
                mysqli_stmt_bind_param($upd, 'ii', $newId, $pairId);
                mysqli_stmt_execute($upd);
                mysqli_stmt_close($upd);
            }

            header("Location: kelola_pelabuhan.php?status=added"); exit;
        }
    }
}

// ═══════════════════════════════════════════
// EDIT PELABUHAN
// ═══════════════════════════════════════════
if(isset($_POST['edit_pelabuhan'])){
    $id         = (int)($_POST['edit_id'] ?? 0);
    $nama       = trim($_POST['edit_nama'] ?? '');
    $lokasi     = trim($_POST['edit_lokasi'] ?? '');
    $pairNama   = trim($_POST['edit_pair_name'] ?? '');
    $pairLokasi = trim($_POST['edit_lokasi_tujuan'] ?? '');

    if($nama === '' || $lokasi === '' || $pairNama === '' || $pairLokasi === ''){
        $error = 'Semua field wajib diisi termasuk pelabuhan tujuan dan lokasinya!';
    } elseif(strtolower($nama) === strtolower($pairNama)){
        $error = 'Nama pelabuhan asal dan tujuan tidak boleh sama!';
    } else {
        // Cek duplikat nama asal (kecuali data sendiri)
        $cek = mysqli_prepare($conn, "SELECT id FROM pelabuhan WHERE LOWER(nama_pelabuhan) = LOWER(?) AND id != ?");
        mysqli_stmt_bind_param($cek, 'si', $nama, $id);
        mysqli_stmt_execute($cek);
        mysqli_stmt_store_result($cek);

        if(mysqli_stmt_num_rows($cek) > 0){
            $error = "Nama pelabuhan <b>" . htmlspecialchars($nama) . "</b> sudah digunakan!";
            mysqli_stmt_close($cek);
        } else {
            mysqli_stmt_close($cek);

            $pairId = null;

            // Cari apakah pelabuhan tujuan sudah ada (selain diri sendiri)
            $cekPair = mysqli_prepare($conn, "SELECT id FROM pelabuhan WHERE LOWER(nama_pelabuhan) = LOWER(?) AND id != ? LIMIT 1");
            mysqli_stmt_bind_param($cekPair, 'si', $pairNama, $id);
            mysqli_stmt_execute($cekPair);
            mysqli_stmt_bind_result($cekPair, $foundPairId);
            if(mysqli_stmt_fetch($cekPair)) $pairId = (int)$foundPairId;
            mysqli_stmt_close($cekPair);

            if(!$pairId){
                // Pelabuhan tujuan belum ada — insert baru
                $stmtNew = mysqli_prepare($conn, "INSERT INTO pelabuhan (nama_pelabuhan, lokasi, pair_id) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($stmtNew, 'ssi', $pairNama, $pairLokasi, $id);
                mysqli_stmt_execute($stmtNew);
                $pairId = mysqli_insert_id($conn);
                mysqli_stmt_close($stmtNew);
            } else {
                // Update lokasi pelabuhan tujuan yang sudah ada
                $updLokasi = mysqli_prepare($conn, "UPDATE pelabuhan SET lokasi=? WHERE id=?");
                mysqli_stmt_bind_param($updLokasi, 'si', $pairLokasi, $pairId);
                mysqli_stmt_execute($updLokasi);
                mysqli_stmt_close($updLokasi);
            }

            // Update pair_id pelabuhan tujuan agar balik ke pelabuhan ini
            $upd = mysqli_prepare($conn, "UPDATE pelabuhan SET pair_id=? WHERE id=?");
            mysqli_stmt_bind_param($upd, 'ii', $id, $pairId);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);

            // Update pelabuhan asal
            $stmt = mysqli_prepare($conn, "UPDATE pelabuhan SET nama_pelabuhan=?, lokasi=?, pair_id=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'ssii', $nama, $lokasi, $pairId, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            header("Location: kelola_pelabuhan.php?status=updated"); exit;
        }
    }
}

// ═══════════════════════════════════════════
// HAPUS PELABUHAN
// ═══════════════════════════════════════════
if(isset($_POST['hapus_pelabuhan'])){
    $id = (int)($_POST['hapus_id'] ?? 0);

    if($id <= 0){
        header("Location: kelola_pelabuhan.php?status=invalid"); exit;
    }

    // Hanya tiket yang memblokir — harga & harga_kendaraan ikut terhapus otomatis (ON DELETE CASCADE)
    $cekTiket = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM tickets WHERE asal_id=? OR tujuan_id=?");
    mysqli_stmt_bind_param($cekTiket, 'ii', $id, $id);
    mysqli_stmt_execute($cekTiket);
    mysqli_stmt_bind_result($cekTiket, $jmlTiket);
    mysqli_stmt_fetch($cekTiket);
    $jmlTiket = (int)$jmlTiket;
    mysqli_stmt_close($cekTiket);

    if($jmlTiket > 0){
        $_SESSION['pelabuhan_error'] = "Pelabuhan tidak bisa dihapus karena masih dipakai di <b>{$jmlTiket} tiket</b>. "
            . 'Hapus atau ubah tiket tersebut terlebih dahulu (phpMyAdmin / database). '
            . 'Data harga rute untuk pelabuhan ini akan otomatis terhapus setelah pelabuhan berhasil dihapus.';
        header("Location: kelola_pelabuhan.php?status=in_use"); exit;
    }

    // Lepas pair_id dari pelabuhan pasangannya dulu
    $updPair = mysqli_prepare($conn, "UPDATE pelabuhan SET pair_id=NULL WHERE pair_id=?");
    mysqli_stmt_bind_param($updPair, 'i', $id);
    mysqli_stmt_execute($updPair);
    mysqli_stmt_close($updPair);

    $stmt = mysqli_prepare($conn, "DELETE FROM pelabuhan WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $ok = mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    $dbErr = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);

    if($ok && $affected > 0){
        header("Location: kelola_pelabuhan.php?status=deleted"); exit;
    }

    $_SESSION['pelabuhan_error'] = $dbErr !== ''
        ? 'Gagal menghapus pelabuhan: ' . $dbErr
        : 'Gagal menghapus pelabuhan (data tidak ditemukan atau dibatalkan database).';
    header("Location: kelola_pelabuhan.php?status=delete_failed"); exit;
}

// ═══════════════════════════════════════════
// NOTIFIKASI
// ═══════════════════════════════════════════
if(isset($_GET['status'])){
    $notif = [
        'added'          => '✅ Pelabuhan berhasil ditambahkan!',
        'updated'        => '✅ Pelabuhan berhasil diperbarui!',
        'deleted'        => '🗑️ Pelabuhan berhasil dihapus!',
        'in_use'         => '⚠️ Pelabuhan tidak dapat dihapus karena masih dipakai.',
        'delete_failed'  => '⚠️ Pelabuhan gagal dihapus dari database.',
        'invalid'        => '⚠️ Data pelabuhan tidak valid.',
    ];
    $msg = $notif[$_GET['status']] ?? '';
}

if(!empty($_SESSION['pelabuhan_error'])){
    $error = $_SESSION['pelabuhan_error'];
    unset($_SESSION['pelabuhan_error']);
}

// ═══════════════════════════════════════════
// AMBIL SEMUA DATA PELABUHAN
// ═══════════════════════════════════════════
$query = mysqli_query($conn, "SELECT * FROM pelabuhan ORDER BY nama_pelabuhan ASC");
$rows  = [];
while($r = mysqli_fetch_assoc($query)) $rows[] = $r;
$total = count($rows);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Pelabuhan — Super Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="../assets/css/sidebar.css" rel="stylesheet">
<link href="../assets/css/responsive.css" rel="stylesheet">
<style>
/* ════ BASE ════ */
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }

body {
    font-family:'Poppins','Segoe UI',sans-serif;
    background:linear-gradient(135deg,#020617,#0f172a,#1e3a8a);
    min-height:100vh;
    color:white;
}

/* ════ LAYOUT ════ */
.main-wrap { margin-left:240px; padding:30px; min-height:100vh; }

/* ════ PAGE HEADER ════ */
.page-header {
    display:flex; justify-content:space-between; align-items:center;
    margin-bottom:24px; flex-wrap:wrap; gap:12px;
}
.page-title { font-size:22px; font-weight:700; }
.page-title span { color:#38bdf8; }

/* ════ GLASS CARD ════ */
.gcard {
    background:rgba(255,255,255,0.04);
    backdrop-filter:blur(16px);
    border:1px solid rgba(255,255,255,0.08);
    border-radius:18px; padding:24px;
    margin-bottom:24px;
    box-shadow:0 4px 30px rgba(0,0,0,0.3);
}
.gcard-title {
    font-size:14px; font-weight:700; color:#38bdf8;
    text-transform:uppercase; letter-spacing:1px;
    margin-bottom:18px;
    display:flex; align-items:center; gap:8px;
}

/* ════ FORM ════ */
.flabel {
    font-size:11px; color:#64748b; font-weight:600;
    text-transform:uppercase; letter-spacing:0.6px;
    margin-bottom:6px; display:block;
}
.finput {
    width:100%;
    background:rgba(255,255,255,0.06);
    border:1.5px solid rgba(255,255,255,0.1);
    border-radius:10px; color:white;
    padding:10px 13px;
    font-family:'Poppins',sans-serif; font-size:13px;
    transition:0.25s; outline:none;
}
.finput:focus {
    border-color:#38bdf8;
    box-shadow:0 0 0 3px rgba(56,189,248,0.12);
}
.finput::placeholder { color:rgba(255,255,255,0.25); }
.finput option {
    background:#0f172a;
    color:white;
}

/* ════ DIVIDER MODAL ════ */
.modal-divider {
    border:none;
    border-top:1px solid rgba(255,255,255,0.08);
    margin:4px 0 8px 0;
}
.section-label {
    font-size:10px; font-weight:700; color:#38bdf8;
    text-transform:uppercase; letter-spacing:1px;
    margin-bottom:10px; margin-top:4px;
    display:flex; align-items:center; gap:6px;
}

/* ════ BUTTONS ════ */
.btn-add {
    background:linear-gradient(135deg,#2563eb,#38bdf8);
    border:none; border-radius:10px; color:white;
    padding:10px 22px; font-family:'Poppins',sans-serif;
    font-weight:600; font-size:13px; cursor:pointer;
    transition:0.25s; white-space:nowrap;
}
.btn-add:hover { opacity:0.88; transform:translateY(-1px); box-shadow:0 0 18px rgba(56,189,248,0.35); }

.btn-edit {
    background:rgba(251,191,36,0.15);
    border:1px solid rgba(251,191,36,0.3);
    color:#fbbf24; border-radius:8px;
    padding:5px 12px; font-size:12px; font-weight:600;
    cursor:pointer; font-family:'Poppins',sans-serif;
    transition:0.2s;
}
.btn-edit:hover { background:rgba(251,191,36,0.25); }

.btn-del {
    background:rgba(239,68,68,0.12);
    border:1px solid rgba(239,68,68,0.25);
    color:#f87171; border-radius:8px;
    padding:5px 12px; font-size:12px; font-weight:600;
    cursor:pointer; font-family:'Poppins',sans-serif;
    transition:0.2s;
}
.btn-del:hover { background:rgba(239,68,68,0.22); }

.btn-open-tambah {
    background:linear-gradient(135deg,#2563eb,#38bdf8);
    border:none; border-radius:10px; color:white;
    padding:10px 20px; font-family:'Poppins',sans-serif;
    font-weight:600; font-size:13px; cursor:pointer;
    transition:0.25s; display:flex; align-items:center; gap:8px;
}
.btn-open-tambah:hover { opacity:0.88; box-shadow:0 0 18px rgba(56,189,248,0.35); }

/* ════ SEARCH ════ */
.search-wrap { position:relative; }
.search-wrap input {
    background:rgba(255,255,255,0.05);
    border:1.5px solid rgba(255,255,255,0.09);
    border-radius:10px; color:white;
    padding:9px 14px 9px 36px;
    font-family:'Poppins',sans-serif; font-size:13px;
    outline:none; transition:0.25s; width:220px;
}
.search-wrap input:focus { border-color:#38bdf8; box-shadow:0 0 0 3px rgba(56,189,248,0.1); }
.search-wrap input::placeholder { color:rgba(255,255,255,0.3); }
.search-icon {
    position:absolute; left:11px; top:50%;
    transform:translateY(-50%); color:#64748b; font-size:14px;
}

/* ════ TABLE ════ */
.tbl-wrap {
    overflow-x:auto; border-radius:14px;
    border:1px solid rgba(255,255,255,0.07);
}
.tbl-wrap table { width:100%; border-collapse:collapse; min-width:500px; }
.tbl-wrap thead th {
    background:rgba(255,255,255,0.05);
    color:#64748b; font-size:11px; font-weight:700;
    text-transform:uppercase; letter-spacing:0.8px;
    padding:13px 16px; text-align:left;
    border-bottom:1px solid rgba(255,255,255,0.06);
    white-space:nowrap;
}
.tbl-wrap tbody td {
    padding:12px 16px; font-size:13px; color:#e2e8f0;
    border-bottom:1px solid rgba(255,255,255,0.04);
    vertical-align:middle;
}
.tbl-wrap tbody tr:hover { background:rgba(255,255,255,0.025); }
.tbl-wrap tbody tr:last-child td { border-bottom:none; }

/* ════ BADGE ════ */
.badge-loc {
    background:rgba(56,189,248,0.12);
    border:1px solid rgba(56,189,248,0.2);
    color:#7dd3fc; border-radius:20px;
    padding:3px 12px; font-size:11px; font-weight:600;
}
.badge-num {
    background:rgba(56,189,248,0.12);
    border:1px solid rgba(56,189,248,0.2);
    color:#38bdf8; border-radius:20px;
    padding:2px 10px; font-size:11px; font-weight:700;
    margin-left:8px;
}

/* ════ ALERT ════ */
.alert-custom {
    padding:13px 18px; border-radius:12px;
    font-size:13px; font-weight:500; margin-bottom:20px;
    display:flex; align-items:center; gap:10px;
    animation:fadeIn 0.35s ease;
}
@keyframes fadeIn { from{opacity:0;transform:translateY(-8px);}to{opacity:1;transform:translateY(0);} }
.alert-success {
    background:rgba(34,197,94,0.1);
    border:1px solid rgba(34,197,94,0.25); color:#4ade80;
}
.alert-error {
    background:rgba(239,68,68,0.1);
    border:1px solid rgba(239,68,68,0.25); color:#f87171;
}

/* ════ EMPTY STATE ════ */
.empty-state {
    text-align:center; padding:48px 20px;
    color:#475569; font-size:14px;
}
.empty-state .icon { font-size:40px; margin-bottom:12px; }

/* ════ MODAL ════ */
.modal-content {
    background:rgba(5,12,30,0.98) !important;
    border:1px solid rgba(255,255,255,0.1) !important;
    border-radius:20px !important;
    backdrop-filter:blur(20px);
    color:white;
}
.modal-header {
    border-bottom:1px solid rgba(255,255,255,0.08) !important;
    padding:20px 24px;
}
.modal-title { font-weight:700 !important; font-size:16px; }
.modal-body  { padding:20px 24px; }
.modal-footer { border-top:1px solid rgba(255,255,255,0.08) !important; padding:16px 24px; }
.btn-close { filter:invert(1); opacity:0.5; }

/* ════ STATS ROW ════ */
.stats-row { display:flex; gap:16px; margin-bottom:24px; flex-wrap:wrap; }
.stat-card {
    background:rgba(56,189,248,0.07);
    border:1px solid rgba(56,189,248,0.15);
    border-radius:14px; padding:16px 22px;
    flex:1; min-width:160px;
}
.stat-card .stat-val { font-size:28px; font-weight:700; color:#38bdf8; }
.stat-card .stat-lbl { font-size:12px; color:#64748b; margin-top:2px; }

/* ════ ROUTE PREVIEW ════ */
.route-preview {
    background:rgba(56,189,248,0.06);
    border:1px solid rgba(56,189,248,0.15);
    border-radius:12px; padding:14px 16px;
    display:none;
}
.route-preview .rp-title { font-size:10px; color:#64748b; font-weight:600; text-transform:uppercase; letter-spacing:0.6px; margin-bottom:10px; }
.route-preview .rp-row { display:flex; align-items:center; gap:10px; }
.route-preview .rp-port { flex:1; }
.route-preview .rp-port .rp-name { font-weight:700; font-size:14px; color:white; }
.route-preview .rp-port .rp-loc  { font-size:11px; color:#38bdf8; margin-top:2px; }
.route-preview .rp-arrow { font-size:18px; color:#475569; flex-shrink:0; }

/* ════ RESPONSIVE ════ */
@media(max-width:768px){
    .main-wrap { margin-left:0; padding:16px; padding-top:72px; }
    .search-wrap input { width:100%; }
    .page-header { flex-direction:column; align-items:flex-start; }
    .form-row { flex-direction:column; }
}
</style>
</head>
<body class="has-mobile-nav">

<?php include('sidebar.php'); ?>

<div class="main-wrap">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div class="page-title">⚓ Kelola <span>Pelabuhan</span></div>
        <button class="btn-open-tambah" data-bs-toggle="modal" data-bs-target="#modalTambah">
            ＋ Tambah Pelabuhan
        </button>
    </div>

    <!-- ALERTS -->
    <?php if($msg): ?>
    <div class="alert-custom alert-success" id="alertMsg">
        <?= htmlspecialchars($msg) ?>
    </div>
    <?php endif; ?>

    <?php if($error): ?>
    <div class="alert-custom alert-error" id="alertErr">
        ⚠️ <?= $error ?>
    </div>
    <?php endif; ?>

    <!-- STATS -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-val"><?= $total ?></div>
            <div class="stat-lbl">⚓ Total Pelabuhan</div>
        </div>
        <div class="stat-card" style="background:rgba(139,92,246,0.07);border-color:rgba(139,92,246,0.15);">
            <div class="stat-val" style="color:#a78bfa;">
                <?php
                $r2 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM harga"));
                echo $r2['c'];
                ?>
            </div>
            <div class="stat-lbl">🗺️ Total Rute</div>
        </div>
    </div>

    <!-- TABEL DATA PELABUHAN -->
    <div class="gcard">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="gcard-title mb-0">
                📋 Daftar Pelabuhan
                <span class="badge-num" id="cntPelabuhan"><?= $total ?></span>
            </div>
            <div class="search-wrap">
                <span class="search-icon">🔍</span>
                <input type="text" id="searchInput"
                    placeholder="Cari pelabuhan..."
                    oninput="searchTable()">
            </div>
        </div>

        <div class="tbl-wrap">
        <table id="tblPelabuhan">
            <thead>
                <tr>
                    <th width="50">No</th>
                    <th>Nama Pelabuhan</th>
                    <th>Lokasi</th>
                    <th>Tujuan Pelabuhan</th>
                    <th>Lokasi Tujuan</th>
                    <th width="140">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if(empty($rows)): ?>
            <tr>
                <td colspan="6">
                    <div class="empty-state">
                        <div class="icon">⚓</div>
                        Belum ada data pelabuhan.<br>
                        <span style="font-size:12px;">Klik tombol <b>Tambah Pelabuhan</b> untuk mulai.</span>
                    </div>
                </td>
            </tr>
            <?php else: $no = 1; foreach($rows as $row): ?>

            <?php
            $pairName   = '—';
            $pairLokasi = '—';
            if(!empty($row['pair_id'])){
                foreach($rows as $p){
                    if($p['id'] == $row['pair_id']){
                        $pairName   = $p['nama_pelabuhan'];
                        $pairLokasi = $p['lokasi'];
                        break;
                    }
                }
            }
            ?>

            <tr>
                <td style="color:#475569;font-size:12px;"><?= $no++ ?></td>

                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span style="font-size:18px;">⚓</span>
                        <span style="font-weight:600;color:white;">
                            <?= htmlspecialchars($row['nama_pelabuhan']) ?>
                        </span>
                    </div>
                </td>

                <td>
                    <span class="badge-loc">
                        📍 <?= htmlspecialchars($row['lokasi']) ?>
                    </span>
                </td>

                <td>
                    <?php if($pairName !== '—'): ?>
                        <span style="color:#a78bfa;font-weight:600;">
                            ⚓ <?= htmlspecialchars($pairName) ?>
                        </span>
                    <?php else: ?>
                        <span style="color:#64748b;font-style:italic;">—</span>
                    <?php endif; ?>
                </td>

                <td>
                    <?php if($pairLokasi !== '—'): ?>
                        <span class="badge-loc">
                            📍 <?= htmlspecialchars($pairLokasi) ?>
                        </span>
                    <?php else: ?>
                        <span style="color:#64748b;font-style:italic;">—</span>
                    <?php endif; ?>
                </td>

                <td>
                    <div class="d-flex gap-2">
                        <button class="btn-edit"
                            onclick="openEdit(
                                <?= (int)$row['id'] ?>,
                                '<?= htmlspecialchars(addslashes($row['nama_pelabuhan'])) ?>',
                                '<?= htmlspecialchars(addslashes($row['lokasi'])) ?>',
                                '<?= htmlspecialchars(addslashes($pairName !== '—' ? $pairName : '')) ?>',
                                '<?= htmlspecialchars(addslashes($pairLokasi !== '—' ? $pairLokasi : '')) ?>'
                            )">
                            ✏️ Edit
                        </button>

                        <button class="btn-del"
                            onclick="openHapus(
                                <?= (int)$row['id'] ?>,
                                '<?= htmlspecialchars(addslashes($row['nama_pelabuhan'])) ?>'
                            )">
                            🗑️ Hapus
                        </button>
                    </div>
                </td>
            </tr>

            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>

</div><!-- end main-wrap -->


<!-- ════════════════════════════════════
     MODAL TAMBAH PELABUHAN
════════════════════════════════════ -->
<div class="modal fade" id="modalTambah" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">

    <div class="modal-header">
        <h5 class="modal-title">⚓ Tambah Pelabuhan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>

    <form method="POST" onsubmit="return validasiForm('tambah')">
    <div class="modal-body">
        <div class="row g-3">

            <!-- ── ASAL ── -->
            <div class="col-12">
                <div class="section-label">⚓ Pelabuhan Asal</div>
            </div>
            <div class="col-12">
                <label class="flabel">Nama Pelabuhan <span style="color:#f87171;">*</span></label>
                <input type="text" name="nama_pelabuhan" id="t_nama" class="finput"
                    placeholder="Contoh: Merak" required
                    oninput="updatePreview()">
            </div>
            <div class="col-12">
                <label class="flabel">Lokasi / Provinsi <span style="color:#f87171;">*</span></label>
                <input type="text" name="lokasi" id="t_lokasi" class="finput"
                    placeholder="Contoh: Banten" required
                    oninput="updatePreview()">
            </div>

            <!-- ── DIVIDER ── -->
            <div class="col-12"><hr class="modal-divider"></div>

            <!-- ── TUJUAN ── -->
            <div class="col-12">
                <div class="section-label">🎯 Pelabuhan Tujuan</div>
            </div>
            <div class="col-12">
                <label class="flabel">Nama Pelabuhan Tujuan <span style="color:#f87171;">*</span></label>
                <input type="text" name="t_pair_name" id="t_pair" class="finput"
                    placeholder="Contoh: Bakauheni" required autocomplete="off"
                    oninput="updatePreview()">
            </div>
            <div class="col-12">
                <label class="flabel">Lokasi / Provinsi Tujuan <span style="color:#f87171;">*</span></label>
                <input type="text" name="t_lokasi_tujuan" id="t_lokasi_tujuan" class="finput"
                    placeholder="Contoh: Lampung" required
                    oninput="updatePreview()">
            </div>

            <!-- ── ROUTE PREVIEW ── -->
            <div class="col-12">
                <div class="route-preview" id="routePreview">
                    <div class="rp-title">Preview Rute</div>
                    <div class="rp-row">
                        <div class="rp-port">
                            <div class="rp-name" id="rp_asal_nama">—</div>
                            <div class="rp-loc" id="rp_asal_lok">—</div>
                        </div>
                        <div class="rp-arrow">⇄</div>
                        <div class="rp-port" style="text-align:right;">
                            <div class="rp-name" id="rp_tujuan_nama">—</div>
                            <div class="rp-loc" id="rp_tujuan_lok">—</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <div class="modal-footer d-flex gap-2">
        <button type="button" class="btn-del flex-fill" data-bs-dismiss="modal">Batal</button>
        <button type="submit" name="tambah_pelabuhan" class="btn-add flex-fill">⚓ Tambah Pelabuhan</button>
    </div>
    </form>

</div>
</div>
</div>


<!-- ════════════════════════════════════
     MODAL EDIT PELABUHAN
════════════════════════════════════ -->
<div class="modal fade" id="modalEdit" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">

    <div class="modal-header">
        <h5 class="modal-title">✏️ Edit Pelabuhan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>

    <form method="POST" onsubmit="return validasiForm('edit')">
    <div class="modal-body">
        <input type="hidden" name="edit_id" id="edit_id">
        <div class="row g-3">

            <!-- ── ASAL ── -->
            <div class="col-12">
                <div class="section-label">⚓ Pelabuhan Asal</div>
            </div>
            <div class="col-12">
                <label class="flabel">Nama Pelabuhan <span style="color:#f87171;">*</span></label>
                <input type="text" name="edit_nama" id="edit_nama" class="finput"
                    placeholder="Contoh: Merak" required>
            </div>
            <div class="col-12">
                <label class="flabel">Lokasi / Provinsi <span style="color:#f87171;">*</span></label>
                <input type="text" name="edit_lokasi" id="edit_lokasi" class="finput"
                    placeholder="Contoh: Banten" required>
            </div>

            <!-- ── DIVIDER ── -->
            <div class="col-12"><hr class="modal-divider"></div>

            <!-- ── TUJUAN ── -->
            <div class="col-12">
                <div class="section-label">🎯 Pelabuhan Tujuan</div>
            </div>
            <div class="col-12">
                <label class="flabel">Nama Pelabuhan Tujuan <span style="color:#f87171;">*</span></label>
                <input type="text" name="edit_pair_name" id="edit_pair" class="finput"
                    placeholder="Contoh: Bakauheni" required autocomplete="off">
            </div>
            <div class="col-12">
                <label class="flabel">Lokasi / Provinsi Tujuan <span style="color:#f87171;">*</span></label>
                <input type="text" name="edit_lokasi_tujuan" id="edit_lokasi_tujuan" class="finput"
                    placeholder="Contoh: Lampung" required>
            </div>

        </div>
    </div>
    <div class="modal-footer d-flex gap-2">
        <button type="button" class="btn-del flex-fill" data-bs-dismiss="modal">Batal</button>
        <button type="submit" name="edit_pelabuhan" class="btn-add flex-fill">💾 Simpan Perubahan</button>
    </div>
    </form>

</div>
</div>
</div>


<!-- ════════════════════════════════════
     MODAL KONFIRMASI HAPUS
════════════════════════════════════ -->
<div class="modal fade" id="modalHapus" tabindex="-1" data-bs-backdrop="static">
<div class="modal-dialog modal-dialog-centered modal-sm">
<div class="modal-content">
    <div class="modal-body text-center" style="padding:30px 24px;">
        <div style="font-size:48px;margin-bottom:14px;">🗑️</div>
        <h5 style="font-weight:700;margin-bottom:8px;">Hapus Pelabuhan?</h5>
        <p style="font-size:13px;color:#64748b;margin-bottom:20px;">
            Pelabuhan berikut akan dihapus permanen:<br>
            <strong id="hapusLabel" style="color:white;font-size:15px;"></strong>
        </p>
        <div style="font-size:12px;color:#f87171;margin-bottom:20px;
            background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);
            border-radius:10px;padding:10px;">
            ⚠️ Harga rute ikut terhapus otomatis. Yang menghalangi hanya jika masih ada <b>tiket</b> dengan pelabuhan ini.
        </div>
        <form method="POST">
            <input type="hidden" name="hapus_id" id="hapusId">
            <div class="d-flex gap-2 justify-content-center">
                <button type="button" class="btn-edit" data-bs-dismiss="modal"
                    style="padding:9px 20px;">Batal</button>
                <button type="submit" name="hapus_pelabuhan" class="btn-del"
                    style="padding:9px 20px;font-size:13px;">Ya, Hapus</button>
            </div>
        </form>
    </div>
</div>
</div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>

// ════ SEARCH TABLE REALTIME ════
function searchTable(){
    const keyword = document.getElementById('searchInput').value.toLowerCase();
    const rows    = document.querySelectorAll('#tblPelabuhan tbody tr');
    let visible   = 0;

    rows.forEach(row => {
        const firstCell = row.querySelector('td:first-child');
        const hasData   = firstCell && firstCell.textContent.trim() !== '';
        const show      = row.textContent.toLowerCase().includes(keyword);
        row.style.display = show ? '' : 'none';
        if(show && hasData) visible++;
    });

    document.getElementById('cntPelabuhan').textContent = visible;
}

// ════ VALIDASI FORM ════
function validasiForm(tipe){
    if(tipe === 'tambah'){
        const nama       = document.getElementById('t_nama').value.trim();
        const lokasi     = document.getElementById('t_lokasi').value.trim();
        const pairNama   = document.getElementById('t_pair').value.trim();
        const pairLokasi = document.getElementById('t_lokasi_tujuan').value.trim();

        if(!nama || !lokasi || !pairNama || !pairLokasi){
            showToast('⚠️ Semua field wajib diisi termasuk pelabuhan tujuan!', 'error');
            return false;
        }
        if(nama.toLowerCase() === pairNama.toLowerCase()){
            showToast('⚠️ Nama pelabuhan asal dan tujuan tidak boleh sama!', 'error');
            return false;
        }
    } else {
        const nama       = document.getElementById('edit_nama').value.trim();
        const lokasi     = document.getElementById('edit_lokasi').value.trim();
        const pairNama   = document.getElementById('edit_pair').value.trim();
        const pairLokasi = document.getElementById('edit_lokasi_tujuan').value.trim();

        if(!nama || !lokasi || !pairNama || !pairLokasi){
            showToast('⚠️ Semua field wajib diisi termasuk pelabuhan tujuan!', 'error');
            return false;
        }
        if(nama.toLowerCase() === pairNama.toLowerCase()){
            showToast('⚠️ Nama pelabuhan asal dan tujuan tidak boleh sama!', 'error');
            return false;
        }
    }
    return true;
}

// ════ ROUTE PREVIEW REALTIME (Modal Tambah) ════
function updatePreview(){
    const nama       = document.getElementById('t_nama').value.trim();
    const lokasi     = document.getElementById('t_lokasi').value.trim();
    const pairNama   = document.getElementById('t_pair').value.trim();
    const pairLokasi = document.getElementById('t_lokasi_tujuan').value.trim();
    const box        = document.getElementById('routePreview');

    if(nama || lokasi || pairNama || pairLokasi){
        box.style.display = 'block';
        document.getElementById('rp_asal_nama').textContent   = nama   || '—';
        document.getElementById('rp_asal_lok').textContent    = lokasi || '—';
        document.getElementById('rp_tujuan_nama').textContent = pairNama   || '—';
        document.getElementById('rp_tujuan_lok').textContent  = pairLokasi || '—';
    } else {
        box.style.display = 'none';
    }
}

// ════ OPEN MODAL EDIT ════
function openEdit(id, nama, lokasi, pairName, pairLokasi){
    document.getElementById('edit_id').value             = id;
    document.getElementById('edit_nama').value           = nama;
    document.getElementById('edit_lokasi').value         = lokasi;
    document.getElementById('edit_pair').value           = pairName   || '';
    document.getElementById('edit_lokasi_tujuan').value  = pairLokasi || '';
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}

// ════ OPEN MODAL HAPUS ════
function openHapus(id, nama){
    document.getElementById('hapusId').value          = id;
    document.getElementById('hapusLabel').textContent = '⚓ ' + nama;
    new bootstrap.Modal(document.getElementById('modalHapus')).show();
}

// ════ TOAST NOTIFICATION ════
function showToast(pesan, tipe){
    const existing = document.getElementById('toastMsg');
    if(existing) existing.remove();

    const div = document.createElement('div');
    div.id = 'toastMsg';
    div.style.cssText = `
        position:fixed; top:20px; right:20px; z-index:9999;
        padding:13px 20px; border-radius:12px; font-size:13px;
        font-family:'Poppins',sans-serif; font-weight:500;
        max-width:340px; animation:fadeIn 0.3s ease;
        display:flex; align-items:center; gap:10px;
        box-shadow:0 8px 32px rgba(0,0,0,0.4);
    `;

    if(tipe === 'error'){
        div.style.background = 'rgba(239,68,68,0.15)';
        div.style.border     = '1px solid rgba(239,68,68,0.3)';
        div.style.color      = '#f87171';
    } else {
        div.style.background = 'rgba(34,197,94,0.12)';
        div.style.border     = '1px solid rgba(34,197,94,0.25)';
        div.style.color      = '#4ade80';
    }

    div.innerHTML = pesan;
    document.body.appendChild(div);
    setTimeout(() => {
        div.style.transition = 'opacity 0.4s';
        div.style.opacity    = '0';
        setTimeout(() => div.remove(), 400);
    }, 3500);
}

// ════ INIT ════
document.addEventListener('DOMContentLoaded', () => {

    // Reset form & preview saat modal tambah ditutup
    document.getElementById('modalTambah').addEventListener('hidden.bs.modal', () => {
        document.getElementById('t_nama').value          = '';
        document.getElementById('t_lokasi').value        = '';
        document.getElementById('t_pair').value          = '';
        document.getElementById('t_lokasi_tujuan').value = '';
        document.getElementById('routePreview').style.display = 'none';
    });

    // Reset form saat modal edit ditutup
    document.getElementById('modalEdit').addEventListener('hidden.bs.modal', () => {
        document.getElementById('edit_id').value             = '';
        document.getElementById('edit_nama').value           = '';
        document.getElementById('edit_lokasi').value         = '';
        document.getElementById('edit_pair').value           = '';
        document.getElementById('edit_lokasi_tujuan').value  = '';
    });

    // Auto dismiss server alert setelah 4 detik
    ['alertMsg','alertErr'].forEach(id => {
        const el = document.getElementById(id);
        if(el){
            setTimeout(() => {
                el.style.transition = 'opacity 0.4s';
                el.style.opacity    = '0';
                setTimeout(() => el.remove(), 400);
            }, 4000);
        }
    });
});

</script>
<script src="../assets/js/mobile-nav.js"></script>
</body>
</html>