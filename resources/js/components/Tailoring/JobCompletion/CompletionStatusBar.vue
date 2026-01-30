<template>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-3 text-center">
        <div class="text-[0.65rem] font-bold text-slate-800 flex items-center justify-center gap-4">
            <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-slate-300"></span>
                <span v-if="recordCount > 0" class="uppercase tracking-widest text-slate-400">{{ recordCount }} record(s)</span>
                <span v-else class="uppercase tracking-widest text-slate-300">No records</span>
            </span>
            
            <span v-if="completionStatus" class="flex items-center gap-2">
                <span class="text-slate-300">|</span>
                <span class="uppercase tracking-widest text-slate-400">Status:</span>
                <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 font-black text-[0.55rem] uppercase tracking-tighter border border-blue-100">
                    {{ completionStatus }}
                </span>
            </span>
        </div>
    </div>
</template>


<script setup>
defineProps({
    recordCount: {
        type: Number,
        default: 0
    },
    completionStatus: {
        type: String,
        default: null
    }
})
</script>
