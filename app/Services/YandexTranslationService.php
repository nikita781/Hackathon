<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YandexTranslationService
{
    private array $supportedLanguages = [
        'ru' => 'ru',
        'en' => 'en',
        'de' => 'de',
        'fr' => 'fr',
        'es' => 'es',
        'pt' => 'pt',
        'zh_CN' => 'zh'
    ];

    public function translate(string $text, string $targetLanguage): ?string
    {
        $text = trim($text, '"');

        $editorData = json_decode($text, true);

        if (json_last_error() === JSON_ERROR_NONE && $this->isEditorJsContent($editorData)) {
            Log::debug("Detected Editor.js content", [
                'blocks_count' => count($editorData['blocks'] ?? []),
                'target_lang' => $targetLanguage
            ]);

            $translatedData = $this->translateEditorJsContentBatch($editorData, $targetLanguage);

            return json_encode($translatedData);
        }

        return $this->translatePlainText($text, $targetLanguage);
    }


    private function translateEditorJsContentBatch(array $editorData, string $targetLanguage): array
    {
        // Собираем все тексты для перевода одним запросом
        $allTexts = [];
        $textMapping = []; // Сохраняем где какой текст находится

        foreach ($editorData['blocks'] as $blockIndex => &$block) {
            $textsFromBlock = $this->extractTextsFromBlock($block);

            foreach ($textsFromBlock as $path => $text) {
                if ($this->shouldTranslateText($text)) {
                    $allTexts[] = $text;
                    $textMapping[] = [
                        'block_index' => $blockIndex,
                        'path' => $path,
                        'original_text' => $text
                    ];
                }
            }
        }

        // Переводим все тексты одним запросом
        if (!empty($allTexts)) {
            $translatedTexts = $this->translateBatch($allTexts, $targetLanguage);

            // Заменяем тексты в блоках
            foreach ($textMapping as $index => $mapping) {
                if (isset($translatedTexts[$index]) && $translatedTexts[$index] !== $mapping['original_text']) {
                    $this->replaceTextInBlock(
                        $editorData['blocks'][$mapping['block_index']],
                        $mapping['path'],
                        $translatedTexts[$index]
                    );
                }
            }
        }

        return $editorData;
    }

    private function extractTextsFromBlock(array $block): array
    {
        $texts = [];
        $type = $block['type'] ?? '';
        $data = $block['data'] ?? [];

        switch ($type) {
            case 'paragraph':
            case 'header':
                if (isset($data['text'])) {
                    $texts['data.text'] = $data['text'];
                }
                break;

            case 'image':
                if (isset($data['caption'])) {
                    $texts['data.caption'] = $data['caption'];
                }
                break;

            case 'list':
            case 'checklist':
                if (isset($data['items']) && is_array($data['items'])) {
                    foreach ($data['items'] as $itemIndex => $item) {
                        if (isset($item['content'])) {
                            $texts["data.items.{$itemIndex}.content"] = $item['content'];
                        }
                    }
                }
                break;
            case "vkvideo":
                break;
            default:
                Log::debug('Неподдерживающийся блок: ' . $type);
        }

        return $texts;
    }

    private function replaceTextInBlock(array &$block, string $path, string $translatedText): void
    {
        $pathParts = explode('.', $path);
        $current = &$block;

        foreach ($pathParts as $part) {
            if (is_array($current) && array_key_exists($part, $current)) {
                $current = &$current[$part];
            } else {
                return;
            }
        }

        $current = $translatedText;
    }

    private function translateBatch(array $texts, string $targetLanguage): array
    {
        if (!isset($this->supportedLanguages[$targetLanguage])) {
            Log::warning("Неподдерживаемый язык перевода: {$targetLanguage}");
            return $texts;
        }

        // Фильтруем тексты
        $textsToTranslate = [];
        $textMapping = [];

        foreach ($texts as $index => $text) {
            if ($this->shouldTranslateText($text)) {
                $textsToTranslate[] = $text;
                $textMapping[] = $index;
            }
        }

        if (empty($textsToTranslate)) {
            return $texts;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Api-Key ' . config('services.yandex.service_account_api'),
                'Content-Type' => 'application/json',
            ])->timeout(15)->post('https://translate.api.cloud.yandex.net/translate/v2/translate', [
                'folder_id' => config('services.yandex.folder_id'),
                'texts' => $textsToTranslate,
                'targetLanguageCode' => $this->supportedLanguages[$targetLanguage],
            ]);

            if ($response->successful()) {
                $translations = $response->json()['translations'] ?? [];

                // Собираем результаты в правильном порядке
                $result = $texts; // Начинаем с оригиналов
                foreach ($translations as $i => $translation) {
                    if (isset($textMapping[$i])) {
                        $originalIndex = $textMapping[$i];
                        $result[$originalIndex] = $translation['text'] ?? $texts[$originalIndex];
                    }
                }

                Log::debug("Batch translation completed", [
                    'target_lang' => $targetLanguage,
                    'texts_count' => count($textsToTranslate),
                    'translations_count' => count($translations)
                ]);

                return $result;
            }

            Log::error('Yandex Translation API error', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

        } catch (\Exception $e) {
            Log::error('Yandex Translation service error: ' . $e->getMessage());
        }

        return $texts;
    }

    private function shouldTranslateText(string $text): bool
    {
        $trimmedText = trim($text);

        if (empty($trimmedText) ||
            $trimmedText === '&nbsp;' ||
            filter_var($trimmedText, FILTER_VALIDATE_URL) ||
            !preg_match('/\p{L}/u', $trimmedText)) {
            return false;
        }

        return true;
    }

    private function isEditorJsContent(?array $data): bool
    {
        if (!is_array($data)) {
            return false;
        }

        return isset($data['blocks']) && is_array($data['blocks']) && isset($data['time']);
    }

    private function translatePlainText(string $text, string $targetLanguage): ?string
    {
        if (!isset($this->supportedLanguages[$targetLanguage])) {
            Log::warning("Неподдерживаемый язык перевода: {$targetLanguage}");
            return null;
        }

        if (empty(trim($text)) || $text === '&nbsp;') {
            return null;
        }

        if (filter_var($text, FILTER_VALIDATE_URL) || preg_match('/^[^\p{L}]+$/u', $text)) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Api-Key ' . config('services.yandex.service_account_api'),
                'Content-Type' => 'application/json',
            ])->timeout(10)->post('https://translate.api.cloud.yandex.net/translate/v2/translate', [
                'folder_id' => config('services.yandex.folder_id'),
                'texts' => [$text],
                'targetLanguageCode' => $this->supportedLanguages[$targetLanguage],
            ]);

            if ($response->successful()) {
                $translated = $response->json()['translations'][0]['text'] ?? $text;

                Log::debug("Text translated", [
                    'original' => $text,
                    'translated' => $translated,
                    'target_lang' => $targetLanguage
                ]);

                return $translated;
            }

            Log::error('Yandex Translation API error', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

        } catch (\Exception $e) {
            Log::error('Yandex Translation service error: ' . $e->getMessage());
        }

        return null;
    }

}
