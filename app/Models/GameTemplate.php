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
     * How each built-in game plays, for its page in the website's
     * marketplace: the controls, whether it ends in a score or a prize, the
     * steps (as the game's own how-to-play screen has them, START_INFO in
     * Kiosk Studio's js/templates.js), and what it suits.
     */
    public const GUIDES = [
        'quiz' => ['controls' => 'Tap', 'ends' => 'score', 'steps' => ['Answer 5 quick questions', 'Tap the right answer before time runs out', 'Score 300 or more to win'], 'good_for' => ['Product launches', 'Brand trivia']],
        'catch' => ['controls' => 'Drag', 'ends' => 'score', 'steps' => ['Drag the basket left and right', 'Catch fruit for +10 points', 'Avoid the bombs!'], 'good_for' => ['Malls and events', 'Families']],
        'slice' => ['controls' => 'Swipe', 'ends' => 'score', 'steps' => ['Swipe across fruit to slice it', 'Slice 3 at once for a combo bonus', 'Don’t slice the bombs!'], 'good_for' => ['Food and drink brands', 'Quick play']],
        'wheel' => ['controls' => 'Tap', 'ends' => 'prize', 'steps' => ['Tap SPIN to turn the wheel', 'Wherever it stops is your prize', 'One spin per player'], 'good_for' => ['Prize giveaways', 'Lead capture']],
        'runner' => ['controls' => 'Tap', 'ends' => 'score', 'steps' => ['Tap anywhere to jump', 'Tap again in the air to double jump', 'Grab coins and dodge the obstacles!'], 'good_for' => ['Leaderboards', 'Repeat play']],
        'memory' => ['controls' => 'Tap', 'ends' => 'score', 'steps' => ['Remember where the pictures are', 'Tap two cards to find a pair', 'Match them all before time runs out!'], 'good_for' => ['Product showcases', 'All ages']],
        'whack' => ['controls' => 'Tap', 'ends' => 'score', 'steps' => ['Critters pop out of the holes', 'Tap them before they hide', 'Leave the bombs alone!'], 'good_for' => ['Events', 'Fast fun']],
        'stack' => ['controls' => 'Tap', 'ends' => 'score', 'steps' => ['A block slides across the top', 'Tap to drop it on the tower', 'Line it up perfectly for a bonus!'], 'good_for' => ['Leaderboards', 'Skill challenges']],
        'scratch' => ['controls' => 'Scratch', 'ends' => 'prize', 'steps' => ['Rub the silver foil with your finger', 'Your prize appears underneath', 'One card per player'], 'good_for' => ['Instant prizes', 'Retail promotions']],
        'hoop' => ['controls' => 'Swipe', 'ends' => 'score', 'steps' => ['Swipe up to throw the ball', 'Sink as many baskets as you can', 'Miss the rim for a bonus!'], 'good_for' => ['Sports brands', 'Events']],
    ];

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
     * How it plays (see GUIDES): a preset plays like the game it's based on.
     * Null for a game of its own.
     *
     * @return array{controls: string, ends: string, steps: list<string>, good_for: list<string>}|null
     */
    public function guide(): ?array
    {
        return self::GUIDES[$this->isBuiltin() ? $this->slug : (string) $this->extends] ?? null;
    }

    /**
     * Whether the website can run it as a demo: the built-in games, which
     * Kiosk Studio's player has.
     */
    public function hasDemo(): bool
    {
        return $this->isBuiltin() && in_array($this->slug, self::BUILTIN, true);
    }

    /**
     * Its icon on the website (resources/views/components/site/icon.blade.php):
     * a built-in game's own, the one a preset is based on, else a controller.
     * The website shows these rather than the emoji Kiosk Studio uses.
     */
    public function siteIcon(): string
    {
        $kind = $this->isBuiltin() ? $this->slug : (string) $this->extends;

        return in_array($kind, self::BUILTIN, true) ? $kind : 'gamepad';
    }

    /**
     * Its colour, as a CSS value (stored colours are #rrggbb or #rrggbbaa).
     */
    public function cssColor(): string
    {
        return preg_match('/^#[0-9a-f]{6}([0-9a-f]{2})?$/i', (string) $this->color) ? $this->color : '#3b3f5c';
    }

    /**
     * Its icon picture, if it has one (a data URL from its package).
     */
    public function iconImage(): ?string
    {
        return str_starts_with((string) $this->icon_image, 'data:image/') ? $this->icon_image : null;
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
