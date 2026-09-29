<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventTypeEnum;
use App\Models\EventPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesEventPricingFixtures;
use Tests\TestCase;

class StaffEventPackageManagementTest extends TestCase
{
    use CreatesEventPricingFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEventPricingFixtures();
    }

    // GET /api/staff/event-packages/{eventType}

    public function test_staff_can_list_packages_filtered_by_event_type_and_ordered_by_name(): void
    {
        $silver = $this->createPackage(EventTypeEnum::Wedding, 'Silver Package', 30000.50);
        $bronze = $this->createPackage(EventTypeEnum::Wedding, 'Bronze Package', 15000);
        $this->createPackage(EventTypeEnum::Birthday, 'Birthday Package');
        $this->createPackage(EventTypeEnum::Wedding, 'Other Account Package', account: $this->otherAccount);
        $this->createPackage(EventTypeEnum::Wedding, 'Deleted Package')->delete();

        $response = $this->withToken($this->token)
            ->getJson('/api/staff/event-packages/Wedding');

        $response->assertStatus(200)
            ->assertExactJson([
                [
                    'id' => $bronze->id,
                    'name' => 'Bronze Package',
                    'description' => 'Bronze Package description',
                    'price' => 15000,
                ],
                [
                    'id' => $silver->id,
                    'name' => 'Silver Package',
                    'description' => 'Silver Package description',
                    'price' => 30000.5,
                ],
            ]);
    }

    public function test_list_returns_empty_array_when_no_packages_exist(): void
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/staff/event-packages/Debut');

        $response->assertStatus(200)->assertExactJson([]);
    }

    public function test_list_returns_404_for_invalid_event_type(): void
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/staff/event-packages/Funeral');

        $response->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_list_packages(): void
    {
        $this->getJson('/api/staff/event-packages/Wedding')->assertStatus(401);
    }

    public function test_admin_cannot_list_packages(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->getJson('/api/staff/event-packages/Wedding')
            ->assertStatus(401);
    }

    public function test_client_cannot_list_packages(): void
    {
        $this->actingAs($this->client, 'client')
            ->getJson('/api/staff/event-packages/Wedding')
            ->assertStatus(401);
    }

    // POST /api/staff/event-packages/{eventType}

    public function test_staff_can_create_package_with_event_type_from_path(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/staff/event-packages/Baptism', [
                'name' => 'Premium Package',
                'description' => 'Everything included',
                'price' => 12500.75,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'name' => 'Premium Package',
                'description' => 'Everything included',
                'price' => 12500.75,
            ])
            ->assertJsonStructure(['id', 'name', 'description', 'price']);

        $this->assertDatabaseHas('event_packages', [
            'id' => $response->json('id'),
            'account_id' => $this->account->id,
            'event_type' => EventTypeEnum::Baptism->value,
            'name' => 'Premium Package',
            'description' => 'Everything included',
            'price' => 12500.75,
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
            'deleted_at' => null,
        ]);
    }

    public function test_create_package_ignores_account_and_event_type_from_body(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/staff/event-packages/Debut', [
                'name' => 'Debut Package',
                'description' => 'Debut',
                'price' => 100,
                'accountId' => $this->otherAccount->id,
                'eventType' => EventTypeEnum::Wedding->value,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('event_packages', [
            'id' => $response->json('id'),
            'account_id' => $this->account->id,
            'event_type' => EventTypeEnum::Debut->value,
        ]);
    }

    public function test_create_package_validates_required_fields(): void
    {
        $this->withToken($this->token)
            ->postJson('/api/staff/event-packages/Wedding', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'description', 'price']);

        $this->assertDatabaseCount('event_packages', 0);
    }

    public function test_create_package_validates_price_limits(): void
    {
        foreach ([-1, 100000000, 10.555, 'abc'] as $invalidPrice) {
            $this->withToken($this->token)
                ->postJson('/api/staff/event-packages/Wedding', [
                    'name' => 'Package',
                    'description' => 'Description',
                    'price' => $invalidPrice,
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['price']);
        }

        $this->assertDatabaseCount('event_packages', 0);
    }

    public function test_create_package_validates_name_length(): void
    {
        $this->withToken($this->token)
            ->postJson('/api/staff/event-packages/Wedding', [
                'name' => str_repeat('a', 256),
                'description' => 'Description',
                'price' => 100,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_create_package_returns_404_for_invalid_event_type(): void
    {
        $this->withToken($this->token)
            ->postJson('/api/staff/event-packages/Funeral', [
                'name' => 'Package',
                'description' => 'Description',
                'price' => 100,
            ])
            ->assertStatus(404);

        $this->assertDatabaseCount('event_packages', 0);
    }

    public function test_unauthenticated_user_cannot_create_package(): void
    {
        $this->postJson('/api/staff/event-packages/Wedding', [
            'name' => 'Package',
            'description' => 'Description',
            'price' => 100,
        ])->assertStatus(401);

        $this->assertDatabaseCount('event_packages', 0);
    }

    public function test_client_cannot_create_package(): void
    {
        $this->actingAs($this->client, 'client')
            ->postJson('/api/staff/event-packages/Wedding', [
                'name' => 'Package',
                'description' => 'Description',
                'price' => 100,
            ])
            ->assertStatus(401);

        $this->assertDatabaseCount('event_packages', 0);
    }

    // PUT /api/staff/event-packages/{eventType}/{eventPackageId}

    public function test_staff_can_update_package(): void
    {
        $package = $this->createPackage(EventTypeEnum::Wedding, 'Old Name', 1000);

        $response = $this->withToken($this->token)
            ->putJson("/api/staff/event-packages/Wedding/{$package->id}", [
                'name' => 'New Name',
                'description' => 'New description',
                'price' => 2000.25,
            ]);

        $response->assertStatus(200)
            ->assertExactJson([
                'id' => $package->id,
                'name' => 'New Name',
                'description' => 'New description',
                'price' => 2000.25,
            ]);

        $this->assertDatabaseHas('event_packages', [
            'id' => $package->id,
            'event_type' => EventTypeEnum::Wedding->value,
            'name' => 'New Name',
            'description' => 'New description',
            'price' => 2000.25,
            'updated_by' => $this->staff->id,
        ]);
    }

    public function test_update_package_validates_required_fields(): void
    {
        $package = $this->createPackage(EventTypeEnum::Wedding, 'Old Name', 1000);

        $this->withToken($this->token)
            ->putJson("/api/staff/event-packages/Wedding/{$package->id}", ['price' => -5])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'description', 'price']);

        $this->assertDatabaseHas('event_packages', ['id' => $package->id, 'name' => 'Old Name']);
    }

    public function test_update_package_returns_404_when_event_type_does_not_match(): void
    {
        $package = $this->createPackage(EventTypeEnum::Wedding, 'Old Name');

        $this->withToken($this->token)
            ->putJson("/api/staff/event-packages/Birthday/{$package->id}", [
                'name' => 'New Name',
                'description' => 'New description',
                'price' => 100,
            ])
            ->assertStatus(404);

        $this->assertDatabaseHas('event_packages', ['id' => $package->id, 'name' => 'Old Name']);
    }

    public function test_update_package_returns_404_for_other_account_package(): void
    {
        $package = $this->createPackage(EventTypeEnum::Wedding, 'Old Name', account: $this->otherAccount);

        $this->withToken($this->token)
            ->putJson("/api/staff/event-packages/Wedding/{$package->id}", [
                'name' => 'New Name',
                'description' => 'New description',
                'price' => 100,
            ])
            ->assertStatus(404);

        $this->assertDatabaseHas('event_packages', ['id' => $package->id, 'name' => 'Old Name']);
    }

    public function test_update_package_returns_404_for_deleted_or_missing_package(): void
    {
        $package = $this->createPackage(EventTypeEnum::Wedding);
        $package->delete();

        $payload = ['name' => 'New Name', 'description' => 'New description', 'price' => 100];

        $this->withToken($this->token)
            ->putJson("/api/staff/event-packages/Wedding/{$package->id}", $payload)
            ->assertStatus(404);

        $this->withToken($this->token)
            ->putJson('/api/staff/event-packages/Wedding/' . Str::uuid()->toString(), $payload)
            ->assertStatus(404);

        $this->withToken($this->token)
            ->putJson('/api/staff/event-packages/Wedding/not-a-uuid', $payload)
            ->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_update_package(): void
    {
        $package = $this->createPackage(EventTypeEnum::Wedding, 'Old Name');

        $this->putJson("/api/staff/event-packages/Wedding/{$package->id}", [
            'name' => 'New Name',
            'description' => 'New description',
            'price' => 100,
        ])->assertStatus(401);

        $this->assertDatabaseHas('event_packages', ['id' => $package->id, 'name' => 'Old Name']);
    }

    // DELETE /api/staff/event-packages/{eventType}/{eventPackageId}

    public function test_staff_can_soft_delete_package(): void
    {
        $package = $this->createPackage(EventTypeEnum::Wedding);

        $this->withToken($this->token)
            ->deleteJson("/api/staff/event-packages/Wedding/{$package->id}")
            ->assertStatus(200);

        $this->assertSoftDeleted('event_packages', [
            'id' => $package->id,
            'updated_by' => $this->staff->id,
        ]);

        $this->withToken($this->token)
            ->getJson('/api/staff/event-packages/Wedding')
            ->assertStatus(200)
            ->assertExactJson([]);
    }

    public function test_deleting_package_in_use_keeps_it_on_existing_events(): void
    {
        $package = $this->createPackage(EventTypeEnum::Wedding, 'Legacy Package', 999);
        $event = $this->createEvent($package);

        $this->withToken($this->token)
            ->deleteJson("/api/staff/event-packages/Wedding/{$package->id}")
            ->assertStatus(200);

        $this->assertDatabaseHas('events', ['id' => $event->id, 'event_package_id' => $package->id]);

        $response = $this->withToken($this->token)->getJson('/api/staff/events');

        $response->assertStatus(200)
            ->assertJsonPath('0.package', [
                'id' => $package->id,
                'name' => 'Legacy Package',
                'description' => 'Legacy Package description',
                'price' => 999,
            ]);
    }

    public function test_delete_package_returns_404_when_event_type_does_not_match(): void
    {
        $package = $this->createPackage(EventTypeEnum::Wedding);

        $this->withToken($this->token)
            ->deleteJson("/api/staff/event-packages/Birthday/{$package->id}")
            ->assertStatus(404);

        $this->assertNotSoftDeleted('event_packages', ['id' => $package->id]);
    }

    public function test_delete_package_returns_404_for_other_account_package(): void
    {
        $package = $this->createPackage(EventTypeEnum::Wedding, account: $this->otherAccount);

        $this->withToken($this->token)
            ->deleteJson("/api/staff/event-packages/Wedding/{$package->id}")
            ->assertStatus(404);

        $this->assertNotSoftDeleted('event_packages', ['id' => $package->id]);
    }

    public function test_delete_package_returns_404_for_missing_package(): void
    {
        $this->withToken($this->token)
            ->deleteJson('/api/staff/event-packages/Wedding/' . Str::uuid()->toString())
            ->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_delete_package(): void
    {
        $package = $this->createPackage(EventTypeEnum::Wedding);

        $this->deleteJson("/api/staff/event-packages/Wedding/{$package->id}")->assertStatus(401);

        $this->assertNotSoftDeleted('event_packages', ['id' => $package->id]);
    }

    public function test_admin_cannot_delete_package(): void
    {
        $package = $this->createPackage(EventTypeEnum::Wedding);

        $this->actingAs($this->admin, 'admin')
            ->deleteJson("/api/staff/event-packages/Wedding/{$package->id}")
            ->assertStatus(401);

        $this->assertNotSoftDeleted('event_packages', ['id' => $package->id]);
        $this->assertSame(1, EventPackage::count());
    }
}
