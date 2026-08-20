<?php

namespace App\Models\Compagnie;

use App\Models\Compagnie\Compagnie;
use App\Models\Document;
use App\Models\User;
use App\Models\Statut;
use App\Models\Ville\Ville;
use App\Models\Voyage\Voyage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

class Gare extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'lng',
        'lat',
        'user_id',
        'statut_id',
        'compagnie_id',
        'ville_id',
        'is_default',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(callback: function (Gare $gare) {
            if (Auth::check()) {
                if (!$gare->user_id) {
                    $gare->user()->associate(Auth::user());
                }
                if (!$gare->compagnie_id && !$gare->is_default) {
                    $gare->compagnie_id = Auth::user()->compagnie_id;
                }
            }
        });
    }

    protected static function booted(): void
    {
        // Un compte rattaché à une compagnie ne voit que ses gares et les gares
        // communes. Les comptes clients ne sont pas filtrés : la recherche de
        // voyages doit continuer à couvrir toutes les compagnies.
        //
        // Le filtre porte sur l'utilisateur, jamais sur l'URL : la version
        // précédente testait `request()->is('compagnie*')`, un chemin qui
        // n'existe plus depuis le passage aux sous-domaines.
        static::addGlobalScope('gareCompany', function (Builder $builder) {
            if (Auth::check() && Auth::user()->compagnie_id) {
                $companyId = Auth::user()->compagnie_id;
                $builder->where(function (Builder $q) use ($companyId) {
                    $q->where('compagnie_id', $companyId)
                      ->orWhere('is_default', true);
                });
            }
        });
    }

    function user():BelongsTo{
        return $this->belongsTo(User::class);
    }

    function statut():BelongsTo{
        return $this->belongsTo(Statut::class);
    }

    function compagnie():BelongsTo{
        return $this->belongsTo(Compagnie::class);
    }

    function ville():BelongsTo{
        return $this->belongsTo(Ville::class);
    }

    function departs():HasMany
    {
        return $this->hasMany(Voyage::class, 'depart_id');
    }

    function arrives():HasMany
    {
        return $this->hasMany(Voyage::class, 'arrive_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
