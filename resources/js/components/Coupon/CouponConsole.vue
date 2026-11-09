<template>
    <div class="ofx">
        <div class="ofx-card">
            <div class="ofx-kpis cpx-kpis">
                <div class="ofx-kpi"><span>Active now</span><b class="ok">{{ summary.states.active ?? 0 }}</b></div>
                <div class="ofx-kpi"><span>Times used</span><b>{{ summary.times_used.toLocaleString('en-US') }}</b></div>
                <div class="ofx-kpi"><span>Discount given</span><b>{{ money(summary.discount_given) }}</b></div>
            </div>

            <div class="ofx-tool">
                <div class="ofx-search">
                    <i class="fa fa-search"></i>
                    <input v-model="search" class="ofx-inp" type="search" placeholder="Search code or title…" aria-label="Search coupons" @input="debouncedLoad" />
                </div>
                <div class="ofx-seg" role="group" aria-label="Filter by status">
                    <button v-for="option in filters" :key="option.value" type="button" :class="{ on: state === option.value }" @click="setState(option.value)">
                        {{ option.label }} <span class="ofx-mute">{{ option.value ? (summary.states[option.value] ?? 0) : summary.all }}</span>
                    </button>
                </div>
                <span class="ofx-sp"></span>
                <button v-if="consoleConfig.permissions.create" type="button" class="ofx-btn ofx-btn-pri" @click="openForm(null)"><i class="fa fa-plus"></i> New coupon</button>
            </div>

            <div class="ofx-tbl-wrap">
                <table class="ofx-tbl ofx-tbl-wide">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Discount</th>
                            <th>Min. bag</th>
                            <th>Applies to</th>
                            <th>Used</th>
                            <th>Valid until</th>
                            <th>Status</th>
                            <th v-if="consoleConfig.permissions.edit">On</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading && !coupons.length">
                            <td colspan="9"><div class="ofx-skel"></div><div class="ofx-skel"></div><div class="ofx-skel"></div></td>
                        </tr>
                        <tr v-else-if="!coupons.length">
                            <td colspan="9">
                                <div class="ofx-empty">
                                    <i class="fa fa-ticket"></i>
                                    {{ search || state ? 'No coupons match.' : 'No coupons yet.' }}
                                    <div v-if="consoleConfig.permissions.create && !search && !state" class="mt-3">
                                        <button type="button" class="ofx-btn ofx-btn-soft" @click="openForm(null)"><i class="fa fa-plus"></i> Create the first coupon</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr v-for="coupon in coupons" :key="coupon.id" class="ofx-row">
                            <td>
                                <span class="cpx-tag">{{ coupon.code }}</span>
                                <i v-if="!coupon.is_searchable" class="fa fa-lock ofx-mute cpx-lock" title="Private: full code only"></i>
                                <small class="cpx-sub">{{ coupon.title }}</small>
                            </td>
                            <td>
                                <b>{{ discountText(coupon) }}</b>
                                <small v-if="coupon.max_discount" class="cpx-sub">up to {{ money(coupon.max_discount) }}</small>
                            </td>
                            <td class="ofx-tnum" :class="{ 'ofx-mute': !coupon.min_bag_total }">{{ coupon.min_bag_total ? money(coupon.min_bag_total) : 'None' }}</td>
                            <td class="cpx-scope" :title="scopeText(coupon)">{{ scopeText(coupon) }}</td>
                            <td class="ofx-tnum">
                                {{ coupon.used.toLocaleString('en-US') }}<template v-if="coupon.usage_limit"> / {{ coupon.usage_limit.toLocaleString('en-US') }}</template>
                                <div v-if="coupon.usage_limit" class="cpx-bar"><i :style="{ width: `${Math.min(100, (coupon.used / coupon.usage_limit) * 100)}%` }"></i></div>
                            </td>
                            <td class="ofx-nowrap">{{ coupon.ends_on ? formatDay(coupon.ends_on) : 'No end date' }}</td>
                            <td><span class="ofx-pill" :class="stateMeta[coupon.state].tone"><span class="ofx-dot"></span>{{ stateMeta[coupon.state].label }}</span></td>
                            <td v-if="consoleConfig.permissions.edit">
                                <CouponSwitch :model-value="coupon.is_active" :label="`Turn ${coupon.code} on or off`" @update:model-value="toggle(coupon, $event)" />
                            </td>
                            <td class="ofx-r ofx-nowrap">
                                <button v-if="consoleConfig.permissions.edit" type="button" class="ofx-btn ofx-btn-ghost ofx-icon" title="Edit" :aria-label="`Edit ${coupon.code}`" @click="openForm(coupon)"><i class="fa fa-pencil"></i></button>
                                <button v-if="consoleConfig.permissions.create" type="button" class="ofx-btn ofx-btn-ghost ofx-icon ofx-ml" title="Duplicate" :aria-label="`Duplicate ${coupon.code}`" @click="duplicate(coupon)"><i class="fa fa-files-o"></i></button>
                                <button
                                    v-if="consoleConfig.permissions.delete"
                                    type="button"
                                    class="ofx-btn ofx-btn-ghost ofx-icon ofx-ml"
                                    :disabled="coupon.used > 0"
                                    :title="coupon.used > 0 ? 'Customers have used this coupon. Pause it instead.' : 'Delete'"
                                    :aria-label="`Delete ${coupon.code}`"
                                    @click="remove(coupon)"
                                ><i class="fa fa-trash-o"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="lastPage > 1" class="ofx-pager">
                <span class="ofx-mute">{{ total }} coupons</span>
                <span class="ofx-sp"></span>
                <button type="button" class="ofx-btn ofx-btn-ghost ofx-btn-sm" :disabled="page <= 1" aria-label="Previous page" @click="goTo(page - 1)"><i class="fa fa-chevron-left"></i></button>
                <span class="ofx-tnum">{{ page }} / {{ lastPage }}</span>
                <button type="button" class="ofx-btn ofx-btn-ghost ofx-btn-sm" :disabled="page >= lastPage" aria-label="Next page" @click="goTo(page + 1)"><i class="fa fa-chevron-right"></i></button>
            </div>
        </div>

        <CouponForm v-if="formOpen" :key="formKey" :coupon="editing" @close="formOpen = false" @saved="onSaved" />
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { errorMessage } from '../Product/Offer/api.js'
import { consoleConfig, couponApi, formatDay, money } from './api.js'
import CouponForm from './CouponForm.vue'
import CouponSwitch from './CouponSwitch.vue'

