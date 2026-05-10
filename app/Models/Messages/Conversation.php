<?php

namespace App\Models\Messages;

use App\Models\Compagnie\Compagnie;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'client_id',
        'compagnie_id',
        'status',
        'type',
        'last_message_at',
        'last_message',
        'unread_count_client',
        'unread_count_agent',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function compagnie(): BelongsTo
    {
        return $this->belongsTo(Compagnie::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage(): HasMany
    {
        return $this->hasMany(Message::class)->latest()->limit(1);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('client_id', $userId);
    }

    public function scopeSupport(Builder $query): Builder
    {
        return $query->where('type', 'support');
    }

    public function isSupport(): bool
    {
        return $this->type === 'support';
    }
}
