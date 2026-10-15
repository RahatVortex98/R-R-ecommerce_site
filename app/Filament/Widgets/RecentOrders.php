<?php
namespace App\Filament\Widgets;
use Filament\Widgets\TableWidget;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
class RecentOrders extends TableWidget {
    use FiltersOrderDates;
    protected int|string|array $columnSpan='full';
    public function table(Table $table): Table{return $table->query($this->dateRange(\App\Models\Order::query())->latest())->heading('Recent COD orders')->columns([TextColumn::make('public_reference')->limit(13),TextColumn::make('name'),TextColumn::make('status')->formatStateUsing(fn($state)=>$state->value)->badge(),TextColumn::make('total_minor')->label('COD total')->formatStateUsing(fn($state)=>\App\Services\PricingService::money((int)$state)),TextColumn::make('created_at')->dateTime()])->paginated([5,10])->recordUrl(fn($record)=>\App\Filament\Resources\OrderResource::getUrl());}
}
