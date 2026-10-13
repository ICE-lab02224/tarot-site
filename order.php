<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/reading.php';

$token = $_GET['o'] ?? '';
$order = order_load($token);
if (!$order) { http_response_code(404); $pageTitle = 'ไม่พบคำสั่งซื้อ'; include __DIR__ . '/includes/header.php';
    echo '<section class="section narrow center"><h2>ไม่พบคำสั่งซื้อนี้</h2><a class="btn" href="index.php">กลับหน้าแรก</a></section>';
    include __DIR__ . '/includes/footer.php'; exit; }

$mode = $PAYMENT['mode'];
$need = count(spread_for($order['pkg_id']));

// ลูกค้ากดเช็กสถานะ (หน้าเว็บถามทุก ๆ 5 วินาที)
if (isset($_GET['status'])) {
    if ($order['status'] === 'pending' && $mode === 'omise') omise_check($order);
    header('Content-Type: application/json');
    echo json_encode(['status' => $order['status']]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok($_POST['csrf'] ?? '')) {
    // โหมดทดลอง: จำลองการจ่ายเงิน
    if (($_POST['action'] ?? '') === 'demo_pay' && $mode === 'demo') order_mark_paid($order, 'demo');
    // ลูกค้าเลือกไพ่ครบแล้ว -> สุ่มไพ่และเก็บผล
    if (($_POST['action'] ?? '') === 'draw' && $order['status'] === 'paid' && $order['auto'] && !$order['cards']) {
        $order['cards'] = draw_cards($need);
        $order['status'] = 'done';
        $order['read_at'] = date('Y-m-d H:i');
        order_save($order);
    }
    header('Location: order.php?o=' . $token);
    exit;
}

if ($order['status'] === 'pending' && $mode === 'omise' && empty($order['charge_id'])) omise_create_promptpay($order);

$pageTitle = 'คำสั่งซื้อ ' . $order['id'] . ' | ' . $SITE['name'];
include __DIR__ . '/includes/header.php';
$link = base_url() . '/order.php?o=' . $token;
?>
<section class="section">
  <p class="center muted small">คำสั่งซื้อ <b class="gold"><?= e($order['id']) ?></b> · <?= e($order['pkg']) ?> · <?= baht($order['price']) ?></p>

<?php if ($order['status'] === 'pending'): ?>
  <!-- ========== ขั้นที่ 1: ชำระเงิน ========== -->
  <div class="card-box narrow center" id="pay-box" data-poll="order.php?o=<?= e($token) ?>&status=1">
    <h2>สแกนจ่ายด้วยพร้อมเพย์</h2>
    <div class="price"><?= baht($order['price']) ?></div>

    <?php if ($mode === 'omise' && !empty($order['qr_image'])): ?>
      <img src="<?= e($order['qr_image']) ?>" alt="PromptPay QR" class="qr-img">
      <p>สแกนด้วยแอปธนาคารใดก็ได้ ระบบจะเปิดให้เลือกไพ่ <b>อัตโนมัติ</b> ทันทีที่จ่ายสำเร็จ</p>
    <?php elseif ($mode === 'promptpay'): ?>
      <div id="qr" class="qr" data-payload="<?= e(promptpay_payload($SITE['promptpay'], $order['price'])) ?>"></div>
      <p>ชื่อบัญชี <?= e($SITE['promptpay_name']) ?></p>
      <p>หลังโอนแล้ว ส่งสลิปทาง LINE <b><?= e($SITE['line_id']) ?></b><br>เมื่อร้านยืนยัน หน้านี้จะเปิดให้เลือกไพ่เองอัตโนมัติ</p>
      <a href="<?= e($SITE['line_url']) ?>" class="btn btn-ghost" target="_blank" rel="noopener">💬 ส่งสลิปทาง LINE</a>
    <?php elseif ($mode === 'omise'): ?>
      <div class="alert">ระบบชำระเงินขัดข้องชั่วคราว กรุณาติดต่อทาง LINE <?= e($SITE['line_id']) ?></div>
    <?php else: ?>
      <div class="qr demo-qr">QR<br>ตัวอย่าง</div>
      <p class="muted">ตอนนี้เว็บอยู่ใน <b>โหมดทดลอง</b> ยังไม่มีการรับเงินจริง</p>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <button class="btn" name="action" value="demo_pay">✅ จำลองว่าจ่ายเงินแล้ว</button>
      </form>
    <?php endif; ?>
    <p class="muted small waiting">⏳ กำลังรอการชำระเงิน...</p>
  </div>

<?php elseif ($order['status'] === 'paid' && !$order['auto']): ?>
  <!-- ========== แพ็คเกจคุยสด ========== -->
  <div class="card-box narrow center">
    <div style="font-size:3rem">📞</div>
    <h2>ชำระเงินเรียบร้อย</h2>
    <p>กรุณาแอดไลน์เพื่อนัดวันเวลาที่สะดวก<br>แจ้งหมายเลขคำสั่งซื้อ <b class="gold"><?= e($order['id']) ?></b></p>
    <a href="<?= e($SITE['line_url']) ?>" class="btn" target="_blank" rel="noopener">💬 นัดเวลาทาง LINE</a>
  </div>

<?php elseif ($order['status'] === 'paid'): ?>
  <!-- ========== ขั้นที่ 2: ลูกค้าเลือกไพ่เอง ========== -->
  <h2>ชำระเงินเรียบร้อย ✨</h2>
  <p class="center">ตั้งจิตถึงคำถาม “<?= e($order['question']) ?>”<br>แล้วเลือกไพ่ <b><?= $need ?></b> ใบ (เลือกแล้ว <b id="pick-count">0</b>)</p>
  <div id="deck" class="deck" data-need="<?= $need ?>"></div>
  <form method="post" id="draw-form" hidden>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="draw">
  </form>

<?php else: $r = build_reading($order); ?>
  <!-- ========== ขั้นที่ 3: คำทำนาย ========== -->
  <h2>คำทำนายของคุณ<?= e($order['name']) ?></h2>
  <p class="center muted">คำถาม: “<?= e($order['question']) ?>” · เปิดไพ่เมื่อ <?= e($order['read_at']) ?></p>
  <div class="center"><button class="btn btn-ghost btn-small" id="open-all">เปิดไพ่ทั้งหมด</button></div>
  <div class="spread reading">
    <?php foreach ($r['items'] as $i => $it): $c = $it['card']; ?>
    <div class="slot">
      <div class="slot-label"><?= $i + 1 ?>. <?= e($it['label']) ?></div>
      <div class="flip"><div class="flip-inner">
        <div class="face front-back">✦</div>
        <div class="face front"><div class="num"><?= e($c['n']) ?></div><div class="sym"><?= $c['sym'] ?></div>
          <div class="en"><?= e($c['en']) ?></div><div class="th"><?= e($c['th']) ?></div></div>
      </div></div>
      <div class="meaning"><div class="kw"><?= e($c['kw']) ?></div><?= e($it['text']) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="summary" id="summary" hidden>
    <h3>🔮 สรุปคำทำนาย<?= $order['pkg_id'] === 'year' ? 'ตลอดปี' : 'เรื่อง' . e($r['topic']) ?></h3>
    <div class="small muted">พลังดวงโดยรวม <?= $r['pct'] ?>%</div>
    <div class="meter"><div data-w="<?= $r['pct'] ?>" style="width:0"></div></div>
    <?php if ($r['answer']): ?><p><b class="gold">คำตอบ:</b> <?= e($r['answer']) ?></p><?php endif; ?>
    <p><?= e($r['overall']) ?></p>
    <?php if ($r['highlight']): ?><p>🌟 <?= e($r['highlight']) ?></p><?php endif; ?>
    <p>⚠️ <?= e($r['caution']) ?></p>
    <p><b class="gold">คำแนะนำสำคัญ:</b> <?= e($r['advice']) ?></p>
    <p>🍀 สีมงคล <b><?= $r['lucky']['color'] ?></b> · เลขนำโชค <b><?= $r['lucky']['number'] ?></b> · วันมงคล <b>วัน<?= $r['lucky']['day'] ?></b></p>
  </div>
  <div class="card-box narrow center small">
    <p>🔖 บันทึกลิงก์นี้ไว้ เพื่อกลับมาอ่านคำทำนายได้ทุกเมื่อ</p>
    <input class="link-box" value="<?= e($link) ?>" readonly onclick="this.select()">
    <p><a href="<?= e($SITE['line_url']) ?>" target="_blank" rel="noopener">ติดปัญหาการใช้งาน ทักไลน์ <?= e($SITE['line_id']) ?></a></p>
  </div>
<?php endif; ?>
</section>
<?php if ($mode === 'promptpay' && $order['status'] === 'pending'): ?>
<script src="assets/qrcode.js"></script>
<?php endif; ?>
<script src="assets/order.js?v=1"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
