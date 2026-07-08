<?php
date_default_timezone_set('Europe/Berlin');
/**
 * 1800MEDICS.DE — ORDER API
 * Sends branded HTML emails in German via SMTP
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false]); exit; }

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) { echo json_encode(['success' => false, 'error' => 'Invalid data']); exit; }

// Load settings
$settings_file = dirname(__DIR__) . '/admin/data/settings.json';
$settings = file_exists($settings_file) ? json_decode(file_get_contents($settings_file), true) : [];
$email_to = $settings['email'] ?? 'kontakt@1800medics.de';
$site_name = $settings['site_name'] ?? '1800Medics.de';

// Save order
$orders_file = dirname(__DIR__) . '/admin/data/orders.json';
$dir = dirname($orders_file);
if (!is_dir($dir)) mkdir($dir, 0755, true);
$orders = file_exists($orders_file) ? json_decode(file_get_contents($orders_file), true) : [];
$data['date'] = date('Y-m-d H:i:s');
$data['status'] = 'Neu';
$orders[] = $data;
file_put_contents($orders_file, json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Build email data
$on = htmlspecialchars($data['order_number'] ?? '');
$fn = htmlspecialchars($data['first_name'] ?? '');
$ln = htmlspecialchars($data['last_name'] ?? '');
$em = htmlspecialchars($data['email'] ?? '');
$ph = htmlspecialchars($data['phone'] ?? '');
$st = htmlspecialchars($data['street'] ?? '');
$zp = htmlspecialchars($data['zip'] ?? '');
$ct = htmlspecialchars($data['city'] ?? '');
$co = htmlspecialchars($data['country'] ?? '');
$pm = htmlspecialchars($data['payment_method'] ?? '');
$notes = htmlspecialchars($data['notes'] ?? '');
$items = $data['items'] ?? [];
$subtotal = $data['subtotal'] ?? 0;
$shipping = $data['shipping'] ?? 0;
$total = $data['total'] ?? 0;

// Payment method label
$pm_labels = [
    'sepa' => 'SEPA Ueberweisung',
    'bitcoin' => 'Bitcoin',
    'ethereum' => 'Ethereum',
    'crypto_other' => 'Kryptowaehrung',
    'paysafecard' => 'Paysafecard',
];
$pm_label = $pm_labels[$pm] ?? $pm;

// Payment details from settings
$pm_details = '';
if (isset($settings['payment_methods'][$pm]['details'])) {
    $pm_details = nl2br(htmlspecialchars($settings['payment_methods'][$pm]['details']));
    $pm_details = str_replace('[BESTELLNUMMER]', $on, $pm_details);
}

// Items HTML
$items_html = '';
foreach ($items as $item) {
    $iname = htmlspecialchars(str_replace(' kaufen', '', $item['name'] ?? ''));
    $iqty = intval($item['qty'] ?? 1);
    $iprice = floatval($item['price'] ?? 0);
    $items_html .= "<tr>
        <td style='padding:12px 16px;border-bottom:1px solid #e4e8ee;font-size:14px;color:#2d3748;'>$iname</td>
        <td style='padding:12px 16px;border-bottom:1px solid #e4e8ee;text-align:center;font-size:14px;color:#2d3748;'>$iqty</td>
        <td style='padding:12px 16px;border-bottom:1px solid #e4e8ee;text-align:right;font-size:14px;color:#2d3748;'>&euro;" . number_format($iprice, 2) . "</td>
        <td style='padding:12px 16px;border-bottom:1px solid #e4e8ee;text-align:right;font-size:14px;font-weight:700;color:#2d3748;'>&euro;" . number_format($iprice * $iqty, 2) . "</td>
    </tr>";
}

// ============================================================
// CUSTOMER EMAIL TEMPLATE
// ============================================================
$customer_email = email_template(
    "Bestellbestaetigung: $on",
    "Vielen Dank fuer Ihre Bestellung!",
    "
    <p style='font-size:16px;color:#2d3748;margin-bottom:8px;'>Hallo $fn,</p>
    <p style='font-size:15px;color:#6b7280;margin-bottom:24px;'>vielen Dank fuer Ihre Bestellung bei $site_name. Nachfolgend finden Sie eine Zusammenfassung Ihrer Bestellung.</p>
    
    <div style='background:#f7f8fa;border-radius:10px;padding:20px;margin-bottom:24px;'>
        <table style='width:100%;'>
            <tr><td style='font-size:13px;color:#6b7280;padding:4px 0;'>Bestellnummer:</td><td style='font-size:15px;font-weight:700;color:#e63946;padding:4px 0;'>$on</td></tr>
            <tr><td style='font-size:13px;color:#6b7280;padding:4px 0;'>Datum:</td><td style='font-size:14px;color:#2d3748;padding:4px 0;'>" . date('d.m.Y H:i') . "</td></tr>
            <tr><td style='font-size:13px;color:#6b7280;padding:4px 0;'>Zahlungsmethode:</td><td style='font-size:14px;color:#2d3748;padding:4px 0;'>$pm_label</td></tr>
        </table>
    </div>

    <h2 style='font-size:18px;font-weight:700;color:#0d1b2a;margin-bottom:16px;'>Bestellte Produkte</h2>
    <table style='width:100%;border-collapse:collapse;margin-bottom:20px;'>
        <tr>
            <th style='padding:10px 16px;text-align:left;font-size:12px;text-transform:uppercase;letter-spacing:0.5px;color:#6b7280;border-bottom:2px solid #e4e8ee;'>Produkt</th>
            <th style='padding:10px 16px;text-align:center;font-size:12px;text-transform:uppercase;letter-spacing:0.5px;color:#6b7280;border-bottom:2px solid #e4e8ee;'>Menge</th>
            <th style='padding:10px 16px;text-align:right;font-size:12px;text-transform:uppercase;letter-spacing:0.5px;color:#6b7280;border-bottom:2px solid #e4e8ee;'>Preis</th>
            <th style='padding:10px 16px;text-align:right;font-size:12px;text-transform:uppercase;letter-spacing:0.5px;color:#6b7280;border-bottom:2px solid #e4e8ee;'>Gesamt</th>
        </tr>
        $items_html
    </table>
    
    <table style='width:100%;max-width:300px;margin-left:auto;'>
        <tr><td style='padding:6px 0;font-size:14px;color:#6b7280;'>Zwischensumme</td><td style='padding:6px 0;text-align:right;font-size:14px;color:#2d3748;font-weight:600;'>&euro;" . number_format($subtotal, 2) . "</td></tr>
        <tr><td style='padding:6px 0;font-size:14px;color:#6b7280;'>Versand</td><td style='padding:6px 0;text-align:right;font-size:14px;color:#2d3748;font-weight:600;'>" . ($shipping == 0 ? 'Kostenlos' : '&euro;' . number_format($shipping, 2)) . "</td></tr>
        <tr><td style='padding:12px 0 6px;font-size:18px;font-weight:800;color:#0d1b2a;border-top:2px solid #0d1b2a;'>Gesamt</td><td style='padding:12px 0 6px;text-align:right;font-size:18px;font-weight:800;color:#e63946;border-top:2px solid #0d1b2a;'>&euro;" . number_format($total, 2) . "</td></tr>
    </table>

    <div style='background:#fef2f2;border-left:4px solid #e63946;border-radius:0 8px 8px 0;padding:20px;margin:28px 0;'>
        <h3 style='font-size:16px;font-weight:700;color:#0d1b2a;margin-bottom:10px;'>Zahlungsinformationen: $pm_label</h3>
        <p style='font-size:14px;color:#2d3748;line-height:1.8;margin:0;'>$pm_details</p>
        <p style='font-size:14px;color:#2d3748;margin-top:10px;'><strong>Verwendungszweck:</strong> $on</p>
    </div>

    <div style='background:#f7f8fa;border-radius:10px;padding:20px;margin-bottom:24px;'>
        <h3 style='font-size:15px;font-weight:700;color:#0d1b2a;margin-bottom:10px;'>Lieferadresse</h3>
        <p style='font-size:14px;color:#2d3748;margin:0;line-height:1.7;'>$fn $ln<br>$st<br>$zp $ct<br>$co</p>
    </div>

    <p style='font-size:14px;color:#6b7280;margin-bottom:8px;'>Nach Zahlungseingang wird Ihre Bestellung innerhalb von 24 Stunden versendet. Sie erhalten eine weitere E-Mail mit Ihrer Sendungsverfolgung.</p>
    <p style='font-size:14px;color:#6b7280;'>Bei Fragen kontaktieren Sie uns unter <a href='mailto:$email_to' style='color:#e63946;font-weight:600;'>$email_to</a></p>
    ",
    $site_name
);

// ============================================================
// ADMIN EMAIL (simpler)
// ============================================================
$admin_email = email_template(
    "Neue Bestellung: $on",
    "Neue Bestellung eingegangen",
    "
    <div style='background:#dcfce7;border-radius:8px;padding:16px;margin-bottom:20px;'>
        <p style='font-size:18px;font-weight:800;color:#16a34a;margin:0;'>Neue Bestellung: $on</p>
        <p style='font-size:14px;color:#166534;margin:4px 0 0;'>Betrag: &euro;" . number_format($total, 2) . " | Zahlung: $pm_label</p>
    </div>
    <h3 style='font-size:15px;color:#0d1b2a;margin-bottom:10px;'>Kundendaten</h3>
    <p style='font-size:14px;color:#2d3748;line-height:1.8;'>Name: $fn $ln<br>E-Mail: $em<br>Telefon: $ph<br>Adresse: $st, $zp $ct, $co</p>
    " . ($notes ? "<p style='font-size:14px;color:#2d3748;'><strong>Anmerkungen:</strong> $notes</p>" : "") . "
    <h3 style='font-size:15px;color:#0d1b2a;margin:20px 0 10px;'>Produkte</h3>
    <table style='width:100%;border-collapse:collapse;'>$items_html</table>
    <p style='font-size:16px;font-weight:800;color:#e63946;margin-top:16px;'>Gesamt: &euro;" . number_format($total, 2) . "</p>
    <p style='margin-top:20px;'><a href='" . ($settings['site_url'] ?? 'https://1800medics.de') . "/admin/?page=orders' style='display:inline-block;padding:12px 28px;background:#e63946;color:#fff;border-radius:8px;font-weight:700;text-decoration:none;'>Im Admin Panel ansehen</a></p>
    ",
    $site_name
);

// Send emails
$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: $site_name <" . ($settings['smtp_user'] ?? $email_to) . ">\r\n";

// To customer
@mail($em, "=?UTF-8?B?" . base64_encode("Bestellbestaetigung $on - $site_name") . "?=", $customer_email, $headers);

// To admin
@mail($email_to, "=?UTF-8?B?" . base64_encode("Neue Bestellung: $on - EUR " . number_format($total, 2)) . "?=", $admin_email, $headers);

echo json_encode(['success' => true, 'order_number' => $on]);

// ============================================================
// EMAIL TEMPLATE FUNCTION
// ============================================================
function email_template($subject, $headline, $content, $site_name) {
    return "
<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1.0'></head>
<body style='margin:0;padding:0;background:#f1f3f5;font-family:Arial,Helvetica,sans-serif;'>
<table width='100%' cellpadding='0' cellspacing='0' style='background:#f1f3f5;padding:32px 16px;'>
<tr><td align='center'>

<!-- HEADER -->
<table width='600' cellpadding='0' cellspacing='0' style='max-width:600px;width:100%;'>
<tr><td style='background:#0d1b2a;padding:24px 32px;border-radius:12px 12px 0 0;text-align:center;'>
    <span style='font-size:24px;font-weight:900;color:#ffffff;letter-spacing:-0.5px;'>1800<span style='display:inline-block;width:24px;height:24px;background:#e63946;color:#fff;border-radius:5px;text-align:center;line-height:24px;font-size:18px;margin:0 2px;vertical-align:middle;'>+</span>medics</span>
</td></tr>

<!-- ACCENT BAR -->
<tr><td style='background:#e63946;height:4px;'></td></tr>

<!-- HEADLINE -->
<tr><td style='background:#ffffff;padding:32px 32px 16px;text-align:center;'>
    <h1 style='font-size:24px;font-weight:800;color:#0d1b2a;margin:0;'>$headline</h1>
</td></tr>

<!-- CONTENT -->
<tr><td style='background:#ffffff;padding:0 32px 32px;'>
$content
</td></tr>

<!-- FOOTER -->
<tr><td style='background:#0d1b2a;padding:24px 32px;border-radius:0 0 12px 12px;text-align:center;'>
    <p style='font-size:12px;color:#8899aa;margin:0 0 8px;'>$site_name | Alle Produkte sind ausschliesslich fuer Forschungszwecke bestimmt.</p>
    <p style='font-size:11px;color:#5d7080;margin:0;'>&copy; " . date('Y') . " $site_name. Alle Rechte vorbehalten.</p>
</td></tr>
</table>

</td></tr>
</table>
</body>
</html>";
}
