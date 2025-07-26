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
        if (!in_array($locale, ['en_US', 'ru_RU', 'es', 'zh_CH', 'fr_FR', 'de_DE', 'pt_PT'])) {
            abort(400);
        }

        session(['locale' => $locale]);

        App::setLocale($locale);

        return redirect()->route('home');
    }

    public function json(string $locale): BinaryFileResponse
    {
        return response()->file(lang_path("$locale.json"));
    }
}
