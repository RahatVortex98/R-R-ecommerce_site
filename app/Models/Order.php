<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Enums\OrderStatus;
class Order extends Model
{
    protected $guarded=[];
    protected function casts(): array{return ['status'=>OrderStatus::class,'expires_at'=>'immutable_datetime','delivered_at'=>'immutable_datetime','notification_sent_at'=>'datetime','subtotal_minor'=>'integer','delivery_minor'=>'integer','total_minor'=>'integer'];}
    public function items(){return $this->hasMany(OrderItem::class);}
    public function movements(){return $this->hasMany(InventoryMovement::class);}
}
