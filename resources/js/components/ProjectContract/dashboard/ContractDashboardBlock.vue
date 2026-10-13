<template>
    <div class="pax pcdx d-grid gap-3">
        <div class="row g-3">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="pad-grad"><div class="top g-green"><i class="fa fa-money"></i><div><div class="num">{{ money(block.cards.committed) }}</div><div class="lbl">Committed</div></div></div>
                    <component :is="tag(urls.list)" class="bottom" :href="urls.list">View Details <i class="fa fa-arrow-circle-right"></i></component></div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="pad-grad"><div class="top g-sky"><i class="fa fa-check-circle"></i><div><div class="num">{{ money(block.cards.paid) }}</div><div class="lbl">Paid</div></div></div>
                    <component :is="tag(urls.paid)" class="bottom" :href="urls.paid">{{ block.cards.payment_rate }}% of Total <i class="fa fa-arrow-circle-right"></i></component></div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="pad-grad"><div class="top g-rose"><i class="fa fa-clock-o"></i><div><div class="num">{{ money(block.cards.balance) }}</div><div class="lbl">Balance</div></div></div>
                    <component :is="tag(urls.pending)" class="bottom" :href="urls.pending">{{ remaining }}% Remaining <i class="fa fa-arrow-circle-right"></i></component></div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="pad-grad"><div class="top g-indigo"><i class="fa fa-shield"></i><div><div class="num">{{ money(block.cards.security_held) }}</div><div class="lbl">Security Held</div></div></div>
                    <button class="bottom border-0 bg-transparent w-100 text-start" type="button" @click="tab = 'securities'">{{ block.cards.expiring_securities }} {{ block.cards.expiring_securities === 1 ? 'guarantee' : 'guarantees' }} expiring <i class="fa fa-arrow-circle-right"></i></button></div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="pad-grad plain"><div class="top"><i class="fa fa-gavel" style="color: var(--pa-accent)"></i><div><div class="num">{{ block.cards.pending_approval }}</div><div class="lbl">Pending Approval</div></div></div>
                    <component :is="tag(urls.list)" class="bottom" :href="urls.list ? `${urls.list}?status=pending_approval` : null">{{ block.cards.waiting_for_me }} waiting for you <i class="fa fa-arrow-circle-right"></i></component></div>
            </div>
            <div class="col-12 col-md-6 col-xl-5">
                <div class="pa-card pad-rate"><div class="pct">{{ block.cards.payment_rate }}%</div><div class="text-muted-pa">Payment Rate</div><div class="pa-bar"><span class="paid" :style="{ width: `${Math.min(100, block.cards.payment_rate)}%` }"></span></div></div>
            </div>
            <div class="col-12 col-xl-4">
                <div class="pa-card pad-rate"><div class="pct">{{ block.cards.scheduled_rate }}%</div><div class="text-muted-pa">Scheduled into Terms</div><div class="pa-bar"><span class="sched" :style="{ width: `${block.cards.scheduled_rate}%` }"></span></div></div>
            </div>
        </div>

        <div class="pad-alert">
            <div class="head"><i class="fa fa-exclamation-triangle"></i><h5>Overdue Payment Alert</h5><span class="badge rounded-pill">{{ block.overdue.count }}</span></div>
            <div class="pa-card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-4"><div class="pad-tile"><i class="fa fa-clock-o text-danger"></i><div class="n text-danger-emphasis">{{ block.overdue.count }}</div><div class="t">Overdue Terms</div></div></div>
                    <div class="col-12 col-md-4"><div class="pad-tile"><i class="fa fa-money text-danger"></i><div class="n text-danger-emphasis">{{ money(block.overdue.amount) }}</div><div class="t">Overdue Amount</div></div></div>
                    <div class="col-12 col-md-4"><div class="pad-tile"><i class="fa fa-calculator" style="color: var(--pa-accent)"></i><div class="n" style="color: var(--pa-accent)">{{ block.overdue.rate }}%</div><div class="t">Of Balance</div></div></div>
                </div>
            </div>
        </div>

        <div class="pa-card pad-sec" style="overflow: hidden">
            <div class="head"><i class="fa fa-bar-chart"></i><h5>Contract Statistics Overview</h5></div>
            <div class="pa-card-body">
                <div class="row g-3 mb-3">
                    <div class="col-6 col-xl-3"><div class="pad-stat"><div class="n" style="color: var(--pa-accent)">{{ block.stats.total }}</div><div class="t">Total Contracts</div></div></div>
                    <div class="col-6 col-xl-3"><div class="pad-stat"><div class="n text-success-emphasis">{{ block.stats.active }}</div><div class="t">Active</div></div></div>
                    <div class="col-6 col-xl-3"><div class="pad-stat"><div class="n text-warning-emphasis">{{ block.stats.pending_approval }}</div><div class="t">Pending Approval</div></div></div>
                    <div class="col-6 col-xl-3"><div class="pad-stat"><div class="n text-danger-emphasis">{{ block.stats.ending_soon }}</div><div class="t">Ending in 60 Days</div></div></div>
                </div>
                <div class="pad-occ">
                    <div class="fw-semibold mb-2" style="font-size: .8125rem">Overall Payment Progress</div>
                    <div class="progress"><div class="progress-bar progress-bar-striped" :style="{ width: `${Math.min(100, block.cards.payment_rate)}%` }">{{ block.cards.payment_rate }}% Paid</div></div>
                    <div class="pa-sub mt-2"><b>{{ whole(block.cards.paid) }}</b> paid, <b>{{ whole(block.cards.balance) }}</b> outstanding out of <b>{{ whole(block.cards.committed) }}</b> committed</div>
                </div>
            </div>
        </div>

        <div class="pa-card pad-sec" style="overflow: hidden">
            <div class="head"><i class="fa fa-truck"></i><h5>Vendor-wise Payment Progress</h5></div>
            <div class="pa-card-body">
                <div v-if="!block.vendors.length" class="pa-empty py-3">No approved contracts in this period.</div>
                <div class="row g-3">
                    <div v-for="vendor in block.vendors" :key="vendor.id" class="col-12 col-md-6 col-xl-4">
                        <div class="pad-vendor" :class="tone(vendor)">
                            <div class="d-flex justify-content-between gap-2"><span class="name" :title="vendor.name">{{ vendor.name }}</span><span class="pct">{{ percent(vendor.paid, vendor.committed) }}%</span></div>
                            <div class="cols"><div><div class="n">{{ vendor.contracts }}</div><div class="t">Contracts</div></div><div><div class="n">{{ whole(vendor.committed) }}</div><div class="t">Committed</div></div><div><div class="n text-success-emphasis">{{ whole(vendor.paid) }}</div><div class="t">Paid</div></div></div>
                            <div class="pa-bar"><span :class="tone(vendor) === 'high' ? 'paid' : 'sched'" :style="{ width: `${Math.min(100, percent(vendor.paid, vendor.committed))}%` }"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="pa-card p-3">
            <div class="pad-tabs" role="tablist">
                <button v-for="item in tabs" :key="item.key" type="button" role="tab" :class="{ on: tab === item.key }" :aria-selected="tab === item.key" @click="tab = item.key; page = 1">
                    <i v-if="item.icon" class="fa" :class="item.icon"></i>{{ item.label }} <span class="text-muted-pa">{{ filtered(item.key).length }}</span>
                </button>
            </div>
            <div class="row g-2 mt-1">
                <div class="col-12 col-md-4"><label class="pa-lbl" :for="`pcdx-search-${uid}`">Project or vendor</label><input :id="`pcdx-search-${uid}`" v-model="search" class="form-control form-control-sm" placeholder="Search" @input="page = 1" /></div>
                <div class="col-6 col-md-3"><label class="pa-lbl" :for="`pcdx-from-${uid}`">From date</label><input :id="`pcdx-from-${uid}`" v-model="from" type="date" class="form-control form-control-sm" @change="page = 1" /></div>
                <div class="col-6 col-md-3"><label class="pa-lbl" :for="`pcdx-to-${uid}`">To date</label><input :id="`pcdx-to-${uid}`" v-model="to" type="date" class="form-control form-control-sm" @change="page = 1" /></div>
                <div class="col-12 col-md-2"><label class="pa-lbl" :for="`pcdx-rows-${uid}`">Show</label><select :id="`pcdx-rows-${uid}`" v-model.number="perPage" class="form-select form-select-sm" @change="page = 1"><option :value="10">10 rows</option><option :value="25">25 rows</option><option :value="50">50 rows</option></select></div>
            </div>
            <div class="table-responsive pa-card mt-2" style="box-shadow: none">
                <table class="table pa-table table-hover align-middle mb-0">
                    <thead v-if="tab === 'due_terms'"><tr><th>Due date</th><th>Term</th><th>Project</th><th>Vendor</th><th class="num">Amount</th><th class="num">Paid</th><th class="num">Balance</th></tr></thead>
                    <thead v-else-if="tab === 'securities'"><tr><th>Valid until</th><th>Security</th><th>Reference</th><th>Project</th><th>Vendor</th><th class="num">Held</th><th>Contract ends</th></tr></thead>
                    <thead v-else><tr><th>Start date</th><th>End date</th><th>Project</th><th>Vendor</th><th class="num">Contract</th><th class="num">Scheduled</th><th class="num">Paid</th><th class="num">Balance</th><th>Status</th></tr></thead>
                    <tbody>
                        <tr v-if="!rows.length"><td colspan="9"><div class="pa-empty py-3">Nothing to show for these filters.</div></td></tr>
                        <template v-if="tab === 'due_terms'">
                            <tr v-for="row in paged" :key="`${row.contract_id}-${row.label}-${row.due_date}`" :class="{ 'is-overdue': row.overdue, 'row-link': urls.view }" @click="open(row.contract_id)">
                                <td class="pa-num">{{ date(row.due_date) }}<div class="pa-sub" :class="{ 'text-danger-emphasis': row.overdue }">{{ dueText(row.due_date) }}</div></td>
                                <td>{{ row.label }}</td><td>{{ row.project_name }}<div class="pa-sub">{{ row.reference_no }}</div></td><td>{{ row.vendor }}</td>
                                <td class="num">{{ money(row.amount) }}</td><td class="num">{{ money(row.paid) }}</td><td class="num fw-semibold">{{ money(row.balance) }}</td>
                            </tr>
                        </template>
                        <template v-else-if="tab === 'securities'">
                            <tr v-for="row in paged" :key="`${row.contract_id}-${row.reference}-${row.valid_until}`" class="is-overdue" :class="{ 'row-link': urls.view }" @click="open(row.contract_id, 'security')">
                                <td class="pa-num">{{ date(row.valid_until) }}<div class="pa-sub text-danger-emphasis">{{ dueText(row.valid_until).replace('overdue', 'ago') }}</div></td>
                                <td>{{ row.purpose }}</td><td>{{ row.reference || '—' }}</td><td>{{ row.project_name }}<div class="pa-sub">{{ row.reference_no }}</div></td><td>{{ row.vendor }}</td>
                                <td class="num">{{ money(row.amount) }}</td><td class="pa-num">{{ date(row.contract_end) }}</td>
                            </tr>
                        </template>
                        <template v-else>
                            <tr v-for="row in paged" :key="row.id" :class="{ 'row-link': urls.view }" @click="open(row.id)">
                                <td class="pa-num">{{ date(row.start_date) }}</td><td class="pa-num">{{ date(row.end_date) }}</td>
                                <td>{{ row.project_name }}<div class="pa-sub">{{ row.reference_no }}</div></td><td>{{ row.vendor }}</td>
                                <td class="num">{{ money(row.contract_amount) }}</td><td class="num">{{ money(row.scheduled) }}</td><td class="num">{{ money(row.paid) }}</td><td class="num">{{ money(row.balance) }}</td>
                                <td><span class="pa-pill" :class="statusTone[row.status]">{{ row.status_label }}</span></td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot v-if="tab !== 'securities' && rows.length">
                        <tr v-if="tab === 'due_terms'"><td colspan="4" class="text-end">Total</td><td class="num">{{ money(sum('amount')) }}</td><td class="num">{{ money(sum('paid')) }}</td><td class="num">{{ money(sum('balance')) }}</td></tr>
                        <tr v-else><td colspan="4" class="text-end">Total</td><td class="num">{{ money(sum('contract_amount')) }}</td><td class="num">{{ money(sum('scheduled')) }}</td><td class="num">{{ money(sum('paid')) }}</td><td class="num">{{ money(sum('balance')) }}</td><td></td></tr>
                    </tfoot>
                </table>
            </div>
            <div class="pa-pager border-0 px-0 pb-0">
                <span>{{ rows.length ? `Showing ${(page - 1) * perPage + 1} to ${Math.min(page * perPage, rows.length)} of ${rows.length} entries` : '' }}</span>
                <div class="d-flex gap-1">
                    <button class="btn btn-quiet btn-sm" type="button" :disabled="page <= 1" @click="page--">Previous</button>
                    <button class="btn btn-quiet btn-sm" type="button" disabled>{{ page }} / {{ pages }}</button>
                    <button class="btn btn-quiet btn-sm" type="button" :disabled="page >= pages" @click="page++">Next</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import '../../../../css/project-contract.css'
