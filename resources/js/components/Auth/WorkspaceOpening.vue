<template>
    <div class="lgx-open" :style="portal" role="status" aria-live="polite">
        <div class="lgx-open-rings" aria-hidden="true"><span></span><span></span><span></span></div>
        <div class="lgx-open-body">
            <div class="lgx-open-mark" :class="{ 'lgx-open-mark--logo': logo }"><img v-if="logo" :src="logo" alt="" /><i v-else class="fa fa-cloud"></i></div>
            <p class="lgx-open-kicker"><span class="lgx-open-dot"></span>Signed in</p>
            <h2 class="lgx-open-title">
                <span style="--i: 0">Welcome</span> <span style="--i: 1">back<template v-if="name">,&nbsp;</template></span>
                <em v-if="name" style="--i: 2">{{ name }}</em>
            </h2>
            <p class="lgx-open-sub">Opening your <b>{{ company }}</b> workspace</p>
            <div class="lgx-open-bar" aria-hidden="true"><i></i></div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    origin: { type: Object, required: true },
    name: { type: String, default: '' },
    company: { type: String, default: '' },
    logo: { type: String, default: null },
})

const portal = computed(() => {
    const { x, y } = props.origin
    const radius = Math.hypot(Math.max(x, window.innerWidth - x), Math.max(y, window.innerHeight - y))

    return { '--ox': `${x}px`, '--oy': `${y}px`, '--or': `${Math.ceil(radius) + 2}px` }
})
</script>
