@props(['action', 'count' => null, 'export' => null])

@php
    // filters in use (page and sort don't count)
    $active = collect(request()->except(['page', 'sort']))->filter(fn ($v) => filled($v))->count();
@endphp

<form method="GET" action="{{ $action }}" class="ft-filter-bar mb-2">
    {{ $slot }}
    <div class="ft-filter-group">
        <button type="submit" class="btn ft-btn-primary"><i class="bi bi-funnel"></i> Filter</button>
        <a href="{{ $action }}" class="btn btn-light">Reset</a>
    </div>
    @if ($export)
        <div class="ft-filter-group ms-auto">
            <x-export-buttons :report="$export" />
        </div>
    @endif
</form>

@if ($count !== null)
    <div class="small text-muted mb-3">
        {{ $count }} {{ \Illuminate\Support\Str::plural('result', $count) }}
        @if ($active)
            &middot; {{ $active }} {{ \Illuminate\Support\Str::plural('filter', $active) }} active &middot;
            <a href="{{ $action }}" class="text-decoration-none">clear all</a>
        @endif
    </div>
@endif
