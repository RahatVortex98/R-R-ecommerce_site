@php($sku=$product->displaySku($discountOnly??false))
@php($quote=$sku?app(\App\Services\PricingService::class)->quote($sku,\Carbon\CarbonImmutable::now()):null)
@php($showSale=!($regularPriceOnly??false)&&$quote&&$quote['saving_minor']>0)
<article class="product-card">
<a class="product-image" href="{{ route('product',$product->slug) }}">
@if($showSale)<span class="badge sale">−{{ $quote['discount_percent'] }}%</span>@elseif($product->sample)<span class="badge">SAMPLE</span>@endif
<img src="{{ $product->imageUrl() }}" @if($product->images->first()?->srcset()) srcset="{{ $product->images->first()->srcset() }}" sizes="(max-width:700px) 45vw, 25vw" @endif alt="{{ $product->images->first()?->alt??$product->name }}" width="400" height="450" loading="lazy">
</a><div class="product-info"><span class="product-brand">{{ $product->brand?->name??'Everyday care' }}</span><h3><a href="{{ route('product',$product->slug) }}">{{ $product->name }}</a></h3>
@if($quote)<div class="price">{{ \App\Services\PricingService::money(($regularPriceOnly??false)?$quote['regular_minor']:$quote['current_minor']) }}@if($showSale)<del>{{ \App\Services\PricingService::money($quote['regular_minor']) }}</del><span class="saving">Save {{ \App\Services\PricingService::money($quote['saving_minor']) }}</span>@endif</div>@else<span class="muted">Coming soon</span>@endif</div>
<button class="card-cart" @disabled(!$sku||$sku->availableQuantity()<1) @if($sku) wire:click="add({{ $sku->id }})" @endif wire:loading.attr="disabled"><span>{{ $sku&&$sku->availableQuantity()>0?'Add to bag':'Out of stock' }}</span><span aria-hidden="true">＋</span></button>
</article>
