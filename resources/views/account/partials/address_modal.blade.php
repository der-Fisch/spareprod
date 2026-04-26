@php
  $addressId = $address?->id;
  $defaults = [
    'label' => old('label', $address?->label),
    'nama_penerima' => old('nama_penerima', $address?->nama_penerima ?: auth()->user()->name),
    'nomor_whatsapp' => old('nomor_whatsapp', $address?->nomor_whatsapp),
    'nama_jalan' => old('nama_jalan', $address?->nama_jalan),
    'nama_kota' => old('nama_kota', $address?->nama_kota),
    'negara' => old('negara', $address?->negara),
    'kode_pos' => old('kode_pos', $address?->kode_pos),
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
            <label for="{{ $modalId }}-nama_penerima">Nama Penerima</label>
            <input id="{{ $modalId }}-nama_penerima" type="text" name="nama_penerima" class="form-control" value="{{ $defaults['nama_penerima'] }}" required>
            @foreach ($errorBag->get('nama_penerima') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
          </div>

          <div class="form-group">
            <label for="{{ $modalId }}-nomor_whatsapp">Nomor WhatsApp</label>
            <input id="{{ $modalId }}-nomor_whatsapp" type="text" name="nomor_whatsapp" class="form-control" value="{{ $defaults['nomor_whatsapp'] }}" required>
            @foreach ($errorBag->get('nomor_whatsapp') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
          </div>

          <div class="form-group form-group-span-2">
            <label for="{{ $modalId }}-nama_jalan">Jalan / Detail Alamat</label>
            <input id="{{ $modalId }}-nama_jalan" type="text" name="nama_jalan" class="form-control" value="{{ $defaults['nama_jalan'] }}" required>
            @foreach ($errorBag->get('nama_jalan') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
          </div>

          <div class="form-group">
            <label for="{{ $modalId }}-nama_kota">Kota / Kabupaten</label>
            <input id="{{ $modalId }}-nama_kota" type="text" name="nama_kota" class="form-control" value="{{ $defaults['nama_kota'] }}" required>
            @foreach ($errorBag->get('nama_kota') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
          </div>

          <div class="form-group">
            <label for="{{ $modalId }}-negara">Negara</label>
            <input id="{{ $modalId }}-negara" type="text" name="negara" class="form-control" value="{{ $defaults['negara'] }}" required>
            @foreach ($errorBag->get('negara') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
          </div>

          <div class="form-group">
            <label for="{{ $modalId }}-kode_pos">Kode Pos</label>
            <input id="{{ $modalId }}-kode_pos" type="text" name="kode_pos" class="form-control" value="{{ $defaults['kode_pos'] }}" required>
            @foreach ($errorBag->get('kode_pos') as $message)<p class="text-danger">{{ $message }}</p>@endforeach
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

