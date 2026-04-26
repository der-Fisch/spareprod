<div class="table-responsive admin-table-responsive">
  <table class="table admin-table">
    <thead>
      <tr>
        <th class="admin-table-number-col">No.</th>
        @foreach ($entityConfig['columns'] as $column)
          <th>{{ $column['label'] }}</th>
        @endforeach
        <th class="text-right">Actions</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($page_obj->items() as $row)
        <tr>
          <td class="admin-table-number-cell">{{ ($page_obj->firstItem() ?? 1) + $loop->index }}</td>
          @foreach ($entityConfig['columns'] as $column)
            @php($value = resolve_path_value($row, $column['key']))
            <td class="{{ ($column['type'] ?? null) === 'image' ? 'admin-table-thumbnail-cell' : '' }}">
              @php($displayValue = ($value === 0 || $value === '0') ? $value : ($value ?: '-'))
              @if (($column['type'] ?? null) === 'currency')
                {{ rupiah($value) }}
              @elseif (($column['type'] ?? null) === 'currency_catalog')
                {{ rupiah_catalog($value) }}
              @elseif (($column['type'] ?? null) === 'image')
                <img src="{{ $value ?: asset('theme/img/marketing1.jpg') }}" alt="{{ $row->title ?? 'Product image' }}" class="admin-table-thumbnail">
              @elseif (($column['type'] ?? null) === 'boolean')
                <span class="table-chip{{ $value ? ' table-chip-success' : ' table-chip-muted' }}">{{ $value ? 'Active' : 'Inactive' }}</span>
              @elseif (($column['type'] ?? null) === 'badge')
                <span class="table-chip">{{ $value ?: 'Draft' }}</span>
              @elseif (($column['type'] ?? null) === 'role')
                <span class="table-chip{{ $value ? ' table-chip-warning' : ' table-chip-muted' }}">{{ $value ? 'Staff' : 'Customer' }}</span>
              @elseif (($column['type'] ?? null) === 'date')
                {{ optional($value)->format('d/m/Y') }}
              @else
                {{ $displayValue }}
              @endif
            </td>
          @endforeach
          <td class="text-right">
            <div class="table-actions">
              <a href="{{ route('admin.entity.modal', ['entity' => $entity, 'pk' => $row->id, 'mode' => 'detail']) }}" data-modal-open title="Detail" class="table-action-pill"><i class="fa fa-eye"></i></a>
              @if ($entityConfig['can_update'])
                <a href="{{ route('admin.entity.modal', ['entity' => $entity, 'pk' => $row->id, 'mode' => 'edit']) }}" data-modal-open title="Edit" class="table-action-pill"><i class="fa fa-pencil"></i></a>
              @endif
              <a href="{{ route('admin.entity.modal', ['entity' => $entity, 'pk' => $row->id, 'mode' => 'delete']) }}" data-modal-open title="Delete" class="table-action-pill table-action-danger"><i class="fa fa-trash"></i></a>
            </div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="{{ count($entityConfig['columns']) + 2 }}">No data found.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

@if ($page_obj->lastPage() > 1)
  <nav class="pagination-shell">
    @if ($page_obj->previousPageUrl())
      <a href="{{ $page_obj->previousPageUrl() }}" class="pagination-link" data-entity-page>Previous</a>
    @endif
    <span class="pagination-status">Page {{ $page_obj->currentPage() }} of {{ $page_obj->lastPage() }}</span>
    @if ($page_obj->nextPageUrl())
      <a href="{{ $page_obj->nextPageUrl() }}" class="pagination-link" data-entity-page>Next</a>
    @endif
  </nav>
@endif
