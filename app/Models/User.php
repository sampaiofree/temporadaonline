<?php

namespace App\Models;

use App\Notifications\Auth\ResetPasswordNotification;
use App\Notifications\Auth\ResetPasswordCodeNotification;
use App\Notifications\Auth\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_code_hash',
        'password_reset_code_hash',
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
            'email_verification_code_expires_at' => 'datetime',
            'email_verification_code_sent_at' => 'datetime',
            'email_verification_code_attempts' => 'integer',
            'password_reset_code_expires_at' => 'datetime',
            'password_reset_code_sent_at' => 'datetime',
            'password_reset_code_verified_at' => 'datetime',
            'password_reset_code_attempts' => 'integer',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function ligas(): BelongsToMany
    {
        return $this->belongsToMany(Liga::class, 'liga_jogador', 'user_id', 'liga_id')->withTimestamps();
    }

    public function clubesLiga(): HasMany
    {
        return $this->hasMany(LigaClube::class, 'user_id');
    }

    public function disponibilidades(): HasMany
    {
        return $this->hasMany(UserDisponibilidade::class);
    }

    public function sendEmailVerificationNotification(): void
    {
        $code = $this->generateEmailVerificationCode();

        $this->forceFill([
            'email_verification_code_hash' => Hash::make($code),
            'email_verification_code_expires_at' => now()->addMinutes(15),
            'email_verification_code_sent_at' => now(),
            'email_verification_code_attempts' => 0,
        ])->save();

        $this->notify(new VerifyEmailNotification($code));
    }

    public function generateEmailVerificationCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function clearEmailVerificationCode(): void
    {
        $this->forceFill([
            'email_verification_code_hash' => null,
            'email_verification_code_expires_at' => null,
            'email_verification_code_sent_at' => null,
            'email_verification_code_attempts' => 0,
        ])->save();
    }

    public function generatePasswordResetCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function sendPasswordResetCodeNotification(): void
    {
        $code = $this->generatePasswordResetCode();

        $this->forceFill([
            'password_reset_code_hash' => Hash::make($code),
            'password_reset_code_expires_at' => now()->addMinutes(15),
            'password_reset_code_sent_at' => now(),
            'password_reset_code_verified_at' => null,
            'password_reset_code_attempts' => 0,
        ])->save();

        $this->notify(new ResetPasswordCodeNotification($code));
    }

    public function clearPasswordResetCode(): void
    {
        $this->forceFill([
            'password_reset_code_hash' => null,
            'password_reset_code_expires_at' => null,
            'password_reset_code_sent_at' => null,
            'password_reset_code_verified_at' => null,
            'password_reset_code_attempts' => 0,
        ])->save();
    }

    public function markPasswordResetCodeVerified(): void
    {
        $this->forceFill([
            'password_reset_code_verified_at' => now(),
        ])->save();
    }

    /**
     * @param  string  $token
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
