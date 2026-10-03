<template>
    <div class="lgx">
        <!-- Split Horizon: brand panel with the live scene, form on a clean pane -->
        <section v-if="screen.layout === 'split'" class="lgx-split">
            <aside class="lgx-art">
                <LiveBackground :kind="screen.background" dark anchor="corner" />
                <div class="lgx-brand" :class="{ 'lgx-brand--logo': screen.logo }">
                    <span class="lgx-mark" :class="{ 'lgx-mark--logo': screen.logo }"><img v-if="screen.logo" :src="screen.logo" alt="" /><i v-else class="fa fa-cloud"></i></span>
                    {{ screen.company }}
                </div>
                <div>
                    <h2>{{ screen.copy.headline }} <em>{{ screen.copy.highlight }}</em></h2>
                    <p class="lgx-lede">{{ screen.copy.lede }}</p>
                    <div class="lgx-chips">
                        <div v-for="feature in screen.copy.features" :key="feature.title" class="lgx-chip">
                            <b>
                                <span v-if="feature.icon === 'live'" class="lgx-live-dot"></span>
                                <i v-else class="fa" :class="feature.icon"></i>
                                {{ feature.title }}
                            </b>
                            <span>{{ feature.caption }}</span>
                        </div>
                    </div>
                </div>
                <div class="lgx-foot">© {{ year }} {{ screen.company }}</div>
            </aside>
            <main class="lgx-pane">
                <div class="lgx-inner">
                    <div class="lgx-brand lgx-brand--mobile" :class="{ 'lgx-brand--logo': screen.logo }">
                        <span class="lgx-mark" :class="{ 'lgx-mark--logo': screen.logo }"><img v-if="screen.logo" :src="screen.logo" alt="" /><i v-else class="fa fa-cloud"></i></span>
                        {{ screen.company }}
                    </div>
                    <h1>Welcome back</h1>
                    <p class="lgx-sub">Sign in to your <b>{{ screen.company }}</b> workspace.</p>
                    <LoginForm :login-url="screen.loginUrl" :prefill="screen.prefill" :preview="screen.preview" />
                </div>
            </main>
        </section>

        <!-- Frosted Canvas: one glass card floating over the live scene -->
        <section v-else class="lgx-frosted">
            <LiveBackground :kind="screen.background" :dark="dark" />
            <div class="lgx-card">
                <div class="lgx-card-head">
                    <div class="lgx-mark lgx-mark--lg" :class="{ 'lgx-mark--logo': screen.logo }"><img v-if="screen.logo" :src="screen.logo" alt="" /><i v-else class="fa fa-cloud"></i></div>
                    <h1>Sign in</h1>
                    <p>{{ screen.copy.tagline }}</p>
                </div>
                <div class="lgx-tenant"><span class="lgx-dot"></span> Workspace <b>{{ screen.company }}</b></div>
                <LoginForm :login-url="screen.loginUrl" :prefill="screen.prefill" :preview="screen.preview" />
            </div>
        </section>

        <button type="button" class="lgx-theme" :title="dark ? 'Switch to light' : 'Switch to dark'" @click="toggleTheme">
            <i class="fa" :class="dark ? 'fa-sun-o' : 'fa-moon-o'"></i>
        </button>
        <div v-if="screen.preview" class="lgx-preview-tag"><i class="fa fa-eye"></i> Preview</div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import LiveBackground from './LiveBackground.vue'
import LoginForm from './LoginForm.vue'

defineProps({
    screen: { type: Object, required: true },
})

const STORAGE_KEY = 'login-theme'
const year = new Date().getFullYear()
const dark = ref(false)

function readStoredTheme() {
    try {
        return localStorage.getItem(STORAGE_KEY)
    } catch {
        return null
    }
}

function applyTheme() {
    document.documentElement.dataset.theme = dark.value ? 'dark' : 'light'
}

function toggleTheme() {
    dark.value = !dark.value
    applyTheme()
    try {
        localStorage.setItem(STORAGE_KEY, dark.value ? 'dark' : 'light')
    } catch {
        // Private mode: the choice simply lasts for this visit.
    }
}

onMounted(() => {
    const stored = readStoredTheme()
    dark.value = stored ? stored === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches
    applyTheme()
})
</script>

