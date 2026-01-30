<template>
    <div class="flex flex-col gap-4">
        <!-- Main Form Grid -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            <!-- Left Column: Basic & Body Measurements -->
            <div class="flex flex-col gap-4">
                <div class="bg-white rounded-3xl shadow-lg shadow-slate-200/50 border border-slate-200 overflow-hidden flex flex-col h-full relative">
                    <!-- Background Tint -->
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-50/20 to-transparent pointer-events-none"></div>
                    
                    <div class="px-4 py-3 border-b border-slate-100 flex items-center gap-3 relative z-10 bg-slate-50/30">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600 shadow-sm border border-blue-100">
                            <i class="fa fa-info-circle text-sm"></i>
                        </div>
                        <h6 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-0">Basic & Body</h6>
                    </div>
                    <div class="p-4 relative z-10">
                        <div class="grid grid-cols-6 gap-3">
                            <!-- Model Selection -->
                            <div class="col-span-3">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">
                                    {{ category?.name || 'Item' }} Model
                                </label>
                                <div class="flex gap-1.5">
                                    <div class="relative flex-1">
                                        <select v-model="measurements.tailoring_category_model_id"
                                            @change="updateModelName" 
                                            class="w-full appearance-none bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all">
                                            <option :value="null">Select Model</option>
                                            <option v-for="model in categoryModels" :key="model.id" :value="model.id">
                                                {{ model.name }}
                                            </option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none text-slate-400">
                                            <i class="fa fa-chevron-down text-[10px]"></i>
                                        </div>
                                    </div>
                                    <button type="button" @click="addCategoryModel" 
                                        class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-blue-600 hover:bg-blue-600 hover:text-white hover:border-blue-600 transition-all flex items-center justify-center">
                                        <i class="fa fa-plus text-[10px]"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Length -->
                            <div class="col-span-3">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">Length</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-300">
                                        <i class="fa fa-arrows-v text-[10px]"></i>
                                    </div>
                                    <input v-model.number="measurements.length" type="number" step="0.01"
                                        placeholder="0.00" class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-8 pr-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all" />
                                </div>
                            </div>

                            <!-- Body Measurements Grid -->
                            <div class="col-span-2">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">Shoulder</label>
                                <input v-model.number="measurements.shoulder" type="number" step="0.01"
                                    placeholder="0.00" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all" />
                            </div>
                            <div class="col-span-2">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">Sleeve</label>
                                <input v-model.number="measurements.sleeve" type="number" step="0.01" 
                                    placeholder="0.00" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all" />
                            </div>
                            <div class="col-span-2">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">Chest</label>
                                <input v-model.number="measurements.chest" type="number" step="0.01" 
                                    placeholder="0.00" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all" />
                            </div>

                            <div class="col-span-2">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">Stomach</label>
                                <input v-model="measurements.stomach" type="text" placeholder="..."
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all" />
                            </div>
                            <div class="col-span-2">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">Neck</label>
                                <input v-model.number="measurements.neck" type="number" step="0.01" 
                                    placeholder="0.00" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all" />
                            </div>
                            <div class="col-span-2">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">Bottom</label>
                                <input v-model="measurements.bottom" type="text" placeholder="..."
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all" />
                            </div>

                            <div class="col-span-3">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">S.L Chest</label>
                                <input v-model.number="measurements.sl_chest" type="number" step="0.01"
                                    placeholder="0.00" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all" />
                            </div>
                            <div class="col-span-3">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">S.L So</label>
                                <input v-model.number="measurements.sl_so" type="number" step="0.01" 
                                    placeholder="0.00" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Collar & Cuff -->
            <div class="flex flex-col gap-4">
                <div class="bg-white rounded-3xl shadow-lg shadow-slate-200/50 border border-slate-200 overflow-hidden flex flex-col h-full relative">
                    <!-- Background Tint -->
                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-50/20 to-transparent pointer-events-none"></div>
                    
                    <div class="px-4 py-3 border-b border-slate-100 flex items-center gap-3 relative z-10 bg-slate-50/30">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 shadow-sm border border-emerald-100">
                            <i class="fa fa-tag text-sm"></i>
                        </div>
                        <h6 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-0">Collar & Cuff</h6>
                    </div>
                    <div class="p-4 relative z-10">
                        <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                            <!-- Collar Group -->
                            <div class="col-span-1">
                                <label class="block text-[0.65rem] font-black text-blue-600 uppercase tracking-widest mb-1 px-1">Collar Type</label>
                                <div class="flex gap-1.5">
                                    <div class="relative flex-1">
                                        <select v-model="measurements.collar" class="w-full appearance-none bg-blue-50/30 border border-blue-100 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white focus:border-blue-500 transition-all">
                                            <option value="">Select Collar</option>
                                            <option v-for="option in getOptions('collar')" :key="option.id" :value="option.value">{{ option.value }}</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none text-blue-300">
                                            <i class="fa fa-chevron-down text-[10px]"></i>
                                        </div>
                                    </div>
                                    <button type="button" @click="addOption('collar')" class="w-8 h-8 rounded-lg border border-blue-100 bg-white text-blue-600 hover:bg-blue-600 hover:text-white transition-all flex items-center justify-center"><i class="fa fa-plus text-[10px]"></i></button>
                                </div>
                            </div>
                            <div class="col-span-1">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">Collar Size</label>
                                <input v-model="measurements.collar_size" type="text" placeholder="..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white focus:border-blue-500 transition-all" />
                            </div>
                            <div class="col-span-1">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">Collar Cloth</label>
                                <div class="flex gap-1.5">
                                    <div class="relative flex-1">
                                        <select v-model="measurements.collar_cloth" class="w-full appearance-none bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white focus:border-blue-500 transition-all">
                                            <option value="">Select Cloth</option>
                                            <option v-for="option in getOptions('collar_cloth')" :key="option.id" :value="option.value">{{ option.value }}</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none text-slate-400">
                                            <i class="fa fa-chevron-down text-[10px]"></i>
                                        </div>
                                    </div>
                                    <button type="button" @click="addOption('collar_cloth')" class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-400 hover:text-blue-600 transition-all flex items-center justify-center"><i class="fa fa-plus text-[10px]"></i></button>
                                </div>
                            </div>
                            <div class="col-span-1">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">Collar Model</label>
                                <div class="flex gap-1.5">
                                    <div class="relative flex-1">
                                        <select v-model="measurements.collar_model" class="w-full appearance-none bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white focus:border-blue-500 transition-all">
                                            <option value="">Select Model</option>
                                            <option v-for="option in getOptions('collar_model')" :key="option.id" :value="option.value">{{ option.value }}</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none text-slate-400">
                                            <i class="fa fa-chevron-down text-[10px]"></i>
                                        </div>
                                    </div>
                                    <button type="button" @click="addOption('collar_model')" class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-400 hover:text-blue-600 transition-all flex items-center justify-center"><i class="fa fa-plus text-[10px]"></i></button>
                                </div>
                            </div>

                            <div class="col-span-2 h-px bg-slate-100 my-1"></div>

                            <!-- Cuff Group -->
                            <div class="col-span-1">
                                <label class="block text-[0.65rem] font-black text-blue-600 uppercase tracking-widest mb-1 px-1">Cuff Type</label>
                                <div class="flex gap-1.5">
                                    <div class="relative flex-1">
                                        <select v-model="measurements.cuff" class="w-full appearance-none bg-blue-50/30 border border-blue-100 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white focus:border-blue-500 transition-all">
                                            <option value="">Select Cuff</option>
                                            <option v-for="option in getOptions('cuff')" :key="option.id" :value="option.value">{{ option.value }}</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none text-blue-300">
                                            <i class="fa fa-chevron-down text-[10px]"></i>
                                        </div>
                                    </div>
                                    <button type="button" @click="addOption('cuff')" class="w-8 h-8 rounded-lg border border-blue-100 bg-white text-blue-600 hover:bg-blue-600 hover:text-white transition-all flex items-center justify-center"><i class="fa fa-plus text-[10px]"></i></button>
                                </div>
                            </div>
                            <div class="col-span-1">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">Cuff Size</label>
                                <input v-model="measurements.cuff_size" type="text" placeholder="..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white focus:border-blue-500 transition-all" />
                            </div>
                            <div class="col-span-1">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">Cuff Cloth</label>
                                <div class="flex gap-1.5">
                                    <div class="relative flex-1">
                                        <select v-model="measurements.cuff_cloth" class="w-full appearance-none bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white focus:border-blue-500 transition-all">
                                            <option value="">Select Cloth</option>
                                            <option v-for="option in getOptions('cuff_cloth')" :key="option.id" :value="option.value">{{ option.value }}</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none text-slate-400">
                                            <i class="fa fa-chevron-down text-[10px]"></i>
                                        </div>
                                    </div>
                                    <button type="button" @click="addOption('cuff_cloth')" class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-400 hover:text-blue-600 transition-all flex items-center justify-center"><i class="fa fa-plus text-[10px]"></i></button>
                                </div>
                            </div>
                            <div class="col-span-1">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">Cuff Model</label>
                                <div class="flex gap-1.5">
                                    <div class="relative flex-1">
                                        <select v-model="measurements.cuff_model" class="w-full appearance-none bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white focus:border-blue-500 transition-all">
                                            <option value="">Select Model</option>
                                            <option v-for="option in getOptions('cuff_model')" :key="option.id" :value="option.value">{{ option.value }}</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none text-slate-400">
                                            <i class="fa fa-chevron-down text-[10px]"></i>
                                        </div>
                                    </div>
                                    <button type="button" @click="addOption('cuff_model')" class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-400 hover:text-blue-600 transition-all flex items-center justify-center"><i class="fa fa-plus text-[10px]"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Sections: Full Width Specifications -->
            <div class="col-span-1 xl:col-span-2">
                <div class="bg-white rounded-3xl shadow-lg shadow-slate-200/50 border border-slate-200 overflow-hidden relative">
                    <!-- Background Tint -->
                    <div class="absolute inset-0 bg-gradient-to-tr from-amber-50/20 to-transparent pointer-events-none"></div>
                    
                    <div class="px-4 py-3 border-b border-slate-100 flex items-center gap-3 relative z-10 bg-slate-50/30">
                        <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600 shadow-sm border border-amber-100">
                            <i class="fa fa-sliders text-sm"></i>
                        </div>
                        <h6 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-0">Specifications</h6>
                    </div>
                    <div class="p-4 relative z-10">
                        <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-8 gap-3">
                            <div class="col-span-1">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">Mar Size</label>
                                <input v-model="measurements.mar_size" type="text" placeholder="..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white focus:border-blue-500 transition-all" />
                            </div>
                            <div class="col-span-1">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">Mar Model</label>
                                <div class="flex gap-1">
                                    <div class="relative flex-1">
                                        <select v-model="measurements.mar_model" class="w-full appearance-none bg-slate-50 border border-slate-200 rounded-xl px-2 py-1.5 text-xs font-bold text-slate-700 focus:bg-white focus:border-blue-500 transition-all">
                                            <option value="">Select</option>
                                            <option v-for="option in getOptions('mar_model')" :key="option.id" :value="option.value">{{ option.value }}</option>
                                        </select>
                                    </div>
                                    <button type="button" @click="addOption('mar_model')" class="w-7 h-7 rounded-lg border border-slate-200 bg-white text-slate-400 hover:text-blue-600 transition-all flex items-center justify-center shrink-0 mt-0.5"><i class="fa fa-plus text-[8px]"></i></button>
                                </div>
                            </div>
                            <div class="col-span-1">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">N.D Button</label>
                                <input v-model="measurements.neck_d_button" type="text" placeholder="..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white focus:border-blue-500 transition-all" />
                            </div>
                            <div class="col-span-1">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">Mob Pkt</label>
                                <select v-model="measurements.mobile_pocket" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white transition-all">
                                    <option value="No">No</option>
                                    <option value="Yes">Yes</option>
                                </select>
                            </div>
                            <div class="col-span-1">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">Side PT</label>
                                <input v-model="measurements.side_pt_size" type="text" placeholder="..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white focus:border-blue-500 transition-all" />
                            </div>
                            <div class="col-span-1">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">PT Model</label>
                                <div class="flex gap-1">
                                    <div class="relative flex-1">
                                        <select v-model="measurements.side_pt_model" class="w-full appearance-none bg-slate-50 border border-slate-200 rounded-xl px-2 py-1.5 text-xs font-bold text-slate-700 focus:bg-white transition-all">
                                            <option value="">Select</option>
                                            <option v-for="option in getOptions('side_pt_model')" :key="option.id" :value="option.value">{{ option.value }}</option>
                                        </select>
                                    </div>
                                    <button type="button" @click="addOption('side_pt_model')" class="w-7 h-7 rounded-lg border border-slate-200 bg-white text-slate-400 hover:text-blue-600 transition-all flex items-center justify-center shrink-0 mt-0.5"><i class="fa fa-plus text-[8px]"></i></button>
                                </div>
                            </div>
                            <div class="col-span-1">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">Regal</label>
                                <input v-model="measurements.regal_size" type="text" placeholder="..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white focus:border-blue-500 transition-all" />
                            </div>
                            <div class="col-span-1">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">Knee Loose</label>
                                <input v-model="measurements.knee_loose" type="text" placeholder="..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white focus:border-blue-500 transition-all" />
                            </div>

                            <div class="col-span-1">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">FP Down</label>
                                <input v-model="measurements.fp_down" type="text" placeholder="..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white transition-all" />
                            </div>
                            <div class="col-span-1">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">FP Size</label>
                                <input v-model="measurements.fp_size" type="text" placeholder="..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white transition-all" />
                            </div>
                            <div class="col-span-1">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">FP Model</label>
                                <div class="flex gap-1">
                                    <div class="relative flex-1">
                                        <select v-model="measurements.fp_model" class="w-full appearance-none bg-slate-50 border border-slate-200 rounded-xl px-2 py-1.5 text-xs font-bold text-slate-700 focus:bg-white transition-all">
                                            <option value="">Select</option>
                                            <option v-for="option in getOptions('fp_model')" :key="option.id" :value="option.value">{{ option.value }}</option>
                                        </select>
                                    </div>
                                    <button type="button" @click="addOption('fp_model')" class="w-7 h-7 rounded-lg border border-slate-200 bg-white text-slate-400 hover:text-blue-600 transition-all flex items-center justify-center shrink-0 mt-0.5"><i class="fa fa-plus text-[8px]"></i></button>
                                </div>
                            </div>
                            <div class="col-span-1">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">Pen Pkt</label>
                                <div class="flex gap-1">
                                    <div class="relative flex-1">
                                        <select v-model="measurements.pen" class="w-full appearance-none bg-slate-50 border border-slate-200 rounded-xl px-2 py-1.5 text-xs font-bold text-slate-700 focus:bg-white transition-all">
                                            <option value="">Select</option>
                                            <option v-for="option in getOptions('pen')" :key="option.id" :value="option.value">{{ option.value }}</option>
                                        </select>
                                    </div>
                                    <button type="button" @click="addOption('pen')" class="w-7 h-7 rounded-lg border border-slate-200 bg-white text-slate-400 hover:text-blue-600 transition-all flex items-center justify-center shrink-0 mt-0.5"><i class="fa fa-plus text-[8px]"></i></button>
                                </div>
                            </div>

                            <div class="col-span-1">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">Stitching</label>
                                <div class="flex gap-1">
                                    <div class="relative flex-1">
                                        <select v-model="measurements.stitching" class="w-full appearance-none bg-slate-50 border border-slate-200 rounded-xl px-2 py-1.5 text-xs font-bold text-slate-700 focus:bg-white transition-all">
                                            <option value="">Select</option>
                                            <option v-for="option in getOptions('stitching')" :key="option.id" :value="option.value">{{ option.value }}</option>
                                        </select>
                                    </div>
                                    <button type="button" @click="addOption('stitching')" class="w-7 h-7 rounded-lg border border-slate-200 bg-white text-slate-400 hover:text-blue-600 transition-all flex items-center justify-center shrink-0 mt-0.5"><i class="fa fa-plus text-[8px]"></i></button>
                                </div>
                            </div>
                            <div class="col-span-1">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">Button</label>
                                <div class="flex gap-1">
                                    <div class="relative flex-1">
                                        <select v-model="measurements.button" class="w-full appearance-none bg-slate-50 border border-slate-200 rounded-xl px-2 py-1.5 text-xs font-bold text-slate-700 focus:bg-white transition-all">
                                            <option value="">Select</option>
                                            <option v-for="option in getOptions('button')" :key="option.id" :value="option.value">{{ option.value }}</option>
                                        </select>
                                    </div>
                                    <button type="button" @click="addOption('button')" class="w-7 h-7 rounded-lg border border-slate-200 bg-white text-slate-400 hover:text-blue-600 transition-all flex items-center justify-center shrink-0 mt-0.5"><i class="fa fa-plus text-[8px]"></i></button>
                                </div>
                            </div>
                            <div class="col-span-1">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">Btn No</label>
                                <input v-model="measurements.button_no" type="text" placeholder="#" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white transition-all" />
                            </div>
                            <div class="col-span-1">
                                <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">Notes</label>
                                <input v-model="measurements.tailoring_notes" type="text" placeholder="..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white transition-all" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'

