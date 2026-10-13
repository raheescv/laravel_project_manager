<template>
    <div class="d-grid gap-3">
        <div class="pa-pagehead mb-0">
            <div class="me-auto">
                <div class="pa-crumbs"><a :href="urlFor(context.urls, 'list')">Project Contracts</a> <i class="fa fa-angle-right mx-1"></i> Reports</div>
                <h1 class="pa-title">{{ report === 'pending' ? 'Pending Payments' : 'Paid Payments' }}</h1>
            </div>
            <a class="btn btn-quiet btn-sm no-print" :href="urlFor(context.urls, report === 'pending' ? 'reportPaid' : 'reportPending')">
                <i class="fa me-1" :class="report === 'pending' ? 'fa-check-circle' : 'fa-clock-o'"></i>{{ report === 'pending' ? 'Paid payments' : 'Pending payments' }}
            </a>
        </div>

        <div class="pa-card pa-filterbar no-print">
            <div class="fields">
                <div><label class="pa-lbl" for="r-from">{{ report === 'pending' ? 'Due from' : 'Paid from' }}</label><input id="r-from" v-model="filters.from_date" type="date" class="form-control form-control-sm" @change="load" /></div>
                <div><label class="pa-lbl" for="r-to">To</label><input id="r-to" v-model="filters.to_date" type="date" class="form-control form-control-sm" @change="load" /></div>
                <div>
                    <label class="pa-lbl" for="r-vendor">Vendor</label>
                    <div class="d-flex"><VendorPicker v-model="filters.account_id" :label="vendorLabel" input-id="r-vendor" placeholder="All vendors" @picked="vendorPicked" /></div>
                </div>
            </div>
            <div class="chiprow">
                <template v-if="report === 'pending'">
                    <span class="pa-lbl mb-0">Status</span>
                    <div class="pa-chips">
                        <button v-for="state in states" :key="state.value" type="button" :class="{ on: filters.state === state.value }" @click="filters.state = state.value; load()">{{ state.label }}</button>
                    </div>
                </template>
                <template v-else>
                    <span class="pa-lbl mb-0">Method</span>
                    <div class="pa-chips">
                        <button type="button" :class="{ on: !filters.method_id }" @click="filters.method_id = ''; load()">All</button>
                        <button v-for="method in options.payment_methods" :key="method.id" type="button" :class="{ on: filters.method_id === method.id }" @click="filters.method_id = method.id; load()">{{ method.name }}</button>
                    </div>
                </template>
                <button class="btn btn-quiet btn-sm ms-auto" type="button" @click="reset"><i class="fa fa-undo me-1"></i>Reset</button>
            </div>
        </div>

        <div v-if="!result" class="pa-skeleton" style="height: 260px"></div>

        <template v-else-if="report === 'pending'">
            <div class="rpx-sum">
                <div class="s"><div class="l">Total pending</div><div class="v">{{ money(result.summary.total) }}</div><div class="pa-sub">{{ result.summary.total_count }} terms · {{ result.summary.contracts }} contracts</div></div>
                <div class="s bad"><div class="l">Overdue</div><div class="v">{{ money(result.summary.overdue) }}</div><div class="pa-sub">{{ result.summary.overdue_count }} terms</div></div>
                <div class="s warn"><div class="l">Due in 30 days</div><div class="v">{{ money(result.summary.soon) }}</div><div class="pa-sub">{{ result.summary.soon_count }} terms</div></div>
                <div class="s"><div class="l">Later</div><div class="v">{{ money(result.summary.later) }}</div><div class="pa-sub">{{ result.summary.later_count }} terms</div></div>
            </div>
            <div class="pa-card p-3">
                <div class="d-flex justify-content-between align-items-center mb-2"><span class="fw-semibold" style="font-size: .8125rem">Ageing of pending balance</span><span class="pa-sub">as of {{ date(today()) }}</span></div>
                <div class="rpx-aging">
                    <span v-for="bucket in ageing" :key="bucket.key" :style="{ flex: bucket.amount, background: bucket.color }" :title="`${bucket.label} ${whole(bucket.amount)}`"></span>
                </div>
                <div class="pa-legend mt-2">
                    <span v-for="bucket in ageing" :key="bucket.key"><i class="pa-swatch" :style="{ background: bucket.amount ? bucket.color : 'var(--pa-line)' }"></i>{{ bucket.label }} <b>{{ whole(bucket.amount) }}</b></span>
                </div>
            </div>
            <div class="pa-card">
                <div class="rpx-tools"><span class="fw-semibold">Pending payments</span><span class="pa-sub">{{ result.rows.length }} {{ result.rows.length === 1 ? 'term' : 'terms' }}{{ result.truncated ? ' (first 1,000)' : '' }}</span>
                    <div class="ms-auto d-flex flex-wrap gap-1 no-print"><a class="btn btn-quiet btn-sm" :href="exportUrl"><i class="fa fa-file-excel-o me-1"></i>Excel</a><button class="btn btn-quiet btn-sm" type="button" @click="print"><i class="fa fa-print me-1"></i>Print</button></div></div>
                <div class="table-responsive">
                    <table class="table pa-table table-hover align-middle">
                        <thead><tr><th>Due date</th><th>Contract</th><th>Vendor</th><th>Term</th><th class="num">Amount</th><th class="num">Paid</th><th class="num">Balance</th><th>Status</th></tr></thead>
                        <tbody>
                            <tr v-if="!result.rows.length"><td colspan="8"><div class="pa-empty py-3">Nothing pending for these filters.</div></td></tr>
                            <tr v-for="row in result.rows" :key="`${row.contract_id}-${row.label}-${row.due_date}`" class="row-link" :class="{ 'is-overdue': row.state === 'overdue' }" @click="openContract(row.contract_id)">
                                <td class="pa-num">{{ date(row.due_date) }}<div class="pa-sub" :class="{ 'text-danger-emphasis': row.state === 'overdue' }">{{ dueText(row.due_date) }}</div></td>
                                <td><span class="fw-semibold">{{ row.reference_no }}</span><div class="pa-sub">{{ row.project_name }}</div></td>
                                <td>{{ row.vendor }}</td><td>{{ row.label }}</td>
                                <td class="num">{{ money(row.amount) }}</td><td class="num">{{ money(row.paid) }}</td><td class="num fw-semibold">{{ money(row.balance) }}</td>
                                <td><span class="pa-pill" :class="{ overdue: 'overdue', soon: 'partial', later: 'pending' }[row.state]">{{ { overdue: 'Overdue', soon: 'Due soon', later: 'Upcoming' }[row.state] }}</span></td>
                            </tr>
                        </tbody>
                        <tfoot v-if="result.rows.length"><tr><td colspan="4">Total</td><td class="num">{{ money(total('amount')) }}</td><td class="num">{{ money(total('paid')) }}</td><td class="num">{{ money(total('balance')) }}</td><td></td></tr></tfoot>
                    </table>
                </div>
            </div>
        </template>

        <template v-else>
            <div class="rpx-sum">
                <div class="s ok"><div class="l">Total paid</div><div class="v">{{ money(result.summary.total) }}</div><div class="pa-sub">{{ result.summary.count }} payments · {{ result.summary.contracts }} contracts</div></div>
                <div v-for="method in result.summary.methods.slice(0, 3)" :key="method.id" class="s"><div class="l">{{ method.name }}</div><div class="v">{{ money(method.amount) }}</div><div class="pa-sub">{{ method.count }} {{ method.count === 1 ? 'payment' : 'payments' }}</div></div>
            </div>
            <div class="pa-card p-3">
                <div class="d-flex justify-content-between align-items-center mb-2"><span class="fw-semibold" style="font-size: .8125rem">Paid by month</span><span class="pa-sub">{{ filters.from_date || filters.to_date ? `${date(filters.from_date)} – ${date(filters.to_date)}` : 'All dates' }}</span></div>
                <div v-if="!result.summary.months.length" class="pa-sub">No payments in this range.</div>
                <div v-else class="rpx-months">
                    <div v-for="month in result.summary.months" :key="month.month"><span class="bar" :style="{ height: `${Math.max(2, (month.amount / maxMonth) * 100)}%` }"></span><b>{{ whole(month.amount) }}</b><small>{{ monthLabel(month.month) }}</small></div>
                </div>
            </div>
            <div class="pa-card">
                <div class="rpx-tools"><span class="fw-semibold">Paid payments</span><span class="pa-sub">{{ result.rows.length }} {{ result.rows.length === 1 ? 'payment' : 'payments' }}{{ result.truncated ? ' (first 1,000)' : '' }}</span>
                    <div class="ms-auto d-flex flex-wrap gap-1 no-print"><a class="btn btn-quiet btn-sm" :href="exportUrl"><i class="fa fa-file-excel-o me-1"></i>Excel</a><button class="btn btn-quiet btn-sm" type="button" @click="print"><i class="fa fa-print me-1"></i>Print</button></div></div>
                <div class="table-responsive">
                    <table class="table pa-table table-hover align-middle">
                        <thead><tr><th>Date</th><th>Voucher</th><th>Contract</th><th>Vendor</th><th>Terms</th><th>Method</th><th>Reference</th><th class="num">Amount</th><th class="no-print"></th></tr></thead>
                        <tbody>
                            <tr v-if="!result.rows.length"><td colspan="9"><div class="pa-empty py-3">No payments for these filters.</div></td></tr>
                            <tr v-for="row in result.rows" :key="row.id">
                                <td class="pa-num">{{ date(row.date) }}</td>
                                <td><button v-if="row.journal_id" class="paf-link fw-semibold" type="button" @click="openJournal(row.journal_id)">{{ row.voucher_no }}</button><template v-else>{{ row.voucher_no }}</template></td>
                                <td><a class="fw-semibold" :href="urlFor(context.urls, 'view', row.contract_id)">{{ row.reference_no }}</a><div class="pa-sub">{{ row.project_name }}</div></td>
                                <td>{{ row.vendor }}</td><td>{{ row.terms }}</td><td>{{ row.method }}</td><td class="pa-sub">{{ row.reference || '—' }}</td>
                                <td class="num fw-semibold text-success-emphasis">{{ money(row.amount) }}</td>
                                <td class="text-end text-nowrap no-print">
                                    <span class="pa-prints">
                                        <a class="btn btn-quiet btn-sm" :href="urlFor(context.urls, 'receipt', row.id)" target="_blank" rel="noopener" title="Payment receipt"><i class="fa fa-print"></i> Receipt</a>
                                        <a class="btn btn-quiet btn-sm" :href="urlFor(context.urls, 'voucher', row.id)" target="_blank" rel="noopener" title="Payment voucher"><i class="fa fa-print"></i> Voucher</a>
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot v-if="result.rows.length"><tr><td colspan="7">Total</td><td class="num">{{ money(total('amount')) }}</td><td class="no-print"></td></tr></tfoot>
                    </table>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { contractApi, errorMessage } from '../api.js'
