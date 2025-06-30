<?php
    include "../pengaturan/koneksi.php";
    if(isset($_POST['daftar'])){
        $npm = $_POST['npm'];
        $nama_lengkap = $_POST['nama_lengkap'];
        $prodi = $_POST ['prodi'];
        $no_telp = $_POST ['no_telp'];
        $password = md5 ($_POST ['password']);
        $cek_npm = mysqli_query ($konek, "select * from pengguna where npm='$npm'");
        if(mysqli_num_rows($cek_npm) > 0){
            echo "<script>alert('NPM sudah terdaftar di sistem');</script>";
            echo "<meta http-equiv='refresh' content='0; url=register_view.php'>"; // halaman diarahkan ke index.php?page=kategori_read
        }else{
            $exe = mysqli_query($konek, "insert into pengguna values (null, '$npm', '$nama_lengkap', '$prodi', '$no_telp', '$npm', '$password', 'mahasiswa', 'diajukan')");
            if($exe){
                echo "<script>alert('Pendaftaran akun kamu berhasil. Tunggu proses verifikasi oleh operator untuk menyetujui pendaftaran akun kamu')</script>";
                echo "<meta http-equiv='refresh' content='0; url=register_view.php'>"; // halaman diarahkan
            }else{
                echo "<script>alert('Gagal daftar akun')</script>";
                echo "<meta http-equiv='refresh' content='0; url=register_view.php'>"; // halaman diarahkan
            }
        }
    }