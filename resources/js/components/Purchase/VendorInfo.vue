<template>
    <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-gradient bg-primary text-white py-2">
            <h5 class="card-title mb-0 text-white">
                <i class="demo-psi-building me-2"></i>Vendor Information
            </h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="vendor-select" class="form-label">
                    Select Vendor <span class="text-danger">*</span>
                </label>
                <select ref="vendorSelect" id="vendor-select" class="select-vendor_id" :value="selectedVendorId"
                    @change="handleVendorChange">
                    <option value="">Select Vendor</option>
                </select>
            </div>
            <div class="alert alert-light mb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-medium">Current Balance</span>
                    <span class="h5 mb-0" :class="accountBalance < 0 ? 'text-danger' : 'text-success'">
                        {{ formatCurrency(accountBalance) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from 'vue'
import { formatCurrency } from '@/utils/currency'
import { useLivewire } from '@/composables/useLivewire'
import { getRoute } from '@/utils/routes'

const props = defineProps({
    selectedVendorId: {
        type: [String, Number],
        default: null
    },
    accountBalance: {
        type: Number,
        default: 0
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

const handleVendorChange = (event) => {
    const value = event.target.value || null
    emit('vendor-changed', value)
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
                return `<div>${escape(item.name || item.text || '')}${item.mobile ? `@${escape(item.mobile)}` : ''}</div>`
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
