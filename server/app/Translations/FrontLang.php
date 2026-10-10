<?php

namespace App\Translations;

use App\Translations\Front\English;
use App\Translations\Front\Amharic;

class FrontLang
{
    /**
     * Resolve the current language ('en' or 'am')
     */
    public static function resolveLang(?string $lang = null): string
    {
        if (!empty($lang)) {
            return str_starts_with(strtolower(trim($lang)), 'am') ? 'am' : 'en';
        }

        if (function_exists('request') && request()) {
            $reqLang = request()->header('X-App-Locale')
                ?? request()->header('Accept-Language')
                ?? request()->query('lang');

            if (!empty($reqLang)) {
                return str_starts_with(strtolower(trim($reqLang)), 'am') ? 'am' : 'en';
            }
        }

        return 'en';
    }

    /**
     * Get all Front translations for given language
     */
    public static function get(?string $lang = null): array
    {
        return self::resolveLang($lang) === 'am' ? Amharic::get() : English::get();
    }

    /**
     * Check if a specific translation key exists
     */
    public static function has(string $key, ?string $lang = null): bool
    {
        $translations = self::get($lang);
        return array_key_exists($key, $translations);
    }

    /**
     * Translate a specific key with optional parameter replacements
     */
    public static function trans(string $key, ?string $lang = null, ?string $default = null, array $replace = []): string
    {
        $translations = self::get($lang);
        $text = $translations[$key] ?? ($default ?? $key);

        if (!empty($replace) && is_string($text)) {
            foreach ($replace as $placeholder => $value) {
                $text = str_replace(':' . $placeholder, (string)$value, $text);
            }
        }

        return (string)$text;
    }
}

