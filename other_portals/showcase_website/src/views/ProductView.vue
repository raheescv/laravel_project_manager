<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { resolveImage } from '@/api/client'
import { fetchProduct, fetchProducts } from '@/api/resources'
import ErrorBox from '@/components/ErrorBox.vue'
import { field, money, t } from '@/i18n'
import { useBagStore } from '@/stores/bag'
import { useCatalogStore } from '@/stores/catalog'
import { sizeLabel, sizing } from '@/sizing'
import { useShopsStore } from '@/stores/shops'
import { toast } from '@/toast'
import { initialOf, priceOf, productReference, productRoute, sortSizes, stockPill } from '@/utils/catalog'

const route = useRoute()
const router = useRouter()
const catalog = useCatalogStore()
const bag = useBagStore()
const shops = useShopsStore()

const product = ref(null)
const loading = ref(true)
const error = ref(null)

/** Every size of this model: [{ id, size, stock, branches? }] (see loadVariants). */
const variants = ref([])
/** Every item of this product's size / colour family (parent_id), when it has one. */
const family = ref([])
const switchingColour = ref(false)
/** Lazily fetched per-shop stock for sibling sizes, keyed by product id. */
const siblingStock = ref({})

const size = ref(null)
const qty = ref(1)
const photo = ref(0)
const mode360 = ref(false)
const frame = ref(0)
const spinning = ref(false)

// ---- derived --------------------------------------------------------------
const name = computed(() => field(product.value, 'name'))
const brandName = computed(
  () => product.value?.brand?.name || product.value?.main_category?.name || '',
)

const gallery = computed(() => {
  const p = product.value
  if (!p) return []
  const list = (p.images || []).map((i) => resolveImage(i.url)).filter(Boolean)
  const thumb = resolveImage(p.thumbnail)
  if (thumb && !list.includes(thumb)) list.unshift(thumb)
  return list
})
const frames = computed(() =>
  (product.value?.images360 || []).map((i) => resolveImage(i.url)).filter(Boolean),
)
const has360 = computed(() => frames.value.length > 1)
const mainImage = computed(() =>
  mode360.value ? frames.value[frame.value] : gallery.value[photo.value],
)
const galleryLabel = computed(() =>
  mode360.value
    ? t('spinCount', { i: frame.value + 1, n: frames.value.length })
    : t('photos', { i: photo.value + 1, n: gallery.value.length }),
)

const colourKey = (p) => String(p?.color || '').trim()
/** The family's colours, each knowing its stock; shown only when there is a choice. */
const colours = computed(() => {
  const byColour = new Map()
  for (const m of family.value) {
    const key = colourKey(m)
    if (!key) continue
    const c = byColour.get(key) || { value: key, name: m.color_name || key, hex: m.color_hex, stock: 0 }
    c.stock += Number(m.total_stock) || 0
    byColour.set(key, c)
  }
  return byColour.size > 1 ? [...byColour.values()] : []
})
const colourName = computed(() => product.value?.color_name || product.value?.color || '')

const sizes = computed(() => variants.value)
const selected = computed(() => sizes.value.find((v) => v.size === size.value) || null)
// Each size is its own product row, so an offer may cover only some of them.
const price = computed(() => priceOf(selected.value?.mrp !== undefined ? selected.value : product.value))
const modelStock = computed(() =>
  sizes.value.length
    ? sizes.value.reduce((n, v) => n + v.stock, 0)
    : Number(product.value?.total_stock) || 0,
)
const overallPill = computed(() => stockPill(modelStock.value))

const note = computed(() => {
  if (sizes.value.length && !selected.value) return { cls: '', text: t('notePick') }
  if (selected.value) {
    const n = selected.value.stock
    const s = sizeLabel(selected.value.size)
    if (n <= 0) return { cls: 'pdp__note--warn', text: t('noteOut', { size: s }) }
    if (n <= 5) return { cls: 'pdp__note--warn', text: t('noteLow', { n, size: s }) }
    return { cls: 'pdp__note--ok', text: t('noteOk', { n, size: s }) }
  }
  const n = modelStock.value
  if (n <= 0) return { cls: 'pdp__note--warn', text: t('soldOut') }
  return { cls: 'pdp__note--ok', text: t('noteTotal', { n }) }
})

