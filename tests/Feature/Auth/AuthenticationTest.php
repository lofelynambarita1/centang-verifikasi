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
            ->assertSee('Collaborative Audit Report');
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('dashboard'));
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

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
