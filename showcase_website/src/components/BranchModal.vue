<script setup>
import { displayName, useBranchStore } from '@/stores/branch'
import { initialOf, storeName } from '@/utils/format'

const branchStore = useBranchStore()

function dismiss() {
  // Only dismissable once a branch has been chosen.
  if (branchStore.currentId) branchStore.modalOpen = false
}
</script>

<template>
  <div class="overlay" @click.self="dismiss">
    <div class="modal">
      <div class="modal__glow"></div>
      <div class="kicker">
        <span class="kicker__line" style="background: var(--gold-bright)"></span>
        <span class="kicker__text" style="color: var(--gold-bright)">
          Welcome to {{ storeName }}
        </span>
      </div>
      <h2 class="modal__title">Choose your branch</h2>
      <p class="modal__sub">
        We'll show you products available at the branch you pick. You can switch anytime from the
        top bar.
      </p>

      <div v-if="branchStore.loading" class="modal__grid">
        <div v-for="n in 4" :key="n" class="skeleton" style="height: 96px; border-radius: 16px" />
      </div>

      <div v-else-if="branchStore.error" class="modal__error">
        {{ branchStore.error }}
        <button class="modal__retry" @click="branchStore.load()">Retry</button>
      </div>

      <div v-else class="modal__grid">
        <button
          v-for="br in branchStore.branches"
          :key="br.id"
          class="branch-card"
          @click="branchStore.select(br.id)"
        >
          <div class="branch-card__avatar">{{ initialOf(displayName(br)) }}</div>
          <div>
            <div class="branch-card__name">{{ displayName(br) }}</div>
            <div class="branch-card__addr">{{ br.location || br.code }}</div>
            <div class="branch-card__status">
              <span class="branch-card__pulse"></span>
              <span>{{ br.mobile || 'Open today' }}</span>
            </div>
          </div>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.overlay {
  position: fixed;
  inset: 0;
  background: rgba(18, 16, 12, 0.72);
  backdrop-filter: blur(12px);
  z-index: 100;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  animation: fadeIn 0.25s ease both;
}

.modal {
  background: var(--dark);
  color: var(--dark-ink);
  border-radius: 26px;
  max-width: 760px;
  width: 100%;
  max-height: 88vh;
  overflow-y: auto;
  padding: 56px;
  box-shadow:
    0 48px 120px rgba(0, 0, 0, 0.5),
    inset 0 1px 0 rgba(243, 239, 230, 0.08);
  animation: modalPop 0.4s var(--ease-out) both;
  position: relative;
  overflow-x: hidden;
}

.modal__glow {
  position: absolute;
  top: -120px;
  right: -100px;
  width: 340px;
  height: 340px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(var(--gold-bright-rgb), 0.18) 0%, rgba(var(--gold-bright-rgb), 0) 70%);
  pointer-events: none;
}

.modal__title {
  font-size: 42px;
  font-weight: 700;
  margin: 16px 0 8px;
  color: var(--white-warm);
}

.modal__sub {
  font-size: 15px;
  color: var(--dark-muted);
  margin: 0 0 34px;
  line-height: 1.55;
  max-width: 480px;
}

.modal__grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.modal__error {
  border: 1px solid rgba(196, 85, 59, 0.5);
  background: rgba(196, 85, 59, 0.12);
  border-radius: 14px;
  padding: 18px 20px;
  font-size: 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.modal__retry {
  border: 1px solid rgba(243, 239, 230, 0.3);
  border-radius: 99px;
  padding: 8px 18px;
  color: var(--dark-ink);
  font-weight: 600;
  font-size: 13px;
  transition: all 0.15s ease;
}

.modal__retry:hover {
  border-color: var(--gold-bright);
  color: var(--gold-bright);
}

.branch-card {
  border: 1px solid rgba(243, 239, 230, 0.14);
  border-radius: 16px;
  padding: 18px 20px;
  cursor: pointer;
  background: rgba(243, 239, 230, 0.05);
  transition: all 0.2s var(--ease-out);
  display: flex;
  align-items: flex-start;
  gap: 14px;
  text-align: left;
  color: inherit;
}

.branch-card:hover {
  border-color: var(--gold-bright);
  background: rgba(var(--gold-bright-rgb), 0.12);
  transform: translateY(-3px);
}

.branch-card__avatar {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: linear-gradient(140deg, var(--gold-bright), var(--gold-deep));
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-display);
  font-weight: 900;
  font-size: 15px;
  color: #fff;
  flex-shrink: 0;
}

.branch-card__name {
  font-family: var(--font-display);
  font-weight: 600;
  font-size: 16px;
  color: var(--white-warm);
}

.branch-card__addr {
  font-size: 13px;
  color: var(--dark-muted);
  margin-top: 3px;
  line-height: 1.4;
}

.branch-card__status {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 8px;
  font-size: 12px;
  color: var(--green);
  font-weight: 600;
}

.branch-card__pulse {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--green);
  display: inline-block;
  animation: pulse 2.4s ease infinite;
}

@media (max-width: 640px) {
  .modal {
    padding: 32px 24px;
  }

  .modal__title {
    font-size: 30px;
  }

  .modal__grid {
    grid-template-columns: 1fr;
  }
}
</style>
