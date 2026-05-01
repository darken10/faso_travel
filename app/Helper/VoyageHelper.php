<?php


namespace App\Helper;

use App\Enums\StatutTicket;
use App\Models\Ticket\Ticket;
use App\Models\Voyage\Voyage;
use App\Models\Voyage\VoyageInstance;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class VoyageHelper
{

    public static function getDateDisponibles(Voyage $voyage): array|Collection
    {

        $joursSelectionnes = collect($voyage->days); // Les jours sélectionnés dans la base
        return self::getDatesDisposWithDays($joursSelectionnes);
    }


    public static function getChaiseDisponibles(Voyage $voyage,$date): array|Collection
    {
        $infoDate = mb_split('-',$date);
        $tkOccuper = Ticket::query()
            ->whereBelongsTo($voyage)
            ->where('date', $date)
            ->where('statut',StatutTicket::Payer)
            ->get()
            ->pluck('numero_chaise')->toArray();

        $allPlace = range(1, $voyage->nb_pace);

        // Exclure les éléments de $toExclude de $allPlace
        return array_diff($allPlace, $tkOccuper);


    }

    public static function getChaiseDisponiblesWithVoyageInstances(string $voyageinstanceId): array|Collection
    {

        $voyageInstance = VoyageInstance::query()->findOrFail($voyageinstanceId);
        $tkOccuper = $voyageInstance->tickets()
            ->where('statut',StatutTicket::Payer)
            ->pluck('numero_chaise')->toArray();


        $allPlace = range(1, $voyageInstance->nb_place);

        // Exclure les éléments de $toExclude de $allPlace
        return array_diff($allPlace, $tkOccuper);


    }


    public static function getAllVoyageDispoForYourTicketEdit(Ticket $ticket):Collection
    {
        return Voyage::query()
            ->whereBelongsTo($ticket->compagnie())
            ->whereBelongsTo($ticket->classe())
            //->whereBelongsTo($ticket->trajet())
            //->wherePrix($ticket->prix())
            ->get();


    }

    public static function getDateDisponiblesWithADays(array $days):Collection
    {
        $joursSelectionnes = collect($days); // Les jours sélectionnés dans la base
        return self::getDatesDisposWithDays($joursSelectionnes);
    }

    public static function getDateDisponiblesWithTicket(Ticket $ticket):Collection
    {
        $joursSelectionnes = collect(self::getAllDaysDisponiblesWithAllTicket($ticket)); // Les jours sélectionnés dans la base
        return self::getDatesDisposWithDays($joursSelectionnes);
    }

    /**
     * @param Collection $joursSelectionnes
     * @return Collection
     */
    public static function getDatesDisposWithDays(Collection $joursSelectionnes): Collection
    {
        $joursSelectionnes = $joursSelectionnes->map(fn($jour) => strtolower($jour));
        $datesDisponibles = [];

        Carbon::setLocale('fr');
        $dates = [];
        for ($i = 0; $i < 30; $i++) {
            $date = Carbon::now()->addDays($i);
            $jourSemaine = strtolower($date->translatedFormat('l'));
            if ($joursSelectionnes->contains($jourSemaine)) {
                $datesDisponibles[] = $date->toDateString() . ' (' . $jourSemaine . ')';
                $dates[] = $date->toDateString();

            }
        }
        return collect([
            'datesDisponiblesAndDays' => $datesDisponibles,
            'datesDisponibles' => $dates
        ]);
    }


    public static  function getAllDaysDisponiblesWithAllTicket(Ticket $ticket) {
        $voyages = self::getAllVoyageDispoForYourTicketEdit($ticket);
        $b = [];
        foreach ($voyages as $voyage){
            $b[] =  $voyage->days;
        }
        $a = [];
        foreach ($b as $i){
            $a = array_unique(array_merge($a,$i));
        }
        return $a;
    }

    public static function getVoyagesByDay(string $date): Collection
    {
        Carbon::setLocale('fr');
        $day = ucfirst(Carbon::parse($date)->translatedFormat('l'));

        return Voyage::whereJsonContains('days', $day)->get();
    }

}
