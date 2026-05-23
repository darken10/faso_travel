<?php

namespace App\Models\Compagnie;

use App\Enums\StatutCare;
use App\Models\Document;
use App\Models\User;
use App\Models\Voyage\Voyage;
use App\Models\Voyage\VoyageInstance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

class Care extends Model
{
    use HasFactory;

    protected $fillable = [
        'immatrculation',
        'numero',
        'number_place',
        'statut',
        'etat',
        'image_uri',
        'compagnie_id',
    ];


    protected static function boot(): void
    {
        parent::boot();

        static::creating(callback: function (Care $care) {
            $care->compagnie_id = Auth::user()->compagnie_id;
        });
    }

    protected $casts = [
        'statut' => StatutCare::class
    ];


    protected static function booted(): void
    {
        static::addGlobalScope('careCompany', function (Builder $builder) {
            if (Auth::check() && request()->is('compagnie/compagnie/cares*')) {
                if (Auth::user()->compagnie_id) {
                    $companyId = Auth::user()->compagnie_id;
                    $builder->where('compagnie_id', $companyId);
                }
            }
        });
    }
    function voyages():HasMany
    {
        return  $this->hasMany(Voyage::class);
    }

    function compagnie():BelongsTo
    {
        return  $this->belongsTo(Compagnie::class);
    }

    function voyageInstances():HasMany
    {
        return $this->hasMany(VoyageInstance::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

}
