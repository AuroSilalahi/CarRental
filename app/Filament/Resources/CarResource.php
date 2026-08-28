<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CarResource\Pages;
use App\Models\Car;
use App\Models\Rental;
use App\Enums\RentalStatus;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components;

use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CarResource extends Resource
{
    protected static ?string $model = Car::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Manajemen Kendaraan';

    protected static ?string $modelLabel = 'Kendaraan';

    protected static ?string $pluralModelLabel = 'Kendaraan';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Components\TextInput::make('brand')
                    ->label('Merek (Brand)')
                    ->required()
                    ->maxLength(255),

                Components\TextInput::make('model')
                    ->label('Model')
                    ->required()
                    ->maxLength(255),

                Components\Select::make('type')
                    ->label('Tipe Kendaraan')
                    ->options([
                        'City Car' => 'City Car',
                        'Sedan' => 'Sedan',
                        'SUV' => 'SUV',
                        'MPV' => 'MPV',
                        'Minivan' => 'Minivan',
                    ])
                    ->required(),

                Components\TextInput::make('license_plate')
                    ->label('Plat Nomor')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(20),

                Components\TextInput::make('passenger_capacity')
                    ->label('Kapasitas Penumpang')
                    ->numeric()
                    ->required()
                    ->minValue(1),

                Components\TextInput::make('colour')
                    ->label('Warna')
                    ->required()
                    ->maxLength(50),

                Components\TextInput::make('year')
                    ->label('Tahun Pembuatan')
                    ->numeric()
                    ->required()
                    ->minValue(1900)
                    ->maxValue(date('Y') + 1),

                Components\TextInput::make('daily_rate_idr')
                    ->label('Tarif Harian (IDR)')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->prefix('Rp'),

                Components\Toggle::make('is_available')
                    ->label('Status Tersedia')
                    ->default(true),

                Components\Toggle::make('is_luxury_brand')
                    ->label('Brand Mewah (Luxury)')
                    ->live(),

                Components\TextInput::make('luxury_multiplier')
                    ->label('Luxury Multiplier')
                    ->numeric()
                    ->step(0.1)
                    ->minValue(1.0)
                    ->maxValue(3.0)
                    ->default(1.0)
                    ->visible(fn ($get) => (bool) $get('is_luxury_brand')),

                Components\FileUpload::make('image_path')
                    ->label('Foto Kendaraan')
                    ->image()
                    ->disk(config('filesystems.default', 'public'))
                    ->directory('cars')
                    ->visibility('public')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('brand')
                    ->label('Merek')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('model')
                    ->label('Model')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge(),

                Tables\Columns\TextColumn::make('license_plate')
                    ->label('Plat Nomor')
                    ->searchable(),

                Tables\Columns\TextColumn::make('passenger_capacity')
                    ->label('Kapasitas')
                    ->suffix(' orang'),

                Tables\Columns\TextColumn::make('daily_rate_idr')
                    ->label('Tarif Harian')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float) $state, 0, ',', '.'))
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_available')
                    ->label('Tersedia')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_luxury_brand')
                    ->label('Luxury')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe Kendaraan')
                    ->options([
                        'City Car' => 'City Car',
                        'Sedan' => 'Sedan',
                        'SUV' => 'SUV',
                        'MPV' => 'MPV',
                        'Minivan' => 'Minivan',
                    ]),

                Tables\Filters\TernaryFilter::make('is_available')
                    ->label('Status Availability'),
            ])
            ->actions([
                EditAction::make()->label('Edit'),
                DeleteAction::make()
                    ->label('Hapus')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Kendaraan')
                    ->modalDescription('Apakah Anda yakin ingin menghapus kendaraan ini?')
                    ->action(function (Car $record) {
                        $hasActiveRentals = Rental::where('car_id', $record->id)
                            ->whereIn('status', [RentalStatus::Pending, RentalStatus::Confirmed, RentalStatus::Active])
                            ->where('end_date', '>=', now())
                            ->exists();

                        if ($hasActiveRentals) {
                            Notification::make()
                                ->title('Gagal Menghapus')
                                ->body('Mobil ini tidak dapat dihapus karena memiliki penyewaan yang aktif atau akan datang.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $record->delete();

                        Notification::make()
                            ->title('Berhasil Hapus')
                            ->body('Kendaraan berhasil dihapus.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCars::route('/'),
            'create' => Pages\CreateCar::route('/create'),
            'edit' => Pages\EditCar::route('/{record}/edit'),
        ];
    }
}
