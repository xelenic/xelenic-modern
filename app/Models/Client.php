<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A client shown in the home page's logo marquee, with an optional story
 * page (summary, image, video and article) that opens from its logo.
 * Files live on the "public" disk.
 */
#[Fillable(['name', 'slug', 'logo_path', 'dark_logo', 'summary', 'image_path', 'video_path', 'video_url', 'article', 'is_published', 'sort_order'])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dark_logo' => 'boolean',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Uploaded files go with the client.
     */
    protected static function booted(): void
    {
        static::deleted(function (Client $client) {
            Storage::disk('public')->delete(array_filter([$client->logo_path, $client->image_path, $client->video_path]));
        });
    }

    /**
     * @param  Builder<Client>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * Marquee order: by sort order, then name.
     *
     * @param  Builder<Client>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }

    public function logoUrl(): string
    {
        return Storage::disk('public')->url($this->logo_path);
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    public function videoUrl(): ?string
    {
        return $this->video_path ? Storage::disk('public')->url($this->video_path) : null;
    }

    /**
     * The embeddable player URL for the video link, if it's YouTube or Vimeo.
     */
    public function videoEmbedUrl(): ?string
    {
        return self::embedUrlFor($this->video_url);
    }

    /**
     * Whether there's anything to show on the story page, so the logo links
     * to it.
     */
    public function hasStory(): bool
    {
        return filled($this->summary) || filled($this->article) || $this->image_path || $this->video_path || $this->videoEmbedUrl();
    }

    /**
     * The article, written in Markdown, as HTML. Raw HTML in it is dropped
     * and unsafe links are removed, so editors can't inject scripts.
     */
    public function articleHtml(): string
    {
        return Str::markdown((string) $this->article, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    /**
     * A YouTube or Vimeo watch link as its embed URL, or null for anything else.
     */
    public static function embedUrlFor(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $host = Str::of((string) parse_url($url, PHP_URL_HOST))->lower()->replaceStart('www.', '')->replaceStart('m.', '')->value();
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $youtubeId = match (true) {
            $host === 'youtu.be' => Str::before($path, '/'),
            $host === 'youtube.com' && $path === 'watch' => $query['v'] ?? null,
            $host === 'youtube.com' && Str::startsWith($path, ['embed/', 'shorts/', 'live/']) => Str::after($path, '/'),
            default => null,
        };

        if ($youtubeId && preg_match('/^[\w-]{6,20}$/', $youtubeId)) {
            return "https://www.youtube-nocookie.com/embed/{$youtubeId}";
        }

        if (in_array($host, ['vimeo.com', 'player.vimeo.com']) && preg_match('#(?:^|/)(\d{6,12})$#', $path, $matches)) {
            return "https://player.vimeo.com/video/{$matches[1]}";
        }

        return null;
    }
}
