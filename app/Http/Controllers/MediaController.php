<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Models\Hackathon;
use App\Models\Project;
use App\Models\Tab;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    public function showHackathonMedia(Hackathon $hackathon): BinaryFileResponse
    {
        if (!Gate::check('view', $hackathon)) {
            abort(404);
        }

        $media = $hackathon->getFirstMedia('main_image');

        if (!$media) {
            abort(404);
        }

        return response()->file($media->getPath());
    }

    public function showHackathonMediaMobile(Hackathon $hackathon): BinaryFileResponse
    {
        Gate::authorize('viewAny', $hackathon);

        $media = $hackathon->getFirstMedia('main_image');

        if (!$media) {
            abort(404);
        }

        return response()->file($media->getPath('preview'));
    }

    public function showHackathonPartners(Hackathon $hackathon, $tab_id): BinaryFileResponse
    {
        if (!Gate::check('view', $hackathon)) {
            abort(404);
        }
        $tab = $hackathon->tabs()->where('id', $tab_id)->firstOrFail();

        $media = $tab->getFirstMedia('partner_images');

        if (!$media) {
            abort(404);
        }

        return response()->file($media->getPath());
    }

    public function getAllHackathonFiles(Tab $tab, $hackathon_id): Collection
    {
        return $tab->getMedia('files')->map(function ($media) use($hackathon_id) {
            return [
                'id' => $media->id,
                'name' => $media->file_name,
                'url' => route('hackathons.files.download', [$hackathon_id, $media]),
            ];
        });
    }

    public function showHackathonFile($hackathon_id, Media $media): BinaryFileResponse
    {
        $hackathon = Hackathon::findOrFail($hackathon_id);

        if (!Gate::check('view', $hackathon)) {
            abort(404);
        }

        return response()->download($media->getPath(), $media->file_name);
    }

    public function showAwardMedia(Award $award): BinaryFileResponse
    {
        $media = $award->getFirstMedia('main_image');

        if (!$media) {
            abort(404);
        }

        return response()->file($media->getPath());
    }

    public function showProjectPreview(Hackathon $hackathon, Project $project): BinaryFileResponse
    {
        if (!Gate::check('view', $hackathon) || !Gate::check('view', $project)) {
            abort(404);
        }

        $media = $project->getFirstMedia('preview');

        if (!$media) {
            abort(404);
        }

        return response()->file($media->getPath());
    }

    public function showProjectPresentation(Hackathon $hackathon, Project $project): JsonResponse
    {
        if (!Gate::check('view', $hackathon) || !Gate::check('view', $project)) {
            abort(404);
        }

        $media = $project->getFirstMedia('presentation');

        if (!$media) {
            abort(404);
        }

        return response()->json([
            'id' => $media->id,
            'name' => $media->file_name,
            'url' => $media->getPath(),
        ]);
    }

    public function showProjectGallery(Request $request, Hackathon $hackathon, Project $project): JsonResponse
    {
        $user = $request->user();

        if (! $user->can('view', $project)) {
            abort(404);
        }

        $mediaItems = $project->getMedia('gallery')->map(fn ($media) => [
            'id' => $media->id,
            'url' => $media->getPath(),
            'name' => $media->name,
        ]);

        return response()->json([
            'gallery' => $mediaItems,
        ]);
    }

//    public function downloadProjectPresentation(Hackathon $hackathon, Project $project): BinaryFileResponse
//    {
//        if (!Gate::check('view', $hackathon) || !Gate::check('view', $project)) {
//            abort(404);
//        }
//
//        $media = $project->getFirstMedia('main_image');
//
//        if (!$media) {
//            abort(404);
//        }
//
//        return response()->download($media->getPath(), $media->file_name);
//    }
}
