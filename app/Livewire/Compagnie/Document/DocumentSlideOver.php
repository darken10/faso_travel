<?php

namespace App\Livewire\Compagnie\Document;

use App\Models\Document;
use App\Models\DocumentRappel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class DocumentSlideOver extends Component
{
    use WithFileUploads;

    public bool    $open           = false;
    public string  $entityType     = '';
    public string  $entityId       = '';
    public string  $entityLabel    = '';
    public string  $entityTypeName = '';
    public bool    $showForm       = false;
    public ?int    $editingDocId   = null;

    public string  $titre           = '';
    public string  $description     = '';
    public bool    $has_expiration  = false;
    public string  $date_expiration = '';
    public         $fichier         = null;
    public ?string $existingFilePath = null;
    public ?string $existingFileName = null;
    public array   $rappels          = [];

    #[On('open-doc-panel')]
    public function openPanel(string $type, string $id, string $label, string $typeName = ''): void
    {
        $this->entityType     = $type;
        $this->entityId       = $id;
        $this->entityLabel    = $label;
        $this->entityTypeName = $typeName;
        $this->showForm       = false;
        $this->resetDocForm();
        $this->open = true;
    }

    public function close(): void
    {
        $this->open     = false;
        $this->showForm = false;
    }

    public function openAddForm(): void
    {
        $this->resetDocForm();
        $this->showForm = true;
    }

    public function openEditForm(int $id): void
    {
        $doc = Document::with('rappels')->findOrFail($id);
        $this->editingDocId     = $id;
        $this->titre            = $doc->titre;
        $this->description      = $doc->description ?? '';
        $this->has_expiration   = $doc->has_expiration;
        $this->date_expiration  = $doc->date_expiration?->format('Y-m-d') ?? '';
        $this->fichier          = null;
        $this->existingFilePath = $doc->file_path;
        $this->existingFileName = $doc->file_name;
        $this->rappels          = $doc->rappels->map(fn($r) => [
            'delai_valeur' => $r->delai_valeur,
            'delai_unite'  => $r->delai_unite,
            'canaux'       => $r->canaux,
        ])->toArray();
        $this->showForm = true;
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->resetDocForm();
    }

    private function resetDocForm(): void
    {
        $this->editingDocId     = null;
        $this->titre            = '';
        $this->description      = '';
        $this->has_expiration   = false;
        $this->date_expiration  = '';
        $this->fichier          = null;
        $this->existingFilePath = null;
        $this->existingFileName = null;
        $this->rappels          = [];
    }

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

    public function save(): void
    {
        $this->validate([
            'titre'                  => 'required|string|max:255',
            'description'            => 'nullable|string|max:1000',
            'has_expiration'         => 'boolean',
            'date_expiration'        => 'nullable|date|required_if:has_expiration,true',
            'fichier'                => $this->editingDocId ? 'nullable|file|max:10240' : 'required|file|max:10240',
            'rappels.*.delai_valeur' => 'required|integer|min:0',
            'rappels.*.delai_unite'  => 'required|in:jours,heures',
            'rappels.*.canaux'       => 'required|array|min:1',
            'rappels.*.canaux.*'     => 'in:email,sms,whatsapp,telegram',
        ]);

        $data = [
            'documentable_type' => $this->entityType,
            'documentable_id'   => $this->entityId,
            'compagnie_id'      => Auth::user()->compagnie_id,
            'titre'             => $this->titre,
            'description'       => $this->description ?: null,
            'has_expiration'    => $this->has_expiration,
            'date_expiration'   => $this->has_expiration && $this->date_expiration ? $this->date_expiration : null,
        ];

        if ($this->fichier) {
            if ($this->editingDocId && $this->existingFilePath) {
                Storage::disk('public')->delete($this->existingFilePath);
            }
            $data['file_path'] = $this->fichier->store('documents', 'public');
            $data['file_name'] = $this->fichier->getClientOriginalName();
            $data['file_size'] = $this->fichier->getSize();
            $data['mime_type'] = $this->fichier->getMimeType();
        }

        if ($this->editingDocId) {
            $doc = Document::findOrFail($this->editingDocId);
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

        $this->showForm = false;
        $this->resetDocForm();
        $this->dispatch('doc-panel-saved');
    }

    public function deleteDoc(int $id): void
    {
        $doc = Document::findOrFail($id);
        if ($doc->file_path) {
            Storage::disk('public')->delete($doc->file_path);
        }
        $doc->delete();
        $this->dispatch('doc-panel-saved');
    }

    public function render()
    {
        $documents = collect();
        if ($this->open && $this->entityType && $this->entityId) {
            $documents = Document::with('rappels')
                ->where('documentable_type', $this->entityType)
                ->where('documentable_id', $this->entityId)
                ->latest()
                ->get();
        }

        return view('livewire.compagnie.document.document-slide-over', compact('documents'));
    }
}
