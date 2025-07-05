<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Models\Hackathon;
use App\Models\Tab;
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

    public function showHackathonPartners($tab_id, Hackathon $hackathon): BinaryFileResponse
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
}
