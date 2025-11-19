<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait Translatable
{
    protected static function bootTranslatable()
    {
        static::created(function ($model) {
            if (config('services.yandex.auto_translate', true)) {
                $model->scheduleTranslation();
            }
        });

        static::updated(function ($model) {
            if (config('services.yandex.auto_translate', true)) {
                $changedFields = $model->getChangedTranslatableFields();
                if (!empty($changedFields)) {
                    $model->scheduleTranslation(
                        config('services.yandex.update_delay'),
                        $changedFields
                    );
                }
            }
        });
    }

    public function scheduleTranslation(int $delay = 0, array $fields = null): void
    {
        $jobClass = $this->getTranslationJobClass();

        if (!class_exists($jobClass)) {
            \Log::error("Job не найден для {$jobClass}");
            return;
        }

        $this->markTranslationRequest();

        $locale = app()->getLocale();

        if ($delay > 0) {
            $jobClass::dispatch($this->id, $locale, $fields)->delay(now()->addSeconds($delay));
        } else {
            $jobClass::dispatch($this->id, $locale, $fields);
        }
    }

    protected function getChangedTranslatableFields(): array
    {
        $original = $this->getOriginal();
        $translatableFields = $this->getTranslatableFields();
        $changedFields = [];

        foreach ($translatableFields as $field) {
            if (isset($original[$field]) && $original[$field] !== $this->$field && !empty($this->$field)) {
                $changedFields[] = $field;
            }
        }

        return $changedFields;
    }

    protected function markTranslationRequest(): void
    {
        $cacheKey = "translation_latest_" . get_class($this) . "_{$this->id}";
        $timestamp = now()->timestamp;

        Cache::put($cacheKey, $timestamp, 600);
    }

    protected function getTranslationJobClass(): ?string
    {
        $modelName = class_basename($this);
        return "App\\Jobs\\Translations\\Translate{$modelName}Job";
    }

    public function getTranslatableFields(): array
    {
        return property_exists($this, 'translatable') ? $this->translatable : [];
    }
}
