<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['redirect_after_splash'])) {
    if ($_SESSION['role'] === 'super_admin') {
        $redirect_to = '../superadmin/dashboard.php';
    } elseif ($_SESSION['role'] === 'admin') {
        $redirect_to = '../admin/dashboard.php';
    } else {
        $redirect_to = 'dashboard.php';
    }
} else {
    $redirect_to = $_SESSION['redirect_after_splash'];
    unset($_SESSION['redirect_after_splash']);
}

$nama = $_SESSION['nama'] ?? 'Pengguna';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Memuat Dashboard — Navira</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
html,body{height:100%;overflow:hidden;font-family:'Poppins',sans-serif;}

/* ════════════════════════════════════════════
   BACKGROUND — gradasi laut dalam, jadi dasar
   tempat gelembung naik dari bawah ke atas
════════════════════════════════════════════ */
.ocean-bg{
    position:fixed; inset:0;
    background:
        radial-gradient(circle at 50% 100%, rgba(56,189,248,.18), transparent 60%),
        linear-gradient(180deg,#020617 0%,#0c2340 50%,#0c4a6e 100%);
    z-index:0;
    animation:bgPulse 4s ease-in-out infinite;
}
@keyframes bgPulse{
    0%,100%{ filter:brightness(1); }
    50%{ filter:brightness(1.06); }
}

/* ════════════════════════════════════════════
   CANVAS GELEMBUNG — semua animasi gelembung
   digambar di sini, gerakannya organik (goyang
   kiri-kanan + naik dengan kecepatan acak,
   bukan garis lurus monoton)
════════════════════════════════════════════ */
#bubbleCanvas{
    position:fixed;
    inset:0;
    z-index:10;
    width:100%;
    height:100%;
    display:block;
}

/* ════════════════════════════════════════════
   LOGO & BRAND — muncul di tengah, sedikit
   "mengambang" seperti kapal di air, lalu
   fade out total tanpa ada layer reveal di belakangnya
════════════════════════════════════════════ */
.wave-brand{
    position:fixed;
    inset:0;
    z-index:20;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    pointer-events:none;
    opacity:0;
    animation:brandIn .9s cubic-bezier(.34,1.4,.64,1) forwards .25s,
              brandOut .6s ease forwards 2.5s;
}
@keyframes brandIn{
    from{opacity:0;transform:translateY(18px) scale(.9);}
    to{opacity:1;transform:translateY(0) scale(1);}
}
@keyframes brandOut{
    to{ opacity:0; transform:translateY(-22px) scale(.95); }
}
.wave-brand .ship{
    font-size:50px;
    display:inline-block;
    animation:shipFloat 3s ease-in-out infinite;
    filter:drop-shadow(0 10px 22px rgba(56,189,248,.45));
}
@keyframes shipFloat{
    0%{   transform:translateY(0) rotate(-3deg); }
    28%{  transform:translateY(-9px) rotate(2deg); }
    52%{  transform:translateY(-3px) rotate(4deg); }
    78%{  transform:translateY(-11px) rotate(-1deg); }
    100%{ transform:translateY(0) rotate(-3deg); }
}
.wave-brand .brand-text{
    font-size:25px;
    font-weight:800;
    letter-spacing:5px;
    margin-top:14px;
    color:white;
    text-shadow:0 4px 22px rgba(0,0,0,.5);
}
.wave-brand .brand-greet{
    font-size:13px;
    color:#bae6fd;
    margin-top:7px;
    font-weight:500;
    text-shadow:0 2px 10px rgba(0,0,0,.4);
}

/* Titik loading kecil di bawah teks, goyang halus juga */
.wave-brand .dots{
    margin-top:18px;
    display:flex;
    gap:6px;
}
.wave-brand .dots span{
    width:6px; height:6px; border-radius:50%;
    background:#38bdf8;
    animation:dotFloat 1.3s ease-in-out infinite;
}
.wave-brand .dots span:nth-child(2){ animation-delay:.15s; }
.wave-brand .dots span:nth-child(3){ animation-delay:.3s; }
@keyframes dotFloat{
    0%,100%{ transform:translateY(0); opacity:.4; }
    50%{ transform:translateY(-5px); opacity:1; }
}
</style>
</head>
<body>