const props = defineProps({
    modelValue: Object,
    category: Object,
    model: Object,
    measurementOptions: Object,
})

const emit = defineEmits(['update:modelValue', 'add-option'])

const toast = useToast()
const measurements = ref(props.modelValue || {})
const categoryModels = ref([])

const getOptions = (type) => {
    if (!props.measurementOptions || !props.measurementOptions[type]) return []
    return Object.entries(props.measurementOptions[type]).map(([id, value]) => ({
        id,
        value
    }))
}

const addOption = async (type) => {
    const value = prompt(`Add new ${type.replace('_', ' ')}:`)
    if (value && value.trim()) {
        emit('add-option', type, value.trim())
    }
}

const addCategoryModel = async () => {
    if (!props.category?.id) {
        toast.error('Please select an item type first')
        return
    }

    const name = prompt(`Add new ${props.category.name} Model:`)
    if (!name || !name.trim()) return

    try {
        const response = await axios.post('/tailoring/order/category-models', {
            tailoring_category_id: props.category.id,
            name: name.trim()
        })

        if (response.data.success) {
            toast.success('Model added successfully')
            // Add to list and select it
            const newModel = response.data.data
            categoryModels.value.push(newModel)
            measurements.value.tailoring_category_model_id = newModel.id
            measurements.value.tailoring_category_model_name = newModel.name
        }
    } catch (error) {
        console.error('Failed to add model', error)
        toast.error(error.response?.data?.message || 'Failed to add model')
    }
}

