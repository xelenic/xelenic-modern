<?php

namespace App\Support;

use App\Models\Project;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A kiosk app designed in Kiosk Studio (the kiosk engine in public/nignest)
 * and attached to a Kiosk App project. The design is the studio's project
 * JSON, kept as a file on the private local disk since its pictures are inside
 * it; the project's details hold a short summary of it for its page.
 *
 * Its data set is what its forms collect: the input fields on its screens, and
 * the entries sent from them while it was played in the studio. The studio
 * keeps those entries in the browser; they come along whenever the design is
 * saved, and are kept in a second file beside it.
 */
final class KioskDesign
{
    /**
     * The largest design accepted, in characters of JSON (about 5 MB).
     */
    public const MAX_LENGTH = 5_000_000;

    /**
     * The largest batch of entries accepted with a design, in characters of
     * JSON, and the most entries kept for one (the newest).
     */
    public const MAX_ENTRIES_LENGTH = 1_000_000;

    public const MAX_ENTRIES = 1000;

    /**
     * Optional on a new project; required when saving one over it.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(bool $required = false): array
    {
        return [
            'kiosk_design' => [$required ? 'required' : 'nullable', 'string', 'max:'.self::MAX_LENGTH, function (string $attribute, mixed $value, Closure $fail) {
                if (self::decode($value) === null) {
                    $fail('The kiosk app design could not be read. Open Kiosk Studio and use the design again.');
                }
            }],
        ];
    }

    /**
     * The form entries sent with a design (kiosk_data), always optional.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function entryRules(): array
    {
        return [
            'kiosk_data' => ['nullable', 'string', 'max:'.self::MAX_ENTRIES_LENGTH, function (string $attribute, mixed $value, Closure $fail) {
                if (self::decodeEntries($value) === null) {
                    $fail('The data collected by the kiosk app could not be read.');
                }
            }],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'kiosk_design.max' => 'The kiosk app design is too large to attach (over 5 MB). Use smaller pictures in Kiosk Studio and try again.',
        ];
    }

    /**
     * The design, or null when it isn't a Kiosk Studio project with screens.
     *
     * @return array<string, mixed>|null
     */
    public static function decode(mixed $json): ?array
    {
        $design = is_string($json) ? json_decode($json, true) : null;

        return is_array($design) && is_array($design['screens'] ?? null) && $design['screens'] !== [] ? $design : null;
    }

    /**
     * Form entries as the studio keeps them: a list of flat records of field
     * names and values. Null when it's anything else.
     *
     * @return list<array<string, scalar|null>>|null
     */
    public static function decodeEntries(mixed $json): ?array
    {
        $entries = is_string($json) ? json_decode($json, true) : null;

        if (! is_array($entries) || ! array_is_list($entries)) {
            return null;
        }

        foreach ($entries as $entry) {
            if (! is_array($entry) || ($entry !== [] && array_is_list($entry))) {
                return null;
            }

            foreach ($entry as $field => $value) {
                if (! is_string($field) || strlen($field) > 64 || ! (is_scalar($value) || $value === null)) {
                    return null;
                }
            }
        }

        return $entries;
    }

    /**
     * What the project's page shows about the design.
     *
     * @param  array<string, mixed>  $design
     * @return array{name: string, screens: int, orientation: string}
     */
    public static function summary(array $design): array
    {
        $name = Str::limit(trim((string) ($design['name'] ?? '')), 120);

        return [
            'name' => $name !== '' ? $name : 'Untitled design',
            'screens' => count($design['screens']),
            'orientation' => ($design['width'] ?? 1080) > ($design['height'] ?? 1920) ? 'landscape' : 'portrait',
        ];
    }

