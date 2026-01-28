<template>
    <div class="position-relative" ref="dropdown">
        <div class="position-relative">
            <input ref="searchInput" :value="displayValue" @click="openDropdown" @focus="openDropdown"
                @keydown.arrow-down.prevent="navigateDown" @keydown.arrow-up.prevent="navigateUp"
                @keydown.enter.prevent="selectHighlighted" @keydown.escape="hideDropdown" type="text"
                :class="inputClass" :placeholder="placeholder" autocomplete="off" readonly style="cursor: pointer;" />
            <div class="position-absolute top-50 end-0 translate-middle-y pe-2 pointer-events-none">
                <i class="fa fa-chevron-down small text-muted transition-transform"
                    :class="{ 'rotate-180': showDropdown }"></i>
            </div>
        </div>

        <!-- Teleport dropdown to body -->
        <Teleport to="body">
            <div v-if="showDropdown" ref="dropdownMenu" :style="{ ...dropdownStyle, zIndex: 9999 }"
                class="position-fixed bg-white border rounded shadow-lg overflow-hidden searchable-dropdown-portal d-flex flex-column">
                <div class="p-2 border-bottom bg-light">
                    <div class="position-relative">
                        <input ref="filterInput" v-model="searchTerm" @input="filterOptions"
                            @keydown.arrow-down.prevent="navigateDown" @keydown.arrow-up.prevent="navigateUp"
                            @keydown.enter.prevent="selectHighlighted" @keydown.escape="hideDropdown" type="text"
                            class="form-control form-control-sm" :placeholder="filterPlaceholder" autocomplete="off" />
                        <div v-if="loading" class="position-absolute end-0 top-50 translate-middle-y pe-3">
                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        </div>
                    </div>
                </div>
                <div class="flex-grow-1 overflow-auto custom-scrollbar">
                    <div v-if="filteredOptions.length === 0 && !searchTerm" class="px-3 py-2 small text-muted">
                        No options available
                    </div>
                    <div v-else-if="filteredOptions.length === 0 && searchTerm && !loading"
                        class="px-3 py-2 small text-muted">
                        No results found for "{{ searchTerm }}"
                    </div>
                    <div v-else-if="loading && filteredOptions.length === 0" class="px-3 py-2 small text-muted">
                        Searching...
                    </div>
                    <div v-for="(option, index) in filteredOptions" :key="option.value" @click="selectOption(option)"
                        :class="[
                            'px-3 py-2 cursor-pointer small border-bottom transition-colors searchable-dropdown-item d-flex justify-content-between align-items-center',
                            index === highlightedIndex ? 'bg-primary text-dark highlighted' : (option.value == modelValue ? 'bg-light text-dark fw-bold' : 'text-dark hover-bg-light')
                        ]">
                        <span>{{ option.label }}</span>
                        <!-- Checkmark for selected item -->
                        <i v-if="option.value == modelValue" class="fa fa-check small ms-2"></i>
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
        default: 'form-control shadow-sm'
    },
    remote: {
        type: Boolean,
        default: false
    },
    loading: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:modelValue', 'change', 'search', 'open'])

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
    if (props.remote) {
        filteredOptions.value = [] // Clear old results during new search
        emit('search', searchTerm.value)
        return
    }

    if (!searchTerm.value || searchTerm.value.trim() === '') {
        filteredOptions.value = normalizedOptions.value
    } else {
        const searchLower = searchTerm.value.toLowerCase().trim()
        filteredOptions.value = normalizedOptions.value.filter(option =>
            option.label.toLowerCase().includes(searchLower)
        )
    }
    highlightedIndex.value = 0
    if (showDropdown.value) {
        nextTick(() => {
            calculateDropdownPosition()
        })
    }
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
    const viewportWidth = window.innerWidth

    // Estimate dropdown height
    const maxDropdownHeight = 320
    const estimatedHeight = Math.min(maxDropdownHeight, (filteredOptions.value.length * 44) + 100)

    // Calculate available space
    const spaceBelow = viewportHeight - rect.bottom
    const spaceAbove = rect.top

    let top, left, width, maxHeight, bottom

    if (spaceBelow >= estimatedHeight || spaceBelow >= spaceAbove) {
        // Show below
        dropdownPosition.value = 'down'
        top = rect.bottom
        maxHeight = Math.min(estimatedHeight, spaceBelow - 10)
    } else {
        // Show above
        dropdownPosition.value = 'up'
        const showHeight = Math.min(estimatedHeight, spaceAbove - 10)
        bottom = viewportHeight - rect.top
        maxHeight = showHeight
    }

    left = rect.left
    width = rect.width

    // Ensure dropdown stays within viewport horizontally
    if (left + width > viewportWidth) {
        left = viewportWidth - width - 10
    }
    if (left < 10) {
        left = 10
        width = Math.min(width, viewportWidth - 20)
    }

    dropdownStyle.value = {
        top: top ? `${top}px` : 'auto',
        bottom: bottom ? `${bottom}px` : 'auto',
        left: `${left}px`,
        width: `${width}px`,
        maxHeight: `${maxHeight}px`
    }
}

// Open dropdown with smart positioning
const openDropdown = () => {
    showDropdown.value = true
    searchTerm.value = ''
    filteredOptions.value = normalizedOptions.value

    // Set highlighted index to selected item
    if (props.modelValue !== null && props.modelValue !== undefined && props.modelValue !== '') {
        const selectedIndex = normalizedOptions.value.findIndex(opt => opt.value == props.modelValue)
        if (selectedIndex !== -1) {
            highlightedIndex.value = selectedIndex
        } else {
            highlightedIndex.value = 0
        }
    } else {
        highlightedIndex.value = 0
    }

    nextTick(() => {
        calculateDropdownPosition()
        if (filterInput.value) {
            filterInput.value.focus()
        }
        // Ensure the highlighted item is visible
        scrollToHighlighted()
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
    if (showDropdown.value) {
        nextTick(() => {
            calculateDropdownPosition()
        })
    }
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
        scrollToHighlighted()
    }
}

const navigateUp = () => {
    if (highlightedIndex.value > 0) {
        highlightedIndex.value--
        scrollToHighlighted()
    }
}

const scrollToHighlighted = () => {
    nextTick(() => {
        const highlighted = dropdownMenu.value?.querySelector('.highlighted')
        if (highlighted) {
            highlighted.scrollIntoView({ block: 'nearest' })
        }
    })
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

<style scoped>
.searchable-dropdown-portal {
    backdrop-filter: blur(8px);
    animation: dropdownFadeIn 0.15s ease-out;
}

@keyframes dropdownFadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.searchable-dropdown-item:hover,
.searchable-dropdown-item.highlighted {
    background-color: var(--bs-primary);
    color: white;
    border-left: 4px solid #0d6efd;
    /* Highlight border */
}

.searchable-dropdown-item {
    border-left: 4px solid transparent;
}

.max-h-80 {
    max-height: 320px;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.rotate-180 {
    transform: rotate(180deg);
}

.transition-transform {
    transition: transform 0.2s ease;
}
</style>
