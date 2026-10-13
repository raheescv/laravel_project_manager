<template>
    <div class="pa-card-body">
        <form v-if="can.note" class="d-flex gap-3 mb-2" @submit.prevent="add">
            <span class="pa-avatar round">{{ initials('Me') }}</span>
            <div class="flex-grow-1">
                <label class="visually-hidden" for="pc-note">Note</label>
                <textarea id="pc-note" v-model="draft" class="form-control mb-2" rows="2" placeholder="Write a note for the team"></textarea>
                <div class="text-end"><button class="btn btn-primary btn-sm" type="submit" :disabled="!draft.trim() || saving">Add note</button></div>
            </div>
        </form>
        <div v-if="!contract.notes.length" class="pa-empty py-3"><i class="fa fa-comment-o"></i>No notes yet.</div>
        <div v-for="note in contract.notes" :key="note.id" class="pa-note">
            <span class="pa-avatar round">{{ initials(note.created_by) }}</span>
            <div class="body">
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-semibold">{{ note.created_by }}</span>
                    <span class="pa-sub">{{ dateTime(note.created_at) }}</span>
                    <span v-if="note.mine && can.note && editing !== note.id" class="ms-auto">
                        <button class="btn-icon" title="Edit note" @click="startEdit(note)"><i class="fa fa-pencil"></i></button>
                        <button class="btn-icon danger" title="Delete note" @click="remove(note)"><i class="fa fa-trash-o"></i></button>
                    </span>
                </div>
                <form v-if="editing === note.id" class="mt-1" @submit.prevent="saveEdit(note)">
                    <textarea v-model="editText" class="form-control mb-2" rows="2" aria-label="Edit note"></textarea>
                    <div class="d-flex gap-2 justify-content-end"><button type="button" class="btn btn-quiet btn-sm" @click="editing = null">Cancel</button><button type="submit" class="btn btn-primary btn-sm" :disabled="!editText.trim()">Save</button></div>
                </form>
                <div v-else class="mt-1">{{ note.note }}</div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { contractApi } from '../api.js'
import { confirmAction } from '../common/useContractContext.js'
import { dateTime, initials } from '../format.js'

const props = defineProps({ contract: { type: Object, required: true }, can: { type: Object, required: true } })
const emit = defineEmits(['run'])

const draft = ref('')
const saving = ref(false)
const editing = ref(null)
const editText = ref('')

async function add() {
    saving.value = true
    await new Promise((resolve) => emit('run', contractApi.addNote(props.contract.id, draft.value).then((response) => { draft.value = ''; return response }).finally(resolve)))
    saving.value = false
}

function startEdit(note) {
    editing.value = note.id
    editText.value = note.note
}

function saveEdit(note) {
    emit('run', contractApi.updateNote(props.contract.id, note.id, editText.value))
    editing.value = null
}

async function remove(note) {
    const ok = await confirmAction({ title: 'Delete this note?', confirm: 'Delete', danger: true })
    if (ok) emit('run', contractApi.deleteNote(props.contract.id, note.id))
}
</script>
