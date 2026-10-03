<template>
    <canvas ref="canvas" class="lgx-live" aria-hidden="true"></canvas>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { SCENES } from './liveScenes.js'

const props = defineProps({
    kind: { type: String, default: 'globe' },
    dark: { type: Boolean, default: false },
    anchor: { type: String, default: 'center' },
})

const canvas = ref(null)
const opts = { mx: 0.5, my: 0.5, inside: false }
const still = window.matchMedia('(prefers-reduced-motion: reduce)').matches
let draw = null
let frame = 0
let observer = null

const PALETTES = {
    dark: { line: '158,216,255', hot: '11,168,250', accent: '11,168,250' },
    light: { line: '10,98,200', hot: '11,140,240', accent: '10,98,200' },
}

function build() {
    const el = canvas.value
    if (!el) return
    const rect = el.getBoundingClientRect()
    const dpr = Math.min(2, window.devicePixelRatio || 1)
    el.width = Math.max(1, rect.width * dpr)
    el.height = Math.max(1, rect.height * dpr)
    const ctx = el.getContext('2d')
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0)
    Object.assign(opts, { dark: props.dark, anchor: props.anchor, c: PALETTES[props.dark ? 'dark' : 'light'] })
    draw = (SCENES[props.kind] || SCENES.globe)(ctx, rect.width, rect.height, opts)
    if (still) draw(4000)
}

function loop(time) {
    draw?.(time)
    frame = requestAnimationFrame(loop)
}

function onPointer(event) {
    const rect = canvas.value.getBoundingClientRect()
    opts.mx = (event.clientX - rect.left) / rect.width
    opts.my = (event.clientY - rect.top) / rect.height
    opts.inside = opts.mx >= 0 && opts.mx <= 1 && opts.my >= 0 && opts.my <= 1
}

onMounted(() => {
    build()
    observer = new ResizeObserver(build)
    observer.observe(canvas.value)
    window.addEventListener('pointermove', onPointer, { passive: true })
    if (!still) frame = requestAnimationFrame(loop)
})

onBeforeUnmount(() => {
    cancelAnimationFrame(frame)
    observer?.disconnect()
    window.removeEventListener('pointermove', onPointer)
})

watch(() => [props.kind, props.dark], build)
</script>
