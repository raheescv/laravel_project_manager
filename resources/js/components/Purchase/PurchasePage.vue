<template>
    <div class="container-fluid py-4">
        <form @submit.prevent="handleSubmit" class="needs-validation" novalidate>
            <!-- Header Section -->
            <div class="row g-4 mb-4">
                <!-- Vendor Selection -->
                <div class="col-12 col-md-6">
                    <VendorInfo :selected-vendor-id="purchases.account_id" :account-balance="accountBalance"
                        :accounts="accounts" @vendor-changed="handleVendorChanged" />
                </div>

                <!-- Invoice Details -->
                <div class="col-12 col-md-6">
                    <InvoiceDetails :invoice-no="purchases.invoice_no || ''" :date="purchases.date || ''"
                        :delivery-date="purchases.delivery_date || ''" />
                </div>
            </div>

            <!-- Items Section -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <ItemsTable :items="items" @item-removed="handleItemRemoved" @item-updated="handleItemUpdated" />
                </div>
            </div>

            <!-- Summary Section -->
            <div class="row g-4">
                <!-- Left Column - Purchase Summary -->
                <div class="col-12 col-md-6">
                    <PurchaseSummary :gross-amount="purchases.gross_amount || 0" :total="purchases.total || 0"
                        :other-discount="purchases.other_discount || 0" :freight="purchases.freight || 0"
                        :address="purchases.address || ''" />
                </div>

                <!-- Right Column - Payment Details -->
                <div class="col-12 col-md-6">
                    <PaymentDetails :grand-total="purchases.grand_total || 0" :payments="payments"
                        :selected-payment-method-id="payment.payment_method_id" :payment-amount="payment.amount || 0"
                        :paid="purchases.paid || 0" :balance="purchases.balance || 0" :purchase-id="purchases.id"
                        :status="purchases.status || 'draft'" :created-user="purchases.created_user"
                        :updated-user="purchases.updated_user" :cancelled-user="purchases.cancelled_user"
                        :errors="errors" :can-print-purchase-note="canPrintPurchaseNote"
                        :can-print-barcode="canPrintBarcode" :can-cancel="canCancel" @add-payment="handleAddPayment"
                        @remove-payment="handleRemovePayment" @save="handleSave" @submit="handleSubmit" />
                </div>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { useLivewire } from '@/composables/useLivewire'
import VendorInfo from './VendorInfo.vue'
import InvoiceDetails from './InvoiceDetails.vue'
import ItemsTable from './ItemsTable.vue'
import PurchaseSummary from './PurchaseSummary.vue'
import PaymentDetails from './PaymentDetails.vue'

const props = defineProps({
    tableId: {
        type: [String, Number],
        default: null
    },
    initialData: {
        type: Object,
        default: () => ({})
    }
})

const { get, on, dispatch, call } = useLivewire()

// Reactive data
const purchases = ref({
    account_id: null,
    invoice_no: '',
    date: '',
    delivery_date: '',
    gross_amount: 0,
    total: 0,
    other_discount: 0,
    freight: 0,
    grand_total: 0,
    paid: 0,
    balance: 0,
    address: '',
    status: 'draft',
    id: null,
    created_user: null,
    updated_user: null,
    cancelled_user: null
})

const items = ref([])
const payments = ref([])
const payment = ref({
    payment_method_id: 1,
    amount: 0
})
const accountBalance = ref(0)
const accounts = ref([])
const errors = ref([])

// Permissions (these should come from backend)
const canPrintPurchaseNote = ref(false)
const canPrintBarcode = ref(false)
const canCancel = ref(false)

// Load data from Livewire
const loadData = () => {
    try {
        const livewireData = get('purchases')
        if (livewireData) {
            purchases.value = { ...purchases.value, ...livewireData }
        }

        const livewireItems = get('items')
        if (livewireItems) {
            items.value = Array.isArray(livewireItems) ? livewireItems : Object.values(livewireItems)
        }

        const livewirePayments = get('payments')
        if (livewirePayments) {
            payments.value = Array.isArray(livewirePayments) ? livewirePayments : Object.values(livewirePayments)
        }

        const livewirePayment = get('payment')
        if (livewirePayment) {
            payment.value = { ...payment.value, ...livewirePayment }
        }

        accountBalance.value = get('account_balance') || 0
        accounts.value = get('accounts') || []
    } catch (error) {
        console.error('Error loading data from Livewire:', error)
    }
}

const handleVendorChanged = (vendorId) => {
    purchases.value.account_id = vendorId
}

const handleItemRemoved = () => {
    loadData()
}

const handleItemUpdated = () => {
    loadData()
}

const handleAddPayment = () => {
    loadData()
}

const handleRemovePayment = () => {
    loadData()
}

const handleSave = (type) => {
    loadData()
}

const handleSubmit = () => {
    // This will be handled by the confirmation dialog
}

