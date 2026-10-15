<?php
namespace Tests\Feature;

use App\Models\{Product, Sku, Order, DeliveryZone};
use App\Services\{CheckoutService, OrderLifecycleService};
use App\Enums\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function item(int $stock = 5): Sku
    {
        return Product::create(['name'=>'Test serum','slug'=>uniqid('serum-'),'published'=>true])->skus()->create(['code'=>uniqid('SKU-'),'regular_minor'=>150000,'on_hand'=>$stock]);
    }

    protected function customer(): array
    {
        $zone = DeliveryZone::firstOrCreate(['name'=>'Dhaka'], ['fee_minor'=>6000,'active'=>true]);
        return ['name'=>'Test Customer','phone'=>'01712345678','address'=>'House 2, Road 3, Dhaka','delivery_zone_id'=>$zone->id];
    }

    public function test_checkout_reserves_and_duplicate_submission_is_idempotent(): void
    {
        $sku=$this->item();
        $service=app(CheckoutService::class);
        $order=$service->submit($this->customer(),[$sku->id=>2],'test-token');
        $again=$service->submit($this->customer(),[$sku->id=>2],'test-token');
        $this->assertSame($order->id,$again->id);
        $this->assertSame(1,Order::count());
        $this->assertSame(5,$sku->fresh()->on_hand);
        $this->assertSame(2,$sku->fresh()->reserved);
        $this->assertSame(306000,$order->total_minor);
        $this->assertSame(OrderStatus::Pending,$order->status);
    }

    public function test_one_unavailable_item_rolls_back_entire_order(): void
    {
        $a=$this->item(); $b=$this->item(0);
        try { app(CheckoutService::class)->submit($this->customer(),[$a->id=>2,$b->id=>1],'rollback'); $this->fail('Unavailable order accepted'); }
        catch (ValidationException $e) { $this->assertSame(0,$a->fresh()->reserved); $this->assertSame(0,Order::count()); }
    }

    public function test_quantities_and_customer_are_validated(): void
    {
        $sku=$this->item();
        $this->expectException(ValidationException::class);
        app(CheckoutService::class)->submit($this->customer(),[$sku->id=>-1],'invalid');
    }

    public function test_cart_does_not_reserve_stock(): void
    {
        $sku=$this->item();
        \Livewire\Livewire::test(\App\Livewire\Cart::class)->call('add',$sku->id)->assertHasNoErrors();
        $this->assertSame(5,$sku->fresh()->on_hand);
        $this->assertSame(0,$sku->fresh()->reserved);
    }

    public function test_confirmation_requires_opaque_reference(): void
    {
        $this->get('/order/1')->assertNotFound();
    }

    public function test_retry_that_waited_for_stock_lock_returns_the_winning_order(): void
    {
        $sku=$this->item(1);$customer=$this->customer();$winner=null;$interleaved=false;
        \DB::connection()->beforeExecuting(function($sql)use(&$interleaved,&$winner,$sku,$customer){
            if(!$interleaved&&str_starts_with(strtolower($sql),'select')&&str_contains($sql,'skus')){
                $interleaved=true;
                // A competing submit wins after our token checks, before our stock read.
                $winner=app(CheckoutService::class)->submit($customer,[$sku->id=>1],'shared-retry-token');
            }
        });
        $retry=app(CheckoutService::class)->submit($customer,[$sku->id=>1],'shared-retry-token');
        $this->assertTrue($interleaved);$this->assertSame($winner->id,$retry->id);
        $this->assertSame(1,Order::count());$this->assertSame(1,$sku->fresh()->reserved);
    }
}
