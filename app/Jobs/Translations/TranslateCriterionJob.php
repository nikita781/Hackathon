<?php

namespace App\Jobs\Translations;

use App\Models\Criterion;

class TranslateCriterionJob extends BaseTranslationJob
{
    public $queue = 'translations';

    protected function getModelClass(): string
    {
        return Criterion::class;
    }
}