import { computed, ref, watch } from 'vue'
import { date, dueText, money, percent, statusTone, whole } from '../format.js'

/**
 * The Agreement Module's section of the home dashboard, drawn in the design
 * approved in docs/project-contract-preview.html. The payload comes from
 * App\Services\Dashboard\Sources\ProjectContractSource.
 */
const props = defineProps({ block: { type: Object, required: true } })

const uid = Math.random().toString(36).slice(2, 8)
const urls = computed(() => props.block.urls ?? {})
const tab = ref('ending')
const search = ref('')
const from = ref('')
const to = ref('')
const page = ref(1)
const perPage = ref(10)

const tabs = [
    { key: 'ending', label: 'Upcoming End Dates' },
    { key: 'due_terms', label: 'Upcoming Due Terms', icon: 'fa-clock-o' },
    { key: 'securities', label: 'Expiring Securities', icon: 'fa-shield' },
    { key: 'expired', label: 'Past End Date', icon: 'fa-file-text-o' },
]
const dateKey = { ending: 'end_date', due_terms: 'due_date', securities: 'valid_until', expired: 'end_date' }

const remaining = computed(() => (props.block.cards.committed ? (100 - props.block.cards.payment_rate).toFixed(1) : 0))

function filtered(key) {
    const term = search.value.trim().toLowerCase()
    return (props.block.tables[key] ?? []).filter((row) => {
        const day = row[dateKey[key]]
        if (from.value && day < from.value) return false
        if (to.value && day > to.value) return false
        return !term || [row.project_name, row.vendor, row.reference_no, row.label].some((value) => String(value ?? '').toLowerCase().includes(term))
    })
}

const rows = computed(() => filtered(tab.value))
const pages = computed(() => Math.max(1, Math.ceil(rows.value.length / perPage.value)))
const paged = computed(() => rows.value.slice((page.value - 1) * perPage.value, page.value * perPage.value))
watch(() => props.block, () => { page.value = 1 })

function sum(key) {
    return rows.value.reduce((total, row) => total + Number(row[key] ?? 0), 0)
}

function tone(vendor) {
    const share = percent(vendor.paid, vendor.committed)
    if (share >= 80) return 'high'
    return share >= 40 ? 'mid' : ''
}

/** A link when the user may open it, plain text otherwise. */
function tag(url) {
    return url ? 'a' : 'span'
}

function open(id, tabName = null) {
    if (!urls.value.view) return
    window.location.href = urls.value.view.replace('__ID__', id) + (tabName ? `?tab=${tabName}` : '')
}
</script>
