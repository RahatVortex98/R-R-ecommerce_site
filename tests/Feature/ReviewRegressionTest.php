<?php
namespace Tests\Feature;
use App\Models\{Product,DeliveryZone,Order,User,Category,Article};
use App\Services\{CheckoutService,OrderLifecycleService,StoreSettings};
use App\Enums\OrderStatus;
use App\Jobs\SendOrderConfirmation;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
class ReviewRegressionTest extends TestCase {
    use DatabaseMigrations;
    private function order(): Order {
        $sku=Product::create(['name'=>'Review care','slug'=>'review-care','published'=>true])->skus()->create(['code'=>'REVIEW-1','regular_minor'=>100000,'on_hand'=>5]);
        $zone=DeliveryZone::create(['name'=>'Dhaka','active'=>true,'fee_minor'=>6000]);
        return app(CheckoutService::class)->submit(['name'=>'Review customer','phone'=>'01700000000','address'=>'Test address, Dhaka','email'=>'customer@example.test','delivery_zone_id'=>$zone->id],[$sku->id=>2],'review-checkout-token');
    }
    public function test_partial_return_replay_does_not_restock_twice_but_new_inspection_can(): void {
        $order=$this->order();$service=app(OrderLifecycleService::class);$owner=User::factory()->create(['role'=>'owner']);
        foreach([OrderStatus::Confirmed,OrderStatus::Dispatched,OrderStatus::Returned] as $status)$service->transition($order,$status,$owner);
        $item=$order->items()->first();
        $service->restockReturn($item,1,$owner,'inspection-event-one');
        $service->restockReturn($item,1,$owner,'inspection-event-one');
        $this->assertSame(4,$item->sku->fresh()->on_hand);$this->assertSame(1,$item->fresh()->restocked);
        $service->restockReturn($item,1,$owner,'inspection-event-two');
        $this->assertSame(5,$item->sku->fresh()->on_hand);
    }
    public function test_second_recipient_failure_does_not_resend_the_first_recipient(): void {
        $transport=new class extends \Illuminate\Mail\Transport\ArrayTransport {
            public bool $failOwner=true;
            public function send(\Symfony\Component\Mime\RawMessage $message,?\Symfony\Component\Mailer\Envelope $envelope=null): ?\Symfony\Component\Mailer\SentMessage {
                $envelope??=\Symfony\Component\Mailer\Envelope::create($message);
                if($envelope->getRecipients()[0]->getAddress()==='owner@example.test'&&$this->failOwner){$this->failOwner=false;throw new \Symfony\Component\Mailer\Exception\TransportException('Temporary owner delivery failure');}
                return parent::send($message,$envelope);
            }
        };
        Mail::extend('selective',fn()=>$transport);config(['mail.default'=>'selective','mail.mailers.selective'=>['transport'=>'selective']]);app(StoreSettings::class)->set('notification_recipient','owner@example.test');
        $order=$this->order();(new SendOrderConfirmation($order->id))->handle();
        $this->assertCount(2,$transport->messages());$this->assertNotNull($order->fresh()->notification_sent_at);
    }
    public function test_queue_submission_outage_has_durable_recovery(): void {
        config(['queue.default'=>'offline-unconfigured']);$order=$this->order();
        $this->assertSame(1,Order::count());$this->assertNull($order->fresh()->notification_sent_at);
        $this->assertDatabaseHas('order_notification_deliveries',['order_id'=>$order->id,'recipient'=>'customer@example.test','sent_at'=>null]);
        config(['queue.default'=>'sync']);$this->artisan('orders:notify')->assertSuccessful();
        $this->assertNotNull($order->fresh()->notification_sent_at);$this->assertCount(1,Mail::getSymfonyTransport()->messages());
    }
    public function test_navigation_uses_actual_categories_and_old_category_slugs_redirect(): void {
        $this->get('/')->assertDontSee('/categories/skincare');
        $category=Category::create(['name'=>'Everyday care','slug'=>'everyday-care']);
        $this->get('/')->assertSee('/categories/everyday-care');
        $category->update(['slug'=>'daily-care']);$this->get('/')->assertSee('/categories/daily-care')->assertDontSee('/categories/everyday-care');
        $this->get('/categories/everyday-care')->assertStatus(301)->assertRedirect('/categories/daily-care');
    }
    public function test_price_sort_uses_the_price_of_an_available_variant(): void {
        $a=Product::create(['name'=>'Mixed care','slug'=>'mixed-care','published'=>true]);$a->skus()->create(['code'=>'MIX-SOLD','regular_minor'=>10000,'on_hand'=>0]);$a->skus()->create(['code'=>'MIX-AVAIL','regular_minor'=>100000,'on_hand'=>2]);
        $b=Product::create(['name'=>'Medium care','slug'=>'medium-care','published'=>true]);$b->skus()->create(['code'=>'MEDIUM','regular_minor'=>50000,'on_hand'=>2]);
        $this->get('/shop?sort=price-low')->assertOk()->assertSeeInOrder(['Medium care','Mixed care']);
    }
    public function test_pagination_has_distinct_indexable_canonical_urls(): void {
        $this->get('/shop?page=2')->assertOk()->assertSee('href="'.url('/shop?page=2').'"',false)->assertDontSee('noindex');
        $this->get('/journal?page=2')->assertOk()->assertSee('href="'.url('/journal?page=2').'"',false);
        $this->get('/shop?q=care&page=2')->assertOk()->assertSee('noindex');
    }
}
