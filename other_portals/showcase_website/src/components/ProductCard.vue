<script setup>
import { computed } from 'vue'

import { field, money, t } from '@/i18n'
import { useCatalogStore } from '@/stores/catalog'
import { sizeLabel } from '@/sizing'
import { initialOf, priceOf, productImage, productRoute, stockPill } from '@/utils/catalog'

const props = defineProps({
  product: { type: Object, required: true },
})

const catalog = useCatalogStore()

const image = computed(() => productImage(props.product))
const name = computed(() => field(props.product, 'name'))
const brand = computed(
  () => props.product.brand?.name || props.product.main_category?.name || '',
)
/** The size / colour family behind this card, when it has more than one item. */
const family = computed(() => (props.product.family?.count > 1 ? props.product.family : null))
const pill = computed(() => stockPill(family.value ? family.value.stock_total : props.product.total_stock))
const price = computed(() => priceOf(props.product))
/** A family priced differently across its sizes shows "From" its lowest price. */
const fromPrice = computed(() => {
  const f = family.value
  return f && Number(f.max_price) > Number(f.min_price) ? Number(f.min_price) : null
})
</script>

<template>
  <router-link class="card" :to="productRoute(product)">
    <div class="card__media">
      <span v-if="product.has_360" class="card__tag">360°</span>
      <span v-if="price.was" class="card__sale"><bdi dir="ltr">&minus;{{ price.percent }}%</bdi></span>
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
        <span v-if="fromPrice !== null" class="card__prices">
          <span class="card__from">{{ t('priceFrom') }}</span>
          <span class="price">{{ money(fromPrice) }}</span>
        </span>
        <span v-else-if="price.was" class="card__prices">
          <span class="price price--sale">{{ money(price.now) }}</span>
          <span class="price price--was">{{ money(price.was) }}</span>
        </span>
        <span v-else class="price">{{ money(price.now) }}</span>
        <span class="pill" :class="pill.cls">{{ pill.text }}</span>
      </div>
      <div v-if="!family && product.size" class="run">
        <span class="run__s" :class="{ 'is-mine': catalog.sized && product.size === catalog.size }">
          {{ sizeLabel(product.size) }}
        </span>
      </div>
    </div>
  </router-link>
</template>
