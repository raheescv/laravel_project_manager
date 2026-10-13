<template>
    <div>
        <div v-if="contract.status !== 'active' && !contract.terms.length" class="pa-empty">
            <i class="fa fa-lock"></i>
            <div class="fw-semibold" style="color: var(--pa-ink)">Payment terms unlock after approval</div>
            <div class="pa-sub">QAR {{ money(contract.contract_amount) }} over {{ contract.no_of_terms }} planned terms.</div>
        </div>

        <template v-else>
            <div class="pa-card-body border-bottom">
                <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-2">
                    <div>
                        <div class="fw-semibold">{{ contract.terms.length }} of {{ contract.no_of_terms }} terms scheduled</div>
                        <div class="pa-sub pa-num">QAR {{ whole(contract.totals.scheduled) }} of {{ whole(contract.contract_amount) }} allocated to terms</div>
                    </div>
                    <button v-if="can.term && contract.status === 'active'" class="btn btn-outline-primary btn-sm" @click="emit('open', 'term')"><i class="fa fa-plus me-1"></i> Add term</button>
                </div>
                <div class="pa-bar mb-2">
                    <span class="paid" :style="{ width: share(contract.totals.paid) }"></span>
                    <span :class="over ? 'over' : 'sched'" :style="{ width: share(contract.totals.scheduled_unpaid) }"></span>
                </div>
                <div class="pa-legend">
                    <span><i class="pa-swatch" style="background: var(--pa-ok)"></i>Paid <b>{{ whole(contract.totals.paid) }}</b></span>
                    <span><i class="pa-swatch" style="background: var(--pa-accent)"></i>Scheduled, unpaid <b>{{ whole(contract.totals.scheduled_unpaid) }}</b></span>
                    <span v-if="!over"><i class="pa-swatch" style="background: var(--pa-line)"></i>Not yet scheduled <b>{{ whole(contract.totals.unscheduled) }}</b></span>
                    <span v-else class="text-danger-emphasis"><i class="fa fa-exclamation-triangle me-1"></i>Terms exceed the contract by <b>{{ whole(-contract.totals.unscheduled) }}</b></span>
                </div>
            </div>

            <div v-if="selected.length" class="pa-selbar">
                <span><b>{{ selected.length }}</b> selected · <b class="pa-num">{{ money(selectedBalance) }}</b></span>
                <button v-if="can.payment" class="btn btn-primary btn-sm ms-auto" @click="emit('open', 'pay', { termIds: [...selected] })"><i class="fa fa-money me-1"></i> Pay selected</button>
                <button class="btn btn-quiet btn-sm" @click="selected = []">Clear</button>
            </div>

            <div v-if="!contract.terms.length" class="pa-empty"><i class="fa fa-tasks"></i>No terms yet. Add the first one when the project plan calls for a payment.</div>
            <div v-else class="table-responsive">
                <table class="table pa-table table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width: 1%"><input class="form-check-input" type="checkbox" aria-label="Select all unpaid terms" :checked="allSelected" :disabled="!payable.length" @change="toggleAll" /></th>
                            <th>Term</th><th>Due date</th><th class="num">Amount</th><th class="num">Paid</th><th class="num">Balance</th><th>Status</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="term in contract.terms" :key="term.id" :class="{ 'is-overdue': term.overdue, 'is-selected': selected.includes(term.id) }">
                            <td><input class="form-check-input" type="checkbox" :aria-label="`Select ${term.label}`" :disabled="term.balance <= 0" :checked="selected.includes(term.id)" @change="toggle(term.id)" /></td>
                            <td><div class="fw-semibold">{{ term.label }}</div><div v-if="term.remarks" class="pa-sub">{{ term.remarks }}</div></td>
                            <td><div class="pa-num">{{ date(term.due_date) }}</div><div v-if="term.balance > 0" class="pa-sub" :class="{ 'text-danger-emphasis': term.overdue }">{{ dueText(term.due_date) }}</div></td>
                            <td class="num">{{ money(term.amount) }}</td>
                            <td class="num" :class="{ 'text-success-emphasis': term.paid > 0 }">{{ money(term.paid) }}</td>
                            <td class="num fw-semibold">{{ money(term.balance) }}</td>
                            <td><span class="pa-pill" :class="term.overdue ? 'overdue' : term.status">{{ term.overdue ? 'Overdue' : termLabels[term.status] }}</span></td>
                            <td class="text-end text-nowrap">
                                <button v-if="can.payment && term.balance > 0 && contract.status === 'active'" class="btn btn-quiet btn-sm" @click="emit('open', 'pay', { termIds: [term.id] })">Pay</button>
                                <template v-if="can.term && contract.status === 'active'">
                                    <button class="btn-icon" title="Edit term" @click="emit('open', 'term', { term })"><i class="fa fa-pencil"></i></button>
                                    <button class="btn-icon danger" :title="term.has_payments ? 'Has payments' : 'Delete term'" :disabled="term.has_payments" @click="remove(term)"><i class="fa" :class="term.has_payments ? 'fa-lock' : 'fa-trash-o'"></i></button>
                                </template>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr><td></td><td colspan="2">Total · {{ contract.terms.length }} terms</td><td class="num">{{ money(contract.totals.scheduled) }}</td><td class="num">{{ money(contract.totals.paid) }}</td><td class="num">{{ money(contract.totals.scheduled_unpaid) }}</td><td colspan="2"></td></tr>
                    </tfoot>
                </table>
            </div>
        </template>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { contractApi } from '../api.js'
import { confirmAction } from '../common/useContractContext.js'
import { date, dueText, money, whole } from '../format.js'

const props = defineProps({ contract: { type: Object, required: true }, can: { type: Object, required: true } })
const emit = defineEmits(['open', 'run'])

const termLabels = { pending: 'Pending', partial: 'Partial', paid: 'Paid' }
const selected = ref([])

const over = computed(() => props.contract.totals.unscheduled < 0)
const payable = computed(() => props.contract.terms.filter((term) => term.balance > 0))
const allSelected = computed(() => payable.value.length > 0 && payable.value.every((term) => selected.value.includes(term.id)))
const selectedBalance = computed(() => props.contract.terms.filter((term) => selected.value.includes(term.id)).reduce((sum, term) => sum + term.balance, 0))

function share(amount) {
    const base = Math.max(props.contract.contract_amount, props.contract.totals.scheduled)
    return base > 0 ? `${Math.min(100, (amount / base) * 100)}%` : '0%'
}

function toggle(id) {
    selected.value = selected.value.includes(id) ? selected.value.filter((item) => item !== id) : [...selected.value, id]
}

function toggleAll() {
    selected.value = allSelected.value ? [] : payable.value.map((term) => term.id)
}

async function remove(term) {
    const ok = await confirmAction({ title: `Delete “${term.label}”?`, text: 'The term is removed from the schedule.', confirm: 'Delete', danger: true })
    if (ok) emit('run', contractApi.deleteTerm(props.contract.id, term.id))
}
</script>
