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

class UserResource extends Resource {
 protected static ?string $model=\App\Models\User::class;
 protected static string|\UnitEnum|null $navigationGroup='Administration';
 public static function form(Schema $schema): Schema{return $schema->components([TextInput::make('name')->required()->maxLength(120),TextInput::make('email')->email()->required()->unique(ignoreRecord:true),TextInput::make('password')->password()->minLength(12)->required(fn(string $operation)=>$operation==='create')->dehydrated(fn($state)=>filled($state)),Select::make('role')->options(['owner'=>'Owner','staff'=>'Staff'])->required()])->columns(2);}
 public static function table(Table $table): Table{return $table->columns([TextColumn::make('name')->searchable(),TextColumn::make('email')->searchable(),TextColumn::make('role')->badge()])->recordActions([EditAction::make(),DeleteAction::make()]);}
 public static function getPages(): array{return ['index'=>\App\Filament\Resources\UserResource\Pages\ManageUsers::route('/')];}
}
