<template>
    <div class="card shadow-sm border-0">
        <div class="card-header bg-gradient bg-primary text-white py-3">
            <h5 class="card-title mb-0 d-flex align-items-center text-white">
                <i class="demo-psi-credit-card-2 fs-4 me-2"></i>Payment Details
            </h5>
        </div>
        <div class="card-body">
            <div class="alert alert-primary text-center mb-3">
                <span class="d-block small mb-1">Total Payable Amount</span>
                <span class="h4 mb-0">{{ formatCurrency(grandTotal) }}</span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-7">
                    <label class="form-label">Payment Method</label>
                    <select ref="paymentMethodSelect" id="payment-method-select" class="select-payment_method_id-list"
                        :value="selectedPaymentMethodId" @change="handlePaymentMethodChange">
                        <option value="">Select Payment Method</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Amount</label>
                    <div class="input-group">
                        <input type="number" class="form-control text-end" :value="paymentAmount"
                            @input="handlePaymentAmountChange($event.target.value)" step="any" placeholder="0.00" />
                        <button type="button" @click="handleAddPayment" class="btn btn-primary">
                            <i class="demo-psi-add me-1"></i> Add
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive mb-4">
                <table class="table table-sm table-bordered table-hover">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 60%">Payment Method</th>
                            <th class="text-end">Amount</th>
                            <th style="width: 80px">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(payment, index) in payments" :key="index">
                            <td class="align-middle">{{ payment.name }}</td>
                            <td class="text-end align-middle">{{ formatCurrency(payment.amount) }}</td>
                            <td class="text-center">
                                <button type="button" @click="handleRemovePayment(index)"
                                    class="btn btn-sm btn-icon btn-outline-danger rounded-circle"
                                    title="Remove Payment">
                                    <i class="demo-pli-recycling"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="payments.length === 0">
                            <td colspan="3" class="text-center py-4 text-muted">
                                <i class="demo-psi-credit-card-2 fs-1 mb-2 d-block"></i>
                                No payments added yet
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="errors && errors.length > 0" class="alert alert-danger border-0 mb-4">
                <ul class="mb-0 ps-3">
                    <li v-for="(error, index) in errors" :key="index">{{ error }}</li>
                </ul>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card bg-light border-0">
                        <div class="card-body p-3">
                            <p v-if="createdUser" class="mb-1 small">
                                Created By: <strong>{{ createdUser.name }}</strong>
                            </p>
                            <p v-if="updatedUser" class="mb-1 small">
                                Updated By: <strong>{{ updatedUser.name }}</strong>
                            </p>
                            <p v-if="cancelledUser" class="mb-0 small text-danger">
                                Cancelled By: <strong>{{ cancelledUser.name }}</strong>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-light border-0">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small">Total Paid:</span>
                                <span class="h6 mb-0 text-success">{{ formatCurrency(paid) }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small">Balance:</span>
                                <span class="h6 mb-0 text-danger">{{ formatCurrency(balance) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 flex-wrap">
                <template v-if="purchaseId">
                    <template v-if="status !== 'cancelled'">
                        <a v-if="canPrintPurchaseNote" :href="printPurchaseNoteUrl" target="_blank"
                            class="btn btn-secondary">
                            <i class="demo-psi-printer me-1"></i> Purchase Note Print
                        </a>
                    </template>
                    <a v-if="canPrintBarcode" :href="printBarcodeUrl" target="_blank" class="btn btn-info">
                        <i class="demo-psi-printer me-1"></i> Print
                    </a>
                </template>
                <template v-if="status === 'draft'">
                    <button type="button" @click="handleSave('draft')" class="btn btn-secondary">
                        <i class="demo-psi-file me-1"></i> Save Draft
                    </button>
                    <button type="button" @click="handleSubmit" class="btn btn-primary">
                        <i class="demo-psi-check me-1"></i> Submit
                    </button>
                </template>
                <template v-else>
                    <template v-if="status !== 'cancelled'">
                        <button v-if="canCancel" type="button" @click="handleSave('cancelled')" class="btn btn-danger">
                            <i class="demo-psi-cross me-1"></i> Cancel Purchase
                        </button>
                        <button type="button" @click="handleSubmit" class="btn btn-primary">
                            <i class="demo-psi-check me-1"></i> Submit
                        </button>
                    </template>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { formatCurrency } from '@/utils/currency'
import { useLivewire } from '@/composables/useLivewire'
import { getRoute } from '@/utils/routes'

const props = defineProps({
    grandTotal: {
        type: Number,
        default: 0
    },
    payments: {
        type: Array,
        default: () => []
    },
    selectedPaymentMethodId: {
        type: [String, Number],
        default: null
    },
    paymentAmount: {
        type: Number,
        default: 0
    },
    paid: {
        type: Number,
        default: 0
    },
    balance: {
        type: Number,
        default: 0
    },
    purchaseId: {
        type: [String, Number],
        default: null
    },
    status: {
        type: String,
        default: 'draft'
    },
    createdUser: {
        type: Object,
        default: null
    },
    updatedUser: {
        type: Object,
        default: null
    },
    cancelledUser: {
        type: Object,
        default: null
    },
    errors: {
        type: Array,
        default: () => []
    },
    canPrintPurchaseNote: {
        type: Boolean,
        default: false
    },
    canPrintBarcode: {
        type: Boolean,
        default: false
    },
    canCancel: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['add-payment', 'remove-payment', 'save', 'submit', 'clear-errors'])

const paymentMethodSelect = ref(null)
const { set, call, on, get, dispatch } = useLivewire()
let tomSelectInstance = null

const printPurchaseNoteUrl = computed(() => {
    return props.purchaseId ? getRoute('purchase::print', { id: props.purchaseId }) : '#'
})

const printBarcodeUrl = computed(() => {
    return props.purchaseId ? getRoute('purchase::barcode-print', { id: props.purchaseId }) : '#'
})

const handlePaymentMethodChange = (event) => {
    const value = event.target.value || null
    set('payment.payment_method_id', value)
    nextTick(() => {
        const paymentInput = document.querySelector('#payment')
        if (paymentInput) {
            paymentInput.select()
        }
    })
}

const handlePaymentAmountChange = (value) => {
    const numValue = parseFloat(value) || 0
    set('payment.amount', numValue)
}

const handleAddPayment = async () => {
    try {
        await call('addPayment')
        emit('add-payment')
        if (tomSelectInstance) {
            tomSelectInstance.clear()
        }
    } catch (error) {
        console.error('Error adding payment:', error)
    }
}

const handleRemovePayment = async (index) => {
    if (confirm('Are you sure?')) {
        try {
            await call('removePayment', index)
            emit('remove-payment', index)
        } catch (error) {
            console.error('Error removing payment:', error)
        }
    }
}

const handleSave = async (type) => {
    if (type === 'cancelled' && !confirm('Are you sure to cancel this?')) {
        return
    }

    // Clear errors before saving
    emit('clear-errors')

    try {
        await call('save', type)
        emit('save', type)
    } catch (error) {
        console.error('Error saving purchase:', error)
    }
}

const handleSubmit = async (event) => {
    // Prevent form submission if event is passed
    if (event) {
        event.preventDefault()
        event.stopPropagation()
    }

    // Don't clear errors here - let them show if validation fails after confirmation

    // Get vendor name and payment methods for confirmation
    // Get vendor name from accounts or purchases
    const accounts = get('accounts') || []
    const purchases = get('purchases') || {}
    const vendorId = purchases.account_id

    // Find vendor name - first check if purchases has account object with name
    let vendorName = 'N/A'
    if (purchases.account) {
        // Format: name@mobile (matching backend format)
        const name = purchases.account.name || ''
        const mobile = purchases.account.mobile || ''
        vendorName = mobile ? `${name}@${mobile}` : (name || 'N/A')
    } else if (vendorId && accounts.length > 0) {
        // Find vendor name from accounts array
        const vendor = accounts.find(acc => {
            const accId = acc.id || acc.value
            return accId == vendorId
        })
        if (vendor) {
            const name = vendor.name || vendor.text || ''
            const mobile = vendor.mobile || ''
            vendorName = mobile ? `${name}@${mobile}` : (name || 'N/A')
        }
    } else if (vendorId) {
        // If we have vendorId but no account data, try to fetch it from Livewire
        // This is a fallback for when data hasn't loaded yet
        const livewirePurchases = get('purchases') || {}
        if (livewirePurchases.account) {
            const name = livewirePurchases.account.name || ''
            const mobile = livewirePurchases.account.mobile || ''
            vendorName = mobile ? `${name}@${mobile}` : (name || 'N/A')
        }
    }

    // Get invoice number
    const invoiceNo = purchases.invoice_no || 'N/A'

    // Get payment methods summary
    const paymentMethodsList = props.payments.map(p => `${p.name}: ${formatCurrency(p.amount)}`).join(', ')
    const paymentMethods = paymentMethodsList || 'No payments added'

    // Prepare confirmation data
    const confirmationData = {
        vendor: vendorName,
        invoice_no: invoiceNo,
        grand_total: formatCurrency(props.grandTotal),
        payment_methods: paymentMethods,
        paid: formatCurrency(props.paid),
        balance: formatCurrency(props.balance)
    }

    // Dispatch confirmation event - use window.dispatchEvent directly to ensure it works
    window.dispatchEvent(new CustomEvent('show-confirmation', { detail: confirmationData }))

    emit('submit')
}

const initializePaymentMethodSelect = () => {
    if (!paymentMethodSelect.value || typeof window.TomSelect === 'undefined') {
        return
    }

    if (tomSelectInstance) {
        tomSelectInstance.destroy()
    }

    const defaultPaymentMethodId = props.selectedPaymentMethodId

    tomSelectInstance = new window.TomSelect(paymentMethodSelect.value, {
        persist: false,
        valueField: 'id',
        nameField: 'name',
        searchField: ['name', 'id'],
        load: function (query, callback) {
            const url = getRoute('account::list') +
                '?query=' + encodeURIComponent(query) +
                '&is_payment_method=1'

            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok')
                    return response.json()
                })
                .then(json => callback(json.items))
                .catch(err => {
                    console.error('Error loading payment method data:', err)
                    callback()
                })
        },
        onFocus: function () {
            this.load('')
        },
        onChange: function (value) {
            handlePaymentMethodChange({ target: { value } })
        },
        render: {
            option: function (item, escape) {
                return `<div>${escape(item.name || item.text || '')}</div>`
            },
            item: function (item, escape) {
                return `<div>${escape(item.name || item.text || '')}</div>`
            }
        }
    })

    // Preload the default payment method
    if (defaultPaymentMethodId) {
        // Use a small delay to ensure TomSelect is fully initialized
        setTimeout(() => {
            // Fetch the payment method data and set it
            const url = getRoute('account::list') +
                '?query=' + encodeURIComponent('') +
                '&is_payment_method=1'

            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok')
                    return response.json()
                })
                .then(json => {
                    if (json.items && json.items.length > 0) {
                        // Find the matching item
                        const item = json.items.find(i =>
                            String(i.id) === String(defaultPaymentMethodId) ||
                            String(i.value) === String(defaultPaymentMethodId)
                        )
                        if (item) {
                            // Add as option first
                            tomSelectInstance.addOption(item)
                            // Then set as value
                            tomSelectInstance.setValue(String(defaultPaymentMethodId))
                        } else {
                            // If item not found, try to add by ID directly
                            tomSelectInstance.addItem(String(defaultPaymentMethodId))
                        }
                    } else {
                        // Fallback: try to add by ID directly
                        tomSelectInstance.addItem(String(defaultPaymentMethodId))
                    }
                })
                .catch(err => {
                    console.error('Error loading default payment method:', err)
                    // Fallback: try to add by ID directly
                    tomSelectInstance.addItem(String(defaultPaymentMethodId))
                })
        }, 100)
    }
}

