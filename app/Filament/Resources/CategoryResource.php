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

class CategoryResource extends Resource {
 protected static ?string $model=\App\Models\Category::class;
 protected static string|\UnitEnum|null $navigationGroup='Catalog';
 public static function form(Schema $schema): Schema{return $schema->components([TextInput::make('name')->required()->maxLength(120),TextInput::make('slug')->required()->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')->unique(ignoreRecord:true),Textarea::make('description'),TextInput::make('meta_title')->maxLength(70),Textarea::make('meta_description')->maxLength(170)])->columns(2);}
 public static function table(Table $table): Table{return $table->columns([TextColumn::make('name')->searchable()->sortable(),TextColumn::make('slug')])->recordActions([EditAction::make(),DeleteAction::make()]);}
 public static function getPages(): array{return ['index'=>\App\Filament\Resources\CategoryResource\Pages\ManageCategorys::route('/')];}
}
