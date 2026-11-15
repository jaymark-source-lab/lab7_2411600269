<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 28px 32px 44px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #222; }
        .brand { font-size: 16px; font-weight: bold; color: #085054; }
        .title { font-size: 14px; font-weight: bold; text-align: right; }
        .meta { color: #666; margin: 6px 0 10px; }
        table { width: 100%; border-collapse: collapse; }
        .data th { background: #085054; color: #fff; text-align: left; padding: 5px; font-size: 8.5px; text-transform: uppercase; }
        .data td { padding: 4px 5px; border-bottom: 1px solid #e2e2e2; }
        .data tr:nth-child(even) td { background: #f3f8f8; }
        .right { text-align: right; }
        .note { color: #b45309; margin-top: 8px; }
        .footer { position: fixed; bottom: -28px; left: 0; right: 0; font-size: 8px; color: #888; border-top: 1px solid #ddd; padding-top: 4px; }
    </style>
</head>
<body>
    <div class="footer">
        FitTrack Pro &middot; generated {{ now('Asia/Manila')->format('M j, Y g:i A') }} by {{ auth()->user()?->name ?? 'System' }}
    </div>

    <table>
        <tr>
            <td class="brand">FitTrack Pro</td>
            <td class="title">{{ $title }} Report</td>
        </tr>
    </table>

    <div class="meta">
        Filters: {{ $summary }} &middot; {{ $rows->count() }} {{ \Illuminate\Support\Str::plural('record', $rows->count()) }}
    </div>

    <table class="data">
        <thead>
            <tr>
                @foreach ($headings as $i => $heading)
                    <th class="{{ in_array($i, $right) ? 'right' : '' }}">{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach (array_values($row) as $i => $cell)
                        <td class="{{ in_array($i, $right) ? 'right' : '' }}">{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headings) }}">No records match these filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($truncated)
        <div class="note">Only the first {{ \App\Exports\Export::PDF_LIMIT }} records are shown. Use CSV for the full list.</div>
    @endif
</body>
</html>
