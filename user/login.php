<?php
session_start();
include('../config/koneksi.php');

$error = "";

if(isset($_POST['login'])){
    $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if(empty($email) || empty($pass)){
        $error = "Email dan password wajib diisi!";
    } else {

        $q = mysqli_query($conn,"SELECT * FROM users WHERE email='$email'");

        if(!$q){
            die("Query Error: " . mysqli_error($conn));
        }

        $d = mysqli_fetch_assoc($q);

        if($d && password_verify($pass,$d['password'])){

            $_SESSION['user_id'] = $d['id'];
            $_SESSION['nama']    = $d['nama'];
            $_SESSION['role']    = $d['role'];

            if($d['role'] == 'super_admin'){
                $_SESSION['redirect_after_splash'] = '../superadmin/dashboard.php';
            }
            elseif($d['role'] == 'admin'){
                $_SESSION['redirect_after_splash'] = '../admin/dashboard.php';
            }
            else {
                $_SESSION['redirect_after_splash'] = 'dashboard.php';
            }

            header("Location: splash_login.php");
            exit;

        } else {
            $error = "Email atau password salah!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk — Navira</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
<link href="../assets/css/navy-theme.css" rel="stylesheet">

<style>
body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    overflow-x: hidden;
    overflow-y: auto;
    min-height: 100vh;
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

.wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    padding: 32px 16px;
    position: relative;
    z-index: 2;
}

/* ── LOGIN BOX ── */
.login-box {
    width: 100%;
    max-width: 380px;
    padding: 32px 28px;
    border-radius: 20px;
    background: rgba(255,255,255,0.08);
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255,255,255,0.15);
    box-shadow: 0 10px 40px rgba(0,0,0,0.5);
    animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ── BRAND (OPSI A: VERTICAL STACK) ── */
.brand-wrap {
    display: flex;
    flex-direction: column; /* Menyusun elemen tegak lurus ke bawah */
    align-items: center;    /* Membuat semua elemen otomatis rata tengah sempurna */
    gap: 8px;               /* Jarak konsisten antar elemen */
    margin-bottom: 28px;    /* Ruang sebelum form/alert error */
}

.brand-logo {
    height: 54px; /* Ukuran disesuaikan agar proporsional saat berdiri sendiri */
    width: auto;
    max-width: 180px;
    object-fit: contain;
    display: block;
}

.brand-name {
    font-size: 26px;
    font-weight: 700;
    color: white;
    letter-spacing: 3px; /* Jarak huruf sedikit renggang agar terkesan modern */
    line-height: 1.2;
}

/* ── SUBTITLE "Pemesanan Tiket Kapal" ── */
.brand-subtitle {
    font-size: 11px;
    color: #38bdf8;
    font-weight: 500;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    text-align: center;
    opacity: .9;
}

/* ── FORM ── */
.form-control {
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.1);
    color: white;
    border-radius: 12px;
    padding: 11px 14px;
    font-family: 'Poppins', sans-serif;
    font-size: 14px;
    transition: .25s;
}
.form-control:focus {
    background: rgba(255,255,255,0.14);
    border-color: rgba(56,189,248,0.5);
    box-shadow: 0 0 0 3px rgba(56,189,248,0.12);
    color: white;
    outline: none;
}
.form-control::placeholder { color: rgba(203,213,245,0.65); }

/* ── PASSWORD TOGGLE ── */
.password-box { position: relative; }
.toggle-password {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    font-size: 12px;
    color: #38bdf8;
    font-weight: 600;
    user-select: none;
}

/* ── TOMBOL MASUK ── */
.btn-ocean {
    background: linear-gradient(135deg, #0ea5e9, #38bdf8);
    border: none;
    border-radius: 12px;
    color: white;
    font-family: 'Poppins', sans-serif;
    font-weight: 700;
    font-size: 15px;
    padding: 12px;
    transition: .3s;
    min-height: 46px;
}
.btn-ocean:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(14,165,233,0.4);
    color: white;
}
.btn-ocean:active { transform: scale(.98); }

/* ── TOMBOL KEMBALI KE BERANDA ── */
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
    font-size: clamp(12px, 3vw, 14px);
    font-weight: 500;
    text-decoration: none;
    transition: .25s;
    cursor: pointer;
    text-align: center;
    min-height: 44px;
}
.btn-back-home:hover {
    background: rgba(255,255,255,0.1);
    border-color: rgba(255,255,255,0.28);
    color: white;
}
.btn-back-home:active { transform: scale(.98); }
.btn-back-home .arrow {
    font-size: 16px;
    line-height: 1;
    flex-shrink: 0;
}

/* ── ALERT ── */
.alert-danger {
    font-size: 13px;
    border-radius: 12px;
    background: rgba(239,68,68,0.12);
    border: 1px solid rgba(239,68,68,0.3);
    color: #fca5a5;
    padding: 11px 14px;
}

/* ── LINK ── */
a { color: #38bdf8; }

/* ── RESPONSIVE ── */
@media (max-width: 480px) {
    .login-box {
        padding: 24px 18px;
    }
    .brand-name { font-size: 22px; }
    .brand-logo { height: 44px; }
    .btn-ocean  { font-size: 14px; }
}
</style>
</head>

<body class="bg-navy">

<video autoplay muted loop playsinline id="bgVideo">
    <source src="../assets/video/laut.mp4" type="video/mp4">
</video>
<div class="overlay"></div>

<div class="wrapper">
<div class="login-box">

    <!-- BRAND (LOGO, NAMA, SUBTITLE RE-ORDERED JADI VERTIKAL) -->
    <div class="brand-wrap">
        <img src="../assets/logo.png" alt="Logo Navira" class="brand-logo">
        <span class="brand-name">NAVIRA</span>
        <div class="brand-subtitle">Pemesanan Tiket Kapal</div>
    </div>

    <!-- ALERT ERROR -->
    <?php if($error): ?>
    <div class="alert-danger mb-3">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- FORM LOGIN -->
    <form method="POST">

        <div class="mb-3">
            <input
                name="email"
                type="email"
                class="form-control"
                placeholder="Alamat Email"
                required
                autocomplete="email">
        </div>

        <div class="password-box mb-3">
            <input
                type="password"
                name="password"
                id="password"
                class="form-control"
                placeholder="Password"
                required
                autocomplete="current-password">
            <span class="toggle-password" onclick="togglePassword('password', this)">Show</span>
        </div>

        <button name="login" class="btn btn-ocean w-100">
            Masuk
        </button>

    </form>

    <!-- LINK DAFTAR -->
    <p class="mt-3 text-center mb-2" style="color:rgba(255,255,255,.75);font-size:13px;">
        Belum punya akun?
        <a href="register.php" style="color:#38bdf8;font-weight:700;text-decoration:underline;">
            Daftar di sini
        </a>
    </p>

    <!-- TOMBOL KEMBALI KE BERANDA -->
    <a href="../index.php" class="btn-back-home">
        <span class="arrow">←</span> Kembali ke Beranda
    </a>

</div>
</div>

<script>
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
</script>

</body>
</html>