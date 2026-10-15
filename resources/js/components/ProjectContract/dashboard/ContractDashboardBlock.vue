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
                    <button class="bottom" type="button" @click="show('securities')">{{ block.cards.expiring_securities }} {{ block.cards.expiring_securities === 1 ? 'guarantee' : 'guarantees' }} expiring <i class="fa fa-arrow-circle-right"></i></button></div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="pad-grad plain"><div class="top"><i class="fa fa-gavel" style="color: var(--pa-accent)"></i><div><div class="num">{{ block.cards.pending_approval }}</div><div class="lbl">Pending Approval</div></div></div>
                    <component :is="tag(urls.list)" class="bottom" :href="urls.list ? `${urls.list}?status=pending_approval` : null">{{ block.cards.waiting_for_me }} waiting for you <i class="fa fa-arrow-circle-right"></i></component></div>
            </div>
            <div class="col-12 col-md-6 col-xl-5">
                <div class="pa-card pad-rate">
                    <div class="pct">{{ block.cards.payment_rate }}%</div><div class="text-muted-pa">Payment Rate</div>
                    <div class="progress"><div class="progress-bar bg-success" :style="{ width: `${Math.min(100, block.cards.payment_rate)}%` }"></div></div>
                </div>
            </div>
            <div class="col-12 col-xl-4">
                <div class="pa-card pad-rate">
                    <div class="pct">{{ block.cards.scheduled_rate }}%</div><div class="text-muted-pa">Scheduled into Terms</div>
                    <div class="progress"><div class="progress-bar" :style="{ width: `${block.cards.scheduled_rate}%`, background: 'var(--pa-accent)' }"></div></div>
                </div>
            </div>
        </div>

        <div class="pad-alert">
            <div class="head"><i class="fa fa-exclamation-triangle"></i><h5>Overdue Payment Alert</h5><span class="badge rounded-pill">{{ block.overdue.count }}</span></div>
            <div class="pa-card-body p-3">
                <div class="row g-3">
                    <div class="col-12 col-md-4"><div class="pad-tile"><i class="fa fa-clock-o text-danger"></i><div class="n text-danger-emphasis">{{ block.overdue.count }}</div><div class="t">Overdue Terms</div></div></div>
                    <div class="col-12 col-md-4"><div class="pad-tile"><i class="fa fa-money text-danger"></i><div class="n text-danger-emphasis">{{ money(block.overdue.amount) }}</div><div class="t">Overdue Amount</div></div></div>
                    <div class="col-12 col-md-4"><div class="pad-tile"><i class="fa fa-calculator" style="color: var(--pa-accent)"></i><div class="n" style="color: var(--pa-accent)">{{ block.overdue.rate }}%</div><div class="t">Of Balance</div></div></div>
                </div>
            </div>
        </div>

        <div class="pad-sec">
            <div class="head"><i class="fa fa-bar-chart"></i><h5>Contract Statistics Overview</h5></div>
            <div class="body">
                <div class="row g-3 mb-3">
                    <div class="col-6 col-xl-3"><div class="pad-stat"><div class="n" style="color: var(--pa-accent)">{{ block.stats.total }}</div><div class="t">Total Contracts</div></div></div>
                    <div class="col-6 col-xl-3"><div class="pad-stat"><div class="n text-success-emphasis">{{ block.stats.active }}</div><div class="t">Active</div></div></div>
                    <div class="col-6 col-xl-3"><div class="pad-stat"><div class="n text-warning-emphasis">{{ block.stats.pending_approval }}</div><div class="t">Pending Approval</div></div></div>
                    <div class="col-6 col-xl-3"><div class="pad-stat"><div class="n text-danger-emphasis">{{ block.stats.ending_soon }}</div><div class="t">Ending in 60 Days</div></div></div>
                </div>
                <div class="pad-occ">
                    <div class="fw-semibold mb-2" style="font-size: .85rem">Overall Payment Progress</div>
                    <div class="progress"><div class="progress-bar progress-bar-striped" :style="{ width: `${Math.min(100, block.cards.payment_rate)}%`, backgroundColor: 'var(--pa-accent)' }">{{ block.cards.payment_rate }}% Paid</div></div>
                    <div class="pa-sub mt-2"><b class="text-body">{{ whole(block.cards.paid) }}</b> paid, <b class="text-body">{{ whole(block.cards.balance) }}</b> outstanding out of <b class="text-body">{{ whole(block.cards.committed) }}</b> committed</div>
                </div>
            </div>
        </div>

        <div class="pad-sec">
            <div class="head"><i class="fa fa-truck"></i><h5>Vendor-wise Payment Progress</h5></div>
            <div class="body">
                <div v-if="!block.vendors.length" class="pa-empty py-3">No approved contracts in this period.</div>
                <div class="row g-3">
                    <div v-for="vendor in block.vendors" :key="vendor.id" class="col-12 col-md-6 col-xl-4">
                        <div class="pad-vendor" :class="tone(vendor)">
                            <div class="d-flex justify-content-between gap-2"><span class="name" :title="vendor.name">{{ vendor.name }}</span><span class="pct">{{ percent(vendor.paid, vendor.committed) }}%</span></div>
                            <div class="cols"><div><div class="n">{{ vendor.contracts }}</div><div class="t">Contracts</div></div><div><div class="n">{{ whole(vendor.committed) }}</div><div class="t">Committed</div></div><div><div class="n text-success-emphasis">{{ whole(vendor.paid) }}</div><div class="t">Paid</div></div></div>
                            <div class="progress"><div class="progress-bar" :class="barClass[tone(vendor)]" :style="{ width: `${Math.min(100, percent(vendor.paid, vendor.committed))}%`, background: tone(vendor) === 'mid' ? 'var(--pa-accent)' : null }"></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="pa-card p-3">
            <ul class="nav pad-tabs" role="tablist">
                <li v-for="item in tabs" :key="item.key" class="nav-item">
                    <button class="nav-link" :class="{ active: tab === item.key }" type="button" role="tab" :aria-selected="tab === item.key" @click="tab = item.key">
                        <i v-if="item.icon" class="fa" :class="item.icon"></i>{{ item.label }}
                    </button>
                </li>
            </ul>

            <div class="row g-2 mt-1">
                <div class="col-6 col-lg-3"><label class="form-label" :for="`pcdx-vendor-${uid}`">Vendor</label>
                    <select :id="`pcdx-vendor-${uid}`" v-model="vendor" class="form-select"><option value="">All vendors</option><option v-for="name in vendorNames" :key="name" :value="name">{{ name }}</option></select></div>
                <div class="col-6 col-lg-3"><label class="form-label" :for="`pcdx-project-${uid}`">Project</label><input :id="`pcdx-project-${uid}`" v-model="project" class="form-control" placeholder="Search project" /></div>
                <div class="col-6 col-lg-3"><label class="form-label" :for="`pcdx-from-${uid}`">From Date</label><input :id="`pcdx-from-${uid}`" v-model="from" type="date" class="form-control" /></div>
                <div class="col-6 col-lg-3"><label class="form-label" :for="`pcdx-to-${uid}`">To Date</label><input :id="`pcdx-to-${uid}`" v-model="to" type="date" class="form-control" /></div>
            </div>

            <div class="pad-tools">
                <div class="btn-group flex-wrap">
                    <button class="btn btn-sm" type="button" :disabled="!rows.length" @click="exportRows">Excel</button>
                    <button class="btn btn-sm" type="button" title="Change how many rows a page shows" @click="cycleRows">Show {{ perPage }} rows</button>
                </div>
                <div class="d-flex align-items-center gap-2"><label class="fw-semibold" :for="`pcdx-search-${uid}`">Search:</label><input :id="`pcdx-search-${uid}`" v-model="search" class="form-control form-control-sm" style="width: 200px" /></div>
            </div>

            <div class="table-responsive pa-card" style="box-shadow: none">
                <table class="table pa-table table-hover align-middle">
                    <template v-if="tab === 'ending'">
                        <thead><tr><th>Start Date</th><th>End Date</th><th>Project</th><th>Vendor</th><th class="num">Contract</th><th class="num">Scheduled</th><th class="num">Paid</th><th class="num">Balance</th><th>Status</th></tr></thead>
                        <tbody>
                            <tr v-for="row in paged" :key="row.id">
                                <td class="pa-num">{{ date(row.start_date) }}</td><td class="pa-num">{{ date(row.end_date) }}</td>
                                <td><component :is="tag(urls.view)" :href="link(row.id)">{{ row.project_name }}</component></td><td>{{ row.vendor }}</td>
                                <td class="num">{{ money(row.contract_amount) }}</td><td class="num">{{ money(row.scheduled) }}</td><td class="num">{{ money(row.paid) }}</td><td class="num">{{ money(row.balance) }}</td>
                                <td><span class="pa-pill" :class="statusTone[row.status]">{{ row.status_label }}</span></td>
                            </tr>
                            <tr v-if="!rows.length"><td colspan="9"><div class="pa-empty py-3">Nothing to show for these filters.</div></td></tr>
                        </tbody>
                        <tfoot v-if="rows.length"><tr><td colspan="4" class="text-end">Total</td><td class="num">{{ money(sum('contract_amount')) }}</td><td class="num">{{ money(sum('scheduled')) }}</td><td class="num">{{ money(sum('paid')) }}</td><td class="num">{{ money(sum('balance')) }}</td><td></td></tr></tfoot>
                    </template>

                    <template v-else-if="tab === 'due_terms'">
                        <thead><tr><th>Due Date</th><th>Term</th><th>Project</th><th>Vendor</th><th class="num">Amount</th><th class="num">Paid</th><th class="num">Balance</th><th>Status</th></tr></thead>
                        <tbody>
                            <tr v-for="row in paged" :key="row.id" :class="{ 'is-overdue': row.overdue }">
                                <td class="pa-num">{{ date(row.due_date) }}</td><td>{{ row.label }}</td>
                                <td><component :is="tag(urls.view)" :href="link(row.contract_id)">{{ row.project_name }}</component></td><td>{{ row.vendor }}</td>
                                <td class="num">{{ money(row.amount) }}</td><td class="num">{{ money(row.paid) }}</td><td class="num">{{ money(row.balance) }}</td>
                                <td><span class="pa-pill" :class="row.overdue ? 'overdue' : 'pending'">{{ row.overdue ? 'Overdue' : 'Pending' }}</span></td>
                            </tr>
                            <tr v-if="!rows.length"><td colspan="8"><div class="pa-empty py-3">Nothing to show for these filters.</div></td></tr>
                        </tbody>
                        <tfoot v-if="rows.length"><tr><td colspan="4" class="text-end">Total</td><td class="num">{{ money(sum('amount')) }}</td><td class="num">{{ money(sum('paid')) }}</td><td class="num">{{ money(sum('balance')) }}</td><td></td></tr></tfoot>
                    </template>

                    <template v-else-if="tab === 'securities'">
                        <thead><tr><th>Valid Until</th><th>Security</th><th>Reference</th><th>Project</th><th>Vendor</th><th class="num">Amount</th><th>Contract Ends</th></tr></thead>
                        <tbody>
                            <tr v-for="row in paged" :key="row.id" :class="{ 'is-overdue': row.valid_until <= block.today }">
                                <td class="pa-num">{{ date(row.valid_until) }}</td><td>{{ row.purpose }}</td><td>{{ row.reference || '—' }}</td>
                                <td><component :is="tag(urls.view)" :href="link(row.contract_id, 'security')">{{ row.project_name }}</component></td><td>{{ row.vendor }}</td>
                                <td class="num">{{ money(row.amount) }}</td><td class="pa-num">{{ date(row.contract_end) }}</td>
                            </tr>
                            <tr v-if="!rows.length"><td colspan="7"><div class="pa-empty py-3">Nothing to show for these filters.</div></td></tr>
                        </tbody>
                        <tfoot v-if="rows.length"><tr><td colspan="5" class="text-end">Total</td><td class="num">{{ money(sum('amount')) }}</td><td></td></tr></tfoot>
                    </template>

                    <template v-else>
                        <thead><tr><th>Start Date</th><th>End Date</th><th>Project</th><th>Vendor</th><th class="num">Contract</th><th class="num">Paid</th><th class="num">Balance</th><th>Security Held</th></tr></thead>
                        <tbody>
                            <tr v-for="row in paged" :key="row.id">
                                <td class="pa-num">{{ date(row.start_date) }}</td><td class="pa-num">{{ date(row.end_date) }}</td>
                                <td><component :is="tag(urls.view)" :href="link(row.id)">{{ row.project_name }}</component></td><td>{{ row.vendor }}</td>
                                <td class="num">{{ money(row.contract_amount) }}</td><td class="num">{{ money(row.paid) }}</td><td class="num">{{ money(row.balance) }}</td>
                                <td><span v-if="row.security_held > 0" class="pa-pill partial">{{ money(row.security_held) }}</span><span v-else class="text-muted-pa">—</span></td>
                            </tr>
                            <tr v-if="!rows.length"><td colspan="8"><div class="pa-empty py-3">Nothing to show for these filters.</div></td></tr>
                        </tbody>
                        <tfoot v-if="rows.length"><tr><td colspan="4" class="text-end">Total</td><td class="num">{{ money(sum('contract_amount')) }}</td><td class="num">{{ money(sum('paid')) }}</td><td class="num">{{ money(sum('balance')) }}</td><td></td></tr></tfoot>
                    </template>
                </table>
            </div>

            <div class="pad-pager">
                <span class="text-muted-pa">{{ rows.length ? `Showing ${(page - 1) * perPage + 1} to ${Math.min(page * perPage, rows.length)} of ${rows.length} entries` : 'Showing 0 entries' }}</span>
                <ul class="pagination pagination-sm">
                    <li class="page-item" :class="{ disabled: page <= 1 }"><button class="page-link" type="button" :disabled="page <= 1" @click="page--">Previous</button></li>
                    <li v-for="number in pageNumbers" :key="number" class="page-item" :class="{ active: number === page, disabled: number === '…' }">
                        <button class="page-link" type="button" :disabled="number === '…'" @click="typeof number === 'number' && (page = number)">{{ number }}</button>
                    </li>
                    <li class="page-item" :class="{ disabled: page >= pages }"><button class="page-link" type="button" :disabled="page >= pages" @click="page++">Next</button></li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script setup>
