<?php
session_start();
mb_internal_encoding('UTF-8');

define('UPLOAD_DIR', __DIR__ . '/upload/');
define('META_FILE', UPLOAD_DIR . 'metadata.json');
define('PERSONS_FILE', UPLOAD_DIR . 'persons.json');
define('NOTIF_FILE', UPLOAD_DIR . 'notifications.json');
define('VAPID_FILE', UPLOAD_DIR . 'vapid.json');
define('RENEW_DAYS', 33);
define('REMIND_WINDOW', 3);
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'admin1234');

if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

$MONTH_NAMES = [
    1 => 'farvardin', 2 => 'ordibehesht', 3 => 'khordad',
    4 => 'tir', 5 => 'mordad', 6 => 'shahrivar',
    7 => 'mehr', 8 => 'aban', 9 => 'azar',
    10 => 'dey', 11 => 'bahman', 12 => 'esfand'
];
$MONTH_NAMES_FA = [
    1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد',
    4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور',
    7 => 'مهر', 8 => 'آبان', 9 => 'آذر',
    10 => 'دی', 11 => 'بهمن', 12 => 'اسفند'
];
$TAB_ORDER = [5, 6, 7, 8, 9, 10, 11, 12, 1, 2, 3, 4];

$TYPE_ORDER = ['diet','analyse','program','challenge','report','test','homeplan','warmup','other'];
$TYPE_FA = [
    'diet' => 'رژیم', 'analyse' => 'آنالیز', 'program' => 'برنامه',
    'challenge' => 'چالش', 'report' => 'گزارش', 'test' => 'تست',
    'homeplan' => 'خانگی', 'warmup' => 'گرم کردن', 'other' => 'other'
];
$TYPE_KEYWORDS = [
    'homeplan'  => ['home plan', 'homeplan', 'home-plan', 'home_plan', 'خانگی'],
    'warmup'    => ['warm up', 'warmup', 'warm-up', 'warm_up', 'گرم کردن', 'گرم‌کردن', 'وارم اپ'],
    'challenge' => ['challenge', 'چالش'],
    'report'    => ['report', 'گزارش', 'ریپورت'],
    'test'      => ['test', 'تست', 'آزمایش', 'آزمون'],
    'analyse'   => ['آنالیز', 'انالیز', 'آناليز', 'analyse', 'analysis', 'analyze', 'analiz'],
    'diet'      => ['رژیم', 'رژيم', 'diet', 'regime'],
    'program'   => ['برنامه', 'program', 'programme', 'plan'],
];
$TYPE_COLORS_HEX = [
    'diet' => '#4ade80', 'analyse' => '#38bdf8', 'program' => '#c084fc',
    'challenge' => '#fb923c', 'report' => '#f472b6', 'test' => '#22d3ee',
    'homeplan' => '#a3e635', 'warmup' => '#fbbf24', 'other' => '#9ca3af'
];
$TYPE_COLORS = [
    'diet' => 'green', 'analyse' => 'blue', 'program' => 'purple',
    'challenge' => 'orange', 'report' => 'pink', 'test' => 'cyan',
    'homeplan' => 'lime', 'warmup' => 'amber', 'other' => 'gray'
];

$AVATAR_COLORS = [
    ['#7c6ef0', '#5b4dc7'], ['#f43f5e', '#be123c'], ['#22c55e', '#15803d'],
    ['#38bdf8', '#0369a1'], ['#f59e0b', '#b45309'], ['#a855f7', '#6b21a8'],
    ['#ec4899', '#9d174d'], ['#14b8a6', '#0f766e'], ['#f97316', '#c2410c'],
    ['#8b5cf6', '#5b21b6'], ['#06b6d4', '#0e7490'], ['#84cc16', '#4d7c0f'],
    ['#e11d48', '#881337'], ['#3b82f6', '#1d4ed8'], ['#d946ef', '#86198f'],
    ['#10b981', '#047857'],
];

// ─── Utilities ───
function pad($n) { return str_pad($n, 2, '0', STR_PAD_LEFT); }

function fmtSize($b) {
    if ($b >= 1073741824) return number_format($b / 1073741824, 1) . ' GB';
    if ($b >= 1048576) return number_format($b / 1048576, 1) . ' MB';
    if ($b >= 1024) return number_format($b / 1024, 1) . ' KB';
    return $b . ' B';
}

function toJalali($gy, $gm, $gd) {
    $g = [0,31,59,90,120,151,181,212,243,273,304,334];
    $gy2 = ($gm > 2) ? $gy + 1 : $gy;
    $d = 355666 + (365 * $gy) + floor(($gy2 + 3) / 4) - floor(($gy2 + 99) / 100) + floor(($gy2 + 399) / 400) + $gd + $g[$gm - 1];
    $jy = -1595 + 33 * floor($d / 12053); $d %= 12053;
    $jy += 4 * floor($d / 1461); $d %= 1461;
    if ($d > 365) { $jy += floor(($d - 1) / 365); $d = ($d - 1) % 365; }
    if ($d < 186) { $jm = 1 + floor($d / 31); $jd = 1 + ($d % 31); }
    else { $jm = 7 + floor(($d - 186) / 30); $jd = 1 + (($d - 186) % 30); }
    return ['year' => (int)$jy, 'month' => (int)$jm, 'day' => (int)$jd];
}
function getJalali($ts = null) {
    if (!$ts) $ts = time();
    return toJalali((int)date('Y', $ts), (int)date('m', $ts), (int)date('d', $ts));
}
function jalaliMonthLen($jy, $jm) {
    if ($jm <= 6) return 31;
    if ($jm <= 11) return 30;
    $r = (($jy % 33) + 33) % 33;
    return in_array($r, [1, 5, 9, 13, 17, 22, 26, 30]) ? 30 : 29;
}
function dayStart($ts) { return (int)strtotime(date('Y-m-d 00:00:00', $ts)); }
function jalaliMidnight($jy, $jm, $jd, $nearTs) {
    $anchor = dayStart($nearTs);
    for ($off = -45; $off <= 45; $off++) {
        $t = $anchor + ($off * 86400);
        $j = toJalali((int)date('Y', $t), (int)date('m', $t), (int)date('d', $t));
        if ($j['year'] === $jy && $j['month'] === $jm && $j['day'] === $jd) return $t;
    }
    return null;
}
function jalaliPlusOneMonth($ts) {
    $j = getJalali($ts);
    $tm = $j['month'] + 1;
    $ty = $j['year'];
    if ($tm > 12) { $tm = 1; $ty++; }
    $td = min($j['day'], jalaliMonthLen($ty, $tm));
    $guess = $ts + (30 * 86400);
    return jalaliMidnight($ty, $tm, $td, $guess);
}

function isAdmin() { return isset($_SESSION['admin']) && $_SESSION['admin'] === true; }
function avatarColor($seed) {
    global $AVATAR_COLORS;
    $h = 0;
    $len = strlen($seed);
    for ($i = 0; $i < $len; $i++) $h = ($h * 31 + ord($seed[$i])) % 100000;
    return $AVATAR_COLORS[$h % count($AVATAR_COLORS)];
}

