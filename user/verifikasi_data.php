<?php
session_start();
include('../config/koneksi.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id  = (int)$_SESSION['user_id'];
$ticket_id = (int)($_SESSION['order']['ticket_id'] ?? 0);

if ($ticket_id <= 0) {
    header("Location: beli_tiket.php");
    exit;
}

// init countdown: verifikasi 15 menit, pembayaran 30 menit
$now = time();
if (empty($_SESSION['order']['verif_expires_at'])) {
    $_SESSION['order']['verif_expires_at'] = $now + 15 * 60;
}
if (empty($_SESSION['order']['pay_expires_at'])) {
    $_SESSION['order']['pay_expires_at'] = $now + 30 * 60;
}
$verif_expires_at = (int)$_SESSION['order']['verif_expires_at'];

// ambil tiket
$stmt = $conn->prepare("SELECT t.id_ticket, t.kode_booking,
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
                        LIMIT 1");
$stmt->bind_param("ii", $ticket_id, $user_id);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ticket) {
    header("Location: beli_tiket.php");
    exit;
}

// ambil detail penumpang
$pax = [];
// SESUDAH
$stmt2 = $conn->prepare("SELECT kategori, jumlah, titel, nama_lengkap, jenis_id, nomor_id, usia, kota_asal
                         FROM penumpang_detail
                         WHERE ticket_id = ?
                         ORDER BY FIELD(kategori,'dewasa','anak','bayi'), jumlah ASC, nama_lengkap ASC");
$stmt2->bind_param("i", $ticket_id);
$stmt2->execute();
$res2 = $stmt2->get_result();
while ($row = $res2->fetch_assoc()) $pax[] = $row;
$stmt2->close();

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function fmtRp($n) { return 'Rp' . number_format((int)$n, 0, ',', '.'); }

// ringkas hitungan penumpang
$count = ['dewasa'=>0,'anak'=>0,'bayi'=>0];
foreach ($pax as $row) {
    $kat = strtolower((string)($row['kategori'] ?? ''));
    if (!isset($count[$kat])) continue;
    // jika model lama (hanya kategori+jumlah), gunakan jumlah sebagai count
    if (!empty($row['titel']) || !empty($row['nama_lengkap']) || !empty($row['nomor_id'])) {
        $count[$kat] += 1;
    } else {
        $count[$kat] += (int)($row['jumlah'] ?? 0);
    }
}

// fallback jika tidak ada pax rows
if (($count['dewasa'] + $count['anak'] + $count['bayi']) <= 0) {
    $count['dewasa'] = max(1, (int)($ticket['total_penumpang'] ?? 1));
}

$agree_err = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi - Navira</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/navy-theme.css" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Poppins',sans-serif;color:white;min-height:100vh;overflow-x:hidden}
        .bubble{position:fixed;bottom:-80px;border-radius:50%;background:rgba(255,255,255,0.05);animation:bup 12s infinite;pointer-events:none;z-index:0}
        @keyframes bup{0%{transform:translateY(0) scale(1);opacity:.5}100%{transform:translateY(-110vh) scale(1.8);opacity:0}}

        .topbar{background:var(--navy-topbar);backdrop-filter:blur(14px);border-bottom:1px solid rgba(255,255,255,.07);color:#fff;padding:14px 16px;position:sticky;top:0;z-index:300}
        .topbar .row{display:flex;align-items:center;gap:12px}
        .back{width:34px;height:34px;border-radius:10px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.07);color:#fff;cursor:pointer;font-size:18px}
        .ttl{font-weight:700;font-size:18px;line-height:1}
        .sub{font-size:12px;opacity:.85;margin-top:4px}
        .container{max-width:740px;margin:0 auto;padding:14px 14px 34px;position:relative;z-index:1}
        .stepper{background:rgba(255,255,255,.04);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.09);border-radius:18px;padding:14px 12px;margin-top:12px;box-shadow:0 0 60px rgba(0,0,0,.45)}
        .steps{display:flex;align-items:center;justify-content:space-between;gap:6px}
        .step{display:flex;flex-direction:column;align-items:center;gap:6px;flex:1}
        .dot{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;border:2px solid rgba(255,255,255,.18);color:rgba(255,255,255,.7);background:rgba(255,255,255,.05)}
        .dot.active{border-color:#38bdf8;background:#38bdf8;color:#020617}
        .line{height:2px;background:rgba(255,255,255,.10);flex:1;margin:0 4px;border-radius:2px}
        .lbl{font-size:10px;color:#94a3b8;text-align:center;line-height:1.2}
        .lbl.active{color:#38bdf8;font-weight:800}
        .timer{margin-top:12px;background:rgba(56,189,248,.10);border:1px solid rgba(56,189,248,.20);border-radius:14px;padding:14px;display:flex;align-items:center;justify-content:center;gap:10px;color:#38bdf8;font-weight:900}
        .timer small{font-weight:700;color:#94a3b8;opacity:1}
        .section{margin-top:14px}
        .hsec{font-weight:800;color:white;margin:12px 4px 10px}
        .card{background:rgba(255,255,255,.04);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.09);border-radius:18px;box-shadow:0 0 60px rgba(0,0,0,.35);overflow:hidden}
        .card .head{padding:14px 14px 10px;border-bottom:1px solid rgba(255,255,255,.08)}
        .route{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px}
        .port{flex:1;text-align:center}
        .port .nm{font-weight:800;letter-spacing:.6px}
        .port .subp{font-size:11px;color:#94a3b8;margin-top:3px}
        .arr{color:#38bdf8;font-weight:900}
        .meta{display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:0 14px 14px}
        .mitem{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:10px 12px}
        .mitem .k{font-size:10px;color:#64748b;text-transform:uppercase;letter-spacing:.6px;font-weight:700}
        .mitem .v{margin-top:4px;font-weight:700;font-size:13px}
        .info{margin:12px 0;background:rgba(56,189,248,.08);border:1px solid rgba(56,189,248,.18);color:#94a3b8;border-radius:14px;padding:14px;display:flex;gap:10px;align-items:flex-start}
        .info .i{width:22px;height:22px;border-radius:50%;background:rgba(56,189,248,.14);display:flex;align-items:center;justify-content:center;font-weight:900;flex-shrink:0;margin-top:2px;color:#38bdf8}
        .paxlist{padding:12px 14px 14px}
        .paxrow{display:flex;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.08)}
        .paxrow:last-child{border-bottom:none}
        .pname{font-weight:800}
        .pid{font-size:12px;color:#94a3b8;margin-top:2px}
        .badge{display:inline-flex;align-items:center;gap:6px;color:#38bdf8;font-weight:800;font-size:12px}
        .agree{margin-top:12px;background:rgba(56,189,248,.08);border:1px solid rgba(56,189,248,.18);border-radius:14px;padding:14px;display:flex;gap:12px;align-items:flex-start}
        .agree input{width:18px;height:18px;margin-top:3px;accent-color:#38bdf8}
        .agree span{font-size:13px;color:#cbd5e1;line-height:1.5}
        .agree b{color:#38bdf8}
        .err{margin-top:10px;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.25);color:#fca5a5;border-radius:12px;padding:10px 12px;font-size:12px}
        .btn{margin-top:14px;width:100%;border:0;border-radius:50px;padding:14px 16px;background:linear-gradient(135deg,#06b6d4,#3b82f6);color:#fff;font-weight:900;letter-spacing:.8px;text-transform:uppercase;cursor:pointer;transition:.25s}
        .btn:hover{transform:scale(1.01);box-shadow:0 0 30px rgba(56,189,248,.35)}
        .btn:disabled{opacity:.45;cursor:not-allowed}
        @media (max-width:640px){.meta{grid-template-columns:1fr}}
    </style>
</head>
<body class="bg-navy-animated">
    <?php for($i=0;$i<6;$i++): ?>
      <div class="bubble" style="left:<?= $i*17+4 ?>%;width:<?= 14+$i*4 ?>px;height:<?= 14+$i*4 ?>px;animation-delay:<?= $i*2 ?>s;animation-duration:<?= 11+$i*2 ?>s;"></div>
    <?php endfor; ?>
    <div class="topbar">
        <div class="row">
            <button class="back" onclick="history.back()">‹</button>
            <div>
                <div class="ttl">Verifikasi</div>
                <div class="sub">Order ID: <?= h($ticket['kode_booking']) ?></div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="stepper">
            <div class="steps">
                <div class="step">
                    <div class="dot active">1</div>
                    <div class="lbl active">Isi Data Diri</div>
                </div>
                <div class="line"></div>
                <div class="step">
                    <div class="dot active">2</div>
                    <div class="lbl active">Verifikasi Data</div>
                </div>
                <div class="line"></div>
                <div class="step">
                    <div class="dot">3</div>
                    <div class="lbl">Pembayaran</div>
                </div>
                <div class="line"></div>
                <div class="step">
                    <div class="dot">4</div>
                    <div class="lbl">E-Tiket</div>
                </div>
            </div>
        </div>

        <div class="timer">
            <small>⏱ Sisa waktu verifikasi</small>
            <span id="timer">--:--:--</span>
        </div>

        <div class="section">
            <div class="hsec">Informasi Perjalanan</div>
            <div class="card">
                <div class="route">
                    <div class="port">
                        <div class="nm"><?= h(explode(',', (string)$ticket['asal'])[0]) ?></div>
                        <div class="subp"><?= h(trim((string)($ticket['asal'] ?? ''))) ?></div>
                    </div>
                    <div class="arr">→</div>
                    <div class="port">
                        <div class="nm"><?= h(explode(',', (string)$ticket['tujuan'])[0]) ?></div>
                        <div class="subp"><?= h(trim((string)($ticket['tujuan'] ?? ''))) ?></div>
                    </div>
                </div>
                <div class="meta">
                    <div class="mitem"><div class="k">Pengguna Jasa</div><div class="v"><?= h(ucwords(str_replace('_',' ', (string)$ticket['jenis_pengguna']))) ?></div></div>
                    <div class="mitem"><div class="k">Layanan</div><div class="v"><?= h(ucfirst(strtolower((string)$ticket['layanan']))) ?></div></div>
                    <div class="mitem"><div class="k">Tanggal</div><div class="v"><?= h(date('d M Y', strtotime((string)$ticket['tanggal']))) ?></div></div>
                    <div class="mitem"><div class="k">Jam</div><div class="v"><?= h((string)$ticket['jam']) ?> WIB</div></div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="hsec">Rincian Penumpang</div>
            <div class="info">
                <div class="i">i</div>
                <div>Mohon pastikan kembali data penumpang yang diisi sudah benar sesuai dengan Kartu Identitas (KTP/SIM/Paspor).</div>
            </div>

            <div class="card">
                <div class="paxlist">
                    <?php
                    $hasDetail = false;
                    foreach ($pax as $row) {
                        if (!empty($row['nama_lengkap']) || !empty($row['nomor_id'])) { $hasDetail = true; break; }
                    }

                    if ($hasDetail) {
                        foreach ($pax as $row) {
                            if (empty($row['nama_lengkap'])) continue;
                            ?>
                            <div class="paxrow">
                                <div>
                                    <div class="pname"><?= h($row['nama_lengkap']) ?></div>
                                    <div class="pid"><?= h($row['nomor_id'] ?: '-') ?></div>
                                </div>
                                <div class="badge">⚙ Terverifikasi</div>
                            </div>
                            <?php
                        }
                    } else {
                        $labels = ['dewasa'=>'Dewasa','anak'=>'Anak','bayi'=>'Bayi'];
                        foreach ($count as $k => $v) {
                            if ($v <= 0) continue;
                            ?>
                            <div class="paxrow">
                                <div>
                                    <div class="pname"><?= h($labels[$k] ?? $k) ?></div>
                                    <div class="pid"><?= (int)$v ?> penumpang</div>
                                </div>
                                <div class="badge">⚙ Terverifikasi</div>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
            </div>

            <?php if ($agree_err): ?>
                <div class="err"><?= h($agree_err) ?></div>
            <?php endif; ?>

            <form action="pembayaran.php" method="POST">
                <div class="agree">
                    <input type="checkbox" id="agree" name="agree" value="1" onchange="document.getElementById('btnNext').disabled = !this.checked;">
                    <span>Ya, saya setuju bahwa data perjalanan (<b>nama penumpang</b>, nomor polisi kendaraan, pelabuhan asal, pelabuhan tujuan, jadwal masuk pelabuhan, dll) yang saya isi benar dan menyetujui data tersebut dapat digunakan sesuai Syarat & Ketentuan yang berlaku.</span>
                </div>
                <button class="btn" id="btnNext" type="submit" disabled>KONFIRMASI</button>
            </form>
        </div>
    </div>

    <script>
        const expiresAt = <?= (int)$verif_expires_at ?> * 1000;
        function pad(n){ return String(n).padStart(2,'0'); }
        function tick(){
            const diff = Math.max(0, expiresAt - Date.now());
            const s = Math.floor(diff/1000);
            const hh = Math.floor(s/3600);
            const mm = Math.floor((s%3600)/60);
            const ss = s%60;
            document.getElementById('timer').textContent = `${pad(hh)}:${pad(mm)}:${pad(ss)}`;
            if(diff <= 0){
                document.getElementById('btnNext').disabled = true;
            }
        }
        tick();
        setInterval(tick, 1000);
    </script>
</body>
</html>

