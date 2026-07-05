<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'

import { resolveImage } from '@/api/client'
import { fetchProduct, fetchProducts } from '@/api/resources'
// fetchProduct(id) → GET /products/{id}
import EmptyState from '@/components/EmptyState.vue'
import ErrorBanner from '@/components/ErrorBanner.vue'
import ProductCard from '@/components/ProductCard.vue'
import { useBranchStore } from '@/stores/branch'
import { formatPrice } from '@/utils/format'

const route = useRoute()
const branchStore = useBranchStore()

const product = ref(null)
const related = ref([])
const loading = ref(true)
const error = ref(null)

const activeImage = ref(0)
const mode360 = ref(false)
const frame360 = ref(0)
const spinning = ref(false)
const selectedSize = ref(null)

// Progress (0–100) of the current 360° frame, for the scrubber fill.
const spinProgress = computed(() => {
  const n = images360.value.length
  return n > 1 ? (frame360.value / (n - 1)) * 100 : 0
})

// ===== Hover-to-zoom (magnifier) on the main preview =====
const zooming = ref(false)
const zoomOrigin = ref('50% 50%')

function zoomEnter() {
  if (mode360.value || !mainImage.value) return
  zooming.value = true
}

function zoomMove(e) {
  if (!zooming.value) return
  const r = e.currentTarget.getBoundingClientRect()
  const x = ((e.clientX - r.left) / r.width) * 100
  const y = ((e.clientY - r.top) / r.height) * 100
  zoomOrigin.value = `${x}% ${y}%`
}

function zoomLeave() {
  zooming.value = false
}

const gallery = computed(() => {
  if (!product.value) return []
  const imgs = (product.value.images || []).map((i) => resolveImage(i.url)).filter(Boolean)
  const thumb = resolveImage(product.value.thumbnail)
  if (thumb && !imgs.includes(thumb)) imgs.unshift(thumb)
  return imgs
})

const images360 = computed(
  () => (product.value?.images360 || []).map((i) => resolveImage(i.url)).filter(Boolean),
)

const mainImage = computed(() =>
  mode360.value ? images360.value[frame360.value] : gallery.value[activeImage.value],
)

const specs = computed(() => {
  const p = product.value
  if (!p) return []
  return [
    { label: 'Code', value: p.code },
    { label: 'Model', value: p.model },
    { label: 'Colour', value: p.color },
    { label: 'Size', value: p.size },
    { label: 'Barcode', value: p.barcode },
  ].filter((s) => s.value)
})

/** related_sizes → size chips with stock; falls back to available_sizes strings. */
const sizeOptions = computed(() => {
  const p = product.value
  if (!p) return []
  if (p.related_sizes?.length) {
    return p.related_sizes.map((s) => ({
      label: s.size,
      out: s.is_out_of_stock,
      branches: s.branches || [],
    }))
  }
  return [...new Set(p.available_sizes || [])].map((s) => ({ label: s, out: false, branches: [] }))
})

const branchAvailability = computed(() => {
  const p = product.value
  if (!p?.inventories?.length) return []
  return p.inventories.map((inv) => ({
    id: inv.id,
    name: inv.branch?.name || '—',
    qty: inv.quantity,
    isCurrent: inv.branch?.id === branchStore.currentId,
    inStock: inv.quantity > 0,
    low: inv.is_low_stock && !inv.is_out_of_stock,
  }))
})

const enquiryHref = computed(() => {
  const mobile = branchStore.current?.mobile?.replace(/\s+/g, '')
  return mobile ? `tel:${mobile}` : null
})

