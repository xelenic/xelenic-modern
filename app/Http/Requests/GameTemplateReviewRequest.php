<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A star rating, and optionally a comment, for a game template, sent from
 * its page in Kiosk Studio's marketplace.
 */
class GameTemplateReviewRequest extends FormRequest
{
    /**
     * Any signed-in user can review a game they can see; the controller
     * checks that they can see it.
     */
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
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rating.required' => 'Choose a star rating.',
            'rating.between' => 'Choose between 1 and 5 stars.',
            'comment.max' => 'Keep your comment under 1,000 characters.',
        ];
    }
}
