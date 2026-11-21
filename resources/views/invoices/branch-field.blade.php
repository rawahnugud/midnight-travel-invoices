<div class="form-group">
  <label for="branch">{{ __('messages.issuing_branch') }}</label>
  <select name="branch" id="branch">
    {{-- Preserve the saved issuer when editing an older invoice explicitly assigned to Sudan. --}}
    <option value="{{ ($inv->branch ?? null) === 'SD' ? 'SD' : '' }}">{{ __('messages.choose_branch') }}</option>
    @foreach(['AE', 'GB'] as $code)
      <option value="{{ $code }}" @selected(old('branch', $inv->branch ?? null) === $code)>{{ __('messages.branch_'.$code) }}</option>
    @endforeach
  </select>
</div>
