<script setup>
import { computed } from 'vue'

import { resolveImage } from '@/api/client'
import { colorHex, formatPrice, initialOf, storeName, tintFor } from '@/utils/format'

const props = defineProps({
  product: { type: Object, required: true },
  compact: { type: Boolean, default: false },
})

const image = computed(
  () => resolveImage(props.product.thumbnail) || resolveImage(props.product.images?.[0]?.url),
)

const eyebrow = computed(
  () => props.product.brand?.name || props.product.main_category?.name || 'Product',
)
const glyph = computed(() => initialOf(eyebrow.value))

// Colour dots: brand tint → per-product hue, so photoless cards of the same
// brand still read as distinct.
const c1 = computed(() => tintFor(eyebrow.value))
const c2 = computed(
  () => colorHex(props.product.color) || tintFor(`${props.product.name || ''}${props.product.id}`),
)

const stock = computed(() => {
  const p = props.product
  if (p.is_out_of_stock || p.stock_quantity_availability_status === 'out_of_stock') {
    return { label: 'Out of stock', cls: 'is-out', dim: true }
  }
  if (p.stock_quantity_availability_status === 'available_in_other_branches') {
    return { label: 'Other branches', cls: 'is-other', dim: true }
  }
  if (p.is_low_stock) return { label: 'Low stock', cls: 'is-low', dim: false }
  return { label: 'In stock', cls: 'is-in', dim: false, subtle: true }
})
</script>

<template>
  <router-link
    :to="`/product/${product.id}`"
    class="card"
    :class="{ 'card--compact': compact, 'card--dim': stock.dim }"
  >
    <div class="card__media">
      <img v-if="image" class="card__photo" :src="image" :alt="product.name" loading="lazy" />
      <template v-else>
        <span class="card__plate" aria-hidden="true"></span>
        <span class="card__mono">{{ glyph }}</span>
        <span class="card__seal">{{ storeName }} · {{ eyebrow }}</span>
      </template>
      <span v-if="!stock.subtle" class="flag" :class="stock.cls">{{ stock.label }}</span>
      <span v-if="product.model" class="card__material">{{ product.model }}</span>
    </div>

    <div class="card__body">
      <div class="card__top">
        <div class="card__brand">{{ eyebrow }}</div>
        <span class="card__dots">
          <span class="card__dot" :style="{ background: c1 }"></span>
          <span class="card__dot" :style="{ background: c2 }"></span>
        </span>
      </div>
      <div class="card__name">{{ product.name }}</div>
      <div class="card__foot">
        <div class="card__price">{{ formatPrice(product.mrp) }}</div>
        <div v-if="product.size" class="card__size">Size {{ product.size }}</div>
        <div v-else-if="!stock.dim" class="card__stockline" :class="stock.cls">
          <span class="card__stockdot"></span>{{ stock.label }}
        </div>
      </div>
    </div>
  </router-link>
</template>

<style scoped>
.card {
  --paper: #f7f3ea;
  display: block;
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: 18px;
  padding: 14px;
  cursor: pointer;
  box-shadow: 0 1px 2px rgba(20, 19, 16, 0.04);
  transition:
    transform 0.4s var(--ease-out),
    box-shadow 0.4s var(--ease-out);
}

.card:hover {
  transform: translateY(-6px);
  box-shadow: 0 34px 70px rgba(20, 19, 16, 0.14);
}

.card--dim {
  opacity: 0.74;
}

.card--dim:hover {
  opacity: 1;
}

/* ---- media / monogram plate ---- */
.card__media {
  position: relative;
  aspect-ratio: 1 / 1;
  border-radius: 12px;
  overflow: hidden;
  background: var(--paper);
  box-shadow: inset 0 0 0 1px rgba(20, 19, 16, 0.05);
  display: flex;
  align-items: center;
  justify-content: center;
}

.card--compact .card__media {
  aspect-ratio: 4 / 3;
}

