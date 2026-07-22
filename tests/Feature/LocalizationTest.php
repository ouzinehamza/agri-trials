<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_pages_share_independent_ui_and_content_locale_configuration(): void
    {
        config()->set('agri.locales', ['fr', 'en']);
        config()->set('agri.default_locale', 'fr');

        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get('/dashboard')->assertOk()->assertInertia(
            fn (Assert $page) => $page
                ->where('locales', ['fr', 'en'])
                ->where('defaultLocale', 'fr')
                ->where('uiLocale', 'fr')
        );
    }
}