async function load() {
  loading.value = true
  error.value = null
  product.value = null
  mode360.value = false
  stopSpin()
  activeImage.value = 0
  frame360.value = 0
  try {
    product.value = await fetchProduct(route.params.id)
    selectedSize.value = product.value?.size || null
    loadRelated()
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function loadRelated() {
  const p = product.value
  if (!p) return
  try {
    // Related products share the same HSN code; fall back to category when absent.
    const params = {
      branch_id: branchStore.currentId,
      per_page: 5,
      type: 'product',
    }
    if (p.hsn_code) params.hsn_code = p.hsn_code
    else params.main_category_id = p.main_category?.id
    const result = await fetchProducts(params)
    related.value = (result.data || []).filter((r) => r.id !== p.id).slice(0, 4)
  } catch {
    related.value = []
  }
}

onMounted(load)
watch(() => route.params.id, load)

// ===== 360° drag rotation =====
let dragging = false
let dragX = 0
let dragFrame = 0

function startDrag(e) {
  if (!mode360.value || images360.value.length < 2) return
  stopSpin() // grabbing the shoe takes over from auto-rotation
  dragging = true
  dragX = e.clientX ?? e.touches?.[0]?.clientX ?? 0
  dragFrame = frame360.value
}

function moveDrag(e) {
  if (!dragging) return
  const x = e.clientX ?? e.touches?.[0]?.clientX ?? 0
  const count = images360.value.length
  const delta = Math.round((x - dragX) / 24) // ~24px per frame
  frame360.value = ((dragFrame + delta) % count + count) % count
}

function endDrag() {
  dragging = false
}

// ===== 360° control bar — auto-spin + scrubber =====
let spinTimer = null

function enter360() {
  if (!images360.value.length) return
  mode360.value = true
}

/** Primary 360° button: enter 360 view and toggle auto-rotation. */
function toggle360() {
  if (!mode360.value) {
    enter360()
    startSpin()
    return
  }
  spinning.value ? stopSpin() : startSpin()
}

function startSpin() {
  if (images360.value.length < 2) return
  enter360()
  spinning.value = true
  clearInterval(spinTimer)
  spinTimer = setInterval(() => {
    frame360.value = (frame360.value + 1) % images360.value.length
  }, 90)
}

function stopSpin() {
  spinning.value = false
  clearInterval(spinTimer)
  spinTimer = null
}

/** Click/scrub the track to jump to a frame. */
function scrub(e) {
  const n = images360.value.length
  if (n < 2) return
  stopSpin()
  enter360()
  const r = e.currentTarget.getBoundingClientRect()
  const pct = Math.min(1, Math.max(0, (e.clientX - r.left) / r.width))
  frame360.value = Math.round(pct * (n - 1))
}

/** Leaving 360 (e.g. picking a still photo) also halts rotation. */
function showPhoto(i) {
  stopSpin()
  mode360.value = false
  activeImage.value = i
}

onBeforeUnmount(stopSpin)
</script>

<template>
  <main class="container detail anim-screen">
    <router-link to="/products" class="btn-back">← Back to results</router-link>

    <ErrorBanner v-if="error" :message="error" @retry="load" />

    <div v-else-if="loading" class="pl pl--loading">
      <div class="skeleton" style="height: 14px; width: 120px; margin: 0 auto"></div>
      <div class="skeleton" style="height: 48px; width: 56%; margin: 18px auto 0"></div>
      <div class="skeleton" style="height: min(460px, 78vw); width: min(460px, 100%); border-radius: 50%; margin: 30px auto 0"></div>
      <div class="skeleton" style="height: 30px; width: 30%; margin: 30px auto 0"></div>
      <div class="skeleton" style="height: 48px; width: 220px; margin: 24px auto 0; border-radius: 99px"></div>
    </div>

    <EmptyState v-else-if="!product" title="Product not found" subtitle="It may have been removed." />

    <template v-else>
      <!-- ===== CONCEPT-STORE PLINTH ===== -->
      <div class="pl">
        <div class="pl__kicker">
          {{ product.brand?.name || product.main_category?.name || 'SIZERUN' }}
        </div>
        <h1 class="pl__name">{{ product.name }}</h1>
        <div v-if="product.model || product.code" class="pl__sub">
          {{ product.model || product.code }}
        </div>

        <!-- STAGE · product on a plinth, specs float around it -->
        <div class="pl__stagewrap">
          <template v-for="(spec, i) in specs.slice(0, 4)" :key="spec.label">
            <div
              class="pl__ann"
              :class="['pl__ann--r', 'pl__ann--l', 'pl__ann--r2', 'pl__ann--l2'][i]"
            >
              <div class="pl__ann-k">{{ spec.label }}</div>
              <div class="pl__ann-v">{{ spec.value }}</div>
            </div>
          </template>

          <div class="pl__disc"></div>
          <div
            class="pl__stage"
            :class="{
              'pl__stage--drag': mode360,
              'pl__stage--zoom': zooming && !mode360,
            }"
            @pointerdown="startDrag"
            @pointermove="moveDrag"
            @pointerup="endDrag"
            @pointerleave="endDrag"
            @mouseenter="zoomEnter"
            @mousemove="zoomMove"
            @mouseleave="zoomLeave"
          >
            <img
              v-if="mainImage"
              class="pl__img"
              :src="mainImage"
              :alt="product.name"
              draggable="false"
              :style="
                zooming && !mode360
                  ? { transform: 'scale(1.9)', transformOrigin: zoomOrigin }
                  : null
              "
            />
            <span v-else class="pl__noimg">no photo yet</span>
            <div v-if="mode360" class="pl__grab">⟲ drag to rotate</div>
          </div>
          <div class="pl__plinth"></div>
        </div>

        <!-- 360° control bar — auto-spin · scrubber · frame counter -->
        <div v-if="images360.length > 1" class="pl__orbit" :class="{ 'pl__orbit--on': mode360 }">
          <button
            class="pl__orbit-btn"
            :class="{ 'pl__orbit-btn--playing': spinning }"
            @click="toggle360"
          >
            <span class="pl__orbit-ico">⟲</span>
            {{ spinning ? 'Rotating' : '360° view' }}
          </button>
          <button
            class="pl__orbit-track"
            type="button"
            aria-label="Scrub 360° view"
            @pointerdown="scrub"
            @pointermove="(e) => e.buttons && scrub(e)"
          >
            <span class="pl__orbit-fill" :style="{ width: spinProgress + '%' }"></span>
            <span class="pl__orbit-knob" :style="{ left: spinProgress + '%' }"></span>
          </button>
          <span class="pl__orbit-frame">
            {{ String(frame360 + 1).padStart(2, '0') }} / {{ images360.length }}
          </span>
        </div>

        <!-- thumbnails -->
        <div v-if="gallery.length > 1" class="pl__thumbs">
          <button
            v-for="(img, i) in gallery"
            :key="img"
            class="pl__thumb"
            :class="{ 'pl__thumb--on': !mode360 && activeImage === i }"
            @click="showPhoto(i)"
          >
            <img :src="img" alt="" loading="lazy" />
          </button>
        </div>

        <!-- specs · full record (also the mobile home for the floating callouts) -->
        <div v-if="specs.length" class="pl__specrow">
          <div v-for="spec in specs" :key="spec.label" class="pl__specchip">
            <span class="pl__specchip-k">{{ spec.label }}</span>
            <span class="pl__specchip-v">{{ spec.value }}</span>
          </div>
        </div>

        <p v-if="product.description" class="pl__desc">{{ product.description }}</p>

        <!-- price -->
        <div class="pl__price">{{ formatPrice(product.mrp) }}</div>
        <div v-if="product.tax" class="pl__taxnote">incl. {{ product.tax }}% GST</div>

        <!-- sizes -->
        <div v-if="sizeOptions.length" class="pl__sizes">
          <button
            v-for="sz in sizeOptions"
            :key="sz.label"
            class="pl__pill"
            :class="{
              'pl__pill--on': selectedSize === sz.label,
              'pl__pill--out': sz.out,
            }"
            :disabled="sz.out"
            @click="selectedSize = sz.label"
          >
            {{ sz.label }}
          </button>
        </div>

        <!-- CTA -->
        <div class="pl__cta">
          <component
            :is="enquiryHref ? 'a' : 'button'"
            :href="enquiryHref || undefined"
            class="pl__buy"
          >
            Enquire at {{ branchStore.currentName }} →
          </component>
          <router-link to="/branches" class="pl__branches-link">All branches</router-link>
        </div>

        <!-- branch availability · dot row -->
        <div v-if="branchAvailability.length" class="pl__branches">
          <div
            v-for="b in branchAvailability"
            :key="b.id"
            class="pl__bdot"
            :class="{ 'pl__bdot--current': b.isCurrent }"
          >
            <span
              class="pl__bdot-i"
              :style="{
                background: b.inStock ? (b.low ? '#c9852a' : '#4f8a3d') : '#9a9384',
                boxShadow: b.inStock ? `0 0 7px ${b.low ? '#c9852a' : '#4f8a3d'}` : 'none',
              }"
            ></span>
            <span class="pl__bdot-name">{{ b.name }}</span>
            <span class="pl__bdot-status">
              {{ b.inStock ? (b.low ? `${b.qty} left` : `${b.qty} in stock`) : 'out' }}
            </span>
            <span v-if="b.isCurrent" class="pl__bdot-you">you</span>
          </div>
        </div>
      </div>

      <!-- RELATED -->
      <template v-if="related.length">
        <div class="pl__related-head">
          <div class="pl__related-rule"></div>
          <h2 class="pl__related-title">You might also like</h2>
          <div class="pl__related-rule"></div>
        </div>
        <div class="related-grid">
          <ProductCard v-for="p in related" :key="p.id" :product="p" compact />
        </div>
      </template>
    </template>
  </main>
