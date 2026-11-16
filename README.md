# Anant Ceramic — Sales CRM + QR

One QR sticker per product, two views:

- **Customer** scans with any phone camera → `/q/AC1001` → sees only what admin allows (no login) → WhatsApp / call / request a quotation (becomes a lead).
- **Staff** scans inside the **Anant Staff app** (`/app`, mobile + PIN) → real price, stock, godown, batch → add to quotation → quotation PDF shared on WhatsApp on the spot.
- **Admin panel** (`/admin`) → dashboard, products (+ Excel bulk upload), scan-visibility control, QR label printing, staff & roles (discount limits), quotations & approvals, customers/leads, reports, settings.

Plain **PHP 8.1+ and MySQL** — no framework, no Composer, no Node build. Runs on BigRock shared Linux (cPanel) as-is.

## Logins (first install)

| | URL | Login |
|---|---|---|
| Admin | `/admin` | `admin@anantceramic.com` / `Anant@123` (or `ADMIN_EMAIL` / `ADMIN_PASSWORD`) — **change it in Settings** |
| Staff app (demo) | `/app` | `9800011201` · PIN `1234` (Rahul, Sales executive 5%) — also `9800011202` Kiran (Senior 8%) |
| Customer page | `/q/AC1001` | no login |

Demo products/staff/quotations load only when `seed_demo` is on. For the live client install set it to `false` (BigRock) or `SEED_DEMO=0` (Railway) **before the first visit**.

## Deploy on BigRock (cPanel, Linux shared hosting)

1. **PHP version**: cPanel → *Select PHP Version* → **8.1 or 8.2**, extensions `pdo_mysql`, `gd`, `mbstring` ticked.
2. **Database**: cPanel → *MySQL® Databases* → create database + user → *Add user to database* → ALL PRIVILEGES.
3. **Upload code**: upload this repo (zip → File Manager → Extract) into the domain's folder, e.g. `public_html/` for `crm.anantceramic.com` (sub-domain document root) — or any sub-folder; the app detects its base path.
4. **Config**: rename `config.local.sample.php` → `config.local.php` and fill DB name/user/password, admin email/password, `seed_demo => false`.
5. Open `https://crm.anantceramic.com/admin` — tables are created automatically on first visit (or import `core/schema.sql` in phpMyAdmin).
6. **SSL**: cPanel → *SSL/TLS Status* → AutoSSL. Then uncomment the HTTPS redirect in `.htaccess`. The staff camera scanner needs HTTPS.
7. **Settings → QR link base URL**: set the final link (e.g. `https://crm.anantceramic.com/q`) **before printing labels** — the QR on the sticker is permanent.
8. Make sure `uploads/` is writable (755/775). It already contains an `.htaccess` that blocks scripts.

If `anantceramic.com/q/...` (main website domain) should open the product page, add this to the main site's `.htaccess`:
`RewriteRule ^q/(.*)$ https://crm.anantceramic.com/q/$1 [R=302,L]` — or simply keep the QR base URL on the crm sub-domain.

## Deploy on Railway (testing)

The `Dockerfile` runs the same Apache + `.htaccess` setup. Add a MySQL service and set on the app service:
`MYSQLHOST=${{MySQL.MYSQLHOST}}`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE` (references), plus `ADMIN_PASSWORD`. Attach a volume at `/var/www/html/uploads` so photos survive redeploys.

## Staff app on Android

It is a PWA: open `/app` in Chrome → ⋮ → **Add to Home screen** → it opens full-screen like an app, with the camera scanner. (A Play-Store wrapper — TWA — can be built later from the same URL.)

## Key rules built in

- Purchase cost is admin-only — never shown on any scan.
- Staff view = visibility template (staff column) **and** the staff role's permissions (dealer price, min price, location…).
- Discount above the role limit, or a rate below the minimum price (unless the role allows it) → quotation goes to **Admin → Quotations** for Approve / Counter / Reject; staff sees the counter in the app and can accept it.
- Quotation numbers `QT-YYMM-0001`, validity and terms come from Quotation settings. Area → boxes is rounded up to whole boxes (optional wastage %).
- Bulk upload: `.xlsx`/`.csv` with the template columns; existing codes update, blank codes get the next code for the category.

## Folder map

```
index.php            front controller + routes
.htaccess            rewrites + blocks core/, views/, pages/
config.php           reads env vars / config.local.php
core/                bootstrap (helpers), schema.sql, installer, demo seed
pages/admin|staff|public   one file per screen
views/               shared layouts + quotation document
assets/              css, js (qrcode, jsQR scanner, SheetJS), icons
uploads/             product photos (writable)
```
