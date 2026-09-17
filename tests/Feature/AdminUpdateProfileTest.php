<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminUpdateProfileTest extends TestCase
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

    public function test_admin_can_update_profile(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->putJson('/api/admin/profile', [
                'firstName' => 'Updated',
                'lastName' => 'Name',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'email',
                'firstName',
                'lastName',
                'avatar',
            ])
            ->assertJson([
                'firstName' => 'Updated',
                'lastName' => 'Name',
            ]);

        $this->assertDatabaseHas('admins', [
            'id' => $this->admin->id,
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ]);
    }

    public function test_admin_update_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->putJson('/api/admin/profile', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['firstName', 'lastName']);
    }

    public function test_unauthenticated_user_cannot_update_admin_profile(): void
    {
        $response = $this->putJson('/api/admin/profile', [
            'firstName' => 'Updated',
            'lastName' => 'Name',
        ]);

        $response->assertStatus(401);
    }
}
