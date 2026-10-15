<?php
namespace App\Livewire;
use App\Models\{Product,Category,Brand};
use Livewire\Attributes\Url;
use Livewire\WithPagination;
class Catalog extends Storefront {
    use WithPagination;
    #[Url] public string $q='';#[Url] public string $category='';#[Url] public string $brand='';#[Url] public string $sort='newest';#[Url] public bool $sale=false;
    #[\Livewire\Attributes\Locked] public string $categorySlug='';
    public function mount(?string $slug=null): void{if($slug){$this->categorySlug=Category::where('slug',$slug)->firstOrFail()->slug;$this->category=$this->categorySlug;}}
    public function updated($name): void{if(in_array($name,['q','category','brand','sort','sale']))$this->resetPage();}
    public function render(){
        $query=Product::published()->with(['skus','images','brand']);
        if($this->q!=='')$query->where('name','like','%'.mb_substr($this->q,0,100).'%');
        if($this->category)$query->whereHas('category',fn($q)=>$q->where('slug',$this->category));
        if($this->brand)$query->whereHas('brand',fn($q)=>$q->where('slug',$this->brand));
        if($this->sale)$query->whereHas('skus',fn($q)=>$q->whereNotNull('sale_minor')->whereColumn('on_hand','>','reserved')->where(fn($x)=>$x->whereNull('sale_starts_at')->orWhere('sale_starts_at','<=',now()))->where(fn($x)=>$x->whereNull('sale_ends_at')->orWhere('sale_ends_at','>',now())));
        if(in_array($this->sort,['price-low','price-high']))$query->orderByRaw('(select min(case when sale_minor is not null and (sale_starts_at is null or sale_starts_at <= ?) and (sale_ends_at is null or sale_ends_at > ?) then sale_minor else regular_minor end) from skus where skus.product_id = products.id) '.($this->sort==='price-low'?'asc':'desc'),[now(),now()]);else $query->latest();
        $categoryPage=$this->categorySlug?Category::where('slug',$this->categorySlug)->firstOrFail():null;
        $title=$categoryPage?($categoryPage->meta_title?:$categoryPage->name.' — R&R Care BD'):($this->sale?'Beauty offers — R&R Care BD':'Shop skincare & beauty — R&R Care BD');
        return view('livewire.catalog',['products'=>$query->paginate(12),'categories'=>Category::orderBy('name')->get(),'brands'=>Brand::orderBy('name')->get()])->layout('layouts.app',['seo'=>$this->seo($title,$categoryPage?->meta_description,noindex:request()->query()!==[]||$this->q!==''||$this->category!==$this->categorySlug||$this->brand!==''||$this->sale||$this->sort!=='newest',canonical:$categoryPage?route('category',$categoryPage->slug):url('/shop'))]);
    }
}
