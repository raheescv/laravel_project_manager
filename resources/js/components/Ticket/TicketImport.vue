<template>
    <section class="tkx-imp">
        <ol class="tkx-steps">
            <li v-for="(s, i) in STEPS" :key="s.key" :class="{ on: step === i + 1, done: step > i + 1 }">
                <span class="b"><i v-if="step > i + 1" class="fa fa-check"></i><template v-else>{{ i + 1 }}</template></span>
                <div><b>{{ s.label }}</b><small>{{ s.hint }}</small></div>
            </li>
        </ol>

        <!-- ================= 1 · Upload ================= -->
        <div v-if="step === 1" class="tkx-imp-body narrow">
            <div class="tkx-card tkx-imp-pad">
                <label class="tkx-bigdrop" :class="{ over, busy }" @dragover.prevent="over = true" @dragleave.prevent="over = false" @drop.prevent="onDrop">
                    <input type="file" accept=".xlsx,.xls,.csv" hidden :disabled="busy" @change="onPick">
                    <span class="ic"><i class="fa" :class="busy ? 'fa-refresh tkx-spin' : 'fa-file-excel-o'"></i></span>
                    <b v-if="busy">Reading {{ fileName }}… {{ progress }}%</b>
                    <b v-else>Drop your Excel or CSV sheet here</b>
                    <span v-if="!busy">or <u>browse your computer</u> · .xlsx, .xls, .csv · up to 10 MB</span>
                </label>
                <div class="tkx-imp-notes">
                    <div><i class="fa fa-columns"></i><span><b>Any column names.</b> You match your sheet's headers to ticket fields in the next step; common names are matched for you.</span></div>
                    <div><i class="fa fa-tag"></i><span><b>Groups are plain text.</b> Whatever is in the group column becomes the ticket's group; existing groups are matched ignoring case.</span></div>
                    <div><i class="fa fa-shield"></i><span><b>Nothing is saved until you confirm.</b> Every row is checked first and you see exactly what will be imported.</span></div>
                </div>
                <div class="tkx-imp-tpl">
                    <span>Starting from scratch? The template has the columns already laid out.</span>
                    <a :href="ticketApi.templateUrl" class="tkx-btn"><i class="fa fa-download"></i> Download template</a>
                </div>
            </div>
        </div>

        <!-- ================= 2 · Match columns ================= -->
        <div v-else-if="step === 2" class="tkx-imp-body">
            <div class="tkx-imp-file tkx-card">
                <div class="tkx-imp-file-top">
                    <i class="fa fa-file-excel-o"></i>
                    <div class="nm"><b>{{ sheet.file_name }}</b><small>{{ sheet.row_count }} rows · {{ sheet.headers.length }} columns</small></div>
                    <span v-if="sheet.truncated" class="warn"><i class="fa fa-exclamation-triangle"></i> Only the first {{ sheet.max_rows }} rows are read</span>
                    <button type="button" class="tkx-btn ghost" @click="restart"><i class="fa fa-refresh"></i> Different file</button>
                </div>
                <div class="tkx-imp-src">
                    <div v-if="sheet.sheets.length > 1" class="grp">
                        <span class="tkx-label">Sheet</span>
                        <div class="tkx-sheet-tabs">
                            <button v-for="sh in sheet.sheets" :key="sh.name" type="button" :class="{ on: sheet.sheet === sh.name }" :disabled="busy" @click="switchSheet(sh.name, null)">
                                <i class="fa fa-table"></i>{{ sh.name }} <span class="ct">{{ Math.max(sh.rows - 1, 0) }}</span>
                            </button>
                        </div>
                    </div>
                    <div class="grp">
                        <span class="tkx-label">Headers are on row</span>
                        <label class="tkx-inp">
                            <select :value="sheet.header_row" :disabled="busy" aria-label="Header row" @change="switchSheet(sheet.sheet, Number($event.target.value))">
                                <option v-for="n in sheet.header_scan_rows" :key="n" :value="n">Row {{ n }}{{ n === 1 ? '' : ` (skip ${n - 1} above)` }}</option>
                            </select>
                        </label>
                    </div>
                </div>
            </div>

            <div v-if="!sheet.row_count" class="tkx-card tkx-imp-pad tkx-imp-warn">
                <i class="fa fa-exclamation-triangle"></i>
                <span>No ticket rows under the headers on <b>{{ sheet.sheet }}</b>. Pick the sheet that holds your tickets<template v-if="sheet.sheets.length < 2">, or a different header row</template>.</span>
            </div>

            <div class="tkx-map-grid">
                <div class="tkx-card">
                    <div class="tkx-card-h"><b>Ticket fields</b><small>Pick the column that holds each field</small></div>
                    <div class="tkx-map">
                        <div v-for="(field, key) in sheet.fields" :key="key" class="tkx-map-row" :class="{ missing: field.required && mappings[key] == null }">
                            <div class="f">
                                <b>{{ field.label }} <em v-if="field.required">Required</em></b>
                                <small>{{ field.hint }}</small>
                            </div>
                            <i class="fa fa-long-arrow-left arr"></i>
                            <div class="c">
                                <label class="tkx-inp">
                                    <select v-model="mappings[key]" :aria-label="`Column for ${field.label}`">
                                        <option :value="null">— Don't import —</option>
                                        <option v-for="h in sheet.headers" :key="h.index" :value="h.index">{{ h.label }}</option>
                                    </select>
                                </label>
                                <small v-if="mappings[key] != null" class="sample">
                                    <span v-if="sheet.mappings[key] === mappings[key]" class="auto"><i class="fa fa-magic"></i> Auto-matched</span>
                                    e.g. {{ sampleOf(mappings[key]) || '(blank)' }}
                                </small>
                                <small v-if="duplicated(key)" class="dup"><i class="fa fa-exclamation-triangle"></i> Also used for {{ duplicated(key) }}</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tkx-map-side">
                    <div class="tkx-card">
                        <div class="tkx-card-h"><b>Your sheet</b><small>Headers and the first values under them</small></div>
                        <ul class="tkx-cols-list">
                            <li v-for="h in sheet.headers" :key="h.index" :class="{ used: usedBy(h.index) }">
                                <div class="t"><b>{{ h.label }}</b><span class="to">{{ usedBy(h.index) || 'Not imported' }}</span></div>
                                <small>{{ h.samples.join(' · ') || '(empty)' }}</small>
                            </li>
                        </ul>
                    </div>

                    <div class="tkx-card tkx-imp-pad">
                        <span class="tkx-label">Blank status becomes</span>
                        <div class="tkx-seg">
                            <button v-for="s in STATUSES" :key="s.key" type="button" :class="{ on: options.default_status === s.key }" @click="options.default_status = s.key">
                                <span class="tkx-dot" :style="{ '--c': s.color }"></span>{{ s.label }}
                            </button>
                        </div>
                        <span class="tkx-label" style="margin-top: 14px">Title already exists</span>
                        <div class="tkx-seg">
                            <button type="button" :class="{ on: options.duplicates === 'skip' }" @click="options.duplicates = 'skip'"><i class="fa fa-ban"></i> Skip the row</button>
                            <button type="button" :class="{ on: options.duplicates === 'import' }" @click="options.duplicates = 'import'"><i class="fa fa-files-o"></i> Import anyway</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tkx-imp-foot">
                <button type="button" class="tkx-btn" @click="restart"><i class="fa fa-arrow-left"></i> Back</button>
                <span class="msg">{{ mappings.title == null ? 'Match a column to Title to continue.' : '' }}</span>
                <button type="button" class="tkx-btn pri" :disabled="mappings.title == null || !sheet.row_count || busy" @click="review">
                    <i class="fa" :class="busy ? 'fa-refresh tkx-spin' : 'fa-check-square-o'"></i> Check {{ sheet.row_count }} rows
                </button>
            </div>
        </div>

        <!-- ================= 3 · Check rows ================= -->
        <div v-else-if="step === 3" class="tkx-imp-body">
            <div class="tkx-sum">
                <button v-for="t in TABS" :key="t.key" type="button" class="tkx-card tkx-sum-i" :class="[t.key, { on: rowFilter === t.key }]" @click="setFilter(t.key)">
                    <span class="ic"><i class="fa" :class="t.icon"></i></span>
                    <div><b>{{ summary[t.count] }}</b><small>{{ t.label }}</small></div>
                </button>
            </div>

            <div class="tkx-card tkx-rev">
                <div class="tkx-rev-scroll">
                    <table>
                        <thead><tr><th>Row</th><th>Result</th><th>Title</th><th>Group</th><th>Status</th><th>Created</th><th>Notes</th></tr></thead>
                        <tbody>
                            <tr v-for="r in pageRows" :key="r.line" :class="r.state">
                                <td class="ln">{{ r.line }}</td>
                                <td><span class="st" :class="r.state"><i class="fa" :class="STATE_ICON[r.state]"></i>{{ STATE_LABEL[r.state] }}</span></td>
                                <td class="ti"><b>{{ r.data.title || '—' }}</b><small v-if="r.data.description">{{ r.data.description }}</small></td>
                                <td><span v-if="r.data.group" class="tkx-gtag" :style="{ '--g': groupColor(r.data.group) }"><span>{{ r.data.group }}</span></span><span v-else class="mute">—</span></td>
                                <td><span class="tkx-st"><span class="tkx-dot" :style="{ '--c': statusOf(r.data.status).color }"></span>{{ statusOf(r.data.status).label }}</span></td>
                                <td class="mute">{{ r.data.created_at ? formatDate(r.data.created_at) : 'Today' }}</td>
                                <td class="is">{{ r.issues.join(' ') }}</td>
                            </tr>
                            <tr v-if="!pageRows.length"><td colspan="7" class="tkx-empty">No rows in this view.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="pageCount > 1" class="tkx-pager">
                    <button type="button" class="tkx-btn icon" :disabled="page === 1" @click="page--"><i class="fa fa-angle-left"></i></button>
                    <span>Page {{ page }} of {{ pageCount }}</span>
                    <button type="button" class="tkx-btn icon" :disabled="page === pageCount" @click="page++"><i class="fa fa-angle-right"></i></button>
                </div>
            </div>

            <div class="tkx-imp-foot">
                <button type="button" class="tkx-btn" @click="step = 2"><i class="fa fa-arrow-left"></i> Change matching</button>
                <span class="msg">
                    <template v-if="summary.error">Rows with errors are left out — fix them in the sheet and import them later.</template>
                </span>
                <button type="button" class="tkx-btn pri" :disabled="!summary.ready || busy" @click="commit">
                    <i class="fa" :class="busy ? 'fa-refresh tkx-spin' : 'fa-upload'"></i> Import {{ summary.ready }} ticket{{ summary.ready === 1 ? '' : 's' }}
                </button>
            </div>
        </div>

        <!-- ================= 4 · Done ================= -->
        <div v-else class="tkx-imp-body narrow">
            <div class="tkx-card tkx-done">
                <span class="ic"><i class="fa fa-check"></i></span>
                <h3>{{ result.created }} ticket{{ result.created === 1 ? '' : 's' }} imported</h3>
                <p>
                    <template v-if="result.skipped">{{ result.skipped }} skipped as duplicates. </template>
                    <template v-if="result.failed">{{ result.failed }} left out because of errors.</template>
                    <template v-if="!result.skipped && !result.failed">Every row made it in.</template>
                </p>
                <div class="a">
                    <button type="button" class="tkx-btn" @click="restart"><i class="fa fa-upload"></i> Import another sheet</button>
                    <button type="button" class="tkx-btn pri" @click="$emit('open-board')"><i class="fa fa-columns"></i> Open the board</button>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { errorMessage, ticketApi } from './api.js'
