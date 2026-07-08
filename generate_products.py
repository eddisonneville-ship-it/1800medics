#!/usr/bin/env python3
"""
1800MEDICS.DE — PRODUCT PAGE GENERATOR
=======================================
Generates individual product HTML pages from WooCommerce CSV export.

Usage:
    python3 generate_products.py wc-product-export.csv

This will create:
    - /product/[slug]/index.html for each published product
    - /category/[slug]/index.html for each category
    - /brand/[slug]/index.html for each brand
    - /shop/index.html updated with all products
    - /sitemap.xml with all URLs
"""

import csv, re, os, sys

SITE_DIR = os.path.dirname(os.path.abspath(__file__))

def g(r, k):
    v = r.get(k, '')
    return v.strip() if v else ''

# Read shared template parts
def get_header(title, meta_desc, canonical=''):
    return f'''<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta property="og:locale" content="de_DE">
<title>{title}</title>
<meta name="description" content="{meta_desc}">
<link rel="canonical" href="https://1800medics.de{canonical}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/style.css">
</head>
<body>

<div class="announcement-bar"><div class="container"><div class="inner">
<div class="ann-item"><span class="ann-dot"></span><span class="hl">Sichere Zahlung</span> per Bitcoin, SEPA &amp; Krypto</div>
<div class="ann-item"><span class="ann-dot"></span><span class="hl">Kostenloser Versand</span> ab 150&euro;</div>
<div class="ann-item"><span class="ann-dot"></span><span class="hl">Diskretes Paket</span> garantiert</div>
</div></div></div>

<header><div class="container"><div class="header-inner">
<a href="/" class="logo"><span>1800</span><span class="logo-cross">+</span><span>medics</span></a>
<nav class="main-nav"><ul>
<li><a href="/shop/">Shop</a></li>
<li><a href="/category/retatrutide/">Retatrutide</a></li>
<li><a href="#">Peptide <span class="nav-arrow">&#9662;</span></a><div class="dropdown"><a href="/category/glp-1-peptide/">GLP-1 Peptide</a><a href="/category/hgh-peptide/">HGH Peptide</a><a href="/category/heilpeptide/">Heilpeptide</a><a href="/category/sarms/">SARMs</a></div></li>
<li><a href="/category/injizierbare-steroide/">Injizierbare Steroide</a></li>
<li><a href="#">Mehr <span class="nav-arrow">&#9662;</span></a><div class="dropdown"><a href="/category/orale-steroide/">Orale Steroide</a><a href="/category/pct/">PCT</a><a href="/category/abnehmen/">Abnehmen</a><a href="/category/sexualgesundheit/">Sexualgesundheit</a></div></li>
</ul></nav>
<div class="header-actions">
<a href="/warenkorb/" class="icon-btn cart-wrap"><svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg><span class="cart-count" style="display:none">0</span></a>
<button class="hamburger" onclick="openMobileMenu()"><span></span><span></span><span></span></button>
</div></div></div></header>

<div class="mobile-menu" id="mobile-menu">
<div class="mobile-menu-hdr"><span class="logo"><span>1800</span><span class="logo-cross">+</span><span>medics</span></span><button class="mobile-close" onclick="closeMobileMenu()">x</button></div>
<div class="mob-nav-row"><a href="/shop/">Shop</a></div>
<div class="mob-nav-row"><a href="/category/retatrutide/">Retatrutide</a></div>
<a href="/category/glp-1-peptide/" class="mob-sub">GLP-1 Peptide</a>
<a href="/category/hgh-peptide/" class="mob-sub">HGH Peptide</a>
<a href="/category/sarms/" class="mob-sub">SARMs</a>
<div class="mob-nav-row"><a href="/category/injizierbare-steroide/">Injizierbare Steroide</a></div>
<div class="mob-nav-row"><a href="/category/orale-steroide/">Orale Steroide</a></div>
<div class="mob-nav-row"><a href="/category/pct/">PCT</a></div>
<div class="mob-nav-row"><a href="/warenkorb/">Warenkorb</a></div>
</div>
'''

