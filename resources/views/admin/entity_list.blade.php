@extends('admin.base')

@section('title', $page_title . ' | Admin')

@section('content')
  <section class="admin-entity-header">
    <div class="admin-entity-copy">
      <span class="eyebrow">Manajemen {{ $entityConfig['singular'] }}</span>
      <h1>{{ $page_title }}</h1>
      <p>{{ $page_description }}</p>
    </div>
    @if ($entityConfig['can_create'])
      <div class="admin-entity-action">
        <a href="{{ route('admin.entity.modal.create', ['entity' => $entity, 'mode' => 'create']) }}" class="btn btn-primary" data-modal-open>Tambah {{ $entityConfig['singular'] }}</a>
      </div>
    @endif
  </section>

  <section id="entity-table-shell">
    @include('partials.admin.entity_table_shell')
  </section>
@endsection
