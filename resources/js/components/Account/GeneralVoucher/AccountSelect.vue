<template>
    <div class="relative account-select-wrapper" ref="dropdown">
        <div class="relative">
            <input ref="searchInput" v-model="inputValue" @input="onInputChange" @focus="openDropdown"
                @keydown.arrow-down.prevent="navigateDown" @keydown.arrow-up.prevent="navigateUp"
                @keydown.enter.prevent="selectHighlighted" @keydown.escape="hideDropdown" @click.stop="openDropdown"
                type="text" class="account-select-input" :placeholder="placeholder" autocomplete="off" />
        </div>

        <!-- Teleport dropdown to body to escape overflow constraints -->
        <Teleport to="body">
            <div v-if="showDropdown" ref="dropdownMenu" :style="dropdownStyle" class="account-select-dropdown"
                style="z-index: 10000 !important;" @click.stop>
                <!-- Options List -->
                <div class="account-select-list">
                    <!-- Loading State -->
                    <div v-if="loading && options.length === 0" class="account-select-empty">
                        <div class="text-center py-3 text-sm text-gray-500">Loading...</div>
                    </div>

                    <!-- Empty States -->
                    <div v-else-if="!loading && filteredOptions.length === 0 && searchTerm.length === 0 && options.length === 0"
                        class="account-select-empty">
                        <div class="text-center py-3 text-sm text-gray-500">Loading accounts...</div>
                    </div>
                    <div v-else-if="!loading && filteredOptions.length === 0 && searchTerm.length < 2 && searchTerm.length > 0"
                        class="account-select-empty">
                        <div class="text-center py-2 text-xs text-gray-400">Type 2+ characters to search</div>
                    </div>
                    <div v-else-if="!loading && filteredOptions.length === 0 && searchTerm.length >= 2"
                        class="account-select-empty">
                        <div class="text-center py-3 text-sm text-gray-500">No results found</div>
                    </div>

                    <!-- Options -->
                    <template v-else>
                        <div v-for="(option, index) in filteredOptions" :key="option.id" @click="selectOption(option)"
                            @mouseenter="highlightedIndex = index" :class="[
                                'account-select-item',
                                index === highlightedIndex ? 'account-select-item-active' : ''
                            ]">
                            {{ option.name }}
                        </div>
                    </template>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import {
    computed,
    nextTick,
    onMounted,
    onUnmounted,
    ref,
    watch
} from 'vue'

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: ''
    },
    accountName: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: 'Select Account'
    }
})

const emit = defineEmits(['update:modelValue', 'update:account-name', 'change'])

const searchTerm = ref('')
const inputValue = ref('')
const showDropdown = ref(false)
const highlightedIndex = ref(0)
const loading = ref(false)
const dropdown = ref(null)
const dropdownMenu = ref(null)
const searchInput = ref(null)
const isSearching = ref(false)
const dropdownStyle = ref({
    top: '0px',
    left: '0px',
    width: '200px',
    maxHeight: '240px',
    position: 'fixed',
    zIndex: 10000
})
const options = ref([])
const selectedOption = ref(null)

// Get account list API URL from route helper or use direct path
const getAccountListUrl = (query = '') => {
    const baseUrl = '/account/list'
    const url = query ? `${baseUrl}?query=${encodeURIComponent(query)}` : baseUrl
    return url
}

// Get CSRF token from meta tag
const getCsrfToken = () => {
    const metaTag = document.querySelector('meta[name="csrf-token"]')
    return metaTag ? metaTag.getAttribute('content') : ''
}

