<template>
    <div class="tkg" role="dialog" aria-modal="true" :aria-label="`Attachment ${index + 1} of ${items.length}`">
        <header class="tkg-bar">
            <div class="tkg-meta">
                <span class="tkg-ico"><i class="fa" :class="iconOf(current)"></i></span>
                <div class="tkg-txt">
                    <b :title="current.name">{{ current.name }}</b>
                    <small>{{ index + 1 }} of {{ items.length }}<template v-if="current.size"> · {{ formatSize(current.size) }}</template></small>
                </div>
            </div>
            <div class="tkg-tools">
                <template v-if="current.is_image">
                    <div class="tkg-zoom">
                        <button type="button" title="Zoom out (−)" :disabled="scale <= MIN_SCALE" @click="zoomBy(1 / 1.4)"><i class="fa fa-search-minus"></i></button>
                        <button type="button" class="pct" title="Fit to screen (0)" @click="resetView">{{ Math.round(scale * 100) }}%</button>
                        <button type="button" title="Zoom in (+)" :disabled="scale >= MAX_SCALE" @click="zoomBy(1.4)"><i class="fa fa-search-plus"></i></button>
                    </div>
                    <button type="button" class="tkg-tool" title="Rotate (R)" @click="rotate = (rotate + 90) % 360"><i class="fa fa-repeat"></i></button>
                </template>
                <a class="tkg-tool" :href="current.url" target="_blank" rel="noopener" title="Open in new tab"><i class="fa fa-external-link"></i></a>
                <a class="tkg-tool" :href="current.url" :download="current.name" title="Download"><i class="fa fa-download"></i></a>
                <button v-if="canRemove" type="button" class="tkg-tool danger" title="Remove attachment" @click="emit('remove', current)"><i class="fa fa-trash-o"></i></button>
                <span class="tkg-div"></span>
                <button ref="closeBtn" type="button" class="tkg-tool" title="Close (Esc)" aria-label="Close" @click="emit('close')"><i class="fa fa-times"></i></button>
            </div>
        </header>

        <div ref="stage" class="tkg-stage" :class="{ zoomed: scale > 1, grabbing: dragging, 'is-image': current.is_image }"
            @wheel="onWheel" @pointerdown="onPointerDown" @pointermove="onPointerMove"
            @pointerup="onPointerUp" @pointercancel="onPointerUp" @dblclick="onDoubleClick">
            <Transition name="tkg-swap" mode="out-in">
                <div :key="current.id" class="tkg-slide">
                    <template v-if="current.is_image">
                        <i v-if="!loaded" class="fa fa-circle-o-notch tkg-spin"></i>
                        <img :src="current.url" :alt="current.name" draggable="false" :class="{ ready: loaded, settling: !dragging && !pinching }"
                            :style="{ transform: `translate(${tx}px, ${ty}px) scale(${scale}) rotate(${rotate}deg)` }"
                            @load="loaded = true" @error="loaded = true">
                    </template>
                    <video v-else-if="current.is_video" :src="current.url" controls autoplay playsinline></video>
                    <iframe v-else-if="isPdf(current)" :src="current.url" :title="current.name"></iframe>
                    <div v-else class="tkg-file">
                        <i class="fa" :class="iconOf(current)"></i>
                        <b>{{ current.name }}</b>
                        <small>No preview for this file type<template v-if="current.size"> · {{ formatSize(current.size) }}</template></small>
                        <a class="tkg-cta" :href="current.url" :download="current.name"><i class="fa fa-download"></i> Download file</a>
                    </div>
                </div>
            </Transition>

            <template v-if="items.length > 1">
                <button type="button" class="tkg-nav prev" title="Previous (←)" aria-label="Previous" @click.stop="go(-1)"><i class="fa fa-angle-left"></i></button>
                <button type="button" class="tkg-nav next" title="Next (→)" aria-label="Next" @click.stop="go(1)"><i class="fa fa-angle-right"></i></button>
            </template>
        </div>

        <footer v-if="items.length > 1" class="tkg-strip">
            <div ref="strip" class="tkg-strip-in">
                <button v-for="(a, i) in items" :key="a.id" type="button" class="tkg-th" :class="{ on: i === index }" :title="a.name" @click="show(i)">
                    <img v-if="a.is_image" :src="a.url" :alt="a.name" loading="lazy" decoding="async">
                    <span v-else class="ph"><i class="fa" :class="iconOf(a)"></i></span>
                </button>
            </div>
        </footer>
        <div v-else-if="current.is_image" class="tkg-hint">Scroll or double-click to zoom · drag to pan</div>
    </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { formatSize } from './ticketMeta.js'

