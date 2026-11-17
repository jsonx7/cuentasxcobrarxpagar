@props(['status'])

@php
    $label = method_exists($status, 'label')
        ? $status->label()
        : \Illuminate\Support\Str::headline((string) $status);

    $classes = method_exists($status, 'badgeClasses')
        ? $status->badgeClasses()
        : 'bg-slate-100 text-slate-600 ring-slate-500/20';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset '.$classes]) }}>
    {{ $label }}
</span>
