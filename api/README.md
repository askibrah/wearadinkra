# Wear Adinkra — Secure Auth API (MySQL)

All requests are AJAX from `index.html`; the site entry stays `index.html`.

## Database
MySQL database `wearadinkra` is created automatically. Set credentials in `api/config.php` (default local WAMP: `root` / `root`). Tables: `users`, `otp_codes`, `activation_tokens`, `password_resets`, `login_attempts`.

## Endpoints (`auth.php`)
`GET ?action=csrf` returns the CSRF token. All other calls are POST JSON `{ action, csrf_token, ... }`:

- `register` (name, email, password; phone optional) → pending account; a verification code and activation link are emailed. If a phone is supplied, the same code is also sent by SMS.
- `verify_otp` (email, otp) → verifies the code and marks both channels verified.
- `resend_otp` (email)
- `activate` (token) → used by `activate.html?token=…`.
- `login` (`login` = email or phone, `password`) → accepts email, international phone, or local `0XXXXXXXXX`; 5 failed attempts lock that identifier/IP for 5 minutes.
- `forgot_request` (identifier = email or phone) → sends the same reset OTP to email and phone.
- `forgot_verify` (identifier, otp) → returns a single-use reset token.
- `forgot_reset` (reset_token, password) → updates the password.
- `review_submit` (product_id, order_id, author, rating, comment, images[≤5]) → saves a customer review (images are compact JPEG/PNG data URLs); `reviews_list?product_id=` lists reviews.
- `order_notify` (order_id, name, email, phone, total, method, items) → emails and texts the customer their order details (no login required).
- `logout`, `me`, `social` (provider, token).

## Configuration
- SMS: Intek key/URL/sender already set in `config.php`.
- Email: the branded templates in `api/mailer.php` include the logo, Kente strips and the same OTP/link sent by SMS. Delivery order on a live server:
  1. If `SMTP_HOST` is filled in `config.php`, authenticated SMTP is used (port 587/TLS or 465/SSL). Gmail requires an App Password.
  2. Otherwise PHP `mail()` is used.
  3. Every message is written to `storage/mail.log` for inspection.
- Local testing without a mail server: set `MAIL_LOG_ONLY` to `true` in `config.php`. Emails are logged and reported as sent, but nothing leaves the machine. Keep it `false` on the live server.
- Preview the exact email designs locally at `email-preview.php?kind=activation`, `?kind=otp` or `?kind=promotion` (sample data only, no real codes).
- Google/Facebook: paste IDs/secrets into the four constants in `config.php`. Until then `social` returns `not_configured`. After an OAuth redirect, open `index.html?social_provider=google&social_token=<token>` and the page exchanges it via AJAX.
- `DEV_MODE` is `false` by default. Set it to `true` temporarily to see OTPs and activation links in AJAX responses for local testing.

## Notifications (`notify.php`)
CSRF-protected POST JSON with `{ action, csrf_token, ... }`:
- `promotion_notify` (type, discount, start, end, productName) → emails every customer in `users` with the promotion type, discount, start date, end date and a shop link, using the branded logo template. Called automatically from the admin Promotions form.
- `test_email` (to, kind = `otp` | `activation` | `promotion`) → sends a sample email for delivery testing.

## Currency
`api/rate.php` fetches a free live GHS→USD rate (Google Finance, with an ExchangeRate-API fallback), caches it for one hour, and prices refresh on page load without a reload.

## Product catalogue
- MySQL `products` table (seeded from `products.json` on first use). `api/products.php` exposes `GET list` and admin-only `POST add` with up to 10 images and a YouTube/TikTok video link.
- All SVGs are copied to `assets/svg/` and product/header photos to `assets/products/`; the store now uses local assets.
- Admin "Add product" supports drag-and-drop image uploads (up to 10) with a progress bar, previews, and YouTube/TikTok video links. The modal stays open after publishing and plays a success sound + toast. Published products appear immediately on the store front.

## Admin panel
- `admin-login.html` — split-panel sign-in styled after the reference image. Demo logins: `admin@wearadinkra.com` / `manager@wearadinkra.com`, both with password `Admin123!` (seeded automatically). 5 failed attempts lock for 5 minutes. Password-strength meter, show/hide password, remember-me, forgot-password flow.
- `admin.html` — protected dashboard with Overview, Products, Orders, Customers, Reviews, Promotions, Website visits and Settings tabs. Products, orders, reviews and visits load from MySQL via AJAX. Order/product images open a media carousel modal with images, video and prices. Settings cover SEO, Google/Facebook OAuth credentials and notification toggles. Includes light/dark mode, collapsible sidebar, 5-per-page product pagination, coloured table headers, order status controls (reflected in customer tracking), and promotions that display discounted price above a struck-through original on the store front.
- `admin-reset.html` — reset link handler.
- `api/admin.php` — CSRF-protected AJAX endpoints: `login`, `me`, `forgot`, `reset`, `logout`. Admin sessions are separate from customer sessions.

## SEO
- `api/seo.php` stores SEO settings in MySQL (`seo_settings`), serves a public `meta` endpoint, and regenerates `robots.txt` + `sitemap.xml` on save. Settings cover index/follow switches, titles/description/keywords/canonical base, Open Graph and Twitter previews, theme colour/favicon, verification tokens, analytics IDs, and Organisation JSON-LD. The storefront injects the tags from the public endpoint; verification tokens are not exposed as JS configuration.

## Website visits
- `site_visits` table + `api/visits.php` (public `track`, admin-only `list`). Every storefront load records IP, approximate country/city, traffic source, page and device. The admin "Website visits" tab shows a daily visits chart and a detailed table of location, country, source, page and device.

## Notifications
- New `store_orders` table + `api/orders.php` (public `add` from checkout, `status` lookup, admin-only `list`, `status` update and `seen`). The admin header bell polls every 15 seconds, shows a red badge with the number of new orders, and lists recent orders; "Mark all read" clears the badge. Status changes made by admin are stored in MySQL and fetched by the customer's order tracking, which updates both the status badge and the progress stages.

## Payments
- Paystack test keys are configured server-side in `api/config.php`; the storefront loads the Paystack inline script on demand and opens the payment popup for Paystack orders. PayPal orders add a **1% processing fee** (50 GHS = 0.50 GHS, 100 GHS = 1 GHS) shown in the checkout summary and added to the total. Stripe is a demo option.

## Extras
- Floating **WhatsApp Support** button at bottom-right. Replace the placeholder number in the `.wa-support` link in `index.html`.
- The former newsletter area is now a "You may also like" product grid.
