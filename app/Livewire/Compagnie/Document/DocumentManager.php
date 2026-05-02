<?php

namespace App\Livewire\Compagnie\Document;

use App\Models\Compagnie\Care;
use App\Models\Compagnie\Chauffer;
use App\Models\Compagnie\Gare;
use App\Models\Document;
use App\Models\DocumentRappel;
use App\Models\Finance\Depense;
use App\Models\Finance\Recette;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.compagnie-panel')]
class DocumentManager extends Component
{
    use WithPagination, WithFileUploads;

    // ── Filtres liste ─────────────────────────────────────────────────────────
    public string $search        = '';
    public string $filterType    = '';
    public string $filterStatut  = '';

    // ── Modal état ────────────────────────────────────────────────────────────
    public bool   $showModal  = false;
    public ?int   $editingId  = null;

    // ── Champs formulaire ─────────────────────────────────────────────────────
    public string  $titre              = '';
    public string  $description        = '';
    public string  $documentable_type  = '';
    public string  $documentable_id    = '';
    public bool    $has_expiration     = false;
    public string  $date_expiration    = '';
    public         $fichier            = null;
    public ?string $existingFilePath   = null;
    public ?string $existingFileName   = null;
    public array   $rappels            = [];

    // ── Types d'entités disponibles ───────────────────────────────────────────
    public const ENTITY_TYPES = [
        'App\\Models\\Compagnie\\Chauffer' => 'Chauffeur',
        'App\\Models\\Compagnie\\Care'     => 'Véhicule',
        'App\\Models\\Compagnie\\Gare'     => 'Gare',
        'App\\Models\\Finance\\Depense'    => 'Dépense',
        'App\\Models\\Finance\\Recette'    => 'Recette',
    ];

    // ── Réactivité ────────────────────────────────────────────────────────────

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatut(): void
    {
        $this->resetPage();
    }

    public function updatedDocumentableType(): void
    {
        $this->documentable_id = '';
    }

    // ── Rappels dynamiques ────────────────────────────────────────────────────

    public function addRappel(): void
    {
        $this->rappels[] = ['delai_valeur' => 7, 'delai_unite' => 'jours', 'canaux' => ['email']];
    }

    public function removeRappel(int $index): void
    {
        array_splice($this->rappels, $index, 1);
        $this->rappels = array_values($this->rappels);
    }

    public function toggleCanal(int $index, string $canal): void
    {
        $canaux = $this->rappels[$index]['canaux'] ?? [];
        if (in_array($canal, $canaux)) {
            $canaux = array_values(array_filter($canaux, fn($c) => $c !== $canal));
        } else {
            $canaux[] = $canal;
        }
        $this->rappels[$index]['canaux'] = $canaux;
    }

    // ── CRUD ─────────────────────────────────────────────────────────────────

    public function openCreate(): void
    {
        $this->reset([
            'editingId', 'titre', 'description', 'documentable_type', 'documentable_id',
            'has_expiration', 'date_expiration', 'fichier', 'existingFilePath', 'existingFileName',
        ]);
        $this->rappels = [];
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $doc = Document::with('rappels')->findOrFail($id);

        $this->editingId          = $id;
        $this->titre              = $doc->titre;
        $this->description        = $doc->description ?? '';
        $this->documentable_type  = $doc->documentable_type;
        $this->documentable_id    = (string) $doc->documentable_id;
        $this->has_expiration     = $doc->has_expiration;
        $this->date_expiration    = $doc->date_expiration?->format('Y-m-d') ?? '';
        $this->fichier            = null;
        $this->existingFilePath   = $doc->file_path;
        $this->existingFileName   = $doc->file_name;
        $this->rappels            = $doc->rappels->map(fn($r) => [
            'delai_valeur' => $r->delai_valeur,
            'delai_unite'  => $r->delai_unite,
            'canaux'       => $r->canaux,
        ])->toArray();
        $this->showModal = true;
    }

