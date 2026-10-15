<?php
namespace App\Console\Commands;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderLifecycleService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
class ExpirePendingOrders extends Command {
    protected $signature='orders:expire';protected $description='Release expired unconfirmed COD reservations';
    public function handle(OrderLifecycleService $service): int {
        Order::where('status','pending')->where('expires_at','<=',now())->chunkById(100,function($orders)use($service){foreach($orders as $order){try{$service->transition($order,OrderStatus::Expired);}catch(ValidationException){/* A concurrent confirmation/cancellation won the lock. */}}});
        return self::SUCCESS;
    }
}
