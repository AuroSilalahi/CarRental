<?php

namespace App\Filament\Widgets;

use App\Enums\IdentityDocumentStatus;
use App\Models\IdentityDocument;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PendingVerificationsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $pendingCount = IdentityDocument::where('status', IdentityDocumentStatus::PendingReview)->count();

        return [
            Stat::make('Antrean Verifikasi KTP', $pendingCount . ' Dokumen')
                ->description('Memerlukan peninjauan admin')
                ->descriptionIcon('heroicon-m-identification')
                ->color('warning'),
        ];
    }
}
