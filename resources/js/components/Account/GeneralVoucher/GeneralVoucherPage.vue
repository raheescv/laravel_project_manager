<template>
    <div>
        <div class="modal-header bg-primary text-white">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-white bg-opacity-25 rounded p-2">
                    <i class="fa fa-file-text text-primary"></i>
                </div>
                <div>
                    <h1 class="modal-title fs-5 mb-0 text-white">General Voucher</h1>
                    <p class="mb-0 small opacity-75">Journal Entry Form</p>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white" @click="closeModal" aria-label="Close"></button>
        </div>
        <form @submit.prevent="save">
            <div class="modal-body">
                <div v-if="errors.length > 0" class="alert alert-danger d-flex gap-2">
                    <i class="fa fa-exclamation-triangle mt-1"></i>
                    <div>
                        <strong>Validation Errors</strong>
                        <ul class="mb-0 mt-2">
                            <li v-for="error in errors" :key="error">{{ error }}</li>
                        </ul>
                    </div>
                </div>

                <div class="card mb-3 shadow-sm">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="date" class="form-label fw-semibold small text-uppercase">
                                    Journal Date <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fa fa-calendar"></i>
                                    </span>
                                    <input type="date" id="date" v-model="journals.date" class="form-control"
                                        required />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="reference_number" class="form-label fw-semibold small text-uppercase">
                                    Reference Number
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fa fa-tag"></i>
                                    </span>
                                    <input type="text" id="reference_number" v-model="journals.reference_number"
                                        class="form-control" placeholder="Enter reference number" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="description" class="form-label fw-semibold small text-uppercase">
                                    Description
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fa fa-align-left"></i>
                                    </span>
                                    <input type="text" id="description" v-model="journals.description"
                                        class="form-control" placeholder="Enter description" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive" style="overflow: visible;">
                            <table class="table table-bordered table-hover mb-0">
                                <thead class="table-primary">
                                    <tr>
                                        <th class="text-center" style="width: 50px;">#</th>
                                        <th>Account</th>
                                        <th class="text-end" style="width: 150px;">Debit</th>
                                        <th class="text-end" style="width: 150px;">Credit</th>
                                        <th>Description</th>
                                        <th>Name</th>
                                        <th class="text-center" style="width: 60px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(entry, index) in entries" :key="entry.key">
                                        <td class="text-center align-middle fw-semibold text-muted">{{ index + 1 }}</td>
                                        <td>
                                            <AccountSelect v-model="entry.account_id" :account-name="entry.account_name"
                                                @update:account-name="entry.account_name = $event"
                                                @change="onAccountChange(index)" placeholder="Select Account" />
                                        </td>
                                        <td>
                                            <input type="number" v-model.number="entry.debit"
                                                @input="onDebitChange(index)" :data-entry-index="index"
                                                data-field="debit" class="form-control form-control-sm text-end"
                                                step="0.01" min="0" placeholder="0.00" />
                                        </td>
                                        <td>
                                            <input type="number" v-model.number="entry.credit"
                                                @input="onCreditChange(index)" :data-entry-index="index"
                                                data-field="credit" class="form-control form-control-sm text-end"
                                                step="0.01" min="0" placeholder="0.00" />
                                        </td>
                                        <td>
                                            <input type="text" v-model="entry.description"
                                                class="form-control form-control-sm" placeholder="Entry description" />
                                        </td>
                                        <td>
                                            <input type="text" v-model="entry.person_name"
                                                class="form-control form-control-sm" placeholder="Person name" />
                                        </td>
                                        <td class="text-center align-middle">
                                            <button type="button" @click="removeEntry(entry.key)"
                                                class="btn btn-sm btn-link text-danger p-1" title="Remove Entry"
                                                :disabled="entries.length <= 2">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr class="table-light">
                                        <td colspan="2" class="text-end fw-bold">Total:</td>
                                        <td class="text-end fw-bold text-success">{{ formatCurrency(totalDebits) }}</td>
                                        <td class="text-end fw-bold text-danger">{{ formatCurrency(totalCredits) }}</td>
                                        <td colspan="3" class="text-center">
                                            <button type="button" @click="addEntry" class="btn btn-sm btn-success"
                                                title="Add New Entry">
                                                <i class="fa fa-plus me-1"></i>
                                                Add Entry
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="Math.abs(totalDebits - totalCredits) > 0.01" class="table-warning">
                                        <td colspan="7" class="text-center">
                                            <i class="fa fa-exclamation-circle me-2"></i>
                                            <strong>Debits and Credits are not balanced. Difference: {{
                                                formatCurrency(Math.abs(totalDebits - totalCredits)) }}</strong>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" @click="closeModal">
                    <i class="fa fa-times me-2"></i>
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary" :disabled="loading">
                    <i class="fa fa-check me-2"></i>
                    <span v-if="loading">Saving...</span>
                    <span v-else>Save Journal</span>
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
import {
    ref,
    computed,
    watch,
    onMounted,
    nextTick
} from 'vue'
import {
    useToast
} from 'vue-toastification'
import AccountSelect from './AccountSelect.vue'

