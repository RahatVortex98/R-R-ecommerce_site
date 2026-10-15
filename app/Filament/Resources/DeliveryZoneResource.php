<?php
namespace App\Filament\Resources;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\{TextInput,Textarea,Select,Toggle,DateTimePicker,Repeater,FileUpload,RichEditor,TagsInput,ColorPicker};
use Filament\Tables\Columns\{TextColumn,IconColumn};
use Filament\Actions\{Action,CreateAction,EditAction,DeleteAction};
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Model;

class DeliveryZoneResource extends Resource {
 protected static ?string $model=\App\Models\DeliveryZone::class;
 protected static string|\UnitEnum|null $navigationGroup='Store';
 public static function form(Schema $schema): Schema{return $schema->components([TextInput::make('name')->required()->maxLength(120),TextInput::make('fee_minor')->label('Delivery fee (BDT)')->numeric()->required()->minValue(0)->formatStateUsing(fn($state)=>$state===null?0:$state/100)->dehydrateStateUsing(fn($state)=>(int)round($state*100)),Toggle::make('active')->default(true)])->columns(2);}
 public static function table(Table $table): Table{return $table->columns([TextColumn::make('name')->searchable(),TextColumn::make('fee_minor')->label('Delivery fee')->formatStateUsing(fn($state)=>\App\Services\PricingService::money((int)$state)),IconColumn::make('active')->boolean()])->recordActions([EditAction::make(),DeleteAction::make()]);}
 public static function getPages(): array{return ['index'=>\App\Filament\Resources\DeliveryZoneResource\Pages\ManageDeliveryZones::route('/')];}
}