const props = defineProps({
    items: { type: Array, required: true },
    start: { type: Number, default: 0 },
    canRemove: { type: Boolean, default: false },
})
const emit = defineEmits(['close', 'remove'])

const MIN_SCALE = 1
const MAX_SCALE = 6
const SWIPE_DISTANCE = 60

const index = ref(Math.min(Math.max(props.start, 0), props.items.length - 1))
const loaded = ref(false)
const scale = ref(1)
const rotate = ref(0)
const tx = ref(0)
const ty = ref(0)
const dragging = ref(false)
const pinching = ref(false)
const stage = ref(null)
const strip = ref(null)
const closeBtn = ref(null)

const current = computed(() => props.items[index.value] ?? props.items[0])
const pointers = new Map()
let gesture = null
let returnFocus = null

function isPdf(attachment) {
    return attachment.mime?.includes('pdf') || /\.pdf$/i.test(attachment.name ?? '')
}

function iconOf(attachment) {
    if (attachment.is_image) return 'fa-picture-o'
    if (attachment.is_video) return 'fa-film'
    if (isPdf(attachment)) return 'fa-file-pdf-o'
    if (/sheet|excel/.test(attachment.mime ?? '')) return 'fa-file-excel-o'
    if (/word|document/.test(attachment.mime ?? '')) return 'fa-file-word-o'
    return 'fa-file-o'
}

function resetView() {
    scale.value = 1
    tx.value = 0
    ty.value = 0
}

function show(i) {
    const count = props.items.length
    if (!count) return
    index.value = (i + count) % count
}

function go(step) {
    show(index.value + step)
}

/** Zoom keeping the given stage point (relative to its centre) fixed under the cursor. */
function zoomTo(nextScale, point = { x: 0, y: 0 }) {
    const clamped = Math.min(MAX_SCALE, Math.max(MIN_SCALE, nextScale))
    if (clamped === MIN_SCALE) return resetView()
    const ratio = clamped / scale.value
    tx.value = point.x - (point.x - tx.value) * ratio
    ty.value = point.y - (point.y - ty.value) * ratio
    scale.value = clamped
}

function zoomBy(factor) {
    zoomTo(scale.value * factor)
}

function stagePoint(event) {
    const rect = stage.value.getBoundingClientRect()
    return { x: event.clientX - rect.left - rect.width / 2, y: event.clientY - rect.top - rect.height / 2 }
}

function onWheel(event) {
    if (!current.value.is_image) return
    event.preventDefault()
    zoomTo(scale.value * Math.exp(-event.deltaY * 0.0022), stagePoint(event))
}

function onDoubleClick(event) {
    if (!current.value.is_image || event.target.tagName !== 'IMG') return
    if (scale.value > 1) resetView()
    else zoomTo(2.5, stagePoint(event))
}

function distance() {
    const [a, b] = [...pointers.values()]
    return Math.hypot(a.x - b.x, a.y - b.y)
}

function onPointerDown(event) {
    if (event.target.closest('button, a, video, iframe') || event.button > 0) return
    pointers.set(event.pointerId, { x: event.clientX, y: event.clientY })
    stage.value.setPointerCapture?.(event.pointerId)
    if (pointers.size === 2 && current.value.is_image) {
        pinching.value = true
        gesture = { type: 'pinch', distance: distance(), scale: scale.value }
        return
    }
    const onBackdrop = event.target === stage.value || event.target.classList.contains('tkg-slide')
    gesture = { type: 'drag', x: event.clientX, y: event.clientY, tx: tx.value, ty: ty.value, moved: false, onBackdrop }
}

function onPointerMove(event) {
    if (!pointers.has(event.pointerId) || !gesture) return
    pointers.set(event.pointerId, { x: event.clientX, y: event.clientY })
    if (gesture.type === 'pinch' && pointers.size === 2) {
        zoomTo(gesture.scale * (distance() / gesture.distance))
        return
    }
    const dx = event.clientX - gesture.x
    const dy = event.clientY - gesture.y
    if (Math.abs(dx) + Math.abs(dy) > 4) gesture.moved = true
    if (scale.value > 1 && gesture.moved) {
        dragging.value = true
        tx.value = gesture.tx + dx
        ty.value = gesture.ty + dy
    }
}

function onPointerUp(event) {
    if (!pointers.has(event.pointerId)) return
    pointers.delete(event.pointerId)
    if (gesture?.type === 'drag' && scale.value === 1 && props.items.length > 1) {
        const dx = event.clientX - gesture.x
        if (Math.abs(dx) > SWIPE_DISTANCE && Math.abs(dx) > Math.abs(event.clientY - gesture.y)) go(dx < 0 ? 1 : -1)
    }
    if (gesture?.type === 'drag' && gesture.onBackdrop && !gesture.moved) emit('close')
    if (!pointers.size) {
        gesture = null
        dragging.value = false
        pinching.value = false
    }
}

