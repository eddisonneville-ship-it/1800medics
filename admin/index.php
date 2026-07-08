<?php
date_default_timezone_set('Europe/Berlin');
/**
 * 1800MEDICS.DE — FULL ADMIN PANEL
 * Complete CMS for managing the static HTML site.
 */
session_start();
error_reporting(0);
require_once __DIR__ . '/email_functions.php';

define('SITE_ROOT', dirname(__DIR__));
define('DATA_DIR', __DIR__ . '/data');
define('SETTINGS_FILE', DATA_DIR . '/settings.json');
define('ORDERS_FILE', DATA_DIR . '/orders.json');
define('PRODUCTS_FILE', DATA_DIR . '/products.json');

if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);
if (!is_dir(DATA_DIR . '/screenshots')) mkdir(DATA_DIR . '/screenshots', 0755, true);

// ============================================================
// SETTINGS DEFAULTS
// ============================================================
function default_settings() {
    return [
        'admin_user' => 'admin',
        'admin_pass' => password_hash('1800Medics!Admin2026', PASSWORD_DEFAULT),
        'site_name' => '1800Medics.de',
        'site_url' => 'https://1800medics.de',
        'email' => 'kontakt@1800medics.de',
        'phone' => '06242 504967',
        'owner_name' => 'Joerg Hinterschitt',
        'owner_address' => 'Osthofener Strasse 67, 67550 Worms, Deutschland',
        'announcement_bar' => 'Sichere Payment per Bitcoin, SEPA & Krypto | Kostenloser Shipping ab 150€ | Diskretes Paket garantiert | Shipping innerhalb von 24 Stunden',
        'shipping_flat' => '25.00',
        'shipping_free_above' => '150.00',
        'shipping_text' => 'Shipping innerhalb von 2 Werktagen nach Deutschland, Oesterreich und die Schweiz.',
        'crypto_only_below' => '100.00',
        'payment_methods' => [
            'sepa' => [
                'enabled' => true,
                'label' => 'SEPA Ueberweisung',
                'min_amount' => 100,
                'details' => "Kontoinhaber: [Name eintragen]\nIBAN: [IBAN eintragen]\nBIC: [BIC eintragen]\nBank: [Bankname eintragen]"
            ],
            'bitcoin' => [
                'enabled' => true,
                'label' => 'Bitcoin',
                'min_amount' => 0,
                'details' => "BTC Address: [Bitcoin Address eintragen]"
            ],
            'ethereum' => [
                'enabled' => true,
                'label' => 'Ethereum',
                'min_amount' => 0,
                'details' => "ETH Address: [Ethereum Address eintragen]"
            ],
            'crypto_other' => [
                'enabled' => true,
                'label' => 'Andere Kryptowaehrung',
                'min_amount' => 0,
                'details' => "Kontaktieren Sie uns unter kontakt@1800medics.de mit Ihrer Bestellnummer fuer die Wallet-Address."
            ],
            'paysafecard' => [
                'enabled' => true,
                'label' => 'Paysafecard',
                'min_amount' => 0,
                'details' => "Senden Sie den Paysafecard-Code an:\nE-Mail: kontakt@1800medics.de\nBetreff: Paysafecard [BESTELLNUMMER]"
            ],
        ],
        'smtp_enabled' => false,
        'smtp_host' => 'smtp.hostinger.com',
        'smtp_port' => '465',
        'smtp_user' => 'kontakt@1800medics.de',
        'smtp_pass' => '',
        'smtp_encryption' => 'ssl',
        'smtp_from_name' => '1800Medics.de',
        'screenshot_upload' => true,
        'screenshot_text' => 'Laden Sie einen Screenshot Ihrer Payment hoch, damit wir Ihre Bestellung schneller bearbeiten koennen.',
        'order_confirmation_text' => 'Vielen Dank fuer Ihre Bestellung! Wir bearbeiten Ihre Bestellung nach Paymentseingang.',
        'disclaimer_footer' => 'Alle auf 1800Medics.de angebotenen Products sind ausschliesslich fuer Forschungs- und Laborzwecke bestimmt. Sie sind nicht fuer den menschlichen Konsum geeignet.',
        'verify_google' => '',
        'verify_bing' => '',
        'verify_facebook_pixel' => '',
        'google_analytics' => '',
        'verify_pinterest' => '',
        'verify_clarity' => '',
        'custom_head_code' => '',
        'livechat_enabled' => false,
        'whatsapp_number' => '',
        'whatsapp_text' => 'Hallo, ich habe eine Frage zu meiner Bestellung.',
        'whatsapp_enabled' => false,
        'livechat_code' => '',
    ];
}

function load_settings() {
    if (!file_exists(SETTINGS_FILE)) { save_settings(default_settings()); }
    $s = json_decode(file_get_contents(SETTINGS_FILE), true);
    return array_merge(default_settings(), $s ?: []);
}

