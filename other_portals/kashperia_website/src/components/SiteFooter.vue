<script setup>
import { ref } from 'vue'

import { house, whatsappLink } from '@/format'
import { HOUSE_COLLECTIONS } from '@/stores/catalog'

const columns = [
  {
    title: 'Discover',
    links: [
      ...HOUSE_COLLECTIONS.map((c) => ({ label: c.label, to: `/collection/${c.slug}` })),
      { label: 'Bestsellers', to: '/curated/bestsellers' },
      { label: 'Limited Editions', to: '/page/limited-editions' },
    ],
  },
  {
    title: 'The Maison',
    links: [
      { label: 'Maison', to: '/maison' },
      { label: 'Stories', to: '/stories' },
      { label: 'Craftsmanship', to: '/maison/craftsmanship' },
      { label: 'Materials', to: '/maison/materials' },
      { label: 'KASHPERIA PRIVÉ', to: '/page/kashperia-prive' },
      { label: 'Beyond Kashperia', to: '/page/beyond-kashperia' },
    ],
  },
  {
    title: 'Explore',
    links: [
      { label: 'Our Boutiques', to: '/boutiques' },
      { label: 'Bespoke', to: '/page/bespoke' },
      { label: 'Personal Stylist & Design Team', to: '/page/personal-stylist-and-design-team' },
      { label: 'Monogramming', to: '/page/monogramming' },
      { label: 'Authentication', to: '/page/authenticate-your-product' },
      { label: 'Garment Care', to: '/page/garment-care' },
    ],
  },
  {
    title: 'Client Care',
    links: [
      { label: 'Client Services', to: '/page/client-services' },
      { label: 'Shipping', to: '/page/shipping' },
      { label: 'Returns & Exchanges', to: '/page/returns-and-exchanges' },
      { label: 'Frequently Asked Questions', to: '/page/frequently-asked-questions' },
      { label: 'Contact', to: '/contact' },
      { label: 'Payment Terms', to: '/page/payment-terms' },
      { label: 'Privacy', to: '/page/privacy' },
    ],
  },
]

const email = ref('')
const consent = ref(false)
const status = ref('')

/** The reference newsletter: a prefilled email to the House (no mailing-list provider yet). */
function subscribe() {
  if (!consent.value) {
    status.value = 'Please confirm consent.'
    return
  }
  status.value = 'Your email application will open to send your subscription request.'
  const body = `Please subscribe ${email.value.trim()} to the Kashperia newsletter. I consent to marketing communications and understand I may unsubscribe.`
  window.location.href = `mailto:${house.email}?subject=${encodeURIComponent('Kashperia newsletter subscription')}&body=${encodeURIComponent(body)}`
}
</script>

<template>
  <footer class="footer">
    <div class="footgrid k-footer-grid">
      <div>
        <div class="logo">KASHPERIA</div>
        <p class="small">Rare by Nature. Exceptional by Craft.<br />Destined for Generations.</p>
      </div>
      <div v-for="column in columns" :key="column.title">
        <h4>{{ column.title }}</h4>
        <RouterLink v-for="link in column.links" :key="link.label" :to="link.to" custom v-slot="{ navigate }">
          <button type="button" @click="navigate">{{ link.label }}</button>
        </RouterLink>
      </div>
    </div>
    <div class="sub"><span>© KASHPERIA · THE HOUSE OF KASHPERIA</span><span>ENGLISH · QATAR (QAR)</span></div>
    <div class="k-newsletter">
      <div>
        <span class="eyebrow">LETTERS FROM THE MAISON</span>
        <h3 class="serif">The Kashperia Journal</h3>
        <p>New collections, craftsmanship and private invitations.</p>
      </div>
      <form @submit.prevent="subscribe">
        <label for="kNewsEmail">EMAIL ADDRESS</label>
        <div class="k-news-row">
          <input id="kNewsEmail" v-model="email" type="email" required placeholder="Your email address" />
          <button type="submit">SUBSCRIBE →</button>
        </div>
        <label class="k-consent"><input id="kNewsConsent" v-model="consent" type="checkbox" required /> I agree to receive Kashperia updates and understand I can unsubscribe.</label>
        <p id="kNewsStatus" role="status">{{ status }}</p>
      </form>
    </div>
    <div class="k-social-footer">
      <a :href="house.instagram" target="_blank" rel="noopener noreferrer">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="2.5" width="19" height="19" rx="5" /><circle cx="12" cy="12" r="4.2" /><circle cx="17.7" cy="6.5" r="1" fill="currentColor" stroke="none" /></svg>
        <span>INSTAGRAM</span>
      </a>
      <a :href="whatsappLink()" target="_blank" rel="noopener noreferrer">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.4 11.5a8.4 8.4 0 0 1-12.5 7.4L3.3 20l1.2-4.4a8.4 8.4 0 1 1 15.9-4.1Z" /><path d="M8.5 8.1c-.3-.6-.7-.6-1-.4-.6.4-1 1.2-.8 2 .5 2.3 3.1 4.9 5.5 5.8 1.1.4 2 .2 2.5-.4l.5-1.1-2.4-1.1-1 1c-1.6-.7-2.8-1.9-3.5-3.5l.9-.9-.7-1.4Z" stroke-width="1.25" /></svg>
        <span>WHATSAPP</span>
      </a>
      <a :href="`mailto:${house.email}`">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="5" width="19" height="14" rx="1.5" /><path d="m3.5 6.5 8.5 6.6 8.5-6.6" /></svg>
        <span>EMAIL</span>
      </a>
      <RouterLink to="/boutiques" custom v-slot="{ navigate }"><button type="button" @click="navigate">BOUTIQUES ↗</button></RouterLink>
    </div>
  </footer>
</template>
