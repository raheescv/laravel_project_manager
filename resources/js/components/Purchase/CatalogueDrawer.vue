<template>
    <Teleport to="body">
        <div class="pcx pcx-cat-root">
            <div class="pcx-cat-scrim" :class="{ on: isOpen }" @click="close"></div>
            <aside class="pcx-cat" :class="{ on: isOpen }" aria-label="Product catalogue" :aria-hidden="!isOpen">
                <div class="pcx-cat-hd">
                    <div class="pcx-ci pcx-cat-ico"><i class="fa fa-th-list"></i></div>
                    <div class="pcx-cat-ttl">
                        <h3>Product catalogue</h3>
                        <p>Filter any column · expand a family · type quantities · add in one go</p>
                    </div>
                    <div class="pcx-cat-mode">
                        <button type="button" :class="{ on: group }" @click="setGroup(true)"><i class="fa fa-sitemap"></i> Families</button>
                        <button type="button" :class="{ on: !group }" @click="setGroup(false)"><i class="fa fa-list"></i> Flat list</button>
                    </div>
                    <button type="button" class="pcx-del" title="Close (Esc)" @click="close"><i class="fa fa-times"></i></button>
                </div>

                <div ref="body" class="pcx-cat-body">
                    <table class="pcx-cat-tbl">
                        <thead>
                            <tr class="l">
                                <th style="width:34px"></th>
                                <th>Product</th>
                                <th>SKU / Code</th>
                                <th class="pcx-cat-hs">Barcode</th>
                                <th>Colour</th>
                                <th>Size</th>
                                <th class="e">Stock</th>
                                <th class="e pcx-cat-hs">Cost</th>
                                <th class="e" style="width:118px">Order Qty</th>
                            </tr>
                            <tr class="f">
                                <th></th>
                                <th v-for="column in filterColumns" :key="column.key" :class="{ 'pcx-cat-hs': column.key === 'barcode' }">
                                    <div class="pcx-cat-flt" :class="{ on: filters[column.key] }">
                                        <i class="fa" :class="column.icon"></i>
                                        <input v-model="filters[column.key]"
                                            :placeholder="column.placeholder" :style="column.key === 'size' ? 'max-width:90px' : ''"
                                            @keydown.enter.prevent />
                                    </div>
                                </th>
                                <th></th>
                                <th class="pcx-cat-hs"></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody v-if="group">
                            <template v-for="family in rows" :key="family.id">
                                <tr class="fam">
                                    <td>
                                        <button type="button" class="pcx-cat-cb" :class="familyTickClass(family)" @click="toggleFamily(family)">
                                            <i class="fa" :class="familyPickedCount(family) && familyPickedCount(family) < family.members.length ? 'fa-minus' : 'fa-check'"></i>
                                        </button>
                                    </td>
                                    <td>
                                        <div class="pcx-cat-nm">
                                            <button v-if="family.is_family" type="button" class="pcx-cat-caret" :class="{ open: isExpanded(family) }" @click="toggleExpand(family)">
                                                <i class="fa fa-chevron-right"></i>
                                            </button>
                                            <span v-else class="pcx-cat-caret-gap"></span>
                                            <div class="pcx-cat-th" :class="{ lt: isLightColour(family.members[0]?.colour_hex) }" :style="thumbStyle(family.members[0])">
                                                <img v-if="family.thumbnail" :src="family.thumbnail" alt="" loading="lazy" />
                                                <span v-else>{{ initials(family.name) }}</span>
                                            </div>
                                            <div class="pcx-cat-nmt">
                                                <b>{{ family.name }}</b>
                                                <small>
                                                    <template v-if="family.brand">{{ family.brand }} · </template>
                                                    <template v-if="family.is_family">{{ family.members.length < family.variant_count ? `${family.members.length} of ${family.variant_count}` : family.variant_count }} variants</template>
                                                    <template v-else>Single item</template>
                                                    <span v-if="familyPieces(family)" class="pcx-cat-picked"> · {{ formatQty(familyPieces(family)) }} pcs picked</span>
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="mono">{{ family.is_family ? family.code : family.members[0]?.code }}</td>
                                    <td class="mono pcx-cat-hs">{{ family.is_family ? '—' : family.members[0]?.barcode }}</td>
                                    <td>
                                        <span class="pcx-cat-dots" :title="familyColours(family).map(colour => colour.name).join(', ')">
                                            <span v-for="colour in familyColours(family).slice(0, SWATCH_LIMIT)" :key="colour.name" class="pcx-cat-sw" :style="{ '--c': colour.hex }"></span>
                                            <span v-if="familyColours(family).length > SWATCH_LIMIT" class="pcx-cat-more-sw">+{{ familyColours(family).length - SWATCH_LIMIT }}</span>
                                        </span>
                                        <span v-if="!family.is_family && family.members[0]?.colour" class="pcx-cat-cname">{{ family.members[0].colour }}</span>
                                    </td>
                                    <td class="mono pcx-cat-sz">{{ familySizeRange(family) }}</td>
                                    <td class="e"><span class="pcx-cat-stk" :class="stockClass(familyStock(family))">{{ formatQty(familyStock(family)) }}</span></td>
                                    <td class="e mono pcx-cat-hs">{{ formatNumber(family.members[0]?.cost ?? 0) }}</td>
                                    <td class="e">
                                        <div v-if="family.is_family" class="pcx-cat-step" :class="{ on: familyPieces(family) }" title="Sets every variant shown">
                                            <button type="button" @click="stepFamily(family, -1)"><i class="fa fa-minus"></i></button>
                                            <input type="number" min="0" placeholder="all" :value="familyCommonQty(family)"
                                                @change="setFamilyQty(family, $event.target.value)" @keydown.enter.prevent="$event.target.blur()" />
                                            <button type="button" @click="stepFamily(family, 1)"><i class="fa fa-plus"></i></button>
                                        </div>
                                        <div v-else class="pcx-cat-step" :class="{ on: qtyOf(family.members[0]) }">
                                            <button type="button" @click="step(family.members[0], -1)"><i class="fa fa-minus"></i></button>
                                            <input type="number" min="0" placeholder="0" :value="qtyOf(family.members[0]) || ''"
                                                @change="setQty(family.members[0], $event.target.value)" @keydown.enter.prevent="$event.target.blur()" />
                                            <button type="button" @click="step(family.members[0], 1)"><i class="fa fa-plus"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <template v-if="family.is_family && isExpanded(family)">
                                    <tr v-for="member in sortedMembers(family)" :key="member.id" class="var" :class="{ sel: qtyOf(member) }">
                                        <td>
                                            <button type="button" class="pcx-cat-cb" :class="{ on: qtyOf(member) }" @click="toggleMember(member)"><i class="fa fa-check"></i></button>
                                        </td>
                                        <td class="nmc">
                                            <div class="pcx-cat-nm">
                                                <div class="pcx-cat-th sm" :class="{ lt: isLightColour(member.colour_hex) }" :style="thumbStyle(member)">
                                                    <img v-if="member.thumbnail" :src="member.thumbnail" alt="" loading="lazy" />
                                                    <span v-else>{{ member.size || '·' }}</span>
                                                </div>
                                                <small class="pcx-cat-vl">{{ member.variant || 'Original' }}</small>
                                            </div>
                                        </td>
                                        <td class="mono">{{ member.code }}</td>
                                        <td class="mono pcx-cat-hs">{{ member.barcode }}</td>
                                        <td>
                                            <span v-if="member.colour" class="pcx-cat-pill"><span class="pcx-cat-sw" :style="{ '--c': member.colour_hex }"></span>{{ member.colour }}</span>
                                        </td>
                                        <td><b>{{ member.size }}</b></td>
                                        <td class="e"><span class="pcx-cat-stk" :class="stockClass(member.stock)">{{ formatQty(member.stock) }}</span></td>
                                        <td class="e mono pcx-cat-hs">{{ formatNumber(member.cost) }}</td>
                                        <td class="e">
                                            <div class="pcx-cat-step" :class="{ on: qtyOf(member) }">
                                                <button type="button" @click="step(member, -1)"><i class="fa fa-minus"></i></button>
                                                <input type="number" min="0" placeholder="0" :value="qtyOf(member) || ''"
                                                    @change="setQty(member, $event.target.value)" @keydown.enter.prevent="$event.target.blur()" />
                                                <button type="button" @click="step(member, 1)"><i class="fa fa-plus"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </template>
                        </tbody>
                        <tbody v-else>
                            <tr v-for="member in rows" :key="member.id" class="var flat" :class="{ sel: qtyOf(member) }">
                                <td>
                                    <button type="button" class="pcx-cat-cb" :class="{ on: qtyOf(member) }" @click="toggleMember(member)"><i class="fa fa-check"></i></button>
                                </td>
                                <td>
                                    <div class="pcx-cat-nm">
                                        <div class="pcx-cat-th sm" :class="{ lt: isLightColour(member.colour_hex) }" :style="thumbStyle(member)">
                                            <img v-if="member.thumbnail" :src="member.thumbnail" alt="" loading="lazy" />
                                            <span v-else>{{ member.size || initials(member.name) }}</span>
                                        </div>
                                        <div class="pcx-cat-nmt">
                                            <b>{{ member.name }}</b>
                                            <small v-if="member.variant">{{ member.variant }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="mono">{{ member.code }}</td>
                                <td class="mono pcx-cat-hs">{{ member.barcode }}</td>
                                <td>
                                    <span v-if="member.colour" class="pcx-cat-pill"><span class="pcx-cat-sw" :style="{ '--c': member.colour_hex }"></span>{{ member.colour }}</span>
                                </td>
                                <td><b>{{ member.size }}</b></td>
                                <td class="e"><span class="pcx-cat-stk" :class="stockClass(member.stock)">{{ formatQty(member.stock) }}</span></td>
                                <td class="e mono pcx-cat-hs">{{ formatNumber(member.cost) }}</td>
                                <td class="e">
                                    <div class="pcx-cat-step" :class="{ on: qtyOf(member) }">
                                        <button type="button" @click="step(member, -1)"><i class="fa fa-minus"></i></button>
                                        <input type="number" min="0" placeholder="0" :value="qtyOf(member) || ''"
                                            @change="setQty(member, $event.target.value)" @keydown.enter.prevent="$event.target.blur()" />
                                        <button type="button" @click="step(member, 1)"><i class="fa fa-plus"></i></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-if="loading && !rows.length" class="pcx-cat-skel">
                        <div v-for="n in 6" :key="n" class="pcx-cat-skel-row"></div>
                    </div>
                    <div v-else-if="!rows.length && !loading" class="pcx-empty">
                        <i class="fa fa-search"></i>
                        <div class="t">{{ loadError ? 'Could not load the catalogue' : 'Nothing matches these filters' }}</div>
                        <div class="s">{{ loadError ? 'Check your connection and try again.' : 'Clear a filter or try a shorter search.' }}</div>
                    </div>
                    <div v-if="meta.current_page < meta.last_page" class="pcx-cat-more">
                        <button type="button" class="pcx-cat-btn" :disabled="loading" @click="loadMore">
                            <i class="fa" :class="loading ? 'fa-spinner fa-spin' : 'fa-angle-down'"></i>
                            Show more ({{ meta.total - rows.length }} left)
                        </button>
                    </div>
                </div>

                <div class="pcx-cat-ft">
                    <div class="pcx-cat-sum">
                        <span><b>{{ pickedLines.length }}</b>{{ pickedLines.length === 1 ? 'line' : 'lines' }}</span>
                        <span><b>{{ formatQty(pickedPieces) }}</b>pcs</span>
                        <span><b>{{ formatNumber(pickedCost) }}</b>at cost</span>
                    </div>
                    <div class="pcx-cat-basket">
                        <span v-for="entry in basket.slice(0, 5)" :key="entry.name">{{ entry.name }} <b>×{{ formatQty(entry.pieces) }}</b></span>
                        <span v-if="basket.length > 5">+{{ basket.length - 5 }} more</span>
                    </div>
                    <div class="pcx-cat-sp"></div>
                    <button type="button" class="pcx-cat-btn ghost" :disabled="!pickedLines.length || adding" @click="clearPicks">Clear</button>
                    <button type="button" class="pcx-cat-btn pri" :disabled="!pickedLines.length || adding" @click="addToPurchase">
                        <i class="fa" :class="adding ? 'fa-spinner fa-spin' : 'fa-plus'"></i> Add to items
                    </button>
                </div>
            </aside>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, reactive, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import { formatNumber } from '@/utils/number'
import { useLivewire } from '@/composables/useLivewire'
import { getRoute } from '@/utils/routes'

const emit = defineEmits(['added'])

const { call } = useLivewire()

const filterColumns = [
    { key: 'name', icon: 'fa-search', placeholder: 'Name' },
    { key: 'sku', icon: 'fa-tag', placeholder: 'SKU' },
    { key: 'barcode', icon: 'fa-barcode', placeholder: 'Barcode' },
    { key: 'colour', icon: 'fa-tint', placeholder: 'Colour' },
    { key: 'size', icon: 'fa-arrows-h', placeholder: 'Size' },
]

/** Family rows show this many colour dots, then a "+N" count, so big families don't stretch the column. */
const SWATCH_LIMIT = 8

const LETTER_SIZES = ['XXXS', 'XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', '2XL', 'XXXL', '3XL', '4XL', '5XL']

const isOpen = ref(false)
const group = ref(true)
const filters = reactive({ name: '', sku: '', barcode: '', colour: '', size: '' })
const rows = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(false)
const loadError = ref(false)
const adding = ref(false)
const expanded = ref(new Set())
/** product id => { member, qty } — survives new searches so picks pile up in one basket. */
const picks = ref({})
const body = ref(null)

let debounceTimer = null
let abortController = null

const memberFilterActive = computed(() => !!(filters.sku || filters.barcode || filters.colour || filters.size))

const pickedLines = computed(() => Object.values(picks.value).filter(pick => pick.qty > 0))
const pickedPieces = computed(() => pickedLines.value.reduce((sum, pick) => sum + pick.qty, 0))
const pickedCost = computed(() => pickedLines.value.reduce((sum, pick) => sum + pick.qty * (Number(pick.member.cost) || 0), 0))
const basket = computed(() => {
    const byName = new Map()
    pickedLines.value.forEach(pick => byName.set(pick.member.name, (byName.get(pick.member.name) || 0) + pick.qty))
    return [...byName].map(([name, pieces]) => ({ name, pieces }))
})

const formatQty = (value) => {
    const number = Number(value) || 0
    return Number.isInteger(number) ? String(number) : number.toFixed(2)
}

const initials = (name) => String(name || '').replace(/[^\p{L}\p{N} ]/gu, '').split(' ').filter(Boolean).slice(0, 2).map(word => word[0]).join('').toUpperCase() || '#'

const thumbStyle = (member) => member?.colour_hex ? { '--a': member.colour_hex } : {}

/** A pale swatch (white, cream, light grey) needs dark lettering on its thumbnail. */
const isLightColour = (hex) => {
    const match = /^#([0-9a-f]{6})$/i.exec(String(hex || ''))
    if (!match) return false
    const value = parseInt(match[1], 16)
    const luminance = (0.299 * ((value >> 16) & 255) + 0.587 * ((value >> 8) & 255) + 0.114 * (value & 255)) / 255
    return luminance > 0.72
}

const stockClass = (stock) => (Number(stock) <= 0 ? 'out' : Number(stock) < 3 ? 'low' : '')

const sizeRank = (size) => {
    const value = String(size ?? '').trim()
    if (value === '') return [0, 0, '']
    if (/^\d+(\.\d+)?$/.test(value)) return [1, parseFloat(value), value]
    const letter = LETTER_SIZES.indexOf(value.toUpperCase())
    if (letter >= 0) return [2, letter, value]
    return [3, 0, value]
}

const compareSizes = (a, b) => {
    const [ga, na, sa] = sizeRank(a)
    const [gb, nb, sb] = sizeRank(b)
    return ga - gb || na - nb || sa.localeCompare(sb, undefined, { numeric: true })
}

/** Colour by colour (in the order the family met them), small to large inside each. */
const sortedMembers = (family) => {
    const colourOrder = new Map()
    family.members.forEach(member => {
        if (!colourOrder.has(member.colour)) colourOrder.set(member.colour, colourOrder.size)
    })
    return [...family.members].sort((a, b) => colourOrder.get(a.colour) - colourOrder.get(b.colour) || compareSizes(a.size, b.size))
}

const familyColours = (family) => {
    const seen = new Map()
    family.members.forEach(member => {
        if (member.colour && !seen.has(member.colour)) seen.set(member.colour, { name: member.colour, hex: member.colour_hex })
    })
    return [...seen.values()]
}

const familySizeRange = (family) => {
    const sizes = [...new Set(family.members.map(member => member.size).filter(Boolean))].sort(compareSizes)
    if (!sizes.length) return ''
    return sizes.length === 1 ? sizes[0] : `${sizes[0]}–${sizes[sizes.length - 1]}`
}

const familyStock = (family) => family.members.reduce((sum, member) => sum + (Number(member.stock) || 0), 0)
const familyPieces = (family) => family.members.reduce((sum, member) => sum + qtyOf(member), 0)
const familyPickedCount = (family) => family.members.filter(member => qtyOf(member) > 0).length
const familyTickClass = (family) => {
    const picked = familyPickedCount(family)
    return picked === 0 ? '' : picked === family.members.length ? 'on' : 'part'
}
/** The quantity every shown member shares, so the family stepper reads "2" after "set all to 2". */
const familyCommonQty = (family) => {
    const quantities = new Set(family.members.map(member => qtyOf(member)))
    const [only] = quantities
    return quantities.size === 1 && only > 0 ? only : ''
}

const isExpanded = (family) => expanded.value.has(family.id) || memberFilterActive.value

const toggleExpand = (family) => {
    const next = new Set(expanded.value)
    next.has(family.id) ? next.delete(family.id) : next.add(family.id)
    expanded.value = next
}

const qtyOf = (member) => (member ? picks.value[member.id]?.qty || 0 : 0)

const setQty = (member, value) => {
    if (!member) return
    const qty = Math.max(0, parseFloat(value) || 0)
    const next = { ...picks.value }
    if (qty > 0) {
        next[member.id] = { member, qty }
    } else {
        delete next[member.id]
    }
    picks.value = next
}

const step = (member, delta) => setQty(member, qtyOf(member) + delta)
const toggleMember = (member) => setQty(member, qtyOf(member) ? 0 : 1)

const setFamilyQty = (family, value) => {
    const qty = Math.max(0, parseFloat(value) || 0)
    const next = { ...picks.value }
    family.members.forEach(member => {
        if (qty > 0) {
            next[member.id] = { member, qty }
        } else {
            delete next[member.id]
        }
    })
    picks.value = next
    if (qty > 0 && !expanded.value.has(family.id)) toggleExpand(family)
}

const stepFamily = (family, delta) => {
    const current = Math.max(0, ...family.members.map(member => qtyOf(member)))
    setFamilyQty(family, current + delta)
}

const toggleFamily = (family) => setFamilyQty(family, familyPickedCount(family) === family.members.length ? 0 : 1)

const clearPicks = () => {
    picks.value = {}
}

const fetchPage = async (page = 1) => {
    abortController?.abort()
    abortController = new AbortController()
    loading.value = true
    loadError.value = false

    const params = new URLSearchParams({ group: group.value ? 1 : 0, page })
    Object.entries(filters).forEach(([key, value]) => {
        if (String(value).trim() !== '') params.set(key, String(value).trim())
    })

    try {
        const response = await fetch(`${getRoute('product::catalogue')}?${params}`, {
            headers: { Accept: 'application/json' },
            signal: abortController.signal,
        })
        if (!response.ok) throw new Error(`HTTP ${response.status}`)
        const json = await response.json()
        rows.value = page === 1 ? json.data : [...rows.value, ...json.data]
        meta.value = json.meta
        if (page === 1 && body.value) body.value.scrollTop = 0
    } catch (error) {
        if (error.name === 'AbortError') return
        console.error('Error loading the product catalogue:', error)
        loadError.value = true
        if (page === 1) rows.value = []
    }
    loading.value = false
}

const loadMore = () => fetchPage(meta.value.current_page + 1)

const setGroup = (value) => {
    if (group.value === value) return
    group.value = value
    rows.value = []
    fetchPage(1)
}

watch(filters, () => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => fetchPage(1), 250)
})

