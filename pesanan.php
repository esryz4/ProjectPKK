<?php
require_once 'includes/functions.php';
login_required();
$status = $_GET['status'] ?? ''; $bayar = $_GET['bayar'] ?? ''; $q = trim($_GET['q'] ?? '');
$where = ['1=1']; $types = ''; $params = [];
if (isset(STATUS_URUTAN[$status])) { $where[] = 'p.status=?'; $types .= 's'; $params[] = $status; }
if (in_array($bayar, ['belum', 'dp', 'lunas'], true)) { $where[] = 'p.status_bayar=?'; $types .= 's'; $params[] = $bayar; }
if ($q !== '') { $where[] = '(p.no_pesanan LIKE ? OR pl.nama LIKE ?)'; $types .= 'ss'; $params[] = "%$q%"; $params[] = "%$q%"; }
$rows = db_all("SELECT p.*, pl.nama AS pelanggan,
                  (SELECT COALESCE(SUM(b.jumlah),0) FROM pembayaran b WHERE b.id_pesanan=p.id_pesanan) AS dibayar
                FROM pesanan p JOIN pelanggan pl ON pl.id_pelanggan=p.id_pelanggan
                WHERE " . implode(' AND ', $where) . " ORDER BY p.id_pesanan DESC", $types, $params);
$judul = 'Pesanan';
require 'includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h4 class="mb-0">Pesanan Jasa Electroplating</h4>
  <?php if (!is_owner()): ?><a href="pesanan_tambah.php" class="btn btn-dark"><i class="bi bi-plus-lg me-1"></i>Pesanan Baru</a><?php endif; ?>
</div>
<form class="row g-2 mb-3">
  <div class="col-md-4"><input name="q" value="<?= e($q) ?>" class="form-control" placeholder="Cari no. pesanan / pelanggan"></div>
  <div class="col-md-3"><select name="status" class="form-select"><option value="">Semua status pengerjaan</option><?php foreach (STATUS_URUTAN as $k => $l): ?><option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><select name="bayar" class="form-select"><option value="">Semua pembayaran</option><option value="belum" <?= $bayar === 'belum' ? 'selected' : '' ?>>Belum bayar</option><option value="dp" <?= $bayar === 'dp' ? 'selected' : '' ?>>DP / sebagian</option><option value="lunas" <?= $bayar === 'lunas' ? 'selected' : '' ?>>Lunas</option></select></div>
  <div class="col-md-2"><button class="btn btn-outline-secondary w-100"><i class="bi bi-funnel me-1"></i>Filter</button></div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
  <thead><tr><th>No. Pesanan</th><th>Pelanggan</th><th>Masuk</th><th>Estimasi</th><th class="text-end">Total</th><th class="text-end">Sisa</th><th>Pengerjaan</th><th>Pembayaran</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr><td class="fw-semibold"><?= e($r['no_pesanan']) ?><?= $r['sumber'] === 'online' ? ' <span class="badge text-bg-info">online</span>' : ' <span class="badge text-bg-secondary">offline</span>' ?></td><td><?= e($r['pelanggan']) ?></td><td><?= tgl($r['tgl_masuk']) ?></td><td><?= tgl($r['tgl_estimasi']) ?></td>
      <?php if ((float)$r['total_biaya'] <= 0): ?><td class="text-end text-muted small">Belum ditetapkan</td><td class="text-end text-muted">-</td><?php else: ?><td class="text-end"><?= rupiah($r['total_biaya']) ?></td><td class="text-end"><?= rupiah(max(0, $r['total_biaya'] - $r['dibayar'])) ?></td><?php endif; ?>
      <td><?= badge_status($r['status']) ?></td><td><?= badge_bayar($r['status_bayar']) ?></td>
      <td class="text-end"><a href="pesanan_detail.php?id=<?= $r['id_pesanan'] ?>" class="btn btn-sm btn-outline-dark"><i class="bi bi-eye"></i> Detail</a></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="9" class="text-center text-muted py-4">Tidak ada pesanan.</td></tr><?php endif; ?>
  </tbody></table></div></div>
<?php require 'includes/footer.php'; ?>
