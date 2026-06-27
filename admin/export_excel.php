<?php
include('auth.php');
include('../config/koneksi.php');
include('../config/payment_helper.php');

ensurePaymentColumns($conn);

/* =============================
   LOAD PHPSPREADSHEET
============================= */
$autoload = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($autoload)) {
    die("❌ PhpSpreadsheet belum diinstall!
    <br>Jalankan: <code>composer require phpoffice/phpspreadsheet</code>");
}

require $autoload;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

/* =============================
   PARAMETER
============================= */
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to'] ?? date('Y-m-d');

/* =============================
   QUERY
============================= */
$sql = "
SELECT 
    t.kode_booking,
    u.nama,
    u.email,
    COALESCE(a.nama_pelabuhan, '-') AS asal,
    COALESCE(b.nama_pelabuhan, '-') AS tujuan,
    t.tanggal,
    t.jam,
    t.layanan,
    t.kendaraan,
    t.plat,
    t.total_penumpang,
    t.total_harga,
    t.status,
    COALESCE(t.payment_status, 'paid') AS payment_status,
    t.paid_at
FROM tickets t
LEFT JOIN users u ON t.user_id = u.id
LEFT JOIN pelabuhan a ON a.id = t.asal_id
LEFT JOIN pelabuhan b ON b.id = t.tujuan_id
WHERE t.tanggal BETWEEN '$from' AND '$to'
ORDER BY t.tanggal DESC
";

$res = mysqli_query($conn, $sql);

if (!$res) {
    die("❌ Query error: " . mysqli_error($conn));
}

/* =============================
   INIT EXCEL
============================= */
$excel = new Spreadsheet();
$sheet = $excel->getActiveSheet();

/* =============================
   HEADER TABLE
============================= */
$headers = [
    'A1'=>'No','B1'=>'Kode','C1'=>'Nama','D1'=>'Email',
    'E1'=>'Asal','F1'=>'Tujuan','G1'=>'Tanggal','H1'=>'Jam',
    'I1'=>'Layanan','J1'=>'Kendaraan','K1'=>'Plat',
    'L1'=>'Penumpang','M1'=>'Total','N1'=>'Status Tiket','O1'=>'Status Pembayaran'
];

foreach($headers as $cell => $text){
    $sheet->setCellValue($cell, $text);
}

/* =============================
   STYLE HEADER
============================= */
$sheet->getStyle('A1:O1')->applyFromArray([
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF']
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '2563EB'] // biru cakep
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER
    ]
]);

/* =============================
   DATA
============================= */
$row = 2;
$no  = 1;
$totalSemua = 0;

while ($d = mysqli_fetch_assoc($res)) {

    $sheet->setCellValue('A'.$row, $no++);
    $sheet->setCellValue('B'.$row, $d['kode_booking']);
    $sheet->setCellValue('C'.$row, $d['nama']);
    $sheet->setCellValue('D'.$row, $d['email']);
    $sheet->setCellValue('E'.$row, $d['asal']);
    $sheet->setCellValue('F'.$row, $d['tujuan']);
    $sheet->setCellValue('G'.$row, $d['tanggal']);
    $sheet->setCellValue('H'.$row, $d['jam']);
    $sheet->setCellValue('I'.$row, $d['layanan']);
    $sheet->setCellValue('J'.$row, $d['kendaraan']);
    $sheet->setCellValue('K'.$row, $d['plat']);
    $sheet->setCellValue('L'.$row, $d['total_penumpang']);
    $sheet->setCellValue('M'.$row, $d['total_harga']);
    $sheet->setCellValue('N'.$row, strtoupper($d['status']));
    $sheet->setCellValue('O'.$row, getPaymentStatusMeta($d['payment_status'] ?? 'pending')['label']);

    if (isTicketPaid($d)) {
        $totalSemua += (int)$d['total_harga'];
    }

    // Zebra row
    if ($row % 2 == 0) {
        $sheet->getStyle("A$row:O$row")->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setRGB('EFF6FF');
    }

    $row++;
}

$lastRow = $row - 1;

/* =============================
   BORDER
============================= */
$sheet->getStyle("A1:O$lastRow")->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
]);

/* =============================
   FORMAT
============================= */
$sheet->getStyle("M2:M$lastRow")
->getNumberFormat()
->setFormatCode('"Rp" #,##0');

/* =============================
   ALIGNMENT
============================= */
$sheet->getStyle("A2:A$lastRow")->getAlignment()->setHorizontal('center');
$sheet->getStyle("G2:H$lastRow")->getAlignment()->setHorizontal('center');
$sheet->getStyle("L2:L$lastRow")->getAlignment()->setHorizontal('center');
$sheet->getStyle("M2:M$lastRow")->getAlignment()->setHorizontal('right');
$sheet->getStyle("N2:N$lastRow")->getAlignment()->setHorizontal('center');

/* =============================
   AUTO WIDTH
============================= */
foreach(range('A','N') as $col){
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

/* =============================
   FREEZE HEADER
============================= */
$sheet->freezePane('A2');

/* =============================
   TOTAL BAWAH
============================= */
$totalRow = $lastRow + 1;

$sheet->mergeCells("A$totalRow:L$totalRow");
$sheet->setCellValue("A$totalRow", "TOTAL");
$sheet->setCellValue("M$totalRow", $totalSemua);

$sheet->getStyle("A$totalRow:N$totalRow")->applyFromArray([
    'font' => ['bold' => true],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'DBEAFE']
    ]
]);

/* =============================
   OUTPUT
============================= */
$filename = "laporan_tiket.xlsx";

if (ob_get_length()) ob_clean();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($excel);
$writer->save('php://output');
exit;