{{-- A built-in game's demo (GameMarketplaceController@demo), framed by its
     store page: Kiosk Studio's player running a three-screen kiosk app made
     from the studio's own templates (js/templates.js): the game's how-to-play
     screen, the game, and a result screen with "Play again". Sandboxed, like
     a play link, and it doesn't load the site's CSS or JS. --}}
@php
    $script = fn (string $file) => asset("nignest/js/{$file}").'?v='.@filemtime(public_path("nignest/js/{$file}"));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $game->name }} · Demo</title>
    <style>
        :root { color-scheme: dark; }
        html, body { height: 100%; margin: 0; background: #000; }
        #app { position: absolute; inset: 0; }
        #again { position: fixed; top: 10px; right: 10px; z-index: 5; display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; padding: 0; border: 0; border-radius: 50%; background: rgba(0, 0, 0, .42); color: #fff; cursor: pointer; opacity: .75; transition: opacity .2s, background .2s; }
        #again:hover, #again:focus-visible { opacity: 1; background: rgba(0, 0, 0, .65); }
        #again:focus-visible { outline: 2px solid #fff; outline-offset: 2px; }
        #again svg { width: 18px; height: 18px; }
    </style>
</head>
<body>
    <main id="app" aria-label="{{ $game->name }} demo"></main>
    <button type="button" id="again" title="Start again" aria-label="Start again">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg>
    </button>

    <script src="{{ $script('art.js') }}"></script>
    <script src="{{ $script('games.js') }}"></script>
    <script src="{{ $script('runtime.js') }}"></script>
    <script src="{{ $script('templates.js') }}"></script>
    <script>
        (function () {
            var kind = @json($game->slug);
            var prize = PRIZE_GAMES.indexOf(kind) >= 0;

            var game = makeScreen(kind);
            var start = gameStartScreen(kind, game.id);
            // Nothing to go back to in a demo.
            start.objects = start.objects.filter(function (o) { return !(o.action && o.action.type === 'back'); });

            var result = makeScreen('page', 'Result');
            result.bg = '#06d6a0';
            result.bg2 = '#118ab2';
            result.effect = 'confetti';
            result.transition = 'zoom';
            result.objects.push(
                A(E(prize ? '🎉' : '🏆', 380, 460), 'bounce', 0.1, 1, 'wiggle'),
                A(T(prize ? (kind === 'scratch' ? 'Your prize' : 'Your spin') : 'Your score', 900, 72, { bold: false }), 'up', 0.6, 0.6),
                A(T(prize ? '@{{prize}}' : '@{{score}}', 1010, prize ? 140 : 180, { shadow: true, h: 240 }), 'pop', 0.9, 0.7),
                A(B('Play again', 1400, game.id, { bg: '#ffffff', color: '#118ab2' }), 'up', 1.3, 0.6, 'shine'),
                A(B('How to play', 1630, start.id, { x: 270, w: 540, h: 140, fontSize: 48, bg: 'rgba(255,255,255,0.2)', shadow: false }), 'fade', 1.6, 0.6)
            );
            game.game.win = result.id;
            game.game.lose = result.id;

            var design = {
                id: 'demo-' + kind, name: @json($game->name), version: 1, width: 1080, height: 1920,
                startScreen: start.id, idleTimeout: 120, vars: { score: 0 }, assets: [], screens: [start, game, result]
            };

            var player = KioskRuntime().createPlayer(document.getElementById('app'), design, {});
            document.getElementById('again').addEventListener('click', function () { player.reset(); });
        })();
    </script>
</body>
</html>
