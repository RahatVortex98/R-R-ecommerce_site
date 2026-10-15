<?php
namespace App\Filament\Widgets;
use App\Models\{Order,Sku};
use App\Services\PricingService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
class CommerceOverview extends StatsOverviewWidget {
    use FiltersOrderDates;
    protected ?string $heading='Your store at a glance';
    protected ?string $description='Date filters use order creation date; delivered value uses delivery date. Stock is current. Delivered value is not a cash settlement report.';
    protected function getStats(): array{return [
        Stat::make('Pending COD orders',$this->dateRange(Order::where('status','pending'))->count())->description('Awaiting your confirmation'),Stat::make('Confirmed orders',$this->dateRange(Order::where('status','confirmed'))->count())->description('Ready to dispatch'),Stat::make('Delivered order value',PricingService::money((int)$this->dateRange(Order::where('status','delivered'),'delivered_at')->sum('total_minor')))->description('Includes delivery charges'),Stat::make('Pending / confirmed value',PricingService::money((int)$this->dateRange(Order::whereIn('status',['pending','confirmed']))->sum('total_minor')))->description('Uncollected COD orders'),Stat::make('Low available stock',Sku::whereRaw('on_hand - reserved <= 5')->count())->description('Current · 5 or fewer available'),Stat::make('Cancelled orders',$this->dateRange(Order::where('status','cancelled'))->count())->description('Reservations released'),
    ];}
}
