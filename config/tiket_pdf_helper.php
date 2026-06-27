<?php
/**
 * Generate & simpan PDF tiket ke uploads/tiket/
 */

require_once __DIR__ . '/app.php';
require_once __DIR__ . '/tiket_helper.php';
require_once __DIR__ . '/payment_helper.php';

function ensureTiketPdfColumns(mysqli $conn): void
{
    static $done = false;
    if ($done) {
        return;
    }

    ensurePaymentColumns($conn);

    $cols = [];
    $res  = $conn->query('SHOW COLUMNS FROM tickets');
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $cols[$row['Field']] = $row;
        }
    }

    if (!isset($cols['file_tiket'])) {
        $conn->query(
            'ALTER TABLE tickets ADD COLUMN file_tiket VARCHAR(255) DEFAULT NULL AFTER paid_at'
        );
    }
    if (!isset($cols['wa_sent_at'])) {
        $conn->query(
            'ALTER TABLE tickets ADD COLUMN wa_sent_at DATETIME DEFAULT NULL AFTER file_tiket'
        );
    }

    $done = true;
}

function getTiketPdfUploadDir(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'tiket';
}

function getTiketPdfFilename(string $kodeBooking): string
{
    $safe = preg_replace('/[^A-Za-z0-9._-]/', '', $kodeBooking);
    return ($safe !== '' ? $safe : 'tiket') . '.pdf';
}

function getStoredTiketPdfFilename(mysqli $conn, string $kodeBooking): ?string
{
    ensureTiketPdfColumns($conn);

    $stmt = $conn->prepare('SELECT file_tiket FROM tickets WHERE kode_booking = ? LIMIT 1');
    $stmt->bind_param('s', $kodeBooking);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }

    $filename = trim((string)($row['file_tiket'] ?? ''));
    if ($filename !== '') {
        return $filename;
    }

    return getTiketPdfFilename($kodeBooking);
}

function getTiketPdfPublicUrl(string $filename): string
{
    return rtrim(appBaseUrl(), '/') . '/uploads/tiket/' . rawurlencode($filename);
}

