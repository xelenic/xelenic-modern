/* Defaults for new objects, new screens and the starter projects. */

function uid(prefix) {
  return (prefix || 'id') + '_' + Math.random().toString(36).slice(2, 9);
}

const OBJECT_KINDS = {
  text: { label: 'Text', type: 'text', w: 820, h: 140, text: 'Your text here', fontSize: 72, color: '#ffffff', bold: true, align: 'center', font: 'sans' },
  button: { label: 'Button', type: 'button', w: 620, h: 160, text: 'Button', fontSize: 56, color: '#ffffff', bg: '#ff7a1a', radius: 80, bold: true, font: 'rounded', action: { type: 'none', target: '' } },
  image: { label: 'Image', type: 'image', w: 480, h: 480, src: '', fit: 'contain', radius: 0 },
  emoji: { label: 'Sticker', type: 'emoji', w: 300, h: 300, emoji: '⭐' },
  rect: { label: 'Box', type: 'shape', shape: 'rect', w: 640, h: 380, bg: '#ffffff', radius: 40, opacity: 0.18 },
  circle: { label: 'Circle', type: 'shape', shape: 'circle', w: 360, h: 360, bg: '#ffd166', opacity: 1 },
  input: { label: 'Input', type: 'input', w: 860, h: 140, placeholder: 'Your name', field: 'name', inputType: 'text', fontSize: 46, required: true, radius: 24 },
  score: { label: 'Score', type: 'text', w: 820, h: 130, text: 'Score: {{score}}', fontSize: 72, color: '#ffe14d', bold: true, align: 'center', font: 'rounded', shadow: true }
};

function makeObject(kind, cx, cy) {
  const d = JSON.parse(JSON.stringify(OBJECT_KINDS[kind] || OBJECT_KINDS.text));
  delete d.label;
  d.id = uid('o');
  d.x = Math.round(cx - d.w / 2);
  d.y = Math.round(cy - d.h / 2);
  return d;
}

const GAME_DEFAULTS = {
  quiz: {
    bg: '#3a0ca3', bg2: '#7209b7', threshold: 300,
    config: {
      time: 12, points: 100, shuffle: false, questionSize: 76,
      accent: '#ffb703', textColor: '#ffffff', answerBg: 'rgba(255,255,255,0.14)',
      questions: [
        { q: 'Which planet is known as the Red Planet?', answers: ['Venus', 'Mars', 'Jupiter', 'Saturn'], correct: 1 },
        { q: 'How many legs does a spider have?', answers: ['6', '8', '10', '12'], correct: 1 },
        { q: 'What is the largest ocean on Earth?', answers: ['Atlantic', 'Indian', 'Arctic', 'Pacific'], correct: 3 },
        { q: 'Which fruit wears its seeds on the outside?', answers: ['Strawberry', 'Banana', 'Mango', 'Kiwi'], correct: 0 },
        { q: 'Which gas do plants take in from the air?', answers: ['Oxygen', 'Nitrogen', 'Carbon dioxide', 'Helium'], correct: 2 }
      ]
    }
  },
  catch: {
    bg: '#4fc3f7', bg2: '#b3e5fc', threshold: 150,
    config: {
      duration: 30, catcher: '🧺', good: '🍎 🍊 🍌 🍓 🍇', bad: '💣', points: 10, penalty: 20, badChance: 20,
      spawnStart: 1.2, spawnEnd: 3, speedStart: 520, speedEnd: 1100, catcherSize: 230, itemSize: 130,
      bgTop: '#4fc3f7', bgBottom: '#b3e5fc', ground: '#66bb6a'
    }
  },
  slice: {
    bg: '#5d3a1a', bg2: '#2b1708', threshold: 200,
    config: {
      duration: 30, fruits: '🍉 🍍 🍊 🍎 🥝 🍋 🍑', bomb: '💣', points: 10, penalty: 30, bombChance: 15, bombEnds: false,
      waveStart: 1.3, waveEnd: 0.7, itemSize: 170, trail: '#ffffff', bgTop: '#5d3a1a', bgBottom: '#2b1708'
    }
  }
};

