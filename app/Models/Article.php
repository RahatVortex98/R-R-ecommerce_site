<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Services\ArticleContentSanitizer;
class Article extends Model
{
    use HasPublicSlug;
    protected $guarded=[];
    protected function casts(): array{return ['published_at'=>'immutable_datetime','tags'=>'array','sample'=>'boolean'];}
    public function scopePublished($q){return $q->where('status','published')->whereNotNull('published_at')->where('published_at','<=',now());}
    public function products(){return $this->belongsToMany(Product::class);}
    public function concerns(){return $this->belongsToMany(Concern::class);}
    public function category(){return $this->belongsTo(Category::class);}
    public function author(){return $this->belongsTo(User::class,'user_id');}
    protected static function booted(): void{static::saving(fn(Article $article)=>$article->body=app(ArticleContentSanitizer::class)->sanitize($article->body));}
    public function coverUrl(): string{return $this->cover_path?(str_starts_with($this->cover_path,'images/')?asset($this->cover_path):\Storage::disk('public')->url($this->cover_path)):asset('images/journal-routine.svg');}
}
