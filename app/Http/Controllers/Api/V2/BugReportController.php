<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Services\V2\BugReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BugReportController extends Controller
{
    public function __construct(protected BugReportService $service)
    {
    }

    /**
     * POST /v2/bug-reports
     * Soumet un rapport de bug ou une suggestion.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category'    => 'required|in:bug,payment,ticket,suggestion,other',
            'title'       => 'required|string|max:100',
            'description' => 'required|string|max:2000',
            'screenshot'  => 'nullable|image|max:5120', // 5 Mo max
        ]);

        $report = $this->service->create(
            $validated['category'],
            $validated['title'],
            $validated['description'],
            $request->file('screenshot'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Votre rapport a bien été envoyé. Merci !',
            'data'    => ['id' => $report->id],
        ], 201);
    }
}
