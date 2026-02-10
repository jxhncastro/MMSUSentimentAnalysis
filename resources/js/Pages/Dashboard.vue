<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

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
    recentFeedback: {
        type: Array,
        default: () => []
    }
});

const form = useForm({});

const submitClear = () => {
    if (confirm('⚠️ DANGER ZONE: ARE YOU SURE?\n\nThis will permanently delete ALL feedback data.\n\nThis action cannot be undone.')) {
        form.post('/clear-data', {
            onSuccess: () => alert('✅ System Cleaned: All data removed.'),
            onError: () => alert('❌ Error: Could not clear data.')
        });
    }
};
</script>

<template>
    <Head title="MMSU Sentiment Dashboard" />

    <DashboardLayout>
        
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-2xl font-black text-[#0c4b33] uppercase tracking-wide">Executive Overview</h2>
                <p class="text-sm text-gray-500 font-bold">Real-time Sentiment Analytics</p>
            </div>
            
            <button 
                @click="submitClear"
                type="button" 
                :disabled="form.processing"
                class="bg-white border border-red-200 text-red-500 hover:bg-red-500 hover:text-white px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition flex items-center gap-2 shadow-sm hover:shadow-md cursor-pointer disabled:opacity-50"
            >
                <span class="text-lg" v-if="!form.processing">🗑️</span>
                <span class="text-lg animate-spin" v-else>⏳</span>
                {{ form.processing ? 'Clearing...' : 'Clear All Data' }}
            </button>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-5 mb-8">
            <div v-for="(item, index) in [
                { label: 'Total Feedback', val: props.stats.total, color: 'bg-white border-gray-100 border text-gray-800' },
                { label: 'Positive', val: props.stats.positive, color: 'bg-[#0c4b33] text-white' },
                { label: 'Negative', val: props.stats.negative, color: 'bg-red-500 text-white' },
                { label: 'Neutral', val: props.stats.neutral, color: 'bg-yellow-500 text-white' }
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
                        <p class="text-2xl font-black uppercase">Top Offices</p>
                    </div>
                    <span class="text-2xl">🏆</span>
                 </div>
                 
                 <div class="space-y-6">
                    <div v-if="props.topPerformers.length === 0" class="text-center text-sm opacity-50 py-10">
                        No data available yet.
                    </div>
                    <div v-else v-for="item in props.topPerformers" :key="item.rank" class="flex items-center justify-between group">
                        <div class="flex items-center gap-4">
                            <div :class="item.color" class="w-8 h-8 rounded-full flex items-center justify-center text-[#0c4b33] font-black text-xs shadow-sm">
                                {{ item.rank }}
                            </div>
                            <div>
                                <div class="text-xs font-black uppercase tracking-tight">{{ item.unit }}</div>
                                <div class="text-[10px] opacity-60">Highest Satisfaction Rating</div>
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
                        <p class="text-2xl font-black text-gray-800 uppercase leading-tight">Top Offices Needing Improvement</p>
                    </div>
                    <span class="text-2xl">⚠️</span>
                 </div>
                 
                 <div class="space-y-6">
                    <div v-if="props.needsImprovement.length === 0" class="text-center text-gray-400 text-sm py-10">
                        No critical issues found.
                    </div>
                    <div v-else v-for="item in props.needsImprovement" :key="item.rank" class="flex items-center justify-between group">
                        <div class="flex items-center gap-4">
                            <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-black text-xs">
                                {{ item.rank }}
                            </div>
                            <div>
                                <div class="text-xs font-black text-gray-700 uppercase tracking-tight">{{ item.unit }}</div>
                                <div class="text-[10px] text-red-400 font-bold uppercase tracking-tighter italic">{{ item.issue }}</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-black text-red-500">{{ item.score }}</span>
                            <div class="text-[8px] text-gray-400 font-bold uppercase">Negative Rate</div>
                        </div>
                    </div>
                 </div>
            </div>
        </div>

        <div class="bg-white rounded-[35px] shadow-sm border border-gray-50 overflow-hidden mb-8">
            <div class="p-8 flex justify-between items-center">
                <h3 class="font-black text-gray-400 text-[10px] uppercase tracking-[0.2em]">Recent Batch Analysis</h3>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    <span class="text-[10px] font-bold text-gray-400 uppercase">System Active</span>
                </div>
            </div>
            
            <div class="px-8 pb-8">
                <div class="grid grid-cols-12 gap-4 bg-gray-50 text-gray-400 text-[9px] font-black uppercase tracking-widest py-3 px-6 rounded-xl mb-4 border border-gray-100">
                    <div class="col-span-2">Operating Unit</div>
                    <div class="col-span-5">Feedback Statement</div> 
                    <div class="col-span-2 text-center">Sentiment</div>
                    <div class="col-span-3 text-right">Detected Triggers</div> 
                </div>

                <div v-if="props.recentFeedback.length === 0" class="text-center text-gray-400 text-sm py-10 italic">
                    Waiting for new uploads...
                </div>

                <div v-else v-for="(row, idx) in props.recentFeedback" :key="idx" 
                class="grid grid-cols-12 gap-4 py-4 px-6 border-b border-gray-50 hover:bg-gray-50/50 transition items-center group">
                    <div class="col-span-2 text-[10px] font-black text-gray-800 uppercase">{{ row.unit }}</div>
                    <div class="col-span-5 text-sm text-gray-600 italic group-hover:text-gray-900 transition pr-4 truncate">"{{ row.text }}"</div>
                    <div class="col-span-2 flex justify-center">
                        <span class="px-4 py-1 rounded-full text-[9px] font-black uppercase tracking-tighter" :class="row.color">
                            {{ row.sentiment }}
                        </span>
                    </div>
                    <div class="col-span-3 text-right font-mono text-[10px] font-bold text-gray-400 group-hover:text-[#0c4b33] uppercase truncate">
                        {{ row.keywords || row.conf || '---' }}
                    </div>
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