<template>
    <div class="tkx-ovl" @mousedown.self="close">
        <div class="tkx-mdl tkx-mgr" role="dialog" aria-modal="true" aria-labelledby="tkx-mgr-title">
            <header class="tkx-mdl-h">
                <span class="tkx-mgr-ic"><i class="fa fa-columns"></i></span>
                <div class="tkx-mgr-head">
                    <b id="tkx-mgr-title">Manage board</b>
                    <small>Columns run left to right in this order. Every change saves straight away.</small>
                </div>
                <div class="acts"><button type="button" class="tkx-x" aria-label="Close" @click="close"><i class="fa fa-times"></i></button></div>
            </header>

            <div class="tkx-mgr-b">
                <div v-if="loading" class="tkx-mgr-list">
                    <div v-for="n in 4" :key="n" class="tkx-skel" style="height: 50px"></div>
                </div>

                <ol v-else class="tkx-mgr-list">
                    <li v-for="(s, i) in list" :key="s.id" class="tkx-mgr-item" :class="{ 'is-drop': dropIndex === i && dragIndex !== i, 'is-drag': dragIndex === i, 'is-open': openId === s.id }"
                        :style="{ '--c': s.color }" @dragover.prevent="dragIndex !== null && (dropIndex = i)" @drop.prevent="onDrop(i)">
                        <div class="tkx-mgr-row">
                            <span class="grip" draggable="true" title="Drag to reorder" @dragstart="onDragStart(i, $event)" @dragend="dragIndex = dropIndex = null"><i class="fa fa-bars"></i></span>
                            <button type="button" class="swatch" :title="openId === s.id ? 'Close colour and icon' : 'Change colour and icon'" :aria-expanded="openId === s.id" @click="toggleStyle(s)">
                                <i class="fa" :class="s.icon"></i>
                            </button>
                            <input v-model="s.label" class="tkx-field name" maxlength="50" :aria-label="`Name of column ${i + 1}`" :disabled="busy === s.id"
                                @keydown.enter.prevent="$event.target.blur()" @keydown.esc.stop="s.label = s.saved; $event.target.blur()" @blur="rename(s)">
                            <span class="ct" :title="`${s.tickets_count} ticket${s.tickets_count === 1 ? '' : 's'} in this column`">{{ s.tickets_count }}</span>
                            <span class="mv">
                                <button type="button" :disabled="i === 0 || reordering" title="Move up — further left on the board" @click="move(i, -1)"><i class="fa fa-chevron-up"></i></button>
                                <button type="button" :disabled="i === list.length - 1 || reordering" title="Move down — further right on the board" @click="move(i, 1)"><i class="fa fa-chevron-down"></i></button>
                            </span>
                            <button type="button" class="del" :disabled="list.length === 1" :title="list.length === 1 ? 'A board needs at least one column' : 'Remove column'" @click="askRemove(s)"><i class="fa fa-trash-o"></i></button>
                        </div>

                        <div v-if="openId === s.id" class="tkx-mgr-style">
                            <div class="tkx-mgr-swatches" role="radiogroup" aria-label="Colour">
                                <button v-for="c in STATUS_COLORS" :key="c" type="button" :class="{ on: s.color === c }" :style="{ '--c': c }" :aria-label="c" @click="restyle(s, { color: c })"></button>
                            </div>
                            <div class="tkx-mgr-icons" role="radiogroup" aria-label="Icon">
                                <button v-for="icon in STATUS_ICONS" :key="icon" type="button" :class="{ on: s.icon === icon }" :title="icon.replace('fa-', '')" @click="restyle(s, { icon })"><i class="fa" :class="icon"></i></button>
                            </div>
                        </div>

                        <div v-if="removing?.id === s.id" class="tkx-mgr-confirm">
                            <template v-if="s.tickets_count">
                                <span>Move its <b>{{ s.tickets_count }}</b> ticket{{ s.tickets_count === 1 ? '' : 's' }} to</span>
                                <label class="tkx-inp">
                                    <span class="tkx-dot" :style="{ '--c': list.find((o) => o.key === removing.moveTo)?.color }"></span>
                                    <select v-model="removing.moveTo" aria-label="Move tickets to">
                                        <option v-for="o in list.filter((o) => o.id !== s.id)" :key="o.key" :value="o.key">{{ o.saved }}</option>
                                    </select>
                                </label>
                            </template>
                            <span v-else>Remove this empty column?</span>
                            <span class="sp"></span>
                            <button type="button" class="tkx-btn" @click="removing = null">Keep</button>
                            <button type="button" class="tkx-btn danger-solid" :disabled="busy === s.id" @click="remove(s)">
                                <i class="fa" :class="busy === s.id ? 'fa-refresh tkx-spin' : 'fa-trash-o'"></i> Remove
                            </button>
                        </div>
                    </li>
                </ol>

                <form v-if="!loading" class="tkx-mgr-add" :style="{ '--c': draft.color }" @submit.prevent="add">
                    <template v-if="list.length < MAX_COLUMNS">
                        <span class="tkx-label">New column</span>
                        <div class="tkx-mgr-row">
                            <span class="swatch"><i class="fa" :class="draft.icon"></i></span>
                            <input ref="newInput" v-model="draft.name" class="tkx-field name" maxlength="50" placeholder="e.g. QA Review, Waiting on customer">
                            <button type="submit" class="tkx-btn pri" :disabled="!draft.name.trim() || adding">
                                <i class="fa" :class="adding ? 'fa-refresh tkx-spin' : 'fa-plus'"></i> Add column
                            </button>
                        </div>
                        <div class="tkx-mgr-swatches sm" role="radiogroup" aria-label="New column colour">
                            <button v-for="c in STATUS_COLORS" :key="c" type="button" :class="{ on: draft.color === c }" :style="{ '--c': c }" :aria-label="c" @click="draft.color = c"></button>
                        </div>
                        <div class="tkx-mgr-icons sm" role="radiogroup" aria-label="New column icon">
                            <button v-for="icon in STATUS_ICONS" :key="icon" type="button" :class="{ on: draft.icon === icon }" :title="icon.replace('fa-', '')" @click="draft.icon = icon"><i class="fa" :class="icon"></i></button>
                        </div>
                    </template>
                    <div v-else class="tkx-empty">The board has the most columns it can hold ({{ MAX_COLUMNS }}). Remove one to add another.</div>
                </form>
            </div>

            <footer class="tkx-mdl-f">
                <span class="tkx-mgr-count">{{ list.length }} of {{ MAX_COLUMNS }} columns</span>
                <span class="sp"></span>
                <button type="button" class="tkx-btn pri" @click="close">Done</button>
            </footer>
        </div>
    </div>
