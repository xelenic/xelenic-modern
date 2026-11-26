<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Http\Requests\JobApplicationRequest;
use App\Models\JobApplication;
use App\Support\Careers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * The Developer Careers page: what we build, life on the team, the open roles
 * (App\Support\Careers) and the application form. Applications are read in
 * the admin panel (Admin\JobApplicationController).
 */
class CareersController extends Controller
{
    public function index(Request $request): View
    {
        $roles = Careers::roles();

        return view('careers.show', [
            'roles' => $roles,
            'teams' => collect(Careers::TEAMS)->map(fn (string $label, string $key) => [
                'label' => $label,
                'count' => $roles->where('team', $key)->count(),
            ])->filter(fn (array $team) => $team['count'] > 0),
            'choices' => Careers::choices(),
            // "Apply for this role" links preselect it.
            'selected' => old('role', $roles->has((string) $request->query('role')) ? $request->query('role') : null),
        ]);
    }

    public function apply(JobApplicationRequest $request): RedirectResponse
    {
        $back = route('careers').'#apply';

        // The hidden field people never see: a bot filled it in.
        if (filled($request->input('website'))) {
            return redirect($back)->with('applied', 'Thanks! We’ve received your application.');
        }

        $data = $request->validated();
        $cv = $request->file('cv');

        JobApplication::create([
            'role' => $data['role'],
            'role_title' => Careers::title($data['role']),
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'location' => $data['location'] ?? null,
            'portfolio_url' => $data['portfolio_url'] ?? null,
            'cv_path' => $cv?->storeAs('job-applications', Str::uuid().'.'.$cv->extension(), 'local'),
            'cv_name' => $cv ? Str::limit($cv->getClientOriginalName(), 180, '') : null,
            'message' => $data['message'] ?? null,
            'status' => ApplicationStatus::New,
        ]);

        return redirect($back)->with('applied', 'Thanks, '.Str::before($data['name'], ' ').'! We’ve received your application and will be in touch.');
    }
}
