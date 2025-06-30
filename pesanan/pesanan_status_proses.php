<?php
// --- Status Proses: Update status pesanan umum & status tiap item ---
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin','Karyawan'])) {
    echo '<script>alert("Akses ditolak");window.location="../index.php";</script>';
    exit;
}
include "pengaturan/koneksi.php";
include "../template/header.php";

// --- Helper Notifikasi ---
function kirim_notifikasi_pelanggan($id_pelanggan, $pesan, $channel = 'Telegram') {
    // TODO: Implementasi pengiriman ke Telegram/WhatsApp/SMS
    // Contoh dummy (anggap selalu sukses)
    return true;
}

function catat_notifikasi($konek, $id_pesanan, $id_pelanggan, $pesan, $channel = 'Telegram', $tipe = 'Status Pesanan') {
    $pesan_sql = mysqli_real_escape_string($konek, $pesan);
    $channel_sql = mysqli_real_escape_string($konek, $channel);
    $tipe_sql = mysqli_real_escape_string($konek, $tipe);
    $query = "INSERT INTO notifikasi (id_pesanan, id_pelanggan, pesan, waktu_kirim, channel, tipe_notifikasi, status_pengiriman)
              VALUES ('$id_pesanan', '$id_pelanggan', '$pesan_sql', NOW(), '$channel_sql', '$tipe_sql', 'Terkirim')";
    mysqli_query($konek, $query);
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    echo '<script>alert("ID pesanan tidak valid");window.history.back();</script>';
    exit;
}
// Ambil data pesanan
$data_pesanan = mysqli_query($konek, "SELECT * FROM pesanan WHERE id_pesanan=$id");
if (!$row = mysqli_fetch_assoc($data_pesanan)) {
    echo '<script>alert("Pesanan tidak ditemukan");window.history.back();</script>';
    exit;
}
// Pesanan dengan status final (Diambil/Dibatalkan) tidak bisa diubah, form akan di-disable.
$is_final_status = in_array($row['status_pesanan_umum'], ['Dibatalkan', 'Diambil']);

// Update status umum
if (isset($_POST['update_status_umum']) && !$is_final_status) {
    $status = mysqli_real_escape_string($konek, $_POST['status_pesanan_umum']);
    $status_sebelumnya = $row['status_pesanan_umum'];
    $id_pengguna = $_SESSION['id_pengguna'];

    // Validasi alur status dari Selesai
    if ($status_sebelumnya == 'Selesai' && in_array($status, ['Baru', 'Diproses', 'Dibatalkan'])) {
        echo '<script>alert("Pesanan yang sudah Selesai hanya bisa diubah menjadi Diambil.");window.location="?page=pesanan_status_proses&id='.$id.'";</script>';
        exit;
    }

    // Jika mengubah menjadi Diambil, pastikan pembayaran Lunas
    if($status == 'Diambil' && $row['status_pembayaran'] != 'Lunas'){
        echo '<script>alert("Pembayaran belum lunas, tidak dapat mengubah status menjadi Diambil");window.location="?page=pesanan_status_proses&id='.$id.'";</script>';
        exit;
    }

    mysqli_begin_transaction($konek);
    try {
        // Update tabel pesanan utama
        $update_query = "UPDATE pesanan SET status_pesanan_umum='$status'";
        if ($status == 'Diambil' && empty($row['tanggal_diambil'])) {
            $update_query .= ", tanggal_diambil = NOW()";
        } elseif ($status == 'Selesai' && empty($row['tanggal_selesai_aktual'])) {
            $update_query .= ", tanggal_selesai_aktual = NOW()";
        }
        $update_query .= " WHERE id_pesanan=$id";
        if (!mysqli_query($konek, $update_query)) {
            throw new Exception("Gagal update status pesanan: " . mysqli_error($konek));
        }

        // Log perubahan status umum jika statusnya benar-benar berubah
        if ($status_sebelumnya != $status) {
            $log_umum_sql = "INSERT INTO riwayat_status_pesanan (id_pesanan, status_sebelumnya, status_baru, id_pengguna, waktu_perubahan) VALUES ('$id', '$status_sebelumnya', '$status', '$id_pengguna', NOW())";
            if (!mysqli_query($konek, $log_umum_sql)) {
                throw new Exception("Gagal mencatat riwayat status umum: " . mysqli_error($konek));
            }
            // --- Kirim notifikasi & catat ke tabel notifikasi ---
            $id_pelanggan = $row['id_pelanggan'];
            $pesan_notif = "Status pesanan Anda dengan nomor invoice ".$row['nomor_invoice']." telah berubah menjadi: $status.";
            if (kirim_notifikasi_pelanggan($id_pelanggan, $pesan_notif, 'Telegram')) {
                catat_notifikasi($konek, $id, $id_pelanggan, $pesan_notif, 'Telegram', 'Status Pesanan');
            }
        }

        // Automatisasi status item untuk mengikuti status umum
        if (in_array($status, ['Diproses', 'Selesai', 'Diambil', 'Dibatalkan'])) {
            $items_to_update_sql = "SELECT id_detail_pesanan, status_item_terkini FROM detail_pesanan WHERE id_pesanan = $id";
            $items_result = mysqli_query($konek, $items_to_update_sql);
            
            while ($item = mysqli_fetch_assoc($items_result)) {
                $id_detail = $item['id_detail_pesanan'];
                $status_item_sebelumnya = $item['status_item_terkini'];

                if ($status_item_sebelumnya != $status && ($status_item_sebelumnya != 'Dibatalkan' || $status == 'Dibatalkan')) {
                    // Update status item
                    $update_item_sql = "UPDATE detail_pesanan SET status_item_terkini = '$status' WHERE id_detail_pesanan = $id_detail";
                    if (!mysqli_query($konek, $update_item_sql)) {
                        throw new Exception("Gagal update status item turunan: " . mysqli_error($konek));
                    }
                    // Log perubahan ke riwayat item
                    $log_item_sql = "INSERT INTO riwayat_status_item (id_detail_pesanan, status_sebelumnya, status_baru, id_pengguna, waktu_perubahan) VALUES ('$id_detail', '$status_item_sebelumnya', '$status', '$id_pengguna', NOW())";
                    if (!mysqli_query($konek, $log_item_sql)) {
                        throw new Exception("Gagal mencatat riwayat item turunan: " . mysqli_error($konek));
                    }
                }
            }
        }
        
        mysqli_commit($konek);
        echo '<script>alert("Status pesanan umum berhasil diperbarui.");window.location="?page=pesanan_status_proses&id='.$id.'";</script>';
        exit;

    } catch (Exception $e) {
        mysqli_rollback($konek);
        echo '<script>alert("Terjadi kesalahan: ' . addslashes($e->getMessage()) . '");window.location="?page=pesanan_status_proses&id='.$id.'";</script>';
        exit;
    }
}

