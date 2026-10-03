<?php

namespace App\Models;

use App\Models\Compagnie\Compagnie;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

/**
 * Entrée immuable de la piste d'audit.
 *
 * Une trace qui peut être réécrite ne prouve rien : le modèle refuse donc toute
 * modification et toute suppression. Seule une purge délibérée en SQL, hors de
 * l'application, peut y toucher — et c'est précisément ce qu'on veut rendre visible.
 */
class AuditLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'compagnie_id',
        'action',
        'auditable_type',
        'auditable_id',
        'avant',
        'apres',
        'motif',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'avant' => 'array',
            'apres' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Une entrée de piste d\'audit ne peut pas être modifiée.');
        });

        static::deleting(function (): never {
            throw new LogicException('Une entrée de piste d\'audit ne peut pas être supprimée.');
        });
    }

    /** @param  array<string, mixed>  $attributes */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new LogicException('Une entrée de piste d\'audit ne peut pas être modifiée.');
    }

    public function delete(): bool
    {
        throw new LogicException('Une entrée de piste d\'audit ne peut pas être supprimée.');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function compagnie(): BelongsTo
    {
        return $this->belongsTo(Compagnie::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeOfCompagnie(Builder $query, int $compagnieId): Builder
    {
        return $query->where('compagnie_id', $compagnieId);
    }

    public function scopeAction(Builder $query, string ...$actions): Builder
    {
        return $query->whereIn('action', $actions);
    }
}
