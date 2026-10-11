<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function show(Request $request, Attachment $attachment)
    {
        abort_unless($attachment->canBeSeenBy($request->user()), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($attachment->path), 404);

        $headers = ['X-Content-Type-Options' => 'nosniff'];

        // PDFs and images can be opened in the browser
        if ($request->boolean('view') && $attachment->isViewable()) {
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