GAME_DEFAULTS.wheel = {
  bg: '#3a0ca3', bg2: '#f72585', threshold: 0,
  config: {
    title: 'SPIN & WIN', subtitle: 'Tap the button to win a prize!', buttonText: 'SPIN',
    winText: 'YOU WON!', loseText: 'SO CLOSE!', spinTime: 5, turns: 6, resultTime: 3,
    textColor: '#ffffff', buttonColor: '#ffd166', buttonTextColor: '#3a0ca3',
    pointerColor: '#ffd166', rimColor: '#ffffff', hubColor: '#ffffff',
    segments: [
      { label: '🎁 Gift', color: '#ef476f', textColor: '#ffffff', weight: 1, points: 100, win: true },
      { label: '10% OFF', color: '#ffd166', textColor: '#5a3e00', weight: 3, points: 50, win: true },
      { label: 'Try again', color: '#118ab2', textColor: '#ffffff', weight: 3, points: 0, win: false },
      { label: '☕ Free coffee', color: '#06d6a0', textColor: '#063b2e', weight: 2, points: 80, win: true },
      { label: '5% OFF', color: '#8338ec', textColor: '#ffffff', weight: 3, points: 30, win: true },
      { label: 'Try again', color: '#073b4c', textColor: '#ffffff', weight: 3, points: 0, win: false },
      { label: '🍦 Ice cream', color: '#fb5607', textColor: '#ffffff', weight: 2, points: 80, win: true },
      { label: 'JACKPOT', color: '#ff006e', textColor: '#ffffff', weight: 0.5, points: 500, win: true }
    ]
  }
};

GAME_DEFAULTS.runner = {
  bg: '#5ec8f8', bg2: '#fff1c1', threshold: 150,
  config: {
    duration: 30, points: 10, clearPoints: 5, penalty: 20, crashEnds: false, doubleJump: true, coinChance: 70,
    speedStart: 650, speedEnd: 1200, gapStart: 1.7, gapEnd: 1,
    runner: '🏃', faceRight: true, obstacles: '🌵 🪨 📦', coins: '🪙 ⭐ 💎', runnerSize: 210, itemSize: 150,
    bgTop: '#5ec8f8', bgBottom: '#fff1c1', ground: '#7cb342'
  }
};

GAME_DEFAULTS.memory = {
  bg: '#7209b7', bg2: '#3a0ca3', threshold: 160,
  config: {
    duration: 60, pairs: 8, points: 20, penalty: 0, timeBonus: 5, peek: 2, flipBack: 0.8,
    symbols: '🍎 🍊 🍌 🍓 🍇 🍉 🍒 🥝 🍍 🥥', cardBack: '❓',
    backColor: '#4361ee', faceColor: '#ffffff', matchColor: '#06d6a0', textColor: '#ffffff'
  }
};

GAME_DEFAULTS.whack = {
  bg: '#8bd450', bg2: '#3d8b37', threshold: 150,
  config: {
    duration: 30, points: 10, penalty: 20, bonusPoints: 30, badChance: 15, bonusChance: 8,
    rateStart: 1.2, rateEnd: 2.6, upStart: 1.1, upEnd: 0.55,
    critters: '🐹 🐭 🐰', bad: '💣', bonus: '⭐', hammer: '🔨', itemSize: 190,
    bgTop: '#8bd450', bgBottom: '#3d8b37', holeColor: '#2b1a0e', moundColor: '#8b5a2b'
  }
};

GAME_DEFAULTS.stack = {
  bg: '#0f172a', bg2: '#3a86ff', threshold: 150,
  config: {
    duration: 45, points: 10, perfectBonus: 10, speedStart: 600, speedEnd: 1300, startWidth: 600, blockHeight: 110, missEnds: true,
    colors: '#ff006e #fb5607 #ffbe0b #8ac926 #1982c4 #6a4c93', bgTop: '#0f172a', bgBottom: '#3a86ff'
  }
};

GAME_DEFAULTS.hoop = {
  bg: '#1e1b4b', bg2: '#4338ca', threshold: 100,
  config: {
    duration: 30, points: 20, swishBonus: 10, hoopMoves: true,
    ball: '🏀', ballSize: 170, bgTop: '#1e1b4b', bgBottom: '#4338ca',
    boardColor: '#ffffff', rimColor: '#f97316', netColor: '#ffffff', floor: '#9a3412'
  }
};

