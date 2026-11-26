{{-- Developer Careers (CareersController@index): what we build, life on the
     team (photos in public/moderning_xelenic/assets/img/careers, from
     public/team), the open roles (App\Support\Careers) and applying. --}}
@php
    $photo = fn (string $name, int $width) => asset("moderning_xelenic/assets/img/careers/{$name}-{$width}.webp");
    $srcset = fn (string $name) => $photo($name, 800).' 800w, '.$photo($name, 1600).' 1600w';
@endphp
<x-layouts.site title="Developer Careers"
  description="Join Xelenic: build kiosk apps, games, cloud and AI products used by real people in malls, stores and events. See the open roles and apply."
  :styles="['css/careers.css']" :scripts="['js/careers.js']">
  <main class="cr">
    {{-- Hero --}}
    <section class="cr-hero">
      <div class="container cr-hero-grid">
        <div class="cr-hero-copy">
          <span class="cr-hiring"><span class="cr-pulse" aria-hidden="true"></span> We’re hiring · {{ $roles->count() }} open {{ Str::plural('role', $roles->count()) }}</span>
          <h1>Build the kiosks people <span class="text-accent">line up to play</span></h1>
          <p class="cr-lead">At Xelenic, developers build things people actually touch: kiosk apps and games in malls and at events, the studio our clients design them in, and the cloud and AI platforms behind them. Come build them with us.</p>
          <div class="cr-actions">
            <a href="#roles" class="btn btn-primary btn-lg">See open roles</a>
            <a href="#apply" class="btn btn-ghost btn-lg">Apply now</a>
          </div>
          <ul class="cr-tags" aria-label="What we work with">
            <li>Laravel</li><li>JavaScript</li><li>HTML5 games</li><li>Kiosk hardware</li><li>AI</li>
          </ul>
        </div>

        <div class="cr-hero-art">
          <figure class="cr-hero-photo">
            <img src="{{ $photo('team-coding', 1600) }}" srcset="{{ $srcset('team-coding') }}" sizes="(max-width: 1000px) 92vw, 560px"
              alt="Xelenic developers working together around laptops in the office" width="1600" height="1165" fetchpriority="high" />
          </figure>
          <div class="cr-float cr-float-code" aria-hidden="true">
            <span class="cr-float-dots"><i></i><i></i><i></i></span>
            <code><b>git</b> push <em>origin</em> kiosk-studio</code>
            <code class="cr-ok"><x-site.icon name="check" /> Live on the kiosk</code>
          </div>
          <div class="cr-float cr-float-team" aria-hidden="true">
            <span class="cr-float-icon"><x-site.icon name="globe" /></span>
            <span><b>Remote-first</b><small>Work from where you work best</small></span>
          </div>
        </div>
      </div>
    </section>

    {{-- What you'll build --}}
    <section class="cr-section">
      <div class="container">
        <div class="cr-head">
          <span class="eyebrow">What you’ll build</span>
          <h2>Products people use every day</h2>
          <p>Our work runs on touchscreens in public places, in our clients’ browsers, and in the cloud behind them.</p>
        </div>
        <ul class="cr-products">
          <li class="reveal" style="--tone: #3b82f6">
            <a href="{{ route('studio') }}">
              <span class="cr-product-icon"><x-site.icon name="device" /></span>
              <strong>Kiosk Studio</strong>
              <span>The browser-based editor our clients design kiosk apps in, with its flow map, screen editor and animation timeline.</span>
              <em>Explore Kiosk Studio <span aria-hidden="true">→</span></em>
            </a>
          </li>
          <li class="reveal" style="--tone: #8b5cf6">
            <a href="{{ route('marketplace.games.index') }}">
              <span class="cr-product-icon"><x-site.icon name="gamepad" /></span>
              <strong>Kiosk games</strong>
              <span>Spin wheels, runners, memory games and more: branded games that bring crowds to a kiosk.</span>
              <em>Play the games <span aria-hidden="true">→</span></em>
            </a>
          </li>
          <li class="reveal" style="--tone: #0ea5e9">
            <a href="{{ route('cloud.index') }}">
              <span class="cr-product-icon"><x-site.icon name="cloud" /></span>
              <strong>Xelenic Cloud</strong>
              <span>Servers, hosting and domains our clients order and manage from their panel.</span>
              <em>See Clouds <span aria-hidden="true">→</span></em>
            </a>
          </li>
          <li class="reveal" style="--tone: #10b981">
            <a href="{{ route('home') }}#ai-middleware">
              <span class="cr-product-icon"><x-site.icon name="sparkles" /></span>
              <strong>AI Middleware</strong>
              <span>The layer that connects AI models, APIs and data to our clients’ businesses.</span>
              <em>About AI Middleware <span aria-hidden="true">→</span></em>
            </a>
          </li>
        </ul>
      </div>
    </section>

    {{-- Life at Xelenic --}}
    <section class="cr-section cr-life">
      <div class="container">
        <div class="cr-head">
          <span class="eyebrow">Life at Xelenic</span>
          <h2>A team that builds, ships and celebrates together</h2>
        </div>
        <div class="cr-gallery">
          <figure class="cr-shot cr-shot-wide reveal">
            <img src="{{ $photo('team-games', 1600) }}" srcset="{{ $srcset('team-games') }}" sizes="(max-width: 720px) 92vw, 760px" alt="The Xelenic team playing team-building games outdoors" width="1600" height="893" loading="lazy" />
            <figcaption><x-site.icon name="family" /> Team-building days</figcaption>
          </figure>
          <figure class="cr-shot reveal">
            <img src="{{ $photo('kiosk-build', 1600) }}" srcset="{{ $srcset('kiosk-build') }}" sizes="(max-width: 720px) 92vw, 400px" alt="Engineers setting up and testing a self-service kiosk" width="1600" height="893" loading="lazy" />
            <figcaption><x-site.icon name="device" /> On site with our kiosks</figcaption>
          </figure>
          <figure class="cr-shot reveal">
            <img src="{{ $photo('team-day-out', 1600) }}" srcset="{{ $srcset('team-day-out') }}" sizes="(max-width: 720px) 92vw, 400px" alt="The Xelenic team relaxing together at an outdoor team day" width="1600" height="893" loading="lazy" />
            <figcaption><x-site.icon name="party" /> Team days out</figcaption>
          </figure>
        </div>

        <ul class="cr-values">
          <li class="reveal"><span class="cr-value-icon"><x-site.icon name="globe" /></span><strong>Remote-first</strong><span>We work as a distributed team, and get together for team days.</span></li>
          <li class="reveal"><span class="cr-value-icon"><x-site.icon name="rocket" /></span><strong>Ship real things</strong><span>Your work goes live on kiosks and in front of our clients’ customers, not into a drawer.</span></li>
          <li class="reveal"><span class="cr-value-icon"><x-site.icon name="code" /></span><strong>Modern, open technology</strong><span>Laravel, modern JavaScript and open-source foundations, kept clean and tested.</span></li>
          <li class="reveal"><span class="cr-value-icon"><x-site.icon name="book" /></span><strong>Learn as you go</strong><span>Code reviews, mentoring and room to pick up new skills, from game design to AI.</span></li>
        </ul>
      </div>
    </section>

    {{-- Open roles --}}
    <section class="cr-section" id="roles">
      <div class="container">
        <div class="cr-head cr-head-row">
          <div>
            <span class="eyebrow">Open roles</span>
            <h2>Find your place on the team</h2>
          </div>
          <div class="cr-filters" role="group" aria-label="Show roles in" data-role-filters>
            <button type="button" class="cr-filter is-active" aria-pressed="true" data-team="all">All <span>{{ $roles->count() }}</span></button>
            @foreach ($teams as $key => $team)
              <button type="button" class="cr-filter" aria-pressed="false" data-team="{{ $key }}">{{ $team['label'] }} <span>{{ $team['count'] }}</span></button>
            @endforeach
          </div>
        </div>

        <div class="cr-roles">
          @foreach ($roles as $role)
            <details class="cr-role reveal" data-role-card data-team="{{ $role['team'] }}" id="role-{{ $role['slug'] }}">
              <summary>
                <span class="cr-role-icon"><x-site.icon :name="['engineering' => 'code', 'games' => 'gamepad', 'ai' => 'sparkles', 'field' => 'device'][$role['team']] ?? 'briefcase'" /></span>
                <span class="cr-role-main">
                  <span class="cr-role-title">{{ $role['title'] }}</span>
                  <span class="cr-role-meta">
                    <span>{{ App\Support\Careers::TEAMS[$role['team']] }}</span>
                    <span><x-site.icon name="briefcase" /> {{ $role['type'] }}</span>
                    <span><x-site.icon name="pin" /> {{ $role['location'] }}</span>
                    <span>{{ $role['level'] }}</span>
                  </span>
                </span>
                <span class="cr-role-toggle" aria-hidden="true"></span>
              </summary>
              <div class="cr-role-body">
                <p class="cr-role-summary">{{ $role['summary'] }}</p>
                <div class="cr-role-cols">
                  <div>
                    <h3>What you’ll do</h3>
                    <ul>@foreach ($role['you_will'] as $item)<li>{{ $item }}</li>@endforeach</ul>
                  </div>
                  <div>
                    <h3>What you’ll bring</h3>
                    <ul>@foreach ($role['you_bring'] as $item)<li>{{ $item }}</li>@endforeach</ul>
                  </div>
                </div>
                <div class="cr-role-foot">
                  <ul class="cr-skills" aria-label="Skills">@foreach ($role['skills'] as $skill)<li>{{ $skill }}</li>@endforeach</ul>
                  <a href="{{ route('careers', ['role' => $role['slug']]) }}#apply" class="btn btn-primary" data-apply-for="{{ $role['slug'] }}">Apply for this role</a>
                </div>
              </div>
            </details>
          @endforeach
        </div>
        <p class="cr-open-note">Don’t see your role? <a href="{{ route('careers', ['role' => App\Support\Careers::OPEN_APPLICATION]) }}#apply" data-apply-for="{{ App\Support\Careers::OPEN_APPLICATION }}">Send an open application</a>: we’d still like to hear from you.</p>
      </div>
    </section>

    {{-- How hiring works --}}
    <section class="cr-section">
      <div class="container">
        <div class="cr-head">
          <span class="eyebrow">How hiring works</span>
          <h2>Simple, friendly and quick</h2>
        </div>
        <ol class="cr-steps">
          <li class="reveal"><span class="cr-step-num">1</span><x-site.icon name="upload" /><strong>Apply</strong><span>Send your CV or a link to your work with the form below.</span></li>
          <li class="reveal"><span class="cr-step-num">2</span><x-site.icon name="chat" /><strong>Say hello</strong><span>A relaxed chat about you, your work and what you’d like to do next.</span></li>
          <li class="reveal"><span class="cr-step-num">3</span><x-site.icon name="code" /><strong>Show us</strong><span>A small, practical task or a walk through something you’ve built.</span></li>
          <li class="reveal"><span class="cr-step-num">4</span><x-site.icon name="rocket" /><strong>Join the team</strong><span>An offer, and a warm welcome to the team.</span></li>
        </ol>
      </div>
    </section>

    {{-- Apply --}}
    <section class="cr-section cr-apply-section" id="apply">
      <div class="container cr-apply">
        <div class="cr-apply-copy">
          <span class="eyebrow">Apply</span>
          <h2>Let’s build something together</h2>
          <p>Tell us a little about yourself. Attach your CV, share a link to your work, or both.</p>
          <ul class="cr-contact">
            <li><x-site.icon name="mail" /> <a href="mailto:info@xelenic.com?subject={{ rawurlencode('Developer career') }}">info@xelenic.com</a></li>
            <li><x-site.icon name="phone" /> <a href="tel:094752751499">09475 275 1499</a></li>
          </ul>
          <figure class="cr-apply-photo">
            <img src="{{ $photo('team-coding', 800) }}" alt="" width="800" height="582" loading="lazy" />
          </figure>
        </div>

        <div class="cr-form-card">
          @if (session('applied'))
            <div class="cr-thanks" role="status">
              <span class="cr-thanks-icon"><x-site.icon name="check" /></span>
              <h3>Application sent</h3>
              <p>{{ session('applied') }}</p>
              <a href="#roles" class="btn btn-ghost">Back to the roles</a>
            </div>
          @else
            <form method="POST" action="{{ route('careers.apply') }}" enctype="multipart/form-data" class="cr-form" data-apply-form novalidate>
              @csrf
              @if ($errors->any())
                <p class="cr-error-summary" role="alert">Please check the highlighted fields.</p>
              @endif

              <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" required @error('role') aria-invalid="true" @enderror>
                  <option value="">Choose a role</option>
                  @foreach ($choices as $slug => $title)
                    <option value="{{ $slug }}" @selected($selected === $slug)>{{ $title }}</option>
                  @endforeach
                </select>
                @error('role') <p class="cr-error">{{ $message }}</p> @enderror
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="name">Full name</label>
                  <input type="text" id="name" name="name" value="{{ old('name') }}" autocomplete="name" required @error('name') aria-invalid="true" @enderror />
                  @error('name') <p class="cr-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                  <label for="email">Email</label>
                  <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" required @error('email') aria-invalid="true" @enderror />
                  @error('email') <p class="cr-error">{{ $message }}</p> @enderror
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="phone">Phone <span class="cr-optional">optional</span></label>
                  <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" autocomplete="tel" @error('phone') aria-invalid="true" @enderror />
                  @error('phone') <p class="cr-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                  <label for="location">Where you’re based <span class="cr-optional">optional</span></label>
                  <input type="text" id="location" name="location" value="{{ old('location') }}" autocomplete="address-level2" placeholder="City, country" />
                </div>
              </div>

              <div class="form-group">
                <label for="portfolio_url">Portfolio, GitHub or LinkedIn</label>
                <input type="url" id="portfolio_url" name="portfolio_url" value="{{ old('portfolio_url') }}" placeholder="https://" @error('portfolio_url') aria-invalid="true" @enderror />
                @error('portfolio_url') <p class="cr-error">{{ $message }}</p> @enderror
              </div>

              <div class="form-group">
                <span class="cr-label">CV</span>
                <label class="cr-file" for="cv" data-file-drop>
                  <input type="file" id="cv" name="cv" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" />
                  <span class="cr-file-icon"><x-site.icon name="upload" /></span>
                  <span class="cr-file-text" data-file-name><b>Choose a file</b> or drop it here<small>PDF or Word, up to 5 MB</small></span>
                </label>
                @error('cv') <p class="cr-error">{{ $message }}</p> @enderror
              </div>

              <div class="form-group">
                <label for="message">Why Xelenic? <span class="cr-optional">optional</span></label>
                <textarea id="message" name="message" rows="4" maxlength="3000" placeholder="Something you’ve built that you’re proud of, or what you’d love to work on.">{{ old('message') }}</textarea>
              </div>

              {{-- Left empty by people; bots fill it in. --}}
              <div class="cr-trap" aria-hidden="true">
                <label for="website">Website</label>
                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off" />
              </div>

              <label class="cr-consent">
                <input type="checkbox" name="consent" value="1" @checked(old('consent')) required />
                <span>Xelenic may keep my details to consider me for this and similar roles.</span>
              </label>
              @error('consent') <p class="cr-error">{{ $message }}</p> @enderror

              <button type="submit" class="btn btn-primary btn-lg cr-submit">Send application <span aria-hidden="true">→</span></button>
            </form>
          @endif
        </div>
      </div>
    </section>
  </main>
</x-layouts.site>
