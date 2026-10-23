@php
  // One list per marquee row, keyed by the direction the row scrolls in.
  // Logos live in public/moderning_xelenic/assets/img/clients-web/{file}.webp;
  // width/height are the image's own size, and "dark" gives a light-on-dark
  // logo a dark tile.
  $clientRows = [
    -1 => [
      ['file' => 'anchor', 'name' => 'Anchor', 'width' => 320, 'height' => 107],
      ['file' => 'nescafe', 'name' => 'Nescafé', 'width' => 320, 'height' => 105],
      ['file' => 'haleon', 'name' => 'Haleon', 'width' => 320, 'height' => 72],
      ['file' => 'mirissa-waves', 'name' => 'Mirissa Waves', 'width' => 141, 'height' => 140],
      ['file' => 'snickers', 'name' => 'Snickers', 'width' => 320, 'height' => 96],
      ['file' => 'mindspark', 'name' => 'MindSpark', 'width' => 320, 'height' => 106, 'dark' => true],
      ['file' => 'fonterra', 'name' => 'Fonterra', 'width' => 241, 'height' => 140],
      ['file' => 'wasthi', 'name' => 'Wasthi FZ LLE', 'width' => 104, 'height' => 140],
    ],
    1 => [
      ['file' => 'redmi', 'name' => 'Redmi', 'width' => 267, 'height' => 108],
      ['file' => 'milan', 'name' => 'Milan', 'width' => 189, 'height' => 140],
      ['file' => 'sensodyne', 'name' => 'Sensodyne', 'width' => 225, 'height' => 86],
      ['file' => 'zeebroo', 'name' => 'Zeebroo', 'width' => 320, 'height' => 113],
      ['file' => 'panadol', 'name' => 'Panadol', 'width' => 140, 'height' => 140],
      ['file' => 'heavenly', 'name' => 'Heavenly', 'width' => 173, 'height' => 69],
      ['file' => 'mms', 'name' => "M&M's", 'width' => 320, 'height' => 115],
      ['file' => 'ratthi', 'name' => 'Ratthi', 'width' => 273, 'height' => 140],
    ],
  ];
@endphp
<!-- CLIENTS -->
<section class="clients" aria-labelledby="clients-title">
  <div class="container clients-head reveal">
    <span class="eyebrow">Our Clients</span>
    <h2 id="clients-title">Trusted by brands that keep growing</h2>
  </div>
  <!-- clients.js loops each row seamlessly (cloning tiles as needed), runs
       the rows in opposite directions, speeds them up and leans them as
       you scroll, and pauses a row while you hover it. -->
  <div class="container">
    <div class="clients-marquee">
      @foreach ($clientRows as $direction => $clients)
        <ul class="clients-row" data-direction="{{ $direction }}">
          @foreach ($clients as $client)
            <li @class(['client', 'client--dark' => $client['dark'] ?? false])><span class="client-tile"><img src="{{ asset('moderning_xelenic/assets/img/clients-web/'.$client['file'].'.webp') }}" alt="{{ $client['name'] }}" width="{{ $client['width'] }}" height="{{ $client['height'] }}" loading="lazy" decoding="async" /></span></li>
          @endforeach
        </ul>
      @endforeach
    </div>
  </div>
</section>
