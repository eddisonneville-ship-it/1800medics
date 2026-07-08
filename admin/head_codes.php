<?php
/**
 * Include this in any PHP-rendered page to add verification codes and tracking.
 * For static HTML pages, the codes need to be manually added or injected via JS.
 * 
 * Usage in PHP pages: <?php include 'admin/head_codes.php'; ?>
 * 
 * For static pages, the tracking is handled by /js/main.js (fetch to /api/track.php)
 * Verification codes for static pages must be added to the HTML manually
 * or use the generate_head_js.php endpoint below.
 */

$settings_file = __DIR__ . '/data/settings.json';
$s = file_exists($settings_file) ? json_decode(file_get_contents($settings_file), true) : [];

// Google Search Console
if (!empty($s['verify_google'])) {
    echo '<meta name="google-site-verification" content="' . htmlspecialchars($s['verify_google']) . '">' . "\n";
}

// Bing Webmaster
if (!empty($s['verify_bing'])) {
    echo '<meta name="msvalidate.01" content="' . htmlspecialchars($s['verify_bing']) . '">' . "\n";
}

// Google Analytics GA4
if (!empty($s['google_analytics'])) {
    $ga_id = htmlspecialchars($s['google_analytics']);
    echo "<script async src='https://www.googletagmanager.com/gtag/js?id=$ga_id'></script>\n";
    echo "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','$ga_id');</script>\n";
}

// Facebook Pixel
if (!empty($s['verify_facebook_pixel'])) {
    $px_id = htmlspecialchars($s['verify_facebook_pixel']);
    echo "<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','$px_id');fbq('track','PageView');</script>\n";
}

// Pinterest
if (!empty($s['verify_pinterest'])) {
    echo '<meta name="p:domain_verify" content="' . htmlspecialchars($s['verify_pinterest']) . '">' . "\n";
}

// Microsoft Clarity
if (!empty($s['verify_clarity'])) {
    $cl = htmlspecialchars($s['verify_clarity']);
    echo "<script>(function(c,l,a,r,i,t,y){c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};t=l.createElement(r);t.async=1;t.src='https://www.clarity.ms/tag/'+i;y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y)})(window,document,'clarity','script','$cl');</script>\n";
}

// Custom head code
if (!empty($s['custom_head_code'])) {
    echo $s['custom_head_code'] . "\n";
}
