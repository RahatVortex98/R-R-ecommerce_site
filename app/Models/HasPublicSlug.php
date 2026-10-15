<?php
namespace App\Models;
trait HasPublicSlug
{
    public static function bootHasPublicSlug(): void
    {
        static::updating(function($model){
            if($model->isDirty('slug')){
                $prefix=$model instanceof Article?'/journal/':'/products/';
                SlugRedirect::updateOrCreate(['old_path'=>$prefix.$model->getOriginal('slug')],['model_type'=>$model::class,'model_id'=>$model->id]);
            }
        });
    }
    public function getRouteKeyName(): string{return 'slug';}
}
