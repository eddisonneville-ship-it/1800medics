<?php
date_default_timezone_set('Europe/Berlin');
/**
 * 1800MEDICS.DE — LOCAL VISITOR TRACKER
 * Lightweight analytics. Include via <script> or <img> tag on every page.
 * Access: /api/track.php?page=/current-path/
 * Dashboard: admin panel > Statistiken
 */

$data_dir = dirname(__DIR__) . '/admin/data';
if (!is_dir($data_dir)) mkdir($data_dir, 0755, true);

$today = date('Y-m-d');
$month = date('Y-m');
$hour = date('H');

// Get visitor data
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$ip_hash = md5($ip . $today); // anonymized, rotates daily
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$ref = $_SERVER['HTTP_REFERER'] ?? '';
$page = $_GET['page'] ?? $_SERVER['REQUEST_URI'] ?? '/';
$page = parse_url($page, PHP_URL_PATH) ?: '/';

// Skip bots
$bots = ['bot','crawler','spider','slurp','googlebot','bingbot','yandex','baidu','semrush','ahrefs','mj12','dotbot','petalbot'];
$is_bot = false;
foreach ($bots as $b) { if (stripos($ua, $b) !== false) { $is_bot = true; break; } }
if ($is_bot) { http_response_code(204); exit; }

// Device detection
$device = 'Desktop';
if (preg_match('/Mobile|Android|iPhone|iPad/i', $ua)) $device = preg_match('/iPad|Tablet/i', $ua) ? 'Tablet' : 'Mobile';

// Browser detection
$browser = 'Andere';
if (stripos($ua, 'Firefox') !== false) $browser = 'Firefox';
elseif (stripos($ua, 'Edg') !== false) $browser = 'Edge';
elseif (stripos($ua, 'Chrome') !== false) $browser = 'Chrome';
elseif (stripos($ua, 'Safari') !== false) $browser = 'Safari';
elseif (stripos($ua, 'Opera') !== false || stripos($ua, 'OPR') !== false) $browser = 'Opera';

// Referrer source
$source = 'Direkt';
if ($ref) {
    $ref_host = parse_url($ref, PHP_URL_HOST) ?? '';
    if (stripos($ref_host, 'google') !== false) $source = 'Google';
    elseif (stripos($ref_host, 'bing') !== false) $source = 'Bing';
    elseif (stripos($ref_host, 'yahoo') !== false) $source = 'Yahoo';
    elseif (stripos($ref_host, 'facebook') !== false || stripos($ref_host, 'fb.') !== false) $source = 'Facebook';
    elseif (stripos($ref_host, 'instagram') !== false) $source = 'Instagram';
    elseif (stripos($ref_host, 'twitter') !== false || stripos($ref_host, 't.co') !== false) $source = 'Twitter';
    elseif (stripos($ref_host, 'telegram') !== false || stripos($ref_host, 't.me') !== false) $source = 'Telegram';
    elseif (stripos($ref_host, '1800medics') !== false) $source = 'Intern';
    else $source = $ref_host;
}

// Load/create daily stats file
$stats_file = "$data_dir/stats_$today.json";
$stats = file_exists($stats_file) ? json_decode(file_get_contents($stats_file), true) : [
    'date' => $today,
    'views' => 0,
    'unique' => [],
    'pages' => [],
    'sources' => [],
    'devices' => [],
    'browsers' => [],
    'hours' => [],
    'hits' => [],
];

// Record visit
$stats['views']++;
if (!in_array($ip_hash, $stats['unique'])) $stats['unique'][] = $ip_hash;
$stats['pages'][$page] = ($stats['pages'][$page] ?? 0) + 1;
$stats['sources'][$source] = ($stats['sources'][$source] ?? 0) + 1;
$stats['devices'][$device] = ($stats['devices'][$device] ?? 0) + 1;
$stats['browsers'][$browser] = ($stats['browsers'][$browser] ?? 0) + 1;
$stats['hours'][$hour] = ($stats['hours'][$hour] ?? 0) + 1;

// Keep last 100 hits for live view
$hit = ['time' => date('H:i:s'), 'page' => $page, 'source' => $source, 'device' => $device];
$stats['hits'][] = $hit;
if (count($stats['hits']) > 100) $stats['hits'] = array_slice($stats['hits'], -100);

// Save
file_put_contents($stats_file, json_encode($stats, JSON_UNESCAPED_UNICODE));

// Monthly summary
$monthly_file = "$data_dir/stats_month_$month.json";
$monthly = file_exists($monthly_file) ? json_decode(file_get_contents($monthly_file), true) : ['month' => $month, 'days' => []];
$monthly['days'][$today] = ['views' => $stats['views'], 'unique' => count($stats['unique'])];
file_put_contents($monthly_file, json_encode($monthly, JSON_UNESCAPED_UNICODE));

// Return 1x1 pixel (for img tag tracking) or 204 (for fetch tracking)
if (isset($_GET['pixel'])) {
    header('Content-Type: image/gif');
    echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
} else {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    echo json_encode(['ok' => true]);
}
