<?php
namespace App\Observers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
class CatalogObserver implements ShouldHandleEventsAfterCommit {
    public function saved(Model $model): void{Cache::forget('home-catalog');}
    public function deleted(Model $model): void{Cache::forget('home-catalog');}
}
