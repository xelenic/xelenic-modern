<?php

namespace App\Http\Requests;

use App\Enums\KioskMachine;
use App\Enums\ProjectType;
use App\Models\Project;
use App\Support\KioskSettings;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    /**
     * Any signed-in user may ask for a project (the route requires auth).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'type' => ['required', Rule::enum(ProjectType::class)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'budget' => ['nullable', Rule::in(Project::BUDGETS)],
            'timeline' => ['nullable', Rule::in(Project::TIMELINES)],
            'details' => ['nullable', 'array'],
        ];

        foreach ($this->questions() as $question) {
            $rules += $this->rulesFor($question);
        }

        // Kiosk projects also say which machine was picked on the first step,
        // and fill in the kiosk settings panel.
        if ($this->isKioskProject()) {
            $rules['details.machine'] = ['nullable', Rule::enum(KioskMachine::class)];
            $rules += KioskSettings::rules();
        }

        return $rules;
    }

    /**
     * The rules for one of the type's extra questions (all optional).
     *
     * @param  array<string, mixed>  $question
     * @return array<string, array<int, mixed>>
     */
    private function rulesFor(array $question): array
    {
        $key = "details.{$question['name']}";

        return match ($question['type']) {
            'number' => [$key => ['nullable', 'integer', 'min:1', 'max:100000']],
            'url' => [$key => ['nullable', 'url', 'max:255']],
            'select' => [$key => ['nullable', Rule::in($question['options'])]],
            'checkboxes' => [
                $key => ['nullable', 'array'],
                "{$key}.*" => [Rule::in($question['options'])],
            ],
            default => [$key => ['nullable', 'string', 'max:255']],
        };
    }

    /**
     * Name each question in error messages after its label.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return collect($this->questions())
            ->mapWithKeys(fn (array $question) => [
                "details.{$question['name']}" => Str::lower(rtrim($question['label'], '?')),
            ])
            ->all();
    }

    /**
     * The answered questions for the chosen type (numbers as integers) and,
     * for kiosks, the machine and settings, ignoring anything else sent.
     *
     * @return array<string, mixed>
     */
    public function details(): array
    {
        $details = collect($this->questions())
            ->mapWithKeys(function (array $question) {
                $answer = $this->validated("details.{$question['name']}");

                return [$question['name'] => $question['type'] === 'number' && filled($answer) ? (int) $answer : $answer];
            });

        if ($this->isKioskProject()) {
            $details->put('machine', $this->validated('details.machine'));
            $details->put('settings', KioskSettings::fromInput($this->validated('settings')));
        }

        return $details->filter(fn (mixed $answer) => filled($answer))->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function questions(): array
    {
        return ProjectType::tryFrom((string) $this->input('type'))?->questions() ?? [];
    }

    private function isKioskProject(): bool
    {
        return $this->input('type') === ProjectType::KioskApp->value;
    }
}
