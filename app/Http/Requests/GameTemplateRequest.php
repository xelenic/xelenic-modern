<?php

namespace App\Http\Requests;

use App\Models\GameTemplate;
use App\Support\GameTemplatePackage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

/**
 * Adding a game template (its .zip) or changing one: its name, description,
 * position and whether it's offered, and for an uploaded game a new version
 * of its .zip. The studio's own games have no .zip.
 */
class GameTemplateRequest extends FormRequest
{
    /**
     * What the uploaded .zip says about itself, once it's been read.
     *
     * @var array<string, mixed>|null
     */
    private ?array $package = null;

    /**
     * The route already requires the "manage projects" permission.
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
        $template = $this->template();

        return [
            'package' => [
                $template ? 'nullable' : 'required',
                $template?->isBuiltin() ? 'prohibited' : null,
                File::default()->extensions(['zip'])->max(GameTemplatePackage::MAX_KILOBYTES),
            ],
            'name' => [$template ? 'required' : 'nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'details' => ['nullable', 'string', 'max:5000'],
            'thumbnail' => ['nullable', File::image()->types(['png', 'jpg', 'jpeg', 'webp'])->max(2 * 1024)],
            'remove_thumbnail' => ['boolean'],
            'screenshots' => ['nullable', 'array', 'max:'.GameTemplate::MAX_SCREENSHOTS],
            'screenshots.*' => [File::image()->types(['png', 'jpg', 'jpeg', 'webp'])->max(4 * 1024)],
            'remove_screenshots' => ['nullable', 'array'],
            'remove_screenshots.*' => ['string'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'package.required' => 'Choose the game template .zip to upload.',
            'package.prohibited' => 'Kiosk Studio\'s own games can\'t be replaced.',
            'screenshots.max' => 'Add up to '.GameTemplate::MAX_SCREENSHOTS.' screenshots.',
            'screenshots.*.max' => 'Each screenshot can be up to 4 MB.',
            'thumbnail.max' => 'The cover photo can be up to 2 MB.',
        ];
    }

    /**
     * Read the .zip, and make sure it's a template this one can be.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            $this->checkScreenshotCount($validator);

            if (! $this->hasFile('package') || $validator->errors()->has('package')) {
                return;
            }

            try {
                $this->package = GameTemplatePackage::read($this->file('package')->getRealPath());
            } catch (InvalidArgumentException $e) {
                $validator->errors()->add('package', $e->getMessage());

                return;
            }

            $template = $this->template();
            $slug = $this->package['slug'];

            if ($template && $slug !== $template->slug) {
                $validator->errors()->add('package', "This .zip is the template \"{$slug}\", not \"{$template->slug}\". Add it as a new template instead.");
            } elseif (! $template && GameTemplate::where('slug', $slug)->exists()) {
                $validator->errors()->add('package', "There's already a template with the id \"{$slug}\". Edit it to upload a new version.");
            }
        }];
    }

    /**
     * The ones kept plus the ones added can't be more than a page shows.
     */
    private function checkScreenshotCount(Validator $validator): void
    {
        $kept = array_diff($this->template()?->screenshots ?? [], (array) $this->input('remove_screenshots', []));
        $total = count($kept) + count($this->file('screenshots', []));

        if ($total > GameTemplate::MAX_SCREENSHOTS && ! $validator->errors()->has('screenshots')) {
            $validator->errors()->add('screenshots', 'A game can have up to '.GameTemplate::MAX_SCREENSHOTS.' screenshots. Remove some first.');
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function package(): ?array
    {
        return $this->package;
    }

    private function template(): ?GameTemplate
    {
        return $this->route('game_template');
    }
}
