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

        \Log::debug('Checking translatable fields for changes', [
            'model' => get_class($this),
            'id' => $this->id,
            'translatable_fields' => $translatableFields
        ]);

        foreach ($translatableFields as $field) {
            $oldValue = $original[$field] ?? null;
            $newValue = $this->$field;

            $hasOldValue = array_key_exists($field, $original);
            $valuesDiffer = $oldValue !== $newValue;
            $newValueNotEmpty = !empty($newValue);

            \Log::debug('Field comparison', [
                'field' => $field,
                'old_value' => $oldValue ? substr($oldValue, 0, 50) : 'NULL',
                'new_value' => $newValue ? substr($newValue, 0, 50) : 'NULL',
                'are_equal' => $oldValue === $newValue ? 'YES' : 'NO',
                'is_new_empty' => empty($newValue) ? 'YES' : 'NO'
            ]);

            if (($hasOldValue && $valuesDiffer && $newValueNotEmpty) ||
                (!$hasOldValue && $newValueNotEmpty)) {
                $changedFields[] = $field;
                \Log::debug('Field changed, will translate', ['field' => $field]);
            }
        }

        \Log::debug('Changed fields result', ['changed_fields' => $changedFields]);

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