</template>

<style scoped>
/* ====================================================================
   CONCEPT-STORE PLINTH — the product floats on a soft pedestal, key
   specs orbit it on hairline connector lines. Calm, luxurious, lots of
   negative space; everything applies on tap. Self-contained stage card
   driven by scoped --pl-* tokens so it reads as an airy ivory gallery,
   matching the always-light editorial look of the rest of the storefront.
   The branding accent (--gold) drives highlights throughout.
   ==================================================================== */
.detail {
  --pl-canvas: var(--paper);
  --pl-ink: var(--ink);
  --pl-ink-soft: var(--ink-soft);
  --pl-muted: var(--muted);
  --pl-line: var(--line);
  --pl-chip: #fff;
  --pl-disc: radial-gradient(62% 62% at 50% 32%, #ffffff, #f4f1ea 60%, #ece7dc);
  --pl-plinth: rgba(20, 19, 16, 0.14);
  --pl-shoe-shadow: rgba(20, 19, 16, 0.22);

  padding-top: 30px;
  padding-bottom: 96px;
}

/* ===== Stage card (full width) ===== */
.pl {
  max-width: none;
  margin: 22px 0 0;
  padding: 54px clamp(24px, 6vw, 88px) 48px;
  text-align: center;
  background:
    radial-gradient(120% 60% at 50% -8%, rgba(var(--gold-rgb), 0.06), transparent 60%),
    linear-gradient(180deg, #fffdf8, var(--pl-canvas) 40%);
  border: 1px solid var(--pl-line);
  border-radius: 30px;
  color: var(--pl-ink);
  position: relative;
  overflow: visible;
  box-shadow: 0 30px 80px -50px rgba(20, 19, 16, 0.5);
}

.pl--loading {
  min-height: 620px;
}

.pl__kicker {
  font-family: var(--font-mono);
  font-size: 11px;
  letter-spacing: 0.28em;
  text-transform: uppercase;
  color: var(--gold);
}

.pl__name {
  font-family: var(--font-display);
  font-weight: 500;
  font-size: clamp(34px, 5.4vw, 60px);
  line-height: 1.02;
  letter-spacing: -0.03em;
  color: var(--pl-ink);
  margin: 14px 0 6px;
}

.pl__sub {
  color: var(--pl-muted);
  font-size: 15px;
}

/* ===== Stage · disc + product + plinth + floating annotations ===== */
.pl__stagewrap {
  position: relative;
  width: min(760px, 96%);
  aspect-ratio: 1;
  margin: 30px auto 6px;
}

.pl__disc {
  position: absolute;
  inset: 5% 7% 8%;
  border-radius: 50%;
  background: var(--pl-disc);
  box-shadow:
    inset 0 2px 5px rgba(255, 255, 255, 0.9),
    inset 0 -14px 30px rgba(20, 19, 16, 0.06),
    0 24px 60px -30px rgba(20, 19, 16, 0.35);
}

.pl__disc::after {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: 50%;
  border: 1px solid rgba(var(--gold-rgb), 0.14);
}

.pl__plinth {
  position: absolute;
  left: 50%;
  bottom: 3%;
  transform: translateX(-50%);
  width: 62%;
  height: 26px;
  background: radial-gradient(50% 100% at 50% 0, var(--pl-plinth), transparent 72%);
  pointer-events: none;
}

.pl__stage {
  position: absolute;
  inset: 4% 6% 7%;
  z-index: 2;
  display: grid;
  place-items: center;
  touch-action: pan-y;
  overflow: hidden;
  border-radius: 50%;
}

.pl__stage--drag {
  cursor: grab;
}
.pl__stage--drag:active {
  cursor: grabbing;
}
.pl__stage--zoom {
  cursor: zoom-in;
}

.pl__img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  user-select: none;
  filter: drop-shadow(0 26px 34px var(--pl-shoe-shadow));
  transition: transform 0.2s var(--ease-out);
  will-change: transform;
}

.pl__noimg {
  font-family: var(--font-mono);
  font-size: 12px;
  color: var(--pl-muted);
  letter-spacing: 0.06em;
}

.pl__grab {
  position: absolute;
  bottom: 4%;
  left: 50%;
  transform: translateX(-50%);
  font-family: var(--font-mono);
  font-size: 10px;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--pl-muted);
  pointer-events: none;
  white-space: nowrap;
  opacity: 0.75;
}

