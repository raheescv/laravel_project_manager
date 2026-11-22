<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { house } from '@/format'
import { toast } from '@/toast'

/**
 * Editorial pages — the reference site's own markup (src/content/pages/*.html,
 * produced from template/kashperia-site), styled by the verbatim reference CSS.
 * The fragments carry hooks instead of inline handlers:
 *   data-to="/route"        navigate          data-scroll="kmu-about"  scroll to a Maison section
 *   data-form="enquiry"     mail the House    data-form="notice"       show the form's status line
 *   data-action="notice"    toast a message   img[data-film]           the house film (video)
 *   img[data-banner]        a titled banner shown whole, its heading moved beneath it
 */
const props = defineProps({ page: { type: String, required: true } })

const pages = import.meta.glob('../content/pages/*.html', { query: '?raw', import: 'default', eager: true })
const ALIASES = { 'the-kashperia-standard': 'standards', 'about-us': 'maison', 'design-centre': 'personal-stylist-and-design-team' }
const MAISON_SECTIONS = { about: 'kmu-about', craftsmanship: 'kmu-craft', materials: 'kmu-materials' }

const route = useRoute()
const router = useRouter()
const root = ref(null)

const slug = computed(() => ALIASES[props.page] || props.page)
const html = computed(() => pages[`../content/pages/${slug.value}.html`] ?? null)

const titles = {
  home: 'KASHPERIA — Rare by Nature. Exceptional by Craft.',
}

function enhance() {
  const el = root.value
  if (!el) return
  const heading = el.querySelector('h1')
  document.title = titles[slug.value] || `${heading ? heading.textContent.replace(/\s+/g, ' ').trim() : 'Kashperia'} — KASHPERIA`

  // The house film replaces its poster unless the visitor prefers reduced motion.
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  el.querySelectorAll('img[data-film]').forEach((img) => {
    if (reduceMotion) return
    const video = document.createElement('video')
    video.className = img.className + ' k-film-video'
    video.src = img.dataset.film
    video.poster = img.getAttribute('src')
    video.muted = true
    video.loop = true
    video.autoplay = true
    video.playsInline = true
    video.setAttribute('playsinline', '')
    video.setAttribute('aria-label', img.alt)
    img.replaceWith(video)
    video.play().catch(() => {})
  })

  // A banner with its own lettering can't sit behind a page title: show it whole.
  el.querySelectorAll('img[data-banner]').forEach((img) => {
    img.closest('section')?.classList.add('k-banner-hero')
  })

  scrollToSection()
}

function scrollToSection(behavior = 'auto') {
  if (slug.value !== 'maison') return
  const id = MAISON_SECTIONS[route.params.section]
  const target = id && root.value?.querySelector(`#${id}`)
  if (target) {
    const top = target.getBoundingClientRect().top + window.scrollY - 90
    window.scrollTo({ top, behavior })
  }
}

function onClick(event) {
  const scroll = event.target.closest('[data-scroll]')
  if (scroll) {
    event.preventDefault()
    const section = Object.keys(MAISON_SECTIONS).find((k) => MAISON_SECTIONS[k] === scroll.dataset.scroll)
    router.replace({ name: 'maison', params: { section } }).then(() => scrollToSection('smooth'))
    return
  }
  const link = event.target.closest('[data-to]')
  if (link) {
    event.preventDefault()
    router.push(link.dataset.to)
    return
  }
  const notice = event.target.closest('[data-action="notice"]')
  if (notice) {
    event.preventDefault()
    toast(notice.dataset.message)
  }
}

/** The reference enquiry: a prefilled email to the House, with the visitor's details. */
function sendEnquiry(form) {
  const v = new FormData(form)
  const email = String(v.get('email') || '').trim()
  const mobile = String(v.get('mobile') || '').trim()
  if (!email && !mobile) {
    toast('Please provide your email address or mobile number.')
    return
  }
  const subject = `Kashperia — ${v.get('topic')} — ${v.get('subject')}`
  const body = `Name: ${v.get('name')}\nEmail: ${email}\nMobile: ${mobile}\nEnquiry: ${v.get('subject')}\n\n${v.get('message') || ''}`
  window.location.href = `mailto:${house.email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`
}

function onSubmit(event) {
  const form = event.target.closest('form[data-form]')
  if (!form) return
  event.preventDefault()
  if (form.dataset.form === 'enquiry') sendEnquiry(form)
  if (form.dataset.form === 'notice') {
    const status = form.dataset.status && document.getElementById(form.dataset.status)
    if (status) status.textContent = form.dataset.message
    else toast(form.dataset.message)
  }
}

onMounted(() => nextTick(enhance))
watch(html, () => nextTick(enhance))
watch(
  () => route.params.section,
  () => nextTick(() => scrollToSection('smooth')),
)
</script>

<template>
  <div v-if="html" ref="root" class="k-page" :class="`k-page--${slug}`" @click="onClick" @submit="onSubmit" v-html="html"></div>
  <div v-else class="page k-curated">
    <span class="eyebrow">THE HOUSE OF KASHPERIA</span>
    <h1>Page not found</h1>
    <div class="k-curated-empty">
      <h2 class="serif">This page has moved</h2>
      <p>The page you were looking for is no longer here.</p>
      <RouterLink to="/" custom v-slot="{ navigate }"><button type="button" class="k-side-clear" @click="navigate">RETURN TO THE HOUSE →</button></RouterLink>
    </div>
  </div>
</template>
