// Load verification/tracking codes from admin panel
(function(){var s=document.createElement('script');s.src='/api/codes.php';document.head.appendChild(s);})();

// Load /js/config.js BEFORE this file for dynamic settings from admin panel
/* ============================================================
   1800MEDICS.DE — MAIN JAVASCRIPT
   Cart, Mobile Menu, Search, Toast, Checkout
   ============================================================ */

// ============================================================
// CART SYSTEM
// ============================================================
function getCart() {
    try { return JSON.parse(localStorage.getItem('cart1800') || '[]'); }
    catch { return []; }
}

function saveCart(cart) {
    localStorage.setItem('cart1800', JSON.stringify(cart));
    updateCartBadge();
}

function addToCart(name, slug, price, image) {
    const cart = getCart();
    const existing = cart.find(i => i.slug === slug);
    if (existing) {
        existing.qty += 1;
    } else {
        cart.push({ name, slug, price: parseFloat(price), image, qty: 1 });
    }
    saveCart(cart);
    showToast('Produkt hinzugefuegt');
}

function removeFromCart(slug) {
    let cart = getCart().filter(i => i.slug !== slug);
    saveCart(cart);
}

function updateQty(slug, qty) {
    const cart = getCart();
    const item = cart.find(i => i.slug === slug);
    if (item) {
        item.qty = Math.max(1, parseInt(qty));
    }
    saveCart(cart);
}

function getCartCount() {
    return getCart().reduce((sum, i) => sum + i.qty, 0);
}

function getCartTotal() {
    return getCart().reduce((sum, i) => sum + (i.price * i.qty), 0);
}

function getShipping(subtotal) {
    var cfg = window.SITE_CONFIG || {};
    var free = cfg.shipping_free_above || 150;
    var flat = cfg.shipping_flat || 25;
    return subtotal >= free ? 0 : flat;
}

function clearCart() {
    localStorage.removeItem('cart1800');
    updateCartBadge();
}

function generateOrderNumber() {
    return '1800M-' + Date.now();
}

function updateCartBadge() {
    const badges = document.querySelectorAll('.cart-count');
    const count = getCartCount();
    badges.forEach(b => {
        b.textContent = count;
        b.style.display = count > 0 ? 'flex' : 'none';
    });
}

// ============================================================
// TOAST NOTIFICATION
// ============================================================
function showToast(msg) {
    let toast = document.getElementById('toast-notification');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast-notification';
        toast.className = 'toast';
        document.body.appendChild(toast);
    }
    toast.textContent = msg;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2500);
}

// ============================================================
// MOBILE MENU
// ============================================================
function openMobileMenu() {
    const menu = document.getElementById('mobile-menu');
    if (menu) { menu.classList.add('open'); document.body.style.overflow = 'hidden'; }
}

function closeMobileMenu() {
    const menu = document.getElementById('mobile-menu');
    if (menu) { menu.classList.remove('open'); document.body.style.overflow = ''; }
}

// ============================================================
// ADD TO CART BUTTON HANDLER
// ============================================================
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.add-to-cart');
    if (btn) {
        e.preventDefault();
        const name = btn.dataset.name;
        const slug = btn.dataset.slug;
        const price = btn.dataset.price;
        const image = btn.dataset.image || '';
        addToCart(name, slug, price, image);
    }
});

