<template>
    <div class="ofx" @click="closePicker">
        <!-- Offer details + totals -->
        <div class="ofx-card">
            <div class="ofx-hero">
                <div class="ofx-fld ofx-fld-name">
                    <label for="ofx-name">Offer name</label>
                    <input id="ofx-name" v-model="form.name" class="ofx-inp" maxlength="255" placeholder="e.g. Eid Festive Sale" :disabled="!canSave" />
                </div>
                <div class="ofx-fld">
                    <label for="ofx-start">Starts</label>
                    <input id="ofx-start" v-model="form.start_date" type="date" class="ofx-inp" :disabled="!canSave" />
                </div>
                <div class="ofx-fld">
                    <label for="ofx-end">Ends</label>
                    <input id="ofx-end" v-model="form.end_date" type="date" class="ofx-inp" :min="form.start_date" :disabled="!canSave" />
                </div>
                <div class="ofx-fld">
                    <label>Status</label>
                    <div class="ofx-seg">
                        <button type="button" :class="{ on: form.status === 'active' }" :disabled="!canSave" @click="form.status = 'active'">Active</button>
                        <button type="button" :class="{ on: form.status === 'disabled' }" :disabled="!canSave" @click="form.status = 'disabled'">Inactive</button>
                    </div>
                </div>
                <div class="ofx-hero-acts">
                    <a :href="listUrl" class="ofx-btn ofx-btn-ghost"><i class="fa fa-times"></i> {{ canSave ? 'Cancel' : 'Back' }}</a>
                    <button v-if="canSave" type="button" class="ofx-btn ofx-btn-pri" :disabled="saving || loading" @click="save">
                        <i class="fa" :class="saving ? 'fa-spinner fa-spin' : 'fa-check'"></i> Save offer
                    </button>
                </div>
            </div>
            <div class="ofx-kpis">
                <div class="ofx-kpi">
                    <span>{{ noun.Many }} on offer</span>
                    <b>{{ stats.count }}<small>in {{ stats.groups }} {{ stats.groups === 1 ? 'category' : 'categories' }}</small></b>
                </div>
                <div class="ofx-kpi">
                    <span>Average discount</span>
                    <b class="ok">{{ stats.averagePercent.toFixed(1) }}%</b>
                </div>
                <div class="ofx-kpi">
                    <span>Customer saves (one of each)</span>
                    <b>{{ formatAmount(stats.saving) }}</b>
                </div>
                <div class="ofx-kpi">
                    <span>Below cost</span>
                    <b :class="{ bad: stats.belowCost }">{{ stats.belowCost }}<small v-if="stats.belowCost">review</small></b>
                </div>
            </div>
        </div>

        <div class="ofx-body">
            <!-- Category rail -->
            <aside class="ofx-card ofx-rail">
                <h6>Categories</h6>
                <button type="button" class="ofx-rail-item" :class="{ on: rail === 'all' }" @click="rail = 'all'">
                    <i class="fa fa-th-large"></i><span>All in offer</span><em>{{ items.length }}</em>
                </button>
                <template v-for="main in categories" :key="main.id">
                    <button type="button" class="ofx-rail-item" :class="{ on: rail === `m:${main.id}` }" @click="rail = `m:${main.id}`">
                        <i class="fa fa-folder-o"></i><span>{{ main.name }}</span><em>{{ inOfferByMain[main.id] || 0 }}/{{ main.count }}</em>
                    </button>
                    <button
                        v-for="sub in main.subs"
                        :key="sub.id"
                        type="button"
                        class="ofx-rail-item ofx-rail-sub"
                        :class="{ on: rail === `s:${main.id}:${sub.id}` }"
                        @click="rail = `s:${main.id}:${sub.id}`"
                    >
                        <span>{{ sub.name }}</span><em>{{ inOfferBySub[sub.id] || 0 }}/{{ sub.count }}</em>
                    </button>
                </template>
                <div v-if="loadingCategories" class="ofx-skel"></div>
            </aside>

            <section class="ofx-main">
                <div class="ofx-card">
                    <!-- Toolbar: search in offer, bulk rule, add products -->
                    <div class="ofx-tool">
                        <div class="ofx-search">
                            <i class="fa fa-search"></i>
                            <input v-model="search" class="ofx-inp" type="search" placeholder="Search name, code, brand…" />
                        </div>
                        <template v-if="canSave">
                            <span class="ofx-eyebrow">Bulk</span>
                            <div class="ofx-inp-group">
                                <input v-model="bulk.value" class="ofx-inp ofx-num ofx-w-sm" inputmode="decimal" @keydown.enter="applyBulk" />
                                <span>{{ bulk.mode === 'percent' ? '%' : 'amt' }}</span>
                            </div>
                            <div class="ofx-seg">
                                <button type="button" :class="{ on: bulk.mode === 'percent' }" @click="bulk.mode = 'percent'">% off</button>
                                <button type="button" :class="{ on: bulk.mode === 'amount' }" @click="bulk.mode = 'amount'">Amount off</button>
                            </div>
                            <select v-model.number="bulk.step" class="ofx-inp ofx-select" title="Round offer prices">
                                <option v-for="option in roundingSteps" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                            <button type="button" class="ofx-btn ofx-btn-pri" :disabled="!visibleItems.length" @click="applyBulk">
                                <i class="fa fa-bolt"></i> {{ applyLabel }}
                            </button>
                            <div class="ofx-picker" @click.stop>
                                <button type="button" class="ofx-btn ofx-btn-soft" @click="togglePicker"><i class="fa fa-plus"></i> Add {{ noun.many }}</button>
                                <div v-if="picker.open" class="ofx-card ofx-picker-menu">
                                    <div class="ofx-search">
                                        <i class="fa" :class="picker.loading ? 'fa-spinner fa-spin' : 'fa-search'"></i>
                                        <input ref="pickerInput" v-model="picker.search" class="ofx-inp" type="search" :placeholder="`Find a ${noun.one} by name, code or barcode`" @input="searchCatalog" />
                                    </div>
                                    <template v-if="picker.search.trim()">
                                        <div class="ofx-mh">
                                            Results
                                            <button v-if="pickerAddable.length > 1" type="button" class="ofx-link-btn" @click="addProducts(pickerAddable)">Add all {{ pickerAddable.length }}</button>
                                        </div>
                                        <button v-for="product in picker.results" :key="product.id" type="button" class="ofx-mi" :disabled="inOffer.has(product.id)" @click="addProducts([product])">
                                            <i class="fa" :class="inOffer.has(product.id) ? 'fa-check' : 'fa-plus'"></i>
                                            <span>{{ product.name }}<small>{{ product.code }} · {{ groupLabel(product) }}</small></span>
                                            <em>{{ formatAmount(product.mrp) }}</em>
                                        </button>
                                        <div v-if="!picker.loading && !picker.results.length" class="ofx-mi-empty">No {{ noun.many }} found.</div>
                                    </template>
                                    <template v-else>
                                        <div class="ofx-mh">Add a whole category at {{ ruleLabel }}</div>
                                        <template v-for="main in categories" :key="main.id">
                                            <button type="button" class="ofx-mi" :disabled="main.count === (inOfferByMain[main.id] || 0)" @click="addCategory(main.id)">
                                                <i class="fa fa-folder-o"></i><span>{{ main.name }}</span><em>{{ remainingLabel(main.count, inOfferByMain[main.id]) }}</em>
                                            </button>
                                            <button v-for="sub in main.subs" :key="sub.id" type="button" class="ofx-mi ofx-mi-sub" :disabled="sub.count === (inOfferBySub[sub.id] || 0)" @click="addCategory(main.id, sub.id)">
                                                <span>{{ sub.name }}</span><em>{{ remainingLabel(sub.count, inOfferBySub[sub.id]) }}</em>
                                            </button>
                                        </template>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Grouped price table -->
                    <div class="ofx-tbl-wrap">
                        <div v-if="loading" class="ofx-pad"><div class="ofx-skel"></div><div class="ofx-skel"></div><div class="ofx-skel"></div><div class="ofx-skel"></div></div>
                        <table v-else-if="visibleItems.length" class="ofx-tbl ofx-tbl-wide">
                            <thead>
                                <tr>
                                    <th class="ofx-c ofx-w-chk"><input type="checkbox" class="ofx-chk" :checked="allVisibleSelected" @change="toggleAll($event.target.checked)" /></th>
                                    <th>{{ noun.Many.slice(0, -1) }}</th>
                                    <th class="ofx-r">MRP</th>
                                    <th class="ofx-r">Cost</th>
                                    <th class="ofx-w-price">Offer price</th>
                                    <th class="ofx-w-pct">Discount</th>
                                    <th>Margin</th>
                                    <th class="ofx-w-chk"></th>
                                </tr>
                            </thead>
                            <tbody v-for="group in groups" :key="group.key">
                                <tr class="ofx-grp">
                                    <td class="ofx-c"><input type="checkbox" class="ofx-chk" :checked="group.items.every((item) => selected.has(item.id))" @change="toggleItems(group.items, $event.target.checked)" /></td>
                                    <td colspan="7">
                                        <div class="ofx-grp-in">
                                            <b>{{ group.label }}</b><span class="ofx-mute">· {{ group.items.length }} {{ group.items.length === 1 ? 'item' : 'items' }}</span>
                                            <span class="ofx-sp"></span>
                                            <template v-if="canSave">
                                                <span class="ofx-eyebrow">Whole group</span>
                                                <div class="ofx-inp-group ofx-inp-sm">
                                                    <input v-model="groupPercent[group.key]" class="ofx-inp ofx-num" inputmode="decimal" placeholder="0" @keydown.enter="applyGroup(group)" />
                                                    <span>% off</span>
                                                </div>
                                                <button type="button" class="ofx-btn ofx-btn-soft ofx-btn-sm" @click="applyGroup(group)">Apply</button>
                                                <button type="button" class="ofx-btn ofx-btn-ghost ofx-btn-sm" title="Remove this group from the offer" @click="removeItems(group.items)"><i class="fa fa-trash-o"></i></button>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-for="item in group.items" :key="item.id" class="ofx-row" :class="{ sel: selected.has(item.id) }">
                                    <td class="ofx-c"><input type="checkbox" class="ofx-chk" :checked="selected.has(item.id)" @change="toggleItems([item], $event.target.checked)" /></td>
                                    <td>
                                        <div class="ofx-pname">
                                            <img v-if="item.thumbnail" :src="item.thumbnail" alt="" class="ofx-thumb" loading="lazy" />
                                            <span v-else class="ofx-thumb" :style="{ background: thumbColor(item.id) }">{{ initials(item) }}</span>
                                            <div>
                                                <b>{{ item.name }}</b>
                                                <small>{{ [item.code, item.brand].filter(Boolean).join(' · ') }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="ofx-r ofx-tnum">{{ formatAmount(item.mrp) }}</td>
                                    <td class="ofx-r ofx-tnum ofx-mute">{{ formatAmount(item.cost) }}</td>
                                    <td>
                                        <input
                                            class="ofx-inp ofx-num ofx-price"
                                            :class="{ below: item.amount < item.cost, custom: item.custom }"
                                            :value="item.amount.toFixed(2)"
                                            inputmode="decimal"
                                            :disabled="!canSave"
                                            @change="setPrice(item, $event.target.value)"
                                            @keydown.enter="$event.target.blur()"
                                        />
                                    </td>
                                    <td>
                                        <div class="ofx-inp-group">
                                            <input
                                                class="ofx-inp ofx-num ofx-w-sm"
                                                :value="percentOff(item.mrp, item.amount).toFixed(1)"
                                                inputmode="decimal"
                                                :disabled="!canSave"
                                                @change="setPercent(item, $event.target.value)"
                                                @keydown.enter="$event.target.blur()"
                                            />
                                            <span>%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span v-if="item.amount < item.cost" class="ofx-pill bad"><i class="fa fa-exclamation-triangle"></i> Below cost</span>
                                        <span v-else-if="item.amount > item.mrp" class="ofx-pill warn">Above MRP</span>
                                        <span v-else class="ofx-pill" :class="marginPercent(item.cost, item.amount) < 15 ? 'warn' : 'ok'">{{ marginPercent(item.cost, item.amount).toFixed(0) }}% margin</span>
                                    </td>
                                    <td class="ofx-c">
                                        <button v-if="canSave" type="button" class="ofx-btn ofx-btn-ghost ofx-icon" title="Remove" @click="removeItems([item])"><i class="fa fa-times"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div v-else class="ofx-empty">
                            <i class="fa fa-tags"></i>
                            <template v-if="search.trim()">Nothing in this offer matches “{{ search }}”.</template>
                            <template v-else-if="railCategory">
                                No {{ noun.many }} from {{ railCategory.name }} in this offer yet.
                                <div v-if="canSave" class="mt-3">
                                    <button type="button" class="ofx-btn ofx-btn-soft" :disabled="adding" @click="addCategory(railCategory.mainId, railCategory.subId)">
                                        <i class="fa" :class="adding ? 'fa-spinner fa-spin' : 'fa-plus'"></i> Add all {{ railCategory.count }} at {{ ruleLabel }}
                                    </button>
                                </div>
                            </template>
                            <template v-else>
                                This offer has no {{ noun.many }} yet.<br />
                                <span v-if="canSave">Pick a category on the left, or use <b>Add {{ noun.many }}</b>.</span>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Selection bar -->
                <div v-if="canSave && selectedCount" class="ofx-selbar">
                    <b>{{ selectedCount }} selected</b>
                    <div class="ofx-inp-group">
                        <input v-model="selection.percent" class="ofx-inp ofx-num ofx-w-sm" inputmode="decimal" placeholder="0" @keydown.enter="applySelected('percent')" />
                        <span>% off</span>
                    </div>
                    <button type="button" class="ofx-btn ofx-btn-line" @click="applySelected('percent')">Apply %</button>
                    <div class="ofx-inp-group">
                        <input v-model="selection.price" class="ofx-inp ofx-num ofx-w-md" inputmode="decimal" placeholder="0.00" @keydown.enter="applySelected('price')" />
                        <span>price</span>
                    </div>
                    <button type="button" class="ofx-btn ofx-btn-line" @click="applySelected('price')">Set price</button>
                    <span class="ofx-sp"></span>
                    <button type="button" class="ofx-btn ofx-btn-line" @click="removeItems(items.filter((item) => selected.has(item.id)))"><i class="fa fa-trash-o"></i> Remove</button>
                    <button type="button" class="ofx-btn ofx-btn-line" @click="selected.clear()">Clear</button>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useToast } from 'vue-toastification'
