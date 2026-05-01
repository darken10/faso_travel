<?php

namespace App\Livewire\Compagnie\Caisse;

use App\Enums\StatutCaisse;
use App\Models\Finance\Caisse;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.compagnie-panel')]
class GestionCaisse extends Component
{
    // Ouverture form
    public int $montant_ouverture = 0;
    public string $note_ouverture = '';

    // Fermeture form
    public int $montant_fermeture = 0;
    public string $note_fermeture = '';

    public function ouvrirCaisse(): void
    {
        $this->validate([
            'montant_ouverture' => 'required|integer|min:0',
            'note_ouverture'    => 'nullable|string|max:500',
        ]);

        $existing = Caisse::sessionOuverte();
        if ($existing) {
            session()->flash('error', 'Vous avez déjà une caisse ouverte. Fermez-la d\'abord.');
            return;
        }

        $compagnieId = Auth::user()->compagnie_id;
        if (!$compagnieId) {
            session()->flash('error', 'Votre compte n\'est pas associé à une compagnie.');
            return;
        }

        Caisse::create([
            'user_id'           => Auth::id(),
            'compagnie_id'      => $compagnieId,
            'montant_ouverture' => $this->montant_ouverture,
            'statut'            => StatutCaisse::Ouverte->value,
            'opened_at'         => now(),
            'note_ouverture'    => $this->note_ouverture ?: null,
        ]);

        $this->montant_ouverture = 0;
        $this->note_ouverture = '';
        session()->flash('success', 'Caisse ouverte ! Vous pouvez commencer les ventes.');
    }

    public function fermerCaisse(): void
    {
        $this->validate([
            'montant_fermeture' => 'required|integer|min:0',
            'note_fermeture'    => 'nullable|string|max:500',
        ]);

        $caisse = Caisse::sessionOuverte();
        if (!$caisse) {
            session()->flash('error', 'Aucune caisse ouverte à fermer.');
            return;
        }

        $caisse->update([
            'montant_fermeture' => $this->montant_fermeture,
            'montant_attendu'   => $caisse->calculerMontantAttendu(),
            'statut'            => StatutCaisse::Fermee->value,
            'closed_at'         => now(),
            'note_fermeture'    => $this->note_fermeture ?: null,
        ]);

        $this->montant_fermeture = 0;
        $this->note_fermeture = '';
        session()->flash('success', 'Caisse fermée avec succès.');
    }

    public function render()
    {
        $caisse = Caisse::sessionOuverte();

        $stats = [];
        if ($caisse) {
            $totalVentes = $caisse->totalVentes();
            $stats = [
                'total_ventes'     => $totalVentes,
                'nombre_tickets'   => $caisse->nombreTickets(),
                'montant_ouverture' => $caisse->montant_ouverture,
                'montant_courant'  => $caisse->montant_ouverture + $totalVentes,
            ];
        }

        return view('livewire.compagnie.caisse.gestion-caisse', compact('caisse', 'stats'));
    }
}
