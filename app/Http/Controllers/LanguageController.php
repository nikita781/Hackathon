<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LanguageController extends Controller
{

    public function switchLang($locale): RedirectResponse
    {
        $localeMap = [
            'ru' => 'ru',
            'en' => 'en',
            'de' => 'de',
            'fr' => 'fr',
            'es' => 'es',
            'zh' => 'zh_CN',
            'zh_CN' => 'zh_CN',
            'pt' => 'pt_PT',
            'pt_PT' => 'pt_PT'
        ];

        if (!array_key_exists($locale, $localeMap)) {
            abort(400, "язык не поддерживается: $locale");
        }

        $normalizedLocale = $localeMap[$locale];

        session(['locale' => $normalizedLocale]);
        app()->setLocale($normalizedLocale);

        return redirect()->back();
    }


    public function json(string $locale): BinaryFileResponse
    {
        if (!in_array($locale, ['en', 'ru', 'es', 'zh_CN', 'fr', 'de', 'pt'])) {
            abort(400);
        }
        return response()->file(lang_path("$locale.json"));
    }
}
