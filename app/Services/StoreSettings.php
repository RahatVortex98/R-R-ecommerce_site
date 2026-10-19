<?php
namespace App\Services;
use App\Models\StoreSetting;
use Illuminate\Support\Facades\Cache;
class StoreSettings {
    public const DEFAULTS=['store_name'=>'R&R Care BD','primary_color'=>'#193328','accent_color'=>'#E8BBA7','hero_title'=>'A little care. A beautiful everyday.','hero_text'=>'Thoughtful skincare, everyday essentials, and little moments of care. Find a routine that feels like you.','hero_image'=>'images/hero-products.svg','logo'=>'images/logo.jpg','available_heading'=>'Available products','discount_heading'=>'Discount products','section_count'=>4,'pending_hours'=>24,'default_description'=>'Discover skincare, cosmetics and beauty essentials at R&R Care BD. Cash on delivery across Bangladesh.','contact_email'=>'','contact_phone'=>'','contact_address'=>'Bangladesh','facebook_url'=>'','instagram_url'=>'','notification_recipient'=>''];
    public function get(string $key,mixed $default=null): mixed{return Cache::remember('store-settings',300,fn()=>StoreSetting::pluck('value','key')->all())[$key]??self::DEFAULTS[$key]??$default;}
    public function heroSlides(): array
    {
        $configured=$this->get('hero_slides', []);
        $slides=[];
        foreach(is_array($configured)?array_slice($configured,0,6):[] as $slide){
            if(!is_array($slide)||!is_string($slide['image']??null)||trim($slide['image'])==='')continue;
            $slides[]=['url'=>str_starts_with($slide['image'],'images/')?asset($slide['image']):\Storage::disk('public')->url($slide['image']),
                'alt'=>is_string($slide['alt']??null)?$slide['alt']:'R&R Care BD banner'];
        }
        return $slides?:[['url'=>$this->assetUrl('hero_image'),'alt'=>'Illustration of a skincare serum and moisturizer']];
    }
    public function set(string $key,mixed $value): void{StoreSetting::updateOrCreate(['key'=>$key],['value'=>$value]);Cache::forget('store-settings');}
    public function assetUrl(string $key): string{$path=$this->get($key);return str_starts_with($path,'images/')?asset($path):\Storage::disk('public')->url($path);}
}
