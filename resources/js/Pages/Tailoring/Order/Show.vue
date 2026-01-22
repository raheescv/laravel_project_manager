<template>
    <div class="min-h-screen bg-[#f8fafc] pb-12">
        <!-- Top Navigation Bar -->
        <div class="bg-white/90 backdrop-blur-md border-b border-slate-200/60 sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center gap-4">
                        <a :href="route('tailoring::order::index')" class="p-2 hover:bg-slate-50 rounded-xl transition-all duration-200 group">
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </a>
                        <div>
                            <div class="flex items-center gap-3">
                                <h1 class="text-lg font-bold text-slate-800 tracking-tight leading-none">Order #{{ order.order_no }}</h1>
                                <span :class="getStatusClass(order.status)" class="text-[10px] px-2.5 py-0.5 rounded-full font-bold uppercase tracking-wider">
                                    {{ order.status }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 mt-1 font-medium">Order entry: {{ formatDate(order.order_date) }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="hidden md:flex gap-2 me-4">
                            <a href="/tailoring/order" class="quick-action-link primary shadow-none border-slate-200/60">
                                <i class="fa fa-list"></i>
                                <span>Orders</span>
                            </a>
                            <a :href="'/tailoring/job-completion?order_no=' + order.order_no" class="quick-action-link success shadow-none border-slate-200/60">
                                <i class="fa fa-check-circle"></i>
                                <span>Goto Job Completion</span>
                            </a>
                        </div>
                        <button @click="printOrder" class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:border-slate-300 transition-all shadow-sm">
                            <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Print
                        </button>
                        <a :href="route('tailoring::order::edit', order.id)" class="inline-flex items-center px-4 py-2 bg-indigo-600 rounded-xl text-sm font-semibold text-white hover:bg-indigo-700 transition-all shadow-md shadow-indigo-100">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                
                <!-- Sidebar (4 cols) -->
                <div class="lg:col-span-4 space-y-6">
                    
                    <!-- Customer Information -->
                    <div class="bg-white rounded-[2rem] border border-slate-200/60 shadow-sm overflow-hidden">
                        <div class="px-6 py-6 bg-slate-50/80 border-b border-slate-100">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4">Customer Details</p>
                            <div class="flex items-start gap-4">
                                <div class="w-14 h-14 rounded-2xl bg-indigo-50 flex items-center justify-center text-xl font-bold text-indigo-600 border border-indigo-100 shrink-0 shadow-inner">
                                    {{ order.customer_name?.charAt(0) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-bold text-lg text-dark leading-tight truncate">{{ order.customer_name }}</h3>
                                    <p class="text-sm text-slate-500 flex items-center gap-1.5 mt-1">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                        {{ order.customer_mobile || 'No mobile' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="p-6 grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Branch</p>
                                <p class="text-sm font-bold text-slate-700">{{ order.branch?.name || 'Main' }}</p>
                            </div>
                            <div class="space-y-1 text-right">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider text-right">Salesman</p>
                                <p class="text-sm font-bold text-slate-700">{{ order.salesman?.name || 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div class="bg-white rounded-[2rem] border border-slate-200/60 shadow-sm p-6">
                        <div class="flex items-center gap-2 mb-4">
                            <span class="w-1 h-3 bg-indigo-500 rounded-full"></span>
                            <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Delivery Timeline</h3>
                        </div>
                        <div class="space-y-5">
                            <div class="flex items-start gap-4">
                                <div class="w-8 h-8 rounded-xl bg-slate-50 flex items-center justify-center shrink-0 border border-slate-100">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="flex-1 border-b border-slate-50 pb-2">
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tight mb-0.5">Order Date</p>
                                    <p class="text-sm font-bold text-slate-700 tracking-tight">{{ formatDate(order.order_date) }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <div class="w-8 h-8 rounded-xl bg-indigo-50 flex items-center justify-center shrink-0 border border-indigo-100/50">
                                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tight mb-0.5">Target Delivery</p>
                                    <p class="text-sm font-bold text-slate-800 tracking-tight">{{ formatDate(order.delivery_date) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Insight -->
                    <div class="bg-white rounded-[2rem] border border-slate-200/60 shadow-sm p-7 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-50/40 rounded-full blur-3xl -mr-10 -mt-10 group-hover:bg-indigo-100/50 transition-all duration-700"></div>
                        <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-6 relative z-10">Financial Overview</h3>
                        <div class="space-y-4 relative z-10">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-slate-500 font-semibold">Subtotal</span>
                                <span class="text-slate-700 font-bold">{{ formatCurrency(order.gross_amount) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-slate-500 font-semibold">Stitch Rate</span>
                                <span class="text-slate-700 font-bold">{{ formatCurrency(order.stitch_amount) }}</span>
                            </div>
                            <div v-if="order.discount" class="flex justify-between items-center text-sm">
                                <span class="text-slate-500 font-semibold">Discount Applied</span>
                                <span class="text-rose-500 font-bold">-{{ formatCurrency(order.discount) }}</span>
                            </div>
                            <div class="pt-4 border-t border-slate-100 flex justify-between items-center">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Total</span>
                                <span class="text-2xl font-black text-slate-800 tracking-tighter">{{ formatCurrency(order.total_amount) }}</span>
                            </div>
                            <div class="pt-2 flex justify-between items-center text-sm">
                                <span class="font-bold text-emerald-600/80">Amount Paid</span>
                                <span class="font-black text-emerald-600">{{ formatCurrency(order.paid_amount) }}</span>
                            </div>
                            <div class="flex justify-between items-center p-4 rounded-2xl mt-4" :class="order.balance_amount > 0 ? 'bg-slate-50 border border-slate-100' : 'bg-emerald-50 border border-emerald-100'">
                                <span class="text-[10px] font-bold uppercase tracking-widest" :class="order.balance_amount > 0 ? 'text-slate-400' : 'text-emerald-600'">Remaining Balance</span>
                                <span class="text-xl font-black" :class="order.balance_amount > 0 ? 'text-slate-800' : 'text-emerald-700'">{{ formatCurrency(order.balance_amount) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content (8 cols) -->
                <div class="lg:col-span-8 space-y-6">
                    
                    <!-- Items Card -->
                    <div class="bg-white rounded-[2.5rem] border border-slate-200/60 shadow-sm overflow-hidden">
                        <div class="px-8 py-7 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-6">
                            <div>
                                <h2 class="text-2xl font-black text-slate-800 tracking-tight">Order Lifecycle</h2>
                                <p class="text-xs text-slate-400 font-bold mt-1 uppercase tracking-wider">Composition • {{ order.items?.length || 0 }} Items</p>
                            </div>
                            
                            <!-- Category Pills -->
                            <div v-if="categoryTabs.length > 0" class="bg-slate-100/80 p-1.5 rounded-2xl flex items-center gap-1.5 self-start shadow-inner">
                                <button
                                    v-for="category in categoryTabs"
                                    :key="category.id"
                                    @click="activeCategoryTab = category.id"
                                    class="px-5 py-2.5 rounded-xl text-xs font-bold transition-all duration-400 whitespace-nowrap"
                                    :class="activeCategoryTab === category.id 
                                        ? 'bg-white text-indigo-600 shadow-sm ring-1 ring-slate-200/50' 
                                        : 'text-slate-400 hover:text-slate-600'"
                                >
                                    {{ category.name }} <span class="ml-1 opacity-40 font-black text-[10px]">{{ category.count }}</span>
                                </button>
                            </div>
                        </div>

                        <div class="p-8">
                            <div v-if="activeCategoryTab" class="space-y-10">
                                <div v-for="(item, index) in getItemsByCategory(activeCategoryTab)" :key="item.id" class="animate-fade-in">
                                    <div class="flex flex-col md:flex-row gap-8">
                                        <!-- Vertical Item ID -->
                                        <div class="hidden md:flex flex-col items-center pt-2">
                                            <div class="w-14 h-14 rounded-2xl bg-white border-2 border-slate-50 flex items-center justify-center shadow-sm">
                                                <span class="text-lg font-black text-slate-800">{{ item.item_no }}</span>
                                            </div>
                                            <div class="w-1 h-full bg-slate-50/60 mt-4 rounded-full min-h-[140px]"></div>
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <div class="flex justify-between items-start mb-6">
                                                <div>
                                                    <h3 class="text-2xl font-black text-slate-800 tracking-tight mb-2">{{ item.product_name }}</h3>
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="text-[10px] font-black text-slate-400 uppercase border border-slate-100 px-2 py-0.5 rounded-md tracking-wider">{{ item.category?.name }}</span>
                                                        <span class="text-[10px] font-black text-indigo-500 bg-indigo-50/50 px-2 py-0.5 rounded-md uppercase tracking-wider">{{ item.category_model?.name || 'Standard' }}</span>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    <a :href="route('tailoring::order::print-cutting-slip', {id: order.id, category_id: item.tailoring_category_id, model_id: item.tailoring_category_model_id || 'all'})"
                                                       target="_blank"
                                                       class="w-11 h-11 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-all border border-slate-100 hover:border-indigo-100 shadow-sm"
                                                       title="Cutting Slip">
                                                        <svg class="w-5.5 h-5.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                        </svg>
                                                    </a>
                                                    <div class="text-right">
                                                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1.5">Value</p>
                                                        <p class="text-xl font-black text-slate-800 tracking-tighter">{{ formatCurrency(item.total) }}</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="bg-slate-50/40 rounded-[2rem] border border-slate-100 p-2 shadow-inner">
                                                <MeasurementView :item="item" />
                                            </div>

                                            <div class="mt-8 grid grid-cols-2 md:grid-cols-4 gap-6 px-4">
                                                <div>
                                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 text-center">Volume</p>
                                                    <div class="bg-white border border-slate-100 rounded-xl py-2 shadow-sm text-center">
                                                        <p class="text-xs font-black text-slate-700">{{ item.quantity }} <span class="opacity-50 font-bold ml-0.5">{{ item.unit?.name || 'Nos' }}</span></p>
                                                    </div>
                                                </div>
                                                <div>
                                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 text-center">Shade</p>
                                                    <div class="bg-white border border-slate-100 rounded-xl py-2 shadow-sm text-center">
                                                        <p class="text-xs font-black text-slate-700">{{ item.product_color || 'Standard' }}</p>
                                                    </div>
                                                </div>
                                                <div>
                                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 text-center">Commission</p>
                                                    <div class="bg-white border border-slate-100 rounded-xl py-2 shadow-sm text-center">
                                                        <p class="text-xs font-black text-slate-700">{{ formatCurrency(item.stitch_rate) }}</p>
                                                    </div>
                                                </div>
                                                <div>
                                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5 text-center">Phase</p>
                                                    <div class="rounded-xl py-2 text-center" :class="getCompletionStatusStyle(item.status || 'Pending', false, false)">
                                                        <p class="text-[10px] font-black uppercase tracking-tight">{{ item.status || 'Pending' }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div v-if="index < getItemsByCategory(activeCategoryTab).length - 1" class="my-12 h-px bg-slate-100 mx-10 opacity-60"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- History -->
                    <div v-if="order.payments && order.payments.length > 0" class="bg-white rounded-[2.5rem] border border-slate-200/60 shadow-sm overflow-hidden">
                        <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between">
                            <h2 class="text-lg font-black text-slate-800 tracking-tight leading-none">Financial Footprint</h2>
                            <span class="text-[10px] font-bold bg-slate-50 text-slate-400 px-3 py-1 rounded-full border border-slate-100 uppercase tracking-wider">{{ order.payments.length }} Transactions</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="bg-slate-50/30">
                                        <th class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Moment</th>
                                        <th class="px-4 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Instrument</th>
                                        <th class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Magnitude</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50/50">
                                    <tr v-for="payment in order.payments" :key="payment.id" class="group hover:bg-indigo-50/20 transition-all duration-300">
                                        <td class="px-8 py-4 text-xs font-bold text-slate-500">{{ formatDate(payment.date) }}</td>
                                        <td class="px-4 py-4">
                                            <span class="text-xs font-black text-slate-700 tracking-tight flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 shadow-sm"></span>
                                                {{ payment.payment_method?.name || 'Cash' }}
                                            </span>
                                        </td>
                                        <td class="px-8 py-4 text-sm font-black text-emerald-600 text-right tracking-tighter group-hover:scale-105 transition-transform duration-300">
                                            {{ formatCurrency(payment.amount) }}
                                        </td>
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
import { computed, ref, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import MeasurementView from '@/components/Tailoring/MeasurementView.vue'

const props = defineProps({
    order: {
        type: Object,
        required: true
    }
})

const activeCategoryTab = ref(null)

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

const categoryTabs = computed(() => {
    if (!props.order.items || props.order.items.length === 0) return []

    const categoriesMap = new Map()
    props.order.items.forEach(item => {
        const catId = item.tailoring_category_id || 'other'
        const catName = item.category?.name || 'Other'

        if (!categoriesMap.has(catId)) {
            categoriesMap.set(catId, {
                id: catId,
                name: catName,
                count: 0
            })
        }
        categoriesMap.get(catId).count++
    })

    return Array.from(categoriesMap.values())
})

const getItemsByCategory = (categoryId) => {
    if (!props.order.items) return []
    return props.order.items.filter(item => (item.tailoring_category_id || 'other') === categoryId)
}

onMounted(() => {
    // Set first category as active by default
    if (categoryTabs.value.length > 0 && !activeCategoryTab.value) {
        activeCategoryTab.value = categoryTabs.value[0].id
    }
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
        'Pending': 'bg-amber-50 text-amber-600 border border-amber-100',
        'Confirmed': 'bg-blue-50 text-blue-600 border border-blue-100',
        'Completed': 'bg-emerald-50 text-emerald-600 border border-emerald-100',
        'Cancelled': 'bg-rose-50 text-rose-600 border border-rose-100',
        'Delivered': 'bg-slate-900 text-white'
    }
    return classes[status] || 'bg-slate-100 text-slate-600 border border-slate-200'
}

const getCompletionStatusStyle = (status, isIcon = false, isText = false) => {
    const styles = {
        'Pending': { icon: 'bg-amber-50 text-amber-600', text: 'text-amber-600', full: 'bg-amber-50 text-amber-600' },
        'In Progress': { icon: 'bg-blue-50 text-blue-600', text: 'text-blue-600', full: 'bg-blue-50 text-blue-600' },
        'Partially Completed': { icon: 'bg-indigo-50 text-indigo-600', text: 'text-indigo-600', full: 'bg-indigo-50 text-indigo-600' },
        'Completed': { icon: 'bg-emerald-50 text-emerald-600', text: 'text-emerald-600', full: 'bg-emerald-50 text-emerald-600' }
    }
    const val = styles[status] || { icon: 'bg-slate-50 text-slate-400', text: 'text-slate-400', full: 'bg-slate-50 text-slate-400' }
    
    if (isIcon) return val.icon
    if (isText) return val.text
    return val.full
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
.quick-action-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.8rem;
    transition: all 0.3s ease;
    border: 1px solid rgba(0, 0, 0, 0.05);
    background: white;
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

.print-icon:hover {
    background-color: #ecfdf5 !important;
    color: #059669 !important;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes fadeSlideUp {
    from { 
        opacity: 0;
        transform: translateY(20px);
    }
    to { 
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fadeSlideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@media print {
    body.print-slips .min-h-screen {
        display: none !important;
    }
    body:not(.print-slips) #cutting-slips-container {
        display: none !important;
    }
    div, section, article {
        background-color: white !important;
    }
    .no-print, nav, .sticky {
        display: none !important;
    }
    * {
        box-shadow: none !important;
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
/*.header, .measure-grid, .measure-box, .measure-val, .measure-label, .slip-table, .col-desc, .highlight-yellow, .info-bar, .info-values, .bottom-section, .field-row, .field-input, .qr-placeholder, .signature-row remain same as they are for print fidelity */
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