<style>
:root {
    --lgx-brand: #0A62C8; --lgx-brand-dark: #01377F; --lgx-accent: #0BA8FA;
    --lgx-bg: #F5F7FB; --lgx-surface: #FFFFFF; --lgx-ink: #0B1220; --lgx-ink-2: #4B5568; --lgx-ink-3: #8A94A6;
    --lgx-line: #E4E8F0; --lgx-field: #F7F9FC; --lgx-danger: #D93A3A; --lgx-danger-bg: #FDF0F0; --lgx-ok: #12A150;
    --lgx-ring: rgba(10, 98, 200, .18); --lgx-shadow: 0 1px 2px rgba(11, 18, 32, .04), 0 12px 40px -12px rgba(11, 18, 32, .18);
}
:root[data-theme="dark"] {
    --lgx-bg: #0A0E16; --lgx-surface: #111724; --lgx-ink: #EEF2F8; --lgx-ink-2: #AAB4C5; --lgx-ink-3: #6B7689;
    --lgx-line: #222B3B; --lgx-field: #0D131E; --lgx-danger: #FF6B6B; --lgx-danger-bg: rgba(255, 107, 107, .08);
    --lgx-ring: rgba(11, 168, 250, .25); --lgx-shadow: 0 1px 2px rgba(0, 0, 0, .3), 0 20px 50px -20px rgba(0, 0, 0, .7);
}
body { margin: 0; background: var(--lgx-bg); }
.lgx, .lgx * { box-sizing: border-box; }
.lgx { min-height: 100vh; font-family: Inter, system-ui, sans-serif; color: var(--lgx-ink); -webkit-font-smoothing: antialiased; }
.lgx h1, .lgx h2, .lgx p { margin: 0; }
.lgx-live { position: absolute; inset: 0; width: 100%; height: 100%; display: block; pointer-events: none; }

/* brand */
.lgx-brand { display: flex; align-items: center; gap: 11px; font-weight: 700; font-size: 17px; letter-spacing: .01em; }
.lgx-mark { width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; font-size: 16px; color: #fff; overflow: hidden; flex: none;
    background: linear-gradient(135deg, var(--lgx-accent), var(--lgx-brand)); box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .25); }
