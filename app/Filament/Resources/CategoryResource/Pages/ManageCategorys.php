<?php
namespace App\Filament\Resources\CategoryResource\Pages;
use Filament\Resources\Pages\ManageRecords;
use Filament\Actions\CreateAction;
class ManageCategorys extends ManageRecords {
 protected static string $resource=\App\Filament\Resources\CategoryResource::class;
 protected function getHeaderActions(): array{return [CreateAction::make()];}
}
