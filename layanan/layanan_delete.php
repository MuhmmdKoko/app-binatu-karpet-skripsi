<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin','Karyawan'])) {
    echo '<script>alert("Akses ditolak");window.location="index.php?page=dashboard_read";</script>';
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo '<script>alert("ID layanan tidak valid.");window.location="?page=layanan_read";</script>';
    exit;
}

// Cek apakah layanan terikat dengan detail pesanan
$check_query = "SELECT COUNT(*) as count FROM detail_pesanan WHERE id_layanan = $id";
$check_result = mysqli_query($konek, $check_query);
$check_data = mysqli_fetch_assoc($check_result);

if ($check_data['count'] > 0) {
    echo '<script>alert("Layanan tidak dapat dihapus karena sudah digunakan dalam pesanan. Harap hapus pesanan terkait terlebih dahulu.");window.location="?page=layanan_read";</script>';
    exit;
}

$query_delete = "DELETE FROM layanan WHERE id_layanan = $id";
if (mysqli_query($konek, $query_delete)) {
    echo '<script>alert("Layanan berhasil dihapus.");window.location="?page=layanan_read";</script>';
} else {
    echo '<script>alert("Gagal menghapus layanan: '.mysqli_error($konek).'");window.location="?page=layanan_read";</script>';
}
exit;
?>
