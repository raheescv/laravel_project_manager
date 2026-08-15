<script setup>
/**
 * Public appointment appointment — "Estate".
 *
 * Every slot for the whole appointment window arrives in ONE payload, so choosing
 * a day or a time is pure local state and lands instantly. The only network
 * call a customer makes is the appointment itself.
 *
 * The component owns the hero as well as the panel, because the hero's copy is
 * what changes between states — picking a time, already booked, expired link.
 * Styling comes entirely from the .apxp system on the page; this component
 * ships no CSS of its own so the two cannot drift apart.
 */
import { computed, onMounted, ref } from 'vue'

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
const selectedDate = ref(null)
const selectedSlot = ref(null)

const days = computed(() => data.value?.days ?? [])
const hasSlots = computed(() => days.value.length > 0)
const property = computed(() => data.value?.property ?? null)

const activeDay = computed(() => {
    if (!hasSlots.value) return null
    return days.value.find((d) => d.date === selectedDate.value) ?? days.value[0]
})

const isBooked = computed(() => data.value?.status === 'scheduled' && data.value?.scheduled)
const isUsable = computed(() => data.value?.usable !== false)

const initial = computed(() => (props.companyName || '?').trim().charAt(0).toUpperCase())

/**
 * Month is stamped on a day tab only where it CHANGES. The appointment window runs
 * a month ahead, so a strip that crosses into September has to say so — but
 * repeating "Aug" a dozen times over is noise.
 */
const dayTabs = computed(() =>
    days.value.map((day, index) => ({
        ...day,
        showMonth: index === 0 || day.month !== days.value[index - 1].month,
    }))
)

/** The building is what a customer recognises; the unit is what they ask for. */
const headlineSubject = computed(() => property.value?.building || property.value?.unit || '')

const propertyMeta = computed(() => {
    const p = property.value
    if (!p) return ''
    // Deliberately no room count: the type ("2 Bedroom") already says it, and
    // the two fields disagree often enough that printing both looks wrong.
    return [p.unit ? `Unit ${p.unit}` : null, p.type].filter(Boolean).join(' · ')
})

const durationLabel = computed(() => {
    const minutes = data.value?.duration_minutes
    if (!minutes) return ''
    return minutes % 60 === 0 && minutes >= 60
        ? `${minutes / 60} hour${minutes === 60 ? '' : 's'}`
        : `${minutes} minutes`
})

const selectedSlotObject = computed(() => {
    if (!selectedSlot.value || !activeDay.value) return null
    return activeDay.value.slots.find((s) => s.value === selectedSlot.value) ?? null
})

const selectedLabel = computed(() => {
    const slot = selectedSlotObject.value
    return slot ? `${activeDay.value.long_label}, ${slot.label}` : ''
})

/** Hero facts. Anything the tenant has not filled in simply does not appear. */
const facts = computed(() => {
    if (!data.value) return []

    if (isBooked.value) {
        return [
            { k: 'Reference', v: data.value.reference_no },
            { k: 'Your agent', v: data.value.salesman_name },
            { k: 'Times shown in', v: data.value.timezone },
        ].filter((f) => f.v)
    }

    return [
        { k: 'Appointment as', v: data.value.customer_name },
        { k: 'Your agent', v: data.value.salesman_name },
        { k: 'Appointment length', v: durationLabel.value },
        { k: 'Times shown in', v: data.value.timezone },
    ].filter((f) => f.v)
})

function applyPayload(payload) {
    data.value = payload
    // Keep the customer on the day they were looking at when possible.
    const stillThere = payload.days?.some((d) => d.date === selectedDate.value)
    if (!stillThere) selectedDate.value = payload.days?.[0]?.date ?? null
    selectedSlot.value = null
}

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
    selectedDate.value = date
    selectedSlot.value = null
    slotTaken.value = false
    message.value = ''
}

function pickSlot(value) {
    selectedSlot.value = value
    slotTaken.value = false
    message.value = ''
}

