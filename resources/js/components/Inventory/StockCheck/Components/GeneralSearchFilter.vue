<template>
    <div class="general-search-filter">
        <label class="form-label small">Search</label>
        <div class="input-group input-group-sm">
            <input type="text" :value="value" @input="handleInput" @keyup.enter="handleSearch"
                class="form-control" placeholder="Search product name, code, barcode..." />
            <button v-if="value" class="btn btn-outline-secondary" type="button" @click="handleClear">
                <i class="fa fa-times"></i>
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
    value: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['update:value', 'search'])

let debounceTimer = null

const handleInput = (event) => {
    const newValue = event.target.value
    emit('update:value', newValue)

    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
        emit('search', newValue)
    }, 500)
}

const handleSearch = () => {
    clearTimeout(debounceTimer)
    emit('search', props.value)
}

const handleClear = () => {
    emit('update:value', '')
    emit('search', '')
}
</script>
