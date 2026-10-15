<?php
namespace App\Filament\Resources\UserResource\Pages;
use Filament\Resources\Pages\ManageRecords;
use Filament\Actions\CreateAction;
class ManageUsers extends ManageRecords {
 protected static string $resource=\App\Filament\Resources\UserResource::class;
 protected function getHeaderActions(): array{return [CreateAction::make()];}
}
