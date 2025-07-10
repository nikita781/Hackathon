<?php

namespace App\Observers;

use App\Models\Criterion;
use App\Models\Project;

class CriterionObserver
{
    public function updated(Criterion $criterion): void
    {
        $projectIds = $criterion->evaluations()->pluck('project_id')->unique();

        Project::whereIn('id', $projectIds)
            ->get()
            ->each(fn ($project) => $project->updateAvgScore());
    }

    public function deleted(Criterion $criterion): void
    {
        $this->updated($criterion);
    }
}
