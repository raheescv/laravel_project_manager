<template>
    <div v-if="show" data-journal-modal class="modal fade show" style="display: block; z-index: 9999; background-color: rgba(0, 0, 0, 0.5);" @click.self="closeModal">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" @click.stop>
            <div class="modal-content shadow-lg">
                <!-- Header -->
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white">
                        <i class="fa fa-book me-2"></i>
                        Journal Entries
                    </h5>
                    <button type="button" class="btn-close btn-close-white" @click="closeModal" aria-label="Close"></button>
                </div>

                <!-- Body -->
                <div class="modal-body">
                    <!-- Journal Info -->
                    <div v-if="journal" class="card mb-3 border-primary">
                        <div class="card-body py-2">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h6 class="mb-1 text-primary">
                                        <i class="fa fa-file-text me-2"></i>
                                        {{ journal.description }}
                                    </h6>
                                    <p v-if="journal.journal_remarks || journal.remarks" class="mb-0 text-muted small">
                                        <i class="fa fa-comment me-1"></i>
                                        {{ journal.journal_remarks || journal.remarks }}
                                    </p>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <span class="badge bg-info text-dark">
                                        <i class="fa fa-calendar me-1"></i>
                                        {{ formatDate(journal.date) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div v-if="loading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3 text-muted">Loading journal entries...</p>
                    </div>

                    <!-- Error State -->
                    <div v-else-if="error" class="alert alert-danger">
                        <i class="fa fa-exclamation-triangle me-2"></i>
                        {{ error }}
                    </div>

                    <!-- Journal Entries Table -->
                    <div v-else-if="entries.length > 0" class="table-responsive">
                        <table class="table table-striped table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 5%;">#</th>
                                    <th style="width: 20%;">Account</th>
                                    <th style="width: 25%;">Description</th>
                                    <th style="width: 15%;">Reference</th>
                                    <th style="width: 11%;" class="text-end">Debit</th>
                                    <th style="width: 12%;" class="text-end">Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(entry, index) in entries" :key="entry.id">
                                    <td>{{ index + 1 }}</td>
                                    <td>
                                        <a v-if="entry.account" :href="`/account/view/${entry.account_id}`" class="text-primary text-decoration-none">
                                            {{ entry.account.name }}
                                        </a>
                                        <span v-else class="text-muted">-</span>
                                    </td>
                                    <td>
                                        <div>{{ entry.description || '-' }}</div>
                                        <small v-if="entry.journal_remarks" class="text-muted d-block mt-1">
                                            {{ entry.journal_remarks }}
                                        </small>
                                    </td>
                                    <td class="text-muted">{{ entry.reference_number || '-' }}</td>
                                    <td class="text-end fw-semibold">
                                        <span v-if="entry.debit > 0" class="text-success">{{ formatCurrency(entry.debit) }}</span>
                                        <span v-else class="text-muted">-</span>
                                    </td>
                                    <td class="text-end fw-semibold">
                                        <span v-if="entry.credit > 0" class="text-danger">{{ formatCurrency(entry.credit) }}</span>
                                        <span v-else class="text-muted">-</span>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Total:</td>
                                    <td class="text-end fw-bold text-success">{{ formatCurrency(totalDebit) }}</td>
                                    <td class="text-end fw-bold text-danger">{{ formatCurrency(totalCredit) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-5">
                        <i class="fa fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No journal entries found</p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeModal">
                        <i class="fa fa-times me-2"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, watch, onMounted } from 'vue'
import axios from 'axios'

export default {
    name: 'JournalEntriesModal',
    props: {
        show: {
            type: Boolean,
            default: false
        },
        journalId: {
            type: Number,
            default: null
        }
    },
    emits: ['close'],
    setup(props, { emit }) {
        const loading = ref(false)
        const error = ref(null)
        const entries = ref([])
        const journal = ref(null)

        const totalDebit = computed(() => {
            return entries.value.reduce((sum, entry) => sum + parseFloat(entry.debit || 0), 0)
        })

        const totalCredit = computed(() => {
            return entries.value.reduce((sum, entry) => sum + parseFloat(entry.credit || 0), 0)
        })

        const formatDate = (date) => {
            if (!date) return '-'
            const d = new Date(date)
            return d.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' })
        }

        const formatCurrency = (amount) => {
            if (!amount || amount === 0) return '-'
            return new Intl.NumberFormat('en-IN', {
                style: 'currency',
                currency: 'INR',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(amount)
        }

        const fetchJournalEntries = async () => {
            if (!props.journalId) {
                entries.value = []
                journal.value = null
                return
            }

            loading.value = true
            error.value = null

            try {
                const response = await axios.get(`/account/journal-entries/${props.journalId}`, {
                    withCredentials: true,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })

                if (response.data.success) {
                    entries.value = response.data.entries || []
                    journal.value = response.data.journal || null
                } else {
                    error.value = response.data.message || 'Failed to load journal entries'
                    entries.value = []
                }
            } catch (err) {
                console.error('Error fetching journal entries:', err)
                error.value = err.response?.data?.message || 'Failed to load journal entries. Please try again.'
                entries.value = []
            } finally {
                loading.value = false
            }
        }

        const closeModal = () => {
            emit('close')
        }

        // Watch for journalId changes and fetch data
        watch(() => props.journalId, (newId) => {
            if (newId && props.show) {
                fetchJournalEntries()
            }
        }, { immediate: true })

        // Watch for show prop changes
        watch(() => props.show, (newValue) => {
            if (newValue && props.journalId) {
                fetchJournalEntries()
            }
        })

        return {
            loading,
            error,
            entries,
            journal,
            totalDebit,
            totalCredit,
            formatDate,
            formatCurrency,
            closeModal
        }
    }
}
</script>

<style scoped>
.modal {
    z-index: 9999;
    backdrop-filter: none;
}

.modal.show {
    backdrop-filter: none;
}

.modal-content {
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.table th {
    font-size: 0.875rem;
    font-weight: 600;
}

.table td {
    font-size: 0.875rem;
    vertical-align: middle;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}
</style>
