<script setup>
import { useBagStore } from '@/stores/bag'
import { useCatalogStore } from '@/stores/catalog'

const bag = useBagStore()
const catalog = useCatalogStore()

const messages = [
  'Complimentary Shipping Across the GCC',
  'Client Advisors Available 24/7',
  'Product Authentication Through Certified Independent Laboratory Testing',
]
</script>

<template>
  <div class="top k-announcement" aria-label="Kashperia services">
    <div class="k-ticker-track">
      <div v-for="copy in 2" :key="copy" class="k-ticker-set" :aria-hidden="copy === 2 ? 'true' : undefined">
        <template v-for="message in messages" :key="message">
          <span class="k-ticker-message">{{ message }}</span><span class="k-ticker-sep" aria-hidden="true">✦</span>
        </template>
      </div>
    </div>
  </div>
  <header class="nav">
    <button type="button" @click="bag.openDrawer('menu')">☰ &nbsp; MENU</button>
    <RouterLink class="logo" to="/" aria-label="Kashperia — Return to homepage">KASHPERIA</RouterLink>
    <div class="navlinks">
      <RouterLink v-for="link in catalog.collectionLinks.slice(0, 4)" :key="link.slug" :to="`/collection/${link.slug}`" custom v-slot="{ navigate }">
        <button type="button" @click="navigate">{{ link.nav }}</button>
      </RouterLink>
      <RouterLink to="/maison" custom v-slot="{ navigate }"><button type="button" @click="navigate">THE MAISON</button></RouterLink>
      <RouterLink to="/stories" custom v-slot="{ navigate }"><button type="button" @click="navigate">STORIES</button></RouterLink>
      <RouterLink to="/contact" custom v-slot="{ navigate }"><button type="button" @click="navigate">CONTACT</button></RouterLink>
      <RouterLink to="/boutiques" custom v-slot="{ navigate }"><button type="button" @click="navigate">BOUTIQUES</button></RouterLink>
    </div>
    <div class="right">
      <button type="button" @click="bag.openDrawer('search')">SEARCH</button>
      <button type="button" @click="bag.openDrawer('cart')">BAG <span id="bagCount">({{ bag.count }})</span></button>
    </div>
  </header>
</template>
