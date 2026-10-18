<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Product extends Model
{
    use HasPublicSlug;
    use \Illuminate\Database\Eloquent\SoftDeletes;
    protected $guarded=[];
    protected function casts(): array{return ['published'=>'boolean','sample'=>'boolean'];}
    public function scopePublished($query){return $query->where('published',true);}
    public function skus(){return $this->hasMany(Sku::class)->orderBy('id');}
    public function images(){return $this->hasMany(ProductImage::class)->orderBy('position')->orderBy('id');}
    public function category(){return $this->belongsTo(Category::class);}
    public function brand(){return $this->belongsTo(Brand::class);}
    public function concerns(){return $this->belongsToMany(Concern::class);}
    public function articles(){return $this->belongsToMany(Article::class);}
    public function imageUrl(): string{return $this->images->first()?->url()??asset('images/sample-serum.svg');}
    public function displaySku(bool $discountOnly=false): ?Sku
    {
        $available=$this->skus->filter(fn($sku)=>$sku->availableQuantity()>0);
        $pricing=app(\App\Services\PricingService::class);$at=\Carbon\CarbonImmutable::now();
        if($discountOnly)$available=$available->filter(fn($sku)=>$pricing->quote($sku,$at)['saving_minor']>0);
        return $available->sortBy(fn($sku)=>$pricing->quote($sku,$at)['current_minor'])->first()??$this->skus->first();
    }
}
