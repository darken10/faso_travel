<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Services\V2\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function __construct(protected ConversationService $service)
    {
    }

    /**
     * GET /v2/conversations
     * Liste des conversations de l'utilisateur authentifié.
     */
    public function index(): JsonResponse
    {
        $conversations = $this->service->getUserConversations();

        return response()->json(['data' => $conversations]);
    }

    /**
     * POST /v2/conversations
     * Crée ou retrouve une conversation (compagnie ou support).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'compagnie_id' => 'required_if:type,company|nullable|exists:compagnies,id',
            'type'         => 'nullable|in:company,support',
        ]);

        $conversation = $this->service->findOrCreate($validated);

        return response()->json(['data' => $conversation->load('compagnie:id,name,logo_uri')], 201);
    }

    /**
     * GET /v2/conversations/{id}/messages
     * Messages paginés (cursor) d'une conversation.
     */
    public function messages(Request $request, string $id): JsonResponse
    {
        $cursor   = $request->query('cursor');
        $paginator = $this->service->getMessages($id, $cursor ?: null);

        return response()->json([
            'data'        => $paginator->items(),
            'next_cursor' => $paginator->nextCursor()?->encode(),
        ]);
    }

    /**
     * POST /v2/conversations/{id}/messages
     * Envoie un message et diffuse via Reverb.
     */
    public function sendMessage(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $message = $this->service->sendMessage($id, $validated['message']);

        return response()->json(['data' => $message], 201);
    }

    /**
     * PATCH /v2/conversations/{id}/read
     * Marque la conversation comme lue pour le client.
     */
    public function markAsRead(string $id): JsonResponse
    {
        $this->service->markAsRead($id);

        return response()->json(['success' => true]);
    }

    /**
     * DELETE /v2/conversations/{id}
     * Archive (soft delete) une conversation.
     */
    public function destroy(string $id): JsonResponse
    {
        $this->service->archive($id);

        return response()->json(['success' => true]);
    }
}
