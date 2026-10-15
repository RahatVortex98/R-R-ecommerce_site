<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
class Sku extends Model
{
    protected $guarded=[];
    protected function casts(): array{return ['regular_minor'=>'integer','sale_minor'=>'integer','on_hand'=>'integer','reserved'=>'integer','sale_starts_at'=>'immutable_datetime','sale_ends_at'=>'immutable_datetime'];}
    public function product(){return $this->belongsTo(Product::class);}
    public function movements(){return $this->hasMany(InventoryMovement::class);}
    public function availableQuantity(): int{return max(0,$this->on_hand-$this->reserved);}
    protected static function booted(): void
    {
        static::saving(function(Sku $sku){
            if($sku->regular_minor<1||($sku->sale_minor!==null&&($sku->sale_minor<1||$sku->sale_minor>=$sku->regular_minor))) throw ValidationException::withMessages(['sale_minor'=>'Sale price must be positive and below regular price.']);
            if($sku->on_hand<0||$sku->reserved<0||$sku->reserved>$sku->on_hand) throw ValidationException::withMessages(['stock'=>'Stock cannot be negative or below reserved quantity.']);
            if($sku->sale_starts_at&&$sku->sale_ends_at&&$sku->sale_ends_at<=$sku->sale_starts_at) throw ValidationException::withMessages(['sale_ends_at'=>'Sale must end after it starts.']);
        });
    }
}
