<template>
    <section class="tkx-board-view">
        <div class="tkx-card tkx-deck">
            <label class="tkx-inp tkx-search">
                <i class="fa fa-search"></i>
                <input ref="searchInput" v-model="filters.search" placeholder="Search tickets…  ( / )" type="search">
            </label>
            <label class="tkx-inp">
                <i class="fa fa-circle-o"></i>
                <select v-model="filters.status" aria-label="Status">
                    <option value="">All statuses</option>
                    <option v-for="s in STATUSES" :key="s.key" :value="s.key">{{ s.label }}</option>
                </select>
            </label>
            <label class="tkx-inp" title="Created from">
                <i class="fa fa-calendar"></i>
                <input v-model="filters.from_date" type="date" aria-label="From date">
            </label>
            <label class="tkx-inp" title="Created to">
                <i class="fa fa-long-arrow-right"></i>
                <input v-model="filters.to_date" type="date" aria-label="To date">
            </label>
            <button type="button" class="tkx-btn icon" title="Reset filters" @click="resetFilters"><i class="fa fa-refresh" :class="{ 'tkx-spin': loading }"></i></button>
            <span class="tkx-deck-sep"></span>
            <button v-if="permissions.create" type="button" class="tkx-btn pri" @click="openCreate()"><i class="fa fa-plus"></i> New ticket</button>
        </div>

        <div class="tkx-pills" role="tablist" aria-label="Groups">
            <button type="button" class="tkx-pill" :class="{ on: filters.group === '' }" @click="filters.group = ''">
                <i class="fa fa-th-large"></i> All groups <span class="ct">{{ groupTotal }}</span>
            </button>
            <button v-for="g in groups" :key="g.name ?? NO_GROUP" type="button" class="tkx-pill"
                :class="{ on: filters.group === (g.name ?? NO_GROUP) }" @click="filters.group = g.name ?? NO_GROUP">
                <span class="tkx-dot" :style="{ '--c': groupColor(g.name) }"></span>
                {{ g.name ?? 'No group' }} <span class="ct">{{ g.count }}</span>
            </button>
            <button v-if="activeGroupMissing" type="button" class="tkx-pill on" @click="filters.group = ''">
                {{ filters.group === NO_GROUP ? 'No group' : filters.group }} <span class="ct">0</span> <i class="fa fa-times"></i>
            </button>
        </div>

        <div class="tkx-cols">
            <div v-for="s in STATUSES" :key="s.key" class="tkx-col" :class="{ 'is-drop': dropTarget === s.key }"
                :style="{ '--c': s.color, '--p': `${share(s.key)}%` }"
                @dragover.prevent="onDragOver(s.key)" @dragleave="onDragLeave(s.key, $event)" @drop.prevent="onDrop(s.key)">
                <div class="tkx-col-h">
                    <span class="ic"><i class="fa" :class="s.icon"></i></span>
                    <b>{{ s.label }}</b>
                    <span class="ct">{{ columns[s.key]?.total ?? 0 }}</span>
                    <button v-if="permissions.create" type="button" class="plus" :title="`New ${s.label} ticket`" @click="openCreate(s.key)"><i class="fa fa-plus"></i></button>
                </div>
                <div class="tkx-col-b">
                    <template v-if="!loaded">
                        <div v-for="n in 3" :key="n" class="tkx-skel" style="height: 96px"></div>
                    </template>
                    <template v-else>
                        <TicketCard v-for="t in columns[s.key]?.tickets ?? []" :key="t.id" :ticket="t" :draggable="permissions.edit"
                            @open="openTicket" @dragstart="dragging = $event" @dragend="dragging = null; dropTarget = null" />
                        <div v-if="!(columns[s.key]?.tickets ?? []).length" class="tkx-empty">
                            {{ dragging ? 'Drop here' : 'No tickets' }}
                        </div>
                        <div v-if="(columns[s.key]?.total ?? 0) > (columns[s.key]?.tickets.length ?? 0)" class="tkx-more">
                            Showing the latest {{ columns[s.key].tickets.length }} of {{ columns[s.key].total }} — narrow the filters to see the rest.
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <TicketDetail v-if="detail.open" :ticket-id="detail.id" :initial-status="detail.status" :groups="allGroups"
            :permissions="permissions" @close="detail.open = false" @changed="reload" />
    </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useToast } from 'vue-toastification'
import { errorMessage, ticketApi } from './api.js'
import TicketCard from './TicketCard.vue'
import TicketDetail from './TicketDetail.vue'
import { groupColor, NO_GROUP, STATUSES, statusOf } from './ticketMeta.js'

defineProps({ permissions: { type: Object, required: true } })

const toast = useToast()
const today = () => {
    const now = new Date()
    return new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 10)
}
const monthStart = () => today().slice(0, 8) + '01'
const defaults = () => ({ search: '', status: '', group: '', from_date: monthStart(), to_date: today() })

const filters = reactive(defaults())
const columns = ref({})
const groups = ref([])
const allGroups = ref([])
const loading = ref(false)
const loaded = ref(false)
const dragging = ref(null)
const dropTarget = ref(null)
const searchInput = ref(null)
const detail = reactive({ open: false, id: null, status: 'open' })

let controller = null
let debounce = null

async function reload() {
    controller?.abort()
    controller = new AbortController()
    loading.value = true
    try {
        const { data } = await ticketApi.board({ ...filters }, controller.signal)
        columns.value = data.columns
        groups.value = data.groups
        allGroups.value = data.all_groups
        loaded.value = true
    } catch (error) {
        const message = errorMessage(error)
        if (message) toast.error(message)
    } finally {
        loading.value = false
    }
}

