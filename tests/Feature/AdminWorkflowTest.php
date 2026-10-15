<?php
namespace Tests\Feature;
use App\Models\{User,Product,Sku};
use App\Filament\Resources\ProductResource\Pages\ManageProducts;
use App\Filament\Resources\ArticleResource\Pages\ManageArticles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;
class AdminWorkflowTest extends TestCase {
    use RefreshDatabase;
    protected function setUp(): void{parent::setUp();$this->actingAs(User::factory()->create(['role'=>'owner']));\Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));}
    public function test_owner_dashboard_and_all_resource_tables_render(): void{
        foreach(['/admin','/admin/products','/admin/categories','/admin/brands','/admin/concerns','/admin/articles','/admin/orders','/admin/skus','/admin/inventory-movements','/admin/delivery-zones','/admin/users'] as $route)$this->get($route)->assertOk();
    }
    public function test_owner_can_create_a_product_through_filament(): void{
        Livewire::test(ManageProducts::class)->callAction('create',data:['name'=>'Admin created serum','slug'=>'admin-created-serum','published'=>true,'sample'=>false,'skus'=>[],'images'=>[]])->assertHasNoFormErrors();
        $this->assertDatabaseHas('products',['slug'=>'admin-created-serum','published'=>true]);
    }
    public function test_product_editor_does_not_accept_direct_stock_fields(): void{
        $p=Product::create(['name'=>'Stock safe','slug'=>'stock-safe','published'=>true]);$s=$p->skus()->create(['code'=>'SAFE-1','regular_minor'=>100000,'on_hand'=>5]);
        Livewire::test(ManageProducts::class)->callAction(\Filament\Actions\Testing\TestAction::make('edit')->table($p),data:['name'=>'Stock safe edited','slug'=>'stock-safe','published'=>true,'skus'=>['record-'.$s->id=>['id'=>$s->id,'code'=>'SAFE-1','label'=>'Standard','regular_minor'=>1000,'on_hand'=>999,'reserved'=>999]]])->assertHasNoFormErrors();
        $this->assertSame(5,$s->fresh()->on_hand);$this->assertSame(0,$s->fresh()->reserved);
    }
    public function test_invalid_sale_price_is_rejected(): void{
        $p=Product::create(['name'=>'Pricing','slug'=>'pricing']);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $p->skus()->create(['code'=>'BAD-PRICE','regular_minor'=>100000,'sale_minor'=>110000]);
    }
    public function test_sample_product_can_be_edited_without_reuploading_its_image(): void{
        \Storage::fake('public');$this->seed(\Database\Seeders\DemoCatalogSeeder::class);$product=Product::first();
        Livewire::test(ManageProducts::class)->mountAction(\Filament\Actions\Testing\TestAction::make('edit')->table($product))->callMountedAction()->assertHasNoFormErrors();
        $this->assertSame(1,$product->images()->count());
    }
}

