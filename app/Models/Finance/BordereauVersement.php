<?php

namespace App\Models\Finance;

use App\Enums\StatutBordereauVersement;
use App\Models\Compagnie\Compagnie;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class BordereauVersement extends Model
{
    protected $table = 'bordereaux_versement';

    protected $fillable = [
        'compagnie_id',
        'caisse_id',
        'numero',
        'montant_attendu',
        'montant_declare',
        'montant_recu',
        'montant_comptabilise',
        'statut',
        'emitted_by_id',
        'received_by_id',
        'transmitted_by_id',
        'accounted_by_id',
        'recette_id',
        'emitted_at',
        'received_at',
        'transmitted_at',
        'accounted_at',
        'reference_comptable',
        'note_emission',
        'note_reception',
        'note_transmission',
        'note_comptabilite',
    ];

    protected function casts(): array
    {
        return [
            'statut' => StatutBordereauVersement::class,
            'montant_attendu' => 'integer',
            'montant_declare' => 'integer',
            'montant_recu' => 'integer',
            'montant_comptabilise' => 'integer',
            'emitted_at' => 'datetime',
            'received_at' => 'datetime',
            'transmitted_at' => 'datetime',
            'accounted_at' => 'datetime',
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

    public function caisse(): BelongsTo
    {
        return $this->belongsTo(Caisse::class);
    }

    public function emetteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'emitted_by_id');
    }

    public function recepteurFinance(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_id');
    }

    public function transmetteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transmitted_by_id');
    }

    public function comptable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accounted_by_id');
    }

    public function recette(): BelongsTo
    {
        return $this->belongsTo(Recette::class);
    }

    public function ecartReception(): ?int
    {
        return $this->montant_recu === null ? null : $this->montant_recu - $this->montant_declare;
    }

    public function ecartComptabilite(): ?int
    {
        return $this->montant_comptabilise === null ? null : $this->montant_comptabilise - $this->montant_recu;
    }
}
