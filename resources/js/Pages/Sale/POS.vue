<template>
    <div
        class="min-h-screen md:max-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50 relative md:overflow-hidden">
        <!-- Optimized floating background elements for mobile -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div
                class="absolute -top-20 sm:-top-40 -right-20 sm:-right-40 w-48 h-48 sm:w-96 sm:h-96 bg-gradient-to-br from-blue-100/30 to-indigo-100/30 rounded-full blur-2xl sm:blur-3xl opacity-40 sm:opacity-60">
            </div>
            <div
                class="absolute -bottom-20 sm:-bottom-40 -left-20 sm:-left-40 w-48 h-48 sm:w-96 sm:h-96 bg-gradient-to-tr from-slate-100/30 to-gray-100/30 rounded-full blur-2xl sm:blur-3xl opacity-40 sm:opacity-60">
            </div>
            <div
                class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-32 h-32 sm:w-64 sm:h-64 bg-gradient-to-r from-blue-50/40 to-indigo-50/40 rounded-full blur-2xl sm:blur-3xl opacity-30 sm:opacity-50">
            </div>
        </div>

        <div class="container-fluid min-h-screen md:h-screen relative z-10 flex flex-col md:overflow-hidden">
            <form class="flex-1 flex flex-col min-h-0 md:overflow-hidden">
                <!-- Enhanced Mobile-first responsive layout -->
                <div
                    class="flex flex-col md:flex-row lg:flex-row flex-1 gap-1 sm:gap-2 md:gap-3 lg:gap-4 xl:gap-6 p-1 sm:p-2 md:p-3 lg:p-4 xl:p-6 min-h-0 md:overflow-hidden md:items-stretch">
                    <!-- Categories Sidebar Component - Mobile: Collapsible, Tablet: Sidebar, Desktop: Always visible -->
                    <div class="order-1 md:order-1 w-full md:w-72 lg:w-auto flex flex-col flex-shrink-0 md:h-full md:min-h-0">
                        <CategoriesSidebar :categories="categories" :selected-category="selectedCategory"
                            @category-selected="selectCategory" />
                    </div>

                    <!-- Main Content Area - Products and Cart -->
                    <div
                        class="flex-1 flex flex-col md:flex-row lg:flex-row gap-1 sm:gap-2 md:gap-3 lg:gap-4 order-2 md:order-2 min-h-0 md:h-full md:overflow-hidden">
                        <!-- Products Section - Enhanced responsive layout -->
                        <div
                            class="flex-1 md:flex-[0.7] lg:flex-[0.6] xl:flex-[0.55] flex flex-col order-1 md:order-1 min-h-0 md:overflow-hidden pb-0 md:pb-0">
                                <!-- Compact customer - employee-product search area -->
                            <div
                                class="bg-gradient-to-br from-white via-indigo-50/40 to-purple-50/30 backdrop-blur-xl rounded-2xl shadow-2xl border-2 border-indigo-200/50 mb-2 sm:mb-2 p-2.5 sm:p-3 md:p-2.5 relative overflow-hidden flex-shrink-0 mobile-search-area">
                                <!-- Enhanced background elements -->
                                <div class="absolute inset-0 overflow-hidden">
                                    <div
                                        class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-blue-300/30 to-indigo-400/30 rounded-full blur-2xl">
                                    </div>
                                    <div
                                        class="absolute bottom-0 left-0 w-20 h-20 bg-gradient-to-tr from-emerald-300/30 to-teal-400/30 rounded-full blur-xl">
                                    </div>
                                    <div
                                        class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-32 h-32 bg-gradient-to-r from-purple-200/20 to-pink-200/20 rounded-full blur-2xl">
                                    </div>
                                </div>

                                <!-- Customer and Mobile - Enhanced responsive grid -->
                                <div class="relative grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-2 sm:gap-2.5 mb-2 sm:mb-2.5">
                                    <div class="space-y-1.5">
                                        <!-- Mobile: Buttons below label, Tablet+: Buttons next to label -->
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 mb-1.5">
                                            <label class="text-sm sm:text-sm font-bold text-slate-800 flex items-center">
                                                <i class="fa fa-user-circle text-indigo-600 mr-2 text-base"></i>
                                                <span>Customer</span>
                                            </label>
                                            <div class="grid grid-cols-2 gap-1.5 sm:grid-cols-none sm:gap-1.5 sm:flex sm:flex-row">
                                                <button type="button" @click="viewCustomerDetails"
                                                    :disabled="!form.account_id || form.account_id === 3" :class="[
                                                        'w-full sm:w-auto px-2.5 py-2 sm:py-1 rounded-lg transition-all duration-200 shadow-md hover:shadow-lg transform active:scale-95 font-semibold text-xs sm:text-xs flex items-center justify-center min-h-[40px] sm:min-h-[28px] touch-manipulation border-2 relative',
                                                        (!form.account_id || form.account_id === 3) ?
                                                            'bg-gray-200 text-gray-500 cursor-not-allowed border-gray-300/50' :
                                                            hasCustomerFeedbacks ?
                                                                'bg-gradient-to-r from-purple-500 via-pink-500 to-rose-500 text-white hover:from-purple-600 hover:via-pink-600 hover:to-rose-600 border-purple-400/30 button-glow' :
                                                                'bg-gradient-to-r from-purple-500 via-pink-500 to-rose-500 text-white hover:from-purple-600 hover:via-pink-600 hover:to-rose-600 border-purple-400/30'
                                                    ]">
                                                    <i class="fa fa-eye text-sm mr-1.5 sm:mr-1"></i>
                                                    <span class="text-sm sm:text-xs">View</span>
                                                </button>
                                                <button type="button" @click="addNewCustomer"
                                                    class="w-full sm:w-auto bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500 text-white px-2.5 py-2 sm:py-1 rounded-lg hover:from-blue-600 hover:via-indigo-600 hover:to-purple-600 transition-all duration-200 shadow-md hover:shadow-lg transform active:scale-95 font-semibold text-xs sm:text-xs flex items-center justify-center min-h-[40px] sm:min-h-[28px] touch-manipulation border-2 border-blue-400/30">
                                                    <i class="fa fa-plus text-sm mr-1.5 sm:mr-1"></i>
                                                    <span class="text-sm sm:text-xs">Add</span>
                                                </button>
                                            </div>
                                        </div>
                                        <SearchableSelect v-model="form.account_id" :options="formattedCustomers"
                                            placeholder="Select Customer"
                                            filter-placeholder="Search by name or mobile..." :visibleItems="8"
                                            @search="searchCustomers" @change="handleCustomerChange"
                                            input-class="w-full rounded-lg border-2 border-indigo-200/60 shadow-md focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition-all duration-200 bg-white/95 backdrop-blur-sm hover:shadow-lg hover:border-indigo-300 text-sm sm:text-sm py-2 sm:py-2 px-3 min-h-[40px] sm:min-h-[36px] font-medium" />
                                    </div>
                                    <div class="space-y-1.5">
                                        <!-- Mobile: Label aligned, Tablet+: Same alignment as Customer section -->
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 mb-1.5">
                                            <label class="text-sm sm:text-sm font-bold text-slate-800 flex items-center">
                                                <span> <i class="fa fa-phone text-emerald-600 mr-2 text-sm"></i> Mobile</span>
                                            </label>
                                        </div>
                                        <input v-model="form.customer_mobile" type="tel"
                                            class="w-full rounded-lg border-2 border-emerald-200/60 shadow-md focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30 transition-all duration-200 bg-white/95 backdrop-blur-sm hover:shadow-lg hover:border-emerald-300 text-sm sm:text-sm py-2 sm:py-2 px-3 min-h-[40px] sm:min-h-[36px] font-medium placeholder:text-slate-400"
                                            placeholder="Enter mobile number">
                                    </div>
                                </div>

                                <!-- Employee and Sale Type - Enhanced responsive grid -->
                                <div class="relative grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-2 sm:gap-2.5 mb-2 sm:mb-2.5">
                                    <div class="space-y-1.5">
                                        <label class="text-sm sm:text-sm font-bold text-slate-800 flex items-center mb-1.5">
                                            <i class="fa fa-user-tie text-purple-600 mr-2 text-sm"></i>
                                            <span>Employee</span>
                                        </label>
                                        <SearchableSelect ref="employeeSelectRef" v-model="form.employee_id" :options="employees"
                                            placeholder="Select employee..." filter-placeholder="Search employees..."
                                            :visibleItems="8"
                                            data-employee-select="true"
                                            input-class="w-full rounded-lg border-2 border-purple-200/60 shadow-md focus:border-purple-500 focus:ring-2 focus:ring-purple-500/30 transition-all duration-200 bg-white/95 backdrop-blur-sm hover:shadow-lg hover:border-purple-300 text-sm sm:text-sm py-2 sm:py-2 px-3 min-h-[40px] sm:min-h-[36px] font-medium" />
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="text-sm sm:text-sm font-bold text-slate-800 flex items-center mb-1.5">
                                            <span> <i class="fa fa-tags text-orange-600 mr-2 text-sm"></i> Sale Type</span>
                                        </label>
                                        <SearchableSelect v-model="form.sale_type" :options="priceTypes"
                                            placeholder="Select type..." filter-placeholder="Search sale types..."
                                            :visibleItems="8"
                                            input-class="w-full rounded-lg border-2 border-orange-200/60 shadow-md focus:border-orange-500 focus:ring-2 focus:ring-orange-500/30 transition-all duration-200 bg-white/95 backdrop-blur-sm hover:shadow-lg hover:border-orange-300 text-sm sm:text-sm py-2 sm:py-2 px-3 min-h-[40px] sm:min-h-[36px] font-medium"
                                            @change="loadProducts" />
                                    </div>
                                </div>

                                <!-- Search Section - Enhanced responsive grid -->
                                <div class="relative">
                                    <div
                                        class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 sm:gap-2">
                                        <div class="space-y-1">
                                            <select v-model="selectedProductType" @change="filterByProductType"
                                                class="w-full rounded-lg border-2 border-indigo-200/60 shadow-md focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition-all duration-200 bg-white/95 backdrop-blur-sm hover:shadow-lg hover:border-indigo-300 text-sm py-2 sm:py-2 px-3 min-h-[40px] sm:min-h-[36px] font-medium">
                                                <option v-for="option in productTypeOptions" :key="option.value"
                                                    :value="option.value">
                                                    {{ option.label }}
                                                </option>
                                            </select>
                                        </div>
                                        <div class="space-y-1">
                                            <div class="relative group">
                                                <input v-model="barcodeKey" @input="searchByBarcode" type="text"
                                                    class="w-full pl-9 pr-3 py-2 sm:py-2 rounded-lg border-2 border-purple-200/60 shadow-md focus:border-purple-500 focus:ring-2 focus:ring-purple-500/30 transition-all duration-200 bg-white/95 backdrop-blur-sm group-hover:shadow-lg group-hover:border-purple-300 text-sm min-h-[40px] sm:min-h-[36px] font-medium placeholder:text-slate-400"
                                                    placeholder="Scan barcode" autocomplete="off">
                                            </div>
                                        </div>
                                        <div class="space-y-1">
                                            <div class="relative group">
                                                <input v-model="productKey" @input="searchProducts" type="text"
                                                    class="w-full pl-9 pr-3 py-2 sm:py-2 rounded-lg border-2 border-orange-200/60 shadow-md focus:border-orange-500 focus:ring-2 focus:ring-orange-500/30 transition-all duration-200 bg-white/95 backdrop-blur-sm group-hover:shadow-lg group-hover:border-orange-300 text-sm min-h-[40px] sm:min-h-[36px] font-medium placeholder:text-slate-400"
                                                    placeholder="Search products" autocomplete="off">
                                            </div>
                                        </div>
                                        <button type="button" @click="viewDraftSales"
                                            class="bg-gradient-to-r from-slate-500 via-slate-600 to-gray-600 text-white py-2 sm:py-2 px-3 sm:px-3 rounded-lg hover:from-slate-600 hover:via-slate-700 hover:to-gray-700 transition-all duration-200 shadow-md hover:shadow-lg transform active:scale-95 font-semibold text-sm sm:text-sm flex items-center justify-center min-h-[40px] sm:min-h-[36px] border-2 border-slate-400/30">
                                            <i class="fa fa-file-alt mr-1.5 text-sm"></i>
                                            <span>Drafts</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Products Grid -->
                            <div
                                class="bg-white rounded-lg shadow-md border border-slate-200 flex-1 p-2 sm:p-3 min-h-0">
                                <div class="h-full overflow-y-auto products-container custom-scrollbar" :style="{
                                    'height': windowWidth >= 1024 ? 'calc(100vh - 220px)' : windowWidth >= 768 ? 'calc(70vh - 120px)' : 'auto',
                                    'min-height': windowWidth >= 768 ? '300px' : '250px',
                                    'max-height': windowWidth < 768 ? 'none' : undefined
                                }">
                                    <div v-if="loading" class="flex items-center justify-center h-full">
                                        <div class="text-center">
                                            <div
                                                class="animate-spin rounded-full h-12 w-12 sm:h-16 sm:w-16 border-b-2 border-blue-500 mx-auto mb-4">
                                            </div>
                                            <p class="text-slate-600 text-sm sm:text-base">Loading products...</p>
                                        </div>
                                    </div>
                                    <products-grid v-else :products="products" :lowStockThreshold="10"
                                        @product-selected="addProductToCart"></products-grid>
                                </div>
                            </div>
                        </div>

                        <!-- Cart Section - Enhanced responsive layout -->
                        <div
                            class="w-full md:w-80 lg:w-96 xl:w-[30rem] md:flex-[0.3] lg:flex-[0.4] xl:flex-[0.45] flex flex-col order-3 md:order-3 md:h-full mobile-cart-container">
                            <div
                                class="bg-white rounded-xl shadow-lg border border-slate-200 md:h-full flex flex-col min-h-0 mobile-cart-wrapper">
                                <!-- Cart Items Component -->
                                <CartItems :items="form.items" :total-quantity="totalQuantity" :cart-height="cartHeight"
                                    :max-height="windowWidth >= 1024 ? '100%' : windowWidth >= 768 ? '500px' : '350px'"
                                    @view-cart-items="viewCartItems" @clear-cart="clearCart"
                                    @update-item-quantity="updateItemQuantity" @edit-cart-item="editCartItem"
                                    @remove-cart-item="removeCartItem" @increase-quantity="increaseQuantity"
                                    @decrease-quantity="decreaseQuantity" @manage-combo-offer="manageComboOffer"
                                    item-class="min-h-[64px] py-4" />



                                <!-- Discount Only (full width) -->
                                <div class="p-2 sm:p-3 border-t border-slate-200">
                                    <div class="mb-2 sm:mb-3">
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                                            <i class="fa fa-tag mr-1 text-blue-500"></i>
                                            Discount
                                        </label>
                                        <div class="relative">
                                            <input v-model.number="form.other_discount" @input="calculateTotals"
                                                type="number" step="0.01" min="0"
                                                class="w-full text-xs sm:text-sm py-2 sm:py-2.5 rounded border-slate-300 focus:border-blue-500 focus:ring-blue-500 min-h-[44px] sm:min-h-[40px] pr-12"
                                                placeholder="0">
                                            <button type="button" @click="convertDiscountToPercentage"
                                                class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-blue-500 text-white px-2 py-1 rounded text-xs font-semibold hover:bg-blue-600 transition-colors">
                                                %
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Enhanced Order Total - Mobile optimized -->
                                    <div
                                        class="bg-gradient-to-br from-white via-indigo-50/40 to-purple-50/30 rounded-2xl p-3 sm:p-4 mb-3 sm:mb-4 border border-indigo-200/50 shadow-xl relative overflow-hidden mobile-summary-card">
                                        <!-- Decorative background elements -->
                                        <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-200/20 to-purple-200/20 rounded-full blur-2xl -mr-16 -mt-16"></div>
                                        <div class="absolute bottom-0 left-0 w-24 h-24 bg-gradient-to-tr from-emerald-200/20 to-teal-200/20 rounded-full blur-xl -ml-12 -mb-12"></div>

                                        <div class="relative z-10 space-y-2.5 sm:space-y-3">
                                            <div class="flex justify-between items-center py-1.5 px-2 bg-white/60 backdrop-blur-sm rounded-lg border border-slate-100">
                                                <span class="text-slate-700 font-semibold flex items-center text-sm">
                                                    <span class="hidden sm:inline"><i class="fa fa-calculator text-indigo-600 mr-2 text-sm"></i> Sub Total</span>
                                                    <span class="sm:hidden"><i class="fa fa-calculator text-indigo-600 mr-2 text-sm"></i> Subtotal</span>
                                                </span>
                                                <span
                                                    class="font-bold text-slate-900 bg-gradient-to-r from-indigo-50 to-blue-50 px-3 py-1.5 rounded-lg shadow-md text-sm border border-indigo-100">
                                                    {{ formatNumber(form.total) }}
                                                </span>
                                            </div>
                                            <div class="flex justify-between items-center py-1.5 px-2 bg-white/60 backdrop-blur-sm rounded-lg border border-red-100" v-if="form.other_discount > 0">
                                                <span class="font-semibold flex items-center text-sm text-red-700">

                                                    <span class="hidden sm:inline">
                                                        <i class="fa fa-tag text-red-600 mr-2 text-sm"></i>
                                                        Discount
                                                        <span v-if="!isNaN(discountPercentage) && isFinite(discountPercentage)">({{ discountPercentage }}%)</span>
                                                    </span>
                                                    <span class="sm:hidden">
                                                        <i class="fa fa-tag text-red-600 mr-2 text-sm"></i>
                                                        Disc<span v-if="!isNaN(discountPercentage) && isFinite(discountPercentage)"> ({{ discountPercentage }}%)</span>
                                                    </span>
                                                </span>
                                                <span
                                                    class="font-bold bg-gradient-to-r from-red-50 to-pink-50 text-red-700 px-3 py-1.5 rounded-lg shadow-md text-sm border border-red-100">
                                                    -{{ formatNumber(form.other_discount) }}
                                                </span>
                                            </div>
                                            <div class="flex justify-between items-center py-1.5 px-2 bg-white/60 backdrop-blur-sm rounded-lg border border-blue-100" v-if="Math.abs(form.round_off) > 0.01">
                                                <span class="font-semibold flex items-center text-sm text-blue-700">
                                                    <i class="fa fa-adjust text-blue-600 mr-2 text-sm"></i>
                                                    <span class="hidden sm:inline">Round Off</span>
                                                    <span class="sm:hidden">Round</span>
                                                </span>
                                                <span
                                                    class="font-bold bg-gradient-to-r from-blue-50 to-cyan-50 text-blue-700 px-3 py-1.5 rounded-lg shadow-md text-sm border border-blue-100">{{
                                                        Number(form.round_off).toFixed(2) }}</span>
                                            </div>
                                            <div
                                                class="border-t-2 border-indigo-200/60 pt-3 sm:pt-3 flex justify-between items-center bg-gradient-to-r from-emerald-50/50 to-green-50/50 -mx-3 sm:-mx-4 px-3 sm:px-4 pb-2 rounded-b-2xl">
                                                <span
                                                    class="font-bold text-lg sm:text-xl text-slate-800 flex items-center">
                                                    <i class="fa fa-receipt text-emerald-600 mr-2 text-lg"></i>
                                                    <span class="hidden sm:inline">Total</span>
                                                    <span class="sm:hidden">Total</span>
                                                </span>
                                                <div
                                                    class="bg-gradient-to-r from-emerald-500 via-green-500 to-teal-500 text-white px-4 sm:px-6 py-2.5 sm:py-3 rounded-xl shadow-2xl transform hover:scale-105 transition-transform duration-200 border-2 border-emerald-400/30">
                                                    <span class="text-xl sm:text-2xl font-black tracking-tight">
                                                        {{ formatNumber(form.grand_total) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Action Buttons - Enhanced mobile/tablet optimized -->
                                    <div class="space-y-3 sm:space-y-3 mobile-action-buttons">
                                        <button v-if="canFeedback" type="button" @click="openFeedback"
                                            class="w-full bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500 text-white py-3.5 sm:py-3 md:py-2.5 px-4 rounded-2xl text-sm sm:text-sm font-bold hover:from-blue-600 hover:via-indigo-600 hover:to-purple-600 transition-all duration-300 shadow-xl hover:shadow-2xl transform active:scale-95 relative overflow-hidden group min-h-[52px] sm:min-h-[48px] border-2 border-blue-400/30">
                                            <div
                                                class="absolute inset-0 bg-gradient-to-r from-white/30 via-white/10 to-transparent opacity-0 group-active:opacity-100 transition-opacity duration-200">
                                            </div>
                                            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                                            <div class="relative z-10 flex items-center justify-center">
                                                <i class="fa fa-comment mr-2 text-base"></i>
                                                <span class="text-base">Feedback</span>
                                            </div>
                                        </button>
                                        <div class="grid grid-cols-2 gap-3 sm:gap-3">
                                            <button type="button" @click="saveDraft"
                                                class="bg-gradient-to-r from-slate-500 via-slate-600 to-gray-600 text-white py-3.5 sm:py-3 md:py-2.5 px-3 sm:px-4 rounded-2xl text-sm sm:text-sm font-bold hover:from-slate-600 hover:via-slate-700 hover:to-gray-700 transition-all duration-300 shadow-xl hover:shadow-2xl transform active:scale-95 relative overflow-hidden group min-h-[52px] sm:min-h-[48px] border-2 border-slate-400/30">
                                                <div
                                                    class="absolute inset-0 bg-gradient-to-r from-white/30 via-white/10 to-transparent opacity-0 group-active:opacity-100 transition-opacity duration-200">
                                                </div>
                                                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                                                <div class="relative z-10 flex items-center justify-center">
                                                    <i class="fa fa-save mr-2 text-base"></i>
                                                    <span class="text-base">Draft</span>
                                                </div>
                                            </button>
                                            <button type="button" @click="submitSale"
                                                :disabled="Object.keys(form.items).length === 0" :class="[
                                                    'py-3.5 sm:py-3 md:py-2.5 px-3 sm:px-4 rounded-2xl text-sm sm:text-sm font-bold transition-all duration-300 shadow-xl relative overflow-hidden group min-h-[52px] sm:min-h-[48px] border-2',
                                                    Object.keys(form.items).length === 0 ?
                                                        'bg-gray-400 text-gray-500 cursor-not-allowed border-gray-300/30' :
                                                        'bg-gradient-to-r from-green-500 via-emerald-500 to-teal-500 text-white hover:from-green-600 hover:via-emerald-600 hover:to-teal-600 hover:shadow-2xl transform active:scale-95 border-green-400/30 btn-pulse'
                                                ]">
                                                <div v-if="Object.keys(form.items).length > 0"
                                                    class="absolute inset-0 bg-gradient-to-r from-white/30 via-white/10 to-transparent opacity-0 group-active:opacity-100 transition-opacity duration-200">
                                                </div>
                                                <div v-if="Object.keys(form.items).length > 0"
                                                    class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                                                <div class="relative z-10 flex items-center justify-center">
                                                    <i class="fa fa-check-circle mr-2 text-base"></i>
                                                    <span class="text-base font-extrabold">Submit</span>
                                                </div>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- End Cart Section -->
                    </div>
                    <!-- End Main Content Area - Products and Cart -->
                </div>
                <!-- End Mobile-first responsive layout -->
            </form>
        </div>

        <!-- Cart Items Modal -->
        <CartItemsModal :show="showCartModal" :cart-items-by-employee="cartItemsByEmployee"
            :total-items="Object.keys(form.items).length" :total-quantity="totalQuantity"
            :can-edit-item-price="canEditItemPrice" @close="showCartModal = false"
            @update-item-quantity="updateItemQuantity" @remove-cart-item="removeCartItem"
            @update-item-field="updateItemField" />

        <!-- Customer Modal -->
        <CustomerModal :show="showCustomerModal" :initial-customer="newCustomer" :customer-types="customerTypes"
            :countries="countries" @close="showCustomerModal = false" @customer-saved="handleCustomerSaved"
            @customer-selected="handleCustomerSelected" />

        <!-- Customer Details Modal -->
        <CustomerDetailsModal :show="showCustomerDetailsModal" :customer-id="selectedCustomerId"
            @close="showCustomerDetailsModal = false" @edit="handleCustomerEdit" />

        <!-- Custom Payment Modal -->
        <CustomPaymentModal :show="showCustomPaymentModal" :total-amount="form.grand_total"
            :payment-methods="paymentMethods" :initial-payments="customPaymentData.payments"
            @close="closeCustomPaymentModal" @save="handleCustomPaymentSave" />

        <!-- Feedback Modal -->
        <FeedbackModal :show="showFeedbackModal" :sale="form" @close="closeFeedbackModal"
            @feedback-submitted="handleFeedbackSubmitted" />

        <!-- Sale Confirmation Modal -->
        <SaleConfirmationModal :show="showConfirmationModal" :sale-data="confirmationData" :loading="submitting"
            :payment-method="selectedPaymentMethod" :send-to-whatsapp="sendToWhatsapp"
            @update:paymentMethod="selectPaymentMethod" @update:sendToWhatsapp="val => sendToWhatsapp = val"
            @openCustomPayment="showCustomPaymentModal = true" @close="closeConfirmationModal"
            @submit="processSubmitSale" />

        <!-- Draft Sales Modal -->
        <DraftSalesModal :show="showDraftSalesModal" @close="closeDraftSalesModal" @draft-loaded="handleDraftLoaded" />

        <!-- Edit Item Modal as a component -->
        <EditItemModal :show="showEditItemModal" :item="editItemData" :employees="employees"
            :can-edit-item-price="canEditItemPrice" @close="showEditItemModal = false" @save="onEditItemSave" />

        <!-- Combo Offer Modal -->
        <ComboOfferModal :show="showComboOfferModal" :cart-items="form.items" :initial-combo-offers="form.comboOffers"
            @close="closeComboOfferModal" @save="handleComboOfferSave" @openSettings="openComboOfferSettings" />
    </div>
</template>

<script>
import CartItems from '@/components/CartItems.vue'
import CartItemsModal from '@/components/CartItemsModal.vue'
import CategoriesSidebar from '@/components/CategoriesSidebar.vue'
import CustomerModal from '@/components/CustomerModal.vue'
import CustomPaymentModal from '@/components/CustomPaymentModal.vue'
import DraftSalesModal from '@/components/DraftSalesModal.vue'
import EditItemModal from '@/components/EditItemModal.vue'
import FeedbackModal from '@/components/FeedbackModal.vue'
import ProductsGrid from '@/components/ProductsGrid.vue'
import SaleConfirmationModal from '@/components/SaleConfirmationModal.vue'
import SearchableSelect from '@/components/SearchableSelectFixed.vue'
import ComboOfferModal from '@/components/ComboOfferModal.vue'
import CustomerDetailsModal from '@/components/CustomerDetailsModal.vue'
import {
    useForm
} from '@inertiajs/vue3'
import {
    computed,
    nextTick,
    onMounted,
    onUnmounted,
    ref,
    watch
} from 'vue'
import {
    useToast
} from 'vue-toastification'
import {
    calculateItemTotals,
    calculateCartTotals,
    applyComboOfferPricing,
    resetComboOfferPricing
} from '@/utils/itemCalculations'

export default {
    components: {
        CartItems,
        SearchableSelect,
        CustomerModal,
        CartItemsModal,
        CategoriesSidebar,
        CustomPaymentModal,
        DraftSalesModal,
        FeedbackModal,
        ProductsGrid,
        SaleConfirmationModal,
        EditItemModal,
        ComboOfferModal,
        CustomerDetailsModal
    },
    props: {
        categories: Array,
        employees: Object,
        customers: Object, // Keep this as fallback
        priceTypes: Object,
        saleData: Object,
        customerTypes: {
            type: Object,
            default: () => ({})
        },
        countries: {
            type: Object,
            default: () => ({})
        },
        paymentMethods: {
            type: Array,
            default: () => []
        },
        defaultProductType: {
            type: String,
            default: 'service'
        },
        defaultCustomerEnabled: {
            type: Boolean,
            default: true
        },
        defaultQuantity: {
            type: Number,
            default: 0.001
        },
        canEditItemPrice: {
            type: Boolean,
            default: false
        },
        canFeedback: {
            type: Boolean,
            default: false
        }
    },

    setup(props) {
        const toast = useToast()

        // Reactive data
        const loading = ref(false)
        const products = ref([])
        const employeeSelectRef = ref(null)
        // Initialize serverCustomers with default customer and props.customers
        const serverCustomers = ref({
            ...(props.defaultCustomerEnabled ? {
                3: {
                    id: 3,
                    name: 'General Customer',
                    mobile: ''
                }
            } : {}),
            ...props.customers || {}
        })
        const loadingCustomers = ref(false) // Track customer loading state
        const selectedCategory = ref('favorite')
        const productKey = ref('')
        const barcodeKey = ref('')
        const selectedPaymentMethod = ref(1)
        const sendToWhatsapp = ref(false)
        const showCartModal = ref(false)
        const showCustomerModal = ref(false)
        const showCustomPaymentModal = ref(false)
        const showDraftSalesModal = ref(false)
        const showFeedbackModal = ref(false)
        const showConfirmationModal = ref(false)
        const submitting = ref(false)
        const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024)
        const customPaymentData = ref({
            payments: [],
            totalPaid: 0,
            balanceDue: 0
        })
        const newCustomer = ref({
            name: '',
            mobile: '',
            email: ''
        })

        // Product type filter
        const selectedProductType = ref(props.defaultProductType)
        const productTypeOptions = ref([{
            value: '',
            label: 'All Types'
        },
        {
            value: 'product',
            label: 'Products'
        },
        {
            value: 'service',
            label: 'Services'
        },
        ])

        // Form data
        const form = useForm({
            id: null,
            date: new Date().toISOString().split('T')[0],
            employee_id: props.saleData.employee_id || '',
            sale_type: 'normal',
            account_id: props.defaultCustomerEnabled ? 3 : null,
            customer_mobile: '',
            other_discount: 0,
            round_off: 0,
            total: 0,
            grand_total: 0,
            paid: 0,
            balance: 0,
            items: {},
            comboOffers: [],
            payment_method: 1,
            custom_payment_data: {},
            rating: 0,
            feedback_type: 'compliment',
            feedback: ''
        })

        // Initialize form with sale data if provided
        const initializeFormWithSaleData = () => {
            if (props.saleData && props.saleData.id) {
                // Show loading error if there was an issue loading the sale
                if (props.saleData.load_error) {
                    toast.error(props.saleData.load_error)
                    return
                }

                // Update form with sale data
                form.id = props.saleData.id
                form.date = props.saleData.date || form.date
                form.employee_id = props.saleData.employee_id || ''
                form.sale_type = props.saleData.sale_type || 'normal'
                form.account_id = props.saleData.account_id || 3
                form.customer_mobile = props.saleData.customer_mobile || ''
                form.account_id = props.saleData.account_id || 3
                form.other_discount = props.saleData.other_discount || 0
                form.round_off = props.saleData.round_off || 0
                form.total = props.saleData.total || 0
                form.grand_total = props.saleData.grand_total || 0

                // Ensure the customer from the sale is included in serverCustomers
                if (props.customers && Object.keys(props.customers).length > 0) {
                    Object.entries(props.customers).forEach(([id, customer]) => {
                        serverCustomers.value[id] = customer
                    })
                }

                // Load sale items (already in correct format from controller)
                if (props.saleData.items && typeof props.saleData.items === 'object') {
                    form.items = {
                        ...props.saleData.items
                    }
                }

                // Handle payment method
                if (props.saleData.payment_method === 'custom' && props.saleData.custom_payment_data) {
                    selectedPaymentMethod.value = 'custom'
                    customPaymentData.value = props.saleData.custom_payment_data
                    form.payment_method = 'custom'
                    form.custom_payment_data = props.saleData.custom_payment_data
                } else {
                    selectedPaymentMethod.value = props.saleData.payment_method || 1
                    form.payment_method = props.saleData.payment_method || 1
                    customPaymentData.value = {
                        payments: [],
                        totalPaid: 0,
                        balanceDue: 0
                    }
                }

                // Handle combo offers (already processed by controller)
                if (props.saleData.comboOffers && Array.isArray(props.saleData.comboOffers)) {
                    form.comboOffers = props.saleData.comboOffers
                }

                // Recalculate totals to ensure consistency
                calculateTotals()

                const statusText = props.saleData.status === 'draft' ? 'draft' : 'sale'
                toast.success(`${statusText.charAt(0).toUpperCase() + statusText.slice(1)} loaded successfully`)
            }
        }

        // Initialize confirmationData after form is created
        const confirmationData = ref({
            customerName: '',
            paymentMethods: ''
        })

        // Computed properties
        const totalQuantity = computed(() => {
            return Object.values(form.items).reduce((sum, item) => sum + item.quantity, 0)
        })

        const discountPercentage = computed(() => {
            return form.total ? Math.round((form.other_discount / form.total) * 100 * 100) / 100 : 0
        })

        const cartItemsByEmployee = computed(() => {
            const grouped = {}
            Object.entries(form.items).forEach(([key, item]) => {
                const employeeName = item.employee_name || 'Unknown Employee'
                if (!grouped[employeeName]) {
                    grouped[employeeName] = []
                }
                grouped[employeeName].push({
                    key,
                    ...item
                })
            })
            return grouped
        })

        // Methods
        const selectCategory = (categoryId) => {
            selectedCategory.value = categoryId
            loadProducts()
        }

        // New method to fetch customers from server
        const fetchCustomers = async (query = '') => {
            try {
                const response = await axios.get(`/account/list?query=${encodeURIComponent(query)}&model=customer`, {
                    headers: {
                        'Cache-Control': 'no-cache'
                    }
                })

                if (response.data?.items) {
                    // Start with existing customers to preserve them
                    const customerObj = {
                        ...serverCustomers.value
                    }
                    if (props.defaultCustomerEnabled) {
                        // Ensure default customer is always present
                        customerObj[3] = {
                            id: 3,
                            name: 'General Customer',
                            mobile: '',
                            phone: ''
                        }
                    }

                    // Add props customers (excluding default to avoid duplicates)
                    Object.entries(props.customers || {}).forEach(([id, customer]) => {
                        if (parseInt(id) !== 3) customerObj[id] = customer
                    })

                    // Add/update server customers from API response
                    response.data.items.forEach(customer => {
                        customerObj[customer.id] = customer
                    })

                    serverCustomers.value = customerObj

                    // Update mobile if default customer is selected
                    if (form.account_id === 3 && customerObj[3]) {
                        form.customer_mobile = customerObj[3].mobile || customerObj[3].phone || form.customer_mobile || ''
                    }
                }
            } catch (error) {
                toast.error('Failed to load customer list')
            }
        }

        const loadProducts = async () => {
            loading.value = true
            try {
                const response = await axios.get('/products', {
                    params: {
                        category_id: selectedCategory.value,
                        sale_type: form.sale_type,
                        search: productKey.value,
                        type: selectedProductType.value
                    }
                })

                // Filter and validate products
                const validProducts = (response.data || []).filter(product => {
                    if (!product?.id) {
                        return false
                    }
                    return true
                })

                products.value = validProducts
            } catch (error) {
                toast.error('Failed to load products')
            } finally {
                loading.value = false
            }
        }

        const filterByProductType = () => {
            loadProducts()
        }

        const searchCustomers = debounce((query) => {
            if (query && query.length > 1) {
                loadingCustomers.value = true
                fetchCustomers(query).finally(() => {
                    loadingCustomers.value = false
                })
            }
        }, 300)

        const searchProducts = debounce(() => {
            loadProducts()
        }, 300)

        const searchByBarcode = debounce(async () => {
            if (!barcodeKey.value) return

            try {
                const response = await axios.get('/products/by-barcode', {
                    params: {
                        barcode: barcodeKey.value
                    }
                })

                if (response.data) {
                    await addProductToCart(response.data)
                    barcodeKey.value = ''
                } else {
                    toast.error('Product not found')
                }
            } catch (error) {
                toast.error('Failed to find product')
            }
        }, 300)

        const addProductToCart = async (product) => {
            if (!form.employee_id) {
                toast.error('Please select an employee first.')
                // Open the employee dropdown
                await nextTick()
                // Small delay to ensure DOM is ready and ref is set
                setTimeout(() => {
                    if (employeeSelectRef.value) {
                        // Use the focus method which opens dropdown and focuses input
                        if (employeeSelectRef.value.focus) {
                            employeeSelectRef.value.focus()
                        } else if (employeeSelectRef.value.openDropdown) {
                            employeeSelectRef.value.openDropdown()
                        }
                    } else {
                        // Fallback: try to find and click the input directly
                        const employeeInput = document.querySelector('input[placeholder*="employee" i]')
                        if (employeeInput) {
                            employeeInput.focus()
                            employeeInput.click()
                            employeeInput.scrollIntoView({ behavior: 'smooth', block: 'center' })
                        }
                    }
                }, 200)
                return
            }

            // Check for id in multiple possible locations
            const productId = product?.id || product?.product_id || product?.inventory_id;
            if (!productId) {
                console.error('addProductToCart: Invalid product data - missing id:', product);
                toast.error('Invalid product data')
                return
            }

            try {
                const response = await axios.post('/pos/add-item', {
                    inventory_id: productId,
                    employee_id: form.employee_id,
                    sale_type: form.sale_type,
                    unit_id: product?.unit_id || null
                })

                const item = response.data
                const key = `${item.employee_id}-${item.inventory_id}`

                if (form.items[key]) {
                    // default quantity taken from the settings
                    form.items[key].quantity = (parseFloat(form.items[key].quantity) || 0) + props.defaultQuantity
                    await updateItemQuantity(key)
                } else {
                    form.items[key] = item
                }
                calculateTotals()
            } catch (error) {
                toast.error(error.response?.data?.message || 'Failed to add product to cart')
            }
        }

        const updateItemQuantity = async (key) => {
            const item = form.items[key]
            if (!item) return

            try {
                calculateTotals() // Update UI immediately

                const response = await axios.post('/pos/update-item', {
                    key,
                    item_id: item.id,
                    quantity: item.quantity,
                    item: item
                })

                if (response.data) {
                    form.items[key] = response.data
                }
            } catch (error) {
                toast.error(error.response?.data?.message || 'Failed to update quantity')
            }
            calculateTotals() // Re-calculate after server update
        }

        const removeCartItem = async (key) => {
            if (confirm('Are you sure you want to remove this item?')) {
                try {
                    await axios.post('/pos/remove-item', {
                        key,
                        item_id: form.items[key].id
                    })

                    delete form.items[key]
                    calculateTotals()
                    toast.success('Item removed from cart')
                } catch (error) {
                    toast.error(error.response?.data?.error || 'Failed to remove item')
                }
            }
        }

        const updateItemField = (payload) => {
            const {
                key,
                field,
                value
            } = payload
            const item = form.items[key]
            if (!item) return

            // Update field with proper type conversion
            if (['quantity', 'unit_price', 'discount', 'tax', 'combo_offer_price'].includes(field)) {
                item[field] = Number(value) || 0
            } else {
                item[field] = value
            }

            // Recalculate item totals using utility function
            form.items[key] = calculateItemTotals(item)

            calculateTotals()

            // Update server for critical fields
            if (['quantity', 'unit_price', 'discount', 'tax', 'combo_offer_price'].includes(field)) {
                updateItemQuantity(key)
            }
        }

        const increaseQuantity = (key) => {
            if (!form.items[key]) return

            form.items[key].quantity = (parseInt(form.items[key].quantity) || 0) + 1
            calculateTotals()
            updateItemQuantity(key)
        }

        const decreaseQuantity = (key) => {
            if (!form.items[key]) return

            const currentQty = parseInt(form.items[key].quantity) || 0
            if (currentQty > 1) {
                form.items[key].quantity = currentQty - 1
                calculateTotals()
                updateItemQuantity(key)
            } else {
                toast.info('Quantity cannot be less than 1. Remove item instead?')
            }
        }

        const selectPaymentMethod = (method) => {
            if (method === 'custom') {
                showCustomPaymentModal.value = true
                return
            }
            selectedPaymentMethod.value = method
            form.payment_method = method
            form.custom_payment_data = {} // Clear custom payment data for other methods
            customPaymentData.value = {
                payments: [],
                totalPaid: 0,
                balanceDue: 0
            } // Clear custom payment data for other methods
        }

        const calculateTotals = () => {
            // Calculate item totals for each item
            Object.keys(form.items).forEach(key => {
                form.items[key] = calculateItemTotals(form.items[key])
            })

            // Calculate cart totals
            const totals = calculateCartTotals(form.items)
            form.gross_amount = totals.gross_amount
            form.item_discount = totals.item_discount
            form.tax_amount = totals.tax_amount
            form.total = totals.total

            // Calculate grand total with discount and round off
            const otherDiscount = Number(form.other_discount) || 0
            const grandTotal = parseFloat(form.total) - otherDiscount
            const roundedTotal = Math.round(grandTotal)

            form.round_off = Math.round((roundedTotal - grandTotal) * 100) / 100
            form.grand_total = roundedTotal
        }

        const updateTotals = (totals) => {
            Object.assign(form, totals)
        }

        const clearCart = () => {
            if (confirm('Are you sure you want to clear the cart?')) {
                form.items = {}
                calculateTotals()
                toast.success('Cart cleared')
            }
        }

        const viewCartItems = () => {
            showCartModal.value = true
        }

        // --- Vue-based Edit Item Modal ---
        const showEditItemModal = ref(false)
        const editItemKey = ref(null)
        const editItemData = ref({})

        // Combo Offer Modal
        const showComboOfferModal = ref(false)
        const showCustomerDetailsModal = ref(false)
        const selectedCustomerId = ref(null)
        const hasCustomerFeedbacks = ref(false)

        // Handler for saving from EditItemModal
        const onEditItemSave = (updatedItem) => {
            if (editItemKey.value && form.items[editItemKey.value]) {
                // Use Object.assign to preserve reactivity
                Object.assign(form.items[editItemKey.value], updatedItem)
                calculateTotals()
                showEditItemModal.value = false
                toast.success('Item updated successfully')
            }
        }
        const editCartItem = (key) => {
            const item = form.items[key]
            if (!item) return
            editItemKey.value = key
            // Deep clone to avoid mutating original until save
            editItemData.value = JSON.parse(JSON.stringify(item))
            showEditItemModal.value = true
        }

        const saveEditedItem = () => {
            if (editItemKey.value && form.items[editItemKey.value]) {
                // Validate numeric fields
                ['quantity', 'unit_price', 'discount', 'tax'].forEach(field => {
                    editItemData.value[field] = Number(editItemData.value[field]) || 0
                })
                // Calculate amounts (inspired by Livewire)
                const item = editItemData.value
                item.gross_amount = item.unit_price * item.quantity
                item.net_amount = item.gross_amount - item.discount
                item.tax_amount = Math.round(item.net_amount * (item.tax / 100) * 100) / 100
                item.total = Math.round((item.net_amount + item.tax_amount) * 100) / 100

                // Save back to cart
                form.items[editItemKey.value] = {
                    ...item
                }
                calculateTotals()
                showEditItemModal.value = false
                toast.success('Item updated successfully')
            }
        }

        const addNewCustomer = () => {
            showCustomerModal.value = true
        }

        const viewCustomerDetails = () => {
            if (!form.account_id || form.account_id === 3) {
                toast.error('Please select a customer first')
                return
            }

            // Show customer details modal
            selectedCustomerId.value = form.account_id
            showCustomerDetailsModal.value = true
        }

        const handleCustomerEdit = (customer) => {
            // Close details modal and open edit modal
            showCustomerDetailsModal.value = false
            newCustomer.value = customer
            showCustomerModal.value = true
        }

        const handleCustomerSaved = (customer) => {
            // Add the new customer to the existing list
            if (customer && customer.id) {
                serverCustomers.value[customer.id] = customer
            }

            // Set the form to use the new customer
            form.account_id = parseInt(customer.id)
            form.customer_mobile = customer.mobile || '';
        }

        const handleCustomerChange = (selectedValue) => {
            if (selectedValue) {
                const customer = findCustomerById(selectedValue)
                if (customer) {
                    const normalized = normalizeCustomerData(customer)
                    form.customer_mobile = normalized.mobile
                }
                // Check if customer has feedbacks
                checkCustomerFeedbacks(selectedValue)
            } else {
                form.customer_mobile = ''
                hasCustomerFeedbacks.value = false
            }
        }

        const checkCustomerFeedbacks = async (customerId) => {
            if (!customerId || customerId === 3) {
                hasCustomerFeedbacks.value = false
                return
            }

            try {
                const response = await axios.get(`/account/customer/${customerId}/details`)
                if (response.data?.feedbacks && Array.isArray(response.data.feedbacks) && response.data.feedbacks.length > 0) {
                    hasCustomerFeedbacks.value = true
                } else {
                    hasCustomerFeedbacks.value = false
                }
            } catch (error) {
                // Silently fail - don't show error for this check
                hasCustomerFeedbacks.value = false
            }
        }

        const handleCustomerSelected = (customer) => {
            // Select the existing customer
            form.account_id = customer.id
            // Use customer mobile if available, otherwise preserve existing (from sale data)
            form.customer_mobile = customer.mobile || customer.phone || form.customer_mobile || ''

            toast.success('Customer selected successfully')
        }

        const handleCustomPaymentSave = (paymentData) => {
            // Save custom payment data
            customPaymentData.value = paymentData
            selectedPaymentMethod.value = 'custom'
            form.payment_method = 'custom'
            form.custom_payment_data = paymentData

            // Make sure the modal closes after saving
            showCustomPaymentModal.value = false

            const paymentMethods = paymentData.payments.map(p => p.name).join(', ')
            toast.success(`Custom payment methods configured: ${paymentMethods}`)
        }

        const closeCustomPaymentModal = () => {
            showCustomPaymentModal.value = false
            // If no custom payment was saved and custom was selected, reset to cash
            if (selectedPaymentMethod.value === 'custom' && (!customPaymentData.value.payments || customPaymentData.value.payments.length === 0)) {
                selectedPaymentMethod.value = 1
                form.payment_method = 1
            }
        }

        const viewDraftSales = () => {
            showDraftSalesModal.value = true
        }

        const handleDraftLoaded = (draft) => {
            // Navigate to the POS page with the draft ID
            window.location.href = `/sale/pos/${draft.id}`
        }

        const closeDraftSalesModal = () => {
            showDraftSalesModal.value = false
        }

        const openFeedback = () => {
            if (!props.canFeedback) {
                toast.error('You do not have permission to access feedback')
                return
            }
            showFeedbackModal.value = true
        }

        const closeFeedbackModal = () => {
            showFeedbackModal.value = false
        }

        const handleFeedbackSubmitted = (feedbackData) => {
            // Add feedback data to the form so it's submitted with the sale
            form.rating = feedbackData.rating;
            form.feedback_type = feedbackData.feedback_type;
            form.feedback = feedbackData.feedback;

            toast.success('Thank you for your feedback!')
        }

        const saveDraft = () => {
            processSale('draft')
        }

        const submitSale = () => {
            if (Object.keys(form.items).length === 0) {
                toast.error('Please add at least one item to cart')
                return
            }

            // Prepare confirmation data
            updateConfirmationData()

            // Show the confirmation modal instead of directly submitting
            showConfirmationModal.value = true
        }

        // Unified function to handle both draft save and sale submission
        const processSale = async (status = 'completed') => {
            submitting.value = true

            try {
                // For draft, we don't need all validations
                // Ensure all items have required fields for completed sales
                const validItems = {};
                let hasInvalidItems = false;

                Object.entries(form.items).forEach(([key, item]) => {
                    // Validate each item
                    if (!item.inventory_id || !item.product_id || !item.employee_id) {
                        hasInvalidItems = true;
                        return;
                    }

                    // Convert numbers to ensure proper format
                    validItems[key] = {
                        ...item,
                        unit_price: Number(item.unit_price) || 0,
                        quantity: Number(item.quantity) || 1,
                        discount: Number(item.discount) || 0,
                        tax: Number(item.tax) || 0,
                    };
                });

                if (hasInvalidItems) {
                    throw new Error('Some items have missing required fields. Please check the cart items.');
                }

                if (Object.keys(form.items).length === 0) {
                    throw new Error('Cart is empty. Please add items before submitting.');
                }

                // Prepare form data for submission
                const formData = {
                    id: form.id,
                    date: form.date,
                    employee_id: form.employee_id,
                    sale_type: form.sale_type,
                    account_id: form.account_id || null,
                    customer_mobile: form.customer_mobile,
                    other_discount: Number(form.other_discount) || 0,
                    round_off: Number(form.round_off) || 0,

                    gross_amount: Number(form.gross_amount) || 0,
                    item_discount: Number(form.item_discount) || 0,
                    tax_amount: Number(form.tax_amount) || 0,

                    total: Number(form.total) || 0,
                    grand_total: Number(form.grand_total) || 0,
                    items: validItems,
                    comboOffers: form.comboOffers || [],
                    payment_method: form.payment_method,
                    custom_payment_data: form.payment_method === 'custom' ?
                        (customPaymentData.value && customPaymentData.value.payments && customPaymentData.value.payments.length > 0 ?
                            customPaymentData.value : {
                                payments: []
                            }) : {
                            payments: []
                        },
                    send_to_whatsapp: sendToWhatsapp.value || false,
                    rating: Number(form.rating) || 0,
                    feedback_type: form.feedback_type || null,
                    feedback: form.feedback || null,
                    status: status
                };

                // Send data to server
                const response = await axios.post('/pos/submit', formData, {
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    timeout: 30000
                });

                submitting.value = false;

                if (status === 'draft') {
                    toast.success('Sale saved as draft');
                } else {
                    showConfirmationModal.value = false;
                    toast.success('Sale submitted successfully');
                    if (response.data && response.data.sale_id) {
                        const printUrl = `/print/sale/invoice/${response.data.sale_id}`;
                        const printWindow = window.open(printUrl, '_blank');
                        if (!printWindow) {
                            toast.error('Popup blocked. Please allow popups for this site.');
                        }
                    }
                }
                // Reset form or redirect for completed sales (with delay to allow print window to open)
                setTimeout(() => {
                    if (response.data && response.data.redirect) {
                        window.location.href = response.data.redirect;
                    } else {
                        // Reset form for new sale
                        form.reset();
                        form.items = {};
                        calculateTotals();
                        customPaymentData.value = {
                            payments: [],
                            totalPaid: 0,
                            balanceDue: 0
                        };
                        selectedPaymentMethod.value = 1;
                    }
                }, 1000); // 1 second delay to ensure print window opens first
            } catch (error) {
                // Get detailed error message from response if available
                let errorMessage = status === 'draft' ? 'Failed to save draft' : 'Failed to submit sale';

                if (error.response) {
                    errorMessage = error.response.data.message || error.response.data.error || errorMessage;

                    // Handle validation errors
                    if (error.response.data.errors) {
                        const validationErrors = error.response.data.errors;
                        const firstError = Object.values(validationErrors)[0];
                        if (firstError && firstError[0]) {
                            errorMessage = firstError[0];
                        }
                    }
                } else if (error.message) {
                    errorMessage = error.message;
                }

                submitting.value = false;
                toast.error(errorMessage);
            }
        }

        // This function gets called when user confirms in the modal
        const processSubmitSale = () => {
            processSale('completed')
        }

        // Helper function to find customer by ID
        const findCustomerById = (id) => {
            if (!id) return null

            // Try both string and number keys
            const stringId = String(id)
            const numberId = Number(id)

            // Check server customers first
            const serverCustomer = serverCustomers.value[stringId] || serverCustomers.value[numberId]
            if (serverCustomer) return serverCustomer

            // Fallback to props customers
            const propsCustomer = props.customers?.[stringId] || props.customers?.[numberId]
            return propsCustomer || null
        }

        // Helper function to normalize customer data
        const normalizeCustomerData = (customer) => {
            if (!customer) return {
                name: 'Walk-in Customer',
                mobile: ''
            }

            if (typeof customer === 'string') {
                const parts = customer.includes(' - ') ? customer.split(' - ') : [customer, '']
                return {
                    name: parts[0].trim(),
                    mobile: parts[1]?.trim() || ''
                }
            }

            return {
                name: customer.name || 'Unknown Customer',
                mobile: customer.mobile || customer.phone || ''
            }
        }

        // Prepare data for the confirmation modal
        const updateConfirmationData = () => {
            const customer = findCustomerById(form.account_id)
            const customerDetails = normalizeCustomerData(customer)

            // Create a plain object from the form data for the confirmation modal
            const formData = {
                employee_id: form.employee_id,
                sale_type: form.sale_type,
                account_id: form.account_id,
                customer_mobile: form.customer_mobile,
                other_discount: form.other_discount,
                round_off: form.round_off,
                total: form.total,
                grand_total: form.grand_total,
                items: form.items,
                payment_method: form.payment_method
            };

            confirmationData.value = {
                ...formData,
                customerName: customerDetails,
                custom_payment_data: customPaymentData.value
            }
        }

        const closeConfirmationModal = () => {
            showConfirmationModal.value = false
        }

        // Helper function to clear form for new sale
        const startNewSale = () => {
            // Reset form to default values
            form.reset()
            form.items = {}
            form.comboOffers = []
            form.date = new Date().toISOString().split('T')[0]
            form.employee_id = ''
            form.sale_type = 'normal'
            form.account_id = 3
            form.customer_mobile = ''
            form.other_discount = 0
            form.round_off = 0
            form.total = 0
            form.grand_total = 0
            form.payment_method = 1

            // Reset payment method
            selectedPaymentMethod.value = 1
            customPaymentData.value = {
                payments: [],
                totalPaid: 0,
                balanceDue: 0
            }

            calculateTotals()
            toast.success('Ready for new sale')
        }

        // Combo Offer Methods
        const manageComboOffer = () => {
            if (Object.keys(form.items).length === 0) {
                toast.error('Please add items to cart before managing combo offers')
                return
            }
            showComboOfferModal.value = true
        }

        const closeComboOfferModal = () => {
            showComboOfferModal.value = false
        }

        const openComboOfferSettings = () => {
            // This would typically open the combo offer management page
            // For now, we'll show a toast and close the modal
            toast.info('Combo offer settings would open here')
            closeComboOfferModal()
        }

        const handleComboOfferSave = (comboData) => {
            // Get all item keys that are in combo offers
            const comboOfferItemKeys = new Set(Object.keys(comboData.comboOfferItems))
            // Reset combo offer prices for items no longer in any combo offer
            // This updates items in place to maintain reactivity
            resetComboOfferPricing(form.items, comboOfferItemKeys)

            // Apply combo offer pricing to cart items
            // This updates items in place to maintain reactivity
            applyComboOfferPricing(form.items, comboData.comboOfferItems)

            // Store combo offers data for submission
            form.comboOffers = comboData.selectedComboOffers

            // Recalculate totals - this will use the updated combo offer prices
            calculateTotals()
            toast.success('Combo offers applied successfully')
        }

        const formatNumber = (value) => {
            const num = parseFloat(value) || 0
            return num.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })
        }

        const convertDiscountToPercentage = () => {
            // Get the current value in the discount field
            const currentValue = parseFloat(form.other_discount) || 0

            if (currentValue > 0) {
                // Validate that percentage doesn't exceed 100
                if (currentValue > 100) {
                    toast.error('Discount percentage cannot exceed 100%')
                    return
                }

                // Calculate the total before discount
                const totalBeforeDiscount = parseFloat(form.total) || 0

                if (totalBeforeDiscount > 0) {
                    // Convert percentage to actual discount amount
                    const discountAmount = (currentValue / 100) * totalBeforeDiscount
                    form.other_discount = parseFloat(discountAmount.toFixed(2))

                    // Recalculate totals
                    calculateTotals()

                    toast.success(`Converted ${currentValue}% to ${discountAmount.toFixed(2)} discount`)
                } else {
                    toast.error('No items in cart to calculate discount percentage')
                }
            } else {
                toast.error('Please enter a percentage value first')
            }
        }


        // Debounce function to limit the rate of function calls
        function debounce(func, wait) {
            let timeout
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout)
                    func(...args)
                }
                clearTimeout(timeout)
                timeout = setTimeout(later, wait)
            }
        }
        // Watchers
        watch(() => form.other_discount, () => {
            calculateTotals()
        })

        // Watch for customer selection to auto-populate mobile
        watch(() => form.account_id, (newCustomerId) => {
            if (newCustomerId) {
                const customer = findCustomerById(newCustomerId)
                if (customer) {
                    const normalized = normalizeCustomerData(customer)
                    form.customer_mobile = normalized.mobile
                }
                // Check if customer has feedbacks
                checkCustomerFeedbacks(newCustomerId)
            } else {
                form.customer_mobile = ''
                hasCustomerFeedbacks.value = false
            }
        }, {
            immediate: true
        })

        // Watch for payment method changes to update confirmation modal
        watch(selectedPaymentMethod, () => {
            updateConfirmationData()
        })

        // Watch for custom payment data changes to update confirmation modal
        watch(customPaymentData, () => {
            updateConfirmationData()
        }, {
            deep: true
        })

        // Lifecycle
        onMounted(() => {
            // Initialize form with sale data if provided from controller FIRST
            initializeFormWithSaleData()

            loadProducts()
            fetchCustomers()

            // Handle viewport height and window width tracking
            const setVH = () => {
                const vh = window.innerHeight * 0.01
                document.documentElement.style.setProperty('--vh', `${vh}px`)
                windowWidth.value = window.innerWidth
            }

            setVH()
            window.addEventListener('resize', setVH)
            window.addEventListener('orientationchange', setVH)
        })

        onUnmounted(() => {
            const setVH = () => {
                const vh = window.innerHeight * 0.01
                document.documentElement.style.setProperty('--vh', `${vh}px`)
                windowWidth.value = window.innerWidth
            }

            window.removeEventListener('resize', setVH)
            window.removeEventListener('orientationchange', setVH)
        })

        const cartHeight = computed(() => {
            if (windowWidth.value >= 1024) {
                // Desktop: Take full height minus header, footer and padding
                return 'calc(100vh - 150px)'
            } else if (windowWidth.value >= 768) {
                // Tablet: Medium height
                return 'calc(60vh - 120px)'
            } else {
                // Mobile: Optimized height for better visibility
                return 'calc(380px - 140px)'
            }
        })

        const formattedCustomers = computed(() => {
            const customersSource = serverCustomers.value || {}

            return Object.entries(customersSource).map(([id, customer]) => {
                const customerData = normalizeCustomerData(customer)
                return {
                    value: parseInt(id),
                    label: `${customerData.name} - ${customerData.mobile}`,
                    name: customerData.name,
                    mobile: customerData.mobile
                }
            })
        })
        return {
            // Reactive data
            loading,
            products,
            serverCustomers,
            loadingCustomers,
            selectedCategory,
            productKey,
            barcodeKey,
            selectedPaymentMethod,
            sendToWhatsapp,
            showCartModal,
            showCustomerModal,
            showCustomPaymentModal,
            showDraftSalesModal,
            showFeedbackModal,
            showConfirmationModal,
            submitting,
            confirmationData,
            customPaymentData,
            form,
            newCustomer,
            windowWidth,
            showEditItemModal,
            editItemKey,
            editItemData,
            selectedProductType,
            productTypeOptions,
            showComboOfferModal,
            showCustomerDetailsModal,
            selectedCustomerId,
            hasCustomerFeedbacks,
            employeeSelectRef,

            // Computed
            totalQuantity,
            discountPercentage,
            cartItemsByEmployee,
            cartHeight,
            formattedCustomers,

            // Methods
            selectCategory,
            fetchCustomers,
            loadProducts,
            searchCustomers,
            searchProducts,
            searchByBarcode,
            addProductToCart,
            updateItemQuantity,
            removeCartItem,
            updateItemField,
            increaseQuantity,
            decreaseQuantity,
            selectPaymentMethod,
            calculateTotals,
            clearCart,
            viewCartItems,
            editCartItem,
            saveEditedItem,
            addNewCustomer,
            viewCustomerDetails,
            handleCustomerEdit,
            handleCustomerChange,
            handleCustomerSaved,
            handleCustomerSelected,
            checkCustomerFeedbacks,
            handleCustomPaymentSave,
            closeCustomPaymentModal,
            viewDraftSales,
            handleDraftLoaded,
            closeDraftSalesModal,
            openFeedback,
            closeFeedbackModal,
            handleFeedbackSubmitted,
            // Exposed methods and data
            updateConfirmationData,
            closeConfirmationModal,
            processSubmitSale,
            saveDraft,
            submitSale,
            onEditItemSave,
            startNewSale,
            filterByProductType,
            manageComboOffer,
            closeComboOfferModal,
            openComboOfferSettings,
            handleComboOfferSave,
            convertDiscountToPercentage,
            formatNumber
        }
    }
}
</script>

<style scoped>
@import '../../../css/pos-common.css';
@import '../../../css/pos.css';
@import '../../../css/mobile-responsive.css';
@import '../../../css/pos-enhanced-responsive.css';

.payment-modal {
    position: fixed;
    z-index: 50;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.modal-overlay {
    position: fixed;
    z-index: 40;
    inset: 0;
    background-color: rgba(0, 0, 0, 0.5);
}

.button-glow {
    animation: glow-pulse 2s ease-in-out infinite;
    box-shadow: 0 0 10px rgba(168, 85, 247, 0.6), 0 0 20px rgba(236, 72, 153, 0.4), 0 0 30px rgba(244, 63, 94, 0.3);
}

@keyframes glow-pulse {
    0%, 100% {
        box-shadow: 0 0 10px rgba(168, 85, 247, 0.6), 0 0 20px rgba(236, 72, 153, 0.4), 0 0 30px rgba(244, 63, 94, 0.3);
    }
    50% {
        box-shadow: 0 0 15px rgba(168, 85, 247, 0.8), 0 0 30px rgba(236, 72, 153, 0.6), 0 0 45px rgba(244, 63, 94, 0.5);
    }
}
</style>
