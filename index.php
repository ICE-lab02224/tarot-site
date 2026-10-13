<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/cards.php';
$pageTitle = $SITE['name'] . ' | ดูดวงไพ่ทาโรต์ออนไลน์';
include __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <h1>ไพ่ทาโรต์รู้ใจ<br><span class="gold">ตอบทุกคำถามที่คุณสงสัย</span></h1>
  <p class="lead"><?= e($SITE['tagline']) ?><br>ลองเปิดไพ่ 3 ใบฟรีด้านล่าง แล้วให้<?= e($SITE['reader']) ?>อ่านไพ่ให้คุณแบบเจาะลึก</p>
  <a href="#play" class="btn">✨ เปิดไพ่ฟรีตอนนี้</a>
  <a href="#packages" class="btn btn-ghost">ดูแพ็คเกจ</a>
</section>

<!-- ===== ลูกเล่น: เลือกไพ่เอง ===== -->
<section id="play" class="section">
  <h2>เปิดไพ่ฟรี 3 ใบ</h2>
  <div id="step-topic">
    <p class="center">1. ตั้งจิตอธิษฐาน แล้วเลือกเรื่องที่อยากรู้</p>
    <div class="topics">
      <button class="topic" data-topic="love">💖 ความรัก</button>
      <button class="topic" data-topic="work">💼 การงาน</button>
      <button class="topic" data-topic="money">💰 การเงิน</button>
      <button class="topic" data-topic="general">🌟 ภาพรวม</button>
    </div>
  </div>

  <div id="step-pick" hidden>
    <p class="center">2. หายใจลึก ๆ แล้วเลือกไพ่ที่รู้สึกว่า "ใช่" <b id="pick-count">0</b>/3 ใบ</p>
    <div class="center"><button id="btn-shuffle" class="btn btn-ghost btn-small">🔀 สับไพ่ใหม่</button></div>
    <div id="deck" class="deck"></div>
  </div>

  <div id="step-result" hidden>
    <p class="center">3. แตะที่ไพ่เพื่อเปิดทีละใบ</p>
    <div id="spread" class="spread"></div>
    <div id="summary" class="summary" hidden></div>
    <div class="center" id="result-actions" hidden>
      <a href="booking.php" class="btn">🔮 ดูดวงแบบเจาะลึก</a>
      <button id="btn-again" class="btn btn-ghost">เปิดไพ่ใหม่อีกครั้ง</button>
    </div>
  </div>
</section>

<!-- ===== แพ็คเกจ ===== -->
<section id="packages" class="section">
  <h2>แพ็คเกจดูดวง</h2>
  <p class="center muted">ทุกแพ็คเกจอ่านไพ่โดย<?= e($SITE['reader']) ?> เปิดไพ่เองได้ตลอด 24 ชม. รู้ผลทันที</p>
  <div class="packages">
    <?php foreach ($PACKAGES as $p): ?>
    <div class="pkg<?= $p['popular'] ? ' popular' : '' ?>">
      <?php if ($p['popular']): ?><div class="badge">ยอดนิยม</div><?php endif; ?>
      <div class="pkg-icon"><?= $p['icon'] ?></div>
      <h3><?= e($p['name']) ?></h3>
      <div class="price"><?= number_format($p['price']) ?> <small>บาท</small></div>
      <p class="muted small"><?= e($p['time']) ?></p>
      <ul>
        <?php foreach ($p['features'] as $f): ?><li>✔ <?= e($f) ?></li><?php endforeach; ?>
      </ul>
      <a href="booking.php?pkg=<?= e($p['id']) ?>" class="btn btn-block">เลือกแพ็คเกจนี้</a>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ===== ขั้นตอน ===== -->
<section id="how" class="section">
  <h2>ดูดวงง่าย ๆ 4 ขั้นตอน รู้ผลใน 1 นาที</h2>
  <div class="steps">
    <div class="step"><span>1</span><h4>เลือกแพ็คเกจ</h4><p>กรอกคำถามที่อยากรู้</p></div>
    <div class="step"><span>2</span><h4>สแกนจ่าย</h4><p>QR พร้อมเพย์ ทุกธนาคาร</p></div>
    <div class="step"><span>3</span><h4>เลือกไพ่เอง</h4><p>ตั้งจิตแล้วเลือกไพ่ด้วยมือคุณ</p></div>
    <div class="step"><span>4</span><h4>รู้ผลทันที</h4><p>คำทำนายละเอียดพร้อมของมงคล เก็บลิงก์ไว้อ่านได้ตลอด</p></div>
  </div>
</section>

<!-- ===== รีวิว ===== -->
<section class="section">
  <h2>เสียงจากลูกค้า</h2>
  <div class="reviews">
    <?php foreach ($REVIEWS as $r): ?>
    <div class="review"><div class="gold">★★★★★</div><p>“<?= e($r['text']) ?>”</p><p class="muted small">— <?= e($r['name']) ?> · <?= e($r['pkg']) ?></p></div>
    <?php endforeach; ?>
  </div>
</section>

<section class="section cta">
  <h2>พร้อมรู้คำตอบแล้วหรือยัง?</h2>
  <a href="booking.php" class="btn">สั่งดูดวงเลย</a>
  <a href="<?= e($SITE['line_url']) ?>" class="btn btn-ghost" target="_blank" rel="noopener">สอบถามทาง LINE</a>
</section>

<script>window.TAROT_CARDS = <?= json_encode($CARDS, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;</script>
<script src="assets/app.js?v=1"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
