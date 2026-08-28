<?php

namespace App\Filament\Resources;

use App\Enums\AccountStatus;
use App\Enums\IdentityDocumentStatus;
use App\Filament\Resources\CustomerResource\Pages;
use App\Mail\IdentityApprovedMail;
use App\Mail\IdentityRejectedMail;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CustomerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Manajemen Pelanggan';

    protected static ?string $modelLabel = 'Pelanggan';

    protected static ?string $pluralModelLabel = 'Pelanggan';

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('Tidak ada pelanggan yang ditemukan')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Pelanggan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telepon')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Registrasi')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('identity_status')
                    ->label('Status Identitas KTP')
                    ->state(function (User $record) {
                        $doc = $record->identityDocuments()->latest()->first();
                        if (! $doc) return 'Belum Upload';
                        return match ($doc->status->value ?? $doc->status) {
                            'pending_review' => 'Pending Review',
                            'verified' => 'Terverifikasi',
                            'rejected' => 'Ditolak',
                            default => 'Belum Upload',
                        };
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Terverifikasi' => 'success',
                        'Pending Review' => 'warning',
                        'Ditolak' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('account_status')
                    ->label('Status Akun')
                    ->badge()
                    ->color(fn (AccountStatus|string $state): string => match ($state instanceof AccountStatus ? $state->value : $state) {
                        'active' => 'success',
                        'deactivated' => 'danger',
                        'locked' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Action::make('viewDocument')
                    ->label('Lihat KTP S3')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->color('info')
                    ->visible(function (User $record) {
                        $doc = $record->identityDocuments()->latest()->first();
                        return $doc && !empty($doc->file_path);
                    })
                    ->url(function (User $record) {
                        $doc = $record->identityDocuments()->latest()->first();
                        return $doc ? $doc->file_url : null;
                    })
                    ->openUrlInNewTab(),

                Action::make('viewCustomerDetails')
                    ->label('Detail Pelanggan')
                    ->icon('heroicon-o-user-circle')
                    ->color('gray')
                    ->modalHeading(fn (User $record) => 'Detail Pelanggan — ' . $record->name)
                    ->modalContent(function (User $record) {
                        $doc = $record->identityDocuments()->latest()->first();
                        $docUrl = $doc ? $doc->file_url : null;

                        return view('filament.components.customer-detail-modal', [
                            'customer' => $record,
                            'document' => $doc,
                            'documentUrl' => $docUrl,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),

                Action::make('approveIdentity')
                    ->label('Setujui KTP')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(function (User $record) {
                        $doc = $record->identityDocuments()->latest()->first();
                        return $doc && ($doc->status->value ?? $doc->status) === 'pending_review';
                    })
                    ->action(function (User $record) {
                        DB::transaction(function () use ($record) {
                            $doc = $record->identityDocuments()->latest()->first();
                            if ($doc) {
                                $doc->update([
                                    'status' => IdentityDocumentStatus::Verified,
                                    'reviewed_at' => now(),
                                    'reviewed_by' => auth()->id(),
                                ]);
                            }

                            DB::afterCommit(function () use ($record) {
                                try {
                                    Mail::to($record->email)->send(new IdentityApprovedMail($record));
                                } catch (\Throwable $e) {
                                    logger()->warning('Resend email delivery skipped for test recipient: ' . $e->getMessage());
                                }
                            });
                        });

                        Notification::make()
                            ->title('Identitas Disetujui')
                            ->body('Dokumen identitas pelanggan telah berhasil disetujui.')
                            ->success()
                            ->send();
                    }),

                Action::make('rejectIdentity')
                    ->label('Tolak KTP')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(function (User $record) {
                        $doc = $record->identityDocuments()->latest()->first();
                        return $doc && ($doc->status->value ?? $doc->status) === 'pending_review';
                    })
                    ->form([
                        Components\Textarea::make('rejection_reason')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->placeholder('Masukkan alasan penolakan dokumen KTP...')
                            ->rules(['required', 'string', 'min:3']),
                    ])
                    ->action(function (User $record, array $data) {
                        $reason = trim($data['rejection_reason'] ?? '');
                        if (empty($reason)) {
                            Notification::make()
                                ->title('Gagal')
                                ->body('Alasan penolakan tidak boleh kosong.')
                                ->danger()
                                ->send();
                            return;
                        }

                        DB::transaction(function () use ($record, $reason) {
                            $doc = $record->identityDocuments()->latest()->first();
                            if ($doc) {
                                $doc->update([
                                    'status' => IdentityDocumentStatus::Rejected,
                                    'rejection_reason' => $reason,
                                    'reviewed_at' => now(),
                                    'reviewed_by' => auth()->id(),
                                ]);
                            }

                            DB::afterCommit(function () use ($record, $reason) {
                                try {
                                    Mail::to($record->email)->send(new IdentityRejectedMail($record, $reason));
                                } catch (\Throwable $e) {
                                    logger()->warning('Resend email delivery skipped for test recipient: ' . $e->getMessage());
                                }
                            });
                        });

                        Notification::make()
                            ->title('Identitas Ditolak')
                            ->body('Dokumen KTP berhasil ditolak dengan alasan.')
                            ->warning()
                            ->send();
                    }),

                Action::make('toggleAccountStatus')
                    ->label(fn (User $record) => ($record->account_status->value ?? $record->account_status) === 'active' ? 'Nonaktifkan' : 'Aktifkan')
                    ->color(fn (User $record) => ($record->account_status->value ?? $record->account_status) === 'active' ? 'warning' : 'success')
                    ->action(function (User $record) {
                        $current = $record->account_status->value ?? $record->account_status;
                        $newStatus = $current === 'active' ? AccountStatus::Deactivated : AccountStatus::Active;
                        $record->update(['account_status' => $newStatus]);

                        Notification::make()
                            ->title('Status Akun Diperbarui')
                            ->body('Status akun pelanggan berhasil diubah.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
        ];
    }
}
