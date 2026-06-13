<template>
    <div class="gvx">
        <!-- ═══════════════  HERO HEADER  ═══════════════ -->
        <div class="gvx-hero">
            <span class="gvx-glow a"></span>
            <span class="gvx-glow b"></span>
            <div class="gvx-hero-row">
                <div class="gvx-hero-id">
                    <div class="gvx-hero-ic"><i class="fa fa-file-text-o"></i></div>
                    <div>
                        <div class="gvx-eyebrow">Journal Entry</div>
                        <h1 class="gvx-hero-title">General Voucher</h1>
                    </div>
                </div>
                <div class="gvx-hero-tools">
                    <span class="gvx-bpill" :class="!hasAmounts ? 'is-draft' : (isBalanced ? 'is-ok' : 'is-off')">
                        <span class="dot"></span>
                        <span v-if="!hasAmounts">Draft</span>
                        <span v-else-if="isBalanced">Balanced</span>
                        <span v-else>Out of balance</span>
                    </span>
                    <button type="button" class="gvx-x" @click="closeModal" aria-label="Close">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <form @submit.prevent="save">
            <div class="gvx-body">
                <!-- Validation errors -->
                <div v-if="errors.length > 0" class="gvx-errors">
                    <div class="gvx-errors-ic"><i class="fa fa-exclamation-triangle"></i></div>
                    <div class="gvx-errors-body">
                        <div class="gvx-errors-title">Please fix the following</div>
                        <ul class="gvx-errors-list">
                            <li v-for="error in errors" :key="error">{{ error }}</li>
                        </ul>
                    </div>
                </div>

                <!-- Meta fields -->
                <div class="gvx-meta">
                    <div class="gvx-field">
                        <label for="date" class="gvx-label">Journal Date <span class="req">*</span></label>
                        <div class="gvx-input">
                            <span class="gvx-input-ic"><i class="fa fa-calendar"></i></span>
                            <input type="date" id="date" v-model="journals.date" required />
                        </div>
                    </div>
                    <div class="gvx-field">
                        <label for="reference_number" class="gvx-label">Reference Number</label>
                        <div class="gvx-input">
                            <span class="gvx-input-ic"><i class="fa fa-tag"></i></span>
                            <input type="text" id="reference_number" v-model="journals.reference_number"
                                placeholder="Optional reference" />
                        </div>
                    </div>
                    <div class="gvx-field">
                        <label for="description" class="gvx-label">Description</label>
                        <div class="gvx-input">
                            <span class="gvx-input-ic"><i class="fa fa-align-left"></i></span>
                            <input type="text" id="description" v-model="journals.description"
                                placeholder="Memo applied to all lines" />
                        </div>
                    </div>
                </div>

                <!-- Journal lines -->
                <div class="gvx-panel">
                    <div class="gvx-panel-head">
                        <span class="gvx-panel-ic"><i class="fa fa-list-ul"></i></span>
                        <div>
                            <div class="gvx-panel-title">Journal Lines</div>
                            <div class="gvx-panel-sub">{{ entries.length }} lines · debits must equal credits</div>
                        </div>
                    </div>

                    <div class="gvx-table-wrap">
                        <table class="gvx-table">
                            <thead>
                                <tr>
                                    <th class="c-idx">#</th>
                                    <th class="c-acc">Account</th>
                                    <th class="c-amt">Debit</th>
                                    <th class="c-amt">Credit</th>
                                    <th class="c-desc">Description</th>
                                    <th class="c-name">Name</th>
                                    <th class="c-act"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(entry, index) in entries" :key="entry.key" class="gvx-row">
                                    <td class="c-idx"><span class="gvx-idx">{{ index + 1 }}</span></td>
                                    <td class="c-acc">
                                        <AccountSelect v-model="entry.account_id" :account-name="entry.account_name"
                                            @update:account-name="entry.account_name = $event"
                                            @change="onAccountChange(index)" placeholder="Select account" />
                                    </td>
                                    <td class="c-amt">
                                        <div class="gvx-amt" :class="{ active: entry.debit > 0 }">
                                            <input type="number" v-model.number="entry.debit"
                                                @input="onDebitChange(index)" :data-entry-index="index"
                                                data-field="debit" step="0.01" min="0" placeholder="0.00" />
                                        </div>
                                    </td>
                                    <td class="c-amt">
                                        <div class="gvx-amt credit" :class="{ active: entry.credit > 0 }">
                                            <input type="number" v-model.number="entry.credit"
                                                @input="onCreditChange(index)" :data-entry-index="index"
                                                data-field="credit" step="0.01" min="0" placeholder="0.00" />
                                        </div>
                                    </td>
                                    <td class="c-desc">
                                        <input type="text" class="gvx-cell-input" v-model="entry.description"
                                            placeholder="Line note" />
                                    </td>
                                    <td class="c-name">
                                        <input type="text" class="gvx-cell-input" v-model="entry.person_name"
                                            placeholder="Person" />
                                    </td>
                                    <td class="c-act">
                                        <button type="button" @click="removeEntry(entry.key)" class="gvx-del"
                                            title="Remove line" :disabled="entries.length <= 2">
                                            <i class="fa fa-trash-o"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <button type="button" class="gvx-add" @click="addEntry">
                        <i class="fa fa-plus"></i> Add Line
                    </button>
                </div>

                <!-- Live balance summary -->
                <div class="gvx-balance">
                    <div class="gvx-bal-cell">
                        <div class="bc-ic debit"><i class="fa fa-arrow-down"></i></div>
                        <div>
                            <div class="bc-lab">Total Debit</div>
                            <div class="bc-val">{{ formatCurrency(totalDebits) }}</div>
                        </div>
                    </div>
                    <div class="gvx-bal-cell">
                        <div class="bc-ic credit"><i class="fa fa-arrow-up"></i></div>
                        <div>
                            <div class="bc-lab">Total Credit</div>
                            <div class="bc-val">{{ formatCurrency(totalCredits) }}</div>
                        </div>
                    </div>
                    <div class="gvx-bal-cell gvx-bal-diff" :class="isBalanced ? 'is-ok' : 'is-off'">
                        <div class="bc-ic"><i class="fa" :class="isBalanced ? 'fa-check' : 'fa-exclamation'"></i></div>
                        <div>
                            <div class="bc-lab">{{ isBalanced ? 'Balanced' : 'Difference' }}</div>
                            <div class="bc-val">
                                {{ isBalanced ? formatCurrency(totalDebits) : formatCurrency(Math.abs(difference)) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════  FOOTER  ═══════════════ -->
            <div class="gvx-footer">
                <button type="button" class="gvx-btn ghost" @click="closeModal">
                    <i class="fa fa-times"></i> Cancel
                </button>
                <button type="submit" class="gvx-btn primary" :disabled="loading">
                    <i class="fa" :class="loading ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                    <span v-if="loading">Saving…</span>
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

const difference = computed(() => totalDebits.value - totalCredits.value)

const hasAmounts = computed(() => totalDebits.value > 0 || totalCredits.value > 0)

const isBalanced = computed(() => hasAmounts.value && Math.abs(difference.value) < 0.01)

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

<style>
/* ════════════  General Voucher — "Premium" design system (scoped under .gvx)  ════════════
   Colour derives from the active settings theme (--bs-primary) and tracks dark mode,
   mirroring the RentOut Premium Hero system (.rvx). Font Awesome 4 icons only.        */

#GeneralVoucherModal .modal-dialog {
    max-width: 1080px;
}

#GeneralVoucherModalContent {
    border: none;
    border-radius: 13px;
    overflow: hidden;
    box-shadow: 0 28px 70px -24px rgba(16, 24, 40, .55);
}

