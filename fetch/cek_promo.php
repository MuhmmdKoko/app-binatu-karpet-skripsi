<?php
header('Content-Type: application/json');
include '../pengaturan/koneksi.php';

$response = ['sukses' => false, 'pesan' => 'Request tidak valid.'];

if (isset($_POST['kode_promo']) && isset($_POST['total_belanja'])) {
    $kode_promo = strtoupper(trim($_POST['kode_promo']));
    $total_belanja = floatval($_POST['total_belanja']);

    if (empty($kode_promo)) {
        $response['pesan'] = 'Kode promo tidak boleh kosong.';
        echo json_encode($response);
        exit;
    }

    // 1. Ambil promo berdasarkan kode dan status
    $sql = "SELECT * FROM promosi WHERE kode_promo = ? AND status_promo IN ('aktif', 'terkirim')";
    $stmt = mysqli_prepare($konek, $sql);
    mysqli_stmt_bind_param($stmt, "s", $kode_promo);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $promo = mysqli_fetch_assoc($result);

        // 2. Validasi tanggal di PHP (dengan defensive check)
        $is_date_valid = false;
        if (isset($promo['masa_berlaku']) && $promo['masa_berlaku'] == 'selamanya') {
            $is_date_valid = true;
        } else {
            $today = date('Y-m-d');
            if (isset($promo['tanggal_mulai']) && isset($promo['tanggal_berakhir']) && $today >= $promo['tanggal_mulai'] && $today <= $promo['tanggal_berakhir']) {
                $is_date_valid = true;
            }
        }

        if ($is_date_valid) {
            // 3. Validasi minimum transaksi (dengan defensive check)
            $min_transaksi = isset($promo['syarat_min_transaksi']) ? floatval($promo['syarat_min_transaksi']) : 0;
            if ($total_belanja >= $min_transaksi) {
                // 4. Hitung diskon (dengan defensive check)
                $diskon = 0;
                $tipe_promo = isset($promo['tipe_promo']) ? $promo['tipe_promo'] : '';
                $nilai_promo = isset($promo['nilai_promo']) ? floatval($promo['nilai_promo']) : 0;

                if ($tipe_promo == 'persen') {
                    $diskon = $total_belanja * ($nilai_promo / 100);
                } else { // Asumsi tipe nominal jika bukan persen
                    $diskon = $nilai_promo;
                }

                if ($diskon > $total_belanja) {
                    $diskon = $total_belanja;
                }

                $total_setelah_diskon = $total_belanja - $diskon;

                $response = [
                    'sukses' => true,
                    'pesan' => 'Promo berhasil diterapkan!',
                    'id_promosi' => isset($promo['id_promosi']) ? $promo['id_promosi'] : null,
                    'diskon' => $diskon,
                    'total_setelah_diskon' => $total_setelah_diskon
                ];
            } else {
                $response['pesan'] = 'Minimum transaksi untuk promo ini adalah Rp' . number_format($min_transaksi) . '.';
            }
        } else {
            $response['pesan'] = 'Promo tidak berlaku pada tanggal ini.';
        }
    } else {
        $response['pesan'] = 'Kode promo tidak ditemukan atau tidak aktif.';
    }
}

echo json_encode($response);
?>
