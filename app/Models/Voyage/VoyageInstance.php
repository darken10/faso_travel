<?php

namespace App\Models\Voyage;

use Carbon\Carbon;
use App\Enums\TypeTicket;
use App\Enums\StatutTicket;
use App\Models\Ville\Ville;
use App\Models\Ticket\Ticket;
use App\Models\Voyage\Voyage;
use App\Models\Compagnie\Care;
use App\Models\Compagnie\Chauffer;
use App\Enums\StatutVoyageInstance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoyageInstance extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "voyage_id",
        "date",
        "care_id",
        "heure",
        "nb_place",
        "chauffer_id",
        'statut',
        'prix',
        'classe_id',
        'rappel_depart_at',
    ];

    protected $with = [
        'voyage',
    ];

    public function scopeAvenir(Builder $query)
    {
        return $query->whereRaw(
            "STR_TO_DATE(CONCAT(date, ' ', heure), '%Y-%m-%d %H:%i:%s') >= ?",
            [Carbon::now()]
        );
    }

    public function voyage(): BelongsTo
    {
        return $this->belongsTo(Voyage::class);
    }

    public function care(): BelongsTo
    {
        return $this->belongsTo(Care::class);
    }


    public function chauffer(): BelongsTo
    {
        return $this->belongsTo(Chauffer::class);
    }

    public function cares(): \Illuminate\Database\Eloquent\Builder|HasMany|VoyageInstance
    {
        return $this->hasMany(Care::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array
     */
    protected function casts(): array
    {
        return [
            "date" => "datetime",
            'heure' => 'datetime',
            'statut' => StatutVoyageInstance::class,
            'rappel_depart_at' => 'datetime',
        ];
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /*---------------------------------------------------------*/

    public function villeDepart()
    {
        return $this->voyage->trajet->depart;
    }

    public function villeArrive(){
        return $this->voyage->trajet->arriver;
    }

    public function gareDepart()
    {
        return $this->voyage->gareDepart;
    }

    public function gareArrive(){
        return $this->voyage->gareArriver;
    }

    public function getPrix(TypeTicket $type)
    {
        return $type===TypeTicket::AllerSimple && $this->prix ? $this->prix : $this->voyage->getPrix($type);
    }

    public function conforts(){
        return $this->voyage->conforts;
    }

    public function compagnie(){
        return $this->voyage->compagnie;
    }

    public function nb_place_disponible()
    {
        return $this->voyage->nb_place ;
    }
    public function classe()
    {
        return $this->voyage->classe;
    }

    public function chaiseDispo(): array
    {
        $tkOccuper = $this->tickets()
            ->where('statut',StatutTicket::Payer)
            ->pluck('numero_chaise')->toArray();

        $allPlace = range(1, $this->nb_place);

        return array_diff($allPlace, $tkOccuper);
    }




    public function scopeDisponibles(Builder $query): Builder
    {
        return $query->whereHas('voyage', function ($q) {
            $q->whereNotNull('heure'); // facultatif, pour éviter les erreurs
        })->where(function ($q) {
            $now = Carbon::now();

            // Concaténation de date_depart (dans VoyageInstance) et heure_depart (depuis relation Voyage)
            $q->whereRaw("
                STR_TO_DATE(CONCAT(date, ' ', (SELECT heure FROM voyages WHERE voyages.id = voyage_instances.voyage_id)), '%Y-%m-%d %H:%i:%s') > ?
            ", [$now]);
        });
    }



    public function availableSeats(): int
    {
        return $this->nb_place - $this->tickets()->count();
    }

    public function getHeureDepart()
    {
        $heureDepart = Carbon::parse($this->heure);
        return Carbon::parse($this->date)->addMinutes($heureDepart->hour * 60 + $heureDepart->minute);
    }

    public function getHeureArrive()
    {
        $heureDepart = Carbon::parse($this->heure);
        $temps = Carbon::parse($this->voyage->temps);
        return Carbon::parse($this->date)->addMinutes($heureDepart->hour * 60 + $heureDepart->minute + $temps->hour * 60 + $temps->minute);
    }

}
