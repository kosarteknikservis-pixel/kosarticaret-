<?php

namespace App\Concerns;

trait HasTranslations
{
    /** @return list<string> */
    protected function translatableFields(): array
    {
        return property_exists($this, 'translatable')
            ? $this->translatable
            : ['name'];
    }

    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if (
            in_array($key, $this->translatableFields(), true)
            && app()->getLocale() !== config('kosar.default_locale', 'tr')
        ) {
            $translated = $this->translate($key);

            return $translated ?? $value;
        }

        return $value;
    }

    public function translate(string $field, ?string $locale = null): mixed
    {
        $locale = $locale ?? app()->getLocale();
        $base = $this->attributes[$field] ?? null;

        if ($locale === 'tr' || $locale === config('kosar.default_locale', 'tr')) {
            return $base;
        }

        $translations = $this->translations ?? [];

        return $translations[$locale][$field] ?? $base;
    }

    public function setTranslation(string $locale, string $field, ?string $value): void
    {
        $translations = $this->translations ?? [];
        $translations[$locale][$field] = $value;
        $this->translations = $translations;
    }
}
