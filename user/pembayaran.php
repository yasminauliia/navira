<?php
session_start();
include('../config/koneksi.php');
include('../config/payment_helper.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
ensurePaymentColumns($conn);

// Lanjut bayar ulang via kode booking
if (!empty($_GET['kode'])) {
    $kodeRetry = trim((string)$_GET['kode']);
    $stmtRetry = $conn->prepare("SELECT id_ticket, kode_booking FROM tickets WHERE kode_booking = ? AND user_id = ? LIMIT 1");
    $stmtRetry->bind_param('si', $kodeRetry, $user_id);
    $stmtRetry->execute();
    $retryRow = $stmtRetry->get_result()->fetch_assoc();
    $stmtRetry->close();
    if ($retryRow) {
        $_SESSION['order']['ticket_id'] = (int)$retryRow['id_ticket'];
        $_SESSION['order']['order_id']  = (string)$retryRow['kode_booking'];
    }
}

$ticket_id = (int)($_SESSION['order']['ticket_id'] ?? 0);
if ($ticket_id <= 0) {
    header("Location: beli_tiket.php");
    exit;
}

// ── PROSES KONFIRMASI PEMBAYARAN ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['konfirmasi'])) {
    $kode_booking = $_POST['kode_booking'] ?? '';

    // Update status tiket menjadi BELUM DIGUNAKAN (sudah bayar)
    $stmtUpdate = $conn->prepare("
        UPDATE tickets
        SET payment_status = 'paid',
            status = 'BELUM DIGUNAKAN'
        WHERE id_ticket = ? AND user_id = ?
        LIMIT 1
    ");
    $stmtUpdate->bind_param('ii', $ticket_id, $user_id);
    $stmtUpdate->execute();
    $stmtUpdate->close();

    // Bersihkan session order
    unset($_SESSION['order']);

    header("Location: hasil_tiket.php?kode=" . urlencode($kode_booking) . "&confirmed=1");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['agree']) && empty($_GET['kode']) && empty($_POST['konfirmasi'])) {
    $_SESSION['error'] = 'Centang persetujuan terlebih dahulu.';
    header("Location: verifikasi_data.php");
    exit;
}

$now = time();
if (empty($_SESSION['order']['pay_expires_at'])) {
    $_SESSION['order']['pay_expires_at'] = $now + 30 * 60;
}
$pay_expires_at = (int)$_SESSION['order']['pay_expires_at'];

// Ambil tiket
$stmt = $conn->prepare("
    SELECT t.id_ticket, t.kode_booking, t.payment_status,
        CONCAT(
            COALESCE(a.nama_pelabuhan, '-'),
            IF(a.lokasi IS NOT NULL AND a.lokasi != '', CONCAT(', ', a.lokasi), '')
        ) AS asal,
        CONCAT(
            COALESCE(b.nama_pelabuhan, '-'),
            IF(b.lokasi IS NOT NULL AND b.lokasi != '', CONCAT(', ', b.lokasi), '')
        ) AS tujuan,
        t.tanggal, t.jam, t.layanan, t.jenis_pengguna,
        t.kendaraan, t.golongan, t.plat,
        t.total_harga, t.total_penumpang
    FROM tickets t
    LEFT JOIN pelabuhan a ON a.id = t.asal_id
    LEFT JOIN pelabuhan b ON b.id = t.tujuan_id
    WHERE t.id_ticket = ? AND t.user_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $ticket_id, $user_id);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ticket) {
    header("Location: beli_tiket.php");
    exit;
}

if (isTicketPaid($ticket)) {
    header("Location: hasil_tiket.php?kode=" . urlencode($ticket['kode_booking']));
    exit;
}

// Hitung penumpang
$pax = [];
$stmt2 = $conn->prepare("SELECT kategori, jumlah FROM penumpang_detail WHERE ticket_id = ?");
$stmt2->bind_param("i", $ticket_id);
$stmt2->execute();
$res2 = $stmt2->get_result();
while ($row = $res2->fetch_assoc()) $pax[] = $row;
$stmt2->close();

