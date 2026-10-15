<?php
namespace Tests\Feature;
use App\Services\ImageVariants;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class ImageVariantsTest extends TestCase {
    public function test_uploaded_product_image_gets_small_webp_versions(): void {
        Storage::fake('public');$image=imagecreatetruecolor(1000,1200);ob_start();imagepng($image);$bytes=ob_get_clean();imagedestroy($image);
        Storage::disk('public')->put('products/test.png',$bytes);
        app(ImageVariants::class)->generate('products/test.png');
        foreach([400,800] as $width){$data=Storage::disk('public')->get('products/test-'.$width.'.webp');$size=getimagesizefromstring($data);$this->assertSame($width,$size[0]);$this->assertSame((int)($width*1.2),$size[1]);$this->assertSame('image/webp',$size['mime']);}
    }
    public function test_executable_content_does_not_generate_image_variants(): void {
        Storage::fake('public');Storage::disk('public')->put('products/fake.php','<?php echo 1;');
        app(ImageVariants::class)->generate('products/fake.php');
        $this->assertFalse(Storage::disk('public')->exists('products/fake-400.webp'));
    }
}
