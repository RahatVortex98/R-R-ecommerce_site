<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class OrderNotificationDelivery extends Model {
    protected $guarded=[];
    protected function casts(): array{return ['sent_at'=>'immutable_datetime'];}
    public function order(){return $this->belongsTo(Order::class);}
}
