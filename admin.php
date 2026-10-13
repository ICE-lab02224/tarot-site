<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'หลังร้าน | ' . $SITE['name'];

if (isset($_GET['logout'])) { unset($_SESSION['admin']); header('Location: admin.php'); exit; }
$err = '';
if (isset($_POST['password'])) {
    if (hash_equals($SITE['admin_password'], (string)$_POST['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        header('Location: admin.php'); exit;
    }
    sleep(1);
    $err = 'รหัสผ่านไม่ถูกต้อง';
}
// ยืนยันรับเงิน (ใช้กับโหมด promptpay)
if (!empty($_SESSION['admin']) && isset($_POST['confirm']) && csrf_ok($_POST['csrf'] ?? '')) {
    if ($o = order_load($_POST['confirm'])) order_mark_paid($o, 'admin');
    header('Location: admin.php'); exit;
}
include __DIR__ . '/includes/header.php';
$STATUS = ['pending' => 'รอจ่ายเงิน', 'paid' => 'จ่ายแล้ว รอเปิดไพ่', 'done' => 'เปิดไพ่แล้ว'];
?>
<section class="section">
<?php if (empty($_SESSION['admin'])): ?>
  <div class="narrow">
    <h2>เข้าสู่ระบบเจ้าของร้าน</h2>
    <?php if ($err): ?><div class="alert"><?= e($err) ?></div><?php endif; ?>
    <form method="post" class="card-box form">
      <label>รหัสผ่าน<input type="password" name="password" required autofocus></label>
      <button class="btn btn-block">เข้าสู่ระบบ</button>
    </form>
  </div>
<?php else:
  $list = order_all();
  $income = array_sum(array_map(function ($o) { return $o['status'] !== 'pending' ? $o['price'] : 0; }, $list));
?>
  <h2>คำสั่งซื้อทั้งหมด (<?= count($list) ?>)</h2>
  <p class="center">รายได้ที่จ่ายแล้ว <b class="gold"><?= baht($income) ?></b> · โหมดรับเงิน: <b><?= e($PAYMENT['mode']) ?></b>
    · <a href="admin.php?logout=1">ออกจากระบบ</a></p>
  <?php if ($SITE['admin_password'] === 'change-me-1234'): ?>
    <div class="alert">⚠ คุณยังใช้รหัสผ่านเริ่มต้นอยู่ กรุณาเปลี่ยนในไฟล์ includes/config.php</div>
  <?php endif; ?>
  <?php if (!$list): ?><p class="center muted">ยังไม่มีคำสั่งซื้อ</p><?php endif; ?>
  <div class="table-wrap"><table>
    <tr><th>เลขที่</th><th>เวลา</th><th>แพ็คเกจ</th><th>ลูกค้า</th><th>คำถาม</th><th>สถานะ</th><th></th></tr>
    <?php foreach ($list as $o): ?>
    <tr>
      <td><?= e($o['id']) ?></td><td><?= e($o['created']) ?></td>
      <td><?= e($o['pkg']) ?><br><small><?= baht($o['price']) ?></small></td>
      <td><?= e($o['name']) ?><br><small>LINE: <?= e($o['line']) ?><br><?= e($o['phone']) ?></small></td>
      <td><?= nl2br(e($o['question'])) ?></td>
      <td><span class="status <?= e($o['status']) ?>"><?= ($o['status'] === 'paid' && empty($o['auto'])) ? 'จ่ายแล้ว รอนัดคุย' : ($STATUS[$o['status']] ?? e($o['status'])) ?></span></td>
      <td>
        <?php if ($o['status'] === 'pending'): ?>
          <form method="post" onsubmit="return confirm('ยืนยันว่าได้รับเงิน <?= baht($o['price']) ?> แล้ว?')">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <button class="btn btn-small" name="confirm" value="<?= e($o['token']) ?>">ยืนยันรับเงิน</button>
          </form>
        <?php endif; ?>
        <a href="order.php?o=<?= e($o['token']) ?>" target="_blank">ดูหน้าลูกค้า</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </table></div>
<?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
