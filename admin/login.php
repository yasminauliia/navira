<?php
session_start();
include('../config/koneksi.php');

if(isset($_POST['login'])){
    $email = $_POST['email'];
    $pass = $_POST['password'];

    $q = mysqli_query($conn,"SELECT * FROM users WHERE email='$email' AND role='admin'");
    $d = mysqli_fetch_assoc($q);

    if($d && password_verify($pass,$d['password'])){
        $_SESSION['user_id'] = $d['id'];
        $_SESSION['role'] = $d['role'];

        header("Location: dashboard.php");
    }else{
        echo "Login admin gagal";
    }
}
?>

<form method="POST">
<input name="email"><br>
<input type="password" name="password"><br>
<button name="login">Login Admin</button>
</form>