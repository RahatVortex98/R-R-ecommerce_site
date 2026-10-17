# R&R Care BD

Laravel 12 + Livewire 4 storefront and Filament 5 administration for a Bangladesh cosmetics shop. Cash on delivery only. Uses your supplied circular R&R logo, forest green and peach branding.

## Start locally

Requirements: PHP 8.4+ with intl, mbstring, bcmath, DOM/XML, fileinfo, GD, ZIP, OpenSSL and PDO SQLite; PDO MySQL for production. Node.js 22+ and Composer 2. The lock file selects PHP 8.4 dependencies; PHP 8.2 is not sufficient for this exact locked installation.

```sh
composer install
npm ci
cp .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
php artisan storage:link
php artisan livewire:publish --assets
php artisan filament:assets
npm run build
php artisan app:create-owner
php artisan serve --host=127.0.0.1 --port=8000
```

On PowerShell use `Copy-Item .env.example .env` instead of `cp` if preferred. Set APP_URL to your actual local URL. The storefront is at `/`; administration is at `/admin`. Owner creation prompts for a password of at least 12 characters. No preset admin password or public registration is included. On Windows enable the required PHP extensions in your own PHP configuration if Composer reports a missing extension.

Optional demonstration catalog:

```sh
php artisan db:seed --class=DemoCatalogSeeder
```

This creates eight clearly labeled sample products and three sample articles, plus example delivery fees of BDT 60 inside Dhaka / BDT 120 outside. It creates no users. The illustrations, prices, stock quantities, product names and content are demonstrations, not real inventory or verified product claims. Replace or unpublish them before launch.

## Owner and staff workflow

- Products: create product, category, brand, descriptions, variants, prices, images and SEO metadata. Prices are entered in BDT and stored in paisa. Set publication only after the product is ready.
- Inventory: select a SKU, choose Adjust stock, enter a signed quantity and reason. New SKUs start at zero stock. Existing reservations cannot be removed by a stock adjustment. Stock history records the changes.
- Discounts: enter sale price below regular price and optional start/end dates. The admin uses Asia/Dhaka; storage uses UTC. Cards display original/current prices, savings and percent off. A product can appear in both homepage sections.
- Orders: view details and customer notes, write staff notes, update status, print packing slips, and restock inspected returns. Status and inventory cannot be edited through raw form fields.
- Articles: write content, choose author/publish date/status, link products in the text, select multiple related products, add concerns/tags and SEO fields. Draft/future articles are unavailable publicly. The rich-text sanitizer permits safe text formatting and links, not scripts or embedded executable content.
- Store settings (owner only): logo/colors, hero, homepage headings/counts, contact/social links, SEO description, notification email, and pending-order expiry.
- Delivery zones: enable areas and set fees. Checkout recalculates delivery and product totals on the server.
- Users (owner only): add staff or owners. Staff can manage catalog/content/orders but cannot manage accounts or store settings. Keep at least one owner.
- Dashboard: operational totals, low available stock, recent orders and date filters. Delivered order value is not proof of collected courier cash.

Adding new layouts, payment gateways, courier integrations or features requires code; dashboard customization covers the settings and content listed above.

## COD stock rules

On-hand = physical stock; reserved = submitted, unshipped orders; available = on-hand minus reserved.

1. Adding/changing/abandoning a cart does not change stock.
2. Placing an order locks requested SKUs in a database transaction and reserves quantities. If any item is unavailable, the entire submission is rejected. Duplicate submissions use the same checkout token and cannot reserve twice.
3. Pending orders expire after 24 hours by default. Confirmation removes expiry. Pending/confirmed cancellations and pending expiry release reservations once.
4. Dispatch deducts both on-hand and reserved quantities. Delivery does not deduct again.
5. Mark Returned only after the parcel is physically back. Inspect it and explicitly restock the sellable quantity; the system prevents restocking more than was returned. Each inspected-return action has a unique operation reference, so retries do not double-restock partial quantities.

Order line names, variant labels, prices, discounts, delivery fees and totals are snapshots. Later catalog edits do not rewrite an existing order. Orders are not deleted through the panel.

## Gmail and queued email

Your personal Gmail address can receive owner notifications: enter it in Store settings → Notification recipient. This does not connect Gmail or enable sending by itself. Mail defaults to the log driver. Optional customer email receives a confirmation when configured; email is not required to order.

