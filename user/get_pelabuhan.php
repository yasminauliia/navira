<?php
error_reporting(0);
include('../config/koneksi.php');
header('Content-Type: application/json');

$q = mysqli_query($conn,"
    SELECT DISTINCT p.id, p.nama_pelabuhan, p.lokasi
    FROM pelabuhan p
    INNER JOIN harga h ON h.asal_id = p.id
    ORDER BY p.nama_pelabuhan ASC
");

$asals = [];
while($r = mysqli_fetch_assoc($q)){
    $asals[] = [
        'id'    => (int)$r['id'],
        'nama'  => $r['nama_pelabuhan'],
        'lokasi'=> $r['lokasi'] ?? '',
        'label' => $r['nama_pelabuhan'] . ($r['lokasi'] ? ', '.$r['lokasi'] : '')
    ];
}
echo json_encode(['success'=>true,'asals'=>$asals]);
?>