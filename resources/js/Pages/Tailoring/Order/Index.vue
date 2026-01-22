<template>
    <div class="card mb-3">
        <div class="card-header bg-white border-bottom-0 py-3">
            <div class="row g-3">
                <!-- Search -->
                <div class="col-md-3">
                    <label class="form-label small text-muted">Search</label>
                    <div class="position-relative">
                        <input type="text" class="form-control" placeholder="Search orders..." v-model="filters.search"
                            @input="debouncedSearch">
                        <span v-if="loading" class="position-absolute top-50 end-0 translate-middle-y me-2">
                            <i class="fa fa-spinner fa-spin text-muted"></i>
                        </span>
                    </div>
                </div>

                <!-- Payment Status -->
                <div class="col-md-2">
                    <label class="form-label small text-muted">Payment</label>
                    <select class="form-select" v-model="filters.payment_status" @change="fetchOrders">
                        <option value="">All</option>
                        <option value="paid">Paid</option>
                        <option value="balance">Balance</option>
                    </select>
                </div>

                <!-- Status -->
                <div class="col-md-2">
                    <label class="form-label small text-muted">Status</label>
                    <select class="form-select" v-model="filters.status" @change="fetchOrders">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <!-- Column Visibility -->
                <div class="col-md-2 ms-auto d-flex align-items-end justify-content-end">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle w-100" type="button"
                            data-bs-toggle="dropdown">
                            <i class="fa fa-columns me-1"></i> Columns
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end p-2">
                            <li v-for="col in columns" :key="col.name" class="dropdown-item p-0 mb-1">
                                <label class="d-flex align-items-center w-100 px-2 py-1 cursor-pointer">
                                    <input type="checkbox" class="form-check-input me-2" v-model="col.visible">
                                    {{ col.label }}
                                </label>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="col-md-3 d-flex align-items-end justify-content-end gap-2">
                    <a href="/tailoring/order" class="quick-action-link primary flex-grow-1">
                        <i class="fa fa-list"></i>
                        <span>Orders</span>
                    </a>
                    <a href="/tailoring/job-completion" class="quick-action-link success flex-grow-1">
                        <i class="fa fa-check-circle"></i>
                        <span>Job Completion</span>
                    </a>
                    <a :href="route('tailoring::order::create')" class="btn btn-primary px-3">
                        <i class="fa fa-plus me-1"></i> New
                    </a>
                </div>
            </div>

            <!-- Advanced Filters Row -->
            <div class="row g-3 mt-1">
                <!-- Date Type -->
                <div class="col-md-2">
                    <label class="form-label small text-muted">Date Type</label>
                    <select class="form-select" v-model="filters.date_type" @change="fetchOrders">
                        <option value="created_at">Order Date</option>
                        <option value="delivery_date">Delivery Date</option>
                    </select>
                </div>

                <!-- Date Range -->
                <div class="col-md-2">
                    <label class="form-label small text-muted">From</label>
                    <input type="date" class="form-control" v-model="filters.from_date" @change="fetchOrders">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">To</label>
                    <input type="date" class="form-control" v-model="filters.to_date" @change="fetchOrders">
                </div>

                <!-- Clear Filters -->
                <div class="col-md-2 d-flex align-items-end">
                    <button v-if="hasActiveFilters" @click="clearFilters"
                        class="btn btn-link text-decoration-none text-danger p-0 mb-2">
                        <i class="fa fa-times me-1"></i> Clear Filters
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th v-if="isColVisible('details')">Order Details</th>
                            <th v-if="isColVisible('customer')">Customer</th>
                            <th v-if="isColVisible('status')" class="text-center">Status</th>
                            <th v-if="isColVisible('amount')" class="text-end">Total Amount</th>
                            <th v-if="isColVisible('actions')" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        <tr v-if="loading">
                            <td colspan="10" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </td>
                        </tr>
                        <template v-else>
                            <tr v-for="order in ordersData.data" :key="order.id">
                                <td v-if="isColVisible('details')">
                                    <div>
                                        <strong>
                                            <a :href="route('tailoring::order::show', order.id)"
                                                class="text-decoration-none text-dark">
                                                #{{ order.order_no }}
                                            </a>
                                        </strong>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fa fa-calendar-alt me-1"></i> {{ formatDate(order.order_date ||
                                            order.created_at) }}
                                        </small>
                                        <small v-if="order.delivery_date" class="text-muted ms-2" title="Delivery Date">
                                            <i class="fa fa-truck me-1"></i> {{ formatDate(order.delivery_date) }}
                                        </small>
                                    </div>
                                </td>
                                <td v-if="isColVisible('customer')">
                                    <div>
                                        <strong>{{ order.account?.name || order.customer_name || 'Walk-in Customer'
                                            }}</strong><br>
                                        <small class="text-muted">{{ order.salesman?.name ? 'Sales: ' +
                                            order.salesman.name : '' }}</small>
                                    </div>
                                </td>
                                <td v-if="isColVisible('status')" class="text-center">
                                    <span :class="'badge ' + getStatusClass(order.status)">
                                        {{ order.status }}
                                    </span>
                                </td>
                                <td v-if="isColVisible('amount')" class="text-end">
                                    <div>
                                        <strong>{{ formatCurrency(order.grand_total) }}</strong><br>
                                        <small :class="order.balance > 0 ? 'text-danger' : 'text-success'">
                                            {{ order.balance > 0 ? 'Bal: ' + formatCurrency(order.balance) : 'Paid' }}
                                        </small>
                                    </div>
                                </td>
                                <td v-if="isColVisible('actions')" class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a :href="route('tailoring::order::show', order.id)"
                                            class="btn btn-light border" title="View Details">
                                            <i class="fa fa-eye text-primary"></i>
                                        </a>
                                        <a :href="route('tailoring::order::edit', order.id)"
                                            class="btn btn-light border" title="Edit Order">
                                            <i class="fa fa-edit text-warning"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!ordersData.data || ordersData.data.length === 0">
                                <td colspan="10" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fa fa-inbox fa-3x mb-3 text-secondary opacity-50"></i>
                                        <p class="mb-1">No orders found matching your criteria.</p>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="ordersData.links && ordersData.links.length > 3"
                class="d-flex justify-content-between align-items-center p-3 border-top">
                <div class="text-muted small">
                    Showing {{ ordersData.from || 0 }} to {{ ordersData.to || 0 }} of {{ ordersData.total || 0 }}
                    results
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li v-for="(link, i) in ordersData.links" :key="i"
                            :class="['page-item', { 'active': link.active, 'disabled': !link.url }]">
                            <button v-if="link.url" @click.prevent="goToPage(link.url)" class="page-link"
                                v-html="link.label"></button>
                            <span v-else class="page-link" v-html="link.label"></span>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import debounce from 'lodash/debounce';