// Route helper function
const getRoute = (name, params = {}) => {
    // Try to use Ziggy route if available
    if (window.route && typeof window.route === 'function') {
        try {
            return window.route(name, params)
        } catch (e) {
            // Fallback if route doesn't exist
        }
    }

    // Fallback to manual URL construction
    const routes = {
        'account::general-voucher::store': '/account/general-voucher',
        'account::general-voucher::update': (id) => `/account/general-voucher/${id}`,
        'account::general-voucher::data': (id) => `/account/general-voucher/${id}/data`
    }

    if (routes[name]) {
        if (typeof routes[name] === 'function') {
            return routes[name](params.id || params)
        }
        return routes[name]
    }

    return name
}

// Get CSRF token from meta tag
const getCsrfToken = () => {
    const metaTag = document.querySelector('meta[name="csrf-token"]')
    if (metaTag) {
        return metaTag.getAttribute('content')
    }
    // Fallback: try to get from input field (if form exists)
    const tokenInput = document.querySelector('input[name="_token"]')
    if (tokenInput) {
        return tokenInput.value
    }
    console.warn('CSRF token not found')
    return ''
}

const props = defineProps({
    tableId: {
        type: [String, Number],
        default: null
    }
})

const emit = defineEmits(['close', 'refresh'])

// Internal state to track if we're in "new mode" after "Save and New"
const isNewMode = ref(false)

const toast = useToast()

const journals = ref({
    branch_id: null,
    source: 'General Voucher',
    date: new Date().toISOString().split('T')[0],
    person_name: null,
    reference_number: null,
    remarks: null,
    description: ''
})

const entries = ref([])
const errors = ref([])
const loading = ref(false)

// Get branch_id from session or set default
onMounted(() => {
    // Try to get branch_id from a global variable or session
    if (window.branch_id) {
        journals.value.branch_id = window.branch_id
    }

    if (props.tableId) {
        loadJournal(props.tableId)
    } else {
        initializeEntries()
    }
})

// Watch description changes to sync to entries
watch(() => journals.value.description, (newValue) => {
    entries.value.forEach(entry => {
        if (!entry.description) {
            entry.description = newValue
        }
    })
})

const totalDebits = computed(() => {
    return entries.value.reduce((sum, entry) => sum + (parseFloat(entry.debit) || 0), 0)
})

const totalCredits = computed(() => {
    return entries.value.reduce((sum, entry) => sum + (parseFloat(entry.credit) || 0), 0)
})

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount || 0)
}

const initializeEntries = () => {
    entries.value = [{
        key: generateKey(),
        account_id: null,
        account_name: null,
        debit: 0,
        credit: 0,
        description: journals.value.description || '',
        person_name: null
    },
    {
        key: generateKey(),
        account_id: null,
        account_name: null,
        debit: 0,
        credit: 0,
        description: journals.value.description || '',
        person_name: null
    }
    ]
}

const generateKey = () => {
    return Date.now().toString(36) + Math.random().toString(36).substr(2)
}