// Load accounts from API
const loadAccounts = async (query = '') => {
    // Allow empty query to load initial list, but skip if query is 1 character
    if (query.length === 1) {
        return
    }

    try {
        loading.value = true
        const url = getAccountListUrl(query)
        const csrfToken = getCsrfToken()
        const headers = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'Cache-Control': 'no-cache'
        }

        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken
        }

        const response = await fetch(url, {
            method: 'GET',
            headers: headers,
            credentials: 'same-origin'
        })

        if (!response.ok) throw new Error('Failed to load accounts')

        const data = await response.json()
        const newOptions = data.items || []

        // If query is empty or we're doing a fresh search, replace options
        // Otherwise, merge to avoid duplicates
        if (query === '' || searchTerm.value.trim() === '') {
            options.value = newOptions
        } else {
            // Merge with existing options to avoid duplicates
            const existingIds = new Set(options.value.map(opt => String(opt.id)))
            const uniqueNewOptions = newOptions.filter(opt => !existingIds.has(String(opt.id)))
            options.value = [...options.value, ...uniqueNewOptions]
        }

        // If we have a selected value, find and set it
        if (props.modelValue && !selectedOption.value) {
            const found = options.value.find(opt => String(opt.id) === String(props.modelValue))
            if (found) {
                selectedOption.value = found
            }
        }
    } catch (error) {
        console.error('Error loading accounts:', error)
        // Don't clear options on error, keep existing ones
    } finally {
        loading.value = false
    }
}

// Filter options based on search term
const filteredOptions = computed(() => {
    const query = searchTerm.value.trim()

    // If no search term, return all options
    if (!query) {
        return options.value
    }

    // If search term is less than 2 characters, return empty (waiting for more input)
    if (query.length < 2) {
        return []
    }

    // Filter options based on search term
    const searchLower = query.toLowerCase()
    return options.value.filter(option =>
        option.name?.toLowerCase().includes(searchLower) ||
        option.mobile?.toLowerCase().includes(searchLower) ||
        option.email?.toLowerCase().includes(searchLower) ||
        String(option.id).includes(searchLower)
    )
})

// Handle input changes
const onInputChange = (event) => {
    const value = event.target.value
    inputValue.value = value

    // If input is completely cleared, clear the selection
    if (!value || value.trim() === '') {
        if (selectedOption.value) {
            // Clear the selection
            selectedOption.value = null
            emit('update:modelValue', null)
            emit('update:account-name', '')
            emit('change', null)
        }
        isSearching.value = false
        searchTerm.value = ''
        // Keep input empty
        inputValue.value = ''
        return
    }

    // If user is typing, enter search mode
    if (value && !isSearching.value) {
        isSearching.value = true
        searchTerm.value = value
        if (!showDropdown.value) {
            openDropdown()
        }
    } else {
        // Update search term while searching
        searchTerm.value = value
    }

    // Trigger search
    onSearch()
}

// Calculate dropdown position
const calculateDropdownPosition = () => {
    if (!dropdown.value) {
        // Initialize with default position if dropdown ref is not ready
        dropdownStyle.value = {
            top: '0px',
            left: '0px',
            width: '200px',
            maxHeight: '240px'
        }
        return
    }

    const rect = dropdown.value.getBoundingClientRect()
    const viewportHeight = window.innerHeight
    const viewportWidth = window.innerWidth

    const spaceBelow = viewportHeight - rect.bottom
    const spaceAbove = rect.top
    const estimatedHeight = 240

    let top, left, width, maxHeight

    if (spaceBelow >= estimatedHeight || spaceBelow >= spaceAbove) {
        // Position below
        top = rect.bottom
        maxHeight = Math.min(240, spaceBelow - 10)
    } else {
        // Position above
        top = rect.top - Math.min(240, estimatedHeight)
        maxHeight = Math.min(240, spaceAbove - 10)
    }

    // Use exact input width - match input box width exactly
    left = rect.left
    width = rect.width

    dropdownStyle.value = {
        top: `${top}px`,
        left: `${left}px`,
        width: `${width}px`,
        maxHeight: `${maxHeight}px`,
        position: 'fixed',
        zIndex: 10000 // Higher than Bootstrap modal (1055)
    }
}

