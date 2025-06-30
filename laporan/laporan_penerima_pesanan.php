<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require "pengaturan/koneksi.php";
include "../template/header.php";

// Cek akses user
if ($_SESSION['role'] != "Admin") {
    echo '<script>alert("Anda tidak memiliki akses ke halaman ini!");window.location.href="index.php";</script>';
    exit;
}
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title fw-semibold mb-4">Laporan Penerima Pesanan</h5>
            <form method="POST" class="mb-4">
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Tanggal Awal</label>
                            <input type="date" class="form-control" name="tgl_awal" value="<?= isset($_POST['tgl_awal']) ? $_POST['tgl_awal'] : date('Y-m-01') ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Tanggal Akhir</label>
                            <input type="date" class="form-control" name="tgl_akhir" value="<?= isset($_POST['tgl_akhir']) ? $_POST['tgl_akhir'] : date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Penerima Pesanan</label>
                            <select name="id_pengguna" class="form-select">
                                <option value="">Semua Penerima</option>
                                <?php
                                $query_pengguna = mysqli_query($konek, "SELECT * FROM pengguna ORDER BY nama_lengkap");
                                while($pengguna = mysqli_fetch_array($query_pengguna)) {
                                    $selected = (isset($_POST['id_pengguna']) && $_POST['id_pengguna'] == $pengguna['id_pengguna']) ? 'selected' : '';
                                    echo "<option value='" . $pengguna['id_pengguna'] . "' $selected>" . htmlspecialchars($pengguna['nama_lengkap']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-search me-1"></i> Tampilkan
                </button>
            </form>

            <?php
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                // Validasi dan sanitasi input tanggal
                $tgl_awal = isset($_POST['tgl_awal']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['tgl_awal']) ? $_POST['tgl_awal'] : date('Y-m-01');
                $tgl_akhir = isset($_POST['tgl_akhir']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['tgl_akhir']) ? $_POST['tgl_akhir'] : date('Y-m-d');
                $tgl_awal_esc = mysqli_real_escape_string($konek, $tgl_awal);
                $tgl_akhir_esc = mysqli_real_escape_string($konek, $tgl_akhir);
                $where = "WHERE DATE(p.tanggal_masuk) BETWEEN '$tgl_awal_esc' AND '$tgl_akhir_esc'";

                // Validasi id_pengguna
                $id_pengguna = '';
                if (!empty($_POST['id_pengguna'])) {
                    $id_pengguna_raw = $_POST['id_pengguna'];
                    // Pastikan id_pengguna hanya angka (whitelist sederhana)
                    if (ctype_digit($id_pengguna_raw)) {
                        $id_pengguna = mysqli_real_escape_string($konek, $id_pengguna_raw);
                        $where .= " AND p.id_pengguna_penerima = '$id_pengguna'";
                    }
                }

                $query = mysqli_query($konek, "
                    SELECT 
                        pg.nama_lengkap,
                        COUNT(p.id_pesanan) as jumlah_pesanan,
                        SUM(COALESCE(p.total_harga_keseluruhan, 0)) as total_nilai_pesanan,
                        AVG(COALESCE(p.total_harga_keseluruhan, 0)) as rata_nilai_pesanan
                    FROM pengguna pg
                    LEFT JOIN pesanan p ON pg.id_pengguna = p.id_pengguna_penerima
                    $where
                    GROUP BY pg.id_pengguna
                    ORDER BY jumlah_pesanan DESC
                ");

                if (!$query) {
                    echo '<div class="alert alert-danger">Error: ' . mysqli_error($konek) . '</div>';
                } else {
            ?>

            <div class="table-responsive mt-4">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Penerima</th>
                            <th>Jumlah Pesanan</th>
                            <th>Total Nilai Pesanan</th>
                            <th>Rata-rata Nilai Pesanan</th>
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        $total_pesanan = 0;
                        $total_nilai = 0;

                        while($data = mysqli_fetch_array($query)) {
                            echo "<tr>";
                            echo "<td>" . $no++ . "</td>";
                            echo "<td>" . htmlspecialchars($data['nama_lengkap']) . "</td>";
                            echo "<td>" . number_format($data['jumlah_pesanan']) . "</td>";
                            echo "<td>Rp " . number_format($data['total_nilai_pesanan'], 0, ',', '.') . "</td>";
                            echo "<td>Rp " . number_format($data['rata_nilai_pesanan'], 0, ',', '.') . "</td>";
                            echo "<td>
                                    <button type='button' class='btn btn-info btn-sm' onclick='showDetail(\"" . $data['nama_lengkap'] . "\")'>
                                        <i class='ti ti-eye'></i>
                                    </button>
                                  </td>";
                            echo "</tr>";

                            $total_pesanan += $data['jumlah_pesanan'];
                            $total_nilai += $data['total_nilai_pesanan'];
                        }
                        ?>
                        <tr>
                            <td colspan="2" align="center"><strong>Total</strong></td>
                            <td><strong><?= number_format($total_pesanan) ?></strong></td>
                            <td><strong>Rp <?= number_format($total_nilai, 0, ',', '.') ?></strong></td>
                            <td><strong>Rp <?= number_format($total_nilai / ($total_pesanan ?: 1), 0, ',', '.') ?></strong></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Ringkasan Statistik -->
            <div class="row mt-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6 class="card-title">Total Penerima</h6>
                            <h4><?= number_format($no - 1) ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6 class="card-title">Total Pesanan</h6>
                            <h4><?= number_format($total_pesanan) ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h6 class="card-title">Total Nilai</h6>
                            <h4>Rp <?= number_format($total_nilai, 0, ',', '.') ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h6 class="card-title">Rata-rata per Penerima</h6>
                            <h4><?= ($no - 1) > 0 ? number_format($total_pesanan / ($no - 1), 1) : '0.0' ?></h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Detail -->
            <div class="modal fade" id="detailModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Detail Pesanan</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" id="detailContent">
                            <!-- Content will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <a href="laporan/export_penerima_pesanan.php?tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>&id_pengguna=<?= isset($id_pengguna) ? $id_pengguna : '' ?>" 
                   class="btn btn-success" target="_blank">
                    <i class="ti ti-file-export"></i> Export Excel
                </a>
                <a href="laporan/print_penerima_pesanan.php?tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>&id_pengguna=<?= isset($id_pengguna) ? $id_pengguna : '' ?>" 
                   class="btn btn-primary" target="_blank">
                    <i class="ti ti-printer"></i> Print
                </a>
            </div>

            <script>
                function showDetail(nama_penerima) {
                    // Load detail pesanan via AJAX
                    $.ajax({
                        url: 'laporan/detail_penerima_pesanan.php',
                        type: 'POST',
                        data: {
                            nama_penerima: nama_penerima,
                            tgl_awal: '<?= $tgl_awal ?>',
                            tgl_akhir: '<?= $tgl_akhir ?>'
                        },
                        success: function(response) {
                            $('#detailContent').html(response);
                            $('#detailModal').modal('show');
                        }
                    });
                }
            </script>
            <?php 
                }
            } 
            ?>
        </div>
    </div>
</div> 