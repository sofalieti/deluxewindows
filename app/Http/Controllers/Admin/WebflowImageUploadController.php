<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WebflowImageUploadController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|file|mimes:jpeg,jpg,png,gif,webp,avif,svg|max:10240',
        ]);

        $file = $request->file('image');
        $ext  = $file->getClientOriginalExtension();
        $name = Str::uuid().'.'.$ext;
        $path = $file->storeAs('webflow-uploads', $name, 'public');

        return response()->json([
            'url' => asset('storage/'.$path),
        ]);
    }

    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|string|max:2048',
        ]);

        $url = (string) $request->input('url');
        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return response()->json(['ok' => false, 'message' => 'Invalid URL'], 422);
        }

        // Only allow deleting files we uploaded via the admin uploader.
        if (! preg_match('#/storage/webflow-uploads/([A-Za-z0-9._-]+)$#', $path, $matches)) {
            return response()->json(['ok' => true, 'deleted' => false, 'message' => 'Not a local upload']);
        }

        $relative = 'webflow-uploads/'.$matches[1];
        $disk = Storage::disk('public');
        $deleted = false;
        if ($disk->exists($relative)) {
            $deleted = $disk->delete($relative);
        }

        return response()->json([
            'ok' => true,
            'deleted' => (bool) $deleted,
        ]);
    }
}
