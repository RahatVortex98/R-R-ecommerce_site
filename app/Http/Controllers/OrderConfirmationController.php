<?php
namespace App\Http\Controllers;
use App\Models\Order;
class OrderConfirmationController {
    public function __invoke(string $reference){$order=Order::where('public_reference',$reference)->firstOrFail();return response()->view('orders.confirmation',['order'=>$order])->header('Cache-Control','private, no-store');}
}
