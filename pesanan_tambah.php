<?php
require_once 'includes/functions.php';
login_required();
if (is_owner()) { flash('danger', 'Pemilik hanya dapat melihat data. Perubahan dilakukan oleh admin.'); redirect('pesanan.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $errors = [];
    $id_pelanggan = (int)($_POST['id_pelanggan'] ?? 0);
    $namaBaru = trim($_POST['pelanggan_nama'] ?? '');
    if ($id_pelanggan === 0 && $namaBaru === '') { $errors[] = 'Pilih pelanggan atau isi data pelanggan baru.'; }

    // Susun item pesanan
    $items = [];
    $L = $_POST['item_layanan'] ?? []; $B = $_POST['item_barang'] ?? []; $S = $_POST['item_spek'] ?? [];
    $J = $_POST['item_jumlah'] ?? []; $H = $_POST['item_harga'] ?? [];
    foreach ($L as $i => $idl) {
        $barang = trim($B[$i] ?? ''); $jml = (float)($J[$i] ?? 0); $idl = (int)$idl;
        if ($barang === '' && $jml <= 0) { continue; }
        $lay = db_one('SELECT * FROM layanan WHERE id_layanan=? AND aktif=1', 'i', [$idl]);
        if (!$lay || $barang === '' || $jml <= 0) { $errors[] = 'Baris barang ke-' . ($i + 1) . ' belum lengkap (layanan, nama barang, jumlah).'; continue; }
        $harga = ($H[$i] ?? '') !== '' ? (float)$H[$i] : (float)$lay['harga_satuan'];
        if ($harga < 0) { $harga = 0; }
        $items[] = ['layanan' => $idl, 'barang' => $barang, 'spek' => trim($S[$i] ?? ''), 'jumlah' => $jml, 'harga' => $harga, 'subtotal' => round($jml * $harga, 2)];
    }
    if (!$items && !$errors) { $errors[] = 'Tambahkan minimal satu barang yang akan dilapisi.'; }

    $total = array_sum(array_column($items, 'subtotal'));
    $dp = (float)($_POST['dp_jumlah'] ?? 0);
    if ($dp < 0 || $dp > $total) { $errors[] = 'Pembayaran awal tidak boleh negatif atau melebihi total biaya.'; }

    if (!$errors) {
        global $db;
        try {
            $db->begin_transaction();
            if ($id_pelanggan === 0) {
                $id_pelanggan = db_exec('INSERT INTO pelanggan (nama, no_hp, alamat) VALUES (?,?,?)', 'sss',
                    [$namaBaru, trim($_POST['pelanggan_hp'] ?? ''), trim($_POST['pelanggan_alamat'] ?? '')]);
            }
            $uid = $_SESSION['user']['id'];
            $estimasi = ($_POST['tgl_estimasi'] ?? '') ?: null;
            $id = db_exec('INSERT INTO pesanan (no_pesanan, id_pelanggan, id_user, tgl_masuk, tgl_estimasi, total_biaya, status, status_bayar, catatan) VALUES (?,?,?,?,?,?,?,?,?)',
                'siissdsss', [buat_no_pesanan(), $id_pelanggan, $uid, $_POST['tgl_masuk'] ?: date('Y-m-d'), $estimasi, $total, 'diterima', 'belum', trim($_POST['catatan'] ?? '')]);
            foreach ($items as $it) {
                db_exec('INSERT INTO detail_pesanan (id_pesanan, id_layanan, nama_barang, spesifikasi, jumlah, harga_satuan, subtotal) VALUES (?,?,?,?,?,?,?)',
                    'iissddd', [$id, $it['layanan'], $it['barang'], $it['spek'], $it['jumlah'], $it['harga'], $it['subtotal']]);
            }
            db_exec('INSERT INTO log_status (id_pesanan, status, id_user, catatan) VALUES (?,?,?,?)', 'isis', [$id, 'diterima', $uid, 'Barang diterima']);
            if ($dp > 0) {
                $metode = in_array($_POST['dp_metode'] ?? '', ['tunai', 'transfer', 'qris'], true) ? $_POST['dp_metode'] : 'tunai';
                db_exec('INSERT INTO pembayaran (id_pesanan, id_user, tgl_bayar, jumlah, metode, keterangan) VALUES (?,?,?,?,?,?)',
                    'iisdss', [$id, $uid, date('Y-m-d'), $dp, $metode, $dp >= $total ? 'Pelunasan' : 'DP']);
                sinkron_status_bayar($id);
            }
            $db->commit();
            flash('success', 'Pesanan berhasil disimpan. Total biaya: ' . rupiah($total) . '.');
            redirect('pesanan_detail.php?id=' . $id);
        } catch (mysqli_sql_exception $ex) {
            $db->rollback();
            $errors[] = 'Gagal menyimpan pesanan: ' . $ex->getMessage();
        }
    }
    flash('danger', implode(' ', $errors));
}

$pelanggan = db_all('SELECT id_pelanggan, nama, no_hp FROM pelanggan ORDER BY nama');
$layanan = db_all('SELECT * FROM layanan WHERE aktif=1 ORDER BY nama_layanan');
$judul = 'Pesanan Baru';
require 'includes/header.php';
?>
<h4 class="mb-3">Pesanan Baru</h4>
<form method="post" id="formPesanan">
<?= csrf_field() ?>
<div class="row g-3">
  <div class="col-lg-5">
    <div class="card mb-3"><div class="card-body">
      <h6 class="mb-3"><i class="bi bi-person me-1"></i>Pelanggan</h6>
      <select name="id_pelanggan" id="selPelanggan" class="form-select mb-3">
        <option value="0">+ Pelanggan baru</option>
        <?php foreach ($pelanggan as $p): ?><option value="<?= $p['id_pelanggan'] ?>"><?= e($p['nama']) ?> <?= $p['no_hp'] ? '(' . e($p['no_hp']) . ')' : '' ?></option><?php endforeach; ?>
      </select>
      <div id="boxBaru">
        <input name="pelanggan_nama" class="form-control mb-2" placeholder="Nama pelanggan baru">
        <input name="pelanggan_hp" class="form-control mb-2" placeholder="No. HP / WhatsApp">
        <textarea name="pelanggan_alamat" class="form-control" rows="2" placeholder="Alamat"></textarea>
      </div>
    </div></div>
    <div class="card"><div class="card-body">
      <h6 class="mb-3"><i class="bi bi-calendar-event me-1"></i>Jadwal &amp; Catatan</h6>
      <div class="row g-2 mb-2">
        <div class="col-6"><label class="form-label small">Tanggal masuk</label><input type="date" name="tgl_masuk" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
        <div class="col-6"><label class="form-label small">Estimasi selesai</label><input type="date" name="tgl_estimasi" class="form-control"></div>
      </div>
      <label class="form-label small">Catatan</label><input name="catatan" class="form-control" placeholder="Opsional">
    </div></div>
  </div>

  <div class="col-lg-7">
    <div class="card mb-3"><div class="card-body">
      <div class="d-flex justify-content-between mb-2"><h6 class="mb-0"><i class="bi bi-box-seam me-1"></i>Barang yang Dilapisi</h6>
        <button type="button" class="btn btn-sm btn-outline-dark" id="tambahBaris"><i class="bi bi-plus-lg"></i> Tambah barang</button></div>
      <div id="daftarItem"></div>
      <div class="d-flex justify-content-between align-items-center border-top pt-3 mt-2">
        <span class="fw-semibold">Total Biaya</span><span class="fs-4 fw-bold" id="totalBiaya">Rp 0</span>
      </div>
    </div></div>
    <div class="card"><div class="card-body">
      <h6 class="mb-3"><i class="bi bi-cash me-1"></i>Pembayaran Awal <small class="text-muted">(opsional)</small></h6>
      <div class="row g-2">
        <div class="col-7"><input type="number" name="dp_jumlah" class="form-control" min="0" step="100" placeholder="Jumlah dibayar (Rp)"></div>
        <div class="col-5"><select name="dp_metode" class="form-select"><option value="tunai">Tunai</option><option value="transfer">Transfer</option></select></div>
      </div>
    </div></div>
    <div class="text-end mt-3"><a href="pesanan.php" class="btn btn-outline-secondary me-2">Batal</a><button class="btn btn-dark"><i class="bi bi-save me-1"></i>Simpan Pesanan</button></div>
  </div>
</div>
</form>

<template id="tplItem">
  <div class="border rounded p-2 mb-2 item-row">
    <div class="row g-2">
      <div class="col-md-6"><select name="item_layanan[]" class="form-select form-select-sm sel-layanan">
        <?php foreach ($layanan as $l): ?><option value="<?= $l['id_layanan'] ?>" data-harga="<?= (float)$l['harga_satuan'] ?>" data-satuan="<?= e($l['satuan']) ?>"><?= e($l['nama_layanan']) ?> (<?= e($l['satuan']) ?>)</option><?php endforeach; ?>
      </select></div>
      <div class="col-md-6"><input name="item_barang[]" class="form-control form-control-sm" placeholder="Nama barang logam"></div>
      <div class="col-12"><input name="item_spek[]" class="form-control form-control-sm" placeholder="Spesifikasi / kebutuhan pelapisan (opsional)"></div>
      <div class="col-4"><input type="number" name="item_jumlah[]" class="form-control form-control-sm in-jumlah" min="0" step="0.01" placeholder="Berat (kg)"></div>
      <div class="col-4"><input type="number" name="item_harga[]" class="form-control form-control-sm in-harga" min="0" step="100" placeholder="Harga per kg"></div>
      <div class="col-3 d-flex align-items-center fw-semibold small sub-text">Rp 0</div>
      <div class="col-1 text-end"><button type="button" class="btn btn-sm btn-outline-danger hapus-baris"><i class="bi bi-x"></i></button></div>
    </div>
  </div>
</template>

<script>
(function () {
  var wrap = document.getElementById('daftarItem'), tpl = document.getElementById('tplItem');
  var fmt = function (n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); };
  function hitung() {
    var total = 0;
    wrap.querySelectorAll('.item-row').forEach(function (r) {
      var sub = (parseFloat(r.querySelector('.in-jumlah').value) || 0) * (parseFloat(r.querySelector('.in-harga').value) || 0);
      r.querySelector('.sub-text').textContent = fmt(sub); total += sub;
    });
    document.getElementById('totalBiaya').textContent = fmt(total);
  }
  function setHarga(r) { var o = r.querySelector('.sel-layanan').selectedOptions[0]; if (o) r.querySelector('.in-harga').value = o.dataset.harga; }
  function tambah() {
    if (!tpl.content.querySelector('option')) { alert('Belum ada layanan aktif. Tambahkan dulu di menu Layanan.'); return; }
    var node = tpl.content.cloneNode(true), r = node.querySelector('.item-row');
    r.querySelector('.sel-layanan').addEventListener('change', function () { setHarga(r); hitung(); });
    r.querySelectorAll('input').forEach(function (i) { i.addEventListener('input', hitung); });
    r.querySelector('.hapus-baris').addEventListener('click', function () { r.remove(); hitung(); });
    setHarga(r); wrap.appendChild(node); hitung();
  }
  document.getElementById('tambahBaris').addEventListener('click', tambah);
  tambah();
  var sel = document.getElementById('selPelanggan'), box = document.getElementById('boxBaru');
  sel.addEventListener('change', function () { box.style.display = sel.value === '0' ? '' : 'none'; });
})();
</script>
<?php require 'includes/footer.php'; ?>
