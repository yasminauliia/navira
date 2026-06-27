<?php
/**
 * Helper pembayaran tiket — satu sumber status untuk user & admin.
 *
 * Status standar: paid, pending, challenge, deny, cancel, expire, failed
 */

const PAYMENT_STATUS_PAID      = 'paid';
const PAYMENT_STATUS_PENDING   = 'pending';
const PAYMENT_STATUS_CHALLENGE = 'challenge';
const PAYMENT_STATUS_DENY      = 'deny';
const PAYMENT_STATUS_CANCEL    = 'cancel';
const PAYMENT_STATUS_EXPIRE    = 'expire';
const PAYMENT_STATUS_FAILED    = 'failed';

const PAYMENT_STATUSES_ALL = [
    PAYMENT_STATUS_PAID,
    PAYMENT_STATUS_PENDING,
    PAYMENT_STATUS_CHALLENGE,
    PAYMENT_STATUS_DENY,
    PAYMENT_STATUS_CANCEL,
    PAYMENT_STATUS_EXPIRE,
    PAYMENT_STATUS_FAILED,
];

function ensurePaymentColumns(mysqli $conn): void
{
    static $done = false;
    if ($done) {
        return;
    }

    $cols = [];
    $res  = $conn->query('SHOW COLUMNS FROM tickets');
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $cols[$row['Field']] = $row;
        }
    }

    if (!isset($cols['payment_status'])) {
        $conn->query(
            "ALTER TABLE tickets ADD COLUMN payment_status VARCHAR(20) NOT NULL DEFAULT 'paid' AFTER status"
        );
    } elseif (stripos((string)($cols['payment_status']['Type'] ?? ''), 'enum') !== false) {
        $conn->query(
            "ALTER TABLE tickets MODIFY payment_status VARCHAR(20) NOT NULL DEFAULT 'paid'"
        );
    }

    if (!isset($cols['paid_at'])) {
        $conn->query(
            'ALTER TABLE tickets ADD COLUMN paid_at DATETIME DEFAULT NULL AFTER payment_status'
        );
    }

    migrateLegacyPaymentStatuses($conn);
    $done = true;
}

function migrateLegacyPaymentStatuses(mysqli $conn): void
{
    $map = [
        'LUNAS'               => PAYMENT_STATUS_PAID,
        'MENUNGGU PEMBAYARAN' => PAYMENT_STATUS_PENDING,
        'GAGAL'               => PAYMENT_STATUS_FAILED,
        'KADALUARSA'          => PAYMENT_STATUS_EXPIRE,
    ];

    foreach ($map as $old => $new) {
        $stmt = $conn->prepare('UPDATE tickets SET payment_status = ? WHERE payment_status = ?');
        $stmt->bind_param('ss', $new, $old);
        $stmt->execute();
        $stmt->close();
    }

    $conn->query("UPDATE tickets SET payment_status = 'paid' WHERE payment_status IS NULL OR payment_status = ''");
}

function normalizePaymentStatus(?string $status): string
{
    $s = strtolower(trim((string)$status));

    if ($s === '' || $s === 'lunas') {
        return PAYMENT_STATUS_PAID;
    }

    $legacy = [
        'menunggu pembayaran' => PAYMENT_STATUS_PENDING,
        'gagal'               => PAYMENT_STATUS_FAILED,
        'kadaluarsa'          => PAYMENT_STATUS_EXPIRE,
    ];

    if (isset($legacy[$s])) {
        return $legacy[$s];
    }

    if (in_array($s, PAYMENT_STATUSES_ALL, true)) {
        return $s;
    }

    return PAYMENT_STATUS_PENDING;
}

function shouldUpdatePaymentStatus(string $currentStatus, string $newStatus): bool
{
    $current = normalizePaymentStatus($currentStatus);
    $new     = normalizePaymentStatus($newStatus);

    if ($current === $new) {
        return true;
    }

    // Status sukses bersifat final — hindari downgrade dari callback berulang.
    if ($current === PAYMENT_STATUS_PAID) {
        return false;
    }

    return true;
}

