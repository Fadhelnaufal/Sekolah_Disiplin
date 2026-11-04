<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin Test',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
        ]);
        $this->superAdmin->assignRole('Super Admin');

        $this->teacher = User::factory()->create([
            'name' => 'Teacher Test',
            'email' => 'teacher@test.com',
            'password' => Hash::make('password123'),
        ]);
        $this->teacher->assignRole('Teacher');
    }

    public function test_super_admin_can_view_users_index(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('users.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Users/Index')
            ->has('users.data')
            ->has('available_roles')
        );
    }

    public function test_teacher_cannot_view_users_index(): void
    {
        $response = $this->actingAs($this->teacher)->get(route('users.index'));
        $response->assertStatus(403);
    }

    public function test_super_admin_can_create_user_with_role(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('users.store'), [
            'name' => 'Guru Baru BK',
            'email' => 'bkbaru@test.com',
            'role' => 'BK',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'bkbaru@test.com',
            'name' => 'Guru Baru BK',
        ]);

        $createdUser = User::where('email', 'bkbaru@test.com')->first();
        $this->assertTrue($createdUser->hasRole('BK'));
    }

    public function test_super_admin_can_update_user_and_change_role(): void
    {
        $targetUser = User::factory()->create();
        $targetUser->assignRole('Teacher');

        $response = $this->actingAs($this->superAdmin)->put(route('users.update', $targetUser), [
            'name' => 'Nama Baru',
            'email' => 'updated@test.com',
            'role' => 'Tata Tertib',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'Nama Baru',
            'email' => 'updated@test.com',
        ]);

        $targetUser->refresh();
        $this->assertTrue($targetUser->hasRole('Tata Tertib'));
        $this->assertFalse($targetUser->hasRole('Teacher'));
    }

    public function test_super_admin_can_reset_user_password(): void
    {
        $targetUser = User::factory()->create([
            'password' => Hash::make('oldpassword'),
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('users.reset-password', $targetUser), [
            'password' => 'newsecret123',
            'password_confirmation' => 'newsecret123',
        ]);

        $response->assertSessionHas('success');
        $targetUser->refresh();
        $this->assertTrue(Hash::check('newsecret123', $targetUser->password));
    }

    public function test_super_admin_cannot_delete_own_account(): void
    {
        $response = $this->actingAs($this->superAdmin)->delete(route('users.destroy', $this->superAdmin));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id]);
    }

    public function test_super_admin_cannot_delete_last_super_admin(): void
    {
        // Only one super admin in DB right now
        $response = $this->actingAs($this->superAdmin)->delete(route('users.destroy', $this->superAdmin));
        $response->assertSessionHas('error');
    }

    public function test_super_admin_can_delete_other_user(): void
    {
        $targetUser = User::factory()->create();
        $targetUser->assignRole('Teacher');

        $response = $this->actingAs($this->superAdmin)->delete(route('users.destroy', $targetUser));
        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseMissing('users', ['id' => $targetUser->id]);
    }
}
