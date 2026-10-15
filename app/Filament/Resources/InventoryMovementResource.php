<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
class InventoryMovementResource extends Resource {
    protected static ?string $model=\App\Models\InventoryMovement::class;
    protected static string|\UnitEnum|null $navigationGroup='Catalog';
    protected static ?string $navigationLabel='Stock history';
    public static function canCreate(): bool{return false;}
    public static function table(Table $table): Table{return $table->defaultSort('id','desc')->columns([TextColumn::make('sku.code')->searchable(),TextColumn::make('on_hand_delta'),TextColumn::make('reserved_delta'),TextColumn::make('reason'),TextColumn::make('order.public_reference')->limit(12),TextColumn::make('user.name'),TextColumn::make('created_at')->dateTime()]);}
    public static function getPages(): array{return ['index'=>\App\Filament\Resources\InventoryMovementResource\Pages\ManageMovements::route('/')];}
}
