<?php
date_default_timezone_set('Europe/Berlin');
/**
 * 1800MEDICS.DE — ADMIN EMAIL FUNCTIONS
 * Sends branded status update and tracking emails in German via SMTP
 */

function send_admin_email($to, $subject, $headline, $content, $settings) {
    $site_name = $settings['site_name'] ?? '1800Medics.de';
    $from = $settings['smtp_user'] ?? $settings['email'] ?? 'kontakt@1800medics.de';
    $from_name = $settings['smtp_from_name'] ?? $site_name;

    $html = "<!DOCTYPE html><html><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1.0'></head>
<body style='margin:0;padding:0;background:#f1f3f5;font-family:Arial,Helvetica,sans-serif;'>
<table width='100%' cellpadding='0' cellspacing='0' style='background:#f1f3f5;padding:32px 16px;'>
<tr><td align='center'>
<table width='600' cellpadding='0' cellspacing='0' style='max-width:600px;width:100%;'>
<tr><td style='background:#0d1b2a;padding:24px 32px;border-radius:12px 12px 0 0;text-align:center;'>
    <span style='font-size:24px;font-weight:900;color:#ffffff;'>1800<span style='display:inline-block;width:24px;height:24px;background:#e63946;color:#fff;border-radius:5px;text-align:center;line-height:24px;font-size:18px;margin:0 2px;vertical-align:middle;'>+</span>medics</span>
</td></tr>
<tr><td style='background:#e63946;height:4px;'></td></tr>
<tr><td style='background:#ffffff;padding:32px 32px 16px;text-align:center;'>
    <h1 style='font-size:24px;font-weight:800;color:#0d1b2a;margin:0;'>$headline</h1>
</td></tr>
<tr><td style='background:#ffffff;padding:0 32px 32px;'>$content</td></tr>
<tr><td style='background:#0d1b2a;padding:24px 32px;border-radius:0 0 12px 12px;text-align:center;'>
    <p style='font-size:12px;color:#8899aa;margin:0 0 8px;'>$site_name | Alle Produkte sind ausschliesslich fuer Forschungszwecke bestimmt.</p>
    <p style='font-size:11px;color:#5d7080;margin:0;'>&copy; " . date('Y') . " $site_name. Alle Rechte vorbehalten.</p>
</td></tr>
</table>
</td></tr></table></body></html>";

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: $from_name <$from>\r\n";

    $encoded_subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
    return @mail($to, $encoded_subject, $html, $headers);
}

// ============================================================
// STATUS UPDATE EMAIL
// ============================================================
function send_status_email($order, $new_status, $settings) {
    $on = htmlspecialchars($order['order_number'] ?? '');
    $fn = htmlspecialchars($order['first_name'] ?? '');
    $email = $order['email'] ?? '';
    $total = $order['total'] ?? 0;
    $contact = $settings['email'] ?? 'kontakt@1800medics.de';

    $status_colors = [
        'Paid' => ['bg' => '#dcfce7', 'color' => '#16a34a', 'icon' => 'Zahlung erhalten'],
        'Shipped' => ['bg' => '#dbeafe', 'color' => '#1d4ed8', 'icon' => 'Paket unterwegs'],
        'Completed' => ['bg' => '#dcfce7', 'color' => '#16a34a', 'icon' => 'Zugestellt'],
        'Cancelled' => ['bg' => '#fee2e2', 'color' => '#dc2626', 'icon' => 'Storniert'],
    ];
    $sc = $status_colors[$new_status] ?? ['bg' => '#f1f3f5', 'color' => '#6b7280', 'icon' => $new_status];

    $status_messages = [
        'Paid' => "Wir haben Ihre Zahlung fuer die Bestellung <strong>$on</strong> erhalten. Ihre Bestellung wird nun fuer den Versand vorbereitet. Sie erhalten eine weitere E-Mail, sobald Ihr Paket versendet wurde.",
        'Versendet' => "Ihre Bestellung <strong>$on</strong> wurde versendet! Ihr Paket ist auf dem Weg zu Ihnen. Die Lieferung erfolgt in der Regel innerhalb von 2 bis 5 Werktagen.",
        'Abgeschlossen' => "Ihre Bestellung <strong>$on</strong> wurde erfolgreich abgeschlossen. Wir hoffen, dass Sie mit Ihren Produkten zufrieden sind.",
        'Storniert' => "Ihre Bestellung <strong>$on</strong> wurde storniert. Falls Sie Fragen haben, kontaktieren Sie uns bitte unter <a href='mailto:$contact' style='color:#e63946;'>$contact</a>.",
    ];
    $status_msg = $status_messages[$new_status] ?? "Der Status Ihrer Bestellung <strong>$on</strong> wurde auf <strong>$new_status</strong> aktualisiert.";

    $content = "
    <p style='font-size:16px;color:#2d3748;margin-bottom:20px;'>Hallo $fn,</p>
    
    <div style='background:{$sc['bg']};border-radius:10px;padding:20px;text-align:center;margin-bottom:24px;'>
        <p style='font-size:14px;font-weight:700;color:{$sc['color']};text-transform:uppercase;letter-spacing:1px;margin:0 0 6px;'>{$sc['icon']}</p>
        <p style='font-size:22px;font-weight:800;color:{$sc['color']};margin:0;'>$new_status</p>
    </div>

    <p style='font-size:15px;color:#2d3748;line-height:1.7;margin-bottom:20px;'>$status_msg</p>

    <div style='background:#f7f8fa;border-radius:10px;padding:16px;margin-bottom:24px;'>
        <table style='width:100%;'>
            <tr><td style='font-size:13px;color:#6b7280;padding:4px 0;'>Bestellnummer:</td><td style='font-size:14px;font-weight:700;color:#e63946;padding:4px 0;'>$on</td></tr>
            <tr><td style='font-size:13px;color:#6b7280;padding:4px 0;'>Gesamtbetrag:</td><td style='font-size:14px;font-weight:700;color:#2d3748;padding:4px 0;'>&euro;" . number_format($total, 2) . "</td></tr>
        </table>
    </div>

    <p style='font-size:14px;color:#6b7280;'>Bei Fragen kontaktieren Sie uns unter <a href='mailto:$contact' style='color:#e63946;font-weight:600;'>$contact</a></p>
    ";

    return send_admin_email($email, "Bestellung $on: $new_status", "Bestellstatus aktualisiert", $content, $settings);
}

