<script setup>
import { useRouter } from 'vue-router'

import ErrorBanner from '@/components/ErrorBanner.vue'
import { displayName, useBranchStore } from '@/stores/branch'
import { initialOf } from '@/utils/format'

const router = useRouter()
const branchStore = useBranchStore()

function shop(id) {
  branchStore.select(id)
  router.push('/')
}
</script>

<template>
  <main class="container branches anim-screen">
    <div class="kicker">
      <span class="kicker__line"></span>
      <span class="kicker__text">Locations</span>
    </div>
    <h1 class="branches__title">Our branches</h1>
    <p class="branches__sub">
      Visit us at any of our locations. Product availability varies per branch.
    </p>

    <ErrorBanner v-if="branchStore.error" :message="branchStore.error" @retry="branchStore.load()" />

    <div v-else class="branches__grid">
      <template v-if="branchStore.loading">
        <div v-for="n in 4" :key="n" class="skeleton" style="height: 180px; border-radius: 22px" />
      </template>
      <template v-else>
        <div
          v-for="br in branchStore.branches"
          :key="br.id"
          class="bcard"
          :class="{ 'bcard--current': br.id === branchStore.currentId }"
        >
          <div class="bcard__head">
            <div class="bcard__left">
              <div class="bcard__avatar">{{ initialOf(displayName(br)) }}</div>
              <div class="bcard__name">{{ displayName(br) }}</div>
            </div>
            <span v-if="br.id === branchStore.currentId" class="bcard__badge">Your branch</span>
          </div>
          <div class="bcard__addr">{{ br.location || br.code || '—' }}</div>
          <div class="bcard__meta">
            <span class="bcard__dot"></span>
            <span v-if="br.mobile">{{ br.mobile }}</span>
            <span v-else>Open today</span>
          </div>
          <div class="bcard__actions">
            <button class="bcard__shop" @click="shop(br.id)">Shop this branch</button>
          </div>
        </div>
      </template>
    </div>
  </main>
</template>

<style scoped>
.branches {
  padding-top: 72px;
  padding-bottom: 96px;
}

.branches__title {
  font-size: 52px;
  font-weight: 700;
  margin: 14px 0 12px;
}

.branches__sub {
  font-size: 15px;
  color: var(--muted);
  margin: 0 0 44px;
  max-width: 520px;
  line-height: 1.55;
}

.branches__grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.bcard {
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: 22px;
  padding: 32px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  box-shadow: 0 2px 6px rgba(28, 27, 24, 0.05);
  transition: all 0.2s ease;
}

.bcard:hover {
  box-shadow: 0 14px 36px rgba(28, 27, 24, 0.11);
}

.bcard--current {
  border-color: rgba(var(--gold-rgb), 0.55);
  background: rgba(var(--gold-rgb), 0.04);
}

.bcard__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.bcard__left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.bcard__avatar {
  width: 44px;
  height: 44px;
  border-radius: 13px;
  background: linear-gradient(140deg, #f2ede4, #e9e2d4);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 17px;
  color: var(--gold);
}

.bcard__name {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 21px;
  letter-spacing: -0.01em;
}

.bcard__badge {
  background: var(--gold);
  color: #fff;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.04em;
  border-radius: 99px;
  padding: 5px 14px;
  box-shadow: 0 4px 10px rgba(var(--gold-rgb), 0.3);
}

.bcard__addr {
  font-size: 14px;
  color: var(--ink-soft);
  margin-top: 8px;
}

.bcard__meta {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--muted);
}

.bcard__dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--green-soft);
  display: inline-block;
}

.bcard__actions {
  display: flex;
  gap: 10px;
  margin-top: 16px;
}

.bcard__shop {
  border: 1.5px solid var(--ink);
  border-radius: 99px;
  padding: 9px 20px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  font-family: var(--font-display);
  transition: all 0.15s ease;
}

.bcard__shop:hover {
  background: var(--ink);
  color: var(--bg);
}

@media (max-width: 900px) {
  .branches__grid {
    grid-template-columns: 1fr;
  }

  .branches__title {
    font-size: 36px;
  }
}
</style>
