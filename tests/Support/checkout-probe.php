<?php
// Internal integration-test process; no HTTP route exposes this script.
require __DIR__.'/../../vendor/autoload.php';
$app=require __DIR__.'/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if(getenv('APP_ENV')!=='testing'||!str_starts_with(getenv('MYSQL_TEST_DATABASE')?:'','rr_care_test_'))exit(9);
config(['database.default'=>'mysql','database.connections.mysql.host'=>getenv('MYSQL_TEST_HOST')?:'127.0.0.1','database.connections.mysql.port'=>getenv('MYSQL_TEST_PORT')?:3306,'database.connections.mysql.database'=>getenv('MYSQL_TEST_DATABASE'),'database.connections.mysql.username'=>getenv('MYSQL_TEST_USERNAME')?:'root','database.connections.mysql.password'=>getenv('MYSQL_TEST_PASSWORD')?:'','queue.default'=>'sync','mail.default'=>'array']);
\Illuminate\Support\Facades\DB::purge('mysql');
while(microtime(true)<(float)$argv[4])usleep(10000);
try{app(\App\Services\CheckoutService::class)->submit(['name'=>'Concurrency test','phone'=>'01700000000','address'=>'Disposable test address, Dhaka','delivery_zone_id'=>(int)$argv[2]],[(int)$argv[1]=>1],$argv[3]);exit(0);}
catch(\Illuminate\Validation\ValidationException){exit(2);}
catch(\Throwable $e){fwrite(STDERR,$e::class.': '.$e->getMessage());exit(3);}
