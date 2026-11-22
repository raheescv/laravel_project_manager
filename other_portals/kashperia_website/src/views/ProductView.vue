<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { resolveImage } from '@/api/client'
import { fetchProduct, fetchProducts } from '@/api/resources'
import ProductCard from '@/components/ProductCard.vue'
import { money, whatsappLink, house } from '@/format'
import { useBagStore } from '@/stores/bag'
import { useCatalogStore } from '@/stores/catalog'
import { toast } from '@/toast'
import {
  editorialFor,
  editorialPhotosFor,
  priceOf,
  productImage,
  productReference,
  productRoute,
  sortSizes,
  zoomMove,
  zoomReset,
} from '@/utils/catalog'

const route = useRoute()
const router = useRouter()
const bag = useBagStore()
const catalog = useCatalogStore()

const product = ref(null) // the family member on show (detail payload)
const family = ref([]) // every size / colour of it (list rows, with stock)
const loading = ref(true)
const error = ref(null)
const switching = ref(false)
const photo = ref(0)
const zoomLocked = ref(false)
const giftTab = ref('box')
const initials = ref('')

// ---- derived ---------------------------------------------------------------------
const editorial = computed(() => editorialFor(product.value) || editorialFor(family.value[0]))
const collectionSlug = computed(() => {
  const name = product.value?.main_category?.name
  return name ? catalog.collectionLinks.find((l) => l.category?.id === product.value.main_category.id)?.slug : null
})
const crumb = computed(() =>
  [product.value?.main_category?.name, product.value?.sub_category?.name].filter(Boolean).join(' / ').toUpperCase(),
)
const eyebrow = computed(() => `THE KASHPERIA BOUTIQUE${product.value?.sub_category ? ` · ${product.value.sub_category.name.toUpperCase()}` : ''}`)

const gallery = computed(() => {
  const p = product.value
  if (!p) return []
  const list = (p.images || []).map((i) => resolveImage(i.url)).filter(Boolean)
  const thumb = resolveImage(p.thumbnail)
  if (thumb && !list.includes(thumb)) list.unshift(thumb)
  return list.length ? list : editorialPhotosFor(p).slice(0, 8)
})
const mainImage = computed(() => gallery.value[photo.value] || productImage(product.value))

const colourOf = (m) => String(m?.color || '').trim()
const sizeOf = (m) => String(m?.size || '').trim()

/** The family's colours, shown only when there is a choice. */
const colours = computed(() => {
  const byColour = new Map()
  for (const m of family.value) {
    const key = colourOf(m)
    if (key && !byColour.has(key)) byColour.set(key, { value: key, name: m.color_name || key, hex: m.color_hex || '#d8d2c6' })
  }
  return byColour.size > 1 ? [...byColour.values()] : []
})
/** Sizes (dimensions) on offer in the colour on show. */
const sizes = computed(() => {
  const colour = colourOf(product.value)
  const rows = family.value.filter((m) => sizeOf(m) && (!colours.value.length || colourOf(m) === colour))
  const bySize = new Map(rows.map((m) => [sizeOf(m), m]))
  return sortSizes([...bySize.keys()]).map((s) => bySize.get(s))
})
const editorialSizes = computed(() => (sizes.value.length ? [] : editorial.value?.sizes || []))

const member = computed(() => family.value.find((m) => m.id === product.value?.id) || product.value)
const stock = computed(() => Number(member.value?.total_stock ?? product.value?.total_stock) || 0)
const price = computed(() => priceOf(member.value?.mrp !== undefined ? member.value : product.value))
const purchasable = computed(() => price.value.now > 0 && stock.value > 0)
const availability = computed(() => {
  if (!price.value.now) return 'Price on request — our Client Services team will be pleased to assist.'
  if (stock.value <= 0) return 'Currently unavailable online. Our Client Services team can advise on availability.'
  if (stock.value <= 3) return `Only ${stock.value} available — complimentary gift presentation included.`
  return 'Available — complimentary gift presentation and delivery options at checkout.'
})
const leadText = computed(() => (editorial.value ? '' : product.value?.description || ''))
const detailsHtml = computed(() => editorial.value?.details || '')
const passportHtml = computed(() => editorial.value?.passport || '')
const careHtml = computed(
  () =>
    editorial.value?.care ||
    '<p><strong>Dry clean only.</strong> Store gently folded in a breathable cover, away from direct sunlight and moisture. Entrust delicate embroidery to a specialist cleaner.</p>',
)
const monogram = computed(() => initials.value.replace(/[^a-zA-Z]/g, '').slice(0, 3).toUpperCase() || 'AB')

