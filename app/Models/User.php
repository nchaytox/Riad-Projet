<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'avatar',
        'password',
        'random_key',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
    ];

    public function getAvatar()
    {
        if (! $this->avatar) {
            return asset('img/default/default-user.jpg');
        }

        return asset('img/user/'.$this->name.'-'.$this->id.'/'.$this->avatar);
    }

    public function customer()
    {
        return $this->hasOne(Customer::class);
    }

    public function isCustomer()
    {
        return $this->role === 'Customer';
    }

    public function isStaff(): bool
    {
        return in_array($this->role, ['Super', 'Admin'], true);
    }

    public function shouldEnforceTwoFactorAuthentication(): bool
    {
        return $this->isStaff();
    }

    public function hasEnabledTwoFactorAuthentication(): bool
    {
        return ! empty($this->two_factor_secret) && $this->two_factor_confirmed_at !== null;
    }

    public function shouldPromptForTwoFactorSetup(): bool
    {
        return $this->shouldEnforceTwoFactorAuthentication() && ! $this->hasEnabledTwoFactorAuthentication();
    }

    public function twoFactorSecret(): ?string
    {
        return $this->two_factor_secret ? decrypt($this->two_factor_secret) : null;
    }

    /**
     * @return array<int, string>
     */
    public function recoveryCodes(): array
    {
        if (! $this->two_factor_recovery_codes) {
            return [];
        }

        $codes = json_decode(decrypt($this->two_factor_recovery_codes), true);

        return Arr::wrap($codes);
    }

    public function replaceRecoveryCodes(array $codes): void
    {
        $this->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode(array_values($codes))),
        ])->save();
    }

    /**
     * @return array<int, string>
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))
            ->map(fn () => strtoupper(Str::random(10)))
            ->toArray();
    }

    public function disableTwoFactorAuthentication(): void
    {
        $this->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }
}
