<?php
namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\User;
use App\Services\{CheckoutService,OrderLifecycleService,InventoryService};
use Illuminate\Validation\ValidationException;

class OrderLifecycleTest extends CheckoutTest
{
    public function test_dispatch_deducts_once_and_delivery_does_not_deduct_again(): void
    {
        $sku=$this->item(); $order=app(CheckoutService::class)->submit($this->customer(),[$sku->id=>2],'dispatch');
        $service=app(OrderLifecycleService::class);
        $service->transition($order,OrderStatus::Confirmed);
        $service->transition($order,OrderStatus::Dispatched);
        $service->transition($order,OrderStatus::Dispatched);
        $service->transition($order,OrderStatus::Delivered);
        $this->assertSame(3,$sku->fresh()->on_hand);
        $this->assertSame(0,$sku->fresh()->reserved);
    }

    public function test_cancel_and_expire_release_exactly_once(): void
    {
        $sku=$this->item(); $order=app(CheckoutService::class)->submit($this->customer(),[$sku->id=>2],'cancel');
        $service=app(OrderLifecycleService::class);
        $service->transition($order,OrderStatus::Cancelled);
        $service->transition($order,OrderStatus::Cancelled);
        $this->assertSame(0,$sku->fresh()->reserved);
        $this->assertSame(5,$sku->fresh()->on_hand);
        $other=app(CheckoutService::class)->submit($this->customer(),[$sku->id=>1],'expire');
        $this->travel(25)->hours();
        $this->artisan('orders:expire')->assertSuccessful();
        $this->artisan('orders:expire')->assertSuccessful();
        $this->assertSame(OrderStatus::Expired,$other->fresh()->status);
        $this->assertSame(0,$sku->fresh()->reserved);
    }

    public function test_confirmed_order_does_not_expire(): void
    {
        $sku=$this->item(); $order=app(CheckoutService::class)->submit($this->customer(),[$sku->id=>1],'confirmed');
        app(OrderLifecycleService::class)->transition($order,OrderStatus::Confirmed);
        $this->travel(25)->hours(); $this->artisan('orders:expire')->assertSuccessful();
        $this->assertSame(OrderStatus::Confirmed,$order->fresh()->status);
        $this->assertSame(1,$sku->fresh()->reserved);
    }

    public function test_invalid_transition_rejected(): void
    {
        $sku=$this->item(); $order=app(CheckoutService::class)->submit($this->customer(),[$sku->id=>1],'invalid-transition');
        $this->expectException(ValidationException::class);
        app(OrderLifecycleService::class)->transition($order,OrderStatus::Delivered);
    }

    public function test_returns_require_explicit_bounded_restock(): void
    {
        $sku=$this->item(); $order=app(CheckoutService::class)->submit($this->customer(),[$sku->id=>2],'return');
        $service=app(OrderLifecycleService::class);
        foreach ([OrderStatus::Confirmed,OrderStatus::Dispatched,OrderStatus::Returned] as $status) $service->transition($order,$status);
        $this->assertSame(3,$sku->fresh()->on_hand);
        $actor=User::factory()->create(['role'=>'owner']);
        $service->restockReturn($order->items()->first(),2,$actor,'return-event-one');
        $this->assertSame(5,$sku->fresh()->on_hand);
        $this->expectException(ValidationException::class);
        $service->restockReturn($order->items()->first(),1,$actor,'return-event-two');
    }
}

