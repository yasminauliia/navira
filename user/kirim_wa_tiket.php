<?php
/**
 * Kirim link PDF tiket via WhatsApp (Fonnte).
 * POST/GET: kode=TKT..., opsional force=1 untuk kirim ulang.
 */
session_start();

include('../config/koneksi.php');
include('../config/tiket_helper.php');
include('../config/payment_helper.php');
include('../config/whatsapp_helper.php');

header('Content-Type: application/json; charset=utf-8');

$kode = trim((string)($_GET['kode'] ?? $_POST['kode'] ?? ''));
$force = in_array(strtolower((string)($_GET['force'] ?? $_POST['force'] ?? '')), ['1', 'true', 'yes'], true);

if ($kode === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Parameter kode booking wajib diisi',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Silakan login terlebih dahulu',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

ensurePaymentColumns($conn);

$ticket = loadTicketByKode($conn, $kode);
if (!$ticket) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Tiket tidak ditemukan',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ((int)$ticket['user_id'] !== (int)$_SESSION['user_id']) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Anda tidak memiliki akses ke tiket ini',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$result = kirimWhatsAppTiketSetelahBayar($conn, $kode, $force);

http_response_code($result['success'] ? 200 : 500);

echo json_encode([
    'success'  => $result['success'],
    'message'  => $result['message'],
    'response' => $result['response'],
    'debug'    => $result['debug'],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