const open = () => {
    isOpen.value = true
    if (!rows.value.length) fetchPage(1)
    nextTick(() => setTimeout(() => body.value?.querySelector('.pcx-cat-flt input')?.focus(), 240))
}

const close = () => {
    isOpen.value = false
}

const addToPurchase = async () => {
    if (!pickedLines.value.length || adding.value) return
    adding.value = true
    const lines = pickedLines.value.map(pick => ({ product_id: pick.member.id, quantity: pick.qty }))
    const pieces = pickedPieces.value
    try {
        const added = await call('addCatalogueItems', lines)
        const count = Number(added) || lines.length
        picks.value = {}
        close()
        emit('added', { lines: count, pieces })
        window.toastr?.success?.(`${count} ${count === 1 ? 'line' : 'lines'} · ${formatQty(pieces)} pcs added`)
    } catch (error) {
        console.error('Error adding catalogue items:', error)
        window.toastr?.error?.('Could not add the picked items. Please try again.')
    }
    adding.value = false
}

const onKeydown = (event) => {
    if (event.key === 'F2') {
        event.preventDefault()
        isOpen.value ? close() : open()
    } else if (event.key === 'Escape' && isOpen.value) {
        close()
    }
}

onMounted(() => window.addEventListener('keydown', onKeydown))
onUnmounted(() => {
    window.removeEventListener('keydown', onKeydown)
    clearTimeout(debounceTimer)
    abortController?.abort()
})

defineExpose({ open, close })
</script>
