<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\{Home,Catalog,ProductDetail,Cart,Checkout,BlogIndex,ArticleDetail};
use App\Http\Controllers\{OrderConfirmationController,SitemapController,RobotsController};

Route::get('/',Home::class)->name('home');
Route::get('/shop',Catalog::class)->name('shop');
Route::get('/categories/{slug}',Catalog::class)->name('category');
Route::get('/products/{slug}',ProductDetail::class)->name('product');
Route::get('/cart',Cart::class)->name('cart');
Route::get('/checkout',Checkout::class)->name('checkout');
Route::get('/order/{reference}',OrderConfirmationController::class)->name('order.confirmation')->middleware('throttle:30,1');
Route::get('/journal',BlogIndex::class)->name('journal');
Route::get('/journal/{slug}',ArticleDetail::class)->name('article');
Route::get('/sitemap.xml',SitemapController::class);
Route::get('/robots.txt',RobotsController::class);
Route::get('/media/{path}',\App\Http\Controllers\PublicMediaController::class)->where('path','.*')->name('media');
Route::get('/storage/{path}',\App\Http\Controllers\PublicMediaController::class)->where('path','.*');
Route::get('/admin/orders/{order}/print',function(\App\Models\Order $order){\Gate::authorize('view',$order);return view('orders.print',compact('order'));})->middleware('auth')->name('orders.print');
