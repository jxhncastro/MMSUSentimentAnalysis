<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed, onUnmounted } from 'vue';
import axios from 'axios';

const isDragging = ref(false);
const isConfirming = ref(false); 
const terminalLogs = ref([]);
const batchProgress = ref(null);
let pollingInterval = null;

const form = useForm({
    file: null,
});

const steps = [
    { title: 'Uploading', icon: '📂' },
    { title: 'Queueing', icon: '⏳' },
    { title: 'Analyzing', icon: '🧠' },
    { title: 'Finalizing', icon: '📊' }
];

const addLog = (msg) => {
    terminalLogs.value.push({ id: Date.now(), text: msg });
    if (terminalLogs.value.length > 8) terminalLogs.value.shift();
};

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
        } catch (e) { console.error("Polling error", e); }
    }, 3000);
};

const handleFile = (event) => {
    const files = event.target.files || event.dataTransfer.files;
    const selected = files[0];
    if (!selected) return;

    if (selected.name.endsWith('.csv')) {
        form.file = selected;
        isConfirming.value = true;
    } else {
        alert("Please upload a CSV file.");
    }
    isDragging.value = false;
};

const submitFile = () => {
    isConfirming.value = false;
    addLog(">> [SYS] Initializing upload for " + form.file.name);
    
    form.post(route('dataset.upload'), {
        forceFormData: true,
        onStart: () => { addLog(">> [SYS] Transferring Data to server..."); },
        onSuccess: () => {
            addLog(">> [SUCCESS] File received by server.");
            addLog(">> [SYS] Background Queue started...");
            startPolling();
        },
        onError: () => { addLog(">> [ERROR] Upload failed. Check file size."); }
    });
};

const cancelConfirmation = () => {
    form.file = null;
    isConfirming.value = false;
};

const showProcessingScreen = computed(() => form.processing || form.wasSuccessful || batchProgress.value);

const currentStep = computed(() => {
    if (batchProgress.value?.status === 'completed') return 4;
    if (batchProgress.value) return 3;
    if (form.processing) return 1;
    return 1;
});

onUnmounted(() => clearInterval(pollingInterval));
</script>

