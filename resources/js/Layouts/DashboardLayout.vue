<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const currentUrl = computed(() => page.url);
const isSidebarOpen = ref(false);

const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
};

const pageTitle = computed(() => {
    if (currentUrl.value.startsWith('/dashboard')) return 'Overview';
    if (currentUrl.value.startsWith('/operating-units')) return 'Operating Units';
    if (currentUrl.value.startsWith('/test-ai')) return 'AI Model Tester';
    if (currentUrl.value.startsWith('/add-csv')) return 'Data Management';
    return 'Dashboard';
});
</script>

<template>
    <div class="flex min-h-screen bg-gray-50 font-calibri text-gray-800 relative">
        
        <Transition name="fade">
            <div 
                v-if="isSidebarOpen" 
                @click="isSidebarOpen = false"
                class="fixed inset-0 bg-black/60 z-40 lg:hidden backdrop-blur-sm"
            ></div>
        </Transition>

        <aside 
            :class="[
                'bg-[#0C4B05] w-72 h-[95vh] fixed lg:sticky top-4 m-4 rounded-2xl flex flex-col py-8 px-5 shadow-2xl z-50 text-white overflow-hidden transition-all duration-300 ease-in-out shrink-0',
                isSidebarOpen ? 'translate-x-0' : '-translate-x-[110%] lg:translate-x-0'
            ]"
        >
            <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-yellow-400 rounded-full opacity-10 blur-3xl pointer-events-none"></div>
            <div class="absolute bottom-0 left-0 -mb-10 -ml-10 w-40 h-40 bg-white rounded-full opacity-5 blur-3xl pointer-events-none"></div>

            <div class="mb-8 flex flex-col items-center text-center relative z-10">
                <div class="w-24 h-24 lg:w-32 lg:h-28 flex items-center justify-center relative group transition-transform duration-500 hover:scale-105">
                    <img 
                        src="/mmsulogo.png" 
                        alt="MMSU Logo" 
                        class="w-full h-full object-contain drop-shadow-xl group-hover:rotate-3 transition-transform duration-700"
                    />
                </div>

                <h1 class="text-sm lg:text-base font-black tracking-[0.12em] text-white leading-tight mt-2 uppercase">
                    Mariano Marcos<br>State University
                </h1>
                
                <p class="text-xs text-yellow-400/80 uppercase tracking-[0.2em] font-bold mt-4 border-t border-white/10 pt-3 w-full">
                    Sentiment Dashboard
                </p>
            </div>

            <nav class="space-y-2 flex-1 relative z-10 overflow-y-auto custom-scrollbar pr-1">
                <Link 
                    v-for="item in [
                        { name: 'Dashboard', href: '/dashboard', icon: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z' },
                        { name: 'Operating Units', href: '/operating-units', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' }
                    ]"
                    :key="item.name"
                    :href="item.href" 
                    @click="isSidebarOpen = false"
                    :class="[
                        'flex items-center px-4 py-3.5 rounded-xl font-semibold transition-all duration-200 group text-[15px]',
                        currentUrl.startsWith(item.href) 
                            ? 'bg-white text-[#0C4B05] shadow-lg' 
                            : 'text-white/80 hover:bg-white/10 hover:text-white'
                    ]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                    </svg>
                    {{ item.name }}
                </Link>

                <div class="mt-8 px-2">
                    <Link 
                        href="/add-csv" 
                        @click="isSidebarOpen = false"
                        class="flex items-center justify-center gap-2 px-4 py-4 text-[#0C4B05] bg-yellow-400 rounded-xl shadow-lg hover:bg-yellow-300 hover:scale-[1.02] active:scale-[0.98] w-full text-base font-bold transition-all"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add CSV File
                    </Link>
                </div>
            </nav>

            <div class="mt-auto pt-6 border-t border-white/10 relative z-10">
                 <div class="flex items-center gap-3 px-2 mb-4">
                     <div class="w-11 h-11 rounded-full bg-white/10 flex items-center justify-center text-yellow-400 font-bold border border-white/10 text-lg">
                         A
                     </div>
                     <div class="text-left overflow-hidden">
                         <h3 class="font-bold text-white text-base truncate leading-tight">MMSU Admin</h3>
                         <p class="text-xs text-white/50 uppercase tracking-tight truncate">Feedback Manager</p>
                     </div>
                 </div>
                 <Link 
                    href="/logout" 
                    method="post" 
                    as="button" 
                    class="w-full text-sm text-white/70 hover:text-white bg-white/5 hover:bg-red-500/20 py-3 rounded-lg transition-all border border-white/5 font-semibold"
                 >
                    Sign Out
                 </Link>
            </div>
        </aside>

        <main class="flex-1 flex flex-col h-screen overflow-hidden">
            <header class="flex-shrink-0 px-8 py-5 bg-white border-b border-gray-200/60 flex items-center justify-between lg:justify-start gap-6 shadow-sm">
                <button 
                    @click="toggleSidebar"
                    class="lg:hidden p-2 rounded-xl text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-[#0C4B05]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12h16M4 6h16M4 18h16" />
                    </svg>
                </button>

                <div class="flex-1 flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl lg:text-3xl font-black text-gray-900 leading-tight">
                            {{ pageTitle }}
                        </h2>
                        <p class="text-sm text-gray-500 hidden sm:block font-medium">Stakeholder Feedback System</p>
                    </div>
                    <div class="text-sm font-semibold text-[#0C4B05] bg-[#0C4B05]/5 px-5 py-2 rounded-full hidden md:block">
                        {{ new Date().toLocaleDateString('en-US', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' }) }}
                    </div>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-6 lg:p-10 custom-scrollbar bg-[#f8fafc]">
                <slot />
            </div>
        </main>
    </div>
</template>

<style scoped>
/* Swapped Garamond for Calibri */
.font-calibri {
    font-family: 'Calibri', 'Candara', 'Segoe UI', 'Optima', 'Arial', sans-serif;
    -webkit-font-smoothing: antialiased;
}

/* Transitions */
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

/* Custom Scrollbar */
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { 
    background: rgba(12, 75, 5, 0.1); 
    border-radius: 10px; 
}
aside .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.2); }
</style>