import { formatDate, groupColor, STATUSES, statusOf } from './ticketMeta.js'

const emit = defineEmits(['imported', 'open-board'])

const STEPS = [
    { key: 'upload', label: 'Upload', hint: 'Excel or CSV' },
    { key: 'match', label: 'Match columns', hint: 'Headers → fields' },
    { key: 'check', label: 'Check rows', hint: 'See what imports' },
    { key: 'done', label: 'Done', hint: 'Tickets on the board' },
]
const TABS = [
    { key: 'all', label: 'Rows in sheet', count: 'total', icon: 'fa-list' },
    { key: 'ready', label: 'Ready to import', count: 'ready', icon: 'fa-check' },
    { key: 'skip', label: 'Skipped (duplicates)', count: 'skip', icon: 'fa-ban' },
    { key: 'error', label: 'With errors', count: 'error', icon: 'fa-exclamation-triangle' },
]
const STATE_LABEL = { ready: 'Ready', skip: 'Skip', error: 'Error' }
const STATE_ICON = { ready: 'fa-check', skip: 'fa-ban', error: 'fa-exclamation-triangle' }
const PER_PAGE = 50

const toast = useToast()
const step = ref(1)
const busy = ref(false)
const over = ref(false)
const progress = ref(0)
const fileName = ref('')
const sheet = ref(null)
const mappings = reactive({})
const options = reactive({ default_status: 'open', duplicates: 'skip' })
const rows = ref([])
const summary = ref({ total: 0, ready: 0, skip: 0, error: 0 })
const rowFilter = ref('all')
const page = ref(1)
const result = ref({ created: 0, skipped: 0, failed: 0 })

