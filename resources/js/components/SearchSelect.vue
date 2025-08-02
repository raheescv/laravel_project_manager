<template>
    <div class="relative" v-click-away="closeDropdown">
        <div class="relative">
            <input v-model="searchTerm" type="text" :placeholder="placeholder" :class="inputClass" @focus="openDropdown"
                @input="onInput" autocomplete="off" />
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </div>

        <!-- Dropdown -->
        <div v-if="isOpen && filteredOptions.length > 0"
            class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto">
            <div v-for="option in filteredOptions" :key="option.value" @click="selectOption(option)"
                class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50 hover:text-blue-900 transition-colors duration-150"
                :class="{ 'bg-blue-100 text-blue-900': option.value === modelValue }">
                {{ option.label }}
            </div>
        </div>

        <!-- No results -->
        <div v-if="isOpen && filteredOptions.length === 0 && searchTerm"
            class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg">
            <div class="px-3 py-2 text-sm text-gray-500">No results found</div>
        </div>
    </div>
</template>

<script>
import { computed, ref, watch } from 'vue'

export default {
    name: 'SearchSelect',
    props: {
        modelValue: {
            type: [String, Number],
            default: ''
        },
        options: {
            type: Object,
            required: true
        },
        placeholder: {
            type: String,
            default: 'Search and select...'
        },
        inputClass: {
            type: String,
            default: 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-all duration-200'
        }
    },
    emits: ['update:modelValue'],
    setup(props, { emit }) {
        const isOpen = ref(false)
        const searchTerm = ref('')

        // Convert options object to array format for easier filtering
        const optionsArray = computed(() => {
            return Object.entries(props.options).map(([key, value]) => ({
                value: key,
                label: value
            }))
        })

        // Filter options based on search term
        const filteredOptions = computed(() => {
            if (!searchTerm.value) {
                return optionsArray.value
            }
            return optionsArray.value.filter(option =>
                option.label.toLowerCase().includes(searchTerm.value.toLowerCase())
            )
        })

        // Watch for modelValue changes to update display
        watch(() => props.modelValue, (newValue) => {
            if (newValue && props.options[newValue]) {
                searchTerm.value = props.options[newValue]
            } else {
                searchTerm.value = ''
            }
        }, { immediate: true })

        const openDropdown = () => {
            isOpen.value = true
        }

        const closeDropdown = () => {
            isOpen.value = false
            // Restore the selected value if user didn't select anything
            if (props.modelValue && props.options[props.modelValue]) {
                searchTerm.value = props.options[props.modelValue]
            } else if (!props.modelValue) {
                searchTerm.value = ''
            }
        }

        const onInput = () => {
            isOpen.value = true
        }

        const selectOption = (option) => {
            searchTerm.value = option.label
            emit('update:modelValue', option.value)
            isOpen.value = false
        }

        return {
            isOpen,
            searchTerm,
            filteredOptions,
            openDropdown,
            closeDropdown,
            onInput,
            selectOption
        }
    },
    directives: {
        'click-away': {
            mounted(el, binding) {
                el.clickAwayHandler = (event) => {
                    if (!el.contains(event.target)) {
                        binding.value()
                    }
                }
                document.addEventListener('click', el.clickAwayHandler)
            },
            unmounted(el) {
                document.removeEventListener('click', el.clickAwayHandler)
            }
        }
    }
}
</script>
