<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TranslatableResource extends JsonResource
{
    /**
     * Получить переведенное значение поля
     */
    protected function trans(string $field): string
    {
        $currentLocale = app()->getLocale();
        $originalValue = $this->getRawOriginal($field);

        if ($currentLocale === $this->locale) {
            return $originalValue;
        }

        if (!$this->translations || !is_array($this->translations)) {
            return $originalValue;
        }

        if (isset($this->translations[$currentLocale]) && is_string($this->translations[$currentLocale])) {
            return $this->translations[$currentLocale];
        }

        if (isset($this->translations[$currentLocale]) &&
            is_array($this->translations[$currentLocale]) &&
            isset($this->translations[$currentLocale][$field])) {
            return $this->translations[$currentLocale][$field];
        }

        return $originalValue;
    }

    protected function getRawOriginal(string $field): string
    {
        $value = $this->resource->getRawOriginal($field);
        return is_string($value) ? $value : '';
    }
}
