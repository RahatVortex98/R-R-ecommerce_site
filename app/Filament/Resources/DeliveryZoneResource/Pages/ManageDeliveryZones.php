<?php
namespace App\Filament\Resources\DeliveryZoneResource\Pages;
use Filament\Resources\Pages\ManageRecords;
use Filament\Actions\CreateAction;
class ManageDeliveryZones extends ManageRecords {
 protected static string $resource=\App\Filament\Resources\DeliveryZoneResource::class;
 protected function getHeaderActions(): array{return [CreateAction::make()];}
}