import { errorMessage, offerApi } from './api.js'
import { applyRule, formatAmount, marginPercent, percentOff, roundPrice, roundingSteps } from './pricing.js'

const root = document.getElementById('product-offer-editor')
const permissions = JSON.parse(root?.dataset.permissions || '{}')
const type = root?.dataset.type === 'service' ? 'service' : 'product'
const noun = type === 'service' ? { one: 'service', many: 'services', Many: 'Services' } : { one: 'product', many: 'products', Many: 'Products' }
const listUrl = root?.dataset.listUrl || '/product/offer'
const editUrlTemplate = root?.dataset.editUrl || '/product/offer/edit/__ID__'

const toast = useToast()
const offerId = ref(Number(root?.dataset.offerId) || null)
const canSave = computed(() => (offerId.value ? permissions.edit : permissions.create))

const today = new Date()
const isoDay = (date) => new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 10)

const form = reactive({
    name: '',
    start_date: isoDay(today),
    end_date: isoDay(new Date(today.getFullYear(), today.getMonth(), today.getDate() + 7)),
    status: 'active',
})

/** Offer lines: the catalogue row plus `amount` (offer price) and `custom` (hand-edited). */
const items = ref([])
const categories = ref([])
const rail = ref('all')
const search = ref('')
const bulk = reactive({ mode: 'percent', value: 10, step: 0 })
const groupPercent = reactive({})
const selection = reactive({ percent: '', price: '' })
const selected = reactive(new Set())
const picker = reactive({ open: false, search: '', results: [], loading: false })
const pickerInput = ref(null)

