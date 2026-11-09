<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;

#[Fillable(['name', 'email', 'phone', 'password', 'is_admin', 'default_address'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'default_address' => 'array',
        ];
    }

    public function sendEmailVerificationNotification(): void
    {
        try {
            \App\Support\MailSettings::apply();
            $this->notify(new \Illuminate\Auth\Notifications\VerifyEmail);
        } catch (\Throwable $e) {
            Log::error('Doğrulama e-postası gönderilemedi', ['user' => $this->id, 'error' => $e->getMessage()]);
        }
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function isCustomer(): bool
    {
        return ! $this->isAdmin();
    }

    /** @param  Builder<self>  $query */
    public function scopeCustomers(Builder $query): Builder
    {
        return $query->where('is_admin', false);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
