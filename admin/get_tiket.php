<?php
// ═══════════════════════════════════════════════════════════════
// get_tiket.php  —  Dipanggil AJAX setiap 3 detik oleh dashboard
// Return: HTML <tr> rows untuk tabel data tiket
// ═══════════════════════════════════════════════════════════════

include('auth.php');
include('../config/koneksi.php');

// Ambil 100 tiket terbaru berdasarkan tanggal + waktu insert
$sql = "
    SELECT
        t.kode_booking,
        COALESCE(u.nama, 'N/A') AS nama_user,
        COALESCE(a.nama_pelabuhan, '-') AS asal,
        COALESCE(b.nama_pelabuhan, '-') AS tujuan,
        t.tanggal,
        t.layanan,
        COALESCE(t.total_penumpang, 0) AS total_penumpang,
        COALESCE(t.total_harga, 0)     AS total_harga,
        t.status
    FROM tickets t
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN pelabuhan a ON a.id = t.asal_id
    LEFT JOIN pelabuhan b ON b.id = t.tujuan_id
    ORDER BY t.tanggal DESC, t.id_ticket DESC
    LIMIT 100
";

$res = mysqli_query($conn, $sql);

if (!$res || mysqli_num_rows($res) === 0) {
    echo '<tr><td colspan="7">
            <div style="text-align:center;padding:32px 20px;color:#334155;">
                <div style="font-size:32px;margin-bottom:8px;">🎫</div>
                Belum ada data tiket
            </div>
          </td></tr>';
    exit;
}

$bulan = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];

function tglPendek(string $d, array $bl): string {
    if (!$d || $d === '0000-00-00') return '—';
    [$y, $m, $g] = explode('-', $d);
    return (int)$g . ' ' . $bl[(int)$m] . ' ' . $y;
}

while ($row = mysqli_fetch_assoc($res)):
    $kode   = htmlspecialchars($row['kode_booking']);
    $nama   = htmlspecialchars($row['nama_user']);
    $rute   = htmlspecialchars($row['asal']) . ' → ' . htmlspecialchars($row['tujuan']);
    $tgl    = tglPendek($row['tanggal'], $bulan);
    $lay    = ucfirst(strtolower(htmlspecialchars($row['layanan'] ?? '')));
    $pax    = (int)$row['total_penumpang'];
    $harga  = 'Rp ' . number_format((int)$row['total_harga'], 0, ',', '.');
    $status = strtoupper(trim($row['status'] ?? ''));
?>
<tr>
    <td>
        <span style="font-weight:700;color:white;font-size:11px;letter-spacing:0.5px;
                     font-variant-numeric:tabular-nums;">
            <?= $kode ?>
        </span>
    </td>
    <td style="color:#cbd5e1;"><?= $nama ?></td>
    <td style="color:#94a3b8;font-size:12px;"><?= $rute ?></td>
    <td style="color:#64748b;font-size:12px;"><?= $tgl ?></td>
    <td>
        <span style="background:rgba(56,189,248,0.1);border:1px solid rgba(56,189,248,0.15);
                     color:#38bdf8;border-radius:20px;padding:2px 10px;font-size:10px;font-weight:700;">
            <?= $lay ?>
        </span>
    </td>
    <td style="text-align:center;">
        <span style="background:rgba(56,189,248,0.07);border:1px solid rgba(56,189,248,0.15);
                     color:#7dd3fc;border-radius:20px;padding:2px 10px;font-size:11px;font-weight:700;">
            👥 <?= $pax ?> org
        </span>
    </td>
    <td style="color:#38bdf8;font-size:12px;font-weight:600;"><?= $harga ?></td>
    <td>
        <?php if ($status === 'DIGUNAKAN'): ?>
        <span class="badge-used">✅ Digunakan</span>
        <?php else: ?>
        <span class="badge-new">🟢 Aktif</span>
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>