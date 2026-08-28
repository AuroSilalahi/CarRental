<?php

namespace App\Models;

use App\Enums\IdentityDocumentFileType;
use App\Enums\IdentityDocumentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdentityDocument extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'file_path',
        'file_type',
        'status',
        'rejection_reason',
        'reviewed_at',
        'reviewed_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_type'   => IdentityDocumentFileType::class,
            'status'      => IdentityDocumentStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * Get secure temporary signed URL (or local URL) for identity document.
     */
    public function getFileUrlAttribute(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        $disk = config('filesystems.default', 'local');

        if ($disk === 's3') {
            try {
                if (\Illuminate\Support\Facades\Storage::disk('s3')->exists($this->file_path)) {
                    return \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl($this->file_path, now()->addMinutes(15));
                }
            } catch (\Throwable $e) {
                // Fallback to local URL if S3 is unavailable or file stored locally
            }
        }

        return asset('storage/' . $this->file_path);
    }

    /**
     * Get the customer (user) that owns this identity document.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