GAME_DEFAULTS.scratch = {
  bg: '#a4133c', bg2: '#ff4d6d', threshold: 0,
  config: {
    title: 'SCRATCH & WIN', subtitle: 'Scratch the card to reveal your prize!', foilText: 'SCRATCH HERE',
    winText: 'YOU WON!', loseText: 'NOT THIS TIME', revealAt: 55, brush: 80, resultTime: 3,
    textColor: '#ffffff', foilColor: '#c0c4cc', cardBorder: '#ffd166',
    segments: [
      { label: '🎁 Free gift', color: '#ef476f', textColor: '#ffffff', weight: 1, points: 100, win: true },
      { label: '10% OFF', color: '#ffd166', textColor: '#5a3e00', weight: 3, points: 50, win: true },
      { label: '☕ Free coffee', color: '#06d6a0', textColor: '#063b2e', weight: 2, points: 80, win: true },
      { label: 'Try again', color: '#118ab2', textColor: '#ffffff', weight: 3, points: 0, win: false },
      { label: '🍦 Ice cream', color: '#fb5607', textColor: '#ffffff', weight: 2, points: 80, win: true },
      { label: 'JACKPOT', color: '#8338ec', textColor: '#ffffff', weight: 0.5, points: 500, win: true }
    ]
  }
};

const GAME_INFO = {
  quiz: { name: 'Quiz', icon: '🧠', blurb: 'Questions with timed answers' },
  catch: { name: 'Catch', icon: '🧺', blurb: 'Drag to catch falling items' },
  slice: { name: 'Slice', icon: '🍉', blurb: 'Swipe to slice flying fruit' },
  wheel: { name: 'Spin Wheel', icon: '🎡', blurb: 'Spin the wheel to win a prize' },
  runner: { name: 'Runner', icon: '🏃', blurb: 'Tap to jump obstacles and grab coins' },
  memory: { name: 'Memory Match', icon: '🃏', blurb: 'Flip the cards to find matching pairs' },
  whack: { name: 'Whack Attack', icon: '🔨', blurb: 'Tap the critters as they pop up' },
  stack: { name: 'Tower Stack', icon: '🧱', blurb: 'Tap to drop blocks and build high' },
  hoop: { name: 'Hoop Shot', icon: '🏀', blurb: 'Swipe up to shoot baskets' },
  scratch: { name: 'Scratch & Win', icon: '🎟️', blurb: 'Scratch the card to reveal a prize' }
};

/* Games that end in a prize from a list (cfg.segments) rather than a score. */
const PRIZE_GAMES = ['wheel', 'scratch'];

const WHEEL_COLORS = ['#ef476f', '#ffd166', '#06d6a0', '#118ab2', '#8338ec', '#fb5607', '#ff006e', '#3a86ff', '#073b4c', '#80b918'];

function makeScreen(kind, name) {
  if (GAME_DEFAULTS[kind]) {
    const d = JSON.parse(JSON.stringify(GAME_DEFAULTS[kind]));
    return {
      id: uid('s'), name: name || GAME_INFO[kind].name + ' Game', type: 'game', bg: d.bg, bg2: d.bg2, objects: [],
      timer: { seconds: 0, target: '' },
      game: { kind: kind, threshold: d.threshold, win: '', lose: '', config: d.config }
    };
  }
  return { id: uid('s'), name: name || 'New Screen', type: 'page', bg: '#22223b', bg2: '#4a4e69', objects: [], timer: { seconds: 0, target: '' } };
}

/* Small helpers to keep the starter project readable. */
function T(text, y, size, extra) {
  return Object.assign({ id: uid('o'), type: 'text', x: 60, y: y, w: 960, h: Math.round(size * 1.6), text: text, fontSize: size, color: '#ffffff', bold: true, align: 'center', font: 'rounded' }, extra || {});
}
function B(text, y, target, extra) {
  return Object.assign({ id: uid('o'), type: 'button', x: 190, y: y, w: 700, h: 180, text: text, fontSize: 60, color: '#ffffff', bg: '#ff7a1a', radius: 90, bold: true, font: 'rounded', action: { type: 'goto', target: target } }, extra || {});
}
function E(emoji, y, size, extra) {
  return Object.assign({ id: uid('o'), type: 'emoji', x: Math.round(540 - size / 2), y: y, w: size, h: size, emoji: emoji }, extra || {});
}
function C(x, y, size, bg, opacity) {
  return { id: uid('o'), type: 'shape', shape: 'circle', x: x, y: y, w: size, h: size, bg: bg, opacity: opacity };
}

