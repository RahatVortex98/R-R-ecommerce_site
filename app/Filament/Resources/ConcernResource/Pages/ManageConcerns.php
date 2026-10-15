<?php
namespace App\Filament\Resources\ConcernResource\Pages;
use Filament\Resources\Pages\ManageRecords;
use Filament\Actions\CreateAction;
class ManageConcerns extends ManageRecords {
 protected static string $resource=\App\Filament\Resources\ConcernResource::class;
 protected function getHeaderActions(): array{return [CreateAction::make()];}
}
