<?php
error_reporting(0);
include('../config/koneksi.php');
header('Content-Type: application/json');

$asal_id = isset($_GET['asal_id']) ? (int)$_GET['asal_id'] : 0;

if($asal_id <= 0){
    echo json_encode(['success'=>false,'message'=>'asal_id tidak valid']);
    exit;
}

// ── Harga penumpang ──
$q = mysqli_query($conn,"
    SELECT
        p.id             AS tid,
        p.nama_pelabuhan AS tnama,
        p.lokasi         AS tlokasi,
        MAX(CASE WHEN LOWER(h.layanan)='reguler' THEN h.harga END) AS h_reg,
        MAX(CASE WHEN LOWER(h.layanan)='express' THEN h.harga END) AS h_exp
    FROM harga h
    JOIN pelabuhan p ON p.id = h.tujuan_id
    WHERE h.asal_id = $asal_id
    GROUP BY p.id, p.nama_pelabuhan, p.lokasi
    ORDER BY p.nama_pelabuhan ASC
");

if(!$q){ echo json_encode(['success'=>false,'message'=>mysqli_error($conn)]); exit; }

$tujuans    = [];
$harga_data = [];
$tid_list   = [];

while($r = mysqli_fetch_assoc($q)){
    $label      = $r['tnama'] . ($r['tlokasi'] ? ', '.$r['tlokasi'] : '');
    $tid        = (int)$r['tid'];
    $tujuans[]  = [
        'id'    => $tid,
        'nama'  => $r['tnama'],
        'lokasi'=> $r['tlokasi'] ?? '',
        'label' => $label,
    ];
    $harga_data[$tid] = [
        'reguler' => (int)($r['h_reg'] ?? 0),
        'express' => (int)($r['h_exp'] ?? 0),
    ];
    $tid_list[] = $tid;
}

if(empty($tid_list)){
    echo json_encode(['success'=>false,'message'=>'Tidak ada rute tersedia']);
    exit;
}

// ── Harga kendaraan — pakai kolom harga_reguler & harga_express ──
$harga_kendaraan = [];
$in = implode(',', $tid_list);

$qk = mysqli_query($conn,"
    SELECT tujuan_id, golongan, harga_reguler, harga_express
    FROM harga_kendaraan
    WHERE asal_id = $asal_id AND tujuan_id IN ($in)
    ORDER BY tujuan_id, golongan
");

if($qk){
    while($r = mysqli_fetch_assoc($qk)){
        $tid = (int)$r['tujuan_id'];
        $gol = $r['golongan'];
        // Key lowercase agar cocok dengan value selLayanan di JS
        $harga_kendaraan[$tid][$gol] = [
            'reguler' => (int)$r['harga_reguler'],
            'express' => (int)$r['harga_express'],
        ];
    }
}

echo json_encode([
    'success'         => true,
    'tujuans'         => $tujuans,
    'harga_data'      => $harga_data,
    'harga_kendaraan' => $harga_kendaraan,
    'total'           => count($tujuans),
]);
?>