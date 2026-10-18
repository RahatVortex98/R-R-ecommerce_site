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

class ArticleResource extends Resource {
 protected static ?string $model=\App\Models\Article::class;
 protected static string|\UnitEnum|null $navigationGroup='Journal';
 public static function form(Schema $schema): Schema{return $schema->components([TextInput::make('title')->required()->maxLength(190),TextInput::make('slug')->required()->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')->unique(ignoreRecord:true),Textarea::make('excerpt')->maxLength(300)->columnSpanFull(),RichEditor::make('body')->required()->toolbarButtons(['bold','italic','h2','h3','bulletList','orderedList','link','blockquote','undo','redo'])->columnSpanFull(),Select::make('status')->options(['draft'=>'Draft','published'=>'Published'])->required()->default('draft'),DateTimePicker::make('published_at')->timezone('Asia/Dhaka'),Select::make('user_id')->relationship('author','name')->label('Author')->searchable()->preload(),Select::make('category_id')->relationship('category','name')->preload(),Select::make('products')->relationship('products','name')->multiple()->searchable()->preload()->columnSpanFull()->helperText('Mention product links in the article and select them here to show related cards.'),Select::make('concerns')->relationship('concerns','name')->multiple()->preload(),TagsInput::make('tags'),FileUpload::make('cover_path')->label('Journal cover image')->helperText('Appears on journal cards and at the top of this article.')->disk('public')->directory('journal')->image()->acceptedFileTypes(['image/jpeg','image/png','image/webp'])->maxSize(4096),TextInput::make('cover_alt')->maxLength(190),TextInput::make('meta_title')->maxLength(70),Textarea::make('meta_description')->maxLength(170),Toggle::make('sample')->label('Sample article')])->columns(2);}
 public static function table(Table $table): Table{return $table->columns([TextColumn::make('title')->searchable()->sortable(),TextColumn::make('status')->badge(),TextColumn::make('author.name'),TextColumn::make('published_at')->dateTime()->sortable()])->recordActions([EditAction::make(),DeleteAction::make()]);}
 public static function getPages(): array{return ['index'=>\App\Filament\Resources\ArticleResource\Pages\ManageArticles::route('/')];}
}
