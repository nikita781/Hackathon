<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAwardRequest;
use App\Http\Requests\UpdateAwardRequest;
use App\Http\Resources\AwardResource;
use App\Models\Award;
use App\Models\Hackathon;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class AwardsController extends Controller
{
    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function store(StoreAwardRequest $request, Hackathon $hackathon): RedirectResponse
    {
        if (!Gate::check('create', Award::class)) {
            abort(404);
        }

        $data = Arr::except($request->validated(), ['image']);
        $locale = app()->getLocale();
        $data['locale'] = $locale;

        if (!auth()->user()->hasAnyRole([Role::SUPER_ADMIN, Role::ADMIN])) {
            $data['system'] = false;
        }

        $award = $hackathon->awards()->create($data);

        if ($request->hasFile('image')) {
            $award->addMediaFromRequest('image')->toMediaCollection('image');
        }

        return back()->with('status', __('award_created_success'));
    }

    /**
     * @throws FileIsTooBig
     * @throws FileDoesNotExist
     */
    public function update(UpdateAwardRequest $request, Hackathon $hackathon, Award $award): RedirectResponse
    {
        if (!Gate::check('update', $award)) {
            abort(404);
        }

        $data = Arr::except($request->validated(), 'image');
        $locale = app()->getLocale();
        $data['locale'] = $locale;

        if (!auth()->user()->hasAnyRole([Role::SUPER_ADMIN, Role::ADMIN])) {
            $data['system'] = false;
        }

        $award->update($data);
        $award->refresh();

        if ($request->hasFile('image')) {
            $award->clearMediaCollection('image');
            $award->addMediaFromRequest('image')->toMediaCollection('image');
            $award->touch();
        }

        return back()->with('status', __('award_updated_success'));
    }

    public function destroy(Hackathon $hackathon, Award $award): RedirectResponse
    {
        if (!Gate::check('delete', $award)) {
            abort(404);
        }
        $award->clearMediaCollection('main_image');
        $award->delete();
        return back()->with('status', __('award_deleted_success'));
    }
}