$count = ['dewasa'=>0,'anak'=>0,'bayi'=>0];
foreach ($pax as $row) {
    $kat = strtolower((string)($row['kategori'] ?? ''));
    if (!isset($count[$kat])) continue;
    $count[$kat] += max(0, (int)($row['jumlah'] ?? 0));
}

if (($count['dewasa'] + $count['anak'] + $count['bayi']) <= 0) {
    $count['dewasa'] = max(1, (int)($ticket['total_penumpang'] ?? 1));
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function fmtRp($n) { return 'Rp' . number_format((int)$n, 0, ',', '.'); }

$flashErr = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

$total    = (int)($ticket['total_harga'] ?? 0);
$paxBayar = max(0, $count['dewasa'] + $count['anak']);
$tarif    = $paxBayar > 0 ? (int)floor($total / $paxBayar) : $total;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - Navira</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/navy-theme.css" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Poppins',sans-serif;color:white;min-height:100vh;overflow-x:hidden}

        .bubble{position:fixed;bottom:-80px;border-radius:50%;background:rgba(255,255,255,0.05);animation:bup 12s infinite;pointer-events:none;z-index:0}
        @keyframes bup{0%{transform:translateY(0) scale(1);opacity:.5}100%{transform:translateY(-110vh) scale(1.8);opacity:0}}

        /* TOPBAR */
        .topbar{background:var(--navy-topbar);backdrop-filter:blur(14px);border-bottom:1px solid rgba(255,255,255,.07);color:#fff;padding:14px 16px;position:sticky;top:0;z-index:300}
        .topbar .row{display:flex;align-items:center;gap:12px}
        .back{width:34px;height:34px;border-radius:10px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.07);color:#fff;cursor:pointer;font-size:18px}
        .ttl{font-weight:700;font-size:18px;line-height:1}
        .sub{font-size:12px;opacity:.85;margin-top:4px}

        /* LAYOUT */
        .container{max-width:740px;margin:0 auto;padding:14px 14px 60px;position:relative;z-index:1}

        /* STEPPER */
        .stepper{background:rgba(255,255,255,.04);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.09);border-radius:18px;padding:14px 12px;margin-top:12px;box-shadow:0 0 60px rgba(0,0,0,.45)}
        .steps{display:flex;align-items:center;justify-content:space-between;gap:6px}
        .step{display:flex;flex-direction:column;align-items:center;gap:6px;flex:1}
        .dot{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;border:2px solid rgba(255,255,255,.18);color:rgba(255,255,255,.7);background:rgba(255,255,255,.05)}
        .dot.active{border-color:#38bdf8;background:#38bdf8;color:#020617}
        .line{height:2px;background:rgba(255,255,255,.10);flex:1;margin:0 4px;border-radius:2px}
        .lbl{font-size:10px;color:#94a3b8;text-align:center;line-height:1.2}
        .lbl.active{color:#38bdf8;font-weight:800}

        /* TIMER */
        .timer{margin-top:12px;background:rgba(56,189,248,.10);border:1px solid rgba(56,189,248,.20);border-radius:14px;padding:14px;display:flex;align-items:center;justify-content:center;gap:10px;color:#38bdf8;font-weight:900}
        .timer small{font-weight:700;color:#94a3b8}

        /* SECTION TITLE */
        .hsec{font-weight:800;color:white;margin:14px 4px 10px}

        /* CARD RINCIAN */
        .card{background:rgba(255,255,255,.04);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.09);border-radius:18px;box-shadow:0 0 60px rgba(0,0,0,.35);overflow:hidden}
        .card .head{padding:14px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;justify-content:space-between;align-items:center}
        .head .k{color:#94a3b8;font-size:12px}
        .head .v{font-weight:900;font-size:22px;letter-spacing:.6px}
        .head a{font-size:12px;font-weight:800;color:#38bdf8;text-decoration:none}
        .body{padding:14px}
        .rowi{display:flex;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.08)}
        .rowi:last-child{border-bottom:none}
        .rowi .l{color:#cbd5e1}
        .rowi .r{font-weight:800}
        .muted{color:#94a3b8;font-size:12px}
        .dash{border-top:1px dashed rgba(255,255,255,.20);margin:12px 0}
        .total{display:flex;justify-content:space-between;align-items:flex-end;padding-top:10px}
        .total .l{font-weight:800}
        .total .r{font-weight:900;font-size:22px;color:#fbbf24}

        /* NOTE */
        .note{margin-top:12px;background:rgba(251,191,36,.10);border:1px solid rgba(251,191,36,.20);border-radius:14px;padding:14px;color:#fbbf24;font-size:12px;line-height:1.5}

        /* TERMS */
        .terms{margin-top:12px;background:rgba(255,255,255,.04);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.09);border-radius:18px;box-shadow:0 0 60px rgba(0,0,0,.25);padding:14px}
        .terms ol{padding-left:18px;color:#cbd5e1}
        .terms li{margin:8px 0;font-size:13px;line-height:1.6}

        /* TOMBOL KONFIRMASI */
        .btn-konfirmasi{
            margin-top:16px;
            width:100%;
            border:0;
            border-radius:50px;
            padding:16px;
            background:linear-gradient(135deg,#16a34a,#22c55e);
            color:#fff;
            font-family:'Poppins',sans-serif;
            font-weight:900;
            font-size:15px;
            letter-spacing:.8px;
            text-transform:uppercase;
            cursor:pointer;
            transition:.25s;
            box-shadow:0 4px 20px rgba(34,197,94,.3);
        }
        .btn-konfirmasi:hover{
            transform:scale(1.02);
            box-shadow:0 0 30px rgba(34,197,94,.5);
        }
        .btn-konfirmasi:active{
            transform:scale(0.98);
        }

        /* INFO KONFIRMASI */
        .konfirmasi-info{
            margin-top:12px;
            background:rgba(34,197,94,.08);
            border:1px solid rgba(34,197,94,.2);
            border-radius:14px;
            padding:14px 16px;
            font-size:13px;
            color:#86efac;
            line-height:1.6;
            display:flex;
            gap:10px;
            align-items:flex-start;
        }
        .konfirmasi-info .icon{
            font-size:20px;
            flex-shrink:0;
            margin-top:1px;
        }

        /* ERROR */
        .pay-err{
            margin-top:10px;
            background:rgba(239,68,68,.08);
            border:1px solid rgba(239,68,68,.25);
            color:#fca5a5;
            border-radius:12px;
            padding:10px 12px;
            font-size:12px;
            display:none;
        }

        /* OVERLAY LOADING */
        #loadingOverlay{
            display:none;
            position:fixed;
            inset:0;
            background:rgba(2,6,23,.85);
            backdrop-filter:blur(10px);
            z-index:9999;
            flex-direction:column;
            align-items:center;
            justify-content:center;
            gap:20px;
        }
        #loadingOverlay.show{display:flex}
        .spinner{
            width:56px;height:56px;
            border:5px solid rgba(255,255,255,.1);
            border-top:5px solid #22c55e;
            border-radius:50%;
            animation:spin 1s linear infinite;
        }
        @keyframes spin{to{transform:rotate(360deg)}}
        .loading-txt{
            font-size:16px;font-weight:700;color:white;
            animation:pulse 1.5s ease infinite;
        }
        @keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}
        .loading-sub{font-size:13px;color:#64748b;margin-top:-10px}

        @media(max-width:480px){
            .head .v{font-size:16px}
            .total .r{font-size:18px}
        }
    </style>
</head>
<body class="bg-navy-animated">

    <!-- BUBBLES -->
    <?php for($i=0;$i<6;$i++): ?>
    <div class="bubble" style="left:<?= $i*17+4 ?>%;width:<?= 14+$i*4 ?>px;height:<?= 14+$i*4 ?>px;animation-delay:<?= $i*2 ?>s;animation-duration:<?= 11+$i*2 ?>s;"></div>
    <?php endfor; ?>

    <!-- LOADING OVERLAY -->
    <div id="loadingOverlay">
        <div class="spinner"></div>
        <div class="loading-txt">Memproses Pembayaran...</div>
        <div class="loading-sub">Mohon tunggu sebentar</div>
    </div>

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="row">
            <button class="back" onclick="history.back()">‹</button>
            <div>
                <div class="ttl">Pembayaran</div>
                <div class="sub">Order ID: <?= h($ticket['kode_booking']) ?></div>
            </div>
        </div>
    </div>

    <div class="container">

        <!-- STEPPER -->
        <div class="stepper">
            <div class="steps">
                <div class="step">
                    <div class="dot active">✓</div>
                    <div class="lbl active">Isi Data</div>
                </div>
                <div class="line"></div>
                <div class="step">
                    <div class="dot active">✓</div>
                    <div class="lbl active">Verifikasi</div>
                </div>
                <div class="line"></div>
                <div class="step">
                    <div class="dot active">3</div>
                    <div class="lbl active">Pembayaran</div>
                </div>
                <div class="line"></div>
                <div class="step">
                    <div class="dot">4</div>
                    <div class="lbl">E-Tiket</div>
                </div>
            </div>
        </div>

        <!-- TIMER -->
        <div class="timer">
            <small>⏱ Selesaikan pembayaran dalam</small>
            <span id="timer">--:--</span>
        </div>

        <!-- RINCIAN HARGA -->
        <div class="hsec">Rincian Pembayaran</div>
        <div class="card">
            <div class="head">
                <div>
                    <div class="k">Kode Transaksi</div>
                    <div class="v"><?= h($ticket['kode_booking']) ?></div>
                </div>
            </div>
            <div class="body">

                <!-- RUTE -->
                <div class="rowi">
                    <div class="l">Rute</div>
                    <div class="r" style="text-align:right;font-size:13px;">
                        <?= h($ticket['asal']) ?><br>
                        <span style="color:#38bdf8;">→</span>
                        <?= h($ticket['tujuan']) ?>
                    </div>
                </div>

                <!-- TANGGAL & JAM -->
                <div class="rowi">
                    <div class="l">Tanggal</div>
                    <div class="r"><?= date('d M Y', strtotime($ticket['tanggal'])) ?></div>
                </div>
                <div class="rowi">
                    <div class="l">Jam Check-In</div>
                    <div class="r"><?= h($ticket['jam']) ?></div>
                </div>
                <div class="rowi">
                    <div class="l">Layanan</div>
                    <div class="r"><?= ucfirst(h($ticket['layanan'])) ?></div>
                </div>

                <?php if(!empty($ticket['golongan'])): ?>
                <div class="rowi">
                    <div class="l">Kendaraan</div>
                    <div class="r"><?= h($ticket['golongan']) ?> — <?= h($ticket['plat']) ?></div>
                </div>
                <?php endif; ?>

                <div class="dash"></div>

                <!-- TARIF PENUMPANG -->
                <div class="muted" style="font-weight:700;margin-bottom:8px;">Tarif Penumpang</div>

                <?php if($count['dewasa'] > 0): ?>
                <div class="rowi">
                    <div class="l">Dewasa × <?= $count['dewasa'] ?> <span class="muted">@<?= fmtRp($tarif) ?></span></div>
                    <div class="r"><?= fmtRp($tarif * $count['dewasa']) ?></div>
                </div>
                <?php endif; ?>

                <?php if($count['anak'] > 0): ?>
                <div class="rowi">
                    <div class="l">Anak × <?= $count['anak'] ?> <span class="muted">@<?= fmtRp($tarif) ?></span></div>
                    <div class="r"><?= fmtRp($tarif * $count['anak']) ?></div>
                </div>
                <?php endif; ?>

                <?php if($count['bayi'] > 0): ?>
                <div class="rowi">
                    <div class="l">Bayi × <?= $count['bayi'] ?></div>
                    <div class="r" style="color:#4ade80;">Gratis</div>
                </div>
                <?php endif; ?>

                <div class="muted" style="font-weight:700;margin:12px 0 8px;">Biaya Lainnya</div>
                <div class="rowi">
                    <div class="l">Asuransi</div>
                    <div class="r muted">Termasuk</div>
                </div>
                <div class="rowi">
                    <div class="l">Biaya Layanan</div>
                    <div class="r"><?= fmtRp(0) ?></div>
                </div>

                <div class="dash"></div>

                <div class="total">
                    <div class="l">Total Pembayaran</div>
                    <div class="r"><?= fmtRp($total) ?></div>
                </div>
            </div>
        </div>

        <!-- NOTE -->
        <div class="note">
            ⚠️ Pembatalan tiket hanya berlaku untuk pembelian tiket dengan minimal harga Rp 50.000.
            Akan dikenakan biaya pembatalan sebesar 25% dari total harga tiket.
        </div>

        <!-- TERMS -->
        <div class="terms">
            <div style="font-weight:700;margin-bottom:10px;font-size:14px;">Syarat & Ketentuan</div>
            <ol>
                <li>Transaksi akan dibatalkan otomatis jika tidak diselesaikan dalam batas waktu yang ditentukan.</li>
                <li>Setelah pembayaran berhasil, E-Tiket dapat diunduh melalui menu <b>Pesanan Saya</b>.</li>
                <li>E-Tiket wajib ditunjukkan saat proses Masuk Pelabuhan (Check-In).</li>
                <li>Check-in dapat dilakukan mulai 2 jam sebelum jadwal masuk pelabuhan.</li>
                <li>Tiket hangus apabila belum Check-In hingga melewati batas jadwal yang dipilih.</li>
            </ol>
        </div>

        <!-- INFO KONFIRMASI -->
        <div class="konfirmasi-info">
            <span class="icon">✅</span>
            <div>
                <b>Konfirmasi Pembayaran Langsung</b><br>
                Klik tombol di bawah untuk mengkonfirmasi pembayaran dan langsung mendapatkan E-Tiket Anda.
                Tiket akan otomatis aktif setelah konfirmasi berhasil.
            </div>
        </div>

        <?php if($flashErr): ?>
        <div class="pay-err" style="display:block"><?= h($flashErr) ?></div>
        <?php endif; ?>

        <!-- FORM KONFIRMASI -->
        <form method="POST" id="formKonfirmasi" onsubmit="return prosesKonfirmasi()">
            <input type="hidden" name="konfirmasi" value="1">
            <input type="hidden" name="kode_booking" value="<?= h($ticket['kode_booking']) ?>">
            <button type="submit" class="btn-konfirmasi" id="btnKonfirmasi">
                ✅ KONFIRMASI PEMBAYARAN
            </button>
        </form>

    </div>

    <script>
    // ── TIMER ──
    const expiresAt = <?= (int)$pay_expires_at ?> * 1000;
    function pad(n){ return String(n).padStart(2,'0'); }
    function tick(){
        const diff = Math.max(0, expiresAt - Date.now());
        if(diff <= 0){
            document.getElementById('timer').textContent = '00:00';
            document.getElementById('btnKonfirmasi').disabled = true;
            document.getElementById('btnKonfirmasi').textContent = '⏱ WAKTU HABIS';
            document.getElementById('btnKonfirmasi').style.background = 'rgba(255,255,255,.1)';
            document.getElementById('btnKonfirmasi').style.cursor = 'not-allowed';
            return;
        }
        const mm = Math.floor(diff/1000/60);
        const ss = Math.floor((diff/1000) % 60);
        document.getElementById('timer').textContent = `${pad(mm)}:${pad(ss)}`;

        // Warna timer merah jika < 5 menit
        if(diff < 5*60*1000){
            document.querySelector('.timer').style.background = 'rgba(239,68,68,.12)';
            document.querySelector('.timer').style.borderColor = 'rgba(239,68,68,.3)';
            document.querySelector('.timer').style.color = '#f87171';
            document.getElementById('timer').style.color = '#f87171';
        }
    }
    tick();
    setInterval(tick, 1000);

    // ── PROSES KONFIRMASI ──
    function prosesKonfirmasi(){
        const btn = document.getElementById('btnKonfirmasi');
        const overlay = document.getElementById('loadingOverlay');

        // Disable tombol
        btn.disabled = true;
        btn.textContent = '⏳ Memproses...';

        // Tampil overlay loading
        overlay.classList.add('show');

        // Biarkan form submit normal
        return true;
    }
    </script>

</body>
</html>