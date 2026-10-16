<?php

namespace Tests\Feature\Credential;

use App\Models\User;
use App\Models\Credential\Credential;
use App\Models\Credential\Tag;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CredentialTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Create and authenticate a test user
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'api');
    }

    /** @test */
    public function user_can_create_a_credential()
    {
        $credentialData = [
            'title' => 'Test Credential',
            'description' => 'Test Description',
            'url' => 'https://example.com',
            'username' => 'testuser',
            'password' => 'testpass123',
            'observations' => 'Test observations',
        ];

        $response = $this->postJson('/api/credentials', $credentialData);

        $response->assertCreated();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'title',
                'description',
                'url',
                'username',
                'observations',
                'tags'
            ]
        ]);

        $this->assertDatabaseHas('credentials', [
            'title' => 'Test Credential',
            'user_id' => $this->user->id
        ]);
    }

    /** @test */
    public function user_can_list_credentials()
    {
        // Create test credentials
        Credential::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson('/api/credentials');

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'description',
                    'url',
                    'username',
                    'observations'
                ]
            ],
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total'
            ]
        ]);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function user_can_show_a_credential()
    {
        $credential = Credential::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Test Credential'
        ]);

        $response = $this->getJson("/api/credentials/{$credential->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'title',
                'description',
                'url',
                'username',
                'observations',
                'tags'
            ]
        ]);

        $this->assertEquals('Test Credential', $response->json('data.title'));
    }

    /** @test */
    public function user_can_update_a_credential()
    {
        $credential = Credential::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Original Title'
        ]);

        $updatedData = [
            'title' => 'Updated Title',
            'description' => 'Updated Description'
        ];

        $response = $this->patchJson("/api/credentials/{$credential->id}", $updatedData);

        $response->assertOk();
        $this->assertEquals('Updated Title', $response->json('data.title'));
        
        // Verify in database
        $this->assertDatabaseHas('credentials', [
            'id' => $credential->id,
            'title' => 'Updated Title',
            'description' => 'Updated Description'
        ]);
    }

    /** @test */
    public function user_can_delete_a_credential()
    {
        $credential = Credential::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->deleteJson("/api/credentials/{$credential->id}");

        $response->assertOk();
        $this->assertNull($response->json('data'));
        
        // Verify soft delete
        $this->assertSoftDeleted('credentials', [
            'id' => $credential->id
        ]);
    }

    /** @test */
    public function user_can_set_and_get_password()
    {
        $credential = Credential::factory()->create([
            'user_id' => $this->user->id
        ]);

        $passwordData = [
            'password' => 'newSecurePass123'
        ];

        // Set password
        $response = $this->patchJson("/api/credentials/password/{$credential->id}", $passwordData);
        $response->assertOk();

        // Get password
        $response = $this->getJson("/api/credentials/password/{$credential->id}");
        $response->assertOk();
        $this->assertEquals('newSecurePass123', $response->json('data'));
    }

    /** @test */
    public function user_can_search_credentials()
    {
        // Create test credentials
        Credential::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Google Account',
            'username' => 'googleuser'
        ]);
        
        Credential::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'GitHub Account',
            'username' => 'githubuser'
        ]);

        // Search by title
        $response = $this->getJson("/api/credentials/search/Github");
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('GitHub Account', $response->json('data.0.title'));

        // Search by username
        $response = $this->getJson("/api/credentials/search/google");
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Google Account', $response->json('data.0.title'));
    }

    /** @test */
    public function user_cannot_access_other_users_credentials()
    {
        // Create another user and their credential
        $otherUser = User::factory()->create();
        $otherCredential = Credential::factory()->create([
            'user_id' => $otherUser->id,
            'title' => 'Other User Credential'
        ]);

        // Try to access other user's credential
        $response = $this->getJson("/api/credentials/{$otherCredential->id}");
        $response->assertNotFound(); // Should not find it due to user_id constraint in repository

        // Try to delete other user's credential
        $response = $this->deleteJson("/api/credentials/{$otherCredential->id}");
        $response->assertNotFound();
    }
}
