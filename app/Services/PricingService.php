<?php
namespace App\Services;
use App\Models\Sku;
use Carbon\CarbonImmutable;
class PricingService {
    public function quote(Sku $sku,CarbonImmutable $at): array {
        $sale=$sku->sale_minor!==null&&(!$sku->sale_starts_at||$at>=$sku->sale_starts_at)&&(!$sku->sale_ends_at||$at<$sku->sale_ends_at);
        $regular=$sku->regular_minor;$current=$sale?$sku->sale_minor:$regular;$saving=$regular-$current;
        return ['regular_minor'=>$regular,'current_minor'=>$current,'saving_minor'=>$saving,'discount_percent'=>$regular?intdiv($saving*100,$regular):0];
    }
    public static function money(int $minor): string{return '৳'.number_format($minor/100,$minor%100?2:0);}
}