const filteredRows = computed(() => (rowFilter.value === 'all' ? rows.value : rows.value.filter((r) => r.state === rowFilter.value)))
const pageCount = computed(() => Math.max(1, Math.ceil(filteredRows.value.length / PER_PAGE)))
const pageRows = computed(() => filteredRows.value.slice((page.value - 1) * PER_PAGE, page.value * PER_PAGE))

function setFilter(key) {
    rowFilter.value = key
    page.value = 1
}

function sampleOf(index) {
    return sheet.value.headers.find((h) => h.index === index)?.samples[0] ?? ''
}

function usedBy(index) {
    return Object.entries(mappings).filter(([, v]) => v === index).map(([k]) => sheet.value.fields[k].label).join(', ')
}

function duplicated(key) {
    const index = mappings[key]
    if (index == null) return ''
    return Object.entries(mappings).filter(([k, v]) => k !== key && v === index).map(([k]) => sheet.value.fields[k].label).join(', ')
}

async function upload(file) {
    if (!file || busy.value) return
    busy.value = true
    progress.value = 0
    fileName.value = file.name
    try {
        const { data } = await ticketApi.importUpload(file, (e) => { progress.value = e.total ? Math.round((e.loaded / e.total) * 100) : 0 })
        applySheet(data)
        step.value = 2
    } catch (error) {
        toast.error(errorMessage(error))
    } finally {
        busy.value = false
        over.value = false
    }
}