.lgx-mark img { width: 100%; height: 100%; object-fit: contain; background: #fff; padding: 4px; }
.lgx-mark--lg { width: 54px; height: 54px; border-radius: 15px; font-size: 22px; margin: 0 auto 18px; box-shadow: 0 10px 24px -8px rgba(10, 98, 200, .6); }
.lgx-mark.lgx-mark--logo { width: auto; height: 100px; min-width: 100px; max-width: 260px; padding: 12px 16px; border-radius: 20px; background: #fff;
    box-shadow: 0 0 0 1px rgba(255, 255, 255, .6), 0 0 0 7px rgba(255, 255, 255, .1), 0 0 48px 6px rgba(11, 168, 250, .45), 0 18px 40px -14px rgba(0, 0, 0, .5);
    animation: lgx-glow 4s ease-in-out infinite; }
.lgx-mark.lgx-mark--logo img { width: auto; height: 100%; max-width: 228px; padding: 0; background: none; }
@keyframes lgx-glow { 50% { box-shadow: 0 0 0 1px rgba(255, 255, 255, .6), 0 0 0 10px rgba(255, 255, 255, .06), 0 0 64px 10px rgba(11, 168, 250, .55), 0 18px 40px -14px rgba(0, 0, 0, .5); } }
.lgx-brand--logo { gap: 20px; font-size: 26px; letter-spacing: -.01em; }
.lgx-brand--mobile .lgx-mark--logo { height: 68px; min-width: 68px; border-radius: 16px; padding: 9px 13px;
    box-shadow: 0 0 0 1px var(--lgx-line), 0 0 0 6px color-mix(in srgb, var(--lgx-accent) 12%, transparent), 0 12px 30px -12px rgba(10, 98, 200, .45); animation: none; }
.lgx-brand--mobile.lgx-brand--logo { font-size: 22px; }
.lgx-mark--lg.lgx-mark--logo { height: 92px; min-width: 92px; margin: 0 auto 20px; display: flex; width: fit-content;
    box-shadow: 0 0 0 1px var(--lgx-line), 0 0 0 7px color-mix(in srgb, var(--lgx-accent) 14%, transparent), 0 0 44px 4px color-mix(in srgb, var(--lgx-accent) 30%, transparent), 0 16px 34px -14px rgba(10, 98, 200, .5); animation: none; }

/* Split Horizon */
.lgx-split { min-height: 100vh; display: grid; grid-template-columns: 1.05fr 1fr; }
.lgx-art { position: relative; overflow: hidden; color: #fff; padding: 44px 56px; display: flex; flex-direction: column; justify-content: space-between;
    background: radial-gradient(1200px 600px at 110% -10%, rgba(11, 168, 250, .55), transparent 60%),
        radial-gradient(800px 500px at -20% 110%, rgba(10, 98, 200, .9), transparent 60%), #01275C; }
.lgx-art::after { content: ""; position: absolute; inset: 0; pointer-events: none;
    background: linear-gradient(90deg, rgba(1, 24, 58, .6) 0%, rgba(1, 24, 58, .15) 50%, transparent 75%); }
.lgx-art > :not(.lgx-live) { position: relative; z-index: 1; }
.lgx-art h2 { font-size: clamp(28px, 3.2vw, 42px); line-height: 1.12; font-weight: 700; letter-spacing: -.025em; max-width: 480px; }
.lgx-art h2 em { font-family: "Instrument Serif", serif; font-weight: 400; font-style: italic; color: #9ED8FF; letter-spacing: 0; }
.lgx-lede { color: rgba(255, 255, 255, .72); font-size: 15px; line-height: 1.6; max-width: 420px; margin-top: 16px !important; }
.lgx-chips { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 34px; }
.lgx-chip { padding: 14px 16px; border-radius: 14px; background: rgba(255, 255, 255, .07); border: 1px solid rgba(255, 255, 255, .12); backdrop-filter: blur(8px); min-width: 130px; }
.lgx-chip b { display: block; font-size: 19px; }
.lgx-chip span { font-size: 12px; color: rgba(255, 255, 255, .65); }
.lgx-live-dot { display: inline-block !important; width: 8px; height: 8px; border-radius: 50%; background: #3DF5A6; margin-right: 8px; vertical-align: middle; animation: lgx-ping 1.8s infinite; }
@keyframes lgx-ping { 0% { box-shadow: 0 0 0 0 rgba(61, 245, 166, .6); } 70%, 100% { box-shadow: 0 0 0 8px rgba(61, 245, 166, 0); } }
.lgx-foot { font-size: 12.5px; color: rgba(255, 255, 255, .5); }
.lgx-pane { display: grid; place-items: center; padding: 40px 24px; background: var(--lgx-surface); }
.lgx-inner { width: 100%; max-width: 380px; }
.lgx-inner h1 { font-size: 28px; font-weight: 700; letter-spacing: -.02em; }
.lgx-sub { color: var(--lgx-ink-2); font-size: 14.5px; margin: 8px 0 30px !important; }
.lgx-brand--mobile { display: none; margin-bottom: 28px; }
@media (max-width: 900px) {
    .lgx-split { grid-template-columns: 1fr; }
    .lgx-art { display: none; }
    .lgx-brand--mobile { display: flex; }
}

/* Frosted Canvas */
.lgx-frosted { min-height: 100vh; display: grid; place-items: center; padding: 40px 16px; position: relative; overflow: hidden;
    background: radial-gradient(1000px 700px at 50% 120%, color-mix(in srgb, var(--lgx-brand) 14%, transparent), transparent 70%), var(--lgx-bg); }
.lgx-card { position: relative; z-index: 1; width: 100%; max-width: 420px; padding: 38px 34px 30px; border-radius: 24px;
    background: color-mix(in srgb, var(--lgx-surface) 74%, transparent); backdrop-filter: blur(24px) saturate(1.4);
    border: 1px solid color-mix(in srgb, var(--lgx-surface) 60%, transparent); box-shadow: var(--lgx-shadow); }
.lgx-card-head { text-align: center; margin-bottom: 26px; }
.lgx-card-head h1 { font-size: 24px; font-weight: 700; letter-spacing: -.02em; }
.lgx-card-head p { color: var(--lgx-ink-2); font-size: 14px; margin-top: 6px !important; }
.lgx-frosted .lgx-input { background: color-mix(in srgb, var(--lgx-surface) 80%, transparent); }
.lgx-tenant { display: flex; align-items: center; gap: 8px; padding: 10px 12px; border-radius: 12px; border: 1px dashed var(--lgx-line); margin-bottom: 20px; font-size: 13px; color: var(--lgx-ink-2); }
.lgx-tenant b { color: var(--lgx-ink); }
.lgx-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--lgx-ok); box-shadow: 0 0 0 3px color-mix(in srgb, var(--lgx-ok) 25%, transparent); }
@media (max-width: 480px) { .lgx-card { padding: 30px 22px 24px; } }

/* form */
.lgx-field { margin-bottom: 16px; }
.lgx-label { display: block; font-size: 13px; font-weight: 600; color: var(--lgx-ink); margin-bottom: 7px; }
.lgx-wrap { position: relative; }
.lgx-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--lgx-ink-3); font-size: 15px !important; width: 16px; text-align: center; transition: color .15s; }
.lgx-wrap:focus-within .lgx-icon { color: var(--lgx-brand); }
.lgx-input { width: 100%; height: 48px; padding: 0 16px 0 42px; font: 500 15px Inter, system-ui, sans-serif; color: var(--lgx-ink); background: var(--lgx-field);
    border: 1.5px solid var(--lgx-line); border-radius: 12px; outline: none; transition: border-color .15s, box-shadow .15s, background .15s; }
