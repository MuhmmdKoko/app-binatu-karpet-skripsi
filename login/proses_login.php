<?php
session_start();
include "../pengaturan/koneksi.php";

if(isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 1. Gunakan prepared statement untuk SELECT (mencegah SQL Injection)
    $stmt = mysqli_prepare($konek, "SELECT id_pengguna, username, nama_lengkap, role, password_hash FROM pengguna WHERE username = ? AND status_aktif = 1");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_array($result, MYSQLI_ASSOC);

    if($data) {
        // 2. Verifikasi password
        if(password_verify($password, $data['password_hash'])) {
            // Set session
            $_SESSION['id_pengguna'] = $data['id_pengguna'];
            $_SESSION['username'] = $data['username'];
            $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
            $_SESSION['role'] = $data['role'];
            
            // 3. Update last login menggunakan prepared statement
            $id_pengguna = $data['id_pengguna'];
            $update_stmt = mysqli_prepare($konek, "UPDATE pengguna SET last_login = NOW() WHERE id_pengguna = ?");
            mysqli_stmt_bind_param($update_stmt, "i", $id_pengguna);
            mysqli_stmt_execute($update_stmt);
            
            header("location:../index.php?page=dashboard_read");
            exit(); // Tambahkan exit setelah redirect
        } else {
            // Gabungkan javascript untuk efisiensi
            echo "<script>alert('Password salah!'); window.location.href='login_view.php';</script>";
        }
    } else {
        // Gabungkan javascript untuk efisiensi
        echo "<script>alert('Username tidak ditemukan atau akun tidak aktif!'); window.location.href='login_view.php';</script>";
    }
}
?>