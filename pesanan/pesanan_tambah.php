<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<?php
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

function catat_notifikasi($konek, $id_pesanan, $id_pelanggan, $pesan, $channel = 'Telegram', $tipe = 'Pesanan Baru') {
    $pesan_sql = mysqli_real_escape_string($konek, $pesan);
    $channel_sql = mysqli_real_escape_string($konek, $channel);
    $tipe_sql = mysqli_real_escape_string($konek, $tipe);
    $query = "INSERT INTO notifikasi (id_pesanan, id_pelanggan, pesan, waktu_kirim, channel, tipe_notifikasi, status_pengiriman)
              VALUES ('$id_pesanan', '$id_pelanggan', '$pesan_sql', NOW(), '$channel_sql', '$tipe_sql', 'Terkirim')";
    mysqli_query($konek, $query);
}


// --- Langkah 1: Pilih Pelanggan ---
$id_pelanggan = isset($_POST['id_pelanggan']) ? intval($_POST['id_pelanggan']) : 0;
$nama_pelanggan = '';
if ($id_pelanggan) {
    $res = mysqli_query($konek, "SELECT * FROM pelanggan WHERE id_pelanggan=$id_pelanggan");
    if ($row = mysqli_fetch_assoc($res)) {
        $nama_pelanggan = $row['nama_pelanggan'];
    }
}

// --- Langkah 2: Ambil daftar layanan ---
$layanan = [];
$resL = mysqli_query($konek, "SELECT * FROM layanan ORDER BY nama_layanan");
while($rowL = mysqli_fetch_assoc($resL)) {
    $layanan[] = $rowL;
}

