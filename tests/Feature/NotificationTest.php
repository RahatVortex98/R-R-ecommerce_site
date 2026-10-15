<?php
namespace Tests\Feature;
use App\Models\{Product,DeliveryZone,Order};
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
class NotificationTest extends TestCase {
    use DatabaseMigrations;
    public function test_checkout_retry_sends_only_one_confirmation_after_commit(): void{
        $sku=Product::create(['name'=>'Mail care','slug'=>'mail-care','published'=>true])->skus()->create(['code'=>'MAIL-1','regular_minor'=>100000,'on_hand'=>5]);
        $zone=DeliveryZone::create(['name'=>'Dhaka','active'=>true,'fee_minor'=>6000]);
        $customer=['name'=>'Mail test','phone'=>'01700000000','address'=>'Test address, Dhaka','email'=>'test@example.test','delivery_zone_id'=>$zone->id];
        $order=app(CheckoutService::class)->submit($customer,[$sku->id=>1],'notification-token');
        app(CheckoutService::class)->submit($customer,[$sku->id=>1],'notification-token');
        $this->assertSame(1,Order::count());$this->assertNotNull($order->fresh()->notification_sent_at);
        $this->assertCount(1,Mail::getSymfonyTransport()->messages());
    }
}
