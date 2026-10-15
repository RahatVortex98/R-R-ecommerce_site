<?php
namespace App\Livewire;
use App\Models\{Product,Article};
use App\Services\{PricingService,StoreSettings};
use Carbon\CarbonImmutable;
class Home extends Storefront {
    public function render(){
        $count=max(1,min(12,(int)app(StoreSettings::class)->get('section_count')));
        ['products'=>$products,'discounts'=>$discounts]=app(\App\Services\CatalogService::class)->home($count);
        return view('livewire.home',['products'=>$products,'discounts'=>$discounts,'articles'=>Article::published()->latest('published_at')->take(3)->get()])->layout('layouts.app',['seo'=>$this->seo('R&R Care BD — Thoughtful beauty, everyday care')]);
    }
}
