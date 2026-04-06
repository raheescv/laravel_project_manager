<div>
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-semibold"><i class="fa fa-filter text-primary me-2"></i>Filter Options</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted text-uppercase mb-1"><i class="fa fa-user me-1"></i>Sales Representative</label>
                    <select class="form-select form-select-sm shadow-sm" id="filter_employee_id">
                        <option value="">All Salesman</option>
                        @foreach($salesUsers as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted text-uppercase mb-1"><i class="fa fa-info-circle me-1"></i>Status</label>
                    <select class="form-select form-select-sm shadow-sm" id="filter_status">
                        <option value="">All Statuses</option>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted text-uppercase mb-1"><i class="fa fa-tag me-1"></i>Type</label>
                    <select class="form-select form-select-sm shadow-sm" id="filter_type">
                        <option value="">All Types</option>
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="kpi-icon bg-primary-subtle text-primary me-3">
                        <i class="fa fa-users fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Total Leads This Month</div>
                        <div class="fs-3 fw-bold mb-0">{{ $leadsThisMonth }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="kpi-icon bg-warning-subtle text-warning me-3">
                        <i class="fa fa-clock-o fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Today's Tasks</div>
                        <div class="fs-3 fw-bold mb-0">{{ $todaysTasks }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h5 class="mb-0 fw-semibold"><i class="fa fa-calendar text-primary me-2"></i>Calendar View</h5>
            <div class="d-flex flex-wrap gap-3">
                <span class="d-inline-flex align-items-center small"><span class="legend-dot bg-success me-1"></span>Visit Scheduled</span>
                <span class="d-inline-flex align-items-center small"><span class="legend-dot bg-primary me-1"></span>Follow Up</span>
                <span class="d-inline-flex align-items-center small"><span class="legend-dot bg-warning me-1"></span>Call Back</span>
                <span class="d-inline-flex align-items-center small"><span class="legend-dot bg-danger me-1"></span>Follow Up For Visit</span>
            </div>
        </div>
        <div class="card-body">
            <div id="lead-calendar"></div>
        </div>
    </div>

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
        <style>
            .kpi-icon { width: 60px; height: 60px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; }
            .legend-dot { width: 12px; height: 12px; border-radius: 3px; display: inline-block; }
            #lead-calendar .fc-toolbar-title { font-size: 1.1rem; font-weight: 600; }
            #lead-calendar .fc-button { background: #fff; border-color: #dee2e6; color: #495057; box-shadow: none; }
            #lead-calendar .fc-button-primary:not(:disabled).fc-button-active,
            #lead-calendar .fc-button-primary:hover { background: var(--bs-primary); color: #fff; border-color: var(--bs-primary); }
            #lead-calendar .fc-day-today { background: rgba(var(--bs-primary-rgb), 0.05) !important; }
            .lead-event-visit-scheduled { background-color: #198754 !important; border-color: #198754 !important; color: #fff !important; }
            .lead-event-follow-up { background-color: #0d6efd !important; border-color: #0d6efd !important; color: #fff !important; }
            .lead-event-call-back { background-color: #ffc107 !important; border-color: #ffc107 !important; color: #1f2937 !important; }
            .lead-event-follow-up-visit { background-color: #dc3545 !important; border-color: #dc3545 !important; color: #fff !important; }
            .lead-event-default { background-color: #6c757d !important; border-color: #6c757d !important; color: #fff !important; }
            .fc .fc-event { padding: 3px 6px; font-weight: 500; border-radius: 4px; }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const el = document.getElementById('lead-calendar');
                if (!el) return;
                const calendar = new FullCalendar.Calendar(el, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
                    },
                    height: 'auto',
                    dayMaxEvents: 3,
                    eventDisplay: 'block',
                    events: function (info, success, failure) {
                        const params = new URLSearchParams({
                            employee_id: document.getElementById('filter_employee_id').value || '',
                            status: document.getElementById('filter_status').value || '',
                            type: document.getElementById('filter_type').value || '',
                            start: info.startStr,
                            end: info.endStr,
                        });
                        fetch("{{ route('property::lead::calendar.data') }}?" + params.toString())
                            .then(r => r.json())
                            .then(data => success(data))
                            .catch(err => failure(err));
                    },
                    eventClick: function (info) {
                        if (info.event.url) {
                            info.jsEvent.preventDefault();
                            window.open(info.event.url, '_self');
                        }
                    },
                });
                calendar.render();

                ['filter_employee_id', 'filter_status', 'filter_type'].forEach(function (id) {
                    document.getElementById(id).addEventListener('change', function () { calendar.refetchEvents(); });
                });
            });
        </script>
    @endpush
</div>
