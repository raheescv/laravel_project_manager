<template>
    <div v-if="show"
        class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-[2px] flex items-center justify-center p-3"
        @click.self="emit('update:show', false)">
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 px-4 py-3 text-white">
                <h3 class="text-sm font-bold tracking-tight flex items-center gap-2">
                    <i class="fa fa-sliders text-xs"></i>
                    Quick Add Configuration
                </h3>
                <p class="text-[11px] text-white/80 mt-0.5">Control quick add visibility directly on this screen.</p>
            </div>
            <div class="px-4 py-4">
                <label
                    class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 px-3 py-3 bg-slate-50/70">
                    <div>
                        <p class="text-sm font-semibold text-slate-800">Enable Quick Add</p>
                        <p class="text-[11px] text-slate-500">Show + buttons for model, model type, and measurement options.</p>
                    </div>
                    <span class="relative inline-flex items-center">
                        <input :checked="enabled" type="checkbox" class="sr-only peer"
                            @change="emit('update:enabled', $event.target.checked)">
                        <span class="w-11 h-6 rounded-full bg-slate-300 peer-checked:bg-blue-600 transition-colors"></span>
                        <span
                            class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></span>
                    </span>
                </label>
                <div class="mt-4 flex items-center justify-between">
                    <span class="text-[11px] font-semibold" :class="isQuickAddActive ? 'text-emerald-600' : 'text-slate-500'">
                        {{ isQuickAddActive ? 'Quick Add is currently ON' : 'Quick Add is currently OFF' }}
                    </span>
                    <button type="button" @click="emit('update:show', false)"
                        class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-600 text-white hover:bg-blue-700 transition-colors">
                        Done
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    show: {
        type: Boolean,
        default: false
    },
    enabled: {
        type: Boolean,
        default: false
    },
    isQuickAddActive: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:show', 'update:enabled'])
</script>
