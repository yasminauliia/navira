<?php
/**
 * Kirim link PDF tiket via WhatsApp (Fonnte) setelah pembayaran berhasil.
 */

require_once __DIR__ . '/fonnte.php';
require_once __DIR__ . '/app.php';
require_once __DIR__ . '/payment_helper.php';
require_once __DIR__ . '/tiket_pdf_helper.php';

function normalizeWhatsAppNumber(string $phone): string
{
    $phone = preg_replace('/\D+/', '', $phone) ?? '';

    if ($phone === '') {
        return '';
    }

    if (strpos($phone, '62') === 0) {
        return '0' . substr($phone, 2);
    }

    if (strpos($phone, '0') === 0) {
        return $phone;
    }

    return '0' . $phone;
}

function buildWhatsAppTiketMessage(string $namaPelanggan, string $kodeBooking, string $linkPdf): string
{
    return "Halo {$namaPelanggan},\n\n"
        . "Terima kasih telah melakukan pemesanan tiket kapal.\n\n"
        . "Kode Booking:\n"
        . "{$kodeBooking}\n\n"
        . "Silakan download tiket Anda melalui link berikut:\n"
        . "{$linkPdf}\n\n"
        . "Mohon tunjukkan tiket saat proses check-in.\n\n"
        . "Terima kasih.";
}

/**
 * @return array{success:bool,message:string,response:mixed,http_code:int,curl_error:string}
 */
function sendWhatsAppFonnte(string $nomorWhatsapp, string $message, ?string $token = null): array
{
    $token = $token ?? FONNTE_TOKEN;

    if ($token === '' || $token === 'TOKEN_FONNTE') {
        return [
            'success'     => false,
            'message'     => 'Token Fonnte belum dikonfigurasi di config/fonnte.php',
            'response'    => null,
            'http_code'   => 0,
            'curl_error'  => '',
        ];
    }

    $nomorWhatsapp = normalizeWhatsAppNumber($nomorWhatsapp);
    if ($nomorWhatsapp === '') {
        return [
            'success'     => false,
            'message'     => 'Nomor WhatsApp pelanggan kosong atau tidak valid',
            'response'    => null,
            'http_code'   => 0,
            'curl_error'  => '',
        ];
    }

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL            => FONNTE_API_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => '',
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_POSTFIELDS     => [
            'target'      => $nomorWhatsapp,
            'message'     => $message,
            'countryCode' => FONNTE_COUNTRY_CODE,
        ],
        CURLOPT_HTTPHEADER     => [
            'Authorization: ' . $token,
        ],
    ]);

    $response  = curl_exec($curl);
    $httpCode  = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    $decoded = json_decode((string)$response, true);
    $apiOk   = is_array($decoded) ? (bool)($decoded['status'] ?? false) : false;

    if ($curlError !== '') {
        return [
            'success'     => false,
            'message'     => 'cURL error: ' . $curlError,
            'response'    => $decoded ?? $response,
            'http_code'   => $httpCode,
            'curl_error'  => $curlError,
        ];
    }

    if ($httpCode < 200 || $httpCode >= 300 || !$apiOk) {
        $detail = is_array($decoded) ? (string)($decoded['reason'] ?? $decoded['message'] ?? '') : (string)$response;

        return [
            'success'     => false,
            'message'     => $detail !== '' ? $detail : 'Pengiriman WhatsApp gagal (HTTP ' . $httpCode . ')',
            'response'    => $decoded ?? $response,
            'http_code'   => $httpCode,
            'curl_error'  => '',
        ];
    }

    return [
        'success'     => true,
        'message'     => 'WhatsApp berhasil dikirim',
        'response'    => $decoded ?? $response,
        'http_code'   => $httpCode,
        'curl_error'  => '',
    ];
}