/** Per-shop rows for the selected size (or this row when no size applies). */
const shopRows = computed(() => {
  const p = product.value
  if (!p) return null
  let rows = null
  const v = selected.value
  if (v?.branches) {
    rows = v.branches.map((b) => ({ id: b.id, name: b.name, stock: Number(b.quantity) || 0 }))
  } else {
    const inv = v && v.id !== p.id ? siblingStock.value[v.id] : p.inventories
    if (!inv) return null
    rows = inv.map((i) => ({
      id: i.branch?.id,
      name: i.branch?.name,
      stock: Number(i.quantity) || 0,
    }))
  }
  // Order as the shops list does and hide back-office branches it excludes.
  if (shops.shops.length) {
    return shops.shops.map((s) => ({
      id: s.id,
      name: s.name || s.location || s.code,
      stock: rows.filter((r) => r.id === s.id).reduce((n, r) => n + r.stock, 0),
    }))
  }
  return rows.filter((r) => r.name)
})

const specs = computed(() => {
  const p = product.value
  if (!p) return []
  const run = sizes.value
  return [
    { label: t('specBrand'), value: brandName.value },
    { label: t('specColour'), value: colourName.value },
    { label: t('specModel'), value: p.model },
    { label: t('specSku'), value: p.code, mono: true },
    { label: t('specBarcode'), value: p.barcode, mono: true },
    {
      label: t('specRun'),
      value: run.length > 1 ? `${run[0].size} – ${run[run.length - 1].size}` : run[0]?.size,
      mono: true,
    },
  ].filter((s) => s.value)
})

const backTo = computed(() => ({ name: 'catalogue', query: catalog.toQuery() }))

// ---- loading --------------------------------------------------------------
// 360° state lives up here: load() runs from an immediate watcher and resets it.
let spinTimer = null
let dragging = false
let dragX = 0
let dragFrame = 0
// Set while an old id link (#/product/123) is swapped for its name link, so the swap doesn't reload.
let renamedTo = null

