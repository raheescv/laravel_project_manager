<script setup>
/**
 * Public appointment page — "Estate".
 *
 * The customer answers one question in two ways: tap a suggested time, or type
 * their own window. Both write the SAME pair of values (start, end), so there is
 * one thing to submit and one thing for the server to validate — a preset is
 * just a window somebody filled in for them.
 *
 * Everything needed to decide arrives in ONE payload — the free slots, the open
 * hours each day runs between, and the stretches already taken — so changing a
 * day or a time is pure local state and lands instantly. The only network call a
 * customer makes is the booking itself. The checks below mirror
 * SlotService::windowProblem() so the answer never differs from the server's,
 * which stays the authority.
 *
 * The component owns the hero as well as the panel, because the hero's copy is
 * what changes between states — picking a time, already booked, expired link.
 * Styling comes entirely from the .apxp system on the page; this component
 * ships no CSS of its own so the two cannot drift apart.
 */
import { computed, onMounted, ref, watch } from 'vue'

const props = defineProps({
    dataUrl: { type: String, required: true },
    bookUrl: { type: String, required: true },
    csrf: { type: String, default: '' },
    companyName: { type: String, default: '' },
    companyLogo: { type: String, default: '' },
    companyTagline: { type: String, default: '' },
})

const loading = ref(true)
const appointment = ref(false)
const loadError = ref('')
const message = ref('')
const slotTaken = ref(false)

const data = ref(null)
const selectedDate = ref(null)   // Y-m-d
const startTime = ref('')        // HH:MM
const endTime = ref('')          // HH:MM
const monthCursor = ref(null)    // first of the month the calendar is showing

const days = computed(() => data.value?.days ?? [])
const windows = computed(() => data.value?.windows ?? {})
const busy = computed(() => data.value?.busy ?? {})
const property = computed(() => data.value?.property ?? null)

/** Days the business is open at all — the calendar's selectable set. */
const openDates = computed(() => Object.keys(windows.value).sort())
/** Company closures, keyed by date — why a day is greyed out. */
const holidays = computed(() => data.value?.holidays ?? {})
const hasSlots = computed(() => openDates.value.length > 0)

const activeWindow = computed(() => windows.value[selectedDate.value] ?? null)
const activeDay = computed(() => days.value.find((d) => d.date === selectedDate.value) ?? null)
const freeStarts = computed(() => new Set((activeDay.value?.slots ?? []).map((s) => s.value.slice(11, 16))))
const activeBusy = computed(() => busy.value[selectedDate.value] ?? [])

const isBooked = computed(() => data.value?.status === 'scheduled' && data.value?.scheduled)
const isUsable = computed(() => data.value?.usable !== false)

const initial = computed(() => (props.companyName || '?').trim().charAt(0).toUpperCase())

const WEEKDAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December']

const pad = (n) => (n < 10 ? '0' + n : '' + n)
const toMinutes = (hhmm) => (hhmm ? Number(hhmm.slice(0, 2)) * 60 + Number(hhmm.slice(3, 5)) : NaN)
const toHhmm = (minutes) => pad(Math.floor(minutes / 60)) + ':' + pad(minutes % 60)
const isoOf = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
const dateOf = (iso) => new Date(`${iso}T00:00:00`)

/** Times follow the tenant's clock preference, exactly as the server labels them. */
function timeLabel(hhmm) {
    if (!hhmm) return ''
    if (data.value?.clock === 24) return hhmm
    const h = Number(hhmm.slice(0, 2))
    const suffix = h >= 12 ? 'PM' : 'AM'
    return `${pad(h % 12 === 0 ? 12 : h % 12)}:${hhmm.slice(3, 5)} ${suffix}`
}

function longLabel(iso) {
    if (!iso) return ''
    const d = dateOf(iso)
    return `${['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][d.getDay()]}, ${d.getDate()} ${MONTHS[d.getMonth()]}`
}