// --- Proses simpan pesanan ---
$err = $sukses = '';
if (isset($_POST['submit_pesanan'])) {
    echo '<pre style="background:#fff;color:#000;z-index:9999;position:relative;">DEBUG POST:\n';
    print_r($_POST);
    echo '</pre>';

    // Data utama pesanan
    $id_pelanggan = intval($_POST['id_pelanggan']);
    $id_pengguna_penerima = intval($_SESSION['id_pengguna']);
    $nomor_invoice = 'INV-' . date('Ymd-His');
    $tanggal_masuk = date('Y-m-d H:i:s');
    // Hitung estimasi selesai berdasarkan item yang dipilih (server-side)
    $max_estimasi_hari = 0;
    if (isset($_POST['item']) && is_array($_POST['item'])) {
        $id_layanans = [];
        foreach ($_POST['item'] as $item) {
            if (!empty($item['id_layanan'])) {
                $id_layanans[] = intval($item['id_layanan']);
            }
        }

        if (!empty($id_layanans)) {
            $ids_string = implode(',', array_unique($id_layanans));
            $res_estimasi = mysqli_query($konek, "SELECT MAX(estimasi_waktu_hari) as max_hari FROM layanan WHERE id_layanan IN ($ids_string)");
            if ($row_estimasi = mysqli_fetch_assoc($res_estimasi)) {
                $max_estimasi_hari = intval($row_estimasi['max_hari']);
            }
        }
    }
    
    $tgl_masuk_obj = new DateTime($tanggal_masuk);
    if ($max_estimasi_hari > 0) {
        $tgl_masuk_obj->add(new DateInterval("P{$max_estimasi_hari}D"));
    }
    $tanggal_estimasi_selesai = $tgl_masuk_obj->format('Y-m-d H:i:s');
    $catatan_pesanan = trim($_POST['catatan_pesanan']);
    $metode_pembayaran = trim($_POST['metode_pembayaran']);
    $status_pembayaran = trim($_POST['status_pembayaran']);
    $status_pesanan_umum = 'Baru';
    // Pastikan semua nilai total, diskon, total setelah diskon dalam satuan rupiah penuh (integer)
    $total_harga_keseluruhan = intval(round(floatval(str_replace(',', '', $_POST['total_harga_keseluruhan']))));
    $nominal_pembayaran = isset($_POST['nominal_pembayaran']) ? intval(round(floatval($_POST['nominal_pembayaran']))) : 0;

    // Ambil promo jika ada
    $id_promosi = (isset($_POST['id_promosi']) && intval($_POST['id_promosi']) > 0) ? intval($_POST['id_promosi']) : 'NULL';
    $diskon = isset($_POST['diskon']) ? intval(round(floatval($_POST['diskon']))) : 0;
    $total_setelah_diskon = isset($_POST['total_setelah_diskon']) ? intval(round(floatval($_POST['total_setelah_diskon']))) : $total_harga_keseluruhan;
    // Pastikan nilai diskon dan total_setelah_diskon benar dari POST (hasil perhitungan JS)

    // Simpan ke tabel pesanan
    $sql = "INSERT INTO pesanan (id_pelanggan, id_pengguna_penerima, nomor_invoice, tanggal_masuk, tanggal_estimasi_selesai, total_harga_keseluruhan, status_pesanan_umum, catatan_pesanan, metode_pembayaran, status_pembayaran, nominal_pembayaran, id_promosi, diskon, total_setelah_diskon) VALUES ($id_pelanggan, $id_pengguna_penerima, '$nomor_invoice', '$tanggal_masuk', '$tanggal_estimasi_selesai', $total_harga_keseluruhan, '$status_pesanan_umum', '$catatan_pesanan', '$metode_pembayaran', '$status_pembayaran', $nominal_pembayaran, $id_promosi, $diskon, $total_setelah_diskon)";
    $q = mysqli_query($konek, $sql) or die('Query pesanan gagal: '.mysqli_error($konek));
    if ($q) {
        $id_pesanan = mysqli_insert_id($konek);
        // Simpan detail item
        if (isset($_POST['item']) && is_array($_POST['item'])) {
            // DEBUG: Cek data POST detail
// var_dump($_POST['item']); exit;
foreach ($_POST['item'] as $item) {
    $id_layanan = intval($item['id_layanan'] ?? 0);
    $kuantitas = floatval($item['kuantitas'] ?? 0);
    $harga_saat_pesan = floatval(str_replace(',', '', $item['harga_saat_pesan'] ?? '0'));
    $subtotal_item = floatval(str_replace(',', '', $item['subtotal_item'] ?? '0'));
    if($id_layanan <= 0) continue;
    $deskripsi = mysqli_real_escape_string($konek, $item['deskripsi_item_spesifik'] ?? '');
    $catatan_item = mysqli_real_escape_string($konek, $item['catatan_item'] ?? '');
    $status_item_terkini = 'Diterima';
    $panjang_karpet = isset($item['panjang_karpet']) ? floatval($item['panjang_karpet']) : 0;
    $lebar_karpet = isset($item['lebar_karpet']) ? floatval($item['lebar_karpet']) : 0;
    // Ambil satuan layanan dari array $layanan
    $satuan_layanan = '';
    foreach ($layanan as $l) {
        if ($l['id_layanan'] == $id_layanan) {
            $satuan_layanan = strtolower(trim($l['satuan']));
            break;
        }
    }
    // Validasi dan kalkulasi untuk m2/m²
    if ($satuan_layanan === 'm2' || $satuan_layanan === 'm²') {
        if ($panjang_karpet <= 0 || $lebar_karpet <= 0) {
            $err = 'Panjang dan lebar karpet wajib diisi dan > 0 untuk layanan karpet.';
            break;
        }
        $kuantitas = $panjang_karpet * $lebar_karpet;
    } else {
        $panjang_karpet = null;
        $lebar_karpet = null;
    }
    $sqlDetail = "INSERT INTO detail_pesanan (id_pesanan, id_layanan, deskripsi_item_spesifik, kuantitas, harga_saat_pesan, subtotal_item, catatan_item, status_item_terkini, panjang_karpet, lebar_karpet) VALUES ($id_pesanan, $id_layanan, '$deskripsi', $kuantitas, $harga_saat_pesan, $subtotal_item, '$catatan_item', '$status_item_terkini', ".($panjang_karpet!==null?$panjang_karpet:'NULL').", ".($lebar_karpet!==null?$lebar_karpet:'NULL').")";
    mysqli_query($konek, $sqlDetail) or die('Query detail_pesanan gagal: '.mysqli_error($konek));
}
        }
        $sukses = 'Pesanan berhasil dicatat!';
        // Kirim notifikasi ke pelanggan & catat ke tabel notifikasi
        $pesan_notif = "Pesanan Anda dengan invoice $nomor_invoice telah dicatat. Status: Baru.";
        if (kirim_notifikasi_pelanggan($id_pelanggan, $pesan_notif, 'Telegram')) {
            catat_notifikasi($konek, $id_pesanan, $id_pelanggan, $pesan_notif, 'Telegram', 'Pesanan Baru');
        }
        echo '<script>alert("Pesanan berhasil dicatat");window.location="?page=pesanan_read";</script>';
        exit;
    } else {
        $err = 'Gagal menyimpan pesanan.';
    }
}
?>
<div class="container-fluid mt-4">
    <h3 class="mb-3">Pencatatan Pesanan Baru</h3>
    <?php if($err): ?>
    <div class="alert alert-danger"><?= $err ?></div>
