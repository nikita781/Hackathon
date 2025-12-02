<?php

namespace App\Jobs\Translations;

use App\Models\TabItem;

class TranslateTabItemJob extends BaseTranslationJob
{
    public $queue = 'translations';

    protected function getModelClass(): string
    {
        return TabItem::class;
    }
}
