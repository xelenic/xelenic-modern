<?php

namespace App\Http\Requests;

use App\Support\Careers;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * An application from the Developer Careers page. Anyone can apply.
 */
class JobApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', 'string', Rule::in(array_keys(Careers::choices()))],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+()\-\s.]{6,40}$/'],
            'location' => ['nullable', 'string', 'max:120'],
            'portfolio_url' => ['nullable', 'url:http,https', 'max:500'],
            'cv' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'message' => ['nullable', 'string', 'max:3000'],
            'consent' => ['accepted'],
            // Left empty by people; bots fill it in (see CareersController).
            'website' => ['nullable', 'string', 'max:200'],
        ];
    }

    /**
     * A CV or a link to their work: we need one of them.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $this->hasFile('cv') && blank($this->input('portfolio_url'))) {
                    $validator->errors()->add('cv', 'Attach your CV, or share a link to your work.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role.required' => 'Choose the role you’re applying for.',
            'role.in' => 'Choose one of the roles listed.',
            'phone.regex' => 'Enter a phone number using digits, spaces and + ( ) -.',
            'portfolio_url.url' => 'Enter a full link, starting with https://.',
            'cv.mimes' => 'Your CV should be a PDF or Word document.',
            'cv.max' => 'Your CV should be 5 MB or smaller.',
            'consent.accepted' => 'Please agree to us keeping your details for this application.',
        ];
    }
}
