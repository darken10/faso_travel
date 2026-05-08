<?php

namespace App\Livewire\Compagnie\Post;

use App\Models\Post\Category;
use App\Models\Post\Post;
use App\Models\Post\Tag;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.compagnie-panel')]
class PostForm extends Component
{
    use WithFileUploads;

    public ?int $postId = null;

    public string $title   = '';
    public string $content = '';
    public ?int   $category_id = null;
    public array  $selectedTags  = [];
    public array  $images        = [];
    public array  $existingImages = [];

    public string $newCategoryName = '';
    public string $newTagName      = '';
    public bool   $showCategoryForm = false;
    public bool   $showTagForm      = false;

    public function mount(?int $postId = null): void
    {
        $this->postId = $postId;

        if ($postId) {
            $post = Post::withoutGlobalScopes()->findOrFail($postId);
            $this->title          = $post->title;
            $this->content        = $post->content ?? '';
            $this->category_id    = $post->category_id;
            $this->selectedTags   = $post->tags()->pluck('tags.id')->toArray();
            $this->existingImages = $post->images_uri ?? [];
        }
    }

    protected function rules(): array
    {
        return [
            'title'        => 'required|string|max:255',
            'content'      => 'required|string',
            'category_id'  => 'nullable|exists:categories,id',
            'selectedTags' => 'array',
            'images.*'     => 'nullable|image|max:5120',
        ];
    }

    public function addCategory(): void
    {
        $this->validate(['newCategoryName' => 'required|string|max:100|unique:categories,name']);
        $cat = Category::create(['name' => trim($this->newCategoryName)]);
        $this->category_id    = $cat->id;
        $this->newCategoryName = '';
        $this->showCategoryForm = false;
    }

    public function addTag(): void
    {
        $this->validate(['newTagName' => 'required|string|max:100|unique:tags,name']);
        $tag = Tag::create(['name' => trim($this->newTagName)]);
        $this->selectedTags[] = $tag->id;
        $this->newTagName  = '';
        $this->showTagForm = false;
    }

    public function removeExistingImage(int $index): void
    {
        unset($this->existingImages[$index]);
        $this->existingImages = array_values($this->existingImages);
    }

    public function save(): void
    {
        $this->validate();

        $newUris = [];
        foreach ($this->images as $image) {
            $newUris[] = $image->store('posts', 'public');
        }

        $allImages = array_merge($this->existingImages, $newUris);

        if ($this->postId) {
            $post = Post::withoutGlobalScopes()->findOrFail($this->postId);
            $post->update([
                'title'       => $this->title,
                'content'     => $this->content,
                'category_id' => $this->category_id,
                'images_uri'  => $allImages ?: null,
            ]);
        } else {
            $post = Post::create([
                'title'       => $this->title,
                'content'     => $this->content,
                'category_id' => $this->category_id,
                'images_uri'  => $allImages ?: null,
            ]);
        }

        $post->tags()->sync($this->selectedTags);

        session()->flash('success', $this->postId ? 'Article mis à jour.' : 'Article publié avec succès !');
        $this->redirect(route('panel.compagnie.posts'));
    }

    public function render()
    {
        return view('livewire.compagnie.post.post-form', [
            'categories' => Category::orderBy('name')->get(),
            'allTags'    => Tag::orderBy('name')->get(),
        ]);
    }
}
