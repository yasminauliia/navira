<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/payment_helper.php';

ensurePaymentColumns($conn);
echo "Kolom pembayaran siap.\n";