/* Give an object an entrance effect (and optionally a looping effect). */
function A(o, enter, delay, dur, loop) {
  o.enter = enter;
  o.enterDelay = delay;
  o.enterDuration = dur;
  if (loop) o.anim = loop;
  return o;
}

/* ---------- Sample logo: an original placeholder brand mark. Replace it with your own. ---------- */
const SAMPLE_LOGO_SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">' +
  '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#ffd166"/><stop offset=".5" stop-color="#ff5d8f"/><stop offset="1" stop-color="#9b5de5"/></linearGradient>' +
  '<linearGradient id="w" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ffffff"/><stop offset="1" stop-color="#ffe3f0"/></linearGradient></defs>' +
  '<circle cx="256" cy="256" r="244" fill="url(#g)"/>' +
  '<circle cx="256" cy="256" r="230" fill="none" stroke="#ffffff" stroke-opacity=".55" stroke-width="8"/>' +
  '<circle cx="256" cy="256" r="196" fill="none" stroke="#ffffff" stroke-opacity=".35" stroke-width="4" stroke-dasharray="4 14" stroke-linecap="round"/>' +
  '<path d="M256 110 L286.6 185.9 L368.2 191.5 L305.5 244.1 L325.4 323.5 L256 280 L186.6 323.5 L206.5 244.1 L143.8 191.5 L225.4 185.9 Z" fill="url(#w)" stroke="url(#w)" stroke-width="18" stroke-linejoin="round"/>' +
  '<path d="M128 112 l8 22 22 8 -22 8 -8 22 -8 -22 -22 -8 22 -8z" fill="#ffffff"/>' +
  '<path d="M392 92 l6 16 16 6 -16 6 -6 16 -6 -16 -16 -6 16 -6z" fill="#ffffff" fill-opacity=".85"/>' +
  '<rect x="88" y="338" width="336" height="84" rx="42" fill="#ffffff"/>' +
  '<text x="256" y="393" text-anchor="middle" font-family="Arial Rounded MT Bold, Nunito, Arial, sans-serif" font-size="46" font-weight="900" fill="#9b2fd1" letter-spacing="3">PLAY ZONE</text>' +
  '</svg>';
function sampleLogoAsset() {
  return { id: 'a_logo', name: 'Sample logo.svg', src: 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(SAMPLE_LOGO_SVG), w: 512, h: 512 };
}

/* ---------- Animated sample screens ---------- */
function welcomeScreen(next) {
  return {
    id: uid('s'), name: 'Welcome', type: 'page', bg: '#1b1446', bg2: '#5b21b6', effect: 'aurora', transition: 'fade',
    timer: { seconds: 0, target: '' },
    objects: [
      A(C(220, 250, 640, '#ffffff', 0.08), 'zoom', 0, 1.2, 'breathe'),
      A({ id: uid('o'), type: 'image', x: 290, y: 320, w: 500, h: 500, src: 'asset:a_logo', fit: 'contain' }, 'pop', 0.2, 0.9, 'float'),
      A(T('WELCOME!', 900, 140, { shadow: true }), 'letters', 0.9, 0.7),
      A(T('Play fun games and win amazing prizes', 1120, 52, { bold: false, color: 'rgba(255,255,255,0.88)', x: 90, w: 900, h: 150 }), 'typewriter', 1.9, 1.6),
      A(E('🎮', 1295, 130, { x: 290 }), 'bounce', 3.3, 0.9, 'float'),
      A(E('🏆', 1295, 130), 'bounce', 3.45, 0.9, 'float'),
      A(E('🎁', 1295, 130, { x: 660 }), 'bounce', 3.6, 0.9, 'float'),
      A(B('TAP TO START', 1480, next || '', { bg: '#ffd166', color: '#3a0ca3', fontSize: 66, h: 190 }), 'up', 4.0, 0.7, 'shine'),
      A(T('Tap the button to begin', 1720, 36, { bold: false, color: 'rgba(255,255,255,0.6)' }), 'fade', 4.6, 0.8, 'blink')
    ]
  };
}

