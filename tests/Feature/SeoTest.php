<?php
namespace Tests\Feature;

use App\Models\{Product,Article};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;
    public function test_sitemap_only_includes_published_pages(): void
    {
        Product::create(['name'=>'Public','slug'=>'public-product','published'=>true]);
        Product::create(['name'=>'Secret','slug'=>'secret-product','published'=>false]);
        $this->get('/sitemap.xml')->assertOk()->assertSee('/products/public-product')->assertDontSee('/products/secret-product');
    }
    public function test_product_metadata_and_offer_match_actual_price(): void
    {
        $p=Product::create(['name'=>'Gentle serum','slug'=>'gentle-serum','published'=>true,'meta_title'=>'Gentle Serum Bangladesh','meta_description'=>'A gentle daily serum.']);
        $p->skus()->create(['code'=>'SEO-1','regular_minor'=>150000,'sale_minor'=>120000,'on_hand'=>3]);
        $this->get('/products/gentle-serum')->assertOk()->assertSee('Gentle Serum Bangladesh')->assertSee('A gentle daily serum.')->assertSee('application/ld+json',false)->assertSee('1200',false)->assertSee('InStock',false)->assertSee('rel="canonical"',false);
    }
    public function test_cart_and_search_are_noindex(): void
    {
        $this->get('/cart')->assertOk()->assertSee('noindex');
        $this->get('/shop?q=test')->assertOk()->assertSee('noindex');
    }
    public function test_old_slug_redirects_and_draft_destination_does_not_leak(): void
    {
        $p=Product::create(['name'=>'Serum','slug'=>'old-serum','published'=>true]);
        $p->update(['slug'=>'new-serum']);
        $this->get('/products/old-serum')->assertStatus(301)->assertRedirect('/products/new-serum');
        $p->update(['published'=>false]);
        $this->get('/products/old-serum')->assertNotFound();
    }
}