// ─── Storage ───
function loadMeta() {
    if (file_exists(META_FILE)) {
        $j = json_decode(file_get_contents(META_FILE), true);
        return is_array($j) ? $j : [];
    }
    return [];
}
function saveMeta($d) {
    @file_put_contents(META_FILE, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
function loadPersons() {
    if (file_exists(PERSONS_FILE)) {
        $j = json_decode(file_get_contents(PERSONS_FILE), true);
        return is_array($j) ? $j : [];
    }
    return [];
}
function savePersons($d) {
    @file_put_contents(PERSONS_FILE, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
function registerPerson($mdir, $slug, $displayName = '') {
    $p = loadPersons();
    $k = $mdir . '/' . $slug;
    if (!isset($p[$k])) {
        $p[$k] = ['month' => $mdir, 'slug' => $slug, 'name' => $displayName, 'created' => time(), 'renewed' => 0, 'dismissed' => 0];
        savePersons($p);
    } elseif ($displayName !== '' && empty($p[$k]['name'])) {
        $p[$k]['name'] = $displayName;
        savePersons($p);
    }
}
function unregisterPerson($mdir, $slug) {
    $p = loadPersons();
    unset($p[$mdir . '/' . $slug]);
    savePersons($p);
}

// ─── Notifications store ───
function loadNotif() {
    if (file_exists(NOTIF_FILE)) {
        $j = json_decode(file_get_contents(NOTIF_FILE), true);
        if (is_array($j)) {
            if (!isset($j['devices']) || !is_array($j['devices'])) $j['devices'] = [];
            if (!isset($j['messages']) || !is_array($j['messages'])) $j['messages'] = [];
            return $j;
        }
    }
    return ['devices' => [], 'messages' => []];
}
function saveNotif($d) {
    @file_put_contents(NOTIF_FILE, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
function registerDevice($id, $ua = '') {
    $n = loadNotif();
    $id = preg_replace('/[^a-zA-Z0-9_\-]/', '', $id);
    if ($id === '') return false;
    if (!isset($n['devices'][$id])) {
        $n['devices'][$id] = ['first' => time(), 'last' => time(), 'ua' => mb_substr($ua, 0, 160)];
    } else {
        $n['devices'][$id]['last'] = time();
    }
    saveNotif($n);
    return true;
}
function removeDevice($id) {
    $n = loadNotif();
    unset($n['devices'][$id]);
    saveNotif($n);
}
function pushMessage($title, $body, $url = '') {
    $n = loadNotif();
    $n['messages'][] = [
        'id'    => 'n' . time() . rand(100, 999),
        'title' => $title,
        'body'  => $body,
        'url'   => $url,
        'time'  => time(),
    ];
    // keep last 40
    if (count($n['messages']) > 40) {
        $n['messages'] = array_slice($n['messages'], -40);
    }
    saveNotif($n);
}
function deletePushMessage($id) {
    $n = loadNotif();
    $n['messages'] = array_values(array_filter($n['messages'], function($m) use ($id) {
        return (isset($m['id']) ? $m['id'] : '') !== $id;
    }));
    saveNotif($n);
}
function clearPushMessages() {
    $n = loadNotif();
    $n['messages'] = [];
    saveNotif($n);
}

/* ════════════════  WEB PUSH (VAPID + RFC 8291)  ════════════════ */

function b64url_enc($s) { return rtrim(strtr(base64_encode($s), '+/', '-_'), '='); }
function b64url_dec($s) {
    $s = strtr($s, '-_', '+/');
    $p = 4 - (strlen($s) % 4);
    if ($p < 4) $s .= str_repeat('=', $p);
    return base64_decode($s);
}

/** Load or generate the VAPID EC P-256 key pair */
function getVapidKeys() {
    if (file_exists(VAPID_FILE)) {
        $j = json_decode(file_get_contents(VAPID_FILE), true);
        if (is_array($j) && !empty($j['public']) && !empty($j['privatePem'])) {
            $j['ok'] = true;
            return $j;
        }
    }
    if (!function_exists('openssl_pkey_new')) return ['ok' => false, 'err' => 'openssl'];
    $pk = @openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
    if ($pk === false) return ['ok' => false, 'err' => 'keygen'];
    $det = @openssl_pkey_get_details($pk);
    if (!$det || !isset($det['ec']['x']) || !isset($det['ec']['y'])) return ['ok' => false, 'err' => 'details'];
    $pem = '';
    @openssl_pkey_export($pk, $pem);
    if ($pem === '') return ['ok' => false, 'err' => 'export'];
    $pubRaw = "\x04" . $det['ec']['x'] . $det['ec']['y'];
    $origin = '';
    if (!empty($_SERVER['HTTP_HOST'])) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $origin = $scheme . '://' . $_SERVER['HTTP_HOST'];
    }
    $j = [
        'ok' => true,
        'public' => b64url_enc($pubRaw),
        'privatePem' => $pem,
        'subject' => $origin !== '' ? $origin : 'mailto:admin@example.com',
        'created' => time(),
    ];
    @file_put_contents(VAPID_FILE, json_encode($j, JSON_UNESCAPED_SLASHES));
    return $j;
}

/** Rebuild SPKI PEM from a 65-byte uncompressed EC point */
function ecPemFromRawPoint($raw) {
    $prefix = hex2bin('3059301306072a8648ce3d020106082a8648ce3d0301070342');
    if ($raw === false || strlen($raw) !== 65) return false;
    $pem = "-----BEGIN PUBLIC KEY-----\n" . base64_encode($prefix . $raw) . "\n-----END PUBLIC KEY-----\n";
    return @openssl_pkey_get_public($pem);
}

/** Convert DER ECDSA signature into JWS raw r||s (64 bytes) */
function ecDerToRaw64($sig) {
    if (substr($sig, 0, 1) !== "\x30") return false;
    $i = 2;
    if (ord($sig[1]) === 0x81) $i = 3;
    if (ord($sig[$i]) !== 0x02) return false;
    $rLen = ord($sig[$i + 1]);
    $r = substr($sig, $i + 2, $rLen);
    $j = $i + 2 + $rLen;
    if (ord($sig[$j]) !== 0x02) return false;
    $sLen = ord($sig[$j + 1]);
    $s = substr($sig, $j + 2, $sLen);
    $r = str_pad(ltrim($r, "\x00"), 32, "\x00", STR_PAD_LEFT);
    $s = str_pad(ltrim($s, "\x00"), 32, "\x00", STR_PAD_LEFT);
    return $r . $s;
}

function hkdfExpand($prk, $info, $len) {
    $t = ''; $out = ''; $i = 1;
    while (strlen($out) < $len) {
        $t = hash_hmac('sha256', $t . $info . chr($i), $prk, true);
        $out .= $t; $i++;
    }
    return substr($out, 0, $len);
}

/** Encrypt one payload for one subscription (RFC 8291 aes128gcm) */
function webpushEncrypt($payload, $p256dhB64, $authB64, $author) {
    $auth = b64url_dec($authB64);
    $userPub = b64url_dec($p256dhB64);
    if ($userPub === false || strlen($userPub) !== 65 || $auth === false) return false;
    if (!function_exists('openssl_pkey_derive')) return false;

    $eph = @openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
    if ($eph === false) return false;
    $det = @openssl_pkey_get_details($eph);
    $ephPub = "\x04" . $det['ec']['x'] . $det['ec']['y'];
    $ephPem = '';
    @openssl_pkey_export($eph, $ephPem);
    $ephPriv = @openssl_pkey_get_private($ephPem);
    $peer = ecPemFromRawPoint($userPub);
    if ($peer === false || $ephPriv === false) return false;
    $ecdh = @openssl_pkey_derive($peer, $ephPriv, 32);
    if ($ecdh === false || $ecdh === null) return false;

    $salt = random_bytes(16);
    $prkKey = hash_hmac('sha256', $ecdh, $auth, true);
    $keyInfo = "WebPush: info" . "\x00" . $userPub . $ephPub;
    $ikm = hkdfExpand($prkKey, $keyInfo, 32);
    $prk = hash_hmac('sha256', $ikm, $salt, true);
    $cek = hkdfExpand($prk, "Content-Encoding: aes128gcm" . "\x00", 16);
    $nonce = hkdfExpand($prk, "Content-Encoding: nonce" . "\x00", 12);

    $tag = '';
    $plain = "\x01" . $payload;
    $ct = openssl_encrypt($plain, 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag);
    if ($ct === false || strlen($tag) !== 16) return false;

    return $salt . pack('N', 4096) . chr(strlen($ephPub)) . $ephPub . $ct . $tag;
}

/** Send a real push to one subscription */
function webpushSend($sub, $payloadJson, $vapid) {
    if (empty($sub['endpoint']) || empty($sub['keys']['p256dh']) || empty($sub['keys']['auth'])) {
        return ['ok' => false, 'err' => 'bad-sub'];
    }
    $endpoint = $sub['endpoint'];
    $host = parse_url($endpoint, PHP_URL_HOST);
    $scheme = parse_url($endpoint, PHP_URL_SCHEME);
    $aud = $scheme . '://' . $host;

    $hdr = b64url_enc(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
    $pld = b64url_enc(json_encode(['aud' => $aud, 'exp' => time() + 3300, 'sub' => $vapid['subject']]));
    $signData = $hdr . '.' . $pld;
    $priv = @openssl_pkey_get_private($vapid['privatePem']);
    if ($priv === false) return ['ok' => false, 'err' => 'priv'];
    $der = '';
    if (!@openssl_sign($signData, $der, $priv, OPENSSL_ALGO_SHA256)) return ['ok' => false, 'err' => 'sign'];
    $raw64 = ecDerToRaw64($der);
    if ($raw64 === false) return ['ok' => false, 'err' => 'der'];
    $jwt = $signData . '.' . b64url_enc($raw64);

    $body = webpushEncrypt($payloadJson, $sub['keys']['p256dh'], $sub['keys']['auth'], $vapid['privatePem']);
    if ($body === false) return ['ok' => false, 'err' => 'encrypt'];

    $headers = [
        'Authorization: vapid t=' . $jwt . ', k=' . $vapid['public'],
        'TTL: 2419200',
        'Urgency: high',
        'Content-Encoding: aes128gcm',
        'Content-Type: application/octet-stream',
        'Content-Length: ' . strlen($body),
    ];

    if (!function_exists('curl_init')) return ['ok' => false, 'err' => 'curl'];
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    return ['ok' => in_array($code, [200, 201, 202]), 'code' => $code, 'err' => $err];
}

/** Store / list push subscriptions inside notifications.json */
function addPushSubscription($id, $endpoint, $p256dh, $auth) {
    $n = loadNotif();
    if (!isset($n['subs']) || !is_array($n['subs'])) $n['subs'] = [];
    $key = md5($endpoint);
    $n['subs'][$key] = [
        'device' => $id,
        'endpoint' => $endpoint,
        'keys' => ['p256dh' => $p256dh, 'auth' => $auth],
        'time' => time(),
    ];
    saveNotif($n);
    return true;
}
function removePushSubscription($endpoint) {
    $n = loadNotif();
    if (isset($n['subs'])) {
        unset($n['subs'][md5($endpoint)]);
        saveNotif($n);
    }
}
function listPushSubscriptions() {
    $n = loadNotif();
    return (isset($n['subs']) && is_array($n['subs'])) ? $n['subs'] : [];
}

// ─── Announcements ───
function getAnnouncements() {
    $m = loadMeta();
    return (isset($m['_announcements']) && is_array($m['_announcements'])) ? $m['_announcements'] : [];
}
function addAnnouncement($text, $type = 'info') {
    $m = loadMeta();
    if (!isset($m['_announcements']) || !is_array($m['_announcements'])) $m['_announcements'] = [];
    $m['_announcements'][] = ['id' => 'a' . time() . rand(100, 999), 'text' => $text, 'type' => $type, 'time' => time()];
    saveMeta($m);
}
function deleteAnnouncement($id) {
    $m = loadMeta();
    if (isset($m['_announcements'])) {
        $m['_announcements'] = array_values(array_filter($m['_announcements'], function($a) use ($id) {
            return (isset($a['id']) ? $a['id'] : '') !== $id;
        }));
    }
    saveMeta($m);
}
function clearAllAnnouncements() {
    $m = loadMeta();
    $m['_announcements'] = [];
    saveMeta($m);
}

// ─── Detection ───
function detectType($filename) {
    global $TYPE_KEYWORDS;
    $base = preg_replace('/\.pdf$/i', '', $filename);
    $low = mb_strtolower($base, 'UTF-8');
    foreach ($TYPE_KEYWORDS as $type => $words) {
        foreach ($words as $w) {
            if (mb_strpos($low, mb_strtolower($w, 'UTF-8')) !== false) return $type;
        }
    }
    return 'other';
}
function extractPersonName($filename) {
    global $TYPE_KEYWORDS;
    $base = preg_replace('/\.pdf$/i', '', $filename);
    $base = preg_replace('/\s*\(\s*\d+\s*\)\s*$/u', ' ', $base);
    $base = preg_replace('/\s*\[\s*\d+\s*\]\s*$/u', ' ', $base);
    $base = preg_replace('/[_\-]\d+\s*$/u', ' ', $base);
    $base = preg_replace('/\s*[-_]?\s*(copy|کپی)\s*\d*\s*$/iu', ' ', $base);
    $base = preg_replace('/\(\s*\d+\s*\)/u', ' ', $base);
    $base = preg_replace('/\s*[-_]?\s*(final|new|نهایی|جدید)\s*$/iu', ' ', $base);
    foreach ($TYPE_KEYWORDS as $type => $words) {
        foreach ($words as $w) {
            $base = preg_replace('/' . preg_quote($w, '/') . '/iu', ' ', $base);
        }
    }
    $base = str_replace(['_', '-', '.', '+', '،', ','], ' ', $base);
    $base = preg_replace('/[\(\)\[\]\{\}]/u', ' ', $base);
    $base = preg_replace('/(^|\s)\d+(\s|$)/u', ' ', $base);
    $base = preg_replace('/\s+/u', ' ', $base);
    $base = trim($base);
    return $base === '' ? 'نامشخص' : $base;
}

function makeSlug($name) {
    $s = trim($name);
    if (preg_match('/[\x{0600}-\x{06FF}]/u', $s)) {
        $table = [
            'آ'=>'a','ا'=>'a','أ'=>'a','إ'=>'e','ب'=>'b','پ'=>'p','ت'=>'t','ث'=>'s','ج'=>'j','چ'=>'ch','ح'=>'h','خ'=>'kh',
            'د'=>'d','ذ'=>'z','ر'=>'r','ز'=>'z','ژ'=>'zh','س'=>'s','ش'=>'sh','ص'=>'s','ض'=>'z','ط'=>'t','ظ'=>'z','ع'=>'a','غ'=>'gh',
            'ف'=>'f','ق'=>'gh','ک'=>'k','ك'=>'k','گ'=>'g','ل'=>'l','م'=>'m','ن'=>'n','و'=>'v','ه'=>'h','ی'=>'i','ي'=>'i','ى'=>'a',
            'ئ'=>'y','ؤ'=>'o','ء'=>''
        ];
        $s = strtr($s, $table);
    }
    $s = mb_strtolower($s, 'UTF-8');
    $s = preg_replace('/[\s\-\.]+/u', '_', $s);
    $s = preg_replace('/[^a-z0-9_]+/', '', $s);
    $s = preg_replace('/_+/', '_', $s);
    $s = trim($s, '_');
    return $s === '' ? 'user' : $s;
}

// ─── name.txt ───
function readDisplayName($mdir, $slug) {
    $f = UPLOAD_DIR . $mdir . '/' . $slug . '/name.txt';
    if (file_exists($f)) {
        $n = trim(file_get_contents($f));
        $n = preg_replace('/^\xEF\xBB\xBF/', '', $n);
        if ($n !== '') return $n;
    }
    return '';
}
function writeDisplayName($mdir, $slug, $name) {
    $dir = UPLOAD_DIR . $mdir . '/' . $slug;
    if (!is_dir($dir)) return false;
    $name = trim($name);
    $f = $dir . '/name.txt';
    if ($name === '') { if (file_exists($f)) @unlink($f); return true; }
    return file_put_contents($f, $name) !== false;
}
function prettifySlug($slug) {
    $s = str_replace('_', ' ', $slug);
    $s = preg_replace('/\s+/u', ' ', trim($s));
    return $s === '' ? $slug : ucwords($s);
}
function getPersonKey($name) {
    $k = mb_strtolower(trim($name), 'UTF-8');
    $k = preg_replace('/\s+/u', '', $k);
    return str_replace(['ي','ك','ﻻ'], ['ی','ک','لا'], $k);
}

// ─── Scan ───
function getAllData() {
    global $MONTH_NAMES;
    $meta = loadMeta();
    $months = [];

    foreach ($MONTH_NAMES as $num => $mdir) {
        $mpath = UPLOAD_DIR . $mdir . '/';
        if (!is_dir($mpath)) continue;

        foreach (scandir($mpath) as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $entryPath = $mpath . $entry;

            if (is_dir($entryPath)) {
                $slug = $entry;
                $displayName = readDisplayName($mdir, $slug);
                if ($displayName === '') $displayName = prettifySlug($slug);
                foreach (scandir($entryPath) as $file) {
                    if ($file === '.' || $file === '..') continue;
                    if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'pdf') continue;
                    $fpath = $entryPath . '/' . $file;
                    $key = $mdir . '/' . $slug . '/' . $file;
                    $info = isset($meta[$key]) ? $meta[$key] : [];
                    $ts = isset($info['timestamp']) ? (int)$info['timestamp'] : 0;
                    if ($ts <= 0 || $ts > time()) {
                        $fm = @filemtime($fpath);
                        $ts = ($fm > 0 && $fm <= time()) ? $fm : time();
                    }
                    if (!isset($months[$mdir])) $months[$mdir] = [];
                    if (!isset($months[$mdir][$slug])) $months[$mdir][$slug] = ['name' => $displayName, 'files' => []];
                    $months[$mdir][$slug]['files'][] = [
                        'name' => $file, 'key' => $key, 'size' => filesize($fpath),
                        'timestamp' => $ts,
                        'type' => isset($info['type']) ? $info['type'] : detectType($file),
                        'jalali' => getJalali($ts),
                    ];
                }
            }
        }
        // Flat legacy PDFs directly in month folder
        if (is_dir($mpath)) {
            foreach (scandir($mpath) as $entry) {
                if ($entry === '.' || $entry === '..' || is_dir($mpath . $entry)) continue;
                if (strtolower(pathinfo($entry, PATHINFO_EXTENSION)) !== 'pdf') continue;
                $entryPath = $mpath . $entry;
                $person = extractPersonName($entry);
                $slug = makeSlug($person);
                $key = $mdir . '/' . $entry;
                $info = isset($meta[$key]) ? $meta[$key] : [];
                $ts = isset($info['timestamp']) ? (int)$info['timestamp'] : 0;
                if ($ts <= 0 || $ts > time()) {
                    $fm = @filemtime($entryPath);
                    $ts = ($fm > 0 && $fm <= time()) ? $fm : time();
                }
                if (!isset($months[$mdir])) $months[$mdir] = [];
                if (!isset($months[$mdir][$slug])) $months[$mdir][$slug] = ['name' => prettifySlug($slug), 'files' => []];
                $months[$mdir][$slug]['files'][] = [
                    'name' => $entry, 'key' => $key, 'size' => filesize($entryPath),
                    'timestamp' => $ts,
                    'type' => isset($info['type']) ? $info['type'] : detectType($entry),
                    'jalali' => getJalali($ts),
                ];
            }
        }
    }

    foreach ($months as $md => &$persons) {
        foreach ($persons as $sl => &$p) {
            usort($p['files'], function($a, $b) { return $b['timestamp'] - $a['timestamp']; });
            $p['last'] = isset($p['files'][0]) ? $p['files'][0]['timestamp'] : 0;
        }
        unset($p);
        uasort($persons, function($a, $b) { return $b['last'] - $a['last']; });
    }
    unset($persons);
    return $months;
}

function lastUploadTime($mdir, $slug) {
    $meta = loadMeta(); $dir = UPLOAD_DIR . $mdir . '/' . $slug;
    if (!is_dir($dir)) return 0;
    $now = time(); $last = 0;
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') continue;
        if (strtolower(pathinfo($f, PATHINFO_EXTENSION)) !== 'pdf') continue;
        $key = $mdir . '/' . $slug . '/' . $f;
        $t = isset($meta[$key]['timestamp']) ? (int)$meta[$key]['timestamp'] : 0;
        if ($t <= 0 || $t > $now) {
            $fm = @filemtime($dir . '/' . $f);
            $t = ($fm > 0 && $fm <= $now) ? $fm : 0;
        }
        if ($t > $last) $last = $t;
    }
    return $last;
}
function allLastUploads($data) {
    $out = [];
    foreach ($data as $m => $persons) {
        foreach ($persons as $s => $p) {
            $max = 0;
            foreach ($p['files'] as $f) {
                $t = (int)(isset($f['timestamp']) ? $f['timestamp'] : 0);
                if ($t > $max) $max = $t;
            }
            if ($max > 0) $out[$m][$s] = $max;
        }
    }
    return $out;
}

// ─── Renewal (Jalali calendar) ───
function getRenewalReminders($mode = 'due', $lastUploads = []) {
    global $MONTH_NAMES_FA, $MONTH_NAMES;
    $p = loadPersons(); $now = time(); $today = dayStart($now);

    foreach ($MONTH_NAMES as $num => $mdir) {
        $mpath = UPLOAD_DIR . $mdir . '/';
        if (!is_dir($mpath)) continue;
        foreach (scandir($mpath) as $e) {
            if ($e === '.' || $e === '..' || !is_dir($mpath . $e)) continue;
            $k = $mdir . '/' . $e;
            if (!isset($p[$k])) $p[$k] = ['month' => $mdir, 'slug' => $e, 'name' => '', 'created' => 0, 'renewed' => 0, 'dismissed' => 0];
        }
    }

    $out = [];
    foreach ($p as $k => $rec) {
        $mdir = isset($rec['month']) ? $rec['month'] : '';
        $slug = isset($rec['slug']) ? $rec['slug'] : '';
        if ($mdir === '' || $slug === '') continue;
        if (!is_dir(UPLOAD_DIR . $mdir . '/' . $slug)) continue;

        $base = isset($lastUploads[$mdir][$slug]) ? (int)$lastUploads[$mdir][$slug] : lastUploadTime($mdir, $slug);
        $renewed = isset($rec['renewed']) ? (int)$rec['renewed'] : 0;
        if ($renewed > 0 && $renewed > $base) $base = $renewed;
        if ($base <= 0) $base = isset($rec['created']) ? (int)$rec['created'] : 0;
        if ($base <= 0 || $base > $now) continue;

        $dueTs = jalaliPlusOneMonth($base);
        if ($dueTs === null) continue;

        $dayOff = (int)round(($today - $dueTs) / 86400);
        $isActive = ($dayOff >= 0 && $dayOff < REMIND_WINDOW);
        $isExpired = ($dayOff >= REMIND_WINDOW);
        $dayInWin = $isActive ? ($dayOff + 1) : 0;
        $daysToDue = max(0, -$dayOff);

        $dis = isset($rec['dismissed']) ? (int)$rec['dismissed'] : 0;
        $dismissedNow = ($dis > 0 && $dis >= $dueTs);

        if ($mode === 'due') {
            if (!$isActive || $dismissedNow) continue;
        }

        $name = readDisplayName($mdir, $slug);
        if ($name === '') $name = isset($rec['name']) ? $rec['name'] : '';
        if ($name === '') $name = prettifySlug($slug);
        $mnum = array_search($mdir, $MONTH_NAMES);

        $out[] = [
            'key' => $k, 'month' => $mdir, 'slug' => $slug, 'name' => $name,
            'monthFa' => $mnum ? $MONTH_NAMES_FA[$mnum] : $mdir,
            'base' => $base, 'dueTs' => $dueTs, 'dayOff' => $dayOff,
            'days' => max(0, (int)floor(($today - dayStart($base)) / 86400)),
            'isActive' => $isActive, 'isExpired' => $isExpired,
            'dayInWin' => $dayInWin, 'daysToDue' => $daysToDue,
            'jalali' => getJalali($base), 'dueJalali' => getJalali($dueTs),
        ];
    }
    usort($out, function($a, $b) {
        $ra = $a['isActive'] ? 0 : ($a['isExpired'] ? 1 : 2);
        $rb = $b['isActive'] ? 0 : ($b['isExpired'] ? 1 : 2);
        if ($ra !== $rb) return $ra - $rb;
        return $b['dueTs'] - $a['dueTs'];
    });
    return $out;
}

// ─── Flash ───
$message = ''; $messageType = '';
if (isset($_SESSION['flash'])) {
    $message = $_SESSION['flash']['msg'];
    $messageType = $_SESSION['flash']['type'];
    unset($_SESSION['flash']);
}

// ─── Login ───
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    if ((isset($_POST['user']) ? $_POST['user'] : '') === ADMIN_USER && (isset($_POST['pass']) ? $_POST['pass'] : '') === ADMIN_PASS) {
        $_SESSION['admin'] = true;
        $_SESSION['flash'] = ['msg' => 'ورود موفقیت‌آمیز!', 'type' => 'success'];
    } else {
        $_SESSION['flash'] = ['msg' => 'نام کاربری یا رمز عبور اشتباه است', 'type' => 'error'];
    }
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
}
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy(); header('Location: ' . $_SERVER['PHP_SELF']); exit;
}

// ─── Upload ───
if (isset($_POST['action']) && $_POST['action'] === 'upload' && isAdmin()) {
    $selMonth = isset($_POST['month']) ? (int)$_POST['month'] : 0;
    $perFileTypes = isset($_POST['file_types']) && is_array($_POST['file_types']) ? $_POST['file_types'] : [];
    if (isset($_FILES['files'])) {
        $meta = loadMeta();
        if (!isset($meta['_persons'])) $meta['_persons'] = [];
        $batchId = 'b' . time();
        $count = 0;
        $files = $_FILES['files'];
        $n = is_array($files['name']) ? count($files['name']) : 1;

        for ($i = 0; $i < $n; $i++) {
            $orig = is_array($files['name']) ? $files['name'][$i] : $files['name'];
            $tmp  = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
            $err  = is_array($files['error']) ? $files['error'][$i] : $files['error'];
            if ($err !== UPLOAD_ERR_OK) continue;
            if (strtolower(pathinfo($orig, PATHINFO_EXTENSION)) !== 'pdf') continue;

            $orig = basename($orig);
            $type = detectType($orig);
            if (isset($perFileTypes[$i]) && in_array($perFileTypes[$i], array_keys($TYPE_FA))) {
                $type = $perFileTypes[$i];
            }

            $person = extractPersonName($orig);
            $pKey = getPersonKey($person);
            $monthNum = ($selMonth >= 1 && $selMonth <= 12) ? $selMonth : getJalali()['month'];
            $mdir = $MONTH_NAMES[$monthNum];

            $slug = null;
            $wantSlug = makeSlug($person);
            $mpath = UPLOAD_DIR . $mdir . '/';
            if (is_dir($mpath)) {
                foreach (scandir($mpath) as $e) {
                    if ($e === '.' || $e === '..' || !is_dir($mpath . $e)) continue;
                    if ($e === $wantSlug) { $slug = $e; break; }
                    $raw = isset($meta['_persons'][$mdir . '/' . $e]['raw']) ? $meta['_persons'][$mdir . '/' . $e]['raw'] : '';
                    if ($raw !== '' && getPersonKey($raw) === $pKey) { $slug = $e; break; }
                }
            }
            if ($slug === null) $slug = $wantSlug;

            $targetDir = UPLOAD_DIR . $mdir . '/' . $slug . '/';
            if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

            $fname = $orig;
            if (file_exists($targetDir . $fname)) {
                $b = pathinfo($fname, PATHINFO_FILENAME);
                $c = 1;
                while (file_exists($targetDir . $b . '_' . $c . '.pdf')) $c++;
                $fname = $b . '_' . $c . '.pdf';
            }

            if (move_uploaded_file($tmp, $targetDir . $fname)) {
                $key = $mdir . '/' . $slug . '/' . $fname;
                $meta[$key] = ['timestamp' => time(), 'batchId' => $batchId, 'type' => $type, 'monthNum' => $monthNum];
                $meta['_persons'][$mdir . '/' . $slug] = ['raw' => $person];
                if (!file_exists($targetDir . 'name.txt')) @file_put_contents($targetDir . 'name.txt', $person);
                registerPerson($mdir, $slug, $person);
                $count++;
            }
        }
        if ($count > 0) {
            $meta['_lastBatch'] = $batchId;
            saveMeta($meta);
            $_SESSION['flash'] = ['msg' => $count . ' فایل آپلود و دسته‌بندی شد!', 'type' => 'success'];
        } else {
            $_SESSION['flash'] = ['msg' => 'خطا در آپلود!', 'type' => 'error'];
        }
        header('Location: ' . $_SERVER['PHP_SELF']); exit;
    }
}

// ─── Announcements ───
if (isset($_POST['action']) && $_POST['action'] === 'add_announce' && isAdmin()) {
    $t = trim(isset($_POST['announce_text']) ? $_POST['announce_text'] : '');
    if ($t !== '') {
        addAnnouncement($t, isset($_POST['announce_type']) ? $_POST['announce_type'] : 'info');
        $_SESSION['flash'] = ['msg' => 'اطلاعیه افزوده شد!', 'type' => 'success'];
    }
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
}
if (isset($_GET['action']) && $_GET['action'] === 'delannounce' && isAdmin()) {
    deleteAnnouncement(isset($_GET['id']) ? $_GET['id'] : '');
    $_SESSION['flash'] = ['msg' => 'اطلاعیه حذف شد!', 'type' => 'success'];
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
}
if (isset($_GET['action']) && $_GET['action'] === 'clearannounces' && isAdmin()) {
    clearAllAnnouncements();
    $_SESSION['flash'] = ['msg' => 'همه اطلاعیه‌ها پاک شدند!', 'type' => 'success'];
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
}

// ─── Renewal actions ───
if (isset($_GET['action']) && $_GET['action'] === 'renew' && isAdmin() && isset($_GET['m']) && isset($_GET['u'])) {
    $p = loadPersons();
    $k = basename($_GET['m']) . '/' . basename($_GET['u']);
    if (isset($p[$k])) { $p[$k]['renewed'] = time(); $p[$k]['dismissed'] = 0; savePersons($p); }
    $_SESSION['flash'] = ['msg' => 'تمدید ثبت شد!', 'type' => 'success'];
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
}
if (isset($_GET['action']) && $_GET['action'] === 'dismiss' && isAdmin() && isset($_GET['m']) && isset($_GET['u'])) {
    $p = loadPersons();
    $k = basename($_GET['m']) . '/' . basename($_GET['u']);
    if (isset($p[$k])) { $p[$k]['dismissed'] = time(); savePersons($p); }
    $_SESSION['flash'] = ['msg' => 'یادآوری بسته شد', 'type' => 'success'];
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
}

// ─── Rename person ───
if (isset($_POST['action']) && $_POST['action'] === 'rename_person' && isAdmin()) {
    $mdir = basename(isset($_POST['m']) ? $_POST['m'] : '');
    $slug = basename(isset($_POST['u']) ? $_POST['u'] : '');
    $newName = trim(isset($_POST['new_name']) ? $_POST['new_name'] : '');
    $rawSlug = trim(isset($_POST['new_slug']) ? $_POST['new_slug'] : '');
    $oldDir = UPLOAD_DIR . $mdir . '/' . $slug;

    if (!is_dir($oldDir)) {
        $_SESSION['flash'] = ['msg' => 'پوشه پیدا نشد!', 'type' => 'error'];
        header('Location: ' . $_SERVER['PHP_SELF'] . '?m=' . urlencode($mdir)); exit;
    }
    if ($newName === '') {
        $_SESSION['flash'] = ['msg' => 'نام نمی‌تواند خالی باشد!', 'type' => 'error'];
        header('Location: ' . $_SERVER['PHP_SELF'] . '?m=' . urlencode($mdir) . '&u=' . urlencode($slug)); exit;
    }

    $meta = loadMeta(); $reg = loadPersons();
    $newSlug = ($rawSlug !== '') ? makeSlug($rawSlug) : $slug;
    $newDir = UPLOAD_DIR . $mdir . '/' . $newSlug;
    $moved = false;

    if ($newSlug !== '' && $newSlug !== $slug) {
        if (is_dir($newDir)) {
            foreach (scandir($oldDir) as $f) {
                if ($f === '.' || $f === '..') continue;
                if (strtolower($f) === 'name.txt') { @unlink($oldDir . '/' . $f); continue; }
                $dest = $f;
                if (file_exists($newDir . '/' . $dest)) {
                    $b = pathinfo($f, PATHINFO_FILENAME);
                    $x = pathinfo($f, PATHINFO_EXTENSION);
                    $c = 1;
                    while (file_exists($newDir . '/' . $b . '_' . $c . '.' . $x)) $c++;
                    $dest = $b . '_' . $c . '.' . $x;
                }
                @rename($oldDir . '/' . $f, $newDir . '/' . $dest);
                $oK = $mdir . '/' . $slug . '/' . $f;
                $nK = $mdir . '/' . $newSlug . '/' . $dest;
                if (isset($meta[$oK])) { $meta[$nK] = $meta[$oK]; unset($meta[$oK]); }
            }
            @rmdir($oldDir); $moved = true;
        } else {
            if (@rename($oldDir, $newDir)) {
                foreach (array_keys($meta) as $k2) {
                    if (strpos($k2, $mdir . '/' . $slug . '/') === 0) {
                        $nK = $mdir . '/' . $newSlug . '/' . basename($k2);
                        $meta[$nK] = $meta[$k2]; unset($meta[$k2]);
                    }
                }
                $moved = true;
            } else { $newSlug = $slug; }
        }
    } else { $newSlug = $slug; }

    $oldPKey = $mdir . '/' . $slug; $newPKey = $mdir . '/' . $newSlug;
    if ($moved) {
        $prevRaw = isset($meta['_persons'][$oldPKey]['raw']) ? $meta['_persons'][$oldPKey]['raw'] : $slug;
        unset($meta['_persons'][$oldPKey]);
        $meta['_persons'][$newPKey] = ['raw' => $prevRaw];
    } else {
        if (!isset($meta['_persons'][$newPKey])) $meta['_persons'][$newPKey] = ['raw' => $slug];
    }
    saveMeta($meta);

    if ($moved && isset($reg[$oldPKey])) {
        $reg[$newPKey] = $reg[$oldPKey];
        $reg[$newPKey]['slug'] = $newSlug;
        unset($reg[$oldPKey]);
    }
    if (!isset($reg[$newPKey])) {
        $reg[$newPKey] = ['month'=>$mdir,'slug'=>$newSlug,'name'=>$newName,'created'=>time(),'renewed'=>0,'dismissed'=>0];
    }
    $reg[$newPKey]['name'] = $newName;
    savePersons($reg);

    writeDisplayName($mdir, $newSlug, $newName);
    $_SESSION['flash'] = ['msg' => 'تغییرات ذخیره شد!', 'type' => 'success'];
    header('Location: ' . $_SERVER['PHP_SELF'] . '?m=' . urlencode($mdir) . '&u=' . urlencode($newSlug)); exit;
}

// ─── Rename file ───
if (isset($_POST['action']) && $_POST['action'] === 'rename_file' && isAdmin()) {
    $key = isset($_POST['key']) ? $_POST['key'] : '';
    $newName = trim(isset($_POST['new_filename']) ? $_POST['new_filename'] : '');
    $path = UPLOAD_DIR . $key;
    $parts = explode('/', $key);
    if ($newName !== '' && file_exists($path)) {
        if (!preg_match('/\.pdf$/i', $newName)) $newName .= '.pdf';
        $newName = str_replace(['/', '\\'], '', $newName);
        $dir = dirname($path);
        if (!file_exists($dir . '/' . $newName)) {
            rename($path, $dir . '/' . $newName);
            $meta = loadMeta();
            $newKey = implode('/', array_slice($parts, 0, -1)) . '/' . $newName;
            if (isset($meta[$key])) {
                $meta[$newKey] = $meta[$key];
                $meta[$newKey]['type'] = detectType($newName);
                unset($meta[$key]);
            }
            saveMeta($meta);
            $_SESSION['flash'] = ['msg' => 'نام فایل ویرایش شد!', 'type' => 'success'];
        } else {
            $_SESSION['flash'] = ['msg' => 'فایل با این نام وجود دارد!', 'type' => 'error'];
        }
    }
    $back = count($parts) >= 3 ? '?m=' . urlencode($parts[0]) . '&u=' . urlencode($parts[1]) : '';
    header('Location: ' . $_SERVER['PHP_SELF'] . $back); exit;
}

// ─── Set file type ───
if (isset($_GET['action']) && $_GET['action'] === 'settype' && isAdmin() && isset($_GET['key']) && isset($_GET['type'])) {
    $key = $_GET['key']; $type = $_GET['type'];
    if (in_array($type, array_keys($TYPE_FA)) && file_exists(UPLOAD_DIR . $key)) {
        $meta = loadMeta();
        if (!isset($meta[$key])) $meta[$key] = ['timestamp' => filemtime(UPLOAD_DIR . $key)];
        $meta[$key]['type'] = $type;
        saveMeta($meta);
        $_SESSION['flash'] = ['msg' => 'نوع فایل تغییر کرد!', 'type' => 'success'];
    }
    $parts = explode('/', $key);
    $back = count($parts) >= 3 ? '?m=' . urlencode($parts[0]) . '&u=' . urlencode($parts[1]) : '';
    header('Location: ' . $_SERVER['PHP_SELF'] . $back); exit;
}

// ─── Move person ───
if (isset($_POST['action']) && $_POST['action'] === 'move_person' && isAdmin()) {
    $mdir = basename(isset($_POST['m']) ? $_POST['m'] : '');
    $slug = basename(isset($_POST['u']) ? $_POST['u'] : '');
    $toMonth = (int)(isset($_POST['to_month']) ? $_POST['to_month'] : 0);
    if ($toMonth >= 1 && $toMonth <= 12) {
        $newMdir = $MONTH_NAMES[$toMonth];
        $oldDir = UPLOAD_DIR . $mdir . '/' . $slug;
        $newDir = UPLOAD_DIR . $newMdir . '/' . $slug;
        if ($newMdir !== $mdir && is_dir($oldDir)) {
            if (!is_dir(UPLOAD_DIR . $newMdir)) mkdir(UPLOAD_DIR . $newMdir, 0755, true);
            $meta = loadMeta(); $reg = loadPersons();
            if (!is_dir($newDir)) mkdir($newDir, 0755, true);
            $movedName = readDisplayName($mdir, $slug);
            foreach (scandir($oldDir) as $f) {
                if ($f === '.' || $f === '..') continue;
                if (strtolower($f) === 'name.txt') { @unlink($oldDir . '/' . $f); continue; }
                $dest = $f;
                if (file_exists($newDir . '/' . $dest)) {
                    $b = pathinfo($f, PATHINFO_FILENAME);
                    $x = pathinfo($f, PATHINFO_EXTENSION);
                    $c = 1;
                    while (file_exists($newDir . '/' . $b . '_' . $c . '.' . $x)) $c++;
                    $dest = $b . '_' . $c . '.' . $x;
                }
                @rename($oldDir . '/' . $f, $newDir . '/' . $dest);
                $ok = $mdir . '/' . $slug . '/' . $f;
                $nk = $newMdir . '/' . $slug . '/' . $dest;
                if (isset($meta[$ok])) { $meta[$nk] = $meta[$ok]; $meta[$nk]['monthNum'] = $toMonth; unset($meta[$ok]); }
            }
            @rmdir($oldDir);
            if ($movedName !== '' && !file_exists($newDir . '/name.txt')) @file_put_contents($newDir . '/name.txt', $movedName);
            $praw = isset($meta['_persons'][$mdir . '/' . $slug]['raw']) ? $meta['_persons'][$mdir . '/' . $slug]['raw'] : $slug;
            $meta['_persons'][$newMdir . '/' . $slug] = ['raw' => $praw];
            unset($meta['_persons'][$mdir . '/' . $slug]);
            $ok2 = $mdir . '/' . $slug; $nk2 = $newMdir . '/' . $slug;
            if (isset($reg[$ok2])) { $reg[$nk2] = $reg[$ok2]; $reg[$nk2]['month'] = $newMdir; unset($reg[$ok2]); }
            if (!isset($reg[$nk2])) $reg[$nk2] = ['month' => $newMdir, 'slug' => $slug, 'name' => $movedName, 'created' => time(), 'renewed' => 0, 'dismissed' => 0];
            if ($movedName !== '') $reg[$nk2]['name'] = $movedName;
            saveMeta($meta); savePersons($reg);
            $_SESSION['flash'] = ['msg' => 'کاربر منتقل شد!', 'type' => 'success'];
            header('Location: ' . $_SERVER['PHP_SELF'] . '?m=' . urlencode($newMdir) . '&u=' . urlencode($slug)); exit;
        }
    }
    header('Location: ' . $_SERVER['PHP_SELF'] . '?m=' . urlencode($mdir)); exit;
}

// ─── Delete file ───
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isAdmin() && isset($_GET['key'])) {
    $key = $_GET['key']; $path = UPLOAD_DIR . $key;
    if (file_exists($path)) {
        $dir = dirname($path);
        @unlink($path);
        $meta = loadMeta(); unset($meta[$key]); saveMeta($meta);
        if (is_dir($dir)) {
            $rest = array_diff(scandir($dir), ['.','..']);
            $pdfs = array_filter($rest, function($x) { return strtolower(pathinfo($x, PATHINFO_EXTENSION)) === 'pdf'; });
            if (count($pdfs) === 0) {
                foreach ($rest as $r) @unlink($dir . '/' . $r);
                @rmdir($dir);
                $parts2 = explode('/', $key);
                unregisterPerson($parts2[0], $parts2[1]);
            }
        }
        $_SESSION['flash'] = ['msg' => 'فایل حذف شد!', 'type' => 'success'];
    }
    $parts = explode('/', $key);
    $back = count($parts) >= 3 ? '?m=' . urlencode($parts[0]) . '&u=' . urlencode($parts[1]) : '';
    header('Location: ' . $_SERVER['PHP_SELF'] . $back); exit;
}

// ─── Delete person ───
if (isset($_GET['action']) && $_GET['action'] === 'delperson' && isAdmin() && isset($_GET['m']) && isset($_GET['u'])) {
    $mdir = basename($_GET['m']); $slug = basename($_GET['u']);
    $dir = UPLOAD_DIR . $mdir . '/' . $slug;
    if (is_dir($dir)) {
        $meta = loadMeta();
        foreach (scandir($dir) as $f) {
            if ($f === '.' || $f === '..') continue;
            @unlink($dir . '/' . $f);
            unset($meta[$mdir . '/' . $slug . '/' . $f]);
        }
        @rmdir($dir);
        unset($meta['_persons'][$mdir . '/' . $slug]);
        unregisterPerson($mdir, $slug);
        saveMeta($meta);
        $_SESSION['flash'] = ['msg' => 'پوشه کاربر حذف شد!', 'type' => 'success'];
    }
    header('Location: ' . $_SERVER['PHP_SELF'] . '?m=' . urlencode($mdir)); exit;
}

// ─── Notification endpoints (JSON) ───
if (isset($_GET['action']) && $_GET['action'] === 'notif_subscribe') {
    header('Content-Type: application/json; charset=utf-8');
    $id = isset($_POST['id']) ? $_POST['id'] : (isset($_GET['id']) ? $_GET['id'] : '');
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $ok = registerDevice($id, $ua);
    echo json_encode(['ok' => $ok]);
    exit;
}
if (isset($_GET['action']) && $_GET['action'] === 'notif_unsubscribe') {
    header('Content-Type: application/json; charset=utf-8');
    $id = isset($_POST['id']) ? $_POST['id'] : (isset($_GET['id']) ? $_GET['id'] : '');
    removeDevice(preg_replace('/[^a-zA-Z0-9_\-]/', '', $id));
    if (isset($_POST['endpoint']) && $_POST['endpoint'] !== '') {
        removePushSubscription($_POST['endpoint']);
    }
    echo json_encode(['ok' => true]);
    exit;
}
if (isset($_GET['action']) && $_GET['action'] === 'notif_poll') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    $since = isset($_GET['since']) ? (int)$_GET['since'] : 0;
    $n = loadNotif();
    $out = [];
    foreach ($n['messages'] as $m) {
        if ((int)$m['time'] > $since) $out[] = $m;
    }
    echo json_encode(['now' => time(), 'items' => $out]);
    exit;
}

