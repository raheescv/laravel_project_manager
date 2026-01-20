<template>
    <div class="min-h-screen bg-gray-50 pb-12">
        <!-- Top Navigation Bar -->
        <div class="bg-white border-b sticky top-0 z-30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center gap-4">
                        <Link :href="route('tailoring::order::index')" class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <div>
                            <h1 class="text-lg font-bold text-gray-900 leading-tight">Order #{{ order.order_no }}</h1>
                            <p class="text-xs text-gray-500 font-medium">{{ order.customer_name }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button @click="printOrder" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-all shadow-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Print Order
                        </button>

                        <Link :href="route('tailoring::order::edit', order.id)" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-lg text-sm font-semibold text-white hover:bg-blue-700 transition-all shadow-md hover:shadow-lg active:scale-95">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit Order
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Order Stats & Customer Info -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Status Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-6">
                                <div>
                                    <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Current Status</p>
                                    <span :class="getStatusClass(order.status)" class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                                        {{ order.status }}
                                    </span>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Completion</p>
                                    <span :class="getCompletionStatusClass(order.completion_status)" class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                                        {{ order.completion_status }}
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="flex justify-between py-3 border-b border-gray-50">
                                    <span class="text-sm text-gray-500 font-medium">Order Date</span>
                                    <span class="text-sm font-bold text-gray-900">{{ formatDate(order.order_date) }}</span>
                                </div>
                                <div class="flex justify-between py-3 border-b border-gray-50">
                                    <span class="text-sm text-gray-500 font-medium">Delivery Date</span>
                                    <span class="text-sm font-bold text-red-600">{{ formatDate(order.delivery_date) }}</span>
                                </div>
                                <div class="flex justify-between py-3">
                                    <span class="text-sm text-gray-500 font-medium">Salesman</span>
                                    <span class="text-sm font-bold text-gray-900">{{ order.salesman?.name || 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="bg-gray-900 p-6 text-white">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center text-xl font-bold">
                                    {{ order.customer_name?.charAt(0) }}
                                </div>
                                <div>
                                    <h3 class="font-bold text-lg leading-tight">{{ order.customer_name }}</h3>
                                    <p class="text-white/60 text-sm">{{ order.customer_mobile || 'No mobile number' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-6">
                             <div class="grid grid-cols-2 gap-4">
                                 <div class="p-3 bg-gray-50 rounded-xl">
                                     <p class="text-[10px] font-black text-gray-400 uppercase mb-1">Branch</p>
                                     <p class="text-sm font-bold text-gray-800">{{ order.branch?.name || 'Main Branch' }}</p>
                                 </div>
                                 <div class="p-3 bg-gray-50 rounded-xl">
                                     <p class="text-[10px] font-black text-gray-400 uppercase mb-1">Total Items</p>
                                     <p class="text-sm font-bold text-gray-800">{{ order.items?.length || 0 }} Items</p>
                                 </div>
                             </div>
                        </div>
                    </div>

                    <!-- Payment Summary Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6">
                            <h3 class="text-sm font-black text-gray-900 uppercase tracking-widest mb-6 border-l-4 border-emerald-500 pl-3">Payment Summary</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Gross Amount</span>
                                    <span class="font-bold text-gray-900">{{ formatCurrency(order.gross_amount) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Stitch Rate Total</span>
                                    <span class="font-bold text-gray-900">{{ formatCurrency(order.stitch_amount) }}</span>
                                </div>
                                <div v-if="order.discount" class="flex justify-between text-sm">
                                    <span class="text-gray-500">Discount</span>
                                    <span class="font-bold text-red-500">-{{ formatCurrency(order.discount) }}</span>
                                </div>
                                <div class="pt-3 border-t-2 border-dashed flex justify-between items-center">
                                    <span class="text-sm font-bold text-gray-900">Total Amount</span>
                                    <span class="text-xl font-black text-gray-900">{{ formatCurrency(order.total_amount) }}</span>
                                </div>
                                <div class="flex justify-between text-sm pt-4">
                                    <span class="text-gray-500">Paid Amount</span>
                                    <span class="font-bold text-emerald-600">{{ formatCurrency(order.paid_amount) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Balance Due</span>
                                    <span :class="order.balance_amount > 0 ? 'text-red-600 font-black' : 'text-emerald-600 font-bold'">
                                        {{ formatCurrency(order.balance_amount) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Items & Details -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="flex items-center justify-between mb-2">
                            <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">Order Items</h2>
                            <div class="h-px bg-gray-200 grow mx-4"></div>
                    </div>

                    <div v-for="(item, index) in order.items" :key="item.id" class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden transition-all hover:shadow-md">
                        <!-- Item Header -->
                        <div class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-full bg-gray-900 text-white flex items-center justify-center text-xs font-bold shadow-sm">
                                    #{{ item.item_no }}
                                </span>
                                <div>
                                    <h3 class="font-black text-gray-900 uppercase tracking-tight">{{ item.product_name }}</h3>
                                    <p class="text-xs text-gray-500 font-semibold">{{ item.category?.name }} • {{ item.category_model?.name || 'Standard' }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <a :href="route('tailoring::order::print-cutting-slip', {id: order.id, category_id: item.tailoring_category_id, model_id: item.tailoring_category_model_id || 'all'})" 
                                   target="_blank"
                                   class="p-2 hover:bg-emerald-50 rounded-xl transition-all text-gray-400 hover:text-emerald-600 border border-transparent hover:border-emerald-100"
                                   title="Print Cutting Slip">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                </a>
                                <div class="text-right">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">Line Total</p>
                                    <p class="text-lg font-black text-gray-900">{{ formatCurrency(item.total) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Item Content -->
                        <div class="p-6">
                            <MeasurementView :item="item" />

                            <!-- Product Info -->
                            <div class="mt-8 pt-6 border-t grid grid-cols-2 md:grid-cols-4 gap-6">
                                <div>
                                    <p class="text-[10px] font-black text-gray-400 uppercase mb-1">Quantity</p>
                                    <p class="text-sm font-bold text-gray-800">{{ item.quantity }} {{ item.unit?.name || 'Nos' }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-gray-400 uppercase mb-1">Color</p>
                                    <p class="text-sm font-bold text-gray-800">{{ item.product_color || 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-gray-400 uppercase mb-1">Stitch Rate</p>
                                    <p class="text-sm font-bold text-gray-800">{{ formatCurrency(item.stitch_rate) }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-gray-400 uppercase mb-1">Status</p>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-100 text-amber-700 uppercase">
                                        {{ item.status || 'Pending' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payments Log -->
                    <div v-if="order.payments && order.payments.length > 0" class="mt-12">
                         <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">Payment History</h2>
                            <div class="h-px bg-gray-200 grow mx-4"></div>
                        </div>
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <table class="w-full text-left">
                                <thead class="bg-gray-50 border-b">
                                    <tr>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Date</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Method</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <tr v-for="payment in order.payments" :key="payment.id" class="hover:bg-gray-50/50 transition-colors">
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-600">{{ formatDate(payment.date) }}</td>
                                        <td class="px-6 py-4 text-sm font-bold text-gray-900">{{ payment.payment_method?.name || 'Cash' }}</td>
                                        <td class="px-6 py-4 text-sm font-black text-emerald-600 text-right">{{ formatCurrency(payment.amount) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cutting Slips Print Template -->
    <div id="cutting-slips-container" class="hidden print:block print:bg-white">
        <div v-for="(group, catId, idx) in groupedItems" :key="'slip-' + catId" class="cutting-slip print-container" :class="{'page-break': idx > 0}">
            <div class="header">
                <div class="header-left">
                    <p>{{ order.customer_mobile || 'No Phone' }}</p>
                    <h1>{{ order.customer_name }}</h1>
                    <p>ID: {{ order.order_no }}</p>
                </div>
                <div class="header-center">
                    <h2>{{ (group.category?.name || 'Tailoring').toUpperCase() }} CUTTING SLIP</h2>
                </div>
                <div class="header-right flex flex-col items-end gap-2">
                    <div class="no-print">
                        <a :href="route('tailoring::order::print-cutting-slip', {id: order.id, category_id: catId, model_id: group.measurements.tailoring_category_model_id || 'all'})" 
                           target="_blank"
                           class="inline-flex items-center px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-[10px] font-bold rounded transition-colors">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                            OPEN SEPARATE
                        </a>
                    </div>
                    <div class="text-right">
                        Order Date: {{ formatDate(order.order_date) }}<br>
                        Delivery Date: {{ formatDate(order.delivery_date) }}
                    </div>
                </div>
            </div>

            <div class="measure-grid">
                <div class="measure-box"><div class="measure-val">{{ group.measurements.length || '' }}</div><div class="measure-label">Length</div></div>
                <div class="measure-box"><div class="measure-val">{{ group.measurements.shoulder || '' }}</div><div class="measure-label">(Shoulder)</div></div>
                <div class="measure-box"><div class="measure-val">{{ group.measurements.sleeve || '' }}</div><div class="measure-label">(Sleeve)</div></div>
                <div class="measure-box"><div class="measure-val">{{ group.measurements.chest || '' }}</div><div class="measure-label">(Chest)</div></div>
                <div class="measure-box"><div class="measure-val">{{ group.measurements.stomach || '' }}</div><div class="measure-label">(Stomach)</div></div>
                <div class="measure-box"><div class="measure-val">{{ group.measurements.sl_chest || '' }}</div><div class="measure-label">(S-L Chest)</div></div>
            </div>

            <div class="measure-grid">
                <div class="measure-box"><div class="measure-val">{{ group.measurements.mar_size || '' }}</div><div class="measure-label">Mar Size</div></div>
                <div class="measure-box"><div class="measure-val">{{ group.measurements.regal_size || '' }}</div><div class="measure-label">(Regal Size)</div></div>
                <div class="measure-box"><div class="measure-val">{{ group.measurements.knee_loose || '' }}</div><div class="measure-label">(Knee Loose)</div></div>
                <div class="measure-box"><div class="measure-val">{{ group.measurements.fp_down || '' }}</div><div class="measure-label">(FP Down)</div></div>
                <div class="measure-box"><div class="measure-val">{{ group.measurements.bottom || '' }}</div><div class="measure-label">(Bottom)</div></div>
                <div class="measure-box"><div class="measure-val">{{ group.measurements.neck || '' }}</div><div class="measure-label">(Neck)</div></div>
            </div>

            <div class="measure-grid" style="grid-template-columns: 2fr 1fr 1fr;">
                <div class="field-row" style="margin-bottom: 0;">Notes: <div class="field-input">{{ group.measurements.tailoring_notes }}</div></div>
                <div class="measure-box"><div class="measure-val">{{ group.measurements.fp_size || '' }}</div><div class="measure-label">(FP Size)</div></div>
                <div class="measure-box"><div class="measure-val">{{ group.measurements.neck_d_button || '' }}</div><div class="measure-label">(Neck D Bottom)</div></div>
            </div>

            <table class="slip-table">
                <thead>
                    <tr>
                        <th class="col-desc">Description</th>
                        <th>Barcode</th>
                        <th>Qty</th>
                        <th>Type</th>
                        <th>Model</th>
                        <th>Color</th>
                        <th>Stitch Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in group.items" :key="item.id">
                        <td class="col-desc">{{ item.product_name }}</td>
                        <td>{{ item.product?.barcode || '-' }}</td>
                        <td>{{ item.quantity }}</td>
                        <td>{{ item.category?.name }}</td>
                        <td><span class="highlight-yellow">{{ item.category_model?.name || 'Standard' }}</span></td>
                        <td>{{ item.product_color || '-' }}</td>
                        <td>{{ formatCurrency(item.stitch_rate) }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="info-bar">
                <div>MAR Model</div>
                <div>FP Model</div>
                <div>Pen</div>
                <div>Mobile Pocket</div>
                <div>Button No.</div>
                <div>Side PT Model</div>
                <div>Side PT Size</div>
                <div style="border-right:none">Cuff</div>
            </div>
            <div class="info-values">
                <div>{{ group.measurements.mar_model || '-' }}</div>
                <div>{{ group.measurements.fp_model || '-' }}</div>
                <div>{{ group.measurements.pen || '-' }}</div>
                <div>{{ group.measurements.mobile_pocket || '-' }}</div>
                <div>{{ group.measurements.button_no || '-' }}</div>
                <div>{{ group.measurements.side_pt_model || '-' }}</div>
                <div>{{ group.measurements.side_pt_size || '-' }}</div>
                <div style="border-right:none">{{ group.measurements.cuff || '-' }}</div>
            </div>

            <div class="bottom-section">
                <div style="flex: 2;">
                    <div class="field-row">Cutting Master: <div class="field-input">{{ order.cutter?.name }}</div></div>
                    <div class="field-row">Tailor Name: <div class="field-input">{{ group.items[0]?.tailor?.name }}</div></div>
                    <div class="field-row" style="margin-top: 20px; gap: 40px;">
                        <label><input type="checkbox" :checked="order.status === 'Confirmed'"> Booking</label>
                        <label><input type="checkbox" :checked="order.status === 'Completed'"> Finished</label>
                        <label><input type="checkbox" :checked="order.status === 'Delivered'"> Delivered</label>
                    </div>
                </div>
                <div style="flex: 1; text-align: right;">
                    <div class="field-row">Remarks: <div class="field-input"></div></div>
                    <div style="display: inline-block; margin-top: 10px;">
                        <div class="qr-placeholder">
                            <img :src="'https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=' + order.order_no + '-' + catId" alt="QR Code" width="100">
                        </div>
                    </div>
                </div>
            </div>

            <div class="signature-row">
                <div>Prepared By: {{ order.salesman?.name }}</div>
                <div>Approved By: {{ order.customer_name }}</div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import MeasurementView from '@/components/Tailoring/MeasurementView.vue'

const props = defineProps({
    order: {
        type: Object,
        required: true
    }
})

const groupedItems = computed(() => {
    if (!props.order.items) return {}
    return props.order.items.reduce((acc, item) => {
        const catId = item.tailoring_category_id || 'other'
        if (!acc[catId]) {
            acc[catId] = {
                category: item.category,
                items: [],
                // Use the first item's measurements for the slip (since they are order-wide per category)
                measurements: item 
            }
        }
        acc[catId].items.push(item)
        return acc
    }, {})
})

const formatDate = (dateString) => {
    if (!dateString) return 'N/A'
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
        'Pending': 'bg-amber-100 text-amber-700',
        'Confirmed': 'bg-blue-100 text-blue-700',
        'Completed': 'bg-emerald-100 text-emerald-700',
        'Cancelled': 'bg-red-100 text-red-700'
    }
    return classes[status] || 'bg-gray-100 text-gray-700'
}

const getCompletionStatusClass = (status) => {
    const classes = {
        'Pending': 'bg-gray-100 text-gray-500',
        'In Progress': 'bg-blue-50 text-blue-600',
        'Partially Completed': 'bg-amber-50 text-amber-600',
        'Completed': 'bg-emerald-50 text-emerald-600'
    }
    return classes[status] || 'bg-gray-100 text-gray-500'
}

const printOrder = () => {
    document.body.classList.remove('print-slips')
    window.print()
}

const printCuttingSlips = () => {
    document.body.classList.add('print-slips')
    window.print()
}
</script>

<style scoped>
@media print {
    body.print-slips .min-h-screen {
        display: none !important;
    }
    body:not(.print-slips) #cutting-slips-container {
        display: none !important;
    }
    .bg-gray-50 {
        background-color: white !important;
    }
    .no-print, nav, .sticky {
        display: none !important;
    }
    .shadow-sm, .shadow-md, .shadow-lg {
        box-shadow: none !important;
    }
    .rounded-2xl {
        border-radius: 0 !important;
    }
    .page-break {
        page-break-before: always;
    }
}

/* Cutting Slip Styles */
.print-container {
    width: 100%;
    max-width: 900px;
    margin: auto;
    border: 1px solid #000;
    padding: 25px;
    box-sizing: border-box;
    background: white;
    color: black;
}

.header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
    border-bottom: 2px solid #000;
    padding-bottom: 10px;
}

.header-left h1 { margin: 0; font-size: 1.6rem; text-transform: uppercase; }
.header-left p { margin: 2px 0; font-size: 1rem; font-weight: bold; color: #333; }
.header-center { text-align: center; }
.header-center h2 { margin: 0; font-size: 1.2rem; border-bottom: 1px solid #000; display: inline-block; padding-bottom: 2px; }
.header-right { text-align: right; font-size: 0.9rem; font-weight: bold; line-height: 1.4; }

.measure-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 8px;
    margin-bottom: 12px;
}

.measure-box {
    border: 1px solid #000;
    border-radius: 6px;
    display: flex;
    align-items: center;
    height: 35px;
    overflow: hidden;
}

.measure-val { 
    width: 40%; 
    background: #f0f0f0;
    border-right: 1px solid #000; 
    height: 100%; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    font-weight: bold; 
    font-size: 1rem;
}

.measure-label { 
    width: 60%; 
    padding-left: 8px; 
    font-weight: bold; 
    font-size: 0.75rem;
    color: #444;
}

.slip-table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
    font-size: 0.85rem;
}

.slip-table th {
    border: 1px solid #000;
    padding: 10px 5px;
    background-color: #f2f2f2;
    text-transform: capitalize;
    font-weight: bold;
}

.slip-table td {
    border: 1px solid #000;
    padding: 10px 8px;
    text-align: center;
}

.col-desc { text-align: left !important; width: 25%; font-weight: bold; }
.highlight-yellow { background-color: #ffff00; font-weight: bold; padding: 2px 5px; }

.info-bar {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    border: 1px solid #000;
    margin-top: 10px;
    background: #fff;
}

.info-bar div {
    padding: 8px 4px;
    text-align: center;
    border-right: 1px solid #000;
    font-size: 0.7rem;
    font-weight: bold;
    background-color: #f9f9f9;
}

.info-values {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    border: 1px solid #000;
    border-top: none;
    margin-bottom: 20px;
    min-height: 30px;
}

.info-values div {
    border-right: 1px solid #000;
    padding: 5px;
    text-align: center;
    font-weight: bold;
}

.bottom-section {
    display: flex;
    gap: 30px;
    margin-top: 20px;
}

.field-row {
    display: flex;
    align-items: center;
    margin-bottom: 12px;
    font-size: 0.9rem;
    font-weight: bold;
}

.field-input {
    flex: 1;
    border-bottom: 1px dashed #000;
    margin-left: 10px;
    min-height: 22px;
}

.qr-placeholder {
    width: 110px;
    height: 110px;
    border: 1px solid #eee;
    padding: 5px;
}

.signature-row {
    display: flex;
    justify-content: space-between;
    margin-top: 50px;
    padding-top: 10px;
    border-top: 1px solid #eee;
    font-size: 0.9rem;
    font-weight: bold;
}
</style>
