<?php
namespace Tests\Feature;
use App\Models\{User,Order};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
class DashboardTest extends TestCase {
    use RefreshDatabase;
    public function test_dashboard_date_range_filters_delivered_value(): void{
        $this->actingAs(User::factory()->create(['role'=>'owner']));
        foreach([['2026-09-01',500000],['2026-10-15',100000]] as [$date,$value])Order::create(['public_reference'=>(string)\Str::uuid(),'checkout_token'=>(string)\Str::uuid(),'status'=>'delivered','delivered_at'=>$date,'name'=>'Demo','phone'=>'01700000000','address'=>'Demo address','delivery_zone_name'=>'Dhaka','subtotal_minor'=>$value,'delivery_minor'=>0,'total_minor'=>$value]);
        Livewire::test(\App\Filament\Widgets\CommerceOverview::class,['pageFilters'=>['start'=>'2026-10-01','end'=>'2026-10-31']])->assertSee('৳1,000')->assertDontSee('৳6,000');
    }
}
