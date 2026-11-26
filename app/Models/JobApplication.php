<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Database\Factories\JobApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * An application from the Developer Careers page. The CV is kept on the
 * private "local" disk and only staff can download it.
 */
#[Fillable(['role', 'role_title', 'name', 'email', 'phone', 'location', 'portfolio_url', 'cv_path', 'cv_name', 'message', 'status', 'notes'])]
class JobApplication extends Model
{
    /** @use HasFactory<JobApplicationFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
        ];
    }

    /**
     * Its CV goes with it.
     */
    protected static function booted(): void
    {
        static::deleted(function (JobApplication $application) {
            if ($application->cv_path) {
                Storage::disk('local')->delete($application->cv_path);
            }
        });
    }

    public function hasCv(): bool
    {
        return $this->cv_path && Storage::disk('local')->exists($this->cv_path);
    }
}
