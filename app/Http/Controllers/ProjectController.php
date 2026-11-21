<?php

namespace App\Http\Controllers;

use App\Enums\ActivityType;
use App\Enums\KioskMachine;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Http\Requests\StoreProjectRequest;
use App\Models\Project;
use App\Support\KioskDesign;
use App\Support\KioskSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectController extends Controller
{
    /**
     * The signed-in user's projects. Everyone's are in the admin panel
     * (Admin\ProjectController).
     */
    public function index(Request $request): View
    {
        return view('projects.index', [
            'projects' => $request->user()->projects()->latest()->paginate(12),
        ]);
    }

    /**
     * Pick what kind of project to start. A choice already made elsewhere
     * (the dashboard's service options send ?type=…) goes straight to its form.
     */
    public function create(Request $request): View|RedirectResponse
    {
        if ($type = ProjectType::tryFrom((string) $request->query('type'))) {
            return redirect()->route('projects.create.type', $type);
        }

        return view('projects.create', [
            'types' => ProjectType::cases(),
        ]);
    }

    /**
     * The form for one kind of project. Kiosk projects pick their machine
     * first (?machine=…), or skip that step with ?skip=1.
     */
    public function createType(Request $request, ProjectType $type): View
    {
        $machine = null;

        if ($type === ProjectType::KioskApp) {
            $machine = KioskMachine::tryFrom($request->string('machine')->value());

            if (! $machine && ! $request->boolean('skip')) {
                return view('projects.kiosk-machines', [
                    'type' => $type,
                    'machines' => KioskMachine::cases(),
                ]);
            }
        }

        // Kiosk projects finish in two more steps (projects.kiosk-form); the
        // rest fill in one page.
        return view($type === ProjectType::KioskApp ? 'projects.kiosk-form' : 'projects.form', [
            'type' => $type,
            'machine' => $machine,
            'kioskSettings' => $type === ProjectType::KioskApp ? KioskSettings::defaults($machine) : null,
            'budgets' => Project::BUDGETS,
            'timelines' => Project::TIMELINES,
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = $request->user()->projects()->create([
            ...Arr::only($request->validated(), ['type', 'name', 'description', 'budget', 'timeline']),
            'details' => $request->details(),
        ]);

        if ($project->kioskDesign()) {
            KioskDesign::save($project, $request->validated('kiosk_design'));
            KioskDesign::addEntries($project, KioskDesign::decodeEntries($request->validated('kiosk_data')) ?? []);
        }

        $project->log(ActivityType::Created, 'Project submitted', $request->user(), $project->kioskDesign()
            ? KioskDesign::changes(null, KioskDesign::decode($request->validated('kiosk_design')))
            : []);

        return to_route('projects.show', $project)
            ->with('success', "Thanks! We've received your {$project->type->label()} project and will be in touch soon.");
    }

    /**
     * A client's own project. The Xelenic team opens projects from the
     * admin panel instead.
     */
    public function show(Request $request, Project $project): View
    {
        abort_unless($project->user()->is($request->user()), 403);

        return view('projects.show', [
            'project' => $project,
        ]);
    }

    /**
     * The Kiosk Studio design attached to a client's own kiosk project.
     */
    public function kioskDesign(Request $request, Project $project): StreamedResponse
    {
        abort_unless($project->user()->is($request->user()), 403);
        abort_unless(KioskDesign::exists($project), 404);

        return KioskDesign::download($project);
    }

    /**
     * The form entries collected by a client's own kiosk app, as a spreadsheet.
     */
    public function kioskData(Request $request, Project $project): StreamedResponse
    {
        abort_unless($project->user()->is($request->user()), 403);
        abort_unless(KioskDesign::entries($project) !== [], 404);

        return KioskDesign::entriesCsv($project);
    }

    /**
     * Save a design from Kiosk Studio to a client's own kiosk project, over
     * the one it had or as its first, while the project is still open. The
     * studio saves in the background and stays open, so it's answered with
     * JSON; a plain form post goes back to the project.
     */
    public function updateKioskDesign(Request $request, Project $project): RedirectResponse|JsonResponse
    {
        abort_unless($project->user()->is($request->user()) && $project->canChangeKioskDesign(), 403);

        $validated = $request->validate(KioskDesign::rules(required: true) + KioskDesign::entryRules(), KioskDesign::messages());
        $before = KioskDesign::design($project);
        $entriesBefore = count(KioskDesign::entries($project));

        KioskDesign::attach($project, $validated['kiosk_design']);
        KioskDesign::addEntries($project, KioskDesign::decodeEntries($validated['kiosk_data'] ?? null) ?? []);

        // The log gets what this save changed; a save that changed nothing isn't logged.
        $newEntries = count(KioskDesign::entries($project)) - $entriesBefore;
        $changes = [
            ...KioskDesign::changes($before, KioskDesign::decode($validated['kiosk_design'])),
            ...($newEntries > 0 ? [['tone' => 'added', 'label' => 'Form entries', 'items' => [$newEntries.' new '.Str::plural('entry', $newEntries)]]] : []),
        ];
        if ($changes !== []) {
            $project->log(ActivityType::Design, $before ? 'Saved the design in Kiosk Studio' : 'Added a Kiosk Studio design', $request->user(), $changes);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Design saved.',
                'design' => $project->kioskDesign(),
                'entries' => count(KioskDesign::entries($project)),
            ]);
        }

        return to_route('projects.show', [$project, 'tab' => 'design'])->with('success', 'Your kiosk app design was saved.');
    }

    /**
     * Let the client withdraw a project before work has started.
     */
    /**
     * Turn on the kiosk app's play link, or make a new one in place of the
     * old, which then stops working.
     */
    public function sharePlay(Request $request, Project $project): RedirectResponse
    {
        abort_unless($project->user()->is($request->user()), 403);
        abort_unless($project->canBeShared(), 404);

        $renewed = $project->isShared();
        $project->share();
        $project->log(ActivityType::PlayLink, $renewed ? 'Made a new play link' : 'Turned on the play link', $request->user(),
            $renewed ? [['tone' => 'info', 'label' => 'Play link', 'items' => ['The old link stopped working']]] : []);

        return to_route('projects.show', $project)->with('success', $renewed
            ? 'Here is your new play link. The old one no longer works.'
            : 'Your play link is ready. Anyone you send it to can play your kiosk app.');
    }

    /**
     * Turn the play link off.
     */
    public function stopSharingPlay(Request $request, Project $project): RedirectResponse
    {
        abort_unless($project->user()->is($request->user()), 403);

        if ($project->isShared()) {
            $project->stopSharing();
            $project->log(ActivityType::PlayLink, 'Turned off the play link', $request->user());
        }

        return to_route('projects.show', $project)->with('success', 'The play link is off. Anyone who has it can no longer play.');
    }

    public function cancel(Request $request, Project $project): RedirectResponse
    {
        abort_unless($project->user()->is($request->user()) && $project->status->canBeCancelled(), 403);

        $was = $project->status;
        $project->update(['status' => ProjectStatus::Cancelled]);
        $project->log(ActivityType::Status, 'Cancelled the project', $request->user(), [
            ['tone' => 'removed', 'label' => 'Status', 'items' => ["{$was->label()} → {$project->status->label()}"]],
        ]);

        return to_route('projects.show', $project)->with('success', 'Your project was cancelled.');
    }
}