/* floating spec annotations (desktop) */
.pl__ann {
  position: absolute;
  z-index: 4;
  white-space: nowrap;
}

.pl__ann-k {
  font-family: var(--font-mono);
  font-size: 10px;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--pl-muted);
}

.pl__ann-v {
  font-family: var(--font-display);
  font-weight: 600;
  font-size: 19px;
  color: var(--pl-ink);
  margin-top: 3px;
}

.pl__ann::before {
  content: '';
  position: absolute;
  top: 52%;
  width: clamp(34px, 5vw, 92px);
  height: 1px;
  background: var(--gold);
}

.pl__ann--l,
.pl__ann--l2 {
  right: calc(100% + clamp(24px, 6vw, 116px));
  text-align: right;
}
.pl__ann--l::before,
.pl__ann--l2::before {
  left: 100%;
}
.pl__ann--r,
.pl__ann--r2 {
  left: calc(100% + clamp(24px, 6vw, 116px));
  text-align: left;
}
.pl__ann--r::before,
.pl__ann--r2::before {
  right: 100%;
}
.pl__ann--r { top: 18%; }
.pl__ann--l { top: 26%; }
.pl__ann--r2 { top: 66%; }
.pl__ann--l2 { top: 72%; }

/* ===== Thumbnails ===== */
.pl__thumbs {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 10px;
}

