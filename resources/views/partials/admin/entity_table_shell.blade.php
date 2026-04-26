<div class="backoffice-summary-grid">
  @foreach ($summary_items as $label => $value)
    <article class="summary-mini-card">
      <span>{{ $label }}</span>
      <strong>{{ $value }}</strong>
    </article>
  @endforeach
</div>

<div class="backoffice-panel backoffice-panel-table">
  <div class="backoffice-filter-card">
    <div class="panel-toolbar">
      <div class="panel-toolbar-actions">
        <form class="backoffice-search-form" method="GET" data-entity-search data-results-target="#entity-table-shell">
          <i class="fa fa-search"></i>
          <input type="text" name="q" value="{{ $search_query }}" placeholder="Cari {{ strtolower($entityConfig['label']) }}">
          <button type="submit" class="btn btn-ghost">Cari</button>
        </form>
        <a href="{{ route('backoffice.entity.list', ['entity' => $entity]) }}" class="backoffice-reset-link" data-entity-page>Reset</a>
      </div>
    </div>
  </div>

  <div class="backoffice-table-card">
    <div class="table-card-header">
      <div>
        <span class="eyebrow">Data List</span>
        <h2>Daftar {{ strtolower($entityConfig['label']) }}</h2>
      </div>
    </div>
    @include('partials.admin.entity_table')
  </div>
</div>
