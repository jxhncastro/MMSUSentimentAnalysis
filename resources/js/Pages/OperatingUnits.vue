<script setup>
import { ref, computed, watch } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';

// --- PROPS (Data from Controller) ---
const props = defineProps({
    charts: {
        type: Object,
        default: () => ({
            overall_sentiment: { Positive: 0, Negative: 0, Neutral: 0 },
            sentiment_by_topic: {},
            top_negative: [], 
            top_positive: [],
            confidence_levels: { High: 0, Medium: 0, Low: 0 }
        })
    },
    recent_feedback: {
        type: Array,
        default: () => []
    },
    filters: Object
});

// --- 1. OPERATING UNITS DATA ---
const unitData = {
    "Accounting Office": ["Signing of Clearance of Students", "Certification of Net take home pay", "Signing of clearance of employees"],
    "Administrative Service Division ": ["Issuance of Certificate of Appearance", "Issuance of an Affidavit of Loss (University/Library Identification Card)", "Issuance of Certification of No Pending Case", "Confirmation of GSIS Loan", "Online Application of Bond", "Authentication/Certification of Document/s"],
    "Alumni Relations Office": ["Signing of University Clearance", "Application for Alumni Identification Card", "Issuance of Alumni Identification Card"],
    "Auxillary Directorate": ["Satellite Canteen Services", "Food Catering Services", "Reservation of Facilities", "Reservation of Accommodation", "Renting Out of Academic Caps and Gowns", "Marketing Center Services"],
    "Budget Management Section": ["Process Obligation/Budget Utilization"],
    "CAFSD": ["Admission and Enrolment", "Processing of Scholarship", "Leave of Absence (LOA)", "Completion/Removal of Grades", "Reading Center Services", "Signing of Student Clearance"],
    "CAS": ["Admission and Enrolment", "Laboratory Service (Biology, Chemistry, Physics)", "Library Service (Reading Center)", "Resolution of Student Grievances", "Signing of Student Clearance"],
    "CASAT": ["Cash Management Services", "Library Services", "Health & Wellness Services", "Center for Diver Training Services", "Signing of Student Clearance"],
    "CBEA": ["Admission and Registration", "Online Adding/Changing/Dropping of Subjects", "Online Application for Graduation", "Reading Center Services", "Signing of Student Clearance"],
    "CCIS": ["Admission and Enrolment", "Application for Dropping/Changing of Subjects", "Library Service (Reading Center)", "Signing of Student Clearance"],
    "CHS": ["Admission and Registration", "First Issuance of TOR", "First Issuance of Diploma", "Processing of Application for Scholarships", "Signing of Student Clearance"],
    "CIT": ["Admission and Registration", "Issuance of Identification Card (ID)", "Authentication of Documents", "Cashier’s Office Services", "Signing of Student Clearance"],
    "COE": ["Admission and Enrolment", "Course Shifting/Transfer", "Laboratory Services", "Guidance - Issuance of CGMC", "Extension Services", "Signing of Student Clearance"],
    "COL": ["Admission and Enrolment for Student Applicant", "Admission for Returning Students", "Dropping/Changing of Subjects", "Signing of Student Clearance"],
    "COM": ["Application for Doctor of Medicine Program", "Admission of Accepted Freshmen", "Admission for Returning Students", "Signing of Student Clearance"],
    "CTE": ["Registrar’s Office: Admission", "Library Services", "Recognition of Student Organizations", "Cash Management Services", "Signing of Student Clearance"],
    "CVM": ["Admission and Enrolment", "Processing of Scholarship", "Use of Laboratory Facilities/Services", "Filing of Complaints", "Signing of Student Clearance"],
    "Cash Management": ["Check/Advice to Debit Account Disbursement", "Collection of Payments", "Cash Disbursement"],
    "Distance Learning Office": ["Answering of Inquiries", "Request for Training on ODEL"],
    "ETEEAP": ["Admission and Registration of ETEEAP Students"],
    "Extension Directorate": ["Seedstock Dispersal Project", "Requested Trainings", "Information Technology Caravan"],
    "General Services Directorate": ["Utilization of University Vehicles", "Repair and Maintenance of Buildings", "Utilization of Sound System"],
    "Graduate School": ["Admission of New Students", "Presentation of Thesis/Dissertation Proposal", "Conduct of Comprehensive Examination"],
    "Health and Wellness Services": ["Medical and Dental Examination", "Medical and Dental Consultation", "Annual Medical Check Up"],
    "Human Resources Management Office ": ["Application for Employment", "Issuance of Service Record", "Application for Leave"],
    "Information Technology Center": ["Technical Services and Maintenance", "ICT Equipment Repair and Maintenance"],
    "Innovation and Technology Directorate (former S&T Park)": ["Food Processing Facilities", "Intellectual Property Management", "Technology Business Incubation"],
    "Instructional Materials Development Office": ["Instructional Materials Development"],
    "Internal Audit Services": ["Request for Audit Reports"],
    "Internationalization, Linkages, and Partnership Directorate": ["Student Internship Abroad Program", "Request for the Forging of Partnerships"],
    "NBERIC": ["Analytical Services", "Reservation of Facilities"],
    "OJT - SIPP Practicum Coordinating Office": ["Internship Deployment Process"],
    "Office of the University and Board Secretary": ["Issuance of AdCo/BOR Resolutions", "Issuance of Memorandum/Special Order"],
    "PPDO": ["Project Management", "Preparation of Program of Works"],
    "Planning Office": ["Provision of Institutional Data"],
    "Procurement Division": ["Procurement through Competitive Bidding", "Sale of Bidding Documents"],
    "Quality Assurance": ["Providing Accreditation Status", "Mock Accreditation of Academic Programs"],
    "Records and Archives Management Office": ["Issuance of Service Records", "Certification of Machine Copies"],
    "Research Directorate": ["Laboratory Animal Care Facility", "Statistical Advising", "Provision of Weather/Soil Data"],
    "Strategic Communication  Office": ["Documentation of Events", "Information Services", "Livestreaming of University events"],
    "Students and Affairs Services Office": ["Issuance of CGMC", "Application for Tertiary Education Subsidy (TES)", "Referral Counselling"],
    "Supply and Property Management Office": ["Issuance of Supplies and Equipment", "Signing of University Clearance"],
    "URERB": ["Initial Submission of Research Protocols", "Post-approval Submissions"],
    "University Library System": ["Charging/Borrowing of Information Resources", "Discharging/Returning Resources"],
    "University Registrar's Office": ["Admission and Registration", "Issuance of TOR/Diploma", "Authentication of Documents", "Issuance of ID", "Leave of Absence (LOA)"]
};

