<div>
    <style>
        .fc {
            --fc-border-color: #e5e7eb;
            --fc-button-bg-color: #24447f;
            --fc-button-border-color: #24447f;
            --fc-button-hover-bg-color: #1B2850;
            --fc-button-hover-border-color: #1B2850;
            --fc-button-active-bg-color: #092C4C;
            --fc-button-active-border-color: #092C4C;
            --fc-event-bg-color: #28C76F;
            --fc-event-border-color: #28C76F;
            --fc-today-bg-color: rgba(40, 199, 111, 0.1);
        }

        .fc .fc-toolbar-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: #092C4C;
            text-transform: capitalize;
        }

        .fc .fc-button {
            padding: 0.6rem 1.2rem;
            font-weight: 500;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            transition: all 0.2s ease-in-out;
            text-transform: capitalize;
        }

        .fc .fc-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.12);
        }

        .fc .fc-button:active {
            transform: translateY(0);
        }

        .fc .fc-event {
            border-radius: 6px;
            padding: 0.35rem 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06);
            transition: all 0.2s ease;
        }

        .fc .fc-event:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.12);
        }

        .fc .fc-col-header-cell {
            background-color: #f8fafc;
            padding: 1.25rem 0;
            border-width: 1px;
        }

        .fc .fc-col-header-cell-cushion {
            font-weight: 600;
            color: #1B2850;
        }

        .fc .fc-day-today {
            background-color: var(--fc-today-bg-color) !important;
        }

        .fc .fc-timegrid-slot {
            height: 3.5em;
            border-bottom: 1px dashed #e5e7eb;
        }

        .fc .fc-timegrid-slot-label {
            font-size: 0.875rem;
            color: #64748b;
        }

        .fc .fc-timegrid-now-indicator-line {
            border-color: #FF9F43;
        }

        .fc .fc-timegrid-now-indicator-arrow {
            border-color: #FF9F43;
            background-color: #FF9F43;
        }

        .fc-direction-ltr .fc-timegrid-col-events {
            margin: 0 5px;
        }

        .fc-theme-standard td,
        .fc-theme-standard th {
            border-color: #f1f5f9;
        }
    </style>
    <div class="card-header">
        <div class="col-lg-12">
            <div class="row">
                <div class="col-md-3">
                    <b><label for="date">Date</label></b>
                    {{ html()->date('date')->value('')->class('form-control')->id('date')->attribute('wire:model.live', 'date') }}
                </div>
                <div class="col-md-9" wire:ignore>
                    <b><label for="employee_id">Employee</label></b>
                    {{ html()->select('employee_id', [])->value()->class('select-employee_id-list')->attribute('multiple')->id('employee_id')->placeholder('All') }}
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
                        myCustomButton: {
                            text: 'Add Appointment',
                            click: function() {
                                Livewire.dispatch('Create-Appointment-Page-Component');
                            }
                        }
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
                        Livewire.dispatch('Edit-Appointment-Page-Component', {
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
                        calendar.refetchEvents();
                    }
                });
            });
        </script>
    @endpush
</div>
