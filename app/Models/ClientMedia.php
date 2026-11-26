<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * One photo or video in a client story page's gallery: an uploaded image or
 * video (on the "public" disk), or a YouTube or Vimeo link.
 */
#[Fillable(['kind', 'path', 'url', 'caption', 'sort_order'])]
class ClientMedia extends Model
{
    public const IMAGE = 'image';

    public const VIDEO = 'video';

    public const LINK = 'link';

    /**
     * Photos and videos a story page can have.
     */
    public const MAX = 30;

    protected $table = 'client_media';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * An uploaded file goes with it.
     */
    protected static function booted(): void
    {
        static::deleted(function (ClientMedia $media) {
            if ($media->path) {
                Storage::disk('public')->delete($media->path);
            }
        });
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function isImage(): bool
    {
        return $this->kind === self::IMAGE;
    }

    public function isVideo(): bool
    {
        return $this->kind === self::VIDEO;
    }

    public function isLink(): bool
    {
        return $this->kind === self::LINK;
    }

    /**
     * The uploaded file's address.
     */
    public function fileUrl(): ?string
    {
        return $this->path ? Storage::disk('public')->url($this->path) : null;
    }

    /**
     * A link's embeddable player.
     */
    public function embedUrl(): ?string
    {
        return $this->isLink() ? Client::embedUrlFor($this->url) : null;
    }

    /**
     * Where a link plays: YouTube or Vimeo.
     */
    public function provider(): ?string
    {
        $embed = $this->embedUrl();

        return match (true) {
            $embed === null => null,
            str_contains($embed, 'vimeo') => 'Vimeo',
            default => 'YouTube',
        };
    }
}