FOOTER = '''
<div class="custom-footer"><div class="cf-inner">
<div class="cf-disclaimer"><strong>Wichtiger Hinweis:</strong> Alle auf 1800Medics.de angebotenen Produkte sind ausschliesslich fuer Forschungs- und Laborzwecke bestimmt.</div>
<div class="cf-grid">
<div class="cf-about"><a href="/" class="cf-logo"><span>1800</span><span class="cf-logo-cross">+</span><span>medics</span></a><p>Peptide, Steroide und Forschungssubstanzen in Deutschland.</p><div class="cf-payments"><span class="cf-pay">Bitcoin</span><span class="cf-pay">Ethereum</span><span class="cf-pay">SEPA</span><span class="cf-pay">Paysafecard</span></div></div>
<div class="cf-col"><h4>Kategorien</h4><ul><li><a href="/category/retatrutide/">Retatrutide</a></li><li><a href="/category/glp-1-peptide/">GLP-1</a></li><li><a href="/category/hgh-peptide/">HGH</a></li><li><a href="/category/sarms/">SARMs</a></li><li><a href="/category/injizierbare-steroide/">Injizierbare</a></li><li><a href="/category/orale-steroide/">Orale</a></li><li><a href="/category/pct/">PCT</a></li></ul></div>
<div class="cf-col"><h4>Shop</h4><ul><li><a href="/shop/">Alle Produkte</a></li><li><a href="/blog/">Blog</a></li><li><a href="/warenkorb/">Warenkorb</a></li><li><a href="/faq/">FAQ</a></li><li><a href="/kontakt/">Kontakt</a></li></ul></div>
<div class="cf-col"><h4>Rechtliches</h4><ul><li><a href="/impressum/">Impressum</a></li><li><a href="/datenschutz/">Datenschutz</a></li><li><a href="/agb/">AGB</a></li><li><a href="/versand/">Versand</a></li><li><a href="/zahlungsarten/">Zahlungsarten</a></li><li><a href="/widerruf/">Widerrufsrecht</a></li></ul></div>
</div>
<div class="cf-bottom"><span>&copy; 2026 1800Medics.de</span><span class="right">Nicht fuer den menschlichen Konsum.</span></div>
</div></div>
<script src="/js/main.js"></script>
</body></html>'''

def make_slug(name):
    s = name.lower()
    s = re.sub(r'[^a-z0-9\s\-]', '', s)
    s = re.sub(r'\s+', '-', s.strip())
    s = re.sub(r'-+', '-', s)
    return s.strip('-')

def cat_slug(cat):
    c = cat.split('>')[-1].strip().lower()
    return make_slug(c)

