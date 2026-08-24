<?php

namespace App\Filament\Resources;

use App\Enums\PaymentStatus;
use App\Exceptions\PaymentAlreadyPaidException;
use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Services\PaymentService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Manajemen Pembayaran';

    protected static ?string $modelLabel = 'Pembayaran';

    protected static ?string $pluralModelLabel = 'Pembayaran';

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('Tidak ada pembayaran yang ditemukan')
            ->columns([
                Tables\Columns\TextColumn::make('rental.reference_number')
                    ->label('No. Referensi Rental')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('rental.customer.name')
                    ->label('Pelanggan')
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount_idr')
                    ->label('Jumlah IDR')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float) $state, 0, ',', '.'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Tanggal Bayar')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Metode Bayar')
                    ->badge(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (PaymentStatus|string $state): string => match ($state instanceof PaymentStatus ? $state->value : $state) {
                        'paid' => 'success',
                        'unpaid', 'pending' => 'warning',
                        'expired', 'failed' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Pembayaran')
                    ->options([
                        'paid' => 'Paid (Lunas)',
                        'unpaid' => 'Unpaid',
                        'pending' => 'Pending',
                        'expired' => 'Expired',
                        'failed' => 'Failed',
                    ]),
            ])
            ->actions([
                Action::make('recordManualPayment')
                    ->label('Catat Pembayaran Manual')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Payment $record) => ($record->status->value ?? $record->status) !== 'paid')
                    ->action(function (Payment $record) {
                        /** @var PaymentService $paymentService */
                        $paymentService = app(PaymentService::class);

                        try {
                            $paymentService->recordPayment($record->rental, [
                                'payment_method' => 'manual',
                            ]);

                            Notification::make()
                                ->title('Pembayaran Berhasil Dicatat')
                                ->body('Status pembayaran dan rental telah berhasil diperbarui ke Lunas & Dikonfirmasi.')
                                ->success()
                                ->send();
                        } catch (PaymentAlreadyPaidException $e) {
                            Notification::make()
                                ->title('Gagal')
                                ->body('Pembayaran ini sudah lunas sebelumnya.')
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
        ];
    }
}
