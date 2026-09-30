<?php

namespace App\Livewire\Compagnie\Message;

use App\Services\Messages\CompagnieMessagerieService;
use App\Traits\ScopedToCompagnie;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Boîte de messages de la compagnie : lire les messages des clients et leur répondre.
 *
 * Le panneau n'a pas de connexion WebSocket : l'écran se rafraîchit par sondage
 * (wire:poll). Les clients, eux, reçoivent les réponses en temps réel par Reverb.
 */
#[Layout('layouts.compagnie-panel')]
class Messagerie extends Component
{
    use ScopedToCompagnie;

    private const PAGE = 40;
    private const THREAD_LIMIT = 200;

    public string $search = '';
    public string $filtre = 'tous';      // tous | non_lus
    public int $limite = self::PAGE;

    public ?string $selectedId = null;
    public string $reponse = '';

    public function select(string $id, CompagnieMessagerieService $service): void
    {
        // Identifiant reçu du navigateur : recherché dans le périmètre de la compagnie.
        $service->find($id, $this->compagnieId());

        $this->selectedId = $id;
        $this->reponse = '';
        $this->resetValidation();
    }

    public function back(): void
    {
        $this->reset(['selectedId', 'reponse']);
    }

    public function updatedSearch(): void { $this->limite = self::PAGE; }
    public function updatedFiltre(): void { $this->limite = self::PAGE; }

    public function loadMore(): void
    {
        $this->limite += self::PAGE;
    }

    public function send(CompagnieMessagerieService $service): void
    {
        $this->validate([
            'reponse' => ['required', 'string', 'max:' . CompagnieMessagerieService::MAX_LENGTH],
        ], [
            'reponse.required' => 'Écrivez un message avant d\'envoyer.',
            'reponse.max'      => 'Message trop long (' . CompagnieMessagerieService::MAX_LENGTH . ' caractères maximum).',
        ]);

        // Un texte d'espaces passe « required » ; le service le refuse proprement.
        if (trim($this->reponse) === '') {
            $this->addError('reponse', 'Écrivez un message avant d\'envoyer.');
            return;
        }

        abort_if($this->selectedId === null, 422);

        $service->reply($this->selectedId, $this->compagnieId(), Auth::user(), $this->reponse);

        $this->reset('reponse');
        $this->dispatch('toast', type: 'success', message: 'Réponse envoyée.');
    }

    public function render(CompagnieMessagerieService $service): View
    {
        $compagnieId = $this->compagnieId();

        $conversations = $service->conversations($compagnieId)
            ->with('client:id,name,first_name,last_name,profile_photo_path')
            ->when($this->search !== '', function ($q) {
                $terme = '%' . addcslashes($this->search, '\\%_') . '%';
                $q->whereHas('client', fn ($c) => $c->where('name', 'like', $terme));
            })
            ->when($this->filtre === 'non_lus', fn ($q) => $q->where('unread_count_agent', '>', 0))
            ->orderByRaw('last_message_at IS NULL')
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->limit($this->limite + 1)
            ->get();

        $aPlus = $conversations->count() > $this->limite;
        $conversations = $conversations->take($this->limite);

        $selected = null;
        $messages = collect();

        if ($this->selectedId !== null) {
            $selected = $service->conversations($compagnieId)
                ->with('client:id,name,first_name,last_name,profile_photo_path')
                ->find($this->selectedId);

            if ($selected) {
                // Un message arrivé pendant que le fil est ouvert est déjà vu : sans
                // cela le compteur du menu afficherait un non-lu que personne n'a à lire.
                $service->markRead($selected);

                $messages = $selected->messages()
                    ->with('sender:id,name,first_name,last_name')
                    ->orderByDesc('created_at')
                    ->limit(self::THREAD_LIMIT)
                    ->get()
                    ->reverse()
                    ->values();
            } else {
                // Conversation archivée entre-temps.
                $this->selectedId = null;
            }
        }

        return view('livewire.compagnie.message.messagerie', [
            'conversations' => $conversations,
            'aPlus'         => $aPlus,
            'selected'      => $selected,
            'messages'      => $messages,
            'totalNonLus'   => $service->unreadCount($compagnieId),
            'maxLength'     => CompagnieMessagerieService::MAX_LENGTH,
        ]);
    }
}
