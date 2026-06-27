<?php
include('auth.php');
include('../config/koneksi.php');

$msg=""; $error="";

// TAMBAH
if(isset($_POST['tambah_admin'])){
    $nama  = mysqli_real_escape_string($conn,$_POST['nama']);
    $email = mysqli_real_escape_string($conn,$_POST['email']);
    $pass  = $_POST['password'];

    if(strlen($pass)<6){
        $error="Password minimal 6 karakter!";
    }else{
        $cek=mysqli_query($conn,"SELECT id FROM users WHERE email='$email'");
        if(mysqli_num_rows($cek)>0){
            $error="Email sudah dipakai!";
        }else{
            $hash=password_hash($pass,PASSWORD_DEFAULT);
            mysqli_query($conn,"INSERT INTO users(nama,email,password,role)
            VALUES('$nama','$email','$hash','admin')");
            header("Location: kelola_admin.php?status=added"); exit;
        }
    }
}

// EDIT
if(isset($_POST['edit_admin'])){
    $id=(int)$_POST['edit_id'];
    $nama=$_POST['edit_nama'];
    $email=$_POST['edit_email'];

    mysqli_query($conn,"UPDATE users SET nama='$nama',email='$email' WHERE id='$id'");
    header("Location: kelola_admin.php?status=updated"); exit;
}

// HAPUS
if(isset($_POST['hapus_admin'])){
    $id=(int)$_POST['hapus_id'];
    mysqli_query($conn,"DELETE FROM users WHERE id='$id'");
    header("Location: kelola_admin.php?status=deleted"); exit;
}

// NOTIF
if(isset($_GET['status'])){
    if($_GET['status']=='added') $msg="Admin ditambahkan!";
    if($_GET['status']=='updated') $msg="Admin diupdate!";
    if($_GET['status']=='deleted') $msg="Admin dihapus!";
}

$data_admin=mysqli_query($conn,"SELECT * FROM users WHERE role='admin' ORDER BY id DESC");
$current = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html>
<head>
<title>Kelola Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="../assets/css/sidebar.css" rel="stylesheet">
<link href="../assets/css/responsive.css" rel="stylesheet">

<style>
body{
    margin:0;
    font-family:'Poppins','Segoe UI',sans-serif;
    background: linear-gradient(135deg,#020617,#0f172a,#1e3a8a);
    color:white;
    overflow-x:hidden;
}

.page-header { margin-bottom: 24px; }
.page-header .title { font-size: 20px; font-weight: 700; }
.page-header .sub { font-size: 12px; color: #475569; margin-top: 4px; }

.card{
    background:#0f172a;
    border: 1px solid rgba(255,255,255,.06);
    padding:20px;
    border-radius:16px;
    margin-bottom:20px;
}

.form-grid{
    display:grid;
    grid-template-columns:1fr 1fr 1fr auto;
    gap:10px;
}

input{
    padding:10px;
    border:none;
    border-radius:8px;
}

button{
    padding:10px;
    border:none;
    border-radius:8px;
    cursor:pointer;
}

.btn-primary{background:#2563eb;color:white;}
.btn-danger{background:#ef4444;color:white;}
.btn-warning{background:#f59e0b;color:white;}

table{
    width:100%;
    border-collapse:collapse;
    background:#020617;
}

th,td{
    padding:12px;
    text-align:center;
}

th{background:#1e293b;}
td{color:white;}

.modal{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.7);
    justify-content:center;
    align-items:center;
}

.modal-box{
    background:#1e293b;
    padding:20px;
    border-radius:12px;
    width:320px;
    max-width:92vw;
}

.tbl-scroll {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body class="has-mobile-nav">

<?php include('sidebar.php'); ?>

<div class="main-wrap">

<div class="page-header">
    <div class="title">🛡️ Kelola Admin</div>
    <div class="sub">Tambah dan kelola akun admin pelabuhan</div>
</div>

<?php if($msg): ?><div style="color:lightgreen"><?= $msg ?></div><?php endif; ?>
<?php if($error): ?><div style="color:red"><?= $error ?></div><?php endif; ?>

<div class="card">
<form method="POST" class="form-grid">
<input name="nama" placeholder="Nama" required>
<input name="email" placeholder="Email" required>
<input name="password" type="password" placeholder="Password" required>
<button name="tambah_admin" class="btn-primary">Tambah</button>
</form>
</div>

<div class="card tbl-scroll">
<table>
<tr><th>No</th><th>Nama</th><th>Email</th><th>Aksi</th></tr>

<?php $no=1; while($a=mysqli_fetch_assoc($data_admin)){ ?>
<tr>
<td><?= $no++ ?></td>
<td><?= $a['nama'] ?></td>
<td><?= $a['email'] ?></td>
<td>
<button class="btn-warning" onclick="openEdit(<?= $a['id'] ?>,'<?= $a['nama'] ?>','<?= $a['email'] ?>')">Edit</button>
<button class="btn-danger" onclick="openDelete(<?= $a['id'] ?>)">Hapus</button>
</td>
</tr>
<?php } ?>

</table>
</div>

</div>

<!-- MODAL -->
<div id="editModal" class="modal">
<div class="modal-box">
<form method="POST">
<input type="hidden" name="edit_id" id="editId">
<input name="edit_nama" id="editNama"><br><br>
<input name="edit_email" id="editEmail"><br><br>
<button name="edit_admin" class="btn-primary">Simpan</button>
<button type="button" onclick="closeEdit()">Batal</button>
</form>
</div>
</div>

<div id="deleteModal" class="modal">
<div class="modal-box">
<form method="POST">
<input type="hidden" name="hapus_id" id="hapusId">
<p>Hapus admin ini?</p>
<button name="hapus_admin" class="btn-danger">Ya</button>
</form>
<button onclick="closeDelete()">Batal</button>
</div>
</div>

<script>
function openEdit(id,nama,email){
    document.getElementById('editModal').style.display='flex';
    editId.value=id;
    editNama.value=nama;
    editEmail.value=email;
}
function closeEdit(){ editModal.style.display='none'; }

function openDelete(id){
    document.getElementById('deleteModal').style.display='flex';
    hapusId.value=id;
}
function closeDelete(){ deleteModal.style.display='none'; }
</script>
<script src="../assets/js/mobile-nav.js"></script>

</body>
</html>