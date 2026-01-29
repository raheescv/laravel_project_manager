<template>
    <div class="flex flex-col gap-3">
        <div class="flex items-center gap-1.5 px-1 mb-0.5">
            <i class="fa fa-calculator text-blue-500 text-xs"></i>
            <h3 class="text-[0.65rem] font-bold text-slate-700 uppercase tracking-widest">Order Summary</h3>
        </div>

        <div class="space-y-2">
            <div v-for="group in groupedItems" :key="group.categoryId" 
                class="bg-white rounded-2xl p-3 shadow-sm border border-slate-200 transition-all hover:shadow-md hover:border-indigo-200 relative overflow-hidden group">
                <!-- Inner background tint -->
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-50/20 to-transparent pointer-events-none"></div>
                
                <div class="flex justify-between items-start relative z-10">
                    <div class="flex gap-2">
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-400 group-hover:bg-indigo-100 group-hover:text-indigo-600 transition-colors">
                            <i class="fa fa-tag text-xs"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-800 text-xs mb-0.5">{{ group.categoryName }}</h4>
                            <p class="text-[9px] text-slate-400 font-bold leading-none uppercase tracking-wider">
                                <span class="text-indigo-600">{{ group.quantity }}</span> {{ group.quantity === 1 ? 'Job' : 'Jobs' }}
                            </p>
                        </div>
                    </div>
                    <div class="text-end">
                        <p class="text-xs font-black text-slate-900 leading-none mb-1">{{ formatCurrency(group.total) }}</p>
                        <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">Subtotal</p>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="groupedItems.length === 0" class="text-center py-6 bg-slate-50 rounded-xl border-2 border-dashed border-slate-200 text-slate-400">
                <i class="fa fa-shopping-basket text-xl mb-1.5 opacity-20"></i>
                <p class="text-[10px] font-bold uppercase tracking-widest">Bag is empty</p>
            </div>
        </div>

        <!-- Grand Total -->
        <div v-if="groupedItems.length > 0" 
            class="bg-indigo-600 rounded-2xl p-4 shadow-xl shadow-indigo-200 flex justify-between items-center transition-all hover:scale-[1.02] relative overflow-hidden">
            <!-- Shimmer effect -->
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full animate-[shimmer_2s_infinite] pointer-events-none"></div>
            
            <div class="relative z-10">
                <p class="text-indigo-100/80 text-[9px] font-black uppercase tracking-[0.2em] mb-0.5">Grand Total</p>
                <div class="flex items-baseline gap-1">
                    <span class="text-white text-xl font-black tracking-tight">{{ formatCurrency(subTotal) }}</span>
                </div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center text-white relative z-10">
                <i class="fa fa-credit-card text-base opacity-80"></i>
            </div>
        </div>

    </div>
</template>



<script setup>
import { computed } from 'vue'

const props = defineProps({
    items: {
        type: Array,
        default: () => []
    }
})

const groupedItems = computed(() => {
    const groups = {}
    
    props.items.forEach(item => {
        const catId = item.tailoring_category_id
        if (!groups[catId]) {
            groups[catId] = {
                categoryId: catId,
                categoryName: item.category?.name || 'Unknown Category',
                quantity: 0,
                total: 0
            }
        }
        
        groups[catId].quantity += parseFloat(item.quantity || 0)
        groups[catId].total += parseFloat(item.total || 0)
    })
    
    return Object.values(groups)
})

const subTotal = computed(() => {
    return props.items.reduce((sum, item) => sum + (parseFloat(item.total) || 0), 0)
})

const formatCurrency = (value) => {
    return parseFloat(value || 0).toFixed(2)
}
</script>
