<script setup>
import { nextTick, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

import { field, money, t } from '@/i18n'
import { useBagStore } from '@/stores/bag'
import { useCatalogStore } from '@/stores/catalog'
import { toast } from '@/toast'
import { initialOf } from '@/utils/catalog'

const bag = useBagStore()
const catalog = useCatalogStore()
const router = useRouter()

const closeBtn = ref(null)
let lastFocus = null

// Focus moves into the drawer on open and back to the trigger on close.
watch(
  () => bag.open,
  async (open) => {
    if (open) {
      lastFocus = document.activeElement
      await nextTick()
      closeBtn.value?.focus()
    } else if (lastFocus && typeof lastFocus.focus === 'function') {
      lastFocus.focus()
      lastFocus = null
    }
  },
)

function change(key, delta) {
  if (bag.setQty(key, delta) === 'removed') toast(t('removed'))
}

function remove(key) {
  bag.remove(key)
  toast(t('removed'))
}

async function findSize() {
  bag.close()
  await router.push({ name: 'catalogue', query: catalog.toQuery() })
  setTimeout(() => document.getElementById('size')?.scrollIntoView({ behavior: 'smooth' }), 80)
}

function keepShopping() {
  bag.close()
  bag.acknowledge()
}
</script>

<template>
  <aside
    id="bag"
    class="bag"
    role="dialog"
    aria-modal="true"
    aria-labelledby="bagTitle"
    :aria-hidden="!bag.open"
  >
    <div class="bag__head">
      <p id="bagTitle" class="h-sm">{{ t('yourBag') }}</p>
      <button ref="closeBtn" class="bag__close" :aria-label="t('close')" @click="bag.close()">
        &times;
      </button>
    </div>

    <div class="bag__body">
      <!-- demo checkout done -->
      <div v-if="bag.done" class="done">
        <span class="done__tick">&#10003;</span>
        <p class="done__title">{{ t('doneTitle') }}</p>
        <p class="done__body">{{ t('doneBody', { total: money(bag.done.total) }) }}</p>
        <button class="btn btn--dark" @click="keepShopping">{{ t('keepShopping') }}</button>
      </div>

      <!-- empty -->
      <div v-else-if="!bag.lines.length" class="empty empty--bag">
        <span class="empty__rule"></span>
        <p class="empty__title">{{ t('bagEmptyTitle') }}</p>
        <p class="empty__body">{{ t('bagEmptyBody') }}</p>
        <button class="btn btn--dark" @click="findSize">{{ t('bagEmptyCta') }}</button>
      </div>

      <!-- lines -->
      <template v-else>
        <div class="lines">
          <div v-for="l in bag.lines" :key="l.key" class="line">
            <div class="line__media">
              <img v-if="l.img" :src="l.img" alt="" loading="lazy" />
              <div v-else class="card__ph card__ph--sm"><span>{{ initialOf(l.brand || l.name) }}</span></div>
            </div>
            <div>
              <p class="line__brand">{{ l.brand }}</p>
              <p class="line__name">{{ field(l, 'name') }}</p>
              <p v-if="l.size" class="line__meta">{{ t('size') }} {{ l.size }}</p>
              <div class="qty qty--sm" style="margin-block-start: 8px">
                <button aria-label="−" @click="change(l.key, -1)">&minus;</button>
                <span>{{ l.qty }}</span>
                <button aria-label="+" @click="change(l.key, 1)">+</button>
              </div>
            </div>
            <div class="line__side">
              <span class="price">{{ money(l.price * l.qty) }}</span>
              <button class="linkbtn" @click="remove(l.key)">{{ t('remove') }}</button>
            </div>
          </div>
        </div>

        <div class="ful">
          <span class="h-sm">{{ t('fulfilment') }}</span>
          <div class="seg">
            <button
              class="seg__btn"
              :class="{ 'is-on': bag.fulfilment === 'pickup' }"
              @click="bag.fulfilment = 'pickup'"
            >
              {{ t('pickup') }}
            </button>
            <button
              class="seg__btn"
              :class="{ 'is-on': bag.fulfilment === 'delivery' }"
              @click="bag.fulfilment = 'delivery'"
            >
              {{ t('delivery') }}
            </button>
          </div>
        </div>
      </template>
    </div>

    <div v-if="bag.lines.length && !bag.done" class="bag__foot">
      <div class="total">
        <span>{{ t('total') }}</span>
        <span class="price price--lg">{{ money(bag.total) }}</span>
      </div>
      <button class="btn btn--primary btn--block" @click="bag.checkout()">{{ t('checkout') }}</button>
      <p class="fineprint">{{ t('fineprint') }}</p>
    </div>
  </aside>
</template>