</template>

<script setup>
import { nextTick, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { errorMessage, ticketApi } from './api.js'
import { setStatuses, STATUS_COLORS, STATUS_ICONS } from './ticketMeta.js'

const props = defineProps({ startAdding: { type: Boolean, default: false } })
const emit = defineEmits(['close', 'changed'])

/** Mirrors TicketStatus::MAX_COLUMNS. */
const MAX_COLUMNS = 12

const toast = useToast()
const list = ref([])
const loading = ref(true)
const busy = ref(null)
const adding = ref(false)
const reordering = ref(false)
const openId = ref(null)
const removing = ref(null)
const dragIndex = ref(null)
const dropIndex = ref(null)
const newInput = ref(null)
const draft = reactive({ name: '', color: nextColor([]), icon: 'fa-flag' })

/** The first palette colour no column uses yet, so a new column stands apart. */
function nextColor(columns) {
    return STATUS_COLORS.find((c) => !columns.some((s) => s.color === c)) ?? STATUS_COLORS[0]
}

/** Take the server's column list; `saved` is the last stored name, so a failed rename can roll back. */
function apply(columns) {
    list.value = columns.map((s) => ({ ...s, saved: s.label }))
    setStatuses(columns.map(({ id, key, label, color, icon }) => ({ id, key, label, color, icon })))
}

async function load() {
    try {
        const { data } = await ticketApi.statuses()
        apply(data)
        draft.color = nextColor(data)
    } catch (error) {
        toast.error(errorMessage(error))
        emit('close')
    } finally {
        loading.value = false
    }
}

/** Run one change against the server; on success redraw from its answer and tell the board. */
async function save(request, { quiet = false } = {}) {
    try {
        const response = await request()
        apply(response.data)
        emit('changed')
        if (!quiet) toast.success(response.message)
        return true
    } catch (error) {
        toast.error(errorMessage(error))
        return false
    }
}

async function add() {
    const name = draft.name.trim()
    if (!name || adding.value) return
    adding.value = true
    if (await save(() => ticketApi.addStatus({ name, color: draft.color, icon: draft.icon }))) {
        draft.name = ''
        draft.color = nextColor(list.value)
        nextTick(() => newInput.value?.focus())
    }
    adding.value = false
}

async function rename(status) {
    const name = status.label.trim()
    if (name === status.saved) return
    if (!name) {
        status.label = status.saved
        return
    }
    busy.value = status.id
    if (!(await save(() => ticketApi.updateStatus(status.id, { name })))) status.label = status.saved
    busy.value = null
}

/** Colour and icon apply on tap — no separate save. */
async function restyle(status, change) {
    const before = { color: status.color, icon: status.icon }
    Object.assign(status, change)
    if (!(await save(() => ticketApi.updateStatus(status.id, change), { quiet: true }))) Object.assign(status, before)
}

function toggleStyle(status) {
    openId.value = openId.value === status.id ? null : status.id
    removing.value = null
}

function askRemove(status) {
    openId.value = null
    removing.value = { id: status.id, moveTo: list.value.find((s) => s.id !== status.id)?.key ?? null }
}

async function remove(status) {
    busy.value = status.id
    if (await save(() => ticketApi.deleteStatus(status.id, status.tickets_count ? removing.value.moveTo : null))) removing.value = null
    busy.value = null
}

async function reorder(from, to) {
    if (from === to || reordering.value) return
    const snapshot = [...list.value]
    const next = [...list.value]
    next.splice(to, 0, next.splice(from, 1)[0])
    list.value = next
    reordering.value = true
    if (!(await save(() => ticketApi.reorderStatuses(next.map((s) => s.id)), { quiet: true }))) list.value = snapshot
    reordering.value = false
}

const move = (index, step) => reorder(index, index + step)

function onDragStart(index, event) {
    dragIndex.value = index
    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.setData('text/plain', String(index))
}

function onDrop(index) {
    const from = dragIndex.value
    dragIndex.value = dropIndex.value = null
    if (from !== null) reorder(from, index)
}

/** Another screen changed the columns (live channel): redraw, unless a rename is being typed. */
async function refresh() {
    if (loading.value || busy.value || adding.value || reordering.value) return
    if (list.value.some((s) => s.label.trim() !== s.saved)) return
    try {
        const { data } = await ticketApi.statuses()
        apply(data)
    } catch {}
}

defineExpose({ refresh })

function close() {
    emit('close')
}

function onKey(event) {
    if (event.key === 'Escape' && !busy.value && !adding.value) close()
}

onMounted(async () => {
    document.body.style.overflow = 'hidden'
    window.addEventListener('keydown', onKey)
    await load()
    if (props.startAdding) nextTick(() => newInput.value?.focus())
})
onBeforeUnmount(() => {
    document.body.style.overflow = ''
    window.removeEventListener('keydown', onKey)
})
</script>

<style>
.tkx-mdl.tkx-mgr { width: min(640px, 100%); }
.tkx-mgr .tkx-mdl-h { flex-wrap: nowrap; align-items: flex-start; }
.tkx-mgr-ic { flex: none; width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; background: var(--acc-soft); color: var(--acc-ink); font-size: 15px; }
.tkx-mgr-head { min-width: 0; }
.tkx-mgr-head b { display: block; color: var(--ink); font-size: 15px; font-weight: 600; line-height: 1.3; }
.tkx-mgr-head small { color: var(--mute); font-size: 12px; }
.tkx-mgr-b { padding: 16px 18px; display: grid; gap: 16px; max-height: 70vh; overflow-y: auto; }
.tkx-mgr-list { list-style: none; margin: 0; padding: 0; display: grid; gap: 6px; }
.tkx-mgr-item { border: 1px solid var(--line); border-inline-start: 3px solid var(--c); border-radius: 11px; background: var(--surf); transition: border-color .15s, box-shadow .15s, opacity .15s; }
.tkx-mgr-item.is-open { box-shadow: 0 6px 18px -12px rgba(15, 21, 34, .35); }
.tkx-mgr-item.is-drag { opacity: .45; }
.tkx-mgr-item.is-drop { box-shadow: 0 -2px 0 0 var(--acc); }
.tkx-mgr-row { display: flex; align-items: center; gap: 8px; padding: 7px 8px; }
.tkx-mgr-row .grip { flex: none; width: 22px; text-align: center; color: var(--mute); cursor: grab; opacity: .7; }
.tkx-mgr-row .grip:active { cursor: grabbing; }
.tkx-mgr-row .swatch { flex: none; width: 32px; height: 32px; border-radius: 9px; border: 0; display: grid; place-items: center; background: color-mix(in srgb, var(--c) 16%, transparent); color: var(--c); cursor: pointer; transition: box-shadow .15s; }
.tkx-mgr-row button.swatch:hover, .tkx-mgr-item.is-open .swatch { box-shadow: 0 0 0 2px color-mix(in srgb, var(--c) 45%, transparent); }
.tkx-mgr-row .name { flex: 1; min-width: 0; height: 34px; padding: 6px 10px; font-weight: 500; }
.tkx-mgr-list .name:not(:focus) { border-color: transparent; background: transparent; }
.tkx-mgr-list .name:not(:focus):hover { border-color: var(--line); }
.tkx-mgr-row .ct { flex: none; min-width: 26px; text-align: center; font-size: 11px; font-weight: 600; color: var(--mute); background: var(--surf-2); border-radius: 10px; padding: 2px 7px; }
.tkx-mgr-row .mv { flex: none; display: inline-flex; border: 1px solid var(--line); border-radius: 8px; overflow: hidden; }
.tkx-mgr-row .mv button, .tkx-mgr-row .del { border: 0; background: transparent; color: var(--mute); width: 28px; height: 28px; cursor: pointer; font-size: 11px; }
.tkx-mgr-row .mv button + button { border-inline-start: 1px solid var(--line); }
.tkx-mgr-row .mv button:hover:not(:disabled) { color: var(--acc-ink); background: var(--acc-soft); }
.tkx-mgr-row .mv button:disabled, .tkx-mgr-row .del:disabled { opacity: .35; cursor: default; }
.tkx-mgr-row .del { flex: none; border-radius: 8px; font-size: 13px; }
.tkx-mgr-row .del:hover:not(:disabled) { color: var(--bs-danger); background: color-mix(in srgb, var(--bs-danger) 10%, transparent); }
.tkx-mgr-style { padding: 4px 12px 12px 40px; display: grid; gap: 10px; }
.tkx-mgr-swatches { display: flex; flex-wrap: wrap; gap: 7px; }
.tkx-mgr-swatches button { width: 24px; height: 24px; border-radius: 50%; border: 0; background: var(--c); cursor: pointer; box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .08); transition: transform .12s, box-shadow .12s; }
.tkx-mgr-swatches button:hover { transform: scale(1.1); }
.tkx-mgr-swatches button.on { box-shadow: 0 0 0 2px var(--surf), 0 0 0 4px var(--c); }
.tkx-mgr-icons { display: grid; grid-template-columns: repeat(auto-fill, minmax(32px, 1fr)); gap: 4px; }
.tkx-mgr-icons button { height: 32px; border-radius: 8px; border: 1px solid transparent; background: var(--surf-2); color: var(--mute); cursor: pointer; }
.tkx-mgr-icons button:hover { color: var(--ink); border-color: var(--line); }
.tkx-mgr-icons button.on { color: var(--c); background: color-mix(in srgb, var(--c) 14%, var(--surf)); border-color: color-mix(in srgb, var(--c) 45%, transparent); }
.tkx-mgr-swatches.sm button { width: 20px; height: 20px; }
.tkx-mgr-icons.sm button { height: 28px; font-size: 12px; }
.tkx-mgr-confirm { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; padding: 10px 12px; border-top: 1px dashed var(--line); background: color-mix(in srgb, var(--bs-danger) 4%, transparent); border-radius: 0 0 10px 10px; font-size: 12.5px; }
.tkx-mgr-confirm b { color: var(--ink); }
.tkx-mgr-confirm .tkx-inp { min-width: 150px; }
.tkx-mgr-confirm .sp { flex: 1; }
.tkx-btn.danger-solid { background: var(--bs-danger); border-color: var(--bs-danger); color: #fff; }
.tkx-btn.danger-solid:hover { background: color-mix(in srgb, var(--bs-danger), #000 10%); color: #fff; }
.tkx-mgr-add { display: grid; gap: 10px; padding: 14px; border: 1px dashed var(--acc-line); border-radius: 12px; background: color-mix(in srgb, var(--acc) 3%, transparent); }
.tkx-mgr-add .tkx-label { margin: 0; }
.tkx-mgr-add .tkx-mgr-row { padding: 0; }
.tkx-mgr-count { color: var(--mute); font-size: 12px; }

@media (max-width: 575px) {
    .tkx-mgr-b { max-height: none; padding: 14px 12px; }
    .tkx-mgr-row { gap: 6px; padding: 6px; }
    .tkx-mgr-row .grip { display: none; }
    .tkx-mgr-style { padding-inline-start: 12px; }
    .tkx-mgr-add .tkx-mgr-row { flex-wrap: wrap; }
    .tkx-mgr-add .tkx-btn.pri { width: 100%; }
}
</style>
