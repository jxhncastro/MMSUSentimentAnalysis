<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import debounce from 'lodash/debounce'; 
import Swal from 'sweetalert2';
import axios from 'axios'; // Ensure you have axios installed for the status polling

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

// --- BATCH PROGRESS STATE & LOGIC ---
const batchProgress = ref({
    status: 'idle', // 'idle', 'processing', 'completed'
    total_rows: 0,
    processed_rows: 0
});

let statusInterval;

const checkAnalysisStatus = async () => {
    try {
        const response = await axios.get('/analysis-status');
        batchProgress.value = response.data;
        
        if (batchProgress.value.status === 'completed') {
            clearInterval(statusInterval);
        }
    } catch (e) {
        console.error("Waiting for active batch...", e);
    }
};

onMounted(() => {
    statusInterval = setInterval(checkAnalysisStatus, 3000); 
});

onUnmounted(() => {
    if (statusInterval) clearInterval(statusInterval);
});

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
        only: ['feedback', 'stats']
    });
}, 300);

const toggleSort = () => {
    if (sortSentiment.value === '') {
        sortSentiment.value = 'desc'; 
    } else if (sortSentiment.value === 'desc') {
        sortSentiment.value = 'asc';  
    } else {
        sortSentiment.value = '';     
    }
    performSearch();
};

watch(search, () => performSearch());
watch(unitFilter, () => performSearch());

const form = useForm({});

const submitClear = () => {
    const swalWithBootstrapButtons = Swal.mixin({
        customClass: {
            confirmButton: "mx-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-bold",
            cancelButton: "mx-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-bold"
        },
        buttonsStyling: false
    });

    swalWithBootstrapButtons.fire({
        title: "ARE YOU SURE?",
        text: "This will permanently delete ALL feedback data. This action cannot be undone!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes",
        cancelButtonText: "No",
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            form.post('/clear-data', {
                preserveScroll: true,
                onSuccess: () => {
                    swalWithBootstrapButtons.fire({
                        title: "System Cleared!",
                        text: "All data has been removed.",
                        icon: "success"
                    });
                },
                onError: () => {
                    swalWithBootstrapButtons.fire({
                        title: "Error!",
                        text: "Could not clear data.",
                        icon: "error"
                    });
                }
            });
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            swalWithBootstrapButtons.fire({
                title: "Cancelled",
                text: "Your data is safe :)",
                icon: "error"
            });
        }
    });
};

const refreshData = () => {
    isRefreshing.value = true;
    router.reload({ 
        only: ['feedback', 'stats', 'topPerformers', 'needsImprovement'],
        onFinish: () => { isRefreshing.value = false; }
    });
};

const showAllTop = ref(false);
const showAllNeedsImprovement = ref(false);

const visibleTopPerformers = computed(() => {
    return showAllTop.value ? props.topPerformers : props.topPerformers.slice(0, 3);
});

const visibleNeedsImprovement = computed(() => {
    return showAllNeedsImprovement.value ? props.needsImprovement : props.needsImprovement.slice(0, 3);
});
</script>

