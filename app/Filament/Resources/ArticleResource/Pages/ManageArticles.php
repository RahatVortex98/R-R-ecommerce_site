<?php
namespace App\Filament\Resources\ArticleResource\Pages;
use Filament\Resources\Pages\ManageRecords;
use Filament\Actions\CreateAction;
class ManageArticles extends ManageRecords {
 protected static string $resource=\App\Filament\Resources\ArticleResource::class;
 protected function getHeaderActions(): array{return [CreateAction::make()];}
}
