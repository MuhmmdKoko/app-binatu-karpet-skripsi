<?php
// Cek apakah user adalah admin
if ($_SESSION['role'] != "Admin") {
    echo "<script>alert('Anda tidak memiliki akses ke halaman ini!');</script>";
    echo "<script>window.location.href = '?page=dashboard_read';</script>";
    exit;
}

include "../template/header.php";

// Ambil data pengguna yang akan diedit
$id = mysqli_real_escape_string($konek, $_GET['id']);
$query = mysqli_query($konek, "SELECT * FROM pengguna WHERE id_pengguna = '$id'");
$data = mysqli_fetch_array($query);

if(!$data) {
    echo "<script>alert('Data pengguna tidak ditemukan!');</script>";
    echo "<script>window.location.href = '?page=pengguna_read';</script>";
    exit;
}

if(isset($_POST['submit'])){
    $nama_lengkap = mysqli_real_escape_string($konek, $_POST['nama_lengkap']);
    $username = mysqli_real_escape_string($konek, $_POST['username']);
    $role = mysqli_real_escape_string($konek, $_POST['role']);
    $email = mysqli_real_escape_string($konek, $_POST['email_internal']);
    $telepon = mysqli_real_escape_string($konek, $_POST['nomor_telepon_internal']);
    $status = isset($_POST['status_aktif']) ? 1 : 0;
    
    // Cek username sudah ada atau belum (kecuali username saat ini)
    $cek = mysqli_query($konek, "SELECT username FROM pengguna WHERE username = '$username' AND id_pengguna != '$id'");
    if(mysqli_num_rows($cek) > 0){
        echo "<script>alert('Username sudah digunakan!');</script>";
    } else {
        $sql = "UPDATE pengguna SET 
                nama_lengkap = '$nama_lengkap',
                username = '$username',
                role = '$role',
                email_internal = '$email',
                nomor_telepon_internal = '$telepon',
                status_aktif = $status";

        // Jika password diisi, update password
        if(!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql .= ", password_hash = '$password'";
        }

        $sql .= " WHERE id_pengguna = '$id'";
        
        if(mysqli_query($konek, $sql)){
            echo "<script>alert('Data berhasil diupdate!');</script>";
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
            <h5 class="card-title fw-semibold mb-4">Edit Data Pengguna</h5>
            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" name="nama_lengkap" value="<?= htmlspecialchars($data['nama_lengkap']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($data['username']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password (Kosongkan jika tidak ingin mengubah)</label>
                            <input type="password" class="form-control" name="password">
                            <small class="text-muted">Biarkan kosong jika tidak ingin mengubah password</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" required>
                                <option value="">Pilih Role</option>
                                <option value="Admin" <?= $data['role'] == 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="Karyawan" <?= $data['role'] == 'Karyawan' ? 'selected' : ''; ?>>Karyawan</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Internal</label>
                            <input type="email" class="form-control" name="email_internal" value="<?= htmlspecialchars($data['email_internal']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nomor Telepon Internal</label>
                            <input type="text" class="form-control" name="nomor_telepon_internal" value="<?= htmlspecialchars($data['nomor_telepon_internal']); ?>">
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="status_aktif" <?= $data['status_aktif'] == 1 ? 'checked' : ''; ?>>
                                <label class="form-check-label">
                                    Status Aktif
                                </label>
                            </div>
                        </div>
                        <button type="submit" name="submit" class="btn btn-primary">Update</button>
                        <a href="?page=pengguna_read" class="btn btn-danger">Kembali</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div> 