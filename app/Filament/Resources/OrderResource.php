<?php
namespace App\Filament\Resources;
use App\Enums\OrderStatus;
use App\Models\{Order,OrderItem};
use App\Services\{OrderLifecycleService,PricingService};
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\{TextInput,Textarea,Select,Repeater};
use Filament\Actions\{Action,EditAction};
class OrderResource extends Resource {
    protected static ?string $model=Order::class;
    protected static string|\UnitEnum|null $navigationGroup='Orders';
    public static function form(Schema $schema): Schema{return $schema->components([
        TextInput::make('public_reference')->disabled(),TextInput::make('status')->formatStateUsing(fn($state)=>$state instanceof OrderStatus?$state->value:$state)->disabled(),TextInput::make('name')->disabled(),TextInput::make('phone')->disabled(),Textarea::make('address')->disabled(),TextInput::make('email')->disabled(),TextInput::make('delivery_zone_name')->disabled(),TextInput::make('total_minor')->label('COD total')->formatStateUsing(fn($state)=>PricingService::money((int)$state))->disabled(),Textarea::make('note')->disabled(),Textarea::make('staff_note')->maxLength(5000),Repeater::make('items')->relationship()->schema([TextInput::make('product_name'),TextInput::make('sku_code'),TextInput::make('quantity'),TextInput::make('unit_minor')->label('Unit price (paisa)'),TextInput::make('restocked')])->disabled()->dehydrated(false)->columnSpanFull(),
    ]);}
    public static function table(Table $table): Table{return $table->defaultSort('created_at','desc')->columns([
        TextColumn::make('public_reference')->label('Reference')->limit(13)->searchable()->copyable(),TextColumn::make('name')->searchable(),TextColumn::make('phone')->searchable(),TextColumn::make('status')->badge()->formatStateUsing(fn($state)=>$state instanceof OrderStatus?$state->value:$state),TextColumn::make('total_minor')->label('COD total')->formatStateUsing(fn($state)=>PricingService::money((int)$state)),TextColumn::make('created_at')->dateTime()->sortable(),TextColumn::make('expires_at')->dateTime(),
    ])->filters([SelectFilter::make('status')->options(collect(OrderStatus::cases())->mapWithKeys(fn($s)=>[$s->value=>ucfirst($s->value)])->all())])->recordActions([
        EditAction::make()->label('Details / notes'),
        Action::make('change_status')->label('Update status')->schema([Select::make('status')->options(fn(Order $record)=>collect($record->status->allowed())->reject(fn($s)=>$s===OrderStatus::Expired)->mapWithKeys(fn($s)=>[$s->value=>ucfirst($s->value)])->all())->required()])->visible(fn(Order $record)=>count($record->status->allowed())>0)->requiresConfirmation()->action(fn(Order $record,array $data)=>app(OrderLifecycleService::class)->transition($record,OrderStatus::from($data['status']),auth()->user())),
        Action::make('restock_return')->label('Restock inspected return')->visible(fn(Order $record)=>$record->status===OrderStatus::Returned)->schema([\Filament\Forms\Components\Hidden::make('request_token')->default(fn()=>(string)\Str::uuid())->required(),Select::make('item_id')->label('Returned item')->options(fn(Order $record)=>$record->items->mapWithKeys(fn($i)=>[$i->id=>$i->product_name.' (remaining '.($i->quantity-$i->restocked).')'])->all())->required(),TextInput::make('quantity')->integer()->minValue(1)->required()])->requiresConfirmation()->action(function(Order $record,array $data){$item=$record->items()->findOrFail($data['item_id']);app(OrderLifecycleService::class)->restockReturn($item,(int)$data['quantity'],auth()->user(),$data['request_token']);}),
        Action::make('print')->url(fn(Order $record)=>route('orders.print',$record))->openUrlInNewTab(),
    ]);}
    public static function getPages(): array{return ['index'=>\App\Filament\Resources\OrderResource\Pages\ManageOrders::route('/')];}
}

