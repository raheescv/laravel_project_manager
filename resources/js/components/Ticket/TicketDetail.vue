<template>
    <div class="tkx-ovl" @mousedown.self="close">
        <div class="tkx-mdl" role="dialog" aria-modal="true">
            <header class="tkx-mdl-h">
                <span class="id">{{ ticket ? `#${ticket.id}` : 'New ticket' }}</span>
                <div class="tkx-stpick" role="radiogroup" aria-label="Status">
                    <button v-for="s in STATUSES" :key="s.key" type="button" :class="{ on: currentStatus === s.key }"
                        :disabled="!canChangeStatus || busy" @click="pickStatus(s.key)">
                        <span class="tkx-dot" :style="{ '--c': s.color }"></span>{{ s.label }}
                    </button>
                </div>
                <div class="acts">
                    <button v-if="ticket && !editing && permissions.edit" type="button" class="tkx-btn" @click="startEdit"><i class="fa fa-pencil"></i> Edit</button>
                    <button type="button" class="tkx-x" aria-label="Close" @click="close"><i class="fa fa-times"></i></button>
                </div>
            </header>

            <div v-if="loading" class="tkx-mdl-b">
                <div class="tkx-mdl-main"><div class="tkx-skel" style="height: 28px; width: 60%"></div><div class="tkx-skel" style="height: 120px; margin-top: 16px"></div></div>
                <div class="tkx-mdl-side"><div class="tkx-skel" style="height: 80px"></div></div>
            </div>

            <div v-else class="tkx-mdl-b" :class="{ 'is-form': !ticket }">
                <div class="tkx-mdl-main">
                    <!-- ---------- edit / create ---------- -->
                    <form v-if="editing" @submit.prevent="save">
                        <label class="tkx-label" for="tkx-title">Title</label>
                        <input id="tkx-title" ref="titleInput" v-model="form.title" class="tkx-field tkx-title-input" maxlength="255" placeholder="What needs attention?">

                        <label class="tkx-label mt" for="tkx-group">Group</label>
                        <input id="tkx-group" v-model="form.group" class="tkx-field" list="tkx-group-list" maxlength="100" placeholder="e.g. Sales, Inventory — or leave blank">
                        <datalist id="tkx-group-list"><option v-for="g in groups" :key="g" :value="g" /></datalist>
                        <div v-if="groups.length" class="tkx-gsuggest">
                            <button v-for="g in groups.slice(0, 12)" :key="g" type="button" class="tkx-gtag" :class="{ on: form.group === g }"
                                :style="{ '--g': groupColor(g) }" @click="form.group = form.group === g ? '' : g"><span>{{ g }}</span></button>
                        </div>

                        <label class="tkx-label mt" for="tkx-desc">Description</label>
                        <textarea id="tkx-desc" v-model="form.description" class="tkx-field" rows="6" placeholder="Steps, what you expected, what happened…"></textarea>

                        <label class="tkx-label mt">Attachments</label>
                        <FileDrop v-model="pendingFiles" />
                    </form>

                    <!-- ---------- read ---------- -->
                    <template v-else-if="ticket">
                        <h2 class="tkx-mdl-title">{{ ticket.title }}</h2>
                        <div class="tkx-props">
                            <div class="tkx-prop"><small>Group</small>
                                <b><span v-if="ticket.group" class="tkx-gtag" :style="{ '--g': groupColor(ticket.group) }"><span>{{ ticket.group }}</span></span><span v-else class="muted">—</span></b>
                            </div>
                            <div class="tkx-prop"><small>Reported by</small><b><span class="tkx-av">{{ initials(ticket.creator) }}</span>{{ ticket.creator || '—' }}</b></div>
                            <div class="tkx-prop"><small>Created</small><b>{{ formatDate(ticket.created_at, true) }}</b></div>
                        </div>
                        <div class="tkx-sec">Description</div>
                        <div class="tkx-desc">{{ ticket.description || 'No description.' }}</div>
                    </template>

                    <template v-if="ticket">
                        <div class="tkx-sec">Attachments <span class="n">{{ ticket.attachments.length }}</span></div>
                        <div v-if="ticket.attachments.length" class="tkx-files">
                            <div v-for="a in ticket.attachments" :key="a.id" class="tkx-file">
                                <a :href="a.url" target="_blank" rel="noopener" class="thumb">
                                    <img v-if="a.is_image" :src="a.url" :alt="a.name" loading="lazy">
                                    <video v-else-if="a.is_video" :src="a.url" muted preload="metadata"></video>
                                    <i v-else class="fa" :class="fileIcon(a)"></i>
                                </a>
                                <div class="n" :title="a.name">{{ a.name }}</div>
                                <div class="s">{{ formatSize(a.size) }}</div>
                                <button v-if="permissions.edit" type="button" class="rm" title="Remove" @click="removeAttachment(a)"><i class="fa fa-trash-o"></i></button>
                            </div>
                        </div>
                        <div v-else-if="editing || !permissions.edit" class="tkx-empty">No files attached.</div>
                        <FileDrop v-if="!editing && permissions.edit" v-model="quickFiles" compact @update:model-value="uploadQuick" />
                    </template>
                </div>

                <aside v-if="ticket" class="tkx-mdl-side">
                    <div class="tkx-sec first">Comments &amp; activity</div>
                    <form v-if="permissions.comment" class="tkx-compose" @submit.prevent="addComment">
                        <textarea v-model="newComment" rows="2" placeholder="Write a comment…  (Ctrl + Enter to send)" @keydown.ctrl.enter="addComment" @keydown.meta.enter="addComment"></textarea>
                        <div class="r"><button type="submit" class="tkx-btn pri" :disabled="!newComment.trim() || busy"><i class="fa fa-paper-plane"></i> Comment</button></div>
                    </form>
                    <div class="tkx-tl">
                        <div v-for="c in ticket.comments" :key="c.id" class="tkx-tl-i">
                            <span class="tkx-av">{{ initials(c.author) }}</span>
                            <div class="tkx-bub">
                                <div class="who"><b>{{ c.author }}</b><span :title="formatDate(c.created_at, true)">{{ relativeTime(c.created_at) }}<template v-if="c.edited"> · edited</template></span></div>
                                <template v-if="editingComment === c.id">
                                    <textarea v-model="editingText" class="tkx-field" rows="3"></textarea>
                                    <div class="acts"><a @click="saveComment(c)">Save</a><a @click="editingComment = null">Cancel</a></div>
                                </template>
                                <template v-else>
                                    <div class="txt">{{ c.comment }}</div>
                                    <div v-if="permissions.comment" class="acts"><a @click="editComment(c)">Edit</a><a class="del" @click="deleteComment(c)">Delete</a></div>
                                </template>
                            </div>
                        </div>
                        <div v-if="ticket.updated_at && ticket.updated_at !== ticket.created_at" class="tkx-ev"><i class="fa fa-pencil"></i> Last updated {{ relativeTime(ticket.updated_at) }}<template v-if="ticket.updater"> by {{ ticket.updater }}</template></div>
                        <div class="tkx-ev"><i class="fa fa-plus-circle"></i> Created {{ relativeTime(ticket.created_at) }}<template v-if="ticket.creator"> by {{ ticket.creator }}</template></div>
                    </div>
                </aside>
            </div>

            <footer class="tkx-mdl-f">
                <button v-if="ticket && permissions.delete && !editing" type="button" class="tkx-btn danger" :disabled="busy" @click="destroy"><i class="fa fa-trash-o"></i> Delete</button>
                <span class="sp"></span>
                <template v-if="editing">
                    <button type="button" class="tkx-btn" @click="cancelEdit">Cancel</button>
                    <button type="button" class="tkx-btn pri" :disabled="busy || !form.title.trim()" @click="save">
                        <i class="fa" :class="busy ? 'fa-refresh tkx-spin' : 'fa-check'"></i> {{ ticket ? 'Save changes' : 'Create ticket' }}
                    </button>
                </template>
                <button v-else type="button" class="tkx-btn" @click="close">Close</button>
            </footer>
        </div>
    </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { errorMessage, ticketApi, ticketForm } from './api.js'