function getTicketPaymentStatus(mysqli $conn, string $kodeBooking): ?string
{
    ensurePaymentColumns($conn);

    $stmt = $conn->prepare('SELECT payment_status FROM tickets WHERE kode_booking = ? LIMIT 1');
    $stmt->bind_param('s', $kodeBooking);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }

    return normalizePaymentStatus($row['payment_status'] ?? '');
}

function updateTicketPaymentStatus(
    mysqli $conn,
    string $kodeBooking,
    string $paymentStatus
): bool
{
    ensurePaymentColumns($conn);

    $paymentStatus = normalizePaymentStatus($paymentStatus);
    $current       = getTicketPaymentStatus($conn, $kodeBooking);
    $wasPaid       = ($current === PAYMENT_STATUS_PAID);

    if ($current === null) {
        return false;
    }

    if (!shouldUpdatePaymentStatus($current, $paymentStatus)) {
        return true;
    }

    if ($paymentStatus === PAYMENT_STATUS_PAID) {
        $stmt = $conn->prepare(
            'UPDATE tickets
             SET payment_status = ?,
                 paid_at = COALESCE(paid_at, NOW())
             WHERE kode_booking = ?
             LIMIT 1'
        );
        $stmt->bind_param('ss', $paymentStatus, $kodeBooking);
    } else {
        $stmt = $conn->prepare(
            'UPDATE tickets SET payment_status = ? WHERE kode_booking = ? LIMIT 1'
        );
        $stmt->bind_param('ss', $paymentStatus, $kodeBooking);
    }

    $ok = $stmt->execute();
    $stmt->close();

    if ($ok && $paymentStatus === PAYMENT_STATUS_PAID && !$wasPaid) {
        require_once __DIR__ . '/whatsapp_helper.php';
        triggerWhatsAppTiketAfterPayment($conn, $kodeBooking);
    }

    return $ok;
}