<div class="ocean-bg"></div>

<!-- ════ LOGO & BRAND ════ -->
<div class="wave-brand">
    <div class="ship">🚢</div>
    <div class="brand-text">NAVIRA</div>
    <div class="brand-greet">Selamat datang <?= htmlspecialchars($nama) ?> 🌊</div>
    <div class="dots"><span></span><span></span><span></span></div>
</div>

<!-- ════ CANVAS GELEMBUNG ════ -->
<canvas id="bubbleCanvas"></canvas>

<script>
// ══════════════════════════════════════════════════════
// EFEK GELEMBUNG REALISTIS
// - Tiap gelembung punya ukuran, kecepatan, goyangan,
//   dan timing kemunculan yang ACAK — bukan looping seragam.
// - Gelembung kecil naik cepat & lurus-ish.
// - Gelembung besar naik lambat & goyang lebih jelas (lebih berat/lambat merespon air).
// - Sesekali ada gelembung "pop" (pecah jadi partikel kecil) di tengah jalan, seperti aslinya.
// - Highlight cahaya di gelembung biar terlihat seperti kaca/air, bukan lingkaran flat.
// ══════════════════════════════════════════════════════

const canvas = document.getElementById('bubbleCanvas');
const ctx    = canvas.getContext('2d');

let W, H;
function resize(){
    W = canvas.width  = window.innerWidth;
    H = canvas.height = window.innerHeight;
}
resize();
window.addEventListener('resize', resize);

const START_TIME     = performance.now();
const TOTAL_DURATION = 3200; // total durasi splash sebelum redirect (ms)

function rand(min, max){ return Math.random() * (max - min) + min; }

// ── Buat 1 gelembung baru dengan properti acak ──
function spawnBubble(forceStart = false){
    const size = rand(4, 26);
    return {
        x: rand(0, W),
        y: forceStart ? rand(H * 0.4, H * 1.1) : H + rand(10, 80),
        size: size,
        baseSize: size,
        speed: rand(0.018, 0.055) * (30 / size),       // gelembung kecil lebih cepat
        wobbleAmp: rand(8, 34) * (size > 14 ? 1.3 : 0.8),
        wobbleFreq: rand(0.0012, 0.0035),
        wobbleSeed: rand(0, Math.PI * 2),
        opacityBase: rand(0.25, 0.6),
        willPop: Math.random() < 0.22,                  // ~22% gelembung akan "pecah"
        popAtY: rand(H * 0.15, H * 0.55),
        popped: false,
        popParticles: [],
        birth: performance.now() + rand(0, 200),
        hueShift: rand(-10, 15)
    };
}

let bubbles = [];
// Isi awal: sebagian sudah "di tengah jalan" supaya begitu splash muncul,
// langsung terasa ramai (tidak semua mulai dari 0 secara serempak)
for (let i = 0; i < 55; i++) {
    bubbles.push(spawnBubble(true));
}

// Spawn rate gelembung baru dari bawah selama splash masih "ramai" (sebelum akhir)
let lastSpawn = 0;
function maybeSpawn(now){
    const elapsed = now - START_TIME;
    if (elapsed > TOTAL_DURATION - 900) return; // berhenti spawn menjelang akhir
    if (now - lastSpawn > rand(40, 110)) {
        bubbles.push(spawnBubble(false));
        lastSpawn = now;
    }
}

