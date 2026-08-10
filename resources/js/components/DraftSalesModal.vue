<template>
    <div v-if="show" class="posx-modal-backdrop" aria-labelledby="modal-title" role="dialog" aria-modal="true"
        @click.self="close">
        <div class="posx-modal" style="max-width: 52rem" @click.stop>

            <div class="posx-modal-head">
                <h4 class="posx-modal-title">
                    <i class="fa fa-file-text-o"></i>
                    <span>
                        Draft Sales
                        <span class="posx-modal-sub">Pick up an unfinished ticket</span>
                    </span>
                </h4>
                <button type="button" class="posx-modal-close" @click="close" aria-label="Close">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <div class="posx-modal-body">
                <!-- Loading -->
                <div v-if="loading" class="text-center py-10">
                    <div class="posx-spinner animate-spin h-9 w-9 mx-auto mb-3"></div>
                    <p class="posx-muted text-xs font-semibold">Loading drafts...</p>
                </div>

                <!-- Error -->
                <div v-else-if="error" class="text-center py-10">
                    <div class="posx-note is-danger inline-block mb-3">
                        <i class="fa fa-exclamation-triangle mr-1.5"></i>{{ error }}
                    </div>
                    <div>
                        <button type="button" class="posx-btn posx-btn-ghost" @click="fetchDraftSales">
                            <i class="fa fa-refresh"></i> Retry
                        </button>
                    </div>
                </div>

                <!-- Empty -->
                <div v-else-if="draftSales.length === 0" class="text-center py-10">
                    <div class="posx-empty-ring mx-auto mb-3"><i class="fa fa-file-text-o"></i></div>
                    <h3 class="posx-ink text-sm font-extrabold mb-1">No Draft Sales</h3>
                    <p class="posx-muted text-xs font-semibold">No drafts found at the moment.</p>
                </div>

                <!-- List -->
                <div v-else>
                    <div class="flex flex-col sm:flex-row gap-2 mb-3">
                        <div class="relative flex-1">
                            <i class="fa fa-search posx-field-ico"></i>
                            <input v-model="searchQuery" @input="filterDrafts" type="text"
                                class="posx-field posx-field-icon"
                                placeholder="Search by customer, mobile, or ID...">
                        </div>
                        <button type="button" class="posx-btn posx-btn-ghost" @click="fetchDraftSales">
                            <i class="fa fa-refresh"></i> Refresh
                        </button>
                    </div>

                    <div class="posx-panel posx-panel-flush overflow-x-auto">
                        <table class="posx-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th class="num">Items</th>
                                    <th class="num">Amount</th>
                                    <th class="num">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="draft in filteredDrafts" :key="draft.id">
                                    <td><span class="posx-chip-neutral">#{{ draft.id }}</span></td>
                                    <td class="posx-muted whitespace-nowrap">{{ formatDate(draft.date) }}</td>
                                    <td>
                                        <div class="posx-ink text-xs font-bold truncate">
                                            {{ draft.customer_name || 'General Customer' }}
                                        </div>
                                        <div v-if="draft.customer_mobile" class="posx-muted text-xs truncate">
                                            {{ draft.customer_mobile }}
                                        </div>
                                    </td>
                                    <td class="num"><span class="posx-chip-neutral">{{ draft.items_count || 0 }}</span></td>
                                    <td class="num"><span class="posx-amount">{{ formatNumber(draft.grand_total) }}</span></td>
                                    <td class="num">
                                        <button type="button" class="posx-btn posx-btn-primary ms-auto"
                                            style="min-height:28px;padding:0 11px;font-size:.6875rem"
                                            @click="loadDraft(draft)">
                                            <i class="fa fa-pencil"></i> Load
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <p v-if="filteredDrafts.length > 0" class="posx-muted text-xs font-semibold text-center mt-3 mb-0">
                        Showing {{ filteredDrafts.length }} of {{ draftSales.length }} drafts
                    </p>
                </div>
            </div>

            <div class="posx-modal-foot">
                <button type="button" class="posx-btn posx-btn-ghost" @click="close">
                    <i class="fa fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { computed, ref, watch } from 'vue'
import { useToast } from 'vue-toastification'

export default {
    props: {
        show: {
            type: Boolean,
            default: false
        }
    },

    emits: ['close', 'draft-loaded'],

    setup(props, { emit }) {
        const toast = useToast()

        // Reactive data
        const loading = ref(false)
        const error = ref(null)
        const draftSales = ref([])
        const searchQuery = ref('')

        // Computed
        const filteredDrafts = computed(() => {
            if (!searchQuery.value) return draftSales.value

            const query = searchQuery.value.toLowerCase()
            return draftSales.value.filter(draft => {
                return (
                    draft.id.toString().includes(query) ||
                    (draft.customer_name && draft.customer_name.toLowerCase().includes(query)) ||
                    (draft.customer_mobile && draft.customer_mobile.includes(query)) ||
                    (draft.employee_name && draft.employee_name.toLowerCase().includes(query))
                )
            })
        })

        // Methods
        const fetchDraftSales = async () => {
            loading.value = true
            error.value = null

            try {
                const response = await axios.get('/pos/drafts', {
                    headers: { 'Cache-Control': 'no-cache' }
                })

                if (response.data && Array.isArray(response.data)) {
                    draftSales.value = response.data
                } else {
                    draftSales.value = response.data.data || []
                }
            } catch (err) {
                error.value = err.response?.data?.message || 'Failed to load draft sales'
                toast.error(error.value)
            } finally {
                loading.value = false
            }
        }

        const filterDrafts = () => {
            // Filtering is handled by computed property
        }

        const loadDraft = (draft) => {
            // Emit event to parent to load the draft
            emit('draft-loaded', draft)
            close()
        }
        const formatDate = (dateString) => {
            if (!dateString) return 'N/A'

            try {
                const date = new Date(dateString)
                return date.toLocaleDateString('en-IN', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                })
            } catch {
                return dateString
            }
        }

        const formatNumber = (value) => {
            const num = parseFloat(value) || 0
            return num.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })
        }

        const close = () => {
            searchQuery.value = ''
            emit('close')
        }

        // Watch for modal open to fetch data
        watch(() => props.show, (newShow) => {
            if (newShow) {
                fetchDraftSales()
            }
        })

        return {
            loading,
            error,
            draftSales,
            searchQuery,
            filteredDrafts,
            fetchDraftSales,
            filterDrafts,
            loadDraft,
            formatDate,
            formatNumber,
            close
        }
    }
}
</script>

<style scoped>
/* Enhanced animations and compatibility */
.modal-enter-active,
.modal-leave-active {
    transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
    transform: scale(0.95);
}

/* Table hover effects */
tbody tr:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Button hover effects */
button:hover {
    transform: translateY(-1px);
}

/* Responsive table */
@media (max-width: 640px) {
    table {
        font-size: 0.75rem;
    }

    .max-w-32 {
        max-width: 6rem;
    }

    td,
    th {
        padding: 0.5rem 0.25rem;
    }
}

/* Custom scrollbar for webkit browsers */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Loading animation enhancement */
@keyframes pulse {

    0%,
    100% {
        opacity: 1;
    }

    50% {
        opacity: 0.5;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
