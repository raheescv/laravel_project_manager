<template>
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form @submit.prevent="handleSubmit">
                <div class="row g-3">
                    <div class="col-md-7">
                        <label for="branch_id" class="form-label fw-semibold text-dark">
                            Branch <span class="text-danger">*</span>
                        </label>
                        <select id="branch_id" v-model="formData.branch_id" class="form-select form-select-lg" required>
                            <option value="">Select Branch</option>
                            <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                                {{ branch.name }}
                            </option>
                        </select>
                    </div>

                    <div class="col-md-5">
                        <label for="date" class="form-label fw-semibold text-dark">
                            Date <span class="text-danger">*</span>
                        </label>
                        <input type="date" id="date" v-model="formData.date" class="form-control form-control-lg" required />
                    </div>

                    <div class="col-md-12">
                        <label for="title" class="form-label fw-semibold text-dark">
                            Title <span class="text-danger">*</span>
                        </label>
                        <input ref="titleInput" type="text" id="title" v-model="formData.title" class="form-control form-control-lg" required maxlength="255" placeholder="Enter stock check title" />
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label fw-semibold text-dark">Description</label>
                        <textarea id="description" v-model="formData.description" class="form-control form-control-lg" rows="4" placeholder="Enter description (optional)"></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <button type="button" class="btn btn-outline-secondary btn-lg px-4" @click="$emit('cancel')">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-lg px-4" :disabled="loading">
                        <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                        <span v-if="!loading">{{ isEditMode ? 'Update' : 'Create' }}</span>
                        <span v-if="loading">{{ isEditMode ? 'Updating...' : 'Creating...' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, nextTick, watch, computed } from 'vue'
import { useToast } from 'vue-toastification'
import axios from 'axios'
import CreateStockCheckAction from '../Apis/CreateStockCheckAction.js'
import UpdateStockCheckMetadataAction from '../Apis/UpdateStockCheckMetadataAction.js'
import GetStockCheckAction from '../Apis/GetStockCheckAction.js'

const toast = useToast()
const createAction = new CreateStockCheckAction()
const updateAction = new UpdateStockCheckMetadataAction()
const getStockCheckAction = new GetStockCheckAction()

const formData = ref({
    branch_id: '',
    date: new Date().toISOString().split('T')[0],
    title: '',
    description: ''
})

const branches = ref([])
const loading = ref(false)
const titleInput = ref(null)

const props = defineProps({
    branchId: {
        type: Number,
        default: null
    },
    stockCheckId: {
        type: Number,
        default: null
    }
})

const emit = defineEmits(['submit', 'cancel'])

const isEditMode = computed(() => props.stockCheckId !== null)

const fetchBranches = async () => {
    try {
        // Use settings branch list endpoint
        const response = await axios.get('/settings/branch/list')
        branches.value = response.data?.items || []
        return branches.value
    } catch (error) {
        console.error('Failed to fetch branches:', error)
        return []
    }
}

const loadStockCheckData = async () => {
    if (!props.stockCheckId) return

    loading.value = true
    try {
        const result = await getStockCheckAction.execute(props.stockCheckId)
        if (result.success && result.data) {
            formData.value = {
                branch_id: result.data.branch_id || '',
                date: result.data.date ? result.data.date.split('T')[0] : new Date().toISOString().split('T')[0],
                title: result.data.title || '',
                description: result.data.description || ''
            }
        } else {
            toast.error(result.message || 'Failed to load stock check')
        }
    } catch (error) {
        toast.error('Failed to load stock check')
        console.error(error)
    } finally {
        loading.value = false
    }
}

const handleSubmit = async () => {
    if (!formData.value.branch_id || !formData.value.date || !formData.value.title) {
        toast.error('Please fill in all required fields')
        return
    }

    loading.value = true
    try {
        let result
        if (isEditMode.value) {
            result = await updateAction.execute(props.stockCheckId, formData.value)
        } else {
            result = await createAction.execute(formData.value)
        }

        if (result.success) {
            emit('submit', result.data)
        } else {
            toast.error(result.message || `Failed to ${isEditMode.value ? 'update' : 'create'} stock check`)
        }
    } catch (error) {
        toast.error(error.response?.data?.message || `Failed to ${isEditMode.value ? 'update' : 'create'} stock check`)
        console.error(error)
    } finally {
        loading.value = false
    }
}

onMounted(async () => {
    // Fetch branches first
    await fetchBranches()

    // Load stock check data if in edit mode
    if (props.stockCheckId) {
        await loadStockCheckData()
    } else {
        // Set default branch from prop (session branch_id from controller) only in create mode
        if (props.branchId) {
            const branchId = parseInt(props.branchId)
            // Verify the branch exists in the branches list
            const branchExists = branches.value.some(branch => branch.id === branchId)
            if (branchExists) {
                formData.value.branch_id = branchId
            } else if (branches.value.length > 0) {
                // If session branch not found, use first available branch as fallback
                formData.value.branch_id = branches.value[0].id
            }
        }
    }

    // Focus title input after modal is fully rendered
    await nextTick()
    if (titleInput.value) {
        titleInput.value.focus()
    }
})

// Watch for stockCheckId changes (in case it's set after mount)
watch(() => props.stockCheckId, async (newId) => {
    if (newId) {
        await loadStockCheckData()
    }
})
</script>
