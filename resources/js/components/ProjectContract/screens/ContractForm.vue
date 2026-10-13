<template>
    <div>
        <div class="pa-pagehead">
            <div class="me-auto">
                <div class="pa-crumbs"><a :href="urlFor(context.urls, 'list')">Project Contracts</a> <i class="fa fa-angle-right mx-1"></i> {{ isEdit ? form.reference_no || 'Edit' : 'New' }}</div>
                <h1 class="pa-title">{{ isEdit ? 'Edit project contract' : 'New project contract' }}</h1>
            </div>
        </div>

        <div v-if="loading" class="d-grid gap-2">
            <div class="pa-skeleton" style="height: 120px"></div>
            <div class="pa-skeleton" style="height: 220px"></div>
        </div>

        <form v-else class="paf d-grid gap-2" novalidate @submit.prevent="save(false)">
            <div v-if="locked" class="pa-callout info"><i class="fa fa-lock mt-1"></i><span>This contract is approved. The vendor, period, amount, terms and frequency change only through an extension; you can still edit the project name and remarks.</span></div>

            <section class="paf-card">
                <div class="paf-head"><i class="fa fa-building-o text-secondary"></i><h5>Project &amp; Vendor Details</h5></div>
                <div class="paf-body">
                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="f-project">Project Name *</label>
                            <div class="input-group has-validation">
                                <span class="input-group-text"><i class="fa fa-briefcase text-primary"></i></span>
                                <input id="f-project" v-model="form.project_name" class="form-control" :class="{ 'is-invalid': errors.project_name }" placeholder="Enter project name" />
                                <div v-if="errors.project_name" class="invalid-feedback">{{ errors.project_name }}</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="f-vendor">Vendor *</label>
                            <div class="input-group has-validation flex-nowrap">
                                <span class="input-group-text"><i class="fa fa-truck text-danger"></i></span>
                                <VendorPicker v-model="form.account_id" :label="vendorName" :invalid="!!errors.account_id" @picked="vendorName = $event.name" />
                                <button v-if="!locked && context.permissions.vendor_create" class="btn btn-outline-primary" type="button" @click="vendorModal = true"><i class="fa fa-plus me-1"></i>New</button>
                            </div>
                            <div v-if="errors.account_id" class="invalid-feedback d-block">{{ errors.account_id }}</div>
                            <div v-else class="form-text">{{ vendorHint }}</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="f-remark">Remarks</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa fa-comment-o text-secondary"></i></span>
                                <textarea id="f-remark" v-model="form.remark" rows="1" class="form-control" placeholder="Scope summary, retention clause, anything worth recording" @input="grow"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="paf-card">
                <div class="paf-head"><i class="fa fa-tag text-primary"></i><h5>Contract Details</h5></div>
                <div class="paf-body">
                    <div class="paf-sub">
                        <i class="fa fa-calendar text-success"></i> Contract Period
                        <span class="paf-dur ms-auto" :class="{ bad: !periodValid }"><i class="fa fa-clock-o"></i>{{ periodValid ? `${months} ${months === 1 ? 'month' : 'months'} · ${days} days` : 'Check the dates' }}</span>
                    </div>
                    <div class="row g-2 mb-3 align-items-start">
                        <div class="col-12 col-md-6 col-xl-4">
                            <label class="form-label" for="f-start">Start Date *</label>
                            <div class="input-group has-validation">
                                <span class="input-group-text"><i class="fa fa-calendar-o text-secondary"></i></span>
                                <input id="f-start" v-model="form.start_date" type="date" class="form-control" :class="{ 'is-invalid': errors.start_date }" :disabled="locked" />
                                <div v-if="errors.start_date" class="invalid-feedback">{{ errors.start_date }}</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-xl-4">
                            <label class="form-label" for="f-end">End Date *</label>
                            <div class="input-group has-validation">
                                <span class="input-group-text"><i class="fa fa-calendar text-danger"></i></span>
                                <input id="f-end" v-model="form.end_date" type="date" class="form-control" :class="{ 'is-invalid': errors.end_date || (form.start_date && form.end_date && !periodValid) }" :disabled="locked" />
                                <div class="invalid-feedback">{{ errors.end_date || 'End date must be after the start date.' }}</div>
                            </div>
                        </div>
                        <div v-if="!locked" class="col-12 col-xl-4">
                            <div class="form-label">Quick set</div>
                            <div class="paf-presets">
                                <button v-for="preset in presets" :key="preset.months" type="button" :class="{ on: presetActive(preset.months) }" @click="applyPreset(preset.months)">{{ preset.label }}</button>
                            </div>
                        </div>
                    </div>

                    <div class="paf-sub"><i class="fa fa-money text-success"></i> Payment Information</div>
                    <div class="row g-2">
                        <div class="col-12 col-md-6 col-xl-3">
                            <label class="form-label" for="f-amount">Contract Amount *</label>
                            <div class="input-group has-validation paf-amount">
                                <span class="input-group-text">QAR</span>
                                <input
                                    id="f-amount"
                                    :value="amountFocused ? amountText : money(parseAmount(amountText))"
                                    class="form-control text-end pa-num"
                                    :class="{ 'is-invalid': errors.contract_amount }"
                                    inputmode="decimal"
                                    :disabled="locked"
                                    @focus="amountFocused = true"
                                    @blur="amountFocused = false"
                                    @input="amountText = $event.target.value"
                                />
                                <div v-if="errors.contract_amount" class="invalid-feedback">{{ errors.contract_amount }}</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-xl-2">
                            <label class="form-label" for="f-terms">No. Of Terms *</label>
                            <div class="input-group paf-stepper">
                                <button type="button" class="btn" aria-label="Fewer terms" :disabled="locked" @click="step(-1)"><i class="fa fa-minus"></i></button>
                                <input id="f-terms" v-model.number="form.no_of_terms" type="number" min="1" class="form-control pa-num" :disabled="locked" @input="termsTouched = true" />
                                <button type="button" class="btn" aria-label="More terms" :disabled="locked" @click="step(1)"><i class="fa fa-plus"></i></button>
                            </div>
                            <div class="form-text">
                                <template v-if="!locked && suggestion && suggestion !== form.no_of_terms">Suggested {{ suggestion }} · <button type="button" class="paf-link" @click="useSuggestion">Use</button></template>
                                <template v-else>Planned only.</template>
                            </div>
                        </div>
                        <div class="col-12 col-xl-7">
                            <div class="form-label">Payment Frequency *</div>
                            <div class="paf-freq" role="radiogroup" aria-label="Payment frequency">
                                <template v-for="frequency in frequencies" :key="frequency.value">
                                    <input :id="`fq-${frequency.value}`" v-model="form.payment_frequency" type="radio" class="btn-check" name="frequency" :value="frequency.value" :disabled="locked" @change="termsTouched = false" />
                                    <label :for="`fq-${frequency.value}`"><i class="fa" :class="frequencyIcons[frequency.value]"></i>{{ frequency.label }}</label>
                                </template>
                            </div>
                            <div v-if="errors.payment_frequency" class="invalid-feedback d-block">{{ errors.payment_frequency }}</div>
                        </div>
                    </div>
                </div>
                <div class="paf-summary">
                    <i class="fa fa-info-circle"></i>
                    <span>{{ summary }}</span>
                    <span v-if="!locked" class="pa-pill info no-dot ms-auto">{{ route.length ? `Needs ${route.length} ${route.length === 1 ? 'approval' : 'approvals'}` : 'No approval needed' }}</span>
                </div>
            </section>

            <section v-if="!locked" class="paf-card">
                <div class="paf-head"><i class="fa fa-gavel text-primary"></i><h5>Approval Route</h5></div>
                <div class="paf-body">
                    <div class="paf-route">
                        <template v-for="level in allLevels" :key="level.level">
                            <div class="paf-route-step" :class="{ skipped: !inRoute(level) }">
                                <span class="n">{{ level.level }}</span>
                                <div><div class="fw-semibold">{{ level.role }}</div><div class="pa-sub">{{ level.min_amount > 0 ? `Amount above QAR ${whole(level.min_amount)}` : 'All contracts' }}</div></div>
                            </div>
                            <i class="fa fa-long-arrow-right text-muted-pa d-none d-md-inline"></i>
                        </template>
                        <div class="paf-route-step done"><span class="n"><i class="fa fa-check"></i></span><div><div class="fw-semibold">Active</div><div class="pa-sub">Terms &amp; payments unlock</div></div></div>
                    </div>
                </div>
            </section>

            <div class="paf-card paf-actions">
                <a class="btn btn-quiet" :href="isEdit ? urlFor(context.urls, 'view', recordId) : urlFor(context.urls, 'list')"><i class="fa fa-arrow-left me-1"></i> Back</a>
                <div class="d-flex flex-wrap gap-2 ms-auto">
                    <button type="submit" class="btn" :class="locked ? 'btn-primary' : 'btn-outline-primary'" :disabled="saving">
                        <i class="fa" :class="saving === 'draft' ? 'fa-spinner fa-spin' : 'fa-save'"></i> {{ locked ? 'Save changes' : 'Save as Draft' }}
                    </button>
                    <button v-if="!locked" type="button" class="btn btn-primary" :disabled="saving" @click="save(true)">
                        <i class="fa" :class="saving === 'submit' ? 'fa-spinner fa-spin' : 'fa-paper-plane'"></i> Submit for Approval
                    </button>
                </div>
            </div>
        </form>

        <VendorModal :open="vendorModal" save-label="Save and select" subtitle="Saved to Vendors and selected on this contract" @close="vendorModal = false" @saved="onVendorCreated" />
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useToast } from 'vue-toastification'
import { contractApi, errorMessage, fieldErrors } from '../api.js'
import { loadOptions, urlFor, useContext, useOptions } from '../common/useContractContext.js'
import { contractMonths, daysBetween, date, money, parseAmount, periodEnd, today, toDate, whole } from '../format.js'
import VendorPicker from '../vendor/VendorPicker.vue'
import VendorModal from '../vendor/VendorModal.vue'

