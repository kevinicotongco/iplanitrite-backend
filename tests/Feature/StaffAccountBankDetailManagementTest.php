<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AccountRolePermissionEnum;
use App\Models\AccountBankDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesInvoiceFixtures;
use Tests\TestCase;

class StaffAccountBankDetailManagementTest extends TestCase
{
    use CreatesInvoiceFixtures, RefreshDatabase;

    private const URL = '/api/staff/bank-details';

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpInvoiceFixtures();
        $this->grantPermissions($this->staff, AccountRolePermissionEnum::cases());
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'bankName' => 'BDO',
            'accountNumber' => '1234567890',
            'qrCode' => 'https://example.com/qr.png',
        ], $overrides);
    }

    // GET /api/staff/bank-details

    public function test_staff_can_list_bank_details_for_own_account_only(): void
    {
        $mine = $this->createBankDetail();
        $otherStaff = $this->createStaff($this->otherAccount, 'other@test.com');
        $this->createBankDetail($this->otherAccount, $otherStaff);

        $this->withToken($this->token)->getJson(self::URL)
            ->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $mine->id)
            ->assertJsonStructure(['*' => ['id', 'bankName', 'accountNumber', 'qrCode']]);
    }

    public function test_list_bank_details_excludes_deleted(): void
    {
        $this->createBankDetail()->delete();

        $this->withToken($this->token)->getJson(self::URL)->assertStatus(200)->assertJsonCount(0);
    }

    public function test_list_bank_details_requires_authentication(): void
    {
        $this->getJson(self::URL)->assertStatus(401);
    }

    public function test_client_cannot_list_bank_details(): void
    {
        $this->actingAs($this->client, 'client')->getJson(self::URL)->assertStatus(401);
    }

    public function test_list_bank_details_requires_permission(): void
    {
        $staff = $this->createStaff($this->account, 'noperm@test.com');
        $token = $staff->createToken('t')->plainTextToken;

        $this->withToken($token)->getJson(self::URL)->assertStatus(403);
    }

    // POST /api/staff/bank-details

    public function test_staff_can_create_bank_detail(): void
    {
        $this->withToken($this->token)->postJson(self::URL, $this->validPayload())
            ->assertStatus(201)
            ->assertJsonPath('bankName', 'BDO')
            ->assertJsonPath('accountNumber', '1234567890')
            ->assertJsonPath('qrCode', 'https://example.com/qr.png');

        $this->assertDatabaseHas('account_bank_details', [
            'account_id' => $this->account->id,
            'bank_name' => 'BDO',
            'account_number' => '1234567890',
            'created_by' => $this->staff->id,
            'updated_by' => $this->staff->id,
        ]);
    }

    public function test_staff_can_create_bank_detail_without_qr_code(): void
    {
        $this->withToken($this->token)->postJson(self::URL, $this->validPayload(['qrCode' => null]))
            ->assertStatus(201)
            ->assertJsonPath('qrCode', null);
    }

    public function test_create_bank_detail_validates_required_fields(): void
    {
        $this->withToken($this->token)->postJson(self::URL, [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['bankName', 'accountNumber']);

        $this->assertDatabaseCount('account_bank_details', 0);
    }

    public function test_create_bank_detail_requires_authentication(): void
    {
        $this->postJson(self::URL, $this->validPayload())->assertStatus(401);
    }

    public function test_client_cannot_create_bank_detail(): void
    {
        $this->actingAs($this->client, 'client')->postJson(self::URL, $this->validPayload())->assertStatus(401);
    }

    public function test_create_bank_detail_requires_permission(): void
    {
        $staff = $this->createStaff($this->account, 'noperm@test.com');
        $token = $staff->createToken('t')->plainTextToken;

        $this->withToken($token)->postJson(self::URL, $this->validPayload())->assertStatus(403);
        $this->assertDatabaseCount('account_bank_details', 0);
    }

    // PUT /api/staff/bank-details/{bankDetailId}

    public function test_staff_can_update_bank_detail(): void
    {
        $bankDetail = $this->createBankDetail();
        $editor = $this->createStaff($this->account, 'editor@test.com');
        $this->grantPermissions($editor, [AccountRolePermissionEnum::BankDetailUpdate]);
        $editorToken = $editor->createToken('t')->plainTextToken;

        $this->withToken($editorToken)->putJson(self::URL . '/' . $bankDetail->id, $this->validPayload(['bankName' => 'BPI']))
            ->assertStatus(200)
            ->assertJsonPath('bankName', 'BPI');

        $this->assertDatabaseHas('account_bank_details', [
            'id' => $bankDetail->id,
            'bank_name' => 'BPI',
            'created_by' => $this->staff->id,
            'updated_by' => $editor->id,
        ]);
    }

    public function test_update_bank_detail_validates_input(): void
    {
        $bankDetail = $this->createBankDetail();

        $this->withToken($this->token)->putJson(self::URL . '/' . $bankDetail->id, ['bankName' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['bankName', 'accountNumber']);
    }

    public function test_update_bank_detail_returns_404_for_unknown_id(): void
    {
        $this->withToken($this->token)->putJson(self::URL . '/' . Str::uuid(), $this->validPayload())->assertStatus(404);
    }

    public function test_update_bank_detail_returns_404_for_other_account(): void
    {
        $otherStaff = $this->createStaff($this->otherAccount, 'other@test.com');
        $other = $this->createBankDetail($this->otherAccount, $otherStaff);

        $this->withToken($this->token)->putJson(self::URL . '/' . $other->id, $this->validPayload())->assertStatus(404);
        $this->assertDatabaseHas('account_bank_details', ['id' => $other->id, 'bank_name' => $other->bank_name]);
    }

    public function test_update_bank_detail_requires_authentication(): void
    {
        $this->putJson(self::URL . '/' . Str::uuid(), $this->validPayload())->assertStatus(401);
    }

    public function test_client_cannot_update_bank_detail(): void
    {
        $this->actingAs($this->client, 'client')->putJson(self::URL . '/' . Str::uuid(), $this->validPayload())->assertStatus(401);
    }

    public function test_update_bank_detail_requires_permission(): void
    {
        $bankDetail = $this->createBankDetail();
        $staff = $this->createStaff($this->account, 'noperm@test.com');
        $token = $staff->createToken('t')->plainTextToken;

        $this->withToken($token)->putJson(self::URL . '/' . $bankDetail->id, $this->validPayload())->assertStatus(403);
    }

    // DELETE /api/staff/bank-details/{bankDetailId}

    public function test_staff_can_delete_bank_detail(): void
    {
        $bankDetail = $this->createBankDetail();

        $this->withToken($this->token)->deleteJson(self::URL . '/' . $bankDetail->id)->assertStatus(200);

        $this->assertSoftDeleted('account_bank_details', ['id' => $bankDetail->id]);
    }

    public function test_delete_bank_detail_returns_404_for_unknown_id(): void
    {
        $this->withToken($this->token)->deleteJson(self::URL . '/' . Str::uuid())->assertStatus(404);
    }

    public function test_delete_bank_detail_returns_404_for_other_account(): void
    {
        $otherStaff = $this->createStaff($this->otherAccount, 'other@test.com');
        $other = $this->createBankDetail($this->otherAccount, $otherStaff);

        $this->withToken($this->token)->deleteJson(self::URL . '/' . $other->id)->assertStatus(404);
        $this->assertNotSoftDeleted('account_bank_details', ['id' => $other->id]);
    }

    public function test_delete_bank_detail_requires_authentication(): void
    {
        $this->deleteJson(self::URL . '/' . Str::uuid())->assertStatus(401);
    }

    public function test_client_cannot_delete_bank_detail(): void
    {
        $this->actingAs($this->client, 'client')->deleteJson(self::URL . '/' . Str::uuid())->assertStatus(401);
    }

    public function test_delete_bank_detail_requires_permission(): void
    {
        $bankDetail = $this->createBankDetail();
        $staff = $this->createStaff($this->account, 'noperm@test.com');
        $token = $staff->createToken('t')->plainTextToken;

        $this->withToken($token)->deleteJson(self::URL . '/' . $bankDetail->id)->assertStatus(403);
        $this->assertNotSoftDeleted('account_bank_details', ['id' => $bankDetail->id]);
        $this->assertSame(1, AccountBankDetail::count());
    }
}
