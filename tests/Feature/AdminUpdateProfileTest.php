<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesBase64Images;
use Tests\TestCase;

class AdminUpdateProfileTest extends TestCase
{
    use CreatesBase64Images, RefreshDatabase;

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

    public function test_admin_can_update_avatar_with_base64_png(): void
    {
        $avatar = $this->base64Png();

        $this->actingAs($this->admin, 'admin')
            ->putJson('/api/admin/profile', ['avatar' => $avatar, 'firstName' => 'Updated', 'lastName' => 'Name'])
            ->assertStatus(200)
            ->assertJsonPath('avatar', $avatar);

        $this->assertDatabaseHas('admins', ['id' => $this->admin->id, 'avatar' => $avatar]);
    }

    public function test_admin_can_update_avatar_with_base64_jpeg_data_uri(): void
    {
        $avatar = 'data:image/jpeg;base64,' . $this->base64Jpeg();

        $this->actingAs($this->admin, 'admin')
            ->putJson('/api/admin/profile', ['avatar' => $avatar, 'firstName' => 'Updated', 'lastName' => 'Name'])
            ->assertStatus(200)
            ->assertJsonPath('avatar', $avatar);
    }

    public function test_admin_update_rejects_invalid_avatars(): void
    {
        foreach ($this->invalidAvatarPayloads() as [$payload]) {
            $this->actingAs($this->admin, 'admin')
                ->putJson('/api/admin/profile', ['avatar' => $payload, 'firstName' => 'Updated', 'lastName' => 'Name'])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['avatar']);
        }

        $this->assertDatabaseHas('admins', ['id' => $this->admin->id, 'avatar' => null]);
    }
}