const props = defineProps({ recordId: { type: Number, default: null } })
const context = useContext()
const options = useOptions()
const toast = useToast()

const isEdit = computed(() => !!props.recordId)
const loading = ref(true)
const saving = ref(false)
const errors = ref({})
const vendorModal = ref(false)
const vendorName = ref('')
const vendorHint = ref('Vendor accounts only.')
const status = ref('draft')
const amountText = ref('')
const amountFocused = ref(false)
const termsTouched = ref(false)

const form = reactive({
    reference_no: '',
    project_name: '',
    account_id: null,
    start_date: today(),
    end_date: periodEnd(today(), 12),
    no_of_terms: 1,
    payment_frequency: 'milestone',
    remark: '',
})

const presets = [
    { months: 3, label: '3 months' },
    { months: 6, label: '6 months' },
    { months: 12, label: '1 year' },
    { months: 24, label: '2 years' },
]
const frequencyIcons = { monthly: 'fa-calendar-o', quarterly: 'fa-th-large', half_yearly: 'fa-adjust', yearly: 'fa-circle-o', milestone: 'fa-flag-o', one_time: 'fa-check' }
const frequencyMonths = { monthly: 1, quarterly: 3, half_yearly: 6, yearly: 12 }

const locked = computed(() => isEdit.value && !['draft', 'rejected'].includes(status.value))
const frequencies = computed(() => options.frequencies)
const periodValid = computed(() => !!(toDate(form.start_date) && toDate(form.end_date) && toDate(form.end_date) > toDate(form.start_date)))
const months = computed(() => contractMonths(form.start_date, form.end_date))
const days = computed(() => daysBetween(form.start_date, form.end_date))
const amount = computed(() => parseAmount(amountText.value))

