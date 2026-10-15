<?php
namespace App\Services;
use App\Models\{Product,Article};
use Carbon\CarbonImmutable;
class SeoService {
    public function product(Product $product): array {
        $url=route('product',$product->slug);$offers=[];
        foreach($product->skus as $sku){$quote=app(PricingService::class)->quote($sku,CarbonImmutable::now());$offers[]=['@type'=>'Offer','sku'=>$sku->code,'url'=>$url,'price'=>number_format($quote['current_minor']/100,2,'.',''),'priceCurrency'=>'BDT','availability'=>'https://schema.org/'.($sku->availableQuantity()>0?'InStock':'OutOfStock'),'itemCondition'=>'https://schema.org/NewCondition'];}
        $schema=['@context'=>'https://schema.org','@type'=>'Product','name'=>$product->name,'image'=>$product->imageUrl(),'description'=>$product->meta_description?:strip_tags($product->description??''),'offers'=>$offers];
        if($product->brand)$schema['brand']=['@type'=>'Brand','name'=>$product->brand->name];
        return ['title'=>$product->meta_title?:$product->name.' — R&R Care BD','description'=>$product->meta_description?:\Str::limit(strip_tags($product->description??''),155),'canonical'=>$url,'image'=>$product->social_image?\Storage::disk('public')->url($product->social_image):$product->imageUrl(),'schema'=>[$schema,$this->breadcrumb($product->name,$url,'Shop',route('shop'))]];
    }
    public function article(Article $article): array {
        $url=route('article',$article->slug);
        return ['title'=>$article->meta_title?:$article->title.' — R&R Care BD','description'=>$article->meta_description?:$article->excerpt,'canonical'=>$url,'image'=>$article->coverUrl(),'schema'=>[['@context'=>'https://schema.org','@type'=>'BlogPosting','headline'=>$article->title,'datePublished'=>$article->published_at->toIso8601String(),'dateModified'=>$article->updated_at->toIso8601String(),'image'=>$article->coverUrl(),'mainEntityOfPage'=>$url,'author'=>['@type'=>$article->author?'Person':'Organization','name'=>$article->author?->name??'R&R Care BD']],$this->breadcrumb($article->title,$url,'Journal',route('journal'))]];
    }
    private function breadcrumb(string $name,string $url,string $parent,string $parentUrl): array {
        return ['@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>[['@type'=>'ListItem','position'=>1,'name'=>'Home','item'=>url('/')],['@type'=>'ListItem','position'=>2,'name'=>$parent,'item'=>$parentUrl],['@type'=>'ListItem','position'=>3,'name'=>$name,'item'=>$url]]];
    }
}
