<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import { fetchBrands } from '@/api/resources'
import ErrorBanner from '@/components/ErrorBanner.vue'
import StepDots from '@/components/StepDots.vue'
import { useFilterStore } from '@/stores/filters'
import { initialOf, tintFor } from '@/utils/format'

const router = useRouter()
const filters = useFilterStore()

const brands = ref([])
const loading = ref(true)
const error = ref(null)

async function load() {
  loading.value = true
  error.value = null
  try {
    brands.value =
      (await fetchBrands({ main_category_id: filters.category?.id ?? null })) || []
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

onMounted(load)

function selectBrand(brand) {
  filters.brand = brand ? { id: brand.id, name: brand.name } : null
  router.push('/sizes')
}
</script>

<template>
  <main class="container screen anim-screen">
    <router-link to="/" class="btn-back">← Back</router-link>
    <div style="margin-top: 24px">
      <StepDots :step="2" label="Brand" />
    </div>
    <h1 class="screen__title">Pick a brand</h1>
    <p v-if="filters.category" class="screen__sub">
      Brands with stock in <strong>{{ filters.category.name }}</strong>
    </p>

    <ErrorBanner v-if="error" :message="error" @retry="load" />

    <div v-else class="brand-grid">
      <template v-if="loading">
        <div v-for="n in 6" :key="n" class="skeleton" style="height: 150px; border-radius: 20px" />
      </template>
      <template v-else>
        <button
          v-for="brand in brands"
          :key="brand.id"
          class="brand-card"
          @click="selectBrand(brand)"
        >
          <div class="brand-card__head">
            <img
              v-if="brand.image_path"
              :src="brand.image_path"
              :alt="brand.name"
              class="brand-card__logo"
              loading="lazy"
            />
            <div
              v-else
              class="brand-card__avatar"
              :style="{ background: tintFor(brand.name) }"
            >
              {{ initialOf(brand.name) }}
            </div>
            <div>
              <div class="brand-card__name">{{ brand.name }}</div>
              <div class="brand-card__count">{{ brand.product_count ?? '—' }} products</div>
            </div>
          </div>
        </button>
        <button class="brand-card brand-card--any" @click="selectBrand(null)">
          Show all brands →
        </button>
      </template>
    </div>
  </main>
</template>

<style scoped>
.screen {
  padding-top: 64px;
  padding-bottom: 96px;
}

.screen__title {
  font-size: 52px;
  font-weight: 700;
  margin: 16px 0 10px;
}

.screen__sub {
  font-size: 15px;
  color: var(--muted);
  margin: 0 0 40px;
}

.screen__sub strong {
  color: var(--ink);
  font-weight: 600;
}

.brand-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 18px;
}

.brand-card {
  border-radius: 20px;
  cursor: pointer;
  background: var(--card);
  border: 1px solid var(--line);
  padding: 28px 26px;
  transition: all 0.25s var(--ease-out);
  box-shadow: 0 2px 6px rgba(28, 27, 24, 0.05);
  text-align: left;
}

.brand-card:hover {
  border-color: var(--gold);
  transform: translateY(-4px);
  box-shadow:
    0 18px 40px rgba(28, 27, 24, 0.13),
    0 0 0 1px var(--gold);
}

.brand-card__head {
  display: flex;
  align-items: center;
  gap: 18px;
}

.brand-card__avatar {
  width: 58px;
  height: 58px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 23px;
  color: var(--white-warm);
  box-shadow: inset 0 -10px 18px rgba(0, 0, 0, 0.15);
  flex-shrink: 0;
}

.brand-card__logo {
  width: 58px;
  height: 58px;
  border-radius: 16px;
  object-fit: contain;
  background: var(--white-warm);
  border: 1px solid var(--line);
  padding: 6px;
  flex-shrink: 0;
}

.brand-card__name {
  font-family: var(--font-display);
  font-weight: 600;
  font-size: 19px;
  letter-spacing: -0.01em;
  color: var(--ink);
}

.brand-card__count {
  font-size: 13px;
  color: var(--muted);
  margin-top: 3px;
}

.brand-card--any {
  border: 1.5px dashed #c9c4b8;
  background: transparent;
  box-shadow: none;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 15px;
  color: var(--muted);
  font-weight: 600;
  font-family: var(--font-display);
  min-height: 116px;
}

.brand-card--any:hover {
  border-color: var(--gold);
  color: var(--gold);
  background: rgba(var(--gold-rgb), 0.04);
  transform: none;
  box-shadow: none;
}

@media (max-width: 1024px) {
  .brand-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 640px) {
  .screen__title {
    font-size: 36px;
  }

  .brand-grid {
    grid-template-columns: 1fr;
  }
}
</style>