// Update status item
if (isset($_POST['update_status_item']) && !$is_final_status) {
    $status_hierarchy = ['Diterima' => 1, 'Diproses' => 2, 'Selesai' => 3, 'Diambil' => 4, 'Dibatalkan' => 0];
    $id_pengguna_session = $_SESSION['id_pengguna'];
    $items_to_update = $_POST['status_item'] ?? [];

    mysqli_begin_transaction($konek);
    try {
        // Ambil status semua item yang akan diupdate dalam satu query
        $item_ids = array_map('intval', array_keys($items_to_update));
        if (empty($item_ids)) {
            throw new Exception("Tidak ada item yang dipilih untuk diupdate.");
        }
        $current_statuses_sql = "SELECT id_detail_pesanan, status_item_terkini FROM detail_pesanan WHERE id_detail_pesanan IN (" . implode(',', $item_ids) . ")";
        $current_statuses_res = mysqli_query($konek, $current_statuses_sql);
        $current_statuses = [];
        while ($item_row = mysqli_fetch_assoc($current_statuses_res)) {
            $current_statuses[$item_row['id_detail_pesanan']] = $item_row['status_item_terkini'];
        }

        foreach ($items_to_update as $id_detail => $new_status) {
            $id_detail = intval($id_detail);
            $old_status = $current_statuses[$id_detail] ?? null;

            if ($old_status === null || !isset($status_hierarchy[$new_status]) || $old_status == $new_status) {
                continue; // Skip jika item tidak ditemukan, status baru tidak valid, atau status tidak berubah
            }

            // --- VALIDASI PER ITEM ---
            // 1. Status tidak boleh mundur (kecuali Dibatalkan)
            if ($new_status != 'Dibatalkan' && isset($status_hierarchy[$old_status]) && $status_hierarchy[$new_status] < $status_hierarchy[$old_status]) {
                throw new Exception("Status item tidak dapat diubah ke status sebelumnya.");
            }
            // 2. Item yang sudah 'Dibatalkan' tidak bisa diubah lagi
            if ($old_status == 'Dibatalkan') {
                throw new Exception("Item yang sudah dibatalkan tidak dapat diubah statusnya.");
            }
            // 3. Untuk update item ke 'Diambil', pembayaran harus lunas dan status umum 'Selesai' atau 'Diambil'
            if ($new_status == 'Diambil') {
                if (!in_array($row['status_pesanan_umum'], ['Selesai', 'Diambil'])) {
                    throw new Exception("Status pesanan umum harus Selesai/Diambil sebelum item bisa Diambil.");
                }
                if ($row['status_pembayaran'] != 'Lunas') {
                    throw new Exception("Pembayaran belum lunas, item tidak dapat Diambil.");
                }
            }
            // --- AKHIR VALIDASI ---

            // Lakukan update dan logging
            $update_item_sql = "UPDATE detail_pesanan SET status_item_terkini = '".mysqli_real_escape_string($konek, $new_status)."' WHERE id_detail_pesanan = $id_detail";
            if (!mysqli_query($konek, $update_item_sql)) {
                throw new Exception("Gagal update status item: " . mysqli_error($konek));
            }

            $log_item_sql = "INSERT INTO riwayat_status_item (id_detail_pesanan, status_sebelumnya, status_baru, id_pengguna, waktu_perubahan) VALUES ('$id_detail', '".mysqli_real_escape_string($konek, $old_status)."', '".mysqli_real_escape_string($konek, $new_status)."', '$id_pengguna_session', NOW())";
            if (!mysqli_query($konek, $log_item_sql)) {
                throw new Exception("Gagal mencatat riwayat item: " . mysqli_error($konek));
            }
        }

        mysqli_commit($konek);
        echo '<script>alert("Status item berhasil diperbarui.");window.location="?page=pesanan_status_proses&id='.$id.'";</script>';
        exit;

    } catch (Exception $e) {
        mysqli_rollback($konek);
        echo '<script>alert("Terjadi kesalahan: ' . addslashes($e->getMessage()) . '");window.location="?page=pesanan_status_proses&id='.$id.'";</script>';
        exit;
    }
}

