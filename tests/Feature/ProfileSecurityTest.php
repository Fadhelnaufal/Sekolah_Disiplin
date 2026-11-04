<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        $this->user = User::factory()->create([
            'name' => 'Pengguna Disiplin',
            'email' => 'pengguna@test.com',
            'password' => Hash::make('password123'),
        ]);
        $this->user->assignRole('Teacher');
    }

    public function test_user_can_view_profile_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('profile.edit'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Profile/Edit')
            ->where('user.name', 'Pengguna Disiplin')
            ->where('user.email', 'pengguna@test.com')
        );
    }

    public function test_user_can_update_profile_information(): void
    {
        $response = $this->actingAs($this->user)->put(route('profile.update'), [
            'name' => 'Nama Baru Profil',
            'email' => 'emailbaru@test.com',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Nama Baru Profil',
            'email' => 'emailbaru@test.com',
        ]);
    }

    public function test_user_can_update_password_with_valid_current_password(): void
    {
        $response = $this->actingAs($this->user)->put(route('profile.password'), [
            'current_password' => 'password123',
            'password' => 'password456',
            'password_confirmation' => 'password456',
        ]);

        $response->assertSessionHas('success');
        $this->user->refresh();
        $this->assertTrue(Hash::check('password456', $this->user->password));
    }

    public function test_user_cannot_update_password_with_invalid_current_password(): void
    {
        $response = $this->actingAs($this->user)->put(route('profile.password'), [
            'current_password' => 'wrongpassword',
            'password' => 'password456',
            'password_confirmation' => 'password456',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->user->refresh();
        $this->assertTrue(Hash::check('password123', $this->user->password));
    }
}
