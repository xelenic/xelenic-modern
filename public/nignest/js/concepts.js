/* Campaign concepts: a whole kiosk app built from a short recipe, out of the
   studio's own templates (templates.js). The website's Trending Kiosk Apps
   pages play them (App\Support\KioskConcepts holds the recipes).

   recipe = {
     name:    'Spin & Win Mall Activation',
     attract: ['Brand spotlight', 'Mega sale'],  // screen templates that take turns
     tagline: 'Spin to win a gift today',          // replaces the first one's placeholder line
     games:   ['wheel'],                           // one game, or a menu of several
     reward:  'Coupon code',                       // optional: a screen template won as the prize
     form:    true                                 // ask winners for their details
   }

   Attract loop → (game menu) → how to play → game → you won / so close →
   (reward) → (details form) → thank you → back to the loop. */

function conceptProject(recipe) {
  const template = function (name) {
    const t = SCREEN_TEMPLATES.find(function (x) { return x.name === name; });
    if (!t) throw new Error('No screen template called "' + name + '"');
    return t.make();
  };
  const isPrize = function (kind) { return PRIZE_GAMES.indexOf(kind) >= 0; };
  const back = function (target, y) {
    return B('Done', y || 1640, target, { x: 360, w: 360, h: 120, fontSize: 44, bg: 'rgba(255,255,255,0.16)', shadow: false });
  };

  // The attract loop.
  const attract = recipe.attract.map(template);
  if (recipe.tagline) {
    attract[0].objects.forEach(function (o) {
      if (o.text === 'Your tagline goes here' || o.text === 'Play fun games and win amazing prizes') o.text = recipe.tagline;
    });
  }
  const home = attract[0];

  // The games, each behind its how-to-play screen.
  const games = recipe.games.map(function (kind) {
    const game = makeScreen(kind);
    const start = gameStartScreen(kind, game.id);
    return { kind: kind, game: game, start: start };
  });

  let entry;
  const extra = [];
  if (games.length === 1) {
    entry = games[0].start;
    // Nothing to go back to but the loop.
    entry.objects.forEach(function (o) { if (o.action && o.action.type === 'back') o.action = { type: 'goto', target: home.id }; });
  } else {
    const colors = [['#7209b7', '#ffffff'], ['#06d6a0', '#073b4c'], ['#ef476f', '#ffffff'], ['#ffd166', '#3a0ca3']];
    const menu = {
      id: uid('s'), name: 'Choose a Game', type: 'page', bg: '#22223b', bg2: '#4a4e69', effect: 'stars', transition: 'slide',
      timer: { seconds: 0, target: '' },
      objects: [
        A(T('Choose a game', 200, 104), 'down', 0.1, 0.6),
        A(T('Score high to win a prize!', 370, 50, { bold: false, color: 'rgba(255,255,255,0.75)' }), 'fade', 0.5, 0.6)
      ].concat(games.map(function (g, i) {
        const c = colors[i % colors.length];
        return A(B(GAME_INFO[g.kind].icon + '  ' + GAME_INFO[g.kind].name, 560 + i * 240, g.start.id, { x: 140, w: 800, h: 200, bg: c[0], color: c[1] }), 'left', 0.5 + i * 0.12, 0.6, i === games.length - 1 ? 'shine' : '');
      }), [A(back(home.id), 'fade', 1.2, 0.5)])
    };
    games.forEach(function (g) {
      g.start.objects.forEach(function (o) { if (o.action && o.action.type === 'back') o.action = { type: 'goto', target: menu.id }; });
    });
    entry = menu;
    extra.push(menu);
  }

  // After the prize: their details (if asked for), then thanks.
  const thanks = {
    id: uid('s'), name: 'Thank You', type: 'page', bg: '#4361ee', bg2: '#4cc9f0', effect: 'sparkles', transition: 'zoom',
    timer: { seconds: 8, target: home.id },
    objects: [
      A(E('🎁', 320, 480), 'bounce', 0.1, 1.1, 'float'),
      A(T(recipe.form ? 'Thanks, {{name}}!' : 'Thanks for playing!', 900, 104, { shadow: true }), 'letters', 0.9, 0.6),
      A(T(recipe.form ? 'We’ll be in touch about your prize.' : 'Show this screen to collect your prize.', 1100, 56, { bold: false, x: 90, w: 900, h: 180 }), 'up', 1.6, 0.6),
      A(T('Back to the start in a few seconds…', 1640, 36, { bold: false, color: 'rgba(255,255,255,0.6)' }), 'fade', 2.2, 0.6, 'blink')
    ]
  };
  let claim = thanks;
  if (recipe.form) {
    const form = {
      id: uid('s'), name: 'Your Details', type: 'page', bg: '#22223b', bg2: '#4a4e69', effect: 'stars', transition: 'slide',
      timer: { seconds: 0, target: '' },
      objects: [
        A(T('Claim your prize', 220, 96), 'down', 0.1, 0.6),
        A(T('Leave your details and we’ll be in touch', 380, 48, { bold: false, color: 'rgba(255,255,255,0.75)', x: 90, w: 900, h: 130 }), 'fade', 0.5, 0.6),
        A({ id: uid('o'), type: 'input', x: 110, y: 600, w: 860, h: 150, placeholder: 'Your name', field: 'name', inputType: 'text', fontSize: 48, required: true, radius: 28 }, 'up', 0.7, 0.6),
        A({ id: uid('o'), type: 'input', x: 110, y: 800, w: 860, h: 150, placeholder: 'Mobile number', field: 'phone', inputType: 'tel', fontSize: 48, required: true, radius: 28 }, 'up', 0.85, 0.6),
        A(T('By submitting you agree to be contacted about this promotion.', 1000, 34, { bold: false, color: 'rgba(255,255,255,0.6)', x: 140, w: 800, h: 110 }), 'fade', 1.1, 0.6),
        A(B('Submit', 1200, thanks.id, { bg: '#06d6a0', color: '#073b4c', action: { type: 'submit', target: thanks.id } }), 'pop', 1.3, 0.7, 'shine'),
        A(B('Skip', 1440, home.id, { x: 360, w: 360, h: 120, fontSize: 44, bg: 'rgba(255,255,255,0.14)', shadow: false }), 'fade', 1.6, 0.5)
      ]
    };
    extra.push(form);
    claim = form;
  }
  if (recipe.reward) {
    // The prize is a screen of its own (a coupon, a gift…): its button
    // claims it, and it returns to the loop on its own.
    const reward = template(recipe.reward);
    reward.timer = { seconds: 20, target: home.id };
    const cta = reward.objects.filter(function (o) { return o.type === 'button'; }).pop();
    if (cta) cta.action = { type: 'goto', target: claim.id };
    extra.push(reward);
    claim = reward;
  }

  // Results.
  const results = [];
  const anyScore = games.some(function (g) { return !isPrize(g.kind); });
  const anyPrize = games.some(function (g) { return isPrize(g.kind); });
  let win = null, lose = null, prize = null;
  if (anyScore) {
    win = winnerScreen(claim.id, entry.id);
    lose = {
      id: uid('s'), name: 'So Close', type: 'page', bg: '#ef476f', bg2: '#ff9f1c', effect: 'rays', transition: 'fade',
      timer: { seconds: 20, target: home.id },
      objects: [
        A(E('😅', 300, 480), 'jelly', 0.1, 0.9, 'wiggle'),
        A(T('SO CLOSE!', 880, 140, { shadow: true }), 'zoom', 0.6, 0.6),
        A(T('Your score: {{score}}', 1090, 64), 'up', 1.0, 0.6),
        A(B('Try again', 1360, entry.id, { bg: '#ffffff', color: '#ef476f' }), 'up', 1.4, 0.6, 'pulse'),
        A(B('Finish', 1600, home.id, { x: 270, w: 540, h: 140, fontSize: 48, bg: 'rgba(255,255,255,0.2)', shadow: false }), 'fade', 1.8, 0.6)
      ]
    };
    results.push(win, lose);
  }
  if (anyPrize) {
    prize = {
      id: uid('s'), name: 'You Won', type: 'page', bg: '#f72585', bg2: '#ffb703', effect: 'confetti', transition: 'zoom',
      timer: { seconds: 25, target: home.id },
      objects: [
        A(C(560, -240, 760, '#ffffff', 0.1), 'zoom', 0, 1, 'breathe'),
        A(E('🎉', 300, 480), 'pop', 0.1, 0.8, 'tada'),
        A(T('YOU WON', 860, 80, { bold: false, color: 'rgba(255,255,255,0.9)' }), 'fade', 0.8, 0.6),
        A(T('{{prize}}', 980, 150, { shadow: true, h: 240 }), 'zoom', 1.1, 0.7, 'glow'),
        A(B('Claim your prize', 1360, claim.id, { bg: '#3a0ca3' }), 'up', 1.7, 0.6, 'shine'),
        A(back(home.id, 1600), 'fade', 2.1, 0.6)
      ]
    };
    results.push(prize);
  }
  games.forEach(function (g) {
    if (isPrize(g.kind)) {
      g.game.game.win = prize.id;
      g.game.game.lose = prize.id;
    } else {
      g.game.game.win = win.id;
      g.game.game.lose = lose.id;
    }
  });

  // The loop takes turns; any of its buttons starts the fun.
  attract.forEach(function (ad, i) {
    if (attract.length > 1) ad.timer = { seconds: AD_SECONDS, target: attract[(i + 1) % attract.length].id };
    else ad.timer = { seconds: 0, target: '' };
    ad.objects.forEach(function (o) { if (o.type === 'button') o.action = { type: 'goto', target: entry.id }; });
  });

  const screens = attract.concat(extra.filter(function (s) { return s === entry; }),
    games.reduce(function (all, g) { return all.concat([g.start, g.game]); }, []),
    results, extra.filter(function (s) { return s !== entry; }), [thanks]);

  return {
    id: uid('p'), name: recipe.name || 'Campaign Kiosk', version: 1, width: 1080, height: 1920,
    startScreen: home.id, idleTimeout: 45, vars: { score: 0 }, assets: [sampleLogoAsset()], screens: screens
  };
}
