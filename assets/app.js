(function () {
  var CARDS = window.TAROT_CARDS || [];
  var POSITIONS = ['อดีต', 'ปัจจุบัน', 'อนาคต'];
  var TOPIC_NAME = { love: 'ความรัก', work: 'การงาน', money: 'การเงิน', general: 'ภาพรวม' };
  var topic = 'general', deck = [], picked = [], opened = 0;
  var $ = function (id) { return document.getElementById(id); };

  function shuffle(a) {
    a = a.slice();
    for (var i = a.length - 1; i > 0; i--) { var j = Math.floor(Math.random() * (i + 1)); var t = a[i]; a[i] = a[j]; a[j] = t; }
    return a;
  }

  function show(step) {
    ['step-topic', 'step-pick', 'step-result'].forEach(function (s) { $(s).hidden = s !== step; });
    $('play').scrollIntoView({ behavior: 'smooth' });
  }

  function renderDeck() {
    deck = shuffle(CARDS.map(function (_, i) { return i; }));
    picked = [];
    $('pick-count').textContent = '0';
    var el = $('deck');
    el.innerHTML = '';
    deck.forEach(function (cardIdx, i) {
      var b = document.createElement('div');
      b.className = 'back';
      b.textContent = '✦';
      b.style.animationDelay = (i * 0.03) + 's';
      b.onclick = function () { pick(cardIdx, b); };
      el.appendChild(b);
    });
  }

  function pick(cardIdx, el) {
    if (picked.length >= 3) return;
    picked.push(cardIdx);
    el.classList.add('picked');
    $('pick-count').textContent = picked.length;
    if (picked.length === 3) setTimeout(renderSpread, 600);
  }

  function renderSpread() {
    opened = 0;
    $('summary').hidden = true;
    $('result-actions').hidden = true;
    var sp = $('spread');
    sp.innerHTML = '';
    picked.forEach(function (idx, i) {
      var c = CARDS[idx];
      var slot = document.createElement('div');
      slot.className = 'slot';
      slot.innerHTML =
        '<div class="slot-label">ใบที่ ' + (i + 1) + ' · ' + POSITIONS[i] + '</div>' +
        '<div class="flip"><div class="flip-inner">' +
          '<div class="face front-back">✦</div>' +
          '<div class="face front"><div class="num">' + c.n + '</div><div class="sym">' + c.sym + '</div>' +
          '<div class="en">' + c.en + '</div><div class="th">' + c.th + '</div></div>' +
        '</div></div>' +
        '<div class="meaning"><div class="kw">' + c.kw + '</div>' + c[topic] + '</div>';
      var flip = slot.querySelector('.flip');
      flip.onclick = function () {
        if (flip.classList.contains('open')) return;
        flip.classList.add('open');
        opened++;
        if (opened === 3) setTimeout(renderSummary, 1200);
      };
      sp.appendChild(slot);
    });
    show('step-result');
  }

  function renderSummary() {
    var cs = picked.map(function (i) { return CARDS[i]; });
    var score = cs.reduce(function (s, c) { return s + c.tone; }, 0); // -3 ถึง 3
    var pct = Math.round(((score + 3) / 6) * 70 + 25);              // 25% - 95%
    var mood;
    if (score >= 2) mood = 'พลังงานโดยรวมสดใสมาก ดวงเรื่อง' + TOPIC_NAME[topic] + 'ของคุณกำลังเป็นขาขึ้น สิ่งที่หวังไว้มีแนวโน้มเป็นไปในทางที่ดี';
    else if (score >= 0) mood = 'พลังงานโดยรวมค่อนข้างดี เรื่อง' + TOPIC_NAME[topic] + 'มีทั้งโอกาสและบทเรียนปนกัน ถ้าตั้งใจและมีสติ ผลลัพธ์จะออกมาดี';
    else mood = 'ไพ่ชุดนี้เตือนให้ระมัดระวังในเรื่อง' + TOPIC_NAME[topic] + 'เป็นพิเศษ แต่ไม่ใช่เรื่องร้าย เป็นสัญญาณให้เตรียมตัวและตั้งสติก่อนตัดสินใจ';

    var story = 'จากอดีตที่เกี่ยวกับ <b>' + cs[0].kw.split(' ')[0] + '</b> (' + cs[0].th + ') ' +
      'ส่งผลมาถึงปัจจุบันที่คุณกำลังอยู่กับ <b>' + cs[1].kw.split(' ')[0] + '</b> (' + cs[1].th + ') ' +
      'และแนวโน้มข้างหน้าจะนำไปสู่ <b>' + cs[2].kw.split(' ')[0] + '</b> (' + cs[2].th + ')';

    var s = $('summary');
    s.innerHTML =
      '<h3>🔮 สรุปคำทำนายเรื่อง' + TOPIC_NAME[topic] + '</h3>' +
      '<div class="small muted">พลังดวงโดยรวม ' + pct + '%</div>' +
      '<div class="meter"><div style="width:0"></div></div>' +
      '<p>' + mood + '</p><p>' + story + '</p>' +
      '<p><b class="gold">คำแนะนำจากไพ่:</b> ' + cs[2].advice + '</p>' +
      '<p class="small muted">นี่เป็นการอ่านไพ่แบบย่อ 3 ใบ ถ้าอยากเห็นภาพละเอียดขึ้น เช่น ใจเขา อุปสรรค หรือรายเดือนตลอดปี ลองแพ็คเกจเจาะลึกได้</p>';
    s.hidden = false;
    $('result-actions').hidden = false;
    setTimeout(function () { s.querySelector('.meter div').style.width = pct + '%'; }, 50);
    s.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  document.querySelectorAll('.topic').forEach(function (b) {
    b.onclick = function () { topic = b.getAttribute('data-topic'); renderDeck(); show('step-pick'); };
  });
  $('btn-shuffle').onclick = renderDeck;
  $('btn-again').onclick = function () { show('step-topic'); };
})();