function isWhatsAppAlreadySent(mysqli $conn, string $kodeBooking): bool
{
    ensureTiketPdfColumns($conn);

    $stmt = $conn->prepare('SELECT wa_sent_at FROM tickets WHERE kode_booking = ? LIMIT 1');
    $stmt->bind_param('s', $kodeBooking);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return !empty($row['wa_sent_at']);
}

function markWhatsAppSent(mysqli $conn, string $kodeBooking): void
{
    ensureTiketPdfColumns($conn);

    $stmt = $conn->prepare('UPDATE tickets SET wa_sent_at = NOW() WHERE kode_booking = ? LIMIT 1');
    $stmt->bind_param('s', $kodeBooking);
    $stmt->execute();
    $stmt->close();
}

/**
 * Dipanggil otomatis saat status pembayaran berubah menjadi paid.
 *
 * @return array{success:bool,message:string,response:mixed,debug:array<string,mixed>}
 */
function kirimWhatsAppTiketSetelahBayar(mysqli $conn, string $kodeBooking): array
{
    if (isWhatsAppAlreadySent($conn, $kodeBooking)) {
        return [
            'success'  => true,
            'message'  => 'WhatsApp sudah pernah dikirim untuk booking ini',
            'response' => null,
            'debug'    => ['skipped' => true, 'kode_booking' => $kodeBooking],
        ];
    }

    $ticket = loadTicketByKode($conn, $kodeBooking);
    if (!$ticket || !isTicketPaid($ticket)) {
        return [
            'success'  => false,
            'message'  => 'Tiket belum lunas atau tidak ditemukan',
            'response' => null,
            'debug'    => ['kode_booking' => $kodeBooking],
        ];
    }

    $namaPelanggan  = trim((string)($ticket['nama_pemesan'] ?? ''));
    $nomorWhatsapp  = trim((string)($ticket['hp_pemesan'] ?? ''));
    $kodeBookingVal = trim((string)($ticket['kode_booking'] ?? $kodeBooking));

    if ($namaPelanggan === '') {
        $namaPelanggan = 'Pelanggan';
    }

    $pdfFilename = saveTiketPdfToDisk($conn, $kodeBookingVal);
    if ($pdfFilename === null) {
        return [
            'success'  => false,
            'message'  => 'Gagal membuat atau menyimpan file PDF tiket',
            'response' => null,
            'debug'    => [
                'kode_booking'    => $kodeBookingVal,
                'nomor_whatsapp'  => $nomorWhatsapp,
                'nama_pelanggan'  => $namaPelanggan,
            ],
        ];
    }

    $linkPdf = getTiketPdfPublicUrl($pdfFilename);
    $token   = FONNTE_TOKEN;
    $pesan   = buildWhatsAppTiketMessage($namaPelanggan, $kodeBookingVal, $linkPdf);

    $result = sendWhatsAppFonnte($nomorWhatsapp, $pesan, $token);

    $debug = [
        'kode_booking'   => $kodeBookingVal,
        'nama_pelanggan' => $namaPelanggan,
        'nomor_whatsapp' => $nomorWhatsapp,
        'link_pdf'       => $linkPdf,
        'file_tiket'     => $pdfFilename,
        'http_code'      => $result['http_code'],
        'api_response'   => $result['response'],
    ];

    if (!$result['success']) {
        return [
            'success'  => false,
            'message'  => $result['message'],
            'response' => $result['response'],
            'debug'    => $debug,
        ];
    }

    markWhatsAppSent($conn, $kodeBookingVal);

    return [
        'success'  => true,
        'message'  => $result['message'],
        'response' => $result['response'],
        'debug'    => $debug,
    ];
}

function triggerWhatsAppTiketAfterPayment(mysqli $conn, string $kodeBooking): void
{
    try {
        kirimWhatsAppTiketSetelahBayar($conn, $kodeBooking);
    } catch (Throwable $e) {
        error_log('[WhatsApp Fonnte] ' . $kodeBooking . ': ' . $e->getMessage());
    }
}