const loading = ref(false)
const loadingCategories = ref(false)
const saving = ref(false)
const adding = ref(false)
const dirty = ref(false)

const inOffer = computed(() => new Set(items.value.map((item) => item.id)))

const countBy = (field) =>
    items.value.reduce((counts, item) => {
        const key = item[field] ?? 0
        counts[key] = (counts[key] || 0) + 1
        return counts
    }, {})
const inOfferByMain = computed(() => countBy('main_category_id'))
const inOfferBySub = computed(() => countBy('sub_category_id'))

function groupKey(product) {
    return `${product.main_category_id}:${product.sub_category_id ?? 0}`
}

function groupLabel(product) {
    return product.sub_category ? `${product.main_category} / ${product.sub_category}` : product.main_category
}

function matchesRail(item) {
    if (rail.value === 'all') return true
    const [kind, mainId, subId] = rail.value.split(':')
    if (Number(mainId) !== item.main_category_id) return false
    return kind === 'm' || Number(subId) === item.sub_category_id
}

/** The category picked in the rail, for the empty state's "Add all" button. */
const railCategory = computed(() => {
    if (rail.value === 'all') return null
    const [kind, mainId, subId] = rail.value.split(':').map((part, index) => (index ? Number(part) : part))
    const main = categories.value.find((category) => category.id === mainId)
    if (!main) return null
    if (kind === 'm') return { mainId, subId: null, name: main.name, count: main.count }
    const sub = main.subs.find((category) => category.id === subId)
    return sub ? { mainId, subId, name: sub.name, count: sub.count } : null
})

