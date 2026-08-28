<?php

namespace App\Models;

use App\Enums\AccountStatus;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail, FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Determine if the user can access the Filament Admin Panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return str_ends_with($this->email, '@carrental.com')
            || $this->email === 'admin@example.com'
            || str_starts_with($this->email, 'admin@');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'city',
        'province',
        'failed_login_attempts',
        'locked_until',
        'account_status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'locked_until'      => 'datetime',
            'account_status'    => AccountStatus::class,
        ];
    }

    /**
     * Get the identity documents for the user.
     */
    public function identityDocuments(): HasMany
    {
        return $this->hasMany(IdentityDocument::class, 'customer_id');
    }

    /**
     * Get the rentals for the user.
     */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class, 'customer_id');
    }
}