/** The month grid, padded to whole weeks so the columns line up under S M T W T F S. */
const calendar = computed(() => {
    const cursor = monthCursor.value ? dateOf(monthCursor.value) : new Date()
    const year = cursor.getFullYear()
    const month = cursor.getMonth()
    const first = new Date(year, month, 1)
    const total = new Date(year, month + 1, 0).getDate()
    const today = isoOf(new Date())

    const cells = []
    for (let i = 0; i < first.getDay(); i++) cells.push(null)
    for (let day = 1; day <= total; day++) {
        const iso = `${year}-${pad(month + 1)}-${pad(day)}`
        cells.push({
            iso,
            day,
            open: !!windows.value[iso],
            hasTimes: (days.value.find((d) => d.date === iso)?.slots ?? []).length > 0,
            today: iso === today,
            holiday: holidays.value[iso] ?? null,
        })
    }
    return { label: `${MONTHS[month]} ${year}`, cells }
})

/** Month arrows stop at the edges of the booking window rather than wandering. */
const canGoBack = computed(() => monthCursor.value > (openDates.value[0] ?? '').slice(0, 7) + '-01')
const canGoForward = computed(() => {
    const last = openDates.value[openDates.value.length - 1] ?? ''
    return monthCursor.value < last.slice(0, 7) + '-01'
})

function shiftMonth(step) {
    const cursor = dateOf(monthCursor.value)
    cursor.setMonth(cursor.getMonth() + step)
    monthCursor.value = isoOf(new Date(cursor.getFullYear(), cursor.getMonth(), 1))
}

/**
 * The suggested times: the day's open hours cut into slots, with anything the
 * server did not offer marked for WHY it is gone. A struck-through time the
 * customer can see is more use than a gap they cannot explain.
 */
const suggestions = computed(() => {
    const window = activeWindow.value
    if (!window) return []

    const step = data.value?.duration_minutes || 60
    const close = toMinutes(window.end)
    const out = []

    for (let m = toMinutes(window.start); m + step <= close; m += step) {
        const hhmm = toHhmm(m)
        const free = freeStarts.value.has(hhmm)
        out.push({
            value: hhmm,
            label: timeLabel(hhmm),
            free,
            note: free ? partOfDay(m) : (overlapsBusy(m, m + step) ? 'Taken' : 'Too soon'),
        })
    }
    return out
})

function partOfDay(minutes) {
    if (minutes < 720) return 'Morning'
    if (minutes < 1020) return 'Afternoon'
    return 'Evening'
}

function overlapsBusy(from, to) {
    return activeBusy.value.some((b) => from < toMinutes(b.end) && to > toMinutes(b.start))
}

const windowMinutes = computed(() => toMinutes(endTime.value) - toMinutes(startTime.value))

/** How long the CONFIRMED appointment runs — what the customer asked for, not a default. */
const bookedLength = computed(() => minutesLabel(data.value?.scheduled?.minutes))

function minutesLabel(minutes) {
    if (!(minutes > 0)) return ''
    const hours = Math.floor(minutes / 60)
    const rest = minutes % 60
    if (hours && rest) return `${hours}h ${rest}m`
    if (hours) return `${hours} hour${hours === 1 ? '' : 's'}`
    return `${rest} minutes`
}

const durationLabel = computed(() => minutesLabel(windowMinutes.value))

/**
 * The same checks SlotService::windowProblem() runs, so the customer is told
 * what is wrong before they submit. The server still decides — this only saves
 * them a round trip and a rejection.
 */
