<script setup>
/**
 * The home dashboard (".dbx", Soft Card). Widgets sit on a 12-column grid
 * (6 on tablets, 1 on phones). With `dashboard.customize`, Customize turns on
 * edit mode: drag to reorder (FLIP-animated), resize by S/M/L/XL or the right
 * edge, collapse, hide, re-add from the library, and add / rename dividers.
 * Every change is undoable and autosaved to the user's layout.
 */
import { computed, nextTick, onBeforeUnmount, onMounted, provide, ref, watch } from 'vue'
import { useToast } from 'vue-toastification'
import DashboardWidget from './DashboardWidget.vue'
import WidgetLibrary from './WidgetLibrary.vue'
import { dashboardApi, errorMessage } from './api.js'
import { createFormatter } from './format.js'
import { itemId, useLayout } from './useLayout.js'

const props = JSON.parse(document.getElementById('dashboard-app')?.dataset.props || '{}')
const toast = useToast()
const api = dashboardApi(props.urls)
provide('fmt', createFormatter(props.currency))

const SPANS = [4, 6, 8, 12]
const SIZE_LABEL = { 4: 'S', 6: 'M', 8: 'L', 12: 'XL' }
const catalogue = Object.fromEntries((props.catalogue || []).map((def) => [def.key, def]))
const canCustomize = !!props.canCustomize
const clone = (value) => JSON.parse(JSON.stringify(value))
const reduced = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches
const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms))

/** Drop widgets the user can't see and dividers left with nothing under them. */
function clean(list) {
    const kept = list.filter((item) => item.type === 'divider' || catalogue[item.key])
    return kept.filter((item, i) => item.type !== 'divider' || (kept[i + 1] && kept[i + 1].type !== 'divider'))
}

const { layout, undoStack, redoStack, saveState, commit, undo, redo, flush, queueSave } = useLayout(clean(props.layout || []), {
    save: (value) => api.saveLayout(value),
    persist: () => canCustomize,
    onError: (error) => toast.error(`Couldn't save your layout. ${errorMessage(error)}`),
})

const page = ref(null)
const gw = ref(null)
const grid = ref(null)
const editing = ref(false)
const libraryOpen = ref(false)
const cols = ref(12)
const refreshToken = ref(0)
const updatedAt = ref(Date.now())
const clock = ref(Date.now())
const menu = ref(null)
const live = ref('')
const tip = ref(null)
const widgetRefs = {}

const say = (message) => { live.value = ''; requestAnimationFrame(() => { live.value = message }) }
const titleOf = (item) => (item.key ? catalogue[item.key]?.title : `Section “${item.title}”`)
const indexOf = (list, id) => list.findIndex((item) => itemId(item) === id)
const hidden = computed(() => Object.values(catalogue).filter((def) => !layout.value.some((item) => item.key === def.key)))
const hasWidgets = computed(() => layout.value.some((item) => item.key))

const hour = new Date().getHours()
const greeting = `Good ${hour < 12 ? 'morning' : hour < 17 ? 'afternoon' : 'evening'}${props.firstName ? ', ' + props.firstName : ''}`
const today = new Date().toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
const updatedLabel = computed(() => {
    const minutes = Math.floor((clock.value - updatedAt.value) / 60000)
    return minutes < 1 ? 'Updated just now' : `Updated ${minutes} min ago`
})

/* ---------------- layout engine ----------------
 * Cards keep their own (natural) height; nothing stretches to match a
 * neighbour. Cards flow left to right in rows on 12 columns (6 on tablets, 1
 * on phones); a row starts below the cards above it, except beside an
 * expanded (`tall`) card, which runs down the side while the next row tucks in
 * next to it. While customizing, free space at the end of each row and a strip
 * between rows become drop zones; they are gone again after Done.
 */
const SLOT_ROW = 54
const slots = ref([])
const hotSlot = ref(-1)
const ready = ref(false)
let model = []
let layoutFrame = 0

const scheduleLayout = () => {
    if (!layoutFrame) layoutFrame = requestAnimationFrame(() => { layoutFrame = 0; relayout() })
}

