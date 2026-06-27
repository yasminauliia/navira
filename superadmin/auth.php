<?php
if(session_status() === PHP_SESSION_NONE){
    session_start();
}

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'super_admin'){
    header("Location: ../user/login.php");
    exit;
}
?>