const START_INFO = {
  quiz: { title: 'QUIZ CHALLENGE', emoji: '🧠', bg: '#3a0ca3', bg2: '#7209b7', effect: 'stars',
    steps: ['Answer 5 quick questions', 'Tap the right answer before time runs out', 'Score 300 or more to win'] },
  catch: { title: 'FRUIT CATCH', emoji: '🧺', bg: '#0077b6', bg2: '#48cae4', effect: 'bubbles',
    steps: ['Drag the basket left and right', 'Catch fruit for +10 points', 'Avoid the bombs!'] },
  slice: { title: 'FRUIT SLICE', emoji: '🍉', bg: '#9d0208', bg2: '#f48c06', effect: 'rays',
    steps: ['Swipe across fruit to slice it', 'Slice 3 at once for a combo bonus', "Don't slice the bombs!"] },
  wheel: { title: 'SPIN & WIN', emoji: '🎡', bg: '#7209b7', bg2: '#f72585', effect: 'sparkles',
    steps: ['Tap SPIN to turn the wheel', 'Wherever it stops is your prize', 'One spin per player'] },
  runner: { title: 'COIN RUNNER', emoji: '🏃', bg: '#0096c7', bg2: '#80ed99', effect: 'bubbles',
    steps: ['Tap anywhere to jump', 'Tap again in the air to double jump', 'Grab coins and dodge the obstacles!'] },
  memory: { title: 'MEMORY MATCH', emoji: '🃏', bg: '#7209b7', bg2: '#4361ee', effect: 'sparkles',
    steps: ['Remember where the pictures are', 'Tap two cards to find a pair', 'Match them all before time runs out!'] },
  whack: { title: 'WHACK ATTACK', emoji: '🔨', bg: '#2d6a4f', bg2: '#95d5b2', effect: 'rays',
    steps: ['Critters pop out of the holes', 'Tap them before they hide', 'Leave the bombs alone!'] },
  stack: { title: 'TOWER STACK', emoji: '🧱', bg: '#14213d', bg2: '#3a86ff', effect: 'stars',
    steps: ['A block slides across the top', 'Tap to drop it on the tower', 'Line it up perfectly for a bonus!'] },
  hoop: { title: 'HOOP SHOT', emoji: '🏀', bg: '#ea580c', bg2: '#7c2d12', effect: 'rays',
    steps: ['Swipe up to throw the ball', 'Sink as many baskets as you can', 'Miss the rim for a bonus!'] },
  scratch: { title: 'SCRATCH & WIN', emoji: '🎟️', bg: '#a4133c', bg2: '#ff4d6d', effect: 'sparkles',
    steps: ['Rub the silver foil with your finger', 'Your prize appears underneath', 'One card per player'] }
};

/* The "how to play" screen shown before a game, with a PLAY button that opens it. */
function gameStartScreen(kind, target) {
  const g = START_INFO[kind];
  const objs = [
    A(B('‹  Back', 70, '', { x: 50, w: 250, h: 110, fontSize: 40, bg: 'rgba(255,255,255,0.16)', shadow: false, action: { type: 'back', target: '' } }), 'fade', 0.2, 0.5),
    A(C(290, 200, 500, '#ffffff', 0.12), 'zoom', 0, 1, 'breathe'),
    A(E(g.emoji, 250, 400), 'bounce', 0.2, 1.1, 'float'),
    A(T(g.title, 730, 112, { shadow: true }), 'letters', 0.8, 0.6),
    A(T('HOW TO PLAY', 930, 40, { color: '#ffd166', h: 64 }), 'fade', 1.5, 0.6)
  ];
  g.steps.forEach(function (s, j) {
    const y = 1020 + j * 150;
    objs.push(A({ id: uid('o'), type: 'shape', shape: 'rect', x: 110, y: y, w: 860, h: 124, bg: '#ffffff', radius: 62, opacity: 0.14 }, 'left', 1.7 + j * 0.15, 0.6));
    objs.push(A(T((j + 1) + '.   ' + s, y, 42, { x: 160, w: 780, h: 124, align: 'left', bold: false, font: 'sans' }), 'left', 1.7 + j * 0.15, 0.6));
  });
  objs.push(A(B(kind === 'wheel' ? '▶  LET\'S SPIN' : kind === 'scratch' ? '▶  GET MY CARD' : '▶  PLAY', 1540, target || '', { bg: '#ffd166', color: '#3a0ca3', fontSize: 72, h: 190 }), 'pop', 2.4, 0.8, 'shine'));
  return {
    id: uid('s'), name: GAME_INFO[kind].name + ' · Start', type: 'page', bg: g.bg, bg2: g.bg2, effect: g.effect, transition: 'slide',
    timer: { seconds: 0, target: '' }, objects: objs
  };
}

