<?php
include('auth.php');
include('../config/koneksi.php');
include('../config/app.php');
include('../config/tiket_helper.php');
include(__DIR__ . '/../phpqrcode/qrlib.php');

// QR pakai teks pendek (bukan URL) supaya tetap bisa discan setelah di-download/cetak
function buildQrPayload(string $kode, string $lane, int $paxNo): string {
    return $kode . '|' . $lane . '|' . $paxNo;
}

function qrToDataUri(string $text): string {
    ob_start();
    QRcode::png($text, null, QR_ECLEVEL_L, 4, 2);
    $img = ob_get_clean();
    return 'data:image/png;base64,' . base64_encode($img);
}

$kode = trim((string)($_GET['kode'] ?? ''));
$tipeParam = strtoupper(trim((string)($_GET['tipe'] ?? 'AB')));

if ($kode === '') {
    die('Kode tiket tidak ditemukan.');
}

$stmt = mysqli_prepare($conn, "
    SELECT t.*, u.nama AS nama_user,
        CONCAT(
            COALESCE(a.nama_pelabuhan, '-'),
            IF(a.lokasi IS NOT NULL AND a.lokasi != '', CONCAT(', ', a.lokasi), '')
        ) AS asal,
        CONCAT(
            COALESCE(b.nama_pelabuhan, '-'),
            IF(b.lokasi IS NOT NULL AND b.lokasi != '', CONCAT(', ', b.lokasi), '')
        ) AS tujuan
    FROM tickets t
    LEFT JOIN users u ON u.id = t.user_id
    LEFT JOIN pelabuhan a ON a.id = t.asal_id
    LEFT JOIN pelabuhan b ON b.id = t.tujuan_id
    WHERE t.kode_booking = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, 's', $kode);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$t = mysqli_fetch_assoc($res);

if (!$t) {
    die('Tiket tidak ditemukan.');
}

/**
 * Bangun daftar penumpang (1 entri = 1 struk A+B)
 */