const openDropdown = async () => {
    if (showDropdown.value) {
        // Already open, just reposition
        nextTick(() => {
            calculateDropdownPosition()
        })
        return
    }

    // Close all other AccountSelect dropdowns
    if (dropdown.value) {
        window.dispatchEvent(new CustomEvent('close-account-select-dropdowns', {
            detail: {
                excludeId: dropdown.value.id || null
            }
        }))
    }

    showDropdown.value = true
    highlightedIndex.value = 0

    // If not searching, clear search term
    if (!isSearching.value) {
        searchTerm.value = ''
    }

    // Wait for DOM to update
    await nextTick()

    // Calculate position immediately after DOM update
    calculateDropdownPosition()

    // Always load accounts when opening (they might have changed)
    // Clear existing options and reload to get fresh data
    if (options.value.length === 0 && !isSearching.value) {
        await loadAccounts('')
    }

    // Recalculate position after accounts load and DOM updates
    await nextTick()
    calculateDropdownPosition()

    // Focus the input
    await nextTick()
    if (searchInput.value) {
        searchInput.value.focus()
        // If not searching, select all text
        if (!isSearching.value && selectedOption.value) {
            searchInput.value.select()
        }
    }

    // Final position calculation after all updates
    setTimeout(() => {
        calculateDropdownPosition()
    }, 100)
}

const hideDropdown = () => {
    showDropdown.value = false

    // Reset to selected value if not searching and has selection
    if (!isSearching.value) {
        if (selectedOption.value) {
            inputValue.value = selectedOption.value.name || ''
        } else {
            inputValue.value = ''
        }
        searchTerm.value = ''
    } else {
        // If was searching but didn't select, clear search mode
        if (!selectedOption.value) {
            inputValue.value = ''
            isSearching.value = false
            searchTerm.value = ''
        } else {
            // If has selection, show it
            inputValue.value = selectedOption.value.name || ''
            isSearching.value = false
            searchTerm.value = ''
        }
    }
}

const onSearch = () => {
    // Clear previous timeout
    if (searchTimeout) {
        clearTimeout(searchTimeout)
    }

    const query = searchTerm.value.trim()

    // If query is empty, show all loaded options
    if (query.length === 0) {
        highlightedIndex.value = 0
        return
    }

    // If query is 1 character, don't search yet
    if (query.length === 1) {
        highlightedIndex.value = 0
        return
    }

    // Search with debounce for 2+ characters
    searchTimeout = setTimeout(() => {
        // Clear options and load new search results
        options.value = []
        loadAccounts(query)
        highlightedIndex.value = 0
    }, 300)
}

let searchTimeout = null

const selectOption = (option) => {
    selectedOption.value = option
    inputValue.value = option.name || ''
    isSearching.value = false
    searchTerm.value = ''
    emit('update:modelValue', option.id)
    emit('update:account-name', option.name)
    emit('change', option.id)
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
        const highlighted = dropdownMenu.value?.querySelector('.bg-blue-50')
        if (highlighted) {
            highlighted.scrollIntoView({
                block: 'nearest'
            })
        }
    })
}

const handleClickOutside = (event) => {
    // Check if click is outside this dropdown
    const clickedOutside = dropdown.value && !dropdown.value.contains(event.target) &&
        dropdownMenu.value && !dropdownMenu.value.contains(event.target)

    // Also check if click is on another AccountSelect input (to close this one)
    const clickedOnOtherAccountSelect = event.target.closest('.account-select-wrapper') &&
        event.target.closest('.account-select-wrapper') !== dropdown.value

    if (clickedOutside || clickedOnOtherAccountSelect) {
        hideDropdown()
    }
}

const handleCloseOtherDropdowns = (event) => {
    // Close this dropdown if it's not the one that should stay open
    if (showDropdown.value) {
        const excludeId = event.detail?.excludeId
        const thisId = dropdown.value?.id

        // If this is not the excluded dropdown, close it
        if (!excludeId || thisId !== excludeId) {
            hideDropdown()
        }
    }
}

const handleResize = () => {
    if (showDropdown.value) {
        nextTick(() => {
            calculateDropdownPosition()
        })
    }
}

const handleScroll = () => {
    if (showDropdown.value) {
        nextTick(() => {
            calculateDropdownPosition()
        })
    }
}

