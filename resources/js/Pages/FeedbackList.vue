<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    feedback: Object
});

// --- 1. DEFINE SENTIMENT DICTIONARIES ---
// Refined list: Removed ambiguous words like 'improvement' to prevent false negatives
const positiveTriggers = [
    'good', 'great', 'excellent', 'fast', 'faster', 'friendly', 'accommodating', 
    'efficient', 'best', 'satisfied', 'satisfaction', 'love', 'amazing', 'thank', 
    'thanks', 'happy', 'smooth', 'easy', 'helpful', 'kind', 'nice', 'clean', 
    'accessible', 'responsive', 'quality', 'well', 'mabait', 'maganda', 'mabilis',
    'maayos', 'madali'
];

const negativeTriggers = [
    // English
    'bad', 'slow', 'slower', 'rude', 'worst', 'poor', 'unfriendly', 
    'disappointed', 'hate', 'terrible', 'awful', 'delayed', 'delays',
    'hard', 'difficult', 'confusing', 'noisy', 'dirty', 'hot', 'crowded', 
    'problem', 'issue', 'complaint', 'lack', 'fail', 'failure',
    'slowly', 'inefficient', 'messy', 'chaos', 'chaotic', 'hassle',

    // Tagalog / Taglish (Common Complaints)
    'matagal', 'bagal', 'mabagal', // Slow
    'mali', 'palpak', // Wrong/Failed
    'perwisyo', 'abala', // Burden/Inconvenience
    'sira', 'pangit', // Broken/Ugly
    'bastos', 'masungit', // Rude
    'init', 'mainit', // Hot
    'dumi', 'madumi', // Dirty
    'ingay', 'maingay', // Noisy
    'pabalik', 'balik', // Recurring/Back-and-forth
    'sayang', 'hirap', 'mahirap', // Waste/Hard
    'tagal' // Long wait
];

// --- 2. EXTRACT KEYWORDS FUNCTION ---
// Returns an array of words found in the text for the "Detected Triggers" column
const getTriggers = (text, sentiment) => {
    if (!text) return [];
    const list = sentiment === 'Positive' ? positiveTriggers : 
                 sentiment === 'Negative' ? negativeTriggers : [];
    
    // Find all matching words (case insensitive)
    const found = list.filter(word => {
        const regex = new RegExp(`\\b${word}\\b`, 'i');
        return regex.test(text);
    });
    return found;
};
</script>

<template>
    <Head title="All Feedback" />

    <DashboardLayout>
        
        <div class="flex justify-between items-end mb-6">
            <div>
                <h2 class="font-black text-2xl text-[#0c4b33] uppercase tracking-tight">Feedback Repository</h2>
                <p class="text-gray-500 text-sm">Full list of stakeholder responses ({{ feedback.total }} records)</p>
            </div>
            
            <div class="flex gap-2">
                <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold border border-green-200">Positive</span>
                <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-bold border border-red-200">Negative</span>
                <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-bold border border-gray-200">Neutral</span>
            </div>
        </div>

        <div class="bg-white rounded-[25px] shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-100">
                            <th class="p-4 font-bold">Operating Unit</th>
                            <th class="p-4 font-bold w-1/2">Feedback Statement</th>
                            <th class="p-4 font-bold">Topic</th>
                            <th class="p-4 font-bold text-center">Sentiment</th>
                            <th class="p-4 font-bold text-right">Detected Triggers</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="item in feedback.data" :key="item.id" class="hover:bg-gray-50/50 transition duration-150">
                            
                            <td class="p-4">
                                <div class="font-bold text-gray-700 text-sm">{{ item.operating_unit }}</div>
                                <div class="text-[10px] text-gray-400">ID: #{{ item.id }}</div>
                            </td>

                            <td class="p-4">
                                <p class="text-sm text-gray-600 leading-relaxed">
                                    {{ item.feedback_text }}
                                </p>
                            </td>

                            <td class="p-4">
                                <span class="px-2 py-1 bg-blue-50 text-blue-600 text-[10px] font-bold uppercase rounded-md border border-blue-100">
                                    {{ item.topic || 'General' }}
                                </span>
                            </td>

                            <td class="p-4 text-center">
                                <span 
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold border shadow-sm"
                                    :class="{
                                        'bg-green-50 text-green-700 border-green-200': item.sentiment === 'Positive',
                                        'bg-red-50 text-red-700 border-red-200': item.sentiment === 'Negative',
                                        'bg-gray-50 text-gray-600 border-gray-200': item.sentiment === 'Neutral'
                                    }"
                                >
                                    <span v-if="item.sentiment === 'Positive'">👍 Positive</span>
                                    <span v-else-if="item.sentiment === 'Negative'">⚠️ Negative</span>
                                    <span v-else>😐 Neutral</span>
                                </span>
                                <div class="w-16 h-1 bg-gray-100 rounded-full mt-2 mx-auto overflow-hidden">
                                    <div 
                                        class="h-full rounded-full" 
                                        :class="{
                                            'bg-green-400': item.sentiment === 'Positive',
                                            'bg-red-400': item.sentiment === 'Negative',
                                            'bg-gray-400': item.sentiment === 'Neutral'
                                        }"
                                        :style="{ width: (item.confidence * 100) + '%' }"
                                    ></div>
                                </div>
                                <div v-if="item.confidence > 0" class="text-[9px] text-gray-300 mt-0.5">
                                    {{ (item.confidence * 100).toFixed(0) }}% Conf.
                                </div>
                            </td>

                            <td class="p-4 text-right">
                                <div class="flex flex-wrap justify-end gap-1">
                                    <template v-if="getTriggers(item.feedback_text, item.sentiment).length > 0">
                                        <span 
                                            v-for="word in getTriggers(item.feedback_text, item.sentiment)" 
                                            :key="word"
                                            class="px-2 py-1 text-[10px] font-bold uppercase rounded border"
                                            :class="item.sentiment === 'Positive' 
                                                ? 'bg-green-100 text-green-700 border-green-200' 
                                                : 'bg-red-100 text-red-700 border-red-200'"
                                        >
                                            {{ word }}
                                        </span>
                                    </template>
                                    <span v-else class="text-xs text-gray-300 italic">No specific keywords</span>
                                </div>
                            </td>

                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-gray-100 flex justify-between items-center bg-gray-50/50">
                <div class="text-xs text-gray-400">
                    Showing {{ feedback.from }} to {{ feedback.to }} of {{ feedback.total }} results
                </div>
                <div class="flex gap-2">
                    <Link 
                        v-if="feedback.prev_page_url" 
                        :href="feedback.prev_page_url"
                        class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-xs font-bold hover:bg-[#0c4b33] hover:text-white transition"
                    >
                        &laquo; Previous
                    </Link>
                    <Link 
                        v-if="feedback.next_page_url" 
                        :href="feedback.next_page_url"
                        class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-xs font-bold hover:bg-[#0c4b33] hover:text-white transition"
                    >
                        Next &raquo;
                    </Link>
                </div>
            </div>
        </div>

    </DashboardLayout>
</template>