// ============================================================
// CART PAGE RENDERER
// ============================================================
function renderCartPage() {
    const container = document.getElementById('cart-container');
    if (!container) return;

    const cart = getCart();

    if (cart.length === 0) {
        container.innerHTML = '<div class="cart-empty"><p>Ihr Warenkorb ist leer.</p><a href="/shop/" class="btn btn-primary">Zum Shop</a></div>';
        return;
    }

    const subtotal = getCartTotal();
    const shipping = getShipping(subtotal);
    const total = subtotal + shipping;

    let html = '';

    if (subtotal < 100 && subtotal > 0) {
        html += '<div class="cart-warning">Bestellungen unter 100\u20ac koennen nur per Kryptowaehrung oder Paysafecard bezahlt werden. SEPA ist erst ab 100\u20ac verfuegbar.</div>';
    }

    html += '<table class="cart-table"><thead><tr><th></th><th>Produkt</th><th>Preis</th><th>Menge</th><th>Gesamt</th><th></th></tr></thead><tbody>';

    cart.forEach(item => {
        html += `<tr data-slug="${item.slug}">
            <td><img src="${item.image}" alt="${item.name}" class="cart-item-img" onerror="this.style.display='none'"></td>
            <td class="cart-item-name"><a href="/product/${item.slug}/">${item.name.replace(' kaufen','')}</a></td>
            <td class="cart-item-price">\u20ac${item.price.toFixed(2)}</td>
            <td>
                <div class="qty-control">
                    <button class="qty-btn qty-minus" data-slug="${item.slug}">-</button>
                    <input type="text" class="qty-val" value="${item.qty}" data-slug="${item.slug}" readonly>
                    <button class="qty-btn qty-plus" data-slug="${item.slug}">+</button>
                </div>
            </td>
            <td class="cart-item-price">\u20ac${(item.price * item.qty).toFixed(2)}</td>
            <td><button class="cart-remove" data-slug="${item.slug}">x</button></td>
        </tr>`;
    });

    html += '</tbody></table>';

    html += `<div class="cart-totals"><table>
        <tr><td>Zwischensumme</td><td>\u20ac${subtotal.toFixed(2)}</td></tr>
        <tr><td>Versand</td><td>${shipping === 0 ? 'Kostenlos' : '\u20ac' + shipping.toFixed(2)}</td></tr>
        <tr class="total-row"><td>Gesamt</td><td>\u20ac${total.toFixed(2)}</td></tr>
    </table></div>`;

    html += '<div style="display:flex;justify-content:space-between;align-items:center;margin-top:24px;flex-wrap:wrap;gap:12px;">';
    html += '<a href="#" onclick="clearCart();renderCartPage();return false;" style="color:var(--gray-text);font-size:14px;">Warenkorb leeren</a>';
    html += '<a href="/kasse/" class="btn btn-primary">Zur Kasse</a>';
    html += '</div>';

    container.innerHTML = html;

    // Attach qty handlers
    container.querySelectorAll('.qty-plus').forEach(btn => {
        btn.addEventListener('click', () => { updateQty(btn.dataset.slug, getCart().find(i => i.slug === btn.dataset.slug).qty + 1); renderCartPage(); });
    });
    container.querySelectorAll('.qty-minus').forEach(btn => {
        btn.addEventListener('click', () => { const item = getCart().find(i => i.slug === btn.dataset.slug); if (item && item.qty > 1) { updateQty(btn.dataset.slug, item.qty - 1); renderCartPage(); } });
    });
    container.querySelectorAll('.cart-remove').forEach(btn => {
        btn.addEventListener('click', () => { removeFromCart(btn.dataset.slug); renderCartPage(); });
    });
}

