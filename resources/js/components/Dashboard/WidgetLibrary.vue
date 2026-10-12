<script setup>
/**
 * Right-hand drawer listing the widgets the user may see but has hidden.
 * Click (or Enter) adds one at the end; dragging starts a drop onto the grid,
 * which the dashboard handles.
 */
import { computed, nextTick, ref, watch } from 'vue'

const props = defineProps({
    open: Boolean,
    widgets: { type: Array, required: true },
})
const emit = defineEmits(['add', 'press', 'close'])

const SIZE_LABEL = { 4: 'S', 6: 'M', 8: 'L', 12: 'XL' }
const query = ref('')
const search = ref(null)

const groups = computed(() => {
    const term = query.value.trim().toLowerCase()
    const result = {}
    props.widgets
        .filter((w) => !term || `${w.title} ${w.subtitle} ${w.group}`.toLowerCase().includes(term))
        .forEach((w) => (result[w.group] ||= []).push(w))
    return result
})

watch(() => props.open, (open) => { if (open) nextTick(() => search.value?.focus({ preventScroll: true })) })
</script>

<template>
    <aside class="dbx-lib" :class="{ open }" aria-label="Widget library" :aria-hidden="!open">
        <div class="dbx-lib-h">
            <b>Add widgets</b>
            <button class="dbx-ib" type="button" aria-label="Close" @click="emit('close')"><i class="fa fa-times"></i></button>
        </div>
        <label class="dbx-lib-s"><i class="fa fa-search"></i><input ref="search" v-model="query" type="search" placeholder="Search widgets" /></label>
        <div class="dbx-lib-tip">Drag a widget onto the dashboard, or click it to add it at the end.</div>
        <div class="dbx-lib-l">
            <div v-if="!widgets.length" class="dbx-lib-e"><i class="fa fa-check-circle"></i>Every widget is already on your dashboard.<br />Hide one to see it here.</div>
            <div v-else-if="!Object.keys(groups).length" class="dbx-lib-e">No widgets match “{{ query }}”.</div>
            <template v-for="(items, group) in groups" :key="group">
                <div class="dbx-lib-g">{{ group }}</div>
                <div v-for="w in items" :key="w.key" class="dbx-li2" role="button" tabindex="0" :aria-label="`Add ${w.title}`"
                    @pointerdown="emit('press', $event, w)" @keydown.enter.prevent="emit('add', w.key)" @keydown.space.prevent="emit('add', w.key)">
                    <span class="dbx-sketch" :class="'k-' + w.kind"><i v-for="n in 3" :key="n"></i></span>
                    <div class="dbx-li2-b"><b>{{ w.title }}</b><span>{{ w.subtitle }} · {{ SIZE_LABEL[w.span] }}</span></div>
                    <span class="dbx-ib" aria-hidden="true"><i class="fa fa-plus"></i></span>
                </div>
            </template>
        </div>
    </aside>
</template>
