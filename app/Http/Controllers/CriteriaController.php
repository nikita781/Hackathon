<?php

namespace App\Http\Controllers;

use App\Http\Requests\CriteriaRequest;
use App\Models\CriterionGroup;
use App\Models\Hackathon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CriteriaController extends Controller
{
    public function store(CriteriaRequest $request, Hackathon $hackathon): RedirectResponse
    {
        if (!Gate::check('update', $hackathon)) {
            abort(404);
        }

        $this->createCriteriaGroup($request, $hackathon);

        return back()->with('status', __('criteria_created_success'));
    }

    public function update(CriteriaRequest $request, Hackathon $hackathon, CriterionGroup $criterionGroup): RedirectResponse
    {
        if (!Gate::check('update', $hackathon)) {
            abort(404);
        }

        $criterionGroup->delete();
        $this->createCriteriaGroup($request, $hackathon);

        return back()->with('status', __('criteria_updated_success'));

    }

    public function destroy(Hackathon $hackathon, CriterionGroup $criterionGroup): RedirectResponse
    {
        if (!Gate::check('update', $hackathon)) {
            abort(404);
        }

        $criterionGroup->delete();

        return back()->with('status', __('criteria_deleted_success'));
    }

    private function createCriteriaGroup(CriteriaRequest $request, Hackathon $hackathon): void
    {
        $data = $request->validated();

        $locale = app()->getLocale();

        $criteriaGroup = $hackathon->criteriaGroups()->create([
            'title' => $data['title'],
            'locale' => $locale,
        ]);

        foreach ($data['criteria'] as $criterion) {
            $criterion['max_score'] = $criterion['max_score'] ?? 10;
            $criterion['locale'] = $locale;
            $criteriaGroup->criteria()->create($criterion);
        }
    }
}
