<?php
if (isset($_GET['id'])){
    $id = $_GET['id'];
    $status = $_GET['status'];
    $exe = mysqli_query($konek, "update pengguna set status_akun='$status' where id = '$id'");
    if($exe){
        echo "<script>alert('Proses verifikasi akun berhasil, status akun pengguna berhasil dirubah menjadi $status');</script>";
        echo "<meta http-equiv='refresh' content='0, url=s?page=pengguna_read'>";
    }else{
        echo "<script>alert('Proses verifikasi akun gagal diperbaharui');</script>";
        echo mysqli_error($konek);
    }
}