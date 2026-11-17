@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'indigo',
])

@php
    $tones = [
        'indigo' => 'bg-indigo-50 text-indigo-600 ring-indigo-600/10',
        'emerald' => 'bg-emerald-50 text-emerald-600 ring-emerald-600/10',
        'amber' => 'bg-amber-50 text-amber-600 ring-amber-600/10',
        'rose' => 'bg-rose-50 text-rose-600 ring-rose-600/10',
        'sky' => 'bg-sky-50 text-sky-600 ring-sky-600/10',
        'slate' => 'bg-slate-100 text-slate-600 ring-slate-600/10',
    ];
@endphp

<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex items-start justify-between gap-3">
        <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
        @isset($icon)
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ring-1 ring-inset {{ $tones[$tone] ?? $tones['indigo'] }}">
                {{ $icon }}
            </span>
        @endisset
    </div>
    <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-slate-400">{{ $hint }}</p>
    @endif
</div>
