/**
 * The caterer's own marks, bundled with this build (public/brand/): the logo on
 * every page and card, the photo behind the first registration step and the
 * watermark behind every other page. A build for another caterer points the
 * VITE_BRAND_* variables at its own files (paths are relative to the portal).
 */
const base = import.meta.env.BASE_URL
const asset = (value, fallback) => {
  const path = value ?? fallback
  if (!path) return null
  // Absolute URL: a relative url() inside a CSS variable resolves against the
  // stylesheet that uses it (dist/assets/), not the page, and 404s.
  return new URL(/^(https?:)?\/\//.test(path) || path.startsWith('/') ? path : `${base}${path}`, document.baseURI).href
}

export const brand = {
  name: import.meta.env.VITE_BRAND_NAME ?? 'Newrest Gulf',
  logo: asset(import.meta.env.VITE_BRAND_LOGO, 'brand/newrest-gulf.png'),
  backdrop: asset(import.meta.env.VITE_BRAND_BACKDROP, 'brand/my-place.png'),
  registrationPhoto: asset(import.meta.env.VITE_BRAND_REGISTRATION_PHOTO, 'brand/registration-bg.jpg'),
}

/** Paints the watermark into the page background once (see `.pp-backdrop` in app.css). */
export function applyBrand() {
  if (brand.backdrop) document.documentElement.style.setProperty('--brand-backdrop', `url("${brand.backdrop}")`)
  if (brand.registrationPhoto) document.documentElement.style.setProperty('--brand-photo', `url("${brand.registrationPhoto}")`)
}