function buildPassengerList(mysqli $conn, array $t): array {
    $ticketId = (int)$t['id_ticket'];
    $list = [];

    $q = mysqli_query($conn, "
        SELECT kategori, jumlah, titel, nama_lengkap, nomor_id
        FROM penumpang_detail
        WHERE ticket_id = {$ticketId}
        ORDER BY FIELD(kategori,'dewasa','anak','bayi'), jumlah ASC, id ASC
    ");

    while ($row = mysqli_fetch_assoc($q)) {
        $kat = strtolower((string)($row['kategori'] ?? 'penumpang'));
        $nama = trim((string)($row['nama_lengkap'] ?? ''));
        $hasDetail = ($nama !== '' || (!empty($row['nomor_id']) && $row['nomor_id'] !== '-'));

        if ($hasDetail) {
            $list[] = [
                'nama' => $nama !== '' ? $nama : ucfirst($kat),
                'nomor_id' => trim((string)($row['nomor_id'] ?? '-')) ?: '-',
                'kategori' => ucfirst($kat),
            ];
        } else {
            $jml = max(0, (int)($row['jumlah'] ?? 0));
            for ($i = 1; $i <= $jml; $i++) {
                $list[] = [
                    'nama' => ucfirst($kat) . ' ' . $i,
                    'nomor_id' => '-',
                    'kategori' => ucfirst($kat),
                ];
            }
        }
    }

    if (count($list) === 0) {
        $list[] = [
            'nama' => $t['nama_user'] ?? 'Penumpang',
            'nomor_id' => '-',
            'kategori' => 'Penumpang',
        ];
    }

    return $list;
}

$passengers = buildPassengerList($conn, $t);
$totalPax = count($passengers);

// Tarif per penumpang (bayi gratis)
$paxBayar = 0;
foreach ($passengers as $p) {
    if (strtolower($p['kategori']) !== 'bayi') {
        $paxBayar++;
    }
}
$tarifPerOrang = $paxBayar > 0
    ? (int)floor((int)$t['total_harga'] / $paxBayar)
    : (int)$t['total_harga'];

$tanggal = date('d M Y', strtotime((string)$t['tanggal']));
$jam = substr((string)$t['jam'], 0, 5);
$asalShort = htmlspecialchars(explode(',', (string)$t['asal'])[0]);
$tujuanShort = htmlspecialchars(explode(',', (string)$t['tujuan'])[0]);
$isKendaraan   = isTiketKendaraan($t);
$jenis         = $isKendaraan ? 'KENDARAAN' : 'PENUMPANG';
$golonganTampil = htmlspecialchars(getGolonganTiket($t));
$platTampil    = htmlspecialchars(getPlatTiket($t));
$kendaraanRows = '';
if ($isKendaraan) {
    $kendaraanRows = <<<HTML
      <tr><td class="muted">GOLONGAN</td><td>: {$golonganTampil}</td></tr>
      <tr><td class="muted">PLAT NOMOR</td><td>: {$platTampil}</td></tr>
HTML;
}
$kodeEsc = htmlspecialchars((string)$t['kode_booking']);
$ticketId = (int)$t['id_ticket'];

// Status scan tiket A (verifikasi) dan B (checkout) per penumpang
$scanStatus = ['A' => [], 'B' => []];
$scanQ = mysqli_query($conn, "
    SELECT pax_no, lane FROM boarding_scans
    WHERE ticket_id = {$ticketId}
");
if ($scanQ) {
    while ($sr = mysqli_fetch_assoc($scanQ)) {
        $scanStatus[$sr['lane']][(int)$sr['pax_no']] = true;
    }
}

if ($tipeParam === 'A' || $tipeParam === 'B') {
    $lanes = [$tipeParam];
} else {
    $lanes = ['A', 'B'];
}

function renderTicket(string $lane, array $ctx): string {
    extract($ctx);
    $laneEsc = htmlspecialchars($lane);
    return <<<HTML
  <div class="ticket">
    <div class="top">
      <div class="brand">
        {$logoImg}
        <div class="brand-text">
          NAVIRA BOARDING PASS
          <small>Untuk Petugas</small>
        </div>
      </div>
      <div class="lane">{$laneEsc}</div>
    </div>

    <div class="title">{$jenis}</div>
    <div class="sub">{$tanggal} • {$jam} WIB</div>
    <div class="pax-no">Penumpang {$paxNo} / {$totalPax}</div>
    <div class="route">{$asalShort} - {$tujuanShort}</div>
    {$kendaraanBlock}

    <div class="center">
      <div style="font-size:9px;font-weight:700;margin-bottom:3px;">SCAN TIKET {$laneEsc}</div>
      <img class="qr" src="{$qrUrl}" alt="QR">
      <div class="kode-below">{$kodeEsc}</div>
      <div style="font-size:7px;color:#6b7280;margin-top:2px;">{$qrLabel}</div>
    </div>

    <div class="line"></div>
    <table class="tbl">
      <tr><td class="muted">NO. TIKET</td><td>: {$idTicket}</td></tr>
      <tr><td class="muted">NAMA</td><td>: {$namaTampil}</td></tr>
      <tr><td class="muted">NO ID</td><td>: {$idTampil}</td></tr>
      <tr><td class="muted">TIPE</td><td>: {$katTampil}</td></tr>
      {$kendaraanRows}
      <tr><td class="muted">TARIF</td><td>: Rp {$tarif}</td></tr>
      <tr><td class="muted">STATUS</td><td>: {$status}</td></tr>
    </table>
    <div class="line"></div>
    <div class="footer">Simpan struk ini untuk proses boarding.</div>
  </div>
HTML;
}

$kendaraanBlock = '';
if ($isKendaraan) {
    $kendaraanBlock = '<div class="kendaraan-info" style="text-align:center;font-size:9px;margin:4px 0;line-height:1.4;">'
        . '<div><b>' . $golonganTampil . '</b></div>'
        . '<div>Plat: <b>' . $platTampil . '</b></div>'
        . '</div>';
}

$logoImg = naviraLogoImg('../', 28, 'brand-logo-img');

$baseCtx = [
    'logoImg' => $logoImg,
    'jenis' => htmlspecialchars($jenis),
    'tanggal' => htmlspecialchars($tanggal),
    'jam' => htmlspecialchars($jam),
    'asalShort' => $asalShort,
    'tujuanShort' => $tujuanShort,
    'kodeEsc' => $kodeEsc,
    'idTicket' => htmlspecialchars((string)$t['id_ticket']),
    'totalPax' => (string)$totalPax,
    'kendaraanRows' => $kendaraanRows,
    'kendaraanBlock' => $kendaraanBlock,
];

// Urutan cetak: Penumpang1-A, Penumpang1-B, Penumpang2-A, Penumpang2-B, ...
$printBlocks = [];
$paxIndex = 0;
foreach ($passengers as $pax) {
    $paxIndex++;
    $isBayi = (strtolower($pax['kategori']) === 'bayi');
    $tarifStr = $isBayi ? '0 (Gratis)' : number_format($tarifPerOrang, 0, ',', '.');

    foreach ($lanes as $lane) {
        $qrText    = buildQrPayload((string)$t['kode_booking'], $lane, $paxIndex);
        $qrUrlLane = qrToDataUri($qrText);

        $sudahScan = !empty($scanStatus[$lane][$paxIndex]);
        if ($lane === 'A') {
            $statusLane = $sudahScan ? 'Verifikasi Berhasil' : 'Belum Verifikasi';
        } else {
            $statusLane = $sudahScan ? 'Checkout Berhasil' : 'Belum Checkout';
        }

        $printBlocks[] = renderTicket($lane, array_merge($baseCtx, [
            'paxNo' => (string)$paxIndex,
            'namaTampil' => htmlspecialchars($pax['nama']),
            'idTampil' => htmlspecialchars($pax['nomor_id']),
            'katTampil' => htmlspecialchars($pax['kategori']),
            'tarif' => $tarifStr,
            'qrUrl' => $qrUrlLane,
            'qrLabel' => htmlspecialchars($qrText),
            'status' => htmlspecialchars($statusLane),
        ]));
    }
}

$totalStruk = count($printBlocks);

// Download PDF struk A+B
if (isset($_GET['download']) && $_GET['download'] === '1') {
    require __DIR__ . '/../dompdf/vendor/autoload.php';
    $pdfHtml = '<html><head><style>
      body{font-family:DejaVu Sans,sans-serif;font-size:10px;color:#111}
      .ticket{width:280px;margin:0 auto 16px;padding:8px;border:1px dashed #ccc;page-break-after:always}
      .ticket:last-child{page-break-after:auto}
      .top{display:flex;justify-content:space-between}
      .brand{display:flex;align-items:center;gap:4px}
      .brand-logo-img{height:24px}
      .lane{font-size:36px;font-weight:900}
      .title{font-size:14px;font-weight:900;text-align:center}
      .sub,.pax-no{text-align:center;font-size:9px}
      .route{text-align:center;font-size:16px;font-weight:900}
      .center{text-align:center}
      .qr{width:130px;height:130px}
      .line{border-top:1px dashed #999;margin:6px 0}
      .tbl{width:100%;font-size:9px}
      .muted{color:#666}
      .footer{font-size:8px;text-align:center}
    </style></head><body>';
    foreach ($printBlocks as $html) {
        $pdfHtml .= $html;
    }
    $pdfHtml .= '</body></html>';

    $dompdf = new Dompdf\Dompdf();
    $dompdf->loadHtml($pdfHtml);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream('struk_AB_' . $kode . '.pdf', ['Attachment' => true]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cetak Tiket - <?= $kodeEsc ?> (<?= $totalPax ?> penumpang)</title>
  <style>
    body{margin:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif}
    .actions{max-width:320px;margin:10px auto;display:flex;gap:8px}
    .actions a,.actions button{flex:1;border:none;border-radius:8px;padding:8px 10px;font-weight:700;cursor:pointer;text-align:center;text-decoration:none}
    .btn-back{background:#334155;color:#fff}
    .btn-print{background:#0ea5e9;color:#fff}
    .stack{width:80mm;margin:0 auto}
    .ticket{
      width:80mm;
      margin:0 auto 12px;
      background:#fff;
      color:#111827;
      padding:8px;
      box-sizing:border-box;
      border:1px dashed #d1d5db;
    }
    .ticket.page-break-after{
      page-break-after:always;
      break-after:page;
    }
    .top{display:flex;justify-content:space-between;align-items:flex-start}
    .brand{display:flex;align-items:center;gap:6px;font-size:11px;font-weight:800;line-height:1.2}
    .brand-logo-img{height:28px!important;width:auto!important}
    .brand-text small{display:block;font-size:9px;color:#6b7280;font-weight:500}
    .lane{font-size:42px;font-weight:900;line-height:1}
    .title{font-size:16px;font-weight:900;letter-spacing:.5px;text-align:center;margin:4px 0 2px}
    .sub{text-align:center;font-size:10px;margin-bottom:4px}
    .pax-no{text-align:center;font-size:10px;font-weight:700;color:#0ea5e9;margin-bottom:4px}
    .route{text-align:center;font-size:18px;font-weight:900;letter-spacing:1px;margin:6px 0}
    .center{text-align:center}
    .qr{width:140px;height:140px;object-fit:cover}
    .kode-below{font-size:14px;font-weight:800;letter-spacing:1px;margin-top:4px}
    .line{border-top:1px dashed #9ca3af;margin:6px 0}
    .tbl{width:100%;border-collapse:collapse;font-size:10px}
    .tbl td{padding:2px 0;vertical-align:top}
    .muted{color:#6b7280}
    .footer{font-size:9px;text-align:center;margin-top:6px}
    .hint{max-width:320px;margin:8px auto;text-align:center;font-size:11px;color:#64748b}

    @media print{
      @page{size:80mm auto;margin:2mm}
      body{background:#fff}
      .actions,.hint{display:none}
      .stack{width:80mm;margin:0}
      .ticket{margin:0;border:none}
    }
  </style>
</head>
<body>
  <div class="actions">
    <a class="btn-back" href="validasi.php?kode=<?= urlencode($kode) ?>">← Kembali</a>
    <button class="btn-print" onclick="window.print()">🖨️ Cetak</button>
    <a class="btn-print" href="?kode=<?= urlencode($kode) ?>&download=1">⬇️ Download PDF</a>
  </div>

  <p class="hint">
    <?= $totalPax ?> penumpang × 2 struk (A+B) = <b><?= $totalStruk ?> lembar</b><br>
    <small>Download/cetak dulu → scan nanti di menu <b>Scan Tiket</b> (Admin)</small>
  </p>

  <div class="stack">
    <?php
    foreach ($printBlocks as $i => $html) {
        if ($i < $totalStruk - 1) {
            $html = str_replace('class="ticket"', 'class="ticket page-break-after"', $html);
        }
        echo $html;
    }
    ?>
  </div>

  <script>
    if (window.location.search.includes('auto=1')) {
      window.addEventListener('load', () => setTimeout(() => window.print(), 400));
    }
  </script>
</body>
</html>
