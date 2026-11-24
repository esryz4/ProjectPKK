<?php
require_once 'includes/functions.php';
login_required();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$p = db_one('SELECT p.*, pl.nama AS pelanggan, pl.no_hp, pl.alamat, u.nama AS penerima
             FROM pesanan p JOIN pelanggan pl ON pl.id_pelanggan=p.id_pelanggan JOIN users u ON u.id_user=p.id_user WHERE p.id_pesanan=?', 'i', [$id]);
if (!$p) { flash('danger', 'Pesanan tidak ditemukan.'); redirect('pesanan.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (is_owner()) { flash('danger', 'Pemilik hanya dapat melihat data. Perubahan dilakukan oleh admin.'); redirect('pesanan_detail.php?id=' . $id); }
    csrf_verify();
    $aksi = $_POST['aksi'] ?? '';
    $uid = $_SESSION['user']['id'];
    $sisa = (float)$p['total_biaya'] - total_dibayar($id);

    if ($aksi === 'status') {
        $baru = $_POST['status'] ?? '';
        if (!isset(STATUS_URUTAN[$baru])) { flash('danger', 'Status tidak valid.'); }
        elseif ($baru === 'diambil' && (float)$p['total_biaya'] <= 0) { flash('danger', 'Biaya belum ditetapkan. Isi dulu lewat tombol Ubah Biaya.'); }
        elseif ($baru === 'diambil' && $sisa > 0) { flash('danger', 'Barang belum bisa diserahkan: pembayaran belum lunas (sisa ' . rupiah($sisa) . ').'); }
        else {
            $selesai = $p['tgl_selesai']; $diambil = $p['tgl_diambil'];
            if ($baru === 'selesai' && !$selesai) { $selesai = date('Y-m-d'); }
            if ($baru === 'diambil') { $diambil = date('Y-m-d'); if (!$selesai) { $selesai = date('Y-m-d'); } }
            if (!in_array($baru, ['selesai', 'diambil'], true)) { $selesai = null; $diambil = null; }
            elseif ($baru === 'selesai') { $diambil = null; }
            db_exec('UPDATE pesanan SET status=?, tgl_selesai=?, tgl_diambil=? WHERE id_pesanan=?', 'sssi', [$baru, $selesai, $diambil, $id]);
            db_exec('INSERT INTO log_status (id_pesanan, status, id_user, catatan) VALUES (?,?,?,?)', 'isis', [$id, $baru, $uid, trim($_POST['catatan'] ?? '')]);
            flash('success', 'Status diperbarui menjadi "' . STATUS_URUTAN[$baru] . '".');
        }
    } elseif ($aksi === 'bayar') {
        $jml = (float)($_POST['jumlah'] ?? 0);
        $metode = in_array($_POST['metode'] ?? '', ['tunai', 'transfer', 'qris'], true) ? $_POST['metode'] : 'tunai';
        if ($jml <= 0) { flash('danger', 'Jumlah pembayaran harus lebih dari 0.'); }
        elseif ($jml > $sisa) { flash('danger', 'Jumlah melebihi sisa tagihan (' . rupiah($sisa) . ').'); }
        else {
            db_exec('INSERT INTO pembayaran (id_pesanan, id_user, tgl_bayar, jumlah, metode, keterangan) VALUES (?,?,?,?,?,?)',
                'iisdss', [$id, $uid, $_POST['tgl_bayar'] ?: date('Y-m-d'), $jml, $metode, trim($_POST['keterangan'] ?? '') ?: ($jml >= $sisa ? 'Pelunasan' : 'Cicilan')]);
            sinkron_status_bayar($id);
            flash('success', 'Pembayaran ' . rupiah($jml) . ' dicatat.');
        }
    } elseif ($aksi === 'ubah_biaya') {
        if ($p['status'] === 'diambil') { flash('danger', 'Pesanan sudah diambil, biaya tidak dapat diubah.'); redirect('pesanan_detail.php?id=' . $id); }
        $ids = $_POST['d_id'] ?? []; $J = $_POST['d_jumlah'] ?? []; $H = $_POST['d_harga'] ?? [];
        $hapus = array_map('intval', $_POST['d_hapus'] ?? []);
        $baris = []; $err = ''; $new = null;
        foreach ($ids as $i => $did) {
            $did = (int)$did;
            if (in_array($did, $hapus, true)) { continue; }
            $jml = (float)($J[$i] ?? 0); $hrg = ($H[$i] ?? '') === '' ? -1 : (float)$H[$i];
            if ($jml <= 0 || $hrg < 0) { $err = 'Jumlah harus lebih dari 0 dan harga tidak boleh negatif.'; break; }
            $baris[] = ['id' => $did, 'jumlah' => $jml, 'harga' => $hrg, 'subtotal' => round($jml * $hrg, 2)];
        }
        $nb = trim($_POST['new_barang'] ?? '');
        if ($err === '' && $nb !== '') {
            $lay = db_one('SELECT * FROM layanan WHERE id_layanan=?', 'i', [(int)($_POST['new_layanan'] ?? 0)]);
            $nj = (float)($_POST['new_jumlah'] ?? 0);
            $nh = ($_POST['new_harga'] ?? '') !== '' ? (float)$_POST['new_harga'] : ($lay ? (float)$lay['harga_satuan'] : -1);
            if (!$lay || $nj <= 0 || $nh < 0) { $err = 'Baris barang baru belum lengkap (layanan, jumlah, harga).'; }
            else { $new = ['layanan' => (int)$lay['id_layanan'], 'barang' => $nb, 'spek' => trim($_POST['new_spek'] ?? ''), 'jumlah' => $nj, 'harga' => $nh, 'subtotal' => round($nj * $nh, 2)]; }
        }
        if ($err === '' && !$baris && !$new) { $err = 'Pesanan harus memiliki minimal satu barang.'; }
        $totalBaru = array_sum(array_column($baris, 'subtotal')) + ($new['subtotal'] ?? 0);
        $sudahBayar = total_dibayar($id);
        if ($err === '' && $totalBaru < $sudahBayar) { $err = 'Total baru (' . rupiah($totalBaru) . ') lebih kecil dari pembayaran yang sudah masuk (' . rupiah($sudahBayar) . '). Koreksi catatan pembayaran dulu.'; }
        if ($err !== '') { flash('danger', $err); }
        else {
            global $db;
            try {
                $db->begin_transaction();
                foreach ($baris as $b) {
                    db_exec('UPDATE detail_pesanan SET jumlah=?, harga_satuan=?, subtotal=? WHERE id_detail=? AND id_pesanan=?', 'dddii', [$b['jumlah'], $b['harga'], $b['subtotal'], $b['id'], $id]);
                }
                foreach ($hapus as $hid) { db_exec('DELETE FROM detail_pesanan WHERE id_detail=? AND id_pesanan=?', 'ii', [$hid, $id]); }
                if ($new) {
                    db_exec('INSERT INTO detail_pesanan (id_pesanan, id_layanan, nama_barang, spesifikasi, jumlah, harga_satuan, subtotal) VALUES (?,?,?,?,?,?,?)',
                        'iissddd', [$id, $new['layanan'], $new['barang'], $new['spek'], $new['jumlah'], $new['harga'], $new['subtotal']]);
                }
                db_exec('UPDATE pesanan SET total_biaya=? WHERE id_pesanan=?', 'di', [$totalBaru, $id]);
                if (round($totalBaru, 2) !== round((float)$p['total_biaya'], 2)) {
                    db_exec('INSERT INTO log_status (id_pesanan, status, id_user, catatan) VALUES (?,?,?,?)', 'isis',
                        [$id, $p['status'], $uid, 'Biaya diperbarui setelah pengecekan: ' . ((float)$p['total_biaya'] > 0 ? rupiah($p['total_biaya']) . ' menjadi ' . rupiah($totalBaru) : 'total ' . rupiah($totalBaru))]);
                }
                sinkron_status_bayar($id);
                $db->commit();
                flash('success', 'Rincian biaya diperbarui. Total sekarang ' . rupiah($totalBaru) . '.');
            } catch (mysqli_sql_exception $ex) {
                $db->rollback();
                flash('danger', 'Gagal menyimpan perubahan biaya.');
            }
        }
    } elseif ($aksi === 'ubah_bayar') {
        $idb = (int)($_POST['id_pembayaran'] ?? 0);
        $jml = (float)($_POST['jumlah'] ?? 0);
        $metode = in_array($_POST['metode'] ?? '', ['tunai', 'transfer', 'qris'], true) ? $_POST['metode'] : 'tunai';
        $tgl = $_POST['tgl_bayar'] ?? '';
        $ket = trim($_POST['keterangan'] ?? '');
        if (!in_array($ket, ['Belum Bayar', 'DP', 'Lunas'], true)) { $ket = 'DP'; }
        $totalBiaya = (float)$p['total_biaya'];
        $lain = (float)db_val('SELECT COALESCE(SUM(jumlah),0) FROM pembayaran WHERE id_pesanan=? AND id_pembayaran<>?', 'ii', [$id, $idb]);
        $ada = (int)db_val('SELECT COUNT(*) FROM pembayaran WHERE id_pembayaran=? AND id_pesanan=?', 'ii', [$idb, $id]);
        if (!$ada) { flash('danger', 'Catatan pembayaran tidak ditemukan.'); }
        elseif ($jml <= 0) { flash('danger', 'Jumlah pembayaran harus lebih dari 0.'); }
        elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) { flash('danger', 'Tanggal pembayaran tidak valid.'); }
        elseif ($lain + $jml > $totalBiaya + 0.001) { flash('danger', 'Total pembayaran (' . rupiah($lain + $jml) . ') melebihi total biaya pesanan (' . rupiah($totalBiaya) . ').'); }
        elseif ($p['status'] === 'diambil' && $lain + $jml < $totalBiaya - 0.001) { flash('danger', 'Pesanan sudah diambil dan harus tetap lunas. Jumlah tidak boleh dikurangi di bawah total biaya.'); }
        else {
            db_exec('UPDATE pembayaran SET tgl_bayar=?, jumlah=?, metode=?, keterangan=? WHERE id_pembayaran=? AND id_pesanan=?', 'sdssii', [$tgl, $jml, $metode, $ket, $idb, $id]);
            sinkron_status_bayar($id);
            flash('success', 'Catatan pembayaran diperbarui.');
        }
    } elseif ($aksi === 'hapus_bayar') {
        owner_only();
        db_exec('DELETE FROM pembayaran WHERE id_pembayaran=? AND id_pesanan=?', 'ii', [(int)$_POST['id_pembayaran'], $id]);
        sinkron_status_bayar($id);
        flash('success', 'Catatan pembayaran dihapus.');
    } elseif ($aksi === 'hapus_pesanan') {
        owner_only();
        db_exec('DELETE FROM pesanan WHERE id_pesanan=?', 'i', [$id]);
        flash('success', 'Pesanan dihapus.');
        redirect('pesanan.php');
    }
    redirect('pesanan_detail.php?id=' . $id);
}

$items = db_all('SELECT d.*, l.nama_layanan, l.satuan, l.harga_satuan AS harga_acuan FROM detail_pesanan d JOIN layanan l ON l.id_layanan=d.id_layanan WHERE d.id_pesanan=?', 'i', [$id]);
$bayar = db_all('SELECT b.*, u.nama AS petugas FROM pembayaran b JOIN users u ON u.id_user=b.id_user WHERE b.id_pesanan=? ORDER BY b.tgl_bayar, b.id_pembayaran', 'i', [$id]);
$log = db_all('SELECT s.*, u.nama AS petugas FROM log_status s JOIN users u ON u.id_user=s.id_user WHERE s.id_pesanan=? ORDER BY s.waktu, s.id_log', 'i', [$id]);
$dibayar = array_sum(array_column($bayar, 'jumlah'));
$sisa = max(0, (float)$p['total_biaya'] - $dibayar);

// Saran tahap berikutnya
$keys = array_keys(STATUS_URUTAN);
$idx = array_search($p['status'], $keys, true);
$next = $keys[min($idx + 1, count($keys) - 1)];

$layananAktif = db_all('SELECT * FROM layanan WHERE aktif=1 ORDER BY nama_layanan');
$judul = 'Detail ' . $p['no_pesanan'];
require 'includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div><a href="pesanan.php" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Daftar pesanan</a>
    <h4 class="mb-0"><?= e($p['no_pesanan']) ?> <?= badge_status($p['status']) ?> <?= badge_bayar($p['status_bayar']) ?></h4></div>
  <div class="d-flex gap-2">
    <a href="nota.php?id=<?= $id ?>" target="_blank" class="btn btn-outline-dark"><i class="bi bi-printer me-1"></i>Cetak Nota</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card mb-3"><div class="card-body">
      <div class="row">
        <div class="col-md-6"><div class="text-muted small">Pelanggan</div><div class="fw-semibold"><?= e($p['pelanggan']) ?></div><div class="small"><?= e($p['no_hp']) ?> <?= e($p['alamat']) ?></div></div>
        <div class="col-md-6"><div class="small">Masuk: <b><?= tgl($p['tgl_masuk']) ?></b> &middot; Estimasi: <b><?= tgl($p['tgl_estimasi']) ?></b></div>
          <div class="small">Selesai: <b><?= tgl($p['tgl_selesai']) ?></b> &middot; Diambil: <b><?= tgl($p['tgl_diambil']) ?></b></div>
          <div class="small"><?= $p['sumber'] === 'online' ? 'Sumber: <b>Pesanan online</b>' : 'Diterima oleh: <b>' . e($p['penerima']) . '</b>' ?></div>
          <?php if (!empty($p['metode_bayar'])): ?><div class="small">Metode dipilih pelanggan: <b><?= e(strtoupper($p['metode_bayar'])) ?></b></div><?php endif; ?></div>
      </div>
      <?php if ($p['catatan']): ?><div class="small text-muted mt-2">Catatan: <?= e($p['catatan']) ?></div><?php endif; ?>
    </div></div>

    <div class="card mb-3"><div class="card-body">
      <h6>Rincian Barang</h6>
      <div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>Barang</th><th>Layanan</th><th class="text-end">Berat</th><th class="text-end">Harga/kg</th><th class="text-end">Subtotal</th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
          <tr><td><?= e($it['nama_barang']) ?><?= $it['spesifikasi'] ? '<div class="small text-muted">' . e($it['spesifikasi']) . '</div>' : '' ?></td><td><?= e($it['nama_layanan']) ?></td>
            <td class="text-end"><?= rtrim(rtrim(number_format($it['jumlah'], 2, ',', '.'), '0'), ',') . ' ' . e($it['satuan']) ?></td><td class="text-end"><?= rupiah($it['harga_satuan']) ?></td><td class="text-end"><?= rupiah($it['subtotal']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr><td colspan="4" class="text-end">Total biaya</td><td class="text-end fw-bold"><?= rupiah($p['total_biaya']) ?></td></tr>
          <tr><td colspan="4" class="text-end">Sudah dibayar</td><td class="text-end text-success"><?= rupiah($dibayar) ?></td></tr>
          <tr><td colspan="4" class="text-end">Sisa tagihan</td><td class="text-end fw-bold <?= $sisa > 0 ? 'text-danger' : '' ?>"><?= rupiah($sisa) ?></td></tr>
        </tfoot>
      </table></div>
      <?php if ($p['status'] !== 'diambil' && !is_owner()): ?>
      <button class="btn btn-sm btn-outline-dark mt-3" type="button" data-bs-toggle="collapse" data-bs-target="#formBiaya"><i class="bi bi-pencil-square me-1"></i>Ubah Biaya</button>
      <div class="collapse mt-3 <?= (float)$p['total_biaya'] <= 0 ? 'show' : '' ?>" id="formBiaya">
        <form method="post" class="border rounded p-3 bg-light"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="aksi" value="ubah_biaya">
          <?php if ((float)$p['total_biaya'] <= 0): ?><div class="alert alert-warning py-2 small">Biaya pesanan ini belum ditetapkan. Harga sudah terisi dari harga acuan layanan, silakan sesuaikan lalu simpan.</div><?php endif; ?>
          <div class="small text-muted mb-2">Sesuaikan jumlah dan harga setelah barang dicek. Centang &ldquo;Hapus&rdquo; untuk membuang baris.</div>
          <?php foreach ($items as $it): ?>
          <div class="row g-2 align-items-center mb-2">
            <div class="col-md-4 small fw-semibold"><?= e($it['nama_barang']) ?><div class="text-muted fw-normal"><?= e($it['nama_layanan']) ?> (<?= e($it['satuan']) ?>)</div></div>
            <input type="hidden" name="d_id[]" value="<?= $it['id_detail'] ?>">
            <div class="col-4 col-md-3"><input type="number" name="d_jumlah[]" class="form-control form-control-sm" min="0.01" step="0.01" value="<?= e((float)$it['jumlah']) ?>"></div>
            <div class="col-5 col-md-3"><input type="number" name="d_harga[]" class="form-control form-control-sm" min="0" step="100" value="<?= e((float)($it['harga_satuan'] > 0 ? $it['harga_satuan'] : $it['harga_acuan'])) ?>"></div>
            <div class="col-3 col-md-2"><div class="form-check"><input class="form-check-input" type="checkbox" name="d_hapus[]" value="<?= $it['id_detail'] ?>" id="h<?= $it['id_detail'] ?>"><label class="form-check-label small" for="h<?= $it['id_detail'] ?>">Hapus</label></div></div>
          </div>
          <?php endforeach; ?>
          <div class="border-top pt-2 mt-2 small fw-semibold">Tambah barang baru (opsional)</div>
          <div class="row g-2 mt-1">
            <div class="col-md-4"><select name="new_layanan" class="form-select form-select-sm"><?php foreach ($layananAktif as $l): ?><option value="<?= $l['id_layanan'] ?>"><?= e($l['nama_layanan']) ?> (<?= e($l['satuan']) ?>)</option><?php endforeach; ?></select></div>
            <div class="col-md-4"><input name="new_barang" class="form-control form-control-sm" placeholder="Nama barang"></div>
            <div class="col-md-4"><input name="new_spek" class="form-control form-control-sm" placeholder="Spesifikasi (opsional)"></div>
            <div class="col-6 col-md-4"><input type="number" name="new_jumlah" class="form-control form-control-sm" min="0" step="0.01" placeholder="Berat (kg)"></div>
            <div class="col-6 col-md-4"><input type="number" name="new_harga" class="form-control form-control-sm" min="0" step="100" placeholder="Harga/kg (kosong = harga layanan)"></div>
          </div>
          <div class="text-end mt-3"><button class="btn btn-sm btn-dark"><i class="bi bi-save me-1"></i>Simpan Perubahan Biaya</button></div>
        </form>
      </div>
      <?php endif; ?>
    </div></div>

    <div class="card"><div class="card-body">
      <h6>Riwayat Pembayaran</h6>
      <div class="table-responsive"><table class="table table-sm mb-3">
        <thead><tr><th>Tanggal</th><th>Metode</th><th>Keterangan</th><th>Petugas</th><th class="text-end">Jumlah</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($bayar as $b): ?>
          <tr><td><?= tgl($b['tgl_bayar']) ?></td><td><?= e(strtoupper($b['metode'])) ?></td><td><?= e($b['keterangan']) ?></td><td><?= e($b['petugas']) ?></td><td class="text-end"><?= rupiah($b['jumlah']) ?></td>
            <td class="text-end text-nowrap"><?php if (!is_owner()): ?><button type="button" class="btn btn-sm btn-link p-0 me-2" title="Ubah" data-bs-toggle="modal" data-bs-target="#modalBayar" data-edit="<?= e(json_encode(['id_pembayaran' => $b['id_pembayaran'], 'tgl_bayar' => $b['tgl_bayar'], 'jumlah' => (float)$b['jumlah'], 'metode' => $b['metode'], 'keterangan' => (stripos((string)$b['keterangan'], 'lunas') !== false ? 'Lunas' : (stripos((string)$b['keterangan'], 'belum') !== false ? 'Belum Bayar' : 'DP'))])) ?>"><i class="bi bi-pencil"></i></button><?php endif; ?></td></tr>
        <?php endforeach; if (!$bayar): ?><tr><td colspan="6" class="text-muted text-center">Belum ada pembayaran.</td></tr><?php endif; ?>
        </tbody></table></div>
      <?php if ($sisa > 0 && !is_owner()): ?>
      <form method="post" class="row g-2 border-top pt-3"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="aksi" value="bayar">
        <div class="col-md-3"><input type="date" name="tgl_bayar" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>"></div>
        <div class="col-md-3"><input type="number" name="jumlah" class="form-control form-control-sm" min="1" max="<?= (int)$sisa ?>" step="any" value="<?= (int)$sisa ?>" required></div>
        <div class="col-md-2"><select name="metode" class="form-select form-select-sm"><option value="tunai" <?= ($p['metode_bayar'] ?? '') === 'tunai' ? 'selected' : '' ?>>Tunai</option><option value="transfer" <?= ($p['metode_bayar'] ?? '') === 'transfer' ? 'selected' : '' ?>>Transfer</option></select></div>
        <div class="col-md-2"><input name="keterangan" class="form-control form-control-sm" placeholder="Ket."></div>
        <div class="col-md-2"><button class="btn btn-sm btn-dark w-100">Catat Bayar</button></div>
      </form>
      <?php endif; ?>
    </div></div>
  </div>

  <div class="col-lg-4">
    <?php if (!is_owner()): ?>
    <div class="card mb-3"><div class="card-body">
      <h6>Perbarui Status Pengerjaan</h6>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="aksi" value="status">
        <select name="status" class="form-select mb-2"><?php foreach (STATUS_URUTAN as $k => $l): ?><option value="<?= $k ?>" <?= $k === $next ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
        <input name="catatan" class="form-control mb-2" placeholder="Catatan (opsional)">
        <button class="btn btn-dark w-100"><i class="bi bi-arrow-repeat me-1"></i>Simpan Status</button>
      </form>
    </div></div>
    <?php endif; ?>
    <div class="card"><div class="card-body">
      <h6 class="mb-3">Riwayat Pengerjaan</h6>
      <ul class="timeline">
        <?php foreach (array_reverse($log) as $s): ?>
          <li><div><?= badge_status($s['status']) ?></div>
            <div class="small text-muted"><?= tgl_waktu($s['waktu']) ?> &middot; <?= e($s['petugas']) ?></div>
            <?php if ($s['catatan']): ?><div class="small"><?= e($s['catatan']) ?></div><?php endif; ?></li>
        <?php endforeach; ?>
      </ul>
    </div></div>
  </div>
</div>
<?php if (!is_owner()): ?>
<div class="modal fade" id="modalBayar" tabindex="-1"><div class="modal-dialog"><form method="post" class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Ubah Pembayaran</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="aksi" value="ubah_bayar"><input type="hidden" name="id_pembayaran" value="0">
    <div class="row g-3">
      <div class="col-6"><label class="form-label">Tanggal bayar</label><input type="date" name="tgl_bayar" class="form-control" required></div>
      <div class="col-6"><label class="form-label">Metode</label><select name="metode" class="form-select"><option value="tunai">Tunai</option><option value="transfer">Transfer</option></select></div>
      <div class="col-12"><label class="form-label">Jumlah (Rp)</label><input type="number" name="jumlah" class="form-control" min="1" step="any" required></div>
      <div class="col-12"><label class="form-label">Keterangan</label><select name="keterangan" class="form-select"><option value="Belum Bayar">Belum Bayar</option><option value="DP">DP</option><option value="Lunas">Lunas</option></select></div>
    </div>
  </div>
  <div class="modal-footer"><button class="btn btn-dark">Simpan Perubahan</button></div>
</form></div></div>
<?php endif; ?>
<?php require 'includes/footer.php'; ?>
