<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import debounce from 'lodash/debounce'; 

// --- 1. OPERATING UNITS DATA ---
const unitData = {
    "Accounting Office": [],
    "Administrative Service Division ": [],
    "Alumni Relations Office": [],
    "Auxillary Directorate": [],
    "Budget Management Section": [],
    "CAFSD": [],
    "CAS": [],
    "CASAT": [],
    "CBEA": [],
    "CCIS": [],
    "CHS": [],
    "CIT": [],
    "COE": [],
    "COL": [],
    "COM": [],
    "CTE": [],
    "CVM": [],
    "Cash Management": [],
    "Distance Learning Office": [],
    "ETEEAP": [],
    "Extension Directorate": [],
    "General Services Directorate": [],
    "Graduate School": [],
    "Health and Wellness Services": [],
    "Human Resources Management Office ": [],
    "Information Technology Center": [],
    "Innovation and Technology Directorate (former S&T Park)": [],
    "Instructional Materials Development Office": [],
    "Internal Audit Services": [],
    "Internationalization, Linkages, and Partnership Directorate": [],
    "NBERIC": [],
    "OJT - SIPP Practicum Coordinating Office": [],
    "Office of the University and Board Secretary": [],
    "PPDO": [],
    "Planning Office": [],
    "Procurement Division": [],
    "Quality Assurance": [],
    "Records and Archives Management Office": [],
    "Research Directorate": [],
    "Strategic Communication  Office": [],
    "Students and Affairs Services Office": [],
    "Supply and Property Management Office": [],
    "URERB": [],
    "University Library System": [],
    "University Registrar's Office": []
};

// Extract keys for the dropdown list
const operatingUnits = computed(() => Object.keys(unitData).sort());

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({ total: 0, positive: 0, negative: 0, neutral: 0 })
    },
    topPerformers: {
        type: Array,
        default: () => []
    },
    needsImprovement: {
        type: Array,
        default: () => []
    },
    feedback: {
        type: Object, 
        default: () => ({ data: [], links: [], meta: {} })
    },
    filters: {
        type: Object,
        default: () => ({ search: '', unit: '', sort_sentiment: '' })
    }
});

// Initialize state
const search = ref(props.filters?.search || '');
const unitFilter = ref(props.filters?.unit || '');
const sortSentiment = ref(props.filters?.sort_sentiment || '');
const isRefreshing = ref(false);
const isLoading = ref(false);

const formatNum = (num) => Number(num || 0).toLocaleString();

// --- SEARCH & FILTER LOGIC ---
const performSearch = debounce(() => {
    router.get(window.location.pathname, { 
        search: search.value, 
        unit: unitFilter.value,
        sort_sentiment: sortSentiment.value
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['feedback', 'stats', 'filters'],
        onStart: () => { isLoading.value = true; },
        onFinish: () => { isLoading.value = false; }
    });
}, 300);

// Sort Toggle Function
const toggleSort = () => {
    if (sortSentiment.value === '') {
        sortSentiment.value = 'desc'; // First click: Sort Descending (Positive -> Neutral -> Negative)
    } else if (sortSentiment.value === 'desc') {
        sortSentiment.value = 'asc';  // Second click: Sort Ascending
    } else {
        sortSentiment.value = '';     // Third click: Reset (Date sort)
    }
    performSearch();
};

watch(search, () => performSearch());
watch(unitFilter, () => performSearch());

const form = useForm({});

const submitClear = () => {
    if (confirm('⚠️ DANGER ZONE: ARE YOU SURE?\n\nThis will permanently delete ALL feedback data.\n\nThis action cannot be undone.')) {
        form.post('/clear-data', {
            preserveScroll: true,
            onSuccess: () => alert('✅ System Cleaned: All data removed.'),
            onError: () => alert('❌ Error: Could not clear data.')
        });
    }
};

const refreshData = () => {
    isRefreshing.value = true;
    router.reload({ 
        only: ['feedback', 'stats', 'topPerformers', 'needsImprovement'],
        onFinish: () => { isRefreshing.value = false; }
    });
};
</script>

