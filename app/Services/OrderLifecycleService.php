<?php
namespace App\Services;
use App\Models\{Order,OrderItem,Sku,User};
use App\Enums\OrderStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class OrderLifecycleService
{
    public function transition(Order $order,OrderStatus $to,?User $actor=null): Order
    {
        return DB::transaction(function()use($order,$to,$actor){
            $order=Order::lockForUpdate()->findOrFail($order->id);
            if($order->status===$to)return $order;
            if(!in_array($to,$order->status->allowed(),true)) throw ValidationException::withMessages(['status'=>'This order transition is not allowed.']);
            if($to===OrderStatus::Expired&&(!$order->expires_at||$order->expires_at->isFuture())) throw ValidationException::withMessages(['status'=>'This order is not due for expiry.']);
            $items=$order->items()->orderBy('sku_id')->get();
            if(in_array($to,[OrderStatus::Cancelled,OrderStatus::Expired,OrderStatus::Dispatched],true)){
                foreach($items as $item){
                    $sku=Sku::lockForUpdate()->findOrFail($item->sku_id);
                    $deduct=$to===OrderStatus::Dispatched?-$item->quantity:0;
                    $sku->reserved-=$item->quantity;$sku->on_hand+=$deduct;$sku->save();
                    $sku->movements()->create(['order_id'=>$order->id,'user_id'=>$actor?->id,'on_hand_delta'=>$deduct,'reserved_delta'=>-$item->quantity,'reason'=>$to->value]);
                }
            }
            $order->status=$to;$order->expires_at=null;
            if($to===OrderStatus::Delivered)$order->delivered_at=now();
            $order->save();return $order;
        },3);
    }
    public function restockReturn(OrderItem $item,int $quantity,User $actor,string $requestToken): void
    {
        if(!preg_match('/^[a-zA-Z0-9-]{8,100}$/',$requestToken))throw ValidationException::withMessages(['quantity'=>'Invalid restock request reference.']);
        DB::transaction(function()use($item,$quantity,$actor,$requestToken){
            $order=Order::lockForUpdate()->findOrFail($item->order_id);
            $item=OrderItem::lockForUpdate()->findOrFail($item->id);
            $operationKey='return:'.$item->id.':'.$requestToken;
            if(\App\Models\InventoryMovement::where('operation_key',$operationKey)->exists())return;
            if($order->status!==OrderStatus::Returned||$quantity<1||$quantity>$item->quantity-$item->restocked)throw ValidationException::withMessages(['quantity'=>'Only inspected, un-restocked returned units may be added back.']);
            $sku=Sku::lockForUpdate()->findOrFail($item->sku_id);$sku->on_hand+=$quantity;$sku->save();
            $item->restocked+=$quantity;$item->save();
            $sku->movements()->create(['order_id'=>$order->id,'user_id'=>$actor->id,'on_hand_delta'=>$quantity,'reserved_delta'=>0,'reason'=>'Inspected return restock','operation_key'=>$operationKey]);
        },3);
    }
}
