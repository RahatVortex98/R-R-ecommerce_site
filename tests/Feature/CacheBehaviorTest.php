<?php
namespace Tests\Feature;
use App\Models\{Product,Category};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class CacheBehaviorTest extends TestCase {
    use RefreshDatabase;
    public function test_cached_home_membership_updates_after_stock_or_publication_changes(): void{
        $p=Product::create(['name'=>'Cached care','slug'=>'cached-care','published'=>true]);$sku=$p->skus()->create(['code'=>'CACHE-1','regular_minor'=>100000,'on_hand'=>2]);
        $this->get('/')->assertSee('Cached care');
        $sku->update(['reserved'=>2]);$this->get('/')->assertDontSee('Cached care');
        $sku->update(['reserved'=>0]);$this->get('/')->assertSee('Cached care');
        $p->update(['published'=>false]);$this->get('/')->assertDontSee('Cached care');
    }
    public function test_scheduled_sale_updates_at_its_time_boundary(): void{
        $this->travelTo(\Carbon\Carbon::parse('2026-10-15 10:00:00','UTC'));
        $p=Product::create(['name'=>'Timed care','slug'=>'timed-care','published'=>true]);$p->skus()->create(['code'=>'TIMED-1','regular_minor'=>100000,'sale_minor'=>80000,'sale_starts_at'=>now()->addSeconds(10),'sale_ends_at'=>now()->addSeconds(20),'on_hand'=>2]);
        $this->get('/')->assertDontSee('Save ৳200');
        $this->travel(10)->seconds();$this->get('/')->assertSee('Save ৳200');
        $this->travel(10)->seconds();$this->get('/')->assertDontSee('Save ৳200');
    }
}