import { tailoringOrderService } from '../../../Services/tailoring-order-service';

const props = defineProps({
    orders: Object
})

const ordersData = ref(props.orders || { data: [], links: [] });
const loading = ref(false);

const filters = ref({
    search: '',
    status: '',
    payment_status: '',
    date_type: 'created_at',
    from_date: '',
    to_date: '',
    customer_id: ''
});

const columns = ref([
    { name: 'details', label: 'Order Details', visible: true },
    { name: 'customer', label: 'Customer', visible: true },
    { name: 'status', label: 'Status', visible: true },
    { name: 'amount', label: 'Total Amount', visible: true },
    { name: 'actions', label: 'Actions', visible: true }
]);

const hasActiveFilters = computed(() => {
    return filters.value.search ||
        filters.value.status ||
        filters.value.payment_status ||
        filters.value.from_date ||
        filters.value.to_date ||
        filters.value.customer_id;
});

const isColVisible = (name) => {
    const col = columns.value.find(c => c.name === name);
    return col ? col.visible : true;
};

const fetchOrders = async (url = null, params = {}) => {
    loading.value = true;
    try {
        // Prepare params: merge current filters with any extra params (like page)
        const requestParams = {
            ...filters.value,
            ...params
        };

        const data = await tailoringOrderService.getOrders(requestParams, url);
        
        ordersData.value = data;
    } catch (error) {
        console.error('Error fetching orders:', error);
    } finally {
        loading.value = false;
    }
};

const debouncedSearch = debounce(() => {
    // Reset to page 1 on search
    fetchOrders(null, { page: 1 });
}, 500);

const goToPage = (url) => {
    if (!url) return;
    
    // Extract page number from URL if possible to ensure clean param handling
    try {
        const target = new URL(url);
        const page = target.searchParams.get('page');
        if (page) {
            fetchOrders(null, { page });
        } else {
            fetchOrders(url);
        }
    } catch (e) {
        fetchOrders(url);
    }
};

const clearFilters = () => {
    filters.value = {
        search: '',
        status: '',
        payment_status: '',
        date_type: 'created_at',
        from_date: '',
        to_date: '',
        customer_id: ''
    };
    fetchOrders(null, { page: 1 });
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'AED',
    }).format(amount || 0)
}

const getStatusClass = (status) => {
    const classes = {
        'pending': 'bg-warning text-dark',
        'confirmed': 'bg-info text-white',
        'in_progress': 'bg-primary text-white',
        'completed': 'bg-success text-white',
        'delivered': 'bg-dark text-white',
        'cancelled': 'bg-danger text-white'
    }
    return classes[status] || 'bg-secondary text-white'
}
</script>

<style scoped>
.quick-action-link {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.3s ease;
    border: 1px solid rgba(0, 0, 0, 0.05);
    background: white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
}

.quick-action-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    background-color: #f8fafc;
}

.quick-action-link.primary {
    color: #3b82f6;
}

.quick-action-link.success {
    color: #10b981;
}
</style>
