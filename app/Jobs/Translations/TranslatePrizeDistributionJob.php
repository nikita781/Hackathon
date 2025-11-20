<?php

namespace App\Jobs\Translations;

use App\Models\PrizeDistribution;

class TranslatePrizeDistributionJob extends BaseTranslationJob
{
    public $queue = 'translations';

    protected function getModelClass(): string
    {
        return PrizeDistribution::class;
    }
}
