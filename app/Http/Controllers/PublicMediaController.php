<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Storage;
class PublicMediaController {
    /** Fallback for local hosts that cannot create a public/storage symlink. */
    public function __invoke(string $path){
        $regular=preg_match('#^(products|journal|branding|social|images)/[a-zA-Z0-9._-]+\.(jpe?g|png|webp)$#i',$path);
        $sample=preg_match('#^images/(sample-[a-z]+|hero-products|journal-[a-z0-9]+)\.svg$#',$path);
        abort_unless($regular||$sample,404);$disk=Storage::disk('public');abort_unless($disk->exists($path),404);
        return response($disk->get($path),200)->header('Content-Type',$sample?'image/svg+xml':$disk->mimeType($path))->header('X-Content-Type-Options','nosniff')->header('Cache-Control','public, max-age=86400');
    }
}
