<?php
// ════════════════════════════════════════════════════════
// SUPERADMIN — KELOLA HARGA TIKET & KENDARAAN
// ════════════════════════════════════════════════════════
include('auth.php');
include('../config/koneksi.php');

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'super_admin'){
    die("Akses ditolak!");
}

$msg   = '';
$error = '';

// ═══════════════════════════════════════════
// CRUD — HARGA TIKET PENUMPANG
// ═══════════════════════════════════════════

// TAMBAH HARGA TIKET
if(isset($_POST['tambah_tiket'])){
    $asal    = (int)$_POST['t_asal'];
    $tujuan  = (int)$_POST['t_tujuan'];
    $layanan = trim($_POST['t_layanan']);
    $harga   = (int)str_replace('.', '', $_POST['t_harga']);

    if($asal === $tujuan){
        $error = 'Pelabuhan asal dan tujuan tidak boleh sama!';
    } elseif($harga <= 0){
        $error = 'Harga harus lebih dari 0!';
    } else {
        $cek = $conn->prepare("SELECT id FROM harga WHERE asal_id=? AND tujuan_id=? AND LOWER(layanan)=LOWER(?)");
        $cek->bind_param('iis', $asal, $tujuan, $layanan);
        $cek->execute();
        $cek->store_result();
        if($cek->num_rows > 0){
            $error = "Harga rute ini untuk layanan <b>$layanan</b> sudah ada!";
        } else {
            $cek->close();
            $stmt = $conn->prepare("INSERT INTO harga(asal_id,tujuan_id,layanan,harga) VALUES(?,?,?,?)");
            $stmt->bind_param('iisi', $asal, $tujuan, $layanan, $harga);
            $stmt->execute();
            header("Location: kelola_harga.php?tab=tiket&status=added"); exit;
        }
        $cek->close();
    }
}

// EDIT HARGA TIKET
if(isset($_POST['edit_tiket'])){
    $id      = (int)$_POST['et_id'];
    $asal    = (int)$_POST['et_asal'];
    $tujuan  = (int)$_POST['et_tujuan'];
    $layanan = trim($_POST['et_layanan']);
    $harga   = (int)str_replace('.', '', $_POST['et_harga']);

    if($asal === $tujuan){
        $error = 'Pelabuhan asal dan tujuan tidak boleh sama!';
    } else {
        $cek = $conn->prepare("SELECT id FROM harga WHERE asal_id=? AND tujuan_id=? AND LOWER(layanan)=LOWER(?) AND id!=?");
        $cek->bind_param('iisi', $asal, $tujuan, $layanan, $id);
        $cek->execute();
        $cek->store_result();
        if($cek->num_rows > 0){
            $error = "Kombinasi rute + layanan ini sudah ada!";
        } else {
            $cek->close();
            $stmt = $conn->prepare("UPDATE harga SET asal_id=?,tujuan_id=?,layanan=?,harga=? WHERE id=?");
            $stmt->bind_param('iisii', $asal, $tujuan, $layanan, $harga, $id);
            $stmt->execute();
            header("Location: kelola_harga.php?tab=tiket&status=updated"); exit;
        }
        $cek->close();
    }
}

