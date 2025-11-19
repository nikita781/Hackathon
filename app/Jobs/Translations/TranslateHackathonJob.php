<?php

namespace App\Jobs\Translations;

use App\Models\Hackathon;

class TranslateHackathonJob extends BaseTranslationJob
{
    public $queue = 'translations';

    protected function getModelClass(): string
    {
        return Hackathon::class;
    }
}
