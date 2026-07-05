<script setup>
import { onMounted, ref, watch } from 'vue'

import { fetchBrands, fetchCategories, fetchColors, fetchSizes } from '@/api/resources'
import { useFilterStore } from '@/stores/filters'

const filters = useFilterStore()

const categories = ref([])
const brands = ref([])
const sizes = ref([])
const colors = ref([])

// Local price inputs, committed to the store on blur/enter so we don't refetch per keystroke.
const minInput = ref(filters.minPrice ?? '')
const maxInput = ref(filters.maxPrice ?? '')

/** Brands + their counts, scoped to the selected category. */
async function loadBrands() {
  brands.value =
    (await fetchBrands({ main_category_id: filters.category?.id ?? null }).catch(() => [])) || []
}

/** Sizes, scoped to the selected category + brand. */
async function loadSizes() {
  const szs = await fetchSizes({
    main_category_id: filters.category?.id ?? null,
    brand_id: filters.brand?.id ?? null,
  }).catch(() => [])
  sizes.value = (szs || []).filter((s) => s.size)
}

onMounted(async () => {
  const [cats, cols] = await Promise.all([
    fetchCategories().catch(() => []),
    fetchColors().catch(() => []),
    loadBrands(),
    loadSizes(),
  ])
  categories.value = cats || []
  colors.value = (cols || []).filter((c) => c.color)
})

// Category drives the brand list/counts; category + brand drive the sizes.
watch(() => filters.category?.id, loadBrands)
watch(() => [filters.category?.id, filters.brand?.id], loadSizes)

function pickCategory(cat) {
  filters.category = filters.category?.id === cat.id ? null : { id: cat.id, name: cat.name }
}

function pickBrand(brand) {
  filters.brand = filters.brand?.id === brand.id ? null : { id: brand.id, name: brand.name }
}

function pickSize(size) {
  filters.size = filters.size === size ? null : size
}

function pickColor(color) {
  filters.color = filters.color === color ? null : color
}

function applyPrice() {
  filters.minPrice = minInput.value === '' ? null : Number(minInput.value)
  filters.maxPrice = maxInput.value === '' ? null : Number(maxInput.value)
}

function clearAll() {
  filters.reset()
  minInput.value = ''
  maxInput.value = ''
}
</script>

<template>
  <aside class="fs">
    <div class="fs__head">
      <h2 class="fs__title">Filters</h2>
      <button v-if="filters.activeCount" class="fs__clear" @click="clearAll">Clear all</button>
    </div>

    <!-- Availability -->
    <section class="fs__group">
      <div class="fs__label">Availability</div>
      <label class="fs__toggle">
        <input v-model="filters.inStockOnly" type="checkbox" class="fs__toggle-input" />
        <span class="fs__toggle-track"><span class="fs__toggle-thumb"></span></span>
        <span class="fs__toggle-text">Exclude out of stock</span>
      </label>
    </section>

    <!-- Category -->
    <section v-if="categories.length" class="fs__group">
      <div class="fs__label">Category</div>
      <div class="fs__options">
        <button
          v-for="cat in categories"
          :key="cat.id"
          class="fs__opt"
          :class="{ 'fs__opt--on': filters.category?.id === cat.id }"
          @click="pickCategory(cat)"
        >
          <span>{{ cat.name }}</span>
          <span class="fs__count">{{ cat.product_count ?? '' }}</span>
        </button>
      </div>
    </section>

    <!-- Brand -->
    <section v-if="brands.length" class="fs__group">
      <div class="fs__label">Brand</div>
      <div class="fs__options">
        <button
          v-for="brand in brands"
          :key="brand.id"
          class="fs__opt"
          :class="{ 'fs__opt--on': filters.brand?.id === brand.id }"
          @click="pickBrand(brand)"
        >
          <span>{{ brand.name }}</span>
          <span class="fs__count">{{ brand.product_count ?? '' }}</span>
        </button>
      </div>
    </section>

    <!-- Size -->
    <section v-if="sizes.length" class="fs__group">
      <div class="fs__label">Size</div>
      <div class="fs__chips">
        <button
          v-for="sz in sizes"
          :key="sz.size"
          class="fs__chip"
          :class="{ 'fs__chip--on': filters.size === sz.size }"
          @click="pickSize(sz.size)"
        >
          {{ sz.size }}
        </button>
      </div>
    </section>

    <!-- Colour -->
    <section v-if="colors.length" class="fs__group">
      <div class="fs__label">Colour</div>
      <div class="fs__chips">
        <button
          v-for="c in colors"
          :key="c.color"
          class="fs__chip"
          :class="{ 'fs__chip--on': filters.color === c.color }"
          @click="pickColor(c.color)"
        >
          {{ c.color }}
        </button>
      </div>
    </section>

    <!-- Price -->
    <section class="fs__group">
      <div class="fs__label">Price</div>
      <div class="fs__price">
        <input
          v-model="minInput"
          class="fs__price-input"
          type="number"
          min="0"
          placeholder="Min"
          @change="applyPrice"
          @keyup.enter="applyPrice"
        />
        <span class="fs__price-sep">–</span>
        <input
          v-model="maxInput"
          class="fs__price-input"
          type="number"
          min="0"
          placeholder="Max"
          @change="applyPrice"
          @keyup.enter="applyPrice"
        />
      </div>
    </section>
  </aside>