function applySheet(data) {
    sheet.value = { ...sheet.value, ...data }
    Object.keys(mappings).forEach((k) => delete mappings[k])
    Object.assign(mappings, data.mappings)
}

/** Re-read the upload from another sheet or header row; the matching is guessed afresh for its headers. */
async function switchSheet(name, headerRow) {
    if (busy.value) return
    busy.value = true
    try {
        const { data } = await ticketApi.importSheet({ token: sheet.value.token, sheet: name, header_row: headerRow })
        applySheet(data)
    } catch (error) {
        toast.error(errorMessage(error))
    } finally {
        busy.value = false
    }
}

function onDrop(event) {
    upload(event.dataTransfer.files?.[0])
}

function onPick(event) {
    upload(event.target.files?.[0])
    event.target.value = ''
}

const payload = () => ({ token: sheet.value.token, mappings: { ...mappings }, options: { ...options } })

async function review() {
    busy.value = true
    try {
        const { data } = await ticketApi.importReview(payload())
        rows.value = data.rows
        summary.value = data.summary
        setFilter(data.summary.error ? 'error' : 'all')
        step.value = 3
    } catch (error) {
        toast.error(errorMessage(error))
    } finally {
        busy.value = false
    }
}

async function commit() {
    busy.value = true
    try {
        const response = await ticketApi.importCommit(payload())
        result.value = response.data
        step.value = 4
        toast.success(response.message)
        emit('imported')
    } catch (error) {
        toast.error(errorMessage(error))
    } finally {
        busy.value = false
    }
}

