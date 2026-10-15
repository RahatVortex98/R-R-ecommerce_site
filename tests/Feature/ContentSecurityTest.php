<?php
namespace Tests\Feature;

use App\Models\{Article, Product};
use App\Services\ArticleContentSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentSecurityTest extends TestCase
{
    use RefreshDatabase;
    public function test_rich_content_removes_executable_markup_and_keeps_bangla(): void
    {
        $clean=app(ArticleContentSanitizer::class)->sanitize('<p>ত্বকের যত্ন</p><script>alert(1)</script><a href="javascript:alert(1)" onclick="alert(2)">Link</a>');
        $this->assertStringContainsString('ত্বকের যত্ন',$clean);
        $this->assertStringNotContainsString('<script',$clean);
        $this->assertStringNotContainsString('javascript:',$clean);
        $this->assertStringNotContainsString('onclick',$clean);
    }
    public function test_article_links_multiple_products_and_hides_drafts(): void
    {
        $article=Article::create(['title'=>'A gentle routine','slug'=>'gentle-routine','body'=>'<p>Care guide</p>','status'=>'published','published_at'=>now()->subDay()]);
        $a=Product::create(['name'=>'Linked serum','slug'=>'linked-serum','published'=>true]);
        $b=Product::create(['name'=>'Secret product','slug'=>'secret-product','published'=>false]);
        $article->products()->attach([$a->id,$b->id]);
        $this->get('/journal/gentle-routine')->assertOk()->assertSee('Linked serum')->assertDontSee('Secret product');
        $article->update(['status'=>'draft']);
        $this->get('/journal/gentle-routine')->assertNotFound();
        $article->update(['status'=>'published','published_at'=>now()->addDay()]);
        $this->get('/journal/gentle-routine')->assertNotFound();
    }
}
