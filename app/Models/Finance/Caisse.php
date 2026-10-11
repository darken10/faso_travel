<?php

namespace App\Models\Finance;

use App\Enums\StatutCaisse;
use App\Enums\StatutPayement;
use App\Models\Compagnie\Compagnie;
use App\Models\Ticket\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;

class Caisse extends Model
{
    protected $fillable = [
        'user_id',
        'compagnie_id',
        'montant_ouverture',
        'montant_fermeture',
        'montant_attendu',
        'statut',
        'opened_at',
        'closed_at',
        'note_ouverture',
        'note_fermeture',
    ];

    protected function casts(): array
    {
        return [
            'statut' => StatutCaisse::class,
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'montant_ouverture' => 'integer',
            'montant_fermeture' => 'integer',
            'montant_attendu' => 'integer',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function compagnie(): BelongsTo
    {
        return $this->belongsTo(Compagnie::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function bordereauVersement(): HasOne
    {
        return $this->hasOne(BordereauVersement::class);
    }

    public function isOuverte(): bool
    {
        return $this->statut === StatutCaisse::Ouverte;
    }

    /**
     * Get the total revenue from ticket sales during this session.
     */
    public function totalVentes(): int
    {
        return $this->totalEspeces();
    }

    /** Montant effectivement détenu par l'agent, hors paiements électroniques. */
    public function totalEspeces(): int
    {
        return (int) $this->tickets()
            ->whereHas('payements', fn ($q) => $q
                ->where('statut', StatutPayement::Complete->value)
                ->where('moyen_payment', \App\Enums\MoyenPayment::ESPECE->value))
            ->withSum(['payements as total' => fn ($q) => $q
                ->where('statut', StatutPayement::Complete->value)
                ->where('moyen_payment', \App\Enums\MoyenPayment::ESPECE->value)], 'montant')
            ->get()
            ->sum('total');
    }

    /**
     * Get the number of tickets sold during this session.
     */
    public function nombreTickets(): int
    {
        return $this->tickets()->count();
    }

    /**
     * Get the expected closing amount (opening + sales).
     */
    public function calculerMontantAttendu(): int
    {
        return $this->montant_ouverture + $this->totalVentes();
    }

    /**
     * Get the difference between actual and expected closing amounts.
     */
    public function ecart(): ?int
    {
        if ($this->montant_fermeture === null) {
            return null;
        }
        return $this->montant_fermeture - $this->calculerMontantAttendu();
    }

    /**
     * Find or check if the current user has an open cash register session.
     */
    public static function sessionOuverte(): ?self
    {
        if (!Auth::check()) {
            return null;
        }

        return static::where('user_id', Auth::id())
            ->where('statut', StatutCaisse::Ouverte->value)
            ->latest('opened_at')
            ->first();
    }
}