<?php endif; ?>
<?php if($sukses): ?>
    <div class="alert alert-success"><?= $sukses ?></div>
<?php endif; ?>
    <!-- Langkah 1: Pilih Pelanggan -->
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title">1. Pilih Pelanggan</h5>
            <div class="row mb-2">
                <div class="col-md-8">
                    <input type="text" id="cariPelanggan" class="form-control" placeholder="Cari nama/telepon pelanggan...">
                    <input type="hidden" id="id_pelanggan" name="id_pelanggan" value="<?= $id_pelanggan ?>">
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-success" id="btnTambahPelanggan">+ Pelanggan Baru</button>
                </div>
            </div>
            <div id="hasilCariPelanggan" class="mb-2"></div>
            <div id="infoPelanggan">
                <?php if($id_pelanggan): ?>
                    <div class="alert alert-info">Pelanggan terpilih: <strong><?= htmlspecialchars($nama_pelanggan) ?></strong></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Langkah 2: Tambah Detail Layanan/Item -->
    <form method="post" id="formPesanan">
        <input type="hidden" name="id_pelanggan" id="id_pelanggan_form" value="<?= $id_pelanggan ?>">
        <input type="hidden" id="estimasi_max_hari" value="0">
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">2. Tambah Layanan/Item</h5>
                <div class="table-responsive">
                    <table class="table table-bordered" id="tabelItem">
                        <thead>
                            <tr>
                                <th>Layanan</th>
                                <th>Deskripsi Item</th>
                                <th>Karpet (m<sup>2</sup>) / Qty</th>
                                <th>Harga Satuan</th>
                                <th>Subtotal</th>
                                <th>Catatan</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-primary" id="btnTambahItem">+ Tambah Item</button>
            </div>
        </div>
        <!-- Langkah 3: Konfirmasi Pesanan -->
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">3. Konfirmasi Pesanan</h5>
                <div class="row mb-2">
                    <div class="col-md-4">
                        <label>Nomor Invoice</label>
                        <input type="text" class="form-control" name="nomor_invoice" value="(otomatis)" readonly>
                    </div>
                    <div class="col-md-4">
                        <label>Tanggal Estimasi Selesai</label>
                        <input type="text" class="form-control" id="tanggal_estimasi_selesai_display" placeholder="Otomatis" readonly>
                        <input type="hidden" name="tanggal_estimasi_selesai" id="tanggal_estimasi_selesai">
                    </div>
                    <div class="col-md-4">
                        <label>Metode Pembayaran</label>
                        <select name="metode_pembayaran" class="form-control" required>
                            <option value="Tunai">Tunai</option>
                            <option value="Transfer Bank">Transfer Bank</option>
                            <option value="QRIS">QRIS</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-4">
    <label>Status Pembayaran</label>
    <select name="status_pembayaran" class="form-control" id="status_pembayaran" required>
        <option value="Belum Lunas">Belum Lunas</option>
        <option value="Lunas">Lunas</option>
        <option value="DP">DP</option>
    </select>
    <div id="nominal_pembayaran_group" style="display:none; margin-top:8px;">
        <label id="label_nominal_pembayaran">Nominal Pembayaran</label>
        <input type="number" min="0" step="100" class="form-control" name="nominal_pembayaran" id="nominal_pembayaran" placeholder="Masukkan nominal pembayaran">
    </div>
