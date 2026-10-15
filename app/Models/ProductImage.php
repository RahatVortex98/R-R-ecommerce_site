<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
class ProductImage extends Model {
    protected $guarded=[];
    public function product(){return $this->belongsTo(Product::class);}
    protected static function booted(): void{static::saved(function(ProductImage $image){if($image->wasChanged('path')||$image->wasRecentlyCreated)app(\App\Services\ImageVariants::class)->generate($image->path);});}
    public function srcset(): string{return app(\App\Services\ImageVariants::class)->srcset($this->path);}
    public function url(): string{return str_starts_with($this->path,'images/')?asset($this->path):Storage::disk('public')->url($this->path);}
}
