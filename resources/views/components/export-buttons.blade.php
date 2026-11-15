@props(['report'])

@php
    // keep the current filters in the export links
    $query = request()->except('page');
@endphp

<div class="btn-group" role="group" aria-label="Export">
    <a href="{{ route('exports.download', ['report' => $report, 'format' => 'csv'] + $query) }}" class="btn btn-outline-secondary">
        <i class="bi bi-filetype-csv"></i> CSV
    </a>
    <a href="{{ route('exports.download', ['report' => $report, 'format' => 'pdf'] + $query) }}" class="btn btn-outline-secondary">
        <i class="bi bi-filetype-pdf"></i> PDF
    </a>
</div>
