<?php

namespace App\Models\Finance;

use App\Models\Compagnie\Compagnie;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class Recette extends Model
{
    protected $fillable = [
        'compagnie_id',
        'libelle',
        'montant',
        'date_recette',
        'source',
        'reference',
        'note',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'date_recette' => 'date',
            'montant' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('compagnie', function (Builder $builder) {
            if (Auth::check() && Auth::user()->compagnie_id) {
                $builder->where('compagnie_id', Auth::user()->compagnie_id);
            }
        });
    }

    public function compagnie(): BelongsTo
    {
        return $this->belongsTo(Compagnie::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
