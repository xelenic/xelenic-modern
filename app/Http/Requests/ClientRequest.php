<?php

namespace App\Http\Requests;

use App\Models\Client;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class ClientRequest extends FormRequest
{
    /**
     * The route already requires the "manage clients" permission.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Use the name for the slug when none is given.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => str($this->input('slug') ?: $this->input('name'))->slug()->value(),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $client = $this->route('client');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique(Client::class)->ignore($client)],
            // Raster images only: SVG files can carry scripts.
            'logo' => [$client ? 'nullable' : 'required', File::types(['png', 'jpg', 'jpeg', 'webp'])->max('2mb')],
            'dark_logo' => ['boolean'],
            'summary' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', File::types(['png', 'jpg', 'jpeg', 'webp'])->max('5mb')],
            'remove_image' => ['boolean'],
            'video' => ['nullable', File::types(['mp4', 'webm'])->max('100mb')],
            'remove_video' => ['boolean'],
            'video_url' => ['nullable', 'url', 'max:255', function (string $attribute, mixed $value, Closure $fail) {
                if (! Client::embedUrlFor($value)) {
                    $fail('Use a YouTube or Vimeo link for the video.');
                }
            }],
            'article' => ['nullable', 'string', 'max:50000'],
            'is_published' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'summary' => 'description',
            'video_url' => 'video link',
            'image' => 'featured image',
        ];
    }
}
