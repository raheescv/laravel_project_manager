<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

import { fetchProducts } from '@/api/resources'
import { money } from '@/format'
import { useBagStore } from '@/stores/bag'
import { LISTING, useCatalogStore } from '@/stores/catalog'
import { priceOf, productImage, productRoute } from '@/utils/catalog'

const bag = useBagStore()
const catalog = useCatalogStore()
const router = useRouter()

const titles = { cart: 'Your shopping bag', search: 'Search the House', menu: 'Explore' }
const title = computed(() => titles[bag.drawer] || 'Discover')

/** The Maison list of the reference menu drawer. */
const maisonLinks = [
  { label: 'Maison', to: '/maison' },
  { label: 'Standards', to: '/page/standards' },
  { label: 'KASHPERIA PRIVÉ', to: '/page/kashperia-prive' },
  { label: 'BEYOND KASHPERIA', to: '/page/beyond-kashperia' },
  { label: 'Contact', to: '/contact' },
  { label: 'Authenticate Your Product', to: '/page/authenticate-your-product' },
  { label: 'Limited Editions', to: '/page/limited-editions' },
]

// ---- search ------------------------------------------------------------------
const query = ref('')
const results = ref([])
const searching = ref(false)
const searchInput = ref(null)
let searchTimer = null
let searchSeq = 0

watch(query, (q) => {
  clearTimeout(searchTimer)
  if (q.trim().length < 2) {
    results.value = []
    searching.value = false
    return
  }
  searching.value = true
  searchTimer = setTimeout(() => runSearch(q.trim()), 250)
})

async function runSearch(q) {
  const seq = ++searchSeq
  try {
    const res = await fetchProducts({ ...LISTING, search: q, per_page: 15 })
    if (seq === searchSeq) results.value = Array.isArray(res?.data) ? res.data : []
  } catch {
    if (seq === searchSeq) results.value = []
  } finally {
    if (seq === searchSeq) searching.value = false
  }
}

watch(
  () => bag.drawer,
  async (type) => {
    if (type === 'menu') catalog.loadCategories()
    if (type === 'search') {
      await nextTick()
      searchInput.value?.focus()
    }
  },
)

function go(to) {
  bag.closeDrawer()
  router.push(to)
}
</script>

<template>
  <div class="overlay" :class="{ show: bag.drawer }" @click="bag.closeDrawer()"></div>
  <aside class="drawer" :class="{ show: bag.drawer }" :aria-hidden="bag.drawer ? 'false' : 'true'" aria-label="Kashperia panel">
    <div class="drawerHead">
      <h2 id="drawerTitle" class="serif">{{ title }}</h2>
      <button type="button" class="close" aria-label="Close" @click="bag.closeDrawer()">×</button>
    </div>
    <div id="drawerContent">
      <div v-if="bag.drawer === 'menu'" class="menuGrid">
        <div>
          <h3 class="serif">Collections</h3>
          <button v-for="link in catalog.collectionLinks" :key="link.slug" type="button" @click="go(`/collection/${link.slug}`)">{{ link.label }}</button>
        </div>
        <div>
          <h3 class="serif">The Maison</h3>
          <button v-for="link in maisonLinks" :key="link.to" type="button" @click="go(link.to)">{{ link.label }}</button>
        </div>
      </div>

      <template v-else-if="bag.drawer === 'search'">
        <p class="muted">Search collections and styles</p>
        <input ref="searchInput" v-model="query" class="select" placeholder="Search Kashperia" type="search" aria-label="Search Kashperia" />
        <div id="results">
          <p v-if="!query.trim()" class="small">Try Topaz, Super 200, Sea Island or Legacy.</p>
          <p v-else-if="searching" class="small">Searching the House…</p>
          <p v-else-if="query.trim().length >= 2 && !results.length" class="small">No creations match “{{ query.trim() }}”.</p>
          <button v-for="p in results" :key="p.id" type="button" class="k-search-hit" @click="go(productRoute(p))">
            <img v-if="productImage(p)" :src="productImage(p)" alt="" loading="lazy" />
            <span>
              {{ p.name }}
              <span class="muted">· {{ p.main_category?.name || 'Kashperia' }}<template v-if="p.sub_category"> / {{ p.sub_category.name }}</template></span>
            </span>
            <span v-if="priceOf(p).now" class="k-search-price">{{ money(priceOf(p).now) }}</span>
          </button>
        </div>
      </template>

      <template v-else-if="bag.drawer === 'cart'">
        <template v-if="bag.lines.length">
          <div v-for="line in bag.lines" :key="line.key" class="k-bag-item">
            <img :src="line.img || ''" alt="" />
            <div>
              <h3 class="serif">{{ line.name }}</h3>
              <p v-if="line.variant" class="pdp-hint">{{ line.variant }}</p>
              <p class="pdp-hint">{{ money(line.price) }}</p>
              <div class="k-bag-qty" aria-label="Quantity">
                <button type="button" aria-label="Decrease quantity" @click="bag.setQty(line.key, -1)">−</button>
                <span>{{ line.qty }}</span>
                <button type="button" aria-label="Increase quantity" @click="bag.setQty(line.key, 1)">+</button>
              </div>
              <button type="button" @click="bag.remove(line.key)">Remove</button>
            </div>
          </div>
          <p class="k-bag-total"><span>Subtotal</span><strong>{{ money(bag.total) }}</strong></p>
          <div class="k-bag-actions">
            <button type="button" @click="go('/checkout')">CONTINUE TO CHECKOUT</button>
            <button type="button" class="secondary" @click="bag.closeDrawer()">CONTINUE SHOPPING</button>
          </div>
          <p class="pdp-hint">Final prices and availability are confirmed securely at checkout.</p>
        </template>
        <p v-else class="muted" style="padding: 30px 0">Your shopping bag is empty.</p>
      </template>
    </div>
  </aside>
</template>
