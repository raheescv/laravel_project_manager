<script setup>
import { useRouter } from 'vue-router'

import { goBackOr } from '@/utils/nav'
import { desktop } from '@/utils/viewport'

/**
 * iOS-style navigation bar: a back button, a centred title and an end slot.
 * On a computer the wallet already names the school and the child, so only the
 * back link stays (and not even that when `desktopBack` is off).
 */
const props = defineProps({
  back: { type: [Object, String], default: null },
  backLabel: { type: String, default: 'Back' },
  title: { type: String, default: '' },
  desktopBack: { type: Boolean, default: true },
})

const router = useRouter()

// Real history when possible (keeps the tab / month / scroll), never out of the portal.
const goBack = () => goBackOr(router, props.back)
</script>

<template>
  <template v-if="desktop">
    <button v-if="back && desktopBack" class="pp-back" type="button" @click="goBack"><i class="fa fa-angle-left"></i>{{ backLabel }}</button>
  </template>
  <header v-else class="pp-appbar">
    <div class="pp-appbar__start">
      <button v-if="back" class="pp-navbtn" type="button" @click="goBack"><i class="fa fa-angle-left"></i>{{ backLabel }}</button>
      <slot name="start" />
    </div>
    <div v-if="title" class="pp-appbar__title">{{ title }}</div>
    <div class="pp-appbar__end"><slot name="end" /></div>
  </header>
</template>
