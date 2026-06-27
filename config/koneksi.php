<?php
$conn = mysqli_connect("127.0.0.1", "root", "", "tiketkapal", 3306);

if (!$conn) {
    die(
        "Koneksi database gagal: " . mysqli_connect_error()
        . "<br><br>Pastikan <b>MySQL</b> di XAMPP Control Panel sudah di-<b>Start</b> (status hijau)."
    );
}
