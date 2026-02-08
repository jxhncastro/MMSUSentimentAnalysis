<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';

const isDragging = ref(false);
const terminalLogs = ref([]);

const form = useForm({
    file: null,
});

const steps = [
    { title: 'Uploading', icon: '📂' },
    { title: 'Data Cleaning', icon: '🧹' },
    { title: 'AI Analysis', icon: '🧠' },
    { title: 'Finalizing', icon: '📊' }
];

// Helper to add logs with a typewriter effect
const addLog = (msg) => {
    terminalLogs.value.push({ id: Date.now(), text: msg });
    // Keep only last 8 logs
    if (terminalLogs.value.length > 8) terminalLogs.value.shift();
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
            addLog(">> [SYS] Transferring data to server...");
        },
        onSuccess: () => {
            addLog(">> [SUCCESS] BERT Model prediction completed.");
            addLog(">> [SYS] Database records updated successfully.");
        },
        onError: () => {
            addLog(">> [ERROR] Connection to AI Model failed.");
        }
    });
};

// This ensures the loading screen stays visible if processing OR if successful
const showProcessingScreen = computed(() => form.processing || form.wasSuccessful);

const currentStep = computed(() => {
    if (form.wasSuccessful) return 4; // All steps done
    if (form.processing) return 2;   // Middle of analysis
    return 1;
});
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
                        <div class="w-20 h-20 bg-green-100 text-[#0c4b33] rounded-full flex items-center justify-center mb-6 text-3xl shadow-inner">
                            📄
                        </div>
                        <h3 class="text-2xl font-black text-gray-800 mb-2">Upload Dataset</h3>
                        <p class="text-gray-400 text-sm mb-8 font-medium">Drag and drop your feedback .csv file here</p>
                        
                        <input type="file" accept=".csv" class="hidden" id="fileInput" @change="handleFile">
                        <label for="fileInput" class="bg-[#0c4b33] text-white px-10 py-4 rounded-2xl font-bold hover:bg-black transition shadow-xl cursor-pointer uppercase text-xs tracking-widest">
                            Browse Files
                        </label>
                    </div>
                </div>

                <div v-else class="w-full max-w-2xl animate-in fade-in zoom-in duration-500">
                    
                    <h3 class="text-center text-xl font-black mb-10 uppercase tracking-widest transition-colors duration-500"
                        :class="form.wasSuccessful ? 'text-green-600' : 'text-[#0c4b33] animate-pulse'">
                        {{ form.wasSuccessful ? '🎉 Processing Complete!' : '🤖 AI Model is Analyzing...' }}
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

                    <div class="bg-gray-900 rounded-3xl p-6 font-mono text-[11px] text-green-400 shadow-2xl h-48 overflow-hidden relative border-t-8 border-gray-800">
                        <div class="flex flex-col gap-1.5">
                            <p class="text-gray-500">>> [SYS] BERT v2.0 Initialization...</p>
                            <p v-for="log in terminalLogs" :key="log.id" class="animate-in slide-in-from-left duration-300">
                                {{ log.text }}
                            </p>
                            <p v-if="form.processing" class="animate-pulse text-yellow-500">| Analyzing text chunks...</p>
                        </div>
                    </div>

                    <div v-if="form.wasSuccessful" class="mt-8 flex gap-4 justify-center animate-in slide-up-4 duration-1000">
                        <Link href="/dashboard" class="bg-[#0c4b33] text-white px-8 py-3 rounded-xl font-bold hover:bg-black transition shadow-lg text-xs uppercase tracking-widest">
                            View Dashboard
                        </Link>
                        <button @click="form.reset(); form.wasSuccessful = false" class="bg-gray-100 text-gray-600 px-8 py-3 rounded-xl font-bold hover:bg-gray-200 transition text-xs uppercase tracking-widest">
                            Upload Another
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
@keyframes slide-up {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
.slide-up-4 {
    animation: slide-up 0.5s ease-out forwards;
}
</style>