<?php
date_default_timezone_set('Europe/Berlin');
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false]); exit; }

$order_number = $_POST['order_number'] ?? 'unknown';
$email = $_POST['email'] ?? '';
$payment = $_POST['payment_method'] ?? '';
$txid = $_POST['txid'] ?? '';
$coin = $_POST['coin'] ?? '';

$data_dir = dirname(__DIR__) . '/admin/data';
$upload_dir = "$data_dir/screenshots/";
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

// Save screenshot file
$saved_file = '';
if (isset($_FILES['screenshot']) && $_FILES['screenshot']['size'] > 0) {
    $ext = pathinfo($_FILES['screenshot']['name'], PATHINFO_EXTENSION) ?: 'png';
    $filename = $order_number . '_' . time() . '.' . $ext;
    move_uploaded_file($_FILES['screenshot']['tmp_name'], $upload_dir . $filename);
    $saved_file = $filename;
}

// Update order with payment confirmation data
$orders_file = "$data_dir/orders.json";
if (file_exists($orders_file)) {
    $orders = json_decode(file_get_contents($orders_file), true) ?: [];
    foreach ($orders as &$o) {
        if (($o['order_number'] ?? '') === $order_number) {
            if ($txid) $o['txid'] = $txid;
            if ($coin) $o['coin_selected'] = $coin;
            if ($saved_file) $o['screenshot'] = $saved_file;
            $o['payment_confirmed'] = date('Y-m-d H:i:s');
            break;
        }
    }
    file_put_contents($orders_file, json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Send notification email to admin
$settings_file = "$data_dir/settings.json";
$s = file_exists($settings_file) ? json_decode(file_get_contents($settings_file), true) : [];
$admin_email = $s['email'] ?? 'kontakt@1800medics.de';

$body = "Zahlungsbestaetigung eingegangen\n\n";
$body .= "Bestellung: $order_number\n";
if ($coin) $body .= "Kryptowaehrung: $coin\n";
if ($txid) $body .= "TXID: $txid\n";
if ($saved_file) $body .= "Screenshot: $saved_file\n";

$headers = "From: " . ($s['smtp_user'] ?? $admin_email) . "\r\n";
@mail($admin_email, "Zahlungsbestaetigung: $order_number", $body, $headers);

echo json_encode(['success' => true, 'file' => $saved_file]);