// Ambil ulang data detail pesanan & riwayat status
$detail = mysqli_query($konek, "SELECT d.*, l.nama_layanan, l.satuan FROM detail_pesanan d JOIN layanan l ON d.id_layanan=l.id_layanan WHERE d.id_pesanan=$id");
$riwayat_query = mysqli_query($konek, "SELECT r.waktu_perubahan, l.nama_layanan, d.deskripsi_item_spesifik, r.status_sebelumnya, r.status_baru, u.nama_lengkap FROM riwayat_status_item r JOIN detail_pesanan d ON r.id_detail_pesanan = d.id_detail_pesanan JOIN layanan l ON d.id_layanan = l.id_layanan JOIN pengguna u ON r.id_pengguna = u.id_pengguna WHERE d.id_pesanan = $id ORDER BY r.waktu_perubahan DESC");

// Query untuk riwayat status pesanan umum
$riwayat_umum_sql = "SELECT rsp.*, p.nama_lengkap 
                     FROM riwayat_status_pesanan rsp
                     JOIN pengguna p ON rsp.id_pengguna = p.id_pengguna
                     WHERE rsp.id_pesanan = $id
                     ORDER BY rsp.waktu_perubahan DESC";
$riwayat_umum_query = mysqli_query($konek, $riwayat_umum_sql);

