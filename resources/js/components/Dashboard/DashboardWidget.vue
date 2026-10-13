<script setup>
/**
 * One dashboard card. It fetches its own payload the first time it scrolls
 * into view (and again on refresh or when its header option changes), and in
 * edit mode shows the handle bar: grip, width switcher, collapse and hide.
 * Layout changes are emitted; the dashboard owns the layout.
 */
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import WidgetBlocks from './WidgetBlocks.vue'
import { errorMessage, isCancel } from './api.js'

const props = defineProps({
    def: { type: Object, required: true },
    item: { type: Object, required: true },
    span: { type: Number, required: true },
    api: { type: Object, required: true },
    editing: Boolean,
    canCustomize: Boolean,
    refreshToken: { type: Number, default: 0 },
    resizeHint: { type: String, default: '' },
})
const emit = defineEmits(['size', 'tall', 'collapse', 'hide', 'move', 'grip', 'resize', 'menu', 'keymove', 'keysize'])

const SIZES = [
    { value: 4, label: 'S' },
    { value: 6, label: 'M' },
    { value: 8, label: 'L' },
    { value: 12, label: 'XL' },
]

const root = ref(null)
const payload = ref(null)
const loading = ref(false)
const error = ref('')
const option = ref(props.def.defaultOption)
const params = ref({})
let seen = false
let controller = null
let observer = null

async function load({ quiet = false } = {}) {
    controller?.abort()
    controller = new AbortController()
    loading.value = true
    error.value = ''
    if (!quiet) payload.value = null
    try {
        payload.value = await props.api.widget(props.def.key, { option: option.value, ...params.value }, controller.signal)
    } catch (e) {
        if (!isCancel(e)) error.value = errorMessage(e)
    } finally {
        loading.value = false
    }
}

const loaded = () => payload.value !== null || loading.value || error.value
const ensureLoaded = () => { if (seen && !props.item.collapsed && !loaded()) load() }

/** Switch the header option. The current content stays (dimmed) until the new payload arrives, so a tall widget never collapses and jumps the page. */
function choose(value) {
    if (value === option.value) return
    option.value = value
    params.value = {}
    load({ quiet: true })
}

function navigate(date) {
    params.value = { ...params.value, date }
    load({ quiet: true })
}

function onGripKey(event) {
    const dir = { ArrowUp: -1, ArrowLeft: -1, ArrowDown: 1, ArrowRight: 1 }[event.key]
    if (!dir) return
    event.preventDefault()
    if (event.shiftKey && (event.key === 'ArrowLeft' || event.key === 'ArrowRight')) emit('keysize', dir)
    else emit('keymove', dir)
}

watch(() => props.item.collapsed, ensureLoaded)
watch(() => props.refreshToken, () => { if (payload.value && !props.item.collapsed) load({ quiet: true }) })

onMounted(() => {
    observer = new IntersectionObserver((entries) => {
        if (entries.some((entry) => entry.isIntersecting)) {
            seen = true
            ensureLoaded()
        }
    }, { rootMargin: '160px' })
    observer.observe(root.value)
})
onBeforeUnmount(() => {
    observer?.disconnect()
    controller?.abort()
})

defineExpose({ reload: () => load({ quiet: true }) })
</script>

