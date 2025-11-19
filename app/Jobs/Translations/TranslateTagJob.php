<?php

namespace App\Jobs\Translations;

use App\Models\Tag;

class TranslateTagJob extends BaseTranslationJob
{
    public $queue = 'translations';

    protected function getModelClass(): string
    {
        return Tag::class;
    }
}
