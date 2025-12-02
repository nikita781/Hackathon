<?php

namespace App\Jobs\Translations;

use App\Models\CriterionGroup;

class TranslateCriterionGroupJob extends BaseTranslationJob
{
    public $queue = 'translations';

    protected function getModelClass(): string
    {
        return CriterionGroup::class;
    }
}
