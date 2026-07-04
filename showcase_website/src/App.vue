<script setup>
import { onMounted } from 'vue'

import BranchModal from '@/components/BranchModal.vue'
import AppFooter from '@/components/AppFooter.vue'
import TopBar from '@/components/TopBar.vue'
import { useBranchStore } from '@/stores/branch'

const branchStore = useBranchStore()

onMounted(() => {
  branchStore.load()
})
</script>

<template>
  <div class="app-shell">
    <BranchModal v-if="branchStore.modalOpen" />
    <TopBar />
    <router-view v-slot="{ Component }">
      <component :is="Component" :key="$route.fullPath" />
    </router-view>
    <AppFooter />
  </div>
</template>

<style scoped>
.app-shell {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.app-shell > :nth-child(2) ~ * {
  flex-shrink: 0;
}
</style>