import FileDrop from './FileDrop.vue'
import { formatDate, formatSize, groupColor, initials, relativeTime, STATUSES } from './ticketMeta.js'

const props = defineProps({
    ticketId: { type: Number, default: null },
    initialStatus: { type: String, default: 'open' },
    groups: { type: Array, default: () => [] },
    permissions: { type: Object, required: true },
})
const emit = defineEmits(['close', 'changed'])

const toast = useToast()
const ticket = ref(null)
const loading = ref(!!props.ticketId)
const editing = ref(!props.ticketId)
const busy = ref(false)
const form = reactive({ title: '', description: '', status: props.initialStatus, group: '' })
const pendingFiles = ref([])
const quickFiles = ref([])
const newComment = ref('')
const editingComment = ref(null)
const editingText = ref('')
const titleInput = ref(null)
let dirty = false

const currentStatus = computed(() => (editing.value ? form.status : ticket.value?.status))
const canChangeStatus = computed(() => (ticket.value ? props.permissions.edit : props.permissions.create))

async function load() {
    try {
        const { data } = await ticketApi.show(props.ticketId)
        ticket.value = data
    } catch (error) {
        toast.error(errorMessage(error))
        emit('close')
    } finally {
        loading.value = false
    }
}

function fillForm() {
    Object.assign(form, {
        title: ticket.value.title,
        description: ticket.value.description,
        status: ticket.value.status,
        group: ticket.value.group ?? '',
    })
}

