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
        if (!in_array($locale, ['en', 'ru', 'es', 'zh_CH'])) {
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
