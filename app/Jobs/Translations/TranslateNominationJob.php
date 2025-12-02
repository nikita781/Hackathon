<?php

namespace App\Jobs\Translations;

use App\Models\Nomination;

class TranslateNominationJob extends BaseTranslationJob
{
    public $queue = 'translations';

    protected function getModelClass(): string
    {
        return Nomination::class;
    }
}
