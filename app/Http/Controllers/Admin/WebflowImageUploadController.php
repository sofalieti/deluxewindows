<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
}
