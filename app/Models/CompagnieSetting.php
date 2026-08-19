<?php

namespace App\Models;

use App\Enums\CompagnieSettingKey;
use App\Enums\CompagnieSettingType;
use App\Models\Compagnie\Compagnie;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Valeur d'un paramètre pour une compagnie donnée.
 *
 * Une ligne n'existe que si la compagnie s'écarte de la valeur par défaut
 * du catalogue ({@see CompagnieSettingKey}) : l'absence de ligne vaut défaut.
 */
class CompagnieSetting extends Model
{
    protected $fillable = ['compagnie_id', 'key', 'value', 'type'];

    public function compagnie(): BelongsTo
    {
        return $this->belongsTo(Compagnie::class);
    }

    public function scopeForCompagnie(Builder $query, int $compagnieId): Builder
    {
        return $query->where('compagnie_id', $compagnieId);
    }

    /** Case du catalogue correspondante, ou null si la clé n'est plus référencée. */
    public function settingKey(): ?CompagnieSettingKey
    {
        return CompagnieSettingKey::tryFrom($this->key);
    }

    /** Type effectif : celui du catalogue, avec repli sur la colonne stockée. */
    public function settingType(): CompagnieSettingType
    {
        return $this->settingKey()?->type()
            ?? CompagnieSettingType::tryFrom((string) $this->type)
            ?? CompagnieSettingType::String;
    }

    /** Valeur convertie dans son type métier. */
    public function getCastedValue(): mixed
    {
        return $this->settingType()->cast($this->value);
    }

    /** Libellé lisible du paramètre (repli sur la clé brute si hors catalogue). */
    public function label(): string
    {
        return $this->settingKey()?->label() ?? $this->key;
    }

    /** Représentation courte de la valeur, pour les tableaux d'administration. */
    public function displayValue(): string
    {
        $value = $this->getCastedValue();

        return match (true) {
            is_bool($value)  => $value ? 'Activé' : 'Désactivé',
            is_array($value) => $value === [] ? '—' : implode(', ', $value),
            $value === null || $value === '' => '—',
            default          => (string) $value,
        };
    }
}
