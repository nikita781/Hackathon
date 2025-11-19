<?php

namespace App\Jobs\Translations;

use App\Models\TabSection;

class TranslateTabSectionJob extends BaseTranslationJob
{
    public $queue = 'translations';

    protected function getModelClass(): string
    {
        return TabSection::class;
    }
}