const updateModelName = () => {
    const selectedModel = categoryModels.value.find(m => m.id === measurements.value.tailoring_category_model_id)
    if (selectedModel) {
        measurements.value.tailoring_category_model_name = selectedModel.name
    } else {
        measurements.value.tailoring_category_model_name = null
    }
}

watch(() => props.category, async (newCategory) => {
    if (newCategory?.id) {
        try {
            const response = await axios.get(`/tailoring/order/category-models/${newCategory.id}`)
            if (response.data.success) {
                categoryModels.value = response.data.data
                // Clear selected model when category changes
                measurements.value.tailoring_category_model_id = null
            }
        } catch (error) {
            console.error('Failed to load category models', error)
        }
    } else {
        categoryModels.value = []
    }
}, { immediate: true })

// Watch measurements and emit updates, but prevent infinite loops
let isUpdatingFromProps = false
watch(measurements, (newVal) => {
    if (!isUpdatingFromProps) {
        emit('update:modelValue', { ...newVal })
    }
}, { deep: true })

watch(() => props.modelValue, (newVal) => {
    if (newVal && JSON.stringify(newVal) !== JSON.stringify(measurements.value)) {
        isUpdatingFromProps = true
        measurements.value = { ...newVal }
        nextTick(() => {
            isUpdatingFromProps = false
        })
    }
}, { deep: true })
</script>