const verdict = computed(() => {
    if (!selectedDate.value || !startTime.value || !endTime.value) {
        return { tone: '', icon: 'fa-info-circle', text: 'Choose a time above.', ok: false }
    }
    const open = activeWindow.value
    if (!open) {
        return { tone: 'warn', icon: 'fa-exclamation-circle', text: 'We are closed that day. Please choose another date.', ok: false }
    }
    if (!(windowMinutes.value > 0)) {
        return { tone: 'bad', icon: 'fa-exclamation-triangle', text: 'The leaving time has to be after the arriving time.', ok: false }
    }

    const from = toMinutes(startTime.value)
    const to = toMinutes(endTime.value)

    if (from < toMinutes(open.start) || to > toMinutes(open.end)) {
        return {
            tone: 'warn',
            icon: 'fa-exclamation-circle',
            text: `That day runs ${timeLabel(open.start)} to ${timeLabel(open.end)}. Please choose a time inside those hours.`,
            ok: false,
        }
    }
    if (overlapsBusy(from, to)) {
        return { tone: 'warn', icon: 'fa-exclamation-circle', text: 'That overlaps something already in the diary. Please shift it a little.', ok: false }
    }
    if (data.value?.server_now && `${selectedDate.value} ${startTime.value}` < earliestStart.value) {
        return {
            tone: 'warn',
            icon: 'fa-clock-o',
            text: `We need at least ${data.value.notice_hours} hours' notice. Please choose a later time.`,
            ok: false,
        }
    }

    return {
        tone: 'ok',
        icon: 'fa-check-circle',
        text: `That window is free${data.value?.employee_name ? ' — ' + data.value.employee_name + ' will hold it for you' : ''}.`,
        ok: true,
    }
})

/** The notice cut-off as a comparable 'Y-m-d HH:MM' stamp, in the tenant's timezone. */
const earliestStart = computed(() => {
    const now = data.value?.server_now
    if (!now) return ''
    const [datePart, timePart] = now.split(' ')
    const stamp = new Date(`${datePart}T${timePart}`)
    stamp.setHours(stamp.getHours() + (data.value?.notice_hours ?? 0))
    return `${isoOf(stamp)} ${pad(stamp.getHours())}:${pad(stamp.getMinutes())}`
})

const canConfirm = computed(() => verdict.value.ok && !appointment.value)

/** The building is what a customer recognises; the unit is what they ask for. */
const headlineSubject = computed(() => property.value?.building || property.value?.unit || '')

const propertyMeta = computed(() => {
    const p = property.value
    if (!p) return ''
    // Deliberately no room count: the type ("2 Bedroom") already says it, and
    // the two fields disagree often enough that printing both looks wrong.
    return [p.unit ? `Unit ${p.unit}` : null, p.type].filter(Boolean).join(' · ')
})

/** Hero facts. Anything the tenant has not filled in simply does not appear. */
const facts = computed(() => {
    if (!data.value) return []

    if (isBooked.value) {
        return [
            { k: 'Reference', v: data.value.reference_no },
            { k: 'Your agent', v: data.value.employee_name },
            { k: 'Times shown in', v: data.value.timezone },
        ].filter((f) => f.v)
    }

    return [
        { k: 'Appointment as', v: data.value.customer_name },
        { k: 'Your agent', v: data.value.employee_name },
        { k: 'Open hours', v: openHoursLabel.value },
        { k: 'Times shown in', v: data.value.timezone },
    ].filter((f) => f.v)
})

/** The open hours of the day in view, for the hero and the sub-heading. */
const openHoursLabel = computed(() => {
    const open = activeWindow.value ?? Object.values(windows.value)[0]
    return open ? `${timeLabel(open.start)} – ${timeLabel(open.end)}` : ''
})

const selectedLabel = computed(() =>
    selectedDate.value && windowMinutes.value > 0
        ? `${longLabel(selectedDate.value)} · ${timeLabel(startTime.value)} – ${timeLabel(endTime.value)}`
        : ''
)

function applyPayload(payload) {
    data.value = payload

    const openDays = Object.keys(payload.windows ?? {}).sort()
    // Keep the customer on the day they were looking at when possible; otherwise
    // open on the first day that actually has a time going spare.
    const firstWithTimes = payload.days?.[0]?.date
    const previous = selectedDate.value

    if (!selectedDate.value || !openDays.includes(selectedDate.value)) {
        selectedDate.value = firstWithTimes ?? openDays[0] ?? null
    }
    monthCursor.value = selectedDate.value ? selectedDate.value.slice(0, 8) + '01' : null

    // A refresh after a lost race must not wipe what the customer typed — the
    // verdict below re-reads the new stretches and tells them what is wrong.
    // Only a day that moved out from under them earns a fresh seed.
    if (selectedDate.value !== previous || !startTime.value) seedTimes()
}

