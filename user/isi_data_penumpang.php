<?php
session_start();
include('../config/koneksi.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Wajib ada order dari simpan_tiket.php
$order = $_SESSION['order'] ?? [];
if (empty($order['order_id']) || empty($order['asal_id']) || empty($order['tujuan_id']) || empty($order['tanggal']) || empty($order['jam'])) {
    header("Location: beli_tiket.php");
    exit;
}

$jml_dewasa = (int)($order['dewasa'] ?? 1);
$jml_anak   = (int)($order['anak'] ?? 0);
$jml_bayi   = (int)($order['bayi'] ?? 0);

$jenis_pengguna = (string)($order['jenis_pengguna'] ?? 'penumpang');
$layanan        = (string)($order['layanan'] ?? 'reguler');

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function fmtRp($n) { return 'Rp' . number_format((int)$n, 0, ',', '.'); }

$err = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Isi Data Diri - Navira</title>
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

    .hsec{font-weight:800;color:white;margin:14px 4px 10px}
    .card{background:rgba(255,255,255,.04);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.09);border-radius:18px;box-shadow:0 0 60px rgba(0,0,0,.35);overflow:hidden}
    .card .head{padding:14px;border-bottom:1px solid rgba(255,255,255,.08)}
    .row2{display:grid;grid-template-columns:1fr 1fr;gap:12px;padding:14px}
    .row1{display:grid;grid-template-columns:1fr;gap:12px;padding:14px}
    label{font-size:12px;color:#cbd5e1;font-weight:700}
    .hint{font-size:11px;color:#64748b;margin-top:6px}
    input,select{width:100%;padding:12px 12px;border-radius:12px;border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.06);color:white;font-family:'Poppins',sans-serif;font-size:14px;outline:none}
    select option{background:#0f172a;color:white}
    input:focus,select:focus{border-color:#38bdf8;box-shadow:0 0 0 3px rgba(56,189,248,.15)}
    .pill{display:inline-flex;gap:8px;flex-wrap:wrap;padding:12px 14px}
    .chip{background:rgba(56,189,248,.12);border:1px solid rgba(56,189,248,.2);color:#38bdf8;border-radius:999px;padding:6px 10px;font-weight:800;font-size:12px}
    .switch{display:flex;align-items:center;gap:10px;padding:10px 14px;border-top:1px solid #eef2f7}
    .switch input{width:40px}
    .err{margin-top:10px;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.25);color:#fca5a5;border-radius:12px;padding:10px 12px;font-size:12px}
    .btn{margin-top:14px;width:100%;border:0;border-radius:50px;padding:15px 16px;background:linear-gradient(135deg,#06b6d4,#3b82f6);color:#fff;font-weight:900;letter-spacing:.8px;text-transform:uppercase;cursor:pointer;transition:.25s}
    .btn:hover{transform:scale(1.01);box-shadow:0 0 30px rgba(56,189,248,.35)}
    .btn:disabled{opacity:.45;cursor:not-allowed}
    .mini{font-size:11px;color:#94a3b8}
    @media (max-width:640px){.row2{grid-template-columns:1fr}}
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
        <div class="ttl">Isi Data Diri</div>
        <div class="sub">Order ID: <?= h($order['order_id']) ?></div>
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
          <div class="dot">2</div>
          <div class="lbl">Verifikasi Data</div>
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

    <?php if ($err): ?><div class="err"><?= h($err) ?></div><?php endif; ?>

    <div class="hsec">Ringkasan Pesanan</div>
    <div class="card">
      <div class="head">
        <div style="font-weight:900"><?= h($order['asal_id']) ?> → <?= h($order['tujuan_id']) ?></div>
        <div class="mini"><?= h(date('d M Y', strtotime((string)$order['tanggal']))) ?> • <?= h($order['jam']) ?> WIB • <?= h(ucfirst(strtolower($layanan))) ?></div>
      </div>
      <div class="pill">
        <?php if ($jml_dewasa): ?><span class="chip">Dewasa x <?= (int)$jml_dewasa ?></span><?php endif; ?>
        <?php if ($jml_anak): ?><span class="chip">Anak x <?= (int)$jml_anak ?></span><?php endif; ?>
        <?php if ($jml_bayi): ?><span class="chip" style="background:rgba(74,222,128,.10);border-color:rgba(74,222,128,.20);color:#4ade80;">Bayi x <?= (int)$jml_bayi ?></span><?php endif; ?>
        <span class="chip" style="margin-left:auto;background:rgba(251,191,36,.10);border-color:rgba(251,191,36,.20);color:#fbbf24;">Total <?= fmtRp((int)($order['total_harga'] ?? 0)) ?></span>
      </div>
    </div>

    <form action="penumpang_detail.php" method="POST" id="formPax">
      <!-- Hidden order info -->
      <input type="hidden" name="order_id" value="<?= h($order['order_id']) ?>">
      <input type="hidden" name="asal_id" value="<?= h($order['asal_id']) ?>">
      <input type="hidden" name="tujuan_id" value="<?= h($order['tujuan_id']) ?>">
      <input type="hidden" name="tanggal" value="<?= h($order['tanggal']) ?>">
      <input type="hidden" name="jam" value="<?= h($order['jam']) ?>">
      <input type="hidden" name="layanan" value="<?= h($layanan) ?>">
      <input type="hidden" name="jenis_pengguna" value="<?= h($jenis_pengguna) ?>">
      <input type="hidden" name="total_harga" value="<?= (int)($order['total_harga'] ?? 0) ?>">

      <div class="hsec">Informasi Pemesan</div>
      <div class="card">
        <div class="row2">
          <div>
            <label>Nama Pemesan</label>
            <input type="text" name="nama_pemesan" id="nama_pemesan" placeholder="Nama sesuai KTP/SIM/Paspor" required>
            <div class="hint">Tanpa gelar/karakter khusus.</div>
          </div>
          <div>
            <label>Nomor Handphone</label>
            <input type="tel" name="hp_pemesan" id="hp_pemesan" placeholder="08xxxxxxxxxx" required>
            <div class="hint">Contoh 08123456789</div>
          </div>
        </div>
        <div class="row1" style="padding-top:0">
          <div>
            <label>Alamat e-mail</label>
            <input type="email" name="email_pemesan" id="email_pemesan" placeholder="nama@email.com" required>
            <div class="hint">Digunakan untuk data pemesan dan pembayaran.</div>
          </div>
        </div>
      </div>

      <div class="hsec">Detail Penumpang</div>

      <?php for ($i=1; $i<=$jml_dewasa; $i++): ?>
        <div class="card" style="margin-bottom:12px;">
          <div class="head" style="display:flex;justify-content:space-between;align-items:center;">
            <div style="font-weight:900">Dewasa <?= $i ?></div>
            <?php if ($i === 1): ?>
              <div style="display:flex;align-items:center;gap:8px;font-size:12px;color:#94a3b8;">
                <span>Sama dengan pemesan</span>
                <input type="checkbox" id="same_pemesan" onchange="copyPemesan()">
              </div>
            <?php endif; ?>
          </div>
          <div class="row2">
            <div>
              <label>Titel</label>
              <select name="titel_dewasa_<?= $i ?>" id="titel_dewasa_<?= $i ?>">
                <option value="Tuan">Tuan</option>
                <option value="Nyonya">Nyonya</option>
                <option value="Nona">Nona</option>
              </select>
            </div>
            <div>
              <label>Nama Lengkap</label>
              <input type="text" name="nama_dewasa_<?= $i ?>" id="nama_dewasa_<?= $i ?>" required>
              <div class="hint">Isi sesuai KTP/SIM/Paspor.</div>
            </div>
          </div>
          <div class="row2" style="padding-top:0">
            <div>
              <label>Jenis ID</label>
              <select name="jenis_id_dewasa_<?= $i ?>" id="jenis_id_dewasa_<?= $i ?>">
                <option value="KTP">KTP</option>
                <option value="SIM">SIM</option>
                <option value="Paspor">Paspor</option>
              </select>
            </div>
            <div>
              <label>Nomor Identitas</label>
              <input type="text" name="no_id_dewasa_<?= $i ?>" id="no_id_dewasa_<?= $i ?>" required>
            </div>
          </div>
          <div class="row2" style="padding-top:0">
            <div>
              <label>Usia</label>
              <input type="number" name="usia_dewasa_<?= $i ?>" id="usia_dewasa_<?= $i ?>" min="10" value="10" required>
              <div class="hint">Usia 10 th keatas</div>
            </div>
            <div>
              <label>Kota Asal</label>
              <input type="text" name="kota_dewasa_<?= $i ?>" id="kota_dewasa_<?= $i ?>" required>
            </div>
          </div>
        </div>
      <?php endfor; ?>

      <?php for ($i=1; $i<=$jml_anak; $i++): ?>
        <div class="card" style="margin-bottom:12px;">
          <div class="head" style="font-weight:900">Anak <?= $i ?></div>
          <div class="row2">
            <div>
              <label>Titel</label>
              <select name="titel_anak_<?= $i ?>">
                <option value="Ananda">Ananda</option>
              </select>
            </div>
            <div>
              <label>Nama Lengkap</label>
              <input type="text" name="nama_anak_<?= $i ?>" required>
            </div>
          </div>
          <div class="row2" style="padding-top:0">
            <div>
              <label>Jenis ID</label>
              <select name="jenis_id_anak_<?= $i ?>">
                <option value="Akta">Akta</option>
                <option value="KIA">KIA</option>
              </select>
            </div>
            <div>
              <label>Nomor Identitas</label>
              <input type="text" name="no_id_anak_<?= $i ?>" required>
            </div>
          </div>
          <div class="row2" style="padding-top:0">
            <div>
              <label>Usia</label>
              <input type="number" name="usia_anak_<?= $i ?>" min="3" max="10" value="3" required>
              <div class="hint">Usia 3–10 th</div>
            </div>
            <div>
              <label>Kota Asal</label>
              <input type="text" name="kota_anak_<?= $i ?>" required>
            </div>
          </div>
        </div>
      <?php endfor; ?>

      <?php for ($i=1; $i<=$jml_bayi; $i++): ?>
        <div class="card" style="margin-bottom:12px;">
          <div class="head" style="font-weight:900">Bayi <?= $i ?></div>
          <div class="row2">
            <div>
              <label>Nama Lengkap</label>
              <input type="text" name="nama_bayi_<?= $i ?>" required>
            </div>
            <div>
              <label>Usia (bulan)</label>
              <input type="number" name="usia_bayi_<?= $i ?>" min="0" max="35" value="0" required>
              <div class="hint">Bayi &lt; 3 tahun</div>
            </div>
          </div>
        </div>
      <?php endfor; ?>

      <button class="btn" type="submit">PESAN SEKARANG</button>
    </form>
  </div>

  <script>
    function onlyDigits(v){ return (v || '').replace(/\D/g,''); }
    function copyPemesan(){
      const cb = document.getElementById('same_pemesan');
      if(!cb) return;
      if(cb.checked){
        const nm = document.getElementById('nama_pemesan').value || '';
        document.getElementById('nama_dewasa_1').value = nm;
      }
    }
    document.getElementById('hp_pemesan').addEventListener('input', (e) => {
      e.target.value = onlyDigits(e.target.value);
    });
    document.getElementById('nama_pemesan').addEventListener('input', () => {
      const cb = document.getElementById('same_pemesan');
      if(cb && cb.checked) copyPemesan();
    });
  </script>
</body>
</html>

