<template>
    <div class="d-grid gap-2">
        <div class="pa-pagehead mb-1">
            <div class="me-auto">
                <div class="pa-crumbs"><a :href="urlFor(context.urls, 'list')">Project Contracts</a> <i class="fa fa-angle-right mx-1"></i> Vendors</div>
                <h1 class="pa-title">Vendors</h1>
            </div>
            <a v-if="context.permissions.vendor_export" class="btn btn-quiet btn-sm" :href="contractApi.vendorExportUrl()"><i class="fa fa-file-excel-o me-1"></i> Export</a>
            <button v-if="context.permissions.vendor_create" class="btn btn-primary btn-sm" type="button" @click="openModal(null)"><i class="fa fa-plus me-1"></i> New vendor</button>
        </div>

        <div class="pa-card pa-filterbar">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="input-group input-group-sm" style="max-width: 320px">
                    <span class="input-group-text"><i class="fa fa-search"></i></span>
                    <input v-model="filters.search" class="form-control" placeholder="Search name, mobile, place, CR no." aria-label="Search vendors" @input="debouncedLoad" />
                </div>
                <div class="pa-chips">
                    <button type="button" :class="{ on: !filters.vendor_type }" @click="setType('')">All</button>
                    <button v-for="type in options.vendor_types" :key="type.value" type="button" :class="{ on: filters.vendor_type === type.value }" @click="setType(type.value)">{{ type.label }}</button>
                </div>
                <span class="pa-sub ms-auto">{{ pagination.total }} {{ pagination.total === 1 ? 'vendor' : 'vendors' }}</span>
            </div>
        </div>

        <div class="pa-card" style="overflow: hidden">
            <div v-if="selected.length" class="pa-selbar">
                <span><b>{{ selected.length }}</b> selected</span>
                <button v-if="context.permissions.vendor_delete" class="btn btn-quiet btn-sm text-danger ms-auto" type="button" @click="removeSelected"><i class="fa fa-trash-o me-1"></i> Delete selected</button>
                <button class="btn btn-quiet btn-sm" type="button" @click="selected = []">Clear</button>
            </div>
            <div class="table-responsive">
                <table class="table pa-table table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width: 1%"><input class="form-check-input" type="checkbox" aria-label="Select all vendors on this page" :checked="rows.length > 0 && rows.every((row) => selected.includes(row.id))" @change="toggleAll" /></th>
                            <th class="sortable" @click="sortBy('name')">Vendor <i class="fa" :class="sortIcon('name')"></i></th>
                            <th>Type</th>
                            <th class="sortable" @click="sortBy('mobile')">Mobile <i class="fa" :class="sortIcon('mobile')"></i></th>
                            <th class="sortable" @click="sortBy('email')">Email <i class="fa" :class="sortIcon('email')"></i></th>
                            <th class="sortable" @click="sortBy('place')">Place <i class="fa" :class="sortIcon('place')"></i></th>
                            <th>CR / Tax No.</th>
                            <th class="num sortable" @click="sortBy('credit_period_days')">Credit <i class="fa" :class="sortIcon('credit_period_days')"></i></th>
                            <template v-if="withContracts"><th class="num">Contracts</th><th class="num">Committed</th></template>
                            <th class="num">Balance</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading && !rows.length"><td :colspan="columns"><div class="pa-skeleton" style="height: 100px"></div></td></tr>
                        <tr v-else-if="!rows.length"><td :colspan="columns"><div class="pa-empty"><i class="fa fa-truck"></i>No vendors match.</div></td></tr>
                        <tr v-for="vendor in rows" :key="vendor.id" :class="{ 'is-selected': selected.includes(vendor.id) }">
                            <td><input class="form-check-input" type="checkbox" :aria-label="`Select ${vendor.name}`" :checked="selected.includes(vendor.id)" @change="toggle(vendor.id)" /></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="pa-avatar">{{ initials(vendor.name) }}</span>
                                    <div style="min-width: 0"><div class="fw-semibold">{{ vendor.name }}</div><div v-if="vendor.alias_name" class="pa-sub" dir="rtl">{{ vendor.alias_name }}</div></div>
                                </div>
                            </td>
                            <td>{{ vendor.vendor_type_label ?? '—' }}</td>
                            <td class="pa-num">{{ vendor.mobile ?? '—' }}</td>
                            <td>{{ vendor.email ?? '—' }}</td>
                            <td>{{ vendor.place ?? '—' }}</td>
                            <td>{{ vendor.registration ?? '—' }}</td>
                            <td class="num">{{ vendor.credit_period_days ? `${vendor.credit_period_days} d` : '—' }}</td>
                            <template v-if="withContracts"><td class="num">{{ vendor.contracts }}</td><td class="num">{{ money(vendor.committed) }}</td></template>
                            <td class="num" :title="vendor.balance < 0 ? 'Paid ahead of bills' : ''">{{ money(vendor.balance) }}</td>
                            <td class="text-end text-nowrap">
                                <button v-if="context.permissions.vendor_edit" class="btn-icon" type="button" title="Edit vendor" @click="openModal(vendor.id)"><i class="fa fa-pencil"></i></button>
                                <button v-if="context.permissions.vendor_delete" class="btn-icon danger" type="button" title="Delete vendor" @click="remove(vendor)"><i class="fa fa-trash-o"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="pa-pager">
                <span class="d-flex align-items-center gap-2">
                    <select v-model.number="filters.per_page" class="form-select form-select-sm" style="width: auto" aria-label="Rows per page" @change="go(1)">
                        <option :value="20">20</option><option :value="100">100</option><option :value="500">500</option>
                    </select>
                    {{ pagination.total ? `Showing ${(pagination.page - 1) * pagination.per_page + 1}–${Math.min(pagination.page * pagination.per_page, pagination.total)} of ${pagination.total}` : '' }}
                </span>
                <div class="d-flex gap-1">
                    <button class="btn btn-quiet btn-sm" :disabled="pagination.page <= 1" aria-label="Previous page" @click="go(pagination.page - 1)"><i class="fa fa-angle-left"></i></button>
                    <button class="btn btn-quiet btn-sm" :disabled="pagination.page >= pagination.last_page" aria-label="Next page" @click="go(pagination.page + 1)"><i class="fa fa-angle-right"></i></button>
                </div>
            </div>
        </div>

        <VendorModal :open="modal.open" :vendor-id="modal.id" @close="modal.open = false" @saved="onSaved" />
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { contractApi, errorMessage } from '../api.js'
import { confirmAction, urlFor, useContext, useOptions } from '../common/useContractContext.js'
import { initials, money } from '../format.js'
import VendorModal from '../vendor/VendorModal.vue'

