<?php
namespace App\Services;
use App\Models\{Sku,DeliveryZone};
use Carbon\CarbonImmutable;
class CartService {
    public function summary(?int $zoneId=null): array {
        $cart=session('cart',[]);$items=[];$subtotal=0;$fingerprint=[];
        $skus=Sku::with(['product.images'])->whereIn('id',array_keys($cart))->orderBy('id')->get();
        foreach($skus as $sku){$quantity=(int)($cart[$sku->id]??0);$quote=app(PricingService::class)->quote($sku,CarbonImmutable::now());$subtotal+=$quote['current_minor']*$quantity;$items[]=compact('sku','quantity','quote');$fingerprint[]=$sku->id.':'.$quantity.':'.$quote['current_minor'];}
        $zone=$zoneId?DeliveryZone::where('active',true)->find($zoneId):null;$delivery=$zone?->fee_minor??0;
        return ['items'=>$items,'subtotal'=>$subtotal,'delivery'=>$delivery,'total'=>$subtotal+$delivery,'hash'=>hash('sha256',implode('|',$fingerprint).'|'.($zone?->id??'').':'.$delivery)];
    }
}