// Watch for modelValue changes to load account name if needed
watch(() => props.modelValue, async (newValue, oldValue) => {
    // Only process if value actually changed
    if (String(newValue) === String(oldValue)) {
        return
    }

    if (newValue) {
        // Try to find in existing options first
        const found = options.value.find(opt => String(opt.id) === String(newValue))
        if (found) {
            selectedOption.value = found
            if (!isSearching.value) {
                inputValue.value = found.name || ''
            }
        } else {
            // Load accounts and find the one
            await loadAccounts('')
            const foundAfterLoad = options.value.find(opt => String(opt.id) === String(newValue))
            if (foundAfterLoad) {
                selectedOption.value = foundAfterLoad
                if (!isSearching.value) {
                    inputValue.value = foundAfterLoad.name || ''
                }
            } else {
                // Try to fetch this specific account by searching
                await loadAccounts(String(newValue))
                const foundAfterSearch = options.value.find(opt => String(opt.id) === String(newValue))
                if (foundAfterSearch) {
                    selectedOption.value = foundAfterSearch
                    if (!isSearching.value) {
                        inputValue.value = foundAfterSearch.name || ''
                    }
                }
            }
        }
    } else {
        selectedOption.value = null
        if (!isSearching.value) {
            inputValue.value = ''
        }
    }
}, {
    immediate: true
})

// Watch for accountName prop changes
watch(() => props.accountName, (newName) => {
    if (newName && !selectedOption.value && props.modelValue) {
        // We have a name but no selected option, create a temporary one
        selectedOption.value = {
            id: props.modelValue,
            name: newName
        }
        if (!isSearching.value) {
            inputValue.value = newName
        }
    }
}, {
    immediate: true
})

onMounted(() => {
    // Generate unique ID for this dropdown instance
    if (dropdown.value && !dropdown.value.id) {
        dropdown.value.id = 'account-select-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9)
    }

    document.addEventListener('click', handleClickOutside)
    window.addEventListener('resize', handleResize)
    window.addEventListener('scroll', handleScroll, true)
    window.addEventListener('close-account-select-dropdowns', handleCloseOtherDropdowns)

    // If we have an initial value, try to load it
    if (props.modelValue) {
        if (props.accountName) {
            selectedOption.value = {
                id: props.modelValue,
                name: props.accountName
            }
            inputValue.value = props.accountName
        } else {
            loadAccounts('')
        }
    }
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
    window.removeEventListener('resize', handleResize)
    window.removeEventListener('scroll', handleScroll, true)
    window.removeEventListener('close-account-select-dropdowns', handleCloseOtherDropdowns)
    if (searchTimeout) {
        clearTimeout(searchTimeout)
    }
})
</script>

<style scoped>
/* Main input field */
.account-select-input {
    width: 100%;
    padding: 6px 30px 6px 10px;
    font-size: 13px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    background: white;
    outline: none;
    cursor: pointer;
}

.account-select-input:focus {
    border-color: #3b82f6;
    outline: none;
}

/* Dropdown container */
.account-select-dropdown {
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    margin-top: 2px;
}


/* Options list */
.account-select-list {
    max-height: 200px;
    overflow-y: auto;
    overflow-x: hidden;
}

.account-select-list::-webkit-scrollbar {
    width: 6px;
}

.account-select-list::-webkit-scrollbar-track {
    background: #f9fafb;
}

.account-select-list::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 3px;
}

.account-select-list::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}

/* Empty states */
.account-select-empty {
    padding: 12px;
    text-align: center;
    color: #6b7280;
    font-size: 13px;
}

/* Option items */
.account-select-item {
    padding: 10px 12px;
    cursor: pointer;
    font-size: 13px;
    color: #111827;
    border-bottom: 1px solid #e5e7eb;
    background: white;
}

.account-select-item:last-child {
    border-bottom: none;
}

.account-select-item:hover {
    background: #f3f4f6;
}

.account-select-item-active {
    background: #3b82f6 !important;
    color: white !important;
}
</style>
