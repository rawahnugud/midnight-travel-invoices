@extends('layout')
@section('title', __('letters.menu'))
@section('content')
<div class="card">
  <div class="card-header"><h2>{{ $letter->exists ? __('letters.edit').' #'.$letter->id : __('letters.new') }}</h2><a href="{{ route('letters.index') }}">{{ __('letters.menu') }}</a></div>
  <form class="card-body" action="{{ $letter->exists ? route('letters.update', $letter) : route('letters.store') }}" method="post">
    @csrf
    @if($letter->exists)
      @method('PUT')
      <input type="hidden" name="revision" value="{{ old('revision', $letter->revision) }}">
    @endif
    @if(session('success'))<p role="status">{{ session('success') }}</p>@endif
    @if($errors->any())<div role="alert">@foreach($errors->all() as $message)<p>{{ $message }}</p>@endforeach</div>@endif
    <div class="form-row two-cols">
      <div class="form-group"><label for="letter-language">{{ __('letters.language') }}</label>
        <select id="letter-language" name="language">@foreach(['en'=>'English','ar'=>'العربية'] as $code=>$label)<option value="{{ $code }}" @selected(old('language', $data['language'] ?? app()->getLocale()) === $code)>{{ $label }}</option>@endforeach</select>
      </div>
      <div class="form-group"><label for="letter-date">{{ __('letters.date') }}</label><input id="letter-date" type="date" name="date" required value="{{ old('date', $data['date'] ?? today()->toDateString()) }}"></div>
      @foreach(['bank','holder','account','signer','role'] as $field)
      <div class="form-group"><label for="letter-{{ $field }}">{{ __('letters.'.$field) }}</label>
        <input id="letter-{{ $field }}" name="{{ $field }}" value="{{ old($field, $data[$field] ?? '') }}" @if($field==='account') dir="ltr" inputmode="numeric" @endif>
      </div>
      @endforeach
    </div>
    <h3>{{ __('letters.beneficiaries') }}</h3>
    <div id="beneficiaries">
      @foreach(old('rows', $data['rows'] ?? [[]]) as $index => $row)
        @include('transfers.row')
      @endforeach
    </div>
    <template id="beneficiary-template">@include('transfers.row', ['index'=>'INDEX', 'row'=>[]])</template>
    <button type="button" class="btn btn-outline" id="add-beneficiary">{{ __('letters.add') }}</button>
    <div class="form-actions">
      @if($letter->exists)<a class="btn btn-outline" href="{{ route('letters.copy', $letter) }}">{{ __('letters.copy') }}</a>@endif
      <button name="action" value="save" class="btn btn-primary">{{ __('letters.save') }}</button>
      <button name="action" value="print" class="btn btn-outline">{{ __('letters.preview') }}</button>
    </div>
  </form>
</div>
<script>
const rows = document.getElementById('beneficiaries');
function updateButtons() {
    for (const button of rows.querySelectorAll('button')) button.disabled = rows.children.length === 1;
    document.getElementById('add-beneficiary').disabled = rows.children.length >= 50;
}
document.getElementById('add-beneficiary').onclick = () => {
    const row = document.getElementById('beneficiary-template').content.cloneNode(true);
    const nextIndex = Number(rows.lastElementChild?.dataset.index ?? -1) + 1;
    row.firstElementChild.dataset.index = nextIndex;
    for (const input of row.querySelectorAll('input')) input.name = input.name.replace('INDEX', nextIndex);
    rows.append(row);
    rows.lastElementChild.querySelector('input').focus();
    updateButtons();
};
rows.onclick = event => {
    if (event.target.matches('button') && rows.children.length > 1) {
        event.target.closest('[data-index]').remove();
        updateButtons();
    }
};
updateButtons();
</script>
@endsection