?>
<div class="container-fluid mt-4">
    <h4 class="mb-4">Status Proses Pesanan</h4>
    
    <form id="umumStatusForm" method="post" action="?page=pesanan_status_proses&id=<?= $id ?>">
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="card-title mb-0">Nomor Invoice: <span class="fw-normal"><?= htmlspecialchars($row['nomor_invoice']) ?></span></h5>
                    </div>
                    <div class="col-md-6 d-flex align-items-center justify-content-end">
                        <label for="status_pesanan_umum" class="me-2 form-label mb-0">Status Umum:</label>
                        <select id="status_pesanan_umum" name="status_pesanan_umum" class="form-select me-2" style="width: auto;" <?= $is_final_status ? 'disabled' : '' ?>>
                            <?php $statuses = ['Baru','Diproses','Selesai','Diambil','Dibatalkan'];
                            foreach($statuses as $s): ?>
                                <option value="<?= $s ?>" <?= $row['status_pesanan_umum']==$s?'selected':'' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="update_status_umum" class="btn btn-primary btn-sm" <?= $is_final_status ? 'disabled' : '' ?>>Update</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="card mt-4">
        <div class="card-body">
            <h5 class="card-title">Riwayat Perubahan Status Pesanan</h5>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Dari</th>
                            <th>Ke</th>
                            <th>Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($riwayat_umum_query) > 0): ?>
                            <?php while($r_umum = mysqli_fetch_assoc($riwayat_umum_query)): ?>
                                <tr>
                                    <td><?= date('d-m-Y H:i:s', strtotime($r_umum['waktu_perubahan'])) ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($r_umum['status_sebelumnya']) ?></span></td>
                                    <td><span class="badge bg-primary"><?= htmlspecialchars($r_umum['status_baru']) ?></span></td>
                                    <td><?= htmlspecialchars($r_umum['nama_lengkap']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">Belum ada riwayat perubahan status pesanan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <form id="itemStatusForm" method="post" action="?page=pesanan_status_proses&id=<?= $id ?>">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Status Tiap Item</h5>
                <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Layanan</th>
                            <th>Deskripsi</th>
                            <th>Kuantitas</th>
                            <th>Status Sekarang</th>
                            <th>Update Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    mysqli_data_seek($detail, 0); // Reset pointer
                    while($d = mysqli_fetch_assoc($detail)):
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($d['nama_layanan']) ?></td>
                            <td><?= htmlspecialchars($d['deskripsi_item_spesifik']) ?></td>
                            <td><?= htmlspecialchars($d['kuantitas']) . ' ' . htmlspecialchars($d['satuan']) ?></td>
                            <td><?= htmlspecialchars($d['status_item_terkini']) ?></td>
                            <td>
                                <select name="status_item[<?= $d['id_detail_pesanan'] ?>]" class="form-control status-select" <?= (in_array($d['status_item_terkini'], ['Dibatalkan', 'Diambil']) || $is_final_status) ? 'disabled' : '' ?> data-current="<?= $d['status_item_terkini'] ?>">
                                    <?php $item_statuses = ['Diterima','Diproses','Selesai','Diambil','Dibatalkan'];
                                    foreach($item_statuses as $si): ?>
                                        <option value="<?= $si ?>" <?= $d['status_item_terkini']==$si?'selected':'' ?>><?= $si ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                </div>
                <button type="submit" name="update_status_item" class="btn btn-success" <?= $is_final_status ? 'disabled' : '' ?>>Update Status Item</button>
                <a href="?page=pesanan_detail&id=<?= $id ?>" class="btn btn-secondary">Kembali</a>
            </div>
        </div>
    </form>

    <div class="card mt-4">
        <div class="card-body">
            <h5 class="card-title">Riwayat Perubahan Status Tiap Item</h5>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Layanan & Deskripsi</th>
                            <th>Dari</th>
                            <th>Ke</th>
                            <th>Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($riwayat_query) > 0): ?>
                            <?php while($rh = mysqli_fetch_assoc($riwayat_query)):
                            ?>
                                <tr>
                                    <td><?= date('d-m-Y H:i:s', strtotime($rh['waktu_perubahan'])) ?></td>
                                    <td><?= htmlspecialchars($rh['nama_layanan']) ?><br><small class="text-muted"><?= htmlspecialchars($rh['deskripsi_item_spesifik']) ?></small></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($rh['status_sebelumnya']) ?></span></td>
                                    <td><span class="badge bg-primary"><?= htmlspecialchars($rh['status_baru']) ?></span></td>
                                    <td><?= htmlspecialchars($rh['nama_lengkap']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Belum ada riwayat perubahan status.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Script untuk konfirmasi (tidak perlu diubah, letakkan di akhir)
const fUmum=document.getElementById('umumStatusForm');
if(fUmum){
  fUmum.addEventListener('submit',function(e){
      const sel=this.querySelector('select[name="status_pesanan_umum"]');
      if(sel && !sel.disabled && (sel.value==='Dibatalkan' || sel.value==='Diambil')){
          const msg= sel.value==='Dibatalkan' ? 'Membatalkan pesanan akan membatalkan semua item. Lanjutkan?' : 'Status Diambil akan mengunci pesanan dari perubahan lebih lanjut. Lanjutkan?';
          if(!confirm(msg)){
              e.preventDefault();
          }
      }
  });
}

const formItem=document.getElementById('itemStatusForm');
if(formItem){
  formItem.addEventListener('submit',function(e){
      const selects=this.querySelectorAll('.status-select');
      let needConfirm=false;
      selects.forEach(s=>{
          if(!s.disabled && s.value==='Dibatalkan'){
              needConfirm=true;
          }
      });
      if(needConfirm && !confirm('Apakah Anda yakin ingin membatalkan item yang dipilih?')){
          e.preventDefault();
      }
  });
}
</script>
