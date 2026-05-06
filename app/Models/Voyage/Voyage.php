<?php

namespace App\Models\Voyage;

use App\Models\User;
use App\Models\Statut;
use App\Enums\TypeTicket;
use App\Enums\JoursSemain;
use App\Models\Voyage\Days;
use App\Models\Ticket\Ticket;
use App\Models\Voyage\Classe;
use App\Models\Voyage\Trajet;
use App\Models\Compagnie\Care;
use App\Models\Compagnie\Gare;
use App\Models\Voyage\Confort;
use App\Models\Compagnie\Compagnie;
use Illuminate\Support\Facades\Auth;
use App\Models\Voyage\VoyageInstance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Voyage extends Model
{
    use HasFactory;

    protected $fillable = [
        'trajet_id',
        'user_id',
        'heure',
        'compagnie_id',
        'prix',
        'statut_id',
        'depart_id',
        'arrive_id',
        'days',
        'temps',
        'prix_aller_retour',
        'is_quotidient',
        'classe_id',
        'nb_pace',
    ];

    protected $with = [
        'user',
        'gareDepart',
        'gareArrive',
        'statut',
        'trajet',
        'compagnie'

    ];



    protected $casts = [
        'heure' => 'datetime',
        'days'=> 'array',
        'temps'=>'datetime',
    ];


    protected static function booted(): void
    {
        static::addGlobalScope('voyageCompany', function (Builder $builder) {
            if (Auth::check() && request()->is('compagnie/voyage*')) {
                if (Auth::user()->compagnie_id) {
                    $companyId = Auth::user()->compagnie_id;
                    $builder->where('compagnie_id', $companyId);
                }
            }
        });
    }


    public function getDays(): \Illuminate\Support\Collection
    {
        return collect(json_decode($this->days))->map(fn ($jour)=>JoursSemain::from($jour));

    }

    public function setDays(): void
    {
        $this->attributes['days'] = json_encode(collect($this->days)->map(fn ($jour)=>$jour->value));
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($voyage) {
            $voyage->user_id = Auth::id();
            $voyage->compagnie_id = $voyage->user->compagnie_id;
        });
    }

    function gareDepart():BelongsTo
    {
        return $this->belongsTo(Gare::class, 'depart_id');
    }

    function gareArrive(): BelongsTo
    {
        return $this->belongsTo(Gare::class, 'arrive_id');
    }

    function gareArriver(): BelongsTo
    {
        return $this->belongsTo(Gare::class, 'arrive_id');
    }


    function statut(): BelongsTo
    {
        return $this->belongsTo(Statut::class, 'statut_id');
    }

    function trajet(): BelongsTo
    {
        return $this->belongsTo(Trajet::class);
    }

    function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    function compagnie(): BelongsTo
    {
        return $this->belongsTo(Compagnie::class);
    }

    function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    function conforts()
    {
        return $this->classe?->conforts();
    }

    function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    function cares(): BelongsToMany
    {
        return $this->belongsToMany(Care::class);
    }

    function care()
    {
        return $this->hasOne(Care::class)->latestOfMany();
    }

    function days():BelongsToMany
    {
        return $this->belongsToMany(Days::class);
    }

    function getPrix(TypeTicket $type):float
    {
        return $type ==TypeTicket::AllerRetour ? $this->prix_aller_retour : $this->prix;
    }

    public function voyage_instances(): HasMany
    {
        return $this->hasMany(VoyageInstance::class);
    }

    // ── Query Scopes ──────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompagnie(Builder $query, int $compagnieId): Builder
    {
        return $query->where('compagnie_id', $compagnieId);
    }

    public function scopeSearch(Builder $query, ?string $depart, ?string $arriver): Builder
    {
        return $query->when($depart, fn ($q) => $q->whereHas('trajet.depart', fn ($t) => $t->where('nom', 'like', "%{$depart}%")))
                     ->when($arriver, fn ($q) => $q->whereHas('trajet.arriver', fn ($t) => $t->where('nom', 'like', "%{$arriver}%")));
    }

    public function scopeHasAvailableSeats(Builder $query): Builder
    {
        return $query->whereHas('voyage_instances', fn ($q) => $q->where('nb_places_disponibles', '>', 0));
    }

    public function scopeOrderByDeparture(Builder $query): Builder
    {
        return $query->orderBy('heure');
    }

}
