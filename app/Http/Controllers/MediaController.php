<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Models\Banner;
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
            abort(403);
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

    public function showHackathonPartners(Hackathon $hackathon, $tab_id): JsonResponse
    {
        if (!Gate::check('view', $hackathon)) {
            abort(403);
        }

        $tab = $hackathon->tabs()->where('id', $tab_id)->firstOrFail();

        $mediaItems = $tab->getMedia('partner_images')->map(fn ($media) => [
            'id' => $media->id,
            'name' => $media->file_name,
            'url' => route('hackathons.tabs.partner-image', [$hackathon, $tab, $media]),
        ]);

        return response()->json(['partners' => $mediaItems]);
    }

    public function showHackathonPartnerImage(Hackathon $hackathon, Tab $tab, Media $media): BinaryFileResponse
    {
        if (!Gate::check('view', $hackathon)) {
            abort(403);
        }

        return response()->file($media->getPath());
    }

    public function getAllHackathonFiles(Tab $tab, Hackathon $hackathon): Collection
    {
        return $tab->getMedia('files')->map(function ($media) use($hackathon) {
            return [
                'id' => $media->id,
                'name' => $media->file_name,
                'url' => route('hackathons.files.download', [$hackathon, $media]),
            ];
        });
    }

    public function showHackathonFile(Hackathon $hackathon, Media $media): BinaryFileResponse
    {
        if (!Gate::check('view', $hackathon)) {
            abort(403);
        }

        return response()->download($media->getPath(), $media->file_name);
    }

    public function showAwardMedia(Award $award): BinaryFileResponse
    {
        $media = $award->getFirstMedia('image');

        if (!$media) {
            abort(404);
        }

        return response()->file($media->getPath());
    }

    public function showProjectPreview(Hackathon $hackathon, Project $project): BinaryFileResponse
    {
        if (!Gate::check('view', $hackathon) || !Gate::check('view', $project)) {
            abort(403);
        }

        $media = $project->getFirstMedia('preview');

        if (!$media) {
            abort(404);
        }

        return response()->file($media->getPath());
    }

    public function showProjectPresentation(Hackathon $hackathon, Project $project): JsonResponse
    {
        $media = $project->getFirstMedia('presentation');

        if (!$media) {
            abort(404);
        }

        return response()->json([
            'id' => $media->id,
            'name' => $media->file_name,
            'url' => route('hackathons.projects.presentation.download', [$hackathon, $project, $media]),
        ]);
    }

    public function DownloadProjectsPresentation(Hackathon $hackathon, Project $project, Media $media): BinaryFileResponse
    {
        if (!Gate::check('view', $hackathon) || !Gate::check('view', $project)) {
            abort(403);
        }

        return response()->download($media->getPath(), $media->file_name);
    }

    public function showProjectGallery(Request $request, Hackathon $hackathon, Project $project): JsonResponse
    {
        $user = $request->user();

        if (!$user->can('view', $project)) {
            abort(403);
        }

        $mediaItems = $project->getMedia('gallery')->map(fn ($media) => [
            'id' => $media->id,
            'name' => $media->name,
            'url' => route('hackathons.projects.gallery.image', [
                'hackathon' => $hackathon->slug,
                'project' => $project->slug,
                'mediaId' => $media->id,
            ]),
        ]);

        return response()->json([
            'gallery' => $mediaItems,
        ]);
    }

    public function showProjectGalleryImage(Hackathon $hackathon, Project $project, int $mediaId): BinaryFileResponse
    {
        if (!Gate::check('view', $project)) {
            abort(403);
        }

        $media = $project->media()->where('id', $mediaId)->first();
        return response()->file($media->getPath());
    }

    public function showBanner(Banner $banner): BinaryFileResponse
    {
        $media = $banner->getFirstMedia('image');

        if (!$media) {
            abort(404);
        }

        return response()->file($media->getPath());
    }
}
