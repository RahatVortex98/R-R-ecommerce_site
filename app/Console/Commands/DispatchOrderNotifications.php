<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\Order;
use App\Jobs\SendOrderConfirmation;
class DispatchOrderNotifications extends Command {
    protected $signature='orders:notify';protected $description='Recover and dispatch durable pending order notifications';
    public function handle(): int {
        $failed=false;
        Order::whereNull('notification_sent_at')->chunkById(100,function($orders)use(&$failed){foreach($orders as $order){try{SendOrderConfirmation::enqueue($order->id);}catch(\Throwable $e){$failed=true;report($e);}}});
        return $failed?self::FAILURE:self::SUCCESS;
    }
}