.pl__thumb {
  width: 60px;
  height: 60px;
  border-radius: 14px;
  border: 1px solid var(--pl-line);
  overflow: hidden;
  cursor: pointer;
  background: var(--pl-chip);
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
  padding: 0;
}
.pl__thumb:hover {
  border-color: var(--gold);
}
.pl__thumb--on {
  border-color: var(--gold);
  box-shadow: 0 0 0 3px rgba(var(--gold-rgb), 0.16);
}
.pl__thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* ===== 360° control bar — auto-spin · scrubber · frame counter ===== */
.pl__orbit {
  display: inline-flex;
  align-items: center;
  gap: 16px;
  margin: 22px auto 0;
  padding: 8px 12px 8px 8px;
  background: var(--pl-chip);
  border: 1px solid var(--pl-line);
  border-radius: 99px;
  box-shadow: 0 16px 34px -22px rgba(20, 19, 16, 0.5);
  transition: box-shadow 0.2s var(--ease-out);
}
.pl__orbit--on {
  box-shadow: 0 18px 40px -20px rgba(var(--gold-rgb), 0.5);
}

.pl__orbit-btn {
  display: inline-flex;
  align-items: center;
  gap: 9px;
  flex: none;
  background: var(--gold);
  color: #fff;
  font-family: var(--font-mono);
  font-size: 11px;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  padding: 10px 18px;
  border-radius: 99px;
  cursor: pointer;
  transition: filter 0.18s ease, transform 0.18s ease;
}
.pl__orbit-btn:hover {
  filter: brightness(1.08);
  transform: translateY(-1px);
}

.pl__orbit-ico {
  font-size: 14px;
  line-height: 1;
  display: inline-block;
}
.pl__orbit-btn--playing .pl__orbit-ico {
  animation: pl-spin 3.4s linear infinite;
}
@keyframes pl-spin {
  to {
    transform: rotate(-360deg);
  }
}
@media (prefers-reduced-motion: reduce) {
  .pl__orbit-btn--playing .pl__orbit-ico {
    animation: none;
  }
}

.pl__orbit-track {
  position: relative;
  width: clamp(90px, 22vw, 160px);
  height: 4px;
  border-radius: 3px;
  background: var(--pl-line);
  cursor: pointer;
  padding: 0;
  touch-action: none;
}
.pl__orbit-fill {
  position: absolute;
  top: 0;
  left: 0;
  height: 100%;
  border-radius: 3px;
  background: var(--gold);
}
.pl__orbit-knob {
  position: absolute;
  top: 50%;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: var(--gold);
  border: 2px solid var(--pl-chip);
  transform: translate(-50%, -50%);
  box-shadow: 0 2px 6px rgba(var(--gold-rgb), 0.5);
}

.pl__orbit-frame {
  font-family: var(--font-mono);
  font-size: 11px;
  color: var(--pl-muted);
  padding-right: 6px;
  min-width: 56px;
  text-align: right;
  font-variant-numeric: tabular-nums;
}

/* ===== Spec strip (mobile home for the callouts) ===== */
.pl__specrow {
  display: none;
  justify-content: center;
  flex-wrap: wrap;
  gap: 10px 22px;
  margin-top: 24px;
}

