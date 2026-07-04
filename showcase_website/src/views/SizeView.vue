<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import { fetchSizes } from '@/api/resources'
import ErrorBanner from '@/components/ErrorBanner.vue'
import StepDots from '@/components/StepDots.vue'
import { useFilterStore } from '@/stores/filters'

const router = useRouter()
const filters = useFilterStore()

const sizes = ref([])
const loading = ref(true)
const error = ref(null)
const selected = ref(filters.size)

const scopeLabel = computed(() =>
  [filters.brand?.name, filters.category?.name].filter(Boolean).join(' · '),
)

const youngSizes = computed(() => sizes.value.filter((s) => s.group === 'young'))
const adultSizes = computed(() => sizes.value.filter((s) => s.group !== 'young'))

async function load() {
  loading.value = true
  error.value = null
  try {
    const params = {
      main_category_id: filters.category?.id ?? null,
      brand_id: filters.brand?.id ?? null,
    }
    sizes.value = ((await fetchSizes(params)) || []).filter((s) => s.size)
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

onMounted(load)

function selectSize(size) {
  selected.value = size
  finish(false)
}

function finish(anySize = false) {
  filters.size = anySize ? null : selected.value
  router.push('/products')
}
</script>

<template>
  <main class="container screen anim-screen">
    <router-link to="/brands" class="btn-back">← Back</router-link>
    <div style="margin-top: 24px">
      <StepDots :step="3" label="Size" />
    </div>
    <h1 class="screen__title">Your size</h1>
    <p class="screen__sub">
      <template v-if="scopeLabel">Sizes available in <strong>{{ scopeLabel }}</strong>. </template>Unsure?
      Our fitters will measure you in store.
    </p>

    <ErrorBanner v-if="error" :message="error" @retry="load" />

    <div v-else class="size-wrap">
      <div v-if="loading" class="size-row">
        <div
          v-for="n in 8"
          :key="n"
          class="skeleton"
          style="width: 82px; height: 66px; border-radius: 16px"
        />
      </div>
      <template v-else>
        <template v-if="adultSizes.length">
          <div class="size-group">Adult</div>
          <div class="size-row">
            <button
              v-for="sz in adultSizes"
              :key="`a-${sz.size}`"
              class="size-chip"
              :class="{ 'size-chip--on': selected === sz.size }"
              @click="selectSize(sz.size)"
            >
              {{ sz.size }}
            </button>
          </div>
        </template>
        <template v-if="youngSizes.length">
          <div class="size-group">Young</div>
          <div class="size-row">
            <button
              v-for="sz in youngSizes"
              :key="`y-${sz.size}`"
              class="size-chip"
              :class="{ 'size-chip--on': selected === sz.size }"
              @click="selectSize(sz.size)"
            >
              {{ sz.size }}
            </button>
          </div>
        </template>
        <div v-if="!sizes.length" class="size-none">No sizes on record — skip this step.</div>
      </template>
    </div>

    <div class="size-actions">
      <button class="size-skip" @click="finish(true)">Skip — any size →</button>
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
  font-size: 14px;
  color: var(--muted);
  margin: 0 0 36px;
}

.screen__sub strong {
  color: var(--ink);
  font-weight: 600;
}

.size-wrap {
  display: flex;
  flex-direction: column;
  gap: 14px;
  max-width: 780px;
}

.size-row {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.size-group {
  font-family: var(--font-display);
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: var(--gold);
  margin-top: 6px;
}

.size-chip {
  min-width: 82px;
  height: 66px;
  padding: 0 16px;
  border-radius: 16px;
  border: 1.5px solid var(--line);
  background: var(--card);
  color: var(--ink);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-display);
  font-weight: 600;
  font-size: 19px;
  cursor: pointer;
  transition: all 0.15s var(--ease-out);
}

.size-chip:hover {
  border-color: var(--gold);
  transform: translateY(-2px);
}

.size-chip--on {
  border-color: var(--gold);
  background: var(--gold);
  color: var(--white-warm);
  box-shadow: 0 8px 20px rgba(var(--gold-rgb), 0.35);
}

.size-none {
  font-size: 14px;
  color: var(--muted);
  padding: 16px 0;
}

.size-actions {
  display: flex;
  gap: 18px;
  margin-top: 48px;
  align-items: center;
}

.size-skip {
  font-size: 14px;
  color: var(--muted);
  cursor: pointer;
  font-weight: 500;
  text-decoration: underline;
  text-underline-offset: 3px;
  transition: color 0.15s ease;
}

.size-skip:hover {
  color: var(--gold);
}

@media (max-width: 640px) {
  .screen__title {
    font-size: 36px;
  }
}
</style>
