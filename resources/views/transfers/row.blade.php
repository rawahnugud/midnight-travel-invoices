<div class="form-row" data-index="{{ $index }}">
  @foreach(['name','account','amount'] as $field)
  <label class="form-group">{{ __('letters.'.$field) }}
    <input name="rows[{{ $index }}][{{ $field }}]" value="{{ $row[$field] ?? '' }}" @if($field==='account') dir="ltr" inputmode="numeric" @elseif($field==='amount') dir="ltr" inputmode="decimal" @endif>
  </label>
  @endforeach
  <button type="button" class="btn btn-text">{{ __('letters.remove') }}</button>
</div>