const suggestion = computed(() => {
    if (!periodValid.value) return null
    if (form.payment_frequency === 'one_time') return 1
    const every = frequencyMonths[form.payment_frequency]
    return every ? Math.max(1, Math.ceil(months.value / every)) : null
})

const allLevels = computed(() => options.approval_levels ?? [])
const inRoute = (level) => level.min_amount <= 0 || amount.value > level.min_amount
const route = computed(() => allLevels.value.filter(inRoute))

const summary = computed(() => {
    if (!periodValid.value) return 'Set a valid contract period to see the summary.'
    const label = (frequencies.value.find((item) => item.value === form.payment_frequency)?.label ?? '').toLowerCase()
    const how = form.payment_frequency === 'one_time' ? 'paid in one payment' : `paid in ${form.no_of_terms} ${label} ${form.no_of_terms === 1 ? 'term' : 'terms'}`
    return `QAR ${money(amount.value)} from ${date(form.start_date)} to ${date(form.end_date)}, ${how}.`
})

/** An error clears as soon as its field is edited, so a stale message never sits under a valid value. */
const errorFields = { project_name: () => form.project_name, account_id: () => form.account_id, start_date: () => form.start_date, end_date: () => form.end_date, contract_amount: () => amountText.value, payment_frequency: () => form.payment_frequency, no_of_terms: () => form.no_of_terms }
Object.entries(errorFields).forEach(([key, read]) => watch(read, () => {
    if (errors.value[key]) {
        const { [key]: _, ...rest } = errors.value
        errors.value = rest
    }
}))

