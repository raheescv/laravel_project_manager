<template>
    <div class="pcx-f">
        <label for="vendor-select">Vendor <i class="req">*</i></label>
        <select ref="vendorSelect" id="vendor-select" class="select-vendor_id" :value="selectedVendorId"
            @change="handleVendorChange">
            <option value="">Select Vendor</option>
        </select>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from 'vue'
import { useLivewire } from '@/composables/useLivewire'
import { getRoute } from '@/utils/routes'

const props = defineProps({
    selectedVendorId: {
        type: [String, Number],
        default: null
    },
    accounts: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['vendor-changed'])

const vendorSelect = ref(null)
const { set, dispatch } = useLivewire()
let tomSelectInstance = null

// The deck strip and the vendor chip need the label; Livewire only carries the id.
const vendor = ref(null)

const readOption = (value) => {
    const option = value && tomSelectInstance ? tomSelectInstance.options[value] : null
    vendor.value = option ? { name: option.name || option.text || '', mobile: option.mobile || '' } : null

    return vendor.value
}

const handleVendorChange = (event) => {
    const value = event.target.value || null
    emit('vendor-changed', value, readOption(value))
    set('purchases.account_id', value)

    // Open product select after vendor is selected
    nextTick(() => {
        const productSelect = document.querySelector('#product-select')
        if (productSelect && productSelect.tomselect) {
            productSelect.tomselect.open()
        }
    })
}

const initializeTomSelect = () => {
    if (!vendorSelect.value || typeof window.TomSelect === 'undefined') {
        return
    }

    // Destroy existing instance if any
    if (tomSelectInstance) {
        tomSelectInstance.destroy()
    }

    const defaultVendorId = props.selectedVendorId

    tomSelectInstance = new window.TomSelect(vendorSelect.value, {
        persist: false,
        valueField: 'id',
        nameField: 'name',
        searchField: ['name', 'mobile', 'email', 'id'],
        load: function (query, callback) {
            const url = getRoute('account::list') +
                '?query=' + encodeURIComponent(query) +
                '&model=vendor'

            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok')
                    return response.json()
                })
                .then(json => callback(json.items))
                .catch(err => {
                    console.error('Error loading vendor data:', err)
                    callback()
                })
        },
        onFocus: function () {
            this.load('')
        },
        render: {
            option: function (item, escape) {
                const name = escape(item.name || item.text || '')
                const meta = item.mobile ? `<div class="opt-meta">${escape(item.mobile)}</div>` : ''

                return `<div><div class="opt-name">${name}</div>${meta}</div>`
            },
            item: function (item, escape) {
                return `<div>${escape(item.name || item.text || '')}</div>`
            }
        },
        create: function (input, callback) {
            dispatch('Vendor-Page-Create-Component', { name: input })
        },
        onChange: function (value) {
            handleVendorChange({ target: { value } })
        }
    })

    // Preload the default vendor
    if (defaultVendorId) {
        // Use a small delay to ensure TomSelect is fully initialized
        setTimeout(() => {
            // Fetch the vendor data and set it
            const url = getRoute('account::list') +
                '?query=' + encodeURIComponent('') +
                '&model=vendor'

            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok')
                    return response.json()
                })
                .then(json => {
                    if (json.items && json.items.length > 0) {
                        // Find the matching item
                        const item = json.items.find(i =>
                            String(i.id) === String(defaultVendorId) ||
                            String(i.value) === String(defaultVendorId)
                        )
                        if (item) {
                            // Add as option first
                            tomSelectInstance.addOption(item)
                            // Then set as value
                            tomSelectInstance.setValue(String(defaultVendorId))
                            emit('vendor-changed', defaultVendorId, readOption(String(defaultVendorId)))
                        } else {
                            // If item not found, try to add by ID directly
                            tomSelectInstance.addItem(String(defaultVendorId))
                        }
                    } else {
                        // Fallback: try to add by ID directly
                        tomSelectInstance.addItem(String(defaultVendorId))
                    }
                })
                .catch(err => {
                    console.error('Error loading default vendor:', err)
                    // Fallback: try to add by ID directly
                    tomSelectInstance.addItem(String(defaultVendorId))
                })
        }, 100)
    }
}

watch(() => props.selectedVendorId, async (newValue) => {
    if (tomSelectInstance && newValue) {
        // Check if the option already exists
        const currentValue = tomSelectInstance.getValue()
        if (currentValue == newValue) {
            return // Already set
        }

        try {
            // Load the vendor data
            const url = getRoute('account::list') +
                '?query=' + encodeURIComponent('') +
                '&model=vendor'

            const response = await fetch(url)
            if (response.ok) {
                const json = await response.json()
                if (json.items && json.items.length > 0) {
                    const item = json.items.find(i =>
                        String(i.id) === String(newValue) ||
                        String(i.value) === String(newValue)
                    )
                    if (item) {
                        tomSelectInstance.addOption(item)
                        tomSelectInstance.setValue(String(newValue))
                        emit('vendor-changed', newValue, readOption(String(newValue)))
                    } else {
                        tomSelectInstance.addItem(String(newValue))
                    }
                } else {
                    tomSelectInstance.addItem(String(newValue))
                }
            } else {
                tomSelectInstance.addItem(String(newValue))
            }
        } catch (error) {
            console.error('Error loading vendor:', error)
            tomSelectInstance.addItem(String(newValue))
        }
    }
})

onMounted(() => {
    // Wait for TomSelect to be available
    if (typeof window.TomSelect !== 'undefined') {
        initializeTomSelect()
    } else {
        // Retry after a short delay
        setTimeout(() => {
            if (typeof window.TomSelect !== 'undefined') {
                initializeTomSelect()
            }
        }, 100)
    }
})
</script>
