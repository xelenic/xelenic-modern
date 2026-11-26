<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Applications from the Developer Careers page: read them, download CVs,
 * move them through the hiring process, keep notes.
 */
class JobApplicationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:manage careers'),
        ];
    }

    public function index(Request $request): View
    {
        $status = ApplicationStatus::tryFrom((string) $request->query('status'));

        return view('admin.careers.index', [
            'applications' => JobApplication::query()
                ->when($status, fn ($query) => $query->where('status', $status))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'status' => $status,
            'counts' => JobApplication::query()->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function show(JobApplication $jobApplication): View
    {
        return view('admin.careers.show', ['application' => $jobApplication]);
    }

    public function update(Request $request, JobApplication $jobApplication): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(ApplicationStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $jobApplication->update($data);

        return back()->with('success', 'Application updated.');
    }

    public function cv(JobApplication $jobApplication): StreamedResponse
    {
        abort_unless($jobApplication->hasCv(), 404);

        return Storage::disk('local')->download($jobApplication->cv_path, $jobApplication->cv_name ?: 'cv.'.pathinfo($jobApplication->cv_path, PATHINFO_EXTENSION));
    }

    public function destroy(JobApplication $jobApplication): RedirectResponse
    {
        $jobApplication->delete();

        return to_route('admin.careers.index')->with('success', "{$jobApplication->name}’s application was deleted.");
    }
}