    public function save(): void
    {
        $rules = [
            'titre'             => 'required|string|max:255',
            'description'       => 'nullable|string|max:1000',
            'documentable_type' => 'required|string',
            'documentable_id'   => 'required|string',
            'has_expiration'    => 'boolean',
            'date_expiration'   => 'nullable|date|required_if:has_expiration,true',
            'fichier'           => $this->editingId ? 'nullable|file|max:10240' : 'required|file|max:10240',
            'rappels.*.delai_valeur' => 'required|integer|min:0',
            'rappels.*.delai_unite'  => 'required|in:jours,heures',
            'rappels.*.canaux'       => 'required|array|min:1',
            'rappels.*.canaux.*'     => 'in:email,sms,whatsapp,telegram',
        ];

        $this->validate($rules);

        $compagnieId = Auth::user()->compagnie_id;

        $data = [
            'documentable_type' => $this->documentable_type,
            'documentable_id'   => $this->documentable_id,
            'compagnie_id'      => $compagnieId,
            'titre'             => $this->titre,
            'description'       => $this->description ?: null,
            'has_expiration'    => $this->has_expiration,
            'date_expiration'   => $this->has_expiration && $this->date_expiration ? $this->date_expiration : null,
        ];

        if ($this->fichier) {
            if ($this->editingId && $this->existingFilePath) {
                Storage::disk('public')->delete($this->existingFilePath);
            }
            $data['file_path'] = $this->fichier->store('documents', 'public');
            $data['file_name'] = $this->fichier->getClientOriginalName();
            $data['file_size'] = $this->fichier->getSize();
            $data['mime_type'] = $this->fichier->getMimeType();
        }

        if ($this->editingId) {
            $doc = Document::findOrFail($this->editingId);
            $doc->update($data);
            $doc->rappels()->delete();
        } else {
            $doc = Document::create($data);
        }

        foreach ($this->rappels as $rappel) {
            DocumentRappel::create([
                'document_id'  => $doc->id,
                'delai_valeur' => $rappel['delai_valeur'],
                'delai_unite'  => $rappel['delai_unite'],
                'canaux'       => $rappel['canaux'],
            ]);
        }

        session()->flash('success', $this->editingId ? 'Document mis à jour.' : 'Document ajouté.');
        $this->showModal = false;
        $this->reset([
            'editingId', 'titre', 'description', 'documentable_type', 'documentable_id',
            'has_expiration', 'date_expiration', 'fichier', 'existingFilePath', 'existingFileName',
        ]);
        $this->rappels = [];
    }

    public function delete(int $id): void
    {
        $doc = Document::findOrFail($id);
        Storage::disk('public')->delete($doc->file_path);
        $doc->delete();
        session()->flash('success', 'Document supprimé.');
    }

    // ── Données pour le formulaire ────────────────────────────────────────────

    public function getEntitiesProperty(): array
    {
        if (!$this->documentable_type) return [];
        $cid = Auth::user()->compagnie_id;

        return match ($this->documentable_type) {
            Chauffer::class => Chauffer::where('compagnie_id', $cid)->get(['id', 'first_name', 'last_name'])
                ->map(fn($c) => ['id' => $c->id, 'label' => $c->fullName()])->toArray(),

            Care::class => Care::withoutGlobalScopes()->where('compagnie_id', $cid)->get(['id', 'immatrculation'])
                ->map(fn($c) => ['id' => $c->id, 'label' => $c->immatrculation])->toArray(),

            Gare::class => Gare::withoutGlobalScopes()->where('compagnie_id', $cid)->get(['id', 'name'])
                ->map(fn($g) => ['id' => $g->id, 'label' => $g->name])->toArray(),

            Depense::class => Depense::withoutGlobalScopes()->where('compagnie_id', $cid)
                ->latest()->limit(100)->get(['id', 'libelle', 'montant'])
                ->map(fn($d) => ['id' => $d->id, 'label' => $d->libelle . ' · ' . number_format($d->montant, 0, ',', ' ') . ' F'])->toArray(),

            Recette::class => Recette::withoutGlobalScopes()->where('compagnie_id', $cid)
                ->latest()->limit(100)->get(['id', 'libelle', 'montant'])
                ->map(fn($r) => ['id' => $r->id, 'label' => $r->libelle . ' · ' . number_format($r->montant, 0, ',', ' ') . ' F'])->toArray(),

            default => [],
        };
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        $compagnieId = Auth::user()->compagnie_id;
        $today       = now()->startOfDay();

        $documents = Document::with(['rappels'])
            ->where('compagnie_id', $compagnieId)
            ->when($this->search, fn($q) => $q->where('titre', 'like', "%{$this->search}%"))
            ->when($this->filterType, fn($q) => $q->where('documentable_type', $this->filterType))
            ->when($this->filterStatut === 'expire', fn($q) => $q
                ->where('has_expiration', true)
                ->whereNotNull('date_expiration')
                ->where('date_expiration', '<', $today))
            ->when($this->filterStatut === 'expire_bientot', fn($q) => $q
                ->where('has_expiration', true)
                ->whereNotNull('date_expiration')
                ->whereBetween('date_expiration', [$today, (clone $today)->addDays(7)]))
            ->when($this->filterStatut === 'valide', fn($q) => $q
                ->where(fn($s) => $s
                    ->where('has_expiration', false)
                    ->orWhereNull('date_expiration')
                    ->orWhere('date_expiration', '>', (clone $today)->addDays(7))))
            ->latest()
            ->paginate(12);

        return view('livewire.compagnie.document.document-manager', [
            'documents'   => $documents,
            'entityTypes' => self::ENTITY_TYPES,
        ]);
    }
}
