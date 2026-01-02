<?php

declare(strict_types=1);

namespace App\Models;

use App\Infrastructure\Models\DeviceToken;
use App\Infrastructure\Models\Item;
use App\Infrastructure\Models\LiminSession;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Carbon\Carbon|null $email_verified_at
 * @property string $password
 * @property string $later_restore_at
 * @property string|null $remember_token
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read LiminSession|null $activeSession
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Item> $items
 * @property-read \Illuminate\Database\Eloquent\Collection<int, LiminSession> $sessions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, DeviceToken> $deviceTokens
 */
final class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'later_restore_at',
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
            'password' => 'hashed',
        ];
    }

    /**
     * @return HasMany<Item, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * @return HasMany<LiminSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(LiminSession::class);
    }

    /**
     * @return HasOne<LiminSession, $this>
     */
    public function activeSession(): HasOne
    {
        return $this->hasOne(LiminSession::class)
            ->whereNull('stopped_at')
            ->latest('started_at');
    }

    /**
     * @return HasMany<DeviceToken, $this>
     */
    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }
}
