<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'สั่งดูดวง | ' . $SITE['name'];
$errors = [];
$old = ['pkg' => $_GET['pkg'] ?? '', 'topic' => 'general', 'name' => '', 'line' => '', 'phone' => '', 'birth' => '', 'question' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $k => $v) $old[$k] = trim((string)($_POST[$k] ?? ''));
    if (!csrf_ok($_POST['csrf'] ?? '')) $errors[] = 'หน้าเว็บหมดอายุ กรุณาลองส่งใหม่อีกครั้ง';
    if (!empty($_POST['website'])) $errors[] = 'ไม่สามารถส่งข้อมูลได้'; // กันบอทสแปม
    $pkg = find_package($old['pkg']);
    if (!$pkg) $errors[] = 'กรุณาเลือกแพ็คเกจ';
    if (mb_strlen($old['name']) < 2) $errors[] = 'กรุณากรอกชื่อ';
    if ($old['line'] === '' && $old['phone'] === '') $errors[] = 'กรุณากรอก LINE ID หรือเบอร์โทรอย่างน้อย 1 ช่อง';
    if (mb_strlen($old['question']) < 5) $errors[] = 'กรุณาพิมพ์คำถามที่อยากรู้';
    foreach ($old as $k => $v) $old[$k] = mb_substr($v, 0, $k === 'question' ? 1000 : 100);

    if (!$errors) {
        $order = [
            'token'    => bin2hex(random_bytes(16)),
            'id'       => date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4)),
            'created'  => date('Y-m-d H:i'),
            'pkg_id'   => $pkg['id'],
            'pkg'      => $pkg['name'],
            'price'    => $pkg['price'],
            'auto'     => $pkg['auto'] ?? true,
            'topic'    => in_array($old['topic'], ['love', 'work', 'money', 'general'], true) ? $old['topic'] : 'general',
            'name'     => $old['name'],
            'line'     => $old['line'],
            'phone'    => $old['phone'],
            'birth'    => $old['birth'],
            'question' => $old['question'],
            'status'   => 'pending',
            'cards'    => [],
        ];
        if (order_save($order)) {
            if ($SITE['email']) {
                $body = "มีคำสั่งซื้อใหม่\n\n";
                foreach (['id', 'pkg', 'price', 'name', 'line', 'phone', 'question'] as $k) $body .= "$k: {$order[$k]}\n";
                @mail($SITE['email'], '=?UTF-8?B?' . base64_encode('คำสั่งซื้อใหม่ ' . $order['id']) . '?=', $body,
                      "Content-Type: text/plain; charset=UTF-8");
            }
            header('Location: order.php?o=' . $order['token']);
            exit;
        }
        $errors[] = 'บันทึกข้อมูลไม่สำเร็จ กรุณาติดต่อทาง LINE แทน';
    }
}
include __DIR__ . '/includes/header.php';
?>
<section class="section narrow">

  <h2>สั่งดูดวง</h2>
  <p class="center muted">จ่ายเงินแล้วเลือกไพ่เองได้ทันที รู้ผลภายในไม่กี่วินาที</p>
  <?php if ($errors): ?>
    <div class="alert"><?php foreach ($errors as $er): ?><div>⚠ <?= e($er) ?></div><?php endforeach; ?></div>
  <?php endif; ?>
  <form method="post" class="card-box form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
    <label>เลือกแพ็คเกจ *
      <select name="pkg" required>
        <option value="">-- เลือกแพ็คเกจ --</option>
        <?php foreach ($PACKAGES as $p): ?>
          <option value="<?= e($p['id']) ?>" <?= $old['pkg'] === $p['id'] ? 'selected' : '' ?>><?= e($p['icon'] . ' ' . $p['name'] . ' - ' . baht($p['price'])) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label id="topic-field">อยากรู้เรื่องอะไร
      <select name="topic">
        <?php foreach (['general' => '🌟 ภาพรวม', 'love' => '💖 ความรัก', 'work' => '💼 การงาน', 'money' => '💰 การเงิน'] as $k => $v): ?>
          <option value="<?= $k ?>" <?= $old['topic'] === $k ? 'selected' : '' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>ชื่อเล่น / ชื่อที่ให้เรียก *<input name="name" value="<?= e($old['name']) ?>" required></label>
    <div class="row">
      <label>LINE ID<input name="line" value="<?= e($old['line']) ?>"></label>
      <label>เบอร์โทร<input name="phone" value="<?= e($old['phone']) ?>" inputmode="tel"></label>
    </div>
    <label>วันเกิด (ไม่บังคับ)<input type="date" name="birth" value="<?= e($old['birth']) ?>"></label>
    <label>คำถามที่อยากรู้ *<textarea name="question" rows="4" required placeholder="เช่น ความรักกับคนที่คุยอยู่จะไปต่อได้ไหม"><?= e($old['question']) ?></textarea></label>
    <button class="btn btn-block">ไปหน้าชำระเงิน</button>
    <p class="muted small center">คำทำนายสร้างโดยระบบอัตโนมัติจากไพ่ที่คุณเลือก ข้อมูลของคุณเก็บเป็นความลับ</p>
  </form>
</section>
<script>
(function () {
  var sel = document.querySelector('[name=pkg]'), f = document.getElementById('topic-field');
  function upd() { f.style.display = (sel.value === 'love' || sel.value === 'career' || sel.value === 'live') ? 'none' : ''; }
  sel.addEventListener('change', upd); upd();
})();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
