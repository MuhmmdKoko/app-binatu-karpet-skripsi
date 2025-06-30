<?php
// Cek apakah user adalah admin
if ($_SESSION['role'] != "Admin") {
    echo "<script>alert('Anda tidak memiliki akses ke halaman ini!');</script>";
    echo "<script>window.location.href = '?page=dashboard_read';</script>";
    exit;
}

$id = mysqli_real_escape_string($konek, $_GET['id']);

// Cek apakah pengguna yang akan dihapus adalah diri sendiri
if($id == $_SESSION['id_pengguna']) {
    echo "<script>alert('Anda tidak dapat menghapus akun Anda sendiri!');</script>";
    echo "<script>window.location.href = '?page=pengguna_read';</script>";
    exit;
}

// Cek apakah pengguna memiliki transaksi terkait
$cek_pesanan = mysqli_query($konek, "SELECT id_pengguna_penerima FROM pesanan WHERE id_pengguna_penerima = '$id' LIMIT 1");
$cek_status = mysqli_query($konek, "SELECT id_pengguna_update FROM status_proses WHERE id_pengguna_update = '$id' LIMIT 1");

if(mysqli_num_rows($cek_pesanan) > 0 || mysqli_num_rows($cek_status) > 0) {
    echo "<script>alert('Pengguna ini tidak dapat dihapus karena memiliki data transaksi terkait!');</script>";
    echo "<script>window.location.href = '?page=pengguna_read';</script>";
    exit;
}

// Hapus pengguna
$query = mysqli_query($konek, "DELETE FROM pengguna WHERE id_pengguna = '$id'");

if($query) {
    echo "<script>alert('Data pengguna berhasil dihapus!');</script>";
} else {
    echo "<script>alert('Terjadi kesalahan: " . mysqli_error($konek) . "');</script>";
}

echo "<script>window.location.href = '?page=pengguna_read';</script>";
?> 