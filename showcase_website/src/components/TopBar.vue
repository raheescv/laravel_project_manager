<script setup>
import { useBranchStore } from '@/stores/branch'
import { initialOf, storeName } from '@/utils/format'

const branchStore = useBranchStore()
</script>

<template>
  <div class="topwrap">
    <!-- editorial marquee -->
    <div class="ticker" aria-hidden="true">
      <div class="ticker__track">
        <span>
          <b>FREE FITTING</b> at every branch <i class="ticker__dot"></i> NEW ART TOYS DROPS WEEKLY
          <i class="ticker__dot"></i> RESERVE ONLINE · COLLECT IN-STORE <i class="ticker__dot"></i>
          SNEAKERS · STREETWEAR · COLLECTIBLES <i class="ticker__dot"></i>
          <b>FREE FITTING</b> at every branch <i class="ticker__dot"></i> NEW ART TOYS DROPS WEEKLY
          <i class="ticker__dot"></i> RESERVE ONLINE · COLLECT IN-STORE <i class="ticker__dot"></i>
          SNEAKERS · STREETWEAR · COLLECTIBLES <i class="ticker__dot"></i>
        </span>
      </div>
    </div>

    <header class="topbar">
      <div class="container topbar__inner">
        <router-link to="/" class="topbar__logo">
          <div class="topbar__mark">{{ initialOf(storeName) }}</div>
          <div>
            <span class="topbar__name">{{ storeName }}</span>
            <span class="topbar__sub">Concept Store</span>
          </div>
        </router-link>
        <div class="topbar__actions">
          <router-link to="/branches" class="topbar__link">Our branches</router-link>
          <button class="topbar__branch" @click="branchStore.openModal()">
            <span class="topbar__dot"></span>
            {{ branchStore.currentName }}
          </button>
        </div>
      </div>
    </header>
  </div>
</template>

<style scoped>
/* ===== Ticker ===== */
.ticker {
  background: var(--dark);
  color: var(--dark-ink);
  overflow: hidden;
  font-size: 12px;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  font-weight: 600;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.ticker__track {
  display: flex;
  white-space: nowrap;
  width: max-content;
  animation: marquee 34s linear infinite;
  padding: 9px 0;
}

.ticker__track span {
  padding: 0 26px;
  display: inline-flex;
  align-items: center;
  gap: 26px;
  color: #cfc9ba;
}

.ticker__track b {
  color: var(--amber);
  font-weight: 700;
}

.ticker__dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: var(--gold-bright);
}

/* ===== Header ===== */
.topbar {
  position: sticky;
  top: 0;
  z-index: 50;
  background: rgba(244, 240, 231, 0.82);
  backdrop-filter: blur(18px) saturate(1.4);
  border-bottom: 1px solid var(--line);
}

.topbar__inner {
  height: 74px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
}

.topbar__logo {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
}

.topbar__mark {
  width: 40px;
  height: 40px;
  border-radius: 13px;
  flex-shrink: 0;
  background: linear-gradient(150deg, var(--gold-bright), var(--gold) 55%, var(--gold-deep));
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-display);
  font-weight: 900;
  font-size: 20px;
  color: #fff;
  box-shadow:
    0 8px 20px rgba(var(--gold-rgb), 0.35),
    inset 0 1px 0 rgba(255, 255, 255, 0.35);
}

.topbar__name {
  display: block;
  font-family: var(--font-display);
  font-weight: 900;
  font-size: 23px;
  letter-spacing: -0.02em;
  line-height: 1;
  color: var(--ink);
}

.topbar__sub {
  display: block;
  font-size: 10px;
  letter-spacing: 0.34em;
  text-transform: uppercase;
  color: var(--muted);
  margin-top: 3px;
  font-weight: 600;
}

.topbar__actions {
  display: flex;
  align-items: center;
  gap: 14px;
}

.topbar__link {
  font-size: 14px;
  color: var(--ink-soft);
  cursor: pointer;
  font-weight: 600;
  transition: all 0.18s ease;
  padding: 9px 14px;
  border-radius: 99px;
}

.topbar__link:hover {
  color: var(--ink);
  background: rgba(20, 19, 16, 0.05);
}

.topbar__branch {
  display: flex;
  align-items: center;
  gap: 9px;
  background: var(--ink);
  border: 1px solid var(--ink);
  color: var(--dark-ink);
  border-radius: 99px;
  padding: 10px 18px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.24s var(--ease-out);
  box-shadow: 0 8px 22px rgba(20, 19, 16, 0.22);
}

.topbar__branch:hover {
  background: var(--gold);
  border-color: var(--gold);
  color: #fff;
  transform: translateY(-1px);
  box-shadow: 0 12px 28px rgba(var(--gold-rgb), 0.36);
}

.topbar__dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--green);
  display: inline-block;
  box-shadow: 0 0 0 3px rgba(111, 191, 90, 0.28);
  animation: pulse 2.6s ease infinite;
}

@media (max-width: 640px) {
  .topbar__link {
    display: none;
  }
  .topbar__sub {
    display: none;
  }
}
</style>