/**
 * Pre-fill the window with the first time still going spare on this day, so the
 * page always opens on something valid — the customer edits rather than starts.
 */
function seedTimes() {
    const first = (activeDay.value?.slots ?? [])[0]?.value?.slice(11, 16)
    const open = activeWindow.value

    const start = first ?? open?.start ?? ''
    startTime.value = start
    endTime.value = start
        ? toHhmm(Math.min(toMinutes(start) + (data.value?.duration_minutes || 60), toMinutes(open?.end ?? '23:59')))
        : ''
}

/** Changing the day re-seeds rather than carrying a time that may not exist there. */
watch(selectedDate, () => seedTimes())

async function load() {
    loading.value = true
    loadError.value = ''
    try {
        const response = await fetch(props.dataUrl, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
        if (!response.ok) throw new Error('We could not load the available times.')
        applyPayload(await response.json())
    } catch (e) {
        loadError.value = e.message || 'Something went wrong loading this page.'
    } finally {
        loading.value = false
    }
}

function pickDay(date) {
    if (!windows.value[date]) return
    selectedDate.value = date
    slotTaken.value = false
    message.value = ''
}

/** A suggested time is just a window, pre-filled. */
function pickSuggestion(hhmm) {
    startTime.value = hhmm
    endTime.value = toHhmm(toMinutes(hhmm) + (data.value?.duration_minutes || 60))
    slotTaken.value = false
    message.value = ''
}

/** Whether the typed window still matches a suggestion, so the chip stays lit. */
function isPicked(hhmm) {
    return startTime.value === hhmm
        && windowMinutes.value === (data.value?.duration_minutes || 60)
}

/** Jump to the earliest time the employee can still take. */
function pickEarliest() {
    const day = days.value.find((d) => (d.slots ?? []).length)
    if (!day) return
    selectedDate.value = day.date
    monthCursor.value = day.date.slice(0, 8) + '01'
    // Seed explicitly: the watcher does not fire when that day is already open.
    seedTimes()
    slotTaken.value = false
    message.value = ''
}

async function confirm() {
    if (!canConfirm.value) return

    appointment.value = true
    slotTaken.value = false
    message.value = ''

    try {
        const response = await fetch(props.bookUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': props.csrf,
            },
            body: JSON.stringify({
                slot: `${selectedDate.value} ${startTime.value}:00`,
                ends_at: `${selectedDate.value} ${endTime.value}:00`,
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            }),
        })

        const payload = await response.json()

        // The endpoint always returns fresh slots, so a lost race re-renders
        // itself without the customer having to reload anything.
        applyPayload(payload)

        if (!payload.success) {
            slotTaken.value = payload.slot_taken === true
            message.value = payload.message || 'That time is no longer available.'
        } else {
            window.scrollTo({ top: 0, behavior: 'smooth' })
        }
    } catch (e) {
        message.value = 'We could not reach the server. Please try again.'
    } finally {
        appointment.value = false
    }
}

onMounted(load)
</script>

