<?php
include('auth.php');

/*
 * Alur scan tiket:
 * 1. Scan E-Ticket → check-in → download/cetak Tiket A & B
 * 2. Scan Tiket A → Verifikasi Berhasil
 * 3. Scan Tiket B → Checkout Berhasil
 */
?>
<!DOCTYPE html>
<html>
<head>
<title>Scan QR - Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://unpkg.com/html5-qrcode"></script>
<style>
body{
    margin:0;
    font-family:'Segoe UI', sans-serif;
    background: linear-gradient(135deg,#020617,#0f172a);
    color:white;
    text-align:center;
}
.header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:15px 20px;
    flex-wrap:wrap;
    gap:10px;
}
.back-btn{
    background: rgba(255,255,255,0.1);
    border:none;
    padding:8px 14px;
    border-radius:10px;
    color:white;
    cursor:pointer;
}
.title{ font-weight:600; font-size:18px; }
.scanner-card{
    width:min(380px, 92vw);
    margin:12px auto;
    padding:15px;
    border-radius:20px;
    background: rgba(255,255,255,0.05);
    box-shadow:0 10px 40px rgba(0,0,0,0.6);
}
#reader{ width:100%; border-radius:15px; overflow:hidden; }
@media (max-width: 480px) {
    .title { font-size: 16px; }
    .header { padding: 12px 14px; }
    .scanner-card { width: 94vw; padding: 12px; }
}
.overlay{
    position:fixed; top:0; left:0; width:100%; height:100%;
    display:none; align-items:center; justify-content:center;
    font-size:28px; font-weight:bold; z-index:999;
    backdrop-filter: blur(8px);
}
.success{ background:rgba(34,197,94,0.2); color:#22c55e; }
.info{ font-size:13px; color:#94a3b8; margin-top:10px; line-height:1.5; }
.manual-box{
    width:min(380px, 92vw);
    margin:12px auto 20px;
    padding:14px;
    border-radius:16px;
    background:rgba(255,255,255,0.06);
    text-align:left;
}
.manual-box input{
    width:100%;
    border:none;
    border-radius:10px;
    padding:10px 12px;
    margin:8px 0;
    font-size:14px;
}
.manual-box button{
    width:100%;
    border:none;
    border-radius:10px;
    padding:10px;
    font-weight:700;
    background:#0ea5e9;
    color:#fff;
}
</style>
</head>
<body>

<div class="header">
    <button class="back-btn" onclick="window.location.href='dashboard.php'">← Dashboard</button>
    <div class="title">📷 Scan Tiket</div>
    <div style="width:80px;"></div>
</div>

<div class="scanner-card">
    <div id="reader"></div>
    <div class="info">Arahkan kamera ke QR pada struk yang sudah di-download/cetak</div>
</div>

<div class="manual-box">
    <div style="font-size:13px;color:#cbd5e1;margin-bottom:4px;">⌨️ Input manual (jika kamera tidak terbaca)</div>
    <input type="text" id="manualKode" placeholder="Contoh: TKT74EDF3D0|A|1">
    <button type="button" onclick="goManual()">Proses Scan</button>
</div>

<div id="overlay" class="overlay"></div>

<script>
let scanner;
let scanned = false;

function beep(){
    try {
        let ctx = new (window.AudioContext || window.webkitAudioContext)();
        let osc = ctx.createOscillator();
        osc.type = "sine";
        osc.frequency.value = 800;
        osc.connect(ctx.destination);
        osc.start();
        setTimeout(()=>osc.stop(), 120);
    } catch(e){}
}

function vibrate(){
    if(navigator.vibrate) navigator.vibrate(100);
}

function showOverlay(text){
    let el = document.getElementById("overlay");
    el.className = "overlay success";
    el.innerHTML = text;
    el.style.display = "flex";
    setTimeout(()=>{ el.style.display = "none"; }, 1200);
}

function buildValidasiLink(decodedText) {
    let t = (decodedText || '').trim();
    if (!t) return null;

    // URL validasi (jika QR lama berisi link)
    if (/validasi\.php/i.test(t)) {
        if (/^https?:\/\//i.test(t)) return t;
        const base = window.location.href.replace(/[^/]*$/, '');
        return base + t.replace(/^\.?\//, '');
    }

    // Format utama: KODE|A|1 atau KODE|B|1
    let pipe = t.match(/^(.+)\|([AB])\|(\d+)$/i);
    if (pipe) {
        return 'validasi.php?kode=' + encodeURIComponent(pipe[1].trim())
            + '&tipe=' + encodeURIComponent(pipe[2].toUpperCase())
            + '&pax=' + encodeURIComponent(pipe[3]);
    }

    // E-Ticket (hanya kode booking)
    return 'validasi.php?kode=' + encodeURIComponent(t);
}

function goToValidasi(decodedText) {
    const target = buildValidasiLink(decodedText);
    if (!target) {
        alert('Kode tiket kosong!');
        return;
    }
    window.location.href = target;
}

function onScanSuccess(decodedText) {
    if (scanned) return;
    scanned = true;
    beep();
    vibrate();
    showOverlay("✔ Berhasil Scan");

    const target = buildValidasiLink(decodedText);
    const go = () => { window.location.href = target; };

    if (scanner && scanner.clear) {
        scanner.clear().then(go).catch(go);
    } else {
        setTimeout(go, 400);
    }
}

function goManual() {
    const val = document.getElementById('manualKode').value.trim();
    if (!val) { alert('Masukkan kode QR struk!'); return; }
    goToValidasi(val);
}

function startScanner(){
    scanner = new Html5QrcodeScanner("reader", {
        fps: 15,
        qrbox: { width: 250, height: 250 },
        rememberLastUsedCamera: true
    });
    scanner.render(onScanSuccess);
}

window.onload = () => {
    if (!window.location.hash) {
        window.location = window.location + '#loaded';
        window.location.reload();
    } else {
        startScanner();
    }
};

window.onbeforeunload = () => {
    if (scanner) scanner.clear().catch(()=>{});
};
</script>
</body>
</html>
