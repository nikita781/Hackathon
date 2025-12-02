<?php

namespace App\Jobs\Translations;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\YandexTranslationService;

abstract class BaseTranslationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $modelId;
    public $locale;
    public $fields;
    protected $modelClass;
    protected $timestamp;
    protected YandexTranslationService $translator;

    public function __construct($modelId, $locale, $fields = null)
    {
        $this->modelId = $modelId;
        $this->locale = $locale;
        $this->fields = $fields;
        $this->translator = new YandexTranslationService();
        $this->timestamp = now()->timestamp;
    }

    abstract protected function getModelClass(): string;

    protected function getTargetLanguages(): array
    {
        $languages = config('services.yandex.target_languages');

        return array_values(array_diff($languages, [$this->locale]));

    }

    public function handle(): void
    {
        if ($this->shouldSkip()) {
            Log::info("Новый запрос на перевод обнаружен, данный запрос отменен", [
                'model' => $this->getModelClass(),
                'id' => $this->modelId,
                'job_timestamp' => $this->timestamp
            ]);
            return;
        }

        try {
            $model = $this->getModelClass()::find($this->modelId);

            if (!$model) {
                Log::warning("Модель не найдена переводчиком", [
                    'class' => $this->getModelClass(),
                    'id' => $this->modelId
                ]);
                return;
            }

            $fieldsToTranslate = $this->getFieldsToTranslate($model);
            $translations = $model->translations ?? [];

            foreach ($fieldsToTranslate as $field) {
                $originalText = $model->getRawOriginal($field);

                if (empty($originalText)) {
                    continue;
                }

                foreach ($this->getTargetLanguages() as $lang) {
                    $translations = $this->translateField(
                        $originalText,
                        $field,
                        $lang,
                        $translations
                    );
                }
            }

            $this->saveTranslations($model, $translations);
            $this->onTranslationComplete($model);

        } catch (\Exception $e) {
            $this->onTranslationFailed($e);
        }
    }

    protected function getFieldsToTranslate($model): array
    {
        if ($this->fields && !empty($this->fields)) {
            return $this->fields;
        }

        return $model->getTranslatableFields();
    }

    protected function translateField(
        string $text,
        string $field,
        string $targetLang,
        array $translations
    ): array {
        $translatedText = $this->translator->translate($text, $targetLang);

        if ($translatedText) {
            if (!isset($translations[$targetLang])) {
                $translations[$targetLang] = [];
            }
            $translations[$targetLang][$field] = $translatedText;
        }

        return $translations;
    }

    protected function saveTranslations($model, array $translations): void
    {
        $model->updateQuietly(['translations' => $translations]);

        Log::info("Модель переведена успешно", [
            'model' => $this->getModelClass(),
            'id' => $this->modelId
        ]);
    }

    protected function onTranslationComplete($model): void
    {
        //
    }

    protected function onTranslationFailed(\Exception $e): void
    {
        Log::error("Ошибка при переводе", [
            'model' => $this->getModelClass(),
            'id' => $this->modelId,
            'error' => $e->getMessage()
        ]);

        $this->fail($e);
    }

    protected function shouldSkip(): bool
    {
        $cacheKey = "translation_latest_{$this->getModelClass()}_{$this->modelId}";
        $latestTimestamp = Cache::get($cacheKey);

        if ($latestTimestamp && $latestTimestamp > $this->timestamp) {
            return true;
        }

        return false;
    }
}
