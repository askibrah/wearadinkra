# Wear Adinkra — live server deployment checklist

## 1. Upload
Upload the whole project folder (all files and folders) to your hosting `public_html` / web root.

## 2. Database
Three ready-to-import SQL files are in `sql/`:

- `sql/wearadinkra-clean.sql` — **recommended for a fresh live site.** Creates the database, all tables, and loads the two admin accounts, the 5 live products, the reviews and the SEO settings. Test users, test orders and local visit logs are excluded.
- `sql/wearadinkra-cpanel-import.sql` — same content as the clean file but with the `CREATE DATABASE` / `USE` / `DROP DATABASE` statements removed. **Use this one on cPanel or any shared host**, where the database must be created in cPanel first and its name carries your account prefix (`cpuser_wearadinkra`). Select that database in phpMyAdmin's left sidebar, then Import.
- `sql/wearadinkra.sql` — full snapshot of the current local database (includes test accounts, orders and 400+ local visits). Use only if you deliberately want to carry all of that over.

Import through phpMyAdmin → **Import** → choose the file → **Go**.

Both files use `DROP DATABASE IF EXISTS` + `CREATE DATABASE`, so importing will replace an existing `wearadinkra` database. Back up first if you already have live data.

## 3. Configure `api/config.php` (required)
`api/config.php` holds your credentials and is **not** in the Git repository. Copy the template once per machine/server, then edit it:

```bash
cp api/config.example.php api/config.php
```

| Constant | What to do |
|---|---|
| `APP_URL` | Change to your real site URL, e.g. `https://wearadinkra.com/`. This is used for **activation links, email logo images, shop links and SEO canonical URLs** — leaving it as localhost will break all emailed links. |
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` | Your live MySQL credentials. |
| `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS`, `SMTP_SECURE` | Fill these to send email over SMTP (recommended). Gmail needs an App Password, port 587, `tls`. Leave `SMTP_HOST` empty to use PHP `mail()` instead. |
| `MAIL_FROM`, `MAIL_FROM_NAME` | Use an address that exists on your hosting account. **The domain must match the live site** (`wearadinkrah.com`) or SPF/DKIM will reject the mail. If it does not match `APP_URL`, the mailer automatically falls back to `no-reply@<site domain>` and logs the correction to `api/storage/mail.log`. |
| `DEV_MODE` | Keep `false` (never expose OTPs in API responses). |
| `MAIL_LOG_ONLY` | Keep `false`. Set `true` **only** for local testing with no mail server — emails are then written to `api/storage/mail.log` and reported as sent. |
| `PAYSTACK_PUBLIC_KEY`, `PAYSTACK_SECRET_KEY` | Replace the test keys with live keys before accepting payments. |
| `PAYSTACK_CA_BUNDLE` | Leave empty on most hosts. If checkout fails with `SSL certificate problem`, set this to the absolute path of your host's `cacert.pem`. |
| `SMS_API_KEY`, `SMS_API_URL`, `SMS_SENDER` | Paste your Intek API key. URL/sender defaults are pre-filled. |
| `SMS_CA_BUNDLE` | Leave empty on most hosts. If SMS fails with `SSL certificate problem`, set this to the absolute path of your host's `cacert.pem`. |

## 4. Permissions
Make these directories writable by the web server (usually `chmod 755`, or `775` if your host requires group write):

- `api/storage/` (mail.log, sms.log, orders.log)
- `assets/product-images/`
- `assets/adinkra-images/` and `assets/adinkra-images/masks/`

## 5. Admin accounts
Demo logins (change these immediately after first login):

- `admin@wearadinkra.com` / `Admin123!` (super admin)
- `manager@wearadinkra.com` / `Admin123!`

Change email / phone / password in **Admin → Settings → Account**.

## 6. SEO base URL
Open **Admin → Settings → SEO** and set **Base URL** to `https://wearadinkrah.com` (or leave it blank so it falls back to `APP_URL`). Saving regenerates `robots.txt` and `sitemap.xml`. If Base URL still says `localhost`, both files keep advertising `http://localhost/wearadinkra/` to search engines.

## 7. Test email and SMS
- Preview the exact email designs (no sending): `email-preview.php?kind=activation`, `?kind=otp`, `?kind=promotion`.
- Send a real test email: **Admin → Settings → Notifications → Send a test email**. Pick the address and the template; the result panel reports the exact sender address used and whether the message was actually sent or only logged.
  (Programmatically: POST JSON `{ "action": "test_email", "csrf_token": "<token from api/admin.php?action=csrf>", "to": "you@example.com", "kind": "otp" }` to `api/notify.php`. Requires an admin session — `kind` can be `otp`, `activation` or `promotion`.)
- Register a test customer account in the storefront: the activation email must contain the **same OTP** sent by SMS plus the activation link.
- Every email uses the branded template with the logo, Kente strips and footer. Delivery is logged to `api/storage/mail.log`; SMS to `api/storage/sms.log`.

## 8. Final smoke test
- Storefront: home/shop/story pages, product detail, cart, checkout, reviews, size guide, Adinkra carousel, search, tracking.
- Admin: login, products, orders (change an order status → customer gets an email), customers, reviews, promotions (creating one emails all customers), settings, notifications.
- Check `api/storage/*.log` and your browser console for errors.

## Known local-only notes
- On local WAMP, PHP `mail()` has no mail server, so emails are only logged. This is expected; configure SMTP or use live hosting for real delivery.
- On local WAMP the SMS gateway may fail with an SSL CA error. This normally resolves on a live host; `SMS_CA_BUNDLE` is available if it does not.
- Promotions are currently saved in the admin browser's `localStorage` (`wa_promotions`). They apply to that browser and are emailed to all DB customers on creation. If you need promotions to be global across all visitors, that requires a promotions table/API (not included in this build).