<template>
    <Head title="Executive Dashboard" />

    <DashboardLayout>
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4 px-2">
            <div>
                <h2 class="text-2xl font-black text-[#0c4b33] uppercase tracking-wide">Executive Overview</h2>
                <p class="text-sm text-gray-500 font-bold">Stakeholder Feedback Analysis</p>
            </div>
            
            <button 
                @click="submitClear"
                type="button" 
                :disabled="form.processing"
                class="bg-white border border-red-200 text-red-500 hover:bg-red-50 hover:border-red-300 px-5 py-2.5 rounded-xl text-[11px] font-black uppercase tracking-widest transition-all flex items-center gap-2 shadow-sm hover:shadow-md cursor-pointer disabled:opacity-50"
            >
                <span class="text-lg" v-if="!form.processing">🗑️</span>
                <span class="text-lg animate-spin" v-else>⏳</span>
                {{ form.processing ? 'Clearing...' : 'Clear All Data' }}
            </button>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-5 mb-8 px-2">
            <div class="transition-all duration-300 rounded-[25px] p-6 shadow-sm flex flex-col justify-between h-32 hover:scale-105 hover:shadow-md cursor-default bg-white text-gray-800 border border-gray-100">
                <div class="flex justify-between items-start">
                    <div class="text-[9px] opacity-80 uppercase font-black tracking-wider leading-tight text-gray-500">Total Feedback</div>
                    <span class="text-sm grayscale opacity-50">📊</span>
                </div>
                <div class="text-3xl font-black text-gray-800">{{ formatNum(props.stats.total) }}</div>
            </div>

            <div class="transition-all duration-300 rounded-[25px] p-6 shadow-sm flex flex-col justify-between h-32 hover:scale-105 hover:shadow-md cursor-default bg-[#0c4b33] text-white">
                <div class="flex justify-between items-start">
                    <div class="text-[9px] opacity-80 uppercase font-black tracking-wider leading-tight text-green-100">Positive</div>
                    <span class="text-sm opacity-80">   </span>
                </div>
                <div class="text-3xl font-black">{{ formatNum(props.stats.positive) }}</div>
            </div>

            <div class="transition-all duration-300 rounded-[25px] p-6 shadow-sm flex flex-col justify-between h-32 hover:scale-105 hover:shadow-md cursor-default bg-red-500 text-white">
                <div class="flex justify-between items-start">
                    <div class="text-[9px] opacity-80 uppercase font-black tracking-wider leading-tight text-red-100">Negative</div>
                    <span class="text-sm opacity-80"></span>
                </div>
                <div class="text-3xl font-black">{{ formatNum(props.stats.negative) }}</div>
            </div>

            <div class="transition-all duration-300 rounded-[25px] p-6 shadow-sm flex flex-col justify-between h-32 hover:scale-105 hover:shadow-md cursor-default bg-yellow-400 text-white">
                <div class="flex justify-between items-start">
                    <div class="text-[9px] opacity-80 uppercase font-black tracking-wider leading-tight text-yellow-100">Neutral</div>
                    <span class="text-sm opacity-80"></span>
                </div>
                <div class="text-3xl font-black drop-shadow-sm">{{ formatNum(props.stats.neutral) }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8 px-2">
            <div class="bg-gradient-to-br from-[#0c4b33] to-[#1a6e4d] rounded-[35px] p-8 shadow-sm text-white border border-[#0c4b33] relative overflow-hidden group hover:shadow-md transition-all">
                <div class="absolute -right-10 -top-10 opacity-10 text-9xl group-hover:scale-110 transition-transform duration-700">🏆</div>
                 <div class="flex justify-between items-start mb-8 relative z-10">
                    <div>
                        <h3 class="font-black text-[10px] uppercase tracking-[0.2em] text-yellow-400 mb-1">Excellence Awardees</h3>
                        <p class="text-xl font-black uppercase tracking-tight">Top Offices</p>
                    </div>
                 </div>
                 <div class="space-y-5 relative z-10">
                    <div v-if="props.topPerformers.length === 0" class="text-center text-sm opacity-60 py-10 italic">No data available yet.</div>
                    <div v-else v-for="(item, i) in props.topPerformers" :key="i" class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="bg-white/10 w-8 h-8 rounded-full flex items-center justify-center text-yellow-400 font-black text-[11px] shadow-inner border border-white/10">{{ i + 1 }}</div>
                            <div>
                                <div class="text-[11px] font-bold uppercase tracking-wide text-white drop-shadow-sm">{{ item.unit }}</div>
                                <div class="text-[9px] text-green-200 uppercase font-black tracking-widest mt-0.5">Highest Satisfaction</div>
                            </div>
                        </div>
                        <span class="text-lg font-black text-yellow-400 drop-shadow-md">{{ item.score }}</span>
                    </div>
                 </div>
            </div>

            <div class="bg-white rounded-[35px] p-8 shadow-sm border border-gray-100 relative overflow-hidden group hover:shadow-md transition-all">
                 <div class="absolute -right-10 -top-10 opacity-[0.03] text-9xl group-hover:scale-110 transition-transform duration-700">⚠️</div>
                 <div class="flex justify-between items-start mb-8 relative z-10">
                    <div>
                        <h3 class="font-black text-[10px] uppercase tracking-[0.2em] text-red-500 mb-1">Action Required</h3>
                        <p class="text-xl font-black text-gray-800 uppercase tracking-tight leading-tight">Needs Improvement</p>
                    </div>
                 </div>
                 <div class="space-y-5 relative z-10">
                    <div v-if="props.needsImprovement.length === 0" class="text-center text-gray-400 text-sm py-10 flex flex-col items-center italic">
                        <span class="text-2xl mb-2 grayscale opacity-50">🎉</span>
                        No critical issues found.
                    </div>
                    <div v-else v-for="(item, i) in props.needsImprovement" :key="i" class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-8 h-8 rounded-full bg-red-50 flex items-center justify-center text-red-500 font-black text-[11px] border border-red-100 shadow-sm">{{ i + 1 }}</div>
                            <div>
                                <div class="text-[11px] font-bold text-gray-700 uppercase tracking-wide">{{ item.unit }}</div>
                                <div class="text-[9px] text-red-400 font-black uppercase tracking-widest mt-0.5 truncate max-w-[200px]" :title="item.issue">{{ item.issue || 'General Service' }}</div>
                            </div>
                        </div>
                        <div class="text-right flex flex-col items-end">
                            <span class="text-lg font-black text-red-500 leading-none">{{ item.score }}</span>
                            <span class="text-[8px] text-gray-400 font-black uppercase tracking-widest mt-1">Neg Rate</span>
                        </div>
                    </div>
                 </div>
            </div>
        </div>

        <div class="bg-white rounded-[35px] p-8 shadow-sm border border-gray-100 flex flex-col relative min-h-[500px]">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4 border-b border-gray-50 pb-4">
                <div>
                    <h2 class="text-xl font-black text-[#0c4b33] tracking-tight uppercase">Feedback Ledger</h2>
                    <p class="text-[11px] text-gray-400 font-bold uppercase tracking-widest mt-1">Processed review data</p>
                </div>
                
                <button 
                    @click="refreshData"
                    :disabled="isRefreshing"
                    class="flex items-center gap-2 px-4 py-2 bg-gray-50 hover:bg-gray-100 text-gray-600 text-[10px] uppercase tracking-widest font-black rounded-xl border border-gray-200 transition shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <svg v-if="isRefreshing" class="animate-spin h-3.5 w-3.5 text-[#0c4b33]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>{{ isRefreshing ? 'Refreshing...' : 'Refresh' }}</span>
                </button>
            </div>

            <div class="flex flex-col md:flex-row gap-4 mb-6">
                <div class="relative flex-grow md:max-w-md">
                    <span class="absolute left-4 top-3 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input 
                        v-model="search"
                        type="text" 
                        placeholder="Search keywords in reviews..." 
                        class="w-full bg-gray-50 border border-gray-100 text-gray-700 text-xs pl-10 pr-4 py-3 rounded-xl focus:ring-1 focus:ring-[#0c4b33] placeholder-gray-400 font-bold transition-all shadow-inner"
                    >
                </div>

                <div class="relative w-full md:w-80">
                    <select 
                        v-model="unitFilter"
                        class="appearance-none w-full bg-gray-50 border border-gray-100 text-gray-700 text-xs font-bold py-3 pl-4 pr-10 rounded-xl focus:ring-1 focus:ring-[#0c4b33] cursor-pointer truncate shadow-inner"
                    >
                        <option value="">All Operating Units</option>
                        <option v-for="unit in operatingUnits" :key="unit" :value="unit">
                            {{ unit }}
                        </option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>

                <div class="flex items-center justify-end flex-1">
                    <div class="text-[10px] text-gray-400 uppercase tracking-widest font-black bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-100">
                        Showing <span class="text-[#0c4b33] mx-1">{{ formatNum(props.feedback.total) }}</span> records
                    </div>
                </div>
            </div>
            
            <div :class="['overflow-x-auto custom-scrollbar flex-1 transition-all duration-300', (isLoading || isRefreshing) ? 'opacity-40 blur-[2px] pointer-events-none animate-pulse' : 'opacity-100']">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50/80 sticky top-0 z-10">
                        <tr class="text-gray-400 text-[9px] font-black uppercase tracking-[0.2em] border-b border-gray-100">
                            <th class="py-4 pr-4 pl-4 rounded-tl-xl w-1/5">Operating Unit</th>
                            <th class="py-4 pr-4 w-2/5">Review</th>
                            <th class="py-4 pr-4 w-1/5">Services Availed</th>
                            <th 
                                class="py-4 text-right pr-4 rounded-tr-xl cursor-pointer hover:text-[#0c4b33] transition-colors select-none group"
                                @click="toggleSort"
                            >
                                <div class="flex items-center justify-end gap-1.5">
                                    Sentiment
                                    <span v-if="sortSentiment === 'asc'" class="text-[#0c4b33]">▲</span>
                                    <span v-else-if="sortSentiment === 'desc'" class="text-[#0c4b33]">▼</span>
                                    <span v-else class="text-gray-300 group-hover:text-gray-400">↕</span>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="row in props.feedback.data" :key="row.id" class="group hover:bg-gray-50/80 transition-colors duration-150">
                            
                            <td class="py-5 pr-4 pl-4 align-top">
                                <span class="text-gray-700 font-bold text-[10px] bg-white px-2 py-1.5 rounded-md border border-gray-200 whitespace-normal block uppercase tracking-tight shadow-sm">
                                    {{ row.operating_unit }}
                                </span>
                            </td>

                            <td class="py-5 pr-6 text-gray-600 text-xs leading-relaxed group-hover:text-gray-900 transition-colors align-top">
                                <span class="text-gray-400 mr-1 italic">"</span>{{ row.feedback_text }}<span class="text-gray-400 ml-1 italic">"</span>
                            </td>

                            <td class="py-5 pr-4 align-top">
                                <div class="text-[9px] text-gray-500 uppercase font-black tracking-wide leading-tight line-clamp-3" :title="row.services_availed">
                                    {{ row.services_availed || 'General Inquiry' }}
                                </div>
                            </td>

                            <td class="py-5 text-right align-top pr-4">
                                <span 
                                    class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-black uppercase tracking-wider border shadow-sm"
                                    :class="{
                                        'bg-green-50 text-green-700 border-green-200': row.sentiment === 'Positive',
                                        'bg-red-50 text-red-700 border-red-200': row.sentiment === 'Negative',
                                        'bg-yellow-50 text-yellow-700 border-yellow-200': row.sentiment === 'Neutral'
                                    }"
                                >
                                    {{ row.sentiment }}
                                </span>
                            </td>

                        </tr>
                        
                        <tr v-if="props.feedback.data.length === 0">
                            <td colspan="4" class="py-16 text-center text-gray-400 italic bg-gray-50/30">
                                <div class="flex flex-col items-center justify-center gap-3">
                                    <span class="text-3xl opacity-40">🔍</span>
                                    <span class="text-xs font-bold">No feedback found matching your criteria.</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="pt-5 border-t border-gray-100 flex justify-between items-center mt-auto">
                <div class="text-[10px] text-gray-400 font-black uppercase tracking-[0.2em] bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-100">
                    <span v-if="props.feedback.total > 0">
                        Page <span class="text-gray-700">{{ props.feedback.current_page }}</span> of <span class="text-gray-700">{{ props.feedback.last_page }}</span>
                    </span>
                    <span v-else>No Pages</span>
                </div>
                
                <div class="flex gap-2">
                    <Link 
                        v-if="props.feedback.prev_page_url" 
                        :href="props.feedback.prev_page_url"
                        :preserve-scroll="true"
                        :preserve-state="true"
                        class="px-5 py-2 bg-white border border-gray-200 text-gray-600 rounded-xl text-[10px] uppercase tracking-widest font-black hover:bg-[#0c4b33] hover:text-white hover:border-[#0c4b33] transition-all shadow-sm"
                    >
                        Prev
                    </Link>
                    <Link 
                        v-if="props.feedback.next_page_url" 
                        :href="props.feedback.next_page_url"
                        :preserve-scroll="true"
                        :preserve-state="true"
                        class="px-5 py-2 bg-white border border-gray-200 text-gray-600 rounded-xl text-[10px] uppercase tracking-widest font-black hover:bg-[#0c4b33] hover:text-white hover:border-[#0c4b33] transition-all shadow-sm"
                    >
                        Next
                    </Link>
                </div>
            </div>

        </div>

    </DashboardLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent; 
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1; 
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8; 
}
/* Ensure line-clamp works for tailwind text truncation */
.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;  
  overflow: hidden;
}
</style>