    /**
     * What a save changed in a design, for the project's log (see
     * App\Models\ProjectActivity): lines of {tone, label, items}. With no
     * $old design, a line saying what the first one is.
     *
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>  $new
     * @return list<array{tone: string, label: string, items: list<string>}>
     */
    public static function changes(?array $old, array $new): array
    {
        $summary = self::summary($new);
        $line = function (string $tone, string $label, iterable $items): ?array {
            $items = array_values(collect($items)->map(fn ($item) => Str::limit((string) $item, 80))->all());
            if ($items === []) {
                return null;
            }
            // A long list is cut short, with a count of the rest.
            $more = count($items) - 12;

            return ['tone' => $tone, 'label' => $label, 'items' => $more > 1 ? [...array_slice($items, 0, 12), "and {$more} more"] : $items];
        };

        if ($old === null) {
            return [$line('info', 'Kiosk Studio design', [
                $summary['name'],
                $summary['screens'].' '.Str::plural('screen', $summary['screens']),
                ucfirst($summary['orientation']),
            ])];
        }

        $was = self::summary($old);
        $before = self::screensById($old);
        $after = self::screensById($new);
        $kept = array_intersect_key($after, $before);
        $name = fn (array $screen) => trim((string) ($screen['name'] ?? '')) ?: 'Untitled screen';
        // A screen's content: everything but its name and its place on the flow map.
        $content = fn (array $screen) => json_encode(Arr::except($screen, ['name', 'fx', 'fy']));
        $moved = collect($kept)->contains(fn (array $screen, $id) => [$screen['fx'] ?? null, $screen['fy'] ?? null] !== [$before[$id]['fx'] ?? null, $before[$id]['fy'] ?? null]);
        $start = fn (array $design, array $screens) => isset($screens['s:'.($design['startScreen'] ?? '')]) ? $name($screens['s:'.$design['startScreen']]) : null;
        $games = fn (array $design) => collect($design['gameTemplates'] ?? [])
            ->filter(fn ($game) => is_array($game) && isset($game['manifest']['id']))
            ->mapWithKeys(fn (array $game) => [(string) $game['manifest']['id'] => (string) ($game['manifest']['name'] ?? $game['manifest']['id'])]);
        $pictures = fn (array $design) => collect($design['assets'] ?? [])
            ->filter(fn ($asset) => is_array($asset))
            ->mapWithKeys(fn (array $asset, $i) => [(string) ($asset['id'] ?? "#{$i}") => (string) ($asset['name'] ?? 'Picture')]);
        [$gamesBefore, $gamesAfter] = [$games($old), $games($new)];
        [$picturesBefore, $picturesAfter] = [$pictures($old), $pictures($new)];

        return array_values(array_filter([
            $was['name'] !== $summary['name'] ? $line('renamed', 'Design renamed', ["{$was['name']} → {$summary['name']}"]) : null,
            $was['orientation'] !== $summary['orientation'] ? $line('edited', 'Orientation', [ucfirst($was['orientation']).' → '.ucfirst($summary['orientation'])]) : null,
            $line('added', 'Screens added', collect(array_diff_key($after, $before))->map($name)),
            $line('removed', 'Screens removed', collect(array_diff_key($before, $after))->map($name)),
            $line('renamed', 'Screens renamed', collect($kept)
                ->filter(fn (array $screen, $id) => $name($screen) !== $name($before[$id]))
                ->map(fn (array $screen, $id) => $name($before[$id]).' → '.$name($screen))),
            $line('edited', 'Screens changed', collect($kept)
                ->filter(fn (array $screen, $id) => $content($screen) !== $content($before[$id]))
                ->map($name)),
            $start($old, $before) !== $start($new, $after) && $start($new, $after) ? $line('edited', 'Start screen', [$start($new, $after)]) : null,
            $line('added', 'Games added', $gamesAfter->diffKeys($gamesBefore)),
            $line('removed', 'Games removed', $gamesBefore->diffKeys($gamesAfter)),
            $line('added', 'Pictures added', $picturesAfter->diffKeys($picturesBefore)),
            $line('removed', 'Pictures removed', $picturesBefore->diffKeys($picturesAfter)),
            $moved ? $line('info', 'Flow map', ['Screens rearranged']) : null,
        ]));
    }

    /**
     * A design's screens, keyed by their id (or place, for one without).
     *
     * @param  array<string, mixed>  $design
     * @return array<string, array<string, mixed>>
     */
    private static function screensById(array $design): array
    {
        return collect($design['screens'] ?? [])
            ->filter(fn ($screen) => is_array($screen))
            ->mapWithKeys(fn (array $screen, int $i) => ['s:'.($screen['id'] ?? "#{$i}") => $screen])
            ->all();
    }

