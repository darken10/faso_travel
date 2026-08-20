<?php

namespace App\Models\Voyage;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Classe extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'user_id',
        'compagnie_id',
        'is_default',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function (Classe $classe) {
            if (!$classe->user_id) {
                $classe->user_id = Auth::id() ?? \App\Models\User::first()?->id;
            }
            // Set compagnie_id from the creating user if not already set
            if (!$classe->compagnie_id && !$classe->is_default && Auth::check()) {
                $classe->compagnie_id = Auth::user()->compagnie_id;
            }
        });
    }

    protected static function booted(): void
    {
        // Une compagnie ne voit que ses classes et les classes communes. Les
        // comptes clients ne sont pas filtrés : la consultation d'un voyage
        // doit afficher la classe de la compagnie qui l'opère.
        //
        // Le filtre porte sur l'utilisateur, jamais sur l'URL : la version
        // précédente testait `request()->is('compagnie*')`, un chemin qui
        // n'existe plus depuis le passage aux sous-domaines.
        static::addGlobalScope('classeCompany', function (Builder $builder) {
            if (Auth::check() && Auth::user()->compagnie_id) {
                $companyId = Auth::user()->compagnie_id;
                $builder->where(function (Builder $q) use ($companyId) {
                    $q->where('compagnie_id', $companyId)
                      ->orWhere('is_default', true);
                });
            }
        });
    }

    function conforts():BelongsToMany
    {
        return $this->belongsToMany(Confort::class);
    }

    function voyages():HasMany
    {
        return $this->hasMany(Voyage::class);
    }
}