function relayout() {
    const g = grid.value
    if (!g) return
    const n = cols.value
    const gp = gap()
    const colWidth = (g.clientWidth - gp * (n - 1)) / n
    const left = (column) => column * (colWidth + gp)
    const widthOf = (span) => span * colWidth + (span - 1) * gp
    const effective = (span) => (n === 12 ? span : n === 6 ? (span <= 6 ? 3 : 6) : 1)
    const zones = editing.value && n > 1

    const items = [...g.children].filter((el) => el.dataset.id && !el.classList.contains('is-drag')).map((el) => {
        const divider = el.dataset.type === 'divider'
        const w = divider ? n : Math.min(n, effective(+el.dataset.span || 12))
        el.style.width = `${widthOf(w)}px`
        return { el, id: el.dataset.id, w, divider, tall: n === 12 && el.hasAttribute('data-tall'), pin: el.hasAttribute('data-row') }
    })
    items.forEach((item) => { item.h = item.el.offsetHeight })

    let y = 0
    let row = []
    let carry = []
    let placed = 0
    const zonesOut = []
    const blocked = (from, to) => carry.some((c) => from < c.to && to > c.from)
    const freeFrom = (from) => { let to = from; while (to < n && !blocked(to, to + 1)) to++; return to }
    const findX = (from, w) => { for (let x = from; x + w <= n; x++) if (!blocked(x, x + w)) return x; return null }
    const clearCarry = () => { y = Math.max(y, ...carry.map((c) => c.bottom + gp)); carry = [] }

    const closeRow = () => {
        if (!row.length) return
        const top = row[0].y
        const flat = row.filter((item) => !item.tall)
        const bottom = Math.max(...(flat.length ? flat : row).map((item) => item.y + item.h))
        if (zones) {
            const last = row[row.length - 1]
            const from = last.x + last.w
            const to = freeFrom(from)
            if (to > from) zonesOut.push({ index: placed, newRow: false, left: left(from), top, width: widthOf(to - from), height: Math.max(80, bottom - top) })
        }
        const expired = carry.map((c) => c.bottom)
        carry = row.filter((item) => item.tall).map((item) => ({ from: item.x, to: item.x + item.w, bottom: item.y + item.h }))
        y = Math.max(bottom, ...expired.filter((b) => b > bottom)) + gp
        if (zones) {
            const from = findX(0, 1) ?? 0
            zonesOut.push({ index: placed, newRow: true, left: left(from), top: y, width: widthOf(freeFrom(from) - from), height: SLOT_ROW })
            y += SLOT_ROW + gp
        }
        row = []
    }

    for (const item of items) {
        if (item.divider) {
            closeRow()
            clearCarry()
            item.x = 0
            item.y = y
            y += item.h + gp
        } else {
            if (item.pin && row.length) closeRow()
            const from = row.length ? row[row.length - 1].x + row[row.length - 1].w : 0
            let x = findX(from, item.w)
            if (x === null) { closeRow(); x = findX(0, item.w) }
            if (x === null) { clearCarry(); x = 0 }
            item.x = x
            item.y = row.length ? row[0].y : y
            row.push(item)
        }
        if (item.id !== '__ph') placed++
        item.el.style.left = `${left(item.x)}px`
        item.el.style.top = `${item.y}px`
    }
    closeRow()
    clearCarry()

    model = items.map((item) => ({ id: item.id, left: left(item.x), top: item.y, width: widthOf(item.w), height: item.h }))
    slots.value = zonesOut
    g.style.height = `${Math.max(0, y - gp)}px`
    if (!ready.value) requestAnimationFrame(() => { ready.value = true })
}

async function animate(mutate) {
    const result = mutate()
    await nextTick()
    scheduleLayout()
    return result
}

/* ---------------- layout actions ---------------- */
/**
 * Which of the resized widget and the one after it begin a grid row right
 * now. A resize pins them there (`row`), so shrinking a card never pulls it up
 * into the row above and the card below never jumps up beside it.
 */