</div>
                    <div class="col-md-8">
                        <label>Catatan Pesanan</label>
                        <input type="text" class="form-control" name="catatan_pesanan">
                    </div>
                </div>
                <div class="row mt-3 align-items-end">
                    <div class="col-md-4">
                        <label for="kode_promo">Kode Promo</label>
                        <div class="input-group mb-2">
                            <input type="text" class="form-control" name="kode_promo" id="kode_promo" placeholder="Masukkan kode promo">
                            <button type="button" class="btn btn-info" id="btnCekPromo">Cek Promo</button>
                        </div>
                        <div id="promo_info" class="small text-success"></div>
                        <input type="hidden" name="id_promosi" id="id_promosi">
                        <input type="hidden" name="diskon" id="diskon">
                    </div>
                    <div class="col-md-8 text-end">
                        <hr>
                        <div id="ringkasan_diskon" style="display:none">
                            <span class="text-muted">Diskon Promo: </span>Rp<span id="display_diskon">0</span><br>
                        </div>
                        <h4>Total: Rp<span id="display_total_akhir">0</span></h4>
                        <input type="hidden" name="total_harga_keseluruhan" id="total_harga_keseluruhan" value="0">
                        <input type="hidden" name="total_setelah_diskon" id="total_setelah_diskon" value="0">
                    </div>
                </div>

                <button type="submit" name="submit_pesanan" class="btn btn-success">Simpan Pesanan</button>
                <a href="?page=pesanan_read" class="btn btn-secondary">Batal</a>
            </div>
        </div>
    </form>
</div>
<!-- Modal tambah pelanggan baru bisa dibuat terpisah jika dibutuhkan -->
<script src="assets/libs/jquery/dist/jquery.min.js"></script>
<script>
// --- Event: Tambah pelanggan baru ---
$('#btnTambahPelanggan').on('click', function() {
    window.location.href = 'index.php?page=pelanggan_tambah';
});
// --- Promo: AJAX cek kode promo dan update diskon ---
$('#btnCekPromo').on('click', function() {
    var kode = $('#kode_promo').val().trim();
    var total = parseFloat($('#total_harga_keseluruhan').val()) || 0;
    if (!kode) {
        $('#promo_info').text('Kode promo wajib diisi').removeClass('text-success').addClass('text-danger');
        return;
    }
    $('#btnCekPromo').prop('disabled', true).text('Cek...');
    $.post('pesanan/promo_cek.php', {kode: kode, total: total}, function(res) {
        if (res.status === 'ok') {
            $('#promo_info').text(res.msg).removeClass('text-danger').addClass('text-success');
            $('#id_promosi').val(res.id_promosi);
            $('#diskon').val(res.diskon);
            $('#display_diskon').text(parseInt(res.diskon).toLocaleString('id-ID'));
            $('#ringkasan_diskon').show();
            var totalAkhir = Math.max(0, total - res.diskon);
            $('#display_total_akhir').text(totalAkhir.toLocaleString('id-ID'));
            $('#total_setelah_diskon').val(totalAkhir);
        } else {
            $('#promo_info').text(res.msg).removeClass('text-success').addClass('text-danger');
            $('#id_promosi').val('');
            $('#diskon').val('');
            $('#display_diskon').text('0');
            $('#ringkasan_diskon').hide();
            $('#display_total_akhir').text(total.toLocaleString('id-ID'));
            $('#total_setelah_diskon').val(total);
        }
    }, 'json').always(function() {
        $('#btnCekPromo').prop('disabled', false).text('Cek Promo');
    });
});
// Update total akhir jika item berubah
function updatePromoTotal() {
    var total = parseFloat($('#total_harga_keseluruhan').val()) || 0;
    var diskon = parseFloat($('#diskon').val()) || 0;
    if (diskon > 0) {
        $('#ringkasan_diskon').show();
        $('#display_diskon').text(parseInt(diskon).toLocaleString('id-ID'));
        var totalAkhir = Math.max(0, total - diskon);
        $('#display_total_akhir').text(totalAkhir.toLocaleString('id-ID'));
        $('#total_setelah_diskon').val(totalAkhir);
    } else {
        $('#ringkasan_diskon').hide();
        $('#display_total_akhir').text(total.toLocaleString('id-ID'));
        $('#total_setelah_diskon').val(total);
    }
}
$('#tabelItem').on('input change', '.subtotal-item', function() {
    // Reset promo jika item berubah
    $('#id_promosi').val('');
    $('#diskon').val('');
    $('#promo_info').text('');
    updatePromoTotal();
});
// Juga update saat total berubah
$('#total_harga_keseluruhan').on('input change', updatePromoTotal);

