<template>
    <div class="relative" ref="dropdown">
        <div class="relative">
            <input ref="searchInput" v-model="displayValue" @click="openDropdown" @focus="openDropdown"
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
            <div v-if="showDropdown" ref="dropdownMenu"
                :style="{ ...dropdownStyle, maxHeight: `${Math.min(500, (props.visibleItems * 40 + 60))}px` }"
                class="fixed z-[9999] bg-white border border-gray-200 rounded-lg shadow-xl overflow-hidden searchable-dropdown-portal">
                <div class="p-2 border-b border-gray-100">
                    <input ref="filterInput" v-model="searchTerm" @input="filterOptions"
                        @keydown.arrow-down.prevent="navigateDown" @keydown.arrow-up.prevent="navigateUp"
                        @keydown.enter.prevent="selectHighlighted" @keydown.escape="hideDropdown" type="text"
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        :placeholder="filterPlaceholder" autocomplete="off" />
                </div>
                <div class="overflow-auto custom-scrollbar" :style="{ maxHeight: `${props.visibleItems * 40}px` }">
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
                        <!-- Check if option has multi-line data -->
                        <div v-if="option.name && option.mobile" class="flex flex-col space-y-1">
                            <div class="font-medium text-gray-900 leading-tight">{{ option.name }}</div>
                            <div class="flex items-center text-xs text-gray-600">
                                <i class="fa fa-phone mr-1.5 text-blue-500 text-xs"></i>
                                <span>{{ option.mobile ? option.mobile : '' }}</span>
                            </div>
                        </div>
                        <!-- Fallback to simple label -->
                        <div v-else>{{ option.label }}</div>
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
        default: 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500'
    },
    visibleItems: {
        type: Number,
        default: 5
    }
})

const emit = defineEmits(['update:modelValue', 'change', 'search'])

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
    const estimatedHeight = Math.min(240, (filteredOptions.value.length * 60) + 80) // 60px per item for multi-line + header

    // Determine position
    let top, left, width, maxHeight

    if (spaceBelow >= estimatedHeight || spaceBelow >= spaceAbove) {
        // Show below
        top = rect.bottom + window.scrollY
        maxHeight = Math.min(240, spaceBelow - 10)
    } else {
        // Show above
        top = rect.top + window.scrollY - Math.min(240, estimatedHeight)
        maxHeight = Math.min(240, spaceAbove - 10)
    }

    left = rect.left + window.scrollX
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
    highlightedIndex.value = 0

    nextTick(() => {
        calculateDropdownPosition()
        if (filterInput.value) {
            filterInput.value.focus()
        }
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
    filteredOptions.value = normalizedOptions.value
}, { deep: true, immediate: true })
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
    background-color: #eff6ff;
    color: #1d4ed8;
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
