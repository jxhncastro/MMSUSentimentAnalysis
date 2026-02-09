<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const currentUrl = computed(() => page.url);
</script>

<template>
    <div class="flex min-h-screen bg-gray-50 font-sans text-gray-800">
        
        <aside class="w-72 bg-[#0c4b33] m-4 rounded-2xl flex flex-col py-8 px-5 shadow-xl h-[95vh] sticky top-4 shrink-0 z-20 text-white relative overflow-hidden">
            
            <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-yellow-400 rounded-full opacity-10 blur-3xl pointer-events-none"></div>
            <div class="absolute bottom-0 left-0 -mb-10 -ml-10 w-40 h-40 bg-green-600 rounded-full opacity-20 blur-3xl pointer-events-none"></div>

            <div class="mb-10 flex flex-col items-center text-center relative z-10">
                
                <div class="w-32 h-32 flex items-center justify-center mb-2 relative group transition-transform duration-500 hover:scale-110">
                    <img 
                        src="/mmsulogo.png" 
                        alt="MMSU Logo" 
                        class="w-full h-full object-contain drop-shadow-2xl group-hover:rotate-6 transition-transform duration-700"
                    />
                </div>

                <h1 class="text-sm font-black tracking-widest text-yellow-400 leading-tight mt-2">
                    MARIANO MARCOS<br>STATE UNIVERSITY
                </h1>
                <p class="text-[10px] text-gray-300 uppercase tracking-[0.2em] font-medium mt-3 border-t border-white/20 pt-2 w-full">
                    Sentiment Dashboard
                </p>
            </div>

            <nav class="space-y-2 flex-1 relative z-10">
                
                <Link 
                    href="/dashboard" 
                    :class="[
                        'flex items-center px-4 py-3 rounded-xl font-medium mb-1 transition-all duration-200 border border-transparent',
                        currentUrl.startsWith('/dashboard') 
                            ? 'bg-white/10 text-yellow-400 border-yellow-400/30 shadow-sm backdrop-blur-sm' 
                            : 'text-gray-300 hover:bg-white/5 hover:text-white'
                    ]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    Dashboard
                </Link>

                <Link 
                    href="/operating-units"
                    :class="[
                        'flex items-center px-4 py-3 rounded-xl font-medium mb-1 transition-all duration-200 border border-transparent',
                        currentUrl.startsWith('/operating-units') 
                            ? 'bg-white/10 text-yellow-400 border-yellow-400/30 shadow-sm backdrop-blur-sm' 
                            : 'text-gray-300 hover:bg-white/5 hover:text-white'
                    ]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Operating Units
                </Link>
                <Link 
                
                    href="/all-feedback" 
                    :class="[
                        'flex items-center px-4 py-3 rounded-xl font-medium mb-1 transition-all duration-200 border border-transparent',
                        currentUrl.startsWith('/all-feedback') 
                            ? 'bg-white/10 text-yellow-400 border-yellow-400/30 shadow-sm backdrop-blur-sm' 
                            : 'text-gray-300 hover:bg-white/5 hover:text-white'
                    ]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    Feedback Repository
                </Link>

                <Link 
                    href="/test-ai"
                    :class="[
                        'flex items-center px-4 py-3 rounded-xl font-medium mb-1 transition-all duration-200 border border-transparent',
                        currentUrl.startsWith('/test-ai') 
                            ? 'bg-white/10 text-yellow-400 border-yellow-400/30 shadow-sm backdrop-blur-sm' 
                            : 'text-gray-300 hover:bg-white/5 hover:text-white'
                    ]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    Live AI Tester
                </Link>
                
                <div class="mt-8 px-2">
                   <Link 
                       href="/add-csv" 
                       class="flex items-center justify-center gap-2 px-4 py-3 text-emerald-900 bg-yellow-400 rounded-xl shadow-lg hover:bg-yellow-300 hover:shadow-yellow-400/50 w-full text-sm font-bold transition transform hover:-translate-y-0.5"
                   >
                       <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                       </svg>
                       Add CSV File
                   </Link>
                   
                </div>
            </nav>

            <div class="mt-auto pt-6 border-t border-white/10 text-center relative z-10">
                 <div class="flex items-center gap-3 px-2">
                     <div class="w-10 h-10 rounded-full bg-white/20 border border-white/30 flex items-center justify-center shrink-0">
                         <span class="font-bold text-yellow-400">JD</span>
                     </div>
                     <div class="text-left overflow-hidden">
                         <h3 class="font-bold text-white text-sm truncate">Juan Dela Cruz</h3>
                         <p class="text-xs text-gray-400">Administrator</p>
                     </div>
                 </div>
                 <Link 
                    href="/logout" 
                    method="post" 
                    as="button" 
                    class="mt-4 w-full text-xs text-gray-400 hover:text-white bg-black/20 hover:bg-black/30 py-2 rounded-lg transition"
                 >
                    Sign Out
                 </Link>
            </div>
        </aside>

        <main class="flex-1 p-6 h-screen overflow-y-auto">
            <header class="flex justify-between items-center mb-8 pb-4 border-b border-gray-200">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">
                        <span v-if="currentUrl.startsWith('/dashboard')">Overview</span>
                        <span v-else-if="currentUrl.startsWith('/operating-units')">Operating Units</span>
                        <span v-else-if="currentUrl.startsWith('/test-ai')">AI Model Tester</span>
                        <span v-else-if="currentUrl.startsWith('/add-csv')">Data Management</span>
                        <span v-else>Dashboard</span>
                    </h2>
                    <p class="text-sm text-gray-500">Welcome to the Stakeholder Feedback System</p>
                </div>
                <div class="text-sm text-gray-400 italic">
                    {{ new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                </div>
            </header>

            <slot />
        </main>
    </div>
</template>