<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'

import { money } from '@/format'
import { familyLine, priceOf, priceRange, productImage, productRoute, zoomMove, zoomReset } from '@/utils/catalog'

const props = defineProps({ product: { type: Object, required: true } })
const router = useRouter()

const image = computed(() => productImage(props.product))
const price = computed(() => priceOf(props.product))
const range = computed(() => priceRange(props.product))
const tag = computed(() => (props.product.sub_category?.name || props.product.main_category?.name || 'Kashperia').toUpperCase())

function open() {
  router.push(productRoute(props.product))
}
</script>

<template>
  <div class="product" role="link" tabindex="0" @click="open" @keydown.enter="open">
    <div class="productImg k-zoom-card" @mousemove="zoomMove" @mouseleave="zoomReset">
      <img v-if="image" :src="image" :alt="product.name" loading="lazy" />
      <div v-else class="k-card-mark" aria-hidden="true">K</div>
    </div>
    <div class="productInfo">
      <span class="tag">{{ tag }}</span>
      <h3 class="serif">{{ product.name }}</h3>
      <p>{{ familyLine(product) }}</p>
      <span v-if="range" class="tag k-price-tag">{{ money(range.from) }} – {{ money(range.to) }}</span>
      <span v-else-if="price.now" class="tag k-price-tag">
        <s v-if="price.was">{{ money(price.was) }}</s> {{ money(price.now) }}
      </span>
      <span v-else class="tag">PRICE ON REQUEST</span>
    </div>
  </div>
</template>
