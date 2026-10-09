<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EventGuestStatusEnum;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\EventGuestGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesEventChecklistFixtures;
use Tests\TestCase;

class ClientEventGuestTest extends TestCase
{
    use CreatesEventChecklistFixtures, RefreshDatabase;

    private EventGuestGroup $groupA;
    private EventGuestGroup $groupB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEventChecklistFixtures();

        $this->groupA = $this->makeGroup($this->event, 'Family', 1);
        $this->groupB = $this->makeGroup($this->event, 'Friends', 2);
    }

    private function makeGroup(Event $event, string $name, int $sortOrder): EventGuestGroup
    {
        return EventGuestGroup::create([
            'event_id' => $event->id,
            'name' => $name,
            'sort_order' => $sortOrder,
        ]);
    }

    private function makeGuest(
        EventGuestGroup $group,
        string $firstName = 'Guest',
        int $sortOrder = 1,
        EventGuestStatusEnum $status = EventGuestStatusEnum::Pending,
    ): EventGuest {
        return EventGuest::create([
            'event_guest_group_id' => $group->id,
            'first_name' => $firstName,
            'last_name' => 'Tester',
            'sort_order' => $sortOrder,
            'status' => $status,
        ]);
    }

    private function url(string $suffix = '', ?Event $event = null): string
    {
        return '/api/clients/events/' . ($event ?? $this->event)->id . '/guest-groups' . $suffix;
    }

    private function guestUrl(EventGuestGroup $group, ?EventGuest $guest = null, string $suffix = ''): string
    {
        return $this->url('/' . $group->id . '/guests' . ($guest !== null ? '/' . $guest->id : '') . $suffix);
    }

    private function otherEvent(): Event
    {
        return $this->createEvent($this->account);
    }

    // GET guest-groups

    public function test_client_can_list_guest_groups_sorted_with_guests(): void
    {
        $this->groupA->update(['sort_order' => 2]);
        $this->groupB->update(['sort_order' => 1]);
        $second = $this->makeGuest($this->groupB, 'Second', 2);
        $first = $this->makeGuest($this->groupB, 'First', 1);

        $this->actingAs($this->client, 'client')->getJson($this->url())
            ->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonPath('0.id', $this->groupB->id)
            ->assertJsonPath('0.name', 'Friends')
            ->assertJsonPath('0.order', 1)
            ->assertJsonPath('0.guests.0.id', $first->id)
            ->assertJsonPath('0.guests.1.id', $second->id)
            ->assertJsonPath('0.guests.0.status', 'Pending')
            ->assertJsonPath('1.id', $this->groupA->id)
            ->assertJsonPath('1.guests', []);
    }

    public function test_list_excludes_groups_and_guests_of_other_events_and_soft_deleted_guests(): void
    {
        $otherGroup = $this->makeGroup($this->otherEvent(), 'Other', 1);
        $this->makeGuest($otherGroup);
        $this->makeGuest($this->groupA, 'Deleted')->delete();

        $this->actingAs($this->client, 'client')->getJson($this->url())
            ->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonPath('0.guests', []);
    }

    public function test_list_returns_404_for_unlinked_and_missing_event(): void
    {
        $this->actingAs($this->client, 'client')->getJson($this->url('', $this->otherEvent()))->assertStatus(404);
        $this->actingAs($this->client, 'client')
            ->getJson('/api/clients/events/' . Str::uuid()->toString() . '/guest-groups')
            ->assertStatus(404);
    }

    public function test_list_requires_client_authentication(): void
    {
        $this->app['auth']->forgetGuards();
        $this->getJson($this->url())->assertStatus(401);
        $this->app['auth']->forgetGuards();
        $this->withToken($this->token)->getJson($this->url())->assertStatus(401);
    }

    // POST guest-groups

    public function test_client_can_create_guest_group_at_end_of_order(): void
    {
        $this->actingAs($this->client, 'client')->postJson($this->url(), ['name' => 'Colleagues'])
            ->assertStatus(200)
            ->assertJsonPath('name', 'Colleagues')
            ->assertJsonPath('order', 3)
            ->assertJsonPath('guests', []);

        $this->assertDatabaseHas('event_guest_groups', [
            'event_id' => $this->event->id,
            'name' => 'Colleagues',
            'sort_order' => 3,
            'created_by' => $this->client->id,
        ]);
    }

    public function test_create_guest_group_validates_name(): void
    {
        $this->actingAs($this->client, 'client')->postJson($this->url(), [])
            ->assertStatus(422)->assertJsonValidationErrors(['name']);
        $this->actingAs($this->client, 'client')->postJson($this->url(), ['name' => str_repeat('a', 256)])
            ->assertStatus(422)->assertJsonValidationErrors(['name']);

        $this->assertSame(2, EventGuestGroup::count());
    }

    public function test_create_guest_group_guards(): void
    {
        $this->actingAs($this->client, 'client')->postJson($this->url('', $this->otherEvent()), ['name' => 'X'])
            ->assertStatus(404);
        $this->app['auth']->forgetGuards();
        $this->postJson($this->url(), ['name' => 'X'])->assertStatus(401);
        $this->app['auth']->forgetGuards();
        $this->withToken($this->token)->postJson($this->url(), ['name' => 'X'])->assertStatus(401);

        $this->assertSame(2, EventGuestGroup::count());
    }

    // PUT guest-groups/{id}

    public function test_client_can_rename_guest_group(): void
    {
        $this->actingAs($this->client, 'client')->putJson($this->url('/' . $this->groupA->id), ['name' => 'Relatives'])
            ->assertStatus(200)
            ->assertJsonPath('id', $this->groupA->id)
            ->assertJsonPath('name', 'Relatives');

        $this->assertDatabaseHas('event_guest_groups', [
            'id' => $this->groupA->id,
            'name' => 'Relatives',
            'updated_by' => $this->client->id,
        ]);
    }

    public function test_rename_guest_group_validates_and_guards(): void
    {
        $url = $this->url('/' . $this->groupA->id);

        $this->actingAs($this->client, 'client')->putJson($url, [])
            ->assertStatus(422)->assertJsonValidationErrors(['name']);
        $this->actingAs($this->client, 'client')->putJson($this->url('/' . Str::uuid()->toString()), ['name' => 'X'])
            ->assertStatus(404);
        $this->actingAs($this->client, 'client')->putJson($this->url('/' . $this->groupA->id, $this->otherEvent()), ['name' => 'X'])
            ->assertStatus(404);
        $this->app['auth']->forgetGuards();
        $this->putJson($url, ['name' => 'X'])->assertStatus(401);
        $this->app['auth']->forgetGuards();
        $this->withToken($this->token)->putJson($url, ['name' => 'X'])->assertStatus(401);

        $this->assertDatabaseHas('event_guest_groups', ['id' => $this->groupA->id, 'name' => 'Family']);
    }

    public function test_rename_guest_group_of_another_event_returns_404(): void
    {
        $otherEvent = $this->otherEvent();
        $this->attachClientToEvent($otherEvent, $this->client);
        $otherGroup = $this->makeGroup($otherEvent, 'Other', 1);

        $this->actingAs($this->client, 'client')->putJson($this->url('/' . $otherGroup->id), ['name' => 'X'])
            ->assertStatus(404);
    }

    // POST guest-groups/sort

    public function test_client_can_sort_guest_groups(): void
    {
        $this->actingAs($this->client, 'client')->postJson($this->url('/sort'), [
            ['id' => $this->groupA->id, 'sortOrder' => 2],
            ['id' => $this->groupB->id, 'sortOrder' => 1],
        ])->assertStatus(200);

        $this->assertDatabaseHas('event_guest_groups', ['id' => $this->groupA->id, 'sort_order' => 2]);
        $this->assertDatabaseHas('event_guest_groups', ['id' => $this->groupB->id, 'sort_order' => 1]);
    }

    public function test_sort_guest_groups_validates_body(): void
    {
        $this->actingAs($this->client, 'client')->postJson($this->url('/sort'), [])
            ->assertStatus(422);
        $this->actingAs($this->client, 'client')->postJson($this->url('/sort'), [['id' => 'x', 'sortOrder' => -1]])
            ->assertStatus(422);
    }

    public function test_sort_guest_groups_rejects_ids_from_other_event_without_changes(): void
    {
        $otherGroup = $this->makeGroup($this->otherEvent(), 'Other', 1);

        $this->actingAs($this->client, 'client')->postJson($this->url('/sort'), [
            ['id' => $this->groupA->id, 'sortOrder' => 9],
            ['id' => $otherGroup->id, 'sortOrder' => 8],
        ])->assertStatus(422)->assertJsonValidationErrors(['1.id']);

        $this->assertDatabaseHas('event_guest_groups', ['id' => $this->groupA->id, 'sort_order' => 1]);
        $this->assertDatabaseHas('event_guest_groups', ['id' => $otherGroup->id, 'sort_order' => 1]);
    }

    public function test_sort_guest_groups_guards(): void
    {
        $payload = [['id' => $this->groupA->id, 'sortOrder' => 5]];

        $this->actingAs($this->client, 'client')->postJson($this->url('/sort', $this->otherEvent()), $payload)->assertStatus(404);
        $this->app['auth']->forgetGuards();
        $this->postJson($this->url('/sort'), $payload)->assertStatus(401);
        $this->app['auth']->forgetGuards();
        $this->withToken($this->token)->postJson($this->url('/sort'), $payload)->assertStatus(401);

        $this->assertDatabaseHas('event_guest_groups', ['id' => $this->groupA->id, 'sort_order' => 1]);
    }

    // POST guests

    public function test_client_can_create_guest_with_pending_status(): void
    {
        $this->makeGuest($this->groupA, 'Existing', 4);

        $this->actingAs($this->client, 'client')->postJson($this->guestUrl($this->groupA), [
            'firstName' => 'Ana',
            'lastName' => 'Reyes',
        ])
            ->assertStatus(200)
            ->assertJsonPath('eventGuestGroupId', $this->groupA->id)
            ->assertJsonPath('order', 5)
            ->assertJsonPath('firstName', 'Ana')
            ->assertJsonPath('middleName', null)
            ->assertJsonPath('status', 'Pending');

        $this->assertDatabaseHas('event_guests', [
            'event_guest_group_id' => $this->groupA->id,
            'first_name' => 'Ana',
            'middle_name' => null,
            'status' => 'Pending',
            'created_by' => $this->client->id,
        ]);
    }

    public function test_client_can_create_guest_with_middle_name(): void
    {
        $this->actingAs($this->client, 'client')->postJson($this->guestUrl($this->groupA), [
            'firstName' => 'Ana',
            'middleName' => 'Cruz',
            'lastName' => 'Reyes',
        ])->assertStatus(200)->assertJsonPath('middleName', 'Cruz')->assertJsonPath('order', 1);
    }

    public function test_create_guest_validates_body(): void
    {
        $this->actingAs($this->client, 'client')->postJson($this->guestUrl($this->groupA), [])
            ->assertStatus(422)->assertJsonValidationErrors(['firstName', 'lastName']);

        $this->assertSame(0, EventGuest::count());
    }

    public function test_create_guest_guards(): void
    {
        $payload = ['firstName' => 'Ana', 'lastName' => 'Reyes'];

        $this->actingAs($this->client, 'client')
            ->postJson($this->guestUrl($this->makeGroup($this->otherEvent(), 'Other', 1)), $payload)
            ->assertStatus(404);
        $this->actingAs($this->client, 'client')
            ->postJson('/api/clients/events/' . $this->event->id . '/guest-groups/' . Str::uuid()->toString() . '/guests', $payload)
            ->assertStatus(404);
        $this->app['auth']->forgetGuards();
        $this->postJson($this->guestUrl($this->groupA), $payload)->assertStatus(401);
        $this->app['auth']->forgetGuards();
        $this->withToken($this->token)->postJson($this->guestUrl($this->groupA), $payload)->assertStatus(401);

        $this->assertSame(0, EventGuest::count());
    }

    // PUT guests/{id}

    public function test_client_can_update_guest_in_same_group(): void
    {
        $guest = $this->makeGuest($this->groupA, 'Old', 3);

        $this->actingAs($this->client, 'client')->putJson($this->guestUrl($this->groupA, $guest), [
            'firstName' => 'New',
            'middleName' => 'Mid',
            'lastName' => 'Name',
            'eventGuestGroupId' => $this->groupA->id,
        ])
            ->assertStatus(200)
            ->assertJsonPath('firstName', 'New')
            ->assertJsonPath('eventGuestGroupId', $this->groupA->id)
            ->assertJsonPath('order', 3);

        $this->assertDatabaseHas('event_guests', [
            'id' => $guest->id,
            'first_name' => 'New',
            'middle_name' => 'Mid',
            'last_name' => 'Name',
            'sort_order' => 3,
            'updated_by' => $this->client->id,
        ]);
    }

    public function test_client_can_move_guest_to_another_group(): void
    {
        $guest = $this->makeGuest($this->groupA, 'Mover', 1);
        $this->makeGuest($this->groupB, 'Resident', 7);

        $this->actingAs($this->client, 'client')->putJson($this->guestUrl($this->groupA, $guest), [
            'firstName' => 'Mover',
            'lastName' => 'Tester',
            'eventGuestGroupId' => $this->groupB->id,
        ])
            ->assertStatus(200)
            ->assertJsonPath('eventGuestGroupId', $this->groupB->id)
            ->assertJsonPath('order', 8);

        $this->assertDatabaseHas('event_guests', [
            'id' => $guest->id,
            'event_guest_group_id' => $this->groupB->id,
            'sort_order' => 8,
        ]);
    }

    public function test_update_guest_validates_body(): void
    {
        $guest = $this->makeGuest($this->groupA);

        $this->actingAs($this->client, 'client')->putJson($this->guestUrl($this->groupA, $guest), [])
            ->assertStatus(422)->assertJsonValidationErrors(['firstName', 'lastName', 'eventGuestGroupId']);
        $this->actingAs($this->client, 'client')->putJson($this->guestUrl($this->groupA, $guest), [
            'firstName' => 'A', 'lastName' => 'B', 'eventGuestGroupId' => 'nope',
        ])->assertStatus(422)->assertJsonValidationErrors(['eventGuestGroupId']);
    }

    public function test_update_guest_rejects_target_group_of_another_event(): void
    {
        $guest = $this->makeGuest($this->groupA);
        $otherGroup = $this->makeGroup($this->otherEvent(), 'Other', 1);

        $this->actingAs($this->client, 'client')->putJson($this->guestUrl($this->groupA, $guest), [
            'firstName' => 'A', 'lastName' => 'B', 'eventGuestGroupId' => $otherGroup->id,
        ])->assertStatus(422)->assertJsonValidationErrors(['eventGuestGroupId']);

        $this->assertDatabaseHas('event_guests', ['id' => $guest->id, 'event_guest_group_id' => $this->groupA->id]);
    }

    public function test_update_guest_guards(): void
    {
        $guest = $this->makeGuest($this->groupA);
        $payload = ['firstName' => 'A', 'lastName' => 'B', 'eventGuestGroupId' => $this->groupA->id];

        $this->actingAs($this->client, 'client')
            ->putJson($this->guestUrl($this->groupB, $guest), $payload)->assertStatus(404);
        $this->actingAs($this->client, 'client')
            ->putJson($this->guestUrl($this->groupA) . '/' . Str::uuid()->toString(), $payload)->assertStatus(404);
        $this->app['auth']->forgetGuards();
        $this->putJson($this->guestUrl($this->groupA, $guest), $payload)->assertStatus(401);
        $this->app['auth']->forgetGuards();
        $this->withToken($this->token)->putJson($this->guestUrl($this->groupA, $guest), $payload)->assertStatus(401);

        $this->assertDatabaseHas('event_guests', ['id' => $guest->id, 'first_name' => 'Guest']);
    }

    // PUT guests/{id}/cancel

    public function test_client_can_cancel_approved_guest(): void
    {
        $guest = $this->makeGuest($this->groupA, 'Going', 1, EventGuestStatusEnum::Approved);

        $this->actingAs($this->client, 'client')
            ->putJson($this->guestUrl($this->groupA, $guest, '/cancel'), ['statusReason' => 'Cannot attend'])
            ->assertStatus(200)
            ->assertJsonPath('status', 'Cancelled');

        $this->assertDatabaseHas('event_guests', [
            'id' => $guest->id,
            'status' => 'Cancelled',
            'status_reason' => 'Cannot attend',
            'updated_by' => $this->client->id,
        ]);
    }

    public function test_cancel_rejects_non_approved_guest(): void
    {
        foreach ([EventGuestStatusEnum::Pending, EventGuestStatusEnum::Requested, EventGuestStatusEnum::Denied, EventGuestStatusEnum::Cancelled] as $status) {
            $guest = $this->makeGuest($this->groupA, $status->value, 1, $status);

            $this->actingAs($this->client, 'client')
                ->putJson($this->guestUrl($this->groupA, $guest, '/cancel'), ['statusReason' => 'Reason'])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['status']);

            $this->assertDatabaseHas('event_guests', ['id' => $guest->id, 'status' => $status->value, 'status_reason' => null]);
        }
    }

    public function test_cancel_validates_reason_and_guards(): void
    {
        $guest = $this->makeGuest($this->groupA, 'Going', 1, EventGuestStatusEnum::Approved);
        $url = $this->guestUrl($this->groupA, $guest, '/cancel');

        $this->actingAs($this->client, 'client')->putJson($url, [])
            ->assertStatus(422)->assertJsonValidationErrors(['statusReason']);
        $this->actingAs($this->client, 'client')
            ->putJson($this->guestUrl($this->groupA) . '/' . Str::uuid()->toString() . '/cancel', ['statusReason' => 'x'])
            ->assertStatus(404);
        $this->actingAs($this->client, 'client')
            ->putJson($this->guestUrl($this->groupB, $guest, '/cancel'), ['statusReason' => 'x'])
            ->assertStatus(404);
        $this->app['auth']->forgetGuards();
        $this->putJson($url, ['statusReason' => 'x'])->assertStatus(401);
        $this->app['auth']->forgetGuards();
        $this->withToken($this->token)->putJson($url, ['statusReason' => 'x'])->assertStatus(401);

        $this->assertDatabaseHas('event_guests', ['id' => $guest->id, 'status' => 'Approved']);
    }

    // POST guests/sort

    public function test_client_can_sort_guests_in_group(): void
    {
        $one = $this->makeGuest($this->groupA, 'One', 1);
        $two = $this->makeGuest($this->groupA, 'Two', 2);

        $this->actingAs($this->client, 'client')->postJson($this->guestUrl($this->groupA, null, '/sort'), [
            ['id' => $one->id, 'sortOrder' => 2],
            ['id' => $two->id, 'sortOrder' => 1],
        ])->assertStatus(200);

        $this->assertDatabaseHas('event_guests', ['id' => $one->id, 'sort_order' => 2]);
        $this->assertDatabaseHas('event_guests', ['id' => $two->id, 'sort_order' => 1]);
    }

    public function test_sort_guests_rejects_ids_from_other_group_without_changes(): void
    {
        $one = $this->makeGuest($this->groupA, 'One', 1);
        $foreign = $this->makeGuest($this->groupB, 'Foreign', 1);

        $this->actingAs($this->client, 'client')->postJson($this->guestUrl($this->groupA, null, '/sort'), [
            ['id' => $one->id, 'sortOrder' => 5],
            ['id' => $foreign->id, 'sortOrder' => 6],
        ])->assertStatus(422)->assertJsonValidationErrors(['1.id']);

        $this->assertDatabaseHas('event_guests', ['id' => $one->id, 'sort_order' => 1]);
        $this->assertDatabaseHas('event_guests', ['id' => $foreign->id, 'sort_order' => 1]);
    }

    public function test_sort_guests_validates_and_guards(): void
    {
        $one = $this->makeGuest($this->groupA);
        $payload = [['id' => $one->id, 'sortOrder' => 3]];
        $url = $this->guestUrl($this->groupA, null, '/sort');

        $this->actingAs($this->client, 'client')->postJson($url, [])->assertStatus(422);
        $this->actingAs($this->client, 'client')
            ->postJson('/api/clients/events/' . $this->event->id . '/guest-groups/' . Str::uuid()->toString() . '/guests/sort', $payload)
            ->assertStatus(404);
        $this->actingAs($this->client, 'client')
            ->postJson($this->url('/' . $this->groupA->id . '/guests/sort', $this->otherEvent()), $payload)
            ->assertStatus(404);
        $this->app['auth']->forgetGuards();
        $this->postJson($url, $payload)->assertStatus(401);
        $this->app['auth']->forgetGuards();
        $this->withToken($this->token)->postJson($url, $payload)->assertStatus(401);

        $this->assertDatabaseHas('event_guests', ['id' => $one->id, 'sort_order' => 1]);
    }

    // DELETE guests/{id}

    public function test_client_can_delete_non_approved_guests(): void
    {
        foreach ([EventGuestStatusEnum::Pending, EventGuestStatusEnum::Requested, EventGuestStatusEnum::Denied, EventGuestStatusEnum::Cancelled] as $status) {
            $guest = $this->makeGuest($this->groupA, $status->value, 1, $status);

            $this->actingAs($this->client, 'client')->deleteJson($this->guestUrl($this->groupA, $guest))
                ->assertStatus(200);

            $this->assertSoftDeleted('event_guests', ['id' => $guest->id]);
        }
    }

    public function test_delete_rejects_approved_guest(): void
    {
        $guest = $this->makeGuest($this->groupA, 'Going', 1, EventGuestStatusEnum::Approved);

        $this->actingAs($this->client, 'client')->deleteJson($this->guestUrl($this->groupA, $guest))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $this->assertDatabaseHas('event_guests', ['id' => $guest->id, 'deleted_at' => null]);
    }

    public function test_delete_guest_guards(): void
    {
        $guest = $this->makeGuest($this->groupA);

        $this->actingAs($this->client, 'client')->deleteJson($this->guestUrl($this->groupA) . '/' . Str::uuid()->toString())
            ->assertStatus(404);
        $this->actingAs($this->client, 'client')->deleteJson($this->guestUrl($this->groupB, $guest))
            ->assertStatus(404);
        $this->actingAs($this->client, 'client')
            ->deleteJson($this->url('/' . $this->groupA->id . '/guests/' . $guest->id, $this->otherEvent()))
            ->assertStatus(404);
        $this->app['auth']->forgetGuards();
        $this->deleteJson($this->guestUrl($this->groupA, $guest))->assertStatus(401);
        $this->app['auth']->forgetGuards();
        $this->withToken($this->token)->deleteJson($this->guestUrl($this->groupA, $guest))->assertStatus(401);

        $this->assertDatabaseHas('event_guests', ['id' => $guest->id, 'deleted_at' => null]);
    }
}
