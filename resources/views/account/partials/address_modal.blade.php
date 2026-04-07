@php
  $addressId = $address?->id;
  $defaults = [
    'label' => old('label', $address?->label),
    'recipient_name' => old('recipient_name', $address?->recipient_name ?: auth()->user()->name),
    'phone_number' => old('phone_number', $address?->phone_number),
    'street' => old('street', $address?->street),
    'city' => old('city', $address?->city),
    'state' => old('state', $address?->state),
    'zipcode' => old('zipcode', $address?->zipcode),
    'is_default' => old('is_default', $address?->is_default ? 1 : 0),
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
          @if ($addressId)
            <input type="hidden" name="address_id" value="{{ $addressId }}">
          @endif

          <div class="form-group">
            <label for="{{ $modalId }}-label">Label Alamat</label>
            <input id="{{ $modalId }}-label" type="text" name="label" class="form-control" value="{{ $defaults['label'] }}" placeholder="Contoh: Rumah, Kantor">
            @foreach ($errorBag->get('label') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
          </div>

          <div class="form-group">
            <label for="{{ $modalId }}-recipient_name">Nama Penerima</label>
            <input id="{{ $modalId }}-recipient_name" type="text" name="recipient_name" class="form-control" value="{{ $defaults['recipient_name'] }}" required>
            @foreach ($errorBag->get('recipient_name') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
          </div>

          <div class="form-group">
            <label for="{{ $modalId }}-phone_number">Nomor HP</label>
            <input id="{{ $modalId }}-phone_number" type="text" name="phone_number" class="form-control" value="{{ $defaults['phone_number'] }}" required>
            @foreach ($errorBag->get('phone_number') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
          </div>

          <div class="form-group form-group-span-2">
            <label for="{{ $modalId }}-street">Jalan / Detail Alamat</label>
            <input id="{{ $modalId }}-street" type="text" name="street" class="form-control" value="{{ $defaults['street'] }}" required>
            @foreach ($errorBag->get('street') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
          </div>

          <div class="form-group">
            <label for="{{ $modalId }}-city">Kota / Kabupaten</label>
            <input id="{{ $modalId }}-city" type="text" name="city" class="form-control" value="{{ $defaults['city'] }}" required>
            @foreach ($errorBag->get('city') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
          </div>

          <div class="form-group">
            <label for="{{ $modalId }}-state">Provinsi</label>
            <input id="{{ $modalId }}-state" type="text" name="state" class="form-control" value="{{ $defaults['state'] }}" required>
            @foreach ($errorBag->get('state') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
          </div>

          <div class="form-group">
            <label for="{{ $modalId }}-zipcode">Kode Pos</label>
            <input id="{{ $modalId }}-zipcode" type="text" name="zipcode" class="form-control" value="{{ $defaults['zipcode'] }}" required>
            @foreach ($errorBag->get('zipcode') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
          </div>

          <div class="form-group form-group-span-2">
            <label class="account-checkbox">
              <input type="checkbox" name="is_default" value="1" @checked((string) $defaults['is_default'] === '1')>
              <span>Jadikan alamat utama untuk checkout</span>
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
