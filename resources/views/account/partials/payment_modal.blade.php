@php
  $paymentMethodId = $paymentMethod?->id;
  $defaults = [
    'provider_code' => old('provider_code', $paymentMethod?->provider_code),
    'account_name' => old('account_name', $paymentMethod?->account_name ?: auth()->user()->name),
    'account_reference' => old('account_reference', $paymentMethod?->account_reference),
    'is_default' => old('is_default', $paymentMethod?->is_default ? 1 : 0),
  ];
@endphp

<div class="ui-modal{{ $activeModal === $modalId ? ' is-visible' : '' }}" id="{{ $modalId }}">
  <div class="ui-modal-backdrop" data-ui-modal-close></div>
  <div class="ui-modal-dialog">
    <div class="ui-modal-content">
      <div class="ui-modal-header">
        <h3>{{ $title }}</h3>
        <button type="button" class="ui-modal-close" data-ui-modal-close>&times;</button>
      </div>
      <div class="ui-modal-body">
        <form method="POST" action="{{ route('account.settings.update') }}" class="account-form-grid">
          @csrf
          <input type="hidden" name="action" value="{{ $actionValue }}">
          <input type="hidden" name="active_tab" value="{{ $activeTab }}">
          @if ($paymentMethodId)
            <input type="hidden" name="payment_method_id" value="{{ $paymentMethodId }}">
          @endif

          <div class="form-group form-group-span-2">
            <label for="{{ $modalId }}-provider_code">Provider Pembayaran</label>
            <select id="{{ $modalId }}-provider_code" name="provider_code" class="form-control" required>
              <option value="">Pilih provider</option>
              @foreach ($paymentProviderOptions as $code => $provider)
                <option value="{{ $code }}" @selected($defaults['provider_code'] === $code)>{{ $provider['name'] }}</option>
              @endforeach
            </select>
            @foreach ($errorBag->get('provider_code') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
          </div>

          <div class="form-group">
            <label for="{{ $modalId }}-account_name">Nama Akun / Pemilik</label>
            <input id="{{ $modalId }}-account_name" type="text" name="account_name" class="form-control" value="{{ $defaults['account_name'] }}">
            @foreach ($errorBag->get('account_name') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
          </div>

          <div class="form-group">
            <label for="{{ $modalId }}-account_reference">Nomor Referensi / No. HP / VA</label>
            <input id="{{ $modalId }}-account_reference" type="text" name="account_reference" class="form-control" value="{{ $defaults['account_reference'] }}" placeholder="Contoh: 8808123456789">
            @foreach ($errorBag->get('account_reference') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
          </div>

          <div class="form-group form-group-span-2">
            <label class="account-checkbox">
              <input type="checkbox" name="is_default" value="1" @checked((string) $defaults['is_default'] === '1')>
              <span>Jadikan metode pembayaran utama</span>
            </label>
          </div>

          <div class="ui-modal-actions">
            <button type="button" class="btn btn-ghost" data-ui-modal-close>Batal</button>
            <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
