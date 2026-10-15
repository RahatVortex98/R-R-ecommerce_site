<?php
namespace App\Jobs;
use App\Models\Order;
use App\Services\StoreSettings;
use App\Mail\OrderConfirmation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Mail;
class SendOrderConfirmation implements ShouldQueue
{
    use Queueable;
    public int $tries=3;
    public function __construct(public int $orderId){}
    public function backoff(): array{return [30,120,300];}
    public function middleware(): array{return [(new WithoutOverlapping('order-mail-'.$this->orderId))->expireAfter(180)];}
    public function handle(): void{
        $order=Order::findOrFail($this->orderId);if($order->notification_sent_at)return;
        $recipients=array_filter(array_unique([$order->email,app(StoreSettings::class)->get('notification_recipient')]));
        foreach($recipients as $email)Mail::to($email)->send(new OrderConfirmation($order));
        $order->update(['notification_sent_at'=>now()]);
    }
}
