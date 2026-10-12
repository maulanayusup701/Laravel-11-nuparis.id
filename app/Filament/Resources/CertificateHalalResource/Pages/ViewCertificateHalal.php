<?php

namespace App\Filament\Resources\CertificateHalalResource\Pages;

use App\Filament\Resources\CertificateHalalResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCertificateHalal extends ViewRecord
{
    protected static string $resource = CertificateHalalResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
