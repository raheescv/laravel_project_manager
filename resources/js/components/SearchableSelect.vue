<template>
    <div class="relative" ref="dropdown">
        <div class="relative">
            <input ref="searchInput" :value="displayValue" @click="openDropdown" @focus="openDropdown"
                @keydown.arrow-down.prevent="navigateDown" @keydown.arrow-up.prevent="navigateUp"
                @keydown.enter.prevent="selectHighlighted" @keydown.escape="hideDropdown" type="text"
                :class="inputClass" :placeholder="placeholder" autocomplete="off" readonly />
            <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
                    :class="{ 'rotate-180': showDropdown }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>
        </div>

        <!-- Teleport dropdown to body to escape overflow constraints -->
        <Teleport to="body">
            <div v-if="showDropdown" ref="dropdownMenu" :style="dropdownStyle"
                class="fixed z-[9999] bg-white border border-gray-200 rounded-lg shadow-xl max-h-60 overflow-hidden searchable-dropdown-portal">
                <div class="p-2 border-b border-gray-100">
                    <input ref="filterInput" v-model="searchTerm" @input="filterOptions"
                        @keydown.arrow-down.prevent="navigateDown" @keydown.arrow-up.prevent="navigateUp"
                        @keydown.enter.prevent="selectHighlighted" @keydown.escape="hideDropdown" type="text"
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        :placeholder="filterPlaceholder" autocomplete="off" />
                </div>
                <div class="max-h-48 overflow-auto custom-scrollbar">
                    <div v-if="filteredOptions.length === 0 && !searchTerm" class="px-3 py-2 text-sm text-gray-500">
                        No options available
                    </div>
                    <div v-else-if="filteredOptions.length === 0 && searchTerm" class="px-3 py-2 text-sm text-gray-500">
                        No results found for "{{ searchTerm }}"
                    </div>
                    <div v-for="(option, index) in filteredOptions" :key="option.value" @click="selectOption(option)"
                        :class="[
                            'px-3 py-2 cursor-pointer text-sm border-b border-gray-100 last:border-b-0 searchable-dropdown-item transition-colors duration-150',
                            index === highlightedIndex ? 'bg-blue-50 text-blue-700 highlighted' : 'hover:bg-gray-50'
                        ]">
                        {{ option.label }}
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: ''
    },
    options: {
        type: [Array, Object],
        required: true
    },
    placeholder: {
        type: String,
        default: 'Select an option...'
    },
    filterPlaceholder: {
        type: String,
        default: 'Search...'
    },
    inputClass: {
        type: String,
        default: 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500'
    }
})

const emit = defineEmits(['update:modelValue', 'change'])

const searchTerm = ref('')
const showDropdown = ref(false)
const highlightedIndex = ref(0)
const dropdown = ref(null)
const dropdownMenu = ref(null)
const noResultsMenu = ref(null)
const searchInput = ref(null)
const filterInput = ref(null)
const dropdownPosition = ref('down') // 'up' or 'down'

// Convert options to array format if it's an object
const normalizedOptions = computed(() => {
    if (Array.isArray(props.options)) {
        return props.options
    }

    // Convert object to array format
    if (typeof props.options === 'object' && props.options !== null) {
        return Object.entries(props.options).map(([value, label]) => ({
            value: value,
            label: String(label)
        }))
    }

    return []
})

// Find current selected option
const selectedOption = computed(() => {
    return normalizedOptions.value.find(option => option.value == props.modelValue)
})

// Filter options based on search term
const filteredOptions = ref([])

const filterOptions = () => {
    if (!searchTerm.value || searchTerm.value.trim() === '') {
        filteredOptions.value = normalizedOptions.value
    } else {
        const searchLower = searchTerm.value.toLowerCase().trim()
        filteredOptions.value = normalizedOptions.value.filter(option =>
            option.label.toLowerCase().includes(searchLower)
        )
    }
    highlightedIndex.value = 0
}

const displayValue = computed(() => {
    return selectedOption.value ? selectedOption.value.label : ''
})

const dropdownStyle = ref({})

// Calculate dropdown position to ensure it stays within viewport
const calculateDropdownPosition = () => {
    if (!dropdown.value) return

    const rect = dropdown.value.getBoundingClientRect()
    const viewportHeight = window.innerHeight
    
    // Estimate dropdown height
    const maxDropdownHeight = 240
    const estimatedHeight = Math.min(maxDropdownHeight, (filteredOptions.value.length * 44) + 100)

    if (rect.bottom + estimatedHeight > viewportHeight && rect.top > estimatedHeight) {
        dropdownPosition.value = 'up'
        dropdownStyle.value = {
            bottom: `${viewportHeight - rect.top}px`,
            left: `${rect.left}px`,
            width: `${rect.width}px`
        }
    } else {
        dropdownPosition.value = 'down'
        dropdownStyle.value = {
            top: `${rect.bottom}px`,
            left: `${rect.left}px`,
            width: `${rect.width}px`
        }
    }
}

// Open dropdown with smart positioning
const openDropdown = () => {
    showDropdown.value = true
    searchTerm.value = ''
    filteredOptions.value = normalizedOptions.value
    
    nextTick(() => {
        calculateDropdownPosition()
        if (filterInput.value) {
            filterInput.value.focus()
        }
    })
}

// Initialize filtered options
onMounted(() => {
    filteredOptions.value = normalizedOptions.value
    document.addEventListener('click', handleClickOutside)
    window.addEventListener('resize', handleResize)
    window.addEventListener('scroll', handleResize, true)

    if (searchInput.value) {
        searchInput.value.addEventListener('click', openDropdown)
    }
})

// Watch for changes in options
watch(() => props.options, () => {
    filteredOptions.value = normalizedOptions.value
}, { deep: true })

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
    window.removeEventListener('resize', handleResize)
    window.removeEventListener('scroll', handleResize, true)
})

const selectOption = (option) => {
    emit('update:modelValue', option.value)
    emit('change', option.value)
    hideDropdown()
}

const selectHighlighted = () => {
    if (filteredOptions.value[highlightedIndex.value]) {
        selectOption(filteredOptions.value[highlightedIndex.value])
    }
}

const navigateDown = () => {
    if (highlightedIndex.value < filteredOptions.value.length - 1) {
        highlightedIndex.value++
    }
}

const navigateUp = () => {
    if (highlightedIndex.value > 0) {
        highlightedIndex.value--
    }
}

const hideDropdown = () => {
    showDropdown.value = false
}

const handleClickOutside = (event) => {
    // Check if click is outside trigger and outside teleported menu
    const isTrigger = dropdown.value && dropdown.value.contains(event.target)
    const isMenu = dropdownMenu.value && dropdownMenu.value.contains(event.target)
    
    if (!isTrigger && !isMenu) {
        hideDropdown()
    }
}

// Handle window resize/scroll to recalculate position
const handleResize = () => {
    if (showDropdown.value) {
        calculateDropdownPosition()
    }
}

</script>
