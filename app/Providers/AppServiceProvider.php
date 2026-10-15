<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Application services are resolved through the container.
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach([\App\Models\Product::class,\App\Models\Category::class,\App\Models\Brand::class,\App\Models\Concern::class,\App\Models\Article::class,\App\Models\DeliveryZone::class,\App\Models\Sku::class,\App\Models\InventoryMovement::class] as $model)\Illuminate\Support\Facades\Gate::policy($model,\App\Policies\CatalogPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Order::class,\App\Policies\OrderPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\User::class,\App\Policies\UserPolicy::class);
    }
}
