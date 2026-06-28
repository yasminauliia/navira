<?php
require '../dompdf/vendor/autoload.php';

include('../config/koneksi.php');
include('../config/tiket_helper.php');
include('../config/payment_helper.php');
include('../config/tiket_pdf_helper.php');

ensurePaymentColumns($conn);

if (!isset($_GET['kode'])) {
    die('Kode tidak ditemukan!');
}

$kode = trim((string)$_GET['kode']);

if (!streamTiketPdfForKode($conn, $kode)) {
    die('E-tiket tidak ditemukan atau pembayaran belum lunas.');
}

exit;
