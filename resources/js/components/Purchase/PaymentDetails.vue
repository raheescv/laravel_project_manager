<template>
    <div class="pcx-card">
        <div class="pcx-chd">
            <div class="pcx-ci"><i class="fa fa-credit-card"></i></div>
            <h2 class="pcx-ct">Payments</h2>
            <span class="pcx-cnt">{{ payments.length }}</span>
        </div>

        <!-- nothing left to collect on a settled or cancelled purchase -->
        <div v-if="canAddPayment" class="pcx-payrow">
            <div class="pcx-f">
                <label for="payment-method-select">Method</label>
                <select ref="paymentMethodSelect" id="payment-method-select" class="select-payment_method_id-list"
                    :value="selectedPaymentMethodId" @change="handlePaymentMethodChange">
                    <option value="">Select Payment Method</option>
                </select>
            </div>
            <div class="pcx-f">
                <label for="payment">Amount</label>
                <input type="number" id="payment" class="pcx-input pcx-input--num" :value="paymentAmount"
                    @input="handlePaymentAmountChange($event.target.value)" @keydown.enter.prevent="handleAddPayment"
                    step="any" placeholder="0.00" />
            </div>
            <button type="button" @click="handleAddPayment" class="pcx-add" title="Add Payment">
                <i class="fa fa-plus"></i>
            </button>
        </div>

        <div class="pcx-plist">
            <div v-for="(paymentRow, index) in payments" :key="index" class="pcx-pl">
                <span class="mi"><i class="fa fa-money"></i></span>
                <span class="n">{{ paymentRow.name }}</span>
                <span class="a">{{ formatNumber(paymentRow.amount) }}</span>
                <button type="button" @click="handleRemovePayment(index)" class="pcx-del" title="Remove Payment">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            <div v-if="payments.length === 0" class="pcx-empty" style="padding:18px 8px">
                <i class="fa fa-credit-card"></i>
                <div class="t">No payments yet</div>
            </div>
        </div>

        <div class="pcx-balrow">
            <div class="pcx-bx pcx-bx--ok">
                <div class="k">Total Paid</div>
                <div class="v">{{ formatNumber(paid) }}</div>
            </div>
            <div class="pcx-bx" :class="{ 'pcx-bx--due': balance > 0 }">
                <div class="k">Balance</div>
                <div class="v">{{ formatNumber(balance) }}</div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { formatNumber } from '@/utils/number'
import { useLivewire } from '@/composables/useLivewire'
import { getRoute } from '@/utils/routes'

const props = defineProps({
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
    status: {
        type: String,
        default: 'draft'
    }
})

const emit = defineEmits(['add-payment', 'remove-payment'])

const canAddPayment = computed(() => props.status !== 'cancelled'
    && (props.status === 'draft' || props.balance > 0))

const paymentMethodSelect = ref(null)
const { set, call, on, get, dispatch } = useLivewire()
let tomSelectInstance = null

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
