<?php

namespace App\Jobs\Translations;

use App\Models\SupportMessage;

class TranslateSupportMessageJob extends BaseTranslationJob
{
    public $queue = 'translations';

    protected function getModelClass(): string
    {
        return SupportMessage::class;
    }
}
