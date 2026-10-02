<template>
    <div class="tkx">
        <header class="tkx-bar">
            <div class="tkx-brand">
                <span class="tkx-logo"><i class="fa fa-ticket"></i></span>
                <div>
                    <b>Support Tickets</b>
                    <small>Console</small>
                </div>
            </div>

            <nav class="tkx-tabs">
                <button type="button" :class="{ on: view === 'board' }" @click="go('board')"><i class="fa fa-columns"></i> Board</button>
                <button v-if="permissions.import" type="button" :class="{ on: view === 'import' }" @click="go('import')"><i class="fa fa-upload"></i> Import</button>
            </nav>

            <a :href="dashboardUrl" class="tkx-btn tkx-back"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a>
        </header>

        <main class="tkx-main">
            <TicketBoard v-show="view === 'board'" ref="board" :permissions="permissions" />
            <TicketImport v-if="permissions.import && view === 'import'" @imported="onImported" @open-board="go('board')" />
        </main>
    </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref } from 'vue'
import TicketBoard from './TicketBoard.vue'
import TicketImport from './TicketImport.vue'

const root = document.getElementById('ticket-console')
const permissions = JSON.parse(root?.dataset.permissions || '{}')
const dashboardUrl = root?.dataset.dashboardUrl || '/'
const urls = { board: root?.dataset.boardUrl || '/ticket', import: root?.dataset.importUrl || '/ticket/import' }

const view = ref(root?.dataset.view === 'import' && permissions.import ? 'import' : 'board')
const board = ref(null)

function go(next) {
    if (view.value === next) return
    view.value = next
    history.pushState({ view: next }, '', urls[next])
}

function onPopState() {
    view.value = location.pathname.endsWith('/import') && permissions.import ? 'import' : 'board'
}

function onImported() {
    board.value?.reload()
}

onMounted(() => window.addEventListener('popstate', onPopState))
onBeforeUnmount(() => window.removeEventListener('popstate', onPopState))
</script>

<style>
/* =====================================================================
   .tkx — Support Tickets console ("Command Deck").
   Every colour derives from the Bootstrap theme vars, so the accent follows
   the settings theme colour and dark mode comes for free.
   ===================================================================== */
.tkx {
    --acc: var(--bs-primary);
    --acc-soft: color-mix(in srgb, var(--acc) 10%, transparent);
    --acc-line: color-mix(in srgb, var(--acc) 30%, transparent);
    --surf: var(--bs-body-bg);
    --surf-2: var(--bs-tertiary-bg);
    --line: var(--bs-border-color);
    --ink: var(--bs-emphasis-color);
    --mute: var(--bs-secondary-color);
    --r: 14px;
    height: 100dvh;
    display: flex;
    flex-direction: column;
    background: var(--surf-2);
    color: var(--bs-body-color);
    font-size: 13px;
}
.tkx *, .tkx *::before, .tkx *::after { box-sizing: border-box; }