function preloadNeighbours() {
    for (const step of [1, -1]) {
        const neighbour = props.items[(index.value + step + props.items.length) % props.items.length]
        if (neighbour?.is_image) new Image().src = neighbour.url
    }
}

function onKey(event) {
    const keys = {
        Escape: () => emit('close'),
        ArrowRight: () => go(1),
        ArrowLeft: () => go(-1),
        '+': () => current.value.is_image && zoomBy(1.4),
        '=': () => current.value.is_image && zoomBy(1.4),
        '-': () => current.value.is_image && zoomBy(1 / 1.4),
        0: () => resetView(),
        r: () => current.value.is_image && (rotate.value = (rotate.value + 90) % 360),
    }
    const action = keys[event.key]
    if (!action || event.target.closest?.('input, textarea')) return
    event.preventDefault()
    event.stopPropagation()
    action()
}

watch(index, () => {
    loaded.value = false
    rotate.value = 0
    resetView()
    preloadNeighbours()
    nextTick(() => strip.value?.children[index.value]?.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' }))
})

watch(() => props.items.length, (count) => {
    if (!count) emit('close')
    else if (index.value >= count) index.value = count - 1
})

onMounted(() => {
    returnFocus = document.activeElement
    window.addEventListener('keydown', onKey, true)
    preloadNeighbours()
    nextTick(() => {
        closeBtn.value?.focus({ preventScroll: true })
        strip.value?.children[index.value]?.scrollIntoView({ block: 'nearest', inline: 'center' })
    })
})
onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKey, true)
    returnFocus?.focus?.({ preventScroll: true })
})
</script>

