<div class="form-group">
  <label for="branch">{{ __('messages.issuing_branch') }}</label>
  <select name="branch" id="branch">
    <option value="">{{ __('messages.choose_branch') }}</option>
    @foreach(\App\Models\Invoice::BRANCHES as $code)
      <option value="{{ $code }}" @selected(old('branch', $inv->branch ?? null) === $code)>{{ __('messages.branch_'.$code) }}</option>
    @endforeach
  </select>
</div>
