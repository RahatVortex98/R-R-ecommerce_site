<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sku;
use App\Services\PricingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_has_branding_and_both_catalog_sections(): void
    {
        $this->get('/')->assertOk()->assertSee('R&R')->assertSee('Available products')->assertSee('Discount products');
    }

    public function test_catalog_excludes_drafts_and_supports_search(): void
    {
        Product::create(['name'=>'Visible serum','slug'=>'visible-serum','published'=>true]);
        Product::create(['name'=>'Hidden cream','slug'=>'hidden-cream','published'=>false]);
        $this->get('/shop?q=serum')->assertOk()->assertSee('Visible serum')->assertDontSee('Hidden cream');
    }

    public function test_available_products_show_only_regular_prices_while_offers_keep_sale_details(): void
    {
        $product = Product::create(['name'=>'Everyday moisturizer','slug'=>'everyday-moisturizer','published'=>true]);
        $product->skus()->create(['code'=>'MOISTURIZER','regular_minor'=>150000,'sale_minor'=>120000,'on_hand'=>5]);

        $response = $this->get('/')->assertOk();
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new \DOMXPath($document);
        $available = $xpath->query('//section[.//h2[normalize-space(.)="Available products"]]')->item(0);
        $discounts = $xpath->query('//section[.//h2[normalize-space(.)="Discount products"]]')->item(0);

        $this->assertNotNull($available);
        $this->assertNotNull($discounts);
        $this->assertSame('৳1,500', trim($xpath->evaluate('string(.//div[@class="price"])', $available)));
        $this->assertSame(0, $xpath->query('.//span[contains(@class,"sale")] | .//del | .//span[@class="saving"]', $available)->length);
        $this->assertStringContainsString('৳1,200', $discounts->textContent);
        $this->assertStringContainsString('Save ৳300', $discounts->textContent);
        $this->assertStringContainsString('−20%', $discounts->textContent);

        $this->get('/shop?sale=1')->assertOk()->assertSee('Save ৳300');
    }

    public function test_sale_pricing_and_end_boundary(): void
    {
        $product = Product::create(['name'=>'Serum','slug'=>'serum','published'=>true]);
        $sku = $product->skus()->create(['code'=>'SERUM-1','regular_minor'=>150000,'sale_minor'=>120000,'sale_starts_at'=>'2026-10-01','sale_ends_at'=>'2026-11-01','on_hand'=>5]);
        $pricing = app(PricingService::class);
        $quote = $pricing->quote($sku, CarbonImmutable::parse('2026-10-15'));
        $this->assertSame(120000, $quote['current_minor']);
        $this->assertSame(30000, $quote['saving_minor']);
        $this->assertSame(20, $quote['discount_percent']);
        $this->assertSame(150000, $pricing->quote($sku, CarbonImmutable::parse('2026-11-01'))['current_minor']);
        $sku->update(['reserved'=>2]);
        $this->assertSame(3, $sku->fresh()->availableQuantity());
    }

    public function test_discount_cards_show_an_available_discounted_variant(): void
    {
        $p=Product::create(['name'=>'Variant care','slug'=>'variant-care','published'=>true]);
        $p->skus()->create(['code'=>'VAR-REGULAR','regular_minor'=>150000,'on_hand'=>5]);
        $p->skus()->create(['code'=>'VAR-SALE','regular_minor'=>150000,'sale_minor'=>120000,'on_hand'=>5]);
        $this->get('/shop?sale=1')->assertOk()->assertSee('Save ৳300');
    }

    public function test_category_has_a_canonical_indexable_page(): void
    {
        \App\Models\Category::create(['name'=>'Skincare','slug'=>'skincare','meta_title'=>'Skincare essentials Bangladesh','meta_description'=>'Shop our skincare essentials.']);
        $this->get('/categories/skincare')->assertOk()->assertSee('Skincare essentials Bangladesh')->assertSee('index, follow')->assertSee('/categories/skincare');
    }
}
