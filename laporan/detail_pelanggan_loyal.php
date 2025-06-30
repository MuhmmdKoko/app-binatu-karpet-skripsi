<?php
include "../pengaturan/koneksi.php";

// Ambil parameter
$id_pelanggan = isset($_POST['id_pelanggan']) ? intval($_POST['id_pelanggan']) : 0;
$terakhir_transaksi = isset($_POST['terakhir_transaksi']) ? $_POST['terakhir_transaksi'] : '';

if ($id_pelanggan <= 0) {
    echo '<div class="alert alert-danger">ID pelanggan tidak valid.</div>';
    exit;
}

// Query data pelanggan
$q_pelanggan = mysqli_query($konek, "SELECT * FROM pelanggan WHERE id_pelanggan = $id_pelanggan");
$data_pelanggan = mysqli_fetch_assoc($q_pelanggan);
if (!$data_pelanggan) {
    echo '<div class="alert alert-danger">Data pelanggan tidak ditemukan.</div>';
    exit;
}

// Query riwayat transaksi pelanggan
$where = "WHERE p.id_pelanggan = $id_pelanggan";
if (!empty($terakhir_transaksi)) {
    $where .= " AND DATE(p.tanggal_masuk) <= '" . mysqli_real_escape_string($konek, $terakhir_transaksi) . "'";
}

$q_transaksi = mysqli_query($konek, "
    SELECT 
        p.*, p.total_setelah_diskon,
        GROUP_CONCAT(l.nama_layanan SEPARATOR ', ') as layanan,
        pg.nama_lengkap as penerima
    FROM pesanan p
    LEFT JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
    LEFT JOIN layanan l ON dp.id_layanan = l.id_layanan
    LEFT JOIN pengguna pg ON p.id_pengguna_penerima = pg.id_pengguna
    $where
    GROUP BY p.id_pesanan
    ORDER BY p.tanggal_masuk DESC
");


?>
<h6>Detail Transaksi - <?= htmlspecialchars($data_pelanggan['nama_pelanggan']) ?></h6>
<div class="mb-2">
    <strong>Alamat:</strong> <?= htmlspecialchars($data_pelanggan['alamat']) ?><br>
    <strong>No. HP:</strong> <?= htmlspecialchars($data_pelanggan['nomor_telepon']) ?><br>
</div>
<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>No</th>
                <th>No. Invoice</th>
                <th>Tanggal</th>
                <th>Layanan</th>
                <th>Total</th>
                <th>Status Pembayaran</th>
                <th>Penerima</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            $total_nilai = 0;
            while($data = mysqli_fetch_assoc($q_transaksi)) {
                echo "<tr>";
                echo "<td>" . $no++ . "</td>";
                echo "<td>" . htmlspecialchars($data['nomor_invoice']) . "</td>";
                echo "<td>" . date('d/m/Y H:i', strtotime($data['tanggal_masuk'])) . "</td>";
                echo "<td>" . htmlspecialchars($data['layanan']) . "</td>";
                $nilai = (isset($data['total_setelah_diskon']) && $data['total_setelah_diskon'] > 0) ? $data['total_setelah_diskon'] : $data['total_harga_keseluruhan'];
                echo "<td>Rp " . number_format($nilai, 0, ',', '.') . "</td>";
                echo "<td>" . htmlspecialchars($data['status_pembayaran']);
                if ($data['status_pembayaran'] == 'DP') {
                    $sisa = $nilai - $data['nominal_pembayaran'];
                    echo '<br><small class="text-info">DP dibayar: Rp ' . number_format($data['nominal_pembayaran'], 0, ',', '.') . ' | Sisa: Rp ' . number_format($sisa, 0, ',', '.') . '</small>';
                }
                echo "</td>";
                echo "<td>" . htmlspecialchars($data['penerima']) . "</td>";
                echo "</tr>";
                $total_nilai += $nilai;
            }
            ?>
            <tr>
                <td colspan="4" align="center"><strong>Total</strong></td>
                <td><strong>Rp <?= number_format($total_nilai, 0, ',', '.') ?></strong></td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>
</div>