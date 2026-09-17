<script setup>
import { watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import ToastHost from '@/components/ToastHost.vue'
import { session } from '@/session'

const route = useRoute()
const router = useRouter()

// The API refused the token (expired, signed out elsewhere, parent disabled):
// back to sign in, remembering the page the parent was on.
watch(
  () => session.expired,
  (expired) => {
    if (!expired || route.meta.guest || route.meta.public) return
    router.replace({ name: 'login', query: route.fullPath !== '/' ? { redirect: route.fullPath } : {} })
  },
)
</script>

<template>
  <RouterView v-slot="{ Component, route: current }">
    <Transition name="pp-fade" mode="out-in">
      <div :key="current.fullPath.split('?')[0]" class="pp-app">
        <component :is="Component" />
      </div>
    </Transition>
  </RouterView>
  <ToastHost />
</template>
