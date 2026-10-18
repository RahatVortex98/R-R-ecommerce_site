<?php
namespace App\Livewire;
use Livewire\Component;
use App\Models\Sku;
use Illuminate\Validation\ValidationException;
abstract class Storefront extends Component
{
    public function add(int $skuId): void
    {
        $sku=Sku::with('product')->find($skuId);$cart=session('cart',[]);$quantity=($cart[$skuId]??0)+1;
        if(!$sku||!$sku->product?->published||$quantity>min(99,$sku->availableQuantity()))throw ValidationException::withMessages(['cart'=>'This quantity is not available.']);
        $cart[$skuId]=$quantity;session(['cart'=>$cart]);$this->dispatch('cart-updated');$this->dispatch('toast',message:'Added to your bag');
    }
    public function seo(string $title,?string $description=null,array $schema=[],bool $noindex=false,?string $canonical=null,?string $image=null): array
    {
        return compact('title','description','schema','noindex','canonical','image');
    }
}
