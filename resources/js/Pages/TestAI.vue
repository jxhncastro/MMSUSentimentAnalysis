<script setup>
import { ref } from 'vue';
import axios from 'axios';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

const comment = ref('');
const aspect = ref('General Feedback');
const result = ref(null);
const loading = ref(false);

// Match these with your Thesis Aspect Categories
const operatingUnits = [
    'General Feedback',
    'customer service',
    'processing time',
    'facilities',
    'process and requirements',
    'online system'
];

const analyzeSentiment = async () => {
    if (!comment.value) return;
    
    loading.value = true;
    result.value = null;

    try {
        // FIX 1: Point to the correct API route (prefixed with /api)
        const response = await axios.post('/api/ai/analyze', {
            text: comment.value, 
            aspect: aspect.value
        });
        
        // FIX 2: Format the raw Python response for the UI
        // Python returns 'confidence_score' (0.98), UI needs 'confidence' (98)
        const raw = response.data;
        result.value = {
            sentiment: raw.sentiment.charAt(0).toUpperCase() + raw.sentiment.slice(1), // Ensure Capitalized
            confidence: raw.confidence_score ? Math.round(raw.confidence_score * 100) : 0,
            method: raw.method || 'XLM-RoBERTa v2' // Fallback if API doesn't send method name
        };

    } catch (error) {
        console.error("Connection Error:", error);
        alert("Could not connect to the Sentiment Engine.\n\n1. Check if the Colab Script is running.\n2. Check if AI_MODEL_URL in .env is correct.");
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <DashboardLayout>
        <div class="p-6 max-w-4xl mx-auto">
            <header class="mb-8">
                <div class="flex items-center gap-3 mb-2">
                    <span class="bg-[#0c4b33] text-white px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">Research Tool</span>
                </div>
                <h2 class="text-4xl font-black text-[#0c4b33] tracking-tight">🤖 MMSU Sentiment Tester</h2>
                <p class="text-gray-500 mt-2">Testing the <strong>XLM-RoBERTa v2</strong> model on stakeholder feedback (English, Tagalog, Ilocano).</p>
            </header>
            
            <div class="bg-white p-8 rounded-3xl shadow-2xl border border-gray-100 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-yellow-400 via-[#0c4b33] to-yellow-400"></div>

                <div class="grid grid-cols-1 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">Target Aspect Category</label>
                        <select v-model="aspect" class="w-full border-gray-200 rounded-xl shadow-sm focus:ring-[#0c4b33] focus:border-[#0c4b33] py-3">
                            <option v-for="unit in operatingUnits" :key="unit" :value="unit">
                                {{ unit.toUpperCase() }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">Stakeholder Feedback Input</label>
                    <textarea 
                        v-model="comment" 
                        rows="4" 
                        class="w-full border-gray-200 rounded-2xl shadow-sm focus:ring-[#0c4b33] focus:border-[#0c4b33] p-5 text-lg" 
                        placeholder="e.g., 'Madi unay ti service ditoy registrar, nabuntog!' or 'Very accommodating staff...'"
                    ></textarea>
                </div>

                <button 
                    @click="analyzeSentiment" 
                    :disabled="loading || !comment"
                    class="w-full py-5 rounded-2xl font-black text-xl transition-all duration-300 shadow-xl flex items-center justify-center gap-4 group"
                    :class="loading ? 'bg-gray-300 cursor-not-allowed text-gray-500' : 'bg-[#0c4b33] hover:bg-[#083524] text-white active:scale-95'"
                >
                    <span v-if="loading" class="animate-spin text-2xl">⏳</span>
                    <span v-else class="group-hover:scale-110 transition-transform">🚀</span>
                    <span>{{ loading ? 'AI IS THINKING...' : 'ANALYZE SENTIMENT' }}</span>
                </button>

                <transition name="slide-up">
                    <div v-if="result" class="mt-10 p-8 rounded-3xl border-2 relative"
                         :class="{
                             'bg-emerald-50 border-emerald-200 text-emerald-900': result.sentiment === 'Positive',
                             'bg-rose-50 border-rose-200 text-rose-900': result.sentiment === 'Negative',
                             'bg-sky-50 border-sky-200 text-sky-900': result.sentiment === 'Neutral'
                         }">
                        
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="px-3 py-1 bg-white/60 rounded-lg text-[10px] font-black uppercase border border-current/10">
                                        ENGINE: {{ result.method }}
                                    </span>
                                </div>
                                
                                <h3 class="text-5xl font-black mb-4 tracking-tighter">{{ result.sentiment }}</h3>
                                
                                <div class="max-w-xs">
                                    <div class="flex justify-between text-xs font-black mb-2 uppercase opacity-70">
                                        <span>AI Confidence Score</span>
                                        <span>{{ result.confidence }}%</span>
                                    </div>
                                    <div class="w-full bg-black/5 rounded-full h-3 p-0.5">
                                        <div class="h-2 rounded-full transition-all duration-1000 ease-out" 
                                             :class="result.sentiment === 'Positive' ? 'bg-emerald-500' : (result.sentiment === 'Negative' ? 'bg-rose-500' : 'bg-sky-500')"
                                             :style="{ width: result.confidence + '%' }"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-7xl animate-bounce-slow">
                                <span v-if="result.sentiment === 'Positive'">😊</span>
                                <span v-else-if="result.sentiment === 'Negative'">😟</span>
                                <span v-else>😐</span>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>

            <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-gray-50 border border-gray-200 p-5 rounded-2xl shadow-sm">
                    <h4 class="font-bold text-[#0c4b33] flex items-center gap-2">
                        <span>🔍</span> Contextual Analysis
                    </h4>
                    <p class="text-xs text-gray-600 mt-1">Model analyzes sentiment based on the <strong>{{ aspect }}</strong> context to improve domain accuracy.</p>
                </div>
                <div class="bg-gray-50 border border-gray-200 p-5 rounded-2xl shadow-sm">
                    <h4 class="font-bold text-[#0c4b33] flex items-center gap-2">
                        <span>🇵🇭</span> Multilingual Support
                    </h4>
                    <p class="text-xs text-gray-600 mt-1">Supports Ilocano (Haan/Madi), Tagalog, and English code-switching.</p>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.slide-up-enter-active, .slide-up-leave-active {
  transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.slide-up-enter-from {
  opacity: 0;
  transform: translateY(30px);
}
.slide-up-leave-to {
  opacity: 0;
  transform: scale(0.95);
}

@keyframes bounce-slow {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
.animate-bounce-slow {
    animation: bounce-slow 3s infinite ease-in-out;
}
</style>