function rowStarts(key) {
    const at = indexOf(layout.value, key)
    const starts = {}
    for (const item of [layout.value[at], layout.value[at + 1]]) {
        const el = item?.key && grid.value?.querySelector(`[data-id="${item.key}"]`)
        if (el && el.offsetLeft < 2) starts[item.key] = true
    }
    return starts
}
function setSize(key, span, starts = rowStarts(key)) {
    const def = catalogue[key]
    if (!def || span < def.minSpan) return
    animate(() => commit((next) => {
        next[indexOf(next, key)].span = span
        Object.keys(starts).forEach((startKey) => { next[indexOf(next, startKey)].row = true })
    })).then((changed) => changed && say(`${def.title} set to ${SIZE_LABEL[span]} width`))
}
function stepSize(key, dir) {
    const item = layout.value[indexOf(layout.value, key)]
    const allowed = SPANS.filter((span) => span >= catalogue[key].minSpan)
    const at = allowed.indexOf(item.span)
    setSize(key, allowed[Math.max(0, Math.min(allowed.length - 1, at + dir))])
}
function toggleTall(key) {
    const item = layout.value[indexOf(layout.value, key)]
    animate(() => commit((next) => {
        const target = next[indexOf(next, key)]
        if (target.tall) delete target.tall
        else target.tall = true
    }))
    say(`${catalogue[key].title} ${item.tall ? 'now one row tall' : 'now two rows tall'}`)
}
function toggleCollapse(key) {
    const item = layout.value[indexOf(layout.value, key)]
    animate(() => commit((next) => { next[indexOf(next, key)].collapsed = !item.collapsed }))
    say(`${catalogue[key].title} ${item.collapsed ? 'expanded' : 'collapsed'}`)
}
function hide(key) {
    animate(() => commit((next) => next.splice(indexOf(next, key), 1)))
    say(`${catalogue[key].title} hidden`)
    if (!editing.value) toast.info(`${catalogue[key].title} hidden. Click here to undo.`, { onClick: () => animate(undo) })
}
async function move(id, dir) {
    const from = indexOf(layout.value, id)
    const to = from + dir
    if (from < 0 || to < 0 || to >= layout.value.length) return
    await animate(() => commit((next) => {
        const [moved] = next.splice(from, 1)
        delete moved.row
        next.splice(to, 0, moved)
    }))
    say(`${titleOf(layout.value[to])} moved to position ${to + 1} of ${layout.value.length}`)
    focusGrip(id)
}
async function addWidget(key, at = null) {
    const def = catalogue[key]
    await animate(() => commit((next) => next.splice(at ?? next.length, 0, { key, span: def.span, collapsed: false, ...(def.tall ? { tall: true } : {}) })))
    say(`${def.title} added`)
    flash(key)
}
async function addDivider() {
    const id = `section-${Date.now().toString(36)}`
    await animate(() => commit((next) => next.push({ type: 'divider', id, title: 'New section' })))
    const title = grid.value.querySelector(`[data-id="${id}"] .dbx-div-t`)
    title?.scrollIntoView({ behavior: reduced() ? 'auto' : 'smooth', block: 'center' })
    setTimeout(() => {
        title?.focus()
        const range = document.createRange()
        range.selectNodeContents(title)
        window.getSelection().removeAllRanges()
        window.getSelection().addRange(range)
    }, 250)
}
function renameDivider(id, text) {
    const title = (text || '').trim().slice(0, 60) || 'Untitled section'
    commit((next) => { next[indexOf(next, id)].title = title })
}
function removeDivider(id) {
    animate(() => commit((next) => next.splice(indexOf(next, id), 1)))
    say('Section divider removed')
}
async function reset() {
    const confirmed = window.Swal
        ? (await window.Swal.fire({ title: 'Reset to the default layout?', text: 'Order, widths, hidden widgets and dividers go back to how the dashboard ships. You can undo this.', icon: 'question', showCancelButton: true, confirmButtonText: 'Reset layout' })).isConfirmed
        : window.confirm('Reset to the default layout?')
    if (!confirmed) return
    if (await animate(() => commit((next) => next.splice(0, next.length, ...clean(clone(props.defaultLayout || [])))))) {
        toast.info('Layout reset to default. Click here to undo.', { onClick: () => animate(undo) })
    }
}
function flash(id) {
    nextTick(() => {
        const el = grid.value?.querySelector(`[data-id="${id}"]`)
        if (!el) return
        el.classList.remove('is-new')
        void el.offsetWidth
        el.classList.add('is-new')
        el.scrollIntoView({ behavior: reduced() ? 'auto' : 'smooth', block: 'nearest' })
    })
}
function focusGrip(id) {
    nextTick(() => grid.value?.querySelector(`[data-id="${id}"] .dbx-grip`)?.focus({ preventScroll: true }))
}
function refreshAll() {
    refreshToken.value++
    updatedAt.value = Date.now()
    clock.value = Date.now()
}
async function setEditing(on) {
    editing.value = on
    menu.value = null
    if (on) return say('Customizing. Drag a handle, or focus it and use the arrow keys.')
    libraryOpen.value = false
    await flush()
    if (saveState.value === 'saved') toast.success('Layout saved')
}

