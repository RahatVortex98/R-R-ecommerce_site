<?php
namespace App\Livewire;
use App\Models\Product;
use App\Services\SeoService;
use Livewire\Attributes\Locked;
class ProductDetail extends Storefront {
    #[Locked] public int $productId;
    public int $skuId=0;
    public function mount(string $slug): void{$product=Product::published()->where('slug',$slug)->firstOrFail();$this->productId=$product->id;$this->skuId=$product->displaySku()?->id??0;}
    public function addSelected(): void{$product=Product::published()->findOrFail($this->productId);abort_unless($product->skus()->whereKey($this->skuId)->exists(),404);$this->add($this->skuId);}
    public function render(){
        $product=Product::published()->with(['skus','images','category','brand','concerns'])->findOrFail($this->productId);
        $sku=$product->skus->firstWhere('id',$this->skuId)??$product->skus->first();
        return view('livewire.product-detail',['product'=>$product,'sku'=>$sku,'articles'=>$product->articles()->published()->get()])->layout('layouts.app',['seo'=>app(SeoService::class)->product($product)]);
    }
}
