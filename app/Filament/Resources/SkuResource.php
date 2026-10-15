<?php
namespace App\Filament\Resources;
use App\Models\Sku;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\{TextInput,Textarea};
use Filament\Actions\Action;
class SkuResource extends Resource {
    protected static ?string $model=Sku::class;
    protected static ?string $navigationLabel='Inventory';
    protected static string|\UnitEnum|null $navigationGroup='Catalog';
    public static function table(Table $table): Table{return $table->columns([TextColumn::make('code')->searchable(),TextColumn::make('product.name')->searchable(),TextColumn::make('label'),TextColumn::make('on_hand')->sortable(),TextColumn::make('reserved')->sortable(),TextColumn::make('available')->state(fn(Sku $record)=>$record->availableQuantity())])->recordActions([
        Action::make('adjust')->label('Adjust stock')->schema([TextInput::make('delta')->label('Quantity change (+ received / − removed)')->integer()->required(),Textarea::make('reason')->required()->minLength(3)->maxLength(190)])->requiresConfirmation()->action(fn(Sku $record,array $data)=>app(\App\Services\InventoryService::class)->adjust($record,(int)$data['delta'],$data['reason'],auth()->user())),
    ]);}
    public static function canCreate(): bool{return false;}
    public static function getPages(): array{return ['index'=>\App\Filament\Resources\SkuResource\Pages\ManageSkus::route('/')];}
}
