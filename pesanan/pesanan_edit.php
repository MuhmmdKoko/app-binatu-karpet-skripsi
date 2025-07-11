<?php
// --- Edit Pesanan (tambah/hapus item & ubah catatan) ---
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin','Karyawan'])) {
    echo '<script>alert("Akses ditolak");window.location="../index.php";</script>';
    exit;
}
include "pengaturan/koneksi.php";
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if(!$id){
    echo '<script>alert("ID pesanan tidak valid");window.history.back();</script>';
    exit;
}
// ambil pesanan
$qPes = mysqli_query($konek,"SELECT * FROM pesanan WHERE id_pesanan=$id");
if(!$rowPes = mysqli_fetch_assoc($qPes)){
    echo '<script>alert("Pesanan tidak ditemukan");window.history.back();</script>';
    exit;
}
if(in_array($rowPes['status_pesanan_umum'],['Diambil','Dibatalkan'])){
    echo '<script>alert("Pesanan telah '.$rowPes['status_pesanan_umum'].' sehingga tidak bisa diedit");window.location="?page=pesanan_detail&id='.$id.'";</script>';
    exit;
}
// daftar layanan
$layanan=[];
$resL=mysqli_query($konek,"SELECT * FROM layanan ORDER BY nama_layanan");
while($r=mysqli_fetch_assoc($resL)){$layanan[]=$r;}

// ambil detail
$detailArr=[];
$resD=mysqli_query($konek,"SELECT * FROM detail_pesanan WHERE id_pesanan=$id");
while($d=mysqli_fetch_assoc($resD)){$detailArr[]=$d;}