// --- STATE & COMPUTED ---
const operatingUnits = Object.keys(unitData).sort();

const selectedUnit = ref(props.filters?.unit || ""); 
const selectedService = ref(props.filters?.service || "All Services");
const isLoading = ref(false); 

const services = computed(() => {
    if (!selectedUnit.value) return ["All Services"];
    const list = unitData[selectedUnit.value] || [];
    return ["All Services", ...list];
});

const formatNum = (num) => Number(num || 0).toLocaleString();

// --- CHART COMPUTATIONS ---
const totalReviews = computed(() => {
    if (!props.charts?.overall_sentiment) return 0;
    return (props.charts.overall_sentiment.Positive || 0) + 
           (props.charts.overall_sentiment.Negative || 0) + 
           (props.charts.overall_sentiment.Neutral || 0);
});

const sentimentGradient = computed(() => {
    const total = totalReviews.value;
    if (total === 0) return 'conic-gradient(#f3f4f6 0% 100%)';

    const pos = ((props.charts.overall_sentiment?.Positive || 0) / total) * 100;
    const neu = ((props.charts.overall_sentiment?.Neutral || 0) / total) * 100;

    return `conic-gradient(
        #0c4b33 0% ${pos}%, 
        #fbbf24 ${pos}% ${pos + neu}%, 
        #ef4444 ${pos + neu}% 100%
    )`;
});

