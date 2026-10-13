<template>
    <PcModal :open="open" :title="term ? 'Edit payment term' : 'Add payment term'" :subtitle="term ? contract.project_name : `${contract.project_name} · term ${contract.terms.length + 1} of ${contract.no_of_terms}`" :icon="term ? 'fa-pencil' : 'fa-plus'" @close="emit('close')">
        <form id="pc-term-form" class="d-grid gap-3" @submit.prevent="save">
            <div>
                <label class="form-label" for="t-label">Label *</label>
                <input id="t-label" v-model="form.label" class="form-control" placeholder="e.g. Mobilisation 20%" required />
            </div>
            <div class="row g-2">
                <div class="col-6">
                    <label class="form-label" for="t-due">Due date *</label>
                    <input id="t-due" v-model="form.due_date" type="date" class="form-control" required />
                </div>
                <div class="col-6">
                    <label class="form-label" for="t-amount">Amount *</label>
                    <div class="input-group">
                        <input id="t-amount" v-model="form.amount" class="form-control text-end pa-num" inputmode="decimal" required />
                        <span class="input-group-text pa-num">{{ pct }}%</span>
                    </div>
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2 pa-chips">
                <span class="pa-sub">Quick fill</span>
                <button v-for="value in [10, 15, 20, 25]" :key="value" type="button" @click="form.amount = round(contract.contract_amount * value / 100)">{{ value }}%</button>
                <button v-if="remaining > 0" type="button" @click="form.amount = remaining">Remaining {{ whole(remaining) }}</button>
            </div>
            <div>
                <label class="form-label" for="t-remarks">Remarks</label>
                <textarea id="t-remarks" v-model="form.remarks" class="form-control" rows="2" placeholder="Optional"></textarea>
            </div>
            <div class="p-3 rounded-3" style="background: var(--pa-head); border: 1px solid var(--pa-line)">
                <div class="d-flex justify-content-between mb-2"><span class="pa-sub">Allocated after this {{ term ? 'change' : 'term' }}</span><span class="fw-semibold pa-num">{{ whole(after) }} of {{ whole(contract.contract_amount) }}</span></div>
                <div class="pa-bar mb-2"><span class="sched" :style="{ width: share(baseline) }"></span><span :class="over > 0 ? 'over' : 'new'" :style="{ width: share(Math.max(after - baseline, 0)) }"></span></div>
                <div class="pa-sub">{{ over > 0 ? `${whole(over)} over the contract amount` : `${whole(contract.contract_amount - after)} left to schedule` }}</div>
            </div>
            <div v-if="over > 0" class="pa-callout warn"><i class="fa fa-exclamation-triangle mt-1"></i><span>This goes {{ whole(over) }} over the contract amount. You can still save it; extend the contract if the amount has grown.</span></div>
            <div v-if="!term && contract.terms.length + 1 > contract.no_of_terms" class="pa-callout info"><i class="fa fa-info-circle mt-1"></i><span>This is more terms than the {{ contract.no_of_terms }} planned. You can still save it.</span></div>
        </form>
        <template #footer>
            <button class="btn btn-quiet" type="button" @click="emit('close')">Cancel</button>
            <button class="btn btn-primary" type="submit" form="pc-term-form" :disabled="saving"><i class="fa" :class="saving ? 'fa-spinner fa-spin' : 'fa-check'"></i> Save term</button>
        </template>
    </PcModal>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { contractApi } from '../api.js'
import PcModal from '../common/PcModal.vue'
import { parseAmount, today, whole } from '../format.js'

const props = defineProps({ open: Boolean, contract: { type: Object, required: true }, term: { type: Object, default: null } })
const emit = defineEmits(['close', 'run'])

const form = reactive({ label: '', due_date: today(), amount: '', remarks: '' })
const saving = ref(false)
const round = (value) => Math.round(value * 100) / 100

watch(() => props.open, (isOpen) => {
    if (!isOpen) return
    saving.value = false
    Object.assign(form, props.term
        ? { label: props.term.label, due_date: props.term.due_date, amount: props.term.amount, remarks: props.term.remarks ?? '' }
        : { label: '', due_date: today(), amount: '', remarks: '' })
})

const amount = computed(() => parseAmount(form.amount))
const baseline = computed(() => props.contract.totals.scheduled - (props.term?.amount ?? 0))
const after = computed(() => baseline.value + amount.value)
const over = computed(() => round(after.value - props.contract.contract_amount))
const remaining = computed(() => round(Math.max(props.contract.contract_amount - baseline.value, 0)))
const pct = computed(() => (props.contract.contract_amount ? ((amount.value / props.contract.contract_amount) * 100).toFixed(1).replace(/\.0$/, '') : 0))

function share(value) {
    const base = Math.max(props.contract.contract_amount, after.value)
    return base > 0 ? `${Math.min(100, (value / base) * 100)}%` : '0%'
}

function save() {
    saving.value = true
    const payload = { label: form.label, due_date: form.due_date, amount: amount.value, remarks: form.remarks || null }
    const request = props.term ? contractApi.updateTerm(props.contract.id, props.term.id, payload) : contractApi.addTerm(props.contract.id, payload)
    emit('run', request, () => { saving.value = false })
}
</script>
