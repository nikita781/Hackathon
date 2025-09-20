<?php

namespace App\Http\Controllers;

use App\Models\EditorUpload;
use App\Models\Hackathon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class EditorController extends Controller
{
    /**
     * @throws FileIsTooBig
     * @throws FileDoesNotExist
     */
    public function upload(Request $request): JsonResponse
    {
        Gate::authorize('create', Hackathon::class);

        $request->validate([
            'image' => ['required', 'image', 'max:3000'],
        ]);

        $upload = EditorUpload::create();

        $media = $upload
            ->addMediaFromRequest('image')
            ->toMediaCollection('editorjs', 'public');

        return response()->json([
            'id' => $media->id,
            'url' => $media->getUrl(),
        ]);
    }
}
