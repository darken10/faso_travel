<?php

namespace App\Models\Ticket;

use App\Enums\ConflictResolution;
use App\Enums\SyncActionType;
use App\Enums\SyncErrorCode;
use App\Enums\SyncResult;
use App\Models\User;
use App\Models\Voyage\VoyageInstance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Journal d'un contrôle de ticket, et clé d'idempotence du batch-sync.
 *
 * À ne pas confondre avec App\Helper\TicketValidation, qui porte les transitions
 * d'état du ticket. Ce modèle n'enregistre que ce qui s'est passé.
 */
class TicketValidation extends Model
{
    use HasFactory;

    protected $table = 'ticket_validations';

    protected $fillable = [
        'operation_id',
        'ticket_id',
        'requested_ticket_id',
        'requested_qr_hash',
        'voyage_instance_id',
        'agent_id',
        'device_id',
        'action',
        'method',
        'verified_offline',
        'client_created_at',
        'result',
        'error_code',
        'resolution',
        'resolution_note',
        'resolved_by_id',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'action'            => SyncActionType::class,
            'result'            => SyncResult::class,
            'error_code'        => SyncErrorCode::class,
            'resolution'        => ConflictResolution::class,
            'resolved_at'       => 'datetime',
            'verified_offline'  => 'boolean',
            'client_created_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
    }

    public function voyageInstance(): BelongsTo
    {
        return $this->belongsTo(VoyageInstance::class, 'voyage_instance_id');
    }

    /** Opérations refusées par le serveur, à arbitrer par l'administration. */
    public function scopeConflicts(Builder $query): Builder
    {
        return $query->where('result', SyncResult::Rejected->value);
    }

    /** Refus pas encore instruits par l'administration ou la finance. */
    public function scopeOpenConflicts(Builder $query): Builder
    {
        return $query->conflicts()->whereNull('resolved_at');
    }

    /**
     * Restreint aux opérations d'agents de la compagnie.
     *
     * À préférer à ofCompagnie() pour les conflits : un refus "ticket introuvable"
     * n'a pas de ticket, donc aucun voyage par lequel remonter à la compagnie. Il
     * reste rattaché à l'agent qui l'a tenté.
     */
    public function scopeOfAgentsOfCompagnie(Builder $query, int $compagnieId): Builder
    {
        return $query->whereHas('agent', fn (Builder $q) => $q->where('compagnie_id', $compagnieId));
    }

    /** Contrôles où l'agent a tranché sans pouvoir vérifier le ticket. */
    public function scopeUnverified(Builder $query): Builder
    {
        return $query->where('verified_offline', false);
    }

    /** Restreint aux contrôles portant sur un voyage d'une compagnie donnée. */
    public function scopeOfCompagnie(Builder $query, int $compagnieId): Builder
    {
        return $query->whereHas(
            'ticket.voyageInstance.voyage',
            fn (Builder $q) => $q->where('compagnie_id', $compagnieId),
        );
    }
}
