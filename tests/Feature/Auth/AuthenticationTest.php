<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_accessible_for_guests(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Verifikasi ISPO');
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_authenticated_pages_are_not_cached(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)
            ->get(route('audit-projects.index'))
            ->assertOk()
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader('Expires', '0');

        $cacheControl = $response->headers->get('Cache-Control');

        $this->assertNotNull($cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
    }

    public function test_guest_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->admin()->create(['email' => 'admin@example.com']);

        $this->post(route('login.store'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])
            ->assertRedirect(route('audit-projects.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_guest_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);

        $this->post(route('login.store'), [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $this->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->get(route('audit-projects.index'))
            ->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk();
    }

    public function test_authenticated_layout_renders_logout_confirmation(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('audit-projects.index'))
            ->assertOk()
            ->assertSee('Keluar dari aplikasi?')
            ->assertSee('Anda yakin ingin keluar dari Verifikasi ISPO?')
            ->assertSee('Batal')
            ->assertSee('Ya, Keluar')
            ->assertSee('data-logout-trigger', false)
            ->assertSee(route('logout'), false);
    }
}
