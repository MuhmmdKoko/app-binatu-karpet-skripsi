<?php
session_start(); //memulai fungsi session
if(!isset($_SESSION['id_pengguna'])){ //jika session id belum di set
    header ('location:login/login_view.php'); //arahkan halaman ke login_view.php di folder login
}
include "pengaturan/koneksi.php"; //memanggil file koneksi di folder template
include "template/sidebar.php"; //memanggil file koneksi di folder template
include "template/header.php"; //memanggil file koneksi di folder template
$page = $_GET['page'];
switch($page) {
    case "dashboard_read":
        include "template/dashboard.php";
        break;
    case "pelanggan_read":
        include "pelanggan/pelanggan_read.php";
        break;
    case "pelanggan_tambah":
        include "pelanggan/pelanggan_tambah.php";
        break;
    case "pelanggan_edit":
        include "pelanggan/pelanggan_edit.php";
        break;
    case "pelanggan_detail":
        include "pelanggan/pelanggan_detail.php";
        break;
    case "pesanan_read":
        include "pesanan/pesanan_read.php";
        break;
    case "pesanan_tambah":
        include "pesanan/pesanan_tambah.php";
        break;
    case "pesanan_edit":
        include "pesanan/pesanan_edit.php";
        break;
    case "pesanan_detail":
        include "pesanan/pesanan_detail.php";
        break;
    case "pesanan_cetak_nota":
        include "pesanan/pesanan_cetak_nota.php";
        break;
    case "pesanan_status_proses":
        include "pesanan/pesanan_status_proses.php";
        break;
    case "pesanan_pembayaran":
        include "pesanan/pesanan_pembayaran.php";
        break;
    case "layanan_read":
        include "layanan/layanan_read.php";
        break;
    case "layanan_tambah":
        include "layanan/layanan_tambah.php";
        break;
    case "layanan_edit":
        include "layanan/layanan_edit.php";
        break;
    case "layanan_delete":
        include "layanan/layanan_delete.php";
        break;
    case "promosi_read":
        include "promosi/promosi_read.php";
        break;
    case "promosi_tambah":
        include "promosi/promosi_tambah.php";
        break;
    case "promosi_edit":
        include "promosi/promosi_edit.php";
        break;
    case "promosi_delete":
        include "promosi/promosi_delete.php";
        break;
    case "promosi_broadcast_konfirmasi":
        include "promosi/promosi_broadcast_konfirmasi.php";
        break;
    case "promosi_kirim_proses":
        include "promosi/promosi_kirim_proses.php";
        break;
    case "barang_add";
        include "barang/barang_add.php";
        break;
    case "barang_read";
        include "barang/view_data.php";
        break;
    case "barang_edit";
        include "barang/barang_edit.php";
        break;
    case "barang_delete"; 
        include "barang/barang_delete.php";
        break;
    case "barang_keluar_add";
        include "barang_keluar/barang_keluar_add.php";
        break;
    case "barang_keluar_read";
        include "barang_keluar/view_data.php";
        break;
    case "barang_keluar_edit";
        include "barang_keluar/barang_keluar_edit.php";
        break;
    case "barang_keluar_delete"; 
        include "barang_keluar/barang_keluar_delete.php";
        break;
    case "barang_masuk_add";
        include "barang_masuk/barang_masuk_add.php";
        break;
    case "barang_masuk_read";
        include "barang_masuk/view_data.php";
        break;
    case "barang_masuk_edit";
        include "barang_masuk/barang_masuk_edit.php";
        break;
    case "barang_masuk_delete"; 
        include "barang_masuk/barang_masuk_delete.php";
        break;
    case "jenis_barang_add";
        include "jenis_barang/jenis_barang_add.php";
        break;
    case "jenis_barang_read";
        include "jenis_barang/view_data.php";
        break;
    case "jenis_barang_edit";
        include "jenis_barang/jenis_barang_edit.php";
        break;
    case "jenis_barang_delete"; 
        include "jenis_barang/jenis_barang_delete.php";
        break;                 
    case "satuan_barang_add";
        include "satuan_barang/satuan_barang_add.php";
        break;
    case "satuan_barang_read";
        include "satuan_barang/view_data.php";
        break;
    case "satuan_barang_edit";
        include "satuan_barang/satuan_barang_edit.php";
        break;
    case "satuan_barang_delete"; 
        include "satuan_barang/satuan_barang_delete.php";
        break;                 
    case "harga_barang_add";
        include "harga_barang/harga_barang_add.php";
        break;
    case "harga_barang_read";
        include "harga_barang/view_data.php";
        break;
    case "harga_barang_edit";
        include "harga_barang/harga_barang_edit.php";
        break;
    case "harga_barang_delete"; 
        include "harga_barang/harga_barang_delete.php";
        break;                 
    case "peringkat_add";
        include "peringkat/peringkat_add.php";
        break;
    case "peringkat_read";
        include "peringkat/view_data.php";
        break;
    case "peringkat_edit";
        include "peringkat/peringkat_edit.php";
        break;
    case "peringkat_delete"; 
        include "peringkat/peringkat_delete.php";
        break;
    case "pemasok_add";
        include "pemasok/pemasok_add.php";
        break;
    case "pemasok_read";
        include "pemasok/view_data.php";
        break;
    case "pemasok_edit";
        include "pemasok/pemasok_edit.php";
        break;
    case "pemasok_delete"; 
        include "pemasok/pemasok_delete.php";
        break;
    case "klien_add";
        include "klien/klien_add.php";
        break;
    case "klien_read";
        include "klien/view_data.php";
        break;
    case "klien_edit";
        include "klien/klien_edit.php";
        break;
    case "klien_delete"; 
        include "klien/klien_delete.php";
        break;
    case "pengguna_read";
        include "pengguna/view_data.php";
        break;    
    case "pengguna_add";
        include "pengguna/pengguna_add.php";
        break;
    case "laporan_barang_masuk_read";
        include "laporanBarangMasuk/laporan_barang_masuk.php";
        break;                            
    case "laporan_harga_barang_masuk_read";
        include "laporanHargaBarangMasuk/laporan_harga_barang_masuk.php";
        break;                            
    case "laporan_barang_keluar_read";
        include "laporanBarangKeluar/laporan_barang_keluar.php";
        break;                            
    case "laporan_harga_barang_keluar_read";
        include "laporanHargaBarangKeluar/laporan_harga_barang_keluar.php";
        break;                            
    case "laporan_stok_barang_read";
        include "laporanStokBarang/laporan_stok_barang.php";
        break;                            
    case "laporan_pelanggan_loyal_read";
        include "laporan/laporan_pelanggan_loyal.php";
        break;
    case "laporan_penerima_pesanan_read";
        include "laporan/laporan_penerima_pesanan.php";
        break;
    case "laporan_status_pesanan_read";
        include "laporan/laporan_status_pesanan.php";
        break;
    case "laporan_pendapatan_read";
        include "laporan/laporan_pendapatan.php";
        break;
    case "laporan_layanan_karpet_read";
        include "laporan/laporan_layanan_karpet.php";
        break;
    case "laporan_kinerja_karyawan_read":
        include "laporan/laporan_kinerja_karyawan.php";
        break;
    case 'laporan_promosi_read':
        include 'laporan/laporan_promosi.php';
        break;
    case "laporan_analisis_waktu_read";
        include "laporan/laporan_analisis_waktu.php";
        break;
    case "laporan_notifikasi_read";
        include "laporan/laporan_notifikasi.php";
        break;
}
include "template/footer.php"; //memanggil file koneksi di folder template