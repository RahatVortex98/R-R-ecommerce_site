<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('inventory_movements',fn(Blueprint $t)=>$t->string('operation_key',150)->nullable()->unique());
        Schema::create('order_notification_deliveries',function(Blueprint $t){$t->id();$t->foreignId('order_id')->constrained()->cascadeOnDelete();$t->string('recipient',190);$t->timestamp('sent_at')->nullable()->index();$t->timestamps();$t->unique(['order_id','recipient']);});
    }
    public function down(): void {Schema::dropIfExists('order_notification_deliveries');Schema::table('inventory_movements',function(Blueprint $t){$t->dropUnique(['operation_key']);$t->dropColumn('operation_key');});}
};