// ─── Web Push endpoints ───
if (isset($_GET['action']) && $_GET['action'] === 'push_pub') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    $v = getVapidKeys();
    echo json_encode([
        'ok' => !empty($v['ok']),
        'public' => !empty($v['public']) ? $v['public'] : '',
        'err' => isset($v['err']) ? $v['err'] : '',
    ]);
    exit;
}
if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'push_sub') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    $id = isset($_POST['id']) ? $_POST['id'] : '';
    $endpoint = isset($_POST['endpoint']) ? $_POST['endpoint'] : '';
    $p256dh = isset($_POST['p256dh']) ? $_POST['p256dh'] : '';
    $auth = isset($_POST['auth']) ? $_POST['auth'] : '';
    $ok = ($endpoint !== '' && $p256dh !== '' && $auth !== '');
    $devs = 0;
    if ($ok) {
        $ok = addPushSubscription($id, $endpoint, $p256dh, $auth);
        $devs = count(listPushSubscriptions());
    }
    echo json_encode(['ok' => $ok, 'subs' => $devs]);
    exit;
}
if (isset($_POST['action']) && $_POST['action'] === 'send_notif' && isAdmin()) {
    $t = trim(isset($_POST['n_title']) ? $_POST['n_title'] : '');
    $b = trim(isset($_POST['n_body']) ? $_POST['n_body'] : '');
    $u = trim(isset($_POST['n_url']) ? $_POST['n_url'] : '');
    if ($t !== '' || $b !== '') {
        pushMessage($t !== '' ? $t : 'مخزن PDF', $b, $u);

        // Dispatch real Web Push to every stored subscription
        $subs = listPushSubscriptions();
        $sent = 0; $failed = 0; $vErr = '';
        if (!empty($subs)) {
            $vapid = getVapidKeys();
            if (!empty($vapid['ok'])) {
                $payload = json_encode([
                    'title' => $t !== '' ? $t : 'مخزن PDF',
                    'body' => $b,
                    'url' => $u !== '' ? $u : '/index.php',
                    'icon' => '/icon.png',
                ], JSON_UNESCAPED_UNICODE);
                foreach ($subs as $s) {
                    $r = webpushSend($s, $payload, $vapid);
                    if (!empty($r['ok'])) $sent++;
                    else { $failed++; $vErr = isset($r['err']) ? (string)$r['err'] : ''; }
                }
            } else {
                $vErr = 'vapid';
            }
        }

        if ($sent > 0) {
            $extra = $failed > 0 ? " ({$failed} ناموفق)" : '';
            $_SESSION['flash'] = ['msg' => "اعلان به {$sent} دستگاه ارسال شد!{$extra}", 'type' => 'success'];
        } elseif (!empty($subs)) {
            $_SESSION['flash'] = ['msg' => 'اعلان ثبت شد (ارسال push ناموفق' . ($vErr ? ': ' . $vErr : '') . ')', 'type' => 'error'];
        } else {
            $_SESSION['flash'] = ['msg' => 'اعلان ثبت شد — فعلاً هیچ دستگاه پوش ثبت نکرده است', 'type' => 'success'];
        }
    } else {
        $_SESSION['flash'] = ['msg' => 'عنوان یا متن اعلان را وارد کنید', 'type' => 'error'];
    }
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
}
if (isset($_GET['action']) && $_GET['action'] === 'del_notif' && isAdmin()) {
    deletePushMessage(isset($_GET['id']) ? $_GET['id'] : '');
    $_SESSION['flash'] = ['msg' => 'اعلان حذف شد', 'type' => 'success'];
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
}
if (isset($_GET['action']) && $_GET['action'] === 'clear_notifs' && isAdmin()) {
    clearPushMessages();
    $_SESSION['flash'] = ['msg' => 'تاریخچه اعلان‌ها پاک شد', 'type' => 'success'];
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
}

// ─── Download / View ───
if (isset($_GET['action']) && in_array($_GET['action'], ['download','view']) && isset($_GET['key'])) {
    $key = $_GET['key']; $path = UPLOAD_DIR . $key;
    if (file_exists($path)) {
        $fn = basename($key);
        header('Content-Type: application/pdf');
        header('Content-Disposition: ' . ($_GET['action'] === 'view' ? 'inline' : 'attachment') . '; filename="' . $fn . '"');
        header('Content-Length: ' . filesize($path));
        header('Accept-Ranges: bytes');
        header('Access-Control-Allow-Origin: *');
        readfile($path); exit;
    }
}

header('Content-Type: text/html; charset=utf-8');

// ─── Data ───
$data = getAllData();
$__reg = loadPersons(); $__regChanged = false;
foreach ($data as $__m => $__persons) {
    foreach ($__persons as $__s => $__p) {
        $__k = $__m . '/' . $__s;
        if (!isset($__reg[$__k])) {
            $__oldest = PHP_INT_MAX;
            foreach ($__p['files'] as $__f) {
                $__t = (int)(isset($__f['timestamp']) ? $__f['timestamp'] : 0);
                if ($__t > 0 && $__t < $__oldest) $__oldest = $__t;
            }
            if ($__oldest === PHP_INT_MAX) $__oldest = time();
            $__pname = isset($__reg[$__k]['name']) ? $__reg[$__k]['name'] : '';
            if ($__pname === '') $__pname = readDisplayName($__m, $__s);
            if ($__pname === '') $__pname = isset($__p['name']) ? $__p['name'] : prettifySlug($__s);
            $__reg[$__k] = ['month'=>$__m,'slug'=>$__s,'name'=>$__pname,'created'=>$__oldest,'renewed'=>0,'dismissed'=>0];
            $__regChanged = true;
        }
    }
}
if ($__regChanged) savePersons($__reg);

$lastUploads = allLastUploads($data);
$nowJ = getJalali();
$todayKey = $nowJ['year'] . '-' . pad($nowJ['month']) . '-' . pad($nowJ['day']);

$reminders = getRenewalReminders('due', $lastUploads);
$allRenewals = getRenewalReminders('all', $lastUploads);
$dueCount = count($reminders);
$announcements = getAnnouncements();
$notifStore = loadNotif();
$notifDevices = count($notifStore['devices']);
$notifHistory = array_reverse($notifStore['messages']);
$meta = loadMeta();
$lastBatch = isset($meta['_lastBatch']) ? $meta['_lastBatch'] : '';
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$activeTab = isset($_GET['m']) ? basename($_GET['m']) : '';
$activeUser = isset($_GET['u']) ? basename($_GET['u']) : '';
if ($activeTab === '' || !in_array($activeTab, $MONTH_NAMES)) $activeTab = $MONTH_NAMES[$nowJ['month']];

$monthPersons = isset($data[$activeTab]) ? $data[$activeTab] : [];
if ($search !== '') {
    $filtered = [];
    foreach ($monthPersons as $slug => $p) {
        $nameMatch = mb_stripos($p['name'], $search) !== false;
        $matchFiles = array_values(array_filter($p['files'], function($f) use ($search) {
            return mb_stripos($f['name'], $search) !== false;
        }));
        if ($nameMatch || count($matchFiles)) {
            $filtered[$slug] = ['name' => $p['name'], 'files' => $nameMatch ? $p['files'] : $matchFiles, 'last' => isset($p['last']) ? $p['last'] : 0];
        }
    }
    $monthPersons = $filtered;
}

function isToday($j) { global $todayKey; return ($j['year'] . '-' . pad($j['month']) . '-' . pad($j['day'])) === $todayKey; }

$currentPerson = ($activeUser !== '' && isset($monthPersons[$activeUser])) ? $monthPersons[$activeUser] : null;
$activeMonthNum = array_search($activeTab, $MONTH_NAMES);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<title>مخزن PDF</title>
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="مخزن PDF">
<meta name="mobile-web-app-capable" content="yes">
<meta name="theme-color" content="#07060f" id="metaTheme">
<script>
(function(){try{var t=localStorage.getItem('theme');if(!t){t=window.matchMedia&&window.matchMedia('(prefers-color-scheme: light)').matches?'light':'dark';}document.documentElement.setAttribute('data-theme',t);}catch(e){document.documentElement.setAttribute('data-theme','dark');}})();
</script>
<link rel="apple-touch-icon" id="pwaIconA" sizes="180x180" href="icon.png">
<link rel="icon" id="pwaIconB" type="image/png" href="icon.png">
<script>
(function(){try{var s=180,c=document.createElement("canvas");c.width=s;c.height=s;var x=c.getContext("2d");
var g=x.createLinearGradient(0,0,s,s);g.addColorStop(0,"#7c6ef0");g.addColorStop(1,"#5b4dc7");x.fillStyle=g;x.fillRect(0,0,s,s);
x.fillStyle="rgba(255,255,255,0.93)";x.beginPath();x.moveTo(54,30);x.lineTo(96,30);x.lineTo(126,64);x.lineTo(126,142);
x.quadraticCurveTo(126,150,118,150);x.lineTo(62,150);x.quadraticCurveTo(54,150,54,142);x.closePath();x.fill();
x.fillStyle="rgba(91,77,199,0.3)";x.beginPath();x.moveTo(96,30);x.lineTo(96,64);x.lineTo(126,64);x.closePath();x.fill();
x.fillStyle="rgba(91,77,199,0.25)";x.fillRect(66,80,44,5);x.fillRect(66,94,36,5);x.fillRect(66,108,40,5);
x.fillStyle="rgba(91,77,199,0.6)";x.font="bold 22px sans-serif";x.textAlign="center";x.fillText("PDF",90,138);
var d=c.toDataURL("image/png");document.getElementById("pwaIconA").href=d;document.getElementById("pwaIconB").href=d;}catch(e){}})();
</script>
<link rel="manifest" href="manifest.json">
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
:root{
    --bg:#07060f;--bg2:#0e0d1b;
    --glass:rgba(255,255,255,0.035);--gb:rgba(255,255,255,0.06);--gh:rgba(255,255,255,0.08);
    --ac:#7c6ef0;--ac2:#a78bfa;--acg:rgba(124,110,240,0.3);
    --tx:#e2e0f0;--td:rgba(255,255,255,0.35);--td2:rgba(255,255,255,0.18);
    --dn:#f43f5e;--bar:rgba(7,6,15,0.8);--bar2:rgba(7,6,15,0.5);
    --ctl:rgba(255,255,255,0.04);--ctl-h:rgba(255,255,255,0.08);--ctl-line:rgba(255,255,255,0.06);
    --strong:#fff;--shadow:rgba(0,0,0,0.3);
    --glow1:rgba(124,110,240,0.07);--glow2:rgba(56,189,248,0.04);
}
html[data-theme="light"]{
    --bg:#f4f4fb;--bg2:#ffffff;
    --glass:rgba(255,255,255,0.75);--gb:rgba(20,18,50,0.08);--gh:rgba(255,255,255,0.95);
    --ac:#6d5de6;--ac2:#6248d4;--acg:rgba(109,93,230,0.22);
    --tx:#1d1b33;--td:rgba(29,27,51,0.55);--td2:rgba(29,27,51,0.35);
    --dn:#e11d48;--bar:rgba(255,255,255,0.85);--bar2:rgba(255,255,255,0.6);
    --ctl:rgba(20,18,50,0.04);--ctl-h:rgba(20,18,50,0.08);--ctl-line:rgba(20,18,50,0.08);
    --strong:#14122e;--shadow:rgba(60,55,120,0.14);
    --glow1:rgba(124,110,240,0.10);--glow2:rgba(56,189,248,0.08);
}
html,body{height:100%;font-family:'Vazirmatn',system-ui,sans-serif;background:var(--bg);color:var(--tx);overflow:hidden;-webkit-tap-highlight-color:transparent;}
.app{display:flex;flex-direction:column;height:100vh;height:100dvh;
    background:radial-gradient(ellipse at 15% 0%,var(--glow1) 0%,transparent 55%),
    radial-gradient(ellipse at 85% 100%,var(--glow2) 0%,transparent 50%),var(--bg);}

.topbar{display:flex;align-items:center;justify-content:space-between;padding:0 20px;height:54px;
    padding-top:env(safe-area-inset-top);height:calc(54px + env(safe-area-inset-top));
    background:var(--bar);backdrop-filter:blur(40px);-webkit-backdrop-filter:blur(40px);
    border-bottom:1px solid var(--gb);z-index:100;flex-shrink:0;}
