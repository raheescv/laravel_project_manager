<template>
    <div>
        <div class="pa-card-body border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="pa-seg">
                <button v-for="option in filters" :key="option.key" type="button" :class="{ on: filter === option.key }" @click="filter = option.key">{{ option.label }}</button>
            </div>
            <span class="pa-sub"><i class="fa fa-book me-1"></i>Each payment posts Dr Vendor / Cr payment method</span>
        </div>
        <div v-if="!visible.length" class="pa-empty"><i class="fa fa-exchange"></i>No transactions yet.</div>
        <div v-else class="table-responsive">
            <table class="table pa-table table-hover align-middle">
                <thead><tr><th>Date</th><th>Reference</th><th>Description</th><th>Method</th><th class="num">Paid</th><th class="num">Balance</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="row in visible" :key="row.key" :class="{ 'is-muted': row.kind !== 'payment' || row.reversed }">
                        <td class="pa-num">{{ date(row.date) }}</td>
                        <td>
                            <button v-if="row.journalId" class="paf-link fw-semibold" type="button" @click="openJournal(row.journalId)">{{ row.reference }}</button>
                            <template v-else>{{ row.reference }}</template>
                        </td>
                        <td>
                            <div>{{ row.description }}</div>
                            <div v-if="row.note" class="pa-sub">{{ row.note }}</div>
                            <span v-if="row.reversed" class="pa-pill overdue mt-1">Reversed</span>
                        </td>
                        <td>{{ row.method ?? '—' }}</td>
                        <td class="num" :class="{ 'text-success-emphasis': row.kind === 'payment' && !row.reversed }">{{ row.amount !== null ? money(row.amount) : '—' }}</td>
                        <td class="num">{{ money(row.balance) }}</td>
                        <td class="text-end text-nowrap">
                            <template v-if="row.kind === 'payment'">
                                <a class="btn-icon" :href="urlFor(context.urls, 'receipt', row.id)" target="_blank" rel="noopener" title="Print payment receipt"><i class="fa fa-print"></i></a>
                                <a class="btn-icon" :href="urlFor(context.urls, 'voucher', row.id)" target="_blank" rel="noopener" title="Print payment voucher"><i class="fa fa-file-text-o"></i></a>
                            </template>
                            <button v-if="row.kind === 'payment' && !row.reversed && can.payment_reverse" class="btn-icon danger" title="Reverse payment" @click="reverse(row)"><i class="fa fa-undo"></i></button>
                        </td>
                    </tr>
                </tbody>
                <tfoot><tr><td colspan="4">Paid to date</td><td class="num">{{ money(contract.totals.paid) }}</td><td class="num">{{ money(contract.totals.balance) }}</td><td></td></tr></tfoot>
            </table>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { contractApi } from '../api.js'
import { urlFor, useContext } from '../common/useContractContext.js'
import { date, money } from '../format.js'

const props = defineProps({ contract: { type: Object, required: true }, can: { type: Object, required: true } })
const emit = defineEmits(['run'])
const toast = useToast()
const context = useContext()

const filters = [
    { key: 'all', label: 'All' },
    { key: 'payment', label: 'Payments' },
    { key: 'change', label: 'Contract changes' },
]
const filter = ref('all')

/** Payments and approved extensions in date order, with the balance after each. */
const rows = computed(() => {
    const extensions = props.contract.extensions.filter((item) => item.status === 'approved').slice().reverse()
    const opening = extensions.length ? extensions[0].old_amount : props.contract.contract_amount
    const events = [
        { key: 'open', kind: 'change', date: props.contract.start_date, order: 0, reference: props.contract.reference_no, description: 'Contract opened', change: opening },
        ...extensions.map((item, index) => ({ key: `ext-${item.id}`, kind: 'change', date: item.created_at, order: 1, reference: `EXT-${index + 1}`, description: `Extension · amount ${item.new_amount - item.old_amount >= 0 ? '+' : ''}${money(item.new_amount - item.old_amount)}`, note: item.reason, change: item.new_amount - item.old_amount })),
        ...props.contract.payments.map((payment) => ({
            key: `pay-${payment.id}`,
            kind: 'payment',
            id: payment.id,
            date: payment.date,
            order: 2,
            reference: payment.voucher_no,
            journalId: payment.journal_id,
            description: payment.terms.join(', ') || 'Payment',
            note: [payment.cheque_no ? `#${payment.cheque_no}` : null, payment.bank_name, payment.reference, payment.reversed ? null : payment.remarks, payment.reversal_reason ? `Reversed: ${payment.reversal_reason}` : null].filter(Boolean).join(' · '),
            method: payment.method,
            amount: payment.amount,
            reversed: payment.status === 'reversed',
        })),
    ].sort((a, b) => (a.date === b.date ? a.order - b.order : a.date < b.date ? -1 : 1))

    let balance = 0
    return events.map((event) => {
        if (event.kind === 'change') balance += event.change
        else if (!event.reversed) balance -= event.amount
        return { ...event, amount: event.kind === 'payment' ? event.amount : null, balance }
    })
})

const visible = computed(() => (filter.value === 'all' ? rows.value : rows.value.filter((row) => row.kind === filter.value)))

function openJournal(journalId) {
    if (window.openJournalModal) window.openJournalModal(journalId)
    else toast.info(`Journal #${journalId}`)
}

async function reverse(row) {
    let reason = null
    if (window.Swal) {
        const result = await window.Swal.fire({
            title: `Reverse ${row.reference}?`,
            text: 'A contra journal is posted and the amount goes back to its terms.',
            input: 'text',
            inputPlaceholder: 'Reason, e.g. paid to the wrong account',
            inputValidator: (value) => (!value ? 'Give a reason for reversing the payment.' : undefined),
            showCancelButton: true,
            confirmButtonText: 'Reverse payment',
            confirmButtonColor: '#c45c56',
            reverseButtons: true,
        })
        if (!result.isConfirmed) return
        reason = result.value
    } else {
        reason = window.prompt(`Reverse ${row.reference}? Give a reason:`)
        if (!reason) return
    }
    emit('run', contractApi.reversePayment(props.contract.id, row.id, reason))
}
</script>
