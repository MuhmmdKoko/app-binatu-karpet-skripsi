<?php
session_start();
include "../pengaturan/koneksi.php";

if(isset($_POST['login'])) {
    $username = mysqli_real_escape_string($konek, $_POST['username']);
    $password = $_POST['password'];

    $query = mysqli_query($konek, "SELECT * FROM pengguna WHERE username = '$username' AND status_aktif = 1");
    $data = mysqli_fetch_array($query);

    if($data) {
        if(password_verify($password, $data['password_hash'])) {
            // Set session
            $_SESSION['id_pengguna'] = $data['id_pengguna'];
            $_SESSION['username'] = $data['username'];
            $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
            $_SESSION['role'] = $data['role'];
            
            // Update last login
            $id_pengguna = $data['id_pengguna'];
            mysqli_query($konek, "UPDATE pengguna SET last_login = NOW() WHERE id_pengguna = '$id_pengguna'");
            
            header("location:../index.php?page=dashboard_read");
        } else {
            echo "<script>alert('Password salah!');</script>";
            echo "<script>window.location.href='login_view.php';</script>";
        }
    } else {
        echo "<script>alert('Username tidak ditemukan atau akun tidak aktif!');</script>";
        echo "<script>window.location.href='login_view.php';</script>";
    }
}
?>