.t-right{display:flex;align-items:center;gap:10px;}
.t-left{display:flex;align-items:center;gap:6px;}
.logo{display:flex;align-items:center;gap:9px;text-decoration:none;color:inherit;}
.logo-icon{width:32px;height:32px;background:linear-gradient(135deg,#7c6ef0,#5b4dc7);border-radius:9px;
    display:flex;align-items:center;justify-content:center;box-shadow:0 2px 14px var(--acg);}
.logo-icon svg{width:15px;height:15px;fill:#fff;}
.logo-text{font-size:15px;font-weight:700;color:var(--strong);}
.badge-admin{display:inline-flex;align-items:center;gap:4px;background:rgba(34,197,94,0.1);
    border:1px solid rgba(34,197,94,0.18);padding:2px 8px;border-radius:6px;font-size:10px;color:#4ade80;font-weight:600;}
.search-wrap{position:relative;}
.search-wrap input{background:var(--ctl);border:1px solid var(--ctl-line);
    border-radius:10px;padding:8px 14px 8px 34px;color:var(--strong);font-family:inherit;font-size:13px;width:200px;outline:none;transition:all .3s;}
.search-wrap input:focus{border-color:rgba(124,110,240,0.35);background:rgba(255,255,255,0.07);
    box-shadow:0 0 0 3px rgba(124,110,240,0.08);width:280px;}
.search-wrap input::placeholder{color:var(--td2);}
.search-wrap .s-svg{position:absolute;left:10px;top:50%;transform:translateY(-50%);width:14px;height:14px;fill:var(--td2);pointer-events:none;}
.icon-btn{width:33px;height:33px;border-radius:9px;background:var(--ctl);border:1px solid var(--ctl-line);
    color:rgba(255,255,255,0.4);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .2s;text-decoration:none;}
.icon-btn:hover{background:var(--ctl-h);color:var(--strong);}
.icon-btn svg{width:15px;height:15px;fill:currentColor;}
html[data-theme="light"] .icon-btn{color:rgba(29,27,51,0.5);}

.bell-btn{position:relative;}
.bell-btn.has-alert{background:rgba(245,158,11,0.12);border-color:rgba(245,158,11,0.24);color:#fbbf24;
    animation:bellPulse 2.4s ease-in-out infinite;}
@keyframes bellPulse{0%,100%{box-shadow:0 0 0 0 rgba(245,158,11,0.22);}50%{box-shadow:0 0 0 5px rgba(245,158,11,0);}}
.bell-badge{position:absolute;top:-5px;right:-5px;min-width:17px;height:17px;padding:0 4px;
    background:linear-gradient(135deg,#f43f5e,#be123c);border-radius:9px;color:#fff;font-size:9.5px;
    font-weight:800;display:flex;align-items:center;justify-content:center;border:2px solid var(--bg);}

.theme-ico-moon{display:none;}
html[data-theme="light"] .theme-ico-moon{display:block;}
html[data-theme="light"] .theme-ico-sun{display:none;}

.month-tabs{display:flex;align-items:center;justify-content:center;background:var(--bar2);backdrop-filter:blur(20px);
    border-bottom:1px solid rgba(255,255,255,0.04);flex-shrink:0;overflow-x:auto;scrollbar-width:none;padding:0 20px;gap:2px;z-index:50;}
.month-tabs::-webkit-scrollbar{display:none;}
.m-tab{padding:12px 18px;font-size:13px;font-weight:500;color:var(--td2);cursor:pointer;border-bottom:2px solid transparent;
    white-space:nowrap;transition:all .2s;flex-shrink:0;display:flex;align-items:center;gap:6px;text-decoration:none;}
.m-tab:hover{color:rgba(255,255,255,0.55);background:rgba(255,255,255,0.03);}
html[data-theme="light"] .m-tab:hover{color:rgba(29,27,51,0.55);background:rgba(20,18,50,0.04);}
.m-tab.active{color:var(--ac2);border-bottom-color:var(--ac);font-weight:600;}
.tab-badge{background:var(--acg);padding:2px 7px;border-radius:5px;font-size:10px;color:#fff;font-weight:600;}

.content{flex:1;overflow-y:auto;overflow-x:hidden;padding:24px;padding-bottom:calc(24px + env(safe-area-inset-bottom));
    z-index:1;-webkit-overflow-scrolling:touch;}
.content::-webkit-scrollbar{width:5px;}
.content::-webkit-scrollbar-thumb{background:rgba(255,255,255,0.06);border-radius:3px;}
html[data-theme="light"] .content::-webkit-scrollbar-thumb{background:rgba(29,27,51,0.15);}
.content-inner{max-width:1200px;margin:0 auto;}

.section-title{display:flex;align-items:center;gap:10px;margin:4px 0 16px;}
.section-title .dot{width:8px;height:8px;border-radius:50%;background:var(--ac);box-shadow:0 0 10px var(--acg);}
.section-title h2{font-size:15px;font-weight:700;color:rgba(255,255,255,0.75);}
html[data-theme="light"] .section-title h2{color:rgba(29,27,51,0.8);}
.section-title .count{font-size:11px;color:var(--td);margin-right:4px;}

.announcements-wrap{display:flex;flex-direction:column;gap:10px;margin-bottom:18px;}
.announce-bar{display:flex;align-items:flex-start;gap:12px;padding:14px 18px;border-radius:14px;
    font-size:13.5px;line-height:1.7;animation:cardIn .4s ease;}
@keyframes cardIn{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}
.announce-bar.info{background:linear-gradient(135deg,rgba(124,110,240,0.12),rgba(124,110,240,0.06));border:1px solid rgba(124,110,240,0.25);color:#c4b5fd;}
.announce-bar.warning{background:linear-gradient(135deg,rgba(245,158,11,0.12),rgba(245,158,11,0.06));border:1px solid rgba(245,158,11,0.25);color:#fcd34d;}
.announce-bar.success{background:linear-gradient(135deg,rgba(34,197,94,0.12),rgba(34,197,94,0.06));border:1px solid rgba(34,197,94,0.25);color:#86efac;}
.announce-bar.danger{background:linear-gradient(135deg,rgba(244,63,94,0.12),rgba(244,63,94,0.06));border:1px solid rgba(244,63,94,0.25);color:#fca5a5;}
html[data-theme="light"] .announce-bar.info{color:#4c37b8;}
html[data-theme="light"] .announce-bar.warning{color:#a15c07;}
html[data-theme="light"] .announce-bar.success{color:#15803d;}
html[data-theme="light"] .announce-bar.danger{color:#be123c;}
.announce-icon{width:32px;height:32px;border-radius:9px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.06);}
html[data-theme="light"] .announce-icon{background:rgba(20,18,50,0.05);}
.announce-icon svg{width:16px;height:16px;fill:currentColor;}
.announce-content{flex:1;min-width:0;}
.announce-content .a-title{font-weight:700;font-size:12px;opacity:0.75;margin-bottom:3px;}
.announce-content .a-text{word-wrap:break-word;white-space:pre-wrap;}
.announce-close{width:26px;height:26px;border-radius:7px;flex-shrink:0;background:rgba(255,255,255,0.06);
    border:1px solid rgba(255,255,255,0.08);color:currentColor;display:flex;align-items:center;justify-content:center;
    cursor:pointer;transition:all .2s;text-decoration:none;opacity:0.6;}
.announce-close:hover{opacity:1;background:rgba(255,255,255,0.12);}
.announce-close svg{width:12px;height:12px;fill:currentColor;}

.person-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:10px;}
.person-card{background:var(--glass);border:1px solid var(--gb);border-radius:16px;padding:18px;
    display:flex;flex-direction:column;gap:12px;transition:all .22s;text-decoration:none;color:inherit;
    animation:cardIn .3s ease both;position:relative;overflow:hidden;}
.person-card::after{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(124,110,240,0.06),transparent 60%);opacity:0;transition:opacity .25s;}
.person-card:hover{background:var(--gh);border-color:rgba(124,110,240,0.25);transform:translateY(-2px);}
.person-card:hover::after{opacity:1;}
.person-top{display:flex;align-items:center;gap:12px;position:relative;z-index:1;}
.avatar{width:46px;height:46px;border-radius:14px;flex-shrink:0;display:flex;align-items:center;justify-content:center;
    font-size:17px;font-weight:800;color:#fff;background:linear-gradient(135deg,#7c6ef0,#5b4dc7);
    box-shadow:0 4px 14px rgba(0,0,0,0.3);text-shadow:0 1px 3px rgba(0,0,0,0.2);}
.person-name{font-size:14.5px;font-weight:700;color:var(--strong);white-space:nowrap;overflow:hidden;
    text-overflow:ellipsis;display:flex;align-items:center;gap:6px;min-width:0;}
.person-name .new-badge{flex-shrink:0;}
.person-sub{font-size:11px;color:var(--td);margin-top:3px;line-height:1.6;}
.type-chips{display:flex;gap:5px;flex-wrap:wrap;position:relative;z-index:1;}
.chip{font-size:10px;font-weight:700;padding:3px 9px;border-radius:6px;display:inline-flex;align-items:center;gap:4px;}
.chip.green{background:rgba(34,197,94,0.12);color:#4ade80;border:1px solid rgba(34,197,94,0.2);}
.chip.blue{background:rgba(56,189,248,0.12);color:#38bdf8;border:1px solid rgba(56,189,248,0.2);}
.chip.purple{background:rgba(168,85,247,0.12);color:#c084fc;border:1px solid rgba(168,85,247,0.2);}
.chip.orange{background:rgba(249,115,22,0.12);color:#fb923c;border:1px solid rgba(249,115,22,0.2);}
.chip.pink{background:rgba(236,72,153,0.12);color:#f472b6;border:1px solid rgba(236,72,153,0.2);}
.chip.cyan{background:rgba(6,182,212,0.12);color:#22d3ee;border:1px solid rgba(6,182,212,0.2);}
.chip.lime{background:rgba(132,204,22,0.12);color:#a3e635;border:1px solid rgba(132,204,22,0.2);}
.chip.amber{background:rgba(245,158,11,0.12);color:#fbbf24;border:1px solid rgba(245,158,11,0.2);}
.chip.gray{background:rgba(255,255,255,0.06);color:var(--td);border:1px solid rgba(255,255,255,0.08);}
html[data-theme="light"] .chip.gray{background:rgba(20,18,50,0.06);border-color:rgba(20,18,50,0.1);}
.person-arrow{position:absolute;left:16px;bottom:16px;opacity:0.25;transition:all .2s;}
.person-card:hover .person-arrow{opacity:0.7;transform:translateX(-3px);}
.person-arrow svg{width:16px;height:16px;fill:currentColor;}
.person-share{width:32px;height:32px;border-radius:9px;flex-shrink:0;
    background:rgba(168,85,247,0.1);border:1px solid rgba(168,85,247,0.2);color:#c084fc;
    display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .2s;text-decoration:none;position:relative;z-index:3;}
.person-share:hover{background:rgba(168,85,247,0.22);transform:scale(1.06);}
.person-share.big{width:38px;height:38px;border-radius:10px;}
.person-share svg{width:14px;height:14px;fill:currentColor;}
.person-share.big svg{width:16px;height:16px;}
.share-loading{opacity:0.5;pointer-events:none;}

.file-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:10px;margin-bottom:20px;}
.file-card{background:var(--glass);border:1px solid var(--gb);border-radius:14px;padding:16px 18px;
    display:flex;align-items:center;gap:14px;transition:all .22s;animation:cardIn .3s ease both;}
.file-card:hover{background:var(--gh);border-color:rgba(255,255,255,0.09);transform:translateY(-2px);}
html[data-theme="light"] .file-card{box-shadow:0 2px 10px rgba(60,55,120,0.05);}
html[data-theme="light"] .file-card:hover{background:#fff;box-shadow:0 8px 26px rgba(60,55,120,0.12);}
.pdf-thumb{width:44px;height:52px;border-radius:10px;display:flex;flex-direction:column;align-items:center;justify-content:center;flex-shrink:0;}
.pdf-thumb.diet{background:linear-gradient(150deg,#22c55e,#15803d);}
.pdf-thumb.analyse{background:linear-gradient(150deg,#38bdf8,#0369a1);}
.pdf-thumb.program{background:linear-gradient(150deg,#a855f7,#6b21a8);}
.pdf-thumb.challenge{background:linear-gradient(150deg,#f97316,#c2410c);}
.pdf-thumb.report{background:linear-gradient(150deg,#ec4899,#9d174d);}
.pdf-thumb.test{background:linear-gradient(150deg,#06b6d4,#0e7490);}
.pdf-thumb.homeplan{background:linear-gradient(150deg,#84cc16,#4d7c0f);}
.pdf-thumb.warmup{background:linear-gradient(150deg,#f59e0b,#b45309);}
.pdf-thumb.other{background:linear-gradient(150deg,#ef4444,#b91c1c);}
.pdf-thumb svg{width:18px;height:18px;fill:rgba(255,255,255,0.9);}
.pdf-thumb .ext{font-size:8px;font-weight:800;color:#fff;margin-top:1px;}
.file-info{flex:1;min-width:0;}
.file-name{font-size:14px;font-weight:600;color:var(--strong);white-space:nowrap;overflow:hidden;
    text-overflow:ellipsis;margin-bottom:5px;display:flex;align-items:center;gap:6px;min-width:0;}
.file-meta{display:flex;gap:10px;font-size:11.5px;color:var(--td);flex-wrap:wrap;align-items:center;}
.file-meta span{display:flex;align-items:center;gap:4px;}
.file-meta svg{width:10px;height:10px;fill:currentColor;}
.file-btns{display:flex;gap:5px;flex-shrink:0;}
.f-action{width:34px;height:34px;border-radius:8px;background:var(--ctl);border:1px solid var(--ctl-line);
    color:rgba(255,255,255,0.3);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .2s;text-decoration:none;}
.f-action svg{width:15px;height:15px;fill:currentColor;}
.f-action.view:hover{background:rgba(56,189,248,0.12);color:#38bdf8;}
.f-action.share:hover{background:rgba(168,85,247,0.12);color:#c084fc;}
.f-action.down:hover{background:rgba(34,197,94,0.12);color:#4ade80;}
.f-action.edit:hover{background:rgba(245,158,11,0.12);color:#fbbf24;}
.f-action.trash:hover{background:rgba(244,63,94,0.12);color:var(--dn);}
.new-badge{display:inline-flex;background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.2);
    padding:2px 7px;border-radius:5px;font-size:9px;font-weight:700;color:#4ade80;animation:pulseB 2s ease infinite;}
@keyframes pulseB{0%,100%{box-shadow:0 0 0 0 rgba(34,197,94,0.15);}50%{box-shadow:0 0 8px 2px rgba(34,197,94,0.2);}}

.back-bar{display:flex;align-items:center;gap:12px;margin-bottom:18px;flex-wrap:wrap;}
.back-btn{display:inline-flex;align-items:center;gap:6px;padding:9px 14px;border-radius:10px;
    background:var(--ctl);border:1px solid var(--gb);color:var(--td);font-size:12.5px;font-weight:600;
    text-decoration:none;transition:all .2s;}
.back-btn:hover{background:var(--gh);color:var(--strong);}
.back-btn svg{width:14px;height:14px;fill:currentColor;}
.person-header{display:flex;align-items:center;gap:12px;flex:1;min-width:0;}
.person-header .avatar{width:42px;height:42px;font-size:16px;border-radius:12px;}
.person-header h1{font-size:17px;font-weight:800;color:var(--strong);white-space:nowrap;overflow:hidden;
    text-overflow:ellipsis;display:flex;align-items:center;gap:8px;}
.person-header .ph-sub{font-size:11.5px;color:var(--td);margin-top:2px;line-height:1.6;}

.empty-box{text-align:center;padding:70px 20px;}
.empty-icon{width:72px;height:72px;margin:0 auto 18px;background:rgba(255,255,255,0.03);border:1px solid var(--gb);
    border-radius:18px;display:flex;align-items:center;justify-content:center;}
.empty-icon svg{width:32px;height:32px;fill:rgba(255,255,255,0.15);}
html[data-theme="light"] .empty-icon{background:rgba(20,18,50,0.03);}
html[data-theme="light"] .empty-icon svg{fill:rgba(29,27,51,0.2);}
.empty-box h3{font-size:16px;font-weight:600;color:rgba(255,255,255,0.4);margin-bottom:5px;}
html[data-theme="light"] .empty-box h3{color:rgba(29,27,51,0.5);}
.empty-box p{font-size:12.5px;color:var(--td2);}
.search-result{padding:2px 0 12px;color:var(--td);font-size:11.5px;}

.overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);backdrop-filter:blur(10px);z-index:9999;align-items:center;justify-content:center;}
html[data-theme="light"] .overlay{background:rgba(30,28,60,0.35);}
.overlay.open{display:flex;}
.modal{background:var(--bg2);border:1px solid var(--gb);border-radius:18px;max-width:440px;width:94%;
    box-shadow:0 25px 80px var(--shadow);overflow:hidden;max-height:90vh;overflow-y:auto;}
.modal.wide{max-width:540px;}
.modal::-webkit-scrollbar{width:3px;}
.modal::-webkit-scrollbar-thumb{background:rgba(255,255,255,0.08);}
html[data-theme="light"] .modal::-webkit-scrollbar-thumb{background:rgba(29,27,51,0.15);}
.modal-top{padding:24px 24px 12px;text-align:center;}
.modal-icon{width:52px;height:52px;border-radius:14px;margin:0 auto 12px;display:flex;align-items:center;justify-content:center;background:rgba(124,110,240,0.1);}
.modal-icon svg{width:22px;height:22px;}
.modal-top h3{font-size:17px;font-weight:700;margin-bottom:4px;color:var(--strong);}
.modal-top p{font-size:12.5px;color:var(--td);line-height:1.6;}
.modal-body{padding:4px 24px 24px;}
.modal-actions{display:flex;border-top:1px solid var(--gb);}
.modal-actions button,.modal-actions a{flex:1;padding:15px;font-family:inherit;font-size:13.5px;font-weight:600;
    border:none;cursor:pointer;transition:all .2s;background:transparent;color:var(--td);text-decoration:none;text-align:center;}
.modal-actions button:first-child{border-left:1px solid var(--gb);}
.modal-actions .danger-btn{color:var(--dn);}
.modal-actions button:hover,.modal-actions a:hover{background:var(--ctl-h);}

.field{width:100%;background:var(--ctl);border:1px solid var(--ctl-line);border-radius:10px;
    padding:11px 14px;color:var(--strong);font-family:inherit;font-size:13.5px;outline:none;transition:all .25s;margin-bottom:10px;}
.field:focus{border-color:rgba(124,110,240,0.35);}
.field::placeholder{color:var(--td2);}
select.field{color:var(--td);cursor:pointer;}
select.field option{background:var(--bg2);color:var(--tx);}
.label{font-size:11px;color:var(--td);margin-bottom:5px;display:block;font-weight:500;}
.submit-btn{width:100%;padding:12px;border-radius:10px;font-family:inherit;font-size:13.5px;font-weight:600;
    border:none;cursor:pointer;color:#fff;background:linear-gradient(135deg,var(--ac),#5b4dc7);box-shadow:0 3px 15px var(--acg);margin-top:4px;transition:all .2s;}
.submit-btn:hover{transform:translateY(-1px);}
.field-area{width:100%;background:var(--ctl);border:1px solid var(--ctl-line);border-radius:10px;
    padding:12px 14px;color:var(--strong);font-family:inherit;font-size:13.5px;outline:none;margin-bottom:10px;resize:vertical;min-height:80px;line-height:1.7;}
.field-area:focus{border-color:rgba(124,110,240,0.35);}
.field-area::placeholder{color:var(--td2);}

.drop-zone{border:2px dashed rgba(255,255,255,0.08);border-radius:14px;padding:28px 18px;text-align:center;
    cursor:pointer;transition:all .25s;position:relative;margin-bottom:14px;}
html[data-theme="light"] .drop-zone{border-color:rgba(20,18,50,0.14);}
.drop-zone:hover,.drop-zone.dragging{border-color:rgba(124,110,240,0.35);background:rgba(124,110,240,0.03);}
.drop-zone input{position:absolute;inset:0;opacity:0;cursor:pointer;}
.drop-zone svg{width:34px;height:34px;fill:rgba(255,255,255,0.15);margin-bottom:6px;}
html[data-theme="light"] .drop-zone svg{fill:rgba(29,27,51,0.22);}
.drop-zone p{color:var(--td);font-size:12.5px;}
.drop-zone .hint{font-size:10.5px;color:var(--td2);margin-top:3px;}

.up-card{background:var(--ctl);border:1px solid var(--ctl-line);
    border-radius:12px;padding:11px 12px;margin-bottom:8px;animation:cardIn .25s ease both;}
.up-card-head{display:flex;align-items:center;gap:8px;margin-bottom:9px;}
.up-card-head svg{width:13px;height:13px;fill:#a78bfa;flex-shrink:0;}
.up-fname{flex:1;min-width:0;font-size:10.5px;color:var(--td);overflow:hidden;
    text-overflow:ellipsis;white-space:nowrap;direction:ltr;text-align:left;}
.up-auto{font-size:9px;padding:2px 6px;border-radius:4px;background:rgba(34,197,94,0.12);
    color:#4ade80;font-weight:700;flex-shrink:0;}
.up-row{display:flex;gap:6px;align-items:center;}
.up-select{background:var(--ctl);border:1px solid var(--ctl-line);
    border-radius:8px;padding:8px 6px;color:var(--strong);font-family:inherit;font-size:11px;
    outline:none;cursor:pointer;flex-shrink:0;width:88px;}
.up-select option{background:var(--bg2);color:var(--tx);}
.up-slug{font-size:9.5px;color:var(--td2);margin-top:6px;display:flex;align-items:center;gap:4px;flex:1;}
.up-slug svg{width:9px;height:9px;fill:currentColor;flex-shrink:0;}
.up-slug b{color:#a78bfa;font-weight:600;direction:ltr;display:inline-block;}

.type-picker{display:flex;gap:6px;margin-bottom:12px;}
.type-opt{flex:1;padding:9px 4px;border-radius:9px;text-align:center;font-size:11px;font-weight:600;cursor:pointer;
    border:1px solid rgba(255,255,255,0.06);background:var(--ctl);color:var(--td);transition:all .2s;user-select:none;text-decoration:none;display:block;}
html[data-theme="light"] .type-opt{border-color:var(--ctl-line);}
.type-opt:hover{background:var(--ctl-h);}
.type-opt.sel.info{background:rgba(124,110,240,0.15);border-color:rgba(124,110,240,0.3);color:#c4b5fd;}
.type-opt.sel.warning{background:rgba(245,158,11,0.15);border-color:rgba(245,158,11,0.3);color:#fcd34d;}
.type-opt.sel.success{background:rgba(34,197,94,0.15);border-color:rgba(34,197,94,0.3);color:#86efac;}
.type-opt.sel.danger{background:rgba(244,63,94,0.15);border-color:rgba(244,63,94,0.3);color:#fca5a5;}

.ftype-picker{display:grid;grid-template-columns:repeat(3,1fr);gap:6px;margin-bottom:12px;}
.ftype-opt{padding:9px 4px;border-radius:9px;text-align:center;font-size:11px;font-weight:600;cursor:pointer;
    border:1px solid rgba(255,255,255,0.06);background:var(--ctl);color:var(--td);
    transition:all .2s;user-select:none;text-decoration:none;display:block;}
html[data-theme="light"] .ftype-opt{border-color:var(--ctl-line);}
.ftype-opt:hover{background:var(--ctl-h);}
.ftype-opt.sel.diet{background:rgba(34,197,94,0.15);border-color:rgba(34,197,94,0.3);color:#4ade80;}
.ftype-opt.sel.analyse{background:rgba(56,189,248,0.15);border-color:rgba(56,189,248,0.3);color:#38bdf8;}
.ftype-opt.sel.program{background:rgba(168,85,247,0.15);border-color:rgba(168,85,247,0.3);color:#c084fc;}
.ftype-opt.sel.challenge{background:rgba(249,115,22,0.15);border-color:rgba(249,115,22,0.3);color:#fb923c;}
.ftype-opt.sel.report{background:rgba(236,72,153,0.15);border-color:rgba(236,72,153,0.3);color:#f472b6;}
.ftype-opt.sel.test{background:rgba(6,182,212,0.15);border-color:rgba(6,182,212,0.3);color:#22d3ee;}
.ftype-opt.sel.homeplan{background:rgba(132,204,22,0.15);border-color:rgba(132,204,22,0.3);color:#a3e635;}
.ftype-opt.sel.warmup{background:rgba(245,158,11,0.15);border-color:rgba(245,158,11,0.3);color:#fbbf24;}
.ftype-opt.sel.other{background:rgba(255,255,255,0.1);border-color:rgba(255,255,255,0.15);color:var(--strong);}

.ann-list{display:flex;flex-direction:column;gap:8px;margin-top:12px;max-height:200px;overflow-y:auto;}
.ann-list::-webkit-scrollbar{width:3px;}
.ann-list::-webkit-scrollbar-thumb{background:rgba(255,255,255,0.08);}
html[data-theme="light"] .ann-list::-webkit-scrollbar-thumb{background:rgba(29,27,51,0.15);}
.ann-item{display:flex;align-items:flex-start;gap:10px;padding:10px 12px;border-radius:10px;
    background:var(--ctl);border:1px solid var(--ctl-line);font-size:12px;line-height:1.6;}
.ann-item .ai-text{flex:1;min-width:0;word-break:break-word;color:var(--strong);}
.ai-tag{font-size:9.5px;font-weight:700;padding:1px 6px;border-radius:4px;margin-bottom:3px;display:inline-block;}
.ai-tag.info{background:rgba(124,110,240,0.15);color:#c4b5fd;}
.ai-tag.warning{background:rgba(245,158,11,0.15);color:#fcd34d;}
.ai-tag.success{background:rgba(34,197,94,0.15);color:#86efac;}
.ai-tag.danger{background:rgba(244,63,94,0.15);color:#fca5a5;}
.ai-del{width:24px;height:24px;border-radius:6px;flex-shrink:0;background:rgba(244,63,94,0.1);color:var(--dn);
    display:flex;align-items:center;justify-content:center;text-decoration:none;transition:all .2s;}
.ai-del:hover{background:rgba(244,63,94,0.25);}
.ai-del svg{width:12px;height:12px;fill:currentColor;}

.alert{padding:12px 16px;border-radius:12px;margin-bottom:16px;font-size:13px;font-weight:500;display:flex;align-items:center;gap:8px;}
.alert.success{background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.2);color:#86efac;}
.alert.error{background:rgba(244,63,94,0.12);border:1px solid rgba(244,63,94,0.2);color:#fca5a5;}
html[data-theme="light"] .alert.success{color:#15803d;}
html[data-theme="light"] .alert.error{color:#be123c;}

.pdf-viewer-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.88);backdrop-filter:blur(12px);
    z-index:99999;flex-direction:column;align-items:center;}
html[data-theme="light"] .pdf-viewer-overlay{background:rgba(30,28,60,0.55);}
.pdf-viewer-overlay.open{display:flex;}
.pdf-viewer-bar{display:flex;align-items:center;justify-content:space-between;padding:12px 20px;
    padding-top:calc(12px + env(safe-area-inset-top));background:rgba(7,6,15,0.95);border-bottom:1px solid var(--gb);
    flex-shrink:0;width:100%;max-width:1000px;}
html[data-theme="light"] .pdf-viewer-bar{background:rgba(255,255,255,0.95);}
.pvb-name{flex:1;font-size:14px;font-weight:600;color:var(--strong);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin:0 16px;}
.pvb-btn{width:36px;height:36px;border-radius:9px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.08);
    color:rgba(255,255,255,0.5);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .2s;text-decoration:none;flex-shrink:0;}
html[data-theme="light"] .pvb-btn{background:rgba(20,18,50,0.05);border-color:var(--ctl-line);color:rgba(29,27,51,0.5);}
.pvb-btn:hover{background:rgba(255,255,255,0.12);color:var(--strong);}
.pvb-btn svg{width:16px;height:16px;fill:currentColor;}
.pdf-viewer-frame{flex:1;width:100%;max-width:1000px;border:none;background:#1a1a2e;min-height:0;}
html[data-theme="light"] .pdf-viewer-frame{background:#e8e8f2;}

.copy-toast{position:fixed;bottom:80px;left:50%;transform:translateX(-50%) translateY(20px);
    background:rgba(14,13,27,0.95);border:1px solid var(--gb);color:#4ade80;padding:10px 20px;
    border-radius:10px;font-size:13px;font-weight:600;z-index:999999;opacity:0;pointer-events:none;
    transition:all .3s;backdrop-filter:blur(12px);display:flex;align-items:center;gap:6px;}
html[data-theme="light"] .copy-toast{background:rgba(255,255,255,0.96);border-color:var(--gb);
    color:#15803d;box-shadow:0 8px 30px rgba(60,55,120,0.18);}
.copy-toast.show{opacity:1;transform:translateX(-50%) translateY(0);}

.rem-list{display:flex;flex-direction:column;gap:9px;max-height:260px;overflow-y:auto;padding-left:3px;}
.rem-list::-webkit-scrollbar{width:3px;}
.rem-list::-webkit-scrollbar-thumb{background:rgba(255,255,255,0.08);}
html[data-theme="light"] .rem-list::-webkit-scrollbar-thumb{background:rgba(29,27,51,0.15);}
.rem-item{background:linear-gradient(135deg,rgba(245,158,11,0.1),rgba(245,158,11,0.04));
    border:1px solid rgba(245,158,11,0.2);border-radius:12px;padding:12px 14px;animation:cardIn .3s ease both;}
.rem-item.old{background:rgba(255,255,255,0.025);border-color:rgba(255,255,255,0.07);opacity:.85;}
.rem-item.upcoming{background:linear-gradient(135deg,rgba(124,110,240,0.08),rgba(124,110,240,0.03));
    border-color:rgba(124,110,240,0.18);}
.rem-head{display:flex;align-items:center;gap:10px;margin-bottom:9px;}
.rem-avatar{width:34px;height:34px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;
    justify-content:center;font-size:14px;font-weight:800;color:#fff;text-shadow:0 1px 3px rgba(0,0,0,0.2);}
.rem-info{flex:1;min-width:0;}
.rem-name{font-size:13.5px;font-weight:700;color:var(--strong);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.rem-sub{font-size:10.5px;color:#fcd34d;margin-top:2px;}
html[data-theme="light"] .rem-sub{color:#a15c07;}
.rem-left{font-size:9.5px;font-weight:700;padding:3px 8px;border-radius:6px;flex-shrink:0;
    background:rgba(244,63,94,0.14);color:#fca5a5;white-space:nowrap;}
.rem-acts{display:flex;gap:6px;}
.rem-btn{flex:1;padding:8px 6px;border-radius:8px;text-align:center;font-size:11.5px;font-weight:700;
    text-decoration:none;transition:all .2s;border:1px solid transparent;}
.rem-btn.go{background:rgba(124,110,240,0.14);color:#c4b5fd;border-color:rgba(124,110,240,0.22);}
.rem-btn.ok{background:rgba(34,197,94,0.14);color:#86efac;border-color:rgba(34,197,94,0.22);}
.rem-btn.no{background:var(--ctl);color:var(--td);border-color:var(--ctl-line);}
.rem-btn.no:hover{background:var(--ctl-h);color:var(--strong);}
.upcoming-toggle{cursor:pointer;justify-content:space-between;padding:9px 12px;border-radius:10px;
    background:var(--ctl);border:1px solid var(--ctl-line);user-select:none;transition:all .2s;}
.upcoming-toggle:hover{background:var(--ctl-h);}
.upcoming-toggle .tg-arrow{width:14px;height:14px;fill:var(--td);transition:transform .25s;}
.upcoming-toggle.open .tg-arrow{transform:rotate(180deg);}
.rem-list.collapsible{display:none;margin-top:9px;}
.rem-list.collapsible.show{display:flex;}

.stat-sec{font-size:11px;font-weight:800;color:var(--td);margin:15px 0 8px;letter-spacing:.2px;
    display:flex;align-items:center;gap:6px;}
.rem-section{font-size:11.5px;font-weight:800;color:#fca5a5;margin:16px 0 9px;display:flex;
    align-items:center;gap:6px;letter-spacing:.2px;}
.rem-section:first-child{margin-top:0;}

@media(min-width:1400px){
    .content{padding:32px 48px;}
    .person-grid{grid-template-columns:repeat(4,1fr);}
    .file-grid{grid-template-columns:repeat(3,1fr);}
    .topbar{padding:0 32px;}
    .month-tabs{padding:0 32px;}
    .search-wrap input{width:280px;font-size:14px;}
    .search-wrap input:focus{width:380px;}
    .icon-btn{width:38px;height:38px;}
    .icon-btn svg{width:17px;height:17px;}
    .m-tab{padding:14px 22px;font-size:14px;}
}
@media(min-width:1024px) and (max-width:1399px){
    .content{padding:28px 36px;}
    .person-grid{grid-template-columns:repeat(3,1fr);}
    .file-grid{grid-template-columns:repeat(2,1fr);}
}
@media(max-width:768px){
    .topbar{padding:0 10px;height:48px;padding-top:env(safe-area-inset-top);height:calc(48px + env(safe-area-inset-top));}
    .logo-icon{width:28px;height:28px;}
    .logo-text{font-size:13px;}
    .search-wrap input{width:100px;font-size:11px;padding:6px 8px 6px 26px;}
    .search-wrap input:focus{width:145px;}
    .search-wrap .s-svg{width:12px;height:12px;left:8px;}
    .icon-btn{width:30px;height:30px;}
    .icon-btn svg{width:13px;height:13px;}
    .m-tab{padding:9px 12px;font-size:11px;}
    .month-tabs{padding:0 10px;justify-content:flex-start;}
    .content{padding:14px;}
    .person-grid{grid-template-columns:1fr;gap:7px;}
    .person-card{padding:12px 14px;border-radius:13px;gap:11px;flex-direction:row;align-items:center;}
    .person-card .person-top{flex:1;min-width:0;gap:11px;}
    .avatar{width:42px;height:42px;font-size:16px;border-radius:12px;}
    .person-name{font-size:14px;white-space:normal;word-break:break-word;line-height:1.4;}
    .person-sub{font-size:10.5px;margin-top:3px;}
    .type-chips{flex-shrink:0;flex-direction:column;gap:3px;align-items:flex-end;}
    .chip{font-size:9px;padding:2px 7px;white-space:nowrap;}
    .person-arrow{display:none;}
    .person-share{width:30px;height:30px;}
    .file-grid{grid-template-columns:1fr;gap:6px;}
    .file-card{padding:12px 14px;gap:10px;}
    .pdf-thumb{width:38px;height:46px;}
    .file-name{font-size:12.5px;}
    .file-meta{font-size:10.5px;gap:8px;}
    .f-action{width:30px;height:30px;}
    .f-action svg{width:12px;height:12px;}
    .modal{width:96%;}
}
@media(max-width:420px){
    .logo-text{display:none;}
    .search-wrap input{width:90px;}
}

/* =========================================================
   2026 interface refresh - visual layer only
   ========================================================= */
:root{
    --surface:rgba(18,17,38,.76);
    --surface-solid:#111024;
    --surface-raised:rgba(255,255,255,.055);
    --hairline:rgba(255,255,255,.075);
    --muted:rgba(238,236,255,.48);
    --quiet:rgba(238,236,255,.25);
    --accent:#8b7cf6;
    --accent-soft:rgba(139,124,246,.14);
    --accent-line:rgba(139,124,246,.28);
    --page-width:1280px;
    --ease:cubic-bezier(.22,1,.36,1);
}
html[data-theme="light"]{
    --surface:rgba(255,255,255,.82);
    --surface-solid:#fff;
    --surface-raised:rgba(24,20,64,.035);
    --hairline:rgba(35,29,82,.09);
    --muted:rgba(29,27,51,.58);
    --quiet:rgba(29,27,51,.34);
    --accent:#6d5de6;
    --accent-soft:rgba(109,93,230,.10);
    --accent-line:rgba(109,93,230,.22);
}

html,body{font-feature-settings:"ss01" 1,"tnum" 1;}
.app{position:relative;isolation:isolate;}
.app::before,.app::after{
    content:"";position:fixed;z-index:-1;border-radius:50%;filter:blur(1px);pointer-events:none;
    animation:ambientFloat 16s var(--ease) infinite alternate;
}
.app::before{width:38vw;height:38vw;min-width:320px;min-height:320px;right:-14vw;top:-20vw;
    background:radial-gradient(circle,rgba(139,124,246,.10),transparent 67%);}
.app::after{width:34vw;height:34vw;min-width:280px;min-height:280px;left:-14vw;bottom:-18vw;
    background:radial-gradient(circle,rgba(56,189,248,.065),transparent 68%);animation-delay:-8s;}
@keyframes ambientFloat{to{transform:translate3d(2.5vw,2vw,0) scale(1.06);}}

/* Header */
.topbar{height:72px;padding:0 24px;background:color-mix(in srgb,var(--bar) 92%,transparent);
    border-bottom-color:var(--hairline);box-shadow:0 14px 44px rgba(0,0,0,.08);}
.topbar-inner{width:100%;max-width:var(--page-width);height:100%;margin:0 auto;display:flex;
    align-items:center;justify-content:space-between;gap:24px;}
.t-right,.t-left{min-width:0;}
.logo{gap:12px;}
.logo-icon{width:40px;height:40px;border-radius:13px;position:relative;overflow:hidden;
    background:linear-gradient(145deg,#9a8cff 0%,#6d5de6 55%,#4b3baa 100%);
    box-shadow:0 10px 30px rgba(109,93,230,.28),inset 0 1px 0 rgba(255,255,255,.28);}
.logo-icon::after{content:"";position:absolute;inset:1px;border-radius:12px;
    background:linear-gradient(145deg,rgba(255,255,255,.16),transparent 45%);}
.logo-icon svg{width:18px;height:18px;position:relative;z-index:1;}
.brand-copy{display:flex;flex-direction:column;line-height:1.2;}
.logo-text{font-size:15.5px;letter-spacing:-.2px;}
.logo-subtitle{font-size:9.5px;color:var(--quiet);font-weight:500;margin-top:4px;}
.badge-admin{height:25px;padding:0 9px;border-radius:8px;color:#6ee7a0;
    background:rgba(34,197,94,.09);border-color:rgba(34,197,94,.17);}
html[data-theme="light"] .badge-admin{color:#16864b;}

.t-left{gap:7px;}
.search-wrap input{width:238px;height:40px;border-radius:13px;padding:0 15px 0 39px;
    background:var(--surface-raised);border-color:var(--hairline);box-shadow:inset 0 1px 0 rgba(255,255,255,.025);}
.search-wrap input:focus{width:292px;background:var(--surface-solid);border-color:var(--accent-line);
    box-shadow:0 0 0 4px var(--accent-soft),0 10px 30px rgba(0,0,0,.08);}
.search-wrap .s-svg{left:13px;width:15px;height:15px;}
.icon-btn{width:40px;height:40px;border-radius:13px;background:var(--surface-raised);border-color:var(--hairline);
    color:var(--muted);box-shadow:inset 0 1px 0 rgba(255,255,255,.025);}
.icon-btn:hover{transform:translateY(-1px);background:var(--surface-solid);border-color:var(--accent-line);
    color:var(--strong);box-shadow:0 9px 24px rgba(0,0,0,.12);}
.icon-btn:active{transform:scale(.96);}
.mobile-admin-trigger{display:none;}

/* Month navigation */
.month-tabs{height:54px;padding:0 20px;justify-content:center;background:color-mix(in srgb,var(--bar2) 88%,transparent);
    border-bottom-color:var(--hairline);}
.tabs-track{height:100%;width:100%;max-width:var(--page-width);margin:0 auto;display:flex;align-items:stretch;
    justify-content:center;gap:3px;overflow-x:auto;scrollbar-width:none;}
.tabs-track::-webkit-scrollbar{display:none;}
.m-tab{height:100%;padding:0 17px;border:0;border-radius:0;position:relative;color:var(--quiet);}
.m-tab::after{content:"";position:absolute;right:50%;left:50%;bottom:0;height:2px;border-radius:2px 2px 0 0;
    background:linear-gradient(90deg,var(--accent),#b7adff);transition:all .28s var(--ease);box-shadow:0 -3px 14px transparent;}
.m-tab:hover{background:transparent;color:var(--muted);}
.m-tab.active{border:0;color:var(--strong);}
.m-tab.active::after{right:16px;left:16px;box-shadow:0 -4px 18px rgba(139,124,246,.45);}
.tab-badge{min-width:20px;height:20px;padding:0 6px;display:inline-flex;align-items:center;justify-content:center;
    border-radius:7px;background:var(--accent-soft);color:var(--ac2);border:1px solid var(--accent-line);}

/* Content and page heading */
.content{padding:32px 28px 48px;scroll-padding-top:24px;}
.content-inner{max-width:var(--page-width);}
.collection-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:24px;margin:4px 0 24px;
    animation:headingIn .55s var(--ease) both;}
@keyframes headingIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}
.collection-copy{min-width:0;}
.collection-kicker{display:block;margin-bottom:6px;color:var(--ac2);font-size:10px;font-weight:800;letter-spacing:.3px;}
.collection-heading h1{font-size:clamp(22px,2.3vw,31px);line-height:1.25;color:var(--strong);font-weight:900;letter-spacing:-.7px;}
.collection-heading p{margin-top:7px;font-size:11.5px;color:var(--quiet);}
.collection-count{width:68px;height:68px;border-radius:20px;display:flex;flex-direction:column;align-items:center;justify-content:center;
    flex-shrink:0;background:var(--accent-soft);border:1px solid var(--accent-line);
    box-shadow:inset 0 1px 0 rgba(255,255,255,.08),0 12px 32px rgba(109,93,230,.10);}
.collection-count strong{font-size:21px;color:var(--strong);line-height:1;font-weight:900;}
.collection-count span{font-size:9px;color:var(--muted);margin-top:5px;}

/* Announcements are slim, readable notices */
.announcements-wrap{gap:8px;margin-bottom:22px;}
.announce-bar{border-radius:15px;padding:13px 15px;gap:11px;box-shadow:inset 0 1px 0 rgba(255,255,255,.035);}
.announce-icon{width:34px;height:34px;border-radius:11px;}
.announce-content .a-title{font-size:10px;text-transform:uppercase;letter-spacing:.45px;}
.announce-content .a-text{font-size:12.5px;line-height:1.8;}

/* People */
.person-grid{grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;}
.person-card{min-height:154px;padding:18px;border-radius:19px;background:var(--surface-raised);border-color:var(--hairline);
    box-shadow:inset 0 1px 0 rgba(255,255,255,.035),0 8px 26px rgba(0,0,0,.055);}
.person-card::before{content:"";position:absolute;inset:auto -20px -45px auto;width:130px;height:100px;
    background:radial-gradient(circle,var(--accent-soft),transparent 68%);opacity:.45;transition:.35s var(--ease);}
.person-card:hover{background:var(--surface);border-color:var(--accent-line);transform:translateY(-4px);
    box-shadow:0 20px 52px rgba(0,0,0,.17),inset 0 1px 0 rgba(255,255,255,.055);}
.person-card:hover::before{transform:scale(1.25);opacity:.8;}
.person-top{gap:13px;}
.avatar{width:48px;height:48px;border-radius:15px;font-size:18px;box-shadow:0 10px 28px rgba(0,0,0,.20),inset 0 1px 0 rgba(255,255,255,.25);}
.person-name{font-size:14px;line-height:1.55;}
.person-sub{display:flex;align-items:center;flex-wrap:wrap;gap:5px 9px;margin-top:3px;font-size:10px;}
.person-sub>span:not(.fresh-count){position:relative;}
.person-sub>span:not(:last-child)::after{content:"";width:3px;height:3px;border-radius:50%;background:var(--quiet);
    position:absolute;left:-6px;top:50%;transform:translateY(-50%);}
.fresh-count{color:#4ade80;font-weight:800;}
.type-chips{margin-top:auto;gap:5px;}
.chip{padding:3px 8px;border-radius:7px;font-size:9.5px;line-height:1.35;}
.person-share{width:34px;height:34px;border-radius:11px;background:var(--accent-soft);border-color:var(--accent-line);}
.person-arrow{left:18px;bottom:18px;}

/* Person detail */
.back-bar{padding:15px 17px;margin:0 0 22px;border-radius:18px;background:var(--surface-raised);
    border:1px solid var(--hairline);box-shadow:inset 0 1px 0 rgba(255,255,255,.03);}
.back-btn{min-height:38px;padding:0 12px;border-radius:11px;background:var(--ctl);border-color:var(--hairline);}
.back-btn:hover{transform:translateY(-1px);border-color:var(--accent-line);}
.person-header .avatar{width:46px;height:46px;border-radius:14px;}
.person-header h1{font-size:18px;}

/* Files */
.file-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;}
.file-card{min-height:92px;padding:15px 16px;border-radius:17px;background:var(--surface-raised);border-color:var(--hairline);
    box-shadow:inset 0 1px 0 rgba(255,255,255,.03);}
.file-card:hover{background:var(--surface);border-color:var(--accent-line);transform:translateY(-2px);
    box-shadow:0 14px 36px rgba(0,0,0,.13);}
.pdf-thumb{width:46px;height:56px;border-radius:12px;box-shadow:0 9px 22px rgba(0,0,0,.18),inset 0 1px 0 rgba(255,255,255,.20);}
.file-name{font-size:12.5px;line-height:1.6;margin-bottom:7px;}
.file-meta{font-size:10px;gap:6px 10px;}
.file-meta .chip{padding:2px 7px;}
.file-btns{gap:4px;}
.f-action{width:32px;height:32px;border-radius:10px;}
.f-action:hover{transform:translateY(-1px);}
.new-badge{height:20px;align-items:center;padding:0 7px;border-radius:7px;letter-spacing:.35px;}

/* Modals */
.overlay{padding:24px;background:rgba(3,3,12,.68);opacity:0;visibility:hidden;display:flex;
    transition:opacity .22s ease,visibility .22s ease;}
.overlay.open{opacity:1;visibility:visible;}
.modal{border-radius:24px;background:color-mix(in srgb,var(--bg2) 91%,transparent);border-color:var(--hairline);
    box-shadow:0 34px 110px rgba(0,0,0,.42),inset 0 1px 0 rgba(255,255,255,.055);
    transform:translateY(12px) scale(.975);opacity:0;transition:transform .34s var(--ease),opacity .22s ease;}
.overlay.open .modal{transform:none;opacity:1;}
.modal-top{padding:27px 27px 14px;}
.modal-icon{width:50px;height:50px;border-radius:16px;margin-bottom:14px;background:var(--accent-soft);border:1px solid var(--accent-line);}
.modal-top h3{font-size:17px;letter-spacing:-.3px;}
.modal-top p{font-size:11px;margin-top:5px;}
.modal-body{padding:6px 26px 27px;}
.field,.field-area,.up-select{border-radius:12px;}
.field{height:43px;}
.submit-btn{height:44px;border-radius:13px;box-shadow:0 10px 25px rgba(109,93,230,.24);}
.submit-btn:hover{box-shadow:0 14px 32px rgba(109,93,230,.32);}
.drop-zone{padding:30px 18px;border-radius:17px;background:linear-gradient(145deg,var(--accent-soft),transparent);}
.up-card{border-radius:13px;}

/* Mobile admin sheet */
.sheet-handle{display:none;}
.mobile-admin-grid{display:grid;gap:8px;}
.mobile-admin-grid button,.mobile-admin-grid a{width:100%;min-height:64px;padding:10px 12px;display:flex;align-items:center;gap:12px;
    text-align:right;background:var(--ctl);border:1px solid var(--ctl-line);border-radius:14px;color:var(--strong);
    text-decoration:none;font-family:inherit;cursor:pointer;}
.mobile-admin-grid strong,.mobile-admin-grid small{display:block;}
.mobile-admin-grid strong{font-size:12px;}
.mobile-admin-grid small{font-size:9.5px;color:var(--quiet);margin-top:3px;}
.mobile-admin-icon{width:36px;height:36px;border-radius:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.mobile-admin-icon svg{width:16px;height:16px;fill:currentColor;}
.upload-tone{background:rgba(124,110,240,.14);color:#a78bfa;}
.announce-tone{background:rgba(56,189,248,.13);color:#38bdf8;}
.push-tone{background:rgba(34,197,94,.13);color:#4ade80;}
.logout-tone{background:rgba(244,63,94,.12);color:#fb7185;}

/* Reminder */
.rem-item{border-radius:15px;}
.rem-head{gap:12px;}
.rem-avatar{width:38px;height:38px;border-radius:12px;}
.rem-name{font-size:12.5px;}
.rem-sub{line-height:1.7;}

@media(min-width:1400px){
    .person-grid{grid-template-columns:repeat(4,minmax(0,1fr));}
    .file-grid{grid-template-columns:repeat(3,minmax(0,1fr));}
}

@media(max-width:1023px){
    .topbar{padding:0 16px;}
    .person-grid{grid-template-columns:repeat(2,minmax(0,1fr));}
    .file-grid{grid-template-columns:1fr;}
    .tabs-track{justify-content:flex-start;}
}

@media(max-width:768px){
    .topbar{height:calc(62px + env(safe-area-inset-top));padding:env(safe-area-inset-top) 11px 0;}
    .topbar-inner{gap:8px;}
    .t-right{gap:7px;flex-shrink:0;}
    .logo{gap:7px;}
    .logo-icon{width:35px;height:35px;border-radius:11px;}
    .brand-copy{display:flex;}
    .logo-subtitle{display:none;}
    .logo-text{display:block;font-size:12.5px;}
    .badge-admin{display:none;}
    .t-left{gap:5px;flex:1;justify-content:flex-end;}
    .search-wrap input{width:100px;height:35px;border-radius:11px;padding-left:30px;font-size:11px;}
    .search-wrap input:focus{width:136px;}
    .search-wrap .s-svg{left:10px;width:13px;height:13px;}
    .icon-btn{width:35px;height:35px;border-radius:11px;}
    .admin-desktop-action{display:none;}
    .mobile-admin-trigger{display:flex;}

    .month-tabs{height:48px;padding:0;}
    .tabs-track{padding:0 9px;}
    .m-tab{padding:0 13px;font-size:11px;}
    .m-tab.active::after{right:12px;left:12px;}

    .content{padding:18px 12px calc(28px + env(safe-area-inset-bottom));}
    .collection-heading{align-items:center;margin:2px 2px 18px;}
    .collection-kicker{font-size:9px;margin-bottom:4px;}
    .collection-heading h1{font-size:20px;}
    .collection-heading p{font-size:10px;margin-top:5px;}
    .collection-count{width:56px;height:56px;border-radius:17px;}
    .collection-count strong{font-size:18px;}

    .person-grid{grid-template-columns:1fr;gap:8px;}
    .person-card{min-height:0;padding:13px 14px;border-radius:16px;display:block;}
    .person-card .person-top{width:100%;}
    .avatar{width:44px;height:44px;border-radius:14px;}
    .person-name{white-space:normal;font-size:13.5px;line-height:1.5;}
    .person-sub{gap:4px 8px;font-size:9.5px;}
    .type-chips{margin-top:10px;flex-direction:row;align-items:center;gap:4px;}
    .chip{font-size:8.5px;padding:2px 7px;}
    .person-arrow{display:none;}
    .person-share{width:32px;height:32px;}

    .back-bar{padding:12px;margin-bottom:15px;gap:8px;border-radius:16px;}
    .person-header{order:-1;width:100%;flex-basis:100%;}
    .back-btn{min-height:34px;padding:0 10px;font-size:10.5px;}

    .file-card{display:grid;grid-template-columns:auto minmax(0,1fr);min-height:0;padding:13px;border-radius:15px;}
    .pdf-thumb{width:41px;height:50px;border-radius:11px;}
    .file-btns{grid-column:1/-1;width:100%;justify-content:flex-end;padding-top:10px;margin-top:2px;border-top:1px solid var(--hairline);}
    .f-action{width:34px;height:34px;}

    .overlay{padding:0;align-items:flex-end;}
    .modal{width:100%;max-width:none;max-height:91dvh;border-radius:24px 24px 0 0;border-bottom:0;
        transform:translateY(36px);}
    .overlay.open .modal{transform:translateY(0);}
    .sheet-handle{display:block;width:42px;height:4px;border-radius:3px;background:var(--hairline);margin:9px auto 0;}
    .compact-modal-top{padding-top:16px;}
    .modal-top{padding:22px 20px 12px;}
    .modal-body{padding:5px 18px calc(22px + env(safe-area-inset-bottom));}
    .modal-icon{width:45px;height:45px;border-radius:14px;}
    .announce-bar{padding:12px;border-radius:14px;}
    .announce-content .a-text{font-size:11.5px;}
}

@media(max-width:420px){
    .search-wrap input{width:88px;}
    .search-wrap input:focus{width:118px;}
    .utility-btn{width:33px;height:33px;}
    .collection-heading p{max-width:220px;}
}

@media(max-width:350px){
    .brand-copy{display:none;}
    .search-wrap input{width:82px;}
    .search-wrap input:focus{width:108px;}
}

/* ============================================================
   ✨ MOTION LAYER — entrance, spring, press, ambient
   (pure-cosmetic: does not modify any handlers)
   ============================================================ */

/* ─ entrance of the whole app shell ─ */
@keyframes shellIn{
    from{opacity:0;transform:translateY(10px);}
    to{opacity:1;transform:none;}
}
.topbar,.month-tabs{animation:shellIn .55s cubic-bezier(.22,1,.36,1) both;}
.month-tabs{animation-delay:.09s;}
.content>.content-inner{animation:shellIn .6s cubic-bezier(.22,1,.36,1) .05s both;}

/* ─ staggered card entrances (nth-child based) ─ */
@keyframes cardSpringIn{
    0%{opacity:0;transform:translateY(16px) scale(.975);}
    60%{opacity:1;transform:translateY(-3px) scale(1.005);}
    100%{opacity:1;transform:translateY(0) scale(1);}
}
.person-grid>.person-card{animation:cardSpringIn .6s cubic-bezier(.22,1.15,.4,1) both;}
.file-grid>.file-card{animation:cardSpringIn .55s cubic-bezier(.22,1.15,.4,1) both;}
.person-grid>.person-card:nth-child(1){animation-delay:.04s;}
.person-grid>.person-card:nth-child(2){animation-delay:.09s;}
.person-grid>.person-card:nth-child(3){animation-delay:.14s;}
.person-grid>.person-card:nth-child(4){animation-delay:.19s;}
.person-grid>.person-card:nth-child(5){animation-delay:.24s;}
.person-grid>.person-card:nth-child(6){animation-delay:.29s;}
.person-grid>.person-card:nth-child(7){animation-delay:.34s;}
.person-grid>.person-card:nth-child(8){animation-delay:.39s;}
.person-grid>.person-card:nth-child(9){animation-delay:.44s;}
.person-grid>.person-card:nth-child(10){animation-delay:.49s;}
.person-grid>.person-card:nth-child(n+11){animation-delay:.54s;}
.file-grid>.file-card:nth-child(1){animation-delay:.03s;}
.file-grid>.file-card:nth-child(2){animation-delay:.08s;}
.file-grid>.file-card:nth-child(3){animation-delay:.13s;}
.file-grid>.file-card:nth-child(4){animation-delay:.18s;}
.file-grid>.file-card:nth-child(5){animation-delay:.23s;}
.file-grid>.file-card:nth-child(6){animation-delay:.28s;}
.file-grid>.file-card:nth-child(n+7){animation-delay:.33s;}

/* ─ press feedback on everything clickable (scale only — handlers untouched) ─ */
.icon-btn,.f-action,.back-btn,.m-tab,.rem-btn,.type-opt,.ftype-opt,.person-share,
.submit-btn,.announce-close,.ai-del,.pvb-btn,.mobile-admin-grid button,.mobile-admin-grid a{
    -webkit-tap-highlight-color:transparent;
}
.icon-btn:active,.f-action:active,.back-btn:active,.m-tab:active,.rem-btn:active,
.type-opt:active,.ftype-opt:active,.person-share:active,.ai-del:active,.pvb-btn:active,
.mobile-admin-grid button:active,.mobile-admin-grid a:active{
    transform:scale(.93)!important;
    transition:transform .12s cubic-bezier(.34,1.56,.64,1)!important;
}

/* ─ hover micro-lift — bigger spring feel ─ */
.person-card,.file-card,.stat-box,.tool-btn,.top-row,.ann-item,.rem-item{
    transition:transform .28s cubic-bezier(.34,1.4,.5,1),
               box-shadow .28s cubic-bezier(.22,1,.36,1),
               background .25s,border-color .25s;
}

/* ─ avatar pop on card hover ─ */
.person-card:hover .avatar,
.back-bar .person-header:hover .avatar{
    transform:scale(1.08) rotate(-3deg);
    transition:transform .35s cubic-bezier(.34,1.6,.64,1);
}
.avatar{transition:transform .35s cubic-bezier(.34,1.6,.64,1);}

/* ─ arrow slide on person card hover ─ */
.person-arrow{transition:opacity .25s,transform .3s cubic-bezier(.34,1.56,.64,1);}
.person-card:hover .person-arrow{transform:translateX(-7px);}

/* ─ chips cascade-in ─ */
@keyframes chipIn{
    from{opacity:0;transform:translateY(6px) scale(.85);}
    to{opacity:1;transform:none;}
}
.person-card:hover .chip{animation:chipIn .3s cubic-bezier(.34,1.56,.64,1) both;}
.person-card:hover .chip:nth-child(2){animation-delay:.05s;}
.person-card:hover .chip:nth-child(3){animation-delay:.1s;}
.person-card:hover .chip:nth-child(4){animation-delay:.15s;}

/* ─ shimmer sweep on NEW badge ─ */
.new-badge{position:relative;overflow:hidden;}
.new-badge::after{
    content:"";position:absolute;top:0;bottom:0;left:-60%;width:55%;
    background:linear-gradient(105deg,transparent,rgba(255,255,255,.45),transparent);
    transform:skewX(-20deg);
    animation:badgeSweep 2.6s ease-in-out infinite;
}
@keyframes badgeSweep{
    0%,55%{left:-60%;}
    100%{left:130%;}
}

/* ─ modal spring entrance (replaces plain fade) ─ */
.modal{transition:transform .42s cubic-bezier(.3,1.5,.55,1),opacity .24s ease;}
@media(max-width:768px){
    .modal{transition:transform .45s cubic-bezier(.28,1.4,.55,1),opacity .24s ease;}
}

/* ─ alert slide-in with elastic tail ─ */
@keyframes alertIn{
    from{opacity:0;transform:translateY(-14px) scale(.97);}
    to{opacity:1;transform:none;}
}
.alert{animation:alertIn .5s cubic-bezier(.3,1.4,.6,1) both;}

/* ─ announcement cascade ─ */
.announcements-wrap>.announce-bar{animation:cardSpringIn .5s cubic-bezier(.22,1.15,.4,1) both;}
.announcements-wrap>.announce-bar:nth-child(2){animation-delay:.08s;}
.announcements-wrap>.announce-bar:nth-child(3){animation-delay:.16s;}
.announcements-wrap>.announce-bar:nth-child(4){animation-delay:.24s;}

/* ─ renewed bell: gentle rock loop when alerting ─ */
@keyframes bellRock{
    0%,58%,100%{transform:rotate(0);}
    62%{transform:rotate(14deg);}
    66%{transform:rotate(-11deg);}
    70%{transform:rotate(7deg);}
    74%{transform:rotate(-4deg);}
    78%{transform:rotate(2deg);}
}
.bell-btn.has-alert svg{animation:bellRock 2.8s ease-in-out infinite;transform-origin:50% 2px;}
.bell-btn.has-alert{animation:none;}

/* ─ floating ambient orbs (CSS-only, GPU friendly) ─ */
.ambient-orb{
    position:fixed;border-radius:50%;pointer-events:none;z-index:0;
    filter:blur(1px);opacity:.55;
}
.ambient-orb.o1{
    width:10px;height:10px;left:12vw;top:22vh;
    background:radial-gradient(circle,var(--ac2),transparent 72%);
    animation:orbDrift1 13s ease-in-out infinite alternate;
}
.ambient-orb.o2{
    width:7px;height:7px;right:16vw;top:38vh;
    background:radial-gradient(circle,#7dd3fc,transparent 72%);
    animation:orbDrift2 17s ease-in-out infinite alternate;
}
.ambient-orb.o3{
    width:13px;height:13px;left:26vw;bottom:14vh;
    background:radial-gradient(circle,var(--ac),transparent 74%);
    animation:orbDrift3 21s ease-in-out infinite alternate;
}
.ambient-orb.o4{
    width:5px;height:5px;right:28vw;bottom:26vh;
    background:radial-gradient(circle,#fda4af,transparent 72%);
    animation:orbDrift1 15s ease-in-out infinite alternate-reverse;
}
@keyframes orbDrift1{
    to{transform:translate(4vw,-6vh) scale(1.35);}
}
@keyframes orbDrift2{
    to{transform:translate(-5vw,5vh) scale(1.2);}
}
@keyframes orbDrift3{
    to{transform:translate(3vw,-4vh) scale(1.45);}
}
html[data-theme="light"] .ambient-orb{opacity:.35;}
@media(max-width:768px){
    .ambient-orb.o2,.ambient-orb.o4{display:none;}
    .ambient-orb.o1{width:8px;height:8px;}
    .ambient-orb.o3{width:9px;height:9px;}
}

/* ─ gentle leds pulse on active tab pill ─ */
.m-tab.active{
    position:relative;
}
.m-tab.active::before{
    content:"";position:absolute;inset:-1px;border-radius:inherit;padding:1px;
    background:linear-gradient(120deg,rgba(255,255,255,.35),transparent 40%,rgba(255,255,255,.2));
    -webkit-mask:linear-gradient(#fff 0 0) content-box,linear-gradient(#fff 0 0);
    -webkit-mask-composite:xor;mask-composite:exclude;
    pointer-events:none;
}

/* ─ logo breathing shine ─ */
@keyframes logoShine{
    0%,100%{box-shadow:0 10px 30px rgba(109,93,230,.28),inset 0 1px 0 rgba(255,255,255,.28);}
    50%{box-shadow:0 12px 36px rgba(139,124,246,.45),inset 0 1px 0 rgba(255,255,255,.4);}
}
.logo-icon{animation:logoShine 4s ease-in-out infinite;}

/* ─ search focus pulse glow ─ */
@keyframes searchGlow{
    0%{box-shadow:0 0 0 0 var(--accent-soft);}
    50%{box-shadow:0 0 0 6px var(--accent-soft),0 14px 40px rgba(109,93,230,.22);}
    100%{box-shadow:0 0 0 4px var(--accent-soft),0 14px 40px rgba(109,93,230,.22);}
}
.search-wrap input:focus{animation:searchGlow .7s ease-out;}

/* ─ upload dropzone heartbeat when dragging ─ */
@keyframes zoneBeat{
    0%,100%{transform:scale(1);}
    50%{transform:scale(1.015);}
}
.drop-zone.dragging{animation:zoneBeat .9s ease-in-out infinite;border-style:solid;}

/* ─ toasts slide with overshoot ─ */
@keyframes toastPop{
    0%{transform:translateX(-50%) translateY(30px) scale(.9);opacity:0;}
    70%{transform:translateX(-50%) translateY(-4px) scale(1.03);opacity:1;}
    100%{transform:translateX(-50%) translateY(0) scale(1);opacity:1;}
}
.copy-toast.show{
    animation:toastPop .45s cubic-bezier(.3,1.5,.6,1) both;
}

/* ============================================================
   🔔 PUSH NOTIFICATIONS
   ============================================================ */
.notif-btn{position:relative;}
.notif-btn.granted{
    background:rgba(34,197,94,.12);border-color:rgba(34,197,94,.26);color:#4ade80;
}
.notif-btn.denied{
    background:rgba(244,63,94,.10);border-color:rgba(244,63,94,.22);color:#fb7185;
}
.notif-btn.pending{
    animation:notifInvite 2.6s ease-in-out infinite;
}
@keyframes notifInvite{
    0%,100%{box-shadow:0 0 0 0 rgba(139,124,246,.35);}
    50%{box-shadow:0 0 0 7px rgba(139,124,246,0);}
}
.notif-dot{
    position:absolute;top:-3px;right:-3px;width:9px;height:9px;border-radius:50%;
    background:linear-gradient(135deg,#4ade80,#16a34a);
    border:2px solid var(--bg);
    animation:dotPop .4s cubic-bezier(.34,1.56,.64,1);
}
@keyframes dotPop{from{transform:scale(0);}to{transform:scale(1);}}
.notif-ico-off{display:none;}
.notif-btn.granted .notif-ico-on{display:none;}
.notif-btn.granted .notif-ico-off{display:block;}

/* status card inside admin modal */
.notif-status{
    display:flex;align-items:center;gap:11px;padding:12px 14px;border-radius:13px;
    background:var(--accent-soft);border:1px solid var(--accent-line);margin-bottom:14px;
}
.notif-status .ns-ic{
    width:38px;height:38px;border-radius:12px;flex-shrink:0;display:flex;align-items:center;justify-content:center;
    background:rgba(255,255,255,.07);
}
.notif-status .ns-ic svg{width:17px;height:17px;fill:var(--ac2);}
.notif-status .ns-n{font-size:19px;font-weight:900;color:var(--strong);line-height:1;}
.notif-status .ns-l{font-size:10px;color:var(--muted);margin-top:4px;}

/* history list */
.notif-hist{display:flex;flex-direction:column;gap:7px;max-height:210px;overflow-y:auto;margin-top:10px;}
.notif-hist::-webkit-scrollbar{width:3px;}
.nh-item{
    display:flex;align-items:flex-start;gap:10px;padding:10px 12px;border-radius:11px;
    background:var(--ctl);border:1px solid var(--ctl-line);
}
.nh-body{flex:1;min-width:0;}
.nh-t{font-size:12px;font-weight:700;color:var(--strong);word-break:break-word;}
.nh-b{font-size:10.5px;color:var(--muted);margin-top:3px;line-height:1.65;word-break:break-word;}
.nh-d{font-size:9px;color:var(--quiet);margin-top:5px;}
.nh-del{
    width:24px;height:24px;border-radius:7px;flex-shrink:0;background:rgba(244,63,94,.1);
    color:var(--dn);display:flex;align-items:center;justify-content:center;text-decoration:none;transition:.2s;
}
.nh-del:hover{background:rgba(244,63,94,.24);}
.nh-del svg{width:11px;height:11px;fill:currentColor;}

/* permission hint strip */
.perm-hint{
    display:flex;align-items:center;gap:9px;padding:11px 13px;border-radius:12px;
    font-size:11px;line-height:1.7;margin-bottom:14px;
    background:rgba(245,158,11,.09);border:1px solid rgba(245,158,11,.2);color:#fbbf24;
}
html[data-theme="light"] .perm-hint{color:#a15c07;}
.perm-hint svg{width:15px;height:15px;fill:currentColor;flex-shrink:0;}

/* ─ tab badge count tick ─ */
@keyframes countTick{
    0%{transform:scale(1);}
    40%{transform:scale(1.25);}
    100%{transform:scale(1);}
}
.tab-badge{transition:transform .25s;}
.m-tab:active .tab-badge{animation:countTick .3s cubic-bezier(.34,1.56,.64,1);}

@media(max-width:768px){
    .person-grid>.person-card{animation-duration:.5s;}
    .modal{transition-timing-function:cubic-bezier(.26,1.35,.5,1);}
}

@media(prefers-reduced-motion:reduce){
    *,*::before,*::after{animation-duration:.01ms!important;animation-iteration-count:1!important;
        transition-duration:.01ms!important;scroll-behavior:auto!important;}
}

/* =========================================================
   Polished Aurora layer – deeper glass, better hierarchy
   ========================================================= */
:root{
    --pulse:rgba(139,124,246,.5);
    --ring1:rgba(139,124,246,.35);
    --ring2:rgba(56,189,248,.22);
    --card-lift:rgba(139,124,246,.10);
    --ink-grad:linear-gradient(120deg,#ffffff 30%,#c9c2ff 100%);
}
html[data-theme="light"]{
    --ink-grad:linear-gradient(120deg,#181534 30%,#5c50c9 100%);
}

/* — richer ambient scene — */
.app::before{
    right:-18vw;top:-24vw;width:44vw;height:44vw;
    background:
        radial-gradient(circle at 40% 40%,var(--ring1) 0%,transparent 62%);
    animation:aur1 22s var(--ease) infinite alternate;
}
.app::after{
    left:-18vw;bottom:-22vw;width:40vw;height:40vw;
    background:radial-gradient(circle at 60% 60%,var(--ring2) 0%,transparent 66%);
    animation:aur2 18s var(--ease) infinite alternate;
}
@keyframes aur1{to{transform:translate(-4vw,4vw) scale(1.12) rotate(8deg);}}
@keyframes aur2{to{transform:translate(3vw,-3vw) scale(1.08) rotate(-6deg);}}
.app .grain{content:"";position:fixed;inset:0;z-index:-1;pointer-events:none;opacity:.5;mix-blend-mode:overlay;
    background-image:radial-gradient(rgba(255,255,255,.045) 1px,transparent 1.4px);
    background-size:3px 3px;}
html[data-theme="light"] .app .grain{opacity:.25;mix-blend-mode:multiply;}

/* — header — */
.topbar{
    border-bottom:0;
    background:linear-gradient(180deg,color-mix(in srgb,var(--bar) 96%,transparent),color-mix(in srgb,var(--bar) 88%,transparent));
}
.topbar::after{
    content:"";position:absolute;left:0;right:0;bottom:0;height:1px;
    background:linear-gradient(90deg,transparent,var(--accent-line) 30%,var(--accent-line) 70%,transparent);
    opacity:.7;
}
.logo-icon{
    background:
        radial-gradient(circle at 30% 20%,rgba(255,255,255,.35),transparent 45%),
        linear-gradient(145deg,#a394ff 0%,#6d5de6 55%,#4534a8 100%);
}
.logo-text{
    background:var(--ink-grad);-webkit-background-clip:text;background-clip:text;color:transparent;
    letter-spacing:-.3px;
}
.logo-subtitle{color:var(--ac2);opacity:.85;font-weight:600;}

.search-wrap input{
    transition:width .35s var(--ease),background .25s,border-color .25s,box-shadow .25s;
}
.search-wrap input:focus{
    box-shadow:0 0 0 4px var(--accent-soft),0 14px 40px rgba(109,93,230,.22),inset 0 1px 0 rgba(255,255,255,.08);
}
.icon-btn{
    position:relative;overflow:hidden;
}
.icon-btn::before{
    content:"";position:absolute;inset:0;border-radius:inherit;
    background:linear-gradient(140deg,rgba(255,255,255,.14),transparent 50%);
    opacity:0;transition:.25s;
}
.icon-btn:hover::before{opacity:1;}
.icon-btn svg{position:relative;z-index:1;}

/* — month tabs as pill nav — */
.month-tabs{background:transparent;border-bottom:0;padding:10px 20px 12px;}
.tabs-track{
    height:auto;padding:5px;gap:2px;border-radius:15px;background:var(--surface-raised);
    border:1px solid var(--hairline);
    box-shadow:inset 0 1px 0 rgba(255,255,255,.045),0 10px 30px rgba(0,0,0,.10);
    align-items:center;
}
.m-tab{
    height:36px;padding:0 15px;border-radius:11px;border:0;color:var(--quiet);
    transition:all .25s var(--ease);
}
.m-tab::after{display:none;}
.m-tab:hover{background:rgba(255,255,255,.045);color:var(--muted);}
html[data-theme="light"] .m-tab:hover{background:rgba(35,29,82,.05);}
.m-tab.active{
    background:linear-gradient(135deg,#8b7cf6,#6d5de6);color:#fff;
    box-shadow:0 8px 22px rgba(109,93,230,.42),inset 0 1px 0 rgba(255,255,255,.25);
    font-weight:700;
}
html[data-theme="light"] .m-tab.active{color:#fff;}
.m-tab.active .tab-badge{
    background:rgba(255,255,255,.22);color:#fff;border-color:transparent;
}
.tab-badge{transition:.25s;}

/* — heading — */
.collection-heading{
    padding:6px 2px;
    position:relative;
}
.collection-kicker{
    display:inline-flex;align-items:center;gap:6px;
    padding:3px 10px;border-radius:20px;
    background:var(--accent-soft);border:1px solid var(--accent-line);
    font-size:9.5px;
}
.collection-kicker::before{content:"";width:5px;height:5px;border-radius:50%;
    background:var(--ac2);box-shadow:0 0 8px var(--pulse);}
.collection-heading h1{
    background:var(--ink-grad);-webkit-background-clip:text;background-clip:text;color:transparent;
}
.collection-count{
    position:relative;
    background:linear-gradient(145deg,var(--accent-soft),rgba(139,124,246,.05));
}
.collection-count::after{
    content:"";position:absolute;inset:-1px;border-radius:21px;padding:1px;
    background:linear-gradient(140deg,var(--accent-line),transparent 40%,var(--accent-line) 90%);
    -webkit-mask:linear-gradient(#fff 0 0) content-box,linear-gradient(#fff 0 0);
    -webkit-mask-composite:xor;mask-composite:exclude;pointer-events:none;
}
.collection-count strong{
    background:var(--ink-grad);-webkit-background-clip:text;background-clip:text;color:transparent;
}

/* — person cards: glass + spherical avatars — */
.person-card{
    background:linear-gradient(160deg,var(--surface-raised),transparent 65%),var(--surface);
    backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);
}
.person-card:hover{box-shadow:0 22px 60px rgba(0,0,0,.2),0 0 0 1px var(--accent-line);}
.avatar{
    position:relative;
    border-radius:16px;
}
.avatar::before{
    content:"";position:absolute;inset:-3px;border-radius:19px;padding:2px;
    background:conic-gradient(from 180deg,var(--ring1),transparent 30%,var(--ring2) 60%,transparent 90%,var(--ring1));
    -webkit-mask:linear-gradient(#fff 0 0) content-box,linear-gradient(#fff 0 0);
    -webkit-mask-composite:xor;mask-composite:exclude;
    animation:spinRing 6s linear infinite;opacity:.8;
}
@keyframes spinRing{to{transform:rotate(360deg);}}
.person-card:hover .avatar::before{animation-duration:2.5s;}
.person-name{
    background:var(--ink-grad);-webkit-background-clip:text;background-clip:text;color:transparent;
    font-weight:800;
}
.person-sub .fresh-count{
    padding:1px 7px;border-radius:6px;background:rgba(34,197,94,.12);
    border:1px solid rgba(34,197,94,.22);position:relative;top:-1px;
}
.person-sub .fresh-count::after{display:none!important;}
.type-chips .chip{
    transition:transform .2s var(--ease),box-shadow .2s;
}
.person-card:hover .type-chips .chip{transform:translateY(-1px);}
.person-share{
    background:var(--accent-soft);
}
.person-share:hover{box-shadow:0 8px 20px rgba(139,124,246,.3);}
.person-arrow{
    font-size:0;
}
.person-arrow::after{
    content:"";width:26px;height:26px;border-radius:8px;
    background:var(--accent-soft);border:1px solid var(--accent-line);
    position:absolute;right:-26px;top:-5px;display:block;
    -webkit-mask:radial-gradient(circle at center,#000 68%,transparent 0);
}
.person-card:hover .person-arrow svg{transform:translateX(-2px);}
.person-arrow svg{transition:transform .25s var(--ease);position:relative;z-index:1;}

/* — detail header — */
.back-bar{
    position:relative;overflow:hidden;
}
.back-bar::before{
    content:"";position:absolute;inset:0;
    background:radial-gradient(circle at 85% 50%,var(--accent-soft),transparent 55%);
    pointer-events:none;
}
.back-bar>*{position:relative;z-index:1;}
.person-header h1{
    background:var(--ink-grad);-webkit-background-clip:text;background-clip:text;color:transparent;
}

/* — file cards — */
.file-card{
    position:relative;overflow:hidden;
    backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);
}
.file-card::after{
    content:"";position:absolute;left:0;top:0;bottom:0;width:3px;
    background:linear-gradient(180deg,var(--accent),transparent);
    opacity:0;transition:.3s;
}
.file-card:hover::after{opacity:.9;}
.file-card:hover{box-shadow:0 16px 44px rgba(0,0,0,.16);}
.file-name{
    background:var(--ink-grad);-webkit-background-clip:text;background-clip:text;color:transparent;
    font-weight:700;
}
.pdf-thumb{
    position:relative;
}
.pdf-thumb::after{
    content:"";position:absolute;top:0;right:0;width:14px;height:14px;
    background:linear-gradient(225deg,rgba(255,255,255,.35),transparent 60%);
    border-radius:0 0 0 10px;
}
.f-action{position:relative;overflow:hidden;}
.f-action::before{
    content:"";position:absolute;inset:0;border-radius:inherit;
    background:radial-gradient(circle at 50% 0%,rgba(255,255,255,.16),transparent 60%);
    opacity:0;transition:.2s;
}
.f-action:hover::before{opacity:1;}
.f-action svg{position:relative;z-index:1;}

/* — new badge fresher look — */
.new-badge{
    background:linear-gradient(135deg,rgba(34,197,94,.22),rgba(34,197,94,.10));
    text-shadow:0 0 12px rgba(74,222,128,.4);
}

/* — announcements with left glow bar — */
.announce-bar{position:relative;overflow:hidden;}
.announce-bar::before{
    content:"";position:absolute;right:0;top:0;bottom:0;width:3px;
    background:currentColor;opacity:.55;
}

/* — modals: floating header icon — */
.modal-icon{
    box-shadow:0 12px 30px rgba(109,93,230,.25),inset 0 1px 0 rgba(255,255,255,.15);
    animation:iconFloat 4s var(--ease) infinite alternate;
}
@keyframes iconFloat{to{transform:translateY(-4px);}}
.submit-btn{
    position:relative;overflow:hidden;
    background:linear-gradient(135deg,#9383f8,#6d5de6 55%,#5443c9);
}
.submit-btn::before{
    content:"";position:absolute;top:0;left:-80%;width:60%;height:100%;
    background:linear-gradient(105deg,transparent,rgba(255,255,255,.25),transparent);
    transform:skewX(-20deg);transition:left .6s var(--ease);
}
.submit-btn:hover::before{left:130%;}

/* — empty state — */
.empty-icon{position:relative;}
.empty-icon::after{
    content:"";position:absolute;inset:-8px;border-radius:24px;
    border:1px dashed var(--accent-line);opacity:.6;
    animation:emptyPulse 3s ease infinite;
}
@keyframes emptyPulse{0%,100%{transform:scale(1);opacity:.6;}50%{transform:scale(1.05);opacity:.25;}}

/* — bell on fire — */
.bell-btn.has-alert svg{
    animation:bellRing 1.6s ease infinite;transform-origin:top center;
}
@keyframes bellRing{
    0%,100%{transform:rotate(0);}
    15%{transform:rotate(12deg);}
    30%{transform:rotate(-10deg);}
    45%{transform:rotate(6deg);}
    60%{transform:rotate(-4deg);}
}
.bell-badge{
    box-shadow:0 0 12px rgba(244,63,94,.6);
    animation:badgeBounce 2s ease infinite;
}
@keyframes badgeBounce{0%,100%{transform:scale(1);}50%{transform:scale(1.12);}}

/* — scrollbars everywhere slim — */
::-webkit-scrollbar{width:5px;}
::-webkit-scrollbar-thumb{border-radius:4px;background:rgba(255,255,255,.12);}
html[data-theme="light"] ::-webkit-scrollbar-thumb{background:rgba(35,29,82,.18);}
::-webkit-scrollbar-thumb:hover{background:var(--accent-line);}

/* — focus visibility — */
a:focus-visible,button:focus-visible,input:focus-visible,select:focus-visible,textarea:focus-visible{
    outline:2px solid var(--accent-line);outline-offset:2px;border-radius:8px;
}

/* — mobile refinements — */
@media(max-width:768px){
    .tabs-track{background:var(--surface-raised);}
    .m-tab{height:34px;}
    .person-card{
        box-shadow:0 4px 18px rgba(0,0,0,.08);
    }
    .avatar::before{inset:-2px;border-radius:17px;}
    .back-bar::before{background:radial-gradient(circle at 90% 30%,var(--accent-soft),transparent 60%);}
}

</style>
</head>
<body>
<div class="app">

<!-- Floating ambient orbs (CSS-only decoration) -->
<i class="ambient-orb o1"></i>
<i class="ambient-orb o2"></i>
<i class="ambient-orb o3"></i>
<i class="ambient-orb o4"></i>

<div class="topbar">
    <div class="topbar-inner">
    <div class="t-right">
        <a class="logo" href="<?= $_SERVER['PHP_SELF'] ?>">
            <div class="logo-icon"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zM6 20V4h7v5h5v11H6z"/></svg></div>
            <span class="brand-copy">
                <span class="logo-text">مخزن PDF</span>
                <span class="logo-subtitle"><?= $MONTH_NAMES_FA[$activeMonthNum] ?> <?= $nowJ['year'] ?></span>
            </span>
        </a>
        <?php if (isAdmin()): ?>
        <span class="badge-admin"><svg width="9" height="9" viewBox="0 0 24 24" fill="#4ade80"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>ادمین</span>
        <?php endif; ?>
    </div>
    <div class="t-left">
        <form method="GET" class="search-form">
            <input type="hidden" name="m" value="<?= htmlspecialchars($activeTab) ?>">
            <div class="search-wrap">
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="جستجو...">
                <svg class="s-svg" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
            </div>
        </form>
        <a class="icon-btn utility-btn" href="#" id="themeBtn" onclick="toggleTheme();return false;" title="تغییر تم">
            <svg class="theme-ico-sun" viewBox="0 0 24 24"><path d="M12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zM2 13h2c.55 0 1-.45 1-1s-.45-1-1-1H2c-.55 0-1 .45-1 1s.45 1 1 1zm18 0h2c.55 0 1-.45 1-1s-.45-1-1-1h-2c-.55 0-1 .45-1 1s.45 1 1 1zM11 2v2c0 .55.45 1 1 1s1-.45 1-1V2c0-.55-.45-1-1-1s-1 .45-1 1zm0 18v2c0 .55.45 1 1 1s1-.45 1-1v-2c0-.55-.45-1-1-1s-1 .45-1 1zM5.99 4.58a.996.996 0 0 0-1.41 0 .996.996 0 0 0 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0s.39-1.03 0-1.41L5.99 4.58zm12.37 12.37a.996.996 0 0 0-1.41 0 .996.996 0 0 0 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0a.996.996 0 0 0 0-1.41l-1.06-1.06zm1.06-10.96a.996.996 0 0 0 0-1.41.996.996 0 0 0-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41s1.03.39 1.41 0l1.06-1.06zM7.05 18.36a.996.996 0 0 0 0-1.41.996.996 0 0 0-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41s1.03.39 1.41 0l1.06-1.06z"/></svg>
            <svg class="theme-ico-moon" viewBox="0 0 24 24"><path d="M12 3a9 9 0 1 0 9 9c0-.46-.04-.92-.1-1.36a5.389 5.389 0 0 1-4.4 2.26 5.403 5.403 0 0 1-3.14-9.8c-.44-.06-.9-.1-1.36-.1z"/></svg>
        </a>
        <a class="icon-btn utility-btn notif-btn" href="#" id="notifBtn" onclick="handleNotifClick();return false;" title="فعال‌سازی اعلان">
            <svg class="notif-ico-on" viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm7-5v-6c0-3.35-2.36-6.15-5.5-6.83V3.5a1.5 1.5 0 0 0-3 0v.67C7.36 4.85 5 7.65 5 11v6l-1.7 1.7c-.32.32-.09.86.36.86h16.68c.45 0 .68-.54.36-.86L19 17z"/><circle cx="18.5" cy="5.5" r="3.2" fill="currentColor" opacity=".55"/></svg>
            <svg class="notif-ico-off" viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/><path d="M9.2 12.3l1.5-1.5 1.6 1.6 3-3 1.5 1.5-4.5 4.5z" fill="var(--bg)"/></svg>
            <span class="notif-dot" id="notifDot" style="display:none"></span>
        </a>
        <a class="icon-btn utility-btn bell-btn <?= $dueCount > 0 ? 'has-alert' : '' ?>" href="#" onclick="openModal('reminderModal');return false;" title="زمان تمدید آنالیز">
            <svg viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
            <?php if ($dueCount > 0): ?><span class="bell-badge"><?= $dueCount ?></span><?php endif; ?>
        </a>
        <?php if (isAdmin()): ?>
        <a class="icon-btn admin-desktop-action" href="#" onclick="document.getElementById('uploadModal').classList.add('open');return false;" title="آپلود">
            <svg viewBox="0 0 24 24"><path d="M9 16h6v-6h4l-7-7-7 7h4v6zm-4 2h14v2H5v-2z"/></svg>
        </a>
        <a class="icon-btn admin-desktop-action" href="#" onclick="document.getElementById('announceModal').classList.add('open');return false;" title="اطلاعیه">
            <svg viewBox="0 0 24 24"><path d="M18 11v2h4v-2h-4zm-2 6.61c.96.71 2.21 1.65 3.2 2.39.4-.53.8-1.07 1.2-1.6-.99-.74-2.24-1.68-3.2-2.4-.4.54-.8 1.08-1.2 1.61zM20.4 5.6c-.4-.53-.8-1.07-1.2-1.6-.99.74-2.24 1.68-3.2 2.4.4.53.8 1.07 1.2 1.6.96-.72 2.21-1.65 3.2-2.4zM4 9c-1.1 0-2 .9-2 2v2c0 1.1.9 2 2 2h1v4h2v-4h1l5 3V6L8 9H4z"/></svg>
        </a>
        <a class="icon-btn admin-desktop-action" href="#" onclick="openModal('pushModal');return false;" title="ارسال اعلان">
            <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
        </a>
        <a class="icon-btn admin-desktop-action" href="?action=logout" title="خروج">
            <svg viewBox="0 0 24 24"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
        </a>
        <a class="icon-btn mobile-admin-trigger" href="#" onclick="openModal('mobileAdminModal');return false;" title="منوی مدیریت">
            <svg viewBox="0 0 24 24"><path d="M19.43 12.98c.04-.32.07-.65.07-.98s-.03-.66-.08-.98l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.37-.31-.6-.22l-2.49 1a7.14 7.14 0 0 0-1.69-.98L14.5 2.42A.488.488 0 0 0 14 2h-4c-.25 0-.46.18-.5.42L9.12 5.07c-.61.25-1.17.59-1.69.98l-2.49-1a.493.493 0 0 0-.6.22l-2 3.46c-.13.22-.07.49.12.64l2.11 1.65c-.04.32-.08.66-.08.98s.03.66.08.98l-2.11 1.65c-.19.15-.24.42-.12.64l2 3.46c.12.22.37.31.6.22l2.49-1c.52.4 1.08.73 1.69.98l.38 2.65c.04.24.25.42.5.42h4c.25 0 .46-.18.5-.42l.38-2.65c.61-.25 1.17-.58 1.69-.98l2.49 1c.23.08.48 0 .6-.22l2-3.46a.506.506 0 0 0-.12-.64l-2.11-1.65zM12 15.5A3.5 3.5 0 1 1 12 8a3.5 3.5 0 0 1 0 7.5z"/></svg>
        </a>
        <?php else: ?>
        <a class="icon-btn" href="#" onclick="document.getElementById('loginModal').classList.add('open');return false;" title="ورود ادمین">
            <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
        </a>
        <?php endif; ?>
    </div>
    </div>
</div>

<div class="month-tabs">
    <div class="tabs-track">
    <?php $qp = $search ? '&q=' . urlencode($search) : ''; ?>
    <?php foreach ($TAB_ORDER as $mi):
        $md = $MONTH_NAMES[$mi];
        $pc = isset($data[$md]) ? count($data[$md]) : 0;
    ?>
    <a href="?m=<?= $md . $qp ?>" class="m-tab <?= $activeTab === $md ? 'active' : '' ?>"><?= $MONTH_NAMES_FA[$mi] ?><?php if ($pc > 0): ?> <span class="tab-badge"><?= $pc ?></span><?php endif; ?></a>
    <?php endforeach; ?>
    </div>
</div>

<div class="content">
<div class="content-inner">

    <?php if ($message): ?>
    <div class="alert <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if (!empty($announcements)):
        $aIcons = [
            'info' => '<path d="M11 7h2v2h-2zm0 4h2v6h-2zm1-9C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>',
            'warning' => '<path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>',
            'success' => '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>',
            'danger' => '<path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/>'
        ];
        $aTitles = ['info'=>'اطلاعیه','warning'=>'هشدار','success'=>'خبر خوب','danger'=>'مهم'];
    ?>
    <div class="announcements-wrap">
        <?php foreach ($announcements as $ann): $at = isset($ann['type']) ? $ann['type'] : 'info'; ?>
        <div class="announce-bar <?= htmlspecialchars($at) ?>">
            <div class="announce-icon"><svg viewBox="0 0 24 24"><?= isset($aIcons[$at]) ? $aIcons[$at] : $aIcons['info'] ?></svg></div>
            <div class="announce-content">
                <div class="a-title"><?= isset($aTitles[$at]) ? $aTitles[$at] : 'اطلاعیه' ?></div>
                <div class="a-text"><?= nl2br(htmlspecialchars(isset($ann['text']) ? $ann['text'] : '')) ?></div>
            </div>
            <?php if (isAdmin()): ?>
            <a class="announce-close" href="?action=delannounce&id=<?= urlencode(isset($ann['id']) ? $ann['id'] : '') ?>" onclick="return confirm('این اطلاعیه حذف شود؟');">
                <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($search): ?>
    <div class="search-result">نتیجه جستجو برای «<?= htmlspecialchars($search) ?>» در <?= $MONTH_NAMES_FA[$activeMonthNum] ?>: <?= count($monthPersons) ?> نفر</div>
    <?php endif; ?>

    <?php if ($currentPerson !== null):
        $pf = $currentPerson['files'];
        $initial = mb_substr($currentPerson['name'], 0, 1, 'UTF-8');
        $pac = avatarColor($activeUser);
        $pShareKeys = [];
        foreach ($pf as $sf) {
            if ($sf['type'] === 'diet' || $sf['type'] === 'analyse') $pShareKeys[] = $sf['key'];
        }
        $pShareJson = htmlspecialchars(json_encode($pShareKeys, JSON_UNESCAPED_UNICODE), ENT_QUOTES);
        $pNewCount = 0;
        foreach ($pf as $nf) if (isToday($nf['jalali'])) $pNewCount++;
        $pNewestTs = isset($lastUploads[$activeTab][$activeUser]) ? $lastUploads[$activeTab][$activeUser] : 0;
    ?>
    <div class="back-bar">
        <a class="back-btn" href="?m=<?= urlencode($activeTab) . $qp ?>">
            <svg viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
            بازگشت
        </a>
        <div class="person-header">
            <div class="avatar" style="background:linear-gradient(135deg,<?= $pac[0] ?>,<?= $pac[1] ?>)"><?= htmlspecialchars($initial) ?></div>
            <div style="min-width:0">
                <h1><?= htmlspecialchars($currentPerson['name']) ?>
                    <?php if ($pNewCount > 0): ?><span class="new-badge">NEW</span><?php endif; ?>
                </h1>
                <div class="ph-sub">
                    <?= $MONTH_NAMES_FA[$activeMonthNum] ?> • <?= count($pf) ?> فایل<?php if ($pNewCount > 0): ?> • <span style="color:#4ade80;font-weight:700"><?= $pNewCount ?> جدید</span><?php endif; ?>
                    <?php if ($pNewestTs > 0): $nj2 = getJalali($pNewestTs); ?><br>
                        <span style="opacity:.7">آخرین آپلود: <?= $nj2['year'] . '/' . pad($nj2['month']) . '/' . pad($nj2['day']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php if (isAdmin()): ?>
        <a class="back-btn" style="color:var(--ac2)" href="#" onclick="openEditPerson('<?= htmlspecialchars(addslashes($currentPerson['name'])) ?>');return false;">
            <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
            ویرایش
        </a>
        <a class="back-btn" style="color:var(--dn)" href="?action=delperson&m=<?= urlencode($activeTab) ?>&u=<?= urlencode($activeUser) ?>" onclick="return confirm('کل پوشه این کاربر حذف شود؟');">
            <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
            حذف
        </a>
        <?php endif; ?>
    </div>

    <div class="file-grid">
        <?php foreach ($pf as $f):
            $ds = $f['jalali']['year'] . '/' . pad($f['jalali']['month']) . '/' . pad($f['jalali']['day']);
            $tp = $f['type'];
            $isNew = isToday($f['jalali']);
            $tc = $TYPE_COLORS[$tp] ?? 'gray';
        ?>
        <div class="file-card" style="--i:<?= isset($fileIdx) ? ++$fileIdx : ($fileIdx = 1) ?>">
            <div class="pdf-thumb <?= $tp ?>">
                <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zM6 20V4h7v5h5v11H6z"/></svg>
                <span class="ext">PDF</span>
            </div>
            <div class="file-info">
                <div class="file-name">
                    <?= htmlspecialchars($f['name']) ?>
                    <?php if ($isNew): ?><span class="new-badge">NEW</span><?php endif; ?>
                </div>
                <div class="file-meta">
                    <span class="chip <?= $tc ?>"><?= $TYPE_FA[$tp] ?></span>
                    <span><svg viewBox="0 0 24 24"><path d="M2 20h20v-4H2v4zm2-3h2v2H4v-2zM2 4v4h20V4H2zm4 3H4V5h2v2zm-4 7h20v-4H2v4zm2-3h2v2H4v-2z"/></svg><?= fmtSize($f['size']) ?></span>
                    <span><svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg><?= $ds ?></span>
                </div>
            </div>
            <div class="file-btns">
                <a class="f-action view" href="#" onclick="viewPDF('<?= htmlspecialchars(addslashes($f['key'])) ?>','<?= htmlspecialchars(addslashes($f['name'])) ?>');return false;" title="مشاهده">
                    <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                </a>
                <a class="f-action share" href="#" onclick="shareFile('<?= htmlspecialchars(addslashes($f['key'])) ?>','<?= htmlspecialchars(addslashes($f['name'])) ?>');return false;" title="اشتراک">
                    <svg viewBox="0 0 24 24"><path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92 1.61 0 2.92-1.31 2.92-2.92s-1.31-2.92-2.92-2.92z"/></svg>
                </a>
                <a class="f-action down" href="?action=download&key=<?= urlencode($f['key']) ?>" title="دانلود">
                    <svg viewBox="0 0 24 24"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
                </a>
                <?php if (isAdmin()): ?>
                <a class="f-action edit" href="#" onclick="openEditFile('<?= htmlspecialchars(addslashes($f['key'])) ?>','<?= htmlspecialchars(addslashes($f['name'])) ?>','<?= $tp ?>');return false;" title="ویرایش">
                    <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                </a>
                <a class="f-action trash" href="#" onclick="askDelete('<?= htmlspecialchars(addslashes($f['key'])) ?>','<?= htmlspecialchars(addslashes($f['name'])) ?>');return false;" title="حذف">
                    <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php elseif (empty($monthPersons)): ?>
    <div class="empty-box">
        <div class="empty-icon"><svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg></div>
        <h3><?= $search ? 'نتیجه‌ای یافت نشد' : 'کاربری در ' . $MONTH_NAMES_FA[$activeMonthNum] . ' وجود ندارد' ?></h3>
        <p><?= $search ? 'با عبارت دیگری جستجو کنید' : 'برای شروع فایل‌ها را آپلود کنید' ?></p>
    </div>

    <?php else: ?>
    <div class="collection-heading">
        <div class="collection-copy">
            <span class="collection-kicker">آرشیو ماه</span>
            <h1>پرونده‌های <?= $MONTH_NAMES_FA[$activeMonthNum] ?></h1>
            <p>افراد بر اساس جدیدترین فایل مرتب شده‌اند.</p>
        </div>
        <div class="collection-count">
            <strong><?= count($monthPersons) ?></strong>
            <span>نفر</span>
        </div>
    </div>
    <div class="person-grid">
        <?php foreach ($monthPersons as $slug => $p):
            $types = [];
            $hasNew = false;
            $newCount = 0;
            foreach ($p['files'] as $f) {
                $types[$f['type']] = isset($types[$f['type']]) ? $types[$f['type']] + 1 : 1;
                if (isToday($f['jalali'])) { $hasNew = true; $newCount++; }
            }
            $initial = mb_substr($p['name'], 0, 1, 'UTF-8');
            $ac = avatarColor($slug);
            $shareKeys = [];
            foreach ($p['files'] as $sf) {
                if ($sf['type'] === 'diet' || $sf['type'] === 'analyse') $shareKeys[] = $sf['key'];
            }
            $shareJson = htmlspecialchars(json_encode($shareKeys, JSON_UNESCAPED_UNICODE), ENT_QUOTES);
            $personLastTs = isset($lastUploads[$activeTab][$slug]) ? (int)$lastUploads[$activeTab][$slug] : 0;
            $personLastText = '';
            if ($personLastTs > 0) {
                $personLastJ = getJalali($personLastTs);
                $personLastText = $personLastJ['year'] . '/' . pad($personLastJ['month']) . '/' . pad($personLastJ['day']);
            }
        ?>
        <a class="person-card" style="--i:<?= isset($cardIdx) ? ++$cardIdx : ($cardIdx = 1) ?>" href="?m=<?= urlencode($activeTab) ?>&u=<?= urlencode($slug) . $qp ?>">
            <div class="person-top">
                <div class="avatar" style="background:linear-gradient(135deg,<?= $ac[0] ?>,<?= $ac[1] ?>)"><?= htmlspecialchars($initial) ?></div>
                <div style="min-width:0;flex:1">
                    <div class="person-name">
                        <?= htmlspecialchars($p['name']) ?>
                        <?php if ($hasNew): ?><span class="new-badge">NEW</span><?php endif; ?>
                    </div>
                    <div class="person-sub">
                        <span><?= count($p['files']) ?> فایل</span>
                        <?php if ($personLastText !== ''): ?><span>آخرین آپلود <?= $personLastText ?></span><?php endif; ?>
                        <?php if ($hasNew): ?><span class="fresh-count"><?= $newCount ?> جدید</span><?php endif; ?>
                    </div>
                </div>
                <?php if (count($shareKeys) > 0): ?>
                <span class="person-share" title="ارسال رژیم و آنالیز"
                      onclick="event.preventDefault();event.stopPropagation();sharePerson(this,'<?= $shareJson ?>','<?= htmlspecialchars(addslashes($p['name'])) ?>');">
                    <svg viewBox="0 0 24 24"><path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92 1.61 0 2.92-1.31 2.92-2.92s-1.31-2.92-2.92-2.92z"/></svg>
                </span>
                <?php endif; ?>
            </div>
            <div class="type-chips">
                <?php foreach ($TYPE_ORDER as $t): if (!isset($types[$t])) continue; ?>
                <span class="chip <?= $TYPE_COLORS[$t] ?>"><?= $TYPE_FA[$t] ?> <?= $types[$t] > 1 ? $types[$t] : '' ?></span>
                <?php endforeach; ?>
            </div>
            <div class="person-arrow"><svg viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg></div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>
</div>
</div>

<!-- Login -->
<div class="overlay" id="loginModal">
<div class="modal">
    <div class="modal-top">
        <div class="modal-icon"><svg viewBox="0 0 24 24" fill="#a78bfa"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM12 17c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zM15.1 8H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg></div>
        <h3>ورود ادمین</h3>
        <p>برای آپلود و مدیریت فایل‌ها وارد شوید</p>
    </div>
    <div class="modal-body">
        <form method="POST">
            <input type="hidden" name="action" value="login">
            <label class="label">نام کاربری</label>
            <input class="field" type="text" name="user" placeholder="admin">
            <label class="label">رمز عبور</label>
            <input class="field" type="password" name="pass" placeholder="رمز عبور">
            <button class="submit-btn" type="submit">ورود</button>
        </form>
    </div>
</div>
</div>

<?php if (isAdmin()): ?>
<!-- Upload -->
<div class="overlay" id="uploadModal">
<div class="modal">
    <div class="modal-top">
        <div class="modal-icon"><svg viewBox="0 0 24 24" fill="#a78bfa"><path d="M9 16h6v-6h4l-7-7-7 7h4v6zm-4 2h14v2H5v-2z"/></svg></div>
        <h3>آپلود فایل</h3>
        <p>پوشه از نام فایل ساخته می‌شود</p>
    </div>
    <div class="modal-body">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload">
            <div class="drop-zone" id="dropZone">
                <svg viewBox="0 0 24 24"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/></svg>
                <p>فایل‌ها را بکشید یا کلیک کنید</p>
                <p class="hint">مثال: علی رضایی رژیم.pdf</p>
                <input type="file" name="files[]" id="fileInput" accept=".pdf" multiple>
            </div>
            <div id="filePreview"></div>
            <label class="label">ماه</label>
            <select class="field" name="month">
                <option value="0">ماه جاری (<?= $MONTH_NAMES_FA[$nowJ['month']] ?>)</option>
                <?php foreach ($TAB_ORDER as $mi): ?>
                <option value="<?= $mi ?>"><?= $MONTH_NAMES_FA[$mi] ?></option>
                <?php endforeach; ?>
            </select>
            <button class="submit-btn" type="submit">آپلود</button>
        </form>
    </div>
</div>
</div>

<!-- Announcements -->
<div class="overlay" id="announceModal">
<div class="modal">
    <div class="modal-top">
        <div class="modal-icon"><svg viewBox="0 0 24 24" fill="#a78bfa"><path d="M18 11v2h4v-2h-4zm-2 6.61c.96.71 2.21 1.65 3.2 2.39.4-.53.8-1.07 1.2-1.6-.99-.74-2.24-1.68-3.2-2.4-.4.54-.8 1.08-1.2 1.61zM20.4 5.6c-.4-.53-.8-1.07-1.2-1.6-.99.74-2.24 1.68-3.2 2.4.4.53.8 1.07 1.2 1.6.96-.72 2.21-1.65 3.2-2.4zM4 9c-1.1 0-2 .9-2 2v2c0 1.1.9 2 2 2h1v4h2v-4h1l5 3V6L8 9H4z"/></svg></div>
        <h3>مدیریت اطلاعیه‌ها</h3>
        <p>می‌توانید چند اطلاعیه همزمان اضافه کنید</p>
    </div>
    <div class="modal-body">
        <form method="POST">
            <input type="hidden" name="action" value="add_announce">
            <input type="hidden" name="announce_type" id="announceType" value="info">
            <label class="label">نوع</label>
            <div class="type-picker">
                <div class="type-opt info sel" data-type="info">اطلاعیه</div>
                <div class="type-opt warning" data-type="warning">هشدار</div>
                <div class="type-opt success" data-type="success">خبر خوب</div>
                <div class="type-opt danger" data-type="danger">مهم</div>
            </div>
            <label class="label">متن</label>
            <textarea class="field-area" name="announce_text" placeholder="متن اطلاعیه..." required></textarea>
            <button class="submit-btn" type="submit">➕ افزودن اطلاعیه</button>
        </form>
        <div style="margin-top:18px;padding-top:14px;border-top:1px solid var(--gb);">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                <span style="font-size:12px;font-weight:700;color:var(--strong);">اطلاعیه‌های فعال (<?= count($announcements) ?>)</span>
                <?php if (!empty($announcements)): ?>
                <a href="?action=clearannounces" onclick="return confirm('همه پاک شوند؟');" style="font-size:11px;color:var(--dn);text-decoration:none;font-weight:600;">پاک‌کردن همه</a>
                <?php endif; ?>
            </div>
            <?php if (empty($announcements)): ?>
            <div style="text-align:center;padding:14px;color:var(--td2);font-size:12px;background:var(--ctl);border-radius:10px;">اطلاعیه‌ای ثبت نشده</div>
            <?php else: ?>
            <div class="ann-list">
                <?php $atn = ['info'=>'اطلاعیه','warning'=>'هشدار','success'=>'خبر خوب','danger'=>'مهم'];
                foreach ($announcements as $a): $t = isset($a['type']) ? $a['type'] : 'info'; ?>
                <div class="ann-item">
                    <div class="ai-text">
                        <span class="ai-tag <?= $t ?>"><?= isset($atn[$t]) ? $atn[$t] : 'اطلاعیه' ?></span>
                        <div><?= nl2br(htmlspecialchars(isset($a['text']) ? $a['text'] : '')) ?></div>
                    </div>
                    <a class="ai-del" href="?action=delannounce&id=<?= urlencode(isset($a['id']) ? $a['id'] : '') ?>" onclick="return confirm('حذف شود؟');">
                        <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>
<?php endif; ?>

<?php if (isAdmin()): ?>
<!-- Push Notifications -->
<div class="overlay" id="pushModal">
<div class="modal">
    <div class="sheet-handle"></div>
    <div class="modal-top">
        <div class="modal-icon" style="background:rgba(34,197,94,0.12)">
            <svg viewBox="0 0 24 24" fill="#4ade80"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
        </div>
        <h3>ارسال اعلان</h3>
        <p>پیام برای همه کاربرانی که اعلان را فعال کرده‌اند</p>
    </div>
    <div class="modal-body">
        <?php
            $pushSubs = listPushSubscriptions();
            $pushCount = count($pushSubs);
            $vapidOk = getVapidKeys();
        ?>
        <div class="notif-status">
            <span class="ns-ic">
                <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
            </span>
            <div>
                <div class="ns-n"><?= number_format($pushCount) ?></div>
                <div class="ns-l">دستگاه پوش فعال (Web Push) • <?= number_format($notifDevices) ?> مشترک ساده</div>
            </div>
            <div style="margin-right:auto">
                <?php if (!empty($vapidOk['ok'])): ?>
                <span style="font-size:9px;font-weight:800;padding:4px 9px;border-radius:8px;background:rgba(34,197,94,.14);border:1px solid rgba(34,197,94,.24);color:#4ade80;">پوش واقعی فعال</span>
                <?php else: ?>
                <span style="font-size:9px;font-weight:800;padding:4px 9px;border-radius:8px;background:rgba(244,63,94,.14);border:1px solid rgba(244,63,94,.24);color:#fb7185;">VAPID ناموفق</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="perm-hint" style="background:var(--accent-soft);border-color:var(--accent-line);color:var(--ac2);">
            <svg viewBox="0 0 24 24"><path d="M11 7h2v2h-2zm0 4h2v6h-2zm1-9C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg>
            <div>برای <b>آیفون</b>: کاربر باید اول سایت را با <b>Add to Home Screen</b> نصب کند و از داخل اپ، زنگ اعلان را بزند؛ بعد از آن پوش حتی با بسته بودن اپ می‌رسد.</div>
        </div>

        <?php if ($notifDevices === 0): ?>
        <div class="perm-hint">
            <svg viewBox="0 0 24 24"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
            هنوز کسی اعلان را فعال نکرده است. کاربران باید روی آیکن زنگ سبز در بالای صفحه بزنند.
        </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="send_notif">
            <label class="label">عنوان اعلان</label>
            <input class="field" type="text" name="n_title" placeholder="مثال: فایل جدید آپلود شد" required>
            <label class="label">متن پیام</label>
            <textarea class="field-area" name="n_body" placeholder="جزئیات پیام..." style="min-height:70px"></textarea>
            <label class="label">لینک مقصد (اختیاری)</label>
            <input class="field" type="text" name="n_url" placeholder="مثال: ?m=mordad" style="direction:ltr;text-align:left">
            <button class="submit-btn" type="submit" style="background:linear-gradient(135deg,#34d399,#16a34a);box-shadow:0 10px 25px rgba(34,197,94,.26)">
                📣 ارسال اعلان
            </button>
        </form>

        <div style="margin-top:18px;padding-top:14px;border-top:1px solid var(--gb);">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:12px;font-weight:700;color:var(--strong);">تاریخچه (<?= count($notifHistory) ?>)</span>
                <?php if (count($notifHistory) > 0): ?>
                <a href="?action=clear_notifs" onclick="return confirm('کل تاریخچه پاک شود؟');" style="font-size:11px;color:var(--dn);text-decoration:none;font-weight:600;">پاک‌کردن همه</a>
                <?php endif; ?>
            </div>
            <?php if (empty($notifHistory)): ?>
            <div style="text-align:center;padding:14px;color:var(--td2);font-size:12px;background:var(--ctl);border-radius:10px;margin-top:9px;">
                هنوز اعلانی ارسال نشده
            </div>
            <?php else: ?>
            <div class="notif-hist">
                <?php foreach ($notifHistory as $nm):
                    $nj = getJalali((int)$nm['time']);
                    $nd = $nj['year'] . '/' . pad($nj['month']) . '/' . pad($nj['day']) . ' • ' . date('H:i', (int)$nm['time']);
                ?>
                <div class="nh-item">
                    <div class="nh-body">
                        <div class="nh-t"><?= htmlspecialchars($nm['title']) ?></div>
                        <?php if (!empty($nm['body'])): ?>
                        <div class="nh-b"><?= nl2br(htmlspecialchars($nm['body'])) ?></div>
                        <?php endif; ?>
                        <div class="nh-d"><?= $nd ?></div>
                    </div>
                    <a class="nh-del" href="?action=del_notif&id=<?= urlencode($nm['id']) ?>" onclick="return confirm('حذف شود؟');">
                        <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<!-- Compact admin menu for mobile -->
<div class="overlay" id="mobileAdminModal">
<div class="modal admin-sheet">
    <div class="sheet-handle"></div>
    <div class="modal-top compact-modal-top">
        <div class="modal-icon"><svg viewBox="0 0 24 24" fill="#a78bfa"><path d="M19.43 12.98c.04-.32.07-.65.07-.98s-.03-.66-.08-.98l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.37-.31-.6-.22l-2.49 1a7.14 7.14 0 0 0-1.69-.98L14.5 2.42A.488.488 0 0 0 14 2h-4c-.25 0-.46.18-.5.42L9.12 5.07c-.61.25-1.17.59-1.69.98l-2.49-1a.493.493 0 0 0-.6.22l-2 3.46c-.13.22-.07.49.12.64l2.11 1.65c-.04.32-.08.66-.08.98s.03.66.08.98l-2.11 1.65c-.19.15-.24.42-.12.64l2 3.46c.12.22.37.31.6.22l2.49-1c.52.4 1.08.73 1.69.98l.38 2.65c.04.24.25.42.5.42h4c.25 0 .46-.18.5-.42l.38-2.65c.61-.25 1.17-.58 1.69-.98l2.49 1c.23.08.48 0 .6-.22l2-3.46a.506.506 0 0 0-.12-.64l-2.11-1.65zM12 15.5A3.5 3.5 0 1 1 12 8a3.5 3.5 0 0 1 0 7.5z"/></svg></div>
        <h3>مدیریت مخزن</h3>
        <p>عملیات مورد نظر را انتخاب کنید</p>
    </div>
    <div class="modal-body">
        <div class="mobile-admin-grid">
            <button type="button" onclick="openAdminTarget('uploadModal')">
                <span class="mobile-admin-icon upload-tone"><svg viewBox="0 0 24 24"><path d="M9 16h6v-6h4l-7-7-7 7h4v6zm-4 2h14v2H5v-2z"/></svg></span>
                <span><strong>آپلود فایل</strong><small>افزودن چند PDF</small></span>
            </button>
            <button type="button" onclick="openAdminTarget('announceModal')">
                <span class="mobile-admin-icon announce-tone"><svg viewBox="0 0 24 24"><path d="M18 11v2h4v-2h-4zM4 9c-1.1 0-2 .9-2 2v2c0 1.1.9 2 2 2h1v4h2v-4h1l5 3V6L8 9H4z"/></svg></span>
                <span><strong>اطلاعیه‌ها</strong><small>ثبت و مدیریت پیام‌ها</small></span>
            </button>
            <button type="button" onclick="openAdminTarget('pushModal')">
                <span class="mobile-admin-icon push-tone"><svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg></span>
                <span><strong>ارسال اعلان</strong><small>پوش به همه کاربران</small></span>
            </button>
            <a href="?action=logout" class="logout-mobile-action">
                <span class="mobile-admin-icon logout-tone"><svg viewBox="0 0 24 24"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg></span>
                <span><strong>خروج</strong><small>پایان نشست مدیریت</small></span>
            </a>
        </div>
    </div>
</div>
</div>
<?php endif; ?>

<!-- Renewal Dashboard -->
<div class="overlay" id="reminderModal">
<div class="modal wide">
    <div class="modal-top">
        <div class="modal-icon" style="background:rgba(245,158,11,0.12)">
            <svg viewBox="0 0 24 24" fill="#fbbf24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
        </div>
        <h3>زمان تمدید آنالیز</h3>
        <p>
            <?php if ($dueCount > 0): ?>
                <span style="color:#fcd34d;font-weight:700"><?= $dueCount ?> نفر موعدشان رسیده</span>
            <?php else: ?>
                در حال حاضر کسی در بازه هشدار نیست
            <?php endif; ?>
        </p>
    </div>
    <div class="modal-body">
        <?php
        $renderRow = function($r, $variant) {
            global $MONTH_NAMES_FA;
            $rc = avatarColor($r['slug']);
            $ri = mb_substr($r['name'], 0, 1, 'UTF-8');
            $rd = $r['jalali']['year'] . '/' . pad($r['jalali']['month']) . '/' . pad($r['jalali']['day']);
            $dj = $r['dueJalali'];
            $dueStr = isset($dj['year']) ? $dj['year'] . '/' . pad($dj['month']) . '/' . pad($dj['day']) : '';
            $dueMonthFa = isset($dj['month'], $MONTH_NAMES_FA[$dj['month']]) ? $MONTH_NAMES_FA[$dj['month']] : '';
            ob_start(); ?>
            <div class="rem-item <?= $variant ?>">
                <div class="rem-head">
                    <div class="rem-avatar" style="background:linear-gradient(135deg,<?= $rc[0] ?>,<?= $rc[1] ?>)"><?= htmlspecialchars($ri) ?></div>
                    <div class="rem-info">
                        <div class="rem-name"><?= htmlspecialchars($r['name']) ?></div>
                        <div class="rem-sub">آپلود: <?= $rd ?> • تمدید: <?= $dueStr ?> (<?= $dueMonthFa ?>)</div>
                    </div>
                    <?php if ($variant === 'active'): ?>
                        <span class="rem-left">روز <?= $r['dayInWin'] ?> از ۳</span>
                    <?php elseif ($variant === 'old'): ?>
                        <span class="rem-left" style="background:rgba(255,255,255,0.06);color:var(--td);">منقضی</span>
                    <?php else: ?>
                        <span class="rem-left" style="background:rgba(124,110,240,0.14);color:#c4b5fd;"><?= $r['daysToDue'] ?> روز مانده</span>
                    <?php endif; ?>
                </div>
                <div class="rem-acts">
                    <a class="rem-btn go" href="?m=<?= urlencode($r['month']) ?>&u=<?= urlencode($r['slug']) ?>">مشاهده</a>
                    <?php if (isAdmin() && $variant !== 'upcoming'): ?>
                    <a class="rem-btn ok" href="?action=renew&m=<?= urlencode($r['month']) ?>&u=<?= urlencode($r['slug']) ?>">تمدید</a>
                    <?php endif; ?>
                    <?php if (isAdmin() && $variant === 'active'): ?>
                    <a class="rem-btn no" href="?action=dismiss&m=<?= urlencode($r['month']) ?>&u=<?= urlencode($r['slug']) ?>">بستن</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php return ob_get_clean();
        };

        $activeList = array_filter($allRenewals, function($x) { return $x['isActive']; });
        $expiredList = array_filter($allRenewals, function($x) { return $x['isExpired']; });
        $upcomingList = array_filter($allRenewals, function($x) { return !$x['isActive'] && !$x['isExpired']; });
        $passedCount = count($activeList) + count($expiredList);
        ?>

        <?php if ($passedCount === 0): ?>
        <div style="text-align:center;padding:22px 16px;color:var(--td2);font-size:12.5px;background:var(--ctl);border-radius:12px;">
            ✅ هیچ‌کس از <?= RENEW_DAYS ?> روز عبور نکرده است
        </div>
        <?php endif; ?>

        <?php if (!empty($activeList)): ?>
        <div class="rem-section">🔔 زمان تمدید رسیده (<?= count($activeList) ?>)</div>
        <div class="rem-list">
            <?php foreach ($activeList as $r) echo $renderRow($r, 'active'); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($upcomingList)): ?>
        <div class="upcoming-toggle" onclick="document.getElementById('upcomingBox').classList.toggle('show');this.classList.toggle('open');">
            <span style="color:#a78bfa;font-weight:800;">🗓 هنوز نرسیده (<?= count($upcomingList) ?>)</span>
            <svg class="tg-arrow" viewBox="0 0 24 24"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>
        </div>
        <div class="rem-list collapsible" id="upcomingBox">
            <?php foreach ($upcomingList as $r) echo $renderRow($r, 'upcoming'); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($expiredList)): ?>
        <div class="rem-section" style="color:var(--td);">⏳ گذشته از موعد (<?= count($expiredList) ?>)</div>
        <div class="rem-list">
            <?php foreach ($expiredList as $r) echo $renderRow($r, 'old'); ?>
        </div>
        <?php endif; ?>

        <div style="font-size:10.5px;color:var(--td2);margin-top:14px;line-height:1.8;text-align:center;">
            💡 هشدار یک ماه شمسی پس از آخرین آپلود فعال می‌شود<br>
            مثلاً ۱۴ مرداد → هشدار در ۱۴، ۱۵، ۱۶ شهریور
        </div>
    </div>
</div>
</div>

<?php if (isAdmin() && $currentPerson !== null): ?>
<!-- Edit Person -->
<div class="overlay" id="editPersonModal">
<div class="modal">
    <div class="modal-top">
        <div class="modal-icon" style="background:rgba(245,158,11,0.1)"><svg viewBox="0 0 24 24" fill="#fbbf24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></div>
        <h3>ویرایش کاربر</h3>
        <p>نام نمایشی یا نام پوشه را تغییر دهید</p>
    </div>
    <div class="modal-body">
        <form method="POST">
            <input type="hidden" name="action" value="rename_person">
            <input type="hidden" name="m" value="<?= htmlspecialchars($activeTab) ?>">
            <input type="hidden" name="u" value="<?= htmlspecialchars($activeUser) ?>">
            <label class="label">نام نمایشی (در name.txt ذخیره می‌شود)</label>
            <input class="field" type="text" name="new_name" id="editPersonName" value="<?= htmlspecialchars($currentPerson['name']) ?>" required placeholder="مثال: سارا احمدی">
            <label class="label">نام پوشه (انگلیسی)</label>
            <input class="field" type="text" name="new_slug" id="editPersonSlug" value="<?= htmlspecialchars($activeUser) ?>"
                   style="direction:ltr;text-align:left" placeholder="مثال: sara_ahmadi">
            <div style="font-size:10.5px;color:var(--td2);margin-bottom:12px;line-height:1.8;">
                📁 upload/<?= htmlspecialchars($activeTab) ?>/<b style="color:var(--ac2);direction:ltr;display:inline-block" id="slugPreview"><?= htmlspecialchars($activeUser) ?></b>/<b style="color:#4ade80">name.txt</b>
            </div>
            <button class="submit-btn" type="submit">ذخیره تغییرات</button>
        </form>
        <form method="POST" style="margin-top:16px;padding-top:14px;border-top:1px solid var(--gb);">
            <input type="hidden" name="action" value="move_person">
            <input type="hidden" name="m" value="<?= htmlspecialchars($activeTab) ?>">
            <input type="hidden" name="u" value="<?= htmlspecialchars($activeUser) ?>">
            <label class="label">انتقال به ماه دیگر</label>
            <select class="field" name="to_month">
                <?php foreach ($TAB_ORDER as $mi): ?>
                <option value="<?= $mi ?>" <?= $MONTH_NAMES[$mi] === $activeTab ? 'selected' : '' ?>><?= $MONTH_NAMES_FA[$mi] ?></option>
                <?php endforeach; ?>
            </select>
            <button class="submit-btn" type="submit" style="background:linear-gradient(135deg,#38bdf8,#0369a1);box-shadow:0 3px 15px rgba(56,189,248,0.25)">انتقال کاربر</button>
        </form>
    </div>
</div>
</div>

<!-- Edit File -->
<div class="overlay" id="editFileModal">
<div class="modal">
    <div class="modal-top">
        <div class="modal-icon" style="background:rgba(245,158,11,0.1)"><svg viewBox="0 0 24 24" fill="#fbbf24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></div>
        <h3>ویرایش فایل</h3>
        <p>نام یا نوع فایل را تغییر دهید</p>
    </div>
    <div class="modal-body">
        <form method="POST">
            <input type="hidden" name="action" value="rename_file">
            <input type="hidden" name="key" id="editFileKey">
            <label class="label">نام فایل</label>
            <input class="field" type="text" name="new_filename" id="editFileName" required>
            <button class="submit-btn" type="submit">ذخیره نام</button>
        </form>
        <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--gb);">
            <label class="label">نوع فایل</label>
            <div class="ftype-picker">
                <?php foreach ($TYPE_ORDER as $t): ?>
                <a class="ftype-opt <?= $t ?>" id="ft_<?= $t ?>" href="#"><?= $TYPE_FA[$t] ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
</div>
<?php endif; ?>

<a class="back-btn" id="confirmDelete" style="display:none"></a>

<!-- Delete -->
<div class="overlay" id="deleteModal">
<div class="modal">
    <div class="modal-top">
        <div class="modal-icon" style="background:rgba(244,63,94,0.1)"><svg viewBox="0 0 24 24" fill="#f43f5e"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg></div>
        <h3>حذف فایل</h3>
        <p id="deleteText">مطمئن هستید؟</p>
    </div>
    <div class="modal-actions">
        <button onclick="document.getElementById('deleteModal').classList.remove('open')">انصراف</button>
        <a href="#" class="danger-btn" id="deleteLink">حذف</a>
    </div>
</div>
</div>



<!-- PDF Viewer -->
<div class="pdf-viewer-overlay" id="pdfViewer">
    <div class="pdf-viewer-bar">
        <div class="pvb-btn" onclick="closePdfViewer()"><svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></div>
        <span class="pvb-name" id="pdfViewerName"></span>
        <a class="pvb-btn" id="pdfViewerDownload" href="#" title="دانلود"><svg viewBox="0 0 24 24"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg></a>
    </div>
    <iframe class="pdf-viewer-frame" id="pdfViewerFrame" src="about:blank" allow="fullscreen"></iframe>
</div>

<div class="copy-toast" id="copyToast">
    <svg width="14" height="14" fill="#4ade80" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
    لینک کپی شد!
</div>

<script>
// ── Theme ──
function applyThemeMeta(t){var m=document.getElementById('metaTheme');if(m)m.setAttribute('content',t==='light'?'#f4f4fb':'#07060f');}
function toggleTheme(){
    var cur=document.documentElement.getAttribute('data-theme')==='light'?'light':'dark';
    var next=cur==='light'?'dark':'light';
    document.documentElement.setAttribute('data-theme',next);
    try{localStorage.setItem('theme',next);}catch(e){}
    applyThemeMeta(next);
}
applyThemeMeta(document.documentElement.getAttribute('data-theme'));

// ── Modals ──
function openModal(id){
    var m = document.getElementById(id);
    if(m) m.classList.add('open');
}
function closeModal(id){
    var m = document.getElementById(id);
    if(m) m.classList.remove('open');
}
function openAdminTarget(id){
    closeModal('mobileAdminModal');
    window.setTimeout(function(){ openModal(id); }, 140);
}
document.querySelectorAll('.overlay').forEach(function(el){
    el.addEventListener('click',function(e){if(e.target===el)el.classList.remove('open');});
});

function askDelete(key,name){
    document.getElementById('deleteText').textContent='فایل «'+name+'» حذف شود؟';
    document.getElementById('deleteLink').href='?action=delete&key='+encodeURIComponent(key);
    document.getElementById('deleteModal').classList.add('open');
}

// ── PDF Viewer ──
function viewPDF(key,name){
    document.getElementById('pdfViewerName').textContent=name;
    document.getElementById('pdfViewerDownload').href='?action=download&key='+encodeURIComponent(key);
    document.getElementById('pdfViewer').classList.add('open');
    var url=location.href.split('?')[0]+'?action=view&key='+encodeURIComponent(key);
    var frame=document.getElementById('pdfViewerFrame');
    var isMobile=/iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
    frame.src=isMobile?'https://docs.google.com/gview?embedded=true&url='+encodeURIComponent(url):url+'#toolbar=1&view=FitH&scrollbar=1';
}
function closePdfViewer(){
    document.getElementById('pdfViewer').classList.remove('open');
    document.getElementById('pdfViewerFrame').src='about:blank';
}
document.addEventListener('keydown',function(e){if(e.key==='Escape')closePdfViewer();});

// ── Share ──
function shareFile(key,name){
    var base=location.href.split('?')[0];
    var dl=base+'?action=download&key='+encodeURIComponent(key);
    var vw=base+'?action=view&key='+encodeURIComponent(key);
    if(navigator.share){
        fetch(dl).then(function(r){if(!r.ok)throw 0;return r.blob();})
        .then(function(b){
            var file;
            try{file=new File([b],name,{type:'application/pdf'});}catch(e){b.name=name;file=b;}
            var sd={files:[file]};
            if(navigator.canShare&&navigator.canShare(sd))return navigator.share(sd);
            return navigator.share({title:name,text:name,url:vw});
        }).catch(function(){navigator.share({title:name,text:name,url:vw}).catch(function(){});});
        return;
    }
    copyLink(vw);
}
function copyLink(u){
    if(navigator.clipboard&&navigator.clipboard.writeText){
        navigator.clipboard.writeText(u).then(showCopyToast).catch(function(){fallbackCopy(u);});
    }else fallbackCopy(u);
}
function fallbackCopy(u){
    var ta=document.createElement('textarea');
    ta.value=u;ta.style.cssText='position:fixed;left:-9999px';
    document.body.appendChild(ta);ta.select();
    try{document.execCommand('copy');showCopyToast();}catch(e){}
    document.body.removeChild(ta);
}
var ctTimer;
function showCopyToast(){
    var t=document.getElementById('copyToast');
    t.classList.add('show');clearTimeout(ctTimer);
    ctTimer=setTimeout(function(){t.classList.remove('show');},2500);
}
function sharePerson(btn,keysJson,personName){
    var keys;
    try{keys=JSON.parse(keysJson);}catch(e){return;}
    if(!keys||!keys.length)return;
    var base=location.href.split('?')[0];
    btn.classList.add('share-loading');
    Promise.all(keys.map(function(k){
        var url=base+'?action=download&key='+encodeURIComponent(k);
        var fname=k.split('/').pop();
        return fetch(url).then(function(r){if(!r.ok)throw new Error('fail');return r.blob();}).then(function(b){
            try{return new File([b],fname,{type:'application/pdf'});}
            catch(e){b.name=fname;return b;}
        });
    })).then(function(files){
        var data={files:files,title:personName};
        if(navigator.canShare&&navigator.canShare(data))return navigator.share(data);
        if(navigator.canShare&&navigator.canShare({files:[files[0]]}))return navigator.share({files:[files[0]],title:personName});
        var links=keys.map(function(k){return base+'?action=view&key='+encodeURIComponent(k);}).join('\n');
        copyLink(links);
    }).catch(function(){
        var links=keys.map(function(k){return base+'?action=view&key='+encodeURIComponent(k);}).join('\n');
        copyLink(links);
    }).finally(function(){setTimeout(function(){btn.classList.remove('share-loading');},400);});
}

// ── Type detection ──
var TYPE_LIST=['homeplan','warmup','challenge','report','test','analyse','diet','program'];
var TYPES={
    homeplan:['home plan','homeplan','home-plan','home_plan','خانگی'],
    warmup:['warm up','warmup','warm-up','warm_up','گرم کردن','گرم‌کردن','وارم اپ'],
    challenge:['challenge','چالش'],
    report:['report','گزارش','ریپورت'],
    test:['test','تست','آزمایش','آزمون'],
    analyse:['آنالیز','انالیز','آناليز','analyse','analysis','analyze','analiz'],
    diet:['رژیم','رژيم','diet','regime'],
    program:['برنامه','program','programme','plan']
};
var TYPEFA={diet:'رژیم',analyse:'آنالیز',program:'برنامه',challenge:'چالش',report:'گزارش',test:'تست',homeplan:'خانگی',warmup:'گرم کردن',other:'other'};
var TYPECOLOR={diet:'#4ade80',analyse:'#38bdf8',program:'#c084fc',challenge:'#fb923c',report:'#f472b6',test:'#22d3ee',homeplan:'#a3e635',warmup:'#fbbf24',other:'#888'};
var FA2EN={'آ':'a','ا':'a','أ':'a','إ':'e','ب':'b','پ':'p','ت':'t','ث':'s','ج':'j','چ':'ch','ح':'h','خ':'kh',
'د':'d','ذ':'z','ر':'r','ز':'z','ژ':'zh','س':'s','ش':'sh','ص':'s','ض':'z','ط':'t','ظ':'z','ع':'a','غ':'gh',
'ف':'f','ق':'gh','ک':'k','ك':'k','گ':'g','ل':'l','م':'m','ن':'n','و':'v','ه':'h','ة':'h','ی':'i','ي':'i',
'ى':'a','ئ':'y','ؤ':'o','ء':'','۰':'0','۱':'1','۲':'2','۳':'3','۴':'4','۵':'5','۶':'6','۷':'7','۸':'8','۹':'9'};
function toSlug(name){
    var s='';
    for(var i=0;i<name.length;i++){var c=name[i];s+=(FA2EN[c]!==undefined?FA2EN[c]:c);}
    s=s.toLowerCase().replace(/[\s\-\.]+/g,'_').replace(/[^a-z0-9_]+/g,'').replace(/_+/g,'_').replace(/^_|_$/g,'');
    return s||'user';
}
function detectInfo(fn){
    var base=fn.replace(/\.pdf$/i,'');
    base=base.replace(/\s*\(\s*\d+\s*\)\s*$/,' ').replace(/\s*\[\s*\d+\s*\]\s*$/,' ')
             .replace(/[_\-]\d+\s*$/,' ').replace(/\s*[-_]?\s*(copy|کپی)\s*\d*\s*$/i,' ')
             .replace(/\(\s*\d+\s*\)/g,' ').replace(/\s*[-_]?\s*(final|new|نهایی|جدید)\s*$/i,' ');
    var low=base.toLowerCase(),type='other';
    for(var i=0;i<TYPE_LIST.length;i++){
        var t=TYPE_LIST[i],found=false;
        for(var j=0;j<TYPES[t].length;j++){if(low.indexOf(TYPES[t][j].toLowerCase())!==-1){found=true;break;}}
        if(found){type=t;break;}
    }
    var nm=base;
    TYPE_LIST.forEach(function(t){
        TYPES[t].forEach(function(w){nm=nm.replace(new RegExp(w.replace(/[.*+?^${}()|[\]\\]/g,'\\$&'),'gi'),' ');});
    });
    nm=nm.replace(/[_\-\.+,،]/g,' ').replace(/[\(\)\[\]\{\}]/g,' ').replace(/(^|\s)\d+(\s|$)/g,' ').replace(/\s+/g,' ').trim();
    return {name:nm||'user',type:type,slug:toSlug(nm||'user')};
}
function escAttr(s){return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function renderUploadPreview(files){
    var h='';
    for(var i=0;i<files.length;i++){
        var inf=detectInfo(files[i].name);
        var opts='';
        ['diet','analyse','program','challenge','report','test','homeplan','warmup','other'].forEach(function(t){
            opts+='<option value="'+t+'"'+(t===inf.type?' selected':'')+'>'+TYPEFA[t]+'</option>';
        });
        h+='<div class="up-card">'+
             '<div class="up-card-head">'+
               '<svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zM6 20V4h7v5h5v11H6z"/></svg>'+
               '<span class="up-fname" title="'+escAttr(files[i].name)+'">'+escAttr(files[i].name)+'</span>'+
               '<span class="up-auto">خودکار</span>'+
             '</div>'+
             '<div class="up-row">'+
               '<select class="up-select" name="file_types['+i+']">'+opts+'</select>'+
               '<div class="up-slug">'+
                 '<svg viewBox="0 0 24 24"><path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>'+
                 'پوشه: <b>'+escAttr(inf.slug)+'</b>'+
               '</div>'+
             '</div>'+
           '</div>';
    }
    document.getElementById('filePreview').innerHTML=h;
}

var fileInput=document.getElementById('fileInput');
var dropZone=document.getElementById('dropZone');
if(fileInput){
    fileInput.addEventListener('change',function(){renderUploadPreview(this.files);});
    dropZone.addEventListener('dragover',function(e){e.preventDefault();dropZone.classList.add('dragging');});
    dropZone.addEventListener('dragleave',function(){dropZone.classList.remove('dragging');});
    dropZone.addEventListener('drop',function(e){
        e.preventDefault();dropZone.classList.remove('dragging');
        if(e.dataTransfer.files.length){fileInput.files=e.dataTransfer.files;fileInput.dispatchEvent(new Event('change'));}
    });
}

document.querySelectorAll('.type-opt').forEach(function(o){
    o.addEventListener('click',function(){
        document.querySelectorAll('.type-opt').forEach(function(x){x.classList.remove('sel');});
        this.classList.add('sel');
        document.getElementById('announceType').value=this.dataset.type;
    });
});

setTimeout(function(){
    var a=document.querySelector('.m-tab.active');
    if(a)a.scrollIntoView({behavior:'smooth',block:'nearest',inline:'center'});
},100);
setTimeout(function(){
    document.querySelectorAll('.alert').forEach(function(el){
        el.style.transition='opacity .5s';el.style.opacity='0';
        setTimeout(function(){el.remove();},500);
    });
},4000);

if('serviceWorker' in navigator){navigator.serviceWorker.register('./sw.js').catch(function(){});}

// ── Edit modals ──
function openEditPerson(name){
    var m=document.getElementById('editPersonModal');
    if(!m)return;
    document.getElementById('editPersonName').value=name;
    var sl=document.getElementById('editPersonSlug');
    if(sl)sl.select();
    m.classList.add('open');
}
function updateSlugPreview(){
    var inp=document.getElementById('editPersonSlug');
    var out=document.getElementById('slugPreview');
    if(inp&&out)out.textContent=toSlug(inp.value)||'user';
}
var eps=document.getElementById('editPersonSlug');
if(eps)eps.addEventListener('input',updateSlugPreview);
function openEditFile(key,name,type){
    var m=document.getElementById('editFileModal');
    if(!m)return;
    document.getElementById('editFileKey').value=key;
    document.getElementById('editFileName').value=name;
    ['diet','analyse','program','challenge','report','test','homeplan','warmup','other'].forEach(function(t){
        var el=document.getElementById('ft_'+t);
        if(!el)return;
        el.classList.toggle('sel',t===type);
        el.href='?action=settype&key='+encodeURIComponent(key)+'&type='+t;
    });
    m.classList.add('open');
}

/* ============================================================
   🔔 PUSH NOTIFICATIONS  (permission + polling + display)
   ============================================================ */
var NOTIF_SUPPORTED = ('Notification' in window);
var NOTIF_KEY_ID    = 'pdfvault_device_id';
var NOTIF_KEY_SEEN  = 'pdfvault_notif_seen';
var NOTIF_OFF_KEY   = 'pdfvault_notif_off';
var notifPollTimer  = null;

function getNotifOff(){
    try{ return localStorage.getItem(NOTIF_OFF_KEY) === '1'; }
    catch(e){ return false; }
}
function setNotifOff(v){
    try{
        if(v) localStorage.setItem(NOTIF_OFF_KEY, '1');
        else  localStorage.removeItem(NOTIF_OFF_KEY);
    }catch(e){}
}

function getDeviceId(){
    var id='';
    try{ id = localStorage.getItem(NOTIF_KEY_ID) || ''; }catch(e){}
    if(!id){
        id = 'd' + Date.now().toString(36) + Math.random().toString(36).slice(2,9);
        try{ localStorage.setItem(NOTIF_KEY_ID, id); }catch(e){}
    }
    return id;
}
function resetDeviceId(){
    try{ localStorage.removeItem(NOTIF_KEY_ID); }catch(e){}
}
function getSeenTs(){
    var v=0;
    try{ v = parseInt(localStorage.getItem(NOTIF_KEY_SEEN) || '0', 10) || 0; }catch(e){}
    return v;
}
function setSeenTs(t){
    try{ localStorage.setItem(NOTIF_KEY_SEEN, String(t)); }catch(e){}
}

function paintNotifBtn(){
    var b = document.getElementById('notifBtn');
    var dot = document.getElementById('notifDot');
    if(!b) return;
    b.classList.remove('granted','denied','pending');
    if(!NOTIF_SUPPORTED){
        b.classList.add('denied');
        b.title = 'مرورگر شما از اعلان پشتیبانی نمی‌کند';
        if(dot) dot.style.display='none';
        return;
    }
    var p = Notification.permission;
    var off = getNotifOff();
    if(p === 'granted' && !off){
        b.classList.add('granted');
        b.title = 'اعلان فعال است — برای غیرفعال‌کردن کلیک کنید';
        if(dot) dot.style.display='block';
    } else if(p === 'granted' && off){
        b.classList.add('pending');
        b.title = 'اعلان غیرفعال است — برای فعال‌کردن کلیک کنید';
        if(dot) dot.style.display='none';
    } else if(p === 'denied'){
        b.classList.add('denied');
        b.title = 'اعلان مسدود شده — از تنظیمات مرورگر اجازه دهید';
        if(dot) dot.style.display='none';
    } else {
        b.classList.add('pending');
        b.title = 'برای دریافت اعلان کلیک کنید';
        if(dot) dot.style.display='none';
    }
}

var LAST_NOTIFICATION_AT = 0;
function subscribeDevice(){
    // silence everything briefly so the toggle test doesn't flash a stale toast
    LAST_NOTIFICATION_AT = Date.now();
    var fd = new FormData();
    fd.append('id', getDeviceId());
    fetch('?action=notif_subscribe', {method:'POST', body:fd})
        .then(function(r){return r.json();})
        .catch(function(){});
}
/* ── hard reset: tears down provider subscription + server + local state ── */
function hardDisableNotifications(){
    LAST_NOTIFICATION_AT = Date.now();
    stopNotifPoll();

    var endpoint = CURRENT_PUSH_ENDPOINT || '';
    var cleanup = Promise.resolve();

    /* 1) unsubscribe from the actual push provider (APNs/FCM) */
    if ('serviceWorker' in navigator && 'PushManager' in window) {
        cleanup = navigator.serviceWorker.getRegistration()
            .then(function(reg){
                if(!reg || !reg.pushManager) return;
                return reg.pushManager.getSubscription().then(function(sub){
                    if(!sub) return;
                    endpoint = endpoint || sub.endpoint;
                    return sub.unsubscribe().catch(function(){});
                });
            })
            .catch(function(){});
    }

    cleanup.then(function(){
        /* 2) server-side removal (device record + push subscription) */
        var fd = new FormData();
        fd.append('id', getDeviceId());
        if(endpoint) fd.append('endpoint', endpoint);
        fetch('?action=notif_unsubscribe', {method:'POST', body:fd}).catch(function(){});
    });

    /* 3) wipe every local trace — next enable is a fresh first-time flow */
    setNotifOff(true);
    resetDeviceId();
    setSeenTs(0);
    CURRENT_PUSH_ENDPOINT = '';

    paintNotifBtn();
}

function unsubscribeDevice(){
    hardDisableNotifications();
}


/* ─── real Web Push subscription ─── */
var CURRENT_PUSH_ENDPOINT = '';

function urlB64ToUint8Array(base64Url){
    var padding = '='.repeat((4 - base64Url.length % 4) % 4);
    var base64 = (base64Url + padding).replace(/-/g, '+').replace(/_/g, '/');
    var rawData = atob(base64);
    var out = new Uint8Array(rawData.length);
    for (var i = 0; i < rawData.length; i++) out[i] = rawData.charCodeAt(i);
    return out;
}

function ensurePushSubscription(){
    if (!NOTIF_SUPPORTED || Notification.permission !== 'granted') return Promise.resolve(false);
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) return Promise.resolve(false);

    var regP = navigator.serviceWorker.register ? navigator.serviceWorker.getRegistration() : Promise.resolve(null);
    if (!regP) return Promise.resolve(false);

    return Promise.resolve(regP).then(function(reg){
        if (!reg) reg = null;
        var readyP = reg ? navigator.serviceWorker.ready : Promise.reject();
        return Promise.resolve(readyP).then(function(swReg){
            return fetch('?action=push_pub', {cache:'no-store'})
                .then(function(r){ return r.json(); })
                .then(function(d){
                    if (!d || !d.ok || !d.public) return false;
                    return swReg.pushManager.getSubscription().then(function(oldSub){
                        var subP = oldSub ? Promise.resolve(oldSub)
                            : swReg.pushManager.subscribe({
                                userVisibleOnly: true,
                                applicationServerKey: urlB64ToUint8Array(d.public)
                            }).catch(function(){ return null; });
                        return subP.then(function(sub){
                            if (!sub) return false;
                            CURRENT_PUSH_ENDPOINT = sub.endpoint;
                            var key = sub.getKey('p256dh');
                            var auth = sub.getKey('auth');
                            if (!key || !auth) return false;
                            var toB64 = function(buf){ return btoa(String.fromCharCode.apply(null, new Uint8Array(buf))); };
                            var fd = new FormData();
                            fd.append('id', getDeviceId());
                            fd.append('endpoint', sub.endpoint);
                            fd.append('p256dh', toB64(key));
                            fd.append('auth', toB64(auth));
                            return fetch('?action=push_sub', {method:'POST', body:fd})
                                .then(function(){ return true; })
                                .catch(function(){ return false; });
                        });
                    });
                })
                .catch(function(){ return false; });
        }).catch(function(){ return false; });
    }).catch(function(){ return false; });
}

function isIosDevice(){
    return /iPhone|iPad|iPod/.test(navigator.userAgent)
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
}
function isStandaloneApp(){
    return (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches)
        || window.navigator.standalone === true;
}

function handleNotifClick(){
    if(!NOTIF_SUPPORTED){
        toastMsg('مرورگر شما از اعلان پشتیبانی نمی‌کند','err');
        return;
    }
    var p = Notification.permission;

    /* ─── already granted → clean toggle on/off (no dialogs) ─── */
    if(p === 'granted'){
        if(getNotifOff()){
            /* re-enable — full fresh registration (new device id + new push sub) */
            setNotifOff(false);
            getDeviceId();
            subscribeDevice();
            ensurePushSubscription();
            setSeenTs(Math.floor(Date.now()/1000));
            startNotifPoll();
            paintNotifBtn();
            toastMsg('اعلان‌ها با رجیستری تازه فعال شد ✅');
        } else {
            /* disable + total reset */
            hardDisableNotifications();
            toastMsg('اعلان‌ها غیرفعال و کاملاً ریست شد');
        }
        return;
    }

    if(p === 'denied'){
        alert('اعلان‌ها در مرورگر شما مسدود شده است.\n\nبرای فعال‌سازی:\nتنظیمات مرورگر ← سایت ← اعلان‌ها ← اجازه دادن');
        return;
    }

    Notification.requestPermission().then(function(res){
        paintNotifBtn();
        if(res === 'granted'){
            setNotifOff(false);
            getDeviceId();
            subscribeDevice();
            setSeenTs(Math.floor(Date.now()/1000));
            startNotifPoll();
            showLocalNotif('اعلان فعال شد ✅','از این پس اطلاع‌رسانی‌های مخزن را دریافت می‌کنید.','');
            toastMsg('اعلان‌ها با موفقیت فعال شد');
            // register real Web Push (delivers even when app is closed)
            ensurePushSubscription().then(function(ok){
                if (ok) {
                    toastMsg('پوش پس‌زمینه فعال شد 🚀');
                } else if (isIosDevice() && !isStandaloneApp()) {
                    setTimeout(function(){
                        toastMsg('برای اعلانِ پس‌زمینه روی آیفون: با دکمه Share، گزینه Add to Home Screen را بزنید','err');
                    }, 1800);
                }
            });
        } else {
            toastMsg('اجازه اعلان داده نشد','err');
        }
    }).catch(function(){});
}

function showLocalNotif(title, body, url){
    if(!NOTIF_SUPPORTED || Notification.permission !== 'granted') return;
    var opts = {
        body: body || '',
        icon: document.getElementById('pwaIconA') ? document.getElementById('pwaIconA').href : undefined,
        badge: document.getElementById('pwaIconA') ? document.getElementById('pwaIconA').href : undefined,
        dir: 'rtl',
        lang: 'fa',
        tag: 'pdfvault-' + Date.now(),
        data: { url: url || location.pathname },
        vibrate: [90,45,90]
    };
    if(navigator.serviceWorker && navigator.serviceWorker.ready){
        navigator.serviceWorker.ready.then(function(reg){
            if(reg && reg.showNotification){
                reg.showNotification(title, opts);
            } else {
                try{ new Notification(title, opts); }catch(e){}
            }
        }).catch(function(){
            try{ new Notification(title, opts); }catch(e){}
        });
    } else {
        try{ new Notification(title, opts); }catch(e){}
    }
}

function pollNotifications(){
    if(!NOTIF_SUPPORTED || Notification.permission !== 'granted') return;
    if(getNotifOff()) return;

    var startedAt = LAST_NOTIFICATION_AT;
    fetch('?action=notif_poll&since=' + getSeenTs(), {cache:'no-store'})
        .then(function(r){return r.json();})
        .then(function(d){
            // ignore responses that raced past a toggle during this fetch
            if(startedAt !== LAST_NOTIFICATION_AT) return;
            if(getNotifOff()) return;
            if(!d) return;
            if(d.items && d.items.length){
                d.items.forEach(function(m, i){
                    setTimeout(function(){
                        showLocalNotif(m.title || 'مخزن PDF', m.body || '', m.url || '');
                    }, i * 700);
                });
            }
            if(d.now) setSeenTs(d.now);
        })
        .catch(function(){});
}
function startNotifPoll(){
    stopNotifPoll();
    pollNotifications();
    notifPollTimer = setInterval(pollNotifications, 25000);
}
function stopNotifPoll(){
    if(notifPollTimer){ clearInterval(notifPollTimer); notifPollTimer = null; }
}

// small reusable toast (reuses copy-toast element)
function toastMsg(text, type){
    var t = document.getElementById('copyToast');
    if(!t) return;
    var ok = type !== 'err';
    t.innerHTML = (ok
        ? '<svg width="14" height="14" fill="#4ade80" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>'
        : '<svg width="14" height="14" fill="#fb7185" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>'
    ) + ' ' + text;
    t.style.color = ok ? '' : '#fb7185';
    t.classList.add('show');
    clearTimeout(window.__tmt);
    window.__tmt = setTimeout(function(){ t.classList.remove('show'); }, 2600);
}

// init
paintNotifBtn();
(function(){
    if(!NOTIF_SUPPORTED) return;
    if(Notification.permission === 'granted' && !getNotifOff()){
        getDeviceId();
        subscribeDevice();
        ensurePushSubscription();
        if(!getSeenTs()) setSeenTs(Math.floor(Date.now()/1000));
        startNotifPoll();
    }
})();
document.addEventListener('visibilitychange', function(){
    if(document.visibilityState === 'visible'){
        if(NOTIF_SUPPORTED && Notification.permission === 'granted'){
            if(!getNotifOff()) pollNotifications();
        }
    }
});

// ── Shortcuts ──
var IS_ADMIN=<?= isAdmin() ? 'true' : 'false' ?>;
document.addEventListener('keydown',function(e){
    var tag=(e.target.tagName||'').toLowerCase();
    var inField=(tag==='input'||tag==='textarea'||tag==='select');
    var k=e.key.toLowerCase();

    if(inField){
        if(e.key==='Escape')e.target.blur();
        return;
    }

    // "/" or "s" → focus search
    if(k==='/'||k==='s'){var si=document.querySelector('.search-wrap input');if(si){e.preventDefault();si.focus();si.select();}return;}
    if(k==='t'){e.preventDefault();toggleTheme();return;}
    if(e.key==='Escape'){
        document.querySelectorAll('.overlay.open').forEach(function(o){o.classList.remove('open');});
        closePdfViewer();return;
    }
    if(!IS_ADMIN)return;
    var map={u:'uploadModal',a:'announceModal',n:'reminderModal'};
    if(map[k]){e.preventDefault();openModal(map[k]);}
});
</script>
</body>
</html>
