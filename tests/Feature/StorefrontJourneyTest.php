<?php
namespace Tests\Feature;
use App\Models\{Product,Order,DeliveryZone,User};
use App\Enums\OrderStatus;
use App\Livewire\{Home,Cart,Checkout};
use App\Services\{OrderLifecycleService,CheckoutService,CartService,InventoryService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;
class StorefrontJourneyTest extends TestCase {
    use RefreshDatabase;
    private function sku(){return Product::create(['name'=>'Journey serum','slug'=>'journey-serum','published'=>true])->skus()->create(['code'=>'JOURNEY-1','regular_minor'=>100000,'on_hand'=>5]);}
    public function test_full_cod_journey_and_snapshot_prices(): void{
        $sku=$this->sku();DeliveryZone::create(['name'=>'Dhaka','fee_minor'=>6000,'active'=>true]);
        Livewire::test(Home::class)->call('add',$sku->id)->assertHasNoErrors();
        $this->assertSame(0,$sku->fresh()->reserved);
        Livewire::test(Checkout::class)->set('name','Journey Customer')->set('phone','01712345678')->set('address','House 1, Road 3, Dhaka')->call('submit')->assertHasNoErrors()->assertRedirect();
        $order=Order::firstOrFail();$this->assertSame(106000,$order->total_minor);$this->assertSame(1,$sku->fresh()->reserved);
        $sku->update(['regular_minor'=>200000]);$this->assertSame(100000,$order->items()->first()->unit_minor);
        $owner=User::factory()->create(['role'=>'owner']);$service=app(OrderLifecycleService::class);
        foreach([OrderStatus::Confirmed,OrderStatus::Dispatched,OrderStatus::Delivered] as $status)$service->transition($order,$status,$owner);
        $this->assertSame(4,$sku->fresh()->on_hand);$this->assertSame(0,$sku->fresh()->reserved);$this->assertEmpty(session('cart',[]));
        $this->get(route('order.confirmation',$order->public_reference))->assertOk()->assertDontSee('House 1')->assertHeader('X-Robots-Tag','noindex, nofollow');
    }
    public function test_price_change_requires_review_then_can_be_resubmitted(): void{
        $sku=$this->sku();DeliveryZone::create(['name'=>'Dhaka','fee_minor'=>6000,'active'=>true]);session(['cart'=>[$sku->id=>1]]);
        $checkout=Livewire::test(Checkout::class)->set('name','Review Customer')->set('phone','01712345678')->set('address','House 1, Road 3, Dhaka');
        $sku->update(['regular_minor'=>120000]);
        $checkout->call('submit')->assertHasErrors('cart');$this->assertSame(0,Order::count());
        $checkout->call('submit')->assertHasNoErrors()->assertRedirect();$this->assertSame(126000,Order::first()->total_minor);
    }
    public function test_inventory_adjustment_cannot_remove_reserved_units(): void{
        $sku=$this->sku();$sku->update(['reserved'=>4]);$actor=User::factory()->create(['role'=>'owner']);
        $this->expectException(ValidationException::class);app(InventoryService::class)->adjust($sku,-2,'Damaged units',$actor);
    }
}
