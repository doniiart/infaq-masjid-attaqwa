<?php

namespace App\Http\Controllers;

use App\Models\TransactionAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionAttachmentController extends Controller
{
    public function show(Request $request, TransactionAttachment $attachment): StreamedResponse
    {
        if (! $attachment->is_public) {
            $user = $request->user();
            if (! $user || ! $user->is_active) {
                abort(403, 'Anda tidak memiliki izin mengakses lampiran privat ini.');
            }
        }

        if (! Storage::disk('local')->exists($attachment->file_path)) {
            abort(404, 'File lampiran tidak ditemukan.');
        }

        return Storage::disk('local')->response($attachment->file_path, $attachment->file_name);
    }
}