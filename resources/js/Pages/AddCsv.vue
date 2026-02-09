<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const isDragging = ref(false);
const terminalLogs = ref([]);
const batchProgress = ref(null); // Tracks the 8k rows progress
let pollingInterval = null;

const form = useForm({
    file: null,
});

const steps = [
    { title: 'Uploading', icon: '📂' },
    { title: 'Queueing', icon: '⏳' },
    { title: 'AI Analysis', icon: '🧠' },
    { title: 'Finalizing', icon: '📊' }
];

const addLog = (msg) => {
    terminalLogs.value.push({ id: Date.now(), text: msg });
    if (terminalLogs.value.length > 8) terminalLogs.value.shift();
};

// Poll the server for background progress
const startPolling = () => {
    pollingInterval = setInterval(async () => {
        try {
            const response = await axios.get('/api/analysis-status');
            if (response.data) {
                batchProgress.value = response.data;
                if (response.data.status === 'completed') {
                    addLog(">> [SUCCESS] 100% Data processed.");
                    clearInterval(pollingInterval);
                }
            }
        } catch (e) {
            console.error("Polling error", e);
        }
    }, 3000);
};

const handleFile = (event) => {
    const files = event.target.files || event.dataTransfer.files;
    const selected = files[0];
    if (!selected) return;

    if (selected.name.endsWith('.csv')) {
        form.file = selected;
        submitFile();
    } else {
        alert("Please upload a CSV file.");
    }
    isDragging.value = false;
};

const submitFile = () => {
    addLog(">> [SYS] Initializing upload for " + form.file.name);
    
    form.post(route('dataset.upload'), {
        forceFormData: true,
        onStart: () => {
            addLog(">> [SYS] Transferring 8,000+ rows to server...");
        },
        onSuccess: () => {
            addLog(">> [SUCCESS] File received by server.");
            addLog(">> [SYS] Background Queue started...");
            startPolling(); // Start watching the progress bar
        },
        onError: () => {
            addLog(">> [ERROR] Upload failed. Check file size.");
        }
    });
};

const showProcessingScreen = computed(() => form.processing || form.wasSuccessful || batchProgress.value);

const currentStep = computed(() => {
    if (batchProgress.value?.status === 'completed') return 4;
    if (batchProgress.value) return 3; // Processing rows
    if (form.processing) return 1;
    return 1;
});

onUnmounted(() => clearInterval(pollingInterval));
</script>