<template>
    <Head title="MMSU Sentiment Dashboard" />

    <DashboardLayout>
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div class="flex items-center gap-6">
                <div>
                    <h2 class="text-3xl font-black text-[#0c4b33] uppercase tracking-wide">Executive Overview</h2>
                    <p class="text-base text-gray-500 font-bold uppercase tracking-tighter">Stakeholder Feedback Analysis</p>
                </div>

                <div class="flex items-center gap-3 bg-white border border-gray-100 shadow-sm px-5 py-3 rounded-[20px]">
                    <div class="bg-[#0c4b33] text-white p-2 rounded-lg shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-black text-gray-400 uppercase tracking-widest leading-none">Total CSV Records</p>
                        <p class="text-2xl font-black text-gray-800 leading-tight">
                            {{ props.stats.total_csv_rows }} 
                            <span class="text-xs text-gray-400 font-bold ml-1 italic">Raw Data</span>
                        </p>
                    </div>
                </div>
            </div>
    
            <button 
                @click="submitClear"
                type="button" 
                :disabled="form.processing"
                class="bg-white border border-red-200 text-red-500 hover:bg-red-500 hover:text-white px-5 py-2.5 rounded-xl text-sm font-black uppercase tracking-widest transition flex items-center gap-2 shadow-sm hover:shadow-md cursor-pointer disabled:opacity-50"
            >
                <span class="text-lg" v-if="!form.processing">🗑️</span>
                <span class="text-lg animate-spin" v-else>⏳</span>
                {{ form.processing ? 'Clearing...' : 'Clear All Data' }}
            </button>
        </div>

        <div v-if="batchProgress?.status !== 'idle'" class="mb-8 overflow-hidden bg-white p-4 rounded-[20px] border border-[#0c4b33]/10 shadow-sm">
            <h3 class="text-center text-xl font-black mb-2 uppercase tracking-widest transition-colors duration-500"
                :class="batchProgress?.status === 'completed' ? 'text-green-600' : 'text-[#0c4b33] animate-pulse'">
                {{ batchProgress?.status === 'completed' ? '🎉 Analysis Complete!' : `🤖 BERT is Analyzing ${batchProgress?.total_rows || 0} Rows...` }}
            </h3>
            
            <div v-if="batchProgress?.status === 'processing'" class="w-full bg-gray-100 rounded-full h-2 mt-3 overflow-hidden">
                <div class="bg-[#0c4b33] h-2 rounded-full transition-all duration-500 ease-out" 
                     :style="{ width: ((batchProgress.processed_rows / batchProgress.total_rows) * 100) + '%' }">
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-5 mb-8">
            <div v-for="(item, index) in [
                { label: 'Total Feedback', val: props.stats.total, color: 'bg-white border-gray-100 border text-gray-800' },
                { label: 'Positive', val: props.stats.positive, color: 'bg-[#0C4B05] text-white' },
                { label: 'Negative', val: props.stats.negative, color: 'bg-[#DE1900] text-white' },
                { label: 'Neutral', val: props.stats.neutral, color: 'bg-[#FFCD00] text-white' }
            ]" :key="index" 
                 class="rounded-[25px] p-6 shadow-sm transition hover:scale-105 duration-300 flex flex-col justify-between h-36"
                 :class="item.color">
                <div class="text-xs opacity-90 uppercase tracking-[0.1em] font-black leading-tight">{{ item.label }}</div>
                <div class="text-4xl font-black">{{ item.val }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-12 items-start">
            
            <div class="bg-gradient-to-br from-[#0C4B05] to-[#1a6e4d] rounded-[35px] p-8 shadow-sm text-white border-b-8 border-[#FFCD00]">
                 <div class="flex justify-between items-start mb-8">
                    <div>
                        <h3 class="font-black text-xs uppercase tracking-widest text-yellow-400">Excellence Awardees</h3>
                        <p class="text-2xl font-black uppercase">Top Offices</p>
                    </div>
                    <span class="text-3xl">🏆</span>
                 </div>
                 
                 <div class="space-y-6">
                    <div v-if="props.topPerformers.length === 0" class="text-center text-base opacity-50 py-10">No data available yet.</div>
                    
                    <div v-else v-for="(item, i) in visibleTopPerformers" :key="i" class="flex items-center justify-between group">
                        <div class="flex items-center gap-4">
                            <div class="bg-white/20 w-10 h-10 rounded-full flex items-center justify-center text-white font-black text-sm shadow-sm">{{ i + 1 }}</div>
                            <div>
                                <div class="text-sm font-black uppercase tracking-tight">{{ item.unit }}</div>
                                <div class="text-xs opacity-70">Highest Satisfaction Rating</div>
                            </div>
                        </div>
                        <span class="text-base font-black text-yellow-400">{{ item.score }}</span>
                    </div>

                    <button 
                        v-if="props.topPerformers.length > 3"
                        @click="showAllTop = !showAllTop"
                        class="w-full mt-4 py-3 text-xs font-black uppercase tracking-widest text-yellow-400 border border-white/20 rounded-xl hover:bg-white/10 transition-colors bg-white/5"
                    >
                        {{ showAllTop ? 'Show Less' : 'See More' }}
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-[35px] p-8 shadow-sm border border-red-50 border-b-8 border-red-500">
                 <div class="flex justify-between items-start mb-8">
                    <div>
                        <h3 class="font-black text-xs uppercase tracking-widest text-red-500">Action Required</h3>
                        <p class="text-2xl font-black text-gray-800 uppercase leading-tight">Top Offices Needing Improvement</p>
                    </div>
                    <span class="text-3xl">⚠️</span>
                 </div>
                 
                 <div class="space-y-6">
                        <div v-if="props.needsImprovement.length === 0" class="text-center text-gray-400 text-base py-10">No critical issues found.</div>
                        
                        <div v-else v-for="(item, i) in visibleNeedsImprovement" :key="i" class="flex items-center justify-between group">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-black text-sm">{{ i + 1 }}</div>
                                <div>
                                    <div class="text-sm font-black text-gray-700 uppercase tracking-tight">{{ item.unit }}</div>
                                    <div class="text-xs text-red-400 font-bold uppercase tracking-tighter italic">{{ item.issue }}</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-base font-black text-red-500">{{ item.score }}</span>
                            </div>
                        </div>

                        <button 
                            v-if="props.needsImprovement.length > 3"
                            @click="showAllNeedsImprovement = !showAllNeedsImprovement"
                            class="w-full mt-4 py-3 text-xs font-black uppercase tracking-widest text-red-500 border border-red-100 rounded-xl hover:bg-red-50 transition-colors bg-red-50/30"
                        >
                            {{ showAllNeedsImprovement ? 'Show Less' : 'See More' }}
                        </button>
                    </div>
            </div>
        </div>

        <div class="bg-white rounded-[25px] p-8 shadow-sm border border-gray-100 font-sans">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <div>
                    <h2 class="text-3xl font-black text-[#0c4b33] tracking-wide uppercase">Datasets</h2>
                    <div class="flex items-center gap-3 mt-1">
                        <p class="text-sm text-gray-500 font-bold">Processed review data.</p>
                    </div>
                </div>
                
                <button 
                    @click="refreshData"
                    :disabled="isRefreshing"
                    class="flex items-center gap-2 px-4 py-2 bg-white hover:bg-gray-50 text-gray-600 text-sm font-bold rounded-xl border border-gray-200 transition shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <svg v-if="isRefreshing" class="animate-spin h-4 w-4 text-[#0c4b33]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>{{ isRefreshing ? 'Refreshing...' : 'Refresh' }}</span>
                </button>
            </div>

            <div class="flex flex-col md:flex-row gap-4 mb-4">
                
                <div class="relative flex-grow md:max-w-xs">
                    <span class="absolute left-3 top-3 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input 
                        v-model="search"
                        type="text" 
                        placeholder="Search feedback..." 
                        class="w-full bg-gray-50 border-none text-gray-700 text-sm pl-10 pr-4 py-3 rounded-xl focus:ring-1 focus:ring-[#0c4b33] placeholder-gray-400 font-medium transition-colors"
                    >
                </div>

                <div class="relative w-full md:w-72">
                    <select 
                        v-model="unitFilter"
                        class="appearance-none w-full bg-gray-50 border-none text-gray-700 text-sm font-bold py-3 pl-4 pr-10 rounded-xl focus:ring-1 focus:ring-[#0c4b33] cursor-pointer truncate"
                    >
                        <option value="">All Operating Units</option>
                        <option v-for="unit in operatingUnits" :key="unit" :value="unit">
                            {{ unit }}
                        </option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>

                <div class="flex items-center text-sm text-gray-500 font-mono ml-auto">
                    Showing <span class="text-gray-800 font-bold mx-1">{{ props.feedback.total }}</span> records
                </div>
            </div>
            
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-gray-400 text-xs font-black uppercase tracking-widest border-b border-gray-100">
                            <th class="py-4 pr-4 pl-4">Operating Unit</th>
                            <th class="py-4 pr-4 w-1/3">Review</th>
                            <th class="py-4 pr-4">Services</th>
                            <th class="py-4 pr-4">Theme</th>
                            <th 
                                class="py-4 text-right pr-4 cursor-pointer hover:text-[#0c4b33] transition-colors select-none"
                                @click="toggleSort"
                            >
                                <div class="flex items-center justify-end gap-1">
                                    Sentiment
                                    <span v-if="sortSentiment === 'asc'">▲</span>
                                    <span v-else-if="sortSentiment === 'desc'">▼</span>
                                    <span v-else class="text-gray-300">↕</span>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 text-sm">
                        <tr v-for="row in props.feedback.data" :key="row.id" class="group hover:bg-gray-50/80 transition-colors duration-150">
                            
                            <td class="py-5 pr-4 pl-4 align-top">
                                <span class="text-gray-700 font-bold text-xs bg-gray-100 px-3 py-1.5 rounded border border-gray-200 whitespace-nowrap uppercase tracking-tight">
                                    {{ row.operating_unit }}
                                </span>
                            </td>

                            <td class="py-5 pr-4 text-gray-600 text-sm leading-relaxed group-hover:text-gray-900 transition-colors align-top">
                                <div class="italic">
                                    "{{ row.feedback_text }}"
                                </div>
                            </td>

                            <td class="py-5 pr-4 align-top">
                                <div class="text-xs text-gray-500 uppercase font-mono font-bold" :title="row.services_availed">
                                    {{ row.services_availed || '---' }}
                                </div>
                            </td>

                            <td class="py-5 pr-4 align-top">
                                <span 
                                    v-if="row.topic"
                                    class="inline-flex items-center px-2 py-1 rounded bg-indigo-50 text-indigo-700 border border-indigo-200 text-xs font-black uppercase tracking-wider shadow-sm"
                                >
                                    {{ row.topic }}
                                </span>
                                <span v-else class="text-xs text-gray-300 font-mono font-bold">---</span>
                            </td>

                            <td class="py-5 text-right align-top pr-4">
                                <span 
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black uppercase tracking-wide border"
                                    :class="{
                                        'bg-green-50 text-green-700 border-green-200': row.sentiment === 'Positive',
                                        'bg-red-50 text-red-700 border-red-200': row.sentiment === 'Negative',
                                        'bg-gray-50 text-gray-600 border-gray-200': row.sentiment === 'Neutral'
                                    }"
                                >
                                    {{ row.sentiment }}
                                </span>
                            </td>

                        </tr>
                        
                        <tr v-if="props.feedback.data.length === 0">
                            <td colspan="5" class="py-12 text-center text-gray-400 italic">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <span class="text-3xl opacity-50">🔍</span>
                                    <span class="text-base">No data found matching your filters.</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="py-5 border-t border-gray-50 flex justify-between items-center mt-2">
                <div class="text-sm text-gray-500 font-bold uppercase tracking-wide">
                    <span v-if="props.feedback.total > 0">
                        Page {{ props.feedback.current_page }} of {{ props.feedback.last_page }}
                    </span>
                    <span v-else>No Pages</span>
                </div>
                <div class="flex gap-2">
                    <Link 
                        v-if="props.feedback.prev_page_url" 
                        :href="props.feedback.prev_page_url"
                        :preserve-scroll="true"
                        :preserve-state="true"
                        class="px-5 py-2.5 bg-white border border-gray-200 text-gray-600 rounded-lg text-sm font-bold hover:bg-[#0c4b33] hover:text-white hover:border-[#0c4b33] transition shadow-sm"
                    >
                        Previous
                    </Link>
                    <Link 
                        v-if="props.feedback.next_page_url" 
                        :href="props.feedback.next_page_url"
                        :preserve-scroll="true"
                        :preserve-state="true"
                        class="px-5 py-2.5 bg-white border border-gray-200 text-gray-600 rounded-lg text-sm font-bold hover:bg-[#0c4b33] hover:text-white hover:border-[#0c4b33] transition shadow-sm"
                    >
                        Next
                    </Link>
                </div>
            </div>

        </div>

    </DashboardLayout>
</template>

<style scoped>
/* Inject Calibri Font and apply to container */
.dashboard-container {
    font-family: 'Calibri', 'Candara', 'Segui UI', 'Optima', Arial, sans-serif;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent; 
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1; 
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8; 
}
</style>