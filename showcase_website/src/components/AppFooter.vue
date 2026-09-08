<script setup>
import { branding, storeName } from '@/branding'
import BrandLogo from '@/components/BrandLogo.vue'
import { t } from '@/i18n'
import { useShopsStore } from '@/stores/shops'

const shops = useShopsStore()
</script>

<template>
  <footer class="foot">
    <div class="wrap">
      <div class="foot__grid">
        <div class="foot__brand">
          <div class="foot__logo"><BrandLogo variant="foot" /></div>
          <p class="foot__tag">{{ t('footTag') }}</p>
        </div>
        <div>
          <p class="h-sm">{{ t('shops') }}</p>
          <ul>
            <li v-for="s in shops.shops" :key="s.id">{{ s.name || s.location || s.code }}</li>
          </ul>
        </div>
        <div>
          <p class="h-sm">{{ t('contact') }}</p>
          <p v-if="branding.company.email" class="foot__line" dir="ltr">
            {{ branding.company.email }}
          </p>
          <p v-if="branding.company.mobile" class="foot__line" dir="ltr">
            {{ branding.company.mobile }}
          </p>
          <p class="foot__line">{{ t('hours') }}</p>
        </div>
      </div>
      <div class="foot__base">
        <span>{{ t('footBase', { store: storeName }) }}</span>
        <span>{{ t('footSample') }}</span>
      </div>
    </div>
  </footer>
</template>
