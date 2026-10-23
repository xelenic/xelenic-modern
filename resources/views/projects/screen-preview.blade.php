{{-- A to-scale drawing of a kiosk screen with its measurements, from $preview
     (screen_size, orientation, resolution, webgl, game_board, payments). On
     the create form, resources/js/kiosk-settings.js redraws it live. --}}
@php
    $dimensions = \App\Support\KioskSettings::dimensions($preview['screen_size'], $preview['orientation'], $preview['resolution']);
    $scale = \App\Support\KioskSettings::PREVIEW_PX_PER_INCH;
    $chips = [
        'webgl' => ['cube', 'WebGL'],
        'game_board' => ['gamepad', 'Game board'],
        'payments' => ['card', 'Payments'],
    ];
@endphp

<div data-kiosk-preview data-px-per-inch="{{ $scale }}">
    <div class="relative flex h-64 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50"
        style="background-image: radial-gradient(circle, rgb(148 163 184 / 0.35) 1px, transparent 1px); background-size: 16px 16px;">
        <div class="absolute top-3 left-3 flex flex-wrap gap-1.5">
            @foreach ($chips as $setting => [$icon, $label])
                <span data-chip="{{ $setting }}" class="inline-flex items-center gap-1 rounded-full bg-white px-2 py-0.5 text-[11px] font-medium text-blue-700 shadow-xs ring-1 ring-blue-100" @unless ($preview[$setting]) hidden @endunless>
                    <x-icon :name="$icon" class="size-3" />
                    {{ $label }}
                </span>
            @endforeach
        </div>

        <div class="relative">
            <div
                data-preview-screen
                data-orientation="{{ $preview['orientation'] }}"
                @class([
                    'group relative overflow-hidden rounded-md border-[3px] border-slate-800 bg-blue-600 shadow-xl shadow-blue-900/20 transition-all duration-300',
                    'kiosk-webgl' => $preview['webgl'],
                ])
                style="width: {{ round($dimensions['width_in'] * $scale, 1) }}px; height: {{ round($dimensions['height_in'] * $scale, 1) }}px;"
            >
                {{-- Sample screen: a header, a grid of tiles and a button --}}
                <div aria-hidden="true" class="absolute inset-[8%] flex flex-col gap-[6%]">
                    <div class="h-[7%] w-1/2 rounded-full bg-white/70"></div>
                    <div class="grid flex-1 grid-cols-2 gap-[6%] group-data-[orientation=landscape]:grid-cols-3">
                        @for ($tile = 0; $tile < 6; $tile++)
                            <div class="rounded-[3px] bg-white/85"></div>
                        @endfor
                    </div>
                    <div class="h-[9%] rounded-full bg-sky-400"></div>
                </div>
            </div>

            {{-- Width and height dimension lines --}}
            <div aria-hidden="true" class="absolute inset-x-0 top-full mt-2 flex items-center">
                <span class="h-2 w-px bg-slate-400"></span>
                <span class="h-px flex-1 bg-slate-300"></span>
                <span class="h-2 w-px bg-slate-400"></span>
            </div>
            <span data-preview-width class="absolute top-full left-1/2 mt-4 -translate-x-1/2 text-[11px] font-medium whitespace-nowrap text-slate-500">{{ $dimensions['width_cm'] }} cm</span>

            <div aria-hidden="true" class="absolute inset-y-0 left-full ml-2 flex flex-col items-center">
                <span class="h-px w-2 bg-slate-400"></span>
                <span class="w-px flex-1 bg-slate-300"></span>
                <span class="h-px w-2 bg-slate-400"></span>
            </div>
            <span data-preview-height class="absolute top-1/2 left-full ml-4 -translate-y-1/2 text-[11px] font-medium whitespace-nowrap text-slate-500">{{ $dimensions['height_cm'] }} cm</span>
        </div>
    </div>

    <dl class="mt-4 grid grid-cols-3 gap-2 text-center">
        <div class="rounded-lg bg-slate-50 px-2 py-2.5">
            <dt class="text-[11px] font-medium tracking-wider text-slate-500 uppercase">Screen</dt>
            <dd class="mt-1 text-sm font-semibold text-slate-900">
                <span data-preview-diagonal>{{ $preview['screen_size'] }}″</span>
                <span data-preview-orientation class="block text-xs font-normal text-slate-500">{{ \App\Support\KioskSettings::ORIENTATIONS[$preview['orientation']] ?? '' }}</span>
            </dd>
        </div>
        <div class="rounded-lg bg-slate-50 px-2 py-2.5">
            <dt class="text-[11px] font-medium tracking-wider text-slate-500 uppercase">Size</dt>
            <dd class="mt-1 text-sm font-semibold text-slate-900">
                <span data-preview-dimensions>{{ $dimensions['width_cm'] }} × {{ $dimensions['height_cm'] }}</span>
                <span class="block text-xs font-normal text-slate-500">cm</span>
            </dd>
        </div>
        <div class="rounded-lg bg-slate-50 px-2 py-2.5">
            <dt class="text-[11px] font-medium tracking-wider text-slate-500 uppercase">Resolution</dt>
            <dd class="mt-1 text-sm font-semibold text-slate-900">
                <span data-preview-resolution>{{ $dimensions['width_px'] }} × {{ $dimensions['height_px'] }}</span>
                <span data-preview-ppi class="block text-xs font-normal text-slate-500">{{ $dimensions['ppi'] }} PPI</span>
            </dd>
        </div>
    </dl>
</div>
