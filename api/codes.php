<?php
date_default_timezone_set('Europe/Berlin');
/**
 * Returns JavaScript that injects verification/tracking codes into the page.
 * Static HTML pages include this via: <script src="/api/codes.php"></script>
 * This way, verification codes set in admin panel apply to ALL pages automatically.
 */

header('Content-Type: application/javascript');
header('Cache-Control: public, max-age=300'); // cache 5 min

$settings_file = dirname(__DIR__) . '/admin/data/settings.json';
$s = file_exists($settings_file) ? json_decode(file_get_contents($settings_file), true) : [];

$output = '';

// Google Search Console
if (!empty($s['verify_google'])) {
    $v = addslashes($s['verify_google']);
    $output .= "var m=document.createElement('meta');m.name='google-site-verification';m.content='$v';document.head.appendChild(m);\n";
}

// Bing
if (!empty($s['verify_bing'])) {
    $v = addslashes($s['verify_bing']);
    $output .= "var mb=document.createElement('meta');mb.name='msvalidate.01';mb.content='$v';document.head.appendChild(mb);\n";
}

// Google Analytics
if (!empty($s['google_analytics'])) {
    $ga = addslashes($s['google_analytics']);
    $output .= "var gs=document.createElement('script');gs.async=true;gs.src='https://www.googletagmanager.com/gtag/js?id=$ga';document.head.appendChild(gs);\n";
    $output .= "window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','$ga');\n";
}

// Facebook Pixel
if (!empty($s['verify_facebook_pixel'])) {
    $px = addslashes($s['verify_facebook_pixel']);
    $output .= "!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','$px');fbq('track','PageView');\n";
}

// Pinterest
if (!empty($s['verify_pinterest'])) {
    $v = addslashes($s['verify_pinterest']);
    $output .= "var mp=document.createElement('meta');mp.name='p:domain_verify';mp.content='$v';document.head.appendChild(mp);\n";
}

// Microsoft Clarity
if (!empty($s['verify_clarity'])) {
    $cl = addslashes($s['verify_clarity']);
    $output .= "(function(c,l,a,r,i,t,y){c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};t=l.createElement(r);t.async=1;t.src='https://www.clarity.ms/tag/'+i;y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y)})(window,document,'clarity','script','$cl');\n";
}

// Custom head code (injected as-is)
if (!empty($s['custom_head_code'])) {
    // Wrap in a div and inject
    $code = str_replace("'", "\\'", str_replace("\n", "\\n", $s['custom_head_code']));
    $output .= "var cd=document.createElement('div');cd.innerHTML='$code';while(cd.firstChild){document.head.appendChild(cd.firstChild);}\n";
}

// Live Chat
if (!empty($s['livechat_enabled']) && !empty($s['livechat_code'])) {
    $chat = str_replace(["\r\n", "\r", "\n"], "", $s['livechat_code']);
    $chat = str_replace("'", "\'", $chat);
    $chat = str_replace("</script>", "<\/script>", $chat);
    $output .= "var lc=document.createElement('div');lc.innerHTML='$chat';var scripts=lc.getElementsByTagName('script');while(scripts.length){var ns=document.createElement('script');if(scripts[0].src)ns.src=scripts[0].src;else ns.textContent=scripts[0].textContent;ns.async=true;document.body.appendChild(ns);scripts[0].parentNode.removeChild(scripts[0]);}\n";
}

// WhatsApp floating button
if (!empty($s['whatsapp_enabled']) && !empty($s['whatsapp_number'])) {
    $wn = addslashes($s['whatsapp_number']);
    $wt = addslashes(urlencode($s['whatsapp_text'] ?? 'Hallo'));
    $output .= "
(function(){
var wa=document.createElement('a');
wa.href='https://wa.me/$wn?text='+decodeURIComponent('$wt');
wa.target='_blank';
wa.rel='noopener';
wa.style.cssText='position:fixed;bottom:24px;left:24px;z-index:9998;width:56px;height:56px;background:#25d366;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 16px rgba(0,0,0,0.2);transition:transform 0.2s;';
wa.onmouseover=function(){this.style.transform='scale(1.1)'};
wa.onmouseout=function(){this.style.transform='scale(1)'};
wa.innerHTML='<svg width="28" height="28" viewBox="0 0 24 24" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>';
document.body.appendChild(wa);
})();
";
}

echo $output ?: '// No tracking codes configured';
