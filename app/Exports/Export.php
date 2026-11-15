<?php

namespace App\Exports;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class Export
{
    // max rows in a PDF (CSV has no limit)
    public const PDF_LIMIT = 1000;

    // filters from the request
    protected array $filters = [];

    // report title
    abstract public function title(): string;

    // column headings
    abstract public function headings(): array;

    // data rows, one plain array per row
    abstract public function rows(): iterable;

    // column numbers aligned right in the PDF
    public function rightAligned(): array
    {
        return [];
    }

    // readable list of the filters used
    public function summary(): string
    {
        $names = [
            'supplier_id' => fn ($id) => Supplier::withTrashed()->find($id)?->name,
            'product_id'  => fn ($id) => Product::find($id)?->name,
            'user_id'     => fn ($id) => User::find($id)?->name,
        ];

        return collect($this->filters)
            ->except(['sort', 'page'])
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value, $key) => Str::headline(Str::beforeLast($key, '_id')) . ': '
                . (isset($names[$key]) ? ($names[$key]($value) ?? $value) : $value))
            ->implode(' | ') ?: 'none (all records)';
    }

    // file name without extension
    public function filename(): string
    {
        return Str::slug($this->title()) . '-' . now()->format('Y-m-d-His');
    }

    // download as CSV, streamed row by row
    public function toCsv(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // lets Excel read UTF-8
            fputcsv($out, $this->headings());

            foreach ($this->rows() as $row) {
                fputcsv($out, $row);
            }

            fclose($out);
        }, $this->filename() . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // download as PDF
    public function toPdf()
    {
        $rows = collect($this->rows())->take(self::PDF_LIMIT + 1);

        return Pdf::loadView('exports.table', [
            'title'     => $this->title(),
            'summary'   => $this->summary(),
            'headings'  => $this->headings(),
            'rows'      => $rows->take(self::PDF_LIMIT),
            'truncated' => $rows->count() > self::PDF_LIMIT,
            'right'     => $this->rightAligned(),
        ])->setPaper('a4', 'landscape')->download($this->filename() . '.pdf');
    }

    // format a date or return blank
    protected function date($value, string $format = 'Y-m-d'): string
    {
        return $value ? Carbon::parse($value)->format($format) : '';
    }

    // money with 2 decimals, no thousands separator
    protected function money($value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
