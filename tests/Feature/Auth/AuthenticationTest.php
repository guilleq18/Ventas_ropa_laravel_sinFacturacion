<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_authenticate_using_username_on_login_screen(): void
    {
        $user = User::factory()->create([
            'username' => 'mica.pos',
        ]);

        $response = $this->post('/login', [
            'email' => 'mica.pos',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_authenticate_with_legacy_django_password_and_get_rehashed(): void
    {
        $user = User::factory()->make([
            'username' => 'legacy.user',
        ]);
        DB::table('users')->insert([
            'name' => $user->name,
            'username' => $user->username,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'email_verified_at' => null,
            'password' => $this->djangoPasswordHash('secreto123'),
            'is_active' => true,
            'remember_token' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = User::query()->where('email', $user->email)->firstOrFail();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'secreto123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertTrue(Hash::check('secreto123', $user->fresh()->password));
        $this->assertFalse(str_starts_with($user->fresh()->password, 'pbkdf2_sha256$'));
    }

    public function test_users_can_authenticate_with_legacy_django_password_using_username(): void
    {
        $user = User::factory()->make([
            'username' => 'legacy.username',
            'email' => 'legacy.username@legacy.local',
        ]);
        DB::table('users')->insert([
            'name' => $user->name,
            'username' => $user->username,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'email_verified_at' => null,
            'password' => $this->djangoPasswordHash('secreto123'),
            'is_active' => true,
            'remember_token' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = User::query()->where('username', 'legacy.username')->firstOrFail();

        $response = $this->post('/login', [
            'email' => 'legacy.username',
            'password' => 'secreto123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertTrue(Hash::check('secreto123', $user->fresh()->password));
        $this->assertFalse(str_starts_with($user->fresh()->password, 'pbkdf2_sha256$'));
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    protected function djangoPasswordHash(string $password, string $salt = 'legacy-salt', int $iterations = 720000): string
    {
        return sprintf(
            'pbkdf2_sha256$%d$%s$%s',
            $iterations,
            $salt,
            base64_encode(hash_pbkdf2('sha256', $password, $salt, $iterations, 32, true)),
        );
    }
}
