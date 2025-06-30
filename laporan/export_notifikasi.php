<?php
include "../pengaturan/koneksi.php";

// Set header for Excel download
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Notifikasi.xls");

$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : (isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'));
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : (isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'));
$tipe_channel = isset($_GET['tipe_channel']) ? $_GET['tipe_channel'] : '';
$filter_by = isset($_GET['filter_by']) ? $_GET['filter_by'] : 'waktu_kirim';

// Validate filter_by to avoid SQL injection
$allowed_filters = ['waktu_kirim', 'waktu_dijadwalkan', 'created_at'];
if (!in_array($filter_by, $allowed_filters)) {
    $filter_by = 'waktu_kirim';
}

// Build WHERE clause
$where = "WHERE DATE(n.$filter_by) BETWEEN '" . mysqli_real_escape_string($konek, $tgl_awal) . "' AND '" . mysqli_real_escape_string($konek, $tgl_akhir) . "'";
if (!empty($tipe_channel)) {
    $where .= " AND n.tipe_channel = '" . mysqli_real_escape_string($konek, $tipe_channel) . "'";
}

// Query untuk data notifikasi
$query_notifikasi = mysqli_query($konek, "
    SELECT 
        n.*,
        p.nomor_invoice,
        pl.nama_pelanggan
    FROM notifikasi n
    LEFT JOIN pesanan p ON n.id_pesanan = p.id_pesanan
    LEFT JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
    $where
    ORDER BY n.$filter_by DESC
");

?>

<h3>Laporan Notifikasi</h3>
<p>Periode: <?= date('d/m/Y', strtotime($tgl_awal)) ?> - <?= date('d/m/Y', strtotime($tgl_akhir)) ?></p>

<table border="1">
    <thead>
        <tr>
            <th>No</th>
            <th>Waktu</th>
            <th>No. Invoice</th>
            <th>Pelanggan</th>
            <th>Pesan</th>
            <th>Status</th>
            <th>Tipe Notifikasi</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $no = 1;
        $total_notifikasi = 0;
        $notifikasi_terkirim = 0;
        $notifikasi_dibaca = 0;

        while($data = mysqli_fetch_array($query_notifikasi)) {
            echo "<tr>";
            echo "<td>" . $no++ . "</td>";
            // Use waktu_kirim, waktu_dijadwalkan, or created_at based on filter_by
            $waktu = isset($data[$filter_by]) ? $data[$filter_by] : (isset($data['waktu_kirim']) ? $data['waktu_kirim'] : '');
            echo "<td>" . ($waktu ? date('d/m/Y H:i', strtotime($waktu)) : '-') . "</td>";
            echo "<td>" . htmlspecialchars(isset($data['nomor_invoice']) ? $data['nomor_invoice'] : '-') . "</td>";
            echo "<td>" . htmlspecialchars(isset($data['nama_pelanggan']) ? $data['nama_pelanggan'] : '-') . "</td>";
            echo "<td>" . htmlspecialchars(isset($data['isi_pesan']) ? $data['isi_pesan'] : '-') . "</td>";
            echo "<td>" . htmlspecialchars(isset($data['status_pengiriman']) ? $data['status_pengiriman'] : '-') . "</td>";
            // Remove pengirim (not available)
            echo "<td>-</td>";
            echo "<td>" . htmlspecialchars(isset($data['tipe_channel']) ? $data['tipe_channel'] : '-') . "</td>";
            echo "</tr>";

            $total_notifikasi++;
            if(isset($data['status_pengiriman']) && $data['status_pengiriman'] == 'Terkirim') {
                $notifikasi_terkirim++;
            }
            if(isset($data['status_pengiriman']) && $data['status_pengiriman'] == 'Dibaca') {
                $notifikasi_dibaca++;
            }
        }
        ?>
    </tbody>
</table>

<p>
    Total Notifikasi: <?= number_format($total_notifikasi) ?><br>
    Notifikasi Terkirim: <?= number_format($notifikasi_terkirim) ?><br>
    Notifikasi Dibaca: <?= number_format($notifikasi_dibaca) ?>
</p> 