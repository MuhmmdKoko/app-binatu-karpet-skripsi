<?php

$host = "localhost";
$port = "3307"; // Sesuaikan dengan port MySQL Anda
$username = "root";
$password = "";
$database = "binatu_karpetv2";

// Membuat koneksi
$konek = mysqli_connect($host, $username, $password, $database, $port);

// Cek koneksi
if (!$konek) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Set karakter encoding
mysqli_set_charset($konek, "utf8mb4");

// Set timezone
date_default_timezone_set('Asia/Jakarta');
?>