function startEdit() {
    fillForm()
    pendingFiles.value = []
    editing.value = true
    nextTick(() => titleInput.value?.focus())
}

function cancelEdit() {
    if (!ticket.value) return close()
    editing.value = false
}

/** In read mode a status tap saves straight away; in the form it is just part of the draft. */
async function pickStatus(status) {
    if (editing.value) {
        form.status = status
        return
    }
    if (status === ticket.value.status) return
    busy.value = true
    try {
        const response = await ticketApi.status(ticket.value.id, status)
        ticket.value = response.data
        dirty = true
        toast.success(response.message)
    } catch (error) {
        toast.error(errorMessage(error))
    } finally {
        busy.value = false
    }
}

async function save() {
    if (!form.title.trim() || busy.value) return
    busy.value = true
    try {
        const payload = ticketForm({ ...form }, pendingFiles.value)
        const response = ticket.value ? await ticketApi.update(ticket.value.id, payload) : await ticketApi.store(payload)
        ticket.value = response.data
        pendingFiles.value = []
        editing.value = false
        dirty = true
        toast.success(response.message)
    } catch (error) {
        toast.error(errorMessage(error))
    } finally {
        busy.value = false
    }
}

async function uploadQuick(files) {
    if (!files.length) return
    busy.value = true
    try {
        const t = ticket.value
        const response = await ticketApi.update(t.id, ticketForm({ title: t.title, description: t.description, status: t.status, group: t.group ?? '' }, files))
        ticket.value = response.data
        dirty = true
        toast.success(`${files.length} file${files.length > 1 ? 's' : ''} attached.`)
    } catch (error) {
        toast.error(errorMessage(error))
    } finally {
        quickFiles.value = []
        busy.value = false
    }
}

async function removeAttachment(attachment) {
    if (!confirm(`Remove ${attachment.name}?`)) return
    try {
        const response = await ticketApi.destroyAttachment(ticket.value.id, attachment.id)
        ticket.value.attachments = ticket.value.attachments.filter((a) => a.id !== attachment.id)
        dirty = true
        toast.success(response.message)
    } catch (error) {
        toast.error(errorMessage(error))
    }
}

async function destroy() {
    if (!confirm(`Delete ticket #${ticket.value.id}? Its comments and files go with it.`)) return
    busy.value = true
    try {
        const response = await ticketApi.destroy(ticket.value.id)
        toast.success(response.message)
        dirty = true
        close()
    } catch (error) {
        toast.error(errorMessage(error))
        busy.value = false
    }
}

async function refreshTicket() {
    const { data } = await ticketApi.show(ticket.value.id)
    ticket.value = data
}

async function addComment() {
    const text = newComment.value.trim()
    if (!text || busy.value) return
    busy.value = true
    try {
        const response = await ticketApi.addComment(ticket.value.id, text)
        newComment.value = ''
        await refreshTicket()
        dirty = true
        toast.success(response.message)
    } catch (error) {
        toast.error(errorMessage(error))
    } finally {
        busy.value = false
    }
}

function editComment(comment) {
    editingComment.value = comment.id
    editingText.value = comment.comment
}

