<?php
include "../pengaturan/koneksi.php";

$tgl_awal = $_POST['tgl_awal'];
$tgl_akhir = $_POST['tgl_akhir'];

// Query untuk data notifikasi
$query_notifikasi = mysqli_query($konek, "
    SELECT 
        n.*,
        p.nomor_invoice,
        pl.nama_pelanggan,
        pg.nama_lengkap as pengirim
    FROM notifikasi n
    LEFT JOIN pesanan p ON n.id_pesanan = p.id_pesanan
    LEFT JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
    LEFT JOIN pengguna pg ON n.id_pengguna = pg.id_pengguna
    WHERE DATE(n.waktu) BETWEEN '$tgl_awal' AND '$tgl_akhir'
    ORDER BY n.waktu DESC
");
?>

<h6>Detail Notifikasi</h6>
<p>
    <strong>Periode:</strong> <?= date('d/m/Y', strtotime($tgl_awal)) ?> - <?= date('d/m/Y', strtotime($tgl_akhir)) ?>
</p>

<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>No</th>
                <th>Waktu</th>
                <th>No. Invoice</th>
                <th>Pelanggan</th>
                <th>Pesan</th>
                <th>Status</th>
                <th>Pengirim</th>
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
                echo "<td>" . date('d/m/Y H:i', strtotime($data['waktu'])) . "</td>";
                echo "<td>" . htmlspecialchars($data['nomor_invoice']) . "</td>";
                echo "<td>" . htmlspecialchars($data['nama_pelanggan']) . "</td>";
                echo "<td>" . htmlspecialchars($data['pesan']) . "</td>";
                echo "<td>" . htmlspecialchars($data['status']) . "</td>";
                echo "<td>" . htmlspecialchars($data['pengirim']) . "</td>";
                echo "</tr>";

                $total_notifikasi++;
                if($data['status'] == 'Terkirim') {
                    $notifikasi_terkirim++;
                }
                if($data['status'] == 'Dibaca') {
                    $notifikasi_dibaca++;
                }
            }
            ?>
        </tbody>
    </table>
</div>

<div class="row mt-3">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6 class="card-title">Total Notifikasi</h6>
                <h4><?= number_format($total_notifikasi) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6 class="card-title">Terkirim</h6>
                <h4><?= number_format($notifikasi_terkirim) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h6 class="card-title">Dibaca</h6>
                <h4><?= number_format($notifikasi_dibaca) ?></h4>
            </div>
        </div>
    </div>
</div> 