import { urlFor, useContext, useOptions } from '../common/useContractContext.js'
import { date, dueText, money, today, whole } from '../format.js'
import VendorPicker from '../vendor/VendorPicker.vue'

const props = defineProps({ report: { type: String, default: 'pending' } })
const context = useContext()
const options = useOptions()
const toast = useToast()

const report = computed(() => props.report)
const result = ref(null)
const vendorLabel = ref('')
const filters = reactive({ from_date: '', to_date: '', account_id: null, state: '', method_id: '' })
const states = [
    { value: '', label: 'All' },
    { value: 'overdue', label: 'Overdue' },
    { value: 'soon', label: 'Due in 30 days' },
    { value: 'later', label: 'Later' },
]
let controller = null

const params = computed(() => ({
    from_date: filters.from_date,
    to_date: filters.to_date,
    account_id: filters.account_id,
    ...(report.value === 'pending' ? { state: filters.state } : { method_id: filters.method_id }),
}))
const exportUrl = computed(() => contractApi.exportUrl(report.value, params.value))
const maxMonth = computed(() => Math.max(1, ...(result.value?.summary.months ?? []).map((month) => month.amount)))
const ageing = computed(() => {
    const buckets = result.value?.summary.ageing ?? {}
    return [
        { key: 'current', label: 'Not yet due', amount: buckets.current ?? 0, color: 'var(--pa-accent)' },
        { key: 'd30', label: '1–30 days', amount: buckets.d30 ?? 0, color: 'var(--pa-warn)' },
        { key: 'd60', label: '31–60 days', amount: buckets.d60 ?? 0, color: 'var(--pa-bad)' },
        { key: 'd90', label: '61–90 days', amount: buckets.d90 ?? 0, color: '#a04a45' },
        { key: 'over90', label: 'Over 90 days', amount: buckets.over90 ?? 0, color: '#7d3632' },
    ]
})

function total(key) {
    return (result.value?.rows ?? []).reduce((sum, row) => sum + Number(row[key] ?? 0), 0)
}

function monthLabel(value) {
    const [year, month] = value.split('-').map(Number)
    return new Date(year, month - 1, 1).toLocaleDateString('en-GB', { month: 'short', year: '2-digit' })
}

async function load() {
    controller?.abort()
    controller = new AbortController()
    try {
        const response = report.value === 'pending'
            ? await contractApi.pendingReport(params.value, controller.signal)
            : await contractApi.paidReport(params.value, controller.signal)
        result.value = response.data
    } catch (error) {
        const message = errorMessage(error)
        if (message && error?.name !== 'CanceledError') toast.error(message)
    }
}


function vendorPicked(vendor) {
    vendorLabel.value = vendor.name
    filters.account_id = vendor.id
    load()
}

function reset() {
    Object.assign(filters, { from_date: '', to_date: '', account_id: null, state: '', method_id: '' })
    vendorLabel.value = ''
    load()
}

function openContract(id) {
    window.location.href = urlFor(context.urls, 'view', id)
}

function openJournal(journalId) {
    if (window.openJournalModal) window.openJournalModal(journalId)
}

function print() {
    window.print()
}

onMounted(load)
</script>
