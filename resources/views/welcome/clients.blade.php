{{-- The published clients (HomeController), managed in Admin → Clients. They
     split into two marquee rows that run in opposite directions; a logo
     links to the client's story page when there's one to show. --}}
@if ($clients->isNotEmpty())
  @php($rows = $clients->count() > 1 ? $clients->split(2) : collect([$clients]))
  <!-- CLIENTS -->
  <section class="clients" id="clients" aria-labelledby="clients-title">
    <div class="container clients-head reveal">
      <span class="eyebrow">Our Clients</span>
      <h2 id="clients-title">Trusted by brands that keep growing</h2>
    </div>
    <!-- clients.js loops each row seamlessly (cloning tiles as needed), runs
         the rows in opposite directions, speeds them up and leans them as
         you scroll, and pauses a row while you hover it. -->
    <div class="container">
      <div class="clients-marquee">
        @foreach ($rows as $index => $row)
          <ul class="clients-row" data-direction="{{ $index === 0 ? -1 : 1 }}">
            @foreach ($row as $client)
              <li @class(['client', 'client--dark' => $client->dark_logo])>
                @if ($client->hasStory())
                  <a href="{{ route('clients.show', $client) }}" class="client-tile" aria-label="{{ $client->name }}: read the story"><img src="{{ $client->logoUrl() }}" alt="{{ $client->name }}" loading="lazy" decoding="async" /></a>
                @else
                  <span class="client-tile"><img src="{{ $client->logoUrl() }}" alt="{{ $client->name }}" loading="lazy" decoding="async" /></span>
                @endif
              </li>
            @endforeach
          </ul>
        @endforeach
      </div>
    </div>
  </section>
@endif
