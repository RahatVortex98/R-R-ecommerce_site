<?php

namespace App\Console\Commands;

use App\Models\{Brand, Category, Concern, Product, Sku};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Cache, DB, Storage};
use Illuminate\Support\Str;

class ImportCatalog extends Command
{
    protected $signature = 'catalog:import {--images= : Folder containing the Excel image filenames} {--dry-run : Preview without database or file changes}';
    protected $description = 'Import the nine supplied RR Care BD Excel products without resetting inventory';

    public function handle(): int
    {
        $rows = json_decode(file_get_contents(database_path('data/rr-care-catalog.json')), true, flags: JSON_THROW_ON_ERROR);
        $folder = $this->option('images') ?: database_path('catalog-images');
        if ($folder && !is_dir($folder)) {
            $this->error('Image folder does not exist.');
            return self::FAILURE;
        }
        if ($this->option('dry-run')) {
            $this->table(['SKU', 'Product', 'BDT', 'Image supplied'], array_map(fn ($row) => [
                $row['SKU Code'], $row['Product Name'], $row['Regular Price (BDT)'],
                $folder && is_file($folder.DIRECTORY_SEPARATOR.$row['Product Image Filename']) ? 'Yes' : 'No',
            ], $rows));
            return self::SUCCESS;
        }
        DB::transaction(function () use ($rows, $folder) {
            foreach ($rows as $row) {
                // Match SKU first: the live Nivea records use different slugs from the workbook.
                $sku = Sku::where('code', $row['SKU Code'])->first();
                $product = $sku ? Product::withTrashed()->findOrFail($sku->product_id) : Product::withTrashed()->firstOrNew(['slug' => $row['Slug']]);
                if ($product->exists && $product->trashed()) {
                    throw new \RuntimeException('Restore deleted product '.$row['SKU Code'].' before importing it.');
                }
                $conflict = Product::withTrashed()->where('slug', $row['Slug'])->when($product->exists, fn ($q) => $q->where('id', '!=', $product->id))->exists();
                if ($conflict) throw new \RuntimeException('Conflicting product slug: '.$row['Slug']);
                $categoryName = match ($row['Category']) {
                    'Haircare' => 'Hair care', 'Skincare / Body Care', 'Body Care' => 'Body care', default => $row['Category'],
                };
                $category = Category::firstOrCreate(['slug' => Str::slug($categoryName)], ['name' => $categoryName]);
                $brand = Brand::firstOrCreate(['slug' => Str::slug($row['Brand'])], ['name' => $row['Brand']]);
                $product->fill([
                    'name' => $row['Product Name'], 'slug' => $row['Slug'], 'category_id' => $category->id,
                    'brand_id' => $brand->id, 'published' => $row['Published'] === 'Yes', 'sample' => $row['Demo Product'] === 'Yes',
                    'description' => $row['Description'], 'ingredients' => $row['Ingredients'], 'usage' => $row['Usage Instructions'],
                    'meta_title' => $row['Meta Title'], 'meta_description' => $row['Meta Description'],
                ])->save();
                $product->skus()->updateOrCreate(['code' => $row['SKU Code']], [
                    'label' => $row['SKU Label (Volume)'], 'regular_minor' => (int) round($row['Regular Price (BDT)'] * 100),
                    'sale_minor' => $row['Sale Price (BDT)'] === null ? null : (int) round($row['Sale Price (BDT)'] * 100),
                ]);
                $concerns = array_map(function ($name) {
                    $name = trim($name);
                    return Concern::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name])->id;
                }, explode(',', $row['Concerns']));
                $product->concerns()->sync($concerns);
                $source = $folder ? $folder.DIRECTORY_SEPARATOR.$row['Product Image Filename'] : null;
                if ($source && is_file($source)) {
                    $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($source);
                    $extension = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
                    if (!$extension) throw new \RuntimeException('Unsupported image: '.$row['Product Image Filename']);
                    $path = 'products/'.$row['Slug'].'-'.substr(hash_file('sha256', $source), 0, 16).'.'.$extension;
                    if (!Storage::disk('public')->put($path, file_get_contents($source))) throw new \RuntimeException('Could not store image: '.$path);
                    $product->images()->updateOrCreate(['position' => 0], ['path' => $path, 'alt' => $row['Alt Text']]);
                } else {
                    $this->warn('No image supplied for '.$row['SKU Code'].'; existing gallery kept. Expected: '.$row['Product Image Filename']);
                }
            }
        });
        Cache::forget('home-catalog');
        $this->info('Imported 9 products. Existing quantities and gallery images were preserved. New SKUs have zero stock; adjust Inventory before selling.');
        return self::SUCCESS;
    }
}