$err=$sukses='';
// PATCH: Ambil kode promo dari tabel promosi jika kode_promo kosong tapi id_promosi ada
$kode_promo_pesanan = (isset($rowPes['kode_promo']) && $rowPes['kode_promo']) ? $rowPes['kode_promo'] : '';
if (!$kode_promo_pesanan && !empty($rowPes['id_promosi'])) {
    $qPromo = mysqli_query($konek, "SELECT kode_promo FROM promosi WHERE id_promosi=".(int)$rowPes['id_promosi']);
    if ($promoRow = mysqli_fetch_assoc($qPromo)) {
        $kode_promo_pesanan = $promoRow['kode_promo'];
    }
}
if(isset($_POST['submit_edit'])){
    try {
    // Ambil data dari form
    $catatan_pesanan = mysqli_real_escape_string($konek, trim($_POST['catatan_pesanan']));
    $items = $_POST['item'] ?? [];

    // Hitung ulang total harga dan estimasi hari maksimum dari item yang di-submit
    $total_harga_baru = 0;
    $max_estimasi_hari = 0;

    // Untuk menghitung estimasi, kita perlu tahu estimasi per layanan dari DB
    $estimasi_layanan = [];
    $resL = mysqli_query($konek, "SELECT id_layanan, estimasi_waktu_hari FROM layanan");
    while($rowL = mysqli_fetch_assoc($resL)) {
        $estimasi_layanan[$rowL['id_layanan']] = (int)$rowL['estimasi_waktu_hari'];
    }

    foreach($items as $it){
        $id_layanan = intval($it['id_layanan'] ?? 0);
        if($id_layanan == 0) continue;

        $harga = floatval(str_replace(',', '', $it['harga_saat_pesan'] ?? '0'));
        $qty = floatval($it['kuantitas'] ?? 0);
        $subtotal = $harga * $qty;
        
        $total_harga_baru += $subtotal;
        
        $hari_estimasi_item = $estimasi_layanan[$id_layanan] ?? 0;
        if($hari_estimasi_item > $max_estimasi_hari) {
            $max_estimasi_hari = $hari_estimasi_item;
        }
    }

    // Hitung ulang tanggal estimasi selesai berdasarkan tanggal masuk asli dari pesanan
    $tanggal_masuk_asli = $rowPes['tanggal_masuk'];
    $tgl_masuk_obj = new DateTime($tanggal_masuk_asli);
    if ($max_estimasi_hari > 0) {
        $tgl_masuk_obj->add(new DateInterval("P{$max_estimasi_hari}D"));
    }
    $tanggal_estimasi_selesai_baru = $tgl_masuk_obj->format('Y-m-d H:i:s');

    // Mulai transaksi database untuk memastikan konsistensi data
    mysqli_begin_transaction($konek);

        // Ambil total harga keseluruhan dari POST tanpa str_replace
        $total_harga_baru = isset($_POST['total_harga_keseluruhan']) ? floatval($_POST['total_harga_keseluruhan']) : 0;
        error_log("DEBUG FINAL: total_harga_baru=$total_harga_baru, POST=".$_POST['total_harga_keseluruhan']);
        // Ambil promo jika ada
        $id_promosi = (isset($_POST['id_promosi']) && intval($_POST['id_promosi']) > 0) ? intval($_POST['id_promosi']) : 'NULL';
        $diskon = isset($_POST['diskon']) ? floatval(str_replace(['.',','], ['', '.'], $_POST['diskon'])) : 0;
        $total_setelah_diskon = isset($_POST['total_setelah_diskon']) ? floatval($_POST['total_setelah_diskon']) : $total_harga_baru;
        // Jika kode promo kosong, pastikan semua field promo direset
        $kode_promo = isset($_POST['kode_promo']) ? trim($_POST['kode_promo']) : '';
        if ($kode_promo === '') {
            $id_promosi = 'NULL';
            $diskon = 0;
            $total_setelah_diskon = $total_harga_baru;
        }

        // DEBUG: Log total_harga_baru sebelum update
        error_log("DEBUG TOTAL: total_harga_baru=$total_harga_baru, diskon=$diskon, total_setelah_diskon=$total_setelah_diskon");
        // 1. Update tabel pesanan utama dengan total dan estimasi baru
        $sql_update_pesanan = "UPDATE pesanan SET 
                                catatan_pesanan='$catatan_pesanan', 
                                tanggal_estimasi_selesai='$tanggal_estimasi_selesai_baru', 
                                total_harga_keseluruhan=$total_harga_baru, 
                                id_promosi=$id_promosi, 
                                diskon=$diskon, 
                                total_setelah_diskon=$total_setelah_diskon
                              WHERE id_pesanan=$id";
        if (!mysqli_query($konek, $sql_update_pesanan)) {
            throw new Exception("Gagal memperbarui pesanan utama: " . mysqli_error($konek));
        }

        // 2. Hapus semua detail pesanan lama
        if (!mysqli_query($konek, "DELETE FROM detail_pesanan WHERE id_pesanan=$id")) {
            throw new Exception("Gagal menghapus detail pesanan lama: " . mysqli_error($konek));
        }

        // 3. Insert ulang semua detail pesanan baru
        foreach($items as $it){
            $id_layanan = intval($it['id_layanan'] ?? 0);
            if($id_layanan == 0) continue;
            $harga_saat_pesan = isset($it['harga_saat_pesan']) ? floatval(str_replace(['.',','], ['', '.'], $it['harga_saat_pesan'])) : 0;
            $kuantitas = isset($it['kuantitas']) ? floatval(str_replace(',', '.', $it['kuantitas'])) : 0;
            $catatan_item = $it['catatan_item'] ?? '';
            $panjang_karpet = isset($it['panjang_karpet']) ? floatval($it['panjang_karpet']) : 0;
            $lebar_karpet = isset($it['lebar_karpet']) ? floatval($it['lebar_karpet']) : 0;
            $deskripsi = mysqli_real_escape_string($konek, $it['deskripsi_item_spesifik'] ?? '');
            $cat_item = mysqli_real_escape_string($konek, $catatan_item ?? ($it['catatan_item'] ?? ''));
            // Ambil satuan layanan dan minimal order
            $satuan_layanan = '';
            $minimal_aktif = false;
            $minimal_qty = 0;
            foreach ($layanan as $l) {
                if ($l['id_layanan'] == $id_layanan) {
                    $satuan_layanan = strtolower(trim($l['satuan']));
                    $minimal_aktif = ($l['minimal_order_aktif'] == 1 || $l['minimal_order_aktif'] === true || $l['minimal_order_aktif'] === '1');
                    $minimal_qty = floatval($l['minimal_order_kuantitas']);
                    break;
                }
            }
            // Logic identik tambah pesanan
            if ($satuan_layanan === 'm2' || $satuan_layanan === 'm²') {
                if ($panjang_karpet <= 0 || $lebar_karpet <= 0) {
                    throw new Exception('Panjang dan lebar karpet wajib diisi dan > 0 untuk layanan karpet.');
                }
                $qty = $panjang_karpet * $lebar_karpet;
                if($minimal_aktif && $minimal_qty > 0 && $qty > 0 && $qty < $minimal_qty) {
                    $qty = $minimal_qty;
                    $cat_item .= ($cat_item ? ' ' : '') . '[Subtotal dihitung minimal order: ' . $minimal_qty . ']';
                }
                $kuantitas = $qty;
                $subtotal_item = $harga_saat_pesan * $qty;
            } else {
                $qty = $kuantitas;
                if($minimal_aktif && $minimal_qty > 0 && $qty > 0 && $qty < $minimal_qty) {
                    $qty = $minimal_qty;
                    $cat_item .= ($cat_item ? ' ' : '') . '[Subtotal dihitung minimal order: ' . $minimal_qty . ']';
                }
                $kuantitas = $qty;
                $subtotal_item = $harga_saat_pesan * $qty;
            }
            $sql_insert_detail = "INSERT INTO detail_pesanan (id_pesanan, id_layanan, deskripsi_item_spesifik, kuantitas, harga_saat_pesan, subtotal_item, catatan_item, status_item_terkini, panjang_karpet, lebar_karpet) VALUES ($id, $id_layanan, '$deskripsi', $qty, $harga_saat_pesan, $subtotal_item, '$cat_item', 'Diterima', ".($panjang_karpet!==null?$panjang_karpet:'NULL').", ".($lebar_karpet!==null?$lebar_karpet:'NULL').")";
            if (!mysqli_query($konek, $sql_insert_detail)) {
                throw new Exception("Gagal menyimpan item detail pesanan: " . mysqli_error($konek));
            }
        }

        // Jika semua query berhasil, commit transaksi
        mysqli_commit($konek);
        echo '<script>alert("Pesanan berhasil diperbarui");window.location="?page=pesanan_detail&id='.$id.'";</script>';
        exit;
    } catch (Exception $e) {
        // Jika terjadi error di salah satu query, batalkan semua perubahan
        mysqli_rollback($konek);
        $err = "Terjadi kesalahan: " . $e->getMessage();
        // Tetap di halaman edit untuk menampilkan pesan error
    }
}
?>

