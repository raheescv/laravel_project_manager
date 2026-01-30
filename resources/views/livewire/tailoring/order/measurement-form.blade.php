<div class="measurement-form-container">
    @if(!$categoryId)
        <div class="card border-0 shadow-sm text-center py-5" style="border-radius: 1.25rem;">
            <div class="card-body">
                <i class="fa fa-info-circle fs-1 text-info opacity-50 mb-3"></i>
                <h5 class="text-dark">No Category Selected</h5>
                <p class="text-muted">Please select a tailoring category to view measurement dimensions.</p>
            </div>
        </div>
    @else
        <div class="row g-4">
            @foreach($sections as $sectionKey => $section)
                @if(count($section['fields']) > 0)
                    <div class="{{ $sectionKey == 'specifications' ? 'col-12' : 'col-md-6' }}">
                        <div class="card border-0 shadow-sm h-100" style="border-radius: 1.25rem;">
                            <div class="card-header bg-white border-0 py-4 px-4 d-flex align-items-center gap-3">
                                <div class="icon-box bg-{{ $section['color'] }}-subtle text-{{ $section['color'] }} rounded-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                                    <i class="{{ $section['icon'] }} fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark letter-spacing-1" style="font-size: 0.9rem;">{{ $section['label'] }}</h6>
                                    @if($sectionKey == 'basic_body' && $category)
                                        <p class="text-muted small mb-0">{{ strtoupper($category->name) }} DIMENSIONS AND FITS</p>
                                    @endif
                                </div>
                            </div>
                            <div class="card-body px-4 pb-4 pt-0">
                                <div class="row g-3">
                                    @foreach($section['fields'] as $field)
                                        <div class="{{ $sectionKey == 'specifications' ? 'col-md-2' : 'col-md-6' }} {{ $sectionKey == 'specifications' && ($field['field_key'] == 'tailoring_notes' || $field['field_key'] == 'stitching' || $field['field_key'] == 'button') ? 'col-md-2' : '' }}">
                                            <label class="form-label fw-bold text-muted mb-1" style="font-size: 0.7rem; letter-spacing: 0.02rem;">{{ strtoupper($field['label']) }}</label>
                                            
                                            @if($field['field_type'] == 'select')
                                                <div class="input-group">
                                                    <select wire:model.live="form.{{ $field['field_key'] }}" class="form-select border-light-subtle bg-light-subtle rounded-3 py-2 px-3" style="font-size: 0.85rem;">
                                                        <option value="">Select {{ $field['label'] }}</option>
                                                        @foreach($options[$field['options_source']] ?? [] as $id => $val)
                                                            <option value="{{ $id }}">{{ $val }}</option>
                                                        @endforeach
                                                    </select>
                                                    @if($field['options_source'] != 'category_models')
                                                        <button class="btn btn-outline-light-subtle border-light-subtle text-primary bg-white px-2 rounded-end-3" type="button" title="Add New">
                                                            <i class="fa fa-plus fs-6"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            @else
                                                <input type="text" wire:model.live="form.{{ $field['field_key'] }}" 
                                                       class="form-control border-light-subtle bg-light-subtle rounded-3 py-2 px-3" 
                                                       style="font-size: 0.85rem;"
                                                       placeholder="{{ str_contains(strtolower($field['label']), 'size') || str_contains(strtolower($field['label']), 'no') || str_contains(strtolower($field['label']), 'notes') ? '...' : '0.00' }}">
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    <style>
        .letter-spacing-1 { letter-spacing: 0.05rem; }
        .bg-light-subtle { background-color: #f3f7f9 !important; }
        .border-light-subtle { border-color: #e2e8f0 !important; }
        .measurement-form-container .form-select:focus, 
        .measurement-form-container .form-control:focus {
            background-color: #fff !important;
            border-color: #4e73df !important;
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.1);
        }
    </style>
</div>