async function saveComment(comment) {
    try {
        const response = await ticketApi.updateComment(ticket.value.id, comment.id, editingText.value)
        editingComment.value = null
        await refreshTicket()
        toast.success(response.message)
    } catch (error) {
        toast.error(errorMessage(error))
    }
}

async function deleteComment(comment) {
    if (!confirm('Delete this comment?')) return
    try {
        const response = await ticketApi.deleteComment(ticket.value.id, comment.id)
        ticket.value.comments = ticket.value.comments.filter((c) => c.id !== comment.id)
        dirty = true
        toast.success(response.message)
    } catch (error) {
        toast.error(errorMessage(error))
    }
}

function fileIcon(attachment) {
    if (attachment.mime?.includes('pdf')) return 'fa-file-pdf-o'
    if (/sheet|excel/.test(attachment.mime ?? '')) return 'fa-file-excel-o'
    if (/word|document/.test(attachment.mime ?? '')) return 'fa-file-word-o'
    return 'fa-file-o'
}

function close() {
    if (dirty) emit('changed')
    emit('close')
}

function onKey(event) {
    if (event.key === 'Escape' && !busy.value) close()
}

onMounted(() => {
    document.body.style.overflow = 'hidden'
    window.addEventListener('keydown', onKey)
    if (props.ticketId) load()
    else nextTick(() => titleInput.value?.focus())
})
onBeforeUnmount(() => {
    document.body.style.overflow = ''
    window.removeEventListener('keydown', onKey)
})
</script>

