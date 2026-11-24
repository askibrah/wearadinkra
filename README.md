# Wear Adinkra

Storefront for **Wear Adinkra** — Ghanaian heritage apparel. A static HTML/CSS/JS
front end backed by a small PHP API and MySQL.

## Stack

| Layer | Tech |
|---|---|
| Front end | Hand-written HTML, CSS and vanilla JS (`index.html`, `admin.html`, …) |
| API | PHP 8 (`api/`), session-based auth with CSRF tokens |
| Database | MySQL / MariaDB (`sql/`) |
| Payments | Paystack (inline popup) + PayPal / Stripe demo options |
| Notifications | SMTP or PHP `mail()`, Intek SMS for OTPs |

## Features

- Product catalogue, product detail pages, cart and checkout
- Customer accounts: registration, email/phone OTP activation, login, password reset
- Admin dashboard: products, orders, reviews, customers, SEO settings, image uploads
- Server-side Paystack initialisation and verification
- SEO: generated `robots.txt` / `sitemap.xml`, Open Graph and JSON-LD
- Rate limiting, brute-force lockout and CSRF protection on all mutating endpoints

## Local setup (WAMP)

1. Copy the config template and fill in your credentials:

   ```bash
   cp api/config.example.php api/config.php
   ```

   `api/config.php` is git-ignored — it holds DB credentials, the SMS API key and
   payment keys, so it must never be committed.

2. Import a database dump from `sql/` (phpMyAdmin → Import). `wearadinkra-clean.sql`
   is the fresh-install option.

3. Serve the folder from your web root and open `http://localhost/wearadinkra/`.
   Tables are created automatically on first request.

4. Make `api/storage/` writable by the web server (it holds logs and rate-limit state).

Deployment details and the full configuration reference are in
[DEPLOYMENT.md](DEPLOYMENT.md). API endpoints are documented in
[api/README.md](api/README.md).

## Structure

```
api/            PHP endpoints (auth, orders, products, admin, SEO, uploads)
assets/         Images, SVG symbols, JS
fonts/          Quicksand (SIL OFL)
sql/            Database dumps
*.html          Storefront and admin pages
```

## Licence

Proprietary — all rights reserved. The bundled Quicksand typeface is licensed
under the SIL Open Font License (see `fonts/OFL.txt`).
