<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\{Product,Category,Brand,Concern,Article,DeliveryZone};
class DemoCatalogSeeder extends Seeder {
    public function run(): void {
        $skin=Category::firstOrCreate(['slug'=>'skincare'],['name'=>'Skincare']);
        $body=Category::firstOrCreate(['slug'=>'body-care'],['name'=>'Body care']);
        $hair=Category::firstOrCreate(['slug'=>'hair-care'],['name'=>'Hair care']);
        $brand=Brand::firstOrCreate(['slug'=>'rr-demo'],['name'=>'R&R · Sample collection']);
        $hydration=Concern::firstOrCreate(['slug'=>'hydration'],['name'=>'Hydration']);
        $data=[
            ['Daily Hydrating Serum','serum',125000,99000,$skin,'30 ml'],
            ['Comfort Barrier Cream','cream',145000,119000,$skin,'50 g'],
            ['Gentle Everyday Cleanser','cleanser',95000,null,$skin,'150 ml'],
            ['Everyday Sun Care SPF 50','sunscreen',135000,108000,$skin,'50 ml'],
            ['Dew Drop Hydrating Toner','toner',110000,null,$skin,'100 ml'],
            ['Soft Touch Body Lotion','body',85000,69000,$body,'200 ml'],
            ['Nourishing Scalp Essence','hair',160000,128000,$hair,'100 ml'],
            ['Melt Away Cleansing Balm','balm',140000,112000,$skin,'75 g'],
        ];
        foreach($data as [$name,$art,$price,$sale,$category,$size]){
            $p=Product::firstOrCreate(['slug'=>\Str::slug($name)],['name'=>$name,'category_id'=>$category->id,'brand_id'=>$brand->id,'published'=>true,'sample'=>true,'description'=>'Sample product for the R&R Care BD demonstration catalog. Replace this description, ingredients and images with verified details for your real product.','ingredients'=>'Replace with the manufacturer’s verified ingredient list.','usage'=>'Follow the actual product label. Patch test before first use.','meta_title'=>$name.' — R&R Care BD','meta_description'=>'Explore '.$name.' in the R&R Care BD sample collection.']);
            $p->skus()->firstOrCreate(['code'=>'DEMO-'.strtoupper($art)],['label'=>$size,'regular_minor'=>$price,'sale_minor'=>$sale,'on_hand'=>20]);
            $p->images()->firstOrCreate(['path'=>'images/sample-'.$art.'.svg'],['alt'=>$name.' — sample illustration','position'=>0]);
            $p->concerns()->syncWithoutDetaching([$hydration->id]);
        }
        DeliveryZone::firstOrCreate(['name'=>'Inside Dhaka'],['fee_minor'=>6000,'active'=>true]);
        DeliveryZone::firstOrCreate(['name'=>'Outside Dhaka'],['fee_minor'=>12000,'active'=>true]);
        $stories=[
            ['A simpler routine, a little more care','Start with the essentials and make space for a routine you can keep.','<h2>Keep your daily routine considered</h2><p>A gentle cleanser, a moisturizer, and suitable sun protection make a useful starting point for learning about everyday skincare. Introduce products gradually and follow their labels.</p><h2>Get to know your products</h2><p>Our sample <a href="/products/gentle-everyday-cleanser">Gentle Everyday Cleanser</a> and <a href="/products/comfort-barrier-cream">Comfort Barrier Cream</a> show how several product links can live inside one article. Replace this demo guide with reviewed content and actual product details before launch.</p>',[2,1]],
            ['What goes into your everyday moisturizer?','A thoughtful look at labels, textures, and finding your own everyday favorite.','<h2>Read the label first</h2><p>Ingredient lists and manufacturer instructions help you understand what a product contains and how to use it. The right texture is a personal preference; introduce a new product carefully.</p><h2>Build your own routine</h2><p>Explore the sample <a href="/products/daily-hydrating-serum">Daily Hydrating Serum</a> and <a href="/products/comfort-barrier-cream">Comfort Barrier Cream</a>. These are demonstration products, not specific treatment recommendations.</p>',[0,1]],
            ['Make everyday sun care a little easier','A small everyday ritual starts with reading the label and choosing thoughtfully.','<h2>Know your sun-care product</h2><p>Follow the manufacturer’s directions for application and reapplication. Product labels should provide the information you need to use the product as intended.</p><h2>Explore your essentials</h2><p>See our sample <a href="/products/everyday-sun-care-spf-50">Everyday Sun Care</a> and <a href="/products/dew-drop-hydrating-toner">Hydrating Toner</a>. Replace this example with accurate editorial content before launch.</p>',[3,4]],
        ];
        foreach($stories as $index=>[$title,$excerpt,$content,$related]){
            $a=Article::firstOrCreate(['slug'=>\Str::slug($title)],['title'=>$title,'excerpt'=>$excerpt,'body'=>$content,'status'=>'published','published_at'=>now()->subDays(3-$index),'cover_path'=>'images/journal-'.($index+1).'.svg','cover_alt'=>$title.' — sample editorial illustration','sample'=>true,'tags'=>['Everyday care','Routine']]);
            $a->products()->syncWithoutDetaching(array_map(fn($i)=>Product::where('slug',\Str::slug($data[$i][0]))->value('id'),$related));
        }
    }
}
