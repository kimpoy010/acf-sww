<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegisterPasswordStrengthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'player']);
    }

    private function baseData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'New Player',
            'username' => 'newplayer',
            'email' => 'newplayer@example.com',
            'password' => 'StrongPassw0rd',
            'password_confirmation' => 'StrongPassw0rd',
        ], $overrides);
    }

    public function test_a_strong_password_registers_successfully(): void
    {
        $response = $this->post(route('register'), $this->baseData());

        $response->assertRedirect();
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['username' => 'newplayer']);
    }

    public function test_a_password_without_an_uppercase_letter_is_rejected(): void
    {
        $response = $this->post(route('register'), $this->baseData([
            'password' => 'weakpassw0rd',
            'password_confirmation' => 'weakpassw0rd',
        ]));

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_a_password_containing_the_username_is_rejected(): void
    {
        $response = $this->post(route('register'), $this->baseData([
            'password' => 'Newplayer1x',
            'password_confirmation' => 'Newplayer1x',
        ]));

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_the_register_page_shows_the_strength_requirements(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertSee(__('At least 8 characters'));
        $response->assertSee(__('Uppercase letter'));
        $response->assertSee(__('No sequential (abc, 123)'));
        $response->assertSee(__('Must not contain username'));
    }

    public function test_the_register_page_has_password_toggle_buttons(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertSee('data-toggle-password="register-password"', false);
        $response->assertSee('data-toggle-password="register-password-confirmation"', false);
    }
}
