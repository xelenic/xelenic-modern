<?php

namespace App\Models;

use Database\Factories\GameTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A game in Kiosk Studio's game templates marketplace (public/nignest). The
 * studio's own games are "builtin" rows; the others come from an uploaded
 * template .zip (see App\Support\GameTemplatePackage), kept on the local disk
 * and handed to the studio when it adds the game to a design.
 *
 * Each has a store page in the marketplace: a cover photo and screenshots
 * (public disk), a longer write-up in Markdown, and users' star reviews.
 */
#[Fillable(['slug', 'name', 'description', 'details', 'kind', 'extends', 'version', 'author', 'icon', 'icon_image', 'color', 'thumbnail_path', 'screenshots', 'is_active', 'sort_order'])]
class GameTemplate extends Model
{
    /** @use HasFactory<GameTemplateFactory> */
    use HasFactory;

    /**
     * The games Kiosk Studio has built in (BUILTIN_KINDS in its
     * js/editor-packages.js): no package, and they can't be deleted.
     */
    public const BUILTIN = ['quiz', 'catch', 'slice', 'wheel', 'runner', 'memory', 'whack', 'stack', 'scratch', 'hoop'];

    /**
     * Screenshots a store page can have.
     */
    public const MAX_SCREENSHOTS = 8;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'screenshots' => 'array',
        ];
    }

    /**
     * Its package and pictures go with it; reviews go by foreign key.
     */
    protected static function booted(): void
    {
        static::deleted(function (GameTemplate $template) {
            Storage::disk('local')->delete($template->packagePath());
            Storage::disk('public')->delete(array_filter([$template->thumbnail_path, ...($template->screenshots ?? [])]));
        });
    }

    /**
     * @return HasMany<GameTemplateReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(GameTemplateReview::class);
    }

    /**
     * @param  Builder<GameTemplate>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Marketplace order: by position, then name.
     *
     * @param  Builder<GameTemplate>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * With its average rating and number of reviews.
     *
     * @param  Builder<GameTemplate>  $query
     */
    public function scopeWithRatings(Builder $query): void
    {
        $query->withCount('reviews')->withAvg('reviews', 'rating');
    }

    public function isBuiltin(): bool
    {
        return $this->kind === 'builtin';
    }

    /**
     * Hidden games are only for staff.
     */
    public function isVisibleTo(?User $user): bool
    {
        return $this->is_active || (bool) $user?->can('manage projects');
    }

    public function packagePath(): string
    {
        return "game-templates/{$this->slug}.zip";
    }

    /**
     * Where its cover photo and screenshots are kept on the public disk.
     */
    public function mediaFolder(): string
    {
        return "game-templates/{$this->slug}";
    }

    public function thumbnailUrl(): ?string
    {
        return $this->thumbnail_path ? Storage::disk('public')->url($this->thumbnail_path) : null;
    }

    /**
     * @return list<string>
     */
    public function screenshotUrls(): array
    {
        return array_values(array_map(fn (string $path) => Storage::disk('public')->url($path), $this->screenshots ?? []));
    }

    /**
     * The write-up, as HTML. Raw HTML in it is dropped.
     */
    public function detailsHtml(): string
    {
        return Str::markdown((string) $this->details, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    public function averageRating(): float
    {
        return round((float) ($this->reviews_avg_rating ?? $this->reviews()->avg('rating')), 1);
    }

    public function reviewsCount(): int
    {
        return (int) ($this->reviews_count ?? $this->reviews()->count());
    }

    /**
     * How Kiosk Studio's marketplace lists it.
     *
     * @return array<string, mixed>
     */
    public function catalogEntry(): array
    {
        return [
            'id' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'kind' => $this->kind,
            'extends' => $this->extends,
            'version' => $this->version,
            'author' => $this->author,
            'icon' => $this->icon,
            'icon_image' => $this->icon_image,
            'color' => $this->color,
            'thumbnail' => $this->thumbnailUrl(),
            'screenshots_count' => count($this->screenshots ?? []),
            'rating' => $this->averageRating(),
            'reviews_count' => $this->reviewsCount(),
            'download' => $this->isBuiltin() ? null : route('game-templates.download', $this),
        ];
    }

    /**
     * Its store page in the marketplace, as $viewer sees it.
     *
     * @return array<string, mixed>
     */
    public function storePage(User $viewer): array
    {
        $reviews = $this->reviews()->with('user')->latest('updated_at')->latest('id')->limit(100)->get();
        $counts = $this->reviews()->selectRaw('rating, count(*) as total')->groupBy('rating')->pluck('total', 'rating');
        $mine = $this->reviews()->where('user_id', $viewer->id)->first();

        return [
            ...$this->catalogEntry(),
            'details_html' => $this->detailsHtml(),
            'screenshots' => $this->screenshotUrls(),
            'updated' => $this->updated_at?->toDateString(),
            // How many reviews gave 5 stars, 4 stars, and so on.
            'breakdown' => collect([5, 4, 3, 2, 1])->mapWithKeys(fn (int $stars) => [(string) $stars => (int) ($counts[$stars] ?? 0)]),
            'reviews' => $reviews->map->toStudio($viewer)->values(),
            'my_review' => $mine?->only(['rating', 'comment']),
        ];
    }
}
