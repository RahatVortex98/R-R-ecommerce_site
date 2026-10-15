<?php
namespace App\Http\Controllers;
use App\Models\{Product,Article};
class SitemapController {
    public function __invoke(){return response()->view('seo.sitemap',['products'=>Product::published()->get(['slug','updated_at']),'articles'=>Article::published()->get(['slug','updated_at']),'categories'=>\App\Models\Category::orderBy('slug')->get()])->header('Content-Type','application/xml');}
}
