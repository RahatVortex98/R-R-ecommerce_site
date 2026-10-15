<?php
namespace App\Filament\Resources\BrandResource\Pages;
use Filament\Resources\Pages\ManageRecords;
use Filament\Actions\CreateAction;
class ManageBrands extends ManageRecords {
 protected static string $resource=\App\Filament\Resources\BrandResource::class;
 protected function getHeaderActions(): array{return [CreateAction::make()];}
}
