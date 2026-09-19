<script setup>
import SchoolMark from '@/components/SchoolMark.vue'
import ThemeToggle from '@/components/ThemeToggle.vue'
import { school } from '@/school'
import { desktop } from '@/utils/viewport'

/** Sign-in pages. On a computer a panel in the school's colour sits beside the form; phones show the form alone. */

// The light/dark switch, unless the page's own app bar already carries one (phones only).
defineProps({ bar: { type: Boolean, default: false } })
</script>

<template>
  <div class="pp-signin">
    <ThemeToggle v-if="desktop || !bar" class="pp-signin__theme" />
    <aside class="pp-signin__art" aria-hidden="true">
      <div class="pp-signin__brand">
        <span v-if="school.logo" class="pp-logo-tile pp-logo-tile--image"><img :src="school.logo" alt="" /></span>
        <span v-else class="pp-logo-tile"><i class="fa fa-graduation-cap"></i></span>
        {{ school.name || 'Parent Portal' }}
      </div>
      <div class="pp-signin__pitch">
        <h2>Your child's canteen card, on every screen.</h2>
        <p>Check the balance, see every bill and top up, from your phone or your computer.</p>
      </div>
      <div class="pp-signin__cards">
        <span class="pp-child-card pp-child-card--none">
          <span class="pp-card__top"><SchoolMark /></span>
        </span>
        <span class="pp-child-card">
          <span class="pp-card__top"><SchoolMark /></span>
          <span class="pp-card__bottom">
            <span class="pp-card__chip"></span><span class="pp-card__label">Student card</span><i class="fa fa-wifi pp-card__nfc"></i>
          </span>
        </span>
      </div>
    </aside>
    <div class="pp-signin__form"><slot /></div>
  </div>
</template>