function loadTicketByKode(mysqli $conn, string $kode): ?array
{
    $stmt = $conn->prepare("SELECT t.*,
        CONCAT(
            COALESCE(a.nama_pelabuhan, '-'),
            IF(a.lokasi IS NOT NULL AND a.lokasi != '', CONCAT(', ', a.lokasi), '')
        ) AS asal,
        CONCAT(
            COALESCE(b.nama_pelabuhan, '-'),
            IF(b.lokasi IS NOT NULL AND b.lokasi != '', CONCAT(', ', b.lokasi), '')
        ) AS tujuan
        FROM tickets t
        LEFT JOIN pelabuhan a ON a.id = t.asal_id
        LEFT JOIN pelabuhan b ON b.id = t.tujuan_id
        WHERE t.kode_booking = ?
        LIMIT 1");
    $stmt->bind_param('s', $kode);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function isTicketPaid(array $ticket): bool
{
    return normalizePaymentStatus($ticket['payment_status'] ?? '') === PAYMENT_STATUS_PAID;
}

function isPaymentPendingState(array $ticket): bool
{
    return in_array(
        normalizePaymentStatus($ticket['payment_status'] ?? ''),
        [PAYMENT_STATUS_PENDING, PAYMENT_STATUS_CHALLENGE],
        true
    );
}

function isPaymentFailedState(array $ticket): bool
{
    return in_array(
        normalizePaymentStatus($ticket['payment_status'] ?? ''),
        [PAYMENT_STATUS_DENY, PAYMENT_STATUS_CANCEL, PAYMENT_STATUS_EXPIRE, PAYMENT_STATUS_FAILED],
        true
    );
}

function canShowTicketContent(array $ticket): bool
{
    return isTicketPaid($ticket);
}

function sqlPaidTicketsCondition(?string $alias = null): string
{
    $col = ($alias !== null && $alias !== '')
        ? preg_replace('/[^a-z_]/i', '', $alias) . '.payment_status'
        : 'payment_status';

    return "({$col} = 'paid' OR {$col} = 'LUNAS' OR {$col} IS NULL OR {$col} = '')";
}

function sqlPendingPaymentCondition(?string $alias = null): string
{
    $col = ($alias !== null && $alias !== '')
        ? preg_replace('/[^a-z_]/i', '', $alias) . '.payment_status'
        : 'payment_status';

    return "({$col} IN ('pending','challenge','MENUNGGU PEMBAYARAN'))";
}

function getPaymentStatusMeta(string $status): array
{
    $status = normalizePaymentStatus($status);

    $meta = [
        PAYMENT_STATUS_PAID => [
            'label'           => 'Lunas',
            'badge_class'     => 'pay-paid',
            'color'           => '#22c55e',
            'view'            => 'ticket',
            'title'           => 'Pembayaran Berhasil',
            'message'         => 'E-tiket Anda aktif dan siap digunakan.',
            'show_pay_button' => false,
            'show_ticket'     => true,
        ],
        PAYMENT_STATUS_PENDING => [
            'label'           => 'Menunggu Pembayaran',
            'badge_class'     => 'pay-pending',
            'color'           => '#fbbf24',
            'view'            => 'pending',
            'title'           => 'Pembayaran Sedang Diproses',
            'message'         => 'Pembayaran Anda masih diproses. Jika belum menyelesaikan pembayaran, lanjutkan dari tombol di bawah.',
            'show_pay_button' => true,
            'show_ticket'     => false,
        ],
        PAYMENT_STATUS_CHALLENGE => [
            'label'           => 'Challenge',
            'badge_class'     => 'pay-challenge',
            'color'           => '#fb923c',
            'view'            => 'pending',
            'title'           => 'Verifikasi Pembayaran Diperlukan',
            'message'         => 'Pembayaran memerlukan verifikasi tambahan dari pihak bank/penyedia kartu. Status akan diperbarui otomatis setelah konfirmasi.',
            'show_pay_button' => true,
            'show_ticket'     => false,
        ],
        PAYMENT_STATUS_DENY => [
            'label'           => 'Ditolak',
            'badge_class'     => 'pay-deny',
            'color'           => '#ef4444',
            'view'            => 'failed',
            'title'           => 'Pembayaran Ditolak',
            'message'         => 'Transaksi ditolak. Silakan gunakan metode pembayaran lain atau hubungi bank Anda.',
            'show_pay_button' => true,
            'show_ticket'     => false,
        ],
        PAYMENT_STATUS_CANCEL => [
            'label'           => 'Dibatalkan',
            'badge_class'     => 'pay-cancel',
            'color'           => '#f87171',
            'view'            => 'failed',
            'title'           => 'Pembayaran Dibatalkan',
            'message'         => 'Transaksi dibatalkan. Anda dapat mencoba melakukan pembayaran kembali.',
            'show_pay_button' => true,
            'show_ticket'     => false,
        ],
        PAYMENT_STATUS_EXPIRE => [
            'label'           => 'Kedaluwarsa',
            'badge_class'     => 'pay-expire',
            'color'           => '#94a3b8',
            'view'            => 'failed',
            'title'           => 'Pembayaran Kedaluwarsa',
            'message'         => 'Batas waktu pembayaran telah habis. Silakan pesan tiket baru atau coba bayar ulang jika masih dalam periode booking.',
            'show_pay_button' => true,
            'show_ticket'     => false,
        ],
        PAYMENT_STATUS_FAILED => [
            'label'           => 'Gagal',
            'badge_class'     => 'pay-failed',
            'color'           => '#dc2626',
            'view'            => 'failed',
            'title'           => 'Pembayaran Gagal',
            'message'         => 'Pembayaran tidak berhasil. Silakan coba lagi dengan metode pembayaran yang berbeda.',
            'show_pay_button' => true,
            'show_ticket'     => false,
        ],
    ];

    return $meta[$status] ?? $meta[PAYMENT_STATUS_PENDING];
}

function paymentStatusBadgeHtml(string $status): string
{
    $meta = getPaymentStatusMeta($status);
    $label = htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8');
    $color = htmlspecialchars($meta['color'], ENT_QUOTES, 'UTF-8');

    return '<span class="pay-badge ' . $meta['badge_class'] . '" style="background:' . $color . '22;color:' . $color . ';border:1px solid ' . $color . '44;">' . $label . '</span>';
}
