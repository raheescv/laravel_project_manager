<div>
    <div wire:ignore.self class="modal fade" id="TailoringMeasurementEditModal" tabindex="-1" aria-labelledby="TailoringMeasurementEditModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-bottom">
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="TailoringMeasurementEditModalLabel">
                            <i class="fa fa-sliders me-1"></i>Edit Measurements
                        </h5>
                        <div class="small text-secondary fw-semibold mt-1">{{ $measurementModalItemTitle ?: 'Select item' }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-body-tertiary p-4">
                    @if (!empty($measurementModalMeta))
                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <span class="badge text-dark bg-white border border-secondary-subtle rounded-pill px-3 py-2"><i class="fa fa-folder-open-o me-1"></i>{{ $measurementModalMeta['category_name'] ?? 'Category' }}</span>
                            <span class="badge text-dark bg-white border border-secondary-subtle rounded-pill px-3 py-2"><i class="fa fa-object-group me-1"></i>{{ $measurementModalMeta['model_name'] ?? 'Model' }}</span>
                            @if (!empty($measurementModalMeta['model_type_name']))
                                <span class="badge text-dark bg-white border border-secondary-subtle rounded-pill px-3 py-2"><i class="fa fa-code-fork me-1"></i>{{ $measurementModalMeta['model_type_name'] }}</span>
                            @endif
                        </div>
                    @endif

                    @php
                        $modalSectionLabels = [
                            'basic_body' => ['label' => 'Dimensions', 'icon' => 'fa-arrows-h'],
                            'collar_cuff' => ['label' => 'Components', 'icon' => 'fa-puzzle-piece'],
                            'specifications' => ['label' => 'Styles & Models', 'icon' => 'fa-cut'],
                        ];
                    @endphp

                    <div class="row g-4">
                        @forelse ($measurementModalSections as $sectionId => $fields)
                            @php $sectionMeta = $modalSectionLabels[$sectionId] ?? ['label' => ucwords(str_replace('_', ' ', $sectionId)), 'icon' => 'fa-list']; @endphp
                            <div class="col-6">
                                <div class="card border shadow-sm">
                                    <div class="card-header bg-white fw-bold fs-5">
                                        <i class="fa {{ $sectionMeta['icon'] }} me-1"></i>{{ $sectionMeta['label'] }}
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            @foreach ($fields as $field)
                                                <div class="col-md-6">
                                                    <label class="form-label text-uppercase text-secondary fw-bold small mb-1">{{ $field['label'] }}</label>
                                                    @if (($field['field_type'] ?? 'text') === 'select')
                                                        <select class="form-select"
                                                            wire:model.defer="measurementModalForm.{{ $field['field_key'] }}">
                                                            <option value="">Select {{ $field['label'] }}</option>
                                                            @foreach (($measurementModalOptions[$field['field_key']] ?? []) as $optionValue => $optionLabel)
                                                                <option value="{{ $optionLabel }}">{{ $optionLabel }}</option>
                                                            @endforeach
                                                        </select>
                                                    @else
                                                        <input type="text" class="form-control"
                                                            wire:model.defer="measurementModalForm.{{ $field['field_key'] }}"
                                                            placeholder="Enter {{ strtolower($field['label']) }}">
                                                    @endif
                                                    @error('measurementModalForm.' . $field['field_key'])
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-secondary mb-0">No measurements available for editing.</div>
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-4">
                        <label class="form-label text-uppercase text-secondary fw-bold small mb-1"><i class="fa fa-sticky-note-o me-1"></i>Special Instructions</label>
                        <textarea class="form-control" rows="3" wire:model.defer="measurementModalNotes"
                            placeholder="Add notes for this item"></textarea>
                        @error('measurementModalNotes')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer bg-white border-top">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary px-4" wire:click="saveMeasurementModal">
                        <i class="fa fa-save me-1"></i>Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('tailoring-measurement-modal-open', () => {
            const modalEl = document.getElementById('TailoringMeasurementEditModal');
            if (!modalEl) return;
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });

        window.addEventListener('tailoring-measurement-modal-close', () => {
            const modalEl = document.getElementById('TailoringMeasurementEditModal');
            if (!modalEl) return;
            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
        });
    </script>
</div>