import '../../../../css/project-contract.css'
import { computed, ref, watch } from 'vue'
import { date, money, percent, statusTone, whole } from '../format.js'

/**
 * The Agreement Module's section of the home dashboard, drawn in the design
 * approved in docs/project-contract-preview.html. The payload comes from
 * App\Services\Dashboard\Sources\ProjectContractSource.
 */
const props = defineProps({ block: { type: Object, required: true } })

const uid = Math.random().toString(36).slice(2, 8)
const urls = computed(() => props.block.urls ?? {})
const tab = ref('ending')
const vendor = ref('')
const project = ref('')
const from = ref('')
const to = ref('')
const search = ref('')
const page = ref(1)
const perPage = ref(10)

const tabs = [
    { key: 'ending', label: 'Upcoming End Date Contracts' },
    { key: 'due_terms', label: 'Upcoming Due Terms', icon: 'fa-clock-o' },
    { key: 'securities', label: 'Expiring Securities', icon: 'fa-shield' },
    { key: 'expired', label: 'Expired Contracts', icon: 'fa-file-text-o' },
]
const dateKey = { ending: 'end_date', due_terms: 'due_date', securities: 'valid_until', expired: 'end_date' }
const barClass = { high: 'bg-success', mid: '', '': 'bg-warning' }