/* ---------------- drag to reorder ---------------- */
const drag = ref(null)
let raf = 0
const others = computed(() => (drag.value ? layout.value.filter((item) => itemId(item) !== drag.value.id) : layout.value))
const entries = computed(() => {
    if (!drag.value) return layout.value.map((item) => ({ id: itemId(item), item }))
    const list = others.value.map((item) => ({ id: itemId(item), item }))
    list.splice(drag.value.index, 0, { id: '__ph', placeholder: true })
    list.push({ id: drag.value.id, item: drag.value.item, dragging: true })
    return list
})

function gap() {
    return parseFloat(getComputedStyle(grid.value).columnGap) || 14
}
function widthFor(span) {
    const effective = cols.value === 12 ? span : cols.value === 6 ? (span <= 6 ? 3 : 6) : 1
    const column = (grid.value.clientWidth - gap() * (cols.value - 1)) / cols.value
    return effective * column + (effective - 1) * gap()
}

async function startDrag(event, item, fromLib = false) {
    if (cols.value === 1 || drag.value) return
    event.preventDefault()
    const id = itemId(item)
    const el = fromLib ? null : grid.value.querySelector(`[data-id="${id}"]`)
    const rect = el ? el.getBoundingClientRect() : { left: event.clientX - 40, top: event.clientY - 18, width: widthFor(item.span), height: 180 }
    const index = fromLib ? layout.value.length : indexOf(layout.value, id)
    drag.value = {
        id, item, index, origin: index, fromLib, newRow: false, originRow: !!item.row,
        span: item.span ?? 12, divider: item.type === 'divider',
        width: rect.width, height: Math.min(rect.height, 420),
        ox: event.clientX - rect.left, oy: event.clientY - rect.top, x: event.clientX, y: event.clientY, dirty: true,
    }
    window.addEventListener('pointermove', onDragMove)
    window.addEventListener('pointerup', onDragUp)
    window.addEventListener('pointercancel', onDragCancel)
    window.addEventListener('keydown', onDragKey, true)
    document.body.style.userSelect = 'none'
    if (fromLib && window.innerWidth < 1200) libraryOpen.value = false
    await nextTick()
    place()
    raf = requestAnimationFrame(tick)
}
function place() {
    const d = drag.value
    const el = grid.value?.querySelector('.is-drag')
    if (!d || !el) return
    const g = grid.value.getBoundingClientRect()
    el.style.transform = `translate3d(${d.x - d.ox - g.left}px, ${d.y - d.oy - g.top}px, 0)`
}
function onDragMove(event) {
    if (!drag.value) return
    drag.value.x = event.clientX
    drag.value.y = event.clientY
    drag.value.dirty = true
}
function tick() {
    const d = drag.value
    if (!d) return
    const edge = 80
    const dy = d.y < edge ? -Math.ceil((edge - d.y) / 4) : d.y > window.innerHeight - edge ? Math.ceil((d.y - window.innerHeight + edge) / 4) : 0
    if (dy) { window.scrollBy(0, dy); d.dirty = true }
    if (d.dirty) { d.dirty = false; place(); retarget() }
    raf = requestAnimationFrame(tick)
}
function retarget() {
    const d = drag.value
    const g = grid.value.getBoundingClientRect()
    const x = d.x - g.left
    const y = d.y - g.top
    const inside = (r) => x >= r.left && x <= r.left + r.width && y >= r.top && y <= r.top + r.height
    let target = null
    let newRow = false
    const zone = slots.value.findIndex(inside)
    hotSlot.value = zone
    if (zone >= 0) {
        target = slots.value[zone].index
        newRow = slots.value[zone].newRow
    } else {
        const hit = model.find((r) => r.id !== '__ph' && inside(r))
        if (hit) {
            const wide = hit.width > grid.value.clientWidth * 0.75
            const before = wide ? y < hit.top + hit.height / 2 : x < hit.left + hit.width / 2
            const at = indexOf(others.value, hit.id)
            target = before ? at : at + 1
        } else {
            if (x < -60 || x > g.width + 60) return
            if (y <= Math.max(0, ...model.filter((r) => r.id !== '__ph').map((r) => r.top + r.height))) return
            target = others.value.length
        }
    }
    if (target !== d.index || newRow !== d.newRow) animate(() => { d.index = target; d.newRow = newRow })
}
const overLibrary = (event) => libraryOpen.value && event.clientX > (document.querySelector('.dbx-lib')?.getBoundingClientRect().left ?? Infinity)
const onDragUp = (event) => finishDrag(!(drag.value?.fromLib && overLibrary(event)))
const onDragCancel = () => finishDrag(false)
function onDragKey(event) {
    if (event.key !== 'Escape') return
    event.preventDefault()
    event.stopPropagation()
    finishDrag(false)
}
async function finishDrag(ok) {
    const d = drag.value
    if (!d || d.finishing) return
    d.finishing = true
    cancelAnimationFrame(raf)
    window.removeEventListener('pointermove', onDragMove)
    window.removeEventListener('pointerup', onDragUp)
    window.removeEventListener('pointercancel', onDragCancel)
    window.removeEventListener('keydown', onDragKey, true)
    document.body.style.userSelect = ''

    if (!ok && d.fromLib) {
        drag.value = null
        return say('Cancelled')
    }
    hotSlot.value = -1
    if (!ok && (d.index !== d.origin || d.newRow !== d.originRow)) await animate(() => { d.index = d.origin; d.newRow = d.originRow })

    const el = grid.value.querySelector('.is-drag')
    const placeholder = grid.value.querySelector('.dbx-ph')
    if (el && placeholder && !reduced()) {
        const g = grid.value.getBoundingClientRect()
        const r = placeholder.getBoundingClientRect()
        el.classList.add('is-landing')
        el.style.transition = 'transform .2s cubic-bezier(.2,.8,.2,1), width .2s'
        el.style.transform = `translate3d(${r.left - g.left}px, ${r.top - g.top}px, 0)`
        el.style.width = `${r.width}px`
        await wait(200)
    }
    if (el) {
        el.classList.remove('is-landing')
        el.style.transition = ''
        el.style.transform = ''
        el.style.width = ''
    }

    const { id, item, index } = d
    drag.value = null
    if (!ok) return say('Move cancelled')
    const changed = commit((next) => {
        const at = indexOf(next, id)
        if (at >= 0) next.splice(at, 1)
        const placed = clone(item)
        if (d.newRow) placed.row = true
        else delete placed.row
        next.splice(index, 0, placed)
    })
    say(changed ? `${titleOf(item)} ${d.fromLib ? 'added at' : 'moved to'} position ${index + 1}` : 'Not moved')
    if (d.fromLib) flash(id)
    else focusGrip(id)
}