<template>
    <section ref="root" class="dbx-w" :class="{ 'is-collapsed': item.collapsed, 'is-busy': loading && payload }" :data-id="def.key" :data-span="span" :aria-label="def.title">
        <div v-if="editing" class="dbx-edit">
            <button class="dbx-grip" type="button" :aria-label="`Move ${def.title}. Arrow keys move it, Shift plus left or right resizes it.`" title="Drag to move · arrow keys work too"
                @pointerdown="emit('grip', $event)" @keydown="onGripKey"><i class="fa fa-arrows"></i></button>
            <span class="dbx-mv">
                <button class="dbx-ib" type="button" aria-label="Move up" @click="emit('move', -1)"><i class="fa fa-chevron-up"></i></button>
                <button class="dbx-ib" type="button" aria-label="Move down" @click="emit('move', 1)"><i class="fa fa-chevron-down"></i></button>
            </span>
            <span class="dbx-edit-t">{{ def.title }}</span>
            <div class="dbx-seg dbx-size" role="group" aria-label="Width">
                <button v-for="s in SIZES" :key="s.value" type="button" :class="{ on: s.value === span }" :disabled="s.value < def.minSpan"
                    :title="`${s.label} · ${s.value} of 12 columns`" @click="emit('size', s.value)">{{ s.label }}</button>
            </div>
            <button class="dbx-ib dbx-tall" :class="{ on: item.tall }" type="button" :aria-pressed="!!item.tall" aria-label="Expand to two rows tall"
                :title="item.tall ? 'Expanded: two rows tall, shows everything — click to shrink back' : 'Expand to two rows tall'" @click="emit('tall')"><i class="fa fa-arrows-v"></i></button>
            <button class="dbx-ib" type="button" :aria-label="item.collapsed ? 'Expand' : 'Collapse'" :title="item.collapsed ? 'Expand' : 'Collapse'" @click="emit('collapse')">
                <i class="fa" :class="item.collapsed ? 'fa-expand' : 'fa-compress'"></i>
            </button>
            <button class="dbx-ib" type="button" :aria-label="`Hide ${def.title}`" title="Hide" @click="emit('hide')"><i class="fa fa-eye-slash"></i></button>
        </div>

        <header class="dbx-h">
            <span class="dbx-chip"><i class="fa" :class="'fa-' + def.icon"></i></span>
            <div class="dbx-ht"><h3>{{ def.title }}</h3><p>{{ def.subtitle }}</p></div>
            <div v-if="def.options.length" class="dbx-seg dbx-tools" role="group" :aria-label="`${def.title} period`">
                <button v-for="o in def.options" :key="o.value" type="button" :class="{ on: o.value === option }" @click="choose(o.value)">{{ o.label }}</button>
            </div>
            <button class="dbx-ib dbx-more" type="button" :aria-label="`${def.title} options`" @click="emit('menu', $event.currentTarget)"><i class="fa fa-ellipsis-h"></i></button>
        </header>

        <div v-show="!item.collapsed" class="dbx-b">
            <div v-if="error" class="dbx-err">
                <i class="fa fa-exclamation-circle"></i><span>{{ error }}</span>
                <button class="dbx-btn sm" type="button" @click="load()">Retry</button>
            </div>
            <template v-else-if="payload">
                <WidgetBlocks :blocks="payload.blocks" @navigate="navigate" />
                <a v-if="payload.link" class="dbx-link" :href="payload.link.url">{{ payload.link.label }} <i class="fa fa-angle-right"></i></a>
            </template>
            <div v-else class="dbx-skel" :class="'k-' + def.kind" aria-busy="true" aria-label="Loading">
                <template v-if="def.kind === 'chart'"><i v-for="h in [40, 70, 55, 85, 60, 75, 45]" :key="h" class="dbx-sk" :style="{ height: h + '%' }"></i></template>
                <template v-else-if="def.kind === 'kpi'"><i v-for="n in 4" :key="n" class="dbx-sk"></i></template>
                <template v-else-if="def.kind === 'donut'"><i class="dbx-sk ring"></i><span><i class="dbx-sk"></i><i class="dbx-sk" style="width: 70%"></i></span></template>
                <template v-else-if="def.kind === 'cal'"><i v-for="n in 14" :key="n" class="dbx-sk"></i></template>
                <template v-else><i v-for="w in [100, 86, 94, 70, 90]" :key="w" class="dbx-sk" :style="{ width: w + '%' }"></i></template>
            </div>
        </div>

        <span v-if="editing" class="dbx-rz" title="Drag to resize" @pointerdown="emit('resize', $event)"></span>
        <span v-if="resizeHint" class="dbx-rz-hint">{{ resizeHint }}</span>
    </section>
</template>
