<?php

use App\Translations\FrontLang;
use App\Translations\BackLang;
use App\Translations\Message;
use Illuminate\Http\JsonResponse;

if (!function_exists('trans_msg')) {
    /**
     * Translate a message key (English/Amharic)
     */
    function trans_msg(string $key, ?string $lang = null, ?string $default = null, array $replace = []): string
    {
        return Message::trans($key, $lang, $default, $replace);
    }
}

if (!function_exists('trans_front')) {
    /**
     * Translate a front-office key (English/Amharic)
     */
    function trans_front(string $key, ?string $lang = null, ?string $default = null, array $replace = []): string
    {
        return FrontLang::trans($key, $lang, $default, $replace);
    }
}

if (!function_exists('trans_back')) {
    /**
     * Translate a back-office key (English/Amharic)
     */
    function trans_back(string $key, ?string $lang = null, ?string $default = null, array $replace = []): string
    {
        return BackLang::trans($key, $lang, $default, $replace);
    }
}

if (!function_exists('msg_response')) {
    /**
     * Return a standardized JsonResponse with translated message
     */
    function msg_response(string $messageKey, int $status = 200, array $extra = [], ?string $lang = null): JsonResponse
    {
        return Message::response($messageKey, $status, $extra, $lang);
    }
}
