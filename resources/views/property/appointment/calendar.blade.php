<x-app-layout>
{{--
    Appointment console — "Glass Studio".

    The whole screen is the calendar: one floating glass toolbar carries the date
    nav, the filters and the view switch, and clicking an appointment opens a
    popover pinned to the event itself rather than a modal over the grid. The
    shell is sized to the viewport at runtime (the theme's header height is not a
    constant we can hard-code), so the page itself never scrolls.
--}}
<div class="container-fluid py-2 apx apx-console">
    <x-property-appointment.premium />
    <link rel="stylesheet" href="{{ https_asset('assets/vendors/fullcalendar/fullcalendar.min.css') }}">

    <style>
        /* the console owns the viewport on this page */
        #content:has(.apx-console) > footer{ display:none; }

        .apx .apxc{
            position:relative; height:calc(100dvh - 200px); min-height:460px; overflow:hidden;
            background:var(--surface); border:1px solid var(--border); border-radius:var(--r-xl);
            box-shadow:var(--shadow-md);
        }
        .apx .apxc-cal{ height:100%; padding:70px 12px 12px; }

        /* ── floating glass toolbar ─────────────────────────────────── */
        .apx .apxc-bar{
            position:absolute; inset-inline:12px; top:11px; z-index:20;
            display:flex; align-items:center; gap:9px; flex-wrap:wrap;
            padding:9px 12px; border-radius:14px;
            background:color-mix(in srgb, var(--surface), transparent 10%);
            border:1px solid var(--border); backdrop-filter:blur(14px); -webkit-backdrop-filter:blur(14px);
            box-shadow:var(--shadow-md);
        }
        .apx .apxc-nav{ display:inline-flex; gap:4px; flex:none; }
        .apx .apxc-nav button{
            width:30px; height:30px; border-radius:9px; cursor:pointer; font-family:inherit;
            border:1px solid var(--border-strong); background:var(--surface); color:var(--text-2);
        }
        .apx .apxc-nav button:hover{ background:var(--surface-2); color:var(--text); }
        .apx .apxc-title{ font-size:14px; font-weight:800; letter-spacing:-.025em; white-space:nowrap; }
        /* salesman picker — a searchable TomSelect rather than a chip per
           employee, because the chip row stops working the moment the team
           outgrows a handful of people */
        .apx .apxc-right{ flex:1 1 auto; justify-content:flex-end; }
        .apx .apxc-bar .ts-wrapper{ flex:1 1 220px; min-width:170px; max-width:none; }
        .apx .apxc-bar .ts-control{
            background:var(--surface); border:1px solid var(--border-strong); border-radius:9px;
            min-height:32px; padding:5px 26px 5px 10px; font-size:11.5px; font-weight:650; color:var(--text);
            box-shadow:none; gap:6px;
        }
        .apx .apxc-bar .ts-wrapper.focus .ts-control{ border-color:var(--brand); box-shadow:0 0 0 3px rgba(var(--brand-rgb),.16); }
        .apx .apxc-bar .ts-control input{ font-size:11.5px; color:var(--text); }
        .apx .apxc-bar .ts-control input::placeholder{ color:var(--text-3); }
        .apx .ts-dropdown{
            background:var(--surface); border:1px solid var(--border); border-radius:var(--r-md);
            box-shadow:var(--shadow-lg); color:var(--text); margin-top:5px; overflow:hidden; z-index:60;
        }
        .apx .ts-dropdown .ts-dropdown-content{ max-height:262px; }
        .apx .ts-dropdown .option{ font-size:12px; padding:8px 11px; color:var(--text-2); }
        .apx .ts-dropdown .option.active{ background:rgba(var(--brand-rgb),.12); color:var(--text); }
        .apx .ts-dropdown .no-results, .apx .ts-dropdown .optgroup-header{ font-size:11px; color:var(--text-3); padding:8px 11px; }
        .apx .apxc-opt{ display:flex; align-items:center; gap:8px; min-width:0; }
        .apx .apxc-opt .nm{ overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .apx .apxc-opt .apx-avatar{ width:19px; height:19px; font-size:8px; }
        .apx .apxc-bar .ts-control .clear-button{
            color:var(--text-3); opacity:.75; inset-inline-end:22px; background:transparent;
        }
        .apx .apxc-bar .ts-control .clear-button:hover{ color:var(--text); opacity:1; }
        .apx .apxc-right{ display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-inline-start:auto; }
        .apx .apxc-bar #pvStatusFilter{ flex:none; }
        .apx .apxc-seg{ display:inline-flex; background:var(--surface-3); border-radius:10px; padding:3px; gap:2px; }
        .apx .apxc-seg button{
            border:0; background:none; font-family:inherit; cursor:pointer;
            font-size:11px; font-weight:750; color:var(--text-3); padding:6px 11px; border-radius:8px;
        }
        .apx .apxc-seg button.on{ background:var(--surface); color:var(--text); box-shadow:var(--shadow-sm); }

        /* ── floating legend ────────────────────────────────────────── */
        .apx .apxc-legend{
            position:absolute; inset-inline-start:16px; bottom:14px; z-index:15;
            display:flex; gap:12px; align-items:center; flex-wrap:wrap;
            padding:7px 12px; border-radius:999px; font-size:10.5px; color:var(--text-3);
            background:color-mix(in srgb, var(--surface), transparent 12%);
            border:1px solid var(--border); backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px);
        }
        .apx .apxc-legend span{ display:inline-flex; align-items:center; gap:6px; }
        .apx .apxc-legend i{ width:10px; height:10px; border-radius:3px; display:inline-block; }

        /* ── event popover ──────────────────────────────────────────── */
        .apx .apxc-pop{
            position:absolute; z-index:40; width:300px; border-radius:var(--r-lg); overflow:hidden;
            background:var(--surface); border:1px solid var(--border);
            box-shadow:0 26px 54px -22px rgba(16,24,40,.5); display:none;
        }
        [data-bs-theme="dark"] .apx .apxc-pop{ box-shadow:0 26px 54px -20px rgba(0,0,0,.75); }
        .apx .apxc-pop.open{ display:block; }
        .apx .apxc-pop .pop-h{ padding:13px 15px; border-bottom:1px solid var(--border); }
        .apx .apxc-pop .pop-h .nm{ font-size:14.5px; font-weight:800; letter-spacing:-.025em; padding-inline-end:22px; }
        .apx .apxc-pop .pop-h .wh{ font-size:11.5px; color:var(--text-2); margin-top:3px; }
        .apx .apxc-pop .pop-x{
            position:absolute; inset-inline-end:9px; top:9px; width:24px; height:24px; border-radius:7px;
            border:0; background:none; color:var(--text-3); cursor:pointer; font-size:13px;
        }
        .apx .apxc-pop .pop-x:hover{ background:var(--surface-3); color:var(--text); }
        .apx .apxc-pop .pop-b{ padding:5px 15px 11px; }
        .apx .apxc-pop .pop-f{ padding:11px 15px; border-top:1px solid var(--border); background:var(--surface-2);
            display:flex; gap:7px; }
        .apx .apxc-pop .pop-f .apx-btn-primary{ flex:1; }
        .apx .apxc-scrim{ position:absolute; inset:0; z-index:35; background:rgba(9,13,20,.42); display:none; }
        .apx .apxc-scrim.open{ display:block; }

        @media (max-width:991.98px){
            .apx .apxc{ height:calc(100dvh - 172px); }
            .apx .apxc-legend{ display:none; }
            .apx .apxc-right{ margin-inline-start:0; }
            .apx .apxc-title{ font-size:13px; }
        }
        @media (max-width:767.98px){
            /* the popover becomes a bottom sheet — a 300px card anchored to a
               thumb-sized event is unusable on a phone */
            .apx .apxc-pop.open{
                inset:auto 0 0 0 !important; width:auto; border-radius:var(--r-xl) var(--r-xl) 0 0;
                max-height:82%; display:flex; flex-direction:column;
            }
            .apx .apxc-pop .pop-b{ overflow:auto; }
            .apx .apxc-cal{ padding-inline:6px; padding-bottom:6px; }
        }
    </style>

    <div class="apxc" id="apxConsole">
        <div class="apxc-bar" id="pvBar">
            <span class="apxc-nav">
                <button type="button" data-act="prev" aria-label="Previous"><i class="fa fa-angle-left"></i></button>
                <button type="button" data-act="next" aria-label="Next"><i class="fa fa-angle-right"></i></button>
            </span>
            <span class="apxc-title" id="pvTitle">&nbsp;</span>
            <button type="button" class="apx-btn apx-btn-ghost" data-act="today">Today</button>

            <span class="apxc-right">
                {{-- Salesmen who already hold appointments are rendered up front so
                     the common choice needs no request; focusing the field loads
                     every employee from the shared users list endpoint. --}}
                <select id="pvSalesmanFilter" aria-label="Salesman" placeholder="All salesmen">
                    @foreach ($salesmen as $salesman)
                        <option value="{{ $salesman->id }}">{{ $salesman->name }}</option>
                    @endforeach
                </select>

                <select class="apx-select" id="pvStatusFilter" aria-label="Status">
                    <option value="">All statuses</option>
                    <option value="scheduled">Confirmed</option>
                    <option value="awaiting">Awaiting</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="no_show">No-show</option>
                </select>
                <span class="apxc-seg" id="pvViews">
                    <button type="button" data-view="timeGridDay">Day</button>
                    <button type="button" data-view="timeGridWeek">Week</button>
                    <button type="button" data-view="dayGridMonth">Month</button>
                    <button type="button" data-view="multiMonthYear">Year</button>
                </span>
                <a href="{{ route('property::sale::appointment_schedule::index') }}"
                   class="apx-btn apx-btn-ghost" title="List view"><i class="fa fa-list-ul"></i></a>
            </span>
        </div>

        <div class="apxc-cal" id="pvCalWrap"><div id="pvCalendar" style="height:100%"></div></div>

        <div class="apxc-legend">
            <span><i style="background:var(--brand)"></i> Confirmed</span>
            <span><i style="background:var(--warning)"></i> Awaiting</span>
            <span><i style="background:var(--success)"></i> Completed</span>
            <span><i style="background:var(--text-3)"></i> Cancelled</span>
            <span><i style="background:var(--danger)"></i> No-show</span>
        </div>

        <div class="apxc-scrim" id="pvScrim"></div>
        <div class="apxc-pop" id="pvPop" role="dialog" aria-label="Appointment details">
            <div class="pop-h">
                <button type="button" class="pop-x" id="pvPopClose" aria-label="Close"><i class="fa fa-times"></i></button>
                <div class="nm" id="pvPopName"></div>
                <div class="wh" id="pvPopWhen"></div>
                <div class="mt-2" id="pvPopChip"></div>
            </div>
            <div class="pop-b" id="pvPopBody"></div>
            <div class="pop-f">
                <a href="#" class="apx-btn apx-btn-primary" id="pvPopOpen"><i class="fa fa-external-link"></i> <span>Open</span></a>
                <a href="#" class="apx-btn apx-btn-ghost" id="pvPopCall" title="Call customer"><i class="fa fa-phone"></i></a>
                <button type="button" class="apx-btn apx-btn-ghost" id="pvPopCopy" title="Copy booking link"><i class="fa fa-clipboard"></i></button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ https_asset('assets/vendors/fullcalendar/index.global.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('pvCalendar');
        if (!el || typeof FullCalendar === 'undefined') return;

        const shell = document.getElementById('apxConsole');
        const bar = document.getElementById('pvBar');
        const wrap = document.getElementById('pvCalWrap');
        const pop = document.getElementById('pvPop');
        const scrim = document.getElementById('pvScrim');
        const phone = () => window.matchMedia('(max-width: 767.98px)').matches;

        let salesmanId = '';
        let status = '';
        let picked = null;

        /* The theme's header height is not a constant we can hard-code, so the
           shell is measured against the viewport and the toolbar's real height
           decides how far down the grid starts. */
        function fit() {
            const top = shell.getBoundingClientRect().top;
            shell.style.height = Math.max(420, window.innerHeight - top - 14) + 'px';
            wrap.style.paddingTop = (bar.offsetHeight + 20) + 'px';
            calendar.updateSize();
        }

        const calendar = new FullCalendar.Calendar(el, {
            initialView: phone() ? 'timeGridDay' : 'timeGridWeek',
            headerToolbar: false,
            height: '100%',
            expandRows: true,
            nowIndicator: true,
            stickyHeaderDates: true,
            allDaySlot: false,
            dayMaxEvents: true,
            multiMonthMaxColumns: 4,
            slotMinTime: '07:00:00',
            slotMaxTime: '22:00:00',
            scrollTime: '08:00:00',
            // 12-hour clock with AM/PM, matching the rest of the scheduler.
            eventTimeFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'short' },
            slotLabelFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'short' },
            listDayAltFormat: false,
            eventDisplay: 'block',
            datesSet: function (info) {
                document.getElementById('pvTitle').textContent = info.view.title;
                closePop();
            },
            events: function (info, success, failure) {
                const params = new URLSearchParams({ start: info.startStr, end: info.endStr });
                if (salesmanId) params.append('salesman_id[]', salesmanId);
                if (status) params.append('status', status);

                fetch('{{ route('property::sale::appointment_schedule::calendar.data') }}?' + params.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                })
                    .then(r => r.json())
                    .then(success)
                    .catch(failure);
            },
            eventDidMount: function (info) {
                const p = info.event.extendedProps;
                info.el.setAttribute('title', `${p.reference_no} — ${p.customer || ''}\n${p.status_label}`);
            },
            eventClick: function (info) {
                info.jsEvent.preventDefault();
                openPop(info);
            },
        });

        calendar.render();
        fit();
        window.addEventListener('resize', fit);

        /* ── detail popover ─────────────────────────────────────────── */
        function row(label, value) {
            return value ? `<div class="apx-kv"><div class="k">${label}</div><div class="v">${value}</div></div>` : '';
        }

        function openPop(info) {
            const p = info.event.extendedProps;

            if (picked) picked.classList.remove('picked');
            picked = info.el;
            picked.classList.add('picked');

            document.getElementById('pvPopName').textContent = p.customer || 'Appointment';
            document.getElementById('pvPopWhen').textContent = [p.long_date, p.time_range].filter(Boolean).join(' · ');
            document.getElementById('pvPopChip').innerHTML =
                `<span class="apx-chip chip-${p.status}"><span class="dot"></span>${p.status_label}</span>`;
            document.getElementById('pvPopBody').innerHTML =
                row('Reference', p.reference_no) +
                row('Property', [p.property, p.building].filter(Boolean).join(', ')) +
                row('Salesman', p.salesman) +
                row('Phone', p.customer_phone) +
                row('Booked', p.booked);

            const open = document.getElementById('pvPopOpen');
            open.style.display = p.agreement_url ? '' : 'none';
            if (p.agreement_url) {
                open.href = p.agreement_url;
                open.querySelector('span').textContent = p.agreement_label;
            }

            const call = document.getElementById('pvPopCall');
            call.style.display = p.customer_phone ? '' : 'none';
            if (p.customer_phone) call.href = 'tel:' + p.customer_phone;

            const copy = document.getElementById('pvPopCopy');
            copy.style.display = p.booking_url ? '' : 'none';
            copy.dataset.url = p.booking_url || '';

            pop.classList.add('open');
            scrim.classList.toggle('open', phone());
            position(info.el);
        }

        /* Anchor beside the event, flipping and clamping so the card is always
           whole and inside the console. Phones ignore this — CSS docks it. */
        function position(target) {
            if (phone()) { pop.style.left = pop.style.top = ''; return; }

            const frame = shell.getBoundingClientRect();
            const r = target.getBoundingClientRect();
            const w = pop.offsetWidth;
            const h = pop.offsetHeight;

            let left = r.right - frame.left + 10;
            if (left + w > frame.width - 8) left = r.left - frame.left - w - 10;
            left = Math.max(8, Math.min(left, frame.width - w - 8));

            let top = r.top - frame.top - 8;
            top = Math.max(bar.offsetHeight + 18, Math.min(top, frame.height - h - 10));

            pop.style.left = left + 'px';
            pop.style.top = top + 'px';
        }

        function closePop() {
            pop.classList.remove('open');
            scrim.classList.remove('open');
            if (picked) { picked.classList.remove('picked'); picked = null; }
        }

        document.getElementById('pvPopClose').addEventListener('click', closePop);
        scrim.addEventListener('click', closePop);
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closePop(); });
        shell.addEventListener('mousedown', function (e) {
            if (!pop.classList.contains('open')) return;
            if (pop.contains(e.target) || e.target.closest('.fc-event')) return;
            closePop();
        });

        document.getElementById('pvPopCopy').addEventListener('click', function () {
            if (!this.dataset.url) return;
            navigator.clipboard.writeText(this.dataset.url).then(function () {
                if (window.toastr) toastr.success('Booking link copied');
            });
        });

        /* ── toolbar ────────────────────────────────────────────────── */
        document.querySelectorAll('.apxc-bar [data-act]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                closePop();
                if (this.dataset.act === 'prev') calendar.prev();
                if (this.dataset.act === 'next') calendar.next();
                if (this.dataset.act === 'today') calendar.today();
            });
        });

        const views = document.getElementById('pvViews');
        function markView(name) {
            views.querySelectorAll('button').forEach(b => b.classList.toggle('on', b.dataset.view === name));
        }
        views.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-view]');
            if (!btn) return;
            closePop();
            calendar.changeView(btn.dataset.view);
            markView(btn.dataset.view);
        });
        markView(calendar.view.type);

        /* Salesman picker. The team will outgrow a chip row, so this searches
           the whole employee list server-side through the same endpoint every
           other employee select in the app uses. */
        function tint(id) {
            let h = 0;
            String(id).split('').forEach(c => { h = (h * 31 + c.charCodeAt(0)) % 360; });
            return `hsl(${h}, 52%, 42%)`;
        }
        function initials(name) {
            return (name || '?').trim().substring(0, 2).toUpperCase();
        }

        function renderRow(item, escape) {
            const label = escape(item.name || item.text || '');
            return `<div class="apxc-opt"><span class="apx-avatar" style="background:${tint(item.id)}">` +
                `${escape(initials(item.name))}</span><span class="nm">${label}</span></div>`;
        }

        let employeesLoaded = false;
        const salesmanPicker = new TomSelect('#pvSalesmanFilter', {
            valueField: 'id',
            labelField: 'name',
            searchField: ['name', 'mobile', 'email'],
            persist: false,
            maxOptions: 200,
            sortField: [{ field: 'name', direction: 'asc' }],
            // Clearing the field IS "all salesmen" — an "All" row inside the list
            // would sort in among the names as soon as the team grows.
            plugins: ['clear_button'],
            load: function (query, callback) {
                const url = '{{ route('users::list') }}?type=employee&query=' + encodeURIComponent(query);
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.ok ? r.json() : Promise.reject())
                    .then(json => callback(json.items || []))
                    .catch(() => callback());
            },
            onFocus: function () {
                // One eager load so the full list is there before anyone types.
                if (employeesLoaded) return;
                employeesLoaded = true;
                this.load('');
            },
            render: {
                option: renderRow,
                item: renderRow,
            },
            onChange: function (value) {
                salesmanId = value || '';
                closePop();
                calendar.refetchEvents();
            },
        });

        document.getElementById('pvStatusFilter').addEventListener('change', function () {
            status = this.value;
            closePop();
            calendar.refetchEvents();
        });
    });
</script>
@endpush
</x-app-layout>
