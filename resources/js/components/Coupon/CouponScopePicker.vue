<template>
    <div class="cpx-picker">
        <div v-if="modelValue.length" class="cpx-chips">
            <span v-for="item in modelValue" :key="item.id" class="ofx-pill acc">
                {{ item.name }}
                <button type="button" class="cpx-chip-x" :aria-label="`Remove ${item.name}`" @click="remove(item)"><i class="fa fa-times"></i></button>
            </span>
        </div>
        <div class="ofx-search ofx-picker">
            <i class="fa fa-search"></i>
            <input v-model="query" class="ofx-inp" type="search" :placeholder="placeholder" @focus="open" @blur="isOpen = false" @input="debouncedSearch" />
            <div v-if="isOpen" class="ofx-card ofx-picker-menu cpx-menu">
                <button v-for="item in choices" :key="item.id" type="button" class="ofx-mi" @mousedown.prevent="add(item)">
                    <i class="fa fa-plus"></i><span>{{ item.name }}<small v-if="item.size">Size {{ item.size }}</small></span>
                </button>
                <div v-if="!loading && !choices.length" class="ofx-mi-empty">Nothing found.</div>
                <div v-if="loading" class="ofx-mi-empty"><i class="fa fa-spinner fa-spin"></i></div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { couponApi } from './api.js'

const props = defineProps({
    modelValue: { type: Array, required: true },
    url: { type: String, required: true },
    placeholder: { type: String, default: 'Search…' },
})
const emit = defineEmits(['update:modelValue'])

const query = ref('')
const results = ref([])
const isOpen = ref(false)
const loading = ref(false)
let controller = null
let timer = null

const choices = computed(() => results.value.filter((item) => !props.modelValue.some((picked) => picked.id === item.id)))

async function search() {
    controller?.abort()
    controller = new AbortController()
    loading.value = true
    try {
        results.value = await couponApi.lookup(props.url, query.value.trim(), controller.signal)
    } catch {
        results.value = []
    } finally {
        loading.value = false
    }
}

function debouncedSearch() {
    clearTimeout(timer)
    timer = setTimeout(search, 250)
}

function open() {
    isOpen.value = true
    search()
}

function add(item) {
    emit('update:modelValue', [...props.modelValue, { id: item.id, name: item.name }])
    query.value = ''
    search()
}

function remove(item) {
    emit('update:modelValue', props.modelValue.filter((picked) => picked.id !== item.id))
}
</script>
