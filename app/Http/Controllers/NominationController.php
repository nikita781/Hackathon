<?php

namespace App\Http\Controllers;

use App\Http\Requests\NominationRequest;
use App\Models\Hackathon;
use App\Models\Nomination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Event\Code\Throwable;

class NominationController extends Controller
{
    public function store(NominationRequest $request, Hackathon $hackathon): RedirectResponse
    {
        if (!Gate::check('update', $hackathon)) {
            abort(404);
        }

        $data = $request->validated();

        $locale = app()->getLocale();

        $nomination = $hackathon->nominations()->create($request->only(['title', 'prize']));

        $nomination->locale = $locale;
        $nomination->save();

        foreach ($data['places'] as $place) {
            $nomination->distribution()->create([
                'place' => $place['place'],
                'prize' => $place['prize'],
                'locale' => $locale,
            ]);
        }

        return back()->with('status', __('nomination_created'));
    }

    public function update(NominationRequest $request, Hackathon $hackathon, Nomination $nomination): RedirectResponse
    {
        if (!Gate::check('update', $hackathon)) {
            abort(404);
        }

        DB::transaction(function () use ($request, $nomination, $hackathon) {
            try {
                $nomination->delete();
                $this->store($request, $hackathon);
                DB::commit();
                return back()->with('status', __('nomination_updated'));
            } catch (\Exception $e) {
                DB::rollBack();
                return back()->with('status', __('nomination_update_error'));
            }
        });
        return back()->with('status', __('nomination_updated'));
    }

    public function destroy(Hackathon $hackathon, Nomination $nomination): RedirectResponse
    {
        if (!Gate::check('update', $hackathon)) {
            abort(404);
        }

        $nomination->delete();
        return back()->with('status', __('nomination_deleted'));
    }
}
