{{-- What a client's story page has in it. --}}
@php
    $parts = array_filter([
        filled($client->article) ? ['article', 'Article'] : null,
        ($client->image_path || $client->hasGallery()) ? ['image', 'Photos'] : null,
        ($client->video_path || $client->videoEmbedUrl()) ? ['play', 'Video'] : null,
    ]);
@endphp
@if ($parts)
  <span class="cl-meta">
    @foreach ($parts as [$icon, $label])
      <span><x-site.icon :name="$icon" /> {{ $label }}</span>
    @endforeach
  </span>
@endif
