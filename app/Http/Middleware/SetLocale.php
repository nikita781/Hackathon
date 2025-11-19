<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private $supportedLocales = ['ru', 'en', 'de', 'fr', 'es', 'zh_CN', 'pt_PT'];

    public function handle(Request $request, Closure $next): Response
    {
        if (session()->has('locale') && in_array(session('locale'), $this->supportedLocales)) {
            app()->setLocale(session('locale'));
            return $next($request);
        }

        if ($request->has('lang') && in_array($request->get('lang'), $this->supportedLocales)) {
            $locale = $request->get('lang');
            app()->setLocale($locale);
            session(['locale' => $locale]);
            return $next($request);
        }

        $browserLocale = $this->getBrowserLocale($request);
        if ($browserLocale) {
            app()->setLocale($browserLocale);
            session(['locale' => $browserLocale]);
            return $next($request);
        }

        app()->setLocale('ru');
        session(['locale' => 'ru']);

        return $next($request);
    }

    private function getBrowserLocale(Request $request): ?string
    {
        $browserLocales = $request->getLanguages();

        foreach ($browserLocales as $browserLocale) {
            $normalized = $this->normalizeBrowserLocale($browserLocale);
            if (in_array($normalized, $this->supportedLocales)) {
                return $normalized;
            }
        }

        return null;
    }

    private function normalizeBrowserLocale(string $locale): string
    {
        $map = [
            'zh' => 'zh_CN',
            'zh-cn' => 'zh_CN',
            'zh-CN' => 'zh_CN',
            'pt' => 'pt_PT',
            'pt-pt' => 'pt_PT',
            'pt-PT' => 'pt_PT',
        ];

        $locale = str_replace('_', '-', $locale);

        return $map[$locale] ?? $locale;
    }
}
