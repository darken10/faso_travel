<?php

namespace App\Services\V2;

use App\Mail\BugReportSubmitted;
use App\Models\BugReport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class BugReportService
{
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

        $report = BugReport::create([
            'user_id'        => Auth::id(),
            'category'       => $category,
            'title'          => $title,
            'description'    => $description,
            'screenshot_url' => $screenshotUrl,
            'status'         => 'open',
        ]);

        $report->load('user');

        $supportEmail = config('mail.support_email', env('SUPPORT_EMAIL', 'support@liptra.net'));

        Mail::to($supportEmail)->send(new BugReportSubmitted($report));

        return $report;
    }
}
