<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesInvoiceFixtures;
use Tests\TestCase;

class StaffDocumentUploadTest extends TestCase
{
    use CreatesInvoiceFixtures, RefreshDatabase;

    private const URL = '/api/staff/documents';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->setUpInvoiceFixtures();
    }

    public function test_staff_can_upload_a_document(): void
    {
        $file = UploadedFile::fake()->image('proof.png');

        $response = $this->withToken($this->token)->post(self::URL, ['file' => $file], ['Accept' => 'application/json']);

        $response->assertStatus(201)
            ->assertJsonPath('displayName', 'proof.png')
            ->assertJsonPath('mimeType', 'image/png')
            ->assertJsonStructure(['id', 'name', 'displayName', 'url', 'size', 'mimeType']);

        $document = Document::findOrFail($response->json('id'));

        $this->assertSame($this->account->id, $document->account_id);
        Storage::disk('public')->assertExists('documents/' . $document->name);
    }

    public function test_staff_can_upload_a_pdf(): void
    {
        $file = UploadedFile::fake()->create('invoice.pdf', 200, 'application/pdf');

        $this->withToken($this->token)->post(self::URL, ['file' => $file], ['Accept' => 'application/json'])
            ->assertStatus(201)
            ->assertJsonPath('mimeType', 'application/pdf');
    }

    public function test_upload_requires_a_file(): void
    {
        $this->withToken($this->token)->postJson(self::URL, [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_upload_rejects_non_file_values(): void
    {
        $this->withToken($this->token)->postJson(self::URL, ['file' => 'not-a-file'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_upload_rejects_files_larger_than_five_megabytes(): void
    {
        $file = UploadedFile::fake()->create('big.pdf', 5121, 'application/pdf');

        $this->withToken($this->token)->post(self::URL, ['file' => $file], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_upload_requires_authentication(): void
    {
        $file = UploadedFile::fake()->image('proof.png');

        $this->post(self::URL, ['file' => $file], ['Accept' => 'application/json'])->assertStatus(401);
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_client_and_admin_cannot_upload(): void
    {
        $file = UploadedFile::fake()->image('proof.png');

        $this->actingAs($this->client, 'client')->post(self::URL, ['file' => $file], ['Accept' => 'application/json'])->assertStatus(401);
        $this->actingAs($this->admin, 'admin')->post(self::URL, ['file' => $file], ['Accept' => 'application/json'])->assertStatus(401);
        $this->assertDatabaseCount('documents', 0);
    }
}