const positivePercentage = computed(() => {
    return totalReviews.value === 0 ? 0 : Math.round(((props.charts.overall_sentiment?.Positive || 0) / totalReviews.value) * 100);
});

const maxTopicCount = computed(() => {
    let max = 0;
    if (!props.charts?.sentiment_by_topic) return 1;
    Object.values(props.charts.sentiment_by_topic).forEach(t => {
        const total = (t.positive || 0) + (t.negative || 0) + (t.neutral || 0);
        if (total > max) max = total;
    });
    return max || 1;
});

// --- FILTER HANDLING ---
const fetchFilteredData = debounce(() => {
    // Dynamically build params so we don't send null/empty strings to the backend
    let params = {};
    if (selectedUnit.value) {
        params.unit = selectedUnit.value;
    }
    if (selectedService.value && selectedService.value !== "All Services") {
        params.service = selectedService.value;
    }

    // Use window.location.pathname so it always hits the correct route
    router.get(window.location.pathname, params, {
        preserveState: true,
        preserveScroll: true,
        only: ['charts', 'recent_feedback', 'filters'],
        onStart: () => isLoading.value = true,
        onFinish: () => isLoading.value = false
    });
}, 300);

// Watcher: Only reset and fetch if the Unit ACTUALLY changes
watch(selectedUnit, (newVal, oldVal) => {
    if (newVal !== oldVal) {
        selectedService.value = "All Services";
        fetchFilteredData();
    }
});

// Fetch data when a specific service is clicked
const selectService = (service) => {
    if (selectedService.value !== service) {
        selectedService.value = service;
        fetchFilteredData();
    }
};

// Sync state if user uses browser Back/Forward buttons
watch(() => props.filters, (newFilters) => {
    selectedUnit.value = newFilters?.unit || "";
    selectedService.value = newFilters?.service || "All Services";
}, { deep: true });

</script>

