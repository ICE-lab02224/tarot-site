(function () {
  var $ = function (id) { return document.getElementById(id); };
  var picked = 0, opened = 0, busy = false;

  // ขอผลไพ่จากเซิร์ฟเวอร์ (สุ่มและจำกัดจำนวนครั้งที่ free.php)
  var getReading = window.FREE_READING || function (topic) {
    var body = new URLSearchParams({ topic: topic });
    return fetch('free.php', { method: 'POST', body: body, credentials: 'same-origin' })
      .then(function (r) { return r.json(); });
  };

  function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

  function show(step) {
    ['step-topic', 'step-pick', 'step-result'].forEach(function (s) { $(s).hidden = s !== step; });
    $('play').scrollIntoView({ behavior: 'smooth' });
  }

  function renderDeck(topic) {
    picked = 0; busy = false;
    $('pick-count').textContent = '0';
    var el = $('deck');
    el.innerHTML = '';
    for (var i = 0; i < 22; i++) {
      var b = document.createElement('div');
      b.className = 'back';
      b.textContent = '✦';
      b.style.animationDelay = (i * 0.03) + 's';
      b.onclick = function () {
        if (busy || picked >= 3 || this.classList.contains('picked')) return;
        this.classList.add('picked');
        $('pick-count').textContent = ++picked;
        if (picked === 3) { busy = true; setTimeout(function () { fetchAndShow(topic); }, 600); }
      };
      el.appendChild(b);
    }
  }

  function fetchAndShow(topic) {
    getReading(topic).then(function (res) {
      if (res.status === 'limit') { renderLimit(); return; }
      renderSpread(res.reading, res.status === 'again');
    }).catch(function () {
      $('spread').innerHTML = '<p class="center">เชื่อมต่อไม่สำเร็จ กรุณาลองใหม่อีกครั้ง</p>';
      $('summary').hidden = true; $('result-actions').hidden = false;
      show('step-result');
    });
  }

  function renderLimit() {
    $('spread').innerHTML = '';
    var s = $('summary');
    s.innerHTML = '<h3>🌙 วันนี้เปิดไพ่ฟรีครบแล้ว</h3><p>เปิดไพ่ฟรีได้วันละ 1 ครั้ง กลับมาใหม่พรุ่งนี้ หรือเลือกแพ็คเกจเริ่มต้นเพียง 29 บาท</p>';
    s.hidden = false; $('result-actions').hidden = false;
    show('step-result');
  }

  function renderSpread(r, again) {
    opened = 0;
    $('summary').hidden = true;
    $('result-actions').hidden = true;
    $('again-note').hidden = !again;
    var sp = $('spread');
    sp.innerHTML = r.cards.map(function (c, i) {
      return '<div class="slot"><div class="slot-label">ใบที่ ' + (i + 1) + ' · ' + c.pos + '</div>' +
        '<div class="flip"><div class="flip-inner"><div class="face front-back">✦</div>' +
        '<div class="face front"><div class="num">' + esc(c.n) + '</div><div class="sym">' + c.sym + '</div>' +
        '<div class="en">' + esc(c.en) + '</div><div class="th">' + esc(c.th) + '</div></div></div></div>' +
        '<div class="meaning"><div class="kw">' + esc(c.kw) + '</div>' + esc(c.text) + '</div></div>';
    }).join('');
    sp.querySelectorAll('.flip').forEach(function (flip) {
      flip.onclick = function () {
        if (flip.classList.contains('open')) return;
        flip.classList.add('open');
        if (++opened === 3) setTimeout(function () { renderSummary(r); }, 1200);
      };
    });
    show('step-result');
  }

  function renderSummary(r) {
    var s = $('summary');
    s.innerHTML =
      '<h3>🔮 สรุปคำทำนายเรื่อง' + esc(r.topic_name) + '</h3>' +
      '<div class="small muted">พลังดวงโดยรวม ' + r.pct + '%</div>' +
      '<div class="meter"><div style="width:0"></div></div>' +
      '<p>' + esc(r.mood) + '</p>' +
      '<div class="locked">🔒 การตีความตามตำแหน่งไพ่ จุดเด่น สิ่งที่ต้องระวัง คำแนะนำ และสี เลข วันมงคล มีในแบบเจาะลึก</div>' +
      '<p class="small muted">เปิดไพ่ฟรีได้วันละ 1 ครั้ง</p>';
    s.hidden = false;
    $('result-actions').hidden = false;
    setTimeout(function () { s.querySelector('.meter div').style.width = r.pct + '%'; }, 50);
    s.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  document.querySelectorAll('.topic').forEach(function (b) {
    b.onclick = function () { renderDeck(b.getAttribute('data-topic')); show('step-pick'); };
  });
})();
