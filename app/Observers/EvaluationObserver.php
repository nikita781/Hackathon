<?php

namespace App\Observers;

use App\Models\Evaluation;

class EvaluationObserver
{
    public function created(Evaluation $evaluation): void
    {
        $evaluation->project->updateAvgScore();
    }

    public function deleted(Evaluation $evaluation): void
    {
        $evaluation->project->updateAvgScore();
    }
}
