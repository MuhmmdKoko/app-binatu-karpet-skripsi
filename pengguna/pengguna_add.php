<?php
// Cek apakah user adalah admin
if ($_SESSION['role'] != "Admin") {
    echo "<script>alert('Anda tidak memiliki akses ke halaman ini!');</script>";
    echo "<script>window.location.href = '?page=dashboard_read';</script>";
    exit;
}

include "../template/header.php";

if(isset($_POST['submit'])){
    $nama_lengkap = mysqli_real_escape_string($konek, $_POST['nama_lengkap']);
    $username = mysqli_real_escape_string($konek, $_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = mysqli_real_escape_string($konek, $_POST['role']);
    $email = mysqli_real_escape_string($konek, $_POST['email_internal']);
    $telepon = mysqli_real_escape_string($konek, $_POST['nomor_telepon_internal']);
    $status = isset($_POST['status_aktif']) ? 1 : 0;
    
    // Cek username sudah ada atau belum
    $cek = mysqli_query($konek, "SELECT username FROM pengguna WHERE username = '$username'");
    if(mysqli_num_rows($cek) > 0){
        echo "<script>alert('Username sudah digunakan!');</script>";
    } else {
        $sql = "INSERT INTO pengguna (nama_lengkap, username, password_hash, role, email_internal, nomor_telepon_internal, status_aktif) 
                VALUES ('$nama_lengkap', '$username', '$password', '$role', '$email', '$telepon', $status)";
        
        if(mysqli_query($konek, $sql)){
            echo "<script>alert('Data berhasil ditambahkan!');</script>";
            echo "<script>window.location.href = '?page=pengguna_read';</script>";
        } else {
            echo "<script>alert('Terjadi kesalahan: " . mysqli_error($konek) . "');</script>";
        }
    }
}
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title fw-semibold mb-4">Tambah Data Pengguna</h5>
            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" name="nama_lengkap" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" required>
                                <option value="">Pilih Role</option>
                                <option value="Admin">Admin</option>
                                <option value="Karyawan">Karyawan</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Internal</label>
                            <input type="email" class="form-control" name="email_internal">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nomor Telepon Internal</label>
                            <input type="text" class="form-control" name="nomor_telepon_internal">
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="status_aktif" checked>
                                <label class="form-check-label">
                                    Status Aktif
                                </label>
                            </div>
                        </div>
                        <button type="submit" name="submit" class="btn btn-primary">Simpan</button>
                        <a href="?page=pengguna_read" class="btn btn-danger">Kembali</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>