function winnerScreen(claimTarget, againTarget) {
  return {
    id: uid('s'), name: 'You Win', type: 'page', bg: '#06d6a0', bg2: '#118ab2', effect: 'confetti', transition: 'zoom',
    timer: { seconds: 0, target: '' },
    objects: [
      A(C(290, 250, 500, '#ffffff', 0.15), 'zoom', 0, 0.8, 'breathe'),
      A(E('🏆', 300, 480), 'bounce', 0.1, 1.1, 'tada'),
      A(T('YOU WIN!', 880, 140, { shadow: true }), 'pop', 1.0, 0.7),
      A(T('Your score: {{score}}', 1100, 64, { color: '#ffe14d' }), 'up', 1.5, 0.6),
      A(B('Claim your prize', 1360, claimTarget || '', { bg: '#ffd166', color: '#073b4c' }), 'up', 1.9, 0.6, 'shine'),
      A(B('Play again', 1600, againTarget || '', { x: 270, w: 540, h: 140, fontSize: 48, bg: 'rgba(255,255,255,0.2)', shadow: false }), 'fade', 2.3, 0.6)
    ]
  };
}

/* Ready-made animated screens, offered in the template marketplace's Screens
   section. `group` files them (welcome | howto | result); `kind` = the game a
   how-to-play screen opens. */
const SCREEN_TEMPLATES = [
  { name: 'Welcome', icon: '✨', group: 'welcome', blurb: 'Logo, animated title, start button', make: function () { return welcomeScreen(''); } },
  { name: 'Quiz start', icon: '🧠', blurb: 'How to play + PLAY button', group: 'howto', kind: 'quiz', make: function () { return gameStartScreen('quiz'); } },
  { name: 'Catch start', icon: '🧺', blurb: 'How to play + PLAY button', group: 'howto', kind: 'catch', make: function () { return gameStartScreen('catch'); } },
  { name: 'Slice start', icon: '🍉', blurb: 'How to play + PLAY button', group: 'howto', kind: 'slice', make: function () { return gameStartScreen('slice'); } },
  { name: 'Wheel start', icon: '🎡', blurb: 'How to play + SPIN button', group: 'howto', kind: 'wheel', make: function () { return gameStartScreen('wheel'); } },
  { name: 'Runner start', icon: '🏃', blurb: 'How to play + PLAY button', group: 'howto', kind: 'runner', make: function () { return gameStartScreen('runner'); } },
  { name: 'Memory start', icon: '🃏', blurb: 'How to play + PLAY button', group: 'howto', kind: 'memory', make: function () { return gameStartScreen('memory'); } },
  { name: 'Whack start', icon: '🔨', blurb: 'How to play + PLAY button', group: 'howto', kind: 'whack', make: function () { return gameStartScreen('whack'); } },
  { name: 'Stack start', icon: '🧱', blurb: 'How to play + PLAY button', group: 'howto', kind: 'stack', make: function () { return gameStartScreen('stack'); } },
  { name: 'Hoop start', icon: '🏀', blurb: 'How to play + PLAY button', group: 'howto', kind: 'hoop', make: function () { return gameStartScreen('hoop'); } },
  { name: 'Scratch start', icon: '🎟️', blurb: 'How it works + card button', group: 'howto', kind: 'scratch', make: function () { return gameStartScreen('scratch'); } },
  { name: 'Winner', icon: '🏆', group: 'result', blurb: 'Trophy, score and confetti', make: function () { return winnerScreen('', ''); } }
];

