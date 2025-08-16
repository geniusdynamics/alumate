<?php

use App\Models\User;
use App\Models\Forum;
use App\Models\ForumTopic;
use App\Models\ForumPost;
use App\Models\ForumTag;

test('user can view forums list', function () {
    $user = User::factory()->create();
    
    // Create a public forum
    $forum = Forum::create([
        'name' => 'General Discussion',
        'description' => 'A place for general discussions',
        'slug' => 'general-discussion',
        'visibility' => 'public',
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->getJson('/api/forums');

    $response->assertStatus(200)
             ->assertJson([
                 'success' => true,
             ])
             ->assertJsonStructure([
                 'success',
                 'data' => [
                     '*' => [
                         'id',
                         'name',
                         'description',
                         'slug',
                         'visibility',
                         'topics_count',
                         'posts_count',
                     ]
                 ]
             ]);
});

test('user can create a forum topic', function () {
    $user = User::factory()->create();
    
    $forum = Forum::create([
        'name' => 'Test Forum',
        'slug' => 'test-forum',
        'visibility' => 'public',
        'is_active' => true,
    ]);

    $topicData = [
        'title' => 'Test Topic',
        'content' => 'This is a test topic content.',
        'tags' => ['test', 'discussion'],
    ];

    $response = $this->actingAs($user)
                     ->postJson("/api/forums/{$forum->id}/topics", $topicData);

    $response->assertStatus(201)
             ->assertJson([
                 'success' => true,
                 'message' => 'Topic created successfully.',
             ]);

    $this->assertDatabaseHas('forum_topics', [
        'forum_id' => $forum->id,
        'user_id' => $user->id,
        'title' => 'Test Topic',
        'content' => 'This is a test topic content.',
    ]);
});

test('user can reply to a forum topic', function () {
    $user = User::factory()->create();
    
    $forum = Forum::create([
        'name' => 'Test Forum',
        'slug' => 'test-forum',
        'visibility' => 'public',
        'is_active' => true,
    ]);

    $topic = ForumTopic::create([
        'forum_id' => $forum->id,
        'user_id' => $user->id,
        'title' => 'Test Topic',
        'slug' => 'test-topic',
        'content' => 'Original topic content',
        'is_approved' => true,
    ]);

    $postData = [
        'content' => 'This is a reply to the topic.',
    ];

    $response = $this->actingAs($user)
                     ->postJson("/api/topics/{$topic->id}/posts", $postData);

    $response->assertStatus(201)
             ->assertJson([
                 'success' => true,
                 'message' => 'Post created successfully.',
             ]);

    $this->assertDatabaseHas('forum_posts', [
        'topic_id' => $topic->id,
        'user_id' => $user->id,
        'content' => 'This is a reply to the topic.',
    ]);
});

test('user can like a forum post', function () {
    $user = User::factory()->create();
    
    $forum = Forum::create([
        'name' => 'Test Forum',
        'slug' => 'test-forum',
        'visibility' => 'public',
        'is_active' => true,
    ]);

    $topic = ForumTopic::create([
        'forum_id' => $forum->id,
        'user_id' => $user->id,
        'title' => 'Test Topic',
        'slug' => 'test-topic',
        'content' => 'Original topic content',
        'is_approved' => true,
    ]);

    $post = ForumPost::create([
        'topic_id' => $topic->id,
        'user_id' => $user->id,
        'content' => 'Test post content',
        'is_approved' => true,
    ]);

    $response = $this->actingAs($user)
                     ->postJson("/api/posts/{$post->id}/like");

    $response->assertStatus(200)
             ->assertJson([
                 'success' => true,
                 'data' => [
                     'liked' => true,
                 ],
             ]);

    $this->assertDatabaseHas('forum_post_likes', [
        'post_id' => $post->id,
        'user_id' => $user->id,
        'type' => 'like',
    ]);
});

test('user can search forum topics', function () {
    $user = User::factory()->create();
    
    $forum = Forum::create([
        'name' => 'Test Forum',
        'slug' => 'test-forum',
        'visibility' => 'public',
        'is_active' => true,
    ]);

    $topic = ForumTopic::create([
        'forum_id' => $forum->id,
        'user_id' => $user->id,
        'title' => 'Laravel Discussion',
        'slug' => 'laravel-discussion',
        'content' => 'Let\'s talk about Laravel framework',
        'is_approved' => true,
    ]);

    $response = $this->actingAs($user)
                     ->getJson('/api/forums/search?query=Laravel');

    $response->assertStatus(200)
             ->assertJson([
                 'success' => true,
                 'query' => 'Laravel',
             ])
             ->assertJsonStructure([
                 'success',
                 'data' => [
                     '*' => [
                         'id',
                         'title',
                         'content',
                         'forum',
                         'user',
                     ]
                 ],
                 'query',
                 'total',
             ]);
});
