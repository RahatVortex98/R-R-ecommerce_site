<?php
namespace App\Livewire;
use App\Models\Article;
use Livewire\WithPagination;
class BlogIndex extends Storefront {
    use WithPagination;
    public function render(){return view('livewire.blog-index',['articles'=>Article::published()->latest('published_at')->paginate(9)])->layout('layouts.app',['seo'=>$this->seo('The care journal — R&R Care BD','Practical skincare guides, ingredient explainers and everyday routines.')]);}
}