/** Every table column a row is searched on, per tab. */
const columns = {
    ending: [['Start Date', 'start_date'], ['End Date', 'end_date'], ['Project', 'project_name'], ['Vendor', 'vendor'], ['Contract', 'contract_amount'], ['Scheduled', 'scheduled'], ['Paid', 'paid'], ['Balance', 'balance'], ['Status', 'status_label']],
    due_terms: [['Due Date', 'due_date'], ['Term', 'label'], ['Project', 'project_name'], ['Vendor', 'vendor'], ['Amount', 'amount'], ['Paid', 'paid'], ['Balance', 'balance']],
    securities: [['Valid Until', 'valid_until'], ['Security', 'purpose'], ['Reference', 'reference'], ['Project', 'project_name'], ['Vendor', 'vendor'], ['Amount', 'amount'], ['Contract Ends', 'contract_end']],
    expired: [['Start Date', 'start_date'], ['End Date', 'end_date'], ['Project', 'project_name'], ['Vendor', 'vendor'], ['Contract', 'contract_amount'], ['Paid', 'paid'], ['Balance', 'balance'], ['Security Held', 'security_held']],
}

const remaining = computed(() => (props.block.cards.committed ? (100 - props.block.cards.payment_rate).toFixed(1) : 0))

const vendorNames = computed(() => [...new Set(Object.values(props.block.tables ?? {}).flat().map((row) => row.vendor).filter(Boolean))].sort())