function restart() {
    step.value = 1
    sheet.value = null
    rows.value = []
}
</script>

<style>
.tkx-imp { flex: 1; min-height: 0; min-width: 0; overflow-y: auto; overflow-x: hidden; padding: 16px 18px 24px; }
.tkx-steps { list-style: none; margin: 0 auto 16px; padding: 0; display: flex; gap: 8px; max-width: 1100px; }
.tkx-steps li { flex: 1; display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 12px; background: var(--surf); border: 1px solid var(--line); color: var(--mute); position: relative; overflow: hidden; }
.tkx-steps li .b { width: 28px; height: 28px; border-radius: 50%; display: grid; place-items: center; font-weight: 600; background: var(--surf-2); flex: none; }
.tkx-steps li b { display: block; color: var(--ink); font-weight: 600; font-size: 12.5px; }
.tkx-steps li small { font-size: 11px; }
.tkx-steps li.on { border-color: var(--acc); box-shadow: 0 0 0 3px var(--acc-soft); }
.tkx-steps li.on .b { background: var(--acc); color: #fff; }
.tkx-steps li.done .b { background: color-mix(in srgb, var(--bs-success) 15%, transparent); color: var(--bs-success); }
.tkx-imp-body { max-width: 1100px; margin: 0 auto; min-width: 0; }
.tkx-imp-body.narrow { max-width: 760px; }
.tkx-imp-pad { padding: 18px; }
.tkx-bigdrop { display: flex; flex-direction: column; align-items: center; gap: 6px; text-align: center; padding: 38px 16px; border: 2px dashed var(--acc-line); border-radius: 14px; background: var(--acc-soft); color: var(--mute); cursor: pointer; margin: 0; transition: background .15s, border-color .15s; }
.tkx-bigdrop .ic { width: 58px; height: 58px; border-radius: 16px; display: grid; place-items: center; font-size: 26px; color: var(--acc); background: var(--surf); box-shadow: 0 8px 20px -12px var(--acc); margin-bottom: 6px; }
.tkx-bigdrop b { color: var(--ink); font-size: 15px; font-weight: 600; }
.tkx-bigdrop.over { border-color: var(--acc); background: color-mix(in srgb, var(--acc) 16%, transparent); }
.tkx-bigdrop.busy { cursor: progress; }
.tkx-imp-notes { display: grid; gap: 10px; margin: 18px 0; }
.tkx-imp-notes div { display: flex; gap: 12px; align-items: flex-start; line-height: 1.5; }
.tkx-imp-notes i { width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center; background: var(--surf-2); color: var(--acc); flex: none; }
.tkx-imp-notes b { color: var(--ink); font-weight: 600; }
.tkx-imp-tpl { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; padding-top: 14px; border-top: 1px solid var(--line); color: var(--mute); }
.tkx-imp-file { padding: 11px 14px; margin-bottom: 12px; }
.tkx-imp-file-top { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.tkx-imp-file-top > i { font-size: 22px; color: var(--bs-success); }
.tkx-imp-file .nm { min-width: 0; }
.tkx-imp-file .nm b { overflow-wrap: anywhere; }
.tkx-imp-src { display: flex; flex-wrap: wrap; gap: 12px 20px; align-items: flex-end; margin-top: 11px; padding-top: 11px; border-top: 1px solid var(--line); }
.tkx-imp-src .grp { min-width: 0; max-width: 100%; }
.tkx-sheet-tabs { display: flex; gap: 4px; flex-wrap: wrap; background: var(--surf-2); border: 1px solid var(--line); padding: 3px; border-radius: 10px; }
.tkx-sheet-tabs button { border: 0; background: transparent; padding: 5px 11px; border-radius: 7px; cursor: pointer; color: var(--mute); font-weight: 500; display: inline-flex; align-items: center; gap: 6px; max-width: 260px; }
.tkx-sheet-tabs button .ct { font-size: 10.5px; opacity: .75; }
.tkx-sheet-tabs button.on { background: var(--surf); color: var(--ink); box-shadow: 0 1px 3px rgba(0, 0, 0, .15); }
.tkx-imp-warn { display: flex; gap: 10px; align-items: center; margin-bottom: 12px; color: var(--bs-warning-text-emphasis, var(--bs-warning)); border-color: color-mix(in srgb, var(--bs-warning) 40%, transparent); }
.tkx-imp-file b { display: block; color: var(--ink); font-weight: 600; }
.tkx-imp-file small { color: var(--mute); }
.tkx-imp-file .warn { color: var(--bs-warning); font-size: 12px; }
.tkx-imp-file .tkx-btn { margin-inline-start: auto; }
.tkx-map-grid { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr); gap: 12px; align-items: start; }
.tkx-map-grid > * { min-width: 0; }
.tkx-map-side { display: grid; grid-template-columns: minmax(0, 1fr); gap: 12px; min-width: 0; }
.tkx-card-h { padding: 13px 16px; border-bottom: 1px solid var(--line); }
.tkx-card-h b { display: block; color: var(--ink); font-weight: 600; }
.tkx-card-h small { color: var(--mute); }
.tkx-map { padding: 6px 16px; }
.tkx-map-row { display: grid; grid-template-columns: minmax(0, 1fr) 24px minmax(0, 1.1fr); gap: 10px; align-items: start; padding: 11px 0; border-bottom: 1px dashed var(--line); }
.tkx-map-row:last-child { border-bottom: 0; }
.tkx-map-row .f b { display: block; color: var(--ink); font-weight: 600; }
.tkx-map-row .f em { font-style: normal; font-size: 10px; font-weight: 600; color: var(--bs-danger); background: color-mix(in srgb, var(--bs-danger) 10%, transparent); padding: 1px 6px; border-radius: 10px; margin-inline-start: 4px; }
.tkx-map-row .f small { color: var(--mute); font-size: 11.5px; }
.tkx-map-row .arr { color: var(--mute); margin-top: 10px; text-align: center; }
.tkx-map-row.missing .tkx-inp { border-color: var(--bs-danger); }
.tkx-map-row .sample, .tkx-map-row .dup { display: block; margin-top: 5px; font-size: 11.5px; color: var(--mute); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tkx-map-row .auto { color: var(--bs-success); font-weight: 500; margin-inline-end: 4px; }
.tkx-map-row .dup { color: var(--bs-warning); }
.tkx-cols-list { list-style: none; margin: 0; padding: 6px 0; max-height: 360px; overflow-y: auto; }
.tkx-cols-list li { padding: 8px 16px; }
.tkx-cols-list li + li { border-top: 1px solid var(--line); }
.tkx-cols-list .t { display: flex; justify-content: space-between; gap: 8px; min-width: 0; }
.tkx-cols-list .t b { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tkx-cols-list b { color: var(--ink); font-weight: 500; }
.tkx-cols-list .to { font-size: 11px; color: var(--mute); white-space: nowrap; }
.tkx-cols-list li.used .to { color: var(--acc); font-weight: 600; }
.tkx-cols-list small { color: var(--mute); font-size: 11.5px; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tkx-seg { display: flex; flex-wrap: wrap; gap: 4px; background: var(--surf-2); border: 1px solid var(--line); padding: 3px; border-radius: 10px; }
.tkx-seg button { flex: 1 1 auto; border: 0; background: transparent; padding: 6px 10px; border-radius: 7px; cursor: pointer; color: var(--mute); font-weight: 500; display: inline-flex; align-items: center; justify-content: center; gap: 6px; white-space: nowrap; font-size: 12.5px; }
.tkx-seg button.on { background: var(--surf); color: var(--ink); box-shadow: 0 1px 3px rgba(0, 0, 0, .15); }
.tkx-imp-foot { display: flex; align-items: center; gap: 12px; margin-top: 14px; flex-wrap: wrap; }
.tkx-imp-foot .msg { flex: 1; color: var(--mute); font-size: 12px; text-align: end; }
.tkx-sum { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; margin-bottom: 12px; }
.tkx-sum-i { --c: var(--acc); display: flex; align-items: center; gap: 12px; padding: 12px 14px; cursor: pointer; text-align: start; color: inherit; border-inline-start: 4px solid var(--c); }
.tkx-sum-i.ready { --c: var(--bs-success); }
.tkx-sum-i.skip { --c: var(--bs-secondary); }
.tkx-sum-i.error { --c: var(--bs-danger); }
.tkx-sum-i .ic { width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; color: var(--c); background: color-mix(in srgb, var(--c) 13%, transparent); }
.tkx-sum-i b { display: block; font-size: 20px; color: var(--ink); font-weight: 600; line-height: 1.1; }
.tkx-sum-i small { color: var(--mute); }
.tkx-sum-i.on { box-shadow: 0 0 0 2px var(--c); }
.tkx-rev { overflow: hidden; }
.tkx-rev-scroll { overflow-x: auto; }
.tkx-rev table { width: 100%; border-collapse: collapse; min-width: 860px; }
.tkx-rev th { text-align: start; font-size: 10.5px; text-transform: uppercase; letter-spacing: .8px; color: var(--mute); font-weight: 600; padding: 10px 12px; background: var(--surf-2); border-bottom: 1px solid var(--line); }
.tkx-rev td { padding: 9px 12px; border-bottom: 1px solid var(--line); vertical-align: top; }
.tkx-rev tr.error td { background: color-mix(in srgb, var(--bs-danger) 4%, transparent); }
.tkx-rev .ln { color: var(--mute); font-variant-numeric: tabular-nums; }
.tkx-rev .ti { max-width: 320px; }
.tkx-rev .ti b { display: block; color: var(--ink); font-weight: 500; }
.tkx-rev .ti small { display: block; color: var(--mute); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tkx-rev .mute { color: var(--mute); }
.tkx-rev .is { color: var(--mute); font-size: 12px; max-width: 260px; }
.tkx-rev tr.error .is { color: var(--bs-danger); }
.tkx-rev .st { display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 600; padding: 2px 9px; border-radius: 20px; }
.tkx-rev .st.ready { color: var(--bs-success); background: color-mix(in srgb, var(--bs-success) 12%, transparent); }
.tkx-rev .st.skip { color: var(--mute); background: var(--surf-2); }
.tkx-rev .st.error { color: var(--bs-danger); background: color-mix(in srgb, var(--bs-danger) 12%, transparent); }
.tkx-st { display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; }
.tkx-pager { display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding: 10px 12px; color: var(--mute); }
.tkx-done { text-align: center; padding: 40px 20px; }
.tkx-done .ic { width: 64px; height: 64px; border-radius: 50%; display: inline-grid; place-items: center; font-size: 28px; color: var(--bs-success); background: color-mix(in srgb, var(--bs-success) 14%, transparent); }
.tkx-done h3 { margin: 14px 0 4px; color: var(--ink); font-weight: 600; font-size: 20px; }
.tkx-done p { color: var(--mute); }
.tkx-done .a { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin-top: 18px; }

@media (max-width: 991px) {
    .tkx-map-grid { grid-template-columns: minmax(0, 1fr); }
    .tkx-sum { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 767px) {
    .tkx-imp { padding: 10px 10px 20px; }
    .tkx-steps li small, .tkx-steps li div { display: none; }
    .tkx-steps li { justify-content: center; }
    .tkx-map-row { grid-template-columns: minmax(0, 1fr); }
    .tkx-map-row .arr { display: none; }
    .tkx-imp-foot .msg { order: 3; width: 100%; text-align: start; }
}
</style>
