<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\{FileUpload, TextInput, Textarea};
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StorefrontContent extends StoreSettings
{
    protected static ?string $slug = 'storefront-content';
    protected static ?string $navigationLabel = 'Storefront uploads';
    protected static string|\UnitEnum|null $navigationGroup = 'Store';

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, ['owner', 'staff'], true);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Store logo')->description('Appears in the storefront header and footer.')->schema([
                $this->upload('logo', 'Store logo'),
            ]),
            Section::make('Homepage banner')->description('Appears at the top of the homepage beside the headline.')->schema([
                $this->upload('hero_image', 'Homepage banner image'),
                TextInput::make('hero_title')->required()->maxLength(120),
                Textarea::make('hero_text')->required()->maxLength(500),
            ]),
            Section::make('Homepage product sections')->description('Published products with available stock appear newest first. Active sale prices also place products in Discount products. Add stock under Catalog → Inventory.')->schema([
                TextInput::make('available_heading')->required()->maxLength(80),
                TextInput::make('discount_heading')->required()->maxLength(80),
                TextInput::make('section_count')->label('Products per section')->integer()->required()->minValue(1)->maxValue(12),
            ]),
            Section::make('Product gallery and journal covers')->description('Catalog → Products → Edit → Product gallery image controls shop cards and product detail photos. Journal → Articles → Edit → Journal cover image controls journal cards and article covers.')->schema([]),
        ])->statePath('data');
    }

    private function upload(string $name, string $label): FileUpload
    {
        return FileUpload::make($name)->label($label)->disk('public')->directory('branding')->image()
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(4096)->visibility('public');
    }
}
