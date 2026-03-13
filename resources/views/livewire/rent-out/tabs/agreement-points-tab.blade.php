<div>
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-semibold mb-0">
            <i class="fa fa-file-text-o me-2 text-primary"></i>Reservation Fees & Disclaimer
        </h6>
        <div class="d-flex gap-2">
            <button type="button" wire:click="addPoint"
                class="btn btn-sm btn-primary d-flex align-items-center shadow-sm">
                <i class="fa fa-plus me-1"></i> Add Point
            </button>
            <button type="button" wire:click="save"
                class="btn btn-sm btn-success d-flex align-items-center shadow-sm">
                <i class="fa fa-save me-1"></i> Save
            </button>
        </div>
    </div>

    @if (count($points_en) > 0)
        <div class="table-responsive">
            <table class="table table-hover align-middle border-bottom mb-0 table-sm">
                <thead class="bg-light text-muted">
                    <tr>
                        <th class="text-center" style="width: 45px;">#</th>
                        <th>English</th>
                        <th>Arabic</th>
                        <th class="text-center" style="width: 60px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($points_en as $index => $point_en)
                        <tr wire:key="point-{{ $index }}">
                            <td class="text-center">
                                <span class="badge rounded-circle d-inline-flex align-items-center justify-content-center bg-primary"
                                    style="width: 24px; height: 24px; font-size: .7rem;">
                                    {{ $index + 1 }}
                                </span>
                            </td>
                            <td>
                                <textarea wire:model="points_en.{{ $index }}"
                                    class="form-control form-control-sm border-secondary-subtle shadow-sm"
                                    rows="2" placeholder="Enter point in English..."></textarea>
                            </td>
                            <td>
                                <textarea wire:model="points_ar.{{ $index }}"
                                    class="form-control form-control-sm border-secondary-subtle shadow-sm"
                                    rows="2" dir="rtl" placeholder="أدخل النقطة بالعربية..."></textarea>
                            </td>
                            <td class="text-center">
                                @if (count($points_en) > 1)
                                    <button type="button" wire:click="removePoint({{ $index }})"
                                        class="btn btn-sm btn-outline-danger" title="Remove"
                                        data-bs-toggle="tooltip">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center py-5 text-muted">
            <i class="fa fa-file-text-o d-block mb-2" style="font-size: 2.5rem; opacity: .3;"></i>
            <p class="mb-1 small">No agreement points yet.</p>
            <p class="mb-0 small text-muted">Click <strong>"Add Point"</strong> to get started.</p>
        </div>
    @endif
</div>