<template>
    <Head title="Add CSV Dataset" />
    
    <DashboardLayout>
        <div class="h-full flex flex-col p-4">
            <h2 class="font-bold text-2xl text-[#0c4b33] mb-6 uppercase tracking-tight">Data Management</h2>

            <div class="bg-white flex-1 rounded-[35px] p-10 shadow-sm border border-gray-100 flex flex-col items-center justify-center relative overflow-hidden min-h-[500px]">
                
                <div v-if="!showProcessingScreen" class="w-full max-w-xl transition-all duration-500">
                    <div 
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="handleFile"
                        :class="[
                            'border-4 border-dashed rounded-[40px] p-10 text-center transition-all duration-300 cursor-pointer flex flex-col items-center justify-center min-h-[350px]',
                            isDragging ? 'border-yellow-400 bg-green-50 scale-105' : 'border-gray-100 hover:border-[#0c4b33] hover:bg-gray-50'
                        ]"
                    >
                        <div class="w-20 h-20 bg-green-100 text-[#0c4b33] rounded-full flex items-center justify-center mb-6 text-3xl shadow-inner">📄</div>
                        <h3 class="text-2xl font-black text-gray-800 mb-2">Upload Dataset</h3>
                        <p class="text-gray-400 text-sm mb-8 font-medium italic">Supports 8,000+ rows via Async Queue</p>
                        
                        <input type="file" accept=".csv" class="hidden" id="fileInput" @change="handleFile">
                        <label for="fileInput" class="bg-[#0c4b33] text-white px-10 py-4 rounded-2xl font-bold hover:bg-black transition shadow-xl cursor-pointer uppercase text-xs tracking-widest">
                            Browse Files
                        </label>
                    </div>
                </div>

                <div v-else class="w-full max-w-2xl animate-in fade-in zoom-in duration-500">
                    
                    <h3 class="text-center text-xl font-black mb-10 uppercase tracking-widest transition-colors duration-500"
                        :class="batchProgress?.status === 'completed' ? 'text-green-600' : 'text-[#0c4b33] animate-pulse'">
                        {{ batchProgress?.status === 'completed' ? '🎉 Analysis Complete!' : '🤖 BERT is Analyzing 8k+ Rows...' }}
                    </h3>

                    <div class="relative flex justify-between items-start mb-12">
                        <div class="absolute top-5 left-0 w-full h-1 bg-gray-100 rounded-full -z-10"></div>
                        <div v-for="(step, index) in steps" :key="index" class="flex flex-col items-center w-1/4">
                            <div 
                                :class="[
                                    'w-12 h-12 rounded-full flex items-center justify-center text-lg border-4 transition-all duration-700',
                                    index < currentStep ? 'bg-[#0c4b33] border-yellow-400 text-white scale-110 shadow-lg' : 'bg-white border-gray-100 text-gray-300'
                                ]"
                            >
                                <span v-if="index < currentStep">✓</span>
                                <span v-else>{{ step.icon }}</span>
                            </div>
                            <p :class="['mt-4 text-[9px] font-black uppercase tracking-tighter text-center transition-colors', index < currentStep ? 'text-[#0c4b33]' : 'text-gray-300']">
                                {{ step.title }}
                            </p>
                        </div>
                    </div>

                    <div v-if="batchProgress" class="mb-8 bg-gray-50 p-4 rounded-2xl border border-gray-100">
                        <div class="flex justify-between text-[10px] font-bold text-[#0c4b33] mb-2 uppercase tracking-widest">
                            <span>Progress</span>
                            <span>
                                {{ (batchProgress.total_rows && batchProgress.total_rows > 0) 
                                    ? Math.round((batchProgress.processed_rows / batchProgress.total_rows) * 100) 
                                    : 0 }}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 h-3 rounded-full overflow-hidden">
                            <div 
                                class="bg-[#0c4b33] h-full transition-all duration-500"
                                :style="{ width: ((batchProgress.total_rows > 0 ? batchProgress.processed_rows / batchProgress.total_rows : 0) * 100) + '%' }"
                            ></div>
                        </div>
                        <p class="text-[9px] text-center mt-2 text-gray-400 font-mono italic">
                            {{ batchProgress.processed_rows || 0 }} / {{ batchProgress.total_rows || 'Counting...' }} records processed
                        </p>
                    </div>

                    <div class="bg-gray-900 rounded-3xl p-6 font-mono text-[11px] text-green-400 shadow-2xl h-48 overflow-hidden relative border-t-8 border-gray-800">
                        <div class="flex flex-col gap-1.5">
                            <p class="text-gray-500">>> [SYS] Async Worker Initialization...</p>
                            <p v-for="log in terminalLogs" :key="log.id" class="animate-in slide-in-from-left duration-300">
                                {{ log.text }}
                            </p>
                            <p v-if="batchProgress && batchProgress.status !== 'completed'" class="animate-pulse text-yellow-500">| Feeding data to XLM-RoBERTa...</p>
                        </div>
                    </div>

                    <div v-if="batchProgress?.status === 'completed'" class="mt-8 flex gap-4 justify-center animate-in slide-up-4 duration-1000">
                        <Link href="/dashboard" class="bg-[#0c4b33] text-white px-8 py-3 rounded-xl font-bold hover:bg-black transition shadow-lg text-xs uppercase tracking-widest">
                            View Dashboard
                        </Link>
                        <button @click="batchProgress = null; form.reset(); form.wasSuccessful = false" class="bg-gray-100 text-gray-600 px-8 py-3 rounded-xl font-bold hover:bg-gray-200 transition text-xs uppercase tracking-widest">
                            Process New File
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>