// ---- recommendations -------------------------------------------------------------
const related = computed(() => {
  const p = product.value
  const key = p?.main_category ? `c${p.main_category.id}` : null
  const items = (key && catalog.collections[key]?.items) || []
  const familyId = p?.parent_id || p?.id
  const others = items.filter((x) => (x.parent_id || x.id) !== familyId)
  const same = others.filter((x) => x.sub_category?.id && x.sub_category.id === p?.sub_category?.id)
  return [...same, ...others.filter((x) => !same.includes(x))].slice(0, 4)
})

// ---- loading ---------------------------------------------------------------------
let renamedTo = null

async function loadFamily(p) {
  try {
    const res = await fetchProducts({ family_id: p.parent_id || p.id, in_stock_only: 0, type: 'product', per_page: 100 })
    return Array.isArray(res?.data) ? res.data : []
  } catch {
    return []
  }
}

async function load() {
  if (!route.params.slug) return
  if (route.params.slug === renamedTo) {
    renamedTo = null
    return
  }
  loading.value = true
  error.value = null
  product.value = null
  family.value = []
  photo.value = 0
  zoomLocked.value = false
  try {
    product.value = await fetchProduct(productReference(route.params.slug))
    const named = productRoute(product.value)
    if (named.params.slug !== route.params.slug) {
      renamedTo = named.params.slug
      router.replace(named)
    }
    document.title = `${product.value.name} — KASHPERIA`
    family.value = await loadFamily(product.value)
    if (product.value.main_category) {
      catalog.loadCollection(`c${product.value.main_category.id}`, { main_category_id: product.value.main_category.id })
    }
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

watch(() => route.params.slug, load, { immediate: true })

/** Show another member of the family (a colour or a size) without leaving the page. */
async function showMember(m) {
  if (!m || m.id === product.value?.id || switching.value) return
  switching.value = true
  try {
    product.value = await fetchProduct(m.id)
    photo.value = 0
  } catch (e) {
    toast(e.message)
  } finally {
    switching.value = false
  }
}

function chooseColour(c) {
  const size = sizeOf(product.value)
  const rows = family.value.filter((m) => colourOf(m) === c.value)
  showMember(rows.find((m) => sizeOf(m) === size) || rows[0])
}

function selectPhoto(i) {
  if (!gallery.value.length) return
  photo.value = (i + gallery.value.length) % gallery.value.length
}

let touchX = 0
let touchY = 0
function touchStart(e) {
  if (e.touches.length !== 1) return
  touchX = e.touches[0].clientX
  touchY = e.touches[0].clientY
}
function touchEnd(e) {
  if (e.changedTouches.length !== 1) return
  const dx = e.changedTouches[0].clientX - touchX
  const dy = e.changedTouches[0].clientY - touchY
  if (Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy) * 1.25) selectPhoto(photo.value + (dx < 0 ? 1 : -1))
}

function toggleZoom(event) {
  zoomLocked.value = !zoomLocked.value
  const el = event.currentTarget.closest('.pdp-photo')
  el.classList.toggle('k-zooming', zoomLocked.value)
}

function variantLabel() {
  return [product.value?.color_name || product.value?.color, product.value?.size].filter(Boolean).join(' · ')
}

function addToBag(goToCheckout = false) {
  if (!purchasable.value) {
    toast(availability.value)
    return
  }
  bag.add({
    id: product.value.id,
    name: product.value.name,
    variant: variantLabel(),
    price: price.value.now,
    was: price.value.was,
    img: mainImage.value,
    slug: route.params.slug,
  })
  if (goToCheckout) router.push('/checkout')
  else bag.openDrawer('cart')
}

function askAdvisor() {
  const label = product.value ? product.value.name : 'a Kashperia creation'
  window.open(whatsappLink(`Hello Kashperia, I would like to enquire about ${label}.`), '_blank', 'noopener,noreferrer')
}

/** Links inside the editorial copy carry data-to (see src/content/products.json). */
function onEditorialClick(event) {
  const link = event.target.closest('[data-to]')
  if (!link) return
  event.preventDefault()
  router.push(link.dataset.to)
}
</script>

<template>
  <main class="pdp" @click="onEditorialClick">
    <template v-if="loading">
      <div class="pdp-grid k-pdp-loading" aria-busy="true">
        <div><div class="pdp-photo k-skeleton"></div></div>
        <div class="pdp-info"><span class="eyebrow">THE KASHPERIA BOUTIQUE</span><h1>&nbsp;</h1><p class="pdp-hint">Loading the creation…</p></div>
      </div>
    </template>

    <div v-else-if="error" class="k-curated-empty">
      <h2 class="serif">This creation could not be found</h2>
      <p>{{ error }}</p>
      <RouterLink to="/" custom v-slot="{ navigate }"><button type="button" class="k-side-clear" @click="navigate">RETURN TO THE HOUSE →</button></RouterLink>
    </div>

    <template v-else-if="product">
      <RouterLink :to="collectionSlug ? `/collection/${collectionSlug}` : '/'" custom v-slot="{ navigate }">
        <button type="button" class="pdp-back" @click="navigate">← {{ crumb || 'THE HOUSE' }}</button>
      </RouterLink>
      <div class="pdp-grid">
        <div>
          <div
            class="pdp-photo k-pdp-zoom"
            :class="{ 'k-zoom-locked': zoomLocked }"
            title="Move cursor to magnify or click to zoom"
            @mousemove="zoomMove"
            @mouseleave="zoomReset"
            @click="toggleZoom"
            @touchstart.passive="touchStart"
            @touchend.passive="touchEnd"
          >
            <button class="k-pdp-zoom-button" type="button" @click.stop="toggleZoom">⌕ {{ zoomLocked ? 'ZOOM OUT' : 'ZOOM IN' }}</button>
            <img v-if="mainImage" :src="mainImage" :alt="product.name" decoding="async" />
            <div v-else class="k-card-mark" aria-hidden="true">K</div>
            <span v-if="gallery.length > 1" class="k-mobile-image-counter" aria-live="polite">{{ photo + 1 }} / {{ gallery.length }}</span>
          </div>
          <div v-if="gallery.length > 1" class="pdp-thumbs">
            <img
              v-for="(src, i) in gallery"
              :key="src"
              :src="src"
              :alt="`${product.name} — photograph ${i + 1}`"
              role="button"
              tabindex="0"
              :aria-label="`View product image ${i + 1}`"
              :class="{ 'k-selected': i === photo }"
              loading="lazy"
              decoding="async"
              @click="selectPhoto(i)"
              @keydown.enter="selectPhoto(i)"
            />
          </div>
          <p v-if="gallery.length > 1" class="k-mobile-swipe-hint">Swipe left or right to view photographs</p>
        </div>

        <div class="pdp-info">
          <span class="eyebrow">{{ eyebrow }}</span>
          <h1>{{ product.name }}</h1>
          <div v-if="editorial" class="k-pdp-story" v-html="editorial.story"></div>
          <p v-if="leadText" class="pdp-lead">{{ leadText }}</p>

          <div class="pdp-price">
            <template v-if="price.now"><s v-if="price.was" class="k-was">{{ money(price.was) }}</s> {{ money(price.now) }}</template>
            <template v-else>Price on request</template>
          </div>
          <p class="pdp-hint">{{ availability }}</p>
          <hr class="pdp-rule" />

          <template v-if="colours.length">
            <div class="pdp-label">Colour · <span>{{ product.color_name || product.color }}</span></div>
            <div class="pdp-swatches">
              <button
                v-for="c in colours"
                :key="c.value"
                type="button"
                class="swatch"
                :class="{ sel: c.value === colourOf(product) }"
                :style="{ background: c.hex }"
                :aria-label="c.name"
                :title="c.name"
                :disabled="switching"
                @click="chooseColour(c)"
              ></button>
            </div>
          </template>
          <template v-else-if="product.color_name || product.color">
            <div class="pdp-label">Colour · <span>{{ product.color_name || product.color }}</span></div>
          </template>

          <template v-if="sizes.length">
            <div class="pdp-label">Select dimensions</div>
            <div class="pdp-size">
              <button
                v-for="m in sizes"
                :key="m.id"
                type="button"
                :class="{ active: m.id === product.id, 'k-size-out': !(Number(m.total_stock) > 0) }"
                :disabled="switching"
                @click="showMember(m)"
              >{{ sizeOf(m) }}</button>
            </div>
            <p class="pdp-hint">Measurements are approximate. Colours and availability are confirmed for each product.</p>
          </template>
          <template v-else-if="editorialSizes.length">
            <div class="pdp-label">Dimensions</div>
            <div class="pdp-size"><button v-for="s in editorialSizes" :key="s" type="button" class="active">{{ s }}</button></div>
            <p class="pdp-hint">Measurements are approximate.</p>
          </template>

          <div class="pdp-cta">
            <button type="button" :disabled="switching" @click="addToBag(false)">Add to Bag</button>
            <button type="button" class="secondary" :disabled="switching" @click="addToBag(true)">Buy Now</button>
          </div>
          <p class="pdp-hint">Complimentary gifting presentation and delivery options are subject to the final service policy.</p>
          <hr class="pdp-rule" />

          <details class="pdp-accord">
            <summary>Product details +</summary>
            <div class="k-pdp-detail">
              <div v-if="detailsHtml" v-html="detailsHtml"></div>
              <p v-else-if="product.description">{{ product.description }}</p>
              <p v-if="product.code" class="pdp-hint">Reference {{ product.code }}</p>
            </div>
          </details>
          <details v-if="passportHtml" class="pdp-accord">
            <summary>Material Passport +</summary>
            <div class="k-pdp-detail" v-html="passportHtml"></div>
          </details>
          <details class="pdp-accord">
            <summary>Delivery &amp; returns +</summary>
            <div class="k-pdp-detail">
              <p>Worldwide delivery with DHL tracking for international orders. In Qatar, same-day and eligible two-hour express delivery are available. Returns are subject to our published policy.</p>
              <p><a data-to="/page/shipping" href="page/shipping">Shipping information</a> · <a data-to="/page/returns-and-exchanges" href="page/returns-and-exchanges">Returns policy</a></p>
            </div>
          </details>
          <details class="pdp-accord">
            <summary>Gift presentation &amp; personalisation +</summary>
            <div class="k-pdp-detail">
              <div class="k-gift-inline" aria-label="Gift presentation and personalisation">
                <div class="k-gift-tabs" role="tablist" aria-label="Gift options">
                  <button type="button" role="tab" :aria-selected="giftTab === 'box'" :class="{ active: giftTab === 'box' }" @click="giftTab = 'box'">GIFT BOX</button>
                  <button type="button" role="tab" :aria-selected="giftTab === 'initials'" :class="{ active: giftTab === 'initials' }" @click="giftTab = 'initials'">MONOGRAMMING INITIALS</button>
                </div>
                <div v-show="giftTab === 'box'" class="k-gift-panel" role="tabpanel">
                  <img :src="'assets/kashperia-gift-box-ribbon.jpg'" alt="Kashperia gift box tied with satin ribbon" loading="lazy" />
                  <p>Discover our signature gift presentation, finished with a satin ribbon. Packaging may vary by creation.</p>
                </div>
                <div v-show="giftTab === 'initials'" class="k-gift-panel" role="tabpanel">
                  <div class="k-monogram-preview" aria-label="Monogram initials preview"><span>{{ monogram }}</span></div>
                  <div class="k-monogram-entry">
                    <label for="k-monogram-initials">YOUR INITIALS</label>
                    <input id="k-monogram-initials" v-model="initials" type="text" maxlength="3" placeholder="e.g. AB" aria-describedby="k-monogram-note" />
                    <p id="k-monogram-note">A preview of your initials. Hand embroidery, font, placement and availability are confirmed by our team for eligible pieces.</p>
                    <a data-to="/page/personal-stylist-and-design-team" href="page/personal-stylist-and-design-team">Enquire about monogramming</a>
                  </div>
                </div>
              </div>
            </div>
          </details>
          <details class="pdp-accord">
            <summary>Garment care +</summary>
            <div class="k-pdp-detail" v-html="careHtml"></div>
          </details>
          <details class="pdp-accord">
            <summary>Payment &amp; security +</summary>
            <div class="k-pdp-detail">
              <p>Major credit cards accepted online. Payments are processed through an approved secure gateway, with additional bank authentication where applicable.</p>
            </div>
          </details>
          <details class="pdp-accord">
            <summary>Need assistance? Contact us +</summary>
            <div class="k-pdp-detail">
              <p>For sizing, gifts or bespoke requests, our Client Services and Personal Stylist &amp; Design Team are at your service.</p>
              <p><a data-to="/contact" href="contact">Contact us</a> · <a :href="whatsappLink()" target="_blank" rel="noopener noreferrer">WhatsApp {{ house.whatsappLabel }}</a></p>
            </div>
          </details>
        </div>
      </div>

      <section v-if="related.length" class="k-recommend" aria-label="Related creations">
        <div class="k-recommend-head">
          <span class="eyebrow">CURATED FOR YOU</span>
          <h2 class="serif">You may also be interested in</h2>
        </div>
        <div class="k-recommend-grid">
          <ProductCard v-for="p in related" :key="p.id" :product="p" />
        </div>
      </section>

      <section class="k-advisors" aria-label="Meet our Style Advisors">
        <div class="k-advisors-content">
          <span class="eyebrow">PERSONAL SERVICE · THE HOUSE OF KASHPERIA</span>
          <h2 class="serif">Meet our Style Advisors</h2>
          <p>We are available to help you with styling suggestions and inspiration tailored for you. Book an appointment in one of our Boutiques or via a video conference with a Style Advisor to receive personalized styling advice based on your tastes and interests.</p>
          <RouterLink to="/page/client-services" custom v-slot="{ navigate }">
            <button type="button" class="k-advisors-button" @click="navigate">ENQUIRE ABOUT AN APPOINTMENT <span aria-hidden="true">↗</span></button>
          </RouterLink>
        </div>
        <div class="k-advisors-image">
          <img :src="'assets/personal-stylist/a-conversation-personal-stylist.jpg'" alt="A Kashperia Style Advisor in conversation with a client" loading="lazy" />
        </div>
      </section>
      <div class="pdp-bottom">
        <span class="eyebrow">THE HOUSE OF KASHPERIA</span>
        <h2 class="serif">Every detail has a story.</h2>
        <p class="pdp-hint">Discover the people, materials and craftsmanship behind our collections.</p>
      </div>

      <Teleport to="body">
        <div class="k-mobile-pdp-sticky">
          <button type="button" @click="addToBag(false)">ADD TO BAG</button>
          <button type="button" @click="askAdvisor">ASK AN ADVISOR</button>
        </div>
      </Teleport>
    </template>
  </main>
</template>
