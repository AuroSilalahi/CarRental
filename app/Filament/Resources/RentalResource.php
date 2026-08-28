<?php

namespace App\Filament\Resources;

use App\Enums\RentalStatus;
use App\Exceptions\CarNotAvailableException;
use App\Filament\Resources\RentalResource\Pages;
use App\Models\Rental;
use App\Services\RentalService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RentalResource extends Resource
{
    protected static ?string $model = Rental::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Manajemen Rental';

    protected static ?string $modelLabel = 'Pemesanan Rental';

    protected static ?string $pluralModelLabel = 'Pemesanan Rental';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('No. Referensi')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Pelanggan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('car.brand')
                    ->label('Kendaraan')
                    ->formatStateUsing(fn ($record) => $record->car->brand . ' ' . $record->car->model . ' (' . $record->car->license_plate . ')')
                    ->searchable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Selesai')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_cost_idr')
                    ->label('Total IDR')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float) $state, 0, ',', '.'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (RentalStatus|string $state): string => match ($state instanceof RentalStatus ? $state->value : $state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'active' => 'success',
                        'completed' => 'gray',
                        'cancelled', 'expired' => 'danger',
                        'review_required' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Rental')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'expired' => 'Expired',
                        'review_required' => 'Review Required',
                    ]),
            ])
            ->actions([
                Action::make('checkInAndPayAtOffice')
                    ->label('Bayar & Serahkan Kunci di Kantor')
                    ->icon('heroicon-o-key')
                    ->color('success')
                    ->visible(fn (Rental $record) => in_array($record->status->value ?? $record->status, ['pending', 'confirmed']))
                    ->form([
                        \Filament\Forms\Components\Select::make('payment_method')
                            ->label('Metode Pembayaran di Kantor')
                            ->options([
                                'Tunai / Cash di Kantor' => 'Tunai / Cash di Kantor',
                                'EDC Kartu Kredit / Debit' => 'EDC Kartu Kredit / Debit',
                                'Bank Transfer Jago / BCA' => 'Bank Transfer Jago / BCA',
                                'QRIS Standee Kantor' => 'QRIS Standee Kantor',
                            ])
                            ->default('Tunai / Cash di Kantor')
                            ->required(),
                    ])
                    ->action(function (Rental $record, array $data) {
                        /** @var RentalService $rentalService */
                        $rentalService = app(RentalService::class);
                        $rentalService->checkInAndPayAtOffice($record, $data);

                        Notification::make()
                            ->title('Serah Terima Kunci & Pembayaran Berhasil')
                            ->body('Status rental diperbarui ke AKTIF. Pelanggan telah menerima kendaraan.')
                            ->success()
                            ->send();
                    }),

                Action::make('completeRentalReturn')
                    ->label('Terima Pengembalian Mobil (Selesai)')
                    ->icon('heroicon-o-check-badge')
                    ->color('primary')
                    ->visible(fn (Rental $record) => in_array($record->status->value ?? $record->status, ['active', 'confirmed']))
                    ->requiresConfirmation()
                    ->modalHeading('Terima Pengembalian Kendaraan')
                    ->modalDescription('Apakah kendaraan telah dikembalikan dan diperiksa kondisinya oleh tim kantor?')
                    ->action(function (Rental $record) {
                        /** @var RentalService $rentalService */
                        $rentalService = app(RentalService::class);
                        $rentalService->completeRental($record);

                        Notification::make()
                            ->title('Rental Selesai')
                            ->body('Mobil telah dikembalikan. Availability kendaraan telah dipulihkan untuk sewa berikutnya.')
                            ->success()
                            ->send();
                    }),

                Action::make('cancelRental')
                    ->label('Batalkan')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Batalkan Pemesanan')
                    ->modalDescription('Apakah Anda yakin ingin membatalkan pemesanan rental ini?')
                    ->visible(fn (Rental $record) => in_array($record->status->value ?? $record->status, ['pending', 'confirmed']))
                    ->action(function (Rental $record) {
                        /** @var RentalService $rentalService */
                        $rentalService = app(RentalService::class);
                        $rentalService->cancelRental($record);

                        Notification::make()
                            ->title('Berhasil Membatalkan')
                            ->body('Pemesanan rental telah dibatalkan dan availability mobil dipulihkan.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRentals::route('/'),
        ];
    }
}
