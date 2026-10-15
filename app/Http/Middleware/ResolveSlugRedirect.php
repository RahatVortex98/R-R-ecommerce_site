<?php
namespace App\Http\Middleware;
use Closure;
use App\Models\{Product,Article,Category,SlugRedirect};
class ResolveSlugRedirect {
    public function handle($request,Closure $next){
        $path='/'.$request->path();
        if(preg_match('#^/(products|journal|categories)/([^/]+)$#',$path,$matches)){
            $type=match($matches[1]){'products'=>Product::class,'journal'=>Article::class,'categories'=>Category::class};
            if(!$type::where('slug',$matches[2])->exists()){
                $redirect=SlugRedirect::where('old_path',$path)->first();
                if($redirect&&in_array($redirect->model_type,[Product::class,Article::class,Category::class],true)){
                    $query=$redirect->model_type::query();if($redirect->model_type!==Category::class)$query->published();
                    $model=$query->find($redirect->model_id);
                    if($model){$target=$model->publicPathPrefix().$model->slug;if($target!==$path)return redirect($target,301);}
                }
            }
        }
        $response=$next($request);
        if($request->is('admin*','cart','checkout','order/*'))$response->headers->set('X-Robots-Tag','noindex, nofollow');
        if($request->is('cart','checkout','order/*'))$response->headers->set('Cache-Control','private, no-store');
        return $response;
    }
}
