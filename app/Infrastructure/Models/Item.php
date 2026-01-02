<?php

declare(strict_types=1);

namespace App\Infrastructure\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property int $user_id
 * @property string $type
 * @property string $state
 * @property string $availability
 * @property string $next_action
 * @property \Carbon\Carbon|null $due_at
 * @property int|null $timebox
 * @property bool $meta
 * @property \Carbon\Carbon|null $last_presented_at
 * @property \Carbon\Carbon|null $done_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read User $user
 */
final class Item extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'items';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'type',
        'state',
        'availability',
        'next_action',
        'due_at',
        'timebox',
        'meta',
        'last_presented_at',
        'done_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'last_presented_at' => 'datetime',
            'done_at' => 'datetime',
            'meta' => 'boolean',
            'timebox' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