/* library: a press that moves becomes a drag, a press that doesn't is a click */
function onLibraryPress(event, def) {
    if (event.button > 0) return
    const startX = event.clientX
    const startY = event.clientY
    const onMove = (e) => {
        if (cols.value === 1 || Math.hypot(e.clientX - startX, e.clientY - startY) < 6) return
        cleanup()
        startDrag(e, { key: def.key, span: def.span, collapsed: false, ...(def.tall ? { tall: true } : {}) }, true)
    }
    const onUp = () => { cleanup(); addWidget(def.key) }
    const cleanup = () => {
        window.removeEventListener('pointermove', onMove)
        window.removeEventListener('pointerup', onUp)
    }
    window.addEventListener('pointermove', onMove)
    window.addEventListener('pointerup', onUp)
}

/* ---------------- resize from the right edge ---------------- */
const resizing = ref(null)
const spanOf = (item) => (resizing.value?.key === item.key ? resizing.value.span : item.span)
const rowOf = (item) => !!(item.row || resizing.value?.rows[item.key])
function startResize(event, item) {
    if (cols.value !== 12) return
    event.preventDefault()
    const def = catalogue[item.key]
    const width = event.currentTarget.closest('.dbx-w').getBoundingClientRect().width
    const column = (grid.value.clientWidth + gap()) / 12
    const allowed = SPANS.filter((span) => span >= def.minSpan)
    resizing.value = { key: item.key, span: item.span, rows: rowStarts(item.key) }
    const onMove = (e) => {
        const wanted = (width + e.clientX - event.clientX + gap()) / column
        const span = allowed.reduce((best, s) => (Math.abs(s - wanted) < Math.abs(best - wanted) ? s : best))
        if (span !== resizing.value.span) animate(() => { resizing.value.span = span })
    }
    const onUp = () => {
        window.removeEventListener('pointermove', onMove)
        window.removeEventListener('pointerup', onUp)
        const { span, rows } = resizing.value
        resizing.value = null
        setSize(item.key, span, rows)
    }
    window.addEventListener('pointermove', onMove)
    window.addEventListener('pointerup', onUp)
}

