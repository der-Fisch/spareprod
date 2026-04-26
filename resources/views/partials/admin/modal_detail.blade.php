<div class="modal fade in admin-modal" style="display:block;">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-modal-close><span>&times;</span></button>
        <h4 class="modal-title">{{ $config['singular'] }} Details</h4>
      </div>
      <div class="modal-body detail-grid">
        @php($detailFields = $config['detail_fields'] ?? $config['columns'])
        @foreach ($detailFields as $column)
          @php($value = resolve_path_value($object, $column['key']))
          @php($richType = in_array(($column['type'] ?? ''), ['list', 'key_value', 'image_list'], true))
          <div class="detail-row">
            <span>{{ $column['label'] }}</span>
            @if ($richType)
              <div class="detail-rich-value">
            @else
              <strong>
            @endif
              @if (($column['type'] ?? null) === 'currency')
                {{ rupiah($value) }}
              @elseif (($column['type'] ?? null) === 'currency_catalog')
                {{ rupiah_catalog($value) }}
              @elseif (($column['type'] ?? null) === 'boolean')
                {{ $value ? 'Active' : 'Inactive' }}
              @elseif (($column['type'] ?? null) === 'role')
                {{ $value ? 'Staff' : 'Customer' }}
              @elseif (($column['type'] ?? null) === 'date')
                {{ optional($value)->format('d/m/Y H:i') }}
              @elseif (($column['type'] ?? null) === 'list')
                <div class="detail-stack">
                  @forelse ((array) $value as $item)
                    <span class="detail-chip">{{ $item }}</span>
                  @empty
                    <span>-</span>
                  @endforelse
                </div>
              @elseif (($column['type'] ?? null) === 'key_value')
                <div class="detail-kv-list">
                  @forelse ((array) $value as $itemLabel => $itemValue)
                    <div class="detail-kv-item">
                      <span>{{ $itemLabel }}</span>
                      <b>{{ $itemValue }}</b>
                    </div>
                  @empty
                    <span>-</span>
                  @endforelse
                </div>
              @elseif (($column['type'] ?? null) === 'image_list')
                <div class="detail-image-list">
                  @forelse ($value ?? [] as $image)
                    <div class="detail-image-item">
                      <img src="{{ $image->image_url }}" alt="{{ $image->alt_text ?: $object->judul }}">
                      <small>{{ $image->image_path }}</small>
                    </div>
                  @empty
                    <span>-</span>
                  @endforelse
                </div>
              @else
                {{ $value ?: '-' }}
              @endif
            @if ($richType)
              </div>
            @else
              </strong>
            @endif
          </div>
        @endforeach
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-modal-close>Close</button>
      </div>
    </div>
  </div>
</div>
