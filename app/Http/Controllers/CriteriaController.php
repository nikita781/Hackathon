<?php

namespace App\Http\Controllers;

use App\Http\Requests\CriteriaRequest;
use App\Models\Criterion;
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

        $data = $request->validated();

        $criteriaGroup = $hackathon->criteriaGroups()->create($request->only(['title']));

        foreach ($data['criteria'] as $criterion) {
            if ($criterion['max_score'] === null) {
                $criterion['max_score'] = 10;
            }
            $criteriaGroup->criteria()->create($criterion);
        }

        return back()->with(['created' => 'Критерии успешно созданы!']);
    }

    public function update(CriteriaRequest $request, Hackathon $hackathon, Criterion $criterion): RedirectResponse
    {
        if (!Gate::check('update', $hackathon)) {
            abort(404);
        }

        DB::transaction(function () use ($request, $criterion, $hackathon) {
            try {
                $criterion->delete();
                $this->store($request, $hackathon);
                DB::commit();
                return back()->with(['updated' => 'Критерии успешно обновлены!']);
            } catch (\Exception $e) {
                DB::rollBack();
                return back()->with(['updated' => 'Ошибка при обновлении критериев!']);
            }
        });

        return back()->with(['updated' => 'Критерии успешно обновлены!']);

    }

    public function destroy(Criterion $criterion, Hackathon $hackathon): RedirectResponse
    {
        if (!Gate::check('update', $hackathon)) {
            abort(404);
        }

        $criterion->delete();

        return back()->with(['deleted' => 'Критерии успешно удалены!']);
    }
}
