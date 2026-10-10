<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Translations\FrontLang;
use App\Translations\BackLang;
use App\Translations\Message;

class TranslationController extends Controller
{
    /**
     * Get all translations for the requested language.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $rawLang = $request->query('lang')
            ?? $request->header('X-App-Locale')
            ?? $request->header('Accept-Language')
            ?? 'en';

        $lang = str_starts_with(strtolower(trim($rawLang)), 'am') ? 'am' : 'en';
        $section = strtolower($request->query('section', 'all'));

        $front = FrontLang::get($lang);
        $back = BackLang::get($lang);
        $message = Message::get($lang);

        if ($section === 'front') {
            return response()->json([
                'success' => true,
                'language' => $lang,
                'translations' => $front
            ]);
        }

        if ($section === 'back') {
            return response()->json([
                'success' => true,
                'language' => $lang,
                'translations' => $back
            ]);
        }

        if ($section === 'message') {
            return response()->json([
                'success' => true,
                'language' => $lang,
                'translations' => $message
            ]);
        }

        return response()->json([
            'success' => true,
            'language' => $lang,
            'translations' => [
                'front' => $front,
                'back' => $back,
                'message' => $message,
                'all' => array_merge($front, $back, $message),
            ]
        ]);
    }
}