For sending from an eligible Gmail account, enable Google two-step verification and generate an App Password. Keep it in the server environment, never code or admin settings. Use the Gmail address as MAIL_FROM_ADDRESS.

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=your-address@gmail.com
MAIL_PASSWORD="your-google-app-password"
MAIL_FROM_ADDRESS=your-address@gmail.com
MAIL_FROM_NAME="R&R Care BD"
```

Google account settings may make App Passwords unavailable. See https://support.google.com/accounts/answer/185833 . For sustained volume, configure a transactional mail provider through Laravel and retain your personal Gmail as the notification recipient. No Gmail account or credentials were connected during development.

Run these continuously through your host's process manager:

```sh
php artisan queue:work --tries=3 --timeout=60
```

Run the Laravel scheduler every minute using your host's scheduler/cron:

```sh
php artisan schedule:run
```

The scheduled `orders:expire` command releases overdue pending reservations. Notification recipients are snapshotted as durable delivery rows inside the order transaction. Email jobs dispatch after commit and record each recipient separately, so failure of owner email does not resend a successful customer email. The scheduled `orders:notify` command recovers undelivered notifications, including jobs that never entered the queue during an outage. You can also run it manually after correcting configuration. A mail failure does not undo an accepted order. Check failed jobs/logs and use `php artisan queue:retry all` after correcting a mail outage. SMTP is an at-least-once delivery mechanism: a provider timeout after acceptance can still produce a duplicate email on retry.

## SEO

Public HTML is server rendered. Product pages have Product/Offer data; articles have BlogPosting/author/date data; both include breadcrumbs, canonical URLs and social tags. Product, category and article slug edits record 301 redirects; category navigation follows your current categories. Normal paginated indexes preserve the page number in their canonical URL. Dedicated `/categories/{slug}` pages support category metadata and appear in the sitemap. Use `/sitemap.xml` and `/robots.txt` after configuring the real domain. Search/filter results, cart, checkout, admin and order confirmation are noindex. Confirmation omits personal customer details and uses an unguessable reference.

Homepage membership is briefly cached, invalidated by product/SKU/image edits, and expires at sale schedule boundaries. Prices are calculated from fresh model data. Settings cache is invalidated on saves. No review ratings are fabricated. Search rankings and rich results are not guaranteed.

Upload real optimized JPEG/PNG/WebP images (maximum 4 MB); add meaningful alt text. Product gallery uploads generate 400px and 800px WebP variants when the source is large enough; cards use responsive srcset. Images over 12 megapixels are not decoded for variants. Supply compressed, appropriately sized originals, especially for hero and blog images. Use accurate ingredients, suitability and directions rather than medical cure promises.

## Verification

```sh
php artisan test
npm run build
composer audit
```

The two MySQL integration tests skip unless explicitly enabled. They use separate processes to compete for the last unit and submit the same checkout token concurrently. Run them against a NEW disposable MySQL database, never the live shop database:

```dotenv
MYSQL_TEST_DATABASE=rr_care_test_inventory
MYSQL_TEST_HOST=127.0.0.1
MYSQL_TEST_PORT=3306
MYSQL_TEST_USERNAME=your_test_user
MYSQL_TEST_PASSWORD=your_test_password
```

Export those variables into your terminal environment, then run `php artisan test --testsuite=Integration`. The tests refuse database names without the `rr_care_test_` prefix and run migrate:fresh in the selected test database. They delete its tables. MySQL race verification is required before production release; SQLite tests do not prove MySQL row-lock behavior.

Development in the Codex Windows sandbox required local-only manifest and PHPUnit wrappers because PHP access checks report existing files as unreadable despite successful actual reads. Those scratch wrappers are outside this source package. On normal hosting, use the standard commands above. No vendor files were modified.

## Production deployment

Use a PHP host/VPS with MySQL, HTTPS, persistent uploads, queue supervision and a scheduler. Point the document root at `public/`. Sites Workers hosting does not run this PHP application.

1. Set APP_ENV=production, APP_DEBUG=false, APP_URL=https://your-domain, a unique APP_KEY and SESSION_SECURE_COOKIE=true.
2. Set DB_CONNECTION=mysql and the DB_HOST/PORT/DATABASE/USERNAME/PASSWORD values. Protect .env and restrict database credentials.
3. Install locked dependencies: `composer install --no-dev --optimize-autoloader`; build assets with `npm ci && npm run build` (separate commands in PowerShell if necessary).
4. Back up your database/uploads. Run `php artisan migrate --force`, `php artisan storage:link`, `php artisan livewire:publish --assets`, and `php artisan filament:assets`.
5. Ensure storage/ and bootstrap/cache/ are writable by the PHP process. Run `php artisan config:cache`, `php artisan route:cache`, and `php artisan view:cache` after correct configuration.
6. Create the owner with `php artisan app:create-owner`, configure real catalog/delivery fees/contact details, and unpublish sample content. Configure and verify real SMTP mail.
7. Configure queue supervision and minute scheduler. Monitor failed jobs, expiry execution, backups, disk space and logs; regularly test restoration. Keep uploaded files persistent across releases.
8. Pass MySQL concurrency tests, then place and cancel a real staging test order and verify stock, email, mobile checkout, canonical domain and sitemap before accepting customers.

The ZIP excludes .env, local SQLite data, test orders, logs, dependencies and Git history. It includes source, lock files and built frontend assets. Install dependencies before running it. Hosting/domain, real inventory and mail credentials still require owner configuration.


#   R - R - e c o m m e r c e _ s i t e  
 