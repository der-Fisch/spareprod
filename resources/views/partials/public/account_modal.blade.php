<div class="ui-modal{{ $activeModal === $actionValue ? ' is-visible' : '' }}" id="{{ $modalId }}">
  <div class="ui-modal-backdrop" data-ui-modal-close></div>
  <div class="ui-modal-dialog">
    <div class="ui-modal-content">
      <div class="ui-modal-header">
        <h3>{{ $title }}</h3>
        <button type="button" class="ui-modal-close" data-ui-modal-close>&times;</button>
      </div>
      <div class="ui-modal-body">
        <form method="POST" action="{{ route('account.settings.update') }}">
          @csrf
          <input type="hidden" name="action" value="{{ $actionValue }}">
          @foreach ($fields as $field)
            <div class="form-group">
              <label for="{{ $modalId }}-{{ $field['name'] }}">{{ $field['label'] }}</label>
              <input
                id="{{ $modalId }}-{{ $field['name'] }}"
                type="{{ $field['type'] }}"
                name="{{ $field['name'] }}"
                value="{{ $field['value'] }}"
                class="form-control"
              >
              @if ($errorBag->has($field['name']))
                @foreach ($errorBag->get($field['name']) as $message)
                  <p class="text-danger">{{ $message }}</p>
                @endforeach
              @endif
            </div>
          @endforeach
          <div class="ui-modal-actions">
            <button type="button" class="btn btn-ghost" data-ui-modal-close>Batal</button>
            <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

