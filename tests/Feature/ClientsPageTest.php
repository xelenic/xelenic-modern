<?php

use App\Models\Client;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

test('our clients page lists every published client, stories first', function () {
    $plain = Client::factory()->create(['name' => 'Plain Brand', 'sort_order' => 1]);
    $story = Client::factory()->create([
        'name' => 'Story Brand',
        'sort_order' => 2,
        'summary' => 'A spin-and-win campaign that filled the mall.',
        'article' => 'The whole story.',
        'image_path' => 'clients/story-brand/photo.jpg',
    ]);
    Client::factory()->create(['name' => 'Hidden Brand', 'is_published' => false]);

    $this->get(route('clients.index'))
        ->assertOk()
        ->assertSee('Our Clients')
        ->assertSeeText('2 brands')
        ->assertSeeText('1 client story')
        ->assertSeeInOrder(['What we’ve built together', 'Story Brand', 'A spin-and-win campaign that filled the mall.', 'Read the story', 'The companies we work with', 'Plain Brand', 'Story Brand'], false)
        ->assertSee(route('clients.show', $plain))
        ->assertSee(route('clients.show', $story))
        ->assertSee($story->imageUrl())
        ->assertSee('aria-label="Story Brand: read the story"', false)
        ->assertSee('aria-label="Plain Brand"', false)
        ->assertDontSee('Hidden Brand');
});

test('without stories it still shows every logo', function () {
    Client::factory()->count(3)->create();

    $this->get(route('clients.index'))
        ->assertOk()
        ->assertDontSee('What we’ve built together')
        ->assertSee('The companies we work with')
        ->assertSee('data-client=', false);
});

test('with no clients yet the page still works', function () {
    $this->get(route('clients.index'))->assertOk()->assertSee('Our client list is on its way.');
});

test('the menu, the home page and each client page lead to it', function () {
    $client = Client::factory()->create();

    $this->get('/')
        ->assertSee('href="'.route('clients.index').'"', false)
        ->assertSee('See all our clients');

    $this->get(route('clients.show', $client))
        ->assertSee('<a href="'.route('clients.index').'" class="story-back">', false);
});

test('stories with a photo come first, the first three as large rows', function () {
    foreach (['Alpha', 'Bravo', 'Charlie', 'Delta'] as $i => $name) {
        Client::factory()->create(['name' => $name, 'sort_order' => $i, 'summary' => "{$name}’s story."]);
    }
    Client::factory()->create(['name' => 'Echo', 'sort_order' => 9, 'summary' => 'Echo’s story.', 'image_path' => 'clients/echo/photo.jpg']);

    $html = $this->get(route('clients.index'))->assertOk()->getContent();

    expect(substr_count($html, 'class="cl-feature reveal'))->toBe(3)
        ->and(strpos($html, 'Echo’s story.'))->toBeLessThan(strpos($html, 'Alpha’s story.'))
        ->and(substr_count($html, 'class="cl-story"'))->toBe(2);
});