<style>
.tkx-ovl { position: fixed; inset: 0; z-index: 1050; background: rgba(10, 14, 22, .55); backdrop-filter: blur(3px); display: flex; align-items: flex-start; justify-content: center; padding: 4vh 16px; overflow: auto; }
.tkx-mdl { width: min(1180px, 100%); background: var(--surf); border-radius: 18px; border: 1px solid var(--line); overflow: hidden; box-shadow: 0 30px 80px -20px rgba(0, 0, 0, .45); }
.tkx-mdl-h { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; padding: 14px 18px; border-bottom: 1px solid var(--line); background: linear-gradient(180deg, var(--acc-soft), transparent); }
.tkx-mdl-h .id { font-weight: 600; color: var(--ink); font-size: 14px; }
.tkx-mdl-h .acts { margin-inline-start: auto; display: flex; gap: 8px; }
.tkx-x { border: 0; background: var(--surf-2); width: 34px; height: 34px; border-radius: 9px; cursor: pointer; color: var(--mute); }
.tkx-x:hover { color: var(--ink); }
.tkx-stpick { display: inline-flex; flex-wrap: wrap; gap: 3px; background: var(--surf-2); padding: 3px; border-radius: 10px; border: 1px solid var(--line); }
.tkx-stpick button { border: 0; background: transparent; padding: 5px 11px; border-radius: 7px; cursor: pointer; color: var(--mute); display: inline-flex; gap: 6px; align-items: center; font-weight: 500; font-size: 12.5px; }
.tkx-stpick button.on { background: var(--surf); color: var(--ink); box-shadow: 0 1px 3px rgba(0, 0, 0, .15); }
.tkx-stpick button:disabled:not(.on) { cursor: default; opacity: .6; }
.tkx-mdl-b { display: grid; grid-template-columns: minmax(0, 1.45fr) minmax(0, 1fr); }
.tkx-mdl-b.is-form { grid-template-columns: minmax(0, 1fr); }
.tkx-mdl-main { padding: 20px 22px; border-inline-end: 1px solid var(--line); min-width: 0; }
.tkx-mdl-b.is-form .tkx-mdl-main { border-inline-end: 0; }
.tkx-mdl-side { padding: 20px 22px; background: var(--surf-2); min-width: 0; max-height: 72vh; overflow-y: auto; }
.tkx-mdl-title { margin: 0; font-size: 20px; color: var(--ink); font-weight: 600; overflow-wrap: anywhere; }
.tkx-title-input { font-size: 16px; font-weight: 600; }
.tkx-mdl .tkx-label.mt { margin-top: 14px; }
.tkx-gsuggest { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
.tkx-gsuggest .tkx-gtag { border: 1px solid transparent; cursor: pointer; }
.tkx-gsuggest .tkx-gtag.on { border-color: var(--g); }
.tkx-props { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-top: 14px; }
.tkx-prop { border: 1px solid var(--line); border-radius: 11px; padding: 9px 11px; min-width: 0; }
.tkx-prop small { display: block; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: var(--mute); margin-bottom: 4px; font-weight: 600; }
.tkx-prop b { color: var(--ink); font-weight: 500; display: flex; align-items: center; gap: 7px; min-width: 0; }
.tkx-prop .muted { color: var(--mute); }
.tkx-desc { line-height: 1.65; white-space: pre-line; overflow-wrap: anywhere; }
.tkx-sec .n { font-size: 10px; background: var(--surf-2); border-radius: 10px; padding: 0 7px; letter-spacing: 0; }
.tkx-sec.first { margin-top: 0; }
.tkx-files { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; margin-bottom: 10px; }
.tkx-file { position: relative; border: 1px solid var(--line); border-radius: 11px; overflow: hidden; background: var(--surf); }
.tkx-file .thumb { display: grid; place-items: center; height: 84px; background: var(--surf-2); color: var(--mute); font-size: 24px; }
.tkx-file .thumb img, .tkx-file .thumb video { width: 100%; height: 100%; object-fit: cover; }
.tkx-file .n { padding: 6px 9px 0; font-size: 11.5px; color: var(--ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.tkx-file .s { padding: 0 9px 6px; font-size: 10.5px; color: var(--mute); }
.tkx-file .rm { position: absolute; top: 6px; right: 6px; border: 0; width: 26px; height: 26px; border-radius: 7px; background: rgba(0, 0, 0, .55); color: #fff; cursor: pointer; opacity: 0; transition: opacity .15s; }
.tkx-file:hover .rm, .tkx-file .rm:focus { opacity: 1; }
.tkx-compose { background: var(--surf); border: 1px solid var(--line); border-radius: 12px; padding: 10px; }
.tkx-compose:focus-within { border-color: var(--acc); box-shadow: 0 0 0 3px var(--acc-soft); }
.tkx-compose textarea { width: 100%; border: 0; outline: 0; resize: vertical; min-height: 52px; background: transparent; color: var(--ink); font-size: 13px; }
.tkx-compose .r { display: flex; justify-content: flex-end; }
.tkx-tl { margin-top: 14px; display: grid; gap: 12px; }
.tkx-tl-i { display: grid; grid-template-columns: 28px minmax(0, 1fr); gap: 10px; }
.tkx-tl-i .tkx-av { width: 28px; height: 28px; font-size: 10.5px; }
.tkx-bub { background: var(--surf); border: 1px solid var(--line); border-radius: 4px 12px 12px 12px; padding: 9px 12px; min-width: 0; }
.tkx-bub .who { display: flex; justify-content: space-between; gap: 8px; font-size: 11.5px; color: var(--mute); margin-bottom: 3px; }
.tkx-bub .who b { color: var(--ink); font-weight: 600; }
.tkx-bub .txt { white-space: pre-line; overflow-wrap: anywhere; }
.tkx-bub .acts { margin-top: 6px; font-size: 11px; display: flex; gap: 12px; }
.tkx-bub .acts a { color: var(--mute); text-decoration: none; cursor: pointer; }
.tkx-bub .acts a:hover { color: var(--acc); }
.tkx-bub .acts a.del:hover { color: var(--bs-danger); }
.tkx-ev { font-size: 11.5px; color: var(--mute); display: flex; gap: 8px; align-items: center; padding-inline-start: 8px; }
.tkx-mdl-f { display: flex; align-items: center; gap: 8px; padding: 12px 18px; border-top: 1px solid var(--line); }
.tkx-mdl-f .sp { flex: 1; }

@media (max-width: 991px) {
    .tkx-mdl-b { grid-template-columns: minmax(0, 1fr); }
    .tkx-mdl-main { border-inline-end: 0; border-bottom: 1px solid var(--line); }
    .tkx-mdl-side { max-height: none; }
}
@media (max-width: 575px) {
    .tkx-ovl { padding: 0; }
    .tkx-mdl { border-radius: 0; min-height: 100dvh; }
    .tkx-props { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .tkx-mdl-h .acts { margin-inline-start: 0; }
}
</style>
