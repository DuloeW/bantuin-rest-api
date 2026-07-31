<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\Village;

#[Guarded([])]
#[Hidden([
    'password',
    'remember_token',
    'email_verified_at',
    'province_id',
    'city_id',
    'district_id',
    'village_id',
])]
class User extends Authenticatable implements FilamentUser, HasName
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function completedRequestPosts()
    {
        return $this->posts()
            ->where('type', 'request')
            ->whereHas('offers.transaction', function ($query) {
                $query->where('status', 'completed');
            });
    }

    public function helpedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'helper_id')->where('status', 'completed');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'skill_users', 'user_id', 'skill_id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function primaryBankAccount(): HasOne
    {
        return $this->hasOne(BankAccount::class)->where('is_primary', true);
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function transactionsAsRequester(): HasMany
    {
        return $this->hasMany(Transaction::class, 'requester_id');
    }

    public function transactionsAsHelper(): HasMany
    {
        return $this->hasMany(Transaction::class, 'helper_id');
    }

    public function reviewReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewed_id');
    }

    public function reviewGiven(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function photoProfile()
    {
        return $this->morphOne(Image::class, 'imageable')
            ->ofMany(['id' => 'max'], function ($query) {
                $query->where('type', 'profile');
            });
    }

    public function ktpPhoto()
    {
        return $this->morphOne(Image::class, 'imageable')
            ->ofMany(['id' => 'max'], function ($query) {
                $query->where('type', 'ktp');
            });
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function getFilamentName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === 'admin';
    }
}
