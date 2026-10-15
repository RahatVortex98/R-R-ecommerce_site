<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Category extends Model { use HasPublicSlug; protected $guarded=[]; public function products(){return $this->hasMany(Product::class);} }
