<?php

namespace App\Filament\Compagnie\Pages;

use App\Enums\StatutCaisse;
use App\Models\Finance\Caisse;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class GestionCaisse extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationGroup = 'Guichet';
    protected static ?string $title = 'Ma Caisse';
    protected static ?string $navigationLabel = 'Ma Caisse';
    protected static ?int $navigationSort = 0;
    protected static string $view = 'filament.compagnie.pages.gestion-caisse';

    public ?array $ouvertureData = [];
    public ?array $fermetureData = [];

    public function mount(): void
    {
        $this->ouvertureForm->fill();
        $this->fermetureForm->fill();
    }

    public function getCaisseOuverte(): ?Caisse
    {
        return Caisse::sessionOuverte();
    }

    protected function getForms(): array
    {
        return [
            'ouvertureForm',
            'fermetureForm',
        ];
    }

    public function ouvertureForm(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('montant_ouverture')
                    ->label('Montant d\'ouverture (F CFA)')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->default(0)
                    ->suffix('F CFA')
                    ->placeholder('0'),

                Textarea::make('note_ouverture')
                    ->label('Note (facultatif)')
                    ->rows(2)
                    ->placeholder('Ex: Fond de caisse du matin...'),
            ])
            ->statePath('ouvertureData');
    }

    public function fermetureForm(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('montant_fermeture')
                    ->label('Montant en caisse à la fermeture (F CFA)')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->suffix('F CFA')
                    ->placeholder('Comptez l\'argent en caisse...'),

                Textarea::make('note_fermeture')
                    ->label('Note de fermeture (facultatif)')
                    ->rows(2)
                    ->placeholder('Ex: RAS, tout est en ordre...'),
            ])
            ->statePath('fermetureData');
    }

    public function ouvrirCaisse(): void
    {
        $existing = Caisse::sessionOuverte();
        if ($existing) {
            Notification::make()
                ->title('Caisse déjà ouverte')
                ->body('Vous avez déjà une caisse ouverte. Fermez-la d\'abord.')
                ->warning()
                ->send();
            return;
        }

        $compagnieId = Auth::user()->compagnie_id;
        if (!$compagnieId) {
            Notification::make()
                ->title('Compte non lié à une compagnie')
                ->body('Votre compte n\'est pas associé à une compagnie. Contactez un administrateur.')
                ->danger()
                ->send();
            return;
        }

        $data = $this->ouvertureForm->getState();

        Caisse::create([
            'user_id' => Auth::id(),
            'compagnie_id' => $compagnieId,
            'montant_ouverture' => (int) ($data['montant_ouverture'] ?? 0),
            'statut' => StatutCaisse::Ouverte->value,
            'opened_at' => now(),
            'note_ouverture' => $data['note_ouverture'] ?? null,
        ]);

        Notification::make()
            ->title('Caisse ouverte !')
            ->body('Votre caisse est maintenant ouverte. Vous pouvez commencer les ventes.')
            ->success()
            ->send();

        $this->ouvertureForm->fill();
    }

    public function fermerCaisse(): void
    {
        $caisse = Caisse::sessionOuverte();

        if (!$caisse) {
            Notification::make()
                ->title('Aucune caisse ouverte')
                ->body('Vous n\'avez pas de caisse ouverte à fermer.')
                ->danger()
                ->send();
            return;
        }

        $data = $this->fermetureForm->getState();

        $montantAttendu = $caisse->calculerMontantAttendu();

        $caisse->update([
            'montant_fermeture' => (int) ($data['montant_fermeture'] ?? 0),
            'montant_attendu' => $montantAttendu,
            'statut' => StatutCaisse::Fermee->value,
            'closed_at' => now(),
            'note_fermeture' => $data['note_fermeture'] ?? null,
        ]);

        Notification::make()
            ->title('Caisse fermée !')
            ->body('Votre caisse a été fermée avec succès.')
            ->success()
            ->send();

        $this->fermetureForm->fill();
    }

    public function getStatsProperty(): array
    {
        $caisse = $this->getCaisseOuverte();

        if (!$caisse) {
            return [
                'total_ventes' => 0,
                'nombre_tickets' => 0,
                'montant_ouverture' => 0,
                'montant_courant' => 0,
            ];
        }

        $totalVentes = $caisse->totalVentes();

        return [
            'total_ventes' => $totalVentes,
            'nombre_tickets' => $caisse->nombreTickets(),
            'montant_ouverture' => $caisse->montant_ouverture,
            'montant_courant' => $caisse->montant_ouverture + $totalVentes,
        ];
    }
}
