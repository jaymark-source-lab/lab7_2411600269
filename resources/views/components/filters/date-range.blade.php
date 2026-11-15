@props(['from' => 'from', 'to' => 'to'])

<div class="ft-filter-group">
    <label for="{{ $from }}">From</label>
    <input type="date" name="{{ $from }}" id="{{ $from }}" class="form-control" value="{{ request($from) }}">
</div>
<div class="ft-filter-group">
    <label for="{{ $to }}">To</label>
    <input type="date" name="{{ $to }}" id="{{ $to }}" class="form-control" value="{{ request($to) }}">
</div>