[data-bs-theme="dark"] #GeneralVoucherModalContent {
    background: #272d34;
}

.gvx {
    --brand: var(--bs-primary, #2563eb);
    --brand-rgb: var(--bs-primary-rgb, 37, 99, 235);
    --brand-600: color-mix(in srgb, var(--brand), #000 12%);
    --brand-700: color-mix(in srgb, var(--brand), #000 28%);
    --brand-400: color-mix(in srgb, var(--brand), #fff 22%);
    --hero-1: color-mix(in srgb, var(--brand), #000 40%);
    --hero-2: color-mix(in srgb, var(--brand), #000 4%);
    --hero-3: color-mix(in srgb, var(--brand), #fff 8%);

    --surface: #ffffff;
    --surface-2: #f5f7fa;
    --surface-3: #eceff4;
    --border: #e4e8ee;
    --border-strong: #d3d9e1;
    --text: #1f2937;
    --text-2: #5b6573;
    --text-3: #8a94a3;

    --success: #059669;
    --success-bg: #ecfdf5;
    --success-rgb: 5, 150, 105;
    --danger: #dc2626;
    --danger-bg: #fef2f2;
    --danger-rgb: 220, 38, 38;
    --warning: #d97706;
    --warning-bg: #fffbeb;
    --warning-rgb: 217, 119, 6;

    --r-sm: 7px;
    --r-md: 10px;
    --r-lg: 12px;
    --shadow-sm: 0 1px 2px rgba(16, 24, 40, .05), 0 1px 3px rgba(16, 24, 40, .05);

    font-size: 12px;
    color: var(--text);
    line-height: 1.45;
    -webkit-font-smoothing: antialiased;
    letter-spacing: -.003em;
}

[data-bs-theme="dark"] .gvx {
    --hero-1: color-mix(in srgb, var(--brand), #000 60%);
    --hero-2: color-mix(in srgb, var(--brand), #000 44%);
    --hero-3: color-mix(in srgb, var(--brand), #000 26%);
    --surface: #272d34;
    --surface-2: #2e353d;
    --surface-3: #353d46;
    --border: #3a424c;
    --border-strong: #4a535e;
    --text: #e8ebef;
    --text-2: #aab2bd;
    --text-3: #7c8693;
    --success-bg: color-mix(in srgb, var(--success), #000 72%);
    --danger-bg: color-mix(in srgb, var(--danger), #000 72%);
    --warning-bg: color-mix(in srgb, var(--warning), #000 72%);
}

/* ═══════════  HERO  ═══════════ */
.gvx-hero {
    position: relative;
    overflow: hidden;
    isolation: isolate;
    padding: 14px 18px;
    color: #fff;
    background:
        radial-gradient(120% 160% at 12% -10%, rgba(255, 255, 255, .20), transparent 50%),
        radial-gradient(90% 140% at 100% 0%, var(--hero-3), transparent 55%),
        linear-gradient(118deg, var(--hero-1) 0%, var(--hero-2) 60%, var(--hero-3) 130%);
}

.gvx-hero::after {
    content: "";
    position: absolute;
    inset: 0;
    z-index: -1;
    opacity: .5;
    background-image: radial-gradient(circle at 1px 1px, rgba(255, 255, 255, .10) 1px, transparent 0);
    background-size: 22px 22px;
    -webkit-mask-image: linear-gradient(180deg, #000, transparent 80%);
    mask-image: linear-gradient(180deg, #000, transparent 80%);
}

.gvx-glow {
    position: absolute;
    z-index: -1;
    border-radius: 50%;
    filter: blur(34px);
}

.gvx-glow.a {
    width: 220px;
    height: 220px;
    top: -90px;
    right: 6%;
    background: rgba(255, 255, 255, .28);
    opacity: .5;
}

.gvx-glow.b {
    width: 170px;
    height: 170px;
    bottom: -80px;
    left: -20px;
    background: var(--brand-400);
    opacity: .4;
}

.gvx-hero-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.gvx-hero-id {
    display: flex;
    align-items: center;
    gap: 11px;
}

.gvx-hero-ic {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    color: #fff;
    background: rgba(255, 255, 255, .16);
    border: 1px solid rgba(255, 255, 255, .28);
    backdrop-filter: blur(6px);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, .25);
}

.gvx-eyebrow {
    font-size: 9px;
    font-weight: 700;
    letter-spacing: .14em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, .78);
}

.gvx-hero-title {
    font-size: 16px;
    font-weight: 800;
    letter-spacing: -.02em;
    margin: 1px 0 0;
    line-height: 1.1;
    color: #fff;
    text-shadow: 0 1px 14px rgba(0, 0, 0, .18);
}

.gvx-hero-tools {
    display: flex;
    align-items: center;
    gap: 9px;
}

.gvx-bpill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .02em;
    padding: 4px 9px;
    border-radius: 999px;
    line-height: 1;
    background: rgba(255, 255, 255, .95);
    box-shadow: 0 4px 12px -4px rgba(0, 0, 0, .3);
    white-space: nowrap;
}

.gvx-bpill .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
}

.gvx-bpill.is-ok {
    color: var(--success);
}

.gvx-bpill.is-ok .dot {
    background: var(--success);
    box-shadow: 0 0 0 3px rgba(var(--success-rgb), .2);
}

.gvx-bpill.is-off {
    color: var(--warning);
}

.gvx-bpill.is-off .dot {
    background: var(--warning);
    box-shadow: 0 0 0 3px rgba(var(--warning-rgb), .2);
}

.gvx-bpill.is-draft {
    color: var(--text-2);
}

.gvx-bpill.is-draft .dot {
    background: var(--text-3);
}

.gvx-x {
    width: 30px;
    height: 30px;
    flex: 0 0 auto;
    border-radius: 9px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    color: #fff;
    cursor: pointer;
    background: rgba(255, 255, 255, .14);
    border: 1px solid rgba(255, 255, 255, .26);
    backdrop-filter: blur(6px);
    transition: background .15s ease, transform .15s ease;
}

.gvx-x:hover {
    background: rgba(255, 255, 255, .28);
}

.gvx-x:active {
    transform: scale(.94);
}

/* ═══════════  BODY  ═══════════ */
.gvx-body {
    padding: 13px 16px;
    background: var(--surface-2);
    max-height: 64vh;
    overflow-y: auto;
}

.gvx-errors {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    background: var(--danger-bg);
    border: 1px solid rgba(var(--danger-rgb), .28);
    border-radius: var(--r-md);
    padding: 9px 11px;
    margin-bottom: 12px;
}

.gvx-errors-ic {
    color: var(--danger);
    font-size: 13px;
    margin-top: 1px;
}

.gvx-errors-title {
    font-weight: 700;
    color: var(--danger);
    font-size: 11.5px;
}

.gvx-errors-list {
    margin: 3px 0 0;
    padding-left: 16px;
    color: var(--danger);
    font-size: 11px;
}

.gvx-errors-list li {
    margin-top: 2px;
}

/* meta fields */
.gvx-meta {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-bottom: 12px;
}

.gvx-label {
    display: block;
    font-size: 9.5px;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: var(--text-3);
    margin-bottom: 4px;
}

.gvx-label .req {
    color: var(--danger);
}

.gvx-input {
    display: flex;
    align-items: stretch;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--r-sm);
    overflow: hidden;
    transition: border-color .15s ease, box-shadow .15s ease;
}

.gvx-input:focus-within {
    border-color: var(--brand-400);
    box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .16);
}

.gvx-input-ic {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    flex: 0 0 auto;
    color: var(--text-3);
    background: var(--surface-2);
    border-right: 1px solid var(--border);
    font-size: 12px;
}

.gvx-input input {
    flex: 1;
    min-width: 0;
    border: none;
    outline: none;
    background: transparent;
    padding: 6px 9px;
    font-size: 12px;
    color: var(--text);
}

.gvx-input input::placeholder {
    color: var(--text-3);
}

/* panel */
.gvx-panel {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--r-lg);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}

.gvx-panel-head {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 9px 12px;
    border-bottom: 1px solid var(--border);
    background: linear-gradient(110deg, rgba(var(--brand-rgb), .07), var(--surface) 60%);
}

.gvx-panel-ic {
    width: 24px;
    height: 24px;
    flex: 0 0 auto;
    border-radius: 7px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(var(--brand-rgb), .12);
    color: var(--brand-600);
    font-size: 11.5px;
}

.gvx-panel-title {
    font-size: 12px;
    font-weight: 700;
    color: var(--text);
    letter-spacing: -.01em;
}

.gvx-panel-sub {
    font-size: 10px;
    color: var(--text-3);
    margin-top: 1px;
}

/* lines table */
.gvx-table-wrap {
    overflow: visible;
}

.gvx-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.gvx-table thead th {
    font-size: 9.5px;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: var(--text-3);
    text-align: left;
    padding: 7px 9px;
    background: var(--surface-2);
    border-bottom: 1px solid var(--border);
    white-space: nowrap;
}

.gvx-table thead th.c-amt {
    text-align: right;
}

.gvx-table thead th.c-idx {
    text-align: center;
}

.gvx-table tbody td {
    padding: 4px 7px;
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
}

.gvx-table tbody tr:last-child td {
    border-bottom: none;
}

.gvx-row {
    transition: background .12s ease;
}

.gvx-row:hover {
    background: var(--surface-2);
}

.gvx-table .c-idx {
    text-align: center;
    width: 38px;
}

.gvx-table .c-acc {
    min-width: 160px;
}

.gvx-table .c-amt {
    width: 112px;
}

.gvx-table .c-desc {
    min-width: 130px;
}

.gvx-table .c-name {
    min-width: 104px;
}

.gvx-table .c-act {
    width: 40px;
    text-align: center;
}

.gvx-idx {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 21px;
    height: 21px;
    border-radius: 6px;
    background: var(--surface-3);
    color: var(--text-2);
    font-size: 10.5px;
    font-weight: 700;
}

.gvx-cell-input {
    width: 100%;
    border: 1px solid var(--border);
    border-radius: 6px;
    background: var(--surface);
    color: var(--text);
    padding: 5px 7px;
    font-size: 11.5px;
    outline: none;
    transition: border-color .15s ease, box-shadow .15s ease;
}

.gvx-cell-input::placeholder {
    color: var(--text-3);
}

.gvx-cell-input:focus {
    border-color: var(--brand-400);
    box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .14);
}

.gvx-amt input {
    width: 100%;
    text-align: right;
    border: 1px solid var(--border);
    border-radius: 6px;
    background: var(--surface);
    color: var(--text);
    padding: 5px 8px;
    font-size: 12px;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    outline: none;
    -moz-appearance: textfield;
    transition: border-color .15s ease, box-shadow .15s ease, background .15s ease, color .15s ease;
}

.gvx-amt input::-webkit-outer-spin-button,
.gvx-amt input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.gvx-amt input::placeholder {
    color: var(--text-3);
    font-weight: 400;
}

.gvx-amt input:focus {
    border-color: var(--brand-400);
    box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .14);
}

.gvx-amt.active input {
    border-color: rgba(var(--success-rgb), .5);
    background: var(--success-bg);
    color: var(--success);
}

.gvx-amt.credit.active input {
    border-color: rgba(var(--danger-rgb), .45);
    background: var(--danger-bg);
    color: var(--danger);
}

.gvx-del {
    width: 26px;
    height: 26px;
    border-radius: 7px;
    border: 1px solid transparent;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    color: var(--text-3);
    cursor: pointer;
    font-size: 12px;
    transition: background .15s ease, color .15s ease, border-color .15s ease;
}

.gvx-del:hover:not(:disabled) {
    background: var(--danger-bg);
    color: var(--danger);
    border-color: rgba(var(--danger-rgb), .25);
}

.gvx-del:disabled {
    opacity: .35;
    cursor: not-allowed;
}

.gvx-add {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    width: 100%;
    padding: 8px;
    border: none;
    border-top: 1px dashed var(--border);
    background: var(--surface-2);
    color: var(--brand-600);
    font-size: 11.5px;
    font-weight: 700;
    cursor: pointer;
    transition: background .15s ease, color .15s ease;
}

.gvx-add:hover {
    background: rgba(var(--brand-rgb), .08);
    color: var(--brand-700);
}

/* live balance summary */
.gvx-balance {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-top: 16px;
}

.gvx-bal-cell {
    display: flex;
    align-items: center;
    gap: 12px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--r-md);
    padding: 13px 15px;
    box-shadow: var(--shadow-sm);
}

.gvx-bal-cell .bc-ic {
    width: 38px;
    height: 38px;
    flex: 0 0 auto;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    background: var(--surface-3);
    color: var(--text-2);
}

.gvx-bal-cell .bc-ic.debit {
    background: var(--success-bg);
    color: var(--success);
}

.gvx-bal-cell .bc-ic.credit {
    background: var(--danger-bg);
    color: var(--danger);
}

.bc-lab {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: var(--text-3);
}

.bc-val {
    font-size: 18px;
    font-weight: 800;
    letter-spacing: -.02em;
    color: var(--text);
    font-variant-numeric: tabular-nums;
    margin-top: 1px;
}

.gvx-bal-diff {
    transition: border-color .2s ease, background .2s ease;
}

.gvx-bal-diff.is-ok {
    border-color: rgba(var(--success-rgb), .4);
    background: linear-gradient(120deg, var(--success-bg), var(--surface) 130%);
}

.gvx-bal-diff.is-ok .bc-ic {
    background: var(--success);
    color: #fff;
    box-shadow: 0 6px 14px -5px rgba(var(--success-rgb), .7);
}

.gvx-bal-diff.is-ok .bc-val {
    color: var(--success);
}

.gvx-bal-diff.is-off {
    border-color: rgba(var(--warning-rgb), .4);
    background: linear-gradient(120deg, var(--warning-bg), var(--surface) 130%);
}

.gvx-bal-diff.is-off .bc-ic {
    background: var(--warning);
    color: #fff;
    box-shadow: 0 6px 14px -5px rgba(var(--warning-rgb), .7);
}

.gvx-bal-diff.is-off .bc-val {
    color: var(--warning);
}

/* ═══════════  FOOTER  ═══════════ */
.gvx-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    padding: 15px 22px;
    background: var(--surface);
    border-top: 1px solid var(--border);
}

.gvx-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 700;
    padding: 10px 20px;
    border-radius: 10px;
    cursor: pointer;
    border: 1px solid transparent;
    transition: background .15s ease, border-color .15s ease, color .15s ease, box-shadow .15s ease, transform .1s ease;
}

.gvx-btn:active {
    transform: translateY(1px);
}

.gvx-btn.ghost {
    background: var(--surface);
    border-color: var(--border);
    color: var(--text-2);
}

.gvx-btn.ghost:hover {
    background: var(--surface-2);
    border-color: var(--border-strong);
    color: var(--text);
}

.gvx-btn.primary {
    color: #fff;
    border: none;
    background: linear-gradient(120deg, var(--brand), var(--brand-600));
    box-shadow: 0 8px 18px -7px rgba(var(--brand-rgb), .6);
}

.gvx-btn.primary:hover {
    background: linear-gradient(120deg, var(--brand-600), var(--brand-700));
    box-shadow: 0 10px 22px -7px rgba(var(--brand-rgb), .7);
}

.gvx-btn.primary:disabled {
    opacity: .65;
    cursor: not-allowed;
}

/* AccountSelect input — premium reskin within .gvx */
.gvx .account-select-input {
    padding: 7px 10px !important;
    font-size: 12.5px !important;
    border: 1px solid var(--border) !important;
    border-radius: 7px !important;
    background: var(--surface) !important;
    color: var(--text) !important;
}

.gvx .account-select-input::placeholder {
    color: var(--text-3);
}

.gvx .account-select-input:focus {
    border-color: var(--brand-400) !important;
    box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .14) !important;
}

/* ═══════════  RESPONSIVE  ═══════════ */
@media (max-width: 860px) {
    .gvx-meta {
        grid-template-columns: 1fr;
    }

    .gvx-balance {
        grid-template-columns: 1fr;
    }

    .gvx-table-wrap {
        overflow-x: auto;
    }

    .gvx-table {
        min-width: 720px;
    }
}
</style>
