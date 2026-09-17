<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'id' => Str::uuid()->toString(),
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Test',
            'last_name' => 'Admin',
        ]);
    }

    public function test_admin_can_change_password(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->putJson('/api/admin/password', [
                'currentPassword' => 'password123',
                'newPassword' => 'newpassword123',
                'newPasswordConfirmation' => 'newpassword123',
            ]);

        $response->assertStatus(204);

        $this->admin->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->admin->password));
    }

    public function test_admin_change_password_validates_current_password(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->putJson('/api/admin/password', [
                'currentPassword' => 'wrongpassword',
                'newPassword' => 'newpassword123',
                'newPasswordConfirmation' => 'newpassword123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['currentPassword']);
    }

    public function test_admin_change_password_validates_new_password_confirmation(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->putJson('/api/admin/password', [
                'currentPassword' => 'password123',
                'newPassword' => 'newpassword123',
                'newPasswordConfirmation' => 'differentpassword',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['newPassword']);
    }

    public function test_admin_change_password_validates_minimum_length(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->putJson('/api/admin/password', [
                'currentPassword' => 'password123',
                'newPassword' => 'short',
                'newPasswordConfirmation' => 'short',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['newPassword']);
    }
}
