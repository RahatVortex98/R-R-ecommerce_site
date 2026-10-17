# R&R Care BD

A cosmetics ecommerce application for Bangladesh, built with **Laravel 12, Livewire 4, and Filament 5**. Customers browse products and care guides, then place guest orders using cash on delivery (COD). Store owners manage the catalog, content, orders, inventory, and branding through Filament.

The storefront uses the supplied R&R logo with forest green and peach branding.

> **Project status:** Working local application with sample content. Production launch requires real products, an owner account, configured delivery charges, verified email, PHP hosting, and MySQL concurrency verification. No Gmail account or public hosting is connected automatically.

## Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Run the application](#run-the-application)
- [Admin login and permissions](#admin-login-and-permissions)
- [Store management](#store-management)
- [COD orders and inventory](#cod-orders-and-inventory)
- [Gmail and order notifications](#gmail-and-order-notifications)
- [SEO and blog publishing](#seo-and-blog-publishing)
- [Tests and verification](#tests-and-verification)
- [Production deployment](#production-deployment)
- [Troubleshooting](#troubleshooting)
- [Project structure](#project-structure)

## Requirements

| Component | Requirement |
| --- | --- |
| PHP | **8.4+ compatible with the Composer lockfile**; PHP 8.4 is the development baseline |
| Composer | Version 2 |
| Node.js | Version 22.12+ recommended; use a version supported by the locked Vite dependencies |
| npm | Required for frontend installation/builds |
| Local database | SQLite with PDO SQLite |
| Production database | MySQL with PDO MySQL |
| PHP extensions | `intl`, `gd`, `mbstring`, `bcmath`, `ctype`, `dom`, `fileinfo`, `filter`, `openssl`, `pdo`, `session`, `tokenizer`, `xml`, and the selected PDO database driver; ZIP support is recommended for Composer |
| Production services | HTTPS, persistent uploads, a queue worker, and a scheduler running every minute |

Check your environment:

```sh
php -v
php -m
composer --version
node --version
npm --version
```

Run `composer check-platform-reqs` after installing dependencies to check the exact requirements of the installed packages. Do not use `--ignore-platform-reqs` to work around an incompatible production host.

## Installation

### 1. Open the project directory

Run all subsequent commands from the folder containing `artisan` and `composer.json`.

For the original local copy, use PowerShell:

```powershell
Set-Location 'C:\Users\User\Documents\Codex\2026-10-15\i-want-a-simple-cosmetic-ecommerce\outputs\rr-care-bd'
```

If you extracted the source ZIP elsewhere, use that extracted project directory instead.

### 2. Install dependencies

```sh
composer install
npm ci
```

Use the committed lockfiles for reproducible installation. You do not need to run `composer update` or `npm update` during normal setup.

### 3. Create the environment file

For a **new installation**, copy the template.

PowerShell:

```powershell
Copy-Item .env.example .env
```

Linux/macOS:

```sh
cp .env.example .env
```

**Keep an existing `.env` when continuing an installed application.** It contains its application key and environment configuration.

Set these local values in `.env`:

```dotenv
APP_NAME="R&R Care BD"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=sqlite
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
MAIL_MAILER=log
```

Leave `DB_DATABASE` unset for the default `database/database.sqlite` location. If the file already defines a different database, update that configuration deliberately rather than accidentally connecting to it.

Generate the application key **once for a new installation**:

```sh
php artisan key:generate
```

Do not regenerate an established production application's key during routine deployments: existing encrypted data and sessions depend on it.

### 4. Create and migrate the local database

```sh
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
```

The migrations include the application tables, database sessions, cache, jobs, notification delivery receipts, and inventory operation references.

### 5. Prepare public assets

```sh
php artisan storage:link
php artisan livewire:publish --assets
php artisan filament:assets
npm run build
```

The normal upload URL uses `public/storage`. A constrained public-image route also supports local environments where the storage symlink cannot be created. Keep `storage/app/public` persistent on the production host.

### 6. Create your owner account

```sh
php artisan app:create-owner
```

Enter your name, a unique email address, and a password of at least **12 characters**. The password is entered privately in the terminal and must be confirmed.

**There is no default admin email or password.** Sample seeding does not create a user.

### 7. Optional: load the demonstration catalog

```sh
php artisan db:seed --class=DemoCatalogSeeder
```

This creates:

- Eight sample products with illustrative images and sample stock/prices.
- Three sample care-journal articles with linked products.
- Sample categories, brand, concerns, and delivery zones.
- Example delivery fees: **BDT 60 inside Dhaka** and **BDT 120 outside Dhaka**.

These fees are examples, not courier quotations. Replace or unpublish sample content before launch. Set your actual products, stock, ingredients, directions, delivery fees, and business contact information.

## Run the application

### Built frontend: three terminals

From the project directory, run each process in a separate terminal.

**Terminal 1 — web application:**

```sh
php artisan serve --host=127.0.0.1 --port=8000
```

**Terminal 2 — queued notifications:**

```sh
php artisan queue:work --tries=3 --timeout=60
```

**Terminal 3 — local scheduler:**

```sh
php artisan schedule:work
```

The queue worker sends notifications. The scheduler expires pending COD reservations and recovers pending notification jobs. Keep both running when testing those behaviors.

### Frontend development

To see CSS/JavaScript changes while editing, run an additional terminal:

```sh
npm run dev
```

Alternatively, rebuild with `npm run build` after frontend changes. Vite is an asset server; the storefront still runs through Laravel's URL.

`composer run dev` starts the scaffold's application, Vite, queue listener, and log processes together. It **does not start the scheduler**, so run `php artisan schedule:work` separately. The individual-terminal approach above is easier to diagnose across Windows/PHP setups.

### Application URLs

| Page | Local URL |
| --- | --- |
| Storefront | [http://127.0.0.1:8000/](http://127.0.0.1:8000/) |
| Product catalog | [http://127.0.0.1:8000/shop](http://127.0.0.1:8000/shop) |
| Care journal | [http://127.0.0.1:8000/journal](http://127.0.0.1:8000/journal) |
| Admin panel | [http://127.0.0.1:8000/admin](http://127.0.0.1:8000/admin) |
| Admin login | [http://127.0.0.1:8000/admin/login](http://127.0.0.1:8000/admin/login) |
| Sitemap | [http://127.0.0.1:8000/sitemap.xml](http://127.0.0.1:8000/sitemap.xml) |
| Robots file | [http://127.0.0.1:8000/robots.txt](http://127.0.0.1:8000/robots.txt) |

**`/login` is not the admin login route.** Customer accounts and public registration are outside this version; checkout is available to guests.

## Admin login and permissions

Sign in at `/admin/login` with the owner account created through `app:create-owner`.

| Capability | Owner | Staff |
| --- | --- | --- |
| View store dashboard | Yes | Yes |
| Manage products, categories, brands, and concerns | Yes | Yes |
| Manage prices, discounts, images, and inventory adjustments | Yes | Yes |
| Manage articles and linked products | Yes | Yes |
| Process orders and inspected returns | Yes | Yes |
| Manage delivery zones | Yes | Yes |
| Manage store settings and notification recipient | Yes | No |
| Manage staff and owner accounts | Yes | No |

The application protects the last owner from ordinary role demotion. Keep at least one usable owner account. SMTP credentials belong in the server environment, not dashboard fields.

## Store management

### Products and inventory

1. Create categories and brands as needed.
2. Create a product with its description, ingredients, directions, and unique slug.
3. Add its SKU/variant, code, size/label, and regular price.
4. Upload product images and enter descriptive alt text.
5. Save the product, then open **Inventory → Adjust stock**.
6. Enter a positive received quantity and a reason. Negative adjustments remove physical stock, but cannot remove units already reserved for orders.
7. Publish the product when its information and availability are correct.

New SKUs start at zero stock. The stock history records reservations, releases, dispatch deductions, adjustments, and inspected-return restocks.

Admin prices are entered in **BDT** and stored as integer **paisa** to avoid floating-point money calculations.

### Homepage sections and discounts

The homepage has two main product sections:

- **Available products:** published products with available stock.
- **Discount products:** available products with an active sale price below the regular price.

A product may appear in both sections. Offer cards show regular price, current price, savings, and the discount percentage, rounded down.

Enter an optional sale start/end time in the product's SKU form. Sale dates are displayed in **Asia/Dhaka** and stored in UTC. An offer is inactive at its end time.

### Branding and storefront settings

Owners can edit the store name, logo, colors, hero content/image, homepage section headings/count, contact information, social links, default SEO description, pending-order expiry duration, and notification recipient.

Settings customize the supplied layout and content. New layouts, integrations, or application features require development; this is not a drag-and-drop page builder.

### Image handling

Product/admin image fields accept JPEG, PNG, and WebP, with a maximum upload size of 4 MB. Use compressed originals and accurate alt text.

Product gallery uploads generate 400px and 800px WebP variants when the source is large enough. Cards use responsive `srcset`. Images above 12 megapixels are not decoded for variants; optimize those before uploading. Hero and article images should also be sized for their intended use.

## COD orders and inventory

```text
Available quantity = On-hand quantity − Reserved quantity
```

| Event | Inventory effect |
| --- | --- |
| Add to cart, change quantity, or abandon cart | No reservation or stock deduction |
| Submit a valid COD order | Reserve the requested quantities atomically |
| Confirm a pending order | Keep reservation; remove pending expiry |
| Cancel a pending/confirmed order | Release reservation once |
| Expire an overdue pending order | Release reservation once |
| Dispatch a confirmed order | Deduct physical stock and release its reservation together |
| Mark dispatched order delivered | No additional deduction |
| Mark an order returned | No automatic restock |
| Restock inspected returned units | Increase physical stock within the un-restocked return quantity |

### Allowed status transitions

```text
pending   → confirmed / cancelled / expired
confirmed → dispatched / cancelled
dispatched → delivered / returned
delivered → returned
```

Pending orders expire after **24 hours by default**, configurable by the owner. Automatic expiry requires the scheduler to run.

Only mark an order **returned after the parcel is physically back**. Inspect the goods, then explicitly restock the sellable quantity. Each restock action has an operation reference so request retries cannot double-restock a partial return.

### Checkout safeguards

- The server checks publication, prices, delivery charges, quantities, and current availability.
- Changed totals require customer review before resubmission.
- SKU rows are locked in a transaction; one unavailable item rejects the entire order.
- Checkout tokens prevent duplicate submissions from creating multiple orders or reservations.
- Order line descriptions, prices, discounts, and delivery totals are snapshots; later catalog edits do not rewrite accepted orders.
- Public confirmations use unguessable references and omit personal customer details.

The dashboard's delivered order value is an operational total, **not proof that the courier remitted cash**. Courier settlement reconciliation is not implemented.

## Gmail and order notifications

### Receiving notifications at your personal Gmail address

Set **Store settings → Notification recipient** to your Gmail address. This selects the recipient; it does not configure the sending mail server.

Customers may optionally supply an email address for confirmation. Email is not required for COD checkout.

### Sending through Gmail SMTP

For an eligible Google account, enable two-step verification and generate an **App Password**. Availability depends on the account's security settings. See [Google's App Password guidance](https://support.google.com/accounts/answer/185833).

Set the following in your private `.env` or hosting environment:

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

Use the sending Gmail address for `MAIL_FROM_ADDRESS`. Do not enter your ordinary Gmail password, commit credentials, or paste them into public issue reports.

After changing configuration locally:

```sh
php artisan config:clear
php artisan queue:restart
```

On production, rebuild cached configuration with `php artisan config:cache` and let the process manager restart workers after `queue:restart`. A standalone local worker must be started again if it exits.

### Delivery and recovery

The default `MAIL_MAILER=log` writes messages locally instead of sending them. Run a queue worker to process queued messages even when using the log transport.

Notification recipients are snapshotted as durable delivery records in the order transaction. Jobs run after commit and track each recipient separately. Owner-delivery failure does not resend an already successful customer confirmation.

The scheduler runs these commands every minute:

```sh
php artisan orders:expire
php artisan orders:notify
```

To inspect and recover delivery after correcting an outage:

```sh
php artisan queue:failed
php artisan orders:notify
php artisan queue:retry all
```

Review failed jobs before retrying them. A queue or mail failure does not undo an accepted order. SMTP cannot guarantee exactly-once delivery when a provider accepts a message but its acknowledgement is lost.

For larger sending volumes, configure a transactional provider through Laravel while retaining Gmail as the owner-notification recipient.

## SEO and blog publishing

### Implemented technical SEO

- Server-rendered storefront, product, category, and article HTML.
- Editable product/category/article metadata and default store description.
- Product/Offer, BlogPosting, and breadcrumb structured data.
- Canonical URLs, including distinct normal pagination URLs.
- Product, category, and article slug redirects after renaming.
- XML sitemap and robots file.
- Noindex rules for search/filter combinations, cart, checkout, admin, and confirmations.
- Social sharing metadata, image alt text, and responsive product image variants.

Homepage membership is cached briefly, invalidated by product/SKU/image changes, and expires at sale boundaries. Displayed prices use fresh model data. Settings changes invalidate their cache.

### Publishing a product-linked article

1. Create an article in **Articles**.
2. Enter its title, unique slug, excerpt, and content.
3. Add contextual links to relevant product pages inside the article.
4. Select multiple related products to show their cards after the article.
5. Add an author, category, concerns/tags, cover image, and alt text.
6. Set the metadata title and description.
7. Set a publish date and change status to **Published**.

Draft and future-dated articles are unavailable publicly and excluded from the sitemap. Related cards exclude unpublished products. Article HTML is sanitized; unsupported executable/embedded content is removed.

Use original, accurate content and verified manufacturer information. Do not invent reviews, ratings, or medical cure claims. Technical SEO does not guarantee rankings or rich search results.

## Tests and verification

Run the application tests and build:

```sh
php artisan test
npm run build
composer validate --strict
composer audit
```

Recorded development verification on **15 October 2026**: 53 tests passed, 199 assertions, and two MySQL integration tests skipped. The frontend build passed and Composer reported no security vulnerability advisories at that time. These are historical results; rerun checks after changes.

### MySQL concurrency tests

The integration tests start separate processes to compete for the last SKU unit and submit the same checkout token concurrently.

**Use a disposable test database only. These tests run `migrate:fresh` and delete the selected database's tables. Never point them at a live or valuable database.** The database name must begin with `rr_care_test_`.

Create a separate MySQL database, then export test settings into your terminal environment.

PowerShell example:

```powershell
$env:MYSQL_TEST_DATABASE = 'rr_care_test_inventory'
$env:MYSQL_TEST_HOST = '127.0.0.1'
$env:MYSQL_TEST_PORT = '3306'
$env:MYSQL_TEST_USERNAME = 'your_test_user'
$env:MYSQL_TEST_PASSWORD = 'your_test_password'
php artisan test --testsuite=Integration
```

Linux/macOS example:

```sh
export MYSQL_TEST_DATABASE=rr_care_test_inventory
export MYSQL_TEST_HOST=127.0.0.1
export MYSQL_TEST_PORT=3306
export MYSQL_TEST_USERNAME=your_test_user
export MYSQL_TEST_PASSWORD='your_test_password'
php artisan test --testsuite=Integration
```

Putting these values only in the app's `.env` is not a substitute for exporting them: the tests read the process environment. Without `MYSQL_TEST_DATABASE`, the two integration tests skip.

**Passing MySQL concurrency verification is a production-release requirement.** SQLite tests do not certify MySQL locking behavior. Also test a real image upload, SMTP delivery, and a complete COD order lifecycle on the target host.

## Production deployment

Use a PHP-compatible host/VPS with MySQL. Point the web document root at the application's **`public/` directory**, never the repository root. This application requires a PHP runtime; a static-only host or Cloudflare Workers alone cannot run it.

### Environment

Example production settings:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_production_database
DB_USERNAME=your_application_user
DB_PASSWORD="your-private-database-password"

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
CACHE_STORE=database
```

Use the installation's established `APP_KEY`. Generate it only for a new environment with no encrypted data to preserve. Configure actual SMTP values separately.

### Release procedure

1. Back up the existing database and uploaded files before making changes.
2. Deploy source and install locked production dependencies:

   ```sh
   composer install --no-dev --optimize-autoloader
   ```

3. Build frontend assets locally/in CI or on the host if Node.js is available:

   ```sh
   npm ci
   npm run build
   ```

4. Ensure the PHP process can write `storage/` and `bootstrap/cache/`, and keep uploaded files persistent between releases. Use appropriate ownership and permissions rather than blanket world-writable permissions.
5. Apply migrations and refresh public assets:

   ```sh
   php artisan migrate --force
   php artisan storage:link
   php artisan livewire:publish --assets
   php artisan filament:assets
   ```

6. Build caches and restart queue workers:

   ```sh
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan queue:restart
   ```

7. Run the queue worker under a process manager configured to restart it:

   ```sh
   php artisan queue:work --tries=3 --timeout=60
   ```

8. Configure the scheduler to run every minute. A Linux cron entry typically looks like:

   ```cron
   * * * * * cd /absolute/path/to/rr-care-bd && php artisan schedule:run >> /dev/null 2>&1
   ```

   Replace both the directory and PHP executable with the host's actual paths. On Windows, use Task Scheduler with the project as the working directory.

9. For a new installation, create the owner, configure real catalog/settings, remove sample content, verify delivery fees, and verify email.
10. Pass the MySQL tests on a separate disposable database. On staging, place, confirm, dispatch, cancel, and return test orders as appropriate; verify stock movements and notifications before accepting customers.

### Operational checks

- Keep HTTPS enabled and production debug output disabled.
- Restrict access to environment files and database credentials.
- Monitor failed jobs, scheduler execution, application logs, disk space, and low stock.
- Back up both the database and `storage/app/public`; test restoration.
- Keep PHP, framework dependencies, and server software maintained.
- Check canonical URLs and sitemap against the real public domain.

Do not run `migrate:fresh`, `db:wipe`, or demonstration seeding as routine production deployment steps.

## Troubleshooting

| Symptom | What to check |
| --- | --- |
| `/login` gives 404 | Use **`/admin/login`**. There is no customer login in this version. |
| Cannot sign into admin | Create an account with `php artisan app:create-owner`; there are no default credentials. Confirm it exists in the configured database. |
| `No application encryption key` | On a new install, create `.env` and run `php artisan key:generate`. Preserve an existing production key. |
| Missing table / database error | Check `.env`, clear stale config with `php artisan config:clear`, create the SQLite file if applicable, and run `php artisan migrate`. |
| `could not find driver` | Enable PDO SQLite or PDO MySQL in the PHP configuration used by the command/server. |
| Composer reports PHP/extension incompatibility | Check `php -v`, `php -m`, and `composer check-platform-reqs`; confirm the terminal uses the intended PHP installation. |
| Styles/scripts missing or Vite manifest missing | Run `npm ci` and `npm run build`, or keep `npm run dev` running while developing. |
| Stale Vite development URL | Stop the unused Vite server and remove a stale `public/hot` file before serving built assets. |
| Page renders but Livewire buttons fail | Republish Livewire assets, verify JavaScript loads, inspect browser errors, and check the session/CSRF configuration. |
| HTTP 419 / expired page | Reload the form; check `APP_URL`, cookie settings, and writable/session database storage. Use one consistent host such as `127.0.0.1`. Set secure cookies only when using HTTPS. |
| Uploaded image does not display | Verify upload storage, `APP_URL`, disk permissions, the storage symlink or media route, and the file's allowed format. |
| No email arrives | `MAIL_MAILER=log` does not send externally. Verify SMTP credentials, queue worker, recipient setting, failed jobs, and application logs. |
| Pending orders never expire | Run the scheduler; inspect `php artisan schedule:list` and manually test `php artisan orders:expire`. |
| Accepted order missed a notification during an outage | Correct queue/mail configuration, restart workers, and run `php artisan orders:notify`. |
| Product missing from the homepage | Check publication and available SKU stock. Discount-section inclusion also requires an active valid sale. |
| Article returns 404 | Check Published status and that its publish date has arrived. |
| Environment edits seem ignored | Run `php artisan config:clear` locally; rebuild cached configuration on production. Restart long-running workers. |
| Port 8000 is in use | Choose another port, such as `--port=8001`, and update `APP_URL` accordingly. |

Application logs normally reside in `storage/logs/laravel.log`. Logs can contain order or mail details; share only the minimum needed and redact personal information and secrets.

**Codex Windows sandbox note:** The original development sandbox reported some existing files as unreadable/unwritable despite successful actual reads/writes. Local scratch wrappers were used to prepare framework manifests and run the equivalent PHPUnit configuration without changing vendor files. Those scratch helpers are not part of the distributed application. Use normal PHP commands on a correctly configured local machine or host; do not patch framework/vendor files to bypass permission errors.

## Project structure

```text
app/
  Console/Commands/       Owner creation, order expiry, notification recovery
  Enums/                  Order lifecycle states
  Filament/               Admin pages, resources, and dashboard widgets
  Http/                   Controllers and public-route middleware
  Jobs/                   Queued order notifications
  Mail/                   Order confirmation email
  Models/                 Catalog, orders, inventory, content, settings
  Observers/              Catalog cache invalidation
  Policies/               Owner/staff authorization
  Services/               Checkout, pricing, stock, lifecycle, SEO, images
  Livewire/               Interactive storefront components
config/                    Framework/service configuration
database/
  migrations/             Database schema
  seeders/                Optional demonstration catalog
public/                    Web root, branding, and published/built assets
resources/
  css/                    Storefront styles
  js/                     Frontend entry points
  views/                  Storefront, email, SEO, and print templates
routes/                    Web routes and scheduled commands
storage/                   Runtime files, logs, and uploaded media
tests/
  Feature/                Storefront, admin, stock, SEO, mail regressions
  Integration/            Opt-in MySQL concurrency tests
  Support/                Integration test subprocess support
```

## Included and excluded scope

Included: guest COD checkout, one inventory location, products/variants, discounts, product-linked articles, editable store settings, and owner/staff administration.

Not included: online payments, courier API integration, SMS/phone OTP, customer accounts, loyalty points, customer reviews, multiwarehouse stock, or courier cash-settlement reconciliation.

The source ZIP excludes `.env`, local SQLite databases, QA credentials/orders, logs, installed dependencies, caches, and Git metadata. It includes source, lockfiles, and built frontend/panel assets. Install dependencies before running an extracted copy.
