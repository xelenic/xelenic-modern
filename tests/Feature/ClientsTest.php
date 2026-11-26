<?php

use App\Models\Client;
use App\Models\ClientMedia;
use Database\Seeders\ClientSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('public');
});

test('the home page renders without any clients', function () {
    $this->get('/')->assertOk()->assertDontSee('Trusted by brands that keep growing');
});

test('the home page shows published client logos, each linking to its page', function () {
    $story = Client::factory()->withStory()->create(['name' => 'Anchor', 'slug' => 'anchor']);
    $logoOnly = Client::factory()->create(['name' => 'Haleon', 'slug' => 'haleon']);
    Client::factory()->hidden()->create(['name' => 'Secret Co']);

    $this->get('/')
        ->assertOk()
        ->assertSee('Trusted by brands that keep growing')
        ->assertSee($story->logoUrl())
        ->assertSee('href="'.route('clients.show', $story).'"', false)
        ->assertSee($logoOnly->logoUrl())
        ->assertSee('href="'.route('clients.show', $logoOnly).'"', false)
        ->assertDontSee('Secret Co');
});

test('a client story shows the description, article, image and video', function () {
    $client = Client::factory()->create([
        'name' => 'Fonterra',
        'slug' => 'fonterra',
        'summary' => 'Self-service kiosks across 40 dairy outlets.',
        'article' => "## The challenge\n\nLong queues at **every** outlet.\n\n<script>alert('x')</script>\n\n[Bad link](javascript:alert(1))",
        'image_path' => 'clients/fonterra/cover.jpg',
        'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    ]);

    $this->get('/clients/fonterra')
        ->assertOk()
        ->assertSee('Self-service kiosks across 40 dairy outlets.')
        ->assertSee('<h2>The challenge</h2>', false)
        ->assertSee('<strong>every</strong>', false)
        ->assertDontSee('<script>', false)
        ->assertDontSee('javascript:', false)
        ->assertSee($client->imageUrl())
        ->assertSee('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ');
});

test('hidden clients have no story page', function () {
    Client::factory()->hidden()->withStory()->create(['slug' => 'secret-co']);

    $this->get('/clients/secret-co')->assertNotFound();
    $this->get('/clients/no-such-client')->assertNotFound();
});

test('admins can add a client with a logo, image, video and story', function () {
    $this->actingAs(userWithRole('admin'))->post('/admin/clients', [
        'name' => 'Mirissa Waves',
        'summary' => 'A booking kiosk for a surf school.',
        'article' => '## How it went',
        'logo' => UploadedFile::fake()->image('logo.png', 320, 140),
        'image' => UploadedFile::fake()->image('cover.jpg', 1600, 900),
        'video' => UploadedFile::fake()->create('clip.mp4', 2048, 'video/mp4'),
        'dark_logo' => '0',
        'is_published' => '1',
        'sort_order' => '3',
    ])->assertRedirect('/admin/clients');

    $client = Client::sole();

    expect($client)
        ->slug->toBe('mirissa-waves')
        ->sort_order->toBe(3)
        ->is_published->toBeTrue()
        ->and($client->hasStory())->toBeTrue();

    Storage::disk('public')->assertExists([$client->logo_path, $client->image_path, $client->video_path]);
    $this->get('/clients/mirissa-waves')->assertOk()->assertSee($client->videoUrl());
});

test('a new client needs a name and a logo, and only takes safe files and video links', function () {
    $admin = userWithRole('admin');

    $this->actingAs($admin)->post('/admin/clients', [])->assertSessionHasErrors(['name', 'logo']);

    $this->actingAs($admin)->post('/admin/clients', [
        'name' => 'Bad files',
        'logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
        'video' => UploadedFile::fake()->create('clip.exe', 10, 'application/octet-stream'),
        'video_url' => 'https://example.com/video',
    ])->assertSessionHasErrors(['logo', 'video', 'video_url']);

    expect(Client::count())->toBe(0);
});

test('editing a client replaces or removes its files', function () {
    $disk = Storage::disk('public');
    $disk->put('clients/old/logo.png', 'old logo');
    $disk->put('clients/old/cover.jpg', 'old image');

    $client = Client::factory()->create([
        'name' => 'Old Name',
        'slug' => 'old-name',
        'logo_path' => 'clients/old/logo.png',
        'image_path' => 'clients/old/cover.jpg',
    ]);

    $this->actingAs(userWithRole('admin'))->put("/admin/clients/{$client->id}", [
        'name' => 'New Name',
        'slug' => '',
        'logo' => UploadedFile::fake()->image('new-logo.webp'),
        'remove_image' => '1',
        'is_published' => '0',
    ])->assertRedirect('/admin/clients');

    $client->refresh();

    expect($client)
        ->name->toBe('New Name')
        ->slug->toBe('new-name')
        ->image_path->toBeNull()
        ->is_published->toBeFalse();

    $disk->assertMissing(['clients/old/logo.png', 'clients/old/cover.jpg']);
    $disk->assertExists($client->logo_path);
});

test('deleting a client deletes its files', function () {
    $disk = Storage::disk('public');
    $disk->put('clients/gone/logo.png', 'logo');

    $client = Client::factory()->create(['logo_path' => 'clients/gone/logo.png']);

    $this->actingAs(userWithRole('admin'))->delete("/admin/clients/{$client->id}")->assertRedirect('/admin/clients');

    expect(Client::count())->toBe(0);
    $disk->assertMissing('clients/gone/logo.png');
});

test('only people who can manage clients reach the clients admin', function () {
    $client = Client::factory()->create();

    $this->actingAs(userWithRole('admin'))->get('/admin/clients')->assertOk()->assertSee($client->name);
    $this->actingAs(userWithRole('admin'))->get('/admin/clients/create')->assertOk();
    $this->actingAs(userWithRole('admin'))->get("/admin/clients/{$client->id}/edit")->assertOk();

    $this->actingAs(userWithRole('manager'))->get('/admin/clients')->assertForbidden();
    $this->actingAs(userWithRole('user'))->get('/admin/clients')->assertForbidden();
});

test('the seeder brings in the original logos once', function () {
    $this->seed(ClientSeeder::class);
    $this->seed(ClientSeeder::class);

    expect(Client::count())->toBe(16)
        ->and(Client::where('slug', 'mindspark')->value('dark_logo'))->toBeTrue();

    Storage::disk('public')->assertExists(Client::pluck('logo_path')->all());
});

test('a client with only a logo still has a page, with the other clients to see next', function () {
    $logoOnly = Client::factory()->create(['name' => 'Haleon', 'slug' => 'haleon']);
    $story = Client::factory()->withStory()->create(['name' => 'Anchor', 'slug' => 'anchor', 'sort_order' => 9]);
    $other = Client::factory()->create(['name' => 'Redmi', 'slug' => 'redmi', 'sort_order' => 1]);
    Client::factory()->hidden()->create(['name' => 'Secret Co', 'slug' => 'secret-co']);

    $this->get('/clients/haleon')
        ->assertOk()
        ->assertSee('Our client')
        ->assertSee('Haleon is one of the brands that trust Xelenic')
        ->assertSee($logoOnly->logoUrl())
        // Clients with a story come first, then the rest; hidden ones never.
        ->assertSeeInOrder(['More of our clients', route('clients.show', $story), route('clients.show', $other)])
        ->assertDontSee('Secret Co');

    $this->get('/clients/anchor')->assertOk()->assertSee('Client story')->assertDontSee('Our client');
});

test('staff add photos, videos and video links to a client page, with captions', function () {
    $client = Client::factory()->create(['name' => 'Anchor', 'slug' => 'anchor']);

    $this->actingAs(userWithRole('admin'))->put("/admin/clients/{$client->id}", [
        'name' => 'Anchor',
        'is_published' => '1',
        'new_media' => [
            UploadedFile::fake()->image('stand.jpg', 1600, 1200),
            UploadedFile::fake()->create('launch.mp4', 4096, 'video/mp4'),
            UploadedFile::fake()->image('team.png', 1200, 900),
        ],
        'new_links' => "https://www.youtube.com/watch?v=dQw4w9WgXcQ\n\n  https://vimeo.com/76979871  ",
    ])->assertSessionHasNoErrors()->assertRedirect('/admin/clients');

    $media = $client->media()->get();
    expect($media->pluck('kind')->all())->toBe(['image', 'video', 'image', 'link', 'link'])
        ->and($media->pluck('sort_order')->all())->toBe([1, 2, 3, 4, 5])
        ->and($media[3]->url)->toBe('https://www.youtube.com/watch?v=dQw4w9WgXcQ')
        ->and($media[4]->provider())->toBe('Vimeo');
    Storage::disk('public')->assertExists($media->whereNotNull('path')->pluck('path')->all());
    expect($media[0]->path)->toStartWith('clients/anchor/gallery/');

    // Captions, a new order, and one taken out (its file goes too).
    [$stand, $launch, $team] = [$media[0], $media[1], $media[2]];
    $this->actingAs(userWithRole('admin'))->put("/admin/clients/{$client->id}", [
        'name' => 'Anchor',
        'is_published' => '1',
        'media' => [
            $stand->id => ['caption' => 'Our stand at the mall', 'sort_order' => '9'],
            $team->id => ['caption' => '  ', 'sort_order' => '0'],
            $launch->id => ['caption' => 'Launch day', 'sort_order' => '2', 'remove' => '1'],
        ],
    ])->assertSessionHasNoErrors();

    expect($client->media()->pluck('id')->all())->toBe([$team->id, $media[3]->id, $media[4]->id, $stand->id])
        ->and($stand->fresh()->caption)->toBe('Our stand at the mall')
        ->and($team->fresh()->caption)->toBeNull()
        ->and(ClientMedia::find($launch->id))->toBeNull();
    Storage::disk('public')->assertMissing($launch->path);

    // The page shows the photos (opening large) and the videos, captions and all.
    $this->get('/clients/anchor')
        ->assertOk()
        ->assertSee('Client story')
        ->assertSeeInOrder(['Photos', $team->fileUrl(), $stand->fileUrl(), 'Our stand at the mall', 'Videos'], false)
        ->assertSee('data-lightbox', false)
        ->assertSee('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', false)
        ->assertSee('client-story.js');
});

test('a gallery takes photos, videos and YouTube or Vimeo links only', function (Closure $data, string $field, string $message) {
    $client = Client::factory()->create();

    $this->actingAs(userWithRole('admin'))->put("/admin/clients/{$client->id}", ['name' => $client->name, ...$data()])
        ->assertSessionHasErrors([$field => $message]);

    expect($client->media()->count())->toBe(0);
})->with([
    'a document' => [fn () => ['new_media' => [UploadedFile::fake()->create('brief.pdf', 10, 'application/pdf')]], 'new_media.0', 'Photos must be PNG, JPG or WebP, and videos MP4 or WebM.'],
    'a big photo' => [fn () => ['new_media' => [UploadedFile::fake()->image('huge.jpg')->size(6000)]], 'new_media.0', 'huge.jpg is over 5 MB. Photos can be up to 5 MB.'],
    'another site' => [fn () => ['new_links' => "https://youtu.be/dQw4w9WgXcQ\nhttps://example.com/clip"], 'new_links', "Line 2 isn't a YouTube or Vimeo link."],
]);

test('a page holds up to thirty photos and videos', function () {
    $client = Client::factory()->create();
    foreach (range(1, 29) as $i) {
        $client->media()->create(['kind' => 'link', 'url' => 'https://youtu.be/dQw4w9WgXcQ', 'sort_order' => $i]);
    }

    $this->actingAs(userWithRole('admin'))->put("/admin/clients/{$client->id}", [
        'name' => $client->name,
        'new_links' => "https://youtu.be/aaaaaaaaaaa\nhttps://youtu.be/bbbbbbbbbbb",
    ])->assertSessionHasErrors(['new_media' => 'A story page can have up to 30 photos and videos. Remove some first.']);
});

test('a client with only a gallery has a story, and deleting the client deletes its gallery', function () {
    $client = Client::factory()->create(['slug' => 'heavenly']);
    Storage::disk('public')->put('clients/heavenly/gallery/one.jpg', 'jpg');
    $client->media()->create(['kind' => 'image', 'path' => 'clients/heavenly/gallery/one.jpg']);

    $this->get('/')->assertSee('aria-label="'.e($client->name).': read the story"', false);
    $this->get('/clients/heavenly')->assertSee('Client story');

    $this->actingAs(userWithRole('admin'))->get('/admin/clients')->assertSee('1 photo');
    $this->actingAs(userWithRole('admin'))->get("/admin/clients/{$client->id}/edit")
        ->assertSee('Photos &amp; videos', false)
        ->assertSee('name="media['.$client->media()->value('id').'][caption]"', false);

    $this->actingAs(userWithRole('admin'))->delete("/admin/clients/{$client->id}");
    Storage::disk('public')->assertMissing('clients/heavenly/gallery/one.jpg');
    expect(ClientMedia::count())->toBe(0);
});

test('the article editor previews Markdown as the page shows it', function () {
    $this->actingAs(userWithRole('admin'))
        ->postJson('/admin/clients/preview-article', ['article' => "## The challenge\n\n**Long** queues.\n\n<script>alert(1)</script>"])
        ->assertOk()
        ->assertJsonPath('html', fn (string $html) => str_contains($html, '<h2>The challenge</h2>') && str_contains($html, '<strong>Long</strong>') && ! str_contains($html, '<script>'));

    $this->actingAs(userWithRole('manager'))->postJson('/admin/clients/preview-article', ['article' => 'Hi'])->assertForbidden();

    $this->actingAs(userWithRole('admin'))->get('/admin/clients/create')
        ->assertOk()
        ->assertSee('data-md-editor', false)
        ->assertSee(route('admin.clients.preview-article'), false);
});

test('a portrait featured photo sits whole beside the article; a landscape one runs wide', function () {
    $disk = Storage::disk('public');
    $disk->putFileAs('clients/tall', UploadedFile::fake()->image('tall.jpg', 900, 1600), 'tall.jpg');
    $disk->putFileAs('clients/wide', UploadedFile::fake()->image('wide.jpg', 1600, 900), 'wide.jpg');
    $tall = Client::factory()->create(['slug' => 'tall', 'image_path' => 'clients/tall/tall.jpg', 'article' => 'Our kiosk at the mall.']);
    $wide = Client::factory()->create(['slug' => 'wide', 'image_path' => 'clients/wide/wide.jpg', 'article' => 'Our campaign.']);

    expect($tall->imageIsPortrait())->toBeTrue()->and($wide->imageIsPortrait())->toBeFalse();

    $this->get('/clients/tall')->assertOk()
        ->assertSeeInOrder(['class="story-split"', 'story-figure is-portrait', $tall->imageUrl(), 'Our kiosk at the mall.'], false)
        ->assertDontSee('class="story-media"', false);

    $this->get('/clients/wide')->assertOk()
        ->assertSeeInOrder(['class="story-media"', $wide->imageUrl(), 'class="story-body"', 'Our campaign.'], false)
        ->assertDontSee('story-split', false);
});