// Listen for ResetSelectBox event
on('ResetSelectBox', (event) => {
    const detail = Array.isArray(event.detail) ? event.detail[0] : event.detail
    if (detail && detail.type !== 'cancelled') {
        if (tomSelectInstance && props.selectedPaymentMethodId) {
            tomSelectInstance.addItem(props.selectedPaymentMethodId)
        }
    }
})

watch(() => props.selectedPaymentMethodId, async (newValue) => {
    if (tomSelectInstance && newValue) {
        // Check if the option already exists
        const currentValue = tomSelectInstance.getValue()
        if (currentValue == newValue) {
            return // Already set
        }

        try {
            // Load the payment method data
            const url = getRoute('account::list') +
                '?query=' + encodeURIComponent(newValue) +
                '&is_payment_method=1'

            const response = await fetch(url)
            if (response.ok) {
                const json = await response.json()
                if (json.items && json.items.length > 0) {
                    const item = json.items.find(i =>
                        (i.id && i.id == newValue) ||
                        (i.value && i.value == newValue)
                    )
                    if (item) {
                        tomSelectInstance.addOption(item)
                        tomSelectInstance.setValue(newValue)
                    } else {
                        tomSelectInstance.addItem(newValue)
                    }
                } else {
                    tomSelectInstance.addItem(newValue)
                }
            } else {
                tomSelectInstance.addItem(newValue)
            }
        } catch (error) {
            console.error('Error loading payment method:', error)
        tomSelectInstance.addItem(newValue)
        }
    }
})

onMounted(() => {
    if (typeof window.TomSelect !== 'undefined') {
        initializePaymentMethodSelect()
    } else {
        setTimeout(() => {
            if (typeof window.TomSelect !== 'undefined') {
                initializePaymentMethodSelect()
            }
        }, 100)
    }
})
</script>