const toast = useToast()

const stateMeta = {
    active: { label: 'Active', tone: 'ok' },
    scheduled: { label: 'Scheduled', tone: 'acc' },
    paused: { label: 'Paused', tone: 'mute' },
    expired: { label: 'Expired', tone: 'bad' },
    used_up: { label: 'Used up', tone: 'warn' },
}
const filters = [
    { value: '', label: 'All' },
    ...Object.entries(stateMeta).map(([value, meta]) => ({ value, label: meta.label })),
]

const coupons = ref([])
const summary = ref({ all: 0, states: {}, times_used: 0, discount_given: 0 })
const search = ref('')
const state = ref('')
const page = ref(1)
const lastPage = ref(1)
const total = ref(0)
const loading = ref(false)
const formOpen = ref(false)
const formKey = ref(0)
const editing = ref(null)
let controller = null
let timer = null

const discountText = (coupon) => (coupon.discount_type === 'percent' ? `${coupon.discount_value}% off` : `${money(coupon.discount_value)} off`)
function scopeText(coupon) {
    const covered = coupon.applies_to === 'all' ? 'Everything' : coupon.items.map((item) => item.name).join(', ')
    const left = coupon.excluded_items.length
    return left ? `${covered}, except ${left} product${left > 1 ? 's' : ''}` : covered
}

async function load() {
    controller?.abort()
    controller = new AbortController()
    loading.value = true
    try {
        const result = await couponApi.list({ search: search.value, state: state.value, page: page.value }, controller.signal)
        coupons.value = result.coupons
        summary.value = result.summary
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

function openForm(coupon) {
    editing.value = coupon
    formKey.value += 1
    formOpen.value = true
}

function duplicate(coupon) {
    const { id, ...copy } = coupon
    openForm({ ...copy, code: `${coupon.code}-COPY`.slice(0, 24) })
}

function onSaved() {
    formOpen.value = false
    load()
}

async function toggle(coupon, isActive) {
    coupon.is_active = isActive
    try {
        toast.success((await couponApi.toggle(coupon.id, isActive)).message)
        load()
    } catch (error) {
        coupon.is_active = !isActive
        toast.error(errorMessage(error))
    }
}

async function remove(coupon) {
    const confirmed = window.Swal
        ? (await window.Swal.fire({ title: `Delete ${coupon.code}?`, text: 'Customers will no longer be able to use it.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete' })).isConfirmed
        : window.confirm(`Delete ${coupon.code}?`)
    if (!confirmed) return

    try {
        toast.success((await couponApi.destroy(coupon.id)).message)
        load()
    } catch (error) {
        toast.error(errorMessage(error))
    }
}

onMounted(load)
</script>