watch(() => ({ ...filters }), (next, prev) => {
    clearTimeout(debounce)
    debounce = setTimeout(reload, next.search !== prev.search ? 300 : 0)
})

const groupTotal = computed(() => groups.value.reduce((sum, g) => sum + g.count, 0))
const visibleTotal = computed(() => STATUSES.reduce((sum, s) => sum + (columns.value[s.key]?.total ?? 0), 0))
const share = (key) => (visibleTotal.value ? Math.round(((columns.value[key]?.total ?? 0) / visibleTotal.value) * 100) : 0)
const activeGroupMissing = computed(() => filters.group !== '' && !groups.value.some((g) => (g.name ?? NO_GROUP) === filters.group))

function resetFilters() {
    Object.assign(filters, defaults())
}

function openTicket(id) {
    Object.assign(detail, { open: true, id, status: 'open' })
}

function openCreate(status = 'open') {
    Object.assign(detail, { open: true, id: null, status })
}

function onDragOver(key) {
    if (dragging.value) dropTarget.value = key
}

function onDragLeave(key, event) {
    if (!event.currentTarget.contains(event.relatedTarget) && dropTarget.value === key) dropTarget.value = null
}

/** Move the card at once, then confirm with the server; put it back if the server refuses. */
async function onDrop(key) {
    const ticket = dragging.value
    dragging.value = null
    dropTarget.value = null
    if (!ticket || ticket.status === key) return

    const from = ticket.status
    const snapshot = JSON.parse(JSON.stringify(columns.value))
    columns.value[from].tickets = columns.value[from].tickets.filter((t) => t.id !== ticket.id)
    columns.value[from].total--
    columns.value[key].tickets.unshift({ ...ticket, status: key })
    columns.value[key].total++

    try {
        await ticketApi.status(ticket.id, key)
        toast.success(`#${ticket.id} moved to ${statusOf(key).label}.`)
    } catch (error) {
        columns.value = snapshot
        toast.error(errorMessage(error))
    }
}

function onKey(event) {
    if (event.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) {
        event.preventDefault()
        searchInput.value?.focus()
    }
}

onMounted(() => {
    reload()
    window.addEventListener('keydown', onKey)
})
onBeforeUnmount(() => window.removeEventListener('keydown', onKey))

defineExpose({ reload })
</script>

<style>
.tkx-board-view { flex: 1; min-height: 0; display: flex; flex-direction: column; padding: 14px 18px 0; gap: 10px; }
.tkx-deck { flex: none; padding: 9px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
.tkx-deck .tkx-search { flex: 1; min-width: 220px; }
.tkx-deck-sep { width: 1px; height: 24px; background: var(--line); }
.tkx-pills { flex: none; display: flex; gap: 6px; overflow-x: auto; padding: 2px 2px 4px; scrollbar-width: thin; }
.tkx-pill { flex: none; display: inline-flex; align-items: center; gap: 7px; padding: 6px 13px; border-radius: 20px; border: 1px solid var(--line); background: var(--surf); cursor: pointer; color: var(--ink); font-weight: 500; font-size: 12.5px; }
.tkx-pill:hover { border-color: var(--acc-line); }
.tkx-pill .ct { font-size: 10.5px; color: var(--mute); }
.tkx-pill.on { background: var(--ink); color: var(--surf); border-color: var(--ink); }
.tkx-pill.on .ct { color: inherit; opacity: .7; }
.tkx-cols { flex: 1; min-height: 0; display: grid; grid-template-columns: repeat(4, minmax(270px, 1fr)); gap: 12px; overflow-x: auto; padding-bottom: 14px; }
.tkx-col { display: flex; flex-direction: column; min-height: 0; border-radius: var(--r); transition: background .15s, box-shadow .15s; }
.tkx-col.is-drop { background: color-mix(in srgb, var(--c) 8%, transparent); box-shadow: inset 0 0 0 2px color-mix(in srgb, var(--c) 45%, transparent); }
.tkx-col-h { flex: none; padding: 11px 13px; display: flex; align-items: center; gap: 10px; background: var(--surf); border: 1px solid var(--line); border-radius: 12px; margin-bottom: 9px; position: relative; overflow: hidden; }
.tkx-col-h::after { content: ""; position: absolute; inset: auto 0 0 0; height: 3px; background: linear-gradient(90deg, var(--c) var(--p), var(--surf-2) var(--p)); }
.tkx-col-h .ic { width: 28px; height: 28px; border-radius: 8px; display: grid; place-items: center; background: color-mix(in srgb, var(--c) 15%, transparent); color: var(--c); }
.tkx-col-h b { color: var(--ink); font-weight: 600; }
.tkx-col-h .ct { margin-inline-start: auto; font-size: 18px; font-weight: 600; color: var(--ink); }
.tkx-col-h .plus { border: 0; background: var(--surf-2); width: 26px; height: 26px; border-radius: 8px; cursor: pointer; color: var(--mute); }
.tkx-col-h .plus:hover { color: var(--acc); background: var(--acc-soft); }
.tkx-col-b { flex: 1; min-height: 120px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; padding: 2px 3px 10px; scrollbar-width: thin; }
.tkx-more { font-size: 11.5px; color: var(--mute); text-align: center; padding: 6px; }

@media (max-width: 767px) {
    .tkx-board-view { padding: 10px 10px 0; }
    .tkx-deck .tkx-inp:not(.tkx-search) { flex: 1 1 45%; }
    .tkx-deck-sep { display: none; }
    .tkx-deck .tkx-btn.pri { flex: 1; }
    .tkx-cols { grid-template-columns: repeat(4, 84vw); scroll-snap-type: x mandatory; }
    .tkx-col { scroll-snap-align: start; }
    .tkx-col-b { max-height: 70dvh; }
}
</style>
