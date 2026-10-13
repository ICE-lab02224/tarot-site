<?php
// =====================================================
//  เปิดไพ่ฟรีหน้าแรก: สุ่มที่เซิร์ฟเวอร์ และจำกัดจำนวนครั้ง
//  - 1 ครั้งต่อวันต่อเครื่อง (เปิดซ้ำวันเดียวกันจะได้ผลเดิม สุ่มใหม่ไม่ได้)
//  - กันคนลบคุกกี้/เปิดโหมดไม่ระบุตัวตน ด้วยการจำกัดต่อ IP อีกชั้น
// =====================================================
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/reading.php';

define('FREE_PER_IP_PER_DAY', 5);   // เผื่อหลายคนใช้เน็ตบ้าน/ร้านเดียวกัน
define('FREE_DIR', __DIR__ . '/data/free');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo '{}'; exit; }

$topic = $_POST['topic'] ?? 'general';
if (!in_array($topic, ['love', 'work', 'money', 'general'], true)) $topic = 'general';

// ระบุเครื่องด้วยคุกกี้สุ่ม (เก็บ 1 ปี)
$vid = $_COOKIE['tv'] ?? '';
if (!preg_match('/^[a-f0-9]{32}$/', $vid)) {
    $vid = bin2hex(random_bytes(16));
    setcookie('tv', $vid, ['expires' => time() + 31536000, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
}
$ipKey = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . __DIR__);  // ไม่เก็บ IP ตรง ๆ

if (!is_dir(FREE_DIR)) mkdir(FREE_DIR, 0755, true);
$file = FREE_DIR . '/' . date('Y-m-d') . '.php';
$fp = fopen($file, 'c+');
flock($fp, LOCK_EX);
$raw = stream_get_contents($fp);
$db = $raw ? json_decode(substr($raw, strpos($raw, "\n") + 1), true) : null;
if (!$db) $db = ['v' => [], 'ip' => []];

if (isset($db['v'][$vid])) {
    // วันนี้เปิดไปแล้ว: ส่งผลเดิมกลับไป
    $r = $db['v'][$vid];
    $res = ['status' => 'again', 'reading' => build_free_reading($r['cards'], $r['topic'])];
} elseif (($db['ip'][$ipKey] ?? 0) >= FREE_PER_IP_PER_DAY) {
    $res = ['status' => 'limit'];
} else {
    $cards = draw_cards(3);
    $db['v'][$vid] = ['cards' => $cards, 'topic' => $topic];
    $db['ip'][$ipKey] = ($db['ip'][$ipKey] ?? 0) + 1;
    $res = ['status' => 'ok', 'reading' => build_free_reading($cards, $topic)];
    ftruncate($fp, 0); rewind($fp);
    fwrite($fp, "<?php http_response_code(404); exit; ?>\n" . json_encode($db));
}
flock($fp, LOCK_UN);
fclose($fp);

// ลบไฟล์เก่าเกิน 3 วัน
foreach (glob(FREE_DIR . '/*.php') as $f) {
    if (basename($f) !== 'index.php' && filemtime($f) < time() - 3 * 86400) @unlink($f);
}
echo json_encode($res, JSON_UNESCAPED_UNICODE);