function drawBubble(b, now){
    const age = now - b.birth;
    if (age < 0) return; // belum lahir

    // Posisi naik ke atas, dipercepat sedikit makin lama (seperti air mendorong)
    const riseProgress = age * b.speed;
    let y = b.y - riseProgress;

    // Goyangan horizontal organik — campuran 2 frekuensi biar tidak simetris
    const wobble1 = Math.sin(age * b.wobbleFreq + b.wobbleSeed) * b.wobbleAmp;
    const wobble2 = Math.sin(age * b.wobbleFreq * 2.3 + b.wobbleSeed * 1.7) * (b.wobbleAmp * 0.35);
    const x = b.x + wobble1 + wobble2;

    // ── Cek apakah gelembung ini "pop" (pecah) di titik tertentu ──
    if (b.willPop && !b.popped && y <= b.popAtY) {
        b.popped = true;
        const pcount = Math.floor(rand(4, 8));
        for (let i = 0; i < pcount; i++) {
            const angle = (Math.PI * 2 / pcount) * i + rand(-0.3, 0.3);
            b.popParticles.push({
                x: x, y: y,
                vx: Math.cos(angle) * rand(0.5, 1.8),
                vy: Math.sin(angle) * rand(0.5, 1.8) - 0.5,
                life: 1,
                size: rand(1.5, 3.5)
            });
        }
    }

    if (b.popped) {
        // Gambar partikel pecahan, lalu fade out & hilang
        b.popParticles.forEach(p => {
            p.x += p.vx;
            p.y += p.vy;
            p.vy += 0.03; // gravitasi ringan
            p.life -= 0.035;
            if (p.life > 0) {
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.size * p.life, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(186,230,253,${p.life * 0.7})`;
                ctx.fill();
            }
        });
        b.popParticles = b.popParticles.filter(p => p.life > 0);
        if (b.popParticles.length === 0) b.dead = true;
        return;
    }

    // Mati kalau sudah keluar atas layar
    if (y < -40) { b.dead = true; return; }

    // Ukuran sedikit berdenyut (bernapas), gelembung tidak statis kaku
    const pulse = 1 + Math.sin(age * 0.006 + b.wobbleSeed) * 0.06;
    const size = b.baseSize * pulse;

    // Fade in saat baru lahir, fade out saat hampir keluar atas
    let alpha = b.opacityBase;
    if (age < 250) alpha *= (age / 250);
    if (y < 60) alpha *= Math.max(0, y / 60);

    // ── Gambar gelembung dengan gradient radial supaya terlihat seperti kaca berisi udara ──
    const grad = ctx.createRadialGradient(
        x - size * 0.3, y - size * 0.3, size * 0.1,
        x, y, size
    );
    grad.addColorStop(0,   `rgba(255,255,255,${alpha * 0.9})`);
    grad.addColorStop(0.4, `rgba(186,230,253,${alpha * 0.45})`);
    grad.addColorStop(0.8, `rgba(125,211,252,${alpha * 0.18})`);
    grad.addColorStop(1,   `rgba(56,189,248,${alpha * 0.05})`);

    ctx.beginPath();
    ctx.arc(x, y, size, 0, Math.PI * 2);
    ctx.fillStyle = grad;
    ctx.fill();

    // Outline tipis
    ctx.beginPath();
    ctx.arc(x, y, size, 0, Math.PI * 2);
    ctx.strokeStyle = `rgba(255,255,255,${alpha * 0.5})`;
    ctx.lineWidth = 1;
    ctx.stroke();

    // Highlight kecil (kilau cahaya di pojok atas-kiri gelembung)
    ctx.beginPath();
    ctx.arc(x - size * 0.32, y - size * 0.32, size * 0.22, 0, Math.PI * 2);
    ctx.fillStyle = `rgba(255,255,255,${alpha * 0.85})`;
    ctx.fill();
}

function animate(now){
    ctx.clearRect(0, 0, W, H);

    maybeSpawn(now);

    bubbles.forEach(b => drawBubble(b, now));
    bubbles = bubbles.filter(b => !b.dead);

    const elapsed = now - START_TIME;
    if (elapsed < TOTAL_DURATION + 200) {
        requestAnimationFrame(animate);
    }
}
requestAnimationFrame(animate);

// ── Redirect setelah animasi selesai ──
setTimeout(() => {
    window.location.href = "<?= htmlspecialchars($redirect_to, ENT_QUOTES) ?>";
}, TOTAL_DURATION);
</script>

</body>
</html>