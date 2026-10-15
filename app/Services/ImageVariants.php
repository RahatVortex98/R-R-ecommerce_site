<?php
namespace App\Services;
use Illuminate\Support\Facades\Storage;
class ImageVariants {
    public function generate(string $path): void {
        if(!preg_match('/\.(jpe?g|png|webp)$/i',$path))return;
        $disk=Storage::disk('public');if(!$disk->exists($path))return;
        $bytes=$disk->get($path);$size=@getimagesizefromstring($bytes);
        if(!$size||$size[0]<1||$size[1]<1||$size[0]*$size[1]>12000000)return;
        $source=@imagecreatefromstring($bytes);if(!$source)return;
        try{foreach([400,800] as $width){
            if($size[0]<$width)continue;
            $height=(int)round($size[1]*$width/$size[0]);$target=imagecreatetruecolor($width,$height);
            imagealphablending($target,false);imagesavealpha($target,true);
            imagecopyresampled($target,$source,0,0,0,0,$width,$height,$size[0],$size[1]);
            ob_start();imagewebp($target,null,82);$output=ob_get_clean();imagedestroy($target);
            $disk->put($this->variantPath($path,$width),$output);
        }}finally{imagedestroy($source);}
    }
    public function variantPath(string $path,int $width): string{return preg_replace('/\.[^.]+$/','-'.$width.'.webp',$path);}
    public function srcset(string $path): string {
        if(str_starts_with($path,'images/'))return '';
        $disk=Storage::disk('public');$items=[];
        foreach([400,800] as $width){$variant=$this->variantPath($path,$width);if($disk->exists($variant))$items[]=$disk->url($variant).' '.$width.'w';}
        return implode(', ',$items);
    }
}
