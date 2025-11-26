<?php

namespace App\Jobs\Translations;

use App\Models\Project;

class TranslateProjectJob extends BaseTranslationJob
{
    public $queue = 'translations';

    protected function getModelClass(): string
    {
        return Project::class;
    }
}
