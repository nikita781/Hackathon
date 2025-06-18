<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LanguageController extends Controller
{
    public function __invoke($locale)
    {
        if (!in_array($locale, ['en', 'ru'])) {
            abort(400);
        }

        session(['locale' => $locale]);

        App::setLocale($locale);

        return redirect()->back();
    }
}
