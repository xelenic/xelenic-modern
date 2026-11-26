{{-- A kiosk app's play link (PlayController): the design running in Kiosk
     Studio's player, scaled to the window. Served sandboxed, so it has no
     access to the site's cookies; it doesn't load the site's own CSS or JS.
     What players type into its forms isn't saved. --}}
@php
    $script = fn (string $file) => asset("nignest/js/{$file}").'?v='.@filemtime(public_path("nignest/js/{$file}"));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0b0c10">
    <title>{{ $name }} · Play</title>
    <meta property="og:title" content="Play {{ $name }}">
    <meta property="og:description" content="A kiosk app made with Kiosk Studio by Xelenic. Play it in your browser.">
    <style>
        :root { color-scheme: dark; }
        html, body { height: 100%; margin: 0; }
        body { display: flex; flex-direction: column; height: 100dvh; background: #000; color: #e8eaf0; font: 14px/1.4 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        header { flex: none; display: flex; align-items: center; gap: 12px; padding: 10px max(14px, env(safe-area-inset-right)) 10px max(14px, env(safe-area-inset-left)); background: #0b0c10; border-bottom: 1px solid #1f232d; }
        .mark { flex: none; display: grid; place-items: center; width: 34px; height: 34px; border-radius: 10px; background: #2563eb; }
        .mark svg { width: 20px; height: 20px; }
        .title { min-width: 0; flex: 1; }
        .title b { display: block; overflow: hidden; font-size: 15px; white-space: nowrap; text-overflow: ellipsis; }
        .title a { color: #8d93a3; font-size: 12px; text-decoration: none; }
        .title a:hover { color: #c7cbd6; }
        button { display: inline-flex; align-items: center; gap: 7px; height: 34px; padding: 0 13px; border: 1px solid #2e323c; border-radius: 9px; background: #191b21; color: #e8eaf0; font-family: inherit; font-size: 13px; font-weight: 600; line-height: 1; cursor: pointer; }
        button:hover { background: #22252d; }
        button:focus-visible { outline: 2px solid #3d8bff; outline-offset: 2px; }
        button svg { width: 16px; height: 16px; }
        #app { position: relative; flex: 1; min-height: 0; }
        #toast { position: fixed; left: 50%; bottom: max(24px, env(safe-area-inset-bottom)); max-width: calc(100% - 40px); padding: 11px 16px; border-radius: 12px; background: rgba(20, 22, 28, .94); box-shadow: 0 12px 30px rgba(0, 0, 0, .5); font-weight: 600; text-align: center; opacity: 0; transform: translate(-50%, 12px); transition: opacity .25s, transform .25s; pointer-events: none; }
        #toast.on { opacity: 1; transform: translate(-50%, 0); }
        body.fs header { display: none; }
        @media (max-width: 520px) {
            .title a, button span { display: none; }
            button { padding: 0 10px; }
        }
    </style>
</head>
<body>
    <header>
        <span class="mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2.5" y="7" width="19" height="11" rx="5"/><path d="M7.5 11v3M6 12.5h3M15.5 12h.01M17.5 14h.01"/></svg>
        </span>
        <div class="title">
            <b>{{ $name }}</b>
            <a href="{{ url('/') }}" target="_blank" rel="noopener">A kiosk app made with Kiosk Studio · Xelenic</a>
        </div>
        <button type="button" id="restart" title="Start again from the first screen">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg>
            <span>Restart</span>
        </button>
        <button type="button" id="fullscreen" title="Play full screen">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 3H3v5M16 3h5v5M8 21H3v-5M16 21h5v-5"/></svg>
            <span>Full screen</span>
        </button>
    </header>

    <main id="app" aria-label="{{ $name }}"></main>
    <div id="toast" role="status" aria-live="polite"></div>

    <script type="application/json" id="design">@json($design)</script>
    <script src="{{ $script('art.js') }}"></script>
    <script src="{{ $script('games.js') }}"></script>
    <script src="{{ $script('runtime.js') }}"></script>
    <script>
        (function () {
            var design = JSON.parse(document.getElementById('design').textContent);
            var toastEl = document.getElementById('toast');
            var hide = 0;
            function toast(message) {
                toastEl.textContent = message;
                toastEl.classList.add('on');
                clearTimeout(hide);
                hide = setTimeout(function () { toastEl.classList.remove('on'); }, 3500);
            }

            var player = KioskRuntime().createPlayer(document.getElementById('app'), design, {
                onSubmit: function () { toast('Thanks! This is a shared preview, so what you enter isn’t saved.'); }
            });
            document.getElementById('restart').addEventListener('click', function () { player.reset(); });

            var fs = document.getElementById('fullscreen');
            if (!document.documentElement.requestFullscreen) fs.hidden = true;
            fs.addEventListener('click', function () {
                if (document.fullscreenElement) document.exitFullscreen();
                else document.documentElement.requestFullscreen().catch(function () { toast('Full screen isn’t available here.'); });
            });
            document.addEventListener('fullscreenchange', function () {
                document.body.classList.toggle('fs', !!document.fullscreenElement);
            });
        })();
    </script>
</body>
</html>
