<div>
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
                    views: {
                        resourceTimeGridDay: {
                            type: 'resourceTimeGrid',
                            duration: {
                                days: 1
                            },
                            buttonText: 'day'
                        },
                        resourceTimeGridWeek: {
                            type: 'resourceTimeGrid',
                            duration: {
                                weeks: 1
                            },
                            buttonText: 'week'
                        }
                    },
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
