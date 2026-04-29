<?php

namespace App\Services\V2;

use PgSql\Lob;
use App\Models\Post\Like;
use App\Models\Post\Post;
use App\Models\Post\Comment;
use App\Models\Post\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Resources\V2\PostResource\PostResource;

class ArticleService
{
    /**
     * Get all articles with pagination
     *
     * @param int $perPage
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getAllArticles(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Post::with(['user', 'category', 'comments', 'likes', 'tags']);
        
        // Appliquer les filtres
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        
        if (isset($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('content', 'like', '%' . $filters['search'] . '%');
            });
        }
        
        $paginator = $query->orderBy('created_at', 'desc')->paginate($perPage);
        $paginator->getCollection()->transform(function ($article) {
            return PostResource::make($article);
        });
        return $paginator;
    }
    
    /**
     * Get article by ID
     *
     * @param int $id
     * @return Post
     */
    public function getArticleById(int $id)
    {
        return Post::with(['user', 'category', 'comments.user', 'likes.user', 'tags'])
            ->findOrFail($id);
    }

    
    /**
     * Toggle like on an article
     *
     * @param int $articleId
     * @return array
     */
    public function toggleLike(int $articleId): array
    {
        $article = Post::findOrFail($articleId);
        $userId = Auth::id();
        
        $existingLike = Like::where('post_id', $articleId)
            ->where('user_id', $userId)
            ->first();
        
        if ($existingLike) {
            $existingLike->delete();
            $action = 'unliked';
        } else {
            $like = new Like();
            $like->post_id = $articleId;
            $like->user_id = $userId;
            $like->save();
            $action = 'liked';
        }
        
        $likesCount = Like::where('post_id', $articleId)->count();
        
        return [
            'action' => $action,
            'likes_count' => $likesCount
        ];
    }
    
    /**
     * Add comment to an article
     *
     * @param int $articleId
     * @param string $content
     * @return Comment
     */
    public function addComment(int $articleId, string $content): Comment
    {
        $article = Post::findOrFail($articleId);
        $comment = new Comment([
            'user_id' => Auth::id(),
            'message' => $content,
        ]);
        $article->comments()->save($comment);
        return $comment->load('user');
    }
    
    /**
     * Delete a comment
     *
     * @param int $commentId
     * @return bool
     */
    public function deleteComment(int $commentId): bool
    {
        $comment = Comment::findOrFail($commentId);
        
        // Vérifier que l'utilisateur est le propriétaire du commentaire ou de l'article
        $article = Post::findOrFail($comment->commentable_id);
        if (!$article) {
            throw new \Exception('Article not found');
        }
        
        if ($comment->user_id !== Auth::id() && $article->user_id !== Auth::id()) {
            throw new \Exception('Vous n\'êtes pas autorisé à supprimer ce commentaire');
        }
        Log::info("Comment with ID: $commentId deleted successfully by user ID: " . Auth::id());
        
        return $comment->delete();
    }
    
    /**
     * Get similar articles (same category or shared tags)
     *
     * @param int $articleId
     * @param int $limit
     * @return Collection
     */
    public function getSimilarArticles(int $articleId, int $limit = 4): Collection
    {
        $article = Post::with(['category', 'tags'])->findOrFail($articleId);
        $tagIds  = $article->tags->pluck('id');

        return Post::with(['user', 'category', 'likes', 'comments', 'tags'])
            ->where('id', '!=', $articleId)
            ->where(function ($q) use ($article, $tagIds) {
                $q->where('category_id', $article->category_id)
                  ->orWhereHas('tags', fn($tq) => $tq->whereIn('tags.id', $tagIds));
            })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get all article categories
     *
     * @return Collection
     */
    public function getCategories(): Collection
    {
        return Category::all();
    }
    
    

}
