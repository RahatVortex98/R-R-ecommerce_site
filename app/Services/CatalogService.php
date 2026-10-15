<?php
namespace App\Services;
use App\Models\{Product,Sku};
use Illuminate\Support\Facades\Cache;
class CatalogService {
    public function home(int $count): array {
        $ids=Cache::get('home-catalog');
        if($ids===null){
            $query=Product::published()->whereHas('skus',fn($q)=>$q->whereColumn('on_hand','>','reserved'));
            $available=(clone $query)->latest('id')->limit(12)->pluck('id')->all();
            $discounts=(clone $query)->whereHas('skus',fn($q)=>$q->whereNotNull('sale_minor')->whereColumn('on_hand','>','reserved')->where(fn($x)=>$x->whereNull('sale_starts_at')->orWhere('sale_starts_at','<=',now()))->where(fn($x)=>$x->whereNull('sale_ends_at')->orWhere('sale_ends_at','>',now())))->latest('id')->limit(12)->pluck('id')->all();
            $ids=compact('available','discounts');
            $boundaries=Sku::query()->where(fn($q)=>$q->where('sale_starts_at','>',now())->orWhere('sale_ends_at','>',now()))->get(['sale_starts_at','sale_ends_at'])->flatMap(fn($s)=>[$s->sale_starts_at,$s->sale_ends_at])->filter(fn($at)=>$at&&$at->isFuture());
            $until=$boundaries->reduce(fn($earliest,$at)=>$at->lessThan($earliest)?$at:$earliest,now()->addMinute());
            Cache::put('home-catalog',$ids,$until);
        }
        // Only IDs are cached: public status, quantities and displayed prices are always fresh.
        $hydrate=fn($list)=>Product::published()->with(['skus','images','brand'])->whereIn('id',array_slice($list,0,$count))->get()->sortBy(fn($p)=>array_search($p->id,$list))->values();
        return ['products'=>$hydrate($ids['available']),'discounts'=>$hydrate($ids['discounts'])];
    }
}
