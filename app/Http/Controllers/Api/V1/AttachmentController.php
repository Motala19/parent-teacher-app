<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class AttachmentController extends Controller
{
    public function link(Request $request, Attachment $attachment)
    {
        abort_unless($attachment->canBeSeenBy($request->user()), 404);

        return response()->json([
            'url' => URL::temporarySignedRoute('files.signed', now()->addMinutes(10), ['attachment' => $attachment->id]),
        ]);
    }
}