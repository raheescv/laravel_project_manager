import { ref } from 'vue'

/**
 * Computers (and iPads in landscape) get the web layout: the wallet of cards on
 * the left, the page on the right. Phones keep the app layout. Keep in step with
 * the `@media (min-width: 1024px)` block at the end of app.css.
 */
const query = window.matchMedia('(min-width: 1024px)')

export const desktop = ref(query.matches)

query.addEventListener('change', (event) => {
  desktop.value = event.matches
})
