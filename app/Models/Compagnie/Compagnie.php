<?php

namespace App\Models\Compagnie;

use App\DTOs\Compagnie\CompagnieSettings;
use App\Enums\CompagnieSettingKey;
use App\Models\Rating;
use App\Models\User;
use App\Models\Statut;
use App\Models\Voyage\Classe;
use App\Models\Voyage\Voyage;
use App\Models\Compagnie\Gare;
use App\Models\CompagnieSetting;
use App\Services\Compagnie\CompagnieSettingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Compagnie extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sigle',
        'slogant',
        'description',
        'logo_uri',
        'user_id',
        'statut_id',
    ];

    protected $with = [
        'statut'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(callback: function (Compagnie $compagnie) {
            if (Auth::check()) {
                $compagnie->user()->associate(Auth::user());
            }
        });
    }

    function user():BelongsTo{
        return $this->belongsTo(User::class);
    }

    function statut():BelongsTo{
        return $this->belongsTo(Statut::class);
    }

    function gares():HasMany{
        return $this->hasMany(Gare::class);
    }

    function voyages():HasMany{
        return $this->hasMany(Voyage::class);
    }

    function users():HasMany{
        return $this->hasMany(User::class);
    }

    function classes():HasManyThrough
    {
        return $this->hasManyThrough(Classe::class,User::class);
    }


    function chauffeurs():HasMany
    {
        return $this->hasMany(Chauffer::class);
    }

    function scopeActives(Builder $query)
    {
        return $query;
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(CompagnieSetting::class);
    }

    /**
     * Paramètres résolus de la compagnie : valeurs propres complétées par les
     * défauts du catalogue. Mis en cache par {@see CompagnieSettingService}.
     */
    public function parametres(): CompagnieSettings
    {
        return app(CompagnieSettingService::class)->bag($this);
    }

    /**
     * Valeur d'un paramètre.
     *
     * @param  CompagnieSettingKey|string  $key  Clé du catalogue.
     */
    public function getSetting(CompagnieSettingKey|string $key, mixed $default = null): mixed
    {
        return app(CompagnieSettingService::class)->get($this, $key, $default);
    }

    /** Écrit un paramètre après validation contre le catalogue. */
    public function setSetting(CompagnieSettingKey $key, mixed $value, bool $allowAdminOnly = false): void
    {
        app(CompagnieSettingService::class)->set($this, $key, $value, $allowAdminOnly);
    }

    public function getDevise(): string
    {
        return $this->parametres()->devise();
    }

    public function getDevisePosition(): string
    {
        return $this->parametres()->devisePosition();
    }

    public function getDevisePriceToUSD(): float
    {
        return $this->parametres()->float(CompagnieSettingKey::DEVISE_PRICE_TO_USD);
    }

    /** Formate un montant selon la devise et sa position configurées. */
    public function formatMontant(int|float $montant): string
    {
        $parametres = $this->parametres();
        $formate = number_format((float) $montant, 0, ',', ' ');

        return $parametres->devisePosition() === 'left'
            ? $parametres->devise().' '.$formate
            : $formate.' '.$parametres->devise();
    }

}
