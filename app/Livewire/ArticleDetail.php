<?php
namespace App\Livewire;
use App\Models\Article;
use App\Services\SeoService;
use Livewire\Attributes\Locked;
class ArticleDetail extends Storefront {
    #[Locked] public int $articleId;
    public function mount(string $slug): void{$this->articleId=Article::published()->where('slug',$slug)->firstOrFail()->id;}
    public function render(){$article=Article::published()->with(['author','concerns'])->findOrFail($this->articleId);return view('livewire.article-detail',['article'=>$article,'products'=>$article->products()->published()->with(['skus','images','brand'])->get()])->layout('layouts.app',['seo'=>app(SeoService::class)->article($article)]);}
}