<div class="container-fluid mt-4">
    <h3 class="mb-3">Edit Pesanan (Invoice <?= htmlspecialchars($rowPes['nomor_invoice']) ?>)</h3>
    <?php if($err): ?><div class="alert alert-danger"><?= $err ?></div><?php endif; ?>
    <form id="formEdit" method="post">
        <input type="hidden" name="tanggal_estimasi_selesai" id="tanggal_estimasi_selesai" value="<?= htmlspecialchars($rowPes['tanggal_estimasi_selesai']) ?>">
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">1. Catatan Pesanan</h5>
                <input type="text" name="catatan_pesanan" class="form-control" value="<?= htmlspecialchars($rowPes['catatan_pesanan']) ?>">
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">Layanan / Item</h5>
                <div class="table-responsive">
                    <table class="table" id="tabelItem">
                        <thead>
                            <tr>
                                <th>Layanan</th><th>Deskripsi</th><th>Karpet (m<sup>2</sup>) / Qty</th><th>Harga</th><th>Subtotal</th><th>Catatan</th><th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($detailArr as $idx => $det): ?>
                            <tr>
                                <td>
                                    <select class="form-control select-layanan" name="item[<?= $idx ?>][id_layanan]" data-estimasi="<?= $det['id_layanan'] ?>">
                                        <?php foreach($layanan as $l): ?>
                                            <option value="<?= $l['id_layanan'] ?>" data-harga="<?= $l['harga_per_unit'] ?>" data-estimasi="<?= $l['estimasi_waktu_hari'] ?>" data-satuan="<?= htmlspecialchars($l['satuan']) ?>" <?= $det['id_layanan']==$l['id_layanan']?'selected':'' ?>><?= $l['nama_layanan'] ?> (Rp<?= number_format($l['harga_per_unit']) ?>/<?= $l['satuan'] ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="text" class="form-control" name="item[<?= $idx ?>][deskripsi_item_spesifik]" value="<?= htmlspecialchars($det['deskripsi_item_spesifik']) ?>" required></td>
                                <td>
                                <?php
                                // Deteksi satuan layanan dari $det['id_layanan']
                                $satuan_layanan = '';
                                foreach($layanan as $l) {
                                    if ($l['id_layanan'] == $det['id_layanan']) {
                                        $satuan_layanan = strtolower(trim($l['satuan']));
                                        break;
                                    }
                                }
                                $isKarpet = ($satuan_layanan === 'm2' || $satuan_layanan === 'm²');
                                ?>
                                <div class="qty-standard-wrapper" style="<?= ($isKarpet ? 'display:none' : 'display:block') ?>;">
                                    <input type="number" step="0.01" min="0" class="form-control qty-item-standard" name="item[<?= $idx ?>][kuantitas]" value="<?= $det['kuantitas'] ?>" <?= $isKarpet ? 'readonly' : '' ?> required>
                                </div>
                                <div class="qty-m2-wrapper" style="display:<?= ($isKarpet ? 'block' : 'none') ?>;max-width:120px;margin:auto;">
                                    <div class="text-center small" style="margin-bottom:2px;">Panjang (m)</div>
                                    <input type="number" step="0.01" min="0" class="form-control panjang-item mb-1 text-center" name="item[<?= $idx ?>][panjang_karpet]" value="<?= htmlspecialchars($det['panjang_karpet'] ?? '') ?>" placeholder="Panjang">
                                    <div class="text-center" style="font-size:18px;font-weight:bold;">×</div>
                                    <div class="text-center small" style="margin-bottom:2px;">Lebar (m)</div>
                                    <input type="number" step="0.01" min="0" class="form-control lebar-item mt-1 text-center" name="item[<?= $idx ?>][lebar_karpet]" value="<?= htmlspecialchars($det['lebar_karpet'] ?? '') ?>" placeholder="Lebar">
                                    <div class="text-center" style="font-size:20px;font-weight:bold;">=</div>
                                    <div class="text-center small" style="margin-bottom:2px;">Luas (m²)</div>
                                    <input type="text" class="form-control qty-item qty-item-standard text-center" style="font-size:18px;font-weight:bold;background:#f8f9fa;" name="item[<?= $idx ?>][kuantitas]" value="<?= $det['kuantitas'] ?>" readonly placeholder="m²">
                                </div>
                                </td>
                                <td><input type="text" class="form-control harga-item" name="item[<?= $idx ?>][harga_saat_pesan]" value="<?= str_replace([',','.'], '', $det['harga_saat_pesan']) ?>" readonly></td>
                                <td><input type="text" class="form-control subtotal-item" name="item[<?= $idx ?>][subtotal_item]" value="<?= str_replace([',','.'], '', $det['subtotal_item']) ?>" readonly></td>
                                <td><input type="text" class="form-control" name="item[<?= $idx ?>][catatan_item]" value="<?= htmlspecialchars($det['catatan_item']) ?>"></td>
                                <td><button type="button" class="btn btn-danger btn-sm btnHapusItem">Hapus</button></td>
                                <input type="hidden" name="item[<?= $idx ?>][estimasi_hari]" value="<?= $det['estimasi_hari'] ?? 0 ?>">
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-primary" id="btnTambahItem">+ Tambah Item</button>
            </div>
        </div>
        <!-- Konfirmasi -->
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">3. Konfirmasi Pesanan</h5>
                <div class="row mb-2 align-items-end">
    <div class="col-md-5 col-12 mb-2 mb-md-0">
        <label for="kode_promo" class="form-label">Kode Promo</label>
<div class="input-group mb-2">
    <select class="form-select" id="dropdown_promo">
        <option value="">-- Pilih Promo Aktif --</option>
        <option value="__manual__">Ketik Manual</option>
    </select>
    <input type="text" class="form-control" name="kode_promo" id="kode_promo" value="<?= htmlspecialchars($kode_promo_pesanan) ?>" placeholder="Masukkan kode promo" autocomplete="off" style="display:none">
    <button type="button" class="btn btn-info d-flex align-items-center gap-1" id="btnCekPromo">
        <span>Cek Promo</span>
        <span class="spinner-border spinner-border-sm d-none" id="promoSpinner" role="status" aria-hidden="true"></span>
    </button>
</div>
<script>
// --- Logic JS identik dengan tambah pesanan ---
function updateItemRowEdit(tr) {
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
    var minimalAktif = selectedOption.data('minimal-aktif') == 1 || selectedOption.data('minimal-aktif') === true || selectedOption.data('minimal-aktif') === '1';
    var minimalQty = parseFloat(selectedOption.data('minimal-qty')) || 0;
    var showMinimalNote = false;
    var usedQty = qty;
    if(minimalAktif && minimalQty > 0 && qty > 0 && qty < minimalQty) {
        usedQty = minimalQty;
        showMinimalNote = true;
    }
    var subtotal = harga * usedQty;
    tr.find('.harga-item').val(harga > 0 ? harga.toLocaleString('id-ID') : '');
    tr.find('.subtotal-item').val(subtotal > 0 ? subtotal.toFixed(2) : '');
    tr.find('.minimal-order-note').remove();
    if(showMinimalNote) {
        tr.find('.subtotal-item').after('<div class="minimal-order-note text-warning small">Subtotal dihitung minimal order: '+minimalQty+'</div>');
    }
    hitungTotalEdit();
}
function hitungTotalEdit() {
    var total = 0;
    $('#tabelItem tbody tr').each(function(){
        var subtotal = parseFloat($(this).find('.subtotal-item').val().replace(/[^0-9.,]/g,'').replace(',','.')) || 0;
        total += subtotal;
    });
    $('#total_harga_keseluruhan').val(total.toFixed(2));
    $('#display_total_akhir').text(total.toLocaleString('id-ID'));
    $('#total_setelah_diskon').val(total.toFixed(2));
}
// Event handler untuk semua perubahan input
$('#tabelItem').on('change input', '.select-layanan, .qty-item-standard, .panjang-item, .lebar-item', function(e) {
    var tr = $(this).closest('tr');
    updateItemRowEdit(tr);
});
$('#tabelItem').on('click', '.btnHapusItem', function(){
    $(this).closest('tr').remove();
    hitungTotalEdit();
});
// --- Promo, diskon, dan validasi ---
$(function(){
    // Fetch promo list
    $.get('pesanan/promo_dropdown_fetch.php', {kode_promo_pesanan: '<?= addslashes($kode_promo_pesanan) ?>'}, function(list) {
        var dropdown = $('#dropdown_promo');
        dropdown.find('option:not([value=""]):not([value="__manual__"])').remove();
        var currentKode = '<?= addslashes($kode_promo_pesanan) ?>'.toLowerCase();
        var found = false;
        $.each(list, function(i, p) {
            var label = p.judul + ' ['+p.kode_promo+']';
            if(p.status !== 'aktif') label += ' (Tidak Aktif)';
            var opt = $('<option>').val((p.kode_promo||'').toLowerCase()).text(label);
            if(p.status !== 'aktif') opt.prop('disabled',true);
            if(p.kode_promo && p.kode_promo.toLowerCase() === currentKode && currentKode) {
                found = true;
                opt.prop('selected',true);
            }
            dropdown.append(opt);
        });
        if(!found && currentKode) {
            dropdown.append($('<option>').val(currentKode).text('Promo Tidak Terdaftar').prop('selected',true).prop('disabled',true));
        }
    },'json');
    // Handle dropdown selection change
    $('#dropdown_promo').on('change', function(){
        var val = $(this).val();
        if(val === '__manual__') {
            $('#kode_promo').val('').show().focus();
            resetPromoEdit();
        } else if(val) {
            $('#kode_promo').val(val).hide();
            $('#btnCekPromo').trigger('click');
        } else {
            $('#kode_promo').val('').hide();
            resetPromoEdit();
        }
    });
    $('#kode_promo').on('input', function(){
        $('#dropdown_promo').val('__manual__');
    });
});
</script>
        <?php if (!empty($rowPes['kode_promo'])): ?>
            <span class="badge bg-success mb-1">Promo aktif: <?= htmlspecialchars($rowPes['kode_promo']) ?></span>
        <?php endif; ?>
        <div id="promo_info" class="small"></div>
        <input type="hidden" name="id_promosi" id="id_promosi" value="<?= htmlspecialchars($rowPes['id_promosi'] ?? '') ?>">
        <input type="hidden" name="diskon" id="diskon" value="<?= htmlspecialchars($rowPes['diskon'] ?? '') ?>">
    </div>
                    <div class="col-md-8 text-end">
                        <div id="ringkasan_diskon" style="display:none">
                            <span class="text-muted">Diskon Promo: </span>Rp<span id="display_diskon">0</span><br>
                        </div>
                        <label>Total Harga Keseluruhan</label>
                        <input type="text" class="form-control" id="total_display" readonly>
                        <input type="hidden" name="total_harga_keseluruhan" id="total_harga_keseluruhan">
                        <input type="hidden" name="total_setelah_diskon" id="total_setelah_diskon" value="<?= htmlspecialchars($rowPes['total_setelah_diskon'] ?? '') ?>">
                        <small class="text-muted">Pastikan total sudah sesuai sebelum menyimpan.</small>
                    </div>
                </div>
            </div>
        </div>
        <button type="submit" name="submit_edit" class="btn btn-success">Simpan Perubahan</button>
        <a href="?page=pesanan_detail&id=<?= $id ?>" class="btn btn-secondary">Batal</a>
    </form>
</div>
<script src="assets/libs/jquery/dist/jquery.min.js"></script>
<script>
var layanan = <?= json_encode($layanan) ?>;
function renderLayananOptions(){
 let html='';
 layanan.forEach(l=>{
   html+='<option value="'+l.id_layanan+'" data-harga="'+l.harga_per_unit+'" data-estimasi="'+l.estimasi_waktu_hari+'" data-minimal-aktif="'+l.minimal_order_aktif+'" data-minimal-qty="'+l.minimal_order_kuantitas+'" data-satuan="'+l.satuan+'">'+l.nama_layanan+' (Rp'+parseInt(l.harga_per_unit).toLocaleString()+'/'+l.satuan+')</option>';
 });
 return html;
}
if(typeof window.itemIndex === 'undefined'){window.itemIndex = $('#tabelItem tbody tr').length;}
$('#btnTambahItem').on('click', function(){
    var layananOptions = renderLayananOptions();
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
// Event handler untuk semua perubahan input
$('#tabelItem').on('change input', '.select-layanan, .qty-item-standard, .panjang-item, .lebar-item', function(e) {
    var tr = $(this).closest('tr');
    updateItemRowEdit(tr);
});
// Hapus item
$('#tabelItem').on('click', '.btnHapusItem', function(){
    $(this).closest('tr').remove();
    hitungTotal();
});
$('#tabelItem').on('click','.btnHapusItem',function(){ $(this).closest('tr').remove();hitungTotal();updatePromoTotal();});
function hitungTotal(){
 let total=0;
 $('#tabelItem .subtotal-item').each(function(){
   const val=parseFloat($(this).val().replace(/[^\d\.]/g,''))||0;
   total+=val;
 });
 $('#total_display').val(total.toLocaleString('id-ID'));
 $('#total_harga_keseluruhan').val(Math.round(total)); // angka bulat rupiah untuk submit
}
// Panggil hitung total
hitungTotal();

// --- Promo: AJAX cek kode promo dan update diskon (edit) ---
$('#btnCekPromo').on('click', function() {
    var kode = $('#kode_promo').val().trim();
    var total = parseFloat($('#total_harga_keseluruhan').val()) || 0;
    var $btn = $(this);
    var $spinner = $('#promoSpinner');
    if (!kode) {
        $('#promo_info').text('Kode promo wajib diisi').removeClass('text-success').addClass('text-danger');
        return;
    }
    $btn.prop('disabled', true);
    $spinner.removeClass('d-none');
    $.post('pesanan/promo_cek.php', {kode: kode, total: total}, function(res) {
        if (res.status === 'ok') {
            $('#promo_info').html('<span class="badge bg-success">'+res.msg+'</span>').removeClass('text-danger').addClass('text-success');
            $('#id_promosi').val(res.id_promosi);
            $('#diskon').val(res.diskon);
            $('#display_diskon').text(parseInt(res.diskon).toLocaleString('id-ID'));
            $('#ringkasan_diskon').show();
            var totalAkhir = Math.max(0, total - res.diskon);
            $('#total_display').val(totalAkhir.toLocaleString('id-ID'));
            $('#total_setelah_diskon').val(Math.round(totalAkhir));
        } else {
            $('#promo_info').html('<span class="badge bg-danger">'+res.msg+'</span>').removeClass('text-success').addClass('text-danger');
            $('#id_promosi').val('');
            $('#diskon').val('');
            $('#display_diskon').text('0');
            $('#ringkasan_diskon').hide();
            $('#total_display').val(total.toLocaleString('id-ID'));
            $('#total_setelah_diskon').val(total);
        }
    }, 'json').always(function() {
        $btn.prop('disabled', false);
        $spinner.addClass('d-none');
    });
});
// Jangan reset kode promo jika item berubah, hanya reset status promo dan diskon
$('#tabelItem').on('input change', '.subtotal-item', function() {
    $('#id_promosi').val('');
    $('#diskon').val('');
    $('#promo_info').text('');
    $('#ringkasan_diskon').hide();
    $('#display_diskon').text('0');
    updatePromoTotal();
});

function updatePromoTotal() {
    var total = parseFloat($('#total_harga_keseluruhan').val()) || 0;
    var diskon = parseFloat($('#diskon').val()) || 0;
    if (diskon > 0) {
        $('#ringkasan_diskon').show();
        $('#display_diskon').text(parseInt(diskon).toLocaleString('id-ID'));
        var totalAkhir = Math.max(0, total - diskon);
        $('#total_display').val(totalAkhir.toLocaleString('id-ID'));
        $('#total_setelah_diskon').val(Math.round(totalAkhir));
    } else {
        $('#ringkasan_diskon').hide();
        $('#total_display').val(total.toLocaleString('id-ID'));
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
$('#total_harga_keseluruhan').on('input change', updatePromoTotal);
// Inisialisasi ringkasan diskon jika sudah ada diskon
$(function(){
    var diskon = parseFloat($('#diskon').val())||0;
    if(diskon>0) { $('#ringkasan_diskon').show(); $('#display_diskon').text(parseInt(diskon).toLocaleString('id-ID')); }
    else { $('#ringkasan_diskon').hide(); }
    updatePromoTotal();
    // AUTO CEK PROMO JIKA FIELD SUDAH TERISI (EDIT)
    var kode = $('#kode_promo').val().trim();
    var total = parseFloat($('#total_harga_keseluruhan').val()) || 0;
    if(kode !== '') {
        $('#btnCekPromo').prop('disabled', true);
        $('#promoSpinner').removeClass('d-none');
        $.post('pesanan/promo_cek.php', {kode: kode, total: total}, function(res) {
            if (res.status === 'ok') {
                $('#promo_info').html('<span class="badge bg-success">'+res.msg+'</span>').removeClass('text-danger').addClass('text-success');
                $('#id_promosi').val(res.id_promosi);
                $('#diskon').val(res.diskon);
                $('#display_diskon').text(parseInt(res.diskon).toLocaleString('id-ID'));
                $('#ringkasan_diskon').show();
                var totalAkhir = Math.max(0, total - res.diskon);
                $('#total_display').val(totalAkhir.toLocaleString('id-ID'));
                $('#total_setelah_diskon').val(totalAkhir);
            } else {
                $('#promo_info').html('<span class="badge bg-danger">'+res.msg+'</span>').removeClass('text-success').addClass('text-danger');
                $('#id_promosi').val('');
                $('#diskon').val('');
                $('#display_diskon').text('0');
                $('#ringkasan_diskon').hide();
                $('#total_display').val(total.toLocaleString('id-ID'));
                $('#total_setelah_diskon').val(total);
            }
        }, 'json').always(function() {
            $('#btnCekPromo').prop('disabled', false);
            $('#promoSpinner').addClass('d-none');
        });
    }
});
function updateItemRowEdit(tr) {
    var selectedOption = tr.find('.select-layanan').find(':selected');
    var satuan = (selectedOption.data('satuan') || '').toLowerCase();
    var harga = parseFloat(selectedOption.data('harga')) || 0;
    var minimalAktif = selectedOption.data('minimal-aktif') == 1 || selectedOption.data('minimal-aktif') === true || selectedOption.data('minimal-aktif') === '1';
    var minimalQty = parseFloat(selectedOption.data('minimal-qty')) || 0;
    var showMinimalNote = false;
    var usedQty = 0;
    if (satuan === 'm2' || satuan === 'm²') {
        tr.find('.qty-standard-wrapper').hide();
        tr.find('.qty-m2-wrapper').show();
        tr.find('.panjang-item').prop('readonly', false).show();
        tr.find('.lebar-item').prop('readonly', false).show();
        tr.find('.qty-item-standard').prop('readonly', true);
        var panjang = parseFloat(tr.find('.panjang-item').val()) || 0;
        var lebar = parseFloat(tr.find('.lebar-item').val()) || 0;
        var qty = panjang * lebar;
        usedQty = qty;
        if(minimalAktif && minimalQty > 0 && qty > 0 && qty < minimalQty) {
            usedQty = minimalQty;
            showMinimalNote = true;
        }
        tr.find('.qty-item-standard').val(qty > 0 ? qty.toFixed(2) : '');
    } else {
        tr.find('.qty-standard-wrapper').show();
        tr.find('.qty-m2-wrapper').hide();
        tr.find('.panjang-item').val('').hide();
        tr.find('.lebar-item').val('').hide();
        tr.find('.qty-item-standard').prop('readonly', false);
        var qty = parseFloat(tr.find('.qty-item-standard').val()) || 0;
        usedQty = qty;
        if(minimalAktif && minimalQty > 0 && qty > 0 && qty < minimalQty) {
            usedQty = minimalQty;
            showMinimalNote = true;
        }
    }
    var subtotal = harga * usedQty;
    tr.find('.harga-item').val(harga > 0 ? harga.toLocaleString('id-ID') : '');
    tr.find('.subtotal-item').val(subtotal > 0 ? subtotal.toFixed(2) : '');
    tr.find('.minimal-order-note').remove();
    if(showMinimalNote) {
        tr.find('.subtotal-item').after('<div class="minimal-order-note text-warning small">Subtotal dihitung minimal order: '+minimalQty+'</div>');
    }
    hitungTotal();
}
$('#tabelItem').on('change input', '.select-layanan', function(e) {
    var tr = $(this).closest('tr');
    updateItemRowEdit(tr);
});
$('#tabelItem').on('change input', '.panjang-item, .lebar-item', function(e) {
    var tr = $(this).closest('tr');
    updateItemRowEdit(tr);
});
$('#tabelItem').on('change input', '.qty-item-standard', function(e) {
    var tr = $(this).closest('tr');
    updateItemRowEdit(tr);
});
// Paksa hanya angka murni saat input
$(document).on('input', '.harga-item, .subtotal-item', function() {
    this.value = this.value.replace(/[^0-9.]/g, '');
});
// Pastikan semua input harga satuan, subtotal, dan total setelah diskon adalah angka ribuan penuh/desimal sebelum submit
$('#formEdit').on('submit',function(){
  // Harga satuan dan subtotal item
  $(this).find('.harga-item, .subtotal-item').each(function(){
    // Hilangkan semua karakter selain angka dan titik/koma
    let val = $(this).val().replace(/[^0-9.,]/g,'');
    // Jika ada koma sebagai desimal, ubah ke titik
    val = val.replace(',', '.');
    // Hilangkan titik ribuan (jika ada), kecuali jika sebagai desimal
    if(val.split('.').length > 2){
      // Jika ada lebih dari satu titik, hanya titik terakhir sebagai desimal
      let parts = val.split('.');
      let desimal = parts.pop();
      val = parts.join('') + '.' + desimal;
    }
    $(this).val(val);
  });
  // Total setelah diskon
  let $totalSetelahDiskon = $('#total_setelah_diskon');
  if($totalSetelahDiskon.length){
    let val = $totalSetelahDiskon.val().replace(/[^0-9.,]/g,'');
    val = val.replace(',', '.');
    if(val.split('.').length > 2){
      let parts = val.split('.');
      let desimal = parts.pop();
      val = parts.join('') + '.' + desimal;
    }
    $totalSetelahDiskon.val(val);
  }
});
</script>