// ============================================================
// TRACKING EMAIL
// ============================================================
function send_tracking_email($order, $tracking_number, $tracking_url, $carrier, $settings) {
    $on = htmlspecialchars($order['order_number'] ?? '');
    $fn = htmlspecialchars($order['first_name'] ?? '');
    $email = $order['email'] ?? '';
    $total = $order['total'] ?? 0;
    $contact = $settings['email'] ?? 'kontakt@1800medics.de';
    $tn = htmlspecialchars($tracking_number);
    $tu = htmlspecialchars($tracking_url);
    $cr = htmlspecialchars($carrier);

    $track_button = $tu ? "<a href='$tu' style='display:inline-block;padding:14px 36px;background:#e63946;color:#ffffff;border-radius:8px;font-size:16px;font-weight:700;text-decoration:none;margin:8px 0;'>Sendung verfolgen</a>" : '';

    $content = "
    <p style='font-size:16px;color:#2d3748;margin-bottom:20px;'>Hallo $fn,</p>
    
    <div style='background:#dbeafe;border-radius:10px;padding:20px;text-align:center;margin-bottom:24px;'>
        <p style='font-size:14px;font-weight:700;color:#1d4ed8;text-transform:uppercase;letter-spacing:1px;margin:0 0 6px;'>Paket unterwegs</p>
        <p style='font-size:22px;font-weight:800;color:#1d4ed8;margin:0;'>Versendet!</p>
    </div>

    <p style='font-size:15px;color:#2d3748;line-height:1.7;margin-bottom:20px;'>Ihre Bestellung <strong>$on</strong> wurde versendet! Ihr Paket ist auf dem Weg zu Ihnen.</p>

    <div style='background:#f7f8fa;border-radius:10px;padding:20px;margin-bottom:24px;'>
        <table style='width:100%;'>
            <tr><td style='font-size:13px;color:#6b7280;padding:6px 0;'>Bestellnummer:</td><td style='font-size:14px;font-weight:700;color:#e63946;padding:6px 0;'>$on</td></tr>
            <tr><td style='font-size:13px;color:#6b7280;padding:6px 0;'>Versanddienstleister:</td><td style='font-size:14px;font-weight:600;color:#2d3748;padding:6px 0;'>$cr</td></tr>
            <tr><td style='font-size:13px;color:#6b7280;padding:6px 0;'>Sendungsnummer:</td><td style='font-size:14px;font-weight:700;color:#0d1b2a;padding:6px 0;font-family:monospace;'>$tn</td></tr>
        </table>
    </div>

    <div style='text-align:center;margin:28px 0;'>
        $track_button
    </div>

    <p style='font-size:14px;color:#6b7280;line-height:1.7;margin-bottom:16px;'>Die Lieferung erfolgt in der Regel innerhalb von 2 bis 5 Werktagen. Alle Sendungen werden in neutraler Verpackung ohne Hinweise auf den Inhalt versendet.</p>

    <p style='font-size:14px;color:#6b7280;'>Bei Fragen kontaktieren Sie uns unter <a href='mailto:$contact' style='color:#e63946;font-weight:600;'>$contact</a></p>
    ";

    return send_admin_email($email, "Sendungsverfolgung: $on", "Ihr Paket ist unterwegs!", $content, $settings);
}
