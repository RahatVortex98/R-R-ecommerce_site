<?php
namespace App\Livewire;
use App\Services\CartService;
use App\Models\Sku;
use Illuminate\Validation\ValidationException;
class Cart extends Storefront {
    public function remove(int $id): void{$cart=session('cart',[]);unset($cart[$id]);session(['cart'=>$cart]);$this->dispatch('cart-updated');}
    public function quantity(int $id,int $quantity): void{
        $sku=Sku::with('product')->find($id);if(!$sku||!$sku->product?->published||$quantity<1||$quantity>min(99,$sku->availableQuantity()))throw ValidationException::withMessages(['cart'=>'Requested quantity is unavailable.']);
        $cart=session('cart',[]);if(!isset($cart[$id]))return;$cart[$id]=$quantity;session(['cart'=>$cart]);$this->dispatch('cart-updated');
    }
    public function render(){return view('livewire.cart',app(CartService::class)->summary())->layout('layouts.app',['seo'=>$this->seo('Your bag — R&R Care BD',noindex:true)]);}
}
