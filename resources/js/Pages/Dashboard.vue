<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head } from '@inertiajs/vue3';

// 1. Data for Top Performing
const topPerformers = [
    { rank: 1, unit: "University Registrar's Office", score: '98%', color: 'bg-yellow-400' },
    { rank: 2, unit: 'University Library System', score: '94%', color: 'bg-gray-300' },
    { rank: 3, unit: 'Accounting Office', score: '91%', color: 'bg-orange-400' },
];

// 2. Data for Needs Improvement (Lowest Positive / Highest Negative)
const needsImprovement = [
    { rank: 1, unit: 'Cash Management', issue: 'Wait Time', score: '64%' },
    { rank: 2, unit: 'General Services Directorate', issue: 'Facility Maintenance', score: '68%' },
    { rank: 3, unit: 'Health and Wellness Services', issue: 'Queue System', score: '72%' },
];
</script>

<template>
    <Head title="MMSU Sentiment Dashboard" />

    <DashboardLayout>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-5 mb-8">
            <div v-for="(item, index) in [
                { label: 'Total Feedback', val: '4,120', color: 'bg-white border-gray-100 border text-gray-800' },
                { label: 'Positive', val: '3,240', color: 'bg-[#0c4b33] text-white' },
                { label: 'Negative', val: '542', color: 'bg-red-500 text-white' },
                { label: 'Neutral', val: '338', color: 'bg-yellow-500 text-white' }
            ]" :key="index" 
                 class="rounded-[25px] p-6 shadow-sm transition hover:scale-105 duration-300 flex flex-col justify-between h-32"
                 :class="item.color">
                <div class="text-[9px] opacity-80 uppercase tracking-[0.2em] font-black leading-tight">{{ item.label }}</div>
                <div class="text-3xl font-black">{{ item.val }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            
            <div class="bg-gradient-to-br from-[#0c4b33] to-[#1a6e4d] rounded-[35px] p-8 shadow-sm text-white border-b-8 border-yellow-400">
                 <div class="flex justify-between items-start mb-8">
                    <div>
                        <h3 class="font-black text-[10px] uppercase tracking-widest text-yellow-400">Excellence Awardees</h3>
                        <p class="text-xl font-bold">Top Performing Units</p>
                    </div>
                    <span class="text-2xl">🏆</span>
                 </div>
                 
                 <div class="space-y-6">
                    <div v-for="item in topPerformers" :key="item.rank" class="flex items-center justify-between group">
                        <div class="flex items-center gap-4">
                            <div :class="item.color" class="w-8 h-8 rounded-full flex items-center justify-center text-[#0c4b33] font-black text-xs shadow-sm">
                                {{ item.rank }}
                            </div>
                            <div>
                                <div class="text-xs font-black uppercase tracking-tight">{{ item.unit }}</div>
                                <div class="text-[10px] opacity-60">High satisfaction rate</div>
                            </div>
                        </div>
                        <span class="text-sm font-black text-yellow-400">{{ item.score }}</span>
                    </div>
                 </div>
            </div>

            <div class="bg-white rounded-[35px] p-8 shadow-sm border border-red-50 border-b-8 border-red-500">
                 <div class="flex justify-between items-start mb-8">
                    <div>
                        <h3 class="font-black text-[10px] uppercase tracking-widest text-red-500">Action Required</h3>
                        <p class="text-xl font-bold text-gray-800">Priority for Improvement</p>
                    </div>
                    <span class="text-2xl">⚠️</span>
                 </div>
                 
                 <div class="space-y-6">
                    <div v-for="item in needsImprovement" :key="item.rank" class="flex items-center justify-between group">
                        <div class="flex items-center gap-4">
                            <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-black text-xs">
                                {{ item.rank }}
                            </div>
                            <div>
                                <div class="text-xs font-black text-gray-700 uppercase tracking-tight">{{ item.unit }}</div>
                                <div class="text-[10px] text-red-400 font-bold uppercase tracking-tighter italic">Primary Issue: {{ item.issue }}</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-black text-red-500">{{ item.score }}</span>
                            <div class="text-[8px] text-gray-400 font-bold uppercase">Positive</div>
                        </div>
                    </div>
                 </div>
            </div>
        </div>

        <div class="bg-white rounded-[35px] shadow-sm border border-gray-50 overflow-hidden mb-8">
            <div class="p-8 flex justify-between items-center">
                <h3 class="font-black text-gray-400 text-[10px] uppercase tracking-[0.2em]">Live Feedback Analysis</h3>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    <span class="text-[10px] font-bold text-gray-400 uppercase">System Active</span>
                </div>
            </div>
            
            <div class="px-8 pb-8">
                <div class="grid grid-cols-12 gap-4 bg-gray-50 text-gray-400 text-[9px] font-black uppercase tracking-widest py-3 px-6 rounded-xl mb-4 border border-gray-100">
                    <div class="col-span-2">Operating Unit</div>
                    <div class="col-span-6">Feedback Statement</div>
                    <div class="col-span-2 text-center">Sentiment</div>
                    <div class="col-span-2 text-right">Model Confidence</div>
                </div>

                <div v-for="(row, idx) in [
                    { unit: 'Registrar', text: 'Sobrang bilis ng pag-process ng TOR ko today!', sentiment: 'Positive', color: 'text-green-600 bg-green-50', conf: '98.2%' },
                    { unit: 'Cashier', text: 'The queue at window 3 is not moving at all, very frustrating.', sentiment: 'Negative', color: 'text-red-600 bg-red-50', conf: '94.5%' },
                    { unit: 'Medical Clinic', text: 'Nurse was helpful but the clinic needs more chairs.', sentiment: 'Neutral', color: 'text-yellow-600 bg-yellow-50', conf: '71.2%' }
                ]" :key="idx" 
                class="grid grid-cols-12 gap-4 py-4 px-6 border-b border-gray-50 hover:bg-gray-50/50 transition items-center group">
                    <div class="col-span-2 text-[10px] font-black text-gray-800 uppercase">{{ row.unit }}</div>
                    <div class="col-span-6 text-sm text-gray-600 italic group-hover:text-gray-900 transition pr-4">"{{ row.text }}"</div>
                    <div class="col-span-2 flex justify-center">
                        <span class="px-4 py-1 rounded-full text-[9px] font-black uppercase tracking-tighter" :class="row.color">
                            {{ row.sentiment }}
                        </span>
                    </div>
                    <div class="col-span-2 text-right font-mono text-[11px] font-bold text-gray-400 group-hover:text-[#0c4b33]">{{ row.conf }}</div>
                </div>
            </div>
        </div>

    </DashboardLayout>
</template>

<style scoped>
.transition {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>