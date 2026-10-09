<template>
    <div class="ofx">
        <div class="ofx-card">
            <div class="ofx-tool">
                <div class="ofx-search">
                    <i class="fa fa-search"></i>
                    <input v-model="search" class="ofx-inp" type="search" placeholder="Search offers…" @input="debouncedLoad" />
                </div>
                <div class="ofx-seg">
                    <button v-for="option in states" :key="option.value" type="button" :class="{ on: state === option.value }" @click="setState(option.value)">{{ option.label }}</button>
                </div>
                <span class="ofx-sp"></span>
                <a v-if="permissions.create" :href="createUrl" class="ofx-btn ofx-btn-pri"><i class="fa fa-plus"></i> New offer</a>
            </div>

            <div class="ofx-tbl-wrap">
                <table class="ofx-tbl">
                    <thead>
                        <tr>
                            <th>Offer</th>
                            <th>Runs</th>
                            <th class="ofx-r">{{ type === 'service' ? 'Services' : 'Products' }}</th>
                            <th>Status</th>
                            <th>Created by</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading && !offers.length">
                            <td colspan="6"><div class="ofx-skel"></div><div class="ofx-skel"></div><div class="ofx-skel"></div></td>
                        </tr>
                        <tr v-else-if="!offers.length">
                            <td colspan="6">
                                <div class="ofx-empty">
                                    <i class="fa fa-tags"></i>
                                    {{ search || state ? 'No offers match.' : 'No offers yet.' }}
                                    <div v-if="permissions.create && !search && !state" class="mt-3"><a :href="createUrl" class="ofx-btn ofx-btn-soft"><i class="fa fa-plus"></i> Create the first offer</a></div>
                                </div>
                            </td>
                        </tr>
                        <tr v-for="offer in offers" :key="offer.id" class="ofx-row">
                            <td>
                                <a v-if="permissions.edit" :href="editUrl(offer.id)" class="ofx-link">{{ offer.name }}</a>
                                <b v-else>{{ offer.name }}</b>
                            </td>
                            <td class="ofx-nowrap">{{ formatDay(offer.start_date) }} <i class="fa fa-long-arrow-right ofx-mute"></i> {{ formatDay(offer.end_date) }}</td>
                            <td class="ofx-r ofx-tnum">{{ offer.products_count }}</td>
                            <td><span class="ofx-pill" :class="stateMeta[offer.state].tone"><span class="ofx-dot"></span>{{ stateMeta[offer.state].label }}</span></td>
                            <td class="ofx-mute">{{ offer.created_by || '—' }}</td>
                            <td class="ofx-r ofx-nowrap">
                                <a v-if="permissions.edit" :href="editUrl(offer.id)" class="ofx-btn ofx-btn-ghost ofx-btn-sm"><i class="fa fa-pencil"></i> Edit</a>
                                <button v-if="permissions.delete" type="button" class="ofx-btn ofx-btn-ghost ofx-btn-sm ofx-ml" title="Delete" @click="remove(offer)"><i class="fa fa-trash-o"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="lastPage > 1" class="ofx-pager">
                <span class="ofx-mute">{{ total }} offers</span>
                <span class="ofx-sp"></span>
                <button type="button" class="ofx-btn ofx-btn-ghost ofx-btn-sm" :disabled="page <= 1" @click="goTo(page - 1)"><i class="fa fa-chevron-left"></i></button>
                <span class="ofx-tnum">{{ page }} / {{ lastPage }}</span>
                <button type="button" class="ofx-btn ofx-btn-ghost ofx-btn-sm" :disabled="page >= lastPage" @click="goTo(page + 1)"><i class="fa fa-chevron-right"></i></button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { errorMessage, offerApi } from './api.js'

const root = document.getElementById('product-offer-list')
const permissions = JSON.parse(root?.dataset.permissions || '{}')
const type = root?.dataset.type || 'product'
const createUrl = root?.dataset.createUrl || '/product/offer/create'
const editUrl = (id) => (root?.dataset.editUrl || '/product/offer/edit/__ID__').replace('__ID__', id)

const toast = useToast()

const states = [
    { value: '', label: 'All' },
    { value: 'running', label: 'Running' },
    { value: 'scheduled', label: 'Scheduled' },
    { value: 'expired', label: 'Expired' },
    { value: 'disabled', label: 'Inactive' },
]
const stateMeta = {
    running: { label: 'Running', tone: 'ok' },
    scheduled: { label: 'Scheduled', tone: 'acc' },
    expired: { label: 'Expired', tone: 'mute' },
    disabled: { label: 'Inactive', tone: 'warn' },
}

const offers = ref([])
const search = ref('')
const state = ref('')
const page = ref(1)
const lastPage = ref(1)
const total = ref(0)
const loading = ref(false)
let controller = null
let timer = null

function formatDay(date) {
    return new Date(`${date}T00:00`).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

async function load() {
    controller?.abort()
    controller = new AbortController()
    loading.value = true
    try {
        const result = await offerApi.list({ type, search: search.value, state: state.value, page: page.value }, controller.signal)
        offers.value = result.offers
        lastPage.value = result.last_page
        total.value = result.total
    } catch (error) {
        const message = errorMessage(error)
        if (message) toast.error(message)
    } finally {
        loading.value = false
    }
}

function debouncedLoad() {
    clearTimeout(timer)
    timer = setTimeout(() => {
        page.value = 1
        load()
    }, 300)
}

function setState(value) {
    state.value = value
    page.value = 1
    load()
}

function goTo(next) {
    page.value = next
    load()
}

async function remove(offer) {
    const confirmed = window.Swal
        ? (await window.Swal.fire({
              title: `Delete “${offer.name}”?`,
              text: `${offer.products_count} ${type === 'service' ? 'services' : 'products'} go back to their normal price.`,
              icon: 'warning',
              showCancelButton: true,
              confirmButtonText: 'Delete',
          })).isConfirmed
        : window.confirm(`Delete “${offer.name}”?`)
    if (!confirmed) return

    try {
        const response = await offerApi.destroy(offer.id)
        toast.success(response.message)
        load()
    } catch (error) {
        toast.error(errorMessage(error))
    }
}

onMounted(load)
</script>
