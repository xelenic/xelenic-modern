<?php

namespace App\Support;

use App\Models\GameTemplate;
use Illuminate\Support\Str;
use InvalidArgumentException;
use ZipArchive;

/**
 * A Kiosk Studio game template .zip, read for the game_templates table. It's
 * checked the way the studio checks one on import (validateManifest and
 * readTemplateZip in public/nignest/js/editor-packages.js), so anything
 * accepted here also installs there:
 *
 *   manifest.json   id, name, version, icon, extends, … (at the top, or in one folder)
 *   game.js         the game's code; a preset ("extends") can leave it out
 *   assets/…        images and sounds
 */
final class GameTemplatePackage
{
    /**
     * The largest package, in kilobytes, as in Kiosk Studio.
     */
    public const MAX_KILOBYTES = 12 * 1024;

    private const FORMAT = 'kiosk-game-template';

    /**
     * A package's picture icon is kept with the row if it's this small.
     */
    private const MAX_ICON_BYTES = 200 * 1024;

    private const IMAGE_TYPES = [
        'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif', 'webp' => 'image/webp', 'svg' => 'image/svg+xml',
    ];

    /**
     * What a template .zip says about itself, as game_templates columns.
     *
     * @return array{slug: string, name: string, description: ?string, kind: string, extends: ?string, version: string, author: ?string, icon: ?string, icon_image: ?string, color: ?string}
     *
     * @throws InvalidArgumentException with a message for whoever uploaded it
     */
    public static function read(string $path): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new InvalidArgumentException('That file is not a .zip.');
        }

        try {
            return self::readManifest($zip);
        } finally {
            $zip->close();
        }
    }

    private static function readManifest(ZipArchive $zip): array
    {
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            if (! str_starts_with($name, '__MACOSX/') && ! str_ends_with($name, '.DS_Store')) {
                $names[] = $name;
            }
        }

        // manifest.json at the top, or inside the one folder the zip was made from.
        $root = '';
        if (! in_array('manifest.json', $names, true)) {
            $nested = collect($names)->first(fn (string $name) => preg_match('#^[^/]+/manifest\.json$#', $name));
            if (! $nested) {
                throw new InvalidArgumentException('manifest.json was not found at the top of the zip.');
            }
            $root = Str::before($nested, '/').'/';
        }

        $manifest = json_decode((string) $zip->getFromName($root.'manifest.json'), true);

        if (! is_array($manifest) || array_is_list($manifest)) {
            throw new InvalidArgumentException('manifest.json is not valid JSON with an object in it.');
        }
        if (isset($manifest['format']) && $manifest['format'] !== self::FORMAT) {
            throw new InvalidArgumentException('This zip is not a Kiosk Studio game template.');
        }

        $id = (string) ($manifest['id'] ?? '');
        if (! preg_match('/^[a-z0-9][a-z0-9_-]{1,40}$/', $id)) {
            throw new InvalidArgumentException('manifest.json needs an "id" of 2–41 lowercase letters, numbers, "-" or "_".');
        }
        if (in_array($id, GameTemplate::BUILTIN, true)) {
            throw new InvalidArgumentException("\"{$id}\" is the name of a built-in game. Choose another id.");
        }
        if (! is_string($manifest['name'] ?? null) || trim($manifest['name']) === '') {
            throw new InvalidArgumentException('manifest.json needs a "name".');
        }

        $extends = $manifest['extends'] ?? null;
        if ($extends !== null && ! in_array($extends, GameTemplate::BUILTIN, true)) {
            throw new InvalidArgumentException('"extends" must be one of: '.implode(', ', GameTemplate::BUILTIN).'.');
        }

        $main = (string) ($manifest['main'] ?? 'game.js');
        if (! $extends && $zip->locateName($root.$main) === false) {
            throw new InvalidArgumentException("{$main} was not found in the zip.");
        }

        [$icon, $iconImage] = self::icon($zip, $root, $manifest['icon'] ?? null);
        $color = $manifest['color'] ?? null;

        return [
            'slug' => $id,
            'name' => Str::limit(trim($manifest['name']), 250),
            'description' => is_string($manifest['description'] ?? null) ? Str::limit(trim($manifest['description']), 495) : null,
            'kind' => $extends ? 'preset' : 'custom',
            'extends' => $extends,
            'version' => Str::limit((string) ($manifest['version'] ?? '1.0.0'), 36),
            'author' => is_string($manifest['author'] ?? null) && $manifest['author'] !== '' ? Str::limit($manifest['author'], 250) : null,
            'icon' => $icon,
            'icon_image' => $iconImage,
            'color' => is_string($color) && preg_match('/^#[0-9a-fA-F]{3,8}$/', $color) ? $color : null,
        ];
    }

    /**
     * The manifest's icon: an emoji, or a picture in the package.
     *
     * @return array{0: ?string, 1: ?string} [emoji, data URL]
     */
    private static function icon(ZipArchive $zip, string $root, mixed $icon): array
    {
        if (! is_string($icon) || $icon === '') {
            return [null, null];
        }

        $type = self::IMAGE_TYPES[strtolower(pathinfo($icon, PATHINFO_EXTENSION))] ?? null;
        if ($type) {
            $stat = $zip->statName($root.$icon);
            $bytes = $stat && $stat['size'] <= self::MAX_ICON_BYTES ? $zip->getFromName($root.$icon) : false;

            return [null, $bytes === false ? null : 'data:'.$type.';base64,'.base64_encode($bytes)];
        }

        return [mb_strlen($icon) <= 4 ? $icon : null, null];
    }
}
