<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin','Karyawan'])) {
    echo '<script>alert("Akses ditolak");window.location="index.php?page=dashboard_read";</script>';
    exit;
}
include "pengaturan/koneksi.php";
$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
    mysqli_query($konek, "DELETE FROM promosi WHERE id_promosi=$id");
}
echo '<script>alert("Promosi berhasil dihapus.");window.location="?page=promosi_read";</script>';
exit;
