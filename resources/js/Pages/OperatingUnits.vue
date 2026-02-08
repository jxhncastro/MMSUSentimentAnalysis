<script setup>
import { ref, computed } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head } from '@inertiajs/vue3';

// 1. Full Data Mapping from OR_SA - Sheet1.csv
const unitData = {
    "Accounting Office": ["Signing of Clearance of Students", "Certification of Net take home pay", "Signing of clearance of employees"],
    "Administrative Service Division ": ["Issuance of Certificate of Appearance", "Issuance of an Affidavit of Loss (University/Library Identification Card)", "Issuance of Certification of No Pending Case", "Confirmation of GSIS Loan", "Online Application of Bond", "Authentication/Certification of Document/s"],
    "Alumni Relations Office": ["Signing of University Clearance", "Application for Alumni Identification Card", "Issuance of Alumni Identification Card"],
    "Auxillary Directorate": ["Satellite Canteen Services", "Food Catering Services", "Reservation of Facilities", "Reservation of Accommodation", "Renting Out of Academic Caps and Gowns", "Marketing Center Services"],
    "Budget Management Section": ["Process Obligation/Budget Utilization"],
    "CAFSD": ["Admission and Enrolment", "Processing of Scholarship", "Leave of Absence (LOA)", "Completion/Removal of Grades", "Reading Center Services"],
    "CAS": ["Admission and Enrolment", "Laboratory Service (Biology, Chemistry, Physics)", "Library Service (Reading Center)", "Resolution of Student Grievances"],
    "CASAT": ["Cash Management Services", "Library Services", "Health & Wellness Services", "Center for Diver Training Services"],
    "CBEA": ["Admission and Registration", "Online Adding/Changing/Dropping of Subjects", "Online Application for Graduation", "Reading Center Services"],
    "CCIS": ["Admission and Enrolment", "Application for Dropping/Changing of Subjects", "Library Service (Reading Center)", "Signing of Student Clearance"],
    "CHS": ["Admission and Registration", "First Issuance of TOR", "First Issuance of Diploma", "Processing of Application for Scholarships"],
    "CIT": ["Admission and Registration", "Issuance of Identification Card (ID)", "Authentication of Documents", "Cashier’s Office Services"],
    "COE": ["Admission and Enrolment", "Course Shifting/Transfer", "Laboratory Services", "Guidance - Issuance of CGMC", "Extension Services"],
    "COL": ["Admission and Enrolment for Student Applicant", "Admission for Returning Students", "Dropping/Changing of Subjects"],
    "COM": ["Application for Doctor of Medicine Program", "Admission of Accepted Freshmen", "Admission for Returning Students"],
    "CTE": ["Registrar’s Office: Admission", "Library Services", "Recognition of Student Organizations", "Cash Management Services"],
    "CVM": ["Admission and Enrolment", "Processing of Scholarship", "Use of Laboratory Facilities/Services", "Filing of Complaints"],
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

const operatingUnits = Object.keys(unitData).sort();
const selectedUnit = ref("University Registrar's Office");

const services = computed(() => {
    return unitData[selectedUnit.value] || ["General Service"];
});
</script>

<template>
    <Head :title="`Analysis - ${selectedUnit}`" />

    <DashboardLayout>
        <div class="flex flex-col gap-6 h-full p-2">
            
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="font-bold text-2xl text-[#0c4b33] uppercase tracking-tight">Operating Unit Analysis</h2>
                    <p class="text-xs text-gray-500 font-medium italic">Viewing analytics for: <span class="text-yellow-600 font-bold">{{ selectedUnit }}</span></p>
                </div>
                
                <div class="flex items-center gap-3 bg-white p-2 px-4 rounded-2xl shadow-sm border border-gray-100">
                    <label class="text-[10px] font-black text-gray-400 uppercase">Switch Unit</label>
                    <select 
                        v-model="selectedUnit" 
                        class="border-none bg-transparent focus:ring-0 text-sm font-bold text-gray-700 min-w-[300px] cursor-pointer"
                    >
                        <option v-for="unit in operatingUnits" :key="unit" :value="unit">
                            {{ unit }}
                        </option>
                    </select>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <h3 class="text-[10px] font-bold text-gray-400 uppercase ml-2 tracking-widest">Available Services</h3>
                <div class="bg-gradient-to-r from-[#0c4b33] to-[#1a6e4d] rounded-2xl py-4 px-8 text-white text-sm font-semibold flex gap-10 shadow-lg overflow-x-auto no-scrollbar border-b-4 border-yellow-400">
                    <span v-for="service in services" :key="service" class="whitespace-nowrap flex items-center gap-3 animate-fade-in transition-all">
                        <div class="w-1.5 h-1.5 bg-yellow-400 rounded-full"></div>
                        {{ service }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <div class="bg-white rounded-[35px] p-8 shadow-sm min-h-[350px] flex flex-col items-center justify-center relative border border-gray-50">
                    <h3 class="absolute top-8 left-0 right-0 text-center font-bold text-gray-400 text-[10px] uppercase tracking-[0.2em]">
                        SENTIMENT RATIO
                    </h3>
                    
                    <div class="relative w-52 h-52 rounded-full bg-[conic-gradient(at_center,_#0c4b33_0deg_240deg,_#fbbf24_240deg_310deg,_#ef4444_310deg_360deg)] shadow-xl">
                        <div class="absolute inset-8 bg-white rounded-full flex flex-col items-center justify-center shadow-inner">
                            <span class="text-4xl font-black text-[#0c4b33]">92%</span>
                            <span class="text-[9px] text-gray-400 font-bold uppercase tracking-tighter">Positive</span>
                        </div> 
                    </div>

                    <div class="flex gap-6 mt-10">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 bg-[#0c4b33] rounded-full"></span> 
                            <span class="text-[9px] font-black text-gray-500 uppercase">Positive</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 bg-yellow-400 rounded-full"></span> 
                            <span class="text-[9px] font-black text-gray-500 uppercase">Neutral</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 bg-red-500 rounded-full"></span> 
                            <span class="text-[9px] font-black text-gray-500 uppercase">Negative</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-[35px] p-8 shadow-sm min-h-[350px] flex flex-col relative border border-gray-50">
                    <h3 class="text-center font-bold text-gray-400 text-[10px] uppercase tracking-[0.2em] mb-12">
                        MONTHLY SERVICE VOLUME
                    </h3>
                    
                    <div class="flex-1 flex items-end justify-between gap-3 px-6 pb-6 border-b border-gray-100 border-l border-gray-100">
                        <div v-for="(h, i) in [40, 75, 55, 100, 65, 80, 45]" :key="i" 
                             :style="{ height: h + '%' }" 
                             class="w-full bg-[#0c4b33] opacity-80 hover:opacity-100 hover:bg-yellow-500 transition-all duration-300 rounded-t-xl shadow-sm cursor-pointer group relative">
                        </div>
                    </div>

                    <div class="flex justify-between px-4 mt-6 text-[10px] font-bold text-gray-300 uppercase tracking-widest">
                        <span>Jan</span><span>Feb</span><span>Mar</span><span>Apr</span><span>May</span><span>Jun</span><span>Jul</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
                <div v-for="(card, index) in [
                    { label: 'Total Feedback', color: 'bg-white text-gray-800 border-gray-100 border', val: '1,420', icon: '📊' },
                    { label: 'Positive', color: 'bg-[#0c4b33] text-white', val: '1,280', icon: '😊' },
                    { label: 'Negative', color: 'bg-red-500 text-white', val: '65', icon: '😟' },
                    { label: 'Neutral', color: 'bg-yellow-500 text-white', val: '75', icon: '😐' }
                ]" :key="index" 
                    class="transition-all duration-300 rounded-[25px] p-6 shadow-sm flex flex-col justify-between h-32 hover:scale-105"
                    :class="card.color">
                    <div class="flex justify-between items-start">
                        <div class="text-[9px] opacity-80 uppercase font-black tracking-wider leading-tight">{{ card.label }}</div>
                        <span class="text-sm">{{ card.icon }}</span>
                    </div>
                    <div class="text-3xl font-black">{{ card.val }}</div>
                </div>
            </div>

        </div>
    </DashboardLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

.animate-fade-in {
    animation: fadeIn 0.5s ease-out forwards;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateX(-10px); }
    to { opacity: 1; transform: translateX(0); }
}
</style>