/* ---------- console bar ---------- */
.tkx-bar { flex: none; display: flex; align-items: center; gap: 16px; padding: 10px 18px; background: var(--surf); border-bottom: 1px solid var(--line); }
.tkx-brand { display: flex; align-items: center; gap: 10px; min-width: 0; }
.tkx-brand b { display: block; color: var(--ink); font-size: 15px; font-weight: 600; line-height: 1.2; }
.tkx-brand small { color: var(--mute); font-size: 11px; }
.tkx-logo { width: 36px; height: 36px; border-radius: 11px; display: grid; place-items: center; color: #fff; font-size: 16px; background: linear-gradient(135deg, var(--acc), color-mix(in srgb, var(--acc), #000 25%)); box-shadow: 0 6px 16px -8px var(--acc); }
.tkx-tabs { display: inline-flex; gap: 2px; padding: 3px; background: var(--surf-2); border: 1px solid var(--line); border-radius: 11px; }
.tkx-tabs button { border: 0; background: transparent; padding: 6px 14px; border-radius: 8px; cursor: pointer; color: var(--mute); font-weight: 500; display: inline-flex; align-items: center; gap: 7px; }
.tkx-tabs button.on { background: var(--surf); color: var(--acc); box-shadow: 0 1px 3px rgba(0, 0, 0, .12); }
.tkx-back { margin-inline-start: auto; text-decoration: none; }
.tkx-main { flex: 1; min-height: 0; min-width: 0; display: flex; flex-direction: column; }

/* ---------- shared atoms ---------- */
.tkx-card { min-width: 0; background: var(--surf); border: 1px solid var(--line); border-radius: var(--r); box-shadow: 0 1px 2px rgba(15, 21, 34, .04); }
.tkx-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; border: 1px solid var(--line); background: var(--surf); color: var(--ink); padding: 7px 13px; border-radius: 9px; cursor: pointer; font-weight: 500; white-space: nowrap; line-height: 1.3; transition: border-color .15s, background .15s; }
.tkx-btn:hover { border-color: var(--acc-line); color: var(--ink); }
.tkx-btn:disabled { opacity: .55; cursor: not-allowed; }
.tkx-btn.pri { background: var(--acc); border-color: var(--acc); color: #fff; box-shadow: 0 6px 16px -8px var(--acc); }
.tkx-btn.pri:hover { background: color-mix(in srgb, var(--acc), #000 10%); color: #fff; }
.tkx-btn.ghost { background: transparent; }
.tkx-btn.danger { color: var(--bs-danger); }
.tkx-btn.danger:hover { border-color: color-mix(in srgb, var(--bs-danger) 40%, transparent); }
.tkx-btn.icon { padding: 7px 10px; }
.tkx-inp { display: flex; align-items: center; gap: 8px; border: 1px solid var(--line); background: var(--surf); border-radius: 9px; padding: 0 10px; height: 34px; color: var(--mute); }
.tkx-inp input, .tkx-inp select { border: 0; outline: 0; background: transparent; height: 100%; color: var(--ink); min-width: 0; flex: 1; font-size: 13px; }
.tkx-inp select option { color: initial; }
.tkx-inp:focus-within { border-color: var(--acc); box-shadow: 0 0 0 3px var(--acc-soft); }
.tkx-field { display: block; width: 100%; border: 1px solid var(--line); background: var(--surf); color: var(--ink); border-radius: 10px; padding: 8px 11px; font-size: 13px; outline: 0; }
.tkx-field:focus { border-color: var(--acc); box-shadow: 0 0 0 3px var(--acc-soft); }
.tkx-label { display: block; font-size: 10.5px; text-transform: uppercase; letter-spacing: 1px; color: var(--mute); font-weight: 600; margin: 0 0 5px 2px; }
.tkx-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--c); flex: none; display: inline-block; }
.tkx-gtag { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 500; padding: 2px 8px; border-radius: 20px; background: color-mix(in srgb, var(--g) 13%, transparent); color: color-mix(in srgb, var(--g), var(--ink) 30%); max-width: 100%; }
.tkx-gtag::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: var(--g); flex: none; }
.tkx-gtag span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tkx-av { width: 24px; height: 24px; border-radius: 50%; display: grid; place-items: center; font-size: 10px; font-weight: 600; color: #fff; background: var(--acc); flex: none; }
.tkx-empty { text-align: center; color: var(--mute); padding: 26px 10px; border: 1px dashed var(--line); border-radius: 12px; font-size: 12px; }
.tkx-sec { font-size: 10.5px; text-transform: uppercase; letter-spacing: 1.1px; color: var(--mute); font-weight: 600; margin: 20px 0 9px; display: flex; align-items: center; gap: 8px; }
.tkx-sec::after { content: ""; flex: 1; height: 1px; background: var(--line); }
.tkx-skel { background: linear-gradient(90deg, var(--surf-2), color-mix(in srgb, var(--surf-2), var(--line) 60%), var(--surf-2)); background-size: 200% 100%; animation: tkx-shimmer 1.2s infinite; border-radius: 12px; }
@keyframes tkx-shimmer { to { background-position: -200% 0; } }
.tkx-spin { animation: tkx-rot 1s linear infinite; display: inline-block; }
@keyframes tkx-rot { to { transform: rotate(360deg); } }

@media (max-width: 767px) {
    .tkx { height: auto; min-height: 100dvh; }
    .tkx-bar { flex-wrap: wrap; padding: 10px 12px; gap: 10px; }
    .tkx-brand small { display: none; }
    .tkx-back span { display: none; }
    .tkx-tabs { order: 3; width: 100%; }
    .tkx-tabs button { flex: 1; justify-content: center; }
}
</style>
