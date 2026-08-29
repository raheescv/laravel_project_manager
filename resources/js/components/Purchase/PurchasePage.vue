<template>
    <div class="pcx">
        <!-- Enter inside a field must not submit — the deck button is the only way in -->
        <form @submit.prevent novalidate>
            <!-- ── COMMAND DECK ─────────────────────────────────────────────
                 Title, live grand total and every action stay pinned while
                 the item lines scroll underneath. -->
            <div class="pcx-deck" ref="deckRef">
                <div class="pcx-deck-top">
                    <div class="pcx-mark"><i class="fa fa-truck"></i></div>
                    <div>
                        <div class="pcx-eye">Purchase Entry</div>
                        <h1 class="pcx-title">{{ headline }}</h1>
                    </div>
                    <span class="pcx-chip" :class="statusChipClass">
                        <i class="fa fa-circle"></i> {{ statusLabel }}
                    </span>

                    <div class="pcx-spacer"></div>

                    <div class="pcx-readout">
                        <span class="k">Grand Total</span>
                        <span class="v">{{ formatCurrency(purchases.grand_total || 0) }}</span>
                    </div>

                    <template v-if="purchases.id">
                        <a v-if="canPrintPurchaseNote && purchases.status !== 'cancelled'" :href="printPurchaseNoteUrl"
                            target="_blank" class="pcx-btn pcx-btn--ghost">
                            <i class="fa fa-file-text-o"></i> Purchase Note
                        </a>
                        <a v-if="canPrintBarcode" :href="printBarcodeUrl" target="_blank"
                            class="pcx-btn pcx-btn--ghost">
                            <i class="fa fa-barcode"></i> Barcode Print
                        </a>
                    </template>

                    <template v-if="purchases.status === 'draft'">
                        <button type="button" @click="handleSave('draft')" class="pcx-btn">
                            <i class="fa fa-file-o"></i> Save Draft
                        </button>
                        <button type="button" @click="handleSubmit" class="pcx-btn pcx-btn--pri">
                            <i class="fa fa-check"></i> Submit Purchase
                        </button>
                    </template>
                    <template v-else-if="purchases.status !== 'cancelled'">
                        <button v-if="canCancel" type="button" @click="handleSave('cancelled')"
                            class="pcx-btn pcx-btn--danger">
                            <i class="fa fa-ban"></i> Cancel Purchase
                        </button>
                        <button type="button" @click="handleSubmit" class="pcx-btn pcx-btn--pri">
                            <i class="fa fa-check"></i> Submit Purchase
                        </button>
                    </template>
                </div>

                <div class="pcx-strip">
                    <div class="pcx-ds">
                        <div class="k">Vendor</div>
                        <div class="v">{{ vendorName || '—' }}</div>
                    </div>
                    <div class="pcx-ds">
                        <div class="k">Invoice No</div>
                        <div class="v">{{ purchases.invoice_no || '—' }}</div>
                    </div>
                    <div class="pcx-ds">
                        <div class="k">Items / Qty</div>
                        <div class="v">{{ items.length }} · {{ totalQuantity }}</div>
                    </div>
                    <div class="pcx-ds">
                        <div class="k">Balance Due</div>
                        <div class="v" :class="{ due: (purchases.balance || 0) > 0 }">
                            {{ formatCurrency(purchases.balance || 0) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- validation errors sit full width, right under the deck -->
            <div v-if="errors && errors.length > 0" class="pcx-alert">
                <i class="fa fa-exclamation-triangle"></i>
                <div>
                    <div class="t">Please fix the following</div>
                    <ul>
                        <li v-for="(error, index) in errors" :key="index">{{ error }}</li>
                    </ul>
                </div>
            </div>

            <div class="pcx-layout">
                <!-- ── MAIN COLUMN ──────────────────────────────────────── -->
                <div>
                    <div class="pcx-card">
                        <div class="pcx-chd">
                            <div class="pcx-ci"><i class="fa fa-building-o"></i></div>
                            <h2 class="pcx-ct">Party &amp; Invoice</h2>
                            <span class="pcx-cnt">Step 1</span>
                        </div>
                        <div class="pcx-fgrid">
                            <VendorInfo :selected-vendor-id="purchases.account_id" :accounts="accounts"
                                @vendor-changed="handleVendorChanged" />
                            <InvoiceDetails :invoice-no="purchases.invoice_no || ''" :date="purchases.date || ''"
                                :delivery-date="purchases.delivery_date || ''" />

                            <!-- full-row chip; it has to come AFTER the fields or grid
                                 auto-placement pushes them onto the next row -->
                            <div v-if="purchases.account_id" class="pcx-vendor">
                                <div class="pcx-av">{{ vendorInitials }}</div>
                                <div>
                                    <div class="pcx-vn">{{ vendorName || 'Vendor' }}</div>
                                    <div v-if="vendorMobile" class="pcx-vm">{{ vendorMobile }}</div>
                                </div>
                                <div class="pcx-vbal">
                                    <div class="k">Current Balance</div>
                                    <div class="v" :class="{ due: accountBalance < 0 }">
                                        {{ formatCurrency(accountBalance) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <ItemsTable :items="items" @item-removed="handleItemRemoved" @item-updated="handleItemUpdated" />

                    <div class="pcx-card">
                        <div class="pcx-chd">
                            <div class="pcx-ci"><i class="fa fa-map-marker"></i></div>
                            <h2 class="pcx-ct">Delivery Address / Notes</h2>
                        </div>
                        <div style="padding:12px 13px">
                            <textarea class="pcx-input" :value="purchases.address || ''"
                                @input="set('purchases.address', $event.target.value)"
                                placeholder="Enter shipping address…"></textarea>
                        </div>
                    </div>
                </div>

                <!-- ── STICKY RAIL ──────────────────────────────────────── -->
                <div class="pcx-rail">
                    <PurchaseSummary :gross-amount="purchases.gross_amount || 0" :total="purchases.total || 0"
                        :other-discount="purchases.other_discount || 0" :freight="purchases.freight || 0"
                        :grand-total="purchases.grand_total || 0" :item-discount="totalItemDiscount"
                        :tax-amount="totalTaxAmount" />

                    <PaymentDetails :payments="payments" :selected-payment-method-id="payment.payment_method_id"
                        :payment-amount="payment.amount || 0" :paid="purchases.paid || 0"
                        :balance="purchases.balance || 0" :status="purchases.status || 'draft'"
                        @add-payment="handleAddPayment" @remove-payment="handleRemovePayment" />
                </div>
            </div>

            <div v-if="purchases.created_user || purchases.updated_user || purchases.cancelled_user" class="pcx-audit">
                <span v-if="purchases.created_user">Created by <b>{{ purchases.created_user.name }}</b></span>
                <span v-if="purchases.updated_user">Updated by <b>{{ purchases.updated_user.name }}</b></span>
                <span v-if="purchases.cancelled_user" class="bad">Cancelled by
                    <b>{{ purchases.cancelled_user.name }}</b></span>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useLivewire } from '@/composables/useLivewire'
import { formatCurrency, formatNumber } from '@/utils/number'
import { getRoute } from '@/utils/routes'
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

const { get, set, on, call } = useLivewire()

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
const redirecting = ref(false)

// Permissions (these should come from backend)
const canPrintPurchaseNote = ref(false)
const canPrintBarcode = ref(false)
const canCancel = ref(false)

// Vendor label for the deck strip — TomSelect knows the name, Livewire only the id
const vendorMeta = ref(null)

// The rail hangs below the sticky deck; the deck grows when buttons wrap.
const deckRef = ref(null)
let deckObserver = null

const headline = computed(() => purchases.value.id
    ? `Purchase ${purchases.value.invoice_no || '#' + purchases.value.id}`
    : 'New Purchase')

const statusLabel = computed(() => {
    const status = purchases.value.status || 'draft'
    return status.charAt(0).toUpperCase() + status.slice(1)
})

const statusChipClass = computed(() => {
    const status = purchases.value.status || 'draft'
    if (status === 'completed') return 'pcx-chip--ok'
    if (status === 'cancelled') return 'pcx-chip--bad'
    return ''
})

const vendorName = computed(() => vendorMeta.value?.name || purchases.value.account?.name || '')

const vendorMobile = computed(() => vendorMeta.value?.mobile || purchases.value.account?.mobile || '')

const vendorInitials = computed(() => (vendorName.value || '?')
    .split(' ').filter(Boolean).slice(0, 2).map(word => word[0]).join('').toUpperCase())

const totalQuantity = computed(() => formatNumber(
    items.value.reduce((sum, item) => sum + (parseFloat(item.quantity) || 0), 0)))

const totalItemDiscount = computed(() =>
    items.value.reduce((sum, item) => sum + (parseFloat(item.discount) || 0), 0))

const totalTaxAmount = computed(() =>
    items.value.reduce((sum, item) => sum + (parseFloat(item.tax_amount) || 0), 0))

const printPurchaseNoteUrl = computed(() => purchases.value.id
    ? getRoute('purchase::print', { id: purchases.value.id }) : '#')

const printBarcodeUrl = computed(() => purchases.value.id
    ? getRoute('purchase::barcode-print', { id: purchases.value.id }) : '#')

// Load data from Livewire
const loadData = (preserveErrors = false) => {
    try {
        const currentErrors = preserveErrors ? errors.value : []

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

        // Preserve errors if requested (e.g., during submit when validation errors might be set)
        if (preserveErrors && currentErrors && currentErrors.length > 0) {
            errors.value = currentErrors
        }
        // Don't load errors here - they will come from validation-errors event
        // Only clear errors if there are no validation errors
        // Errors will be set by the validation-errors event listener
    } catch (error) {
        console.error('Error loading data from Livewire:', error)
    }
}

// Helper function to get Livewire component
const getComponent = () => {
    const livewire = window.Livewire || window.livewire
    if (!livewire) return null

    if (livewire.find) {
        const names = ['purchase.page', 'purchase-page', 'purchasePage']
        for (const name of names) {
            const comp = livewire.find(name)
            if (comp) return comp
        }
    }

    if (livewire.all) {
        const components = livewire.all()
        return components.length > 0 ? components[0] : null
    }

    return null
}

const handleVendorChanged = (vendorId, meta = null) => {
    purchases.value.account_id = vendorId
    vendorMeta.value = meta
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

const handleSave = async (type) => {
    if (type === 'cancelled' && !confirm('Are you sure to cancel this?')) {
        return
    }

    errors.value = []

    try {
        await call('save', type)
        loadData()
    } catch (error) {
        console.error('Error saving purchase:', error)
    }
}

const handleSubmit = (event) => {
    if (event) {
        event.preventDefault()
        event.stopPropagation()
    }

    // Don't clear errors here - let them show if validation fails after confirmation
    const name = vendorName.value
    const mobile = vendorMobile.value
    const vendor = name ? (mobile ? `${name}@${mobile}` : name) : 'N/A'

    const paymentMethods = payments.value
        .map(p => `${p.name}: ${formatCurrency(p.amount)}`)
        .join(', ') || 'No payments added'

    window.dispatchEvent(new CustomEvent('show-confirmation', {
        detail: {
            vendor,
            invoice_no: purchases.value.invoice_no || 'N/A',
            grand_total: formatCurrency(purchases.value.grand_total || 0),
            payment_methods: paymentMethods,
            paid: formatCurrency(purchases.value.paid || 0),
            balance: formatCurrency(purchases.value.balance || 0)
        }
    }))
}

const redirectToEditPage = (purchaseId) => {
    if (!purchaseId) {
        return
    }

    redirecting.value = true
    window.location.href = `/purchase/edit/${purchaseId}`
}

// Listen for Livewire events
const setupEventListeners = () => {
    // Function to process and set validation errors
    const processValidationErrors = (eventData) => {
        console.log('Processing validation errors:', eventData)
        let errorArray = []

        // Handle different event data formats
        if (Array.isArray(eventData)) {
            // If first element is an array (nested from Livewire)
            if (eventData.length > 0 && Array.isArray(eventData[0])) {
                errorArray = eventData[0]
            }
            // If first element is an object with errors
            else if (eventData.length > 0 && eventData[0] && typeof eventData[0] === 'object') {
                if (eventData[0].errors) {
                    errorArray = Array.isArray(eventData[0].errors) ? eventData[0].errors : [eventData[0].errors]
                } else if (Array.isArray(eventData[0])) {
                    errorArray = eventData[0]
                }
            }
            // If array contains strings directly
            else if (eventData.every(item => typeof item === 'string')) {
                errorArray = eventData
            }
            // If first element is a string
            else if (eventData.length > 0 && typeof eventData[0] === 'string') {
                errorArray = eventData
            }
        }
        // If it's an object
        else if (eventData && typeof eventData === 'object') {
            if (eventData.errors) {
                errorArray = Array.isArray(eventData.errors) ? eventData.errors : [eventData.errors]
            } else if (Array.isArray(eventData)) {
                errorArray = eventData
            }
        }
        // If it's a string
        else if (typeof eventData === 'string') {
            errorArray = [eventData]
        }

        // Filter and set errors
        errorArray = errorArray.filter(err => typeof err === 'string' && err.trim().length > 0)
        console.log('Final error array:', errorArray)
        if (errorArray.length > 0) {
            // Add timestamp to prevent premature clearing
            errorArray._timestamp = Date.now()
            errors.value = errorArray
        }
    }

    // Listen for validation errors using useLivewire's on method
    const validationErrorsListener = on('validation-errors', (eventData) => {
        console.log('Validation errors event received via on():', eventData)
        processValidationErrors(eventData)
    })

    // Also listen via window event listener (Livewire dispatches to window)
    const validationErrorsWindowListener = (event) => {
        console.log('Validation errors window event received:', event)
        const eventData = event.detail || event
        processValidationErrors(eventData)
    }

    if (window.Livewire) {
        window.addEventListener('validation-errors', validationErrorsWindowListener)
    }

    // Also listen for Livewire component events directly
    if (window.Livewire) {
        window.Livewire.hook('message.processed', (message, component) => {
            // Check if this is our purchase page component
            if (component && component.name === 'purchase.page') {
                // Check for validation errors in the response
                if (message.effects && message.effects.dispatches) {
                    message.effects.dispatches.forEach(dispatch => {
                        if (dispatch.event === 'validation-errors') {
                            console.log('Found validation-errors in Livewire message:', dispatch.params)
                            processValidationErrors(dispatch.params)
                        }
                    })
                }
            }
        })
    }

    // Listen for success event to clear errors and refresh page
    const successListener = on('success', (eventData) => {
        // Small delay to ensure validation errors have been processed first
        // and to allow redirect-to-print event to fire first if enabled
        setTimeout(() => {
            if (redirecting.value) {
                return
            }

            // Only proceed if no validation errors were set
            // This prevents refreshing when validation fails
            if (errors.value && errors.value.length > 0) {
                // Check if these are validation errors (they would have been set recently)
                const errorTimestamp = errors.value._timestamp
                const now = Date.now()
                if (errorTimestamp && (now - errorTimestamp) < 2000) {
                    // Recent validation errors, don't refresh
                    console.log('Validation errors present, not refreshing page')
                    return
                }
            }

            // Clear errors
            errors.value = []

            // Check if we're already redirecting to print (redirect-to-print event handles that)
            // If not redirecting, refresh the page to show fresh data
            // Use a small delay to ensure redirect-to-print has a chance to fire first
            setTimeout(() => {
                // Only refresh if we haven't been redirected
                if (document.visibilityState === 'visible') {
                    window.location.reload()
                }
            }, 100)
        }, 600)
    })

    // Listen for error events from Livewire
    const errorListener = on('error', (eventData) => {
        const errorMessage = Array.isArray(eventData) ? eventData[0]?.message || eventData[0] : (eventData?.message || eventData)
        if (errorMessage) {
            errors.value = Array.isArray(errorMessage) ? errorMessage : [errorMessage]
            // Also reload data to get validation errors
            setTimeout(() => {
                loadData()
            }, 100)
        }
    })

    // Listen for Livewire validation errors
    if (window.Livewire) {
        // Listen for Livewire's internal error events
        window.addEventListener('livewire:error', () => {
            setTimeout(() => {
                loadData()
            }, 100)
        })

        // Listen for component updates which might include validation errors
        // But don't clear errors on update - preserve validation errors
        window.addEventListener('livewire:update', () => {
            setTimeout(() => {
                // Preserve errors during update to prevent clearing validation errors
                const currentErrors = errors.value
                const errorTimestamp = currentErrors?._timestamp
                const now = Date.now()

                // Only preserve errors if they were set recently (within last 5 seconds)
                // This indicates they're validation errors from a recent submit attempt
                const shouldPreserveErrors = currentErrors &&
                    currentErrors.length > 0 &&
                    errorTimestamp &&
                    (now - errorTimestamp) < 5000

                if (shouldPreserveErrors) {
                    // Temporarily store errors
                    const preservedErrors = [...currentErrors]
                    loadData()
                    // Restore errors after loadData
                    errors.value = preservedErrors
                } else {
                    // Normal update, reload data normally
                    loadData()
                }
            }, 100)
        })
    }

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
                    // Don't clear errors here - wait for validation
                    // Call the save method with 'completed' type to actually submit to backend
                    try {
                        await call('save', 'completed')
                        // Wait a moment to ensure any events are processed
                        await new Promise(resolve => setTimeout(resolve, 300))
                        // Only proceed if save was successful (no validation errors were set)
                        if (!errors.value || errors.value.length === 0) {
                            const savedPurchaseId = get('table_id') || get('purchases')?.id || purchases.value.id

                            if (savedPurchaseId) {
                                redirectToEditPage(savedPurchaseId)
                                return
                            }

                            // Check if success event will handle refresh, otherwise refresh here
                            // The success event listener will handle the page refresh
                            // Just clear errors here as backup
                            errors.value = []
                        } else {
                            // Validation errors were set, don't clear them or refresh
                            console.log('Validation errors present, not refreshing:', errors.value)
                        }
                    } catch (error) {
                        console.error('Error submitting purchase:', error)
                        // Wait for validation-errors event to be processed
                        await new Promise(resolve => setTimeout(resolve, 500))
                        // If no validation errors were set, show a generic error
                        if (!errors.value || errors.value.length === 0) {
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
                }
            })
        } else {
            // Fallback to native confirm if Swal is not available
            if (confirm('Are you sure you want to submit this purchase?')) {
                try {
                    await call('save', 'completed')
                    await new Promise(resolve => setTimeout(resolve, 300))

                    if (!errors.value || errors.value.length === 0) {
                        const savedPurchaseId = get('table_id') || get('purchases')?.id || purchases.value.id

                        if (savedPurchaseId) {
                            redirectToEditPage(savedPurchaseId)
                        }
                    }
                } catch (error) {
                    console.error('Error submitting purchase:', error)
                }
            }
        }
    })

    // Redirect to print (if enabled, this will redirect instead of refreshing)
    const redirectToPrintListener = on('redirect-to-print', (eventData) => {
        const id = eventData?.id || eventData
        if (id) {
            redirecting.value = true
            // Redirect to print page instead of refreshing
            window.location.href = `/purchase/barcode-print/${id}`
        }
    })

    const redirectToEditListener = on('redirect-to-edit', (eventData) => {
        const url = eventData?.url || eventData
        if (url) {
            redirecting.value = true
            window.location.href = url
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

const syncDeckHeight = () => {
    const deck = deckRef.value
    if (!deck) return
    deck.closest('.pcx')?.style.setProperty('--pcx-deck-h', `${deck.offsetHeight}px`)
}

onUnmounted(() => {
    if (deckObserver) deckObserver.disconnect()
})

onMounted(() => {
    syncDeckHeight()
    if (deckRef.value && typeof ResizeObserver !== 'undefined') {
        deckObserver = new ResizeObserver(syncDeckHeight)
        deckObserver.observe(deckRef.value)
    }

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
.pcx {
    max-width: 1440px;
    margin: 0 auto;
    padding: 1.25rem 1rem 2rem;
}
</style>
