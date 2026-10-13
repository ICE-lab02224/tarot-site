<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('ORDERS_DIR', __DIR__ . '/../data/orders');

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function baht($n) { return number_format($n) . ' บาท'; }

function find_package($id) {
    global $PACKAGES;
    foreach ($PACKAGES as $p) if ($p['id'] === $id) return $p;
    return null;
}

function csrf_token() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}

function csrf_ok($t) {
    return !empty($_SESSION['csrf']) && is_string($t) && hash_equals($_SESSION['csrf'], $t);
}

function base_url() {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dir;
}

// ---------- คำสั่งซื้อ (เก็บเป็นไฟล์ละ 1 รายการ ไม่ต้องใช้ฐานข้อมูล) ----------
// บรรทัดแรกของไฟล์กันไม่ให้คนนอกเปิดอ่านได้โดยตรง

function order_path($token) {
    if (!preg_match('/^[a-f0-9]{32}$/', (string)$token)) return null;
    return ORDERS_DIR . '/' . $token . '.php';
}

function order_save(array $o) {
    $o['updated'] = date('Y-m-d H:i');
    return file_put_contents(order_path($o['token']),
        "<?php http_response_code(404); exit; ?>\n" . json_encode($o, JSON_UNESCAPED_UNICODE), LOCK_EX) !== false;
}

function order_load($token) {
    $p = order_path($token);
    if (!$p || !is_file($p)) return null;
    $raw = file_get_contents($p);
    return json_decode(substr($raw, strpos($raw, "\n") + 1), true);
}

function order_all() {
    $out = [];
    foreach (glob(ORDERS_DIR . '/*.php') as $f) {
        $t = basename($f, '.php');
        if ($o = order_load($t)) $out[] = $o;
    }
    usort($out, function ($a, $b) { return strcmp($b['created'], $a['created']); });
    return $out;
}

function order_mark_paid(array &$o, $note = '') {
    if ($o['status'] !== 'pending') return;
    $o['status'] = 'paid';
    $o['paid_at'] = date('Y-m-d H:i');
    if ($note) $o['paid_note'] = $note;
    order_save($o);
}

// ---------- QR พร้อมเพย์ (มาตรฐาน EMVCo ของธนาคารแห่งประเทศไทย) ----------

function promptpay_payload($id, $amount) {
    $id = preg_replace('/\D/', '', $id);
    if (strlen($id) === 10) {                       // เบอร์มือถือ
        $acc = '01' . '13' . '0066' . substr($id, 1);
    } else {                                        // เลขบัตรประชาชน / เลขผู้เสียภาษี
        $acc = '02' . sprintf('%02d', strlen($id)) . $id;
    }
    $f = function ($tag, $val) { return $tag . sprintf('%02d', strlen($val)) . $val; };
    $merchant = $f('00', 'A000000677010111') . $acc;
    $data = $f('00', '01') . $f('01', '12') . $f('29', $merchant) . $f('53', '764')
          . $f('54', number_format($amount, 2, '.', '')) . $f('58', 'TH') . '6304';
    return $data . crc16($data);
}

function crc16($s) {
    $crc = 0xFFFF;
    for ($i = 0; $i < strlen($s); $i++) {
        $crc ^= ord($s[$i]) << 8;
        for ($j = 0; $j < 8; $j++) {
            $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
            $crc &= 0xFFFF;
        }
    }
    return strtoupper(sprintf('%04X', $crc));
}

// ---------- Omise (รับเงินอัตโนมัติ) ----------

function omise_request($method, $path, $params = []) {
    global $PAYMENT;
    $ch = curl_init('https://api.omise.co' . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => $PAYMENT['omise_secret_key'] . ':',
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CUSTOMREQUEST  => $method,
    ]);
    if ($params) curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    $res = curl_exec($ch);
    curl_close($ch);
    return $res ? json_decode($res, true) : null;
}

function omise_create_promptpay(array &$o) {
    $c = omise_request('POST', '/charges', [
        'amount'      => (int)round($o['price'] * 100),   // หน่วยสตางค์
        'currency'    => 'thb',
        'source'      => ['type' => 'promptpay'],
        'description' => 'Order ' . $o['id'],
    ]);
    if (!$c || ($c['object'] ?? '') !== 'charge') return false;
    $o['charge_id'] = $c['id'];
    $o['qr_image']  = $c['source']['scannable_code']['image']['download_uri'] ?? '';
    order_save($o);
    return true;
}

function omise_check(array &$o) {
    if (empty($o['charge_id'])) return;
    $c = omise_request('GET', '/charges/' . rawurlencode($o['charge_id']));
    if ($c && ($c['status'] ?? '') === 'successful') order_mark_paid($o, 'Omise');
}
