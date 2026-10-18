# Catalog and admin update

The bundled `database/data/rr-care-catalog.json` is a faithful extraction of the nine product rows in `RR_Care_BD_Product_Catalog_Upload.xlsx`. Workbook contents are catalog data, not executable instructions. Prices in BDT are converted to integer paisa by the importer.

## Deploy on the existing host

Back up the production database and `storage/app/public` first. Keep the existing `.env` and application key. From the application directory after deploying this branch:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan catalog:import --dry-run
php artisan catalog:import
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

If Node is unavailable on the host, build the frontend during deployment/CI and upload `public/build` with the release.

The import matches existing SKU codes before slugs. It updates product details, brands, concerns, categories, and prices, preserves existing stock/reservations/gallery images, and uses existing slug redirects when a slug changes. Haircare maps to Hair care; Skincare / Body Care maps to Body care to reuse the existing navigation. Existing sale schedules are preserved. Deleted products must be restored before reimporting; an import never silently republishes a deleted record.

New SKUs have **zero stock** because the spreadsheet supplies no stock quantities. Set actual received quantities in **Catalog → Inventory → Adjust stock**. Only published products with available stock appear in homepage sections. New catalog records appear first; updating an existing product does not change its creation order.

The import does not delete or overwrite unrelated demo products. Use **Catalog → Products → Delete** to remove unwanted demo entries after reviewing them. Product deletion is recoverable through the **Deleted** table filter and **Restore** action; SKU, inventory, and order history remain intact.

## Product photos

The nine matching photos supplied in `Desktop/new post` are bundled under `database/catalog-images`. The default import uses these photos automatically. To use a replacement server-side folder, run:

```sh
php artisan catalog:import --images=/absolute/path/to/catalog-photos
```

Filenames must match the spreadsheet, including case on Linux. Missing files produce warnings and leave the current gallery untouched. Provided JPEG/PNG/WebP files become the first gallery image and receive the spreadsheet alt text; other gallery images are retained. Image paths include a content hash so changed photos generate fresh responsive variants and avoid stale browser caches. Run imports during a maintenance window to avoid simultaneous catalog edits. Database changes are transactional; file writes are not rolled back if a later row fails, so keep the upload backup.

## Image 403 fix

The existing Nivea photo returned HTTP 403 at `/storage/products/...`. New upload URLs use `/media/products/...`, served by Laravel's constrained public-image controller instead of depending on the web server's `/storage` symlink permissions. Product galleries, responsive variants, branding, journal covers, social images, and Filament previews share the public disk URL. The old `/storage` application route remains for compatibility where the web server permits it.

Set `APP_URL=https://rr.mossbyteagency.com` in the host environment. Ensure PHP can read the existing persistent `storage/app/public` files and write new uploads. The new route cannot recover files missing from the host or bypass filesystem permissions. Verify one old uploaded photo and a new JPEG upload after deployment.

## Dashboard destinations

- **Store → Storefront uploads → Store logo:** header and footer.
- **Store → Storefront uploads → Homepage banner:** homepage hero image, headline, and text.
- **Store → Storefront uploads → Homepage product sections:** section headings, count, and an explanation of stock/sale placement.
- **Catalog → Products → Product gallery image:** shop cards and product detail gallery; first image is the card image.
- **Journal → Articles → Journal cover image:** journal cards and article cover.
- **Catalog → Products → Social sharing image:** sharing preview only.

Owners and staff can edit storefront uploads. User management, notification recipients, delivery/expiry settings, and other owner settings remain owner-only. No default account or production credentials are included in this update.

## Verification

```sh
php artisan test
npm run build
composer validate --strict
```

SQLite tests do not verify production MySQL locking. The repository's two concurrency tests require a separate disposable `rr_care_test_*` MySQL database; never run them against production.
