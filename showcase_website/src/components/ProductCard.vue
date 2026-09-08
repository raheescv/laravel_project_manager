<script setup>
import { computed } from 'vue'

import { field, money } from '@/i18n'
import { useCatalogStore } from '@/stores/catalog'
import { initialOf, productImage, stockPill } from '@/utils/catalog'

const props = defineProps({
  product: { type: Object, required: true },
})

const catalog = useCatalogStore()

const image = computed(() => productImage(props.product))
const name = computed(() => field(props.product, 'name'))
const brand = computed(
  () => props.product.brand?.name || props.product.main_category?.name || '',
)
const pill = computed(() => stockPill(props.product.total_stock))
</script>

<template>
  <router-link class="card" :to="{ name: 'product', params: { id: product.id } }">
    <div class="card__media">
      <span v-if="product.has_360" class="card__tag">360°</span>
      <img v-if="image" :src="image" :alt="name" loading="lazy" decoding="async" />
      <div v-else class="card__ph" aria-hidden="true">
        <img v-if="product.brand?.image_path" :src="product.brand.image_path" alt="" loading="lazy" />
        <span v-else>{{ initialOf(brand || name) }}</span>
      </div>
    </div>
    <div class="card__body">
      <span class="card__brand">{{ brand }}</span>
      <h3 class="card__name">{{ name }}</h3>
      <div class="card__row">
        <span class="price">{{ money(product.mrp) }}</span>
        <span class="pill" :class="pill.cls">{{ pill.text }}</span>
      </div>
      <div v-if="product.size" class="run">
        <span class="run__s" :class="{ 'is-mine': catalog.sized && product.size === catalog.size }">
          {{ product.size }}
        </span>
      </div>
    </div>
  </router-link>
</template>
