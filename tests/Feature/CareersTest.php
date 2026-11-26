<?php

use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use App\Support\Careers;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function application(array $overrides = []): array
{
    return [
        'role' => 'laravel-developer',
        'name' => 'Nadia Perera',
        'email' => 'nadia@example.com',
        'phone' => '+94 77 123 4567',
        'location' => 'Colombo',
        'portfolio_url' => 'https://github.com/nadia',
        'message' => 'I love building things people use.',
        'consent' => '1',
        ...$overrides,
    ];
}

test('the careers page shows the team, the roles and the application form', function () {
    $response = $this->get(route('careers'))
        ->assertOk()
        ->assertSee('Developer Careers')
        ->assertSeeText('We’re hiring · 6 open roles')
        ->assertSee('img/careers/team-coding-1600.webp')
        ->assertSee('img/careers/kiosk-build-1600.webp')
        ->assertSee('img/careers/team-day-out-1600.webp')
        ->assertSee('img/careers/team-games-1600.webp')
        ->assertSee(route('careers.apply'))
        ->assertSee('09475 275 1499');

    foreach (Careers::roles() as $role) {
        $response->assertSee($role['title'])->assertSee('id="role-'.$role['slug'].'"', false);
    }

    foreach (['team-coding', 'kiosk-build', 'team-day-out', 'team-games'] as $photo) {
        expect(public_path("moderning_xelenic/assets/img/careers/{$photo}-800.webp"))->toBeFile();
    }
});

test('a link to a role preselects it in the form', function () {
    $this->get(route('careers', ['role' => 'kiosk-game-developer']))
        ->assertSee('<option value="kiosk-game-developer" selected>', false);
});

test('anyone can apply with a CV', function () {
    $this->post(route('careers.apply'), application(['cv' => UploadedFile::fake()->create('Nadia CV.pdf', 300, 'application/pdf')]))
        ->assertRedirect(route('careers').'#apply')
        ->assertSessionHas('applied', 'Thanks, Nadia! We’ve received your application and will be in touch.');

    $application = JobApplication::sole();
    expect($application)
        ->role->toBe('laravel-developer')
        ->role_title->toBe('Laravel Developer')
        ->status->toBe(ApplicationStatus::New)
        ->cv_name->toBe('Nadia CV.pdf');
    Storage::disk('local')->assertExists($application->cv_path);
    expect($application->cv_path)->toStartWith('job-applications/');

    $this->get(route('careers'))->assertSee('Application sent');
});

test('applications are checked', function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    $this->post(route('careers.apply'), application(['role' => 'astronaut']))->assertSessionHasErrors('role');
    $this->post(route('careers.apply'), application(['email' => 'not-an-email']))->assertSessionHasErrors('email');
    $this->post(route('careers.apply'), application(['consent' => null]))->assertSessionHasErrors('consent');
    $this->post(route('careers.apply'), application(['portfolio_url' => null]))->assertSessionHasErrors(['cv' => 'Attach your CV, or share a link to your work.']);
    $this->post(route('careers.apply'), application(['cv' => UploadedFile::fake()->create('cv.exe', 10)]))->assertSessionHasErrors('cv');
    $this->post(route('careers.apply'), application(['cv' => UploadedFile::fake()->create('cv.pdf', 6000, 'application/pdf')]))->assertSessionHasErrors('cv');

    expect(JobApplication::count())->toBe(0);
});

test('an open application and a portfolio link alone are fine', function () {
    $this->post(route('careers.apply'), application(['role' => Careers::OPEN_APPLICATION, 'phone' => null]))->assertSessionHasNoErrors();

    expect(JobApplication::sole())->role_title->toBe('Open application (any role)')->cv_path->toBeNull();
});

test('bots filling the hidden field are thanked and ignored', function () {
    $this->post(route('careers.apply'), application(['website' => 'http://spam.example']))
        ->assertRedirect(route('careers').'#apply')
        ->assertSessionHas('applied');

    expect(JobApplication::count())->toBe(0);
});

test('staff can review applications, download CVs and keep notes', function () {
    $this->post(route('careers.apply'), application(['cv' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')]));
    $application = JobApplication::sole();
    $admin = userWithRole('admin');

    $this->actingAs($admin)->get(route('admin.careers.index'))
        ->assertOk()
        ->assertSee('Nadia Perera')
        ->assertSee('Laravel Developer')
        ->assertSee('CV attached');

    $this->actingAs($admin)->get(route('admin.careers.show', $application))
        ->assertOk()
        ->assertSee('nadia@example.com')
        ->assertSee('https://github.com/nadia')
        ->assertSee('I love building things people use.');

    $this->actingAs($admin)->get(route('admin.careers.cv', $application))->assertOk()->assertDownload('cv.pdf');

    $this->actingAs($admin)->patch(route('admin.careers.update', $application), ['status' => 'interviewing', 'notes' => 'Strong Laravel background.'])
        ->assertSessionHas('success');
    expect($application->fresh())->status->toBe(ApplicationStatus::Interviewing)->notes->toBe('Strong Laravel background.');

    $path = $application->cv_path;
    $this->actingAs($admin)->delete(route('admin.careers.destroy', $application))->assertRedirect(route('admin.careers.index'));
    expect(JobApplication::count())->toBe(0);
    Storage::disk('local')->assertMissing($path);
});

test('only staff who manage careers can see applications', function () {
    $application = JobApplication::factory()->create();

    $this->get(route('admin.careers.index'))->assertRedirect(route('login'));
    foreach (['user', 'manager'] as $role) {
        $user = userWithRole($role);
        $this->actingAs($user)->get(route('admin.careers.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.careers.show', $application))->assertForbidden();
    }
});

test('the site menu and footer link to the careers page', function () {
    $this->get('/')->assertSee('<a href="'.route('careers').'">Careers</a>', false);
});
