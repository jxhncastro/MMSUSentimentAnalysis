<script setup>
import { ref } from 'vue';
import axios from 'axios';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

const comment = ref('');
const aspect = ref('General');
const result = ref(null);
const loading = ref(false);

// Dynamically mapping the units from your project data
const operatingUnits = [
    'General',
    'Accounting Office',
    'Administrative Service Division',
    'Alumni Relations Office',
    'Auxillary Directorate',
    'Cash Management',
    'Registrar',
    'Library Services',
    'Medical/Dental Clinic'
];

const analyzeSentiment = async () => {
    if (!comment.value) return;
    
    loading.value = true;
    result.value = null;

    try {
        const response = await axios.post('/ai/analyze', {
            comment: comment.value,
            aspect: aspect.value
        });
        result.value = response.data;
    } catch (error) {
        alert("Error connecting to AI. Make sure your Google Colab cell is running and the Ngrok URL is updated!");
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <DashboardLayout>
        <div class="p-6 max-w-3xl mx-auto">
            <header class="mb-6">
                <h2 class="text-3xl font-extrabold text-[#0c4b33]">🤖 Live AI Sentiment Tester</h2>
                <p class="text-gray-500">Test how the BERT model classifies stakeholder feedback in real-time.</p>
            </header>
            
            <div class="bg-white p-8 rounded-2xl shadow-xl border border-gray-100 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-2 h-full bg-yellow-400"></div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Select Operating Unit (Aspect)</label>
                        <select v-model="aspect" class="w-full border-gray-300 rounded-xl shadow-sm focus:ring-[#0c4b33] focus:border-[#0c4b33]">
                            <option v-for="unit in operatingUnits" :key="unit" :value="unit">
                                {{ unit }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Stakeholder Feedback / Comment</label>
                    <textarea 
                        v-model="comment" 
                        rows="5" 
                        class="w-full border-gray-300 rounded-xl shadow-sm focus:ring-[#0c4b33] focus:border-[#0c4b33] p-4" 
                        placeholder="Type the feedback here (Tagalog, English, or Ilocano)..."
                    ></textarea>
                </div>

                <button 
                    @click="analyzeSentiment" 
                    :disabled="loading || !comment"
                    class="w-full py-4 rounded-xl font-bold text-lg transition-all duration-200 shadow-lg flex items-center justify-center gap-3"
                    :class="loading ? 'bg-gray-400 cursor-not-allowed' : 'bg-[#0c4b33] hover:bg-[#083524] text-white'"
                >
                    <span v-if="loading" class="animate-spin">🌀</span>
                    <span>{{ loading ? 'AI is analyzing text...' : 'Run Sentiment Analysis' }}</span>
                </button>

                <transition name="fade">
                    <div v-if="result" class="mt-8 p-6 rounded-2xl border-2 shadow-inner"
                         :class="{
                             'bg-green-50 border-green-200 text-green-800': result.sentiment === 'Positive',
                             'bg-red-50 border-red-200 text-red-800': result.sentiment === 'Negative',
                             'bg-blue-50 border-blue-200 text-blue-800': result.sentiment === 'Neutral'
                         }">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-xs uppercase tracking-widest font-bold opacity-60 mb-1">Classification Result</p>
                                <h3 class="text-3xl font-black mb-1">{{ result.sentiment }}</h3>
                                <p class="text-sm">
                                    The model is <strong>{{ result.confidence }}%</strong> confident in this result.
                                </p>
                            </div>
                            <div class="text-5xl">
                                <span v-if="result.sentiment === 'Positive'">😊</span>
                                <span v-else-if="result.sentiment === 'Negative'">😟</span>
                                <span v-else>😐</span>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>

            <div class="mt-6 bg-yellow-50 border border-yellow-200 p-4 rounded-xl flex gap-3">
                <span class="text-yellow-600 font-bold">💡 Tip:</span>
                <p class="text-sm text-yellow-800 italic">
                    This test uses the **XLM-RoBERTa** model optimized for the MMSU dashboard. It can process code-switched Tagalog-English feedback typical of Filipino stakeholders.
                </p>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.5s ease, transform 0.5s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>