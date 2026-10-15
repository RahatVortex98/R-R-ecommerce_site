<?php
namespace App\Services;

use App\Models\{Order,Sku,DeliveryZone};
use App\Enums\OrderStatus;
use App\Jobs\SendOrderConfirmation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\{DB,Validator};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\UniqueConstraintViolationException;

class CheckoutService
{
    public function submit(array $customer,array $cart,string $token): Order
    {
        if($existing=Order::where('checkout_token',$token)->first()) return $existing;
        $data=Validator::make($customer,[
            'name'=>'required|string|min:2|max:120','phone'=>['required','regex:/^(?:\+?88)?01[3-9][0-9]{8}$/'],
            'address'=>'required|string|min:10|max:1000','email'=>'nullable|email|max:190','note'=>'nullable|string|max:1000','delivery_zone_id'=>'required|integer',
        ])->validate();
        if(!$cart||count($cart)>50||strlen($token)<1||strlen($token)>100) throw ValidationException::withMessages(['cart'=>'Your cart is empty or invalid.']);
        foreach($cart as $id=>$quantity) if(!ctype_digit((string)$id)||!is_int($quantity)||$quantity<1||$quantity>99) throw ValidationException::withMessages(['cart'=>'Choose a quantity between 1 and 99.']);
        ksort($cart,SORT_NUMERIC);
        try {
            return DB::transaction(function()use($data,$cart,$token,$customer){
                if($existing=Order::where('checkout_token',$token)->first()) return $existing;
                $zone=DeliveryZone::where('active',true)->find($data['delivery_zone_id']);
                if(!$zone) throw ValidationException::withMessages(['delivery_zone_id'=>'Please select an available delivery area.']);
                $skus=Sku::with('product')->whereIn('id',array_keys($cart))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                // A retry may have waited while the winning request reserved the last unit.
                // Use a current locking read: a plain read can retain MySQL's earlier snapshot.
                if($existing=Order::where('checkout_token',$token)->lockForUpdate()->first())return $existing;
                $lines=[];$subtotal=0;$fingerprint=[];$pricing=app(PricingService::class);
                foreach($cart as $id=>$quantity){
                    $sku=$skus->get($id);
                    if(!$sku||!$sku->product->published||$sku->availableQuantity()<$quantity) throw ValidationException::withMessages(['cart'=>'An item is unavailable or its quantity exceeds current stock. Please review your cart.']);
                    $quote=$pricing->quote($sku,CarbonImmutable::now());
                    $subtotal+=$quote['current_minor']*$quantity;
                    $fingerprint[]=$id.':'.$quantity.':'.$quote['current_minor'];
                    $lines[]=[$sku,$quantity,$quote];
                }
                $review=hash('sha256',implode('|',$fingerprint).'|'.$zone->id.':'.$zone->fee_minor);
                if(isset($customer['review_hash'])&&!hash_equals($review,$customer['review_hash'])) throw ValidationException::withMessages(['cart'=>'Prices or delivery fees changed. Review the updated totals and submit again.']);
                $order=Order::create(array_merge(collect($data)->except('delivery_zone_id')->all(),[
                    'public_reference'=>(string)Str::uuid(),'checkout_token'=>$token,'status'=>OrderStatus::Pending,
                    'expires_at'=>now()->addHours((int)app(StoreSettings::class)->get('pending_hours',24)),
                    'delivery_zone_name'=>$zone->name,'subtotal_minor'=>$subtotal,'delivery_minor'=>$zone->fee_minor,'total_minor'=>$subtotal+$zone->fee_minor,
                ]));
                foreach($lines as [$sku,$quantity,$quote]){
                    $order->items()->create(['sku_id'=>$sku->id,'product_name'=>$sku->product->name,'sku_code'=>$sku->code,'sku_label'=>$sku->label,'quantity'=>$quantity,'regular_minor'=>$quote['regular_minor'],'unit_minor'=>$quote['current_minor'],'saving_minor'=>$quote['saving_minor']]);
                    $sku->reserved+=$quantity;$sku->save();
                    $sku->movements()->create(['order_id'=>$order->id,'on_hand_delta'=>0,'reserved_delta'=>$quantity,'reason'=>'COD reservation']);
                }
                $recipients=array_filter(array_unique([$order->email,app(StoreSettings::class)->get('notification_recipient')]));
                foreach($recipients as $recipient)$order->notificationDeliveries()->create(['recipient'=>$recipient]);
                DB::afterCommit(function()use($order){try{SendOrderConfirmation::enqueue($order->id);}catch(\Throwable $e){report($e);}});
                return $order;
            },3);
        }catch(UniqueConstraintViolationException $e){return Order::where('checkout_token',$token)->first()??throw $e;}
    }
}