def main():
    if len(sys.argv) < 2:
        print("Usage: python3 generate_products.py <product-export.csv>")
        sys.exit(1)
    
    csv_path = sys.argv[1]
    
    with open(csv_path, 'r', encoding='utf-8-sig') as f:
        products = list(csv.DictReader(f))
    
    # Filter published only
    published = [p for p in products if g(p, 'Published') == '1']
    print(f"Total products: {len(products)}")
    print(f"Published: {len(published)}")
    
    # Collect categories and brands
    categories = {}
    brands = {}
    all_cards = []
    sitemap_urls = []
    
    for p in published:
        name = g(p, 'Name')
        slug = g(p, 'SKU') or make_slug(name)
        price = g(p, 'Regular price')
        desc = g(p, 'Description')
        short = g(p, 'Short description')
        cat = g(p, 'Categories')
        brand = g(p, 'Brands') or g(p, 'Attribute 1 value(s)')
        image = g(p, 'Images').split(',')[0].strip() if g(p, 'Images') else ''
        display_name = name.replace(' kaufen', '')
        
        cat_s = cat_slug(cat) if cat else 'uncategorized'
        cat_label = cat.split('>')[-1].strip() if cat else 'Uncategorized'
        brand_s = make_slug(brand) if brand else ''
        
        # Product page
        product_html = get_header(
            f'{display_name} kaufen | 1800Medics.de',
            f'{display_name} kaufen bei 1800Medics.de. Diskreter Versand nach DE, AT, CH.',
            f'/product/{slug}/'
        )
        
        product_html += f'''
<div class="shop-hero"><div class="container"><div class="breadcrumb"><a href="/">Home</a> / <a href="/shop/">Shop</a> / <a href="/category/{cat_s}/">{cat_label}</a> / {display_name}</div><h1>{display_name}</h1></div></div>
<section class="product-page"><div class="container">
<div class="product-layout">
<div class="product-image">{'<img src="' + image + '" alt="' + display_name + '">' if image else '<div style="color:var(--gray-text);font-size:14px;">Bild folgt</div>'}</div>
<div class="product-details">
<h1>{display_name}</h1>
<div class="product-price-tag">&euro;{price}</div>
<div class="product-meta">
<span>Marke: <strong>{brand}</strong></span>
<span>Kategorie: <a href="/category/{cat_s}/">{cat_label}</a></span>
<span>Versand: Innerhalb von 24 Stunden</span>
</div>
<button class="btn btn-primary add-to-cart" style="width:100%;justify-content:center;" data-name="{name}" data-slug="{slug}" data-price="{price}" data-image="{image}">In den Warenkorb</button>
<p style="font-size:13px;color:var(--gray-text);margin-top:12px;">Kostenloser Versand ab 150&euro;. Sichere Zahlung per Bitcoin, SEPA, Krypto oder Paysafecard.</p>
</div>
</div>
</div></section>
<section class="product-description"><div class="container" style="max-width:900px;">{desc}</div></section>
'''
        product_html += FOOTER
        
        pdir = os.path.join(SITE_DIR, 'product', slug)
        os.makedirs(pdir, exist_ok=True)
        with open(os.path.join(pdir, 'index.html'), 'w', encoding='utf-8') as f:
            f.write(product_html)
        
        # Card HTML for listings
        card = f'''<a href="/product/{slug}/" class="prod-card" data-name="{display_name}" data-cat="{cat_s}" data-brand="{brand_s}">
<div class="prod-img">{'<img src="' + image + '" alt="' + display_name + '" loading="lazy">' if image else ''}</div>
<div class="prod-info"><div class="prod-name">{display_name}</div><div class="prod-price">&euro;{price}</div></div></a>'''
        
        all_cards.append(card)
        categories.setdefault(cat_s, {'label': cat_label, 'cards': []})['cards'].append(card)
        if brand_s:
            brands.setdefault(brand_s, {'label': brand, 'cards': []})['cards'].append(card)
        
        sitemap_urls.append(f'https://1800medics.de/product/{slug}/')
    
    print(f"Product pages: {len(published)}")
    
    # Category pages
    for slug, data in categories.items():
        cat_html = get_header(f'{data["label"]} kaufen | 1800Medics.de', f'{data["label"]} kaufen bei 1800Medics.de.', f'/category/{slug}/')
        cat_html += f'<div class="shop-hero"><div class="container"><div class="breadcrumb"><a href="/">Home</a> / <a href="/shop/">Shop</a> / {data["label"]}</div><h1>{data["label"]}</h1></div></div>'
        cat_html += f'<section style="padding:40px 0 80px;"><div class="container"><p style="color:var(--gray-text);margin-bottom:24px;">{len(data["cards"])} Produkte</p><div class="prod-grid prod-cols-4">{"".join(data["cards"])}</div></div></section>'
        cat_html += FOOTER
        cdir = os.path.join(SITE_DIR, 'category', slug)
        os.makedirs(cdir, exist_ok=True)
        with open(os.path.join(cdir, 'index.html'), 'w', encoding='utf-8') as f:
            f.write(cat_html)
        sitemap_urls.append(f'https://1800medics.de/category/{slug}/')
    print(f"Category pages: {len(categories)}")
    
    # Brand pages
    for slug, data in brands.items():
        brand_html = get_header(f'{data["label"]} kaufen | 1800Medics.de', f'{data["label"]} Produkte bei 1800Medics.de.', f'/brand/{slug}/')
        brand_html += f'<div class="shop-hero"><div class="container"><div class="breadcrumb"><a href="/">Home</a> / <a href="/shop/">Shop</a> / {data["label"]}</div><h1>{data["label"]}</h1></div></div>'
        brand_html += f'<section style="padding:40px 0 80px;"><div class="container"><p style="color:var(--gray-text);margin-bottom:24px;">{len(data["cards"])} Produkte</p><div class="prod-grid prod-cols-4">{"".join(data["cards"])}</div></div></section>'
        brand_html += FOOTER
        bdir = os.path.join(SITE_DIR, 'brand', slug)
        os.makedirs(bdir, exist_ok=True)
        with open(os.path.join(bdir, 'index.html'), 'w', encoding='utf-8') as f:
            f.write(brand_html)
        sitemap_urls.append(f'https://1800medics.de/brand/{slug}/')
    print(f"Brand pages: {len(brands)}")
    
    # Update shop page
    shop_html = get_header('Shop | 1800Medics.de', 'Alle Forschungssubstanzen bei 1800Medics.de.', '/shop/')
    shop_html += '<div class="shop-hero"><div class="container"><div class="breadcrumb"><a href="/">Home</a> / Shop</div><h1>Shop</h1></div></div>'
    shop_html += '<section style="padding:40px 0 80px;"><div class="container">'
    shop_html += '<div class="filter-bar">'
    shop_html += '<a href="/shop/" class="filter-btn active">Alle Produkte</a>'
    for slug, data in sorted(categories.items()):
        shop_html += f'<a href="/category/{slug}/" class="filter-btn">{data["label"]}</a>'
    shop_html += '</div>'
    shop_html += '<div class="search-bar"><input type="text" id="search-input" class="search-input" placeholder="Produkte suchen..."></div>'
    shop_html += f'<p style="color:var(--gray-text);margin-bottom:24px;">{len(all_cards)} Produkte</p>'
    shop_html += f'<div class="prod-grid prod-cols-4">{"".join(all_cards)}</div>'
    shop_html += '</div></section>'
    shop_html += FOOTER
    with open(os.path.join(SITE_DIR, 'shop', 'index.html'), 'w', encoding='utf-8') as f:
        f.write(shop_html)
    
    # Sitemap
    sitemap_urls.extend([
        'https://1800medics.de/',
        'https://1800medics.de/shop/',
        'https://1800medics.de/blog/',
        'https://1800medics.de/impressum/',
        'https://1800medics.de/datenschutz/',
        'https://1800medics.de/agb/',
        'https://1800medics.de/faq/',
        'https://1800medics.de/kontakt/',
        'https://1800medics.de/versand/',
        'https://1800medics.de/zahlungsarten/',
    ])
    
    sitemap = '<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n'
    for url in sitemap_urls:
        sitemap += f'  <url><loc>{url}</loc></url>\n'
    sitemap += '</urlset>'
    with open(os.path.join(SITE_DIR, 'sitemap.xml'), 'w') as f:
        f.write(sitemap)
    
    print(f"Sitemap: {len(sitemap_urls)} URLs")
    
    total = 0
    for root, dirs, files in os.walk(SITE_DIR):
        total += len([f for f in files if f.endswith('.html')])
    print(f"\nTotal HTML pages: {total}")
    print("Done!")

if __name__ == '__main__':
    main()