// Listen for Livewire events
const setupEventListeners = () => {
    // Show confirmation dialog
    const showConfirmationListener = on('show-confirmation', async (eventData) => {
        // Handle both Livewire array format [data] and direct object format
        let data = eventData
        if (Array.isArray(eventData) && eventData.length > 0) {
            data = eventData[0]
        } else if (eventData && eventData.detail) {
            // If it's wrapped in detail, extract it
            data = Array.isArray(eventData.detail) ? eventData.detail[0] : eventData.detail
        }
        if (!data) return

        const message = `
            <table class="table table-bordered table-striped">
                <tr>
                    <th colspan="2" class="text-center">${data.vendor || 'N/A'}</th>
                </tr>
                <tr>
                    <th class="text-start"><strong>Invoice No</strong></th>
                    <td class="text-end">${data.invoice_no || 'N/A'}</td>
                </tr>
                <tr>
                    <th class="text-start"><strong>Grand Total</strong></th>
                    <td class="text-end">${data.grand_total || '0.00'}</td>
                </tr>
                <tr>
                    <th class="text-start"><strong>Payment Methods</strong></th>
                    <td class="text-end">${data.payment_methods || 'No payments added'}</td>
                </tr>
                <tr>
                    <th class="text-start"><strong>Paid</strong></th>
                    <td class="text-end">${data.paid || '0.00'}</td>
                </tr>
                <tr>
                    <th class="text-start"><strong>Balance</strong></th>
                    <td class="text-end">${data.balance || '0.00'}</td>
                </tr>
            </table>
        `

        // Use global Swal (loaded via script tag)
        if (window.Swal) {
            window.Swal.fire({
                title: 'Are you sure?',
                html: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, submit it!'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    // Call the save method with 'completed' type to actually submit to backend
                    try {
                        await call('save', 'completed')
                    } catch (error) {
                        console.error('Error submitting purchase:', error)
                        if (window.Swal) {
                            window.Swal.fire({
                                title: 'Error!',
                                text: 'Failed to submit purchase. Please try again.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            })
                        }
                    }
                }
            })
        } else {
            // Fallback to native confirm if Swal is not available
            if (confirm('Are you sure you want to submit this purchase?')) {
                try {
                    await call('save', 'completed')
                } catch (error) {
                    console.error('Error submitting purchase:', error)
                }
            }
        }
    })

    // Redirect to print
    const redirectToPrintListener = on('redirect-to-print', (event) => {
        const id = event.detail?.id || event.detail
        if (id) {
            window.location.href = `/purchase/barcode-print/${id}`
        }
    })

    // Select dropdown values
    const selectDropdownValuesListener = on('SelectDropDownValues', (event) => {
        const data = Array.isArray(event.detail) ? event.detail[0] : event.detail
        if (data && data.account_id) {
            purchases.value.account_id = data.account_id
        }
    })

    return () => {
        // Cleanup listeners if needed
    }
}

onMounted(() => {
    // Load initial data if provided
    if (props.initialData && Object.keys(props.initialData).length > 0) {
        if (props.initialData.purchases) {
            purchases.value = { ...purchases.value, ...props.initialData.purchases }
        }
        if (props.initialData.items) {
            items.value = Array.isArray(props.initialData.items)
                ? props.initialData.items
                : Object.values(props.initialData.items)
        }
        if (props.initialData.payments) {
            payments.value = Array.isArray(props.initialData.payments)
                ? props.initialData.payments
                : Object.values(props.initialData.payments)
        }
        if (props.initialData.payment) {
            payment.value = { ...payment.value, ...props.initialData.payment }
        }
        if (props.initialData.account_balance !== undefined) {
            accountBalance.value = props.initialData.account_balance
        }
        if (props.initialData.accounts) {
            accounts.value = props.initialData.accounts
        }
        if (props.initialData.canPrintPurchaseNote !== undefined) {
            canPrintPurchaseNote.value = props.initialData.canPrintPurchaseNote
        }
        if (props.initialData.canPrintBarcode !== undefined) {
            canPrintBarcode.value = props.initialData.canPrintBarcode
        }
        if (props.initialData.canCancel !== undefined) {
            canCancel.value = props.initialData.canCancel
        }
    }

    // Wait for Livewire to be ready
    if (window.Livewire || window.livewire) {
        loadData()
        setupEventListeners()

        // Listen for Livewire data updates
        const updateListener = on('livewire-data-updated', () => {
            loadData()
        })

        // Poll for data updates (Livewire updates) - less frequent
        const interval = setInterval(() => {
            loadData()
        }, 1000)

        onUnmounted(() => {
            clearInterval(interval)
            if (updateListener) updateListener()
        })
    }
})
</script>

<style scoped>
.container-fluid {
    max-width: 1400px;
    margin: 0 auto;
}
</style>