const visibleItems = computed(() => {
    const term = search.value.trim().toLowerCase()
    return items.value.filter((item) => matchesRail(item) && (!term || `${item.name} ${item.code} ${item.brand ?? ''}`.toLowerCase().includes(term)))
})

const groups = computed(() => {
    const byKey = new Map()
    for (const item of visibleItems.value) {
        const key = groupKey(item)
        if (!byKey.has(key)) byKey.set(key, { key, label: groupLabel(item), items: [] })
        byKey.get(key).items.push(item)
    }
    return [...byKey.values()].sort((a, b) => a.label.localeCompare(b.label))
})

const stats = computed(() => {
    let mrp = 0
    let offer = 0
    let percentSum = 0
    let belowCost = 0
    for (const item of items.value) {
        mrp += item.mrp
        offer += item.amount
        percentSum += percentOff(item.mrp, item.amount)
        if (item.amount < item.cost) belowCost++
    }
    const count = items.value.length
    return {
        count,
        groups: new Set(items.value.map(groupKey)).size,
        averagePercent: count ? percentSum / count : 0,
        saving: mrp - offer,
        belowCost,
    }
})

const selectedCount = computed(() => items.value.filter((item) => selected.has(item.id)).length)
const allVisibleSelected = computed(() => visibleItems.value.length > 0 && visibleItems.value.every((item) => selected.has(item.id)))
const pickerAddable = computed(() => picker.results.filter((product) => !inOffer.value.has(product.id)))
const ruleLabel = computed(() => (bulk.mode === 'percent' ? `${Number(bulk.value) || 0}% off` : `${formatAmount(bulk.value)} off`))
const applyLabel = computed(() => {
    if (rail.value === 'all' && !search.value.trim()) return `Apply to all ${visibleItems.value.length}`
    return `Apply to ${visibleItems.value.length} shown`
})

