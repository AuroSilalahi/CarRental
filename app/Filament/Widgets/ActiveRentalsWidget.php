<?php

namespace App\Filament\Widgets;

use App\Enums\RentalStatus;
use App\Models\Rental;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ActiveRentalsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $activeCount = Rental::where('status', RentalStatus::Active)->count();

        return [
            Stat::make('Rental Aktif Saat Ini', $activeCount . ' Unit')
                ->description('Jumlah kendaraan sedang dirental')
                ->descriptionIcon('heroicon-m-truck')
                ->color('info'),
        ];
    }
}
