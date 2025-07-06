<?php

namespace App\Http\Controllers;

use App\Http\Requests\CriteriaRequest;
use App\Models\Criterion;
use App\Models\CriterionGroup;
use App\Models\Hackathon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CriteriaController extends Controller
{
    public function store(CriteriaRequest $request, Hackathon $hackathon): RedirectResponse
    {
        if (!Gate::check('update', $hackathon)) {
            abort(404);
        }

        $this->createCriteriaGroup($request, $hackathon);

        return back()->with(['created' => 'Критерии успешно созданы!']);
    }

    public function update(CriteriaRequest $request, Hackathon $hackathon, CriterionGroup $criterionGroup): RedirectResponse
    {
        if (!Gate::check('update', $hackathon)) {
            abort(404);
        }

        $criterionGroup->delete();
        $this->createCriteriaGroup($request, $hackathon);

        return back()->with(['updated' => 'Критерии успешно обновлены!']);

    }

    public function destroy(Hackathon $hackathon, CriterionGroup $criterionGroup): RedirectResponse
    {
        if (!Gate::check('update', $hackathon)) {
            abort(404);
        }

        $criterionGroup->delete();

        return back()->with(['deleted' => 'Критерии успешно удалены!']);
    }

    private function createCriteriaGroup(CriteriaRequest $request, Hackathon $hackathon): void
    {
        $data = $request->validated();

        $criteriaGroup = $hackathon->criteriaGroups()->create([
            'title' => $data['title'],
        ]);

        foreach ($data['criteria'] as $criterion) {
            $criterion['max_score'] = $criterion['max_score'] ?? 10;
            $criteriaGroup->criteria()->create($criterion);
        }
    }
}
