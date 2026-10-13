(function () {
  var $ = function (s) { return document.querySelector(s); };

  // ----- หน้าจ่ายเงิน: แสดง QR และเช็กสถานะทุก 5 วินาที -----
  var qr = $('#qr');
  if (qr && window.qrcode) {
    var q = qrcode(0, 'M');
    q.addData(qr.getAttribute('data-payload'));
    q.make();
    qr.innerHTML = q.createSvgTag({ cellSize: 6, margin: 2 });
  }
  var pay = $('#pay-box');
  if (pay) {
    setInterval(function () {
      fetch(pay.getAttribute('data-poll'), { cache: 'no-store' })
        .then(function (r) { return r.json(); })
        .then(function (d) { if (d.status !== 'pending') location.reload(); })
        .catch(function () {});
    }, 5000);
  }

  // ----- หน้าเลือกไพ่ -----
  var deck = $('#deck');
  if (deck) {
    var need = +deck.getAttribute('data-need'), picked = 0;
    for (var i = 0; i < 22; i++) {
      var b = document.createElement('div');
      b.className = 'back';
      b.textContent = '✦';
      b.style.animationDelay = (i * 0.03) + 's';
      b.onclick = function () {
        if (picked >= need || this.classList.contains('picked')) return;
        this.classList.add('picked');
        $('#pick-count').textContent = ++picked;
        if (picked === need) setTimeout(function () { $('#draw-form').submit(); }, 700);
      };
      deck.appendChild(b);
    }
  }

  // ----- หน้าคำทำนาย: แตะเพื่อเปิดไพ่ -----
  var flips = document.querySelectorAll('.reading .flip');
  if (flips.length) {
    var opened = 0;
    var showSummary = function () {
      var s = $('#summary');
      if (!s.hidden) return;
      s.hidden = false;
      setTimeout(function () { var m = s.querySelector('.meter div'); m.style.width = m.getAttribute('data-w') + '%'; }, 50);
      s.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };
    var open = function (f) {
      if (f.classList.contains('open')) return;
      f.classList.add('open');
      if (++opened === flips.length) setTimeout(showSummary, 1200);
    };
    flips.forEach(function (f) { f.onclick = function () { open(f); }; });
    $('#open-all').onclick = function () {
      flips.forEach(function (f, i) { setTimeout(function () { open(f); }, i * 250); });
    };
  }
})();