<style>
/* Lightbox for ticket attachments — always dark so photos and screenshots read true; accent follows the theme. */
.tkg { position: fixed; inset: 0; z-index: 1080; display: grid; grid-template-rows: auto minmax(0, 1fr) auto; color: #e9edf3; background: rgba(9, 11, 17, .94); animation: tkx-fade .16s ease-out; user-select: none; }
@supports (backdrop-filter: blur(1px)) { .tkg { background: rgba(9, 11, 17, .86); backdrop-filter: blur(18px) saturate(120%); } }

.tkg-bar { display: flex; align-items: center; gap: 16px; padding: 12px 16px; background: linear-gradient(180deg, rgba(0, 0, 0, .45), transparent); position: relative; z-index: 2; }
.tkg-meta { display: flex; align-items: center; gap: 11px; min-width: 0; flex: 1; }
.tkg-ico { flex: 0 0 auto; width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; background: color-mix(in srgb, var(--acc, #4f7cff) 26%, transparent); color: #fff; font-size: 15px; }
.tkg-txt { min-width: 0; display: grid; }
.tkg-txt b { font-weight: 600; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #fff; }
.tkg-txt small { font-size: 11.5px; color: rgba(233, 237, 243, .6); font-variant-numeric: tabular-nums; }

.tkg-tools { display: flex; align-items: center; gap: 6px; flex: 0 0 auto; }
.tkg-tool, .tkg-zoom button { border: 0; height: 36px; min-width: 36px; padding: 0 10px; border-radius: 10px; display: inline-grid; place-items: center; background: rgba(255, 255, 255, .08); color: #e9edf3; cursor: pointer; font-size: 15px; text-decoration: none; transition: background .15s, color .15s; }
.tkg-tool:hover, .tkg-zoom button:hover:not(:disabled) { background: rgba(255, 255, 255, .18); color: #fff; }
.tkg-tool:focus-visible, .tkg-zoom button:focus-visible, .tkg-nav:focus-visible, .tkg-th:focus-visible { outline: 2px solid var(--acc, #4f7cff); outline-offset: 2px; }
.tkg-tool.danger:hover { background: rgba(239, 68, 68, .85); }
.tkg-zoom { display: inline-flex; background: rgba(255, 255, 255, .08); border-radius: 10px; }
.tkg-zoom button { background: transparent; }
.tkg-zoom button:disabled { opacity: .35; cursor: default; }
.tkg-zoom .pct { min-width: 56px; font-size: 12px; font-weight: 600; font-variant-numeric: tabular-nums; }
.tkg-div { width: 1px; height: 22px; background: rgba(255, 255, 255, .16); margin: 0 4px; }

.tkg-stage { position: relative; overflow: hidden; min-height: 0; touch-action: pan-y; }
.tkg-stage.is-image { touch-action: none; }
.tkg-stage.zoomed { cursor: grab; }
.tkg-stage.grabbing { cursor: grabbing; }
.tkg-slide { position: absolute; inset: 0; display: grid; place-items: center; padding: 8px 76px 16px; }
.tkg-slide img { max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 6px; box-shadow: 0 24px 70px -20px rgba(0, 0, 0, .8); opacity: 0; will-change: transform; }
.tkg-slide img.ready { opacity: 1; transition: opacity .2s; }
.tkg-slide img.ready.settling { transition: opacity .2s, transform .18s ease-out; }
.tkg-slide video { max-width: 100%; max-height: 100%; border-radius: 8px; background: #000; box-shadow: 0 24px 70px -20px rgba(0, 0, 0, .8); }
.tkg-slide iframe { width: min(1000px, 100%); height: 100%; border: 0; border-radius: 8px; background: #fff; box-shadow: 0 24px 70px -20px rgba(0, 0, 0, .8); }
.tkg-spin { position: absolute; font-size: 28px; color: rgba(255, 255, 255, .55); animation: tkg-rot .8s linear infinite; }
@keyframes tkg-rot { to { transform: rotate(360deg); } }

.tkg-file { display: grid; justify-items: center; gap: 8px; text-align: center; padding: 36px 40px; border-radius: 18px; background: rgba(255, 255, 255, .06); border: 1px solid rgba(255, 255, 255, .1); max-width: 420px; }
.tkg-file > .fa { font-size: 56px; color: rgba(255, 255, 255, .8); margin-bottom: 6px; }
.tkg-file b { font-size: 15px; color: #fff; overflow-wrap: anywhere; }
.tkg-file small { color: rgba(233, 237, 243, .6); }
.tkg-cta { margin-top: 10px; display: inline-flex; align-items: center; gap: 8px; padding: 9px 18px; border-radius: 10px; background: var(--acc, #4f7cff); color: #fff; font-weight: 600; text-decoration: none; }
.tkg-cta:hover { color: #fff; filter: brightness(1.1); }

.tkg-nav { position: absolute; top: 50%; transform: translateY(-50%); width: 48px; height: 48px; border-radius: 50%; border: 1px solid rgba(255, 255, 255, .14); background: rgba(20, 24, 33, .6); color: #fff; font-size: 26px; line-height: 1; cursor: pointer; display: grid; place-items: center; z-index: 2; transition: background .15s, transform .15s; }
.tkg-nav:hover { background: rgba(255, 255, 255, .2); transform: translateY(-50%) scale(1.06); }
.tkg-nav.prev { left: 16px; }
.tkg-nav.next { right: 16px; }

.tkg-strip { padding: 10px 16px 16px; display: flex; justify-content: center; background: linear-gradient(0deg, rgba(0, 0, 0, .45), transparent); }
.tkg-strip-in { display: flex; gap: 8px; overflow-x: auto; max-width: 100%; padding: 4px; scrollbar-width: none; }
.tkg-strip-in::-webkit-scrollbar { display: none; }
.tkg-th { flex: 0 0 auto; width: 72px; height: 52px; padding: 0; border-radius: 9px; overflow: hidden; border: 2px solid transparent; background: rgba(255, 255, 255, .08); cursor: pointer; opacity: .5; transition: opacity .15s, border-color .15s, transform .15s; }
.tkg-th:hover { opacity: .85; }
.tkg-th.on { opacity: 1; border-color: var(--acc, #4f7cff); transform: translateY(-2px); }
.tkg-th img { width: 100%; height: 100%; object-fit: cover; display: block; }
.tkg-th .ph { display: grid; place-items: center; height: 100%; color: #fff; font-size: 18px; }
.tkg-hint { text-align: center; padding: 10px 16px 18px; font-size: 11.5px; color: rgba(233, 237, 243, .45); }

.tkg-swap-enter-active, .tkg-swap-leave-active { transition: opacity .14s ease, transform .14s ease; }
.tkg-swap-enter-from { opacity: 0; transform: scale(.985); }
.tkg-swap-leave-to { opacity: 0; }

@media (max-width: 767px) {
    .tkg-bar { padding: 10px 10px; gap: 10px; }
    .tkg-zoom, .tkg-div, .tkg-ico { display: none; }
    .tkg-tool { height: 34px; min-width: 34px; padding: 0 8px; }
    .tkg-slide { padding: 4px 8px 10px; }
    .tkg-nav { width: 38px; height: 38px; font-size: 22px; background: rgba(20, 24, 33, .45); }
    .tkg-nav.prev { left: 8px; }
    .tkg-nav.next { right: 8px; }
    .tkg-th { width: 56px; height: 42px; }
}
@media (prefers-reduced-motion: reduce) {
    .tkg, .tkg-swap-enter-active, .tkg-swap-leave-active, .tkg-slide img.ready.settling { animation: none; transition: none; }
}
</style>
