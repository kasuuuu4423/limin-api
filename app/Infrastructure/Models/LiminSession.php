<?php

declare(strict_types=1);

namespace App\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property int $user_id
 * @property string|null $device_id
 * @property \Carbon\Carbon $started_at
 * @property \Carbon\Carbon|null $stopped_at
 * @property string|null $current_item_id
 * @property \Carbon\Carbon|null $current_item_presented_at
 * @property \Carbon\Carbon|null $interrupt_offered_at
 * @property \Carbon\Carbon|null $interrupt_accepted_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read User $user
 * @property-read Item|null $currentItem
 */
final class LiminSession extends Model
{
    use HasUuids;

    protected $table = 'limin_sessions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'device_id',
        'started_at',
        'stopped_at',
        'current_item_id',
        'current_item_presented_at',
        'interrupt_offered_at',
        'interrupt_accepted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'stopped_at' => 'datetime',
            'current_item_presented_at' => 'datetime',
            'interrupt_offered_at' => 'datetime',
            'interrupt_accepted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function currentItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'current_item_id');
    }
}
