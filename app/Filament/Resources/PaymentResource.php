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

                Tables\Columns\TextColumn::make('transaction_reference')
                    ->label('No. Referensi')
                    ->placeholder('-')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (PaymentStatus|string $state): string => match ($state instanceof PaymentStatus ? $state->value : $state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'unpaid' => 'gray',
                        'expired', 'failed' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Pembayaran')
                    ->options([
                        'paid' => 'Paid (Lunas)',
                        'pending' => 'Pending (Perlu Verifikasi)',
                        'unpaid' => 'Unpaid',
                        'expired' => 'Expired',
                        'failed' => 'Failed',
                    ]),
            ])
            ->actions([
                Action::make('viewProof')
                    ->label('Lihat Bukti S3')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->color('info')
                    ->visible(fn (Payment $record) => !empty($record->proof_path))
                    ->url(fn (Payment $record) => $record->proof_url)
                    ->openUrlInNewTab(),

                Action::make('approvePayment')
                    ->label('Setujui Pembayaran')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Payment $record) => ($record->status->value ?? $record->status) !== 'paid')
                    ->requiresConfirmation()
                    ->action(function (Payment $record) {
                        /** @var PaymentService $paymentService */
                        $paymentService = app(PaymentService::class);

                        try {
                            $paymentService->recordPayment($record->rental, [
                                'payment_method' => $record->payment_method ?? 'manual',
                            ]);

                            \App\Jobs\SendPaymentConfirmationEmail::dispatch($record->rental->fresh(['customer', 'car', 'payment']));

                            Notification::make()
                                ->title('Pembayaran Disetujui & Dikonfirmasi')
                                ->body('Email konfirmasi otomatis telah dikirim ke pelanggan via Resend.')
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

                Action::make('rejectPayment')
                    ->label('Tolak Bukti')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Payment $record) => ($record->status->value ?? $record->status) === 'pending')
                    ->requiresConfirmation()
                    ->action(function (Payment $record) {
                        $record->update(['status' => PaymentStatus::Failed]);

                        Notification::make()
                            ->title('Bukti Pembayaran Ditolak')
                            ->body('Status pembayaran diperbarui ke Failed.')
                            ->warning()
                            ->send();
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
