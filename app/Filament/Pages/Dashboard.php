<?php
namespace App\Filament\Pages;
use Filament\Schemas\Schema;
use Filament\Forms\Components\DatePicker;
class Dashboard extends \Filament\Pages\Dashboard {
    use \Filament\Pages\Dashboard\Concerns\HasFiltersForm;
    public function filtersForm(Schema $schema): Schema{return $schema->components([DatePicker::make('start')->label('From date (Dhaka)')->beforeOrEqual('end'),DatePicker::make('end')->label('To date (Dhaka)')->afterOrEqual('start')]);}
}
