<template>
    <div class="pa-card-body d-grid gap-3">
        <div
            v-if="can.document"
            class="pa-drop"
            :class="{ over: dragging }"
            @dragover.prevent="dragging = true"
            @dragleave.prevent="dragging = false"
            @drop.prevent="onDrop"
        >
            <i class="fa fa-cloud-upload fa-2x d-block mb-2" style="color: var(--pa-accent)"></i>
            <div class="fw-semibold" style="color: var(--pa-ink)">{{ uploading ? 'Uploading…' : 'Drop files to upload' }}</div>
            <div class="pa-sub mb-2">or <button type="button" class="paf-link" @click="picker.click()">browse</button> · PDF, images, Word or Excel up to 10 MB</div>
            <select v-model="documentType" class="form-select form-select-sm d-inline-block w-auto" aria-label="Document type">
                <option value="">Document type</option>
                <option v-for="type in options.document_types" :key="type" :value="type">{{ type }}</option>
            </select>
            <input ref="picker" type="file" class="d-none" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.csv" @change="onPick" />
        </div>

        <div v-if="!contract.documents.length" class="pa-empty py-3"><i class="fa fa-paperclip"></i>No documents yet.</div>
        <div v-else class="pa-files">
            <div v-for="document in contract.documents" :key="document.id" class="pa-file">
                <span class="fi" :class="kind(document)"><i class="fa" :class="icon(document)"></i></span>
                <div class="flex-grow-1" style="min-width: 0">
                    <div class="name" :title="document.name">{{ document.name }}</div>
                    <div class="pa-sub">{{ [document.document_type, fileSize(document.size), date(document.created_at)].filter(Boolean).join(' · ') }}</div>
                </div>
                <a class="btn-icon" :href="document.url" target="_blank" rel="noopener" title="Open"><i class="fa fa-eye"></i></a>
                <a class="btn-icon" :href="document.url" :download="document.name" title="Download"><i class="fa fa-download"></i></a>
                <button v-if="can.document" class="btn-icon danger" title="Delete" @click="remove(document)"><i class="fa fa-trash-o"></i></button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { contractApi, formData } from '../api.js'
import { confirmAction, useOptions } from '../common/useContractContext.js'
import { date, fileSize } from '../format.js'

const props = defineProps({ contract: { type: Object, required: true }, can: { type: Object, required: true } })
const emit = defineEmits(['run'])
const options = useOptions()

const picker = ref(null)
const dragging = ref(false)
const uploading = ref(false)
const documentType = ref('')

function kind(document) {
    const name = document.name.toLowerCase()
    if (name.endsWith('.pdf')) return 'pdf'
    if (/\.(xlsx?|csv)$/.test(name)) return 'xls'
    return ''
}

function icon(document) {
    return { pdf: 'fa-file-pdf-o', xls: 'fa-file-excel-o' }[kind(document)] ?? (document.mime_type?.startsWith('image/') ? 'fa-file-image-o' : 'fa-file-text-o')
}

async function upload(files) {
    if (!files.length || uploading.value) return
    uploading.value = true
    await new Promise((resolve) => emit('run', contractApi.uploadDocuments(props.contract.id, formData({ document_type: documentType.value }, { files })).finally(resolve)))
    uploading.value = false
    if (picker.value) picker.value.value = ''
}

function onPick(event) {
    upload([...event.target.files])
}

function onDrop(event) {
    dragging.value = false
    upload([...event.dataTransfer.files])
}

async function remove(document) {
    const ok = await confirmAction({ title: `Delete ${document.name}?`, text: 'It is removed from this contract.', confirm: 'Delete', danger: true })
    if (ok) emit('run', contractApi.deleteDocument(props.contract.id, document.id))
}
</script>