const loadJournal = async (id) => {
    try {
        loading.value = true
        const url = getRoute('account::general-voucher::data', { id })
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        if (!response.ok) throw new Error('Failed to load journal')
        const data = await response.json()

        journals.value = {
            ...data.journal,
            source: 'General Voucher'
        }

        entries.value = data.entries.map(entry => ({
            key: generateKey(),
            account_id: entry.account_id,
            account_name: entry.account_name,
            debit: parseFloat(entry.debit) || 0,
            credit: parseFloat(entry.credit) || 0,
            description: entry.description || '',
            person_name: entry.person_name || null
        }))
    } catch (error) {
        toast.error('Failed to load journal: ' + error.message)
    } finally {
        loading.value = false
    }
}

const addEntry = () => {
    entries.value.push({
        key: generateKey(),
        account_id: null,
        account_name: null,
        debit: 0,
        credit: 0,
        description: journals.value.description || '',
        person_name: null
    })
}

const removeEntry = (key) => {
    if (entries.value.length <= 2) {
        toast.warning('At least two entries are required')
        return
    }
    if (confirm('Are you sure you want to remove this entry?')) {
        entries.value = entries.value.filter(entry => entry.key !== key)
    }
}

const onAccountChange = (index) => {
    const entry = entries.value[index]

    // Reset credit and debit if they have non-zero numeric values
    if (!isNumeric(entry.debit)) {
        entry.debit = 0
    }
    if (!isNumeric(entry.credit)) {
        entry.credit = 0
    }

    // Auto-focus debit or credit field if both are zero and account is selected
    if (entry.account_id && entry.debit === 0 && entry.credit === 0) {
        // Check other entries to determine which field to focus
        const otherEntries = entries.value.filter((e, i) => i !== index && e.account_id && (e.debit > 0 || e.credit > 0))
        const totalDebits = otherEntries.reduce((sum, e) => sum + (e.debit || 0), 0)
        const totalCredits = otherEntries.reduce((sum, e) => sum + (e.credit || 0), 0)

        // Focus the appropriate field after a short delay
        nextTick(() => {
            setTimeout(() => {
                let inputToFocus = null

                if (totalDebits > totalCredits) {
                    // More debits exist, focus credit field
                    inputToFocus = document.querySelector(`input[data-entry-index="${index}"][data-field="credit"]`)
                } else if (totalCredits > totalDebits) {
                    // More credits exist, focus debit field
                    inputToFocus = document.querySelector(`input[data-entry-index="${index}"][data-field="debit"]`)
                } else {
                    // Balanced or no other entries - alternate: even index = debit, odd index = credit
                    if (index % 2 === 0) {
                        inputToFocus = document.querySelector(`input[data-entry-index="${index}"][data-field="debit"]`)
                    } else {
                        inputToFocus = document.querySelector(`input[data-entry-index="${index}"][data-field="credit"]`)
                    }
                }

                if (inputToFocus) {
                    inputToFocus.focus()
                    inputToFocus.select()
                }
            }, 150)
        })
    }
}

const onDebitChange = (index) => {
    if (entries.value[index].debit > 0 && entries.value[index].credit > 0) {
        entries.value[index].credit = 0
    }
}

const onCreditChange = (index) => {
    if (entries.value[index].credit > 0 && entries.value[index].debit > 0) {
        entries.value[index].debit = 0
    }
}

const isNumeric = (value) => {
    return !isNaN(parseFloat(value)) && isFinite(value)
}

const validate = () => {
    errors.value = []

    if (!journals.value.date) {
        errors.value.push('The Date field is required.')
    }

    if (entries.value.length < 2) {
        errors.value.push('At least two journal entries are required.')
    }

    entries.value.forEach((entry, index) => {
        if (!entry.account_id) {
            errors.value.push(`The Account field is required for entry #${index + 1}.`)
        }
        if (!isNumeric(entry.debit) || entry.debit < 0) {
            errors.value.push(`The Debit field is required for entry #${index + 1}.`)
        }
        if (!isNumeric(entry.credit) || entry.credit < 0) {
            errors.value.push(`The Credit field is required for entry #${index + 1}.`)
        }
        if (entry.debit > 0 && entry.credit > 0) {
            errors.value.push(`Entry #${index + 1} cannot have both debit and credit amounts.`)
        }
    })

    // Check if debits equal credits
    if (Math.abs(totalDebits.value - totalCredits.value) > 0.01) {
        errors.value.push('Total debits must equal total credits.')
    }

    // Check if at least one debit and one credit
    const hasDebit = entries.value.some(entry => entry.debit > 0)
    const hasCredit = entries.value.some(entry => entry.credit > 0)

    if (!hasDebit || !hasCredit) {
        errors.value.push('At least one entry must have a debit amount and one must have a credit amount.')
    }

    return errors.value.length === 0
}

