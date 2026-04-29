<?php

namespace App\Http\Controllers\Api\V2;

use Illuminate\Http\Request;
use App\Models\Post\Category;
use Illuminate\Http\JsonResponse;
use App\Services\V2\ArticleService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Http\Resources\CategoryResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ApiV2\PostListResource;
use App\Http\Resources\ApiV2\PostShowResource;
use App\Http\Resources\V2\PostResource\PostResource;

class ArticleController extends Controller
{
    protected $articleService;

    public function __construct(ArticleService $articleService)
    {
        $this->articleService = $articleService;
    }

    /**
     * Get all articles
     *
     * @param Request $request
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $filters = [
                'category_id' => $request->get('category_id'),
                'search' => $request->get('search'),
            ];
            $articles = $this->articleService->getAllArticles($perPage, $filters);

            return PostListResource::collection($articles);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Get article by ID
     *
     * @param int $id
     */
    public function show(int $id)
    {
        try {
            $article = $this->articleService->getArticleById($id);

            return PostShowResource::make($article);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'status' => 404
            ], 404);
        }
    }

    /**
     * Create a new article
     *
     * @param Request $request
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'category_id' => 'required|exists:post_categories,id',
                'image' => 'nullable|image|max:2048',
            ]);

           /*  $article = $this->articleService->createArticle(
                $validated,
                $request->hasFile('image') ? $request->file('image') : null
            ); 

            return PostShowResource::make($article);*/
            return ['error'];
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update an article
     *
     * @param Request $request
     * @param int $id
     */
    public function update(Request $request, int $id)
    {
        try {
            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'content' => 'sometimes|string',
                'category_id' => 'sometimes|exists:post_categories,id',
                'image' => 'nullable|image|max:2048',
            ]);

            /* $article = $this->articleService->updateArticle(
                $id,
                $validated,
                $request->hasFile('image') ? $request->file('image') : null
            );

            return PostShowResource::make($article); */
            return ['error'];
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete an article
     *
     * @param int $id
     */
    public function destroy(int $id)
    {
        try {
           /*  $result = $this->articleService->deleteArticle($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Article supprimé avec succès',
            ], 200); */
            return ['error'];
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Toggle like on an article
     *
     * @param int $id
     */
    public function toggleLike(int $id)
    {
        try {
            $result = $this->articleService->toggleLike($id);

            return response()->json([
                'status' => 'success',
                'message' => $result['action'] === 'liked' ? 'Article aimé' : 'Article plus aimé',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get all comments for an article
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getAllComments(int $id): JsonResponse
    {
        try {
            $article = $this->articleService->getArticleById($id);
            
            return response()->json(
                $article->comments()
                    ->with('user:id,name,profile_photo_path')
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function ($comment) {
                        return [
                            'id' => $comment->id,
                            'content' => $comment->message,
                            'created_at' => $comment->created_at->format('Y-m-d H:i:s'),
                            'author' => [
                                'id' => $comment->user->id,
                                'name' => $comment->user->name,
                                'avatar' => $comment->user->profile_photo_path
                            ]
                        ];
                    })
            );
        } catch (\Exception $e) {
            return response()->json([], 404);
        }
    }

    /**
     * Add comment to an article
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function addComment(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
        ], [
            'content.required' => 'Le champ contenu est obligatoire.',
            'content.string' => 'Le champ contenu doit être une chaîne de caractères.',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $comment = $this->articleService->addComment($id, $request->content);

        return response()->json(CommentResource::make($comment), 201);
    }

    /**
     * Delete a comment
     *
     * @param int $commentId
     * @return JsonResponse
     */
    public function deleteComment(int $commentId): JsonResponse
    {
        Log::info("Attempting to delete comment with ID: $commentId");
        try {

            $result = $this->articleService->deleteComment($commentId);

            return response()->json([
                'status' => 'success',
                'message' => 'Commentaire supprimé avec succès',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get similar articles for a given article ID
     *
     * @param int $id
     */
    public function similar(int $id)
    {
        try {
            $articles = $this->articleService->getSimilarArticles($id);

            return PostListResource::collection($articles);
        } catch (\Exception $e) {
            return response()->json([
                'error'   => true,
                'message' => $e->getMessage(),
                'status'  => 404,
            ], 404);
        }
    }

    /**
     * Get all article categories
     *
     */
    public function getCategories()
    {
        try {
            $categories = $this->articleService->getCategories();

            return CategoryResource::collection($categories);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
