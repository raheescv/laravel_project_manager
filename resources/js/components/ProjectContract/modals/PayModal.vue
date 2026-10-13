<template>
    <PcModal :open="open" title="Record payment" :subtitle="`To ${contract.vendor?.name ?? 'vendor'}`" icon="fa-money" tone="success" @close="emit('close')">
        <form id="pc-pay-form" class="d-grid gap-3" @submit.prevent="save">
            <div>
                <div class="form-label">Terms</div>
                <div class="d-grid gap-1">
                    <label v-for="term in payable" :key="term.id" class="d-flex align-items-center gap-2 p-2 rounded-3" style="background: var(--pa-head); border: 1px solid var(--pa-line); cursor: pointer">
                        <input v-model="selected" class="form-check-input mt-0" type="checkbox" :value="term.id" />
                        <span class="flex-grow-1" style="min-width: 0">
                            <span class="fw-semibold d-block text-truncate">{{ term.label }}</span>
                            <span class="pa-sub" :class="{ 'text-danger-emphasis': term.overdue }">Due {{ date(term.due_date) }}{{ term.overdue ? ' · overdue' : '' }}</span>
                        </span>
                        <span class="text-end"><span class="pa-sub d-block">Balance</span><span class="fw-semibold pa-num">{{ money(term.balance) }}</span></span>
                    </label>
                </div>
                <div v-if="!payable.length" class="pa-sub">Nothing is due on this contract.</div>
            </div>
            <div class="row g-2">
                <div class="col-6">
                    <label class="form-label" for="p-date">Payment date</label>
                    <input id="p-date" v-model="form.date" type="date" class="form-control" :max="today()" required />
                </div>
                <div class="col-6">
                    <label class="form-label" for="p-amount">Amount</label>
                    <input id="p-amount" v-model="form.amount" class="form-control text-end pa-num" inputmode="decimal" required />
                    <div class="form-text">Up to {{ money(selectedBalance) }} · part payment allowed</div>
                </div>
            </div>
            <div>
                <div class="form-label">Payment method</div>
                <div class="pa-choice">
                    <template v-for="method in options.payment_methods" :key="method.id">
                        <input :id="`pm-${method.id}`" v-model="form.payment_method_id" type="radio" name="payment-method" :value="method.id" />
                        <label :for="`pm-${method.id}`"><i class="fa" :class="method.is_cheque ? 'fa-credit-card' : /bank/i.test(method.name) ? 'fa-bank' : 'fa-money'"></i>{{ method.name }}</label>
                    </template>
                </div>
                <div v-if="!options.payment_methods.length" class="pa-callout warn mt-1"><i class="fa fa-exclamation-triangle mt-1"></i><span>No payment methods are configured. Add them in Settings first.</span></div>
            </div>
            <div v-if="isCheque" class="row g-2">
                <div class="col-4"><label class="form-label" for="p-cheque">Cheque no.</label><input id="p-cheque" v-model="form.cheque_no" class="form-control" /></div>
                <div class="col-4"><label class="form-label" for="p-cheque-date">Cheque date</label><input id="p-cheque-date" v-model="form.cheque_date" type="date" class="form-control" /></div>
                <div class="col-4"><label class="form-label" for="p-bank">Bank</label><input id="p-bank" v-model="form.bank_name" class="form-control" /></div>
            </div>
            <div><label class="form-label" for="p-reference">Reference</label><input id="p-reference" v-model="form.reference" class="form-control" placeholder="Transfer ref, voucher note" /></div>
            <div class="pa-callout info"><i class="fa fa-book mt-1"></i><span>Posts <b>Dr</b> {{ contract.vendor?.name ?? 'Vendor' }} / <b>Cr</b> {{ methodName }} · QAR {{ money(amount) }}</span></div>
        </form>
        <template #footer>
            <button class="btn btn-quiet" type="button" @click="emit('close')">Cancel</button>
            <button class="btn btn-success" type="submit" form="pc-pay-form" :disabled="saving || !selected.length || amount <= 0 || amount > selectedBalance + 0.001 || !form.payment_method_id">
                <i class="fa" :class="saving ? 'fa-spinner fa-spin' : 'fa-check'"></i> Record payment
            </button>
        </template>
    </PcModal>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { contractApi } from '../api.js'
import PcModal from '../common/PcModal.vue'
import { useOptions } from '../common/useContractContext.js'
import { date, money, parseAmount, today } from '../format.js'

const props = defineProps({ open: Boolean, contract: { type: Object, required: true }, termIds: { type: Array, default: () => [] } })
const emit = defineEmits(['close', 'run'])
const options = useOptions()

const selected = ref([])
const saving = ref(false)
const form = reactive({ date: today(), amount: '', payment_method_id: null, cheque_no: '', cheque_date: '', bank_name: '', reference: '' })

const payable = computed(() => props.contract.terms.filter((term) => term.balance > 0))
const selectedBalance = computed(() => payable.value.filter((term) => selected.value.includes(term.id)).reduce((sum, term) => sum + term.balance, 0))
const amount = computed(() => parseAmount(form.amount))
const method = computed(() => options.payment_methods.find((item) => item.id === form.payment_method_id))
const methodName = computed(() => method.value?.name ?? 'payment method')
const isCheque = computed(() => !!method.value?.is_cheque)

watch(() => props.open, (isOpen) => {
    if (!isOpen) return
    saving.value = false
    selected.value = props.termIds.length ? [...props.termIds] : payable.value.slice(0, 1).map((term) => term.id)
    Object.assign(form, { date: today(), payment_method_id: options.payment_methods[0]?.id ?? null, cheque_no: '', cheque_date: '', bank_name: '', reference: '' })
    form.amount = Math.round(selectedBalance.value * 100) / 100
})

watch(selected, () => {
    if (props.open) form.amount = Math.round(selectedBalance.value * 100) / 100
})

function save() {
    saving.value = true
    emit('run', contractApi.pay(props.contract.id, {
        term_ids: selected.value,
        date: form.date,
        amount: amount.value,
        payment_method_id: form.payment_method_id,
        cheque_no: form.cheque_no || null,
        cheque_date: form.cheque_date || null,
        bank_name: form.bank_name || null,
        reference: form.reference || null,
    }), () => { saving.value = false })
}
</script>
