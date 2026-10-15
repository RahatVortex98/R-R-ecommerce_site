<?php
namespace App\Livewire;
use App\Models\{Product,Article};
use App\Services\{PricingService,StoreSettings};
use Carbon\CarbonImmutable;
class Home extends Storefront {
    public function render(){
        $count=max(1,min(12,(int)app(StoreSettings::class)->get('section_count')));
        $query=Product::published()->with(['skus','images','brand'])->whereHas('skus',fn($q)=>$q->whereColumn('on_hand','>','reserved'));
        $products=(clone $query)->latest()->take($count)->get();
        $now=CarbonImmutable::now();
        $discounts=(clone $query)->whereHas('skus',fn($q)=>$q->whereNotNull('sale_minor')->whereColumn('sale_minor','<','regular_minor')->whereColumn('on_hand','>','reserved')->where(fn($x)=>$x->whereNull('sale_starts_at')->orWhere('sale_starts_at','<=',$now))->where(fn($x)=>$x->whereNull('sale_ends_at')->orWhere('sale_ends_at','>',$now)))->latest()->take($count)->get();
        return view('livewire.home',['products'=>$products,'discounts'=>$discounts,'articles'=>Article::published()->latest('published_at')->take(3)->get()])->layout('layouts.app',['seo'=>$this->seo('R&R Care BD — Thoughtful beauty, everyday care')]);
    }
}