// HAPUS HARGA TIKET
if(isset($_POST['hapus_tiket'])){
    $id = (int)$_POST['hapus_id'];
    $stmt = $conn->prepare("DELETE FROM harga WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header("Location: kelola_harga.php?tab=tiket&status=deleted"); exit;
}

// ═══════════════════════════════════════════
// CRUD — HARGA KENDARAAN
// Struktur tabel harga_kendaraan:
//   id, asal_id, tujuan_id, golongan, harga_reguler, harga_express
//
// Jika tabel masih pakai kolom lama (layanan + harga), jalankan migrasi ini:
//   ALTER TABLE harga_kendaraan ADD COLUMN harga_reguler INT NOT NULL DEFAULT 0;
//   ALTER TABLE harga_kendaraan ADD COLUMN harga_express INT NOT NULL DEFAULT 0;
//   UPDATE harga_kendaraan SET harga_reguler = harga WHERE LOWER(layanan) = 'reguler';
//   UPDATE harga_kendaraan SET harga_express = harga WHERE LOWER(layanan) = 'express';
//   -- Setelah data aman, hapus kolom lama:
//   ALTER TABLE harga_kendaraan DROP COLUMN layanan;
//   ALTER TABLE harga_kendaraan DROP COLUMN harga;
//   ALTER TABLE harga_kendaraan ADD UNIQUE KEY uq_rute_gol (asal_id, tujuan_id, golongan);
// ═══════════════════════════════════════════

// DAFTAR GOLONGAN
$golonganList = [
    'gol_1'  => 'Gol I — Sepeda',
    'gol_2'  => 'Gol II — Motor <500cc',
    'gol_3'  => 'Gol III — Motor >500cc',
    'gol_4a' => 'Gol IVA — Mobil Penumpang',
    'gol_4b' => 'Gol IVB — Mobil Barang ≤5m',
    'gol_5a' => 'Gol VA — Bus Sedang',
    'gol_5b' => 'Gol VB — Truk Sedang',
    'gol_6a' => 'Gol VIA — Bus Besar',
    'gol_6b' => 'Gol VIB — Truk Besar',
    'gol_7'  => 'Gol VII — Tronton 10–12m',
    'gol_8'  => 'Gol VIII — Tronton 12–16m',
    'gol_9'  => 'Gol IX — Tronton >16m',
];

// TAMBAH BATCH HARGA KENDARAAN (semua golongan sekaligus, 2 kolom harga per baris)
// Otomatis menyimpan rute balik (tujuan→asal) dengan harga yang sama
if(isset($_POST['tambah_kendaraan_batch'])){
    $asal   = (int)$_POST['k_asal'];
    $tujuan = (int)$_POST['k_tujuan'];

    if($asal === $tujuan){
        $error = 'Pelabuhan asal dan tujuan tidak boleh sama!';
    } elseif(!$asal || !$tujuan){
        $error = 'Pilih pelabuhan asal terlebih dahulu!';
    } else {
        $inserted = 0;
        $updated  = 0;

        // Helper: upsert satu baris harga kendaraan
        $upsertHarga = function($asalId, $tujuanId, $gol, $hargaReg, $hargaExp) use ($conn, &$inserted, &$updated) {
            $cek = $conn->prepare("SELECT id, harga_reguler, harga_express FROM harga_kendaraan WHERE asal_id=? AND tujuan_id=? AND golongan=?");
            $cek->bind_param('iis', $asalId, $tujuanId, $gol);
            $cek->execute();
            $res      = $cek->get_result();
            $existing = $res->fetch_assoc();
            $cek->close();

            if($existing){
                $newReg = ($hargaReg !== null) ? $hargaReg : $existing['harga_reguler'];
                $newExp = ($hargaExp !== null) ? $hargaExp : $existing['harga_express'];
                $stmt = $conn->prepare("UPDATE harga_kendaraan SET harga_reguler=?, harga_express=? WHERE id=?");
                $stmt->bind_param('iii', $newReg, $newExp, $existing['id']);
                $stmt->execute();
                $stmt->close();
                $updated++;
            } else {
                $newReg = $hargaReg ?? 0;
                $newExp = $hargaExp ?? 0;
                $stmt = $conn->prepare("INSERT INTO harga_kendaraan(asal_id, tujuan_id, golongan, harga_reguler, harga_express) VALUES(?,?,?,?,?)");
                $stmt->bind_param('iisii', $asalId, $tujuanId, $gol, $newReg, $newExp);
                $stmt->execute();
                $stmt->close();
                $inserted++;
            }
        };

        foreach($golonganList as $gol => $label){
            $rawReg = $_POST['harga_'.$gol.'_reguler'] ?? '';
            $rawExp = $_POST['harga_'.$gol.'_express'] ?? '';

            // Skip jika dua-duanya kosong
            if($rawReg === '' && $rawExp === '') continue;

            $hargaReg = ($rawReg !== '') ? (int)str_replace(['.', ','], '', $rawReg) : null;
            $hargaExp = ($rawExp !== '') ? (int)str_replace(['.', ','], '', $rawExp) : null;

            // Simpan rute A → B
            $upsertHarga($asal, $tujuan, $gol, $hargaReg, $hargaExp);

            // Simpan rute B → A (rute balik, harga sama)
            $upsertHarga($tujuan, $asal, $gol, $hargaReg, $hargaExp);
        }

        $qs = "tab=kendaraan&status=added&inserted=$inserted";
        if($updated > 0) $qs .= "&updated=$updated";
        header("Location: kelola_harga.php?$qs"); exit;
    }
}

// EDIT HARGA KENDARAAN (single row)
if(isset($_POST['edit_kendaraan'])){
    $id       = (int)$_POST['ek_id'];
    $asal     = (int)$_POST['ek_asal'];
    $tujuan   = (int)$_POST['ek_tujuan'];
    $golongan = trim($_POST['ek_golongan']);
    $hargaReg = (int)str_replace(['.', ','], '', $_POST['ek_harga_reguler']);
    $hargaExp = (int)str_replace(['.', ','], '', $_POST['ek_harga_express']);

    if($asal === $tujuan){
        $error = 'Pelabuhan asal dan tujuan tidak boleh sama!';
    } else {
        $cek = $conn->prepare("SELECT id FROM harga_kendaraan WHERE asal_id=? AND tujuan_id=? AND golongan=? AND id!=?");
        $cek->bind_param('iisi', $asal, $tujuan, $golongan, $id);
        $cek->execute();
        $cek->store_result();
        if($cek->num_rows > 0){
            $error = "Kombinasi rute + golongan ini sudah ada!";
        } else {
            $cek->close();
            // Update rute A → B
            $stmt = $conn->prepare("UPDATE harga_kendaraan SET asal_id=?, tujuan_id=?, golongan=?, harga_reguler=?, harga_express=? WHERE id=?");
            $stmt->bind_param('iisiii', $asal, $tujuan, $golongan, $hargaReg, $hargaExp, $id);
            $stmt->execute();
            $stmt->close();

            // Sync rute balik B → A (upsert)
            $cekBalik = $conn->prepare("SELECT id FROM harga_kendaraan WHERE asal_id=? AND tujuan_id=? AND golongan=?");
            $cekBalik->bind_param('iis', $tujuan, $asal, $golongan);
            $cekBalik->execute();
            $resBalik = $cekBalik->get_result();
            $rowBalik = $resBalik->fetch_assoc();
            $cekBalik->close();

            if($rowBalik){
                $stmtB = $conn->prepare("UPDATE harga_kendaraan SET harga_reguler=?, harga_express=? WHERE id=?");
                $stmtB->bind_param('iii', $hargaReg, $hargaExp, $rowBalik['id']);
                $stmtB->execute();
                $stmtB->close();
            } else {
                $stmtB = $conn->prepare("INSERT INTO harga_kendaraan(asal_id, tujuan_id, golongan, harga_reguler, harga_express) VALUES(?,?,?,?,?)");
                $stmtB->bind_param('iisii', $tujuan, $asal, $golongan, $hargaReg, $hargaExp);
                $stmtB->execute();
                $stmtB->close();
            }

            header("Location: kelola_harga.php?tab=kendaraan&status=updated"); exit;
        }
        $cek->close();
    }
}

// HAPUS HARGA KENDARAAN (single)
if(isset($_POST['hapus_kendaraan'])){
    $id = (int)$_POST['hapus_id'];
    $stmt = $conn->prepare("DELETE FROM harga_kendaraan WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header("Location: kelola_harga.php?tab=kendaraan&status=deleted"); exit;
}

// HAPUS BANYAK HARGA KENDARAAN
if(isset($_POST['hapus_kendaraan_multi'])){
    $ids = array_filter(array_map('intval', explode(',', $_POST['hapus_id'] ?? '')));
    if(!empty($ids)){
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types        = str_repeat('i', count($ids));
        $stmt         = $conn->prepare("DELETE FROM harga_kendaraan WHERE id IN ($placeholders)");
        $params       = array_merge([$types], $ids);
        $bindNames    = [];
        foreach($params as $key => $value) $bindNames[$key] = &$params[$key];
        call_user_func_array([$stmt, 'bind_param'], $bindNames);
        $stmt->execute();
    }
    header("Location: kelola_harga.php?tab=kendaraan&status=deleted"); exit;
}

// ═══════════════════════════════════════════
// NOTIFIKASI
// ═══════════════════════════════════════════
if(isset($_GET['status'])){
    $ins = (int)($_GET['inserted'] ?? 0);
    $upd = (int)($_GET['updated']  ?? 0);
    $notif = [
        'added'   => ($ins > 0 || $upd > 0)
            ? "✅ $ins data ditambahkan" . ($upd > 0 ? ", $upd data diperbarui!" : '!')
            : '✅ Data berhasil disimpan!',
        'updated' => '✅ Data berhasil diperbarui!',
        'deleted' => '🗑️ Data berhasil dihapus!',
    ];
    $msg = $notif[$_GET['status']] ?? '';
}

$activeTab = $_GET['tab'] ?? 'tiket';

// ═══════════════════════════════════════════
// AMBIL DATA
// ═══════════════════════════════════════════
$pelabuhan = $conn->query("SELECT * FROM pelabuhan ORDER BY nama_pelabuhan ASC");
$pel_arr   = [];
while($p = $pelabuhan->fetch_assoc()) $pel_arr[] = $p;

$pairMap = [];
$namaMap = [];
foreach($pel_arr as $p){
    $pairMap[(int)$p['id']] = $p['pair_id'] ? (int)$p['pair_id'] : null;
    $namaMap[(int)$p['id']] = $p['nama_pelabuhan'];
}

$data_tiket = $conn->query("
    SELECT h.*, p1.nama_pelabuhan AS nm_asal, p2.nama_pelabuhan AS nm_tujuan
    FROM harga h
    JOIN pelabuhan p1 ON h.asal_id = p1.id
    JOIN pelabuhan p2 ON h.tujuan_id = p2.id
    ORDER BY p1.nama_pelabuhan, p2.nama_pelabuhan, h.layanan
");

$data_kend = $conn->query("
    SELECT k.*, p1.nama_pelabuhan AS nm_asal, p2.nama_pelabuhan AS nm_tujuan
    FROM harga_kendaraan k
    JOIN pelabuhan p1 ON k.asal_id = p1.id
    JOIN pelabuhan p2 ON k.tujuan_id = p2.id
    ORDER BY p1.nama_pelabuhan, p2.nama_pelabuhan, k.golongan
");

function golonganLabel($gol){
    $map = [
        'gol_1'  => 'Gol I — Sepeda',
        'gol_2'  => 'Gol II — Motor <500cc',
        'gol_3'  => 'Gol III — Motor >500cc',
        'gol_4a' => 'Gol IVA — Mobil Penumpang',
        'gol_4b' => 'Gol IVB — Mobil Barang ≤5m',
        'gol_5a' => 'Gol VA — Bus Sedang',
        'gol_5b' => 'Gol VB — Truk Sedang',
        'gol_6a' => 'Gol VIA — Bus Besar',
        'gol_6b' => 'Gol VIB — Truk Besar',
        'gol_7'  => 'Gol VII — Tronton 10–12m',
        'gol_8'  => 'Gol VIII — Tronton 12–16m',
        'gol_9'  => 'Gol IX — Tronton >16m',
    ];
    return $map[$gol] ?? $gol;
}

function fmtHarga($val){
    if($val == 0) return '<span style="color:#4ade80;font-weight:700;">Gratis</span>';
    return 'Rp '.number_format($val, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Harga — Super Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="../assets/css/sidebar.css" rel="stylesheet">
<link href="../assets/css/responsive.css" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Poppins', 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #020617, #0f172a, #1e3a8a);
    min-height: 100vh;
    color: white;
}

.main-wrap { margin-left: 240px; padding: 30px; min-height: 100vh; }

.page-header {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 24px; flex-wrap: wrap; gap: 12px;
}
.page-title { font-size: 22px; font-weight: 700; }
.page-title span { color: #38bdf8; }

.gcard {
    background: rgba(255,255,255,0.04);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 18px; padding: 24px; margin-bottom: 24px;
    box-shadow: 0 4px 30px rgba(0,0,0,0.3);
}
.gcard-title {
    font-size: 14px; font-weight: 700; color: #38bdf8;
    text-transform: uppercase; letter-spacing: 1px;
    margin-bottom: 18px; display: flex; align-items: center; gap: 8px;
}

.tab-bar {
    display: flex; gap: 8px; margin-bottom: 24px;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 14px; padding: 6px; width: fit-content;
}
.tab-item {
    padding: 10px 22px; border-radius: 10px;
    font-size: 14px; font-weight: 600; cursor: pointer;
    text-decoration: none; color: #64748b; transition: all 0.25s;
}
.tab-item:hover { color: white; background: rgba(255,255,255,0.06); }
.tab-item.active {
    background: linear-gradient(135deg, #2563eb, #38bdf8);
    color: white; box-shadow: 0 0 20px rgba(56,189,248,0.3);
}

.flabel {
    font-size: 11px; color: #64748b; font-weight: 600;
    text-transform: uppercase; letter-spacing: 0.6px;
    margin-bottom: 6px; display: block;
}
.finput, .fselect {
    width: 100%;
    background: rgba(255,255,255,0.06);
    border: 1.5px solid rgba(255,255,255,0.1);
    border-radius: 10px; color: white;
    padding: 10px 13px; font-family: 'Poppins', sans-serif;
    font-size: 13px; transition: 0.25s; outline: none;
}
.finput:focus, .fselect:focus {
    border-color: #38bdf8;
    box-shadow: 0 0 0 3px rgba(56,189,248,0.12);
}
.finput::placeholder { color: rgba(255,255,255,0.25); }
.fselect option { background: #0f172a; color: white; }

.btn-add {
    background: linear-gradient(135deg, #2563eb, #38bdf8);
    border: none; border-radius: 10px; color: white;
    padding: 10px 22px; font-family: 'Poppins', sans-serif;
    font-weight: 600; font-size: 13px; cursor: pointer;
    transition: 0.25s; white-space: nowrap;
}
.btn-add:hover { opacity: 0.88; transform: translateY(-1px); box-shadow: 0 0 18px rgba(56,189,248,0.35); }

.btn-edit {
    background: rgba(251,191,36,0.15);
    border: 1px solid rgba(251,191,36,0.3);
    color: #fbbf24; border-radius: 8px;
    padding: 5px 12px; font-size: 12px; font-weight: 600;
    cursor: pointer; font-family: 'Poppins', sans-serif; transition: 0.2s;
}
.btn-edit:hover { background: rgba(251,191,36,0.25); }

.btn-del {
    background: rgba(239,68,68,0.12);
    border: 1px solid rgba(239,68,68,0.25);
    color: #f87171; border-radius: 8px;
    padding: 5px 12px; font-size: 12px; font-weight: 600;
    cursor: pointer; font-family: 'Poppins', sans-serif; transition: 0.2s;
}
.btn-del:hover { background: rgba(239,68,68,0.22); }

.search-wrap { position: relative; }
.search-wrap input {
    background: rgba(255,255,255,0.05);
    border: 1.5px solid rgba(255,255,255,0.09);
    border-radius: 10px; color: white;
    padding: 9px 14px 9px 36px;
    font-family: 'Poppins', sans-serif; font-size: 13px;
    outline: none; transition: 0.25s; width: 220px;
}
.search-wrap input:focus { border-color: #38bdf8; box-shadow: 0 0 0 3px rgba(56,189,248,0.1); }
.search-wrap input::placeholder { color: rgba(255,255,255,0.3); }
.search-icon {
    position: absolute; left: 11px; top: 50%;
    transform: translateY(-50%); color: #64748b; font-size: 14px;
}

.tbl-wrap {
    overflow-x: auto; border-radius: 14px;
    border: 1px solid rgba(255,255,255,0.07);
}
.tbl-wrap table { width: 100%; border-collapse: collapse; min-width: 580px; }
.tbl-wrap thead th {
    background: rgba(255,255,255,0.05); color: #64748b;
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.8px; padding: 13px 16px; text-align: left;
    border-bottom: 1px solid rgba(255,255,255,0.06); white-space: nowrap;
}
.tbl-wrap thead th.th-reg { color: #38bdf8; }
.tbl-wrap thead th.th-exp { color: #fbbf24; }
.tbl-wrap tbody td {
    padding: 12px 16px; font-size: 13px; color: #e2e8f0;
    border-bottom: 1px solid rgba(255,255,255,0.04); vertical-align: middle;
}
.tbl-wrap tbody tr:hover { background: rgba(255,255,255,0.02); }
.tbl-wrap tbody tr:last-child td { border-bottom: none; }

.badge-reg {
    background: rgba(56,189,248,0.15); color: #38bdf8;
    border: 1px solid rgba(56,189,248,0.2);
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;
}
.badge-exp {
    background: rgba(251,191,36,0.12); color: #fbbf24;
    border: 1px solid rgba(251,191,36,0.2);
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;
}
.badge-gol {
    background: rgba(139,92,246,0.12); color: #a78bfa;
    border: 1px solid rgba(139,92,246,0.2);
    padding: 3px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 600; white-space: nowrap;
}

.rute-cell { display: flex; align-items: center; gap: 8px; }
.rute-cell .port { font-weight: 600; color: white; }
.rute-cell .arrow { color: #38bdf8; font-size: 16px; }

.alert-custom {
    padding: 13px 18px; border-radius: 12px;
    font-size: 13px; font-weight: 500; margin-bottom: 20px;
    display: flex; align-items: center; gap: 10px;
    animation: fadeIn 0.35s ease;
}
@keyframes fadeIn { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }
.alert-success { background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.25); color: #4ade80; }
.alert-error   { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.25); color: #f87171; }

.empty-state { text-align: center; padding: 40px 20px; color: #475569; font-size: 14px; }
.empty-state .icon { font-size: 36px; margin-bottom: 10px; }

.modal-content {
    background: rgba(5,12,30,0.97) !important;
    border: 1px solid rgba(255,255,255,0.1) !important;
    border-radius: 20px !important;
    backdrop-filter: blur(20px); color: white;
}
.modal-header { border-bottom: 1px solid rgba(255,255,255,0.08) !important; padding: 20px 24px; }
.modal-title { font-weight: 700 !important; font-size: 16px; }
.modal-footer { border-top: 1px solid rgba(255,255,255,0.08) !important; }
.btn-close { filter: invert(1); opacity: 0.5; }
.modal-body { padding: 20px 24px; }

.count-badge {
    background: rgba(56,189,248,0.12);
    border: 1px solid rgba(56,189,248,0.2);
    color: #38bdf8; border-radius: 20px;
    padding: 2px 10px; font-size: 11px; font-weight: 700; margin-left: 8px;
}

.tujuan-display {
    width: 100%;
    background: rgba(56,189,248,0.06);
    border: 1.5px solid rgba(56,189,248,0.35);
    border-radius: 10px; color: #7dd3fc;
    padding: 10px 13px; font-family: 'Poppins', sans-serif;
    font-size: 13px; min-height: 42px;
    display: flex; align-items: center; gap: 6px;
}
.tujuan-display.empty { color: #475569; font-style: italic; }

/* ══════════════════════════════════════
   BATCH FORM — GRID HARGA KENDARAAN
══════════════════════════════════════ */
.batch-rute-row {
    display: flex; gap: 16px; align-items: flex-end;
    flex-wrap: wrap; margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid rgba(255,255,255,0.07);
}
.batch-rute-row .col-rute { flex: 1; min-width: 180px; }

.batch-grid-wrap { overflow-x: auto; }
.batch-grid {
    width: 100%; border-collapse: separate; border-spacing: 0;
    min-width: 520px;
}
.batch-grid thead th {
    padding: 10px 14px; font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.8px; color: #64748b;
    background: rgba(255,255,255,0.04);
    border-bottom: 1px solid rgba(255,255,255,0.07);
    white-space: nowrap;
}
.batch-grid thead th:first-child { border-radius: 10px 0 0 0; }
.batch-grid thead th:last-child  { border-radius: 0 10px 0 0; }
.batch-grid thead th.th-reg {
    color: #38bdf8; background: rgba(56,189,248,0.07);
    border-left: 1px solid rgba(56,189,248,0.15);
}
.batch-grid thead th.th-exp {
    color: #fbbf24; background: rgba(251,191,36,0.07);
    border-left: 1px solid rgba(251,191,36,0.15);
}
.batch-grid tbody td {
    padding: 8px 12px;
    border-bottom: 1px solid rgba(255,255,255,0.04);
    vertical-align: middle; font-size: 13px;
}
.batch-grid tbody tr:last-child td { border-bottom: none; }
.batch-grid tbody tr:hover { background: rgba(255,255,255,0.02); }
.batch-grid .td-label { font-size: 12px; font-weight: 600; color: #e2e8f0; white-space: nowrap; padding-right: 16px; }
.batch-grid .badge-gol-sm {
    display: inline-block;
    background: rgba(139,92,246,0.12); color: #a78bfa;
    border: 1px solid rgba(139,92,246,0.2);
    padding: 2px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 600; white-space: nowrap;
}

.batch-input-reg {
    width: 100%; min-width: 130px;
    background: rgba(56,189,248,0.05);
    border: 1.5px solid rgba(56,189,248,0.18);
    border-radius: 8px; color: white;
    padding: 7px 10px; font-family: 'Poppins', sans-serif;
    font-size: 12px; transition: 0.2s; outline: none;
}
.batch-input-reg:focus {
    border-color: #38bdf8;
    box-shadow: 0 0 0 2px rgba(56,189,248,0.15);
    background: rgba(56,189,248,0.09);
}
.batch-input-reg::placeholder { color: rgba(255,255,255,0.2); }

.batch-input-exp {
    width: 100%; min-width: 130px;
    background: rgba(251,191,36,0.05);
    border: 1.5px solid rgba(251,191,36,0.18);
    border-radius: 8px; color: white;
    padding: 7px 10px; font-family: 'Poppins', sans-serif;
    font-size: 12px; transition: 0.2s; outline: none;
}
.batch-input-exp:focus {
    border-color: #fbbf24;
    box-shadow: 0 0 0 2px rgba(251,191,36,0.15);
    background: rgba(251,191,36,0.09);
}
.batch-input-exp::placeholder { color: rgba(255,255,255,0.2); }

.batch-info {
    font-size: 11px; color: #475569; margin-top: 12px;
    display: flex; align-items: center; gap: 6px;
}
.batch-submit-row {
    margin-top: 18px; display: flex;
    justify-content: flex-end; gap: 12px; align-items: center;
    flex-wrap: wrap;
}
.btn-fill-zero {
    background: rgba(99,102,241,0.12);
    border: 1px solid rgba(99,102,241,0.25);
    color: #818cf8; border-radius: 8px;
    padding: 8px 16px; font-family: 'Poppins', sans-serif;
    font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.2s;
}
.btn-fill-zero:hover { background: rgba(99,102,241,0.22); }
.btn-clear-batch {
    background: rgba(239,68,68,0.08);
    border: 1px solid rgba(239,68,68,0.2);
    color: #f87171; border-radius: 8px;
    padding: 8px 16px; font-family: 'Poppins', sans-serif;
    font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.2s;
}
.btn-clear-batch:hover { background: rgba(239,68,68,0.16); }

@media (max-width: 768px) {
    .main-wrap { margin-left: 0; padding: 16px; padding-top: 72px; }
    .tab-bar { width: 100%; }
    .tab-item { flex: 1; text-align: center; padding: 9px 10px; }
    .page-header { flex-direction: column; align-items: flex-start; }
    .batch-rute-row { flex-direction: column; }
}
</style>
</head>
<body class="has-mobile-nav">

<?php include('sidebar.php'); ?>

<div class="main-wrap">

    <div class="page-header">
        <div class="page-title">💰 Kelola <span>Harga</span></div>
        <div style="font-size:13px;color:#475569;">Superadmin Panel</div>
    </div>

    <?php if($msg): ?>
    <div class="alert-custom alert-success" id="alertMsg">✅ <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php if($error): ?>
    <div class="alert-custom alert-error" id="alertErr">⚠️ <?= $error ?></div>
    <?php endif; ?>

    <div class="tab-bar">
        <a href="?tab=tiket"     class="tab-item <?= $activeTab=='tiket'?'active':'' ?>">🎫 Harga Tiket Penumpang</a>
        <a href="?tab=kendaraan" class="tab-item <?= $activeTab=='kendaraan'?'active':'' ?>">🚗 Harga Kendaraan</a>
    </div>


    <?php if($activeTab === 'tiket'): ?>
    <!-- ══════════ HARGA TIKET ══════════ -->
    <div class="gcard">
        <div class="gcard-title">➕ Tambah Harga Tiket</div>
        <form method="POST" onsubmit="return validasiForm(this,'tiket')">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="flabel">Pelabuhan Asal</label>
                    <select id="t_asal" name="t_asal" class="fselect" required
                        onchange="applyPair('t_asal','t_tujuan_hidden','t_tujuan_display')">
                        <option value="">— Pilih Asal —</option>
                        <?php foreach($pel_arr as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama_pelabuhan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="flabel">Pelabuhan Tujuan</label>
                    <input type="hidden" name="t_tujuan" id="t_tujuan_hidden">
                    <div class="tujuan-display empty" id="t_tujuan_display">— Otomatis dari asal —</div>
                </div>
                <div class="col-md-2">
                    <label class="flabel">Layanan</label>
                    <select name="t_layanan" class="fselect" required>
                        <option value="Reguler">🪑 Reguler</option>
                        <option value="Express">⚡ Express</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="flabel">Harga</label>
                    <input type="text" name="t_harga" class="finput"
                        placeholder="Rp 0" oninput="formatRp(this)" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" name="tambah_tiket" class="btn-add w-100">＋ Tambah</button>
                </div>
            </div>
        </form>
    </div>

    <div class="gcard">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="gcard-title mb-0">
                📋 Data Harga Tiket
                <span class="count-badge" id="cntTiket">0</span>
            </div>
            <div class="search-wrap">
                <span class="search-icon">🔍</span>
                <input type="text" id="searchTiket" placeholder="Cari rute..."
                    oninput="searchTable('tblTiket','searchTiket','cntTiket')">
            </div>
        </div>
        <div class="tbl-wrap">
        <table id="tblTiket">
            <thead>
                <tr>
                    <th>No</th><th>Rute</th><th>Layanan</th><th>Harga</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $no = 1; $tiket_rows = [];
            while($h = $data_tiket->fetch_assoc()) $tiket_rows[] = $h;
            if(empty($tiket_rows)): ?>
            <tr><td colspan="5">
                <div class="empty-state"><div class="icon">🎫</div>Belum ada data harga tiket</div>
            </td></tr>
            <?php else: foreach($tiket_rows as $h): ?>
            <tr>
                <td style="color:#475569;font-size:12px;"><?= $no++ ?></td>
                <td>
                    <div class="rute-cell">
                        <span class="port"><?= htmlspecialchars($h['nm_asal']) ?></span>
                        <span class="arrow">→</span>
                        <span class="port"><?= htmlspecialchars($h['nm_tujuan']) ?></span>
                    </div>
                </td>
                <td>
                    <?php if(strtolower($h['layanan'])==='reguler'): ?>
                        <span class="badge-reg">🪑 Reguler</span>
                    <?php else: ?>
                        <span class="badge-exp">⚡ Express</span>
                    <?php endif; ?>
                </td>
                <td style="font-weight:700;color:#38bdf8;">
                    Rp <?= number_format($h['harga'],0,',','.') ?>
                </td>
                <td>
                    <div class="d-flex gap-2">
                        <button class="btn-edit" onclick="openEditTiket(
                            <?= $h['id'] ?>,<?= $h['asal_id'] ?>,<?= $h['tujuan_id'] ?>,
                            '<?= htmlspecialchars($h['layanan']) ?>',<?= $h['harga'] ?>,
                            '<?= htmlspecialchars(addslashes($h['nm_tujuan'])) ?>'
                        )">✏️ Edit</button>
                        <button class="btn-del" onclick="openHapus(
                            <?= $h['id'] ?>,
                            '<?= htmlspecialchars($h['nm_asal']." → ".$h['nm_tujuan']) ?>',
                            'tiket'
                        )">🗑️ Hapus</button>
                    </div>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>


    <?php else: ?>
    <!-- ══════════════════════════════════════════════
         HARGA KENDARAAN — TABEL DATA
    ══════════════════════════════════════════════ -->
    <div class="gcard">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="gcard-title mb-0">
                🚗 Data Harga Kendaraan
                <span class="count-badge" id="cntKend">0</span>
            </div>
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <button type="button" class="btn-add" onclick="openModalTambahKend()">➕ Tambah Harga</button>
                <button type="button" class="btn-edit" id="toggleSelectModeBtn" onclick="toggleSelectMode()">Pilih</button>
                <button type="button" class="btn-del" onclick="openHapusMulti()">🗑️ Hapus Terpilih</button>
                <div class="search-wrap">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="searchKend" placeholder="Cari rute / golongan..."
                        oninput="searchTable('tblKend','searchKend','cntKend')">
                </div>
            </div>
        </div>
        <div class="tbl-wrap">
        <table id="tblKend">
            <thead>
                <tr>
                    <th class="select-col" style="width:50px;text-align:center;display:none;">
                        Pilih<br>
                        <input type="checkbox" id="chkAllKend" onchange="toggleSelectAll('chkAllKend','selKend')">
                    </th>
                    <th>No</th>
                    <th>Rute</th>
                    <th>Golongan</th>
                    <th class="th-reg">🪑 Harga Reguler</th>
                    <th class="th-exp">⚡ Harga Express</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $no = 1; $kend_rows = [];
            while($k = $data_kend->fetch_assoc()) $kend_rows[] = $k;
            if(empty($kend_rows)): ?>
            <tr><td colspan="7">
                <div class="empty-state"><div class="icon">🚗</div>Belum ada data harga kendaraan</div>
            </td></tr>
            <?php else: foreach($kend_rows as $k): ?>
            <tr>
                <td class="select-col" style="display:none;">
                    <input type="checkbox" class="selKend" value="<?= $k['id'] ?>">
                </td>
                <td style="color:#475569;font-size:12px;"><?= $no++ ?></td>
                <td>
                    <div class="rute-cell">
                        <span class="port"><?= htmlspecialchars($k['nm_asal']) ?></span>
                        <span class="arrow">→</span>
                        <span class="port"><?= htmlspecialchars($k['nm_tujuan']) ?></span>
                    </div>
                </td>
                <td>
                    <span class="badge-gol"><?= htmlspecialchars(golonganLabel($k['golongan'])) ?></span>
                </td>
                <td style="font-weight:700;color:#38bdf8;">
                    <?= fmtHarga($k['harga_reguler']) ?>
                </td>
                <td style="font-weight:700;color:#fbbf24;">
                    <?= fmtHarga($k['harga_express']) ?>
                </td>
                <td>
                    <div class="d-flex gap-2">
                        <button class="btn-edit" onclick="openEditKend(
                            <?= $k['id'] ?>,
                            <?= $k['asal_id'] ?>,
                            <?= $k['tujuan_id'] ?>,
                            '<?= htmlspecialchars($k['golongan']) ?>',
                            <?= (int)$k['harga_reguler'] ?>,
                            <?= (int)$k['harga_express'] ?>,
                            '<?= htmlspecialchars(addslashes($k['nm_tujuan'])) ?>'
                        )">✏️ Edit</button>
                        <button class="btn-del" onclick="openHapus(
                            <?= $k['id'] ?>,
                            '<?= htmlspecialchars($k['nm_asal']." → ".$k['nm_tujuan']) ?> (<?= golonganLabel($k['golongan']) ?>)',
                            'kendaraan'
                        )">🗑️ Hapus</button>
                    </div>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endif; ?>

</div><!-- end main-wrap -->


<!-- ════ MODAL EDIT TIKET ════ -->
<div class="modal fade" id="modalEditTiket" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title">✏️ Edit Harga Tiket</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <form method="POST">
    <div class="modal-body">
        <input type="hidden" name="et_id" id="et_id">
        <input type="hidden" name="et_tujuan" id="et_tujuan_hidden">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="flabel">Pelabuhan Asal</label>
                <select name="et_asal" id="et_asal" class="fselect" required
                    onchange="applyPair('et_asal','et_tujuan_hidden','et_tujuan_display')">
                    <?php foreach($pel_arr as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama_pelabuhan']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="flabel">Pelabuhan Tujuan</label>
                <div class="tujuan-display empty" id="et_tujuan_display">—</div>
            </div>
            <div class="col-md-6">
                <label class="flabel">Layanan</label>
                <select name="et_layanan" id="et_layanan" class="fselect" required>
                    <option value="Reguler">🪑 Reguler</option>
                    <option value="Express">⚡ Express</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="flabel">Harga</label>
                <input type="text" name="et_harga" id="et_harga" class="finput"
                    oninput="formatRp(this)" required>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn-del" data-bs-dismiss="modal">Batal</button>
        <button type="submit" name="edit_tiket" class="btn-add">💾 Simpan Perubahan</button>
    </div>
    </form>
</div>
</div>
</div>


<!-- ════ MODAL EDIT KENDARAAN ════ -->
<div class="modal fade" id="modalEditKend" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title">✏️ Edit Harga Kendaraan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <form method="POST">
    <div class="modal-body">
        <input type="hidden" name="ek_id" id="ek_id">
        <input type="hidden" name="ek_tujuan" id="ek_tujuan_hidden">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="flabel">Pelabuhan Asal</label>
                <select name="ek_asal" id="ek_asal" class="fselect" required
                    onchange="applyPair('ek_asal','ek_tujuan_hidden','ek_tujuan_display')">
                    <?php foreach($pel_arr as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama_pelabuhan']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="flabel">Pelabuhan Tujuan</label>
                <div class="tujuan-display empty" id="ek_tujuan_display">—</div>
            </div>
            <div class="col-md-12">
                <label class="flabel">Golongan Kendaraan</label>
                <select name="ek_golongan" id="ek_golongan" class="fselect" required>
                    <option value="gol_1">Gol I — Sepeda</option>
                    <option value="gol_2">Gol II — Motor &lt;500cc</option>
                    <option value="gol_3">Gol III — Motor &gt;500cc</option>
                    <option value="gol_4a">Gol IVA — Mobil Penumpang</option>
                    <option value="gol_4b">Gol IVB — Mobil Barang ≤5m</option>
                    <option value="gol_5a">Gol VA — Bus Sedang</option>
                    <option value="gol_5b">Gol VB — Truk Sedang</option>
                    <option value="gol_6a">Gol VIA — Bus Besar</option>
                    <option value="gol_6b">Gol VIB — Truk Besar</option>
                    <option value="gol_7">Gol VII — Tronton 10–12m</option>
                    <option value="gol_8">Gol VIII — Tronton 12–16m</option>
                    <option value="gol_9">Gol IX — Tronton &gt;16m</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="flabel">🪑 Harga Reguler</label>
                <input type="text" name="ek_harga_reguler" id="ek_harga_reguler" class="finput"
                    placeholder="Rp 0" oninput="formatRp(this)" required>
            </div>
            <div class="col-md-6">
                <label class="flabel">⚡ Harga Express</label>
                <input type="text" name="ek_harga_express" id="ek_harga_express" class="finput"
                    placeholder="Rp 0" oninput="formatRp(this)" required>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn-del" data-bs-dismiss="modal">Batal</button>
        <button type="submit" name="edit_kendaraan" class="btn-add">💾 Simpan Perubahan</button>
    </div>
    </form>
</div>
</div>
</div>


<!-- ════ MODAL TAMBAH BATCH KENDARAAN ════ -->
<div class="modal fade" id="modalTambahKend" tabindex="-1">
<div class="modal-dialog modal-dialog-centered modal-xl">
<div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title">➕ Tambah Harga Kendaraan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <form method="POST" id="formBatchKend" onsubmit="return validasiBatch()">
    <div class="modal-body" style="max-height:75vh;overflow-y:auto;">
        <p style="font-size:12px;color:#64748b;margin-bottom:18px;">
            Isi harga untuk setiap golongan sekaligus. Kolom yang dikosongkan akan dilewati.
            Nilai <b style="color:#4ade80">0</b> = Gratis. Jika rute + golongan sudah ada, harga akan diperbarui.
        </p>

        <!-- Pilih Rute -->
        <div class="batch-rute-row" style="margin-bottom:16px;padding-bottom:16px;">
            <div class="col-rute">
                <label class="flabel">Pelabuhan Asal</label>
                <select id="k_asal" name="k_asal" class="fselect" required
                    onchange="applyPair('k_asal','k_tujuan_hidden','k_tujuan_display')">
                    <option value="">— Pilih Asal —</option>
                    <?php foreach($pel_arr as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama_pelabuhan']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-rute">
                <label class="flabel">Pelabuhan Tujuan</label>
                <input type="hidden" name="k_tujuan" id="k_tujuan_hidden">
                <div class="tujuan-display empty" id="k_tujuan_display">— Otomatis dari asal —</div>
            </div>
        </div>

        <!-- Tabel Batch Golongan -->
        <div class="batch-grid-wrap">
            <table class="batch-grid">
                <thead>
                    <tr>
                        <th style="min-width:200px;">Golongan Kendaraan</th>
                        <th class="th-reg" style="min-width:160px;">🪑 Harga Reguler</th>
                        <th class="th-exp" style="min-width:160px;">⚡ Harga Express</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($golonganList as $gol => $label): ?>
                <tr>
                    <td class="td-label">
                        <span class="badge-gol-sm"><?= htmlspecialchars($label) ?></span>
                    </td>
                    <td>
                        <input type="text"
                            class="batch-input-reg"
                            name="harga_<?= $gol ?>_reguler"
                            id="inp_<?= $gol ?>_reguler"
                            placeholder="Rp 0"
                            oninput="formatRp(this)"
                            autocomplete="off">
                    </td>
                    <td>
                        <input type="text"
                            class="batch-input-exp"
                            name="harga_<?= $gol ?>_express"
                            id="inp_<?= $gol ?>_express"
                            placeholder="Rp 0"
                            oninput="formatRp(this)"
                            autocomplete="off">
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="batch-info" style="margin-top:14px;">
            💡 Tip: Gunakan tombol di bawah untuk mengisi semua kolom sekaligus, lalu sesuaikan satu per satu.
        </div>
        <div class="batch-submit-row" style="margin-top:14px;justify-content:flex-start;">
            <button type="button" class="btn-fill-zero" onclick="fillAllZero('reguler')">
                🪑 Isi Semua Reguler = 0
            </button>
            <button type="button" class="btn-fill-zero" onclick="fillAllZero('express')">
                ⚡ Isi Semua Express = 0
            </button>
            <button type="button" class="btn-clear-batch" onclick="clearBatch()">
                🗑️ Reset Semua
            </button>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn-del" data-bs-dismiss="modal">Batal</button>
        <button type="submit" name="tambah_kendaraan_batch" class="btn-add" style="padding:10px 28px;">
            💾 Simpan Semua Harga
        </button>
    </div>
    </form>
</div>
</div>
</div>


<!-- ════ MODAL KONFIRMASI HAPUS ════ -->
<div class="modal fade" id="modalHapus" tabindex="-1" data-bs-backdrop="static">
<div class="modal-dialog modal-dialog-centered modal-sm">
<div class="modal-content">
    <div class="modal-body text-center" style="padding:28px 24px;">
        <div style="font-size:44px;margin-bottom:12px;">🗑️</div>
        <h5 style="font-weight:700;margin-bottom:8px;">Hapus Data?</h5>
        <p style="font-size:13px;color:#64748b;margin-bottom:20px;">
            Data berikut akan dihapus permanen:<br>
            <strong id="hapusLabel" style="color:white;"></strong>
        </p>
        <form method="POST" id="formHapus">
            <input type="hidden" name="hapus_id" id="hapusIdInput">
            <input type="hidden" name="" id="hapusActionInput">
            <div class="d-flex gap-2 justify-content-center">
                <button type="button" class="btn-edit" data-bs-dismiss="modal">Batal</button>
                <button type="submit" id="hapusSubmitBtn" class="btn-del" style="padding:8px 20px;">Ya, Hapus</button>
            </div>
        </form>
    </div>
</div>
</div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>

// ════ DATA PASANGAN PELABUHAN DARI PHP ════
const pairMap = <?= json_encode($pairMap) ?>;
const namaMap = <?= json_encode($namaMap) ?>;

// ════ APPLY PAIR ════
function applyPair(asalId, hiddenId, displayId){
    const asalEl   = document.getElementById(asalId);
    const hiddenEl = document.getElementById(hiddenId);
    const dispEl   = document.getElementById(displayId);
    const asalVal  = parseInt(asalEl.value, 10);

    if(!asalVal){
        hiddenEl.value     = '';
        dispEl.textContent = '— Pilih asal dulu —';
        dispEl.classList.add('empty');
        return;
    }

    const pairId = pairMap[asalVal] ?? null;
    if(pairId && namaMap[pairId]){
        hiddenEl.value     = pairId;
        dispEl.textContent = '⚓ ' + namaMap[pairId];
        dispEl.classList.remove('empty');
    } else {
        hiddenEl.value     = '';
        dispEl.textContent = '— Tidak ada pasangan —';
        dispEl.classList.add('empty');
    }
}

// ════ FORMAT RUPIAH ════
function formatRp(el){
    let raw = el.value.replace(/[^0-9]/g, '');
    el.value = raw ? new Intl.NumberFormat('id-ID').format(parseInt(raw)) : '';
}

// ════ VALIDASI FORM TIKET ════
function validasiForm(form, tipe){
    if(tipe === 'tiket'){
        const tujuan = document.getElementById('t_tujuan_hidden').value;
        if(!tujuan){
            showAlert('⚠️ Pilih pelabuhan asal terlebih dahulu!', 'error');
            return false;
        }
    }
    return true;
}

// ════ VALIDASI BATCH KENDARAAN ════
function validasiBatch(){
    const tujuan = document.getElementById('k_tujuan_hidden').value;
    if(!tujuan){
        showAlert('⚠️ Pilih pelabuhan asal terlebih dahulu!', 'error');
        return false;
    }
    const inputs  = document.querySelectorAll('#formBatchKend input[type="text"]');
    let anyFilled = false;
    inputs.forEach(inp => { if(inp.value.trim() !== '') anyFilled = true; });
    if(!anyFilled){
        showAlert('⚠️ Isi minimal satu harga sebelum menyimpan!', 'error');
        return false;
    }
    return true;
}

// ════ FILL ALL ZERO ════
function fillAllZero(layanan){
    const cls = layanan === 'reguler' ? '.batch-input-reg' : '.batch-input-exp';
    document.querySelectorAll(cls).forEach(inp => { inp.value = '0'; });
}

// ════ CLEAR BATCH ════
function clearBatch(){
    document.querySelectorAll('.batch-input-reg, .batch-input-exp').forEach(inp => { inp.value = ''; });
}

// ════ OPEN MODAL EDIT TIKET ════
function openEditTiket(id, asal, tujuan, layanan, harga, nmTujuan){
    document.getElementById('et_id').value            = id;
    document.getElementById('et_asal').value          = asal;
    document.getElementById('et_tujuan_hidden').value = tujuan;
    document.getElementById('et_layanan').value       = layanan;
    document.getElementById('et_harga').value         = new Intl.NumberFormat('id-ID').format(harga);

    const dispEl = document.getElementById('et_tujuan_display');
    dispEl.textContent = '⚓ ' + nmTujuan;
    dispEl.classList.remove('empty');

    new bootstrap.Modal(document.getElementById('modalEditTiket')).show();
}

// ════ OPEN MODAL EDIT KENDARAAN ════
// hargaReg & hargaExp menggantikan param layanan + harga yang lama
function openEditKend(id, asal, tujuan, golongan, hargaReg, hargaExp, nmTujuan){
    document.getElementById('ek_id').value             = id;
    document.getElementById('ek_asal').value           = asal;
    document.getElementById('ek_tujuan_hidden').value  = tujuan;
    document.getElementById('ek_golongan').value       = golongan;
    document.getElementById('ek_harga_reguler').value  = new Intl.NumberFormat('id-ID').format(hargaReg);
    document.getElementById('ek_harga_express').value  = new Intl.NumberFormat('id-ID').format(hargaExp);

    const dispEl = document.getElementById('ek_tujuan_display');
    dispEl.textContent = '⚓ ' + nmTujuan;
    dispEl.classList.remove('empty');

    new bootstrap.Modal(document.getElementById('modalEditKend')).show();
}

// ════ OPEN MODAL TAMBAH KENDARAAN ════
function openModalTambahKend(){
    // Reset form sebelum buka
    document.getElementById('k_asal').value          = '';
    document.getElementById('k_tujuan_hidden').value = '';
    const disp = document.getElementById('k_tujuan_display');
    disp.textContent = '— Otomatis dari asal —';
    disp.classList.add('empty');
    clearBatch();
    new bootstrap.Modal(document.getElementById('modalTambahKend')).show();
}

// ════ HAPUS MULTI ════
function getSelectedKendaraanIds(){
    return Array.from(document.querySelectorAll('.selKend:checked')).map(cb => cb.value);
}

function openHapusMulti(){
    const ids = getSelectedKendaraanIds();
    if(ids.length === 0){
        showAlert('⚠️ Pilih minimal satu harga kendaraan terlebih dahulu!', 'error');
        return;
    }
    document.getElementById('hapusIdInput').value     = ids.join(',');
    document.getElementById('hapusLabel').textContent = ids.length + ' harga kendaraan terpilih';
    document.getElementById('hapusActionInput').name  = 'hapus_kendaraan_multi';
    document.getElementById('hapusActionInput').value = '1';
    new bootstrap.Modal(document.getElementById('modalHapus')).show();
}

function toggleSelectMode(){
    const active = document.body.classList.toggle('select-mode-active');
    document.getElementById('toggleSelectModeBtn').textContent = active ? 'Batal Pilih' : 'Pilih';
    document.querySelectorAll('.select-col').forEach(el => {
        el.style.display = active ? '' : 'none';
    });
    if(!active){
        document.getElementById('chkAllKend').checked = false;
        document.querySelectorAll('.selKend').forEach(cb => cb.checked = false);
    }
}

function toggleSelectAll(masterId, checkboxClass){
    const master = document.getElementById(masterId);
    document.querySelectorAll('.' + checkboxClass).forEach(cb => cb.checked = master.checked);
}

// ════ OPEN MODAL HAPUS ════
function openHapus(id, label, tipe){
    document.getElementById('hapusIdInput').value     = id;
    document.getElementById('hapusLabel').textContent = label;
    document.getElementById('hapusActionInput').name  = tipe === 'tiket' ? 'hapus_tiket' : 'hapus_kendaraan';
    document.getElementById('hapusActionInput').value = '1';
    new bootstrap.Modal(document.getElementById('modalHapus')).show();
}

// ════ SEARCH TABLE ════
function searchTable(tableId, searchId, countId){
    const keyword = document.getElementById(searchId).value.toLowerCase();
    const rows    = document.querySelectorAll('#'+tableId+' tbody tr');
    let visible   = 0;
    rows.forEach(row => {
        const show = row.textContent.toLowerCase().includes(keyword) || keyword === '';
        row.style.display = show ? '' : 'none';
        if(show && row.querySelector('td:nth-child(2)')?.textContent.trim() !== '') visible++;
    });
    const cnt = document.getElementById(countId);
    if(cnt) cnt.textContent = visible;
}

// ════ ALERT TOAST ════
function showAlert(pesan, tipe){
    const existing = document.getElementById('toastMsg');
    if(existing) existing.remove();
    const div = document.createElement('div');
    div.id = 'toastMsg';
    div.style.cssText = `
        position:fixed;top:20px;right:20px;z-index:9999;
        padding:13px 20px;border-radius:12px;font-size:13px;
        font-family:'Poppins',sans-serif;font-weight:500;
        max-width:360px;animation:fadeIn 0.3s ease;
        display:flex;align-items:center;gap:10px;
        box-shadow:0 8px 32px rgba(0,0,0,0.4);
    `;
    div.style.background = tipe==='error' ? 'rgba(239,68,68,0.15)' : 'rgba(34,197,94,0.12)';
    div.style.border     = tipe==='error' ? '1px solid rgba(239,68,68,0.3)' : '1px solid rgba(34,197,94,0.25)';
    div.style.color      = tipe==='error' ? '#f87171' : '#4ade80';
    div.innerHTML        = pesan;
    document.body.appendChild(div);
    setTimeout(() => {
        div.style.transition = 'opacity 0.4s';
        div.style.opacity    = '0';
        setTimeout(() => div.remove(), 400);
    }, 3500);
}

// ════ INIT ════
document.addEventListener('DOMContentLoaded', () => {

    ['cntTiket','cntKend'].forEach(countId => {
        const el = document.getElementById(countId);
        if(!el) return;
        const tableId = countId === 'cntTiket' ? 'tblTiket' : 'tblKend';
        const cellIdx = countId === 'cntTiket' ? 'td:first-child' : 'td:nth-child(2)';
        let n = 0;
        document.querySelectorAll('#'+tableId+' tbody tr').forEach(r => {
            if(r.querySelector(cellIdx)?.textContent.trim() !== '') n++;
        });
        el.textContent = n;
    });

    ['alertMsg','alertErr'].forEach(id => {
        const el = document.getElementById(id);
        if(el) setTimeout(() => {
            el.style.transition = 'opacity 0.4s';
            el.style.opacity    = '0';
            setTimeout(() => el.remove(), 400);
        }, 4000);
    });

    document.getElementById('modalTambahKend')?.addEventListener('hidden.bs.modal', () => {
        document.getElementById('k_asal').value          = '';
        document.getElementById('k_tujuan_hidden').value = '';
        const disp = document.getElementById('k_tujuan_display');
        disp.textContent = '— Otomatis dari asal —';
        disp.classList.add('empty');
        clearBatch();
    });
    document.getElementById('modalEditTiket')?.addEventListener('hidden.bs.modal', () => {
        const d = document.getElementById('et_tujuan_display');
        d.textContent = '—'; d.classList.add('empty');
        document.getElementById('et_tujuan_hidden').value = '';
    });
    document.getElementById('modalEditKend')?.addEventListener('hidden.bs.modal', () => {
        const d = document.getElementById('ek_tujuan_display');
        d.textContent = '—'; d.classList.add('empty');
        document.getElementById('ek_tujuan_hidden').value = '';
    });
});

</script>
<script src="../assets/js/mobile-nav.js"></script>
</body>
</html>
