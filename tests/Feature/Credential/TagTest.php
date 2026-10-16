<?php

namespace Tests\Feature\Credential;

use App\Models\User;
use App\Models\Credential\Tag;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TagTest extends TestCase
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
    public function user_can_create_a_tag()
    {
        $tagData = [
            'name' => 'Work',
            'slug' => 'work',
            'description' => 'Work related credentials'
        ];

        $response = $this->postJson('/api/tags', $tagData);

        $response->assertCreated();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'name',
                'slug',
                'description'
            ]
        ]);

        $this->assertDatabaseHas('tags', [
            'name' => 'Work',
            'user_id' => $this->user->id
        ]);
    }

    /** @test */
    public function user_can_list_tags()
    {
        // Create test tags
        Tag::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson('/api/tags');

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'slug',
                    'description'
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
    public function user_can_show_a_tag()
    {
        $tag = Tag::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Personal'
        ]);

        $response = $this->getJson("/api/tags/{$tag->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'name',
                'slug',
                'description'
            ]
        ]);

        $this->assertEquals('Personal', $response->json('data.name'));
    }

    /** @test */
    public function user_can_update_a_tag()
    {
        $tag = Tag::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Original Name'
        ]);

        $updatedData = [
            'name' => 'Updated Name',
            'description' => 'Updated Description'
        ];

        $response = $this->patchJson("/api/tags/{$tag->id}", $updatedData);

        $response->assertOk();
        $this->assertEquals('Updated Name', $response->json('data.name'));
        
        // Verify in database
        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'Updated Name',
            'description' => 'Updated Description'
        ]);
    }

    /** @test */
    public function user_can_delete_a_tag()
    {
        $tag = Tag::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->deleteJson("/api/tags/{$tag->id}");

        $response->assertOk();
        $this->assertNull($response->json('data'));
        
        // Verify soft delete
        $this->assertSoftDeleted('tags', [
            'id' => $tag->id
        ]);
    }

    /** @test */
    public function user_can_search_tags()
    {
        // Create test tags
        Tag::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Work Projects'
        ]);
        
        Tag::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Personal Finance'
        ]);

        // Search by name
        $response = $this->getJson("/api/tags/search/Finance");
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Personal Finance', $response->json('data.0.name'));

        // Search by partial match
        $response = $this->getJson("/api/tags/search/work");
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Work Projects', $response->json('data.0.name'));
    }

    /** @test */
    public function user_cannot_access_other_users_tags()
    {
        // Create another user and their tag
        $otherUser = User::factory()->create();
        $otherTag = Tag::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'Other User Tag'
        ]);

        // Try to access other user's tag
        $response = $this->getJson("/api/tags/{$otherTag->id}");
        $response->assertNotFound(); // Should not find it due to user_id constraint in repository

        // Try to delete other user's tag
        $response = $this->deleteJson("/api/tags/{$otherTag->id}");
        $response->assertNotFound();
    }
}
