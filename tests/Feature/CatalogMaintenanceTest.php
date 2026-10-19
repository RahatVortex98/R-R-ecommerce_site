<?php

namespace Tests\Feature;

use App\Filament\Resources\ProductResource\Pages\ManageProducts;
use App\Models\{Product, User};
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public', ['url' => config('filesystems.disks.public.url')]);
    }

    public function test_product_deletion_is_recoverable_and_keeps_skus(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staff']));
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
        $product = Product::create(['name' => 'Delete me', 'slug' => 'delete-me', 'published' => true]);
        $sku = $product->skus()->create(['code' => 'KEEP', 'regular_minor' => 10000, 'on_hand' => 5]);
        Livewire::test(ManageProducts::class)->callAction(TestAction::make('delete')->table($product));
        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertDatabaseHas('skus', ['id' => $sku->id, 'on_hand' => 5]);
        $this->get('/products/delete-me')->assertNotFound();
        $product->refresh()->restore();
        $this->get('/products/delete-me')->assertOk();
    }

    public function test_admin_shows_newest_product_first(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'owner']));
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
        $old = Product::create(['name' => 'Old', 'slug' => 'old']);
        $new = Product::create(['name' => 'New', 'slug' => 'new']);
        Livewire::test(ManageProducts::class)->assertCanSeeTableRecords([$new, $old], inOrder: true);
    }

    public function test_uploaded_images_use_a_route_outside_the_forbidden_storage_directory(): void
    {
        Storage::fake('public', ['url' => config('filesystems.disks.public.url')]);
        Storage::disk('public')->put('products/photo.jpg', file_get_contents(public_path('images/logo.jpg')));
        $product = Product::create(['name' => 'Photo', 'slug' => 'photo', 'published' => true]);
        $image = $product->images()->create(['path' => 'products/photo.jpg', 'alt' => 'Product']);
        $this->assertStringContainsString('/media/products/photo.jpg', $image->url());
        $this->get('/media/products/photo.jpg')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get('/media/products/secret.php')->assertNotFound();
    }

    public function test_catalog_import_matches_existing_sku_and_preserves_stock(): void
    {
        $product = Product::create(['name' => 'Existing Nivea', 'slug' => 'nivea-soft-light-jar-100ml']);
        $sku = $product->skus()->create(['code' => 'NIV-SOFT-100ML', 'regular_minor' => 10000, 'on_hand' => 7, 'reserved' => 2]);
        $this->artisan('catalog:import')->assertSuccessful();
        $this->artisan('catalog:import')->assertSuccessful();
        $this->assertSame(9, Product::count());
        $this->assertSame(9, \App\Models\Sku::count());
        $this->assertSame(7, $sku->fresh()->on_hand);
        $this->assertSame(2, $sku->fresh()->reserved);
        $this->assertSame(54000, $sku->fresh()->regular_minor);
        $this->assertSame(43000, $sku->fresh()->sale_minor);
        $this->assertSame('Nivea', $product->fresh()->brand->name);
    }

    public function test_staff_can_update_storefront_uploads_without_access_to_owner_settings(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staff']));
        $this->get('/admin/storefront-content')->assertOk()->assertSee('Homepage banner')->assertSee('Store logo');
        $this->get('/admin/store-settings')->assertForbidden();
        Livewire::test(\App\Filament\Pages\StorefrontContent::class)
            ->set('data.hero_title', 'Fresh care for every day')
            ->set('data.notification_recipient', 'unwanted@example.com')
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame('Fresh care for every day', app(\App\Services\StoreSettings::class)->get('hero_title'));
        $this->assertSame('', app(\App\Services\StoreSettings::class)->get('notification_recipient'));
    }

    public function test_import_preview_does_not_write_products(): void
    {
        $this->artisan('catalog:import', ['--dry-run' => true])->assertSuccessful();
        $this->assertDatabaseCount('products', 0);
    }

    public function test_staff_can_save_and_reorder_uploaded_hero_banner_slides(): void
    {
        $this->actingAs(User::factory()->create(['role'=>'staff']));
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
        foreach(['first.jpg','second.jpg'] as $file){
            Storage::disk('public')->put('branding/'.$file, file_get_contents(public_path('images/logo.jpg')));
        }
        $slides=[
            ['image'=>'branding/second.jpg','alt'=>'Second banner first'],
            ['image'=>'branding/first.jpg','alt'=>'First banner second'],
        ];
        Livewire::test(\App\Filament\Pages\StorefrontContent::class)
            ->set('data.hero_slides', array_map(fn($slide)=>['image'=>[$slide['image']], 'alt'=>$slide['alt']], $slides))
            ->call('save')->assertHasNoFormErrors();

        $stored=array_values(app(\App\Services\StoreSettings::class)->get('hero_slides'));
        $this->assertSame($slides, $stored);
        $this->get('/')->assertOk()->assertSeeInOrder(['Second banner first', 'First banner second']);
    }

    public function test_deleted_product_cannot_be_added_from_a_stale_shop_page(): void
    {
        $product = Product::create(['name' => 'Removed', 'slug' => 'removed', 'published' => true]);
        $sku = $product->skus()->create(['code' => 'REMOVED', 'regular_minor' => 10000, 'on_hand' => 4]);
        $product->delete();
        Livewire::test(\App\Livewire\Catalog::class)->call('add', $sku->id)->assertHasErrors('cart');
        $this->withSession(['cart' => [$sku->id => 1]])->get('/cart')->assertOk()->assertDontSee('Removed');
    }

    public function test_removed_lines_are_pruned_so_the_remaining_cart_can_be_checked_out(): void
    {
        $removed = Product::create(['name' => 'Removed', 'slug' => 'removed', 'published' => true]);
        $oldSku = $removed->skus()->create(['code' => 'OLD', 'regular_minor' => 10000, 'on_hand' => 4]);
        $kept = Product::create(['name' => 'Kept', 'slug' => 'kept', 'published' => true]);
        $newSku = $kept->skus()->create(['code' => 'NEW', 'regular_minor' => 10000, 'on_hand' => 4]);
        $removed->delete();
        $this->withSession(['cart' => [$oldSku->id => 1, $newSku->id => 1]])->get('/cart')
            ->assertOk()->assertSee('Kept')->assertSessionHas('cart', [$newSku->id => 1]);
    }

    public function test_replacing_an_imported_photo_generates_fresh_responsive_images(): void
    {
        $folder = storage_path('framework/testing/catalog-source');
        \Illuminate\Support\Facades\File::ensureDirectoryExists($folder);
        $filename = $folder.'/nivea 100 ml indian.jpg';
        $first = \Illuminate\Http\UploadedFile::fake()->image('first.jpg', 1000, 1000);
        copy($first->getPathname(), $filename);
        $this->artisan('catalog:import', ['--images' => $folder])->assertSuccessful();
        $product = Product::whereHas('skus', fn ($query) => $query->where('code', 'NIV-SOFT-100ML'))->firstOrFail();
        $oldPath = $product->images()->first()->path;
        $second = \Illuminate\Http\UploadedFile::fake()->image('second.jpg', 1000, 500);
        copy($second->getPathname(), $filename);
        $this->artisan('catalog:import', ['--images' => $folder])->assertSuccessful();
        $image = $product->images()->first();
        $this->assertNotSame($oldPath, $image->path);
        $this->assertSame(1, $product->images()->count());
        $variant = app(\App\Services\ImageVariants::class)->variantPath($image->path, 400);
        $size = getimagesizefromstring(Storage::disk('public')->get($variant));
        $this->assertSame(400, $size[0]);
        $this->assertSame(200, $size[1]);
    }
}
