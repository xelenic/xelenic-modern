<?php

namespace App\Http\Requests;

use App\Models\Client;
use App\Models\ClientMedia;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

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
            // The gallery: what's there (caption, position, remove), and what's added.
            'media' => ['nullable', 'array'],
            'media.*.caption' => ['nullable', 'string', 'max:200'],
            'media.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'media.*.remove' => ['boolean'],
            'new_media' => ['nullable', 'array', 'max:20'],
            'new_media.*' => [File::types(['png', 'jpg', 'jpeg', 'webp', 'mp4', 'webm'])->max('100mb')],
            'new_links' => ['nullable', 'string', 'max:3000'],
            'is_published' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'new_media.max' => 'Add up to 20 photos and videos at a time.',
            'new_media.*.max' => 'Each video can be up to 100 MB.',
            'new_media.*.mimes' => 'Photos must be PNG, JPG or WebP, and videos MP4 or WebM.',
        ];
    }

    /**
     * Photos are smaller than videos; links must play; and the gallery has
     * a limit.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            foreach ($this->file('new_media', []) as $i => $file) {
                if (str_starts_with((string) $file->getMimeType(), 'image/') && $file->getSize() > 5 * 1024 * 1024) {
                    $validator->errors()->add("new_media.{$i}", "{$file->getClientOriginalName()} is over 5 MB. Photos can be up to 5 MB.");
                }
            }

            foreach (preg_split('/\R/', (string) $this->input('new_links')) as $n => $line) {
                if (trim($line) !== '' && ! Client::embedUrlFor(trim($line))) {
                    $validator->errors()->add('new_links', 'Line '.($n + 1).' isn\'t a YouTube or Vimeo link.');
                }
            }

            $client = $this->route('client');
            $removing = collect($this->input('media', []))->filter(fn ($item) => ! empty($item['remove']))->count();
            $total = ($client ? $client->media()->count() : 0) - $removing + count($this->file('new_media', [])) + count($this->links());
            if ($total > ClientMedia::MAX) {
                $validator->errors()->add('new_media', 'A story page can have up to '.ClientMedia::MAX.' photos and videos. Remove some first.');
            }
        }];
    }

    /**
     * The YouTube and Vimeo links to add, one per line.
     *
     * @return list<string>
     */
    public function links(): array
    {
        return collect(preg_split('/\R/', (string) $this->input('new_links')))
            ->map(fn (string $line) => trim($line))
            ->filter(fn (string $line) => $line !== '' && Client::embedUrlFor($line))
            ->values()
            ->all();
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