/* ---------------- widget menu ---------------- */
function openMenu(key, button) {
    const r = button.getBoundingClientRect()
    menu.value = { key, top: r.bottom + 6, left: Math.max(8, r.right - 210) }
}
function menuAction(action) {
    const key = menu.value.key
    menu.value = null
    if (action === 'refresh') widgetRefs[key]?.reload()
    if (action === 'collapse') toggleCollapse(key)
    if (action === 'hide') hide(key)
}

/* ---------------- page-level listeners ---------------- */
function onKeydown(event) {
    if (event.key === 'Escape') {
        if (menu.value) menu.value = null
        else if (libraryOpen.value) libraryOpen.value = false
        return
    }
    if (!editing.value || !(event.metaKey || event.ctrlKey) || event.key.toLowerCase() !== 'z') return
    if (event.target.isContentEditable || event.target.matches('input, textarea')) return
    event.preventDefault()
    animate(event.shiftKey ? redo : undo)
}
function onPointerDown(event) {
    if (menu.value && !event.target.closest('.dbx-pop, .dbx-more')) menu.value = null
}
function onTipOver(event) {
    const target = event.target.closest?.('[data-tip]')
    tip.value = target && !drag.value ? { text: target.dataset.tip, x: event.clientX, y: event.clientY } : null
}
function onTipMove(event) {
    if (tip.value) tip.value = { ...tip.value, x: event.clientX, y: event.clientY }
}
const closeMenu = () => { menu.value = null }

watch([entries, editing, cols, () => drag.value?.newRow], () => nextTick(scheduleLayout))

let resizeObserver = null
let itemObserver = null
let childObserver = null
let clockTimer = 0
let autoRefresh = 0
onMounted(() => {
    resizeObserver = new ResizeObserver(() => {
        const width = gw.value.clientWidth
        cols.value = width >= 960 ? 12 : width >= 640 ? 6 : 1
        scheduleLayout()
    })
    resizeObserver.observe(gw.value)
    itemObserver = new ResizeObserver(scheduleLayout)
    const observeChildren = () => [...grid.value.children].forEach((el) => itemObserver.observe(el))
    childObserver = new MutationObserver(() => { observeChildren(); scheduleLayout() })
    childObserver.observe(grid.value, { childList: true })
    observeChildren()
    relayout()
    window.addEventListener('keydown', onKeydown)
    window.addEventListener('pointerdown', onPointerDown)
    window.addEventListener('scroll', closeMenu, { passive: true })
    clockTimer = setInterval(() => { clock.value = Date.now() }, 30000)
    autoRefresh = setInterval(() => { if (document.visibilityState === 'visible' && !editing.value && !drag.value) refreshAll() }, 5 * 60 * 1000)
})
onBeforeUnmount(() => {
    resizeObserver?.disconnect()
    itemObserver?.disconnect()
    childObserver?.disconnect()
    cancelAnimationFrame(layoutFrame)
    window.removeEventListener('keydown', onKeydown)
    window.removeEventListener('pointerdown', onPointerDown)
    window.removeEventListener('scroll', closeMenu)
    clearInterval(clockTimer)
    clearInterval(autoRefresh)
})
</script>