<template>
    <Head :title="selectedUnit ? `Analysis - ${selectedUnit}` : 'Global Analytics'" />

    <DashboardLayout>
        <div class="flex flex-col gap-6 h-full p-2 lg:p-4">
            
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="font-bold text-2xl text-[#0c4b33] uppercase tracking-tight">
                        {{ selectedUnit ? 'Operating Unit Analysis' : 'Global Analytics' }}
                    </h2>
                    <p class="text-xs text-gray-500 font-medium italic">
                        Viewing analytics for: <span class="text-yellow-600 font-bold">{{ selectedUnit || 'All Operating Units' }}</span>
                    </p>
                </div>
                
                <div class="flex items-center gap-3 bg-white p-2 px-4 rounded-2xl shadow-sm border border-gray-100 transition-all hover:shadow-md">
                    <label class="text-[10px] font-black text-gray-400 uppercase">Switch Unit</label>
                    <select 
                        v-model="selectedUnit" 
                        class="border-none bg-transparent focus:ring-0 text-sm font-bold text-gray-700 min-w-[300px] cursor-pointer"
                    >
                        <option value="">All Operating Units</option>
                        <option v-for="unit in operatingUnits" :key="unit" :value="unit">
                            {{ unit }}
                        </option>
                    </select>
                </div>
            </div>

            <div :class="['flex flex-col gap-6 transition-all duration-300', isLoading ? 'opacity-50 blur-[2px] pointer-events-none animate-pulse' : 'opacity-100']">
                
                <div v-if="selectedUnit" class="flex flex-col gap-2">
                    <div class="flex justify-between items-center px-2">
                        <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Filter by Service</h3>
                        <span class="text-[10px] font-bold text-[#0c4b33] uppercase">{{ selectedService }}</span>
                    </div>
                    
                    <div class="bg-gradient-to-r from-[#0c4b33] to-[#1a6e4d] rounded-2xl py-4 px-6 shadow-lg overflow-x-auto no-scrollbar border-b-4 border-yellow-400">
                        <div class="flex gap-3">
                            <button 
                                v-for="service in services" 
                                :key="service" 
                                @click="selectService(service)"
                                class="whitespace-nowrap px-4 py-2 rounded-full text-xs font-bold transition-all duration-300 border focus:outline-none"
                                :class="[
                                    selectedService === service 
                                    ? 'bg-yellow-400 text-[#0c4b33] border-yellow-400 shadow-md scale-105' 
                                    : 'bg-white/10 text-white border-white/20 hover:bg-white/20 hover:border-white/40'
                                ]"
                            >
                                {{ service }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <div class="bg-white rounded-[35px] p-8 shadow-sm min-h-[350px] flex flex-col items-center justify-center relative border border-gray-100 hover:shadow-md transition-shadow">
                        <h3 class="absolute top-8 left-0 right-0 text-center font-bold text-gray-400 text-[10px] uppercase tracking-[0.2em]">
                            SENTIMENT RATIO
                        </h3>
                        
                        <div class="relative w-52 h-52 rounded-full shadow-xl transition-all duration-700 ease-in-out hover:scale-105"
                             :style="{ background: sentimentGradient }">
                            <div class="absolute inset-8 bg-white rounded-full flex flex-col items-center justify-center shadow-inner">
                                <span class="text-4xl font-black text-[#0c4b33]">{{ positivePercentage }}%</span>
                                <span class="text-[9px] text-gray-400 font-bold uppercase tracking-tighter">Positive</span>
                            </div> 
                        </div>

                        <div class="flex gap-6 mt-10">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 bg-[#0c4b33] rounded-full shadow-sm"></span> 
                                <span class="text-[9px] font-black text-gray-500 uppercase">Pos: {{ formatNum(props.charts?.overall_sentiment?.Positive) }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 bg-yellow-400 rounded-full shadow-sm"></span> 
                                <span class="text-[9px] font-black text-gray-500 uppercase">Neu: {{ formatNum(props.charts?.overall_sentiment?.Neutral) }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 bg-red-500 rounded-full shadow-sm"></span> 
                                <span class="text-[9px] font-black text-gray-500 uppercase">Neg: {{ formatNum(props.charts?.overall_sentiment?.Negative) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-[35px] p-8 shadow-sm min-h-[350px] flex flex-col relative border border-gray-100 overflow-y-auto custom-scrollbar hover:shadow-md transition-shadow">
                        
                        <div v-if="selectedUnit" class="flex-1 flex flex-col">
                            <h3 class="text-center font-bold text-gray-400 text-[10px] uppercase tracking-[0.2em] mb-6 sticky top-0 bg-white z-10 pb-2">
                                SENTIMENT DRIVERS (TOPICS)
                            </h3>
                            
                            <div class="flex-1 flex flex-col gap-5">
                                <div v-if="Object.keys(props.charts?.sentiment_by_topic || {}).length === 0" class="flex-1 flex items-center justify-center text-gray-300 text-xs italic">
                                    No topic data available for this unit.
                                </div>

                                <div v-for="(counts, topic) in props.charts.sentiment_by_topic" :key="topic" class="group relative cursor-default">
                                    <div class="flex justify-between items-end text-[10px] font-bold text-gray-600 mb-1.5">
                                        <span class="truncate pr-2">{{ topic }}</span>
                                        <span class="opacity-50 group-hover:opacity-0 transition-opacity">{{ formatNum((counts.positive || 0) + (counts.negative || 0) + (counts.neutral || 0)) }}</span>
                                    </div>

                                    <div class="absolute right-0 -top-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex gap-1.5 text-[8px] font-black">
                                        <span v-if="counts.positive" class="text-green-700 bg-green-100 px-1.5 rounded">POS: {{ counts.positive }}</span>
                                        <span v-if="counts.neutral" class="text-yellow-700 bg-yellow-100 px-1.5 rounded">NEU: {{ counts.neutral }}</span>
                                        <span v-if="counts.negative" class="text-red-700 bg-red-100 px-1.5 rounded">NEG: {{ counts.negative }}</span>
                                    </div>

                                    <div class="w-full h-3 bg-gray-100 rounded-full flex overflow-hidden shadow-inner">
                                        <div v-if="counts.positive" class="bg-gradient-to-r from-[#0c4b33] to-[#1a6e4d] h-full transition-all duration-700 group-hover:brightness-110" :style="{ width: ((counts.positive || 0) / maxTopicCount * 100) + '%' }"></div>
                                        <div v-if="counts.neutral" class="bg-gradient-to-r from-yellow-400 to-yellow-300 h-full transition-all duration-700 group-hover:brightness-110" :style="{ width: ((counts.neutral || 0) / maxTopicCount * 100) + '%' }"></div>
                                        <div v-if="counts.negative" class="bg-gradient-to-r from-red-600 to-red-400 h-full transition-all duration-700 group-hover:brightness-110" :style="{ width: ((counts.negative || 0) / maxTopicCount * 100) + '%' }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="flex-1 flex flex-col">
                            <h3 class="text-center font-bold text-red-500 text-[10px] uppercase tracking-[0.2em] mb-6 sticky top-0 bg-white z-10 pb-2">
                                ⚠️ TOP OFFICES NEEDING IMPROVEMENT
                            </h3>
                            <div class="space-y-5">
                                <div v-for="(item, i) in props.charts.top_negative" :key="i" class="flex items-center gap-4 group">
                                    <span class="text-[12px] font-black text-red-200 w-4 text-center group-hover:text-red-400 transition-colors">{{ i + 1 }}</span>
                                    <div class="flex-1">
                                        <div class="flex justify-between text-[11px] font-bold text-gray-700 mb-1.5">
                                            <span class="truncate w-48 group-hover:text-red-600 transition-colors">{{ item.office }}</span>
                                            <span class="text-red-500 bg-red-50 px-2 py-0.5 rounded-md shadow-sm">{{ formatNum(item.count) }}</span>
                                        </div>
                                        <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden shadow-inner">
                                            <div class="bg-gradient-to-r from-red-600 to-red-400 h-full rounded-full transition-all duration-1000 ease-out group-hover:brightness-110" 
                                                 :style="{ width: (item.count / (props.charts.top_negative[0]?.count || 1) * 100) + '%' }">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="props.charts?.top_negative?.length === 0" class="text-center text-sm text-gray-400 py-10 flex-1 flex items-center justify-center italic">
                                    No negative feedback found! 🎉
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
                    <div class="transition-all duration-300 rounded-[25px] p-6 shadow-sm flex flex-col justify-between h-32 hover:scale-105 hover:shadow-md cursor-default bg-white text-gray-800 border border-gray-100">
                        <div class="flex justify-between items-start">
                            <div class="text-[9px] opacity-80 uppercase font-black tracking-wider leading-tight text-gray-500">Total Feedback</div>
                            <span class="text-sm grayscale opacity-50">📊</span>
                        </div>
                        <div class="text-3xl font-black text-gray-800">{{ formatNum(totalReviews) }}</div>
                    </div>

                    <div class="transition-all duration-300 rounded-[25px] p-6 shadow-sm flex flex-col justify-between h-32 hover:scale-105 hover:shadow-md cursor-default bg-[#0c4b33] text-white">
                        <div class="flex justify-between items-start">
                            <div class="text-[9px] opacity-80 uppercase font-black tracking-wider leading-tight text-green-100">Positive</div>
                            <span class="text-sm opacity-80">😊</span>
                        </div>
                        <div class="text-3xl font-black">{{ formatNum(props.charts?.overall_sentiment?.Positive) }}</div>
                    </div>

                    <div class="transition-all duration-300 rounded-[25px] p-6 shadow-sm flex flex-col justify-between h-32 hover:scale-105 hover:shadow-md cursor-default bg-red-500 text-white">
                        <div class="flex justify-between items-start">
                            <div class="text-[9px] opacity-80 uppercase font-black tracking-wider leading-tight text-red-100">Negative</div>
                            <span class="text-sm opacity-80">😟</span>
                        </div>
                        <div class="text-3xl font-black">{{ formatNum(props.charts?.overall_sentiment?.Negative) }}</div>
                    </div>

                    <div class="transition-all duration-300 rounded-[25px] p-6 shadow-sm flex flex-col justify-between h-32 hover:scale-105 hover:shadow-md cursor-default bg-yellow-400 text-white">
                        <div class="flex justify-between items-start">
                            <div class="text-[9px] opacity-80 uppercase font-black tracking-wider leading-tight text-yellow-100">Neutral</div>
                            <span class="text-sm opacity-80">😐</span>
                        </div>
                        <div class="text-3xl font-black drop-shadow-sm">{{ formatNum(props.charts?.overall_sentiment?.Neutral) }}</div>
                    </div>
                </div>

                <div class="bg-white rounded-[25px] shadow-sm border border-gray-100 overflow-hidden mb-6 flex flex-col">
                    <div class="p-6 pb-4 border-b border-gray-50">
                        <h3 class="font-bold text-gray-400 text-[10px] uppercase tracking-[0.2em]">
                            📋 LATEST FEEDBACK FOR {{ selectedUnit ? selectedUnit.toUpperCase() : 'ALL UNITS' }}
                        </h3>
                    </div>
                    
                    <div class="overflow-x-auto p-2">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50/50">
                                <tr class="text-[10px] font-black text-gray-400 uppercase tracking-wider">
                                    <th class="py-3 pl-4 rounded-l-lg">Office</th>
                                    <th class="py-3">Feedback</th>
                                    <th class="py-3">Topic</th>
                                    <th class="py-3 text-right pr-4 rounded-r-lg">Sentiment</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="row in props.recent_feedback" :key="row.id" class="group hover:bg-gray-50 transition-colors">
                                    <td class="py-4 pl-4 font-bold text-gray-700 text-xs whitespace-nowrap">{{ row.office }}</td>
                                    <td class="py-4 max-w-lg text-gray-600 text-xs pr-6 leading-relaxed">
                                        <span class="text-gray-400 mr-1">"</span>{{ row.comment }}<span class="text-gray-400 ml-1">"</span>
                                    </td>
                                    <td class="py-4">
                                        <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-[9px] font-bold uppercase tracking-wider border border-gray-200 shadow-sm">{{ row.topic }}</span>
                                    </td>
                                    <td class="py-4 text-right pr-4">
                                        <span class="inline-block px-2.5 py-1 rounded-md text-[10px] font-black uppercase tracking-wider shadow-sm border"
                                              :class="{
                                                  'bg-green-50 text-green-700 border-green-200': row.sentiment === 'Positive',
                                                  'bg-red-50 text-red-700 border-red-200': row.sentiment === 'Negative',
                                                  'bg-yellow-50 text-yellow-700 border-yellow-200': row.sentiment === 'Neutral'
                                              }">
                                            {{ row.sentiment }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="props.recent_feedback.length === 0">
                                    <td colspan="4" class="py-12 text-center text-sm text-gray-400 italic bg-gray-50/50 rounded-lg">
                                        No recent feedback matches the selected filters.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;  /* IE and Edge */
    scrollbar-width: none;  /* Firefox */
}
.custom-scrollbar::-webkit-scrollbar { 
    width: 6px; 
}
.custom-scrollbar::-webkit-scrollbar-thumb { 
    background: #e5e7eb; 
    border-radius: 10px; 
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #d1d5db;
}
</style>