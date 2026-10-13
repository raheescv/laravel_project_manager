<template>
    <PcModal :open="open" :title="renew ? 'Record renewal' : 'Add security'" :subtitle="`${contract.project_name} · ${money(contract.totals.security_held)} held`" icon="fa-shield" size="lg" @close="emit('close')">
        <form id="pc-security-form" class="d-grid gap-3" @submit.prevent="save">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="pa-seg">
                    <button v-for="direction in options.security_directions" :key="direction.value" type="button" :class="{ on: form.direction === direction.value }" @click="form.direction = direction.value">{{ direction.label }}</button>
                </div>
                <span class="pa-sub">{{ form.direction === 'received' ? 'The vendor lodges this with you. You return it at the end.' : 'You lodge this with the vendor. They refund it at the end.' }}</span>
            </div>
            <div>
                <div class="form-label">Form of security</div>
                <div class="pa-choice">
                    <template v-for="form_ in options.security_forms" :key="form_.value">
                        <input :id="`sf-${form_.value}`" v-model="form.form" type="radio" name="security-form" :value="form_.value" />
                        <label :for="`sf-${form_.value}`"><i class="fa" :class="formIcon[form_.value]"></i>{{ form_.label }}</label>
                    </template>
                </div>
            </div>
            <div class="row g-2">
                <div class="col-12 col-md-6">
                    <label class="form-label" for="s-purpose">Purpose</label>
                    <select id="s-purpose" v-model="form.purpose" class="form-select">
                        <option v-for="purpose in options.security_purposes" :key="purpose.value" :value="purpose.value">{{ purpose.label }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="s-amount">Amount *</label>
                    <div class="input-group"><span class="input-group-text" style="width: auto; padding: 0 .6rem">QAR</span><input id="s-amount" v-model="form.amount" class="form-control text-end pa-num" inputmode="decimal" required /><span class="input-group-text pa-num" style="width: auto; padding: 0 .6rem">{{ pct }}%</span></div>
                </div>
                <template v-if="form.form !== 'cash'">
                    <div class="col-12 col-md-6"><label class="form-label" for="s-bank">Bank</label><input id="s-bank" v-model="form.bank_name" class="form-control" /></div>
                    <div class="col-12 col-md-6"><label class="form-label" for="s-ref">{{ form.form === 'cheque' ? 'Cheque no.' : 'Guarantee no.' }} *</label><input id="s-ref" v-model="form.reference_no" class="form-control" required /></div>
                </template>
                <template v-if="form.form === 'bank_guarantee'">
                    <div class="col-6"><label class="form-label" for="s-from">Valid from</label><input id="s-from" v-model="form.valid_from" type="date" class="form-control" /></div>
                    <div class="col-6">
                        <label class="form-label" for="s-to">Valid until *</label>
                        <input id="s-to" v-model="form.valid_until" type="date" class="form-control" required />
                        <div class="form-text" :class="{ 'text-danger-emphasis': form.valid_until && form.valid_until < contract.end_date }">{{ form.valid_until && form.valid_until < contract.end_date ? 'Ends before the contract does' : `Contract ends ${date(contract.end_date)}` }}</div>
                    </div>
                </template>
                <div v-if="form.form === 'cheque'" class="col-12 col-md-6"><label class="form-label" for="s-chqdate">Cheque date</label><input id="s-chqdate" v-model="form.cheque_date" type="date" class="form-control" /></div>
                <template v-if="form.form === 'cash'">
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="s-method">Payment method *</label>
                        <select id="s-method" v-model="form.payment_method_id" class="form-select" required>
                            <option v-for="method in options.payment_methods" :key="method.id" :value="method.id">{{ method.name }}</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6"><label class="form-label" for="s-date">Date</label><input id="s-date" v-model="form.received_date" type="date" class="form-control" /></div>
                </template>
                <div class="col-12"><label class="form-label" for="s-file">Scan of the document</label><input id="s-file" type="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" @change="file = $event.target.files[0] ?? null" /></div>
            </div>
            <div class="pa-callout info"><i class="fa fa-book mt-1"></i><span>{{ bookingText }}</span></div>
        </form>
        <template #footer>
            <button class="btn btn-quiet" type="button" @click="emit('close')">Cancel</button>
            <button class="btn btn-primary" type="submit" form="pc-security-form" :disabled="saving"><i class="fa" :class="saving ? 'fa-spinner fa-spin' : 'fa-check'"></i> Save security</button>
        </template>
    </PcModal>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { contractApi, formData } from '../api.js'
import PcModal from '../common/PcModal.vue'
import { useOptions } from '../common/useContractContext.js'
import { date, money, parseAmount, today } from '../format.js'

const props = defineProps({ open: Boolean, contract: { type: Object, required: true }, renew: { type: Object, default: null } })
const emit = defineEmits(['close', 'run'])
const options = useOptions()

const formIcon = { bank_guarantee: 'fa-shield', cash: 'fa-money', cheque: 'fa-credit-card' }
const form = reactive({})
const file = ref(null)
const saving = ref(false)

watch(() => props.open, (isOpen) => {
    if (!isOpen) return
    saving.value = false
    file.value = null
    const base = props.renew
    Object.assign(form, {
        direction: base?.direction ?? 'received',
        form: base?.form ?? 'bank_guarantee',
        purpose: base?.purpose ?? 'performance_bond',
        amount: base ? base.held : '',
        bank_name: base?.bank_name ?? '',
        reference_no: '',
        valid_from: base?.valid_until ?? today(),
        valid_until: '',
        cheque_date: '',
        payment_method_id: options.payment_methods[0]?.id ?? null,
        received_date: today(),
        renewed_from_id: base?.id ?? null,
    })
})

const amount = computed(() => parseAmount(form.amount))
const pct = computed(() => (props.contract.contract_amount ? ((amount.value / props.contract.contract_amount) * 100).toFixed(1).replace(/\.0$/, '') : 0))
const methodName = computed(() => options.payment_methods.find((method) => method.id === form.payment_method_id)?.name ?? 'payment method')
const bookingText = computed(() => {
    if (form.form !== 'cash') return `Tracked only. No journal is posted unless the ${form.form === 'cheque' ? 'cheque is deposited' : 'guarantee is encashed'}.`
    return form.direction === 'received'
        ? `Posts Dr ${methodName.value} / Cr Vendor Security Deposit (liability).`
        : `Posts Dr Security Deposit Paid (asset) / Cr ${methodName.value}.`
})

function save() {
    saving.value = true
    const cash = form.form === 'cash'
    const body = formData({
        direction: form.direction,
        form: form.form,
        purpose: form.purpose,
        amount: amount.value,
        bank_name: cash ? null : form.bank_name,
        reference_no: cash ? null : form.reference_no,
        valid_from: form.form === 'bank_guarantee' ? form.valid_from : null,
        valid_until: form.form === 'bank_guarantee' ? form.valid_until : null,
        cheque_date: form.form === 'cheque' ? form.cheque_date : null,
        payment_method_id: cash ? form.payment_method_id : null,
        received_date: form.received_date,
        renewed_from_id: form.renewed_from_id,
    }, { document: file.value })
    emit('run', contractApi.addSecurity(props.contract.id, body), () => { saving.value = false })
}
</script>
