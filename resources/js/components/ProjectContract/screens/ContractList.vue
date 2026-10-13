<template>
    <div>
        <div class="pa-pagehead">
            <div class="me-auto">
                <div class="pa-crumbs">Agreements</div>
                <h1 class="pa-title">Project Contracts</h1>
            </div>
            <a v-if="context.permissions.report" class="btn btn-quiet" :href="urlFor(context.urls, 'reportPending')"><i class="fa fa-clock-o me-1"></i> Pending payments</a>
            <a v-if="context.permissions.report" class="btn btn-quiet" :href="urlFor(context.urls, 'reportPaid')"><i class="fa fa-check-circle me-1"></i> Paid payments</a>
            <a v-if="context.permissions.create" class="btn btn-primary" :href="urlFor(context.urls, 'create')"><i class="fa fa-plus me-1"></i> New contract</a>
        </div>

        <div class="pa-card">
            <div class="pa-card-head flex-wrap">
                <div class="pa-seg">
                    <button v-for="tab in statusTabs" :key="tab.value" type="button" :class="{ on: filters.status === tab.value }" @click="setStatus(tab.value)">
                        {{ tab.label }} <span class="text-muted-pa">{{ counts[tab.value || 'all'] ?? 0 }}</span>
                    </button>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <div class="input-group input-group-sm" style="width: 240px">
                        <span class="input-group-text"><i class="fa fa-search"></i></span>
                        <input v-model="filters.search" class="form-control" placeholder="Search project, vendor or PC no." aria-label="Search contracts" @input="debouncedLoad" />
                    </div>
                    <input v-model="filters.from_date" type="date" class="form-control form-control-sm" style="width: 145px" aria-label="Running from" @change="reload" />
                    <input v-model="filters.to_date" type="date" class="form-control form-control-sm" style="width: 145px" aria-label="Running until" @change="reload" />
                </div>
            </div>
            <div class="table-responsive">
                <table class="table pa-table table-hover align-middle">
                    <thead>
                        <tr>
                            <th class="sortable" @click="sortBy('project_name')">Project <i class="fa" :class="sortIcon('project_name')"></i></th>
                            <th>Vendor</th>
                            <th class="sortable" @click="sortBy('start_date')">Period <i class="fa" :class="sortIcon('start_date')"></i></th>
                            <th class="num sortable" @click="sortBy('contract_amount')">Amount <i class="fa" :class="sortIcon('contract_amount')"></i></th>
                            <th style="width: 170px">Paid</th>
                            <th>Next due</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading && !rows.length"><td colspan="7"><div class="pa-skeleton" style="height: 120px"></div></td></tr>
                        <tr v-else-if="!rows.length"><td colspan="7"><div class="pa-empty"><i class="fa fa-file-text-o"></i>No contracts match these filters.</div></td></tr>
                        <tr v-for="row in rows" :key="row.id" class="row-link" :class="{ 'is-overdue': row.next_due_date && daysFromToday(row.next_due_date) < 0 }" @click="open(row)">
                            <td><div class="fw-semibold">{{ row.project_name }}</div><div class="pa-sub">{{ row.reference_no }}</div></td>
                            <td><div class="d-flex align-items-center gap-2"><span class="pa-avatar">{{ initials(row.vendor?.name) }}</span><span>{{ row.vendor?.name ?? '—' }}</span></div></td>
                            <td class="pa-num">{{ date(row.start_date) }} – {{ date(row.end_date) }}</td>
                            <td class="num">{{ money(row.contract_amount) }}</td>
                            <td>
                                <div class="d-flex justify-content-between pa-sub mb-1"><span class="pa-num">{{ whole(row.paid) }}</span><span>{{ Math.round(percent(row.paid, row.contract_amount, 0)) }}%</span></div>
                                <div class="pa-bar thin"><span class="paid" :style="{ width: `${Math.min(100, percent(row.paid, row.contract_amount))}%` }"></span></div>
                            </td>
                            <td>
                                <template v-if="row.next_due_date"><div class="pa-num">{{ date(row.next_due_date) }} · {{ whole(row.next_due_amount) }}</div><div class="pa-sub" :class="{ 'text-danger-emphasis': daysFromToday(row.next_due_date) < 0 }">{{ dueText(row.next_due_date) }}</div></template>
                                <span v-else class="pa-sub">{{ row.status === 'active' ? 'No terms due' : '—' }}</span>
                            </td>
                            <td>
                                <span class="pa-pill" :class="statusTone[row.status]">{{ row.status_label }}</span>
                                <div v-if="row.status === 'pending_approval' && row.pending_level" class="pa-sub mt-1">Level {{ row.pending_level }} of {{ row.level_count }}</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="pa-pager">
                <span>{{ pagination.total ? `Showing ${(pagination.page - 1) * pagination.per_page + 1}–${Math.min(pagination.page * pagination.per_page, pagination.total)} of ${pagination.total}` : '' }}</span>
                <div class="d-flex gap-1">
                    <button class="btn btn-quiet btn-sm" :disabled="pagination.page <= 1" aria-label="Previous page" @click="go(pagination.page - 1)"><i class="fa fa-angle-left"></i></button>
                    <button class="btn btn-quiet btn-sm" :disabled="pagination.page >= pagination.last_page" aria-label="Next page" @click="go(pagination.page + 1)"><i class="fa fa-angle-right"></i></button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { contractApi, errorMessage } from '../api.js'
import { urlFor, useContext } from '../common/useContractContext.js'
import { date, daysFromToday, dueText, initials, money, percent, statusTone, whole } from '../format.js'

const context = useContext()
const toast = useToast()

const statusTabs = [
    { value: '', label: 'All' },
    { value: 'pending_approval', label: 'Pending approval' },
    { value: 'active', label: 'Active' },
    { value: 'draft', label: 'Draft' },
    { value: 'rejected', label: 'Rejected' },
    { value: 'completed', label: 'Completed' },
]

const filters = reactive({ search: '', status: new URLSearchParams(window.location.search).get('status') ?? '', from_date: '', to_date: '', sort: 'id', direction: 'desc', page: 1 })
const rows = ref([])
const counts = ref({})
const pagination = ref({ page: 1, last_page: 1, total: 0, per_page: 15 })
const loading = ref(false)
let controller = null
let timer = null

async function load() {
    controller?.abort()
    controller = new AbortController()
    loading.value = true
    try {
        const response = await contractApi.list(filters, controller.signal)
        rows.value = response.data.rows
        counts.value = response.data.counts
        pagination.value = response.data.pagination
    } catch (error) {
        const message = errorMessage(error)
        if (message && error?.name !== 'CanceledError') toast.error(message)
    } finally {
        loading.value = false
    }
}

function reload() {
    filters.page = 1
    load()
}

function debouncedLoad() {
    clearTimeout(timer)
    timer = setTimeout(reload, 250)
}

function setStatus(status) {
    filters.status = status
    reload()
}

function sortBy(column) {
    filters.direction = filters.sort === column && filters.direction === 'asc' ? 'desc' : 'asc'
    filters.sort = column
    reload()
}

function sortIcon(column) {
    if (filters.sort !== column) return 'fa-sort text-muted-pa'
    return filters.direction === 'asc' ? 'fa-sort-asc' : 'fa-sort-desc'
}

function go(page) {
    filters.page = page
    load()
}

function open(row) {
    window.location.href = urlFor(context.urls, 'view', row.id)
}

onMounted(load)
</script>