.lgx-input--toggle { padding-right: 46px; }
.lgx-input::placeholder { color: var(--lgx-ink-3); font-weight: 400; }
.lgx-input:focus { border-color: var(--lgx-brand); background: var(--lgx-surface); box-shadow: 0 0 0 4px var(--lgx-ring); }
.lgx-input.lgx-bad { border-color: var(--lgx-danger); }
.lgx-input:-webkit-autofill { -webkit-text-fill-color: var(--lgx-ink); -webkit-box-shadow: 0 0 0 40px var(--lgx-field) inset; }
.lgx-eye { position: absolute; right: 6px; top: 50%; transform: translateY(-50%); border: 0; background: transparent; width: 36px; height: 36px; border-radius: 9px; color: var(--lgx-ink-3); cursor: pointer; }
.lgx-eye:hover { color: var(--lgx-ink); background: var(--lgx-line); }
.lgx-err, .lgx-caps { font-size: 12.5px; margin-top: 6px; display: flex; gap: 6px; align-items: center; }
.lgx-err { color: var(--lgx-danger); }
.lgx-caps { color: #B7791F; }
.lgx-check { display: inline-flex; align-items: center; gap: 9px; font-size: 13.5px; color: var(--lgx-ink-2); cursor: pointer; user-select: none; margin: 4px 0 22px; position: relative; }
.lgx-check input { position: absolute; opacity: 0; pointer-events: none; }
.lgx-box { width: 18px; height: 18px; border-radius: 5px; border: 1.5px solid var(--lgx-line); background: var(--lgx-field); display: grid; place-items: center; transition: .15s; }
.lgx-box .fa { font-size: 11px; color: #fff; opacity: 0; }
.lgx-check input:checked + .lgx-box { background: var(--lgx-brand); border-color: var(--lgx-brand); }
.lgx-check input:checked + .lgx-box .fa { opacity: 1; }
.lgx-check input:focus-visible + .lgx-box { box-shadow: 0 0 0 4px var(--lgx-ring); }
.lgx-submit { width: 100%; height: 50px; border: 0; border-radius: 12px; font: 600 15px Inter, system-ui, sans-serif; color: #fff; cursor: pointer;
    background: linear-gradient(135deg, var(--lgx-brand), var(--lgx-brand-dark)); box-shadow: 0 8px 20px -8px rgba(10, 98, 200, .6);
    display: flex; align-items: center; justify-content: center; gap: 10px; transition: transform .12s, box-shadow .15s, filter .15s; }
.lgx-submit:hover:not(:disabled) { filter: brightness(1.08); }
.lgx-submit:active:not(:disabled) { transform: translateY(1px); }
.lgx-submit:disabled { cursor: default; opacity: .85; }
.lgx-submit.lgx-ok { background: linear-gradient(135deg, #17B15C, #0E8A45); box-shadow: 0 8px 20px -8px rgba(18, 161, 80, .6); }
.lgx-arrow { transition: transform .2s; }
.lgx-submit:hover .lgx-arrow { transform: translateX(3px); }
.lgx-spin { width: 18px; height: 18px; border: 2.2px solid rgba(255, 255, 255, .35); border-top-color: #fff; border-radius: 50%; animation: lgx-spin .7s linear infinite; }
@keyframes lgx-spin { to { transform: rotate(360deg); } }
.lgx-alert { display: flex; gap: 11px; align-items: flex-start; padding: 12px 14px; border-radius: 12px; background: var(--lgx-danger-bg); color: var(--lgx-danger);
    font-size: 13.5px; line-height: 1.45; margin-bottom: 18px; border: 1px solid color-mix(in srgb, var(--lgx-danger) 22%, transparent); }
.lgx-alert .fa { margin-top: 2px; }
.lgx-shake { animation: lgx-shake .4s; }
@keyframes lgx-shake { 20%, 60% { transform: translateX(-6px); } 40%, 80% { transform: translateX(6px); } }
.lgx-fade-enter-active, .lgx-fade-leave-active { transition: opacity .2s, transform .2s; }
.lgx-fade-enter-from, .lgx-fade-leave-to { opacity: 0; transform: translateY(-4px); }
.lgx-secure { display: flex; align-items: center; justify-content: center; gap: 7px; font-size: 12px; color: var(--lgx-ink-3); margin-top: 20px; }

/* chrome */
.lgx-theme { position: fixed; top: 16px; right: 16px; z-index: 5; width: 38px; height: 38px; border-radius: 50%; border: 1px solid var(--lgx-line);
    background: var(--lgx-surface); color: var(--lgx-ink-2); cursor: pointer; box-shadow: var(--lgx-shadow); }
.lgx-theme:hover { color: var(--lgx-ink); }
.lgx-preview-tag { position: fixed; top: 16px; left: 50%; transform: translateX(-50%); z-index: 5; padding: 6px 14px; border-radius: 999px;
    background: #0B1220; color: #fff; font-size: 12px; font-weight: 600; }
@media (prefers-reduced-motion: reduce) { .lgx-live-dot, .lgx-shake, .lgx-mark--logo { animation: none; } }
</style>
