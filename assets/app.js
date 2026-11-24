(function () {
  var d = document, root = d.documentElement;
  root.classList.add('js');
  // Transisi saat pindah halaman
  d.addEventListener('click', function (e) {
    var a = e.target.closest('a[href]');
    if (!a || e.ctrlKey || e.metaKey || e.shiftKey || a.target === '_blank' || a.hasAttribute('download')) return;
    var h = a.getAttribute('href');
    if (!h || h.charAt(0) === '#' || /^(mailto:|tel:|javascript:|https?:)/i.test(h) && a.hostname !== location.hostname) return;
    if (a.hasAttribute('data-bs-toggle')) return;
    e.preventDefault(); d.body.classList.add('keluar');
    setTimeout(function () { location.href = a.href; }, 200);
  });
  window.addEventListener('pageshow', function () { d.body.classList.remove('keluar'); });

  // Muncul saat digulir
  var els = [].slice.call(d.querySelectorAll('.app-content .card, .app-content section > h3, .app-content .alert, .app-content .table-responsive, .hero'));
  els.forEach(function (el, i) { el.classList.add('reveal'); el.style.setProperty('--d', ((i % 4) * 0.08) + 's'); });
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (es) { es.forEach(function (x) { if (x.isIntersecting) { x.target.classList.add('tampil'); io.unobserve(x.target); } }); }, { threshold: .08 });
    els.forEach(function (el) { io.observe(el); });
  } else { els.forEach(function (el) { el.classList.add('tampil'); }); }

  // Kartu bereaksi saat kursor mendekat
  var kartu = [].slice.call(d.querySelectorAll('.fx-card'));
  if (kartu.length && matchMedia('(hover:hover)').matches) {
    var tunggu = false, mx = 0, my = 0;
    d.addEventListener('mousemove', function (e) {
      mx = e.clientX; my = e.clientY;
      if (tunggu) return; tunggu = true;
      requestAnimationFrame(function () {
        tunggu = false;
        kartu.forEach(function (k) {
          var r = k.getBoundingClientRect();
          var dx = Math.max(r.left - mx, 0, mx - r.right), dy = Math.max(r.top - my, 0, my - r.bottom);
          var jarak = Math.sqrt(dx * dx + dy * dy), dekat = jarak < 90;
          k.style.setProperty('--near', dekat ? Math.max(0.25, 1 - jarak / 90) : 0);
          k.style.setProperty('--mx', (mx - r.left) + 'px'); k.style.setProperty('--my', (my - r.top) + 'px');
          k.classList.toggle('dekat', dekat);
        });
      });
    });
  }
})();

// Sidebar: tampilkan / sembunyikan (diingat di browser)
(function () {
  var btn = document.getElementById('sbToggle'); if (!btn) return;
  var root = document.documentElement;
  btn.addEventListener('click', function () {
    var sembunyi = root.classList.toggle('sb-hide');
    try { localStorage.setItem('sb', sembunyi ? '0' : '1'); } catch (e) {}
  });
})();
// Peta Google butuh internet: jika offline tampilkan keterangan
(function () {
  var box = document.getElementById('petaBox'); if (!box) return;
  function cek() {
    var f = box.querySelector('iframe');
    if (!navigator.onLine && f) { f.style.display = 'none'; if (!box.querySelector('.peta-offline')) { var d = document.createElement('div'); d.className = 'peta-offline'; d.textContent = 'Peta tidak tampil karena tidak ada koneksi internet. Alamat tertera di sebelah kiri.'; box.appendChild(d); } }
    else if (navigator.onLine && f) { f.style.display = ''; var o = box.querySelector('.peta-offline'); if (o) o.remove(); }
  }
  window.addEventListener('online', cek); window.addEventListener('offline', cek); cek();
})();
