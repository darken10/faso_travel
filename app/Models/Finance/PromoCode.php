<?php

namespace App\Models\Finance;

use App\Models\Compagnie\Compagnie;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromoCode extends Model
{
    protected $fillable = [
        'compagnie_id', 'code', 'type', 'valeur',
        'date_debut', 'date_fin', 'usage_limit', 'used_count',
        'min_montant', 'active',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin'   => 'date',
            'active'     => 'boolean',
        ];
    }

    public function compagnie(): BelongsTo
    {
        return $this->belongsTo(Compagnie::class);
    }

    /** Le code est-il actuellement utilisable ? */
    public function isValide(?int $montant = null): bool
    {
        if (!$this->active) {
            return false;
        }
        $today = Carbon::today();
        if ($this->date_debut && $today->lt($this->date_debut->copy()->startOfDay())) {
            return false;
        }
        if ($this->date_fin && $today->gt($this->date_fin->copy()->startOfDay())) {
            return false;
        }
        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }
        if ($montant !== null && $this->min_montant && $montant < $this->min_montant) {
            return false;
        }
        return true;
    }

    /** Montant de la réduction pour un prix donné (0 si non applicable). */
    public function reductionPour(int $montant): int
    {
        if (!$this->isValide($montant)) {
            return 0;
        }
        $reduction = $this->type === 'pourcentage'
            ? (int) round($montant * $this->valeur / 100)
            : (int) $this->valeur;

        return min($reduction, $montant); // jamais plus que le prix
    }

    /** Motif d'invalidité (pour message utilisateur). */
    public function raisonInvalide(?int $montant = null): string
    {
        if (!$this->active) {
            return 'Ce code promo est désactivé.';
        }
        $today = Carbon::today();
        if ($this->date_debut && $today->lt($this->date_debut->copy()->startOfDay())) {
            return "Ce code n'est pas encore actif.";
        }
        if ($this->date_fin && $today->gt($this->date_fin->copy()->startOfDay())) {
            return 'Ce code promo a expiré.';
        }
        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return "Ce code a atteint sa limite d'utilisation.";
        }
        if ($montant !== null && $this->min_montant && $montant < $this->min_montant) {
            return 'Montant minimum non atteint pour ce code.';
        }
        return '';
    }
}
