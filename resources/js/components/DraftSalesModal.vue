<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <!-- Background overlay -->
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="close">
            </div>

            <!-- Modal positioning -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal panel -->
            <div
                class="relative inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full mx-4">
                <!-- Header -->
                <div class="bg-gradient-to-r from-slate-600 to-slate-700 px-4 py-3 text-white">
                    <div class="flex items-center justify-between">
                        <h4 class="text-lg font-semibold text-white mb-0 flex items-center">
                            <i class="fa fa-file-alt mr-2 text-blue-200"></i>
                            Draft Sales
                        </h4>
                        <button type="button" @click="close"
                            class="text-white hover:text-gray-200 focus:outline-none transition-colors">
                            <i class="fa fa-times text-lg"></i>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="px-3 py-3">
                    <!-- Loading state -->
                    <div v-if="loading" class="flex items-center justify-center py-6">
                        <div class="text-center">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-slate-600 mx-auto mb-3">
                            </div>
                            <p class="text-slate-600 text-sm">Loading drafts...</p>
                        </div>
                    </div>

                    <!-- Error state -->
                    <div v-else-if="error" class="text-center py-6">
                        <div class="text-red-500 mb-3">
                            <i class="fa fa-exclamation-triangle text-2xl"></i>
                        </div>
                        <p class="text-red-600 mb-3 text-sm">{{ error }}</p>
                        <button @click="fetchDraftSales"
                            class="bg-slate-600 hover:bg-slate-700 text-white px-3 py-1.5 rounded-lg transition-colors text-sm">
                            <i class="fa fa-refresh mr-1"></i>
                            Retry
                        </button>
                    </div>

                    <!-- No drafts state -->
                    <div v-else-if="draftSales.length === 0" class="text-center py-6">
                        <div class="text-slate-400 mb-3">
                            <i class="fa fa-file-alt text-3xl"></i>
                        </div>
                        <h3 class="text-base font-semibold text-slate-600 mb-1">No Draft Sales</h3>
                        <p class="text-slate-500 text-sm">No drafts found at the moment.</p>
                    </div>

                    <!-- Draft sales list -->
                    <div v-else>
                        <!-- Search and filters -->
                        <div class="mb-3 flex flex-col sm:flex-row gap-2">
                            <div class="flex-1">
                                <div class="relative">
                                    <input v-model="searchQuery" @input="filterDrafts" type="text"
                                        class="w-full pl-8 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-slate-500 transition-all"
                                        placeholder="Search by customer, mobile, or ID...">
                                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                                        <i class="fa fa-search text-gray-400 text-xs"></i>
                                    </div>
                                </div>
                            </div>
                            <button @click="fetchDraftSales"
                                class="bg-slate-600 hover:bg-slate-700 text-white px-3 py-2 rounded-lg transition-colors flex items-center text-sm">
                                <i class="fa fa-refresh mr-1 text-xs"></i>
                                Refresh
                            </button>
                        </div>

                        <!-- Draft sales table -->
                        <div class="overflow-x-auto bg-white rounded-lg border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                                    <tr>
                                        <th scope="col"
                                            class="px-2 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            ID
                                        </th>
                                        <th scope="col"
                                            class="px-2 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Date
                                        </th>
                                        <th scope="col"
                                            class="px-2 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Customer
                                        </th>
                                        <th scope="col"
                                            class="px-2 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Items
                                        </th>
                                        <th scope="col"
                                            class="px-2 py-2 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Amount
                                        </th>
                                        <th scope="col"
                                            class="px-2 py-2 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Action
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    <tr v-for="draft in filteredDrafts" :key="draft.id"
                                        class="hover:bg-blue-50 transition-colors duration-200">
                                        <td class="px-2 py-3 whitespace-nowrap text-xs font-medium text-slate-700">
                                            <span
                                                class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-semibold">#{{
                                                    draft.id }}</span>
                                        </td>
                                        <td class="px-2 py-3 whitespace-nowrap text-xs text-gray-600">
                                            {{ formatDate(draft.date) }}
                                        </td>
                                        <td class="px-2 py-3 text-xs text-gray-900">
                                            <div class="max-w-32">
                                                <div class="font-medium truncate">
                                                    {{ draft.customer_name || 'General Customer' }}</div>
                                                <div class="text-gray-500 text-xs truncate"
                                                    v-if="draft.customer_mobile">{{ draft.customer_mobile }}</div>
                                            </div>
                                        </td>
                                        <td class="px-2 py-3 whitespace-nowrap text-xs text-center">
                                            <span
                                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                {{ draft.items_count || 0 }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-2 py-3 whitespace-nowrap text-xs font-bold text-right text-slate-900">
                                            <div
                                                class="bg-gradient-to-r from-emerald-50 to-green-50 px-2 py-1 rounded-lg border border-green-200">
                                                ₹{{ parseFloat(draft.grand_total || 0).toFixed(2) }}
                                            </div>
                                        </td>
                                        <td class="px-2 py-3 whitespace-nowrap text-center">
                                            <button @click="loadDraft(draft)"
                                                class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200 transform hover:scale-105 shadow-sm hover:shadow-md">
                                                <i class="fa fa-edit mr-1"></i>
                                                Load
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination info -->
                        <div class="mt-3 text-xs text-gray-500 text-center bg-gray-50 py-2 rounded-lg"
                            v-if="filteredDrafts.length > 0">
                            <i class="fa fa-info-circle mr-1"></i>
                            Showing {{ filteredDrafts.length }} of {{ draftSales.length }} drafts
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="bg-gradient-to-r from-gray-50 to-gray-100 px-3 py-2 sm:flex sm:flex-row-reverse border-t border-gray-200">
                    <button type="button" @click="close"
                        class="w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 sm:mt-0 sm:w-auto transition-all duration-200">
                        <i class="fa fa-times mr-2"></i>
                        Close
                    </button>
                </div>
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
