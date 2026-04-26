<div class="admin-summary-grid">
  @foreach ($summary_items as $label => $value)
    <article class="summary-mini-card">
      <span>{{ $label }}</span>
      <strong>{{ $value }}</strong>
    </article>
  @endforeach
</div>

<div class="admin-panel admin-panel-table">
  <div class="admin-filter-card">
    <div class="panel-toolbar">
      <div class="panel-toolbar-actions">
        <form class="admin-search-form" method="GET" data-entity-search data-results-target="#entity-table-shell">
          <i class="fa fa-search"></i>
          <input type="text" name="q" value="{{ $search_query }}" placeholder="Cari {{ strtolower($entityConfig['label']) }}">
          <button type="submit" class="btn btn-ghost">Cari</button>
        </form>
        <a href="{{ route('admin.entity.list', ['entity' => $entity]) }}" class="admin-reset-link" data-entity-page>Reset</a>
      </div>
    </div>
  </div>

  <div class="admin-table-card">
    <div class="table-card-header">
      <div>
        <span class="eyebrow">Data List</span>
        <h2>Daftar {{ strtolower($entityConfig['label']) }}</h2>
      </div>
    </div>
    @include('partials.admin.entity_table')
  </div>
</div>
