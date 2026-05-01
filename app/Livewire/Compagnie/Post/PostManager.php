<?php

namespace App\Livewire\Compagnie\Post;

use App\Models\Post\Category;
use App\Models\Post\Post;
use App\Models\Post\Tag;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.compagnie-panel')]
class PostManager extends Component
{
    use WithPagination;
    use WithFileUploads;

    public string $search = '';
    public bool $showModal = false;
    public ?int $editingId = null;

    public string $title = '';
    public string $content = '';
    public ?int $category_id = null;
    public array $selectedTags = [];
    public $images = [];

    protected function rules(): array
    {
        return [
            'title'       => 'required|string|max:255',
            'content'     => 'required|string',
            'category_id' => 'nullable|exists:post_categories,id',
            'selectedTags' => 'array',
            'images.*'    => 'nullable|image|max:2048',
        ];
    }

    public function updatedSearch(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'title', 'content', 'category_id', 'selectedTags', 'images']);
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $post = Post::withoutGlobalScopes()->findOrFail($id);
        $this->editingId = $id;
        $this->title = $post->title;
        $this->content = $post->content ?? '';
        $this->category_id = $post->category_id;
        $this->selectedTags = $post->tags()->pluck('tags.id')->toArray();
        $this->images = [];
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $imagesUris = [];
        if ($this->images) {
            foreach ($this->images as $image) {
                $imagesUris[] = $image->store('posts', 'public');
            }
        }

        if ($this->editingId) {
            $post = Post::withoutGlobalScopes()->findOrFail($this->editingId);
            $post->update([
                'title'       => $this->title,
                'content'     => $this->content,
                'category_id' => $this->category_id,
                'images_uri'  => !empty($imagesUris) ? $imagesUris : $post->images_uri,
            ]);
        } else {
            $post = Post::create([
                'title'       => $this->title,
                'content'     => $this->content,
                'category_id' => $this->category_id,
                'images_uri'  => $imagesUris ?: null,
            ]);
        }

        if ($this->selectedTags) {
            $post->tags()->sync($this->selectedTags);
        }

        $this->showModal = false;
        session()->flash('success', $this->editingId ? 'Article mis à jour.' : 'Article créé.');
        $this->reset(['editingId', 'title', 'content', 'category_id', 'selectedTags', 'images']);
    }

    public function delete(int $id): void
    {
        Post::withoutGlobalScopes()->findOrFail($id)->delete();
        session()->flash('success', 'Article supprimé.');
    }

    public function render()
    {
        $compagnieId = Auth::user()->compagnie_id;
        $userIds = \App\Models\User::where('compagnie_id', $compagnieId)->pluck('id');

        $posts = Post::withoutGlobalScopes()
            ->whereIn('user_id', $userIds)
            ->when($this->search, fn ($q) => $q->where('title', 'like', '%' . $this->search . '%'))
            ->with(['category', 'tags'])
            ->latest()
            ->paginate(12);

        $categories = Category::orderBy('name')->get();
        $allTags = Tag::orderBy('name')->get();

        return view('livewire.compagnie.post.post-manager', compact('posts', 'categories', 'allTags'));
    }
}
