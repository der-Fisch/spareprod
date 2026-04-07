<div class="modal fade in backoffice-modal" style="display:block;">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-modal-close><span>&times;</span></button>
        <h4 class="modal-title">{{ $mode === 'create' ? 'Add' : 'Edit' }} {{ $config['singular'] }}</h4>
      </div>
      <form method="POST" enctype="multipart/form-data" action="{{ $object ? route('backoffice.entity.modal.store', ['entity' => $entity, 'pk' => $object->id, 'mode' => $mode]) : route('backoffice.entity.modal.create.store', ['entity' => $entity, 'mode' => $mode]) }}" data-modal-form>
        @csrf
        <div class="modal-body">
          @if (method_exists($errorsBag, 'all') && count($errorsBag->all()))
            <div class="alert alert-danger backoffice-form-alert">
              <strong>Data belum bisa disimpan.</strong>
              <ul class="backoffice-form-alert-list">
                @foreach ($errorsBag->all() as $message)
                  <li>{{ $message }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          @foreach ($config['fields'] as $field)
            @continue(($field['create_only'] ?? false) && $mode !== 'create')
            @php($fieldType = $field['type'])
            @php($fieldName = $field['name'])
            @php($fieldId = $entity . '-' . $fieldName)
            @php($value = $input[$fieldName] ?? (($fieldType === 'checkbox') ? false : ''))
            @php($options = $field['options'] ?? [])
            @php($placeholder = $field['placeholder'] ?? '')
            <div class="form-group">
              <label for="{{ $fieldId }}">{{ $field['label'] }}</label>

              @if ($fieldType === 'textarea')
                <textarea id="{{ $fieldId }}" name="{{ $fieldName }}" class="form-control" placeholder="{{ $placeholder }}">{{ $value }}</textarea>
              @elseif ($fieldType === 'select')
                @php($selectedLabel = $options[$value] ?? '')
                <div class="searchable-select" data-searchable-select data-options='@json($options)'>
                  <input type="hidden" id="{{ $fieldId }}" name="{{ $fieldName }}" value="{{ $value }}" data-searchable-select-value>
                  <button type="button" class="searchable-select-trigger" data-searchable-select-trigger>
                    <span data-searchable-select-label data-searchable-select-placeholder="{{ $placeholder !== '' ? $placeholder : 'Pilih ' . strtolower($field['label']) }}">{{ $selectedLabel !== '' ? $selectedLabel : ($placeholder !== '' ? $placeholder : 'Pilih ' . strtolower($field['label'])) }}</span>
                    <i class="fa fa-angle-down"></i>
                  </button>
                  <div class="searchable-select-panel" data-searchable-select-panel>
                    <input type="text" class="form-control searchable-select-search" placeholder="{{ $placeholder !== '' ? $placeholder : 'Cari ' . strtolower($field['label']) }}" data-searchable-select-search autocomplete="off">
                    <div class="searchable-select-options" data-searchable-select-options>
                      @foreach ($options as $optionValue => $optionLabel)
                        <button type="button" class="searchable-select-option" data-searchable-select-option data-option-label="{{ strtolower($optionLabel) }}" data-option-value="{{ $optionValue }}" data-option-text="{{ $optionLabel }}">
                          {{ $optionLabel }}
                        </button>
                      @endforeach
                    </div>
                  </div>
                </div>
              @elseif ($fieldType === 'multiselect')
                @php($selected = collect(is_array($value) ? $value : [])->map(fn ($item) => (string) $item)->all())
                @php($selectedLabels = collect($options)->filter(fn ($optionLabel, $optionValue) => in_array((string) $optionValue, $selected, true))->values()->implode(', '))
                <div class="searchable-multiselect" data-searchable-multiselect>
                  <button type="button" class="searchable-multiselect-trigger" data-multiselect-trigger>
                    <span data-multiselect-label data-multiselect-placeholder="{{ $placeholder !== '' ? $placeholder : 'Pilih ' . strtolower($field['label']) }}">{{ $selectedLabels !== '' ? $selectedLabels : ($placeholder !== '' ? $placeholder : 'Pilih ' . strtolower($field['label'])) }}</span>
                    <i class="fa fa-angle-down"></i>
                  </button>
                  <div class="searchable-multiselect-panel" data-multiselect-panel>
                    <input type="text" class="form-control searchable-multiselect-search" placeholder="{{ $placeholder !== '' ? $placeholder : 'Cari ' . strtolower($field['label']) }}" data-multiselect-search>
                    <div class="searchable-multiselect-options" data-multiselect-options>
                      @foreach ($options as $optionValue => $optionLabel)
                        <label class="searchable-multiselect-option" data-option-label="{{ strtolower($optionLabel) }}">
                          <input type="checkbox" name="{{ $fieldName }}[]" value="{{ $optionValue }}" @checked(in_array((string) $optionValue, $selected, true))>
                          <span>{{ $optionLabel }}</span>
                        </label>
                      @endforeach
                    </div>
                  </div>
                </div>
              @elseif ($fieldType === 'checkbox')
                <div class="checkbox">
                  <label>
                    <input id="{{ $fieldId }}" type="checkbox" name="{{ $fieldName }}" value="1" @checked((bool) $value)>
                    {{ $field['label'] }}
                  </label>
                </div>
              @elseif ($fieldType === 'rating')
                @php($ratingValue = is_numeric($value) ? (float) $value : 0)
                <div class="rating-input" data-rating-input>
                  <input type="hidden" id="{{ $fieldId }}" name="{{ $fieldName }}" value="{{ $ratingValue }}" data-rating-value>
                  <div class="rating-stars" data-rating-stars>
                    @for ($starIndex = 1; $starIndex <= 5; $starIndex++)
                      <button type="button" class="rating-star" data-star-index="{{ $starIndex }}" aria-label="Rating {{ $starIndex }}">
                        <i class="fa fa-star-o icon-empty"></i>
                        <i class="fa fa-star-half-o icon-half"></i>
                        <i class="fa fa-star icon-full"></i>
                      </button>
                    @endfor
                  </div>
                  <small class="help-block rating-help" data-rating-text></small>
                </div>
              @elseif ($fieldType === 'currency_catalog')
                <div class="currency-input" data-currency-field data-currency-scale="catalog">
                  <input type="hidden" id="{{ $fieldId }}" name="{{ $fieldName }}" value="{{ $value }}" data-currency-hidden>
                  <input type="text" class="form-control currency-input-display" value="" placeholder="{{ $placeholder !== '' ? $placeholder : 'Rp0' }}" data-currency-display autocomplete="off">
                </div>
              @elseif ($fieldType === 'compatibility_repeater')
                @php($rows = is_array($value) && count($value) ? array_values($value) : [['vehicle_name' => '', 'year_start' => '', 'year_end' => '']])
                <div class="repeater-field" data-repeater data-repeater-name="{{ $fieldName }}">
                  <div class="repeater-list" data-repeater-list>
                    @foreach ($rows as $index => $row)
                      <div class="repeater-item repeater-item-compatibility" data-repeater-item>
                        <div class="repeater-item-grid repeater-item-grid-3">
                          <div>
                            <span class="repeater-sub-label">Nama Kendaraan</span>
                            <input type="text" class="form-control" name="{{ $fieldName }}[{{ $index }}][vehicle_name]" value="{{ $row['vehicle_name'] ?? '' }}" placeholder="Toyota Avanza">
                          </div>
                          <div>
                            <span class="repeater-sub-label">Tahun Awal</span>
                            <input type="number" class="form-control" name="{{ $fieldName }}[{{ $index }}][year_start]" value="{{ $row['year_start'] ?? '' }}" placeholder="2015">
                          </div>
                          <div>
                            <span class="repeater-sub-label">Tahun Akhir</span>
                            <input type="number" class="form-control" name="{{ $fieldName }}[{{ $index }}][year_end]" value="{{ $row['year_end'] ?? '' }}" placeholder="2021">
                          </div>
                        </div>
                        <button type="button" class="repeater-remove" data-repeater-remove>Hapus</button>
                      </div>
                    @endforeach
                  </div>
                  <button type="button" class="repeater-add" data-repeater-add>+ Tambah Kompatibilitas</button>
                  <script type="text/template" data-repeater-template>
                    <div class="repeater-item repeater-item-compatibility" data-repeater-item>
                      <div class="repeater-item-grid repeater-item-grid-3">
                        <div>
                          <span class="repeater-sub-label">Nama Kendaraan</span>
                          <input type="text" class="form-control" name="{{ $fieldName }}[__INDEX__][vehicle_name]" value="" placeholder="Toyota Avanza">
                        </div>
                        <div>
                          <span class="repeater-sub-label">Tahun Awal</span>
                          <input type="number" class="form-control" name="{{ $fieldName }}[__INDEX__][year_start]" value="" placeholder="2015">
                        </div>
                        <div>
                          <span class="repeater-sub-label">Tahun Akhir</span>
                          <input type="number" class="form-control" name="{{ $fieldName }}[__INDEX__][year_end]" value="" placeholder="2021">
                        </div>
                      </div>
                      <button type="button" class="repeater-remove" data-repeater-remove>Hapus</button>
                    </div>
                  </script>
                </div>
              @elseif ($fieldType === 'specification_repeater')
                @php($rows = is_array($value) && count($value) ? array_values($value) : [['label' => '', 'value' => '']])
                <div class="repeater-field" data-repeater data-repeater-name="{{ $fieldName }}">
                  <div class="repeater-list" data-repeater-list>
                    @foreach ($rows as $index => $row)
                      <div class="repeater-item repeater-item-spec" data-repeater-item>
                        <div class="repeater-item-grid repeater-item-grid-2">
                          <div>
                            <span class="repeater-sub-label">Label</span>
                            <input type="text" class="form-control" name="{{ $fieldName }}[{{ $index }}][label]" value="{{ $row['label'] ?? '' }}" placeholder="Bahan">
                          </div>
                          <div>
                            <span class="repeater-sub-label">Value</span>
                            <input type="text" class="form-control" name="{{ $fieldName }}[{{ $index }}][value]" value="{{ $row['value'] ?? '' }}" placeholder="Copper Alloy">
                          </div>
                        </div>
                        <button type="button" class="repeater-remove" data-repeater-remove>Hapus</button>
                      </div>
                    @endforeach
                  </div>
                  <button type="button" class="repeater-add" data-repeater-add>+ Tambah Spesifikasi</button>
                  <script type="text/template" data-repeater-template>
                    <div class="repeater-item repeater-item-spec" data-repeater-item>
                      <div class="repeater-item-grid repeater-item-grid-2">
                        <div>
                          <span class="repeater-sub-label">Label</span>
                          <input type="text" class="form-control" name="{{ $fieldName }}[__INDEX__][label]" value="" placeholder="Bahan">
                        </div>
                        <div>
                          <span class="repeater-sub-label">Value</span>
                          <input type="text" class="form-control" name="{{ $fieldName }}[__INDEX__][value]" value="" placeholder="Copper Alloy">
                        </div>
                      </div>
                      <button type="button" class="repeater-remove" data-repeater-remove>Hapus</button>
                    </div>
                  </script>
                </div>
              @elseif ($fieldType === 'variation_repeater')
                @php($rows = is_array($value) && count($value) ? array_values($value) : [['title' => '', 'price' => '', 'sale_price' => '', 'inventory' => '']])
                <div class="repeater-field" data-repeater data-repeater-name="{{ $fieldName }}">
                  <div class="repeater-list" data-repeater-list>
                    @foreach ($rows as $index => $row)
                      <div class="repeater-item repeater-item-variation" data-repeater-item>
                        <div class="repeater-item-grid repeater-item-grid-4">
                          <div>
                            <span class="repeater-sub-label">Nama Varian</span>
                            <input type="text" class="form-control" name="{{ $fieldName }}[{{ $index }}][title]" value="{{ $row['title'] ?? '' }}" placeholder="Positive Clamp">
                          </div>
                          <div>
                            <span class="repeater-sub-label">Harga</span>
                            <div class="currency-input currency-input-small" data-currency-field data-currency-scale="catalog">
                              <input type="hidden" name="{{ $fieldName }}[{{ $index }}][price]" value="{{ $row['price'] ?? '' }}" data-currency-hidden>
                              <input type="text" class="form-control currency-input-display" value="" placeholder="Rp0" data-currency-display autocomplete="off">
                            </div>
                          </div>
                          <div>
                            <span class="repeater-sub-label">Harga Promo</span>
                            <div class="currency-input currency-input-small" data-currency-field data-currency-scale="catalog">
                              <input type="hidden" name="{{ $fieldName }}[{{ $index }}][sale_price]" value="{{ $row['sale_price'] ?? '' }}" data-currency-hidden>
                              <input type="text" class="form-control currency-input-display" value="" placeholder="Rp0" data-currency-display autocomplete="off">
                            </div>
                          </div>
                          <div>
                            <span class="repeater-sub-label">Stok</span>
                            <input type="number" class="form-control" name="{{ $fieldName }}[{{ $index }}][inventory]" value="{{ $row['inventory'] ?? '' }}" placeholder="24">
                          </div>
                        </div>
                        <button type="button" class="repeater-remove" data-repeater-remove>Hapus</button>
                      </div>
                    @endforeach
                  </div>
                  <button type="button" class="repeater-add" data-repeater-add>+ Tambah Variasi</button>
                  <script type="text/template" data-repeater-template>
                    <div class="repeater-item repeater-item-variation" data-repeater-item>
                      <div class="repeater-item-grid repeater-item-grid-4">
                        <div>
                          <span class="repeater-sub-label">Nama Varian</span>
                          <input type="text" class="form-control" name="{{ $fieldName }}[__INDEX__][title]" value="" placeholder="Positive Clamp">
                        </div>
                        <div>
                          <span class="repeater-sub-label">Harga</span>
                          <div class="currency-input currency-input-small" data-currency-field data-currency-scale="catalog">
                            <input type="hidden" name="{{ $fieldName }}[__INDEX__][price]" value="" data-currency-hidden>
                            <input type="text" class="form-control currency-input-display" value="" placeholder="Rp0" data-currency-display autocomplete="off">
                          </div>
                        </div>
                        <div>
                          <span class="repeater-sub-label">Harga Promo</span>
                          <div class="currency-input currency-input-small" data-currency-field data-currency-scale="catalog">
                            <input type="hidden" name="{{ $fieldName }}[__INDEX__][sale_price]" value="" data-currency-hidden>
                            <input type="text" class="form-control currency-input-display" value="" placeholder="Rp0" data-currency-display autocomplete="off">
                          </div>
                        </div>
                        <div>
                          <span class="repeater-sub-label">Stok</span>
                          <input type="number" class="form-control" name="{{ $fieldName }}[__INDEX__][inventory]" value="" placeholder="24">
                        </div>
                      </div>
                      <button type="button" class="repeater-remove" data-repeater-remove>Hapus</button>
                    </div>
                  </script>
                </div>
              @elseif ($fieldType === 'image_repeater')
                @php($rows = is_array($value) && count($value) ? array_values($value) : [['image_path' => '', 'alt_text' => '', 'image_file' => null]])
                <div class="repeater-field repeater-field-images" data-repeater data-repeater-name="{{ $fieldName }}">
                  <div class="repeater-list" data-repeater-list>
                    @foreach ($rows as $index => $row)
                      @php($previewPath = $row['image_path'] ?? '')
                      @php($previewSrc = preg_match('/^https?:\/\//', $previewPath) ? $previewPath : ($previewPath !== '' ? asset($previewPath) : ''))
                      <div class="repeater-item repeater-item-image" data-repeater-item>
                        <div class="repeater-item-grid repeater-item-grid-2 repeater-item-grid-image">
                          <div class="image-preview-card" data-image-preview-shell>
                            <div class="image-preview-frame">
                              <img src="{{ $previewSrc }}" alt="{{ $row['alt_text'] ?? 'Preview gambar' }}" data-image-preview @if ($previewSrc === '') style="display:none;" @endif>
                              <div class="image-preview-placeholder" data-image-placeholder @if ($previewSrc !== '') style="display:none;" @endif>Preview gambar akan muncul di sini</div>
                            </div>
                            <small class="image-preview-meta" data-image-preview-meta>{{ $previewPath !== '' ? $previewPath : 'Belum ada gambar dipilih.' }}</small>
                          </div>
                          <div class="image-editor-card">
                            <div>
                              <span class="repeater-sub-label">Path / URL Gambar</span>
                              <input type="text" class="form-control" name="{{ $fieldName }}[{{ $index }}][image_path]" value="{{ $previewPath }}" placeholder="theme/img/products/item.jpg" data-image-path-input>
                            </div>
                            <div>
                              <span class="repeater-sub-label">Upload Foto</span>
                              <label class="image-upload-control">
                                <input type="file" name="{{ $fieldName }}[{{ $index }}][image_file]" accept="image/*" data-image-file-input>
                                <span>Pilih file gambar</span>
                              </label>
                            </div>
                            <div>
                              <span class="repeater-sub-label">Alt Text</span>
                              <input type="text" class="form-control" name="{{ $fieldName }}[{{ $index }}][alt_text]" value="{{ $row['alt_text'] ?? '' }}" placeholder="Deskripsi gambar">
                            </div>
                          </div>
                        </div>
                        <button type="button" class="repeater-remove" data-repeater-remove>Hapus</button>
                      </div>
                    @endforeach
                  </div>
                  <button type="button" class="repeater-add" data-repeater-add>+ Tambah Gambar</button>
                  <script type="text/template" data-repeater-template>
                    <div class="repeater-item repeater-item-image" data-repeater-item>
                      <div class="repeater-item-grid repeater-item-grid-2 repeater-item-grid-image">
                        <div class="image-preview-card" data-image-preview-shell>
                          <div class="image-preview-frame">
                            <img src="" alt="Preview gambar" data-image-preview style="display:none;">
                            <div class="image-preview-placeholder" data-image-placeholder>Preview gambar akan muncul di sini</div>
                          </div>
                          <small class="image-preview-meta" data-image-preview-meta>Belum ada gambar dipilih.</small>
                        </div>
                        <div class="image-editor-card">
                          <div>
                            <span class="repeater-sub-label">Path / URL Gambar</span>
                            <input type="text" class="form-control" name="{{ $fieldName }}[__INDEX__][image_path]" value="" placeholder="theme/img/products/item.jpg" data-image-path-input>
                          </div>
                          <div>
                            <span class="repeater-sub-label">Upload Foto</span>
                            <label class="image-upload-control">
                              <input type="file" name="{{ $fieldName }}[__INDEX__][image_file]" accept="image/*" data-image-file-input>
                              <span>Pilih file gambar</span>
                            </label>
                          </div>
                          <div>
                            <span class="repeater-sub-label">Alt Text</span>
                            <input type="text" class="form-control" name="{{ $fieldName }}[__INDEX__][alt_text]" value="" placeholder="Deskripsi gambar">
                          </div>
                        </div>
                      </div>
                      <button type="button" class="repeater-remove" data-repeater-remove>Hapus</button>
                    </div>
                  </script>
                </div>
              @else
                <input id="{{ $fieldId }}" type="{{ $fieldType }}" name="{{ $fieldName }}" value="{{ $value }}" class="form-control" placeholder="{{ $placeholder }}" @if(isset($field['step'])) step="{{ $field['step'] }}" @endif>
              @endif

              @foreach (($errorsBag->get($fieldName) ?? []) as $message)
                <p class="text-danger">{{ $message }}</p>
              @endforeach
              @if (! empty($field['help_text']))
                <p class="help-block">{{ $field['help_text'] }}</p>
              @endif
            </div>
          @endforeach
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
          <button type="submit" class="btn btn-primary">{{ $mode === 'create' ? 'Save' : 'Update' }}</button>
        </div>
      </form>
    </div>
  </div>
</div>