const rows = computed(() => {
    const key = tab.value
    const projectTerm = project.value.trim().toLowerCase()
    const term = search.value.trim().toLowerCase()
    return (props.block.tables[key] ?? []).filter((row) => {
        const day = row[dateKey[key]]
        if (vendor.value && row.vendor !== vendor.value) return false
        if (from.value && day < from.value) return false
        if (to.value && day > to.value) return false
        if (projectTerm && ![row.project_name, row.reference_no].some((value) => String(value ?? '').toLowerCase().includes(projectTerm))) return false
        return !term || columns[key].some(([, field]) => String(row[field] ?? '').toLowerCase().includes(term))
    })
})
const pages = computed(() => Math.max(1, Math.ceil(rows.value.length / perPage.value)))
const paged = computed(() => rows.value.slice((page.value - 1) * perPage.value, page.value * perPage.value))

/** Numbered pages with gaps once there are many: 1 … 4 5 6 … 12. */
const pageNumbers = computed(() => {
    const last = pages.value
    if (last <= 7) return Array.from({ length: last }, (_, index) => index + 1)
    const around = [page.value - 1, page.value, page.value + 1].filter((number) => number > 1 && number < last)
    return [1, ...(around[0] > 2 ? ['…'] : []), ...around, ...(around.at(-1) < last - 1 ? ['…'] : []), last]
})

