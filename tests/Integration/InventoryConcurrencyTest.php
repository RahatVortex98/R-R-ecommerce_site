<?php
namespace Tests\Integration;

use App\Models\{Product,DeliveryZone,Order};
use Illuminate\Support\Facades\{Artisan,DB};
use Symfony\Component\Process\Process;
use Tests\TestCase;

/** Opt-in only: creates/drops tables in a disposable database named rr_care_test_*. */
class InventoryConcurrencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $database=getenv('MYSQL_TEST_DATABASE');
        if(!$database)$this->markTestSkipped('Set MYSQL_TEST_DATABASE to a disposable rr_care_test_* MySQL database.');
        if(!str_starts_with($database,'rr_care_test_'))$this->fail('Refusing to reset a database without the rr_care_test_ prefix.');
        config(['database.default'=>'mysql','database.connections.mysql.host'=>getenv('MYSQL_TEST_HOST')?:'127.0.0.1','database.connections.mysql.port'=>getenv('MYSQL_TEST_PORT')?:3306,'database.connections.mysql.database'=>$database,'database.connections.mysql.username'=>getenv('MYSQL_TEST_USERNAME')?:'root','database.connections.mysql.password'=>getenv('MYSQL_TEST_PASSWORD')?:'']);
        DB::purge('mysql');Artisan::call('migrate:fresh',['--force'=>true]);
    }
    private function race(bool $sameToken): void
    {
        $sku=Product::create(['name'=>'Last unit','slug'=>'last-unit','published'=>true])->skus()->create(['code'=>'LAST-1','regular_minor'=>100000,'on_hand'=>1]);
        $zone=DeliveryZone::create(['name'=>'Dhaka','active'=>true,'fee_minor'=>6000]);
        $start=microtime(true)+2;$processes=[];$token=(string)\Str::uuid();
        foreach([0,1] as $index){
            $process=new Process([PHP_BINARY,base_path('tests/Support/checkout-probe.php'),(string)$sku->id,(string)$zone->id,$sameToken?$token:(string)\Str::uuid(),(string)$start],base_path(),['APP_ENV'=>'testing']);
            $process->setTimeout(30);$process->start();$processes[]=$process;
        }
        foreach($processes as $process)$process->wait();
        $codes=array_map(fn($p)=>$p->getExitCode(),$processes);sort($codes);
        $this->assertSame($sameToken?[0,0]:[0,2],$codes,implode("\n",array_map(fn($p)=>$p->getErrorOutput(),$processes)));
        $this->assertSame(1,Order::count());$this->assertSame(1,$sku->fresh()->reserved);$this->assertSame(1,$sku->fresh()->on_hand);
    }
    public function test_competing_customers_cannot_oversell_the_last_unit(): void{$this->race(false);}
    public function test_duplicate_simultaneous_submissions_reserve_only_once(): void{$this->race(true);}
}
