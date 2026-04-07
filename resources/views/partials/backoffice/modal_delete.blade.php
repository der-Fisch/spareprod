<div class="modal fade in backoffice-modal" style="display:block;">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-modal-close><span>&times;</span></button>
        <h4 class="modal-title">Delete {{ $config['singular'] }}</h4>
      </div>
      <form method="POST" action="{{ route('backoffice.entity.modal.store', ['entity' => $entity, 'pk' => $object->id, 'mode' => 'delete']) }}" data-modal-form>
        @csrf
        <div class="modal-body">
          <p>Are you sure you want to delete <strong>{{ $object }}</strong>?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
          <button type="submit" class="btn btn-primary">Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>