</template>

<style scoped>
.fs {
  width: 100%;
  min-width: 0;
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: 20px;
  padding: 24px 22px;
  box-shadow: 0 2px 6px rgba(28, 27, 24, 0.05);
  /* Fixed-height area: the filter list scrolls inside this box, the card never
     grows to match the product grid. */
  max-height: calc(100vh - 118px);
  overflow-y: auto;
}

/* Long option labels/counts must not force the card wider than its column. */
.fs__opt {
  min-width: 0;
}

.fs__opt > span:first-child {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.fs__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}

.fs__title {
  font-family: var(--font-display);
  font-size: 20px;
  font-weight: 700;
  margin: 0;
}

.fs__clear {
  font-size: 12px;
  color: var(--gold);
  font-weight: 600;
  cursor: pointer;
  text-decoration: underline;
  text-underline-offset: 3px;
}

.fs__group {
  padding: 18px 0;
  border-top: 1px solid var(--line-soft);
}

.fs__group:first-of-type {
  border-top: none;
  padding-top: 0;
}

.fs__label {
  font-family: var(--font-display);
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--muted-2);
  margin-bottom: 12px;
}

.fs__options {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.fs__opt {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  width: 100%;
  text-align: left;
  padding: 9px 12px;
  border-radius: 10px;
  font-size: 14px;
  color: var(--ink-soft);
  cursor: pointer;
  transition: all 0.12s ease;
}

.fs__opt:hover {
  background: #f5f3ee;
}

.fs__opt--on {
  background: var(--gold);
  color: var(--white-warm);
  font-weight: 600;
}

.fs__count {
  font-size: 12px;
  color: var(--muted-2);
}

.fs__opt--on .fs__count {
  color: rgba(255, 255, 255, 0.75);
}

.fs__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.fs__chip {
  padding: 7px 14px;
  border-radius: 99px;
  border: 1.5px solid var(--line);
  background: var(--card);
  color: var(--ink);
  font-size: 13px;
  font-weight: 600;
  font-family: var(--font-display);
  cursor: pointer;
  transition: all 0.12s ease;
}

.fs__chip:hover {
  border-color: var(--gold);
}

.fs__chip--on {
  border-color: var(--gold);
  background: var(--gold);
  color: var(--white-warm);
}

.fs__price {
  display: flex;
  align-items: center;
  gap: 10px;
}

.fs__price-input {
  width: 100%;
  min-width: 0;
  background: var(--card);
  border: 1.5px solid var(--line);
  border-radius: 12px;
  padding: 10px 12px;
  font-size: 14px;
  outline: none;
  transition: border-color 0.15s ease;
}

.fs__price-input:focus {
  border-color: var(--gold);
}

.fs__price-sep {
  color: var(--muted-2);
}

/* ===== Availability toggle ===== */
.fs__toggle {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  user-select: none;
}

.fs__toggle-input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.fs__toggle-track {
  flex: none;
  width: 40px;
  height: 23px;
  border-radius: 99px;
  background: var(--line);
  border: 1px solid var(--line);
  position: relative;
  transition: background 0.18s ease;
}

.fs__toggle-thumb {
  position: absolute;
  top: 2px;
  left: 2px;
  width: 17px;
  height: 17px;
  border-radius: 50%;
  background: #fff;
  box-shadow: 0 1px 3px rgba(20, 19, 16, 0.3);
  transition: transform 0.18s var(--ease-out);
}

.fs__toggle-input:checked + .fs__toggle-track {
  background: var(--gold);
  border-color: var(--gold);
}

.fs__toggle-input:checked + .fs__toggle-track .fs__toggle-thumb {
  transform: translateX(17px);
}

.fs__toggle-input:focus-visible + .fs__toggle-track {
  box-shadow: 0 0 0 3px rgba(var(--gold-rgb), 0.28);
}

.fs__toggle-text {
  font-size: 14px;
  color: var(--ink-soft);
  font-weight: 600;
}
</style>
