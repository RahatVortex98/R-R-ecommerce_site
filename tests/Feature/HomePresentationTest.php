<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\StoreSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_cards_show_available_stock_before_the_cart_button(): void
    {
        $product = Product::create(['name'=>'Stocked cream','slug'=>'stocked-cream','published'=>true]);
        $product->skus()->create(['code'=>'STOCKED','regular_minor'=>54000,'on_hand'=>60,'reserved'=>7]);

        $this->get('/')->assertOk()->assertSeeInOrder(['In stock', '53 available', 'Add to bag']);
        $this->get('/shop')->assertOk()->assertSeeInOrder(['In stock', '53 available', 'Add to bag']);
        $this->get('/products/stocked-cream')->assertOk()->assertSeeInOrder(['53 available', 'Add to bag']);
        $product->skus()->first()->update(['reserved'=>60]);
        $this->get('/shop')->assertOk()->assertSee('Out of stock')->assertDontSee('53 available');
    }

    public function test_homepage_renders_configured_banner_slides_in_order(): void
    {
        app(StoreSettings::class)->set('hero_slides', [
            ['image'=>'branding/first.jpg','alt'=>'Our first care collection'],
            ['image'=>'branding/second.jpg','alt'=>'Our second care collection'],
        ]);

        $this->get('/')->assertOk()
            ->assertSeeInOrder(['Our first care collection', 'Our second care collection'])
            ->assertSee('Previous banner')->assertSee('Next banner')->assertSee('Pause slideshow');
    }

    public function test_homepage_keeps_the_current_banner_without_carousel_slides(): void
    {
        app(StoreSettings::class)->set('hero_image', 'branding/existing.jpg');

        $this->get('/')->assertOk()->assertSee('/media/branding/existing.jpg')
            ->assertDontSee('Next banner')->assertDontSee('Pause slideshow');
    }
}
