window.SITE_CONFIG = {
    "currency": "EUR",
    "currency_symbol": "\u20ac",
    "shipping_flat": 25,
    "shipping_free_above": 150,
    "crypto_only_below": 100,
    "payment_methods": {
        "sepa": {
            "enabled": true,
            "label": "SEPA Ueberweisung",
            "min_amount": 100,
            "details": "Kontoinhaber: [Name eintragen]\nIBAN: [IBAN eintragen]\nBIC: [BIC eintragen]\nBank: [Bankname eintragen]",
            "screenshot_required": true
        },
        "bank_transfer": {
            "enabled": true,
            "label": "Bankueberweisung",
            "min_amount": 100,
            "details": "Kontoinhaber: [Name eintragen]\nIBAN: [IBAN eintragen]\nBIC: [BIC eintragen]\nBank: [Bankname eintragen]",
            "screenshot_required": true
        },
        "crypto": {
            "enabled": true,
            "label": "Kryptowaehrung",
            "min_amount": 0,
            "details": "",
            "screenshot_required": false,
            "txid_enabled": true,
            "coins": [
                {"name": "Bitcoin (BTC)", "wallet": ""},
                {"name": "Ethereum (ETH)", "wallet": ""},
                {"name": "USDT (TRC20)", "wallet": ""},
                {"name": "USDT (ERC20)", "wallet": ""},
                {"name": "Litecoin (LTC)", "wallet": ""}
            ]
        },
        "paysafecard": {
            "enabled": true,
            "label": "Paysafecard",
            "min_amount": 0,
            "details": "Senden Sie den Paysafecard-Code an:\nkontakt@1800medics.de",
            "screenshot_required": true
        }
    },
    "screenshot_upload": true,
    "screenshot_text": "Laden Sie einen Screenshot Ihrer Zahlung hoch.",
    "order_confirmation_text": "Vielen Dank fuer Ihre Bestellung! Wir bearbeiten Ihre Bestellung nach Zahlungseingang."
};