.card__photo {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.card__plate {
  position: absolute;
  inset: 16px;
  border: 1px solid rgba(20, 19, 16, 0.1);
  border-radius: 8px;
}

.card__plate::before,
.card__plate::after {
  content: '';
  position: absolute;
  width: 9px;
  height: 9px;
  border: 1px solid rgba(20, 19, 16, 0.28);
}

.card__plate::before {
  top: -1px;
  left: -1px;
  border-right: 0;
  border-bottom: 0;
}

.card__plate::after {
  bottom: -1px;
  right: -1px;
  border-left: 0;
  border-top: 0;
}

.card__mono {
  font-family: var(--font-display);
  font-weight: 400;
  font-size: 82px;
  line-height: 1;
  letter-spacing: -0.02em;
  color: var(--ink);
  opacity: 0.9;
  user-select: none;
  transition: transform 0.5s var(--ease-out);
}

.card:hover .card__mono {
  transform: scale(1.04);
}

.card--compact .card__mono {
  font-size: 58px;
}

.card__seal {
  position: absolute;
  bottom: 20px;
  left: 16px;
  right: 16px;
  text-align: center;
  font-size: 8.5px;
  font-weight: 700;
  letter-spacing: 0.28em;
  text-transform: uppercase;
  color: var(--muted-2);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.card--compact .card__seal {
  display: none;
}

/* ---- stock flag ---- */
.flag {
  position: absolute;
  top: 12px;
  left: 12px;
  z-index: 2;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.03em;
  border-radius: 99px;
  padding: 5px 11px;
  background: rgba(255, 255, 255, 0.85);
  border: 1px solid rgba(20, 19, 16, 0.06);
  backdrop-filter: blur(6px);
}

.flag.is-other {
  color: var(--gold-deep);
}

.flag.is-low {
  color: #fff;
  background: var(--red);
  border-color: transparent;
}

.flag.is-out {
  color: #fff;
  background: rgba(20, 19, 16, 0.9);
  border-color: transparent;
}

.card__material {
  position: absolute;
  bottom: 12px;
  right: 12px;
  z-index: 2;
  background: rgba(255, 255, 255, 0.9);
  color: var(--ink-soft);
  font-size: 11px;
  font-weight: 600;
  border-radius: 99px;
  padding: 5px 12px;
  backdrop-filter: blur(4px);
}

/* ---- body ---- */
.card__body {
  padding: 16px 6px 6px;
}

.card--compact .card__body {
  padding: 13px 4px 4px;
}

.card__top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.card__brand {
  font-size: 10px;
  color: var(--gold);
  font-weight: 700;
  letter-spacing: 0.16em;
  text-transform: uppercase;
}

.card__dots {
  display: inline-flex;
  gap: 5px;
  flex-shrink: 0;
}

.card__dot {
  width: 11px;
  height: 11px;
  border-radius: 50%;
  box-shadow:
    inset 0 0 0 1px rgba(0, 0, 0, 0.16),
    0 0 0 1.5px var(--card);
  display: inline-block;
}

.card__name {
  font-family: var(--font-display);
  font-weight: 500;
  font-size: 20px;
  line-height: 1.18;
  letter-spacing: -0.01em;
  margin: 11px 0 16px;
  color: var(--ink);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  min-height: 47px;
}

.card--compact .card__name {
  font-size: 16px;
  min-height: 0;
  margin: 9px 0 12px;
}

.card__foot {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 8px;
  padding-top: 13px;
  border-top: 1px solid var(--line);
}

.card__price {
  font-family: var(--font-display);
  font-weight: 600;
  font-size: 21px;
  letter-spacing: -0.01em;
  color: var(--ink);
}

.card--compact .card__price {
  font-size: 18px;
}

.card__size {
  font-size: 11.5px;
  font-weight: 600;
  color: var(--muted);
  flex-shrink: 0;
}

.card__stockline {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11.5px;
  font-weight: 600;
  white-space: nowrap;
  flex-shrink: 0;
}

.card__stockdot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
}

.card__stockline.is-in {
  color: var(--green-soft);
}
.card__stockline.is-in .card__stockdot {
  background: var(--green-soft);
}
.card__stockline.is-low {
  color: var(--amber);
}
.card__stockline.is-low .card__stockdot {
  background: var(--amber);
}
</style>
