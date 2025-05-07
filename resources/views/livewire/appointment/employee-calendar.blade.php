<div>
    <style>
        /* Calendar Container and Theme */
        .fc {
            --fc-border-color: #e2e8f0;
            --fc-button-bg-color: #4f46e5;
            --fc-button-border-color: #4f46e5;
            --fc-button-hover-bg-color: #4338ca;
            --fc-button-hover-border-color: #4338ca;
            --fc-button-active-bg-color: #3730a3;
            --fc-button-active-border-color: #3730a3;
            --fc-event-bg-color: #818cf8;
            --fc-event-border-color: #818cf8;
            --fc-today-bg-color: rgba(79, 70, 229, 0.06);
            background: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 1rem;
        }

        /* Header and Title */
        .fc .fc-toolbar {
            padding: 1.25rem;
            margin: -1rem -1rem 1rem -1rem;
            background: linear-gradient(to right, #4f46e5, #6366f1);
            border-radius: 1rem 1rem 0 0;
        }

        .fc .fc-toolbar-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ffffff;
            text-transform: capitalize;
        }

        /* Buttons */
        .fc .fc-button {
            padding: 0.625rem 1rem;
            font-weight: 500;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
            text-transform: capitalize;
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .fc .fc-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.12);
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .fc .fc-button-primary:not(:disabled):active,
        .fc .fc-button-primary:not(:disabled).fc-button-active {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.25);
        }

        /* Calendar Grid */
        .fc .fc-scrollgrid {
            border: none;
        }

        .fc .fc-scrollgrid-section-header>td {
            border: none;
        }

        .fc .fc-col-header-cell {
            background-color: #f8fafc;
            padding: 1.25rem 0;
            border-width: 1px;
            border-style: solid;
            border-color: #e2e8f0;
        }

        .fc .fc-col-header-cell-cushion {
            font-weight: 600;
            color: #1e293b;
            text-decoration: none !important;
            padding: 8px 14px;
        }

        /* Time Slots */
        .fc .fc-timegrid-slot {
            height: 3.5em;
            border-bottom: 1px dashed #e2e8f0;
            transition: background-color 0.2s ease;
        }

        .fc .fc-timegrid-slot:hover {
            background-color: rgba(79, 70, 229, 0.02);
        }

        .fc .fc-timegrid-slot-label {
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 500;
        }

        /* Events */
        .fc .fc-event {
            border-radius: 0.5rem;
            padding: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06);
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .fc .fc-event:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .fc .fc-event-main {
            padding: 0.25rem 0;
        }

        .fc .fc-event-time {
            font-weight: 600;
        }

        .fc .fc-event-title {
            font-weight: 500;
        }

        /* Today Column */
        .fc .fc-day-today {
            background-color: var(--fc-today-bg-color) !important;
        }

        .fc .fc-day-today .fc-col-header-cell-cushion {
            color: #4f46e5;
            font-weight: 700;
        }

        /* Time Indicator */
        .fc .fc-timegrid-now-indicator-line {
            border-color: #f43f5e;
            border-width: 2px;
        }

        .fc .fc-timegrid-now-indicator-arrow {
            border-color: #f43f5e;
            border-width: 5px;
        }

        /* Resource Timeline */
        .fc-direction-ltr .fc-resource-timeline-divider {
            background: #f1f5f9;
            width: 3px;
        }

        .fc-resource-timeline .fc-resource-group-header {
            background: #f8fafc;
        }

        /* Responsiveness */
        @media (max-width: 768px) {
            .fc .fc-toolbar {
                flex-direction: column;
                gap: 1rem;
            }

            .fc .fc-toolbar-title {
                font-size: 1.25rem;
            }
        }

        /* Custom Scrollbars */
        .fc-scroller {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f1f5f9;
        }

        .fc-scroller::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .fc-scroller::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }

        .fc-scroller::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 3px;
        }

        .fc-scroller::-webkit-scrollbar-thumb:hover {
            background-color: #94a3b8;
        }

        /* Page Layout Enhancements */
        .card-header {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 1.5rem;
        }

        .card-header label {
            color: #1e293b;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .card-header .form-control {
            border-color: #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.625rem;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .card-header .form-control:hover {
            border-color: #cbd5e1;
        }

        .card-header .form-control:focus {
            border-color: #818cf8;
            box-shadow: 0 0 0 3px rgba(129, 140, 248, 0.1);
        }

        .select2-container--default .select2-selection--multiple {
            border-color: #e2e8f0;
            border-radius: 0.5rem;
            min-height: 42px;
            padding: 3px;
        }

        .select2-container--default .select2-selection--multiple:hover {
            border-color: #cbd5e1;
        }

        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: #818cf8;
            box-shadow: 0 0 0 3px rgba(129, 140, 248, 0.1);
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            padding: 2px 8px;
            font-size: 0.875rem;
        }

        .card-body {
            padding: 1.5rem;
            background-color: #ffffff;
        }

        .gap-4 {
            gap: 1.5rem;
        }

        /* Employee Selection Area */
        .employee-filter-section {
            background: linear-gradient(to right, #f8fafc, #f1f5f9);
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
    </style>
    <div class="card-header">
        <div class="col-lg-12">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="date" class="d-block">Date</label>
                        {{ html()->date('date')->value('')->class('form-control shadow-sm')->id('date')->attribute('wire:model.live', 'date') }}
                    </div>
                </div>
                <div class="col-md-9" wire:ignore>
                    <div class="form-group">
                        <label for="employee_id" class="d-block">Employee</label>
                        {{ html()->select('employee_id', [])->value()->class('select-employee_id-list w-100')->attribute('multiple')->id('employee_id')->placeholder('All') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="d-md-flex gap-4">
            <div class="flex-fill">
                <div id="employee-calendar" wire:ignore></div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            let calendar;
            let appointmentModal;
            document.addEventListener("DOMContentLoaded", () => {
                const today = new Date();
                const currentYear = today.getFullYear();
                const currentMonth = (today.getMonth() + 1).toString().padStart(2, "0");
                const currentDay = today.getDate().toString().padStart(2, "0");

                const events = [{
                    title: 'Sprint Planning',
                    start: `${currentYear}-${currentMonth}-${currentDay}T09:00:00`,
                    end: `${currentYear}-${currentMonth}-${currentDay}T10:30:00`,
                    resourceId: 15,
                    description: 'Weekly sprint planning meeting'
                }];
                calendar = new FullCalendar.Calendar(document.getElementById("employee-calendar"), {
                    titleFormat: {
                        month: 'long',
                        year: 'numeric',
                        day: 'numeric',
                        weekday: 'long'
                    },
                    slotMinTime: "08:00",
                    slotMaxTime: "23:00",
                    slotDuration: '00:15:00',
                    selectable: true,
                    contentHeight: 700,
                    aspectRatio: 1.5,
                    eventResourceEditable: true, // except for between resources
                    customButtons: {
                        @can('appointment.create')
                            myCustomButton: {
                                text: 'Add Appointment',
                                click: function() {
                                    Livewire.dispatch('Create-Appointment-Page-Component');
                                }
                            }
                        @endcan
                    },
                    headerToolbar: {
                        left: 'prev,next,myCustomButton',
                        center: 'title',
                        right: "prev,today,next,resourceTimeGridDay,resourceTimeGridWeek,resourceDayGridMonth,listWeek",
                    },
                    eventResourceEditable: true,

                    timeZone: "local",
                    editable: true,
                    droppable: true,
                    selectable: true,
                    dayMaxEvents: true,
                    expandRows: true,
                    height: 'auto',
                    initialView: 'resourceTimeGridDay',
                    // Enhanced visual configurations
                    eventDisplay: 'block',
                    eventBackgroundColor: '#4f46e5',
                    eventBorderColor: 'transparent',
                    nowIndicator: true,
                    dayMaxEvents: 4,
                    navLinks: true,
                    weekNumbers: true,
                    weekNumberFormat: {
                        week: 'numeric'
                    },
                    eventTimeFormat: {
                        hour: '2-digit',
                        minute: '2-digit',
                        meridiem: false,
                        hour12: false
                    },
                    slotLabelFormat: {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false
                    },
                    views: {
                        resourceTimeGridDay: {
                            type: 'resourceTimeGrid',
                            duration: {
                                days: 1
                            },
                            buttonText: 'Day',
                            slotDuration: '00:15:00',
                            slotLabelInterval: '01:00',
                        },
                        resourceTimeGridWeek: {
                            type: 'resourceTimeGrid',
                            duration: {
                                weeks: 1
                            },
                            buttonText: 'Week',
                            slotDuration: '00:30:00',
                            slotLabelInterval: '01:00',
                        },
                        resourceDayGridMonth: {
                            type: 'resourceDayGridMonth',
                            buttonText: 'Month',
                        },
                        listWeek: {
                            buttonText: 'List'
                        }
                    },
                    resources: function(fetchInfo, successCallback, failureCallback) {
                        console.log(fetchInfo);
                        @this.getResources(fetchInfo).then(events => {
                            successCallback(events);
                        }).catch(error => {
                            failureCallback(error);
                        });
                    },
                    events: function(info, successCallback, failureCallback) {
                        @this.getEvents(info.start, info.end).then(events => {
                            successCallback(events);
                        }).catch(error => {
                            failureCallback(error);
                        });
                    },
                    themeSystem: "bootstrap5",
                    slotLabelInterval: '00:10:00',
                    slotDuration: '00:10:00',
                    allDaySlot: true,
                    businessHours: {
                        daysOfWeek: [1, 2, 3, 4, 5],
                        startTime: '08:00',
                        endTime: '18:00',
                    },
                    eventDidMount: function(info) {
                        if (info.event.extendedProps.description) {
                            info.el.title = info.event.extendedProps.description;
                        }
                    },
                    dateClick: function(info) {
                        // alert('Clicked on: ' + info.dateStr);
                    },
                    eventClick: function(info) {
                        Livewire.dispatch('View-Appointment-Page-Component', {
                            id: info.event.id,
                        });
                    },
                    eventDrop: function(info) {
                        Livewire.dispatch('Update-Appointment-Page-Component', {
                            id: info.event.id,
                            start: info.event.start,
                            end: info.event.end,
                            key: info.oldEvent.extendedProps.key,
                            employee_id: info.event.getResources()[0]?.id
                        });
                    },
                    eventResize: function(info) {
                        Livewire.dispatch('Update-Appointment-Page-Component', {
                            id: info.event.id,
                            start: info.event.start,
                            end: info.event.end,
                            key: info.oldEvent.extendedProps.key,
                            employee_id: info.event.getResources()[0]?.id
                        });
                    }
                });
                calendar.render();
                $('#employee_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('employee_id', value).then(() => {
                        if (calendar) {
                            calendar.refetchResources();
                            calendar.refetchEvents();
                        }
                    });
                });
                $('#date').change(function() {
                    calendar.gotoDate($(this).val());
                });
                window.addEventListener('Refresh-EmployeeCalendar-Component', event => {
                    if (calendar) {
                        calendar.refetchResources();
                        calendar.refetchEvents();
                    }
                });
            });
        </script>
    @endpush
</div>