$('#status_pembayaran').on('change', function() {
    var val = $(this).val();
    if(val === 'DP' || val === 'Lunas') {
        $('#nominal_pembayaran_group').show();
        if(val === 'DP') {
            $('#label_nominal_pembayaran').text('Nominal DP');
        } else {
            $('#label_nominal_pembayaran').text('Nominal Pembayaran');
        }
    } else {
        $('#nominal_pembayaran_group').hide();
        $('#nominal_pembayaran').val('');
    }
});
// Trigger saat load jika perlu
$(function(){
    $('#status_pembayaran').trigger('change');
});
// --- Cari pelanggan ---
$('#cariPelanggan').on('keyup', function(){
    var q = $(this).val();
    if(q.length < 2) return;
    $.get('pelanggan/pelanggan_search.php', {q: q}, function(data){
        $('#hasilCariPelanggan').html(data);
        // Klik pada card hasil pencarian untuk memilih pelanggan
        $('.pelanggan-result-item').on('click', function(){
            var id = $(this).find('.id-pelanggan-data').val();
            var nama = $(this).find('.nama-pelanggan-data').val();
            $('#id_pelanggan').val(id);
            $('#id_pelanggan_form').val(id);
            $('#infoPelanggan').html('<div class="alert alert-info">Pelanggan terpilih: <strong>'+nama+'</strong></div>');
            $('#hasilCariPelanggan').html('');
        });
    });
});

