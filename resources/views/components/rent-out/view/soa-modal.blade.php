{{--
    Reusable Statement-of-Account date-range modal (SOA Statement + Utilities SOA).
    Submitting opens "{route}/{fromDate}/{toDate}" in a new tab — handled by the
    shared .rv-soa-form script in the parent view, so no per-modal JS is needed.

    Usage:
        <x-rent-out.view.soa-modal id="SOAStatementModal" formId="SOAStatementForm"
            title="SOA Statement" icon="fa-calendar"
            :route="route('print::rentout::statement', $rentOut->id)"
            hint="Select the date range for the SOA Statement." />
--}}
@props([
    'id',
    'formId',
    'title',
    'route',
    'icon' => 'fa-calendar',
    'hint' => 'Select the date range to generate the statement.',
])

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header rv-modal-header border-0 py-2 px-3">
                <h6 class="modal-title text-white fw-bold mb-0 rv-modal-title" id="{{ $id }}Label">
                    <i class="fa {{ $icon }} me-1"></i>{{ $title }}
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="{{ $formId }}" class="rv-soa-form" data-soa-route="{{ $route }}" data-soa-modal="{{ $id }}">
                <div class="modal-body px-3 py-3">
                    <div class="alert alert-info py-2 px-2 mb-3" style="font-size:.78rem;">
                        <i class="fa fa-info-circle me-1"></i>{{ $hint }}
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">From Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm rv-soa-from" value="{{ date('Y-m-01') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">To Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm rv-soa-to" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-3 py-2">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal"><i class="fa fa-times me-1"></i>Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-print me-1"></i>Generate</button>
                </div>
            </form>
        </div>
    </div>
</div>
