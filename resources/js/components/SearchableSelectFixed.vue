<template>
    <div class="position-relative" ref="dropdown">
        <div class="position-relative">
            <input ref="searchInput" v-model="displayValue" @click="openDropdown" @focus="openDropdown"
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
                    <input ref="filterInput" v-model="searchTerm" @input="filterOptions"
                        @keydown.arrow-down.prevent="navigateDown" @keydown.arrow-up.prevent="navigateUp"
                        @keydown.enter.prevent="selectHighlighted" @keydown.escape="hideDropdown" type="text"
                        class="form-control form-control-sm" :placeholder="filterPlaceholder" autocomplete="off" />
                </div>
                <div class="flex-grow-1 overflow-auto custom-scrollbar">
                    <div v-if="filteredOptions.length === 0 && !searchTerm" class="px-3 py-2 small text-muted">
                        No options available
                    </div>
                    <div v-else-if="filteredOptions.length === 0 && (searchTerm && searchTerm.length >= 2 || loading)"
                        class="px-3 py-2 small text-muted text-center">
                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                        <span>Searching...</span>
                    </div>
                    <div v-else-if="filteredOptions.length === 0 && searchTerm" class="px-3 py-2 small text-muted">
                        No results found for "{{ searchTerm }}"
                    </div>
                    <div v-for="(option, index) in filteredOptions" :key="option.value" @click="selectOption(option)"
                        :class="[
                            'px-3 py-2 cursor-pointer border-bottom transition-colors searchable-dropdown-item d-flex justify-content-between align-items-center',
                            index === highlightedIndex ? 'bg-dark text-dark highlighted' : (option.value == modelValue ? 'bg-light text-dark fw-bold' : 'text-dark hover-bg-light')
                        ]">
                        <div class="flex-grow-1">
                            <!-- Check if option has multi-line data -->
                            <div v-if="option.name && option.mobile" class="d-flex flex-column">
                                <div class="fw-bold small leading-tight">{{ option.name }}</div>
                                <div class="d-flex align-items-center x-small opacity-75">
                                    <i class="fa fa-phone me-1 small"></i>
                                    <span>{{ option.mobile }}</span>
                                </div>
                            </div>
                            <!-- Fallback to simple label -->
                            <div v-else class="small">{{ option.label }}</div>
                        </div>

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
        type: Array,
        default: () => []
    },
    placeholder: {
        type: String,
        default: 'Select an option'
    },
    filterPlaceholder: {
        type: String,
        default: 'Type to search'
    },
    inputClass: {
        type: String,
        default: 'form-control shadow-sm'
    },
    visibleItems: {
        type: Number,
        default: 5
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
const searchInput = ref(null)
const filterInput = ref(null)
const dropdownStyle = ref({})

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

// Display value for the input
const displayValue = computed(() => {
    if (selectedOption.value) {
        // If option has name property, show just the name in input
        return selectedOption.value.name || selectedOption.value.label
    }
    return ''
})

// Filter options based on search term
const filteredOptions = ref([])

const filterOptions = () => {
    // Emit search event when the search term changes with at least 2 characters
    if (searchTerm.value && searchTerm.value.trim().length >= 2) {
        emit('search', searchTerm.value.trim())
    }

    if (!searchTerm.value || searchTerm.value.trim() === '') {
        filteredOptions.value = normalizedOptions.value
    } else {
        const searchLower = searchTerm.value.toLowerCase().trim()
        filteredOptions.value = normalizedOptions.value.filter(option => {
            // Search in label
            if (option.label.toLowerCase().includes(searchLower)) {
                return true
            }
            // Search in name and mobile if they exist as separate fields
            if (option.name && option.name.toLowerCase().includes(searchLower)) {
                return true
            }
            if (option.mobile && option.mobile.toLowerCase().includes(searchLower)) {
                return true
            }
            return false
        })
    }
    highlightedIndex.value = 0
    if (showDropdown.value) {
        nextTick(() => {
            calculateDropdownPosition()
        })
    }
}

// Calculate dropdown position relative to viewport
const calculateDropdownPosition = () => {
    if (!dropdown.value) return

    const rect = dropdown.value.getBoundingClientRect()
    const viewportHeight = window.innerHeight
    const viewportWidth = window.innerWidth

    // Calculate available space
    const spaceBelow = viewportHeight - rect.bottom
    const spaceAbove = rect.top

    // Estimate dropdown height (max 240px based on max-h-60)
    const estimatedHeight = Math.min(320, (filteredOptions.value.length * 60) + 80) // 60px per item for multi-line + header

    // Determine position
    let top, left, width, maxHeight

    if (spaceBelow >= estimatedHeight || spaceBelow >= spaceAbove) {
        // Show below
        top = rect.bottom
        maxHeight = Math.min(estimatedHeight, spaceBelow - 10)
    } else {
        // Show above
        const showHeight = Math.min(estimatedHeight, spaceAbove - 10)
        top = rect.top - showHeight
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
        top: `${top}px`,
        left: `${left}px`,
        width: `${width}px`,
        maxHeight: `${maxHeight}px`
    }
}

// Open dropdown with accurate positioning
const openDropdown = () => {
    showDropdown.value = true
    searchTerm.value = ''
    filteredOptions.value = normalizedOptions.value

    // Set highlighted index to selected item
    if (props.modelValue !== null && props.modelValue !== undefined && props.modelValue !== '') {
        const selectedIndex = filteredOptions.value.findIndex(opt => opt.value == props.modelValue)
        if (selectedIndex !== -1) {
            highlightedIndex.value = selectedIndex
        } else {
            highlightedIndex.value = 0
        }
    } else {
        highlightedIndex.value = 0
    }

    emit('open')

    nextTick(() => {
        calculateDropdownPosition()
        if (filterInput.value) {
            filterInput.value.focus()
        }
        // Ensure the highlighted item is visible
        scrollToHighlighted()
    })
}

const hideDropdown = () => {
    showDropdown.value = false
    searchTerm.value = ''
}

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

const handleClickOutside = (event) => {
    if (dropdown.value && !dropdown.value.contains(event.target) &&
        dropdownMenu.value && !dropdownMenu.value.contains(event.target)) {
        hideDropdown()
    }
}

const handleResize = () => {
    if (showDropdown.value) {
        calculateDropdownPosition()
    }
}

const handleScroll = () => {
    if (showDropdown.value) {
        calculateDropdownPosition()
    }
}

// Initialize
onMounted(() => {
    filteredOptions.value = normalizedOptions.value
    document.addEventListener('click', handleClickOutside)
    window.addEventListener('resize', handleResize)
    window.addEventListener('scroll', handleScroll, true)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
    window.removeEventListener('resize', handleResize)
    window.removeEventListener('scroll', handleScroll, true)
})

// Watch for changes in modelValue
watch(() => props.modelValue, () => {
    // Value changed externally, no need to update display as it's computed
}, { immediate: true })

// Watch for changes in options
watch(() => props.options, () => {
    // If there's an active search term, filter the new options
    if (searchTerm.value && searchTerm.value.trim().length >= 2) {
        const searchLower = searchTerm.value.toLowerCase().trim()
        filteredOptions.value = normalizedOptions.value.filter(option => {
            // Search in label
            if (option.label.toLowerCase().includes(searchLower)) {
                return true
            }
            // Search in name and mobile if they exist as separate fields
            if (option.name && option.name.toLowerCase().includes(searchLower)) {
                return true
            }
            if (option.mobile && option.mobile.toLowerCase().includes(searchLower)) {
                return true
            }
            return false
        })
    } else {
        filteredOptions.value = normalizedOptions.value
    }
    highlightedIndex.value = 0
    if (showDropdown.value) {
        nextTick(() => {
            calculateDropdownPosition()
        })
    }
}, { deep: true, immediate: true })

// Expose methods for parent component
defineExpose({
    openDropdown,
    focus: () => {
        nextTick(() => {
            if (searchInput.value) {
                // First open the dropdown
                openDropdown()
                // Then focus the input (which will also trigger openDropdown via @focus)
                searchInput.value.focus()
                // Scroll into view
                searchInput.value.scrollIntoView({ behavior: 'smooth', block: 'center' })
            }
        })
    }
})
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

.rotate-180 {
    transform: rotate(180deg);
}

.transition-transform {
    transition: transform 0.2s ease;
}

.x-small {
    font-size: 0.75rem;
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
</style>