// --- Tambah item layanan ---
var layanan = <?= json_encode($layanan) ?>;
function getMaxEstimasiHari() {
    var max = 0;
    $('#tabelItem tbody tr').each(function(){
        var est = parseInt($(this).find('select option:selected').data('estimasi')) || 0;
        if(est > max) max = est;
    });
    return max;
}
function setTanggalEstimasi(){
    var maxEstimasi = 0;
    $('#tabelItem tbody tr').each(function(){
        var estimasi = parseInt($(this).find('select').find(':selected').data('estimasi')) || 0;
        if(estimasi > maxEstimasi){
            maxEstimasi = estimasi;
        }
    });

    if(maxEstimasi > 0){
        var tglEstimasi = new Date(); // Get current date and time
        tglEstimasi.setDate(tglEstimasi.getDate() + maxEstimasi); // Add the estimation days

        // Format to YYYY-MM-DD HH:MM:SS for database
        var yyyy = tglEstimasi.getFullYear();
        var mm = String(tglEstimasi.getMonth() + 1).padStart(2, '0');
        var dd = String(tglEstimasi.getDate()).padStart(2, '0');
        var hh = String(tglEstimasi.getHours()).padStart(2, '0');
        var ii = String(tglEstimasi.getMinutes()).padStart(2, '0');
        var ss = String(tglEstimasi.getSeconds()).padStart(2, '0');

        var dbDateTime = yyyy + '-' + mm + '-' + dd + ' ' + hh + ':' + ii + ':' + ss;
        // Format for display
        var displayDateTime = dd + '/' + mm + '/' + yyyy + ' ' + hh + ':' + ii;

        $('#tanggal_estimasi_selesai').val(dbDateTime);
        $('#display_estimasi_selesai').text(displayDateTime);
    } else {
        $('#tanggal_estimasi_selesai').val('');
        $('#display_estimasi_selesai').text('-');
    }
}
function renderLayananOptions() {
    var html = '';
    layanan.forEach(function(l){
        html += '<option value="'+l.id_layanan+'" data-harga="'+l.harga_per_unit+'" data-estimasi="'+l.estimasi_waktu_hari+'">'+l.nama_layanan+' (Rp'+parseInt(l.harga_per_unit).toLocaleString()+'/'+l.satuan+')</option>';
    });
    return html;
}
if(typeof window.itemIndex === 'undefined'){window.itemIndex=0;}
$('#btnTambahItem').on('click', function(){
    var layananOptions = '';
    <?php foreach($layanan as $l): ?>
    layananOptions += '<option value="<?= $l['id_layanan'] ?>" data-harga="<?= $l['harga_per_unit'] ?>" data-estimasi="<?= $l['estimasi_waktu_hari'] ?>" data-minimal-aktif="<?= $l['minimal_order_aktif'] ?>" data-minimal-qty="<?= $l['minimal_order_kuantitas'] ?>" data-satuan="<?= htmlspecialchars($l['satuan']) ?>"><?= htmlspecialchars($l['nama_layanan']) ?> (Rp<?= number_format($l['harga_per_unit']) ?>/<?= htmlspecialchars($l['satuan']) ?>)</option>';
    <?php endforeach; ?>

    var row = '<tr>'+
        '<td>'+
            '<select class="form-control select-layanan" name="item['+window.itemIndex+'][id_layanan]" required>'+
                '<option value="">- Pilih Layanan -</option>' + layananOptions +
            '</select>'+
        '</td>'+
        '<td><input type="text" class="form-control" name="item['+window.itemIndex+'][deskripsi_item_spesifik]" required></td>'+
        '<td class="qty-cell">'+
            '<div class="qty-standard-wrapper">'+
                '<input type="number" step="0.01" min="0" class="form-control qty-item-standard" name="item['+window.itemIndex+'][kuantitas]" required>'+
            '</div>'+ 
            '<div class="qty-m2-wrapper" style="display:none;max-width:120px;margin:auto;">'+
                '<div class="text-center small" style="margin-bottom:2px;">Panjang (m)</div>'+ 
                '<input type="number" step="0.01" min="0" class="form-control panjang-item mb-1 text-center" name="item['+window.itemIndex+'][panjang_karpet]" placeholder="Panjang">'+ 
                '<div class="text-center" style="font-size:18px;font-weight:bold;">×</div>'+ 
                '<div class="text-center small" style="margin-bottom:2px;">Lebar (m)</div>'+ 
                '<input type="number" step="0.01" min="0" class="form-control lebar-item mt-1 text-center" name="item['+window.itemIndex+'][lebar_karpet]" placeholder="Lebar">'+ 
                '<div class="text-center" style="font-size:20px;font-weight:bold;">=</div>'+ 
                '<div class="text-center small" style="margin-bottom:2px;">Luas (m²)</div>'+ 
                '<input type="text" class="form-control qty-item qty-item-standard text-center" style="font-size:18px;font-weight:bold;background:#f8f9fa;" name="item['+window.itemIndex+'][kuantitas]" readonly placeholder="m²">'+ 
            '</div>'+ 
        '</td>'+ 
        '<td><input type="text" class="form-control harga-item" name="item['+window.itemIndex+'][harga_saat_pesan]" readonly></td>'+ 
        '<td><input type="text" class="form-control subtotal-item" name="item['+window.itemIndex+'][subtotal_item]" readonly></td>'+ 
        '<td><input type="text" class="form-control" name="item['+window.itemIndex+'][catatan_item]"></td>'+ 
        '<td><button type="button" class="btn btn-danger btn-sm btnHapusItem">Hapus</button></td>'+ 
        '<input type="hidden" name="item['+window.itemIndex+'][estimasi_hari]" value="0">'+ 
    '</tr>';
    $('#tabelItem tbody').append(row);
    window.itemIndex++;
});