async function load() {
  // Leaving the page clears the param before this component unmounts.
  if (!route.params.slug) return
  if (route.params.slug === renamedTo) {
    renamedTo = null
    return
  }
  loading.value = true
  error.value = null
  product.value = null
  variants.value = []
  family.value = []
  siblingStock.value = {}
  size.value = null
  qty.value = 1
  photo.value = 0
  frame.value = 0
  mode360.value = false
  stopSpin()
  try {
    product.value = await fetchProduct(productReference(route.params.slug))
    const named = productRoute(product.value)
    if (named.params.slug !== route.params.slug) {
      renamedTo = named.params.slug
      router.replace(named)
    }
    await loadVariants()
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

/** A family member as a size-run entry. */
function runEntry(v) {
  return {
    id: v.id,
    size: String(v.size).trim(),
    stock: Number(v.total_stock) || 0,
    mrp: v.mrp,
    online_price: v.online_price,
    online_discount_percent: v.online_discount_percent,
  }
}

/**
 * Every item of the product's family: its head (parent_id) and the variants
 * under it. A family can outgrow one page (100 rows — e.g. 18 colours × sizes),
 * and a truncated family drops whole colours' sizes, so every page is read.
 */
async function loadFamily(p) {
  const members = []
  try {
    for (let page = 1; page <= 20; page++) {
      const res = await fetchProducts({
        family_id: p.parent_id || p.id,
        in_stock_only: 0,
        type: 'product',
        per_page: 100,
        page,
      })
      if (Array.isArray(res?.data)) members.push(...res.data)
      if (!res?.pagination?.has_more_pages) break
    }
  } catch {
    /* keep whatever pages arrived */
  }
  return members
}

/**
 * The size run. A product with size / colour variants is one family
 * (`parent_id`): the run is the family's sizes in this colour, and the other
 * colours are a swatch away. Older catalogues filed each size as its own row
 * sharing the model's `code`, so without a family the siblings come from a
 * code search (no in-stock filter, so sold-out sizes still show struck
 * through); `related_sizes` is the last fallback.
 */
async function loadVariants() {
  const p = product.value
  if (!p) return
  let list = []

  const members = await loadFamily(p)
  if (members.length > 1) {
    family.value = members
    const sameColour = members.filter((v) => colourKey(v) === colourKey(p))
    list = sameColour
      .filter((v) => v.size !== null && String(v.size).trim() !== '')
      .map(runEntry)
  }

  if (!list.length && p.code) {
    try {
      const res = await fetchProducts({
        search: p.code,
        in_stock_only: 0,
        type: 'product',
        per_page: 100,
      })
      list = (res?.data || [])
        .filter((v) => v.code === p.code && v.size !== null && String(v.size).trim() !== '')
        .map(runEntry)
    } catch {
      list = []
    }
  }

  if (!family.value.length && list.length <= 1 && (p.related_sizes || []).length > 1) {
    list = p.related_sizes.map((r) => ({
      id: String(r.size) === String(p.size) ? p.id : null,
      size: String(r.size).trim(),
      stock: Number(r.total_stock) || 0,
      branches: r.branches || [],
    }))
  }

  if (!list.length && p.size) {
    list = [{ id: p.id, size: String(p.size).trim(), stock: Number(p.total_stock) || 0 }]
  }

  // One entry per size — keep the row with the most stock.
  const bySize = new Map()
  for (const v of list) {
    const prev = bySize.get(v.size)
    if (!prev || v.stock > prev.stock) bySize.set(v.size, v)
  }
  variants.value = sortSizes([...bySize.values()], (v) => v.size)

  // Preselect the size you came from: this row's own size, else your chosen size.
  const own = variants.value.find((v) => v.size === String(p.size).trim())
  const mine = catalog.sized ? variants.value.find((v) => v.size === catalog.size) : null
  size.value = own?.stock > 0 ? own.size : mine?.stock > 0 ? mine.size : null
}

// A sibling size's per-shop breakdown is one small detail request, on demand.
watch(selected, async (v) => {
  const p = product.value
  if (!v || !v.id || !p || v.id === p.id || v.branches || siblingStock.value[v.id]) return
  try {
    const d = await fetchProduct(v.id)
    siblingStock.value = { ...siblingStock.value, [v.id]: d?.inventories || [] }
  } catch {
    siblingStock.value = { ...siblingStock.value, [v.id]: [] }
  }
})

watch(() => route.params.slug, load, { immediate: true })

// ---- interactions ---------------------------------------------------------
/**
 * Another colour of the family: open that colour's item in the size you had
 * (or the one with the most stock) without leaving the page — the family and
 * its run are already here.
 */
async function pickColour(c) {
  const p = product.value
  if (!p || c.value === colourKey(p) || switchingColour.value) return
  const inColour = family.value.filter((m) => colourKey(m) === c.value)
  const wanted = size.value || String(p.size || '').trim()
  const target =
    inColour.find((m) => String(m.size).trim() === wanted && Number(m.total_stock) > 0) ||
    [...inColour].sort((a, b) => (Number(b.total_stock) || 0) - (Number(a.total_stock) || 0))[0]
  if (!target) return
  switchingColour.value = true
  try {
    const next = await fetchProduct(target.id)
    product.value = next
    siblingStock.value = {}
    photo.value = 0
    frame.value = 0
    mode360.value = false
    stopSpin()
    variants.value = sortSizes(
      family.value
        .filter((m) => colourKey(m) === c.value && m.size !== null && String(m.size).trim() !== '')
        .map(runEntry),
      (v) => v.size,
    )
    const same = variants.value.find((v) => v.size === wanted && v.stock > 0)
    size.value = same ? same.size : null
    const named = productRoute(next)
    renamedTo = named.params.slug !== route.params.slug ? named.params.slug : null
    router.replace(named)
  } catch (e) {
    toast(e.message)
  } finally {
    switchingColour.value = false
  }
}

function pickSize(v) {
  if (v.stock <= 0) return
  size.value = size.value === v.size ? null : v.size
}

function add() {
  const p = product.value
  if (!p) return
  if (sizes.value.length && !selected.value) {
    toast(t('pickSizeFirst'))
    return
  }
  bag.add(
    {
      id: selected.value?.id || p.id,
      size: selected.value?.size || (p.size ? String(p.size).trim() : ''),
      price: price.value.now,
      was: price.value.was,
      name: p.name,
      name_arabic: p.name_arabic,
      brand: brandName.value,
      img: gallery.value[0] || null,
    },
    qty.value,
  )
  toast(t('added', { name: name.value }))
}

// ---- 360° spin ------------------------------------------------------------
function startSpin() {
  if (!has360.value) return
  mode360.value = true
  spinning.value = true
  clearInterval(spinTimer)
  spinTimer = setInterval(() => {
    frame.value = (frame.value + 1) % frames.value.length
  }, 90)
}
function stopSpin() {
  spinning.value = false
  clearInterval(spinTimer)
  spinTimer = null
}
function enter360() {
  if (!has360.value) return
  if (mode360.value) {
    spinning.value ? stopSpin() : startSpin()
  } else {
    startSpin()
  }
}
function showPhoto(i) {
  stopSpin()
  mode360.value = false
  photo.value = i
}
function dragStart(e) {
  if (!mode360.value || !has360.value) return
  stopSpin()
  dragging = true
  dragX = e.clientX
  dragFrame = frame.value
  e.currentTarget.setPointerCapture?.(e.pointerId)
}
function dragMove(e) {
  if (!dragging) return
  const count = frames.value.length
  const delta = Math.round((e.clientX - dragX) / 24)
  frame.value = (((dragFrame + delta) % count) + count) % count
}
function dragEnd() {
  dragging = false
}

onBeforeUnmount(stopSpin)
</script>

<template>
  <div class="wrap">
    <router-link class="backlink" :to="backTo">
      <span class="backlink__arrow">&#8249;</span> {{ t('back') }}
    </router-link>

    <ErrorBox v-if="error" :message="error" @retry="load" />

    <div v-else-if="loading" class="pdp" aria-busy="true">
      <div class="pdp__gallery"><div class="skel skel--gal"></div></div>
      <div class="pdp__info pdp__skel">
        <div class="skel skel--line" style="width: 30%"></div>
        <div class="skel skel--line" style="width: 70%; height: 34px"></div>
        <div class="skel skel--line" style="width: 40%"></div>
        <div class="skel skel--line" style="width: 25%; height: 26px"></div>
        <div class="skel" style="height: 120px; margin-block-start: 20px"></div>
      </div>
    </div>

    <div v-else-if="!product" class="empty">
      <span class="empty__rule"></span>
      <p class="empty__title">{{ t('notFound') }}</p>
      <router-link class="btn btn--primary" :to="backTo">{{ t('back') }}</router-link>
    </div>

    <div v-else class="pdp">
      <!-- gallery -->
      <div class="pdp__gallery">
        <div
          class="gal"
          :class="{ 'gal--360': mode360 }"
          @pointerdown="dragStart"
          @pointermove="dragMove"
          @pointerup="dragEnd"
          @pointercancel="dragEnd"
        >
          <img v-if="mainImage" :src="mainImage" :alt="name" draggable="false" />
          <div v-else class="card__ph" aria-hidden="true">
            <img v-if="product.brand?.image_path" :src="product.brand.image_path" alt="" />
            <span v-else>{{ initialOf(brandName || name) }}</span>
          </div>
          <span v-if="gallery.length || mode360" class="gal__count">{{ galleryLabel }}</span>
          <button v-if="mode360" class="gal__spin" @click.stop="enter360">
            {{ spinning ? t('pause') : t('play') }}
          </button>
        </div>
        <div v-if="gallery.length > 1 || has360" class="thumbs">
          <button
            v-for="(im, i) in gallery"
            :key="im"
            class="thumb"
            :class="{ 'is-on': !mode360 && photo === i }"
            :aria-label="String(i + 1)"
            @click="showPhoto(i)"
          >
            <img :src="im" alt="" loading="lazy" />
          </button>
          <button
            v-if="has360"
            class="thumb thumb--360"
            :class="{ 'is-on': mode360 }"
            :aria-label="t('spin')"
            @click="enter360"
          >
            360°
          </button>
        </div>
      </div>

      <!-- info -->
      <div class="pdp__info">
        <p class="pdp__brand">{{ brandName }}</p>
        <h1 class="pdp__title">{{ name }}</h1>
        <p v-if="colourName" class="pdp__colour">{{ t('colour') }}: {{ colourName }}</p>
        <div class="pdp__pricerow">
          <span v-if="price.was" class="pdp__prices">
            <span class="price price--lg price--sale">{{ money(price.now) }}</span>
            <span class="price price--was">{{ money(price.was) }}</span>
            <span class="sale-tag"><bdi dir="ltr">&minus;{{ price.percent }}%</bdi></span>
          </span>
          <span v-else class="price price--lg">{{ money(price.now) }}</span>
          <span class="pill" :class="overallPill.cls">{{ overallPill.text }}</span>
        </div>
        <p class="pdp__note" :class="note.cls">{{ note.text }}</p>

        <div class="pdp__block">
          <template v-if="colours.length">
            <div class="pdp__blockhead">
              <p class="h-sm">{{ t('colours') }}</p>
              <span class="field__tag">{{ colourName }}</span>
            </div>
            <div class="swatches" :aria-busy="switchingColour">
              <button
                v-for="c in colours"
                :key="c.value"
                class="swatch"
                :class="{ 'is-on': c.value === colourKey(product), 'is-out': c.stock <= 0 }"
                :title="c.name"
                :aria-label="c.name"
                @click="pickColour(c)"
              >
                <span class="swatch__dot" :style="{ '--c': c.hex || '#9ca3af' }"></span>
                <span class="swatch__name">{{ c.name }}</span>
              </button>
            </div>
          </template>
          <template v-if="sizes.length">
            <div class="pdp__blockhead">
              <p class="h-sm">{{ t('chooseSize') }}</p>
              <span class="field__tag">{{ t('sizeGuide', { system: sizing.system }) }}</span>
            </div>
            <div class="szgrid">
              <button
                v-for="v in sizes"
                :key="v.size"
                class="sz"
                :class="{ 'is-out': v.stock <= 0, 'is-on': size === v.size }"
                :disabled="v.stock <= 0"
                @click="pickSize(v)"
              >
                {{ sizeLabel(v.size) }}
              </button>
            </div>
          </template>
          <div class="pdp__buy">
            <div class="qty">
              <button aria-label="−" @click="qty = Math.max(1, qty - 1)">&minus;</button>
              <span>{{ qty }}</span>
              <button aria-label="+" @click="qty = Math.min(9, qty + 1)">+</button>
            </div>
            <button class="btn btn--primary" :disabled="modelStock <= 0" @click="add">
              {{ sizes.length && !selected ? t('pickSizeFirst') : t('addToBag') }}
            </button>
          </div>
        </div>

        <div class="pdp__block">
          <p class="h-sm">{{ t('inStores') }}</p>
          <div v-if="shopRows" class="stores">
            <div v-for="s in shopRows" :key="s.id" class="stores__row">
              <span class="stores__name">{{ s.name }}</span>
              <span class="stores__stock" :class="{ 'is-out': s.stock <= 0 }">
                {{ s.stock <= 0 ? t('outHere') : `${s.stock} ${t('pieces')}` }}
              </span>
            </div>
          </div>
          <div v-else class="stores" aria-busy="true">
            <div v-for="n in 3" :key="n" class="stores__row">
              <span class="skel skel--line" style="width: 45%"></span>
              <span class="skel skel--line" style="width: 18%"></span>
            </div>
          </div>
        </div>

        <div v-if="specs.length" class="pdp__block">
          <p class="h-sm">{{ t('details') }}</p>
          <dl class="spec">
            <template v-for="s in specs" :key="s.label">
              <dt>{{ s.label }}</dt>
              <dd :class="{ mono: s.mono }">{{ s.value }}</dd>
            </template>
          </dl>
        </div>

        <div v-if="product.description" class="pdp__block">
          <p class="h-sm">{{ t('description') }}</p>
          <p class="pdp__desc">{{ product.description }}</p>
        </div>
      </div>
    </div>
  </div>
</template>
