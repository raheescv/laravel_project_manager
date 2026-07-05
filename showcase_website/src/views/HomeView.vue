<script setup>
import { onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

import { fetchBrands, fetchCategories, fetchProducts } from '@/api/resources'
import ErrorBanner from '@/components/ErrorBanner.vue'
import StepDots from '@/components/StepDots.vue'
import { useBranchStore } from '@/stores/branch'
import { useFilterStore } from '@/stores/filters'

const router = useRouter()
const branchStore = useBranchStore()
const filters = useFilterStore()

const categories = ref([])
const loading = ref(true)
const error = ref(null)
const stats = ref({ products: '—', brands: '—' })
const gridEl = ref(null)

async function load() {
  loading.value = true
  error.value = null
  try {
    const [cats, brands, page] = await Promise.all([
      fetchCategories(),
      fetchBrands().catch(() => []),
      fetchProducts({ per_page: 1, branch_id: branchStore.currentId, type: 'product' }).catch(
        () => null,
      ),
    ])
    categories.value = cats || []
    stats.value = {
      products: page?.pagination?.total ?? '—',
      brands: Array.isArray(brands) ? brands.length : '—',
    }
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

onMounted(load)
watch(() => branchStore.currentId, load)

function selectCategory(cat) {
  filters.category = { id: cat.id, name: cat.name }
  filters.brand = null
  filters.size = null
  router.push('/sizes')
}

function browseAll() {
  filters.reset()
  router.push('/products')
}

function scrollToCategories() {
  gridEl.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}
</script>

<template>
  <div class="anim-screen">
    <!-- HERO -->
    <!-- <section class="hero">
      <div class="hero__glow hero__glow--tr"></div>
      <div class="hero__glow hero__glow--bl"></div>
      <div class="container hero__inner">
        <div>
          <div class="kicker">
            <span class="kicker__line" style="background: var(--gold-bright)"></span>
            <span class="kicker__text" style="color: var(--gold-bright)">
              Sneakers · Streetwear · Art toys
            </span>
          </div>
          <h1 class="hero__title">
            Everything you<br />can <span class="hero__accent">run in.</span>
          </h1>
          <p class="hero__sub">
            Every sneaker, silhouette and collectible is stocked, checked and fitted in person. Tell
            us what you're after — we'll show you exactly what's on the shelf at
            {{ branchStore.currentName }}.
          </p>
          <div class="hero__actions">
            <button class="hero__cta" @click="scrollToCategories">Start browsing ↓</button>
            <router-link to="/branches" class="hero__link">Find a branch</router-link>
          </div>
          <div class="hero__stats">
            <div>
              <div class="hero__stat-num">{{ stats.products }}</div>
              <div class="hero__stat-label">Styles in store</div>
            </div>
            <div>
              <div class="hero__stat-num">{{ stats.brands }}</div>
              <div class="hero__stat-label">Curated brands</div>
            </div>
            <div>
              <div class="hero__stat-num">{{ branchStore.branches.length || '—' }}</div>
              <div class="hero__stat-label">Branches citywide</div>
            </div>
          </div>
        </div>
        <div class="hero__art">
          <span class="hero__art-label">in-store fitting, every pair</span>
          <div class="hero__art-card">
            <div class="hero__art-kicker">This week's pick</div>
            <div class="hero__art-title">Visit {{ branchStore.currentName }}</div>
          </div>
        </div>
      </div>
    </section> -->

    <!-- CATEGORY GRID -->
    <main ref="gridEl" class="container catmain">
      <StepDots :step="1" label="Category" />
      <h2 class="catmain__title">What are you looking for?</h2>

      <ErrorBanner v-if="error" :message="error" @retry="load" />

      <div v-else class="catmain__grid">
        <template v-if="loading">
          <div v-for="n in 4" :key="n" class="skeleton" style="height: 264px; border-radius: 22px" />
        </template>
        <template v-else>
          <button
            v-for="(cat, i) in categories"
            :key="cat.id"
            class="cat-card"
            @click="selectCategory(cat)"
          >
            <div class="cat-card__num">{{ String(i + 1).padStart(2, '0') }}</div>
            <div class="cat-card__media placeholder-art">
              <span class="cat-card__imglabel">{{ cat.name }}</span>
            </div>
            <div class="cat-card__body">
              <div>
                <div class="cat-card__name">{{ cat.name }}</div>
                <div class="cat-card__count">
                  {{ cat.product_count ?? '—' }} products in this range
                </div>
              </div>
              <span class="cat-card__arrow">→</span>
            </div>
          </button>
          <button v-if="!loading" class="cat-card cat-card--all" @click="browseAll">
            Browse everything →
          </button>
        </template>
      </div>

      <!-- VALUE STRIP -->
      <!-- <div class="values">
        <div class="value">
          <div class="value__icon">✓</div>
          <div>
            <div class="value__title">Fitted in person</div>
            <div class="value__sub">Every pair is tried on with a trained fitter before you buy.</div>
          </div>
        </div>
        <div class="value">
          <div class="value__icon">◷</div>
          <div>
            <div class="value__title">Live availability</div>
            <div class="value__sub">Stock shown here reflects the shelf at your chosen branch.</div>
          </div>
        </div>
        <div class="value">
          <div class="value__icon">↺</div>
          <div>
            <div class="value__title">30-day exchange</div>
            <div class="value__sub">Wrong fit? Swap sizes at any branch within 30 days.</div>
          </div>
        </div>
      </div> -->
    </main>
  </div>
</template>

<style scoped>
/* ===== Hero ===== */
.hero {
  background: var(--dark);
  color: var(--dark-ink);
  position: relative;
  overflow: hidden;
}

.hero__glow {
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
}

.hero__glow--tr {
  top: -140px;
  right: -60px;
  width: 480px;
  height: 480px;
  background: radial-gradient(circle, rgba(var(--gold-bright-rgb), 0.16) 0%, rgba(var(--gold-bright-rgb), 0) 70%);
}

.hero__glow--bl {
  bottom: -180px;
  left: -120px;
  width: 420px;
  height: 420px;
  background: radial-gradient(circle, rgba(var(--gold-bright-rgb), 0.08) 0%, rgba(var(--gold-bright-rgb), 0) 70%);
}

.hero__inner {
  padding-top: 84px;
  padding-bottom: 88px;
  position: relative;
  display: grid;
  grid-template-columns: 1.15fr 1fr;
  gap: 56px;
  align-items: center;
}

.hero__title {
  font-size: 64px;
  font-weight: 700;
  margin: 20px 0 18px;
  letter-spacing: -0.035em;
  line-height: 1.02;
  color: var(--white-warm);
}

.hero__accent {
  color: var(--gold-bright);
  font-style: italic;
  font-weight: 400;
}

.hero__sub {
  font-size: 16px;
  color: var(--dark-muted);
  line-height: 1.65;
  margin: 0 0 34px;
  max-width: 440px;
}

.hero__actions {
  display: flex;
  align-items: center;
  gap: 18px;
}

.hero__cta {
  background: var(--gold);
  color: #fff;
  border-radius: 99px;
  padding: 16px 36px;
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 15px;
  transition: all 0.2s ease;
  box-shadow: 0 8px 24px rgba(var(--gold-rgb), 0.35);
}

.hero__cta:hover {
  background: var(--gold-bright);
  transform: translateY(-2px);
  box-shadow: 0 14px 32px rgba(var(--gold-rgb), 0.45);
}

.hero__link {
  color: var(--dark-ink);
  font-family: var(--font-display);
  font-weight: 600;
  font-size: 14px;
  border-bottom: 2px solid rgba(var(--gold-bright-rgb), 0.5);
  padding-bottom: 2px;
  transition: border-color 0.15s ease;
}

.hero__link:hover {
  border-bottom-color: var(--gold-bright);
}

.hero__stats {
  display: flex;
  gap: 40px;
  margin-top: 52px;
  padding-top: 28px;
  border-top: 1px solid rgba(243, 239, 230, 0.12);
}

.hero__stat-num {
  font-family: var(--font-display);
  font-size: 26px;
  font-weight: 700;
  color: var(--gold-bright);
}

.hero__stat-label {
  font-size: 12px;
  color: var(--dark-muted);
  letter-spacing: 0.08em;
  text-transform: uppercase;
  margin-top: 2px;
}

.hero__art {
  height: 440px;
  border-radius: 24px;
  background: repeating-linear-gradient(45deg, #26221a, #26221a 12px, #2d2820 12px, #2d2820 24px);
  border: 1px solid rgba(var(--gold-bright-rgb), 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.hero__art-label {
  font-family: monospace;
  font-size: 13px;
  color: var(--dark-muted);
  background: rgba(23, 21, 15, 0.8);
  padding: 6px 14px;
  border-radius: 8px;
}

.hero__art-card {
  position: absolute;
  bottom: 22px;
  left: 22px;
  background: rgba(23, 21, 15, 0.85);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(var(--gold-bright-rgb), 0.35);
  border-radius: 14px;
  padding: 14px 18px;
}

.hero__art-kicker {
  font-size: 11px;
  color: var(--gold-bright);
  font-weight: 600;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.hero__art-title {
  font-family: var(--font-display);
  font-weight: 600;
  font-size: 16px;
  color: var(--white-warm);
  margin-top: 3px;
}

/* ===== Categories ===== */
.catmain {
  padding-top: 72px;
  padding-bottom: 96px;
}

.catmain__title {
  font-size: 44px;
  font-weight: 700;
  margin: 14px 0 44px;
}

.catmain__grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 18px;
}

.cat-card {
  border-radius: 22px;
  overflow: hidden;
  cursor: pointer;
  background: var(--card);
  border: 1px solid var(--line);
  transition: all 0.25s var(--ease-out);
  box-shadow: 0 2px 6px rgba(28, 27, 24, 0.05);
  position: relative;
  text-align: left;
  padding: 0;
}

.cat-card:hover {
  border-color: var(--gold);
  transform: translateY(-6px);
  box-shadow:
    0 24px 48px rgba(28, 27, 24, 0.16),
    0 0 0 1px var(--gold);
}

.cat-card--all {
  border: 1.5px dashed #c9c4b8;
  background: transparent;
  box-shadow: none;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-display);
  font-weight: 600;
  font-size: 15px;
  color: var(--muted);
  min-height: 264px;
}

.cat-card--all:hover {
  border-color: var(--gold);
  color: var(--gold);
  background: rgba(var(--gold-rgb), 0.04);
  transform: none;
  box-shadow: none;
}

.cat-card__num {
  position: absolute;
  top: 16px;
  left: 18px;
  font-family: var(--font-display);
  font-size: 13px;
  font-weight: 700;
  color: var(--gold);
  background: rgba(255, 255, 255, 0.9);
  border-radius: 8px;
  padding: 4px 10px;
  z-index: 2;
}

.cat-card__media {
  height: 180px;
}

.cat-card__imglabel {
  font-family: monospace;
  font-size: 12px;
  color: var(--muted-2);
  background: rgba(255, 255, 255, 0.75);
  padding: 4px 10px;
  border-radius: 6px;
}

.cat-card__body {
  padding: 20px 22px 22px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.cat-card__name {
  font-family: var(--font-display);
  font-weight: 600;
  font-size: 19px;
  letter-spacing: -0.01em;
  color: var(--ink);
}

.cat-card__count {
  font-size: 13px;
  color: var(--muted);
  margin-top: 4px;
}

.cat-card__arrow {
  font-size: 18px;
  color: var(--gold);
}

/* ===== Value strip ===== */
.values {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 18px;
  margin-top: 64px;
}

.value {
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: 18px;
  padding: 26px 28px;
  display: flex;
  gap: 16px;
  align-items: flex-start;
}

.value__icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: #f2ede4;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 17px;
  color: var(--gold);
  flex-shrink: 0;
  font-family: var(--font-display);
  font-weight: 700;
}

.value__title {
  font-family: var(--font-display);
  font-weight: 600;
  font-size: 16px;
}

.value__sub {
  font-size: 13px;
  color: var(--muted);
  margin-top: 4px;
  line-height: 1.5;
}

/* ===== Responsive ===== */
@media (max-width: 1024px) {
  .catmain__grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .hero__inner {
    grid-template-columns: 1fr;
  }

  .hero__art {
    display: none;
  }
}

@media (max-width: 640px) {
  .hero__title {
    font-size: 42px;
  }

  .catmain__title {
    font-size: 32px;
  }

  .catmain__grid,
  .values {
    grid-template-columns: 1fr;
  }
}
</style>