// Satu fungsi update UI & kalkulasi qty/subtotal
function updateItemRowTambah(tr) {
    var selectedOption = tr.find('.select-layanan').find(':selected');
    var satuan = (selectedOption.data('satuan') || '').toLowerCase();
    var harga = parseFloat(selectedOption.data('harga')) || 0;
    if (satuan === 'm2' || satuan === 'm²') {
        tr.find('.qty-standard-wrapper').hide();
        tr.find('.qty-m2-wrapper').show();
        tr.find('.panjang-item').prop('readonly', false).show();
        tr.find('.lebar-item').prop('readonly', false).show();
        tr.find('.qty-item-standard').prop('readonly', true);
        var panjang = parseFloat(tr.find('.panjang-item').val()) || 0;
        var lebar = parseFloat(tr.find('.lebar-item').val()) || 0;
        var qty = panjang * lebar;
        tr.find('.qty-item-standard').val(qty > 0 ? qty.toFixed(2) : '');
    } else {
        tr.find('.qty-standard-wrapper').show();
        tr.find('.qty-m2-wrapper').hide();
        tr.find('.panjang-item').val('').hide();
        tr.find('.lebar-item').val('').hide();
        tr.find('.qty-item-standard').prop('readonly', false);
        var qty = parseFloat(tr.find('.qty-item-standard').val()) || 0;
    }
    var subtotal = harga * qty;
    tr.find('.harga-item').val(harga > 0 ? harga.toLocaleString('id-ID') : '');
    tr.find('.subtotal-item').val(subtotal > 0 ? subtotal.toFixed(2) : '');
    hitungTotal();
    setTanggalEstimasi();
}

// Event handler untuk semua perubahan input
$('#tabelItem').on('change input', '.select-layanan, .qty-item-standard, .panjang-item, .lebar-item', function(e) {
    var tr = $(this).closest('tr');
    updateItemRowTambah(tr);
});
// Hapus item
$('#tabelItem').on('click', '.btnHapusItem', function(){
    $(this).closest('tr').remove();
    hitungTotal();
    setTanggalEstimasi();
});

function setTanggalEstimasi() {
    var minEstimasi = Infinity;
    var hasItems = false;

    $('#tabelItem .select-layanan').each(function(){
        var selectedOption = $(this).find(':selected');
        if (selectedOption.length && selectedOption.val() !== "") {
            hasItems = true;
            var estimasi = parseInt(selectedOption.data('estimasi')) || 0;
            if (estimasi < minEstimasi) {
                minEstimasi = estimasi;
            }
        }
    });

    if (hasItems && minEstimasi !== Infinity) {
        var tglSelesai = new Date();
        tglSelesai.setDate(tglSelesai.getDate() + minEstimasi);
        
        var dd = String(tglSelesai.getDate()).padStart(2, '0');
        var mm = String(tglSelesai.getMonth() + 1).padStart(2, '0');
        var yyyy = tglSelesai.getFullYear();
        
        var tglDisplay = dd + '/' + mm + '/' + yyyy;
        var tglValue = yyyy + '-' + mm + '-' + dd;

        $('#tanggal_estimasi_selesai_display').val(tglDisplay);
        $('#tanggal_estimasi_selesai').val(tglValue);
    } else {
        $('#tanggal_estimasi_selesai_display').val('');
        $('#tanggal_estimasi_selesai').val('');
    }
}
function hitungTotal(){
    var total = 0;
    $('#tabelItem .subtotal-item').each(function(){
        var val = parseFloat($(this).val()) || 0;
        total += val;
    });

    $('#total_harga_keseluruhan').val(total.toFixed(2));
    $('#display_total_akhir').text(total.toLocaleString('id-ID'));
}

// Paksa hanya angka murni saat input
$(document).on('input', '.harga-item, .subtotal-item', function() {
    this.value = this.value.replace(/[^0-9.]/g, '');
});
// Bersihkan format angka sebelum submit
$('#formPesanan').on('submit',function(){
    $(this).find('.harga-item, .subtotal-item, #total_harga_keseluruhan').each(function(){
        this.value=this.value.replace(/[^0-9.]/g,'');
    });
    // Kuantitas biarkan saja, tidak diformat
});

// Tambah baris item pertama saat halaman dimuat
$(document).ready(function() {
    if ($('#tabelItem tbody tr').length === 0) {
        $('#btnTambahItem').click();
    }
});
</script>
