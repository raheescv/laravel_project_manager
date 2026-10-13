<template>
    <div ref="root" class="pc-picker">
        <input
            :id="inputId"
            v-model="query"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': invalid }"
            :placeholder="placeholder"
            autocomplete="off"
            role="combobox"
            :aria-expanded="open"
            @focus="openMenu"
            @input="search"
            @keydown.down.prevent="move(1)"
            @keydown.up.prevent="move(-1)"
            @keydown.enter.prevent="pick(results[highlight])"
            @keydown.esc="close"
        />
        <div v-if="open" class="pc-picker-menu" role="listbox">
            <div v-if="loading && !results.length" class="pa-sub px-3 py-2">Searching…</div>
            <div v-else-if="!results.length" class="pa-sub px-3 py-2">No vendor matches “{{ query }}”.</div>
            <button
                v-for="(vendor, index) in results"
                :key="vendor.id"
                type="button"
                :class="{ hl: index === highlight }"
                role="option"
                @mousedown.prevent="pick(vendor)"
            >
                <span class="pa-avatar">{{ initials(vendor.name) }}</span>
                <span class="flex-grow-1" style="min-width: 0">
                    <span class="d-block text-truncate">{{ vendor.name }}</span>
                    <span class="pa-sub">{{ [vendor.alias_name, vendor.mobile].filter(Boolean).join(' · ') }}</span>
                </span>
            </button>
        </div>
    </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { contractApi } from '../api.js'
import { initials } from '../format.js'

const props = defineProps({
    modelValue: { type: [Number, String, null], default: null },
    label: { type: String, default: '' },
    placeholder: { type: String, default: 'Search vendor by name or mobile' },
    inputId: { type: String, default: 'f-vendor' },
    invalid: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'picked'])

const root = ref(null)
const query = ref(props.label)
const open = ref(false)
const loading = ref(false)
const results = ref([])
const highlight = ref(0)
let controller = null
let timer = null

watch(() => props.label, (label) => { query.value = label })

async function fetchResults() {
    controller?.abort()
    controller = new AbortController()
    loading.value = true
    try {
        const response = await contractApi.vendorSearch(query.value === props.label ? '' : query.value, controller.signal)
        results.value = response.data
        highlight.value = 0
    } catch {
        // A newer search replaced this one.
    } finally {
        loading.value = false
    }
}

function openMenu() {
    open.value = true
    fetchResults()
}

function search() {
    open.value = true
    clearTimeout(timer)
    timer = setTimeout(fetchResults, 220)
}

function move(step) {
    if (!results.value.length) return
    highlight.value = (highlight.value + step + results.value.length) % results.value.length
}

function pick(vendor) {
    if (!vendor) return
    emit('update:modelValue', vendor.id)
    emit('picked', vendor)
    query.value = vendor.name
    close()
}

function close() {
    open.value = false
    if (props.modelValue && query.value !== props.label && props.label) query.value = props.label
}

function onDocumentClick(event) {
    if (root.value && !root.value.contains(event.target)) close()
}

onMounted(() => document.addEventListener('mousedown', onDocumentClick))
onBeforeUnmount(() => {
    document.removeEventListener('mousedown', onDocumentClick)
    controller?.abort()
    clearTimeout(timer)
})
</script>
