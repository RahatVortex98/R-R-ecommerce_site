<?php
namespace App\Filament\Pages;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Forms\Components\{TextInput,Textarea,FileUpload,ColorPicker};
use Filament\Notifications\Notification;
class StoreSettings extends Page implements HasSchemas {
    use InteractsWithSchemas;
    protected static ?string $slug='store-settings';
    protected static ?string $navigationLabel='Store settings';
    protected static string|\UnitEnum|null $navigationGroup='Administration';
    protected string $view='filament.pages.store-settings';
    public ?array $data=[];
    public static function canAccess(): bool{return auth()->user()?->role==='owner';}
    public function mount(): void{$settings=app(\App\Services\StoreSettings::class);$values=[];foreach(\App\Services\StoreSettings::DEFAULTS as $key=>$default)$values[$key]=$settings->get($key);$values['hero_slides']=$settings->get('hero_slides',[]);$this->form->fill($values);}
    public function form(Schema $schema): Schema{return $schema->components([
        TextInput::make('store_name')->required()->maxLength(100),TextInput::make('contact_email')->email(),TextInput::make('contact_phone')->maxLength(30),TextInput::make('contact_address')->maxLength(200),ColorPicker::make('primary_color')->required()->regex('/^#[0-9a-fA-F]{6}$/'),ColorPicker::make('accent_color')->required()->regex('/^#[0-9a-fA-F]{6}$/'),
        FileUpload::make('logo')->disk('public')->directory('branding')->image()->acceptedFileTypes(['image/jpeg','image/png','image/webp'])->maxSize(4096),FileUpload::make('hero_image')->disk('public')->directory('branding')->image()->acceptedFileTypes(['image/jpeg','image/png','image/webp'])->maxSize(4096),
        TextInput::make('hero_title')->required()->maxLength(120),Textarea::make('hero_text')->required()->maxLength(500),TextInput::make('available_heading')->required()->maxLength(80),TextInput::make('discount_heading')->required()->maxLength(80),TextInput::make('section_count')->integer()->minValue(1)->maxValue(12)->required(),TextInput::make('pending_hours')->integer()->minValue(1)->maxValue(168)->required(),Textarea::make('default_description')->required()->maxLength(170),TextInput::make('notification_recipient')->email()->helperText('Your Gmail address can receive order notifications. Sending credentials stay in the environment.'),TextInput::make('facebook_url')->url()->startsWith('https://')->nullable(),TextInput::make('instagram_url')->url()->startsWith('https://')->nullable(),
    ])->statePath('data')->columns(2);}
    public function save(): void{abort_unless(static::canAccess(),403);$settings=app(\App\Services\StoreSettings::class);foreach($this->form->getState() as $key=>$value)$settings->set($key,$value);Notification::make()->title('Store settings saved')->success()->send();}
}