// ============================================================
// CHECKOUT PAGE RENDERER
// ============================================================
function renderCheckoutPage() {
    const summaryEl = document.getElementById('checkout-summary');
    if (!summaryEl) return;

    const cart = getCart();
    if (cart.length === 0) {
        window.location.href = '/warenkorb/';
        return;
    }

    const subtotal = getCartTotal();
    const shipping = getShipping(subtotal);
    const total = subtotal + shipping;

    let html = '<h3>Bestelluebersicht</h3>';
    cart.forEach(item => {
        html += `<div class="summary-item"><span class="name">${item.name.replace(' kaufen','')}</span><span class="qty">x${item.qty}</span><span class="price">\u20ac${(item.price * item.qty).toFixed(2)}</span></div>`;
    });
    html += `<div class="summary-item"><span class="name">Versand</span><span class="price">${shipping === 0 ? 'Kostenlos' : '\u20ac' + shipping.toFixed(2)}</span></div>`;
    html += `<div class="summary-total"><span>Gesamt</span><span>\u20ac${total.toFixed(2)}</span></div>`;
    summaryEl.innerHTML = html;

    // Dynamic payment options from config
    var cfg = window.SITE_CONFIG || {};
    var pm = cfg.payment_methods || {};
    var payContainer = document.querySelector('.payment-options');
    if (payContainer) {
        payContainer.innerHTML = '';
        Object.keys(pm).forEach(function(key) {
            var m = pm[key];
            if (!m.enabled) return;
            var disabled = subtotal < (m.min_amount || 0);
            var div = document.createElement('div');
            div.className = 'payment-option' + (disabled ? ' payment-disabled' : '');
            div.innerHTML = '<input type="radio" name="payment" value="' + key + '" id="payment-' + key + '"' + (disabled ? ' disabled' : '') + '><label for="payment-' + key + '">' + m.label + '</label>';
            if (disabled) div.innerHTML += '<div class="payment-disabled-msg">Ab \u20ac' + (m.min_amount||0).toFixed(0) + ' verfuegbar</div>';
            payContainer.appendChild(div);
        });
        // Re-attach click handlers
        payContainer.querySelectorAll('.payment-option').forEach(function(opt) {
            opt.addEventListener('click', function() {
                if (this.classList.contains('payment-disabled')) return;
                payContainer.querySelectorAll('.payment-option').forEach(function(o) { o.classList.remove('selected'); });
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });
    }
}

function submitOrder(e) {
    e.preventDefault();

    const form = e.target;
    const firstName = form.querySelector('[name="first_name"]').value.trim();
    const lastName = form.querySelector('[name="last_name"]').value.trim();
    const email = form.querySelector('[name="email"]').value.trim();
    const phone = form.querySelector('[name="phone"]').value.trim();
    const street = form.querySelector('[name="street"]').value.trim();
    const zip = form.querySelector('[name="zip"]').value.trim();
    const city = form.querySelector('[name="city"]').value.trim();
    const country = form.querySelector('[name="country"]').value;
    const notes = form.querySelector('[name="notes"]').value.trim();
    const payment = form.querySelector('[name="payment"]:checked');

    if (!firstName || !lastName || !email || !phone || !street || !zip || !city) {
        showToast('Bitte alle Pflichtfelder ausfuellen');
        return;
    }
    if (!payment) {
        showToast('Bitte Zahlungsmethode waehlen');
        return;
    }

    const cart = getCart();
    const subtotal = getCartTotal();
    const shipping = getShipping(subtotal);
    const total = subtotal + shipping;
    const orderNumber = generateOrderNumber();

    const orderData = {
        order_number: orderNumber,
        first_name: firstName,
        last_name: lastName,
        email: email,
        phone: phone,
        street: street,
        zip: zip,
        city: city,
        country: country,
        notes: notes,
        payment_method: payment.value,
        items: cart,
        subtotal: subtotal,
        shipping: shipping,
        total: total
    };

    // Store for confirmation page
    localStorage.setItem('lastOrder', JSON.stringify(orderData));

    // Submit via SMTP
    const submitBtn = form.querySelector('[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Wird verarbeitet...';
    submitBtn.disabled = true;

    fetch('/api/order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(orderData)
    })
    .then(res => res.json())
    .then(result => {
        clearCart();
        window.location.href = '/bestaetigung/';
    })
    .catch(err => {
        // Fallback: still redirect, order is in localStorage
        console.error('SMTP error:', err);
        clearCart();
        window.location.href = '/bestaetigung/';
    });
}

// ============================================================
// CONFIRMATION PAGE RENDERER
// ============================================================
function renderConfirmationPage() {
    var container = document.getElementById('confirmation-container');
    if (!container) return;

    var order = JSON.parse(localStorage.getItem('lastOrder') || 'null');
    if (!order) {
        container.innerHTML = '<div class="cart-empty"><p>Keine Bestellung gefunden.</p><a href="/shop/" class="btn btn-primary">Zum Shop</a></div>';
        return;
    }

    var cfg = window.SITE_CONFIG || {};
    var pm = cfg.payment_methods || {};
    var method = pm[order.payment_method] || {};
    var screenshotRequired = method.screenshot_required || false;

    // Order summary
    var itemsHtml = '';
    (order.items || []).forEach(function(item) {
        itemsHtml += '<div class="summary-item"><span class="name">' + (item.name||'').replace(' kaufen','') + '</span><span class="qty">x' + item.qty + '</span><span class="price">\u20ac' + (item.price * item.qty).toFixed(2) + '</span></div>';
    });

    // Payment details HTML
    var payHtml = '';

    if (order.payment_method === 'crypto') {
        var coins = method.coins || [];
        var txidEnabled = method.txid_enabled || false;

        payHtml += '<h3>Kryptowaehrung</h3>';
        payHtml += '<p>Gesamtbetrag: <strong>\u20ac' + order.total.toFixed(2) + '</strong></p>';
        payHtml += '<p>Verwendungszweck: <span class="mono">' + order.order_number + '</span></p>';

        if (coins.length > 0) {
            payHtml += '<div style="margin-top:16px"><label style="font-size:14px;font-weight:700;color:#0d1b2a;display:block;margin-bottom:6px;">Waehrung waehlen:</label>';
            payHtml += '<select id="coin-select" style="width:100%;padding:10px 14px;border:1.5px solid #e4e8ee;border-radius:8px;font-size:15px;font-family:inherit;margin-bottom:12px;" onchange="showCoinWallet()">';
            payHtml += '<option value="">-- Bitte waehlen --</option>';
            coins.forEach(function(c, i) {
                payHtml += '<option value="' + i + '">' + (c.name||'') + '</option>';
            });
            payHtml += '</select></div>';
            payHtml += '<div id="wallet-display" style="display:none;margin-top:12px;">';
            payHtml += '<label style="font-size:14px;font-weight:700;color:#0d1b2a;display:block;margin-bottom:6px;">Wallet-Adresse:</label>';
            payHtml += '<div style="display:flex;gap:8px;align-items:center;">';
            payHtml += '<input type="text" id="wallet-address" readonly style="flex:1;padding:10px 14px;border:1.5px solid #e4e8ee;border-radius:8px;font-size:14px;font-family:monospace;background:#f7f8fa;">';
            payHtml += '<button onclick="copyWallet()" style="padding:10px 18px;background:#e63946;color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-family:inherit;white-space:nowrap;" id="copy-btn">Kopieren</button>';
            payHtml += '</div></div>';

            if (txidEnabled) {
                payHtml += '<div style="margin-top:16px;">';
                payHtml += '<label style="font-size:14px;font-weight:700;color:#0d1b2a;display:block;margin-bottom:6px;">Transaktions-ID (optional):</label>';
                payHtml += '<input type="text" id="txid-input" placeholder="TXID eingeben" style="width:100%;padding:10px 14px;border:1.5px solid #e4e8ee;border-radius:8px;font-size:14px;font-family:monospace;">';
                payHtml += '</div>';
            }
        }
    } else {
        var details = (method.details || '').replace(/\n/g, '<br>').replace('[BESTELLNUMMER]', order.order_number);
        payHtml += '<h3>' + (method.label || order.payment_method) + '</h3>';
        payHtml += '<p>Gesamtbetrag: <strong>\u20ac' + order.total.toFixed(2) + '</strong></p>';
        payHtml += '<p>Verwendungszweck: <span class="mono">' + order.order_number + '</span></p>';
        payHtml += '<div style="margin-top:12px;line-height:1.8;">' + details + '</div>';
    }

    // Screenshot section
    var screenshotHtml = '';
    if (cfg.screenshot_upload !== false) {
        var reqText = screenshotRequired ? ' <span style="color:#e63946;">*</span>' : '';
        screenshotHtml += '<div style="margin-top:28px;padding-top:24px;border-top:1px solid #e4e8ee;">';
        screenshotHtml += '<h3>Zahlungsnachweis hochladen' + reqText + '</h3>';
        screenshotHtml += '<p style="font-size:14px;color:#6b7280;margin-bottom:12px;">' + (cfg.screenshot_text || 'Laden Sie einen Screenshot Ihrer Zahlung hoch.') + '</p>';
        if (screenshotRequired) {
            screenshotHtml += '<p style="font-size:13px;color:#e63946;font-weight:600;margin-bottom:12px;">Pflichtfeld: Bitte laden Sie einen Zahlungsnachweis hoch, um fortzufahren.</p>';
        }
        screenshotHtml += '<input type="file" id="screenshot" accept="image/*,.pdf" onchange="onScreenshotSelected()" style="margin-bottom:12px;">';
        screenshotHtml += '<div id="upload-status"></div>';
        screenshotHtml += '</div>';
    }

    // Final CTA
    var ctaHtml = '<div style="margin-top:24px;text-align:center;">';
    ctaHtml += '<button id="paid-btn" class="btn btn-primary" style="width:100%;max-width:400px;justify-content:center;font-size:18px;padding:18px 36px;" onclick="confirmPaid()" ' + (screenshotRequired ? 'disabled style="width:100%;max-width:400px;justify-content:center;font-size:18px;padding:18px 36px;opacity:0.5;cursor:not-allowed;"' : '') + '>Ich habe bezahlt</button>';
    ctaHtml += '</div>';

    container.innerHTML = '<div class="confirm-box">' +
        '<h1>Vielen Dank fuer Ihre Bestellung!</h1>' +
        '<div class="order-num">Bestellnummer: ' + order.order_number + '</div>' +
        '<div style="text-align:left;margin-bottom:24px;">' + itemsHtml +
        '<div class="summary-item"><span class="name">Versand</span><span class="price">' + (order.shipping === 0 ? 'Kostenlos' : '\u20ac' + order.shipping.toFixed(2)) + '</span></div>' +
        '<div class="summary-total"><span>Gesamt</span><span>\u20ac' + order.total.toFixed(2) + '</span></div></div>' +
        '<div class="payment-instructions">' + payHtml + '</div>' +
        screenshotHtml + ctaHtml +
        '</div>';

    // Store coins data for wallet display
    if (order.payment_method === 'crypto') {
        window._coins = (method.coins || []);
    }
    window._screenshotRequired = screenshotRequired;
    window._screenshotUploaded = false;
}

function showCoinWallet() {
    var sel = document.getElementById('coin-select');
    var display = document.getElementById('wallet-display');
    var input = document.getElementById('wallet-address');
    var coins = window._coins || [];
    var idx = parseInt(sel.value);

    if (isNaN(idx) || !coins[idx]) {
        display.style.display = 'none';
        return;
    }

    input.value = coins[idx].wallet || 'Nicht konfiguriert';
    display.style.display = 'block';
}

function copyWallet() {
    var input = document.getElementById('wallet-address');
    var btn = document.getElementById('copy-btn');
    navigator.clipboard.writeText(input.value).then(function() {
        btn.textContent = 'Kopiert!';
        btn.style.background = '#16a34a';
        setTimeout(function() { btn.textContent = 'Kopieren'; btn.style.background = '#e63946'; }, 2000);
    }).catch(function() {
        input.select();
        document.execCommand('copy');
        btn.textContent = 'Kopiert!';
        btn.style.background = '#16a34a';
        setTimeout(function() { btn.textContent = 'Kopieren'; btn.style.background = '#e63946'; }, 2000);
    });
}

function onScreenshotSelected() {
    var file = document.getElementById('screenshot').files[0];
    if (file) {
        window._screenshotUploaded = true;
        var btn = document.getElementById('paid-btn');
        if (btn) { btn.disabled = false; btn.style.opacity = '1'; btn.style.cursor = 'pointer'; }
        document.getElementById('upload-status').innerHTML = '<p style="color:#16a34a;font-weight:600;">Datei ausgewaehlt: ' + file.name + '</p>';
    }
}

function confirmPaid() {
    if (window._screenshotRequired && !window._screenshotUploaded) {
        showToast('Bitte laden Sie einen Zahlungsnachweis hoch');
        return;
    }

    var order = JSON.parse(localStorage.getItem('lastOrder') || '{}');
    var btn = document.getElementById('paid-btn');
    btn.disabled = true;
    btn.textContent = 'Wird verarbeitet...';

    // Upload screenshot if selected
    var fileInput = document.getElementById('screenshot');
    var txidInput = document.getElementById('txid-input');

    var formData = new FormData();
    formData.append('order_number', order.order_number || 'unknown');
    formData.append('email', order.email || '');
    formData.append('payment_method', order.payment_method || '');
    if (txidInput && txidInput.value) formData.append('txid', txidInput.value);

    var coinSelect = document.getElementById('coin-select');
    if (coinSelect && coinSelect.value !== '') {
        var coins = window._coins || [];
        var idx = parseInt(coinSelect.value);
        if (coins[idx]) formData.append('coin', coins[idx].name);
    }

    if (fileInput && fileInput.files[0]) {
        formData.append('screenshot', fileInput.files[0]);
    }

    fetch('/api/upload.php', { method: 'POST', body: formData })
    .then(function(res) { return res.json(); })
    .then(function(result) {
        btn.textContent = 'Bestaetigt!';
        btn.style.background = '#16a34a';
        document.getElementById('upload-status').innerHTML = '<div class="upload-success" style="margin-top:12px;">Zahlungsnachweis erfolgreich uebermittelt. Wir bearbeiten Ihre Bestellung.</div>';
    })
    .catch(function(err) {
        btn.textContent = 'Bestaetigt!';
        btn.style.background = '#16a34a';
        document.getElementById('upload-status').innerHTML = '<div class="upload-success" style="margin-top:12px;">Bestellung bestaetigt. Vielen Dank!</div>';
    });
}



// ============================================================
// SEARCH FUNCTIONALITY
// ============================================================
function initSearch() {
    var searchInput = document.getElementById('search-input');
    if (!searchInput) return;

    searchInput.addEventListener('input', function() {
        var query = this.value.toLowerCase().trim();
        var cards = document.querySelectorAll('.prod-card');
        var visible = 0;
        cards.forEach(function(card) {
            var name = (card.getAttribute('data-name') || card.querySelector('.prod-name')?.textContent || '').toLowerCase();
            var show = name.indexOf(query) !== -1 || query === '';
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });
    });
}
