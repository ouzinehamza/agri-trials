<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BrandingController extends Controller
{
    public function index(): Response
    {
        // Current theme is provided company-wide via HandleInertiaRequests shared props.
        return Inertia::render('Branding/Index');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'appName' => ['required', 'string', 'max:60'],
            'logoText' => ['required', 'string', 'max:3'],
            'primary' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'accent' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'fontSans' => ['required', 'string', 'max:160'],
            'radius' => ['required', 'integer', 'min:0', 'max:20'],
            'density' => ['required', 'in:comfortable,compact'],
        ]);

        Setting::put('theme', $data);

        return redirect()->route('branding.index')->with('success', 'Thème enregistré pour toute l\'entreprise.');
    }
}
