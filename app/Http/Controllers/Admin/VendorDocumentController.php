<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VendorDocument;
use Illuminate\Support\Facades\Storage;

class VendorDocumentController extends Controller
{
    /**
     * Preview dokumen di browser (inline)
     */
    public function preview(VendorDocument $document)
    {
        if (!Storage::disk('local')->exists($document->path)) {
            abort(404, 'Dokumen tidak ditemukan.');
        }

        $content = Storage::disk('local')->get($document->path);
        $mime = $document->mime ?? Storage::disk('local')->mimeType($document->path);

        return response($content, 200)
            ->header('Content-Type', $mime)
            ->header('Content-Disposition', 'inline; filename="' . $document->original_name . '"');
    }

    /**
     * Download dokumen
     */
    public function download(VendorDocument $document)
    {
        if (!Storage::disk('local')->exists($document->path)) {
            abort(404, 'Dokumen tidak ditemukan.');
        }

        return Storage::disk('local')->download(
            $document->path,
            $document->original_name ?? basename($document->path)
        );
    }
}
