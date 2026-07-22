<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'import_result' => fn () => $request->session()->get('import_result'),
                'import_preview' => fn () => $request->session()->get('import_preview'),
            ],
            'locales' => array_values(config('agri.locales')),
            'defaultLocale' => config('agri.default_locale'),
            'uiLocale' => $request->user()?->locale ?? app()->getLocale(),
            'theme' => fn () => \App\Models\Setting::get('theme'),
        ];
    }
}