watch(suggestion, (value) => {
    if (value && !termsTouched.value && !locked.value && !loading.value) form.no_of_terms = value
})

function presetActive(monthsCount) {
    return periodValid.value && form.end_date === periodEnd(form.start_date, monthsCount)
}

function applyPreset(monthsCount) {
    if (!form.start_date) return
    form.end_date = periodEnd(form.start_date, monthsCount)
    if (!termsTouched.value && suggestion.value) form.no_of_terms = suggestion.value
}

function step(delta) {
    form.no_of_terms = Math.max(1, (Number(form.no_of_terms) || 1) + delta)
    termsTouched.value = true
}

function useSuggestion() {
    form.no_of_terms = suggestion.value
    termsTouched.value = false
}

function grow(event) {
    event.target.style.height = 'auto'
    event.target.style.height = `${event.target.scrollHeight + 2}px`
}

function onVendorCreated(vendor) {
    form.account_id = vendor.id
    vendorName.value = vendor.name
    vendorHint.value = 'Vendor created and selected.'
    vendorModal.value = false
}

async function save(submit) {
    saving.value = submit ? 'submit' : 'draft'
    errors.value = {}
    if (!termsTouched.value && suggestion.value && !locked.value && !isEdit.value) form.no_of_terms = suggestion.value
    const payload = {
        project_name: form.project_name,
        account_id: form.account_id,
        start_date: form.start_date,
        end_date: form.end_date,
        no_of_terms: form.no_of_terms,
        payment_frequency: form.payment_frequency,
        contract_amount: amount.value,
        remark: form.remark || null,
        submit,
    }
    try {
        const response = isEdit.value ? await contractApi.update(props.recordId, payload) : await contractApi.store(payload)
        toast.success(response.message)
        window.location.href = urlFor(context.urls, 'view', isEdit.value ? props.recordId : response.data.id)
    } catch (error) {
        errors.value = fieldErrors(error)
        toast.error(errorMessage(error))
        saving.value = false
    }
}

onMounted(async () => {
    await loadOptions()
    if (isEdit.value) {
        try {
            const { data } = await contractApi.show(props.recordId)
            Object.assign(form, {
                reference_no: data.reference_no,
                project_name: data.project_name,
                account_id: data.vendor?.id ?? null,
                start_date: data.start_date,
                end_date: data.end_date,
                no_of_terms: data.no_of_terms,
                payment_frequency: data.payment_frequency,
                remark: data.remark ?? '',
            })
            vendorName.value = data.vendor?.name ?? ''
            amountText.value = String(data.contract_amount)
            status.value = data.status
            termsTouched.value = true
        } catch (error) {
            toast.error(errorMessage(error))
        }
    } else {
        form.no_of_terms = suggestion.value ?? 1
    }
    loading.value = false
})
</script>
