<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueWidget extends BaseWidget
{
    public ?string $startDate = null;
    public ?string $endDate = null;

    protected function getStats(): array
    {
        $query = Payment::where('status', PaymentStatus::Paid);

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('paid_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        } else {
            // Default to current calendar month
            $query->whereMonth('paid_at', Carbon::now()->month)
                  ->whereYear('paid_at', Carbon::now()->year);
        }

        $totalRevenue = (int) $query->sum('amount_idr');

        return [
            Stat::make('Total Pendapatan (Paid)', 'Rp ' . number_format($totalRevenue, 0, ',', '.'))
                ->description('Total pendapatan dari transaksi lunas')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];
    }
}