function demoProject() {
  const welcome = welcomeScreen('s_menu');
  welcome.id = 's_attract';

  const menu = {
    id: 's_menu', name: 'Choose a Game', type: 'page', bg: '#22223b', bg2: '#4a4e69', effect: 'stars', transition: 'slide',
    timer: { seconds: 0, target: '' },
    objects: [
      A(T('Choose a game', 200, 104), 'down', 0.1, 0.6),
      A(T('Score high to win a prize!', 370, 50, { bold: false, color: 'rgba(255,255,255,0.75)' }), 'fade', 0.5, 0.6),
      A(B('🧠  Quiz Challenge', 560, 's_start_quiz', { x: 140, w: 800, h: 200, bg: '#7209b7' }), 'left', 0.5, 0.6),
      A(B('🧺  Fruit Catch', 800, 's_start_catch', { x: 140, w: 800, h: 200, bg: '#06d6a0', color: '#073b4c' }), 'left', 0.62, 0.6),
      A(B('🍉  Fruit Slice', 1040, 's_start_slice', { x: 140, w: 800, h: 200, bg: '#ef476f' }), 'left', 0.74, 0.6),
      A(B('🎡  Spin & Win', 1280, 's_start_wheel', { x: 140, w: 800, h: 200, bg: '#ffd166', color: '#3a0ca3' }), 'left', 0.86, 0.6, 'shine'),
      A(B('‹  Back', 1640, '', { x: 360, w: 360, h: 120, fontSize: 44, bg: 'rgba(255,255,255,0.14)', shadow: false, action: { type: 'back', target: '' } }), 'fade', 1.2, 0.5)
    ]
  };

  const games = {};
  const starts = {};
  ['quiz', 'catch', 'slice', 'wheel'].forEach(function (kind) {
    const g = makeScreen(kind, { quiz: 'Quiz Challenge', catch: 'Fruit Catch', slice: 'Fruit Slice', wheel: 'Spin & Win' }[kind]);
    g.id = 's_' + kind;
    g.game.win = kind === 'wheel' ? 's_prize' : 's_win';
    g.game.lose = 's_lose';
    if (kind === 'quiz') g.effect = 'stars';
    if (kind === 'wheel') g.effect = 'sparkles';
    games[kind] = g;
    const s = gameStartScreen(kind, g.id);
    s.id = 's_start_' + kind;
    starts[kind] = s;
  });

  const prize = {
    id: 's_prize', name: 'You Won a Prize', type: 'page', bg: '#f72585', bg2: '#ffb703', effect: 'confetti', transition: 'zoom',
    timer: { seconds: 0, target: '' },
    objects: [
      A(C(560, -240, 760, '#ffffff', 0.1), 'zoom', 0, 1, 'breathe'),
      A(E('🎉', 300, 480), 'pop', 0.1, 0.8, 'tada'),
      A(T('YOU WON', 860, 80, { bold: false, color: 'rgba(255,255,255,0.9)' }), 'fade', 0.8, 0.6),
      A(T('{{prize}}', 980, 150, { shadow: true, h: 240 }), 'zoom', 1.1, 0.7, 'glow'),
      A(B('Claim your prize', 1360, 's_form', { bg: '#3a0ca3' }), 'up', 1.7, 0.6, 'shine'),
      A(B('Play again', 1600, 's_menu', { x: 270, w: 540, h: 140, fontSize: 48, bg: 'rgba(255,255,255,0.22)', shadow: false }), 'fade', 2.1, 0.6)
    ]
  };

  const win = winnerScreen('s_form', 's_menu');
  win.id = 's_win';

  const lose = {
    id: 's_lose', name: 'Try Again', type: 'page', bg: '#ef476f', bg2: '#ff9f1c', effect: 'rays', transition: 'fade',
    timer: { seconds: 25, target: 's_attract' },
    objects: [
      A(E('😅', 300, 480), 'jelly', 0.1, 0.9, 'wiggle'),
      A(T('SO CLOSE!', 880, 140, { shadow: true }), 'zoom', 0.6, 0.6),
      A(T('Your score: {{score}}', 1090, 64), 'up', 1.0, 0.6),
      A(B('Try again', 1360, 's_menu', { bg: '#ffffff', color: '#ef476f' }), 'up', 1.4, 0.6, 'pulse'),
      A(B('Finish', 1600, 's_attract', { x: 270, w: 540, h: 140, fontSize: 48, bg: 'rgba(255,255,255,0.2)', shadow: false }), 'fade', 1.8, 0.6)
    ]
  };

  const form = {
    id: 's_form', name: 'Your Details', type: 'page', bg: '#22223b', bg2: '#4a4e69', effect: 'stars', transition: 'slide',
    timer: { seconds: 0, target: '' },
    objects: [
      A(T('Claim your prize', 220, 96), 'down', 0.1, 0.6),
      A(T('Enter your details below', 380, 48, { bold: false, color: 'rgba(255,255,255,0.75)' }), 'fade', 0.5, 0.6),
      A({ id: uid('o'), type: 'input', x: 110, y: 580, w: 860, h: 150, placeholder: 'Your name', field: 'name', inputType: 'text', fontSize: 48, required: true, radius: 28 }, 'up', 0.7, 0.6),
      A({ id: uid('o'), type: 'input', x: 110, y: 780, w: 860, h: 150, placeholder: 'Email address', field: 'email', inputType: 'email', fontSize: 48, required: true, radius: 28 }, 'up', 0.85, 0.6),
      A(T('By submitting you agree to be contacted about this promotion.', 980, 34, { bold: false, color: 'rgba(255,255,255,0.6)', x: 140, w: 800, h: 110 }), 'fade', 1.1, 0.6),
      A(B('Submit', 1180, 's_thanks', { bg: '#06d6a0', color: '#073b4c', action: { type: 'submit', target: 's_thanks' } }), 'pop', 1.3, 0.7, 'shine'),
      A(B('Skip', 1420, 's_attract', { x: 360, w: 360, h: 120, fontSize: 44, bg: 'rgba(255,255,255,0.14)', shadow: false }), 'fade', 1.6, 0.5)
    ]
  };

  const thanks = {
    id: 's_thanks', name: 'Thank You', type: 'page', bg: '#4361ee', bg2: '#4cc9f0', effect: 'sparkles', transition: 'zoom',
    timer: { seconds: 10, target: 's_attract' },
    objects: [
      A(E('🎁', 320, 480), 'bounce', 0.1, 1.1, 'float'),
      A(T('Thanks, {{name}}!', 900, 110, { shadow: true }), 'letters', 0.9, 0.6),
      A(T('Show this screen at the counter to collect your prize.', 1100, 54, { bold: false, h: 200 }), 'up', 1.6, 0.6),
      A(T('Returning to start in a few seconds…', 1640, 36, { bold: false, color: 'rgba(255,255,255,0.6)' }), 'fade', 2.2, 0.6, 'blink')
    ]
  };

  /* [screen, flow-map x, flow-map y] */
  const layout = [
    [welcome, 40, 380], [menu, 330, 340],
    [starts.quiz, 630, 0], [starts.catch, 630, 340], [starts.slice, 630, 680], [starts.wheel, 630, 1020],
    [games.quiz, 930, 0], [games.catch, 930, 340], [games.slice, 930, 680], [games.wheel, 930, 1020],
    [win, 1230, 60], [lose, 1230, 520], [prize, 1230, 980], [form, 1530, 200], [thanks, 1830, 260]
  ];
  return {
    id: uid('p'), name: 'Play & Win Kiosk', version: 1,
    width: 1080, height: 1920, startScreen: 's_attract', idleTimeout: 60,
    vars: { score: 0 }, assets: [sampleLogoAsset()],
    screens: layout.map(function (l) { l[0].fx = l[1]; l[0].fy = l[2]; return l[0]; })
  };
}

function blankProject() {
  const s = makeScreen('page', 'Start');
  s.fx = 80; s.fy = 120;
  s.objects.push(T('Hello kiosk!', 860, 110));
  return { id: uid('p'), name: 'Untitled Kiosk', version: 1, width: 1080, height: 1920, startScreen: s.id, idleTimeout: 60, vars: { score: 0 }, assets: [], screens: [s] };
}

const EMOJI_LIBRARY = '🎮 🏆 🎁 🎉 ⭐ 🌟 💎 🔥 ⚡ ❤️ 👍 😀 😎 🤩 😅 🥳 🧠 🧺 🍉 🍎 🍊 🍌 🍓 🍇 🍍 🥝 🍋 🍑 🍒 🥥 💣 🪙 💰 🎯 🎈 🚀 ⏱️ 📱 📍 ✅ ❌ ❓'.split(' ');
