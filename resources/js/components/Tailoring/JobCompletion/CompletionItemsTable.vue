<template>
    <div class="flex flex-col gap-2 completion-items-table ultra-compact">
        <div
            class="rounded-xl md:rounded-2xl overflow-hidden shadow-md border border-slate-200/80 bg-gradient-to-br from-slate-50 to-white">
            <div class="p-2.5 sm:p-3 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2">
                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                    <div
                        class="shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-blue-100 flex items-center justify-center text-blue-700 shadow-sm">
                        <i class="fa fa-tasks text-sm sm:text-base"></i>
                    </div>
                    <div class="min-w-0">
                        <h3
                            class="flex items-center gap-1.5 text-xs sm:text-sm font-bold text-slate-800 leading-tight truncate">
                            <i class="fa fa-clipboard text-blue-600"></i> Job Completion Items
                        </h3>
                        <p class="flex items-center gap-1 text-slate-600 text-[11px] font-medium"><i
                                class="fa fa-list text-slate-500"></i> {{ items.length }} items total</p>
                    </div>
                </div>
                <div
                    class="flex items-center justify-between sm:justify-end gap-2 border-t sm:border-t-0 border-slate-200 pt-2 sm:pt-0">
                    <span class="flex items-center gap-1 text-[11px] font-semibold text-slate-600"><i
                            class="fa fa-check-square-o text-blue-600"></i> Select All</span>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0">
                        <input type="checkbox" @change="handleSelectAll" :checked="allSelected" class="sr-only peer">
                        <div
                            class="w-11 h-6 bg-slate-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600 shadow-inner">
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <div v-if="items.length === 0"
            class="rounded-2xl border-2 border-dashed border-slate-300 py-10 text-center bg-slate-50/80">
            <div class="flex flex-col items-center gap-3">
                <i class="fa fa-search text-5xl text-slate-500"></i>
                <h5 class="text-sm font-bold text-slate-700">No items found</h5>
                <p class="text-sm text-slate-600">Search for an order to see completion items</p>
            </div>
        </div>

        <div v-else class="rounded-2xl overflow-hidden shadow-md border border-slate-200/80 bg-white">
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[700px] lg:min-w-[980px]">
                    <colgroup>
                        <col class="w-12">
                        <col class="col-item-details">
                        <col class="col-material">
                        <col class="col-actions">
                    </colgroup>
                    <thead>
                        <tr class="bg-slate-100 border-b-2 border-slate-200">
                            <th class="px-2.5 py-2 w-12"><input type="checkbox" @change="handleSelectAll"
                                    :checked="allSelected" class="w-4 h-4 rounded border-slate-400 text-blue-600"></th>
                            <th class="px-2.5 py-2 text-[11px] font-bold text-slate-700 uppercase tracking-wide"><i
                                    class="fa fa-list text-blue-600"></i> Item Details</th>
                            <th class="px-2.5 py-2 text-[11px] font-bold text-slate-700 uppercase tracking-wide"><i
                                    class="fa fa-building text-blue-600"></i> Material</th>
                            <th
                                class="px-2.5 py-2 text-[11px] font-bold text-slate-700 uppercase tracking-wide text-center">
                                <i class="fa fa-cogs text-blue-600"></i> Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-slate-50/30">
                        <template v-for="item in items" :key="item.id">
                            <tr class="hover:bg-slate-100/60"
                                :class="{ 'bg-blue-50/60': item.is_selected_for_completion }">
                                <td class="px-2.5 py-2 align-top">
                                    <input type="checkbox" :checked="item.is_selected_for_completion"
                                        @change="toggleItemCompletion(item, $event)"
                                        class="w-4 h-4 rounded border-slate-400 text-blue-600">
                                </td>
                                <td class="px-2.5 py-2 align-top">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span
                                            class="text-[11px] font-bold text-blue-700 bg-blue-100 px-1.5 py-0.5 rounded-md border border-blue-200">#{{
                                                item.item_no }}</span>
                                        <span
                                            class="text-[11px] font-semibold text-slate-600 uppercase tracking-wide">{{
                                                item.category?.name }}</span>
                                    </div>
                                    <div class="text-[13px] font-bold text-slate-800">{{ item.product_name }}</div>
                                    <div class="text-[11px] text-slate-600 mt-1">Qty: <strong>{{ Number(item.quantity)
                                    }}</strong> | Price: <strong class="text-blue-700">{{
                                                formatCurrency(item.total) }}</strong></div>
                                    <div class="text-[11px] font-bold text-blue-700 mt-1">
                                        Completed Units: {{ Number(item.completed_quantity ?? 0) }} / {{
                                            getUnitCount(item) }}
                                        <span class="mx-1">|</span>
                                        Total Commission: {{ formatCurrency(item.tailor_total_commission) }}
                                    </div>
                                    <div class="text-[11px] text-slate-600 mt-1">
                                        <i class="fa fa-check-circle text-emerald-600 mr-1"></i>
                                        Completion: <strong>{{ item.completion_status || 'not completed' }}</strong>
                                        <span class="mx-1">|</span>
                                        <i class="fa fa-truck text-sky-600 mr-1"></i>
                                        Delivery: <strong>{{ item.delivery_status || 'not delivered' }}</strong>
                                    </div>
                                    <button @click="viewMeasurements(item)"
                                        class="mt-1.5 inline-flex items-center gap-1 text-amber-600 hover:text-amber-700 font-semibold uppercase tracking-wide text-[11px] py-1 px-1.5 rounded-md hover:bg-amber-50 border border-amber-200/60">
                                        <i class="fa fa-eye"></i> Measurements
                                    </button>
                                </td>
                                <td class="px-2.5 py-2 align-top min-w-[210px] md:min-w-[220px]">
                                    <div
                                        class="rounded-xl p-2 border border-slate-200 bg-white shadow-sm min-w-[210px]">
                                        <div class="flex items-center justify-between mb-1.5">
                                            <span
                                                class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-700"><i
                                                    class="fa fa-cubes text-slate-500"></i>Current Stock</span>
                                            <span class="text-[13px] font-bold text-slate-800">{{
                                                (item.inventory?.quantity ?? 0).toFixed(3) }}</span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <span
                                                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-700"><i
                                                        class="fa fa-scissors text-slate-500"></i>Used</span>
                                                <input v-model.number="item.used_quantity"
                                                    @input="calculateStockBalance(item)" type="number" step="0.001"
                                                    min="0"
                                                    class="input-field w-full text-sm py-1.5 px-2.5 rounded-lg border border-slate-300 bg-slate-50 text-slate-800" />
                                            </div>
                                            <div>
                                                <span
                                                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-700"><i
                                                        class="fa fa-trash text-slate-500"></i>Waste</span>
                                                <input v-model.number="item.wastage"
                                                    @input="calculateStockBalance(item)" type="number" step="0.001"
                                                    min="0"
                                                    class="input-field w-full text-sm py-1.5 px-2.5 rounded-lg border border-slate-300 bg-slate-50 text-slate-800" />
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-2.5 py-2 align-top min-w-[130px] md:min-w-[140px]">
                                    <div class="flex flex-col gap-1.5 items-center">
                                        <button @click="toggleExpand(item.id)"
                                            class="px-2.5 py-1.5 rounded-lg border border-slate-300 text-slate-700 bg-white text-[11px] font-semibold hover:bg-slate-50 w-[112px] md:w-[160px] lg:w-[210px]">
                                            <i class="fa"
                                                :class="isExpanded(item.id) ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                            {{ isExpanded(item.id) ? 'Collapse' : 'Expand' }}
                                        </button>
                                        <button @click="saveItem(item)"
                                            class="px-3 py-2 rounded-lg bg-blue-600 text-white font-bold text-[11px] uppercase tracking-wide shadow-md hover:bg-blue-700 w-[112px] md:w-[160px] lg:w-[210px]">
                                            <i class="fa fa-save mr-1"></i> Save Job
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="isExpanded(item.id)" class="bg-white border-b border-slate-200"
                                :class="{ 'bg-blue-50/20': item.is_selected_for_completion }">
                                <td></td>
                                <td colspan="3" class="px-2.5 pb-2.5">
                                    <div
                                        class="rounded-2xl border border-blue-200/80 bg-gradient-to-br from-blue-50/80 to-slate-50 p-1.5 sm:p-2">
                                        <div
                                            class="flex items-center gap-2 text-[11px] font-bold text-blue-800 uppercase tracking-wide mb-2">
                                            <i class="fa fa-table"></i>
                                            Tailor Assignment
                                        </div>

                                        <div v-if="getAssignments(item).length >= 2"
                                            class="rounded-xl border border-emerald-300/80 bg-emerald-50/80 px-2 py-1.5 mb-1.5 shadow-sm">
                                            <div
                                                class="text-[11px] font-bold text-emerald-900 mb-2 flex items-center gap-1.5">
                                                <i class="fa fa-clone"></i>
                                                Apply Same Values To All Rows
                                            </div>
                                            <div
                                                class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-1.5 items-end">
                                                <div>
                                                    <label class="text-xs font-semibold text-slate-700"><i
                                                            class="fa fa-user text-slate-500 mr-1"></i>Tailor</label>
                                                    <VSelect :modelValue="getBulk(item).tailor_id"
                                                        @update:modelValue="getBulk(item).tailor_id = $event"
                                                        :options="tailorOptions" placeholder="Select tailor"
                                                        class="compact-vselect" />
                                                </div>
                                                <div>
                                                    <label class="text-xs font-semibold text-slate-700"><i
                                                            class="fa fa-money text-slate-500 mr-1"></i>Commission</label>
                                                    <input v-model.number="getBulk(item).tailor_commission"
                                                        type="number" step="0.01" min="0"
                                                        class="input-field w-full text-sm py-1.5 px-2.5 rounded-lg border border-slate-300 bg-slate-50 text-slate-800" />
                                                </div>
                                                <div>
                                                    <label class="text-xs font-semibold text-slate-700"><i
                                                            class="fa fa-calendar text-slate-500 mr-1"></i>Completion
                                                        Date</label>
                                                    <input v-model="getBulk(item).completion_date" type="date"
                                                        class="input-field w-full text-sm py-1.5 px-2.5 rounded-lg border border-slate-300 bg-slate-50 text-slate-800" />
                                                </div>
                                                <div>
                                                    <label class="text-xs font-semibold text-slate-700"><i
                                                            class="fa fa-star text-amber-500 mr-1"></i>Rating</label>
                                                    <StarRating :modelValue="getBulk(item).rating" size="sm"
                                                        @update:modelValue="getBulk(item).rating = $event" />
                                                </div>
                                                <div>
                                                    <label class="text-xs font-semibold text-slate-700"><i
                                                            class="fa fa-flag text-slate-500 mr-1"></i>Status</label>
                                                    <VSelect :modelValue="getBulk(item).status"
                                                        @update:modelValue="getBulk(item).status = $event || ''"
                                                        :options="assignmentStatusOptions" placeholder="Select status"
                                                        class="compact-vselect" />
                                                </div>
                                                <div>
                                                    <button @click="applyBulkToAll(item)"
                                                        class="px-2.5 py-1.5 rounded-lg bg-emerald-600 text-white text-[11px] font-bold hover:bg-emerald-700 w-full">Apply
                                                        To All</button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="space-y-1.5">
                                            <div v-for="(assignment, index) in getAssignments(item)"
                                                :key="assignment.id || `row-${item.id}-${index}`"
                                                class="rounded-xl border border-blue-200/90 bg-white/95 px-2 py-1.5 shadow-sm">
                                                <div class="text-[10px] font-bold text-blue-700 mb-1">Piece #{{ index +
                                                    1 }}</div>
                                                <div
                                                    class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-1.5 items-end">
                                                    <div>
                                                        <label class="text-xs font-semibold text-slate-700"><i
                                                                class="fa fa-user text-slate-500 mr-1"></i>Assign
                                                            Tailor</label>
                                                        <VSelect :modelValue="assignment.tailor_id"
                                                            @update:modelValue="updateAssignmentField(item, index, 'tailor_id', $event)"
                                                            :options="tailorOptions" placeholder="Select tailor"
                                                            class="compact-vselect" />
                                                    </div>
                                                    <div>
                                                        <label class="text-xs font-semibold text-slate-700"><i
                                                                class="fa fa-money text-slate-500 mr-1"></i>Commission /
                                                            Piece</label>
                                                        <input :value="assignment.tailor_commission"
                                                            @input="updateAssignmentField(item, index, 'tailor_commission', $event.target.value)"
                                                            type="number" step="0.01" min="0"
                                                            class="input-field w-full text-sm py-1.5 px-2.5 rounded-lg border border-slate-300 bg-slate-50 text-slate-800" />
                                                    </div>
                                                    <div>
                                                        <label class="text-xs font-semibold text-slate-700"><i
                                                                class="fa fa-calendar text-slate-500 mr-1"></i>Completion
                                                            Date</label>
                                                        <input :value="assignment.completion_date"
                                                            @input="updateAssignmentField(item, index, 'completion_date', $event.target.value)"
                                                            type="date"
                                                            class="input-field w-full text-sm py-1.5 px-2.5 rounded-lg border border-slate-300 bg-slate-50 text-slate-800" />
                                                    </div>
                                                    <div>
                                                        <label class="text-xs font-semibold text-slate-700"><i
                                                                class="fa fa-star text-amber-500 mr-1"></i>Rating</label>
                                                        <StarRating class="mt-1" :modelValue="assignment.rating"
                                                            size="sm"
                                                            @update:modelValue="updateAssignmentField(item, index, 'rating', $event)" />
                                                    </div>
                                                    <div>
                                                        <label class="text-xs font-semibold text-slate-700"><i
                                                                class="fa fa-flag text-slate-500 mr-1"></i>Status</label>
                                                        <VSelect :modelValue="assignment.status"
                                                            @update:modelValue="updateAssignmentField(item, index, 'status', $event || 'pending')"
                                                            :options="assignmentStatusOptions"
                                                            placeholder="Select status" class="compact-vselect" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="md:hidden p-2 space-y-2">
                <div v-for="item in items" :key="`mobile-${item.id}`"
                    class="rounded-xl border border-slate-200 bg-slate-50/40 p-2"
                    :class="{ 'bg-blue-50/60 border-blue-200': item.is_selected_for_completion }">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex items-start gap-2">
                            <input type="checkbox" :checked="item.is_selected_for_completion"
                                @change="toggleItemCompletion(item, $event)"
                                class="w-4 h-4 mt-0.5 rounded border-slate-400 text-blue-600 shrink-0">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span
                                        class="text-[11px] font-bold text-blue-700 bg-blue-100 px-1.5 py-0.5 rounded border border-blue-200">#{{
                                            item.item_no }}</span>
                                    <span
                                        class="text-[10px] font-semibold text-slate-600 uppercase tracking-wide truncate">{{
                                            item.category?.name }}</span>
                                </div>
                                <div class="text-sm font-bold text-slate-800 leading-tight">{{ item.product_name }}
                                </div>
                            </div>
                        </div>
                        <button @click="toggleExpand(item.id)"
                            class="px-2.5 py-1 rounded-lg border border-slate-300 text-slate-700 bg-white text-[11px] font-semibold hover:bg-slate-50 shrink-0">
                            <i class="fa" :class="isExpanded(item.id) ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                        </button>
                    </div>

                    <div class="mt-2 text-[11px] text-slate-600">
                        Qty: <strong>{{ Number(item.quantity) }}</strong>
                        <span class="mx-1">|</span>
                        Price: <strong class="text-blue-700">{{ formatCurrency(item.total) }}</strong>
                    </div>
                    <div class="text-[11px] font-bold text-blue-700 mt-1">
                        Completed: {{ Number(item.completed_quantity ?? 0) }} / {{ getUnitCount(item) }}
                        <span class="mx-1">|</span>
                        Commission: {{ formatCurrency(item.tailor_total_commission) }}
                    </div>
                    <div class="text-[11px] text-slate-600 mt-1">
                        <i class="fa fa-check-circle text-emerald-600 mr-1"></i>
                        <strong>{{ item.completion_status || 'not completed' }}</strong>
                        <span class="mx-1">|</span>
                        <i class="fa fa-truck text-sky-600 mr-1"></i>
                        <strong>{{ item.delivery_status || 'not delivered' }}</strong>
                    </div>

                    <div class="mt-2 rounded-lg p-2 border border-slate-200 bg-white">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-slate-700"><i
                                    class="fa fa-cubes text-slate-500"></i>Current Stock</span>
                            <span class="text-sm font-bold text-slate-800">{{ (item.inventory?.quantity ?? 0).toFixed(3)
                            }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-slate-700"><i
                                        class="fa fa-scissors text-slate-500"></i>Used</span>
                                <input v-model.number="item.used_quantity" @input="calculateStockBalance(item)"
                                    type="number" step="0.001" min="0"
                                    class="input-field w-full text-sm py-1.5 px-2 rounded-lg border border-slate-300 bg-slate-50 text-slate-800" />
                            </div>
                            <div>
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-slate-700"><i
                                        class="fa fa-trash text-slate-500"></i>Waste</span>
                                <input v-model.number="item.wastage" @input="calculateStockBalance(item)" type="number"
                                    step="0.001" min="0"
                                    class="input-field w-full text-sm py-1.5 px-2 rounded-lg border border-slate-300 bg-slate-50 text-slate-800" />
                            </div>
                        </div>
                    </div>

                    <div v-if="isExpanded(item.id)"
                        class="mt-2 rounded-xl border border-blue-200/80 bg-gradient-to-br from-blue-50/80 to-slate-50 p-2">
                        <div
                            class="flex items-center gap-2 text-[11px] font-bold text-blue-800 uppercase tracking-wide mb-2">
                            <i class="fa fa-table"></i>
                            Tailor Assignment
                        </div>

                        <div v-if="getAssignments(item).length >= 2"
                            class="rounded-lg border border-emerald-300/80 bg-emerald-50/80 px-2.5 py-2 mb-2 shadow-sm">
                            <div class="text-[11px] font-bold text-emerald-900 mb-2 flex items-center gap-1.5">
                                <i class="fa fa-clone"></i>
                                Apply Same Values
                            </div>
                            <div class="grid grid-cols-1 gap-2">
                                <div>
                                    <label class="text-xs font-semibold text-slate-700"><i
                                            class="fa fa-user text-slate-500 mr-1"></i>Tailor</label>
                                    <VSelect :modelValue="getBulk(item).tailor_id"
                                        @update:modelValue="getBulk(item).tailor_id = $event" :options="tailorOptions"
                                        placeholder="Select tailor" class="compact-vselect" />
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-xs font-semibold text-slate-700"><i
                                                class="fa fa-money text-slate-500 mr-1"></i>Commission</label>
                                        <input v-model.number="getBulk(item).tailor_commission" type="number"
                                            step="0.01" min="0"
                                            class="input-field w-full text-sm py-1.5 px-2 rounded-lg border border-slate-300 bg-slate-50 text-slate-800" />
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-700"><i
                                                class="fa fa-calendar text-slate-500 mr-1"></i>Date</label>
                                        <input v-model="getBulk(item).completion_date" type="date"
                                            class="input-field w-full text-sm py-1.5 px-2 rounded-lg border border-slate-300 bg-slate-50 text-slate-800" />
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-xs font-semibold text-slate-700"><i
                                                class="fa fa-star text-amber-500 mr-1"></i>Rating</label>
                                        <StarRating :modelValue="getBulk(item).rating" size="sm"
                                            @update:modelValue="getBulk(item).rating = $event" />
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-700"><i
                                                class="fa fa-flag text-slate-500 mr-1"></i>Status</label>
                                        <VSelect :modelValue="getBulk(item).status"
                                            @update:modelValue="getBulk(item).status = $event || ''"
                                            :options="assignmentStatusOptions" placeholder="Select status"
                                            class="compact-vselect" />
                                    </div>
                                </div>
                                <button @click="applyBulkToAll(item)"
                                    class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700 w-full">Apply
                                    To All</button>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div v-for="(assignment, index) in getAssignments(item)"
                                :key="assignment.id || `mobile-row-${item.id}-${index}`"
                                class="rounded-lg border border-blue-200/90 bg-white/95 px-2.5 py-2 shadow-sm">
                                <div class="text-[10px] font-bold text-blue-700 mb-1">Piece #{{ index + 1 }}</div>
                                <div class="grid grid-cols-1 gap-2">
                                    <div>
                                        <label class="text-xs font-semibold text-slate-700"><i
                                                class="fa fa-user text-slate-500 mr-1"></i>Assign Tailor</label>
                                        <VSelect :modelValue="assignment.tailor_id"
                                            @update:modelValue="updateAssignmentField(item, index, 'tailor_id', $event)"
                                            :options="tailorOptions" placeholder="Select tailor"
                                            class="compact-vselect" />
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="text-xs font-semibold text-slate-700"><i
                                                    class="fa fa-money text-slate-500 mr-1"></i>Commission</label>
                                            <input :value="assignment.tailor_commission"
                                                @input="updateAssignmentField(item, index, 'tailor_commission', $event.target.value)"
                                                type="number" step="0.01" min="0"
                                                class="input-field w-full text-sm py-1.5 px-2 rounded-lg border border-slate-300 bg-slate-50 text-slate-800" />
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold text-slate-700"><i
                                                    class="fa fa-calendar text-slate-500 mr-1"></i>Date</label>
                                            <input :value="assignment.completion_date"
                                                @input="updateAssignmentField(item, index, 'completion_date', $event.target.value)"
                                                type="date"
                                                class="input-field w-full text-sm py-1.5 px-2 rounded-lg border border-slate-300 bg-slate-50 text-slate-800" />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-700"><i
                                                class="fa fa-star text-amber-500 mr-1"></i>Rating</label>
                                        <StarRating class="mt-1" :modelValue="assignment.rating" size="sm"
                                            @update:modelValue="updateAssignmentField(item, index, 'rating', $event)" />
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-700"><i
                                                class="fa fa-flag text-slate-500 mr-1"></i>Status</label>
                                        <VSelect :modelValue="assignment.status"
                                            @update:modelValue="updateAssignmentField(item, index, 'status', $event || 'pending')"
                                            :options="assignmentStatusOptions" placeholder="Select status"
                                            class="compact-vselect" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-2 flex gap-2">
                        <button @click="viewMeasurements(item)"
                            class="flex-1 inline-flex items-center justify-center gap-1 text-amber-600 hover:text-amber-700 font-semibold uppercase tracking-wide text-[11px] py-1.5 px-2 rounded-md hover:bg-amber-50 border border-amber-200/60">
                            <i class="fa fa-eye"></i> Measurements
                        </button>
                        <button @click="saveItem(item)"
                            class="flex-1 px-3 py-1.5 rounded-lg bg-blue-600 text-white font-bold text-[11px] uppercase tracking-wide shadow-md hover:bg-blue-700">
                            <i class="fa fa-save mr-1"></i> Save Job
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <MeasurementViewModal v-if="selectedItemForView" :show="showViewModal" :item="selectedItemForView"
            @close="closeViewModal" />
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import MeasurementViewModal from '@/components/Tailoring/MeasurementViewModal.vue'
import VSelect from '@/components/VSelect.vue'
import StarRating from '@/components/Tailoring/StarRating.vue'

const props = defineProps({
    items: {
        type: [Array, Object],
        default: () => []
    },
    tailors: {
        type: [Array, Object],
        default: () => ({})
    },
})

const emit = defineEmits(['update-item'])

const items = computed(() => Array.isArray(props.items) ? props.items : [])
const tailors = computed(() => {
    if (Array.isArray(props.tailors)) return props.tailors
    if (props.tailors && typeof props.tailors === 'object') return props.tailors
    return []
})

const tailorOptions = computed(() => {
    if (Array.isArray(tailors.value)) {
        return tailors.value.map((tailor) => ({
            value: tailor?.value ?? tailor?.id,
            label: tailor?.label ?? tailor?.name ?? String(tailor?.id ?? ''),
        })).filter((option) => option.value !== undefined && option.value !== null && option.label)
    }

    return Object.entries(tailors.value || {}).map(([value, label]) => ({
        value,
        label: String(label),
    }))
})

const assignmentStatusOptions = [
    { value: 'pending', label: 'Pending' },
    { value: 'completed', label: 'Completed' },
    { value: 'delivered', label: 'Delivered' },
]

const selectedItemForView = ref(null)
const showViewModal = ref(false)
const expandedRows = ref({})
const bulkValues = ref({})
const itemsKey = ref('')

const viewMeasurements = (item) => {
    selectedItemForView.value = item
    showViewModal.value = true
}

const closeViewModal = () => {
    showViewModal.value = false
    selectedItemForView.value = null
}

const getUnitCount = (item) => Math.max(1, Math.round(Number(item?.quantity || 0)))

const normalizeAssignment = (item, source = null) => ({
    id: source?.id ?? null,
    tailoring_order_item_id: item.id,
    tailor_id: source?.tailor_id ?? null,
    tailor_commission: Number(source?.tailor_commission ?? 0),
    completion_date: source?.completion_date ?? null,
    rating: source?.rating ?? null,
    status: source?.status ?? 'pending',
})

const ensureAssignments = (item) => {
    const targetUnits = getUnitCount(item)

    let assignments = Array.isArray(item.tailor_assignments) && item.tailor_assignments.length > 0
        ? item.tailor_assignments
        : (item.tailor_assignment ? [item.tailor_assignment] : [])

    assignments = assignments.map((assignment) => normalizeAssignment(item, assignment))

    while (assignments.length < targetUnits) {
        assignments.push(normalizeAssignment(item))
    }

    if (assignments.length > targetUnits) {
        assignments = assignments.slice(0, targetUnits)
    }

    item.tailor_assignments = assignments
    return item.tailor_assignments
}

const getAssignments = (item) => Array.isArray(item?.tailor_assignments) ? item.tailor_assignments : []

const deriveCompletionStatus = (completedUnits, totalUnits) => {
    if (completedUnits <= 0) return 'not completed'
    if (completedUnits < totalUnits) return 'partially completed'
    return 'completed'
}

const deriveDeliveryStatus = (deliveredUnits, totalUnits) => {
    if (deliveredUnits <= 0) return 'not delivered'
    if (deliveredUnits < totalUnits) return 'partially delivered'
    return 'delivered'
}

const recalculateFromAssignments = (item) => {
    const assignments = ensureAssignments(item)
    const totalUnits = getUnitCount(item)

    const completedUnits = assignments.filter((assignment) => ['completed', 'delivered'].includes(assignment.status)).length
    const deliveredUnits = assignments.filter((assignment) => assignment.status === 'delivered').length
    const pendingUnits = Math.max(0, totalUnits - completedUnits)
    const totalCommission = assignments.reduce((sum, assignment) => sum + Number(assignment.tailor_commission || 0), 0)

    item.pending_quantity = Number(pendingUnits)
    item.completed_quantity = Number(completedUnits)
    item.delivered_quantity = Number(deliveredUnits)
    item.completion_status = deriveCompletionStatus(completedUnits, totalUnits)
    item.delivery_status = deriveDeliveryStatus(deliveredUnits, totalUnits)
    item.tailor_total_commission = Number(totalCommission.toFixed(2))

    const latest = assignments[assignments.length - 1] || null
    item.tailor_assignment = latest ? { ...latest } : null
    item.item_completion_date = latest?.completion_date ?? null
}

const initializeExpandedRows = (list) => {
    const next = {}
    list.forEach((item) => {
        next[item.id] = false
    })

    const firstPending = list.find((item) => Number(item.pending_quantity ?? 0) > 0)
    if (firstPending) {
        next[firstPending.id] = true
    } else if (list[0]) {
        next[list[0].id] = true
    }

    expandedRows.value = next
}

watch(() => items.value, (list) => {
    ; (list || []).forEach((item) => {
        ensureAssignments(item)
        recalculateFromAssignments(item)
        calculateStockBalance(item)
    })

    const newKey = (list || []).map((item) => item.id).join(',')
    if (newKey !== itemsKey.value) {
        initializeExpandedRows(list || [])
        itemsKey.value = newKey
    }
}, { immediate: true })

const allSelected = computed(() => items.value.length > 0 && items.value.every(item => item.is_selected_for_completion))

const toggleExpand = (itemId) => {
    expandedRows.value[itemId] = !expandedRows.value[itemId]
}

const isExpanded = (itemId) => !!expandedRows.value[itemId]

const getBulk = (item) => {
    if (!bulkValues.value[item.id]) {
        bulkValues.value[item.id] = {
            tailor_id: null,
            tailor_commission: null,
            completion_date: '',
            rating: null,
            status: '',
        }
    }

    return bulkValues.value[item.id]
}

const applyBulkToAll = (item) => {
    const bulk = getBulk(item)
    const assignments = ensureAssignments(item)

    assignments.forEach((assignment) => {
        if (bulk.tailor_id !== null && bulk.tailor_id !== '') assignment.tailor_id = bulk.tailor_id
        if (bulk.tailor_commission !== null && bulk.tailor_commission !== '') assignment.tailor_commission = Number(bulk.tailor_commission || 0)
        if (bulk.completion_date) assignment.completion_date = bulk.completion_date
        if (bulk.rating !== null && bulk.rating !== '') assignment.rating = Number(bulk.rating)
        if (bulk.status) assignment.status = bulk.status
    })

    recalculateFromAssignments(item)
}

const handleSelectAll = (event) => {
    const isChecked = event.target.checked
    const today = new Date().toISOString().split('T')[0]

    items.value.forEach((item) => {
        item.is_selected_for_completion = isChecked
        if (isChecked) {
            ensureAssignments(item).forEach((assignment) => {
                if (!assignment.completion_date) assignment.completion_date = today
            })
        }
        recalculateFromAssignments(item)
    })
}

const toggleItemCompletion = (item, event) => {
    const isChecked = event.target.checked
    item.is_selected_for_completion = isChecked

    if (isChecked) {
        const today = new Date().toISOString().split('T')[0]
        ensureAssignments(item).forEach((assignment) => {
            if (!assignment.completion_date) assignment.completion_date = today
        })
    }

    recalculateFromAssignments(item)
}

const updateAssignmentField = (item, index, key, value) => {
    const assignments = ensureAssignments(item)
    if (!assignments[index]) return

    if (key === 'tailor_commission') {
        assignments[index][key] = Number(value || 0)
    } else if (key === 'rating') {
        assignments[index][key] = value ? Number(value) : null
    } else {
        assignments[index][key] = value || null
    }

    recalculateFromAssignments(item)
}

function calculateStockBalance(item) {
    const stockQuantity = parseFloat(item.inventory?.quantity ?? 0)
    const usedQuantity = parseFloat(item.used_quantity || 0)
    const wastage = parseFloat(item.wastage || 0)

    item.total_quantity_used = usedQuantity + wastage
    item.stock_balance = stockQuantity - item.total_quantity_used
}

const saveItem = (item) => {
    calculateStockBalance(item)
    recalculateFromAssignments(item)

    emit('update-item', item.id, {
        ...item,
        tailor_assignments: ensureAssignments(item),
        tailor_assignment: item.tailor_assignment,
        pending_quantity: item.pending_quantity,
        completed_quantity: item.completed_quantity,
        delivered_quantity: item.delivered_quantity,
        completion_status: item.completion_status,
        delivery_status: item.delivery_status,
        is_selected_for_completion: item.is_selected_for_completion,
    })
}

const formatCurrency = (value) => parseFloat(value || 0).toFixed(2)
</script>

<style scoped>
.completion-items-table :deep(.input-field),
.completion-items-table input[type="number"],
.completion-items-table input[type="date"],
.completion-items-table select {
    color: #1e293b;
    font-size: 0.875rem;
}

.completion-items-table input:focus,
.completion-items-table select:focus {
    outline: none;
}

.completion-items-table .overflow-x-auto {
    -webkit-overflow-scrolling: touch;
}

.completion-items-table :deep(.compact-vselect .multiselect__tags) {
    min-height: 34px;
    padding: 5px 26px 5px 10px;
    border-radius: 0.65rem;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    font-size: 0.75rem;
    font-weight: 600;
}

.completion-items-table :deep(.compact-vselect .multiselect__select) {
    height: 32px;
    width: 28px;
}

.completion-items-table :deep(.compact-vselect .multiselect__single),
.completion-items-table :deep(.compact-vselect .multiselect__placeholder) {
    margin-bottom: 0;
}

.completion-items-table :deep(.compact-vselect .multiselect__content-wrapper) {
    border-radius: 0.75rem;
}

.completion-items-table .col-item-details {
    width: 46%;
}

.completion-items-table .col-material {
    width: 36%;
}

.completion-items-table .col-actions {
    width: 18%;
}

@media (max-width: 1024px) {
    .completion-items-table table {
        min-width: 760px;
    }

    .completion-items-table .col-item-details {
        width: 44%;
    }

    .completion-items-table .col-material {
        width: 34%;
    }

    .completion-items-table .col-actions {
        width: 22%;
    }
}

@media (max-width: 768px) {
    .completion-items-table table {
        min-width: 640px;
    }

    .completion-items-table .col-item-details {
        width: 42%;
    }

    .completion-items-table .col-material {
        width: 33%;
    }

    .completion-items-table .col-actions {
        width: 25%;
    }

    .completion-items-table :deep(.compact-vselect .multiselect__tags) {
        min-height: 32px;
        padding: 4px 24px 4px 8px;
        font-size: 0.7rem;
    }
}
</style>