<template>
    <Head title="Add CSV Dataset" />
    
    <DashboardLayout>
        <div class="h-full flex flex-col p-4 overflow-y-auto">
            <h2 class="font-bold text-2xl text-[#0c4b33] mb-4 uppercase tracking-tight">Data Management</h2>

            <div class="bg-white rounded-[35px] p-6 shadow-sm border border-gray-100 flex flex-col items-center justify-center relative overflow-hidden">
                
                <div v-if="!showProcessingScreen && !isConfirming" class="w-full max-w-xl transition-all duration-500">
                    <div 
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="handleFile"
                        :class="[
                            'border-4 border-dashed rounded-[40px] p-6 text-center transition-all duration-300 cursor-pointer flex flex-col items-center justify-center min-h-[280px]',
                            isDragging ? 'border-yellow-400 bg-green-50 scale-105 shadow-2xl' : 'border-gray-100 hover:border-[#0c4b33] hover:bg-gray-50'
                        ]"
                    >
                        <div class="w-20 h-20 bg-white shadow-xl shadow-slate-100 rounded-3xl flex items-center justify-center mb-6 transform transition-transform duration-500 hover:rotate-3">
                            <span class="text-3xl">📄</span>
                        </div>
                        <h3 class="text-xl font-black text-gray-800 mb-2">Upload Dataset</h3>
                        <p class="text-gray-400 text-sm mb-6 font-medium italic">Supports thousands of rows via Async Queue</p>
                        
                        <input type="file" accept=".csv" class="hidden" id="fileInput" @change="handleFile">
                        <label for="fileInput" class="bg-[#0c4b33] text-white px-10 py-4 rounded-2xl font-bold hover:bg-black transition shadow-xl cursor-pointer uppercase text-xs tracking-widest">
                            Browse Files
                        </label>
                    </div>
                </div>

                <div v-else-if="isConfirming" class="w-full max-w-md animate-in fade-in zoom-in duration-300 text-center">
                    <div class="w-16 h-16 bg-yellow-100 text-yellow-600 rounded-3xl flex items-center justify-center mb-6 mx-auto shadow-inner rotate-3">
                        <span class="text-2xl">⚠️</span>
                    </div>
                    <h3 class="text-xl font-black text-gray-800 mb-2">Confirm Upload</h3>
                    
                    <div class="bg-gray-50 rounded-3xl p-5 mb-6 border border-gray-100 text-left shadow-inner">
                        <p class="text-[10px] text-gray-400 uppercase font-black tracking-widest mb-1">Selected File</p>
                        <p class="font-bold text-[#0c4b33] truncate text-md">{{ form.file.name }}</p>
                        <p class="text-[11px] text-gray-400 mt-1 font-mono uppercase">{{ (form.file.size / 1024).toFixed(2) }} KB</p>
                    </div>
                    
                    <div class="flex flex-col gap-3">
                        <button @click="submitFile" class="bg-[#0c4b33] text-white py-4 rounded-2xl font-bold hover:bg-black transition shadow-lg uppercase text-xs tracking-widest">
                            Analyze Dataset
                        </button>
                        <button @click="cancelConfirmation" class="text-gray-400 py-2 rounded-2xl font-bold hover:text-red-500 transition uppercase text-[10px] tracking-widest">
                            Cancel Selection
                        </button>
                    </div>
                </div>

                <div v-else class="w-full max-w-2xl animate-in fade-in zoom-in duration-500">
                    
                    <h3 class="text-center text-lg font-black mb-6 uppercase tracking-widest transition-colors duration-500"
                        :class="batchProgress?.status === 'completed' ? 'text-green-600' : 'text-[#0c4b33] animate-pulse'">
                        {{ batchProgress?.status === 'completed' ? 'Analysis Complete!' : `Analyzing ${batchProgress?.total_rows || 0} Rows...` }}
                    </h3>

                    <div class="relative flex justify-between items-start mb-8 px-4">
                        <div class="absolute top-6 left-0 w-full h-1 bg-gray-100 rounded-full -z-10"></div>
                        <div v-for="(step, index) in steps" :key="index" class="flex flex-col items-center w-1/4">
                            <div 
                                :class="[
                                    'w-12 h-12 rounded-2xl flex items-center justify-center text-lg border-4 transition-all duration-700',
                                    index < currentStep ? 'bg-[#0c4b33] border-yellow-400 text-white shadow-xl shadow-[#0c4b33]/30 rotate-3' : 'bg-white border-gray-100 text-gray-300'
                                ]"
                            >
                                <span v-if="index < currentStep" class="text-sm">✓</span>
                                <span v-else class="text-sm">{{ step.icon }}</span>
                            </div>
                            <p :class="['mt-3 text-[9px] font-black uppercase tracking-tighter text-center transition-colors', index < currentStep ? 'text-[#0c4b33]' : 'text-gray-300']">
                                {{ step.title }}
                            </p>
                        </div>
                    </div>

                    <div v-if="batchProgress" class="mb-6 bg-gray-50 p-4 rounded-[2rem] border border-gray-100 shadow-inner">
                        <div class="flex justify-between text-[10px] font-bold text-[#0c4b33] mb-2 uppercase tracking-widest">
                            <span>Processing Status</span>
                            <span>{{ (batchProgress.total_rows > 0) ? Math.round((batchProgress.processed_rows / batchProgress.total_rows) * 100) : 0 }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 h-3 rounded-full overflow-hidden p-0.5 shadow-inner">
                            <div 
                                class="bg-gradient-to-r from-[#0c4b33] to-emerald-500 h-full rounded-full transition-all duration-1000 ease-out"
                                :style="{ width: ((batchProgress.total_rows > 0 ? batchProgress.processed_rows / batchProgress.total_rows : 0) * 100) + '%' }"
                            ></div>
                        </div>
                        <p class="text-[9px] text-center mt-2 text-gray-400 font-mono italic">
                            {{ batchProgress.processed_rows || 0 }} / {{ batchProgress.total_rows || 'Counting...' }} records completed
                        </p>
                    </div>

                    <div class="bg-gray-900 rounded-[2rem] p-5 font-mono text-[10px] text-green-400 shadow-2xl h-32 overflow-hidden relative border-t-4 border-gray-800 group">
                        <div class="flex flex-col gap-1">
                            <p class="text-gray-500 border-b border-gray-800 pb-1 mb-1">>> SYSTEM CONSOLE</p>
                            <p v-for="log in terminalLogs" :key="log.id" class="animate-in slide-in-from-left duration-300">
                                {{ log.text }}
                            </p>
                        </div>
                    </div>

                    <div v-if="batchProgress?.status === 'completed'" class="mt-8 flex gap-4 justify-center animate-in slide-up-4 duration-1000">
                        <Link href="/dashboard" class="flex-1 max-w-xs bg-[#0c4b33] text-white py-3.5 rounded-2xl font-bold hover:bg-black transition shadow-xl text-xs uppercase tracking-widest text-center">
                            View Dashboard
                        </Link>
                        <button @click="batchProgress = null; form.reset(); form.wasSuccessful = false" class="px-8 bg-gray-100 text-gray-600 rounded-2xl font-bold hover:bg-gray-200 transition text-xs uppercase tracking-widest">
                            New File
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>