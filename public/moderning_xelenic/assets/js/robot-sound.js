(() => {
  'use strict';

  // The robot's voice: original sounds synthesized live with the Web Audio
  // API (no audio files) — chirps, beep-boops and warbly "hmm?"s in the
  // spirit of a small, curious robot, whooshes for its flights and a servo
  // whir that follows how fast it turns. three-robot.js calls in when things
  // happen (window.XelenicSound).
  //
  // Browsers only allow sound once the visitor has interacted with the page,
  // so nothing plays until the first click, tap or key press — and the robot
  // says hello then. The speaker button mutes it, and that's remembered.

  const STORAGE_KEY = 'xelenic-robot-sound';
  const button = document.querySelector('.sound-toggle');
  let enabled = readPref() !== 'off';
  let ctx = null;
  let master = null;
  let servo = null;
  let noiseBuffer = null;
  const lastPlayed = {};

  function readPref() {
    try { return localStorage.getItem(STORAGE_KEY); } catch (err) { return null; }
  }
  function writePref(value) {
    try { localStorage.setItem(STORAGE_KEY, value); } catch (err) { /* private mode */ }
  }

  // ---- Audio graph -------------------------------------------------------

  function unlock(event) {
    if (ctx) {
      if (ctx.state === 'suspended') ctx.resume();
      return;
    }
    const AudioCtx = window.AudioContext || window.webkitAudioContext;
    if (!AudioCtx) return;
    ctx = new AudioCtx();
    // Everything passes through a compressor so overlapping sounds never clip.
    const limiter = ctx.createDynamicsCompressor();
    limiter.threshold.value = -14;
    limiter.ratio.value = 6;
    master = ctx.createGain();
    master.gain.value = enabled ? 0.7 : 0;
    master.connect(limiter);
    limiter.connect(ctx.destination);
    buildServo();
    // Say hello — unless this very click is the one muting it.
    const onToggle = event && button && button.contains(event.target);
    if (enabled && !onToggle) play('hello');
  }
  ['pointerdown', 'keydown', 'touchend'].forEach((type) => {
    window.addEventListener(type, unlock, { passive: true });
  });
  document.addEventListener('visibilitychange', () => {
    if (!ctx) return;
    if (document.hidden) ctx.suspend();
    else ctx.resume();
  });

  // One "syllable": an oscillator gliding through a list of pitches (Hz)
  // over its duration, with optional vibrato, a soft attack/decay and a
  // low-pass filter so it stays cute rather than harsh.
  function tone({ at = 0, dur = 0.12, freqs, type = 'sine', gain = 0.2, vibrato = 0, rate = 22, cutoff = 3200 }) {
    const t0 = ctx.currentTime + at;
    const osc = ctx.createOscillator();
    osc.type = type;
    osc.frequency.setValueAtTime(freqs[0], t0);
    for (let i = 1; i < freqs.length; i++) {
      osc.frequency.exponentialRampToValueAtTime(freqs[i], t0 + (dur * i) / (freqs.length - 1));
    }
    if (vibrato) {
      const lfo = ctx.createOscillator();
      const depth = ctx.createGain();
      lfo.frequency.value = rate;
      depth.gain.value = vibrato;
      lfo.connect(depth);
      depth.connect(osc.frequency);
      lfo.start(t0);
      lfo.stop(t0 + dur + 0.05);
    }
    const filter = ctx.createBiquadFilter();
    filter.type = 'lowpass';
    filter.frequency.value = cutoff;
    const env = ctx.createGain();
    env.gain.setValueAtTime(0.0001, t0);
    env.gain.exponentialRampToValueAtTime(gain, t0 + Math.min(0.02, dur * 0.25));
    env.gain.exponentialRampToValueAtTime(0.0001, t0 + dur);
    osc.connect(filter);
    filter.connect(env);
    env.connect(master);
    osc.start(t0);
    osc.stop(t0 + dur + 0.05);
  }

  // A burst of filtered noise sweeping between two frequencies — a whoosh.
  function whoosh({ at = 0, dur = 0.5, from = 400, to = 2400, gain = 0.12 }) {
    if (!noiseBuffer) {
      noiseBuffer = ctx.createBuffer(1, ctx.sampleRate, ctx.sampleRate);
      const data = noiseBuffer.getChannelData(0);
      for (let i = 0; i < data.length; i++) data[i] = Math.random() * 2 - 1;
    }
    const t0 = ctx.currentTime + at;
    const src = ctx.createBufferSource();
    src.buffer = noiseBuffer;
    const band = ctx.createBiquadFilter();
    band.type = 'bandpass';
    band.Q.value = 1.4;
    band.frequency.setValueAtTime(from, t0);
    band.frequency.exponentialRampToValueAtTime(to, t0 + dur);
    const env = ctx.createGain();
    env.gain.setValueAtTime(0.0001, t0);
    env.gain.exponentialRampToValueAtTime(gain, t0 + dur * 0.35);
    env.gain.exponentialRampToValueAtTime(0.0001, t0 + dur);
    src.connect(band);
    band.connect(env);
    env.connect(master);
    src.start(t0);
    src.stop(t0 + dur + 0.05);
  }

  // A soft, continuous servo whir whose pitch and loudness follow how fast
  // the robot is turning (silent when it's still).
  function buildServo() {
    const osc = ctx.createOscillator();
    osc.type = 'sawtooth';
    osc.frequency.value = 160;
    const band = ctx.createBiquadFilter();
    band.type = 'bandpass';
    band.Q.value = 5;
    band.frequency.value = 420;
    const gain = ctx.createGain();
    gain.gain.value = 0;
    osc.connect(band);
    band.connect(gain);
    gain.connect(master);
    osc.start();
    servo = { osc, band, gain };
  }

  // ---- The robot's sounds --------------------------------------------------

  const VOICES = {
    // "Wa-ahh?" — a friendly, rising hello.
    hello: () => {
      tone({ dur: 0.24, freqs: [520, 900, 720], type: 'triangle', vibrato: 18, rate: 18, gain: 0.22 });
      tone({ at: 0.27, dur: 0.16, freqs: [700, 1150], type: 'triangle', vibrato: 30, gain: 0.18 });
    },
    // Lifting off: a whoosh under a rising "wheee".
    takeoff: () => {
      whoosh({ dur: 0.55, from: 380, to: 2200, gain: 0.1 });
      tone({ dur: 0.38, freqs: [420, 1250], type: 'sine', vibrato: 14, rate: 14, gain: 0.16 });
    },
    // Touching down: a soft thud, then "beep-boop".
    land: () => {
      tone({ dur: 0.13, freqs: [260, 120], type: 'sine', gain: 0.22, cutoff: 600 });
      tone({ at: 0.13, dur: 0.07, freqs: [880], type: 'square', gain: 0.07, cutoff: 1800 });
      tone({ at: 0.22, dur: 0.1, freqs: [620], type: 'square', gain: 0.07, cutoff: 1600 });
    },
    // Discover: a curious "hmm?"
    curious: () => {
      tone({ dur: 0.32, freqs: [480, 720, 1000], type: 'triangle', vibrato: 26, rate: 24, gain: 0.2 });
    },
    // Strategize: a thoughtful "hmm-mm".
    think: () => {
      tone({ dur: 0.18, freqs: [620, 560], type: 'triangle', gain: 0.17 });
      tone({ at: 0.22, dur: 0.24, freqs: [560, 610, 520], type: 'triangle', vibrato: 10, rate: 9, gain: 0.17 });
    },
    // Build: a busy "beep-beep".
    work: () => {
      tone({ dur: 0.06, freqs: [920], type: 'square', gain: 0.08, cutoff: 2200 });
      tone({ at: 0.1, dur: 0.06, freqs: [920], type: 'square', gain: 0.08, cutoff: 2200 });
    },
    // A light metallic clank, for each hammer blow while building.
    clank: () => {
      tone({ dur: 0.09, freqs: [1250, 1180], type: 'square', gain: 0.06, cutoff: 2600 });
      tone({ dur: 0.06, freqs: [2500], type: 'sine', gain: 0.05 });
    },
    // Deploy: a rising rocket rumble.
    launch: () => {
      whoosh({ dur: 0.7, from: 200, to: 3000, gain: 0.1 });
      tone({ dur: 0.6, freqs: [280, 1500], type: 'sawtooth', vibrato: 6, rate: 7, gain: 0.08, cutoff: 1400 });
    },
    // Grow: a happy "ta-da!" arpeggio.
    celebrate: () => {
      [660, 880, 1100, 1320].forEach((f, i) => tone({ at: i * 0.08, dur: 0.1, freqs: [f], type: 'triangle', gain: 0.15 }));
      tone({ at: 0.34, dur: 0.24, freqs: [1320, 1760], type: 'triangle', vibrato: 30, gain: 0.15 });
    },
    // A springy little "boing" for each joyful hop.
    boing: () => {
      tone({ dur: 0.14, freqs: [300, 620, 320], type: 'sine', gain: 0.09 });
    },
    // A soft "tip" for each step along the road.
    step: () => {
      tone({ dur: 0.05, freqs: [190, 120], type: 'sine', gain: 0.07, cutoff: 500 });
    },
    // The orbit bursting open around it.
    powerup: () => {
      tone({ dur: 0.5, freqs: [300, 900], type: 'triangle', vibrato: 8, rate: 8, gain: 0.13 });
      tone({ at: 0.3, dur: 0.18, freqs: [1800], type: 'sine', gain: 0.06 });
      tone({ at: 0.42, dur: 0.22, freqs: [2400], type: 'sine', gain: 0.05 });
    },
  };

  // How soon the same sound may repeat (ms), so scrolling back and forth
  // over a boundary doesn't turn into a stutter.
  const SPACING = { takeoff: 700, land: 700, step: 110, clank: 120, boing: 160 };

  function play(name) {
    if (!ctx || !enabled || ctx.state !== 'running' || !VOICES[name]) return;
    const now = performance.now();
    if (now - (lastPlayed[name] || 0) < (SPACING[name] || 300)) return;
    lastPlayed[name] = now;
    VOICES[name]();
  }

  // speed: how fast the robot is turning, in radians per second.
  function setServo(speed) {
    if (!servo || !enabled) return;
    const s = Math.max(0, speed - 0.25); // ignore the gentle idle motion
    const t = ctx.currentTime;
    servo.osc.frequency.setTargetAtTime(150 + Math.min(s, 6) * 70, t, 0.05);
    servo.band.frequency.setTargetAtTime(380 + Math.min(s, 6) * 160, t, 0.05);
    servo.gain.gain.setTargetAtTime(Math.min(0.05, s * 0.02), t, 0.06);
  }

  // ---- Speaker button ------------------------------------------------------

  function reflect() {
    if (!button) return;
    button.setAttribute('aria-pressed', String(enabled));
    button.classList.toggle('is-muted', !enabled);
    button.setAttribute('title', enabled ? 'Mute robot sounds' : 'Turn robot sounds on');
  }
  if (button) {
    reflect();
    button.addEventListener('click', () => {
      enabled = !enabled;
      writePref(enabled ? 'on' : 'off');
      reflect();
      unlock();
      if (master) master.gain.setTargetAtTime(enabled ? 0.7 : 0, ctx.currentTime, 0.03);
      if (servo && !enabled) servo.gain.gain.setTargetAtTime(0, ctx.currentTime, 0.03);
      if (enabled) play('hello');
    });
  }

  window.XelenicSound = { play, servo: setServo };
})();
