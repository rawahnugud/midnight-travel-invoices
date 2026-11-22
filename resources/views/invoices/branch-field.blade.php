<div class="form-group">
  <label for="branch">{{ __('messages.issuing_branch') }}</label>
  <select name="branch" id="branch">
    {{-- Preserve the saved issuer when editing an older invoice explicitly assigned to Sudan. --}}
    <option data-currency="{{ $business->default_currency }}" value="{{ ($inv->branch ?? null) === 'SD' ? 'SD' : '' }}">{{ __('messages.choose_branch') }}</option>
    @foreach(['AE', 'GB'] as $code)
      <option data-currency="{{ $business->invoiceDetails($code)->default_currency }}" value="{{ $code }}" @selected(old('branch', $inv->branch ?? null) === $code)>{{ __('messages.branch_'.$code) }}</option>
    @endforeach
  </select>
</div>
