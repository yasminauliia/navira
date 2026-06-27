
<?php
session_start();
include('../config/koneksi.php');

$msg   = "";
$error = "";

// [UPDATE 1] Tambah flag $registerSuccess untuk trigger redirect JS ke login.php
$registerSuccess = false;

if(isset($_POST['register'])){
    $nama      = mysqli_real_escape_string($conn, $_POST['nama']);
    $email     = strtolower(mysqli_real_escape_string($conn, $_POST['email']));
    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';

    if(empty($password) || empty($password2)){
        $error = "Password wajib diisi!";
    } elseif($password !== $password2){
        $error = "Password tidak cocok!";
    } else {

        $cek = mysqli_query($conn,"SELECT id FROM users WHERE email='$email'");
        if(mysqli_num_rows($cek) > 0){
            $error = "Email sudah terdaftar! Silakan <a href='login.php' class='text-decoration-none text-white fw-bold'>login di sini</a>.";
        } else {

            $hash = password_hash($password, PASSWORD_DEFAULT);
            mysqli_query($conn,"INSERT INTO users(nama,email,password,role)
            VALUES('$nama','$email','$hash','user')");

            // [UPDATE 2] Set $registerSuccess = true agar JS auto-redirect ke login.php
            $registerSuccess = true;
            $msg = "Akun berhasil dibuat! Mengalihkan ke halaman login...";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar — Navira</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
<link href="../assets/css/navy-theme.css" rel="stylesheet">

<style>
body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    min-height: 100vh;

    /* [UPDATE 3] Ubah dari overflow:hidden → overflow-y:auto
       agar pada layar kecil (Android) bisa scroll vertikal,
       tidak terpotong saat keyboard virtual muncul */
    overflow-x: hidden;
    overflow-y: auto;
}

#bgVideo {
    position: fixed;
    top: 50%;
    left: 50%;
    min-width: 100%;
    min-height: 100%;
    transform: translate(-50%, -50%);
    object-fit: cover;
    z-index: -2;
    filter: brightness(1.35) contrast(1.05) saturate(1.1);
}

.overlay {
    position: fixed;
    width: 100%;
    height: 100%;
    background: linear-gradient(to bottom, var(--navy-overlay-top), var(--navy-overlay-bottom));
    z-index: -1;
}

/* [UPDATE 4] Wrapper sekarang min-height:100vh, bukan height:100vh
   supaya tidak terpotong di layar HP kecil */
.wrapper {
    display: flex;
    min-height: 100vh;
    position: relative;
    z-index: 2;
}

.left {
    width: 50%;
    display: flex;
    align-items: center;
    justify-content: center;

    /* [UPDATE 5] Tambah padding atas-bawah supaya box tidak mepet
       di layar portrait */
    padding: 32px 16px;
}

.box {
    width: 380px;
    max-width: 100%;
    background: rgba(255,255,255,0.08);
    backdrop-filter: blur(20px);
    padding: 35px;
    border-radius: 20px;
    color: white;
    box-shadow: 0 10px 40px rgba(0,0,0,0.4);
    border: 1px solid rgba(255,255,255,0.15);
    animation: fadeUp 0.6s ease;
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Brand */
.brand-wrap {
    display: flex;
    flex-direction: column; /* Diubah ke column agar elemen menyusun ke bawah secara vertikal */
    align-items: center;
    justify-content: center;
    gap: 8px; /* Mengatur kerapatan vertikal antar elemen brand */
    margin: 0 auto 20px;
}
.brand-logo {
    height: 54px; /* Ukuran disesuaikan agar seimbang dalam susunan vertikal */
    width: auto;
    max-width: 200px;
    object-fit: contain;
    display: block;
}
.brand-name {
    font-size: 26px; /* Disesuaikan agar serasi dengan halaman login */
    font-weight: 700;
    color: white;
    letter-spacing: 3px; /* Jarak huruf disamakan dengan login agar estetik */
    line-height: 1.2;
}

/* [TAMBAHAN BARU] Style khusus untuk subtitle pemesanan tiket kapal */
.brand-subtitle {
    font-size: 11px;
    color: #38bdf8;
    font-weight: 500;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    text-align: center;
    opacity: .9;
}

/* Input */
.form-control {
    border-radius: 12px;
    background: rgba(255,255,255,0.1);
    border: none;
    color: white;
}
.form-control::placeholder { color: #cbd5f5; }

/* Tombol daftar */
.btn-main {
    background: linear-gradient(90deg,#3b82f6,#0ea5e9);
    border: none;
    border-radius: 12px;
    color: white;
    font-weight: 600;
    transition: 0.3s;
}
.btn-main:hover {
    transform: scale(1.03);
    box-shadow: 0 5px 20px rgba(59,130,246,0.4);
    color: white;
}

/* Password show/hide */
.password-box { position: relative; }
.toggle-password {
    position: absolute;
    right: 10px;
    top: 10px;
    cursor: pointer;
    font-size: 12px;
    color: #93c5fd;
}

/* [UPDATE 6] Tombol "Kembali ke Beranda" — desain proporsional,
   nyaman di desktop maupun Android.
   - Ukuran font adaptif (clamp)
   - Padding cukup besar agar mudah di-tap di layar sentuh
   - Border subtle, tidak mengganggu hirarki visual */
.btn-back-home {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 11px 18px;
    margin-top: 10px;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.15);
    background: rgba(255,255,255,0.05);
    color: rgba(255,255,255,0.7);
    font-family: 'Poppins', sans-serif;
    font-size: clamp(12px, 3vw, 14px);  /* adaptif ukuran layar */
    font-weight: 500;
    text-decoration: none;
    transition: 0.25s;
    cursor: pointer;
    text-align: center;
    min-height: 44px; /* Apple HIG: minimal 44px untuk target tap */
}
.btn-back-home:hover {
    background: rgba(255,255,255,0.1);
    border-color: rgba(255,255,255,0.28);
    color: white;
}
.btn-back-home:active {
    transform: scale(0.98);
}
.btn-back-home .arrow {
    font-size: 16px;
    line-height: 1;
    flex-shrink: 0;
}

/* [UPDATE 7] Alert sukses diberi style khusus biar terlihat lebih
   tercerai dari alert error Bootstrap biasa */
.alert-navira-success {
    background: rgba(34,197,94,0.12);
    border: 1px solid rgba(34,197,94,0.3);
    color: #4ade80;
    border-radius: 12px;
    padding: 12px 16px;
    font-size: 13px;
    text-align: center;
    margin-bottom: 14px;
}

/* Helper teks kecil cek password */
.small-text { font-size: 12px; }

/* Panel kanan */
.right {
    width: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    text-align: center;
    padding: 40px;
}
.right h1 {
    font-size: 42px;
    font-weight: 700;
    text-shadow: 0 2px 12px rgba(0,0,0,0.3);
}
.right p { opacity: 0.9; }

a { color: #60a5fa; text-decoration: none; }

/* ── RESPONSIVE ──
   [UPDATE 8] Breakpoint lebih lengkap:
   - 768px: panel kiri-kanan jadi satu kolom (stack vertikal)
   - 480px: ukuran font & padding lebih kecil lagi */
@media (max-width: 768px) {
    .wrapper {
        flex-direction: column;
    }
    .left, .right {
        width: 100%;
    }
    .left {
        padding: 40px 20px 24px;
    }
    .right {
        padding: 24px 20px 48px;
        order: -1; /* judul pindah ke atas di mobile */
    }
    .right h1 { font-size: 26px; }
    .right p  { font-size: 14px; }

    .box {
        padding: 28px 22px;
    }
    .brand-name { font-size: 22px; }
    .brand-logo { height: 44px; }
}

@media (max-width: 480px) {
    .box {
        padding: 24px 18px;
    }
    .btn-main, .btn-back-home {
        font-size: 13px;
    }
}
</style>
</head>

<body class="bg-navy">

<video autoplay muted loop playsinline id="bgVideo">
    <source src="../assets/video/laut.mp4" type="video/mp4">
</video>
<div class="overlay"></div>

<div class="wrapper">

    <div class="left">
    <div class="box">

        <div class="brand-wrap">
            <img src="../assets/logo.png" alt="Logo" class="brand-logo">
            <span class="brand-name">NAVIRA</span>
            <div class="brand-subtitle">Pemesanan Tiket Kapal</div>
        </div>
        <h5 class="mb-3 text-center" style="font-size: 15px; opacity: 0.85;">Buat Akun Baru</h5>

        <?php if($msg):   ?>
        <div class="alert-navira-success">
            ✅ <?= $msg ?>
        </div>
        <?php endif; ?>

        <?php if($error): ?>
        <div class="alert alert-danger" style="font-size:13px;border-radius:12px;">
            <?= $error ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="formDaftar">

            <input name="nama" class="form-control mb-2"
                placeholder="Nama lengkap" required>

            <input name="email" type="email" class="form-control mb-2"
                placeholder="Email" required>

            <div class="password-box mb-2">
                <input type="password" name="password" id="password"
                    class="form-control" placeholder="Password"
                    required onkeyup="checkMatch()">
                <span class="toggle-password"
                    onclick="togglePassword('password', this)">Show</span>
            </div>

            <div class="password-box mb-1">
                <input type="password" name="password2" id="password2"
                    class="form-control" placeholder="Konfirmasi Password"
                    required onkeyup="checkMatch()">
                <span class="toggle-password"
                    onclick="togglePassword('password2', this)">Show</span>
            </div>

            <div id="info" class="small-text mb-3"></div>

            <button name="register" class="btn btn-main w-100">
                Daftar Sekarang
            </button>

        </form>

        <p class="mt-3 text-center" style="color:white;font-size:13px;">
            Sudah punya akun?
            <a href="login.php" style="color:#38bdf8;font-weight:700;text-decoration:underline;">
                Masuk di sini
            </a>
        </p>

        <a href="../index.php" class="btn-back-home">
            <span class="arrow">←</span> Kembali ke Beranda
        </a>

    </div>
    </div>

    <div class="right">
        <div>
            <h1>Mulai Petualangan 🌊</h1>
            <p>Buat akun dan jelajahi perjalanan<br>laut tanpa batas.</p>
        </div>
    </div>

</div>

<script>
// ─── Show / Hide Password ───────────────────────────────
function togglePassword(id, el){
    const input = document.getElementById(id);
    if(input.type === 'password'){
        input.type = 'text';
        el.innerText = 'Hide';
    } else {
        input.type = 'password';
        el.innerText = 'Show';
    }
}

// ─── Cek kecocokan password realtime ───────────────────
function checkMatch(){
    const p1   = document.getElementById('password').value;
    const p2   = document.getElementById('password2').value;
    const info = document.getElementById('info');

    if(p2.length === 0){ info.innerHTML = ''; return; }

    info.innerHTML = p1 === p2
        ? "<span style='color:#22c55e;'>✔ Password cocok</span>"
        : "<span style='color:#ef4444;'>✖ Password tidak cocok</span>";
}

// [UPDATE 9] Auto redirect ke login.php 2 detik setelah daftar berhasil
// PHP flag $registerSuccess di-echo ke JS supaya tidak perlu polling/fetch
<?php if($registerSuccess): ?>
setTimeout(function(){
    window.location.href = 'login.php';
}, 2000);
<?php endif; ?>
</script>

</body>
</html>

