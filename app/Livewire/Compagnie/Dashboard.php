<?php

namespace App\Livewire\Compagnie;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.compagnie-panel')]
class Dashboard extends Component
{
    public function render()
    {
        /** @var User $user */
        $user = Auth::user();

        // Le tableau de bord ne charge pas de statistiques globales : chaque carte est
        // filtrée par la même permission que la route vers laquelle elle mène.
        $actions = collect([
            ['guichet.ticket.sell', 'panel.compagnie.vente-ticket', 'Vente de tickets', 'Créer une vente au guichet.', 'blue', 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z'],
            ['guichet.ticket.view.gare', 'panel.compagnie.tickets', 'Tickets', 'Consulter les tickets de votre périmètre.', 'indigo', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012 2h2a2 2 0 00-2-2'],
            ['caisse.session.view.own', 'panel.compagnie.caisse', 'Ma caisse', 'Ouvrir ou suivre votre session de caisse.', 'emerald', 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
            ['caisse.historique.view', 'panel.compagnie.caisses-historique', 'Historique des caisses', 'Suivre les sessions et les écarts.', 'emerald', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['voyage.instance.view', 'panel.compagnie.instances', 'Départs programmés', 'Consulter les départs à venir.', 'amber', 'M4 6h16M4 10h16M4 14h16M4 18h16'],
            ['reseau.chauffeur.view', 'panel.compagnie.chauffeurs', 'Chauffeurs', 'Suivre les chauffeurs et affectations.', 'violet', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
            ['compagnie.user.view', 'panel.compagnie.users', 'Équipe', 'Consulter les collaborateurs.', 'violet', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857'],
            ['rh.payroll.view', 'panel.compagnie.bulletins-paie', 'Bulletins de paie', 'Accéder aux bulletins du personnel.', 'emerald', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
            ['rh.leave.view', 'panel.compagnie.absences-conges', 'Absences et congés', 'Suivre les absences et demandes de congé.', 'violet', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
            ['finance.depense.view', 'panel.compagnie.depenses', 'Dépenses', 'Consulter les dépenses autorisées.', 'rose', 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6'],
            ['finance.rapport.view', 'panel.compagnie.rapports', 'Rapports', 'Accéder aux rapports de votre périmètre.', 'amber', 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
            ['crm.conversation.view', 'panel.compagnie.messages', 'Messages clients', 'Traiter les échanges clients.', 'blue', 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
            ['reseau.document.view', 'panel.compagnie.documents', 'Documents', 'Consulter les documents autorisés.', 'slate', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        ])->filter(fn (array $action): bool => $user->hasPermission($action[0]))
            ->map(fn (array $action): array => [
                'route' => $action[1], 'label' => $action[2], 'description' => $action[3],
                'color' => $action[4], 'icon' => $action[5],
            ])->values();

        return view('livewire.compagnie.dashboard', [
            'actions' => $actions,
            'roles' => $user->roles()->orderByDesc('rang')->get(['name', 'label']),
        ]);
    }
}
