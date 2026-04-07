@extends('backoffice.base')

@section('title', $page_title . ' | Backoffice')

@section('content')
  <section class="backoffice-entity-header">
    <div class="backoffice-entity-copy">
      <span class="eyebrow">{{ $entityConfig['singular'] }} Management</span>
      <h1>{{ $page_title }}</h1>
      <p>{{ $page_description }}</p>
    </div>
    @if ($entityConfig['can_create'])
      <div class="backoffice-entity-action">
        <a href="{{ route('backoffice.entity.modal.create', ['entity' => $entity, 'mode' => 'create']) }}" class="btn btn-primary" data-modal-open>Add {{ $entityConfig['singular'] }}</a>
      </div>
    @endif
  </section>

  <section id="entity-table-shell">
    @include('partials.backoffice.entity_table_shell')
  </section>
@endsection
