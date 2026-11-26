{{-- A concept's demo (TrendingKioskAppController@demo), framed by its page:
     the whole campaign, built from its recipe by Kiosk Studio's own templates
     (js/concepts.js) and run by its player. Sandboxed, like a play link, and
     it doesn't load the site's CSS or JS. --}}
@php
    $script = fn (string $file) => asset("nignest/js/{$file}").'?v='.@filemtime(public_path("nignest/js/{$file}"));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $concept['name'] }} · Demo</title>
    <style>
        :root { color-scheme: dark; }
        html, body { height: 100%; margin: 0; background: #000; }
        #app { position: absolute; inset: 0; }
        #again { position: fixed; top: 10px; right: 10px; z-index: 5; display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; padding: 0; border: 0; border-radius: 50%; background: rgba(0, 0, 0, .42); color: #fff; cursor: pointer; opacity: .75; transition: opacity .2s, background .2s; }
        #again:hover, #again:focus-visible { opacity: 1; background: rgba(0, 0, 0, .65); }
        #again:focus-visible { outline: 2px solid #fff; outline-offset: 2px; }
        #again svg { width: 18px; height: 18px; }
        #toast { position: fixed; left: 50%; bottom: 24px; max-width: calc(100% - 40px); padding: 10px 14px; border-radius: 12px; background: rgba(20, 22, 28, .94); color: #fff; font: 600 13px/1.4 system-ui, sans-serif; text-align: center; opacity: 0; transform: translate(-50%, 10px); transition: opacity .25s, transform .25s; pointer-events: none; }
        #toast.on { opacity: 1; transform: translate(-50%, 0); }
    </style>
</head>
<body>
    <main id="app" aria-label="{{ $concept['name'] }} demo"></main>
    <button type="button" id="again" title="Start again" aria-label="Start again">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg>
    </button>
    <div id="toast" role="status" aria-live="polite"></div>

    <script src="{{ $script('art.js') }}"></script>
    <script src="{{ $script('games.js') }}"></script>
    <script src="{{ $script('runtime.js') }}"></script>
    <script src="{{ $script('templates.js') }}"></script>
    <script src="{{ $script('concepts.js') }}"></script>
    <script>
        (function () {
            var recipe = @json(['name' => $concept['name']] + $concept['recipe']);
            var toast = document.getElementById('toast');
            var hide = 0;
            var player = KioskRuntime().createPlayer(document.getElementById('app'), conceptProject(recipe), {
                onSubmit: function () {
                    toast.textContent = 'This is a demo, so what you entered isn’t saved.';
                    toast.classList.add('on');
                    clearTimeout(hide);
                    hide = setTimeout(function () { toast.classList.remove('on'); }, 3500);
                }
            });
            document.getElementById('again').addEventListener('click', function () { player.reset(); });
        })();
    </script>
</body>
</html>