const save = async (saveAndAddNew = false) => {
    errors.value = []

    if (!validate()) {
        return
    }

    try {
        loading.value = true

        const entriesData = entries.value
            .filter(entry => entry.account_id && (entry.debit > 0 || entry.credit > 0))
            .map(entry => ({
                account_id: entry.account_id,
                debit: entry.debit || 0,
                credit: entry.credit || 0,
                description: entry.description || null,
                person_name: entry.person_name || null
            }))

        // Prepare payload matching the API structure
        const payload = {
            branch_id: journals.value.branch_id,
            date: journals.value.date,
            source: journals.value.source || 'General Voucher',
            person_name: journals.value.person_name || null,
            reference_number: journals.value.reference_number || null,
            remarks: journals.value.remarks || null,
            description: journals.value.description || null,
            entries: entriesData
        }

        // Use route helper for API endpoints
        // If in new mode (after "Save and New"), always use POST
        const effectiveTableId = isNewMode.value ? null : props.tableId
        const url = effectiveTableId ?
            getRoute('account::general-voucher::update', { id: effectiveTableId }) :
            getRoute('account::general-voucher::store')

        const method = effectiveTableId ? 'PUT' : 'POST'

        const csrfToken = getCsrfToken()
        if (!csrfToken) {
            throw new Error('CSRF token not found. Please refresh the page.')
        }

        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        })

        const data = await response.json()

        if (!response.ok) {
            // Handle validation errors (422)
            if (response.status === 422 && data.errors) {
                const validationErrors = []
                Object.keys(data.errors).forEach(key => {
                    if (Array.isArray(data.errors[key])) {
                        data.errors[key].forEach(error => {
                            validationErrors.push(`${key}: ${error}`)
                        })
                    } else {
                        validationErrors.push(`${key}: ${data.errors[key]}`)
                    }
                })
                errors.value = validationErrors
                toast.error('Validation failed. Please check the form.')
                return
            }

            // Handle CSRF token mismatch (419)
            if (response.status === 419) {
                toast.error('Session expired. Please refresh the page and try again.')
                return
            }

            throw new Error(data.message || 'Failed to save journal')
        }

        if (!data.success) {
            throw new Error(data.message || 'Failed to save journal')
        }

        toast.success(data.message || 'Journal saved successfully')

        // Dispatch refresh event
        window.dispatchEvent(new CustomEvent('RefreshGeneralVoucherTable'))
        emit('refresh')

        if (saveAndAddNew) {
            // Reset form for new entry
            journals.value = {
                branch_id: journals.value.branch_id,
                source: 'General Voucher',
                date: new Date().toISOString().split('T')[0],
                person_name: null,
                reference_number: null,
                remarks: null,
                description: ''
            }
            initializeEntries()

            // If we were updating, switch to new mode
            if (props.tableId) {
                isNewMode.value = true
                // Dispatch custom event to remount as new entry
                window.dispatchEvent(new CustomEvent('GeneralVoucher-Reset-To-New'))
            }
        } else {
            // Regular save: keep the form open with current values
            // Don't close the modal, just keep editing
            // If we were in new mode, update the tableId from the response
            if (data.data && data.data.id && isNewMode.value) {
                // The entry was just created, but we'll stay in new mode for next save
                // This allows continuing to edit the newly created entry
            }
        }
    } catch (error) {
        toast.error(error.message || 'Failed to save journal')
        errors.value = [error.message]
    } finally {
        loading.value = false
    }
}

const closeModal = () => {
    const modal = document.getElementById('GeneralVoucherModal')
    if (modal) {
        const bsModal = bootstrap.Modal.getInstance(modal)
        if (bsModal) {
            bsModal.hide()
        }
    }
    emit('close')
}
</script>

<style scoped>
/* Ensure dropdown appears above everything */
.table-responsive {
    overflow: visible !important;
}

.table td {
    position: relative;
    overflow: visible !important;
}
</style>