function save_settings($s) {
    file_put_contents(SETTINGS_FILE, json_encode($s, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function load_json($file) {
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?: [];
}

function save_json($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$settings = load_settings();

// ============================================================
// AUTH
// ============================================================
function is_logged_in() { return !empty($_SESSION['admin_ok']); }

if (isset($_POST['do_login'])) {
    $s = load_settings();
    if ($_POST['username'] === $s['admin_user'] && password_verify($_POST['password'], $s['admin_pass'])) {
        $_SESSION['admin_ok'] = true;
    } else {
        $login_error = true;
    }
}
if (isset($_GET['logout'])) { session_destroy(); header('Location: index.php'); exit; }

// ============================================================
// PRODUCT DB
// ============================================================
function site_wide_replace($old_email, $new_email, $old_phone, $new_phone) {
    $count = 0;
    $dirs = [SITE_ROOT];
    $skip = [SITE_ROOT . '/admin', SITE_ROOT . '/api'];
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(SITE_ROOT, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        $path = $file->getPathname();
        
        // Skip admin and api directories
        foreach ($skip as $s) { if (strpos($path, $s) === 0) continue 2; }
        
        if (!$file->isFile()) continue;
        if (!in_array($file->getExtension(), ['html', 'js', 'json', 'txt', 'xml'])) continue;
        
        $content = file_get_contents($path);
        $original = $content;
        
        if ($old_email && $new_email && $old_email !== $new_email) {
            $content = str_replace($old_email, $new_email, $content);
        }
        if ($old_phone && $new_phone && $old_phone !== $new_phone) {
            $content = str_replace($old_phone, $new_phone, $content);
        }
        
        if ($content !== $original) {
            file_put_contents($path, $content);
            $count++;
        }
    }
    return $count;
}

function load_products() {
    $products = load_json(PRODUCTS_FILE);
    if (empty($products)) {
        // Scan product directories to build initial DB
        $dir = SITE_ROOT . '/product';
        if (!is_dir($dir)) return [];
        foreach (scandir($dir) as $slug) {
            if ($slug[0] === '.') continue;
            $f = "$dir/$slug/index.html";
            if (!file_exists($f)) continue;
            $html = file_get_contents($f);
            preg_match('/<h1>(.*?)<\/h1>/s', $html, $nm);
            preg_match('/data-price="([^"]*)"/', $html, $pm);
            preg_match('/data-image="([^"]*)"/', $html, $im);
            preg_match('/Brand:.*?<strong>(.*?)<\/strong>/', $html, $bm);
            preg_match('/href="\/category\/([^\/]+)\/"/', $html, $cm);
            $products[] = [
                'slug' => $slug,
                'name' => isset($nm[1]) ? trim($nm[1]) : $slug,
                'price' => isset($pm[1]) ? $pm[1] : '0',
                'image' => isset($im[1]) ? $im[1] : '',
                'brand' => isset($bm[1]) ? trim($bm[1]) : '',
                'category' => isset($cm[1]) ? $cm[1] : '',
                'published' => true,
            ];
        }
        save_json(PRODUCTS_FILE, $products);
    }
    return $products;
}

// ============================================================
// ACTIONS
// ============================================================
$page = $_GET['page'] ?? 'dashboard';
$msg = '';

if (!is_logged_in() && !isset($_POST['do_login'])) { $page = 'login'; }

// Save settings
if (isset($_POST['save_settings']) && is_logged_in()) {
    $s = load_settings();
    foreach (['email','phone','owner_name','owner_address','announcement_bar','shipping_flat','shipping_free_above','shipping_text','crypto_only_below','disclaimer_footer','order_confirmation_text','screenshot_text'] as $k) {
        if (isset($_POST[$k])) $s[$k] = $_POST[$k];
    }
    $s['screenshot_upload'] = isset($_POST['screenshot_upload']);
    if (isset($_POST['livechat_enabled'])) $s['livechat_enabled'] = isset($_POST['livechat_enabled']);
    if (isset($_POST['livechat_code'])) $s['livechat_code'] = $_POST['livechat_code'];
    save_settings($s);
    $settings = $s;
    
    // Regenerate pages that use these settings
    regenerate_checkout($s);
    regenerate_confirmation($s);
    
    $msg = 'Settings saved!';
}

// Save payment methods
if (isset($_POST['save_payments']) && is_logged_in()) {
    $s = load_settings();
    foreach ($s['payment_methods'] as $m => &$pm) {
        $pm['enabled'] = isset($_POST["pay_{$m}_enabled"]);
        $pm['label'] = $_POST["pay_{$m}_label"] ?? $pm['label'];
        $pm['min_amount'] = floatval($_POST["pay_{$m}_min"] ?? 0);
        $pm['screenshot_required'] = isset($_POST["pay_{$m}_screenshot_required"]);
        if ($m !== 'crypto') {
            $pm['details'] = $_POST["pay_{$m}_details"] ?? '';
        }
    }
    // Handle crypto wallets
    if (isset($s['payment_methods']['crypto'])) {
        $wallets = [];
        for ($i = 0; $i < 20; $i++) {
            $coin = trim($_POST["pay_crypto_wallet_coin_$i"] ?? '');
            if ($coin) {
                $wallets[] = [
                    'coin' => $coin,
                    'network' => trim($_POST["pay_crypto_wallet_network_$i"] ?? ''),
                    'address' => trim($_POST["pay_crypto_wallet_address_$i"] ?? ''),
                ];
            }
        }
        // Add new wallet
        $new_coin = trim($_POST['pay_crypto_wallet_coin_new'] ?? '');
        if ($new_coin) {
            $wallets[] = [
                'coin' => $new_coin,
                'network' => trim($_POST['pay_crypto_wallet_network_new'] ?? ''),
                'address' => trim($_POST['pay_crypto_wallet_address_new'] ?? ''),
            ];
        }
        $s['payment_methods']['crypto']['wallets'] = $wallets;
    }
    save_settings($s);
    $settings = $s;
    regenerate_checkout($s);
    regenerate_confirmation($s);
    $msg = 'Payment methods saved!';
}

// Save SMTP
if (isset($_POST['save_smtp']) && is_logged_in()) {
    $s = load_settings();
    $s['smtp_enabled'] = isset($_POST['smtp_enabled']);
    foreach (['smtp_host','smtp_port','smtp_user','smtp_pass','smtp_encryption','smtp_from_name'] as $k) {
        if (isset($_POST[$k])) $s[$k] = $_POST[$k];
    }
    save_settings($s);
    $settings = $s;
    $msg = 'SMTP Settings saved!';
}

// Test SMTP
if (isset($_POST['test_smtp']) && is_logged_in()) {
    $s = load_settings();
    $to = $s['email'];
    $subject = 'SMTP Test - 1800Medics.de';
    $body = 'Dies ist eine Testnachricht vom 1800Medics.de Admin Panel.';
    $result = @mail($to, $subject, $body, "From: {$s['smtp_user']}");
    $msg = $result ? 'Test email sent!' : 'Email sending failed. Check SMTP settings.';
}

// Save product
if (isset($_POST['save_product']) && is_logged_in()) {
    $products = load_products();
    $slug = $_POST['slug'];
    $found = false;
    $pdata = [
        'slug' => $slug,
        'name' => $_POST['name'],
        'price' => $_POST['price'],
        'image' => $_POST['image'],
        'brand' => $_POST['brand'],
        'category' => $_POST['category'],
        'description' => $_POST['description'] ?? '',
        'published' => isset($_POST['published']),
    ];
    foreach ($products as &$p) {
        if ($p['slug'] === $slug) { $p = array_merge($p, $pdata); $found = true; break; }
    }
    if (!$found) $products[] = $pdata;
    save_json(PRODUCTS_FILE, $products);
    regenerate_product_page($slug, $pdata, load_settings());
    $msg = 'Product saved and page updated!';
}

// Delete product
if (isset($_GET['delete']) && is_logged_in()) {
    $slug = $_GET['delete'];
    $products = load_products();
    $products = array_values(array_filter($products, function($p) use ($slug) { return $p['slug'] !== $slug; }));
    save_json(PRODUCTS_FILE, $products);
    $dir = SITE_ROOT . "/product/$slug";
    if (is_dir($dir)) { @unlink("$dir/index.html"); @rmdir($dir); }
    $msg = 'Product deleted!'; $page = 'products';
}

// Save announcement bar
if (isset($_POST['save_announcement']) && is_logged_in()) {
    $s = load_settings();
    $s['announcement_bar'] = $_POST['announcement_bar'];
    save_settings($s);
    $settings = $s;
    $msg = 'Announcement bar saved! Changes appear on new/regenerated pages.';
}

// Save shipping
if (isset($_POST['save_shipping']) && is_logged_in()) {
    $s = load_settings();
    $s['shipping_flat'] = $_POST['shipping_flat'];
    $s['shipping_free_above'] = $_POST['shipping_free_above'];
    $s['shipping_text'] = $_POST['shipping_text'];
    $s['crypto_only_below'] = $_POST['crypto_only_below'];
    save_settings($s);
    $settings = $s;
    // Update JS shipping values
    regenerate_js($s);
    $msg = 'Shipping saved and JS updated!';
}

// Save verification codes
if (isset($_POST['save_verification']) && is_logged_in()) {
    $s = load_settings();
    foreach (['verify_google','verify_bing','verify_facebook_pixel','google_analytics','verify_pinterest','verify_clarity','custom_head_code','livechat_code'] as $k) {
        if (isset($_POST[$k])) $s[$k] = $_POST[$k];
    }
    save_settings($s);
    $settings = $s;
    $msg = 'Tracking codes saved!';
}

// Change password
if (isset($_POST['change_password']) && is_logged_in()) {
    $s = load_settings();
    if ($_POST['new_password'] === $_POST['confirm_password'] && strlen($_POST['new_password']) >= 8) {
        $s['admin_pass'] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        if (!empty($_POST['new_username'])) $s['admin_user'] = $_POST['new_username'];
        save_settings($s);
        $msg = 'Credentials updated!';
    } else {
        $msg = 'Passwords do not match or are too short (min. 8 chars).';
    }
}

// Update order status
if (isset($_POST['update_order']) && is_logged_in()) {
    $orders = load_json(ORDERS_FILE);
    $idx = intval($_POST['order_idx']);
    if (isset($orders[$idx])) {
        $orders[$idx]['status'] = $_POST['order_status'];
        $orders[$idx]['admin_notes'] = $_POST['admin_notes'] ?? '';
        save_json(ORDERS_FILE, $orders);
        $msg = 'Order updated!';
    }
}

// Save contact/impressum
if (isset($_POST['save_contact']) && is_logged_in()) {
    $s = load_settings();
    $old_email = $s['email'];
    $old_phone = $s['phone'];
    
    $s['email'] = $_POST['email'];
    $s['phone'] = $_POST['phone'];
    $s['owner_name'] = $_POST['owner_name'];
    $s['owner_address'] = $_POST['owner_address'];
    $s['whatsapp_number'] = $_POST['whatsapp_number'] ?? '';
    $s['whatsapp_text'] = $_POST['whatsapp_text'] ?? '';
    $s['whatsapp_enabled'] = isset($_POST['whatsapp_enabled']);
    if (isset($_POST['smtp_enabled_contact'])) $s['smtp_enabled'] = isset($_POST['smtp_enabled_contact']);
    if (isset($_POST['smtp_host_c'])) $s['smtp_host'] = $_POST['smtp_host_c'];
    if (isset($_POST['smtp_port_c'])) $s['smtp_port'] = $_POST['smtp_port_c'];
    if (isset($_POST['smtp_enc_c'])) $s['smtp_encryption'] = $_POST['smtp_enc_c'];
    if (isset($_POST['smtp_user_c'])) $s['smtp_user'] = $_POST['smtp_user_c'];
    if (isset($_POST['smtp_pass_c'])) $s['smtp_pass'] = $_POST['smtp_pass_c'];
    if (isset($_POST['smtp_name_c'])) $s['smtp_from_name'] = $_POST['smtp_name_c'];
    save_settings($s);
    $settings = $s;
    
    // Site-wide email/phone replacement across all HTML files
    if ($old_email !== $s['email'] || $old_phone !== $s['phone']) {
        $count = site_wide_replace($old_email, $s['email'], $old_phone, $s['phone']);
        $msg = "Contact details saved! Updated $count files across the site.";
    } else {
        $msg = 'Contact details saved!';
    }
}

// ============================================================
// REGENERATE FUNCTIONS
// ============================================================
function get_header_html($settings) {
    return file_exists(__DIR__ . '/template_header.html') ? file_get_contents(__DIR__ . '/template_header.html') : '';
}

function get_footer_html($settings) {
    return file_exists(__DIR__ . '/template_footer.html') ? file_get_contents(__DIR__ . '/template_footer.html') : '';
}

function regenerate_product_page($slug, $data, $settings) {
    $name = htmlspecialchars($data['name'] ?? '');
    $price = htmlspecialchars($data['price'] ?? '0');
    $image = htmlspecialchars($data['image'] ?? '');
    $brand = htmlspecialchars($data['brand'] ?? '');
    $category = htmlspecialchars($data['category'] ?? '');
    $desc = $data['description'] ?? '';
    $display = str_replace(' kaufen', '', $name);
    $img_html = $image ? "<img src=\"$image\" alt=\"$display\">" : '<div style="color:#6b7280;">Bild folgt</div>';

    $html = "<!DOCTYPE html><html lang=\"de\"><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width,initial-scale=1.0\">";
    $html .= "<title>$display kaufen | {$settings['site_name']}</title>";
    $html .= '<link rel="stylesheet" href="/css/style.css"><link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet"></head><body>';
    $html .= get_header_html($settings);
    $html .= "<div class=\"shop-hero\"><div class=\"container\"><h1>$display</h1></div></div>";
    $html .= '<section class="product-page"><div class="container"><div class="product-layout">';
    $html .= "<div class=\"product-image\">$img_html</div>";
    $html .= "<div class=\"product-details\"><h1>$display</h1><div class=\"product-price-tag\">&euro;$price</div>";
    $html .= "<div class=\"product-meta\"><span>Brand: <strong>$brand</strong></span><span>Category: <a href=\"/category/$category/\">$category</a></span></div>";
    $html .= "<button class=\"btn btn-primary add-to-cart\" style=\"width:100%;justify-content:center;\" data-name=\"$name\" data-slug=\"$slug\" data-price=\"$price\" data-image=\"$image\">In den Cart</button>";
    $html .= '</div></div></div></section>';
    if ($desc) $html .= "<section class=\"product-description\"><div class=\"container\" style=\"max-width:900px;\">$desc</div></section>";
    $html .= get_footer_html($settings);
    $html .= '<script src="/js/main.js"></script></body></html>';

    $dir = SITE_ROOT . "/product/$slug";
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents("$dir/index.html", $html);
}

function regenerate_checkout($s) {
    // Generate payment methods JS config
    $pm_json = json_encode($s['payment_methods']);
    $js_config = "window.SITE_CONFIG = " . json_encode([
        'shipping_flat' => floatval($s['shipping_flat']),
        'shipping_free_above' => floatval($s['shipping_free_above']),
        'crypto_only_below' => floatval($s['crypto_only_below']),
        'payment_methods' => $s['payment_methods'],
        'screenshot_upload' => $s['screenshot_upload'],
        'screenshot_text' => $s['screenshot_text'],
        'order_confirmation_text' => $s['order_confirmation_text'],
    ]) . ";\n";
    
    $config_path = SITE_ROOT . '/js/config.js';
    file_put_contents($config_path, $js_config);
}

function regenerate_confirmation($s) {
    regenerate_checkout($s); // same config file
}

function regenerate_js($s) {
    regenerate_checkout($s);
}

// ============================================================
// CATEGORIES LIST
// ============================================================
$all_categories = ['retatrutide','glp-1-peptide','hgh-peptide','heilpeptide','sarms','injizierbare-steroide','orale-steroide','pct','sexualgesundheit','abnehmen','stacks','nahrungsergaenzung'];

// ============================================================
// HTML
// ============================================================
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin | <?= htmlspecialchars($settings['site_name']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Outfit',sans-serif;background:#f1f3f5;color:#2d3748}
a{color:#e63946;text-decoration:none}a:hover{text-decoration:underline}
.login-page{display:flex;align-items:center;justify-content:center;min-height:100vh}
.login-box{background:#fff;padding:40px;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,0.08);width:380px;text-align:center}
.login-box h1{font-size:24px;color:#0d1b2a;margin-bottom:8px}
.login-box p{color:#6b7280;font-size:14px;margin-bottom:24px}
.login-box input{width:100%;padding:12px 16px;border:1.5px solid #e4e8ee;border-radius:8px;font-size:15px;font-family:inherit;margin-bottom:12px}
.login-box input:focus{border-color:#e63946;outline:none}
.login-box button{width:100%;padding:14px;background:#e63946;color:#fff;border:none;border-radius:8px;font-size:16px;font-weight:700;cursor:pointer;font-family:inherit}
.login-box button:hover{background:#c1121f}
.login-error{color:#dc2626;font-size:13px;margin-bottom:12px}
.wrap{display:flex;min-height:100vh}
.side{width:220px;background:#0d1b2a;color:#8899aa;padding:20px 0;flex-shrink:0;position:sticky;top:0;height:100vh;overflow-y:auto}
.side .logo{padding:0 16px 20px;font-size:18px;font-weight:800;color:#fff;border-bottom:1px solid rgba(255,255,255,0.08);margin-bottom:12px}
.side .logo em{color:#e63946;font-style:normal}
.side a{display:block;padding:9px 16px;color:#8899aa;font-size:13px;font-weight:600;transition:0.2s}
.side a:hover,.side a.on{color:#fff;background:rgba(230,57,70,0.12);text-decoration:none}
.side a.on{border-left:3px solid #e63946}
.side .sep{border-top:1px solid rgba(255,255,255,0.06);margin:10px 0}
.mn{flex:1;padding:28px;overflow-x:auto;max-width:100%}
.mn h1{font-size:26px;color:#0d1b2a;margin-bottom:20px}
.cd{background:#fff;border-radius:12px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,0.04);margin-bottom:24px}
.cd h2{font-size:17px;color:#0d1b2a;margin-bottom:16px;padding-bottom:10px;border-bottom:1px solid #f1f3f5}
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px}
.st{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 10px rgba(0,0,0,0.04)}
.st .n{font-size:32px;font-weight:900;color:#0d1b2a}.st .n em{color:#e63946;font-style:normal}
.st .l{font-size:12px;color:#6b7280;margin-top:4px}
table{width:100%;border-collapse:collapse}
th{text-align:left;padding:8px 10px;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;border-bottom:2px solid #e4e8ee}
td{padding:8px 10px;border-bottom:1px solid #f1f3f5;font-size:13px}
tr:hover td{background:#f9fafb}
.bg{display:inline-block;padding:2px 10px;border-radius:100px;font-size:10px;font-weight:700}
.bg-g{background:#dcfce7;color:#16a34a}.bg-r{background:#fee2e2;color:#dc2626}.bg-y{background:#fef3cd;color:#92400e}.bg-b{background:#dbeafe;color:#1d4ed8}.bg-x{background:#f1f3f5;color:#6b7280}
.fg{margin-bottom:14px}.fg label{display:block;font-size:12px;font-weight:700;color:#0d1b2a;margin-bottom:4px}
.fg input,.fg select,.fg textarea{width:100%;padding:9px 12px;border:1.5px solid #e4e8ee;border-radius:8px;font-size:14px;font-family:inherit}
.fg input:focus,.fg select:focus,.fg textarea:focus{border-color:#e63946;outline:none}
.fr{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.fr3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px}
.bt{display:inline-block;padding:9px 22px;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;border:none;font-family:inherit}
.bt-r{background:#e63946;color:#fff}.bt-r:hover{background:#c1121f;text-decoration:none}
.bt-o{background:transparent;color:#e63946;border:1.5px solid #e63946}.bt-o:hover{background:#e63946;color:#fff;text-decoration:none}
.bt-d{background:transparent;color:#dc2626;border:1px solid #dc2626}.bt-d:hover{background:#dc2626;color:#fff;text-decoration:none}
.bt-s{padding:6px 14px;font-size:11px}
.msg{padding:10px 18px;background:#dcfce7;color:#16a34a;border-radius:8px;margin-bottom:16px;font-weight:600;font-size:14px}
.msg-e{background:#fee2e2;color:#dc2626}
.sb{padding:9px 14px;border:1.5px solid #e4e8ee;border-radius:8px;font-size:13px;width:280px;font-family:inherit;margin-bottom:14px}
.sb:focus{border-color:#e63946;outline:none}
.thumb{width:36px;height:36px;object-fit:contain;border-radius:4px;background:#f1f3f5}
.chk{display:flex;align-items:center;gap:8px;margin-bottom:8px}
.chk input{accent-color:#e63946}
.pm-card{border:1.5px solid #e4e8ee;border-radius:10px;padding:16px;margin-bottom:14px}
.pm-card h3{font-size:14px;font-weight:700;margin-bottom:10px;color:#0d1b2a}
@media(max-width:768px){.side{width:56px}.side a span,.side .logo span{display:none}.mn{padding:14px}.stats{grid-template-columns:1fr 1fr}.fr,.fr3{grid-template-columns:1fr}}
</style>
</head>
<body>

<?php if ($page === 'login'): ?>
<div class="login-page">
<form class="login-box" method="POST">
<h1>1800<em style="color:#e63946">+</em>medics</h1>
<p>Admin Panel</p>
<?php if (!empty($login_error)): ?><div class="login-error">Invalid credentials.</div><?php endif; ?>
<input type="text" name="username" placeholder="Username" required>
<input type="password" name="password" placeholder="Password" required>
<button type="submit" name="do_login">Log In</button>
</form>
</div>
<?php else: ?>
<div class="wrap">
<div class="side">
<div class="logo">1800<em>+</em>medics</div>
<a href="?page=dashboard" class="<?=$page==='dashboard'?'on':''?>">Dashboard</a>
<a href="?page=products" class="<?=in_array($page,['products','edit_product'])?'on':''?>">Products</a>
<a href="?page=orders" class="<?=in_array($page,['orders','view_order'])?'on':''?>">Orders</a>
<div class="sep"></div>
<a href="?page=payments" class="<?=$page==='payments'?'on':''?>">Payment Methods</a>
<a href="?page=shipping" class="<?=$page==='shipping'?'on':''?>">Shipping</a>
<a href="?page=announcement" class="<?=$page==='announcement'?'on':''?>">Notification Bar</a>
<a href="?page=contact" class="<?=$page==='contact'?'on':''?>">Contact / Imprint</a>
<a href="?page=smtp" class="<?=$page==='smtp'?'on':''?>">SMTP / Email</a>
<div class="sep"></div>
<a href="?page=pages" class="<?=in_array($page,['pages','edit_page'])?'on':''?>">Edit Pages</a>
<a href="?page=security" class="<?=$page==='security'?'on':''?>">Security</a>
<div class="sep"></div>
<a href="/" target="_blank">View Site</a>
<a href="?logout=1">Log Out</a>
</div>
<div class="mn">

<?php if ($msg): ?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<?php
// ============================================================
// DASHBOARD
// ============================================================
if ($page === 'dashboard'):
    $products = load_products();
    $orders = load_json(ORDERS_FILE);
    $pub = count(array_filter($products, fn($p) => $p['published'] ?? true));
?>
<h1>Dashboard</h1>
<div class="stats">
<div class="st"><div class="n"><?=count($products)?></div><div class="l">Products</div></div>
<div class="st"><div class="n"><em><?=$pub?></em></div><div class="l">Published</div></div>
<div class="st"><div class="n"><?=count($products)-$pub?></div><div class="l">Draft</div></div>
<div class="st"><div class="n"><em><?=count($orders)?></em></div><div class="l">Orders</div></div>
</div>
<div class="cd"><h2>Recent Orders</h2>
<?php if (empty($orders)): ?><p style="color:#6b7280">No orders yet.</p>
<?php else: ?>
<table><tr><th>No.</th><th>Customer</th><th>Amount</th><th>Payment</th><th>Status</th></tr>
<?php foreach (array_slice(array_reverse($orders), 0, 10) as $o): ?>
<tr><td><?=htmlspecialchars($o['order_number']??'')?></td><td><?=htmlspecialchars(($o['first_name']??'').' '.($o['last_name']??''))?></td><td>&euro;<?=number_format($o['total']??0,2)?></td><td><?=htmlspecialchars($o['payment_method']??'')?></td><td><span class="bg bg-g"><?=htmlspecialchars($o['status']??'Neu')?></span></td></tr>
<?php endforeach; ?>
</table><?php endif; ?></div>

<?php
// ============================================================
// PRODUCTS
// ============================================================
elseif ($page === 'products'):
    $products = load_products();
    $search = $_GET['s'] ?? '';
    if ($search) $products = array_filter($products, fn($p) => stripos($p['name']??'',$search)!==false || stripos($p['brand']??'',$search)!==false);
    $products = array_values($products);
    $pp = 50; $pg = max(1,intval($_GET['pg']??1)); $tp = max(1,ceil(count($products)/$pp));
    $slice = array_slice($products, ($pg-1)*$pp, $pp);
?>
<h1>Products (<?=count(load_products())?>)</h1>
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px">
<form method="GET" style="display:flex;gap:6px"><input type="hidden" name="page" value="products"><input type="text" name="s" class="sb" placeholder="Search..." value="<?=htmlspecialchars($search)?>" style="margin:0"><button class="bt bt-o bt-s">Search</button></form>
<a href="?page=edit_product&slug=new" class="bt bt-r">+ New Product</a>
</div>
<div class="cd" style="overflow-x:auto">
<table><tr><th></th><th>Name</th><th>Price</th><th>Brand</th><th>Cat.</th><th>Status</th><th></th></tr>
<?php foreach ($slice as $p): ?>
<tr>
<td><?php if($p['image']):?><img src="<?=htmlspecialchars($p['image'])?>" class="thumb"><?php endif;?></td>
<td><a href="?page=edit_product&slug=<?=urlencode($p['slug'])?>"><?=htmlspecialchars(mb_substr($p['name'],0,45))?></a></td>
<td>&euro;<?=htmlspecialchars($p['price'])?></td>
<td><?=htmlspecialchars(mb_substr($p['brand'],0,20))?></td>
<td><?=htmlspecialchars(mb_substr($p['category'],0,15))?></td>
<td><?=($p['published']??true)?'<span class="bg bg-g">Live</span>':'<span class="bg bg-x">Draft</span>'?></td>
<td><a href="?page=edit_product&slug=<?=urlencode($p['slug'])?>" class="bt bt-o bt-s">Edit</a> <a href="?delete=<?=urlencode($p['slug'])?>" class="bt bt-d bt-s" onclick="return confirm('Delete?')">x</a></td>
</tr>
<?php endforeach; ?>
</table></div>
<?php if ($tp > 1): ?><div style="display:flex;gap:6px;justify-content:center;margin-top:14px"><?php for($i=1;$i<=$tp;$i++):?><a href="?page=products&pg=<?=$i?>&s=<?=urlencode($search)?>" class="bt <?=$i===$pg?'bt-r':'bt-o'?> bt-s"><?=$i?></a><?php endfor;?></div><?php endif; ?>

<?php
// ============================================================
// EDIT PRODUCT
// ============================================================
elseif ($page === 'edit_product'):
    $slug = $_GET['slug'] ?? 'new';
    $products = load_products();
    $p = ['slug'=>'','name'=>'','price'=>'','image'=>'','brand'=>'','category'=>'','description'=>'','published'=>true];
    if ($slug !== 'new') { foreach ($products as $pr) { if ($pr['slug'] === $slug) { $p = $pr; break; } } }
    if (empty($p['description']) && $slug !== 'new') {
        $f = SITE_ROOT . "/product/$slug/index.html";
        if (file_exists($f)) { $h = file_get_contents($f); if (preg_match('/<section class="product-description">.*?<div[^>]*>(.*?)<\/div>\s*<\/section>/s',$h,$m)) $p['description']=trim($m[1]); }
    }
?>
<h1><?=$slug==='new'?'New Product':'Edit Product'?></h1>
<form method="POST" class="cd">
<div class="fr"><div class="fg"><label>Slug (URL)</label><input name="slug" value="<?=htmlspecialchars($p['slug'])?>" <?=$slug!=='new'?'readonly':'required'?>></div><div class="fg"><label>Name</label><input name="name" value="<?=htmlspecialchars($p['name'])?>" required></div></div>
<div class="fr"><div class="fg"><label>Price (EUR)</label><input name="price" value="<?=htmlspecialchars($p['price'])?>"></div><div class="fg"><label>Brand</label><input name="brand" value="<?=htmlspecialchars($p['brand'])?>"></div></div>
<div class="fr"><div class="fg"><label>Category</label><select name="category"><?php foreach($all_categories as $c):?><option value="<?=$c?>" <?=($p['category']??'')===$c?'selected':''?>><?=$c?></option><?php endforeach;?></select></div><div class="fg"><label>Image URL</label><input name="image" value="<?=htmlspecialchars($p['image'])?>"></div></div>
<div class="fg"><label>Description (HTML)</label><textarea name="description" rows="12" style="font-family:monospace;font-size:12px"><?=htmlspecialchars($p['description']??'')?></textarea></div>
<div class="chk"><input type="checkbox" name="published" <?=($p['published']??true)?'checked':''?>> <label>Published</label></div>
<button type="submit" name="save_product" class="bt bt-r">Save</button> <a href="?page=products" class="bt bt-o" style="margin-left:6px">Cancel</a>
</form>

<?php
// ============================================================
// ORDERS
// ============================================================
elseif ($page === 'orders'):
    $orders = load_json(ORDERS_FILE);
?>
<h1>Orders (<?=count($orders)?>)</h1>
<div class="cd" style="overflow-x:auto">
<?php if(empty($orders)):?><p style="color:#6b7280">No orders yet.</p>
<?php else:?>
<table><tr><th>No.</th><th>Date</th><th>Customer</th><th>Email</th><th>Amount</th><th>Payment</th><th>Status</th><th></th></tr>
<?php foreach(array_reverse($orders,true) as $i=>$o):?>
<tr>
<td><strong><?=htmlspecialchars($o['order_number']??'')?></strong></td>
<td><?=htmlspecialchars($o['date']??'')?></td>
<td><?=htmlspecialchars(($o['first_name']??'').' '.($o['last_name']??''))?></td>
<td><?=htmlspecialchars($o['email']??'')?></td>
<td><strong>&euro;<?=number_format($o['total']??0,2)?></strong></td>
<td><?=htmlspecialchars($o['payment_method']??'')?></td>
<td><span class="bg <?php $st=$o['status']??'Neu'; echo $st==='Neu'?'bg-y':($st==='Bezahlt'?'bg-g':($st==='Versendet'?'bg-b':'bg-x'));?>"><?=htmlspecialchars($st)?></span></td>
<td><a href="?page=view_order&idx=<?=$i?>" class="bt bt-o bt-s">Details</a></td>
</tr>
<?php endforeach;?>
</table><?php endif;?></div>

<?php
// ============================================================
// VIEW ORDER
// ============================================================
elseif ($page === 'view_order'):
    $orders = load_json(ORDERS_FILE);
    $idx = intval($_GET['idx'] ?? -1);
    $o = $orders[$idx] ?? null;
    if (!$o) { echo '<p>Order not found.</p>'; } else:
?>
<h1>Order: <?=htmlspecialchars($o['order_number']??'')?></h1>
<div class="fr">
<div class="cd">
<h2>Customer Details</h2>
<p><strong>Name:</strong> <?=htmlspecialchars(($o['first_name']??'').' '.($o['last_name']??''))?></p>
<p><strong>Email:</strong> <?=htmlspecialchars($o['email']??'')?></p>
<p><strong>Phone:</strong> <?=htmlspecialchars($o['phone']??'')?></p>
<p><strong>Address:</strong> <?=htmlspecialchars(($o['street']??'').', '.($o['zip']??'').' '.($o['city']??'').', '.($o['country']??''))?></p>
<p><strong>Notes:</strong> <?=htmlspecialchars($o['notes']??'Keine')?></p>
</div>
<div class="cd">
<h2>Change Status</h2>
<form method="POST">
<input type="hidden" name="order_idx" value="<?=$idx?>">
<div class="fg"><label>Status</label><select name="order_status">
<?php foreach(['New','Paid','Shipped','Completed','Cancelled'] as $st):?>
<option value="<?=$st?>" <?=($o['status']??'Neu')===$st?'selected':''?>><?=$st?></option>
<?php endforeach;?></select></div>
<div class="fg"><label>Admin Notes</label><textarea name="admin_notes" rows="3"><?=htmlspecialchars($o['admin_notes']??'')?></textarea></div>
<button name="update_order" class="bt bt-r">Update</button>
</form>
</div>
</div>
<div class="cd"><h2>Ordered Products</h2>
<table><tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr>
<?php foreach(($o['items']??[]) as $item):?>
<tr><td><?=htmlspecialchars($item['name']??'')?></td><td><?=$item['qty']??1?></td><td>&euro;<?=number_format($item['price']??0,2)?></td><td>&euro;<?=number_format(($item['price']??0)*($item['qty']??1),2)?></td></tr>
<?php endforeach;?>
<tr><td colspan="3"><strong>Shipping</strong></td><td>&euro;<?=number_format($o['shipping']??0,2)?></td></tr>
<tr><td colspan="3"><strong style="font-size:16px">Total</strong></td><td><strong style="color:#e63946;font-size:16px">&euro;<?=number_format($o['total']??0,2)?></strong></td></tr>
</table></div>
<div class="cd"><h2>Payment</h2><p><strong>Method:</strong> <?=htmlspecialchars($o['payment_method']??'')?></p></div>
<?php
    // Check for screenshots
    $screenshots = glob(DATA_DIR . "/screenshots/{$o['order_number']}*");
    if ($screenshots):
?>
<div class="cd"><h2>Screenshots</h2>
<?php foreach($screenshots as $ss): $fn = basename($ss); ?>
<p><a href="data/screenshots/<?=$fn?>" target="_blank"><?=$fn?></a></p>
<?php endforeach;?></div>
<?php endif; endif; ?>

<?php
// ============================================================
// PAYMENT METHODS
// ============================================================
elseif ($page === 'payments'):
?>
<h1>Payment Methods</h1>
<p style="font-size:14px;color:#6b7280;margin:-16px 0 20px;">Payment methods are shown or hidden based on the customer's cart total. Set a minimum amount to control when each method appears. All amounts in EUR.</p>
<form method="POST">
<?php foreach($settings['payment_methods'] as $key => $pm): ?>
<div class="pm-card">
<div class="chk"><input type="checkbox" name="pay_<?=$key?>_enabled" <?=$pm['enabled']?'checked':''?>> <h3><?=htmlspecialchars($pm['label'])?></h3></div>
<div class="fr">
<div class="fg"><label>Label</label><input name="pay_<?=$key?>_label" value="<?=htmlspecialchars($pm['label'])?>"></div>
<div class="fg"><label>Minimum Amount (EUR, 0 = no minimum)</label><input name="pay_<?=$key?>_min" type="number" step="0.01" value="<?=$pm['min_amount']?>"></div>
</div>
<div class="fg"><label>Payment Details (shown on confirmation page)</label><textarea name="pay_<?=$key?>_details" rows="4"><?=htmlspecialchars($pm['details'])?></textarea></div>
</div>
<?php endforeach; ?>
<button name="save_payments" class="bt bt-r">Save Payment Methods</button>
</form>

<?php
// ============================================================
// SHIPPING
// ============================================================
elseif ($page === 'shipping'):
?>
<h1>Shipping</h1>
<form method="POST" class="cd">
<div class="fr">
<div class="fg"><label>Shipping Cost (EUR)</label><input name="shipping_flat" value="<?=htmlspecialchars($settings['shipping_flat'])?>"></div>
<div class="fg"><label>Free Shipping Above (EUR)</label><input name="shipping_free_above" value="<?=htmlspecialchars($settings['shipping_free_above'])?>"></div>
</div>
<div class="fg"><label>Crypto/Paysafecard Only Below (EUR)</label><input name="crypto_only_below" value="<?=htmlspecialchars($settings['crypto_only_below'])?>"></div>
<div class="fg"><label>Shipping Notice</label><textarea name="shipping_text" rows="3"><?=htmlspecialchars($settings['shipping_text'])?></textarea></div>
<button name="save_shipping" class="bt bt-r">Save Shipping</button>
</form>

<?php
// ============================================================
// ANNOUNCEMENT BAR
// ============================================================
elseif ($page === 'announcement'):
?>
<h1>Notification Bar</h1>
<form method="POST" class="cd">
<div class="fg"><label>Text (separator: | )</label><textarea name="announcement_bar" rows="3"><?=htmlspecialchars($settings['announcement_bar'])?></textarea></div>
<p style="font-size:12px;color:#6b7280;margin-bottom:14px">Example: Sichere Payment per Bitcoin | Kostenloser Shipping ab 150&euro; | Diskretes Paket</p>
<button name="save_announcement" class="bt bt-r">Save</button>
</form>

<?php
// ============================================================
// CONTACT / IMPRESSUM
// ============================================================
elseif ($page === 'contact'):
?>
<h1>Contact / Imprint</h1>
<form method="POST" class="cd">
<h2>Contact Details (used on Imprint, Contact, Footer etc.)</h2>
<div class="fr">
<div class="fg"><label>Email</label><input name="email" value="<?=htmlspecialchars($settings['email'])?>"></div>
<div class="fg"><label>Phone</label><input name="phone" value="<?=htmlspecialchars($settings['phone'])?>"></div>
</div>
<div class="fg"><label>Owner / Company Name</label><input name="owner_name" value="<?=htmlspecialchars($settings['owner_name'])?>"></div>
<div class="fg"><label>Address</label><input name="owner_address" value="<?=htmlspecialchars($settings['owner_address'])?>"></div>
<button name="save_contact" class="bt bt-r">Save Contact Details</button>
</form>

<?php
// ============================================================
// SMTP
// ============================================================
elseif ($page === 'smtp'):
?>
<h1>SMTP / Email Settings</h1>
<form method="POST" class="cd">
<h2>SMTP Configuration</h2>
<div class="chk"><input type="checkbox" name="smtp_enabled" <?=$settings['smtp_enabled']?'checked':''?>> <label>SMTP Enabled</label></div>
<div class="fr3">
<div class="fg"><label>SMTP Host</label><input name="smtp_host" value="<?=htmlspecialchars($settings['smtp_host'])?>"></div>
<div class="fg"><label>Port</label><input name="smtp_port" value="<?=htmlspecialchars($settings['smtp_port'])?>"></div>
<div class="fg"><label>Encryption</label><select name="smtp_encryption"><option value="ssl" <?=$settings['smtp_encryption']==='ssl'?'selected':''?>>SSL</option><option value="tls" <?=$settings['smtp_encryption']==='tls'?'selected':''?>>TLS</option></select></div>
</div>
<div class="fr">
<div class="fg"><label>Username</label><input name="smtp_user" value="<?=htmlspecialchars($settings['smtp_user'])?>"></div>
<div class="fg"><label>Password</label><input type="password" name="smtp_pass" value="<?=htmlspecialchars($settings['smtp_pass'])?>"></div>
</div>
<div class="fg"><label>Sender Name</label><input name="smtp_from_name" value="<?=htmlspecialchars($settings['smtp_from_name'])?>"></div>
<div style="display:flex;gap:10px;margin-top:10px">
<button name="save_smtp" class="bt bt-r">Save SMTP</button>
<button name="test_smtp" class="bt bt-o">Send Test Email</button>
</div>
</form>
<div class="cd">
<h2>Screenshot Upload</h2>
<form method="POST">
<div class="chk"><input type="checkbox" name="screenshot_upload" <?=$settings['screenshot_upload']?'checked':''?>> <label>Screenshot Upload auf Confirmation Page aktivieren</label></div>
<div class="fg"><label>Upload Notice Text</label><textarea name="screenshot_text" rows="2"><?=htmlspecialchars($settings['screenshot_text'])?></textarea></div>
<div class="fg"><label>Confirmation Text</label><textarea name="order_confirmation_text" rows="2"><?=htmlspecialchars($settings['order_confirmation_text'])?></textarea></div>
<button name="save_settings" class="bt bt-r">Save</button>
</form>
</div>

<?php
// ============================================================
// PAGES
// ============================================================
elseif ($page === 'pages'):
    $pages_list = [
        ['Homepage', '/index.html'],
        ['Shop', '/shop/index.html'],
        ['Cart', '/warenkorb/index.html'],
        ['Checkout', '/kasse/index.html'],
        ['Confirmation Page', '/bestaetigung/index.html'],
        ['Impressum', '/impressum/index.html'],
        ['Datenschutz', '/datenschutz/index.html'],
        ['AGB', '/agb/index.html'],
        ['Widerrufsrecht', '/widerruf/index.html'],
        ['Shipping', '/versand/index.html'],
        ['Paymentsarten', '/zahlungsarten/index.html'],
        ['FAQ', '/faq/index.html'],
        ['Kontakt', '/kontakt/index.html'],
    ];
?>
<h1>Edit Pages</h1>
<div class="cd"><table><tr><th>Page</th><th>Path</th><th></th></tr>
<?php foreach($pages_list as $pg):?>
<tr><td><?=$pg[0]?></td><td><code style="font-size:12px"><?=$pg[1]?></code></td><td><a href="?page=edit_page&file=<?=urlencode($pg[1])?>" class="bt bt-o bt-s">Edit</a></td></tr>
<?php endforeach;?></table></div>

<?php
// ============================================================
// EDIT PAGE
// ============================================================
elseif ($page === 'edit_page'):
    $file = $_GET['file'] ?? '';
    $filepath = SITE_ROOT . $file;
    if (isset($_POST['save_page'])) { file_put_contents($filepath, $_POST['content']); $msg = 'Page saved!'; echo '<div class="msg">Page saved!</div>'; }
    $content = file_exists($filepath) ? file_get_contents($filepath) : '';
?>
<h1>Page: <?=htmlspecialchars($file)?></h1>
<form method="POST" class="cd">
<div class="fg"><textarea name="content" rows="35" style="font-family:monospace;font-size:12px"><?=htmlspecialchars($content)?></textarea></div>
<button name="save_page" class="bt bt-r">Save</button> <a href="?page=pages" class="bt bt-o" style="margin-left:6px">Back</a>
</form>

<?php
// ============================================================
// SECURITY
// ============================================================
elseif ($page === 'security'):
?>
<h1>Security</h1>
<div class="cd">
<h2>Change Credentials</h2>
<form method="POST">
<div class="fg"><label>New Username</label><input name="new_username" value="<?=htmlspecialchars($settings['admin_user'])?>"></div>
<div class="fr">
<div class="fg"><label>New Password (min. 8 chars)</label><input type="password" name="new_password" required></div>
<div class="fg"><label>Confirm Password</label><input type="password" name="confirm_password" required></div>
</div>
<button name="change_password" class="bt bt-r">Change Credentials</button>
</form>
</div>
<div class="cd">
<h2>Site Info</h2>
<p>Domain: <?=htmlspecialchars($settings['site_url'])?></p>
<p>E-Mail: <?=htmlspecialchars($settings['email'])?></p>
<p>Products: <?=count(load_products())?></p>
<p>Orders: <?=count(load_json(ORDERS_FILE))?></p>
<p>Admin: /admin/</p>
</div>

<?php
// ============================================================
// STATISTICS
// ============================================================
elseif ($page === 'stats'):
    $data_dir = DATA_DIR;
    $today = date('Y-m-d');
    $month = date('Y-m');
    
    // Load today stats
    $today_file = "$data_dir/stats_$today.json";
    $today_stats = file_exists($today_file) ? json_decode(file_get_contents($today_file), true) : ['views'=>0,'unique'=>[],'pages'=>[],'sources'=>[],'devices'=>[],'browsers'=>[],'hours'=>[],'hits'=>[]];
    
    // Load monthly stats
    $monthly_file = "$data_dir/stats_month_$month.json";
    $monthly = file_exists($monthly_file) ? json_decode(file_get_contents($monthly_file), true) : ['days'=>[]];
    
    // Calculate monthly totals
    $month_views = 0; $month_unique = 0;
    foreach ($monthly['days'] ?? [] as $d => $v) { $month_views += $v['views']; $month_unique += $v['unique']; }
    
    // Last 7 days
    $week_data = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $f = "$data_dir/stats_$d.json";
        $ds = file_exists($f) ? json_decode(file_get_contents($f), true) : ['views'=>0,'unique'=>[]];
        $week_data[] = ['date' => date('d.m', strtotime($d)), 'views' => $ds['views'], 'unique' => count($ds['unique'] ?? [])];
    }
?>
<h1>Statistics</h1>
<div class="stats">
<div class="st"><div class="n"><em><?=$today_stats['views']?></em></div><div class="l">Page Views Today</div></div>
<div class="st"><div class="n"><?=count($today_stats['unique'])?></div><div class="l">Visitors Today</div></div>
<div class="st"><div class="n"><em><?=$month_views?></em></div><div class="l">Views This Month</div></div>
<div class="st"><div class="n"><?=$month_unique?></div><div class="l">Visitors This Month</div></div>
</div>

<div class="fr">
<div class="cd">
<h2>Last 7 Days</h2>
<table><tr><th>Date</th><th>Views</th><th>Visitors</th></tr>
<?php foreach ($week_data as $wd): ?>
<tr><td><?=$wd['date']?></td><td><strong><?=$wd['views']?></strong></td><td><?=$wd['unique']?></td></tr>
<?php endforeach; ?>
</table>
</div>

<div class="cd">
<h2>Devices Today</h2>
<table><tr><th>Device</th><th>Views</th></tr>
<?php arsort($today_stats['devices']); foreach ($today_stats['devices'] as $d => $n): ?>
<tr><td><?=htmlspecialchars($d)?></td><td><?=$n?></td></tr>
<?php endforeach; ?>
<?php if (empty($today_stats['devices'])): ?><tr><td colspan="2" style="color:#6b7280">No data yet</td></tr><?php endif; ?>
</table>
</div>
</div>

<div class="fr">
<div class="cd">
<h2>Top Pages Today</h2>
<table><tr><th>Page</th><th>Views</th></tr>
<?php arsort($today_stats['pages']); $i=0; foreach ($today_stats['pages'] as $p => $n): if (++$i > 20) break; ?>
<tr><td><code style="font-size:12px"><?=htmlspecialchars($p)?></code></td><td><?=$n?></td></tr>
<?php endforeach; ?>
<?php if (empty($today_stats['pages'])): ?><tr><td colspan="2" style="color:#6b7280">No data yet</td></tr><?php endif; ?>
</table>
</div>

<div class="cd">
<h2>Sources Today</h2>
<table><tr><th>Source</th><th>Views</th></tr>
<?php arsort($today_stats['sources']); foreach ($today_stats['sources'] as $s => $n): ?>
<tr><td><?=htmlspecialchars($s)?></td><td><?=$n?></td></tr>
<?php endforeach; ?>
<?php if (empty($today_stats['sources'])): ?><tr><td colspan="2" style="color:#6b7280">No data yet</td></tr><?php endif; ?>
</table>
</div>
</div>

<div class="fr">
<div class="cd">
<h2>Browsers Today</h2>
<table><tr><th>Browser</th><th>Views</th></tr>
<?php arsort($today_stats['browsers']); foreach ($today_stats['browsers'] as $b => $n): ?>
<tr><td><?=htmlspecialchars($b)?></td><td><?=$n?></td></tr>
<?php endforeach; ?>
<?php if (empty($today_stats['browsers'])): ?><tr><td colspan="2" style="color:#6b7280">No data yet</td></tr><?php endif; ?>
</table>
</div>

<div class="cd">
<h2>Live Visitors (last 100)</h2>
<div style="max-height:300px;overflow-y:auto;">
<table><tr><th>Time</th><th>Page</th><th>Source</th><th>Device</th></tr>
<?php foreach (array_reverse($today_stats['hits'] ?? []) as $h): ?>
<tr><td><?=htmlspecialchars($h['time']??'')?></td><td><code style="font-size:11px"><?=htmlspecialchars(mb_substr($h['page']??'',0,35))?></code></td><td><?=htmlspecialchars($h['source']??'')?></td><td><?=htmlspecialchars($h['device']??'')?></td></tr>
<?php endforeach; ?>
<?php if (empty($today_stats['hits'])): ?><tr><td colspan="4" style="color:#6b7280">No data yet</td></tr><?php endif; ?>
</table>
</div>
</div>
</div>

<?php
// ============================================================
// VERIFICATION CODES
// ============================================================
elseif ($page === 'verification'):
?>
<h1>Tracking &amp; Verification Codes</h1>
<form method="POST">
<div class="cd">
<h2>Google Search Console</h2>
<div class="fg"><label>Verification Meta Tag (content value only)</label>
<input name="verify_google" value="<?=htmlspecialchars($settings['verify_google'] ?? '')?>" placeholder="z.B. AbCdEfG123456789">
</div>
<p style="font-size:12px;color:#6b7280;">Inserted as <code>&lt;meta name="google-site-verification" content="..."&gt;</code> eingefuegt.</p>
</div>

<div class="cd">
<h2>Bing Webmaster Tools</h2>
<div class="fg"><label>Verification Meta Tag (content value only)</label>
<input name="verify_bing" value="<?=htmlspecialchars($settings['verify_bing'] ?? '')?>" placeholder="z.B. 1234567890ABCDEF">
</div>
</div>

<div class="cd">
<h2>Google Analytics (GA4)</h2>
<div class="fg"><label>Measurement ID</label>
<input name="google_analytics" value="<?=htmlspecialchars($settings['google_analytics'] ?? '')?>" placeholder="z.B. G-XXXXXXXXXX">
</div>
<p style="font-size:12px;color:#6b7280;">The GA4 tracking script is automatically added to all pages.</p>
</div>

<div class="cd">
<h2>Facebook Pixel</h2>
<div class="fg"><label>Pixel ID</label>
<input name="verify_facebook_pixel" value="<?=htmlspecialchars($settings['verify_facebook_pixel'] ?? '')?>" placeholder="z.B. 123456789012345">
</div>
</div>

<div class="cd">
<h2>Pinterest</h2>
<div class="fg"><label>Pinterest Verification Tag (content value only)</label>
<input name="verify_pinterest" value="<?=htmlspecialchars($settings['verify_pinterest'] ?? '')?>" placeholder="e.g. abc123def456">
</div>
</div>

<div class="cd">
<h2>Microsoft Clarity</h2>
<div class="fg"><label>Clarity Project ID</label>
<input name="verify_clarity" value="<?=htmlspecialchars($settings['verify_clarity'] ?? '')?>" placeholder="e.g. abcdefghij">
</div>
<p style="font-size:12px;color:#6b7280;">The Clarity tracking script will be automatically added to all pages.</p>
</div>

<div class="cd">
<h2>Live Chat</h2>
<div class="chk"><input type="checkbox" name="livechat_enabled" <?=$settings['livechat_enabled']?'checked':''?>> <label>Enable Live Chat on all pages</label></div>
<div class="fg"><label>Live Chat Code (paste full script from Tawk.to, Tidio, LiveChat, Crisp, etc.)</label>
<textarea name="livechat_code" rows="8" style="font-family:monospace;font-size:12px;" placeholder="<!--Start of Tawk.to Script-->
<script type='text/javascript'>
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement('script'),s0=document.getElementsByTagName('script')[0];
s1.async=true;
s1.src='https://embed.tawk.to/YOUR_ID/default';
s1.charset='UTF-8';
s0.parentNode.insertBefore(s1,s0);
})();
</script>
<!--End of Tawk.to Script-->"><?=htmlspecialchars($settings['livechat_code'] ?? '')?></textarea>
</div>
<p style="font-size:12px;color:#6b7280;">Paste the full embed code from your live chat provider. When enabled, it appears on every page of the site.</p>
</div>

<div class="cd">
<h2>Custom Head Code</h2>
<div class="fg"><label>Any code for &lt;head&gt; (HTML/JS)</label>
<textarea name="custom_head_code" rows="6" style="font-family:monospace;font-size:12px;"><?=htmlspecialchars($settings['custom_head_code'] ?? '')?></textarea>
</div>
<p style="font-size:12px;color:#6b7280;">Added to the &lt;head&gt; of all pages. Suitable for Tawk.to, Hotjar, or other tracking codes.</p>
</div>

<div class="cd">
<h2>Local Tracking</h2>
<p style="font-size:14px;color:#2d3748;margin-bottom:12px;">Local visitor tracking is automatically active. It records page views, visitors, sources, devices and browsers without external services.</p>
<p style="font-size:14px;color:#2d3748;">View data: <a href="?page=stats" style="color:#e63946;font-weight:700;">Statistics</a></p>
<p style="font-size:12px;color:#6b7280;margin-top:12px;">The tracker stores no personal data. IP addresses are anonymized (daily rotating hash). No cookies required.</p>
</div>

<button name="save_verification" class="bt bt-r">Save All Codes</button>
</form>

<?php endif; ?>

</div></div>
<?php endif; ?>
</body></html>
