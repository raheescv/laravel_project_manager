<template>
    <PcModal :open="open && !!security" title="Close security" :subtitle="security ? `${security.purpose_label} · QAR ${money(security.settleable)} free to settle` : ''" icon="fa-shield" @close="emit('close')">
        <form v-if="security" id="pc-settle-form" class="d-grid gap-3" @submit.prevent="save">
            <div class="pa-choice">
                <input id="st-return" v-model="form.type" type="radio" name="settle-type" value="return" />
                <label for="st-return"><i class="fa fa-undo"></i>{{ security.direction === 'received' ? 'Return to vendor' : 'Refunded by vendor' }}</label>
                <input id="st-forfeit" v-model="form.type" type="radio" name="settle-type" value="forfeit" class="danger" />
                <label for="st-forfeit"><i class="fa fa-ban"></i>{{ security.form === 'cash' ? 'Forfeit' : 'Encash' }}</label>
            </div>
            <div class="row g-2">
                <div class="col-6"><label class="form-label" for="st-date">Date</label><input id="st-date" v-model="form.date" type="date" class="form-control" :max="today()" required /></div>
                <div class="col-6">
                    <label class="form-label" for="st-amount">Amount</label>
                    <input id="st-amount" v-model="form.amount" class="form-control text-end pa-num" inputmode="decimal" required />
                    <div class="form-text">Part amounts allowed</div>
                </div>
                <div v-if="needsMethod" class="col-12">
                    <label class="form-label" for="st-method">{{ methodLabel }}</label>
                    <select id="st-method" v-model="form.payment_method_id" class="form-select" required>
                        <option v-for="method in options.payment_methods" :key="method.id" :value="method.id">{{ method.name }}</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="st-reason">Reason<span v-if="form.type === 'forfeit'"> *</span></label>
                    <textarea id="st-reason" v-model="form.reason" class="form-control" rows="2" :placeholder="form.type === 'forfeit' ? 'Breach, delay penalty or defects not fixed' : 'Work handed over, defects period ended'" :required="form.type === 'forfeit'"></textarea>
                </div>
            </div>
            <div class="pa-callout info"><i class="fa fa-book mt-1"></i><span>{{ bookingText }}</span></div>
            <div class="pa-sub"><i class="fa fa-gavel me-1"></i>Goes through the approval levels before anything posts.</div>
        </form>
        <template #footer>
            <button class="btn btn-quiet" type="button" @click="emit('close')">Cancel</button>
            <button class="btn" :class="form.type === 'forfeit' ? 'btn-danger' : 'btn-primary'" type="submit" form="pc-settle-form" :disabled="saving">
                <i class="fa" :class="saving ? 'fa-spinner fa-spin' : 'fa-paper-plane'"></i> Submit for approval
            </button>
        </template>
    </PcModal>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { contractApi } from '../api.js'
import PcModal from '../common/PcModal.vue'
import { useOptions } from '../common/useContractContext.js'
import { money, parseAmount, today } from '../format.js'

const props = defineProps({ open: Boolean, contract: { type: Object, required: true }, security: { type: Object, default: null }, type: { type: String, default: 'return' } })
const emit = defineEmits(['close', 'run'])
const options = useOptions()

const form = reactive({ type: 'return', date: today(), amount: '', payment_method_id: null, reason: '' })
const saving = ref(false)

watch(() => props.open, (isOpen) => {
    if (!isOpen || !props.security) return
    saving.value = false
    Object.assign(form, { type: props.type ?? 'return', date: today(), amount: props.security.settleable, payment_method_id: options.payment_methods[0]?.id ?? null, reason: '' })
})

const cash = computed(() => props.security?.form === 'cash')
const needsMethod = computed(() => (cash.value ? form.type === 'return' : form.type === 'forfeit'))
const methodLabel = computed(() => (cash.value ? (props.security.direction === 'received' ? 'Paid back from' : 'Refund received into') : props.security.direction === 'received' ? 'Encashed into' : 'Encashed from'))
const methodName = computed(() => options.payment_methods.find((method) => method.id === form.payment_method_id)?.name ?? 'payment method')
const amountText = computed(() => `QAR ${money(parseAmount(form.amount))}`)
const bookingText = computed(() => {
    const received = props.security?.direction === 'received'
    if (cash.value) {
        if (received) return form.type === 'return' ? `Posts Dr Vendor Security Deposit / Cr ${methodName.value} · ${amountText.value}` : `Posts Dr Vendor Security Deposit / Cr Forfeited Deposits (income) · ${amountText.value}`
        return form.type === 'return' ? `Posts Dr ${methodName.value} / Cr Security Deposit Paid · ${amountText.value}` : `Posts Dr Security Deposit Loss / Cr Security Deposit Paid · ${amountText.value}`
    }
    if (form.type === 'return') return 'Nothing to post: the document goes back.'
    return received ? `Posts Dr ${methodName.value} / Cr Forfeited Deposits (income) · ${amountText.value}` : `Posts Dr Security Deposit Loss / Cr ${methodName.value} · ${amountText.value}`
})

function save() {
    saving.value = true
    emit('run', contractApi.settleSecurity(props.contract.id, props.security.id, {
        type: form.type,
        amount: parseAmount(form.amount),
        date: form.date,
        payment_method_id: needsMethod.value ? form.payment_method_id : null,
        reason: form.reason || null,
    }), () => { saving.value = false })
}
</script>
