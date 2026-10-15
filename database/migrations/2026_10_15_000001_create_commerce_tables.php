<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users',fn(Blueprint $t)=>$t->string('role')->default('staff'));
        foreach(['categories','brands','concerns'] as $name) Schema::create($name,function(Blueprint $t){$t->id();$t->string('name');$t->string('slug')->unique();$t->text('description')->nullable();$t->string('meta_title')->nullable();$t->text('meta_description')->nullable();$t->timestamps();});
        Schema::create('products',function(Blueprint $t){$t->id();$t->foreignId('category_id')->nullable()->constrained()->nullOnDelete();$t->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();$t->string('name');$t->string('slug')->unique();$t->boolean('published')->default(false);$t->text('description')->nullable();$t->text('ingredients')->nullable();$t->text('usage')->nullable();$t->string('meta_title')->nullable();$t->text('meta_description')->nullable();$t->string('social_image')->nullable();$t->boolean('sample')->default(false);$t->timestamps();});
        Schema::create('skus',function(Blueprint $t){$t->id();$t->foreignId('product_id')->constrained()->restrictOnDelete();$t->string('code')->unique();$t->string('label')->default('Standard');$t->unsignedBigInteger('regular_minor');$t->unsignedBigInteger('sale_minor')->nullable();$t->timestamp('sale_starts_at')->nullable();$t->timestamp('sale_ends_at')->nullable();$t->unsignedInteger('on_hand')->default(0);$t->unsignedInteger('reserved')->default(0);$t->timestamps();});
        Schema::create('product_images',function(Blueprint $t){$t->id();$t->foreignId('product_id')->constrained()->cascadeOnDelete();$t->string('path');$t->string('alt');$t->integer('position')->default(0);$t->timestamps();});
        Schema::create('concern_product',function(Blueprint $t){$t->foreignId('concern_id')->constrained()->cascadeOnDelete();$t->foreignId('product_id')->constrained()->cascadeOnDelete();$t->primary(['concern_id','product_id']);});
        Schema::create('store_settings',function(Blueprint $t){$t->id();$t->string('key')->unique();$t->json('value')->nullable();$t->timestamps();});
        Schema::create('delivery_zones',function(Blueprint $t){$t->id();$t->string('name');$t->unsignedBigInteger('fee_minor');$t->boolean('active')->default(true);$t->timestamps();});
        Schema::create('orders',function(Blueprint $t){$t->id();$t->uuid('public_reference')->unique();$t->string('checkout_token',100)->unique();$t->string('status')->default('pending')->index();$t->timestamp('expires_at')->nullable()->index();$t->string('name');$t->string('phone');$t->text('address');$t->string('email')->nullable();$t->text('note')->nullable();$t->text('staff_note')->nullable();$t->string('delivery_zone_name');$t->unsignedBigInteger('subtotal_minor');$t->unsignedBigInteger('delivery_minor');$t->unsignedBigInteger('total_minor');$t->timestamp('notification_sent_at')->nullable();$t->timestamp('delivered_at')->nullable();$t->timestamps();});
        Schema::create('order_items',function(Blueprint $t){$t->id();$t->foreignId('order_id')->constrained()->restrictOnDelete();$t->foreignId('sku_id')->constrained()->restrictOnDelete();$t->string('product_name');$t->string('sku_code');$t->string('sku_label');$t->unsignedInteger('quantity');$t->unsignedInteger('restocked')->default(0);$t->unsignedBigInteger('regular_minor');$t->unsignedBigInteger('unit_minor');$t->unsignedBigInteger('saving_minor');$t->timestamps();});
        Schema::create('inventory_movements',function(Blueprint $t){$t->id();$t->foreignId('sku_id')->constrained()->restrictOnDelete();$t->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();$t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();$t->integer('on_hand_delta');$t->integer('reserved_delta');$t->string('reason');$t->timestamps();});
        Schema::create('articles',function(Blueprint $t){$t->id();$t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();$t->foreignId('category_id')->nullable()->constrained()->nullOnDelete();$t->string('title');$t->string('slug')->unique();$t->text('excerpt')->nullable();$t->longText('body');$t->string('status')->default('draft');$t->timestamp('published_at')->nullable();$t->string('cover_path')->nullable();$t->string('cover_alt')->nullable();$t->json('tags')->nullable();$t->string('meta_title')->nullable();$t->text('meta_description')->nullable();$t->string('social_image')->nullable();$t->boolean('sample')->default(false);$t->timestamps();});
        Schema::create('article_product',function(Blueprint $t){$t->foreignId('article_id')->constrained()->cascadeOnDelete();$t->foreignId('product_id')->constrained()->cascadeOnDelete();$t->primary(['article_id','product_id']);});
        Schema::create('article_concern',function(Blueprint $t){$t->foreignId('article_id')->constrained()->cascadeOnDelete();$t->foreignId('concern_id')->constrained()->cascadeOnDelete();$t->primary(['article_id','concern_id']);});
        Schema::create('slug_redirects',function(Blueprint $t){$t->id();$t->string('old_path')->unique();$t->string('model_type');$t->unsignedBigInteger('model_id');$t->timestamps();});
    }
    public function down(): void
    {
        foreach(['slug_redirects','article_concern','article_product','articles','inventory_movements','order_items','orders','delivery_zones','store_settings','concern_product','product_images','skus','products','concerns','brands','categories'] as $table) Schema::dropIfExists($table);
        Schema::table('users',fn(Blueprint $t)=>$t->dropColumn('role'));
    }
};
