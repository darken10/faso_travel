<?php

namespace App\Livewire\Compagnie\Post;

use App\Models\Post\Post;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.compagnie-panel')]
class PostManager extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void { $this->resetPage(); }

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

        return view('livewire.compagnie.post.post-manager', compact('posts'));
    }
}
