<?php

namespace App\Models\Ticket;

use Carbon\Carbon;
use App\Models\User;
use App\Enums\TypeTicket;
use App\Enums\StatutTicket;
use App\Models\Compagnie\Gare;
use App\Models\Ticket\Payement;
use App\Models\Compagnie\Compagnie;
use App\Models\Ticket\AutrePersonne;
use Illuminate\Support\Facades\Auth;
use App\Models\Voyage\VoyageInstance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ticket extends Model
{
    use HasFactory,Notifiable;

    protected $fillable = [
        'user_id',
        'voyage_id',
        "a_bagage",
        "bagages_data",
        'date',
        'type',
        'statut',
        'numero_ticket',
        'numero_chaise',
        'code_sms',
        'code_qr',
        'image_uri',
        'pdf_uri',
        'code_qr_uri',
        'is_my_ticket',
        'autre_personne_id',
        'retour_validate_by',
        "transferer_at", "valider_by_id", "valider_at", "transferer_a_user_id",
        "retour_validate_at",
        "voyage_instance_id",
        "caisse_id"
    ];

    protected $with = [
        'user',
        'payements',
        'voyageInstance',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('userCompany', function (Builder $builder) {
            if (Auth::check() && request()->is('compagnie/ticket*')) {
                if (Auth::user()->compagnie_id) {
                    $companyId = Auth::user()->compagnie_id;
                    $builder->whereHas('voyageInstance', function ($query) use ($companyId) {
                        $query->whereHas('voyage', function ($subQuery) use ($companyId) {
                            $subQuery->where('compagnie_id', $companyId);
                        });
                    });
                }
            }

        });
    }

    public function canValider(): bool
    {
        return $this->statut === StatutTicket::Payer;
    }

    protected function casts(): array
    {
        return [
            'type'         => TypeTicket::class,
            'a_bagage'     => 'boolean',
            'is_my_ticket' => 'boolean',
            'date'         => 'date',
            'bagages'      => 'array',
            'statut'       => StatutTicket::class,
        ];
    }

    function payements():HasMany
    {
        return $this->hasMany(Payement::class);
    }

    function user():BelongsTo{
        return $this->belongsTo(User::class);
    }



    #----------------------------------------------------------------

    function villeDepart(){
        return $this->voyageInstance->voyage->trajet->depart;
    }

    function villeArriver(){
        return $this->voyageInstance->voyage->trajet->arriver;
    }

    function compagnie():Compagnie{
        return $this->voyageInstance->voyage->compagnie;
    }

    function heureDepart(){
        return $this->voyageInstance->heure;
    }

    /**
     * @return Carbon
     */
    function heureArriver():Carbon{
        $temps = $this->voyageInstance->voyage->temps;
        return $this->voyageInstance->heure->addHours($temps->hour)
                    ->addMinutes($temps->minute)
                    ->addSeconds($temps->second);
    }

    function gareDepart():Gare{
        return $this->voyageInstance->voyage->gareDepart;
    }

    function gareArriver():Gare{
        return $this->voyageInstance->voyage->gareArriver;
    }

    function conforts(){
        return $this->voyageInstance->voyage->conforts;
    }

    function classe(){
        return $this->voyageInstance->voyage->classe;
    }

    function autre_personne(): BelongsTo
    {
        return $this->belongsTo(AutrePersonne::class);
    }

    function trajet()
    {
        return $this->voyageInstance->voyage->trajet;
    }

    public function prix(): float
    {
        return $this->voyageInstance->voyage->getPrix($this->type);
    }

    public function heureRdv():Carbon
    {
        return $this->voyageInstance->voyage->heure->subMinutes(10);
    }

    public function voyageInstance():BelongsTo
    {
        return $this->belongsTo(VoyageInstance::class);
    }

    public function caisse(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Finance\Caisse::class);
    }

    // ── Query Scopes ──────────────────────────────────────────────────────────

    public function scopeForUser(Builder $builder, int $userId): Builder
    {
        return $builder->where('user_id', $userId);
    }

    public function scopeByStatut(Builder $builder, StatutTicket $statut): Builder
    {
        return $builder->where('statut', $statut);
    }

    public function scopePaid(Builder $builder): Builder
    {
        return $builder->where('statut', StatutTicket::Payer);
    }

    public function scopeActive(Builder $builder): Builder
    {
        return $builder->where('statut', StatutTicket::Valider);
    }

    public function scopeValidated(Builder $builder): Builder
    {
        return $builder->where('statut', StatutTicket::Valider);
    }

    public function scopeForCompagnie(Builder $builder, int $compagnieId): Builder
    {
        return $builder->whereHas('voyageInstance.voyage', fn ($q) => $q->where('compagnie_id', $compagnieId));
    }

    public function scopeForDate(Builder $builder, string $date): Builder
    {
        return $builder->whereDate('date', $date);
    }

    public function scopeSearch(Builder $builder, string $term): Builder
    {
        return $builder->where(function ($q) use ($term) {
            $q->where('numero_ticket', 'like', "%{$term}%")
              ->orWhere('code_sms', 'like', "%{$term}%")
              ->orWhereHas('user', fn ($u) => $u->where('first_name', 'like', "%{$term}%")
                                                 ->orWhere('last_name', 'like', "%{$term}%")
                                                 ->orWhere('phone', 'like', "%{$term}%"));
        });
    }
}