    public static function save(Project $project, string $json): void
    {
        Storage::disk('local')->put(self::path($project), $json);
    }

    /**
     * Put a (valid) design on a project that already exists, in place of any
     * design it had.
     */
    public static function attach(Project $project, string $json): void
    {
        self::save($project, $json);

        $project->update(['details' => [...($project->details ?? []), 'design' => self::summary(self::decode($json))]]);
    }

    /**
     * The project's saved design, as Kiosk Studio's project data, or null.
     *
     * @return array<string, mixed>|null
     */
    public static function design(Project $project): ?array
    {
        return self::exists($project) ? self::decode(Storage::disk('local')->get(self::path($project))) : null;
    }

    public static function exists(Project $project): bool
    {
        return $project->kioskDesign() !== null && Storage::disk('local')->exists(self::path($project));
    }

    /**
     * The fields the design's forms collect: every input on every screen.
     *
     * @return list<array{field: string, label: string, type: string, required: bool, screen: string}>
     */
    public static function fields(Project $project): array
    {
        $design = self::design($project);

        return collect($design['screens'] ?? [])
            ->flatMap(fn (array $screen) => collect($screen['objects'] ?? [])
                ->filter(fn (mixed $object) => is_array($object) && ($object['type'] ?? null) === 'input')
                ->map(fn (array $object) => [
                    'field' => (string) ($object['field'] ?? '') ?: 'field',
                    'label' => (string) ($object['placeholder'] ?? ''),
                    'type' => (string) ($object['inputType'] ?? 'text'),
                    'required' => (bool) ($object['required'] ?? false),
                    'screen' => (string) ($screen['name'] ?? ''),
                ]))
            ->values()
            ->all();
    }

    /**
     * The form entries kept for a project's design, oldest first.
     *
     * @return list<array<string, scalar|null>>
     */
    public static function entries(Project $project): array
    {
        return self::decodeEntries(Storage::disk('local')->get(self::entriesPath($project))) ?? [];
    }

    /**
     * Add entries to the ones kept, leaving out any already there (each save
     * brings the browser's whole list), and keep the newest MAX_ENTRIES.
     *
     * @param  list<array<string, scalar|null>>  $entries
     */
    public static function addEntries(Project $project, array $entries): void
    {
        if ($entries === []) {
            return;
        }

        $kept = collect(self::entries($project))
            ->concat($entries)
            ->unique(fn (array $entry) => json_encode($entry))
            ->sortBy(fn (array $entry) => (string) ($entry['time'] ?? ''))
            ->take(-self::MAX_ENTRIES)
            ->values();

        Storage::disk('local')->put(self::entriesPath($project), $kept->toJson());
    }

    /**
     * Every field the entries use, time first, then in the order they appear.
     *
     * @param  list<array<string, scalar|null>>  $entries
     * @return list<string>
     */
    public static function columns(array $entries): array
    {
        return collect($entries)
            ->flatMap(fn (array $entry) => array_keys($entry))
            ->unique()
            ->sortBy(fn (string $column) => $column === 'time' ? 0 : 1)
            ->values()
            ->all();
    }

    /**
     * The entries as a spreadsheet, newest last.
     */
    public static function entriesCsv(Project $project): StreamedResponse
    {
        $entries = self::entries($project);
        $columns = self::columns($entries);

        return response()->streamDownload(function () use ($entries, $columns) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);

            foreach ($entries as $entry) {
                fputcsv($out, array_map(fn (string $column) => self::cell($entry[$column] ?? ''), $columns));
            }

            fclose($out);
        }, Str::slug($project->name).'-data.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Anyone at the kiosk can type into its forms, so a value that a
     * spreadsheet would run as a formula is kept as plain text.
     */
    private static function cell(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }

    /**
     * The design as a .kiosk.json file, which Kiosk Studio opens.
     */
    public static function download(Project $project): StreamedResponse
    {
        return Storage::disk('local')->download(
            self::path($project),
            Str::slug($project->name).'.kiosk.json',
            ['Content-Type' => 'application/json'],
        );
    }

    private static function path(Project $project): string
    {
        return "kiosk-designs/{$project->id}.json";
    }

    private static function entriesPath(Project $project): string
    {
        return "kiosk-designs/{$project->id}.entries.json";
    }
}
