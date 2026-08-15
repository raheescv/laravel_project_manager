import PublicAppointment from './components/PropertyAppointment/PublicAppointment.vue'
import { createApp } from 'vue'

/**
 * The public appointment page is served to logged-out customers, so it mounts a
 * bare Vue app rather than going through mountVueApp() — that helper pulls in
 * vue-toastification, which this page has no use for and no chrome to show.
 *
 * The company branding is handed over as props: it lives in the hero the
 * component renders, and a logged-out page has no other chrome to carry it.
 */
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('property-appointment')
    if (!el) return

    createApp(PublicAppointment, {
        dataUrl: el.dataset.dataUrl,
        bookUrl: el.dataset.bookUrl,
        csrf: el.dataset.csrf,
        companyName: el.dataset.companyName || '',
        companyLogo: el.dataset.companyLogo || '',
        companyTagline: el.dataset.companyTagline || '',
    }).mount(el)
})
