<?php
namespace App\Filament\Resources\ProductResource\Pages;
use Filament\Resources\Pages\ManageRecords;
use Filament\Actions\CreateAction;
class ManageProducts extends ManageRecords {
 protected static string $resource=\App\Filament\Resources\ProductResource::class;
 protected function getHeaderActions(): array{return [CreateAction::make()];}
}
