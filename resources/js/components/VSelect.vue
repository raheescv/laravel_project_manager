<template>
    <div class="relative w-full" ref="container">
        <!-- Trigger -->
        <div class="relative cursor-pointer group" @click="toggleDropdown">
            <div :class="[
                'flex items-center justify-between w-full rounded-xl border px-3 py-1.5 text-xs font-bold transition-all duration-200',
                isOpen ? 'bg-white border-blue-500 ring-4 ring-blue-500/10' : 'bg-slate-50 border-slate-200 group-hover:border-slate-300'
            ]">
                <span :class="displayLabel ? 'text-slate-700' : 'text-slate-400'">
                    {{ displayLabel || placeholder }}
                </span>
                <i :class="[
                    'fa fa-chevron-down text-[10px] transition-transform duration-200',
                    isOpen ? 'rotate-180 text-blue-500' : 'text-slate-400'
                ]"></i>
            </div>
        </div>

        <!-- Portal for Dropdown -->
        <Teleport to="body">
            <div v-if="isOpen" 
                ref="dropdown"
                :style="dropdownStyle"
                class="fixed z-[9999] bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden animate-[dropdownFade_0.2s_ease-out]">
                
                <!-- Search Box -->
                <div class="p-2 border-b border-slate-50 bg-slate-50/50">
                    <div class="relative">
                        <i class="fa fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-[10px]"></i>
                        <input
                            ref="searchInput"
                            v-model="searchTerm"
                            type="text"
                            :placeholder="searchPlaceholder"
                            class="w-full bg-white border border-slate-200 rounded-lg pl-8 pr-3 py-1.5 text-[0.7rem] font-medium focus:outline-none focus:border-blue-400 transition-all placeholder:text-slate-300"
                            @keydown.esc="closeDropdown"
                            @keydown.arrow-down.prevent="navigateOptions(1)"
                            @keydown.arrow-up.prevent="navigateOptions(-1)"
                            @keydown.enter.prevent="selectHighlighted"
                        />
                    </div>
                </div>

                <!-- Options List -->
                <div class="max-h-60 overflow-y-auto custom-scrollbar py-1">
                    <div v-if="loading" class="px-4 py-3 text-center text-slate-400 text-[0.7rem] flex items-center justify-center gap-2">
                        <i class="fa fa-spinner fa-spin"></i>
                        Searching...
                    </div>
                    <div v-else-if="filteredOptions.length === 0" class="px-4 py-3 text-center text-slate-400 text-[0.7rem] italic">
                        No results found
                    </div>
                    <div
                        v-for="(option, index) in (loading ? [] : filteredOptions)"
                        :key="option.value"
                        class="px-3 py-2 cursor-pointer transition-all duration-150 flex items-center justify-between group/item"
                        :class="[
                            index === highlightedIndex ? 'bg-blue-50 text-blue-600' : 'text-slate-600 hover:bg-slate-50',
                            option.value === modelValue ? 'bg-blue-50/50' : ''
                        ]"
                        @click="selectOption(option)"
                        @mouseenter="highlightedIndex = index"
                    >
                        <div class="flex flex-col">
                            <span :class="option.value === modelValue ? 'font-black text-blue-600' : 'font-bold'">
                                {{ option.label }}
                            </span>
                            <span v-if="option.description" class="text-[10px] opacity-60 font-medium">
                                {{ option.description }}
                            </span>
                        </div>
                        <i v-if="option.value === modelValue" class="fa fa-check text-[10px] text-blue-500"></i>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'

const props = defineProps({
    modelValue: [String, Number, Boolean, null],
    options: {
        type: Array,
        required: true,
        // Expects [{ value, label, description }]
    },
    placeholder: {
        type: String,
        default: 'Select an option'
    },
    searchPlaceholder: {
        type: String,
        default: 'Type to search...'
    },
    loading: {
        type: Boolean,
        default: false
    },
    remote: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:modelValue', 'change', 'search'])

const container = ref(null)
const dropdown = ref(null)
const searchInput = ref(null)
const isOpen = ref(false)
const searchTerm = ref('')
const highlightedIndex = ref(0)
const dropdownStyle = ref({})

const filteredOptions = computed(() => {
    if (props.remote) return props.options
    if (!searchTerm.value) return props.options
    const term = searchTerm.value.toLowerCase()
    return props.options.filter(opt => 
        String(opt.label).toLowerCase().includes(term) || 
        (opt.description && String(opt.description).toLowerCase().includes(term))
    )
})

watch(searchTerm, (newVal) => {
    if (props.remote) {
        emit('search', newVal)
    }
})

const displayLabel = computed(() => {
    const selected = props.options.find(opt => String(opt.value) === String(props.modelValue))
    if (selected) return selected.label
    return (props.modelValue !== undefined && props.modelValue !== null && props.modelValue !== '') ? String(props.modelValue) : ''
})

const toggleDropdown = () => {
    if (isOpen.value) {
        closeDropdown()
    } else {
        openDropdown()
    }
}

const openDropdown = () => {
    isOpen.value = true
    searchTerm.value = ''
    highlightedIndex.value = props.options.findIndex(opt => opt.value == props.modelValue)
    if (highlightedIndex.value < 0) highlightedIndex.value = 0
    
    updateDropdownPosition()
    
    nextTick(() => {
        if (searchInput.value) searchInput.value.focus()
    })
}

const closeDropdown = () => {
    isOpen.value = false
}

const selectOption = (option) => {
    emit('update:modelValue', option.value)
    emit('change', option.value)
    closeDropdown()
}

const selectHighlighted = () => {
    if (filteredOptions.value[highlightedIndex.value]) {
        selectOption(filteredOptions.value[highlightedIndex.value])
    }
}

const navigateOptions = (direction) => {
    const newIndex = highlightedIndex.value + direction
    if (newIndex >= 0 && newIndex < filteredOptions.value.length) {
        highlightedIndex.value = newIndex
        
        // Scroll into view
        nextTick(() => {
            const el = dropdown.value.querySelectorAll('.cursor-pointer')[newIndex]
            if (el) el.scrollIntoView({ block: 'nearest' })
        })
    }
}

const updateDropdownPosition = () => {
    if (!container.value) return
    
    const rect = container.value.getBoundingClientRect()
    const spaceBelow = window.innerHeight - rect.bottom
    const spaceAbove = rect.top
    const dropdownHeight = 300 // Max approximate height
    
    let top, bottom
    if (spaceBelow < dropdownHeight && spaceAbove > spaceBelow) {
        bottom = window.innerHeight - rect.top + 5
    } else {
        top = rect.bottom + 5
    }
    
    dropdownStyle.value = {
        top: top ? `${top}px` : 'auto',
        bottom: bottom ? `${bottom}px` : 'auto',
        left: `${rect.left}px`,
        width: `${rect.width}px`
    }
}

const handleClickOutside = (event) => {
    if (container.value && !container.value.contains(event.target)) {
        if (dropdown.value && !dropdown.value.contains(event.target)) {
            closeDropdown()
        }
    }
}

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside)
    window.addEventListener('resize', updateDropdownPosition)
    window.addEventListener('scroll', updateDropdownPosition, true)
})

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside)
    window.removeEventListener('resize', updateDropdownPosition)
    window.removeEventListener('scroll', updateDropdownPosition, true)
})

watch(isOpen, (newVal) => {
    if (newVal) {
        window.addEventListener('scroll', updateDropdownPosition, true)
    } else {
        window.removeEventListener('scroll', updateDropdownPosition, true)
    }
})
</script>

<style scoped>
@keyframes dropdownFade {
    from {
        opacity: 0;
        transform: translateY(-8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}
</style>
