</div>
<footer class="app-foot no-print">&copy; <?= date('Y') ?> <?= NAMA_USAHA ?> &middot; Sistem Informasi Manajemen Penjualan dan Operasional Jasa Electroplating</footer>
</main>
</div>
<script src="assets/vendor/bootstrap.bundle.min.js"></script>
<script>
// Modal tambah/ubah generik: tombol dengan data-edit='{json}' mengisi field sesuai atribut name
document.querySelectorAll('.modal').forEach(function (m) {
  m.addEventListener('show.bs.modal', function (ev) {
    var f = m.querySelector('form'); if (!f) return;
    var b = ev.relatedTarget;
    f.reset();
    var judul = m.querySelector('.modal-title');
    if (b && b.dataset.edit) {
      var d = JSON.parse(b.dataset.edit);
      Object.keys(d).forEach(function (k) { if (f.elements[k]) f.elements[k].value = d[k] === null ? '' : d[k]; });
      if (judul) judul.textContent = 'Ubah Data';
    } else {
      if (f.elements['id']) f.elements['id'].value = 0;
      if (judul) judul.textContent = 'Tambah Data';
    }
  });
});
document.querySelectorAll('form[data-confirm]').forEach(function (f) {
  f.addEventListener('submit', function (e) { if (!confirm(f.dataset.confirm)) e.preventDefault(); });
});
</script>
<script src="assets/app.js?v=<?= @filemtime(__DIR__ . '/../assets/app.js') ?>"></script>
</body>
</html>
