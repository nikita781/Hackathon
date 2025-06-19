<?php

namespace App\Http\Controllers;

use App\Http\Requests\TabRequest;
use App\Models\Hackathon;
use App\Models\Tab;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Exceptions\MediaCannotBeDeleted;

class TabController extends Controller
{
    /**
     * @throws FileIsTooBig
     * @throws FileDoesNotExist
     * @throws MediaCannotBeDeleted
     */
    public function update(TabRequest $request, Hackathon $hackathon): RedirectResponse
    {
        if (!Gate::check('update', $hackathon)) {
            abort(404);
        }

        $data = $request->validated();
        $tab = $hackathon->tabs()->where('title', $data['title'])->firstOrFail();

        if (!empty($data['delete_media_ids'])) {
            foreach ($data['delete_media_ids'] as $id) {
                $tab->deleteMedia($id);
            }
        }

        if (!empty($data['files'])) {
            foreach ($data['files'] as $file) {
                $tab->addMedia($file)->toMediaCollection('files');
            }
        }

        if (!empty($data['partners'])) {
            foreach ($data['partners'] as $partner) {
                $tab->addMedia($partner)->toMediaCollection('partner_images');
            }
        }

        return back()->with('success', 'Сохранено');
    }
}