const context = useContext()
const options = useOptions()
const toast = useToast()

const filters = reactive({ search: '', vendor_type: '', page: 1, per_page: 20, sort: 'name', direction: 'asc' })
const rows = ref([])
const selected = ref([])
const withContracts = ref(true)
const pagination = ref({ page: 1, last_page: 1, total: 0, per_page: 20 })
const loading = ref(false)
const modal = reactive({ open: false, id: null })
let controller = null
let timer = null

const columns = computed(() => (withContracts.value ? 12 : 10))

async function load() {
    controller?.abort()
    controller = new AbortController()
    loading.value = true
    try {
        const response = await contractApi.vendors(filters, controller.signal)
        rows.value = response.data.rows
        pagination.value = response.data.pagination
        withContracts.value = response.data.with_contracts
    } catch (error) {
        const message = errorMessage(error)
        if (message && error?.name !== 'CanceledError') toast.error(message)
    } finally {
        loading.value = false
    }
}

function debouncedLoad() {
    clearTimeout(timer)
    timer = setTimeout(() => go(1), 250)
}

function setType(type) {
    filters.vendor_type = type
    go(1)
}

function go(page) {
    filters.page = page
    load()
}

function sortBy(column) {
    filters.direction = filters.sort === column && filters.direction === 'asc' ? 'desc' : 'asc'
    filters.sort = column
    go(1)
}

function sortIcon(column) {
    if (filters.sort !== column) return 'fa-sort text-muted-pa'
    return filters.direction === 'asc' ? 'fa-sort-asc' : 'fa-sort-desc'
}

function toggle(id) {
    selected.value = selected.value.includes(id) ? selected.value.filter((item) => item !== id) : [...selected.value, id]
}

function toggleAll() {
    const ids = rows.value.map((row) => row.id)
    const all = ids.every((id) => selected.value.includes(id))
    selected.value = all ? selected.value.filter((id) => !ids.includes(id)) : [...new Set([...selected.value, ...ids])]
}

function openModal(id) {
    modal.id = id
    modal.open = true
}

function onSaved() {
    modal.open = false
    load()
}

async function remove(vendor) {
    if (vendor.contracts > 0) {
        toast.error(`${vendor.name} has ${vendor.contracts} ${vendor.contracts === 1 ? 'contract' : 'contracts'} and cannot be deleted.`)
        return
    }
    const ok = await confirmAction({ title: `Delete ${vendor.name}?`, text: 'The vendor is removed permanently.', confirm: 'Delete', danger: true })
    if (!ok) return
    try {
        const response = await contractApi.deleteVendor(vendor.id)
        toast.success(response.message)
        selected.value = selected.value.filter((id) => id !== vendor.id)
        load()
    } catch (error) {
        toast.error(errorMessage(error))
    }
}

async function removeSelected() {
    const count = selected.value.length
    const ok = await confirmAction({ title: `Delete ${count} ${count === 1 ? 'vendor' : 'vendors'}?`, text: 'Vendors with contracts or ledger entries are kept.', confirm: 'Delete', danger: true })
    if (!ok) return
    try {
        const response = await contractApi.deleteVendors(selected.value)
        response.data?.failed?.length ? toast.warning(response.message, { timeout: 8000 }) : toast.success(response.message)
        selected.value = []
        load()
    } catch (error) {
        toast.error(errorMessage(error))
    }
}

onMounted(load)
</script>