<template>
    <div class="dbx" :class="{ 'is-editing': editing, 'is-dragging': drag, 'lib-open': libraryOpen, 'is-c12': cols === 12, 'is-c1': cols === 1 }"
        @mouseover="onTipOver" @mousemove="onTipMove" @mouseleave="tip = null">
        <div ref="page" class="dbx-page">
            <div class="dbx-top">
                <div class="dbx-greet">
                    <div class="dbx-eyebrow">
                        <i class="fa fa-calendar-o"></i> {{ today }}
                        <template v-if="props.branch"><span>·</span><i class="fa fa-code-fork"></i> {{ props.branch }}</template>
                    </div>
                    <h1>{{ greeting }}</h1>
                    <p>Here's how the business is doing today.</p>
                </div>
                <div class="dbx-acts">
                    <span class="dbx-upd">{{ updatedLabel }}</span>
                    <button class="dbx-btn" type="button" @click="refreshAll"><i class="fa fa-refresh"></i><span class="tx">Refresh</span></button>
                    <button v-if="canCustomize" class="dbx-btn pri" type="button" @click="setEditing(true)"><i class="fa fa-sliders"></i><span class="tx">Customize</span></button>
                </div>
            </div>

            <div v-if="editing" class="dbx-bar" role="toolbar" aria-label="Dashboard editor">
                <span class="dbx-bar-t"><i class="fa fa-th-large"></i><b>Customizing</b><span class="dbx-bar-h">Drag to move · S M L XL to resize · hide what you don't need</span></span>
                <button class="dbx-btn" type="button" @click="libraryOpen = !libraryOpen"><i class="fa fa-plus"></i><span class="tx">Add widget</span><span v-if="hidden.length" class="dbx-count">{{ hidden.length }}</span></button>
                <button class="dbx-btn" type="button" @click="addDivider"><i class="fa fa-minus"></i><span class="tx">Add divider</span></button>
                <span class="dbx-sep"></span>
                <button class="dbx-ib" type="button" title="Undo (Ctrl/⌘ Z)" aria-label="Undo" :disabled="!undoStack.length" @click="animate(undo)"><i class="fa fa-undo"></i></button>
                <button class="dbx-ib" type="button" title="Redo (Ctrl/⌘ Shift Z)" aria-label="Redo" :disabled="!redoStack.length" @click="animate(redo)"><i class="fa fa-repeat"></i></button>
                <button class="dbx-ib" type="button" title="Reset to default" aria-label="Reset to default" @click="reset"><i class="fa fa-history"></i></button>
                <span class="dbx-save" :data-s="saveState">
                    <template v-if="saveState === 'saving'"><i class="fa fa-refresh fa-spin"></i> Saving…</template>
                    <template v-else-if="saveState === 'failed'"><i class="fa fa-exclamation-circle"></i> Not saved <button type="button" @click="queueSave">Retry</button></template>
                    <template v-else><i class="fa fa-check"></i> Saved</template>
                </span>
                <button class="dbx-btn pri" type="button" @click="setEditing(false)"><i class="fa fa-check"></i><span class="tx">Done</span></button>
            </div>

            <div ref="gw" class="dbx-gw">
                <div class="dbx-guides" aria-hidden="true"><i v-for="n in 12" :key="n"></i></div>
                <div v-if="editing && slots.length" class="dbx-zones" aria-hidden="true">
                    <div v-for="(zone, i) in slots" :key="i" class="dbx-zone" :class="{ hot: drag && hotSlot === i, strip: zone.newRow }"
                        :style="{ left: zone.left + 'px', top: zone.top + 'px', width: zone.width + 'px', height: zone.height + 'px' }">
                        <i class="fa fa-plus"></i><span>{{ zone.newRow ? 'Drop here for a new row' : 'Drop here' }}</span>
                    </div>
                </div>
                <div ref="grid" class="dbx-grid" :class="{ 'is-ready': ready }">
                    <template v-for="entry in entries" :key="entry.id">
                        <div v-if="entry.placeholder" class="dbx-ph" data-id="__ph" :data-span="drag.span" :data-type="drag.divider ? 'divider' : null" :data-row="drag.newRow ? '' : null" :style="{ height: drag.height + 'px' }"></div>

                        <div v-else-if="entry.item.type === 'divider'" class="dbx-div" :class="{ 'is-drag': entry.dragging }" data-type="divider" :data-id="entry.id" :style="entry.dragging ? { width: drag.width + 'px' } : null">
                            <template v-if="editing">
                                <button class="dbx-grip" type="button" aria-label="Move section divider. Arrow keys move it." title="Drag to move" @pointerdown="startDrag($event, entry.item)"
                                    @keydown.up.prevent="move(entry.id, -1)" @keydown.left.prevent="move(entry.id, -1)" @keydown.down.prevent="move(entry.id, 1)" @keydown.right.prevent="move(entry.id, 1)"><i class="fa fa-arrows"></i></button>
                                <span class="dbx-mv">
                                    <button class="dbx-ib" type="button" aria-label="Move up" @click="move(entry.id, -1)"><i class="fa fa-chevron-up"></i></button>
                                    <button class="dbx-ib" type="button" aria-label="Move down" @click="move(entry.id, 1)"><i class="fa fa-chevron-down"></i></button>
                                </span>
                            </template>
                            <h2 :key="entry.item.title" class="dbx-div-t" :contenteditable="editing ? 'true' : 'false'" spellcheck="false"
                                @keydown.enter.prevent="$event.target.blur()" @blur="editing && renameDivider(entry.id, $event.target.textContent)">{{ entry.item.title }}</h2>
                            <span class="dbx-div-rule"></span>
                            <button v-if="editing" class="dbx-ib dbx-div-x" type="button" aria-label="Remove section divider" title="Remove divider" @click="removeDivider(entry.id)"><i class="fa fa-times"></i></button>
                        </div>

                        <DashboardWidget v-else :ref="(el) => { if (el) widgetRefs[entry.id] = el }" :def="catalogue[entry.item.key]" :item="entry.item" :span="spanOf(entry.item)" :data-row="rowOf(entry.item) ? '' : null" :data-tall="entry.item.tall && !entry.item.collapsed ? '' : null" :api="api"
                            :editing="editing" :can-customize="canCustomize" :refresh-token="refreshToken"
                            :resize-hint="resizing?.key === entry.item.key ? `${SIZE_LABEL[resizing.span]} · ${resizing.span} / 12` : ''"
                            :class="{ 'is-drag': entry.dragging, 'is-resizing': resizing?.key === entry.item.key }" :style="entry.dragging ? { width: drag.width + 'px' } : null"
                            @size="setSize(entry.item.key, $event)" @tall="toggleTall(entry.item.key)" @collapse="toggleCollapse(entry.item.key)" @hide="hide(entry.item.key)" @move="move(entry.id, $event)"
                            @keymove="move(entry.id, $event)" @keysize="stepSize(entry.item.key, $event)" @grip="startDrag($event, entry.item)" @resize="startResize($event, entry.item)"
                            @menu="openMenu(entry.item.key, $event)" />
                    </template>
                </div>
            </div>

            <div v-if="!hasWidgets" class="dbx-empty">
                <i class="fa fa-th-large"></i><b>Your dashboard is empty</b>
                <template v-if="canCustomize">Add the widgets you want to see every day.<br /><button class="dbx-btn pri" type="button" @click="setEditing(true); libraryOpen = true"><i class="fa fa-plus"></i> Add widgets</button></template>
                <template v-else>Ask an administrator for access to dashboard widgets.</template>
            </div>
        </div>

        <WidgetLibrary v-if="canCustomize" :open="libraryOpen" :widgets="hidden" @add="addWidget" @press="onLibraryPress" @close="libraryOpen = false" />

        <div v-if="menu" class="dbx-pop" :style="{ top: menu.top + 'px', left: menu.left + 'px' }" role="menu">
            <button type="button" role="menuitem" @click="menuAction('refresh')"><i class="fa fa-refresh"></i>Refresh</button>
            <button type="button" role="menuitem" @click="menuAction('collapse')">
                <i class="fa" :class="layout[indexOf(layout, menu.key)]?.collapsed ? 'fa-expand' : 'fa-compress'"></i>{{ layout[indexOf(layout, menu.key)]?.collapsed ? 'Expand' : 'Collapse' }}
            </button>
            <button v-if="canCustomize" type="button" role="menuitem" @click="menuAction('hide')"><i class="fa fa-eye-slash"></i>Hide from dashboard</button>
        </div>

        <div v-if="tip" class="dbx-tip" :style="{ left: tip.x + 'px', top: tip.y - 34 + 'px' }">{{ tip.text }}</div>
        <div class="dbx-sr" aria-live="polite">{{ live }}</div>
    </div>
</template>
