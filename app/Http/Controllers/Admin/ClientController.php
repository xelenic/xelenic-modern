<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientRequest;
use App\Models\Client;
use App\Models\ClientMedia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ClientController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:manage clients'),
        ];
    }

    /**
     * Every client, in the order the home page shows them.
     */
    public function index(): View
    {
        return view('admin.clients.index', [
            'clients' => Client::ordered()
                ->withExists('media')
                ->withCount([
                    'media as photos_count' => fn ($query) => $query->where('kind', ClientMedia::IMAGE),
                    'media as videos_count' => fn ($query) => $query->where('kind', '!=', ClientMedia::IMAGE),
                ])
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.clients.create', [
            'client' => new Client(['is_published' => true, 'sort_order' => (Client::max('sort_order') ?? 0) + 1]),
        ]);
    }

    public function store(ClientRequest $request): RedirectResponse
    {
        $client = new Client($this->attributes($request));
        $this->storeFiles($request, $client);
        $client->save();
        $this->saveGallery($request, $client);

        return to_route('admin.clients.index')->with('success', "{$client->name} was added.");
    }

    public function edit(Client $client): View
    {
        return view('admin.clients.edit', [
            'client' => $client->load('media'),
        ]);
    }

    public function update(ClientRequest $request, Client $client): RedirectResponse
    {
        $client->fill($this->attributes($request));
        $this->storeFiles($request, $client);
        $client->save();
        $this->saveGallery($request, $client);

        return to_route('admin.clients.index')->with('success', "{$client->name} was updated.");
    }

    public function destroy(Client $client): RedirectResponse
    {
        // The model deletes its files (see Client::booted()).
        $client->delete();

        return to_route('admin.clients.index')->with('success', "{$client->name} was deleted.");
    }

    /**
     * The article editor's preview: Markdown as the story page shows it.
     */
    public function previewArticle(Request $request): JsonResponse
    {
        $request->validate(['article' => ['nullable', 'string', 'max:50000']]);

        return response()->json(['html' => Client::markdownToHtml($request->input('article'))]);
    }

    /**
     * The gallery: captions, positions and removals for what's there, then
     * new uploads and links at the end.
     */
    private function saveGallery(ClientRequest $request, Client $client): void
    {
        foreach ($client->media()->get() as $item) {
            $input = $request->input("media.{$item->id}");
            if (! is_array($input)) {
                continue;
            }
            if (! empty($input['remove'])) {
                // The model deletes its file (see ClientMedia::booted()).
                $item->delete();

                continue;
            }
            $item->update([
                'caption' => trim((string) ($input['caption'] ?? '')) ?: null,
                'sort_order' => (int) ($input['sort_order'] ?? $item->sort_order),
            ]);
        }

        $next = (int) $client->media()->max('sort_order') + 1;
        foreach ($request->file('new_media', []) as $file) {
            $video = str_starts_with((string) $file->getMimeType(), 'video/') || in_array(strtolower($file->getClientOriginalExtension()), ['mp4', 'webm'], true);
            $client->media()->create([
                'kind' => $video ? ClientMedia::VIDEO : ClientMedia::IMAGE,
                'path' => $file->store("clients/{$client->slug}/gallery", 'public'),
                'sort_order' => $next++,
            ]);
        }
        foreach ($request->links() as $url) {
            $client->media()->create(['kind' => ClientMedia::LINK, 'url' => $url, 'sort_order' => $next++]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(ClientRequest $request): array
    {
        return [
            ...Arr::only($request->validated(), ['name', 'slug', 'summary', 'video_url', 'article']),
            'dark_logo' => $request->boolean('dark_logo'),
            'is_published' => $request->boolean('is_published'),
            'sort_order' => (int) $request->validated('sort_order'),
        ];
    }

    /**
     * Save new uploads (replacing and deleting the old files) and drop the
     * image or video when asked to.
     */
    private function storeFiles(ClientRequest $request, Client $client): void
    {
        $disk = Storage::disk('public');

        foreach (['logo' => 'logo_path', 'image' => 'image_path', 'video' => 'video_path'] as $input => $column) {
            $remove = $request->boolean("remove_{$input}");

            if (($request->hasFile($input) || $remove) && $client->{$column}) {
                $disk->delete($client->{$column});
                $client->{$column} = null;
            }

            if ($request->hasFile($input)) {
                $client->{$column} = $request->file($input)->store("clients/{$client->slug}", 'public');
            }
        }
    }
}
