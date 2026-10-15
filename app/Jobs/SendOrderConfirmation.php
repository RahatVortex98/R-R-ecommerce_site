<?php
namespace App\Jobs;
use App\Models\Order;
use App\Services\StoreSettings;
use App\Mail\OrderConfirmation;
use Illuminate\Contracts\Queue\{ShouldQueue,ShouldBeUnique};
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Mail;
class SendOrderConfirmation implements ShouldQueue,ShouldBeUnique
{
    use Queueable;
    public int $tries=3;
    public int $uniqueFor=3600;
    public function __construct(public int $orderId){}
    public static function enqueue(int $id): void {
        $job=new static($id);
        try{dispatch($job);}catch(\Throwable $e){
            // A queue push can fail after PendingDispatch acquired the uniqueness lock.
            try{app(\Illuminate\Bus\UniqueLock::class)->release($job);}catch(\Throwable $releaseError){report($releaseError);}
            throw $e;
        }
    }
    public function backoff(): array{return [30,120,300];}
    public function uniqueId(): string{return 'order-confirmation-'.$this->orderId;}
    public function middleware(): array{return [(new WithoutOverlapping('order-mail-'.$this->orderId))->expireAfter(180)];}
    public function handle(): void{
        $order=Order::findOrFail($this->orderId);if($order->notification_sent_at)return;
        foreach($order->notificationDeliveries()->whereNull('sent_at')->get() as $delivery){
            Mail::to($delivery->recipient)->send(new OrderConfirmation($order));
            $delivery->update(['sent_at'=>now()]);
        }
        $order->update(['notification_sent_at'=>now()]);
    }
}
