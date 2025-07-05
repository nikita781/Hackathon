<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAwardRequest;
use App\Http\Requests\UpdateAwardRequest;
use App\Http\Resources\AwardResource;
use App\Models\Award;
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
    public function store(StoreAwardRequest $request): RedirectResponse
    {
        if (!Gate::check('create', Award::class)) {
            abort(404);
        }

        $data = Arr::except($request->validated(), 'image');

        if (!auth()->user()->hasAnyRole([Role::SUPER_ADMIN, Role::ADMIN])) {
            $data['system'] = false;
        }

        $award = Award::create($data);

        if ($request->hasFile('image')) {
            $award->addMediaFromRequest('image')->toMediaCollection('main_image');
        }

        return back()->with('award', 'Награда успешно создана');
    }

    /**
     * @throws FileIsTooBig
     * @throws FileDoesNotExist
     */
    public function update(UpdateAwardRequest $request, Award $award): RedirectResponse
    {
        if (!Gate::check('update', $award)) {
            abort(404);
        }

        $data = Arr::except($request->validated(), 'image');

        if (!auth()->user()->hasAnyRole([Role::SUPER_ADMIN, Role::ADMIN])) {
            $data['system'] = false;
        }

        Award::update($data);

        if ($request->hasFile('image')) {
            if ($award->hasMedia('main_image')) {
                $award->clearMediaCollection('main_image');
            }
            $award->addMediaFromRequest('image')->toMediaCollection('main_image');
        }

        return back()->with('award', 'Награда успешно создана');
    }

    public function destroy(Award $award): RedirectResponse
    {
        if (!Gate::check('delete', $award)) {
            abort(404);
        }
        $award->clearMediaCollection('main_image');
        $award->delete();
        return back()->with('award', 'Награда успешно удалена');
    }
}
