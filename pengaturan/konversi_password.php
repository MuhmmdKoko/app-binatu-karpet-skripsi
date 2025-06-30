<?php
include "koneksi.php";

// Data password default untuk setiap user
$default_passwords = [
    'admin' => 'admin123',
    'rina_kasir' => 'rina123',
    'agus_lapangan' => 'agus123'
];

// Ambil semua pengguna
$query = mysqli_query($konek, "SELECT id_pengguna, username FROM pengguna");

$berhasil = 0;
$gagal = 0;

while($user = mysqli_fetch_array($query)) {
    $username = $user['username'];
    $id_pengguna = $user['id_pengguna'];
    
    // Ambil password default berdasarkan username
    $password = isset($default_passwords[$username]) ? $default_passwords[$username] : 'password123';
    
    // Generate password hash baru menggunakan bcrypt
    $new_hash = password_hash($password, PASSWORD_BCRYPT);
    
    // Update password di database
    $update = mysqli_query($konek, "UPDATE pengguna SET password_hash = '$new_hash' WHERE id_pengguna = $id_pengguna");
    
    if($update) {
        $berhasil++;
        echo "Berhasil mengupdate password untuk user: $username<br>";
        echo "Password baru: $password<br><br>";
    } else {
        $gagal++;
        echo "Gagal mengupdate password untuk user: $username<br>";
        echo "Error: " . mysqli_error($konek) . "<br><br>";
    }
}

echo "<h3>Hasil Konversi Password:</h3>";
echo "Berhasil: $berhasil user<br>";
echo "Gagal: $gagal user<br><br>";

echo "<h3>Informasi Login:</h3>";
echo "<pre>";
foreach($default_passwords as $username => $password) {
    echo "Username: $username\nPassword: $password\n\n";
}
echo "Untuk user lain:\nPassword default: password123\n";
echo "</pre>";

echo "<br><a href='../login/login_view.php'>Kembali ke Login</a>";
?> 