.pl__specchip {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.pl__specchip-k {
  font-family: var(--font-mono);
  font-size: 10px;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--pl-muted);
}
.pl__specchip-v {
  font-family: var(--font-display);
  font-weight: 600;
  font-size: 15px;
  color: var(--pl-ink);
}

/* ===== Description / price ===== */
.pl__desc {
  font-size: 15px;
  line-height: 1.65;
  color: var(--pl-ink-soft);
  max-width: 52ch;
  margin: 26px auto 0;
}

.pl__price {
  font-family: var(--font-display);
  font-weight: 600;
  font-size: 40px;
  color: var(--pl-ink);
  margin-top: 30px;
}

.pl__taxnote {
  font-family: var(--font-mono);
  font-size: 11px;
  letter-spacing: 0.05em;
  color: var(--pl-muted);
  margin-top: 5px;
}

/* ===== Sizes ===== */
.pl__sizes {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 9px;
  margin: 26px 0;
}

.pl__pill {
  min-width: 50px;
  height: 46px;
  padding: 0 14px;
  border-radius: 99px;
  border: 1.5px solid var(--pl-line);
  background: var(--pl-chip);
  color: var(--pl-ink);
  display: grid;
  place-items: center;
  font-family: var(--font-display);
  font-weight: 600;
  font-size: 15px;
  cursor: pointer;
  transition: all 0.15s ease;
}
.pl__pill:hover:not(:disabled) {
  border-color: var(--gold);
  color: var(--gold);
}
.pl__pill--on {
  background: var(--gold);
  border-color: var(--gold);
  color: #fff;
  box-shadow: 0 10px 24px -10px rgba(var(--gold-rgb), 0.7);
}
.pl__pill--out {
  color: var(--pl-muted);
  text-decoration: line-through;
  cursor: not-allowed;
  opacity: 0.6;
}

/* ===== CTA ===== */
.pl__cta {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  flex-wrap: wrap;
  margin-top: 6px;
}

.pl__buy {
  background: var(--gold);
  color: #fff;
  padding: 16px 42px;
  border-radius: 99px;
  font-weight: 600;
  font-size: 15px;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 16px 34px -12px rgba(var(--gold-rgb), 0.7);
}
.pl__buy:hover {
  transform: translateY(-2px);
  box-shadow: 0 22px 44px -12px rgba(var(--gold-rgb), 0.85);
}

.pl__branches-link {
  font-size: 14px;
  font-weight: 600;
  color: var(--pl-muted);
  border-bottom: 1px solid transparent;
  transition: all 0.15s ease;
}
.pl__branches-link:hover {
  color: var(--gold);
  border-bottom-color: var(--gold);
}

/* ===== Branch availability · dot row ===== */
.pl__branches {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 10px 20px;
  margin-top: 34px;
}

.pl__bdot {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--pl-ink-soft);
}

.pl__bdot--current .pl__bdot-name {
  color: var(--gold);
}

.pl__bdot-i {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex: none;
}

.pl__bdot-name {
  font-weight: 600;
}

.pl__bdot-status {
  font-family: var(--font-mono);
  font-size: 11px;
  color: var(--pl-muted);
}

.pl__bdot-you {
  background: var(--gold);
  color: #fff;
  font-family: var(--font-mono);
  font-size: 9px;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  border-radius: 99px;
  padding: 2px 8px;
}

/* ===== Related ===== */
.pl__related-head {
  display: flex;
  align-items: center;
  gap: 18px;
  margin: 72px 0 26px;
}

.pl__related-title {
  font-family: var(--font-display);
  font-size: 26px;
  font-weight: 600;
  margin: 0;
  letter-spacing: -0.02em;
  white-space: nowrap;
}

.pl__related-rule {
  flex: 1;
  height: 1px;
  background: var(--line);
}

.related-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 22px;
}

/* ===== Responsive ===== */
@media (max-width: 900px) {
  .pl__ann {
    display: none;
  }
  .pl__specrow {
    display: flex;
  }
  .pl__stagewrap {
    width: min(560px, 100%);
  }
}

@media (max-width: 1024px) {
  .related-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 640px) {
  .pl {
    padding: 34px 20px 34px;
    border-radius: 24px;
  }
  .pl__price {
    font-size: 34px;
  }
  .pl__related-title {
    font-size: 22px;
  }
  .related-grid {
    grid-template-columns: 1fr;
  }
}
</style>