async function confirm() {
    if (!selectedSlot.value || appointment.value) return

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
                slot: selectedSlot.value,
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
                        <template v-if="data.salesman_name && property && property.location">
                            {{ data.salesman_name }} will meet you at {{ property.location }}.
                        </template>
                        <template v-else-if="data.salesman_name">
                            {{ data.salesman_name }} has it in the diary and will be in touch if anything changes.
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
                        Get in touch with {{ data.salesman_name || 'us' }} and we'll send you a fresh one —
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
                        <template v-if="data.salesman_name">{{ data.salesman_name }} has</template>
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
                            <template v-if="durationLabel"> · {{ durationLabel }}</template>
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
                    <div v-if="data.salesman_name" class="apxp-kv">
                        <span class="k">Your agent</span>
                        <span class="v">
                            {{ data.salesman_name }}<template v-if="data.salesman_phone"><br>{{ data.salesman_phone }}</template>
                        </span>
                    </div>
                    <div class="apxp-kv"><span class="k">Reference</span><span class="v">{{ data.reference_no }}</span></div>
                    <div class="apxp-kv"><span class="k">Times shown in</span><span class="v">{{ data.timezone }}</span></div>
                </div>

                <div class="apxp-note">
                    Need to change it?
                    <template v-if="data.salesman_phone">
                        Call {{ data.salesman_name }} on {{ data.salesman_phone }} and we'll move it for you.
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
                        This one is no longer valid. {{ data.salesman_name || 'Our team' }} can issue a fresh link
                        and you'll be booked in a minute.
                    </p>
                    <a v-if="data.salesman_phone" class="call" :href="`tel:${data.salesman_phone}`">
                        <i class="fa fa-phone"></i> Call {{ data.salesman_name }}
                    </a>
                </div>
            </div>

            <!-- nothing open -->
            <div v-else-if="!hasSlots" class="apxp-panel">
                <div class="apxp-empty">
                    <div class="art"><i class="fa fa-calendar-o"></i></div>
                    <h3>No times open just now</h3>
                    <p>
                        {{ data.salesman_name || 'Our team' }} has no free slots at the moment. Get in touch and
                        we'll arrange a appointment with you directly.
                    </p>
                    <a v-if="data.salesman_phone" class="call" :href="`tel:${data.salesman_phone}`">
                        <i class="fa fa-phone"></i> Call {{ data.salesman_name }}
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
                        <h3>{{ activeDay.long_label }}</h3>
                        <div v-if="property && property.location" class="where">
                            <i class="fa fa-map-marker"></i>{{ property.location }}
                        </div>
                    </div>
                    <span class="cnt">
                        {{ activeDay.slots.length }} time{{ activeDay.slots.length === 1 ? '' : 's' }} available
                    </span>
                </div>
                <div class="apxp-rule"></div>

                <div class="apxp-strip">
                    <button
                        v-for="day in dayTabs"
                        :key="day.date"
                        type="button"
                        class="apxp-d"
                        :class="{ sel: activeDay.date === day.date }"
                        @click="pickDay(day.date)"
                    >
                        <div class="w">{{ day.weekday }}</div>
                        <div class="n">{{ day.day }}</div>
                        <div class="mo" :class="{ ghost: !day.showMonth }">{{ day.month }}</div>
                    </button>
                </div>

                <div class="apxp-rows">
                    <button
                        v-for="slot in activeDay.slots"
                        :key="slot.value"
                        type="button"
                        class="apxp-row"
                        :class="{ sel: selectedSlot === slot.value }"
                        @click="pickSlot(slot.value)"
                    >
                        <span class="t">{{ slot.label }}</span>
                        <span class="m">
                            {{ slot.part }}<template v-if="slot.end_label"> · ends {{ slot.end_label }}</template>
                        </span>
                        <span class="pick">
                            <template v-if="selectedSlot === slot.value"><i class="fa fa-check"></i> Selected</template>
                            <template v-else>Select</template>
                        </span>
                    </button>
                </div>

                <div class="apxp-foot">
                    <div class="lab">
                        <div class="k">Your appointment</div>
                        <div class="v" :class="{ none: !selectedSlot }">
                            {{ selectedSlot ? selectedLabel : 'Choose a time above' }}
                        </div>
                        <div v-if="data.expires_at" class="exp">This link is valid until {{ data.expires_at }}.</div>
                    </div>
                    <button type="button" class="apxp-cta" :disabled="!selectedSlot || appointment" @click="confirm">
                        <i v-if="appointment" class="fa fa-spinner fa-spin"></i>
                        {{ appointment ? 'Confirming' : 'Confirm appointment' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
