<?php

namespace App\Jobs\Translations;

use App\Models\Award;

class TranslateAwardJob extends BaseTranslationJob
{
    public $queue = 'translations';

    protected function getModelClass(): string
    {
        return Award::class;
    }
}