watch([tab, vendor, project, from, to, search, perPage, () => props.block], () => { page.value = 1 })
watch(pages, (last) => { if (page.value > last) page.value = last })

function show(key) {
    tab.value = key
    page.value = 1
}

function sum(key) {
    return rows.value.reduce((total, row) => total + Number(row[key] ?? 0), 0)
}

function cycleRows() {
    perPage.value = { 10: 25, 25: 50, 50: 10 }[perPage.value] ?? 10
}

/** Download the filtered rows of the open tab as a CSV that Excel opens. */
function exportRows() {
    const cell = (value) => `"${String(value ?? '').replaceAll('"', '""')}"`
    const lines = [columns[tab.value].map(([label]) => cell(label)).join(','), ...rows.value.map((row) => columns[tab.value].map(([, field]) => cell(row[field])).join(','))]
    const url = URL.createObjectURL(new Blob(['﻿' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' }))
    const anchor = Object.assign(document.createElement('a'), { href: url, download: `${tabs.find((item) => item.key === tab.value).label}.csv` })
    anchor.click()
    URL.revokeObjectURL(url)
}

function tone(row) {
    const share = percent(row.paid, row.committed)
    if (share >= 80) return 'high'
    return share >= 40 ? 'mid' : ''
}

/** A link when the user may open it, plain text otherwise. */
function tag(url) {
    return url ? 'a' : 'span'
}

function link(id, tabName = null) {
    if (!urls.value.view) return null
    return urls.value.view.replace('__ID__', id) + (tabName ? `?tab=${tabName}` : '')
}
</script>