function loadPenumpangRows(mysqli $conn, int $ticketId): array
{
    $stmt = $conn->prepare('SELECT * FROM penumpang_detail WHERE ticket_id = ? ORDER BY id ASC');
    $stmt->bind_param('i', $ticketId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows ?: [];
}

function getNaviraLogoHtml(): string
{
    return naviraLogoHtmlPdf();
}

function buildTiketPdfHtml(array $data, array $penumpangRows): string
{
    $statusDb = !empty($data['status']) ? $data['status'] : 'Belum Scan';

    if ($statusDb === 'DIGUNAKAN') {
        $statusText  = 'SUDAH DIGUNAKAN';
        $statusColor = '#ef4444';
        $statusBg    = '#fef2f2';
    } else {
        $statusText  = 'BELUM DIGUNAKAN';
        $statusColor = '#16a34a';
        $statusBg    = '#f0fdf4';
    }

    $qrBase64 = '';
    if (extension_loaded('gd')) {
        $qr = @file_get_contents(
            'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode((string)$data['kode_booking'])
        );
        if ($qr !== false) {
            $qrBase64 = base64_encode($qr);
        }
    }

    $tanggalFmt = date('l, d F Y', strtotime((string)$data['tanggal']));
    $jamFmt     = substr((string)$data['jam'], 0, 5);

    $isKendaraan    = isTiketKendaraan($data);
    $jenisTampil    = $isKendaraan ? 'Berkendara' : 'Pejalan Kaki';
    $golonganTampil = htmlspecialchars(getGolonganTiket($data));
    $platTampil     = htmlspecialchars(getPlatTiket($data));
    $logoHtml    = getNaviraLogoHtml(); // base64, di samping tulisan NAVIRA

    $kendaraanHtml = '';
    if ($isKendaraan) {
        $kendaraanHtml = '
      <div class="section-head">Informasi Kendaraan</div>
      <table class="info-grid">
        <tr>
          <td class="info-label">Golongan</td>
          <td class="info-value">' . $golonganTampil . '</td>
        </tr>
        <tr>
          <td class="info-label">Plat Nomor</td>
          <td class="info-value">' . $platTampil . '</td>
        </tr>
      </table>';
    }

    $html = '
<style>
@page { margin: 14px 16px; }
body {
  font-family: DejaVu Sans, sans-serif;
  font-size: 10px;
  color: #1e293b;
  margin: 0;
}
.ticket {
  border: 2px solid #1E2E4F;
  border-radius: 16px;
  overflow: hidden;
}
.header {
  background: #1E2E4F;
  color: #ffffff;
  padding: 14px 16px;
}
.header-table { width: 100%; border-collapse: collapse; }
.header-table td { vertical-align: middle; padding: 0; }
.brand-logo { height: 48px; width: auto; display: block; }
.brand-row-inner { width: 100%; border-collapse: collapse; }
.brand-row-inner td { vertical-align: middle; padding: 0; }
.brand-logo-cell { width: 56px; padding-right: 10px !important; }
.brand-text { padding-left: 0; }
.brand-name {
  font-size: 18px;
  font-weight: 700;
  letter-spacing: 2px;
  color: #ffffff;
}
.brand-tagline {
  font-size: 8px;
  color: #94a3b8;
  margin-top: 2px;
  letter-spacing: 0.5px;
}
.header-meta {
  text-align: right;
  font-size: 8px;
  color: #cbd5e1;
}
.header-meta strong {
  display: block;
  font-size: 9px;
  color: #38bdf8;
  margin-bottom: 2px;
}
.body-pad { padding: 14px 16px 12px; }
.booking-bar {
  background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 100%);
  border: 1px solid #bae6fd;
  border-radius: 12px;
  padding: 10px 12px;
  margin-bottom: 10px;
}
.booking-label {
  font-size: 7px;
  font-weight: 700;
  letter-spacing: 1px;
  color: #0369a1;
  text-transform: uppercase;
}
.booking-code {
  font-size: 18px;
  font-weight: 700;
  color: #1E2E4F;
  letter-spacing: 1px;
  margin-top: 2px;
}
.route-card {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 10px 12px;
  margin-bottom: 10px;
  background: #f8fafc;
}
.route-top { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
.route-top td { vertical-align: top; padding: 0; }
.schedule-title {
  font-size: 8px;
  font-weight: 700;
  text-transform: uppercase;
  color: #475569;
}
.schedule-time {
  font-size: 11px;
  font-weight: 700;
  color: #0f172a;
  margin-top: 2px;
}
.service-pill {
  text-align: right;
}
.service-name {
  font-size: 12px;
  font-weight: 700;
  color: #f97316;
}
.service-type {
  font-size: 8px;
  color: #64748b;
  margin-top: 1px;
}
.route-mid { width: 100%; border-collapse: collapse; }
.route-mid td { vertical-align: middle; padding: 4px 0; }
.port-name { font-size: 13px; font-weight: 700; color: #1E2E4F; }
.port-loc { font-size: 8px; color: #64748b; margin-top: 1px; }
.route-arrow {
  text-align: center;
  font-size: 16px;
  color: #38bdf8;
  font-weight: 700;
  width: 40px;
}
.route-note {
  margin-top: 6px;
  font-size: 7px;
  color: #94a3b8;
  font-style: italic;
}
.alert-box {
  background: #fffbeb;
  border: 1px solid #fde68a;
  border-left: 4px solid #f59e0b;
  border-radius: 8px;
  padding: 8px 10px;
  margin-bottom: 10px;
  font-size: 7.5px;
  line-height: 1.5;
  color: #78350f;
}
.alert-box div { margin-top: 3px; }
.alert-box div:first-child { margin-top: 0; }
.content-table { width: 100%; border-collapse: collapse; }
.content-table > tbody > tr > td { vertical-align: top; padding: 0; }
.content-main { padding-right: 12px; }
.content-side {
  width: 130px;
  border-left: 2px dashed #cbd5e1;
  padding-left: 12px;
}
.section-head {
  font-size: 9px;
  font-weight: 700;
  color: #1E2E4F;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin: 8px 0 4px;
  padding-bottom: 3px;
  border-bottom: 2px solid #38bdf8;
  display: inline-block;
}
.table-pen {
  width: 100%;
  border-collapse: collapse;
  margin-top: 2px;
}
.table-pen th,
.table-pen td {
  border: 1px solid #e2e8f0;
  padding: 5px 6px;
  font-size: 8px;
}
.table-pen th {
  background: #1E2E4F;
  color: #ffffff;
  font-weight: 700;
  text-align: left;
}
.table-pen tr:nth-child(even) td { background: #f8fafc; }
.table-pen td.num { text-align: center; width: 22px; }
.info-grid {
  width: 100%;
  border-collapse: collapse;
  margin-top: 2px;
  font-size: 8px;
}
.info-grid td { padding: 4px 6px; border-bottom: 1px solid #f1f5f9; }
.info-label { width: 72px; color: #64748b; font-weight: 600; }
.info-value { color: #0f172a; }
.qr-label {
  text-align: center;
  font-size: 8px;
  font-weight: 700;
  color: #1E2E4F;
  margin-bottom: 6px;
}
.qr-frame {
  text-align: center;
  background: #ffffff;
  border: 2px solid #1E2E4F;
  border-radius: 10px;
  padding: 6px;
}
.status-wrap {
  margin-top: 10px;
  text-align: center;
}
.status-label {
  font-size: 7px;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.status-badge {
  display: inline-block;
  margin-top: 4px;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 7px;
  font-weight: 700;
  color: ' . $statusColor . ';
  background: ' . $statusBg . ';
  border: 1px solid ' . $statusColor . ';
}
.footer {
  background: #f1f5f9;
  border-top: 1px solid #e2e8f0;
  padding: 8px 16px;
  font-size: 7px;
  color: #64748b;
  line-height: 1.4;
  text-align: center;
}
</style>

<div class="ticket">
  <div class="header">
    <table class="header-table">
      <tr>
        <td>
          <table class="brand-row-inner">
            <tr>
              <td class="brand-logo-cell">' . $logoHtml . '</td>
              <td class="brand-text">
                <div class="brand-name">NAVIRA</div>
                <div class="brand-tagline">E-Ticket Kapal Penyeberangan</div>
              </td>
            </tr>
          </table>
        </td>
        <td class="header-meta">
          <strong>E-TICKET RESMI</strong>
          Dicetak: ' . date('d/m/Y H:i') . ' WIB
        </td>
      </tr>
    </table>
  </div>

  <div class="body-pad">
    <div class="booking-bar">
      <div class="booking-label">Kode Booking</div>
      <div class="booking-code">' . htmlspecialchars((string)$data['kode_booking']) . '</div>
    </div>

    <div class="route-card">
      <table class="route-top">
        <tr>
          <td>
            <div class="schedule-title">Jadwal Masuk Pelabuhan (Check In)</div>
            <div class="schedule-time">' . $tanggalFmt . ' &bull; ' . $jamFmt . ' WIB</div>
          </td>
          <td class="service-pill">
            <div class="service-name">' . ucfirst(strtolower((string)$data['layanan'])) . '</div>
            <div class="service-type">' . $jenisTampil . '</div>
          </td>
        </tr>
      </table>
      <table class="route-mid">
        <tr>
          <td style="width:42%;">
            <div class="port-name">' . htmlspecialchars(explode(',', (string)$data['asal'])[0]) . '</div>
            <div class="port-loc">' . htmlspecialchars((string)$data['asal']) . '</div>
          </td>
          <td class="route-arrow">&rarr;</td>
          <td style="width:42%; text-align:right;">
            <div class="port-name">' . htmlspecialchars(explode(',', (string)$data['tujuan'])[0]) . '</div>
            <div class="port-loc">' . htmlspecialchars((string)$data['tujuan']) . '</div>
          </td>
        </tr>
      </table>
      <div class="route-note">* Nama kapal akan diinformasikan saat tiba di pelabuhan</div>
    </div>

    <div class="alert-box">
      <div><strong>WAJIB</strong> menunjukkan bukti E-Ticket dan Kartu Identitas asli penumpang saat proses Masuk Pelabuhan (Check In).</div>
      <div><strong>Masuk Pelabuhan (Check In)</strong> dilakukan sesuai jadwal yang tertera di atas.</div>
      <div>Tiket akan <strong>hangus (expired)</strong> apabila tidak melakukan Check In hingga melewati batas waktu jadwal.</div>
    </div>

    <table class="content-table">
      <tr>
        <td class="content-main">
          <div class="section-head">Rincian Data Penumpang</div>
          <table class="table-pen">
            <tr>
              <th class="num">No</th>
              <th>Nama Penumpang</th>
              <th style="width:105px;">Nomor ID</th>
              <th style="width:58px;">Jenis</th>
            </tr>';

    $no = 1;
    foreach ($penumpangRows as $p) {
        $nama = $p['nama_lengkap'] ?: ucfirst((string)$p['kategori']) . ' ' . $no;
        $html .= '<tr>
              <td class="num">' . $no . '</td>
              <td>' . htmlspecialchars((string)$nama) . '</td>
              <td>' . htmlspecialchars((string)($p['nomor_id'] ?: '-')) . '</td>
              <td>' . ucfirst((string)$p['kategori']) . '</td>
            </tr>';
        $no++;
    }

    $html .= '
          </table>
          ' . $kendaraanHtml . '

          <div class="section-head">Informasi Pemesan</div>
          <table class="info-grid">
            <tr>
              <td class="info-label">Nama</td>
              <td class="info-value">' . htmlspecialchars((string)($data['nama_pemesan'] ?? '-')) . '</td>
            </tr>
            <tr>
              <td class="info-label">Email</td>
              <td class="info-value">' . htmlspecialchars((string)($data['email_pemesan'] ?? '-')) . '</td>
            </tr>
            <tr>
              <td class="info-label">Nomor Telp</td>
              <td class="info-value">' . htmlspecialchars((string)($data['hp_pemesan'] ?? '-')) . '</td>
            </tr>
          </table>
        </td>
        <td class="content-side">
          <div class="qr-label">Scan QR Saat Check-in</div>
          <div class="qr-frame">';

    if ($qrBase64 !== '') {
        $html .= '<img src="data:image/png;base64,' . $qrBase64 . '" width="108" height="108" />';
    } else {
        $html .= '<div style="font-size:8px;color:#6b7280;padding:12px 4px;">QR tidak tersedia</div>';
    }

    $html .= '
          </div>
          <div class="status-wrap">
            <div class="status-label">Status Tiket</div>
            <span class="status-badge">' . $statusText . '</span>
          </div>
        </td>
      </tr>
    </table>
  </div>

  <div class="footer">
    Simpan E-Ticket ini dan tunjukkan kepada petugas saat proses boarding.
    Informasi Syarat &amp; Ketentuan dapat dilihat pada laman resmi Navira.
  </div>
</div>
';

    return $html;
}

function renderTiketPdfBinary(string $html): ?string
{
    if (!class_exists(\Dompdf\Dompdf::class)) {
        require_once dirname(__DIR__) . '/dompdf/vendor/autoload.php';
    }

    $dompdf = new \Dompdf\Dompdf();
    $options = $dompdf->getOptions();
    $options->setChroot(dirname(__DIR__));
    $options->setIsRemoteEnabled(true);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    return $dompdf->output();
}

function saveTiketPdfToDisk(mysqli $conn, string $kodeBooking): ?string
{
    ensureTiketPdfColumns($conn);

    $ticket = loadTicketByKode($conn, $kodeBooking);
    if (!$ticket || !isTicketPaid($ticket)) {
        return null;
    }

    $filename = getStoredTiketPdfFilename($conn, $kodeBooking) ?? getTiketPdfFilename($kodeBooking);
    $dir      = getTiketPdfUploadDir();
    $path     = $dir . DIRECTORY_SEPARATOR . $filename;

    if (is_file($path) && filesize($path) > 0) {
        updateTicketPdfFilename($conn, $kodeBooking, $filename);
        return $filename;
    }

    $penumpangRows = loadPenumpangRows($conn, (int)$ticket['id_ticket']);
    $html          = buildTiketPdfHtml($ticket, $penumpangRows);
    $binary        = renderTiketPdfBinary($html);

    if ($binary === null || $binary === '') {
        return null;
    }

    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return null;
    }

    if (file_put_contents($path, $binary) === false) {
        return null;
    }

    updateTicketPdfFilename($conn, $kodeBooking, $filename);

    return $filename;
}

function updateTicketPdfFilename(mysqli $conn, string $kodeBooking, string $filename): void
{
    ensureTiketPdfColumns($conn);

    $stmt = $conn->prepare('UPDATE tickets SET file_tiket = ? WHERE kode_booking = ? LIMIT 1');
    $stmt->bind_param('ss', $filename, $kodeBooking);
    $stmt->execute();
    $stmt->close();
}

function streamTiketPdfForKode(mysqli $conn, string $kodeBooking): bool
{
    $ticket = loadTicketByKode($conn, $kodeBooking);
    if (!$ticket) {
        return false;
    }

    if (!isTicketPaid($ticket)) {
        return false;
    }

    $penumpangRows = loadPenumpangRows($conn, (int)$ticket['id_ticket']);
    $html          = buildTiketPdfHtml($ticket, $penumpangRows);
    $binary        = renderTiketPdfBinary($html);

    if ($binary === null || $binary === '') {
        return false;
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="tiket_' . $kodeBooking . '.pdf"');
    header('Content-Length: ' . strlen($binary));
    echo $binary;

    return true;
}
