<?php

namespace App\Filament\Resources\Facultades\Pages;

use App\Filament\Resources\Facultades\FacultadResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFacultades extends ManageRecords
{
    protected static string $resource = FacultadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
