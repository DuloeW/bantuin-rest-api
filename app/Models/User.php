<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    use HasFactory, Notifiable, HasApiTokens, HasUuids;

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
        ];
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    public function transactionsAsRequester()
    {
        return $this->hasMany(Transaction::class, 'requester_id');
    }

    public function transactionsAsHelper()
    {
        return $this->hasMany(Transaction::class, 'helper_id');
    }

    public function reviewReceived()
    {
        return $this->hasMany(Review::class, 'reviewed_id');
    }

    public function photoProfile()
    {
        return $this->morphOne(Image::class, 'imageable');
    }

    public function ktpPhoto()
    {
        return $this->morphOne(Image::class, 'imageable');
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function village()
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
