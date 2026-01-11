<template>
    <div class="stock-check-search-bar mb-3">
        <div class="input-group">
            <input type="text" class="form-control" v-model="localQuery" @input="handleInput"
                placeholder="Search stock checks..." />
            <button v-if="localQuery" class="btn btn-outline-secondary" type="button" @click="handleClear">
                <i class="fa fa-times"></i>
            </button>
            <button class="btn btn-outline-primary" type="button" @click="handleSearch">
                <i class="fa fa-search"></i> Search
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    searchQuery: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['update:searchQuery', 'search', 'clear'])

const localQuery = ref(props.searchQuery)

let debounceTimer = null

watch(() => props.searchQuery, (newVal) => {
    localQuery.value = newVal
})

const handleInput = () => {
    emit('update:searchQuery', localQuery.value)

    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
        emit('search', localQuery.value)
    }, 500)
}

const handleSearch = () => {
    clearTimeout(debounceTimer)
    emit('search', localQuery.value)
}

const handleClear = () => {
    localQuery.value = ''
    emit('update:searchQuery', '')
    emit('clear')
}
</script>

<style scoped>
.stock-check-search-bar {
    max-width: 500px;
}
</style>
