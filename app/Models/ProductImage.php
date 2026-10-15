<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
class ProductImage extends Model {
    protected $guarded=[];
    public function product(){return $this->belongsTo(Product::class);}
    public function url(): string{return str_starts_with($this->path,'images/')?asset($this->path):Storage::disk('public')->url($this->path);}
}
