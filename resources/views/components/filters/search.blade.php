@props(['name' => 'search', 'label' => 'Search', 'placeholder' => ''])

<div class="ft-filter-group">
    <label for="{{ $name }}">{{ $label }}</label>
    <input type="text" name="{{ $name }}" id="{{ $name }}" class="form-control"
           value="{{ request($name) }}" placeholder="{{ $placeholder }}">
</div>
