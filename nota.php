<?php
require_once 'includes/functions.php';
login_any();
$id = (int)($_GET['id'] ?? 0);
$p = db_one('SELECT p.*, pl.nama AS pelanggan, pl.no_hp, pl.alamat, u.nama AS penerima
             FROM pesanan p JOIN pelanggan pl ON pl.id_pelanggan=p.id_pelanggan JOIN users u ON u.id_user=p.id_user WHERE p.id_pesanan=?', 'i', [$id]);
if (!$p) { die('Pesanan tidak ditemukan.'); }
if (is_pelanggan()) {
    $milik = db_val('SELECT COUNT(*) FROM pelanggan WHERE id_pelanggan=? AND id_user=?', 'ii', [$p['id_pelanggan'], $_SESSION['user']['id']]);
    if (!$milik) { http_response_code(403); die('Akses ditolak.'); }
}
$namaPenerima = $p['sumber'] === 'online' ? 'Pesanan online' : $p['penerima'];
$items = db_all('SELECT d.*, l.nama_layanan, l.satuan FROM detail_pesanan d JOIN layanan l ON l.id_layanan=d.id_layanan WHERE d.id_pesanan=?', 'i', [$id]);
$dibayar = total_dibayar($id);
$sisa = max(0, (float)$p['total_biaya'] - $dibayar);
$belum = (float)$p['total_biaya'] <= 0;   // biaya belum ditetapkan
?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><title>Nota <?= e($p['no_pesanan']) ?></title>
<link href="assets/vendor/bootstrap.min.css" rel="stylesheet">
<style>body{background:#fff;font-size:14px}.nota{max-width:720px;margin:20px auto;padding:24px;border:1px solid #ccc}@media print{.nota{border:0;margin:0}.no-print{display:none}}</style>
</head><body>
<div class="nota">
  <div class="text-center border-bottom pb-2 mb-3"><h5 class="mb-0"><?= NAMA_USAHA ?></h5><div>Jasa Electroplating &middot; <?= ALAMAT_USAHA ?></div></div>
  <div class="row mb-3">
    <div class="col-6"><div>No. Pesanan: <b><?= e($p['no_pesanan']) ?></b></div><div>Tanggal masuk: <?= tgl($p['tgl_masuk']) ?></div><div>Estimasi selesai: <?= tgl($p['tgl_estimasi']) ?></div></div>
    <div class="col-6"><div>Pelanggan: <b><?= e($p['pelanggan']) ?></b></div><div><?= e($p['no_hp']) ?></div><div>Penerima: <?= e($namaPenerima) ?></div></div>
  </div>
  <table class="table table-sm table-bordered">
    <thead><tr><th>Barang</th><th>Layanan</th><th class="text-end">Berat</th><th class="text-end">Harga/kg</th><th class="text-end">Subtotal</th></tr></thead>
    <tbody><?php foreach ($items as $it): ?>
      <tr><td><?= e($it['nama_barang']) ?></td><td><?= e($it['nama_layanan']) ?></td><td class="text-end"><?= rtrim(rtrim(number_format($it['jumlah'], 2, ',', '.'), '0'), ',') . ' ' . e($it['satuan']) ?></td><td class="text-end"><?= ($belum ? '-' : rupiah($it['harga_satuan'])) ?></td><td class="text-end"><?= ($belum ? '-' : rupiah($it['subtotal'])) ?></td></tr>
    <?php endforeach; ?></tbody>
    <tfoot>
      <tr><th colspan="4" class="text-end">Total</th><th class="text-end"><?= ($belum ? 'Belum ditetapkan' : rupiah($p['total_biaya'])) ?></th></tr>
      <tr><td colspan="4" class="text-end">Dibayar</td><td class="text-end"><?= ($belum ? '-' : rupiah($dibayar)) ?></td></tr>
      <tr><td colspan="4" class="text-end">Sisa</td><td class="text-end"><?= ($belum ? '-' : rupiah($sisa)) ?></td></tr>
    </tfoot>
  </table>
  <div class="mb-3">Status pengerjaan: <b><?= e(STATUS_URUTAN[$p['status']]) ?></b> &middot; Pembayaran: <b><?= $p['status_bayar'] === 'lunas' ? 'LUNAS' : ($p['status_bayar'] === 'dp' ? 'BELUM LUNAS' : 'BELUM BAYAR') ?></b></div>
  <div class="row text-center mt-4"><div class="col-6">Pelanggan<br><br><br>( <?= e($p['pelanggan']) ?> )</div><div class="col-6">Hormat kami<br><br><br>( <?= e(NAMA_USAHA) ?> )</div></div>
  <div class="text-center mt-4 no-print"><button class="btn btn-dark" onclick="window.print()">Cetak</button></div>
</div>
</body></html>
