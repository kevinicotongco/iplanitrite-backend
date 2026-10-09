<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\EventGuest;
use App\Models\EventGuestGroup;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesEventChecklistFixtures;
use Tests\TestCase;

class EventGuestAuditColumnsTest extends TestCase
{
    use CreatesEventChecklistFixtures, RefreshDatabase;

    private EventGuestGroup $group;
    private EventGuest $guest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEventChecklistFixtures();

        $this->group = EventGuestGroup::create([
            'event_id' => $this->event->id,
            'name' => 'Family',
            'sort_order' => 1,
            'created_by' => $this->client->id,
            'updated_by' => $this->client->id,
        ]);

        $this->guest = EventGuest::create([
            'event_guest_group_id' => $this->group->id,
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'sort_order' => 1,
            'created_by' => $this->client->id,
            'updated_by' => $this->client->id,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function foreignKeyTargets(string $table): array
    {
        $rows = DB::select(
            "SELECT a.attname AS column_name, c.confrelid::regclass::text AS referenced_table
             FROM pg_constraint c
             JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY (c.conkey)
             WHERE c.contype = 'f' AND c.conrelid = ?::regclass",
            [$table]
        );

        $targets = [];

        foreach ($rows as $row) {
            $targets[$row->column_name] = $row->referenced_table;
        }

        return $targets;
    }

    public function test_event_guests_audit_columns_are_foreign_keys_to_clients(): void
    {
        $targets = $this->foreignKeyTargets('event_guests');

        $this->assertSame('clients', $targets['created_by'] ?? null);
        $this->assertSame('clients', $targets['updated_by'] ?? null);
    }

    public function test_event_guest_groups_audit_columns_are_foreign_keys_to_clients(): void
    {
        $targets = $this->foreignKeyTargets('event_guest_groups');

        $this->assertSame('clients', $targets['created_by'] ?? null);
        $this->assertSame('clients', $targets['updated_by'] ?? null);
    }

    public function test_event_guest_creator_and_updater_resolve_to_clients(): void
    {
        $this->assertInstanceOf(Client::class, $this->guest->creator);
        $this->assertSame($this->client->id, $this->guest->creator->id);
        $this->assertInstanceOf(Client::class, $this->guest->updater);
        $this->assertSame($this->client->id, $this->guest->updater->id);
    }

    public function test_event_guest_group_creator_and_updater_resolve_to_clients(): void
    {
        $this->assertInstanceOf(Client::class, $this->group->creator);
        $this->assertSame($this->client->id, $this->group->creator->id);
        $this->assertInstanceOf(Client::class, $this->group->updater);
        $this->assertSame($this->client->id, $this->group->updater->id);
    }

    public function test_event_guest_rejects_created_by_that_is_not_a_client(): void
    {
        $this->expectException(QueryException::class);

        EventGuest::create([
            'event_guest_group_id' => $this->group->id,
            'first_name' => 'Bad',
            'last_name' => 'Actor',
            'sort_order' => 2,
            'created_by' => $this->staff->id,
        ]);
    }

    public function test_event_guest_rejects_updated_by_that_does_not_exist(): void
    {
        $this->expectException(QueryException::class);

        $this->guest->update(['updated_by' => Str::uuid()->toString()]);
    }

    public function test_event_guest_group_rejects_created_by_that_is_not_a_client(): void
    {
        $this->expectException(QueryException::class);

        EventGuestGroup::create([
            'event_id' => $this->event->id,
            'name' => 'Bad',
            'sort_order' => 2,
            'created_by' => $this->staff->id,
        ]);
    }

    public function test_deleting_client_nulls_audit_columns_instead_of_deleting_guests(): void
    {
        $this->event->clients()->detach($this->client->id);
        $this->client->forceDelete();

        $this->assertDatabaseHas('event_guests', [
            'id' => $this->guest->id,
            'created_by' => null,
            'updated_by' => null,
        ]);
        $this->assertDatabaseHas('event_guest_groups', [
            'id' => $this->group->id,
            'created_by' => null,
            'updated_by' => null,
        ]);
    }

    public function test_client_routes_store_the_authenticated_client_in_audit_columns(): void
    {
        $this->actingAs($this->client, 'client')
            ->postJson('/api/clients/events/' . $this->event->id . '/guest-groups/' . $this->group->id . '/guests', [
                'firstName' => 'Ben',
                'lastName' => 'Cruz',
            ])
            ->assertStatus(200);

        $created = EventGuest::where('first_name', 'Ben')->firstOrFail();

        $this->assertSame($this->client->id, $created->created_by);
        $this->assertSame($this->client->id, $created->updated_by);
        $this->assertInstanceOf(Client::class, $created->creator);
    }
}
