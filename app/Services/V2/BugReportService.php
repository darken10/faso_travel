<?php

namespace App\Services\V2;

use App\Models\BugReport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BugReportService
{
    /**
     * Enregistre un rapport de bug soumis par l'utilisateur.
     */
    public function create(
        string $category,
        string $title,
        string $description,
        ?UploadedFile $screenshot = null
    ): BugReport {
        $screenshotUrl = null;

        if ($screenshot) {
            $path          = $screenshot->store('bug-reports', 'public');
            $screenshotUrl = Storage::url($path);
        }

        return BugReport::create([
            'user_id'        => Auth::id(),
            'category'       => $category,
            'title'          => $title,
            'description'    => $description,
            'screenshot_url' => $screenshotUrl,
            'status'         => 'open',
        ]);
    }
}