function remainingLabel(total, inOfferCount = 0) {
    const remaining = total - (inOfferCount || 0)
    return remaining > 0 ? `+${remaining}` : 'all in'
}

const palette = ['#2563eb', '#0f766e', '#7c3aed', '#db2777', '#ea580c', '#0891b2', '#65a30d', '#9333ea', '#b45309', '#475569']
const thumbColor = (id) => palette[id % palette.length]
const initials = (item) => (item.brand || item.name || '?').slice(0, 2).toUpperCase()

/* ---------- pricing ---------- */

function ruleFor(product) {
    return applyRule(product.mrp, bulk.mode, bulk.value, bulk.step)
}

function setPrice(item, value) {
    item.amount = roundPrice(value)
    item.custom = true
}

function setPercent(item, value) {
    item.amount = applyRule(item.mrp, 'percent', value)
    item.custom = true
}

function applyTo(targets, mode, value) {
    for (const item of targets) {
        item.amount = applyRule(item.mrp, mode, value, mode === 'price' ? 0 : bulk.step)
        item.custom = false
    }
}

function applyBulk() {
    applyTo(visibleItems.value, bulk.mode, bulk.value)
    toast.success(`${ruleLabel.value} applied to ${visibleItems.value.length} ${noun.many}.`)
}

function applyGroup(group) {
    applyTo(group.items, 'percent', groupPercent[group.key] || 0)
}

function applySelected(mode) {
    const targets = items.value.filter((item) => selected.has(item.id))
    applyTo(targets, mode, mode === 'price' ? selection.price : selection.percent)
}

/* ---------- membership ---------- */