<template>
    <div>
        <!-- ── hero ─────────────────────────────────────────────── -->
        <header class="apxp-hero">
            <div class="apxp-inner">
                <div class="apxp-mast">
                    <span class="bm">
                        <img v-if="companyLogo" :src="companyLogo" :alt="companyName">
                        <template v-else>{{ initial }}</template>
                    </span>
                    <div>
                        <div class="nm">{{ companyName }}</div>
                        <div class="ds">{{ companyTagline }}</div>
                    </div>
                    <span class="lock"><i class="fa fa-lock"></i> Personal link</span>
                </div>

                <!-- still loading -->
                <template v-if="loading">
                    <div class="apxp-sk sm"></div>
                    <div class="apxp-sk lg"></div>
                    <div class="apxp-sk md"></div>
                </template>

                <!-- could not load -->
                <template v-else-if="loadError">
                    <div class="apxp-eyebrow">Property appointment</div>
                    <h1 class="apxp-h1">We couldn't load your times.</h1>
                    <p class="apxp-sub">{{ loadError }}</p>
                </template>

                <!-- booked -->
                <template v-else-if="isBooked">
                    <div class="apxp-eyebrow">Appointment confirmed</div>
                    <h1 class="apxp-h1">
                        You're all set for <em>{{ data.scheduled.headline_date }}</em>.
                    </h1>
                    <p class="apxp-sub">
                        Your time is held.
                        <template v-if="data.employee_name && property && property.location">
                            {{ data.employee_name }} will meet you at {{ property.location }}.
                        </template>
                        <template v-else-if="data.employee_name">
                            {{ data.employee_name }} has it in the diary and will be in touch if anything changes.
                        </template>
                    </p>
                    <div class="apxp-facts">
                        <div v-for="fact in facts" :key="fact.k" class="apxp-fact">
                            <div class="k">{{ fact.k }}</div>
                            <div class="v">{{ fact.v }}</div>
                        </div>
                    </div>
                </template>

                <!-- link expired -->
                <template v-else-if="!isUsable">
                    <div class="apxp-eyebrow">Property appointment</div>
                    <h1 class="apxp-h1">This link has expired.</h1>
                    <p class="apxp-sub">
                        Get in touch with {{ data.employee_name || 'us' }} and we'll send you a fresh one —
                        it only takes a moment.
                    </p>
                </template>

                <!-- pick a time -->
                <template v-else>
                    <div class="apxp-eyebrow">Private appointment</div>
                    <h1 class="apxp-h1">
                        <template v-if="headlineSubject">
                            Come and see <em>{{ headlineSubject }}</em> for yourself.
                        </template>
                        <template v-else>Let's find a time that suits you.</template>
                    </h1>
                    <div v-if="propertyMeta" class="apxp-meta">{{ propertyMeta }}</div>
                    <p class="apxp-sub">
                        <template v-if="data.employee_name">{{ data.employee_name }} has</template>
                        <template v-else>We've</template>
                        held these times open for you. Pick whichever suits — nothing else is needed from you.
                    </p>
                    <div class="apxp-facts">
                        <div v-for="fact in facts" :key="fact.k" class="apxp-fact">
                            <div class="k">{{ fact.k }}</div>
                            <div class="v">{{ fact.v }}</div>
                        </div>
                    </div>
                </template>
            </div>
        </header>

        <!-- ── panel ────────────────────────────────────────────── -->
        <div class="apxp-body">
            <!-- still loading -->
            <div v-if="loading" class="apxp-panel">
                <div class="apxp-empty">
                    <div class="art"><i class="fa fa-spinner fa-spin"></i></div>
                    <h3>Finding available times</h3>
                    <p>One moment — we're checking the calendar.</p>
                </div>
            </div>

            <!-- could not load -->
            <div v-else-if="loadError" class="apxp-panel">
                <div class="apxp-empty">
                    <div class="art"><i class="fa fa-exclamation-triangle"></i></div>
                    <h3>Something went wrong</h3>
                    <p>Please refresh the page. If it keeps happening, contact us and we'll arrange a time with you directly.</p>
                </div>
            </div>

            <!-- booked -->
            <div v-else-if="isBooked" class="apxp-panel">
                <div class="apxp-conf-h">
                    <div>
                        <div class="k">Your appointment</div>
                        <div class="when">{{ data.scheduled.long_date }}</div>
                        <div class="at">
                            {{ data.scheduled.time }}<template v-if="data.scheduled.end_time"> – {{ data.scheduled.end_time }}</template>
                            <template v-if="data.scheduled.minutes"> · {{ bookedLength }}</template>
                        </div>
                    </div>
                    <span class="apxp-seal"><i class="fa fa-check"></i> Confirmed</span>
                </div>

                <div class="apxp-pad">
                    <div v-if="property && property.unit" class="apxp-kv">
                        <span class="k">Property</span>
                        <span class="v">
                            {{ property.unit }}<template v-if="property.building">, {{ property.building }}</template>
                        </span>
                    </div>
                    <div v-if="property && property.location" class="apxp-kv">
                        <span class="k">Where</span><span class="v">{{ property.location }}</span>
                    </div>
                    <div v-if="data.employee_name" class="apxp-kv">
                        <span class="k">Your agent</span>
                        <span class="v">
                            {{ data.employee_name }}<template v-if="data.employee_phone"><br>{{ data.employee_phone }}</template>
                        </span>
                    </div>
                    <div class="apxp-kv"><span class="k">Reference</span><span class="v">{{ data.reference_no }}</span></div>
                    <div class="apxp-kv"><span class="k">Times shown in</span><span class="v">{{ data.timezone }}</span></div>
                </div>

                <div class="apxp-note">
                    Need to change it?
                    <template v-if="data.employee_phone">
                        Call {{ data.employee_name }} on {{ data.employee_phone }} and we'll move it for you.
                    </template>
                    <template v-else>Get in touch and we'll move it for you.</template>
                    Keep this link — it stays valid for your appointment.
                </div>
            </div>

            <!-- link expired -->
            <div v-else-if="!isUsable" class="apxp-panel">
                <div class="apxp-empty">
                    <div class="art"><i class="fa fa-clock-o"></i></div>
                    <h3>We can send you another link</h3>
                    <p>
                        This one is no longer valid. {{ data.employee_name || 'Our team' }} can issue a fresh link
                        and you'll be booked in a minute.
                    </p>
                    <a v-if="data.employee_phone" class="call" :href="`tel:${data.employee_phone}`">
                        <i class="fa fa-phone"></i> Call {{ data.employee_name }}
                    </a>
                </div>
            </div>

            <!-- nothing open -->
            <div v-else-if="!hasSlots" class="apxp-panel">
                <div class="apxp-empty">
                    <div class="art"><i class="fa fa-calendar-o"></i></div>
                    <h3>No times open just now</h3>
                    <p>
                        {{ data.employee_name || 'Our team' }} has no free slots at the moment. Get in touch and
                        we'll arrange a appointment with you directly.
                    </p>
                    <a v-if="data.employee_phone" class="call" :href="`tel:${data.employee_phone}`">
                        <i class="fa fa-phone"></i> Call {{ data.employee_name }}
                    </a>
                </div>
            </div>

            <!-- pick a time -->
            <div v-else class="apxp-panel">
                <div v-if="slotTaken" class="apxp-alert bad">
                    <i class="fa fa-exclamation-triangle"></i>
                    <div>
                        <div class="t">That time was just taken by someone else</div>
                        <div class="s">Nothing has been saved. The times below refreshed a moment ago — please pick another.</div>
                    </div>
                </div>
                <div v-else-if="message" class="apxp-alert warn">
                    <i class="fa fa-info-circle"></i>
                    <div><div class="t">{{ message }}</div></div>
                </div>

                <div class="apxp-ptop">
                    <div>
                        <h3>Choose your time</h3>
                        <div v-if="property && property.location" class="where">
                            <i class="fa fa-map-marker"></i>{{ property.location }}
                        </div>
                    </div>
                    <span class="cnt">{{ openHoursLabel }}</span>
                </div>
                <div class="apxp-rule"></div>

                <div class="apxp-split">
                    <!-- ── the month ─────────────────────────────── -->
                    <div class="apxp-cal">
                        <div class="h">
                            <button type="button" :disabled="!canGoBack" aria-label="Previous month" @click="shiftMonth(-1)">
                                <i class="fa fa-angle-left"></i>
                            </button>
                            <div class="m">{{ calendar.label }}</div>
                            <button type="button" :disabled="!canGoForward" aria-label="Next month" @click="shiftMonth(1)">
                                <i class="fa fa-angle-right"></i>
                            </button>
                        </div>
                        <div class="apxp-dow">
                            <span v-for="(name, index) in ['S', 'M', 'T', 'W', 'T', 'F', 'S']" :key="index">{{ name }}</span>
                        </div>
                        <div class="apxp-days">
                            <template v-for="(cell, index) in calendar.cells" :key="index">
                                <span v-if="!cell"></span>
                                <button
                                    v-else
                                    type="button"
                                    class="apxp-dy"
                                    :class="{ sel: cell.iso === selectedDate, today: cell.today, hol: !!cell.holiday }"
                                    :disabled="!cell.open"
                                    :title="cell.holiday ? 'Closed — ' + cell.holiday : null"
                                    @click="pickDay(cell.iso)"
                                >
                                    {{ cell.day }}
                                    <span v-if="cell.hasTimes" class="pip"></span>
                                </button>
                            </template>
                        </div>
                        <div class="apxp-legend">
                            <div><i class="fa fa-circle-o"></i> Today</div>
                            <div><i class="fa fa-circle" style="font-size:7px"></i> Times still open</div>
                        </div>
                    </div>

                    <!-- ── the day ───────────────────────────────── -->
                    <div class="apxp-pane">
                        <div class="apxp-seldate">{{ longLabel(selectedDate) }}</div>
                        <div class="apxp-selsub">
                            <template v-if="activeWindow">
                                {{ data.employee_name || 'We' }} {{ data.employee_name ? 'is' : 'are' }} available
                                {{ openHoursLabel }}
                            </template>
                            <template v-else>Closed on this day.</template>
                        </div>

                        <template v-if="suggestions.length">
                            <div class="apxp-sechead">
                                <div class="h">Suggested times</div>
                                <div class="s">
                                    {{ data.duration_minutes }} minutes each · tap one to fill the times below
                                </div>
                            </div>
                            <div class="apxp-slots">
                                <button
                                    v-for="suggestion in suggestions"
                                    :key="suggestion.value"
                                    type="button"
                                    class="apxp-slot"
                                    :class="{ sel: isPicked(suggestion.value) }"
                                    :disabled="!suggestion.free"
                                    @click="pickSuggestion(suggestion.value)"
                                >
                                    {{ suggestion.label }}
                                    <small>{{ suggestion.note }}</small>
                                </button>
                            </div>
                        </template>

                        <div class="apxp-or"><span>or set your own window</span></div>

                        <div class="apxp-when">
                            <div class="apxp-datefield">
                                <label class="apxp-fl" for="apxp-date">Date</label>
                                <input
                                    id="apxp-date"
                                    class="apxp-fi sm"
                                    type="date"
                                    :value="selectedDate"
                                    :min="openDates[0]"
                                    :max="openDates[openDates.length - 1]"
                                    @change="pickDay($event.target.value)"
                                >
                            </div>
                            <div>
                                <label class="apxp-fl" for="apxp-from">Arriving at</label>
                                <input id="apxp-from" v-model="startTime" class="apxp-fi" type="time">
                            </div>
                            <div>
                                <label class="apxp-fl" for="apxp-to">Leaving by</label>
                                <input id="apxp-to" v-model="endTime" class="apxp-fi" type="time">
                            </div>
                            <div class="apxp-durwrap">
                                <span class="apxp-dur" :class="{ bad: !durationLabel }">
                                    <i class="fa" :class="durationLabel ? 'fa-hourglass-half' : 'fa-exclamation-triangle'"></i>
                                    {{ durationLabel || 'Check times' }}
                                </span>
                            </div>
                        </div>

                        <div class="apxp-verdict" :class="verdict.tone">
                            <i class="fa" :class="verdict.icon"></i>
                            <div>{{ verdict.text }}</div>
                        </div>

                        <div class="apxp-nowline">
                            <div v-if="data.now_label">Right now it is <b>{{ data.now_label }}</b></div>
                            <button type="button" class="apxp-mini" @click="pickEarliest">
                                <i class="fa fa-bolt"></i> Earliest available
                            </button>
                        </div>
                    </div>
                </div>

                <div class="apxp-foot">
                    <div class="lab">
                        <div class="k">Your appointment</div>
                        <div class="v" :class="{ none: !selectedLabel }">
                            {{ selectedLabel || 'Choose a time above' }}
                        </div>
                        <div v-if="data.expires_at" class="exp">This link is valid until {{ data.expires_at }}.</div>
                    </div>
                    <button type="button" class="apxp-cta" :disabled="!canConfirm" @click="confirm">
                        <i v-if="appointment" class="fa fa-spinner fa-spin"></i>
                        {{ appointment ? 'Confirming' : 'Confirm appointment' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
