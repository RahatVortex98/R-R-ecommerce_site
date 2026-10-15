<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryMovement extends Model {protected $guarded=[];public function sku(){return $this->belongsTo(Sku::class);}public function order(){return $this->belongsTo(Order::class);}public function user(){return $this->belongsTo(User::class);}}
