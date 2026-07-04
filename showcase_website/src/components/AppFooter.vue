<script setup>
import { useBranchStore } from '@/stores/branch'
import { initialOf, storeName } from '@/utils/format'

const branchStore = useBranchStore()
const year = new Date().getFullYear()
</script>

<template>
  <footer class="footer">
    <div class="container">
      <div class="footer__top">
        <div>
          <div class="footer__brand">
            <div class="footer__mark">{{ initialOf(storeName) }}</div>
            <span class="footer__name">{{ storeName }}</span>
          </div>
          <div class="footer__tag">
            Sneakers, streetwear and art toys — fitted in person across our branches. Availability
            shown per branch; visit us to purchase.
          </div>
        </div>
        <div class="footer__cols">
          <div>
            <div class="footer__head">Shop</div>
            <div class="footer__links">
              <router-link to="/">Browse products</router-link>
              <router-link to="/branches">Our branches</router-link>
            </div>
          </div>
          <div>
            <div class="footer__head">Visit</div>
            <div class="footer__links">
              <span>Mon–Sun, hours vary by branch</span>
              <span v-if="branchStore.current?.mobile">
                {{ branchStore.current.mobile }} · {{ branchStore.current.name }}
              </span>
            </div>
          </div>
        </div>
      </div>
      <div class="footer__copy">
        © {{ year }} {{ storeName }} · All products subject to in-store availability
      </div>
    </div>
  </footer>
</template>

<style scoped>
.footer {
  background: var(--dark);
  color: var(--dark-muted);
  padding: 56px 0 40px;
  margin-top: auto;
}

.footer__top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 32px;
  padding-bottom: 36px;
  border-bottom: 1px solid rgba(243, 239, 230, 0.1);
}

.footer__brand {
  display: flex;
  align-items: center;
  gap: 11px;
}

.footer__mark {
  width: 28px;
  height: 28px;
  border-radius: 9px;
  background: linear-gradient(140deg, var(--gold-bright), var(--gold-deep));
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-display);
  font-weight: 900;
  font-size: 14px;
  color: #fff;
}

.footer__name {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 17px;
  letter-spacing: 0.08em;
  color: var(--white-warm);
}

.footer__tag {
  font-size: 13px;
  margin-top: 12px;
  line-height: 1.6;
  max-width: 300px;
}

.footer__cols {
  display: flex;
  gap: 64px;
}

.footer__head {
  font-family: var(--font-display);
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--gold-bright);
  margin-bottom: 14px;
}

.footer__links {
  display: flex;
  flex-direction: column;
  gap: 9px;
  font-size: 14px;
}

.footer__links a {
  cursor: pointer;
  transition: color 0.15s ease;
}

.footer__links a:hover {
  color: var(--white-warm);
}

.footer__copy {
  font-size: 12px;
  margin-top: 26px;
  color: #6e675a;
}
</style>