function addProducts(products) {
    const fresh = products.filter((product) => !inOffer.value.has(product.id))
    items.value.push(...fresh.map((product) => ({ ...product, amount: ruleFor(product), custom: false })))
    if (fresh.length > 1) toast.success(`Added ${fresh.length} ${noun.many} at ${ruleLabel.value}.`)
    return fresh.length
}

async function addCategory(mainId, subId = null) {
    adding.value = true
    try {
        const result = await offerApi.products({ type, main_category_id: mainId, sub_category_id: subId ?? undefined })
        const added = addProducts(result.products)
        if (added === 1) toast.success(`Added 1 ${noun.one} at ${ruleLabel.value}.`)
        if (!added) toast.info(`Every ${noun.one} in that category is already in the offer.`)
        if (result.truncated) toast.warning(`That category is very large; only the first 1000 ${noun.many} were added.`)
        rail.value = subId ? `s:${mainId}:${subId}` : `m:${mainId}`
        picker.open = false
    } catch (error) {
        toast.error(errorMessage(error))
    } finally {
        adding.value = false
    }
}

function removeItems(targets) {
    const ids = new Set(targets.map((item) => item.id))
    items.value = items.value.filter((item) => !ids.has(item.id))
    ids.forEach((id) => selected.delete(id))
}

function toggleItems(targets, checked) {
    targets.forEach((item) => (checked ? selected.add(item.id) : selected.delete(item.id)))
}

function toggleAll(checked) {
    toggleItems(visibleItems.value, checked)
}

/* ---------- product picker ---------- */

let searchTimer = null
let searchController = null

function togglePicker() {
    picker.open = !picker.open
    if (picker.open) nextTick(() => pickerInput.value?.focus())
}

function closePicker() {
    picker.open = false
}

function searchCatalog() {
    clearTimeout(searchTimer)
    const term = picker.search.trim()
    if (!term) {
        picker.results = []
        return
    }
    searchTimer = setTimeout(async () => {
        searchController?.abort()
        searchController = new AbortController()
        picker.loading = true
        try {
            picker.results = (await offerApi.products({ type, search: term }, searchController.signal)).products
        } catch (error) {
            const message = errorMessage(error)
            if (message) toast.error(message)
        } finally {
            picker.loading = false
        }
    }, 250)
}

/* ---------- load + save ---------- */

async function loadCategories() {
    loadingCategories.value = true
    try {
        categories.value = await offerApi.categories({ type })
    } catch (error) {
        toast.error(errorMessage(error))
    } finally {
        loadingCategories.value = false
    }
}

async function loadOffer() {
    if (!offerId.value) return
    loading.value = true
    try {
        const offer = await offerApi.show(offerId.value)
        Object.assign(form, { name: offer.name, start_date: offer.start_date, end_date: offer.end_date, status: offer.status })
        items.value = offer.items.map((item) => ({ ...item, amount: roundPrice(item.amount), custom: false }))
    } catch (error) {
        toast.error(errorMessage(error))
    } finally {
        loading.value = false
    }
}

async function save() {
    if (!form.name.trim()) return toast.error('Give the offer a name.')
    if (!items.value.length) return toast.error(`Add at least one ${noun.one} to the offer.`)
    if (form.end_date < form.start_date) return toast.error('The offer must end on or after its start date.')

    const payload = { ...form, type, items: items.value.map((item) => ({ product_id: item.id, amount: item.amount })) }
    saving.value = true
    try {
        const response = offerId.value ? await offerApi.update(offerId.value, payload) : await offerApi.store(payload)
        toast.success(response.message)
        dirty.value = false
        if (!offerId.value) {
            offerId.value = response.data.id
            history.replaceState(null, '', editUrlTemplate.replace('__ID__', offerId.value))
        }
    } catch (error) {
        toast.error(errorMessage(error))
    } finally {
        saving.value = false
    }
}

function warnUnsaved(event) {
    if (!dirty.value) return
    event.preventDefault()
    event.returnValue = ''
}

onMounted(async () => {
    await Promise.all([loadCategories(), loadOffer()])
    watch([form, items], () => (dirty.value = true), { deep: true })
    window.addEventListener('beforeunload', warnUnsaved)
})

onBeforeUnmount(() => window.removeEventListener('beforeunload', warnUnsaved))
</script>
