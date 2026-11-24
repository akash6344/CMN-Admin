<?php

return [
    /*
     * Connection that owns `payments_plans` and `payment_transactions`
     * (the live CMNHousing website database, cmn_laravel_13).
     *   'live'   → LIVE_DB_* (MySQL) or the local database/live.sqlite fallback
     *   'sqlite' → this app's own DB (used by the test suite)
     */
    'connection' => env('PAYMENTS_DB_CONNECTION', 'live'),

    // Public URL of the website that stores invoice PDFs (payment-invoices/...).
    // e.g. https://cmnhousing.com/storage  → invoice link = {base}/{invoice}
    'invoice_base_url' => env('PAYMENTS_INVOICE_BASE_URL', ''),
];
