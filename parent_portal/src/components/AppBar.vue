<script setup>
import { useRouter } from 'vue-router'

import { goBackOr } from '@/utils/nav'

/** iOS-style navigation bar: a back button, a centred title and an end slot. */
const props = defineProps({
  back: { type: [Object, String], default: null },
  backLabel: { type: String, default: 'Back' },
  title: { type: String, default: '' },
})

const router = useRouter()

// Real history when possible (keeps the tab / month / scroll), never out of the portal.
const goBack = () => goBackOr(router, props.back)
</script>

<template>
  <header class="pp-appbar">
    <div class="pp-appbar__start">
      <button v-if="back" class="pp-navbtn" type="button" @click="goBack"><i class="fa fa-angle-left"></i>{{ backLabel }}</button>
      <slot name="start" />
    </div>
    <div v-if="title" class="pp-appbar__title">{{ title }}</div>
    <div class="pp-appbar__end"><slot name="end" /></div>
  </header>
</template>
