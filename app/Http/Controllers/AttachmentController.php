<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    // Website: the logged-in user's access is checked
    public function show(Request $request, Attachment $attachment)
    {
        abort_unless($attachment->canBeSeenBy($request->user()), 404);

        return $this->serve($attachment, $request->boolean('view'));
    }

    // Phone app: opened from a signed link that expires after 10 minutes
    public function signed(Request $request, Attachment $attachment)
    {
        return $this->serve($attachment, true);
    }

    private function serve(Attachment $attachment, bool $inline)
    {
        $disk = Storage::disk('local');
        abort_unless($disk->exists($attachment->path), 404);

        $headers = ['X-Content-Type-Options' => 'nosniff'];

        if ($inline && $attachment->isViewable()) {
            return $disk->response(
                $attachment->path,
                $attachment->original_name,
                $headers + ['Content-Type' => $attachment->mime],
                'inline'
            );
        }

        return $disk->download($attachment->path, $attachment->original_name, $headers);
    }
}