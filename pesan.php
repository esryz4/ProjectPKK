<?php
require_once 'includes/functions.php';
require_once 'config/pembayaran.php';
if (empty($_SESSION['user'])) { $_SESSION['next'] = 'pesan.php'; redirect('login.php'); }
if (!is_pelanggan()) { flash('info', 'Pesanan untuk pelanggan datang langsung dibuat lewat menu Pesanan > Pesanan Baru.'); redirect('pesanan_tambah.php'); }

$pl = db_one('SELECT * FROM pelanggan WHERE id_user=?', 'i', [$_SESSION['user']['id']]);
if (!$pl) { die('Data pelanggan untuk akun ini tidak ditemukan. Hubungi pengelola.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $items = []; $err = '';
    $L = $_POST['item_layanan'] ?? []; $B = $_POST['item_barang'] ?? []; $S = $_POST['item_spek'] ?? []; $J = $_POST['item_jumlah'] ?? [];
    foreach ($L as $i => $idl) {
        $barang = trim($B[$i] ?? ''); $jml = (float)($J[$i] ?? 0);
        if ($barang === '' && $jml <= 0) { continue; }
        $lay = db_one('SELECT * FROM layanan WHERE id_layanan=? AND aktif=1', 'i', [(int)$idl]);
        if (!$lay || $barang === '' || $jml <= 0) { $err = 'Lengkapi nama barang dan jumlah pada setiap baris.'; break; }
        $items[] = ['layanan' => (int)$idl, 'barang' => $barang, 'spek' => trim($S[$i] ?? ''), 'jumlah' => $jml, 'harga' => 0, 'subtotal' => 0];   // harga ditetapkan pengelola setelah barang dicek
    }
    if ($err === '' && !$items) { $err = 'Tambahkan minimal satu barang.'; }
    $metode = $_POST['metode_bayar'] ?? '';
    if ($err === '' && !in_array($metode, ['transfer', 'tunai'], true)) { $err = 'Pilih metode pembayaran terlebih dahulu.'; }
    if ($err === '') {
        try {
            $db->begin_transaction();
            $total = array_sum(array_column($items, 'subtotal'));
            $uid = $_SESSION['user']['id'];
            $id = db_exec('INSERT INTO pesanan (no_pesanan, id_pelanggan, id_user, tgl_masuk, total_biaya, status, status_bayar, metode_bayar, catatan, sumber) VALUES (?,?,?,?,?,?,?,?,?,?)',
                'siisdsssss', [buat_no_pesanan(), $pl['id_pelanggan'], $uid, date('Y-m-d'), $total, 'menunggu', 'belum', $metode, trim($_POST['catatan'] ?? ''), 'online']);
            foreach ($items as $it) {
                db_exec('INSERT INTO detail_pesanan (id_pesanan, id_layanan, nama_barang, spesifikasi, jumlah, harga_satuan, subtotal) VALUES (?,?,?,?,?,?,?)',
                    'iissddd', [$id, $it['layanan'], $it['barang'], $it['spek'], $it['jumlah'], $it['harga'], $it['subtotal']]);
            }
            db_exec('INSERT INTO log_status (id_pesanan, status, id_user, catatan) VALUES (?,?,?,?)', 'isis', [$id, 'menunggu', $uid, 'Pesanan online dibuat pelanggan']);
            $db->commit();
            flash('success', 'Pesanan terkirim. Silakan serahkan barang ke lokasi kami. Biaya akan kami tetapkan setelah barang dicek.');
            redirect('pesanan_saya.php?id=' . $id);
        } catch (mysqli_sql_exception $ex) {
            $db->rollback();
            $err = 'Pesanan gagal disimpan, coba lagi.';
        }
    }
    flash('danger', $err);
}
$layanan = db_all('SELECT * FROM layanan WHERE aktif=1 ORDER BY nama_layanan');
$judul = 'Pesan Sekarang';
require 'includes/header_publik.php';
?>
<h4 class="mb-1">Buat Pesanan</h4>
<p class="text-muted">Isi barang yang akan dilapisi. Biaya ditetapkan oleh pemilik/pegawai setelah barang dicek di lokasi, lalu nominalnya muncul di halaman Pesanan Saya.</p>
<form method="post"><?= csrf_field() ?>
<div class="card mb-3"><div class="card-body">
  <div class="d-flex justify-content-between mb-2"><h6 class="mb-0"><i class="bi bi-box-seam me-1"></i>Barang yang Akan Dilapisi</h6>
    <button type="button" class="btn btn-sm btn-outline-dark" id="tambahBaris"><i class="bi bi-plus-lg"></i> Tambah barang</button></div>
  <div id="daftarItem"></div>
</div></div>
<div class="card mb-3"><div class="card-body"><label class="form-label">Catatan untuk kami</label><input name="catatan" class="form-control" placeholder="Opsional, mis. hasil akhir yang diinginkan"></div></div>
<div class="card mb-3"><div class="card-body">
  <h6 class="mb-3"><i class="bi bi-wallet2 me-1"></i>Metode Pembayaran</h6>
  <div class="d-flex flex-wrap gap-3 mb-3">
    <div class="form-check"><input class="form-check-input" type="radio" name="metode_bayar" id="mTf" value="transfer" required <?= ($_POST['metode_bayar'] ?? '') === 'transfer' ? 'checked' : '' ?>><label class="form-check-label" for="mTf"><i class="bi bi-bank me-1"></i>Transfer Bank</label></div>
    <div class="form-check"><input class="form-check-input" type="radio" name="metode_bayar" id="mTunai" value="tunai" <?= ($_POST['metode_bayar'] ?? '') === 'tunai' ? 'checked' : '' ?>><label class="form-check-label" for="mTunai"><i class="bi bi-cash me-1"></i>Tunai (bayar di lokasi)</label></div>
  </div>
  <div id="infoTransfer" style="display:none"><?= blok_pembayaran('transfer') ?></div>
  <div id="infoTunai" style="display:none"><?= blok_pembayaran('tunai') ?></div>
  <div id="infoPilih" class="small text-muted">Pilih metode untuk melihat petunjuk pembayaran. Pembayaran dilakukan setelah biaya kami tetapkan.</div>
</div></div>
<div class="text-end"><a href="akun.php" class="btn btn-outline-secondary me-2">Batal</a><button class="btn btn-dark"><i class="bi bi-send me-1"></i>Kirim Pesanan</button></div>
</form>

<template id="tplItem">
  <div class="border rounded p-2 mb-2 item-row"><div class="row g-2">
    <div class="col-md-6"><select name="item_layanan[]" class="form-select form-select-sm sel-layanan">
      <?php foreach ($layanan as $l): ?><option value="<?= $l['id_layanan'] ?>"><?= e($l['nama_layanan']) ?> (<?= e($l['satuan']) ?>)</option><?php endforeach; ?>
    </select></div>
    <div class="col-md-6"><input name="item_barang[]" class="form-control form-control-sm" placeholder="Nama barang logam"></div>
    <div class="col-12"><input name="item_spek[]" class="form-control form-control-sm" placeholder="Keterangan (bahan, ukuran, dll.)"></div>
    <div class="col-9 col-md-10"><input type="number" name="item_jumlah[]" class="form-control form-control-sm in-jumlah" min="0" step="0.01" placeholder="Berat (kg)"></div>
    <div class="col-3 col-md-2 text-end"><button type="button" class="btn btn-sm btn-outline-danger hapus-baris"><i class="bi bi-x"></i></button></div>
  </div></div>
</template>
<script>
(function () {
  var wrap = document.getElementById('daftarItem'), tpl = document.getElementById('tplItem');
  function tambah() {
    if (!tpl.content.querySelector('option')) { alert('Layanan belum tersedia.'); return; }
    var node = tpl.content.cloneNode(true), r = node.querySelector('.item-row');
    r.querySelector('.hapus-baris').addEventListener('click', function () { r.remove(); });
    wrap.appendChild(node);
  }
  document.getElementById('tambahBaris').addEventListener('click', tambah);
  tambah();
})();
</script>
<script>
(function () {
  var q = document.getElementById('infoTunai'), t = document.getElementById('infoTransfer'), p = document.getElementById('infoPilih');
  function tampil() {
    var v = (document.querySelector('input[name="metode_bayar"]:checked') || {}).value;
    q.style.display = v === 'tunai' ? '' : 'none';
    t.style.display = v === 'transfer' ? '' : 'none';
    p.style.display = v ? 'none' : '';
  }
  document.querySelectorAll('input[name="metode_bayar"]').forEach(function (r) { r.addEventListener('change', tampil); });
  tampil();
})();
</script>
<?php require 'includes/footer.php'; ?>
