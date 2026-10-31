<?php

use App\Models\Client;
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

test('the home page shows published client logos, linking those with a story', function () {
    $story = Client::factory()->withStory()->create(['name' => 'Anchor', 'slug' => 'anchor']);
    $logoOnly = Client::factory()->create(['name' => 'Haleon', 'slug' => 'haleon']);
    Client::factory()->hidden()->create(['name' => 'Secret Co']);

    $this->get('/')
        ->assertOk()
        ->assertSee('Trusted by brands that keep growing')
        ->assertSee($story->logoUrl())
        ->assertSee('href="'.route('clients.show', $story).'"', false)
        ->assertSee($logoOnly->logoUrl())
        ->assertDontSee('href="'.route('clients.show', $